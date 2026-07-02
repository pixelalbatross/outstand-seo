<?php
/**
 * Shared engine-adapter behaviour.
 *
 * @package OutstandSEO
 */

namespace Outstand\WP\SEO\Engines;

use Outstand\WP\SEO\Engines\Codec\Passthrough;

/**
 * Base adapter: derives normalization, the REST/JS schema, and native-meta
 * registration from each concrete engine's field map. It holds **no** codec
 * logic of its own — every field carries a `CodecInterface` the engine injects,
 * so engines compose reusable primitives (or supply fully custom transformers).
 */
abstract class AbstractEngine implements EngineInterface {

	/**
	 * Fallback focus-keyphrase meta key for engines whose plugin stores no focus
	 * keyword of its own (e.g. The SEO Framework core). Plugin-owned so the value
	 * survives switching between such engines without a migration.
	 *
	 * @var string
	 */
	protected const DEFAULT_FOCUS_KW_KEY = '_outstand_seo_focus_kw';

	/**
	 * {@inheritDoc}
	 *
	 * @param int $post_id Post ID.
	 * @return array<string,mixed>
	 */
	public function normalize( int $post_id ): array {
		$out = [];

		foreach ( $this->get_field_map() as $name => $field ) {
			$raw          = get_post_meta( $post_id, $field['key'], true );
			$out[ $name ] = $field['codec']->decode( $raw );
		}

		$pattern = $this->get_primary_term_key_pattern();
		if ( '' !== $pattern ) {
			$out['primaryTerms'] = $this->read_primary_terms( $pattern, $post_id );
		}

		return $out;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param array<string,mixed> $canonical Canonical value map.
	 * @param int                 $post_id   Post ID.
	 */
	public function denormalize( array $canonical, int $post_id ): void {
		$writes = [];

		foreach ( $this->get_field_map() as $name => $field ) {
			if ( ! array_key_exists( $name, $canonical ) ) {
				continue;
			}

			$key = $field['key'];

			// Thread the accumulating native value so several canonical fields
			// sharing one key (e.g. Yoast's CSV robots-adv) merge cleanly.
			$current = $writes[ $key ] ?? get_post_meta( $post_id, $key, true );

			$writes[ $key ] = $field['codec']->encode( $canonical[ $name ], $current );
		}

		foreach ( $writes as $key => $value ) {
			update_post_meta( $post_id, $key, $value );
		}

		$pattern = $this->get_primary_term_key_pattern();
		if ( '' !== $pattern && isset( $canonical['primaryTerms'] ) && is_array( $canonical['primaryTerms'] ) ) {
			// Only write keys for taxonomies actually attached to this post type,
			// so a crafted request can't create arbitrary `_primary_term_*` meta.
			$allowed = get_object_taxonomies( get_post_type( $post_id ) );
			foreach ( $canonical['primaryTerms'] as $taxonomy => $term_id ) {
				if ( ! in_array( $taxonomy, $allowed, true ) || ! is_taxonomy_hierarchical( $taxonomy ) ) {
					continue;
				}

				update_post_meta( $post_id, sprintf( $pattern, $taxonomy ), (int) $term_id );
			}
		}
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array<string,mixed>
	 */
	public function get_js_config(): array {
		$fields = [];

		foreach ( $this->get_field_map() as $name => $field ) {
			$fields[ $name ] = [ 'kind' => $field['codec']->kind() ];
		}

		return [
			'engine'       => $this->get_slug(),
			'fields'       => $fields,
			'primaryTerms' => '' !== $this->get_primary_term_key_pattern(),
		];
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array<string,mixed>
	 */
	public function get_rest_schema(): array {
		$properties = [];

		foreach ( $this->get_field_map() as $name => $field ) {
			$kind = $field['codec']->kind();
			switch ( $kind ) {
				case 'bool':
					$properties[ $name ] = [ 'type' => 'boolean' ];
					break;
				case 'int':
					$properties[ $name ] = [ 'type' => 'integer' ];
					break;
				default:
					$properties[ $name ] = [ 'type' => 'string' ];
					break;
			}
		}

		if ( '' !== $this->get_primary_term_key_pattern() ) {
			$properties['primaryTerms'] = [
				'type'                 => 'object',
				'additionalProperties' => [ 'type' => 'integer' ],
			];
		}

		return [
			'type'       => 'object',
			// 'edit' only — SEO meta (focus keyword, redirect, robots, social) must
			// not be exposed to anonymous 'view'/'embed' reads. Native keys are
			// registered with show_in_rest => false for the same reason.
			'context'    => [ 'edit' ],
			'properties' => $properties,
		];
	}

	/**
	 * {@inheritDoc}
	 *
	 * Base engines provide no defaults; concrete engines override to expose the
	 * title/description they generate for the current post.
	 *
	 * @param int $post_id Current post ID.
	 * @return array<string,mixed>
	 */
	public function get_editor_defaults( int $post_id ): array {
		return [
			'values'        => [],
			'titleTemplate' => null,
		];
	}

	/**
	 * {@inheritDoc}
	 *
	 * Base engines honor nothing; concrete engines override with what they map.
	 *
	 * @return array<string,bool>
	 */
	public function get_breadcrumb_capabilities(): array {
		return [
			self::BREADCRUMB_SEPARATOR        => false,
			self::BREADCRUMB_SHOW_HOME        => false,
			self::BREADCRUMB_SHOW_CURRENT     => false,
			self::BREADCRUMB_PREFERS_TAXONOMY => false,
			self::BREADCRUMB_SHOW_ON_HOME     => false,
			self::BREADCRUMB_HOME             => false,
		];
	}

	/**
	 * {@inheritDoc}
	 *
	 * Registers every native key referenced by the field map (deduped) and the
	 * per-taxonomy primary-term keys as post meta, without `show_in_rest` — the
	 * editor reads/writes the canonical `outstand_seo` REST field instead.
	 *
	 * @param string[] $post_types Public, REST-enabled post types.
	 */
	public function register_native_meta( array $post_types ): void {

		$keys = [];
		foreach ( $this->get_field_map() as $field ) {
			if ( empty( $field['key'] ) ) {
				continue;
			}

			// Skip keys owned by an external system (e.g. the TSF Focus extension's
			// shared serialized blob) — registering a sanitize_callback on them
			// would corrupt the foreign format on every write.
			if ( isset( $field['register'] ) && false === $field['register'] ) {
				continue;
			}

			// First declaration of a key wins its type (shared CSV keys are strings).
			$keys[ $field['key'] ] ??= $field['type'] ?? 'string';
		}

		$pattern = $this->get_primary_term_key_pattern();

		// A closure tolerates the extra args WordPress passes to a meta
		// sanitize_callback (unlike the internal `intval`), and preserves the
		// sign — TSF stores the permissive robots directive as -1.
		$sanitize_int = static fn( $value ) => (int) $value;

		foreach ( $post_types as $post_type ) {
			foreach ( $keys as $key => $type ) {
				$is_int = 'integer' === $type;
				register_post_meta(
					$post_type,
					$key,
					[
						'type'              => $is_int ? 'integer' : 'string',
						'single'            => true,
						'show_in_rest'      => false,
						'default'           => $is_int ? 0 : '',
						'sanitize_callback' => $is_int ? $sanitize_int : 'sanitize_text_field',
					]
				);
			}

			if ( '' === $pattern ) {
				continue;
			}

			foreach ( get_object_taxonomies( $post_type ) as $taxonomy ) {
				if ( ! is_taxonomy_hierarchical( $taxonomy ) ) {
					continue;
				}

				register_post_meta(
					$post_type,
					sprintf( $pattern, $taxonomy ),
					[
						'type'              => 'integer',
						'single'            => true,
						'show_in_rest'      => false,
						'default'           => 0,
						'sanitize_callback' => 'absint',
					]
				);
			}
		}
	}

	/**
	 * Merge caller args over canonical breadcrumb defaults and cast to the
	 * expected types, so concrete engines can trust the shape.
	 *
	 * @param array<string,mixed> $args Raw block args.
	 * @return array<string,mixed> Normalized args.
	 */
	protected function normalize_breadcrumb_args( array $args ): array {
		return [
			self::BREADCRUMB_SEPARATOR        => (string) ( $args[ self::BREADCRUMB_SEPARATOR ] ?? '/' ),
			self::BREADCRUMB_SHOW_HOME        => (bool) ( $args[ self::BREADCRUMB_SHOW_HOME ] ?? true ),
			self::BREADCRUMB_SHOW_CURRENT     => (bool) ( $args[ self::BREADCRUMB_SHOW_CURRENT ] ?? true ),
			self::BREADCRUMB_PREFERS_TAXONOMY => (bool) ( $args[ self::BREADCRUMB_PREFERS_TAXONOMY ] ?? false ),
			self::BREADCRUMB_SHOW_ON_HOME     => (bool) ( $args[ self::BREADCRUMB_SHOW_ON_HOME ] ?? false ),
			self::BREADCRUMB_HOME             => (string) ( $args[ self::BREADCRUMB_HOME ] ?? '' ),
			self::BREADCRUMB_POST_ID          => (int) ( $args[ self::BREADCRUMB_POST_ID ] ?? 0 ),
		];
	}

	/**
	 * Whether the trail should render in the current request, honoring the
	 * show_on_home arg on the front page.
	 *
	 * @param array<string,mixed> $args Normalized breadcrumb args.
	 * @return bool
	 */
	protected function should_render_breadcrumbs( array $args ): bool {
		if ( is_front_page() && empty( $args[ self::BREADCRUMB_SHOW_ON_HOME ] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Read the primary-term IDs for every hierarchical taxonomy on the post.
	 *
	 * @param string $pattern Primary-term key `sprintf` pattern.
	 * @param int    $post_id Post ID.
	 * @return array<string,int>
	 */
	protected function read_primary_terms( string $pattern, int $post_id ): array {
		$terms = [];

		foreach ( get_object_taxonomies( get_post_type( $post_id ) ) as $taxonomy ) {
			if ( ! is_taxonomy_hierarchical( $taxonomy ) ) {
				continue;
			}

			$terms[ $taxonomy ] = (int) get_post_meta( $post_id, sprintf( $pattern, $taxonomy ), true );
		}

		return $terms;
	}

	/**
	 * Decode HTML entities to plain text for use in editor form fields. Engines
	 * generate defaults for HTML output (e.g. the separator as `&#x2d;`), but the
	 * sidebar shows them in text inputs and counts their characters, so they must
	 * be plain text.
	 *
	 * @param string $value Possibly entity-encoded string.
	 * @return string Plain-text string.
	 */
	protected function decode_entities( string $value ): string {
		return html_entity_decode( $value, ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * Field descriptor for a plain string meta key (passthrough codec).
	 *
	 * @param string $key Native meta key.
	 * @return array<string,mixed>
	 */
	protected function str_field( string $key ): array {
		return [
			'key'   => $key,
			'type'  => 'string',
			'codec' => new Passthrough( 'string' ),
		];
	}

	/**
	 * Field descriptor for a plain integer meta key (passthrough codec).
	 *
	 * @param string $key Native meta key.
	 * @return array<string,mixed>
	 */
	protected function int_field( string $key ): array {
		return [
			'key'   => $key,
			'type'  => 'integer',
			'codec' => new Passthrough( 'int' ),
		];
	}
}
