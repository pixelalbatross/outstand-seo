<?php
/**
 * Yoast SEO engine adapter.
 *
 * @package OutstandSEO
 */

namespace Outstand\WP\SEO\Engines;

use Outstand\WP\SEO\Engines\Codec\BooleanString;
use Outstand\WP\SEO\Engines\Codec\CsvFlag;
use Outstand\WP\SEO\Engines\Codec\CsvTriState;
use Outstand\WP\SEO\Engines\Codec\TriState;
use Outstand\WP\SEO\Engines\Codec\TwoStateTriState;

/**
 * Adapts Outstand SEO to Yoast SEO (wordpress-seo).
 */
class Yoast extends AbstractEngine {

	/**
	 * {@inheritDoc}
	 */
	public function get_slug(): string {
		return 'yoast';
	}

	/**
	 * {@inheritDoc}
	 */
	public function is_active(): bool {
		return defined( 'WPSEO_VERSION' );
	}

	/**
	 * Disable Yoast's editor surfaces. The `wpseo_enable_editor_features_{pt}`
	 * filter gates both the classic metabox and the block-editor sidebar,
	 * pre-publish, and document panels. Dequeuing the block-editor style is a
	 * belt-and-suspenders for the editor-iframe CSS. Frontend indexable output
	 * reads `_yoast_wpseo_*` postmeta independently, so it is unaffected.
	 */
	public function disable_native_editor_ui(): void {
		add_action( 'init', [ $this, 'filter_editor_features' ], 99 );
		add_action( 'admin_enqueue_scripts', [ $this, 'dequeue_editor_assets' ], 100 );
		add_action( 'enqueue_block_assets', [ $this, 'dequeue_editor_assets' ], 100 );
	}

	/**
	 * Turn off Yoast's editor features for every public post type.
	 */
	public function filter_editor_features(): void {
		foreach ( get_post_types( [ 'public' => true ] ) as $post_type ) {
			add_filter( "wpseo_enable_editor_features_{$post_type}", '__return_false' );
		}
	}

	/**
	 * Dequeue Yoast's editor scripts/styles as a fallback.
	 */
	public function dequeue_editor_assets(): void {
		wp_dequeue_style( 'yoast-seo-block-editor' );
		wp_dequeue_script( 'yoast-seo-post-edit' );
		wp_dequeue_script( 'yoast-seo-block-editor' );
		wp_dequeue_script( 'yoast-seo-post-edit-classic' );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function get_field_map(): array {

		// A boolean token within the shared `_yoast_wpseo_meta-robots-adv` CSV.
		$adv = static fn( string $token ) => [
			'key'   => '_yoast_wpseo_meta-robots-adv',
			'type'  => 'string',
			'codec' => new CsvFlag( $token ),
		];

		return [
			'title'              => $this->str_field( '_yoast_wpseo_title' ),
			'description'        => $this->str_field( '_yoast_wpseo_metadesc' ),
			'canonical'          => $this->str_field( '_yoast_wpseo_canonical' ),
			'focusKw'            => $this->str_field( '_yoast_wpseo_focuskw' ),
			// Yoast noindex: 2 permissive, 0 default, 1 restrictive.
			'noindex'            => [
				'key'   => '_yoast_wpseo_meta-robots-noindex',
				'type'  => 'string',
				'codec' => new TriState( '2', '1', '0' ),
			],
			// Yoast nofollow: only the restrictive '1' persists.
			'nofollow'           => [
				'key'   => '_yoast_wpseo_meta-robots-nofollow',
				'type'  => 'string',
				'codec' => new TwoStateTriState( '1', '0' ),
			],
			// Archiving lives as the `noarchive` token in the shared CSV.
			'noarchive'          => [
				'key'   => '_yoast_wpseo_meta-robots-adv',
				'type'  => 'string',
				'codec' => new CsvTriState( 'noarchive' ),
			],
			'noimageindex'       => $adv( 'noimageindex' ),
			'nosnippet'          => $adv( 'nosnippet' ),
			'cornerstone'        => [
				'key'   => '_yoast_wpseo_is_cornerstone',
				'type'  => 'string',
				'codec' => new BooleanString( '1', 'false' ),
			],
			'redirect'           => $this->str_field( '_yoast_wpseo_redirect' ),
			'ogTitle'            => $this->str_field( '_yoast_wpseo_opengraph-title' ),
			'ogDescription'      => $this->str_field( '_yoast_wpseo_opengraph-description' ),
			'ogImageUrl'         => $this->str_field( '_yoast_wpseo_opengraph-image' ),
			'ogImageId'          => $this->int_field( '_yoast_wpseo_opengraph-image-id' ),
			'twitterTitle'       => $this->str_field( '_yoast_wpseo_twitter-title' ),
			'twitterDescription' => $this->str_field( '_yoast_wpseo_twitter-description' ),
			'twitterImageUrl'    => $this->str_field( '_yoast_wpseo_twitter-image' ),
			'twitterImageId'     => $this->int_field( '_yoast_wpseo_twitter-image-id' ),
		];
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function get_primary_term_key_pattern(): string {
		return '_yoast_wpseo_primary_%s';
	}

	/**
	 * {@inheritDoc}
	 *
	 * Resolves Yoast's per-post-type title/description templates through
	 * `wpseo_replace_vars`, and derives the live title template by splitting the
	 * title format on the `%%title%%` variable. Social defaults fall back to the
	 * SEO title/description, mirroring Yoast's own frontend fallback chain.
	 *
	 * @param int $post_id Current post ID.
	 * @return array<string,mixed>
	 */
	public function get_editor_defaults( int $post_id ): array {

		if ( ! function_exists( 'wpseo_replace_vars' ) || ! class_exists( '\WPSEO_Options' ) ) {
			return [
				'values'        => [],
				'titleTemplate' => null,
			];
		}

		$post      = get_post( $post_id );
		$post_type = get_post_type( $post_id );

		$title_format = (string) \WPSEO_Options::get( "title-{$post_type}", '' );
		$desc_format  = (string) \WPSEO_Options::get( "metadesc-{$post_type}", '' );

		$title       = wpseo_replace_vars( $title_format, $post );
		$description = wpseo_replace_vars( $desc_format, $post );

		$values = [
			'title'              => $title,
			'description'        => $description,
			'ogTitle'            => $title,
			'ogDescription'      => $description,
			'twitterTitle'       => $title,
			'twitterDescription' => $description,
		];

		return [
			'values'        => array_map( [ $this, 'decode_entities' ], $values ),
			'titleTemplate' => $this->build_title_template( $title_format, $post ),
		];
	}

	/**
	 * {@inheritDoc}
	 *
	 * Yoast exposes runtime filters over its crumb list and separator, so every
	 * arg but prefers_taxonomy (whose trail rebuild is too fragile) can be
	 * honored per block instance.
	 *
	 * @return array<string,bool>
	 */
	public function get_breadcrumb_capabilities(): array {
		return [
			self::BREADCRUMB_SEPARATOR        => true,
			self::BREADCRUMB_SHOW_HOME        => true,
			self::BREADCRUMB_SHOW_CURRENT     => true,
			self::BREADCRUMB_HOME             => true,
			self::BREADCRUMB_SHOW_ON_HOME     => true,
			self::BREADCRUMB_PREFERS_TAXONOMY => false,
		];
	}

	/**
	 * {@inheritDoc}
	 *
	 * Wraps yoast_breadcrumb() in transient filters that apply the block's
	 * separator, home label, and home/current visibility, then removes them so
	 * the overrides never leak into other breadcrumb calls.
	 *
	 * @param array<string,mixed> $args Normalized breadcrumb args.
	 * @return string
	 */
	public function get_breadcrumb_html( array $args ): string {
		if ( ! function_exists( 'yoast_breadcrumb' ) ) {
			return '';
		}

		$args = $this->normalize_breadcrumb_args( $args );

		if ( ! $this->should_render_breadcrumbs( $args ) ) {
			return '';
		}

		$separator = static fn() => $args[ self::BREADCRUMB_SEPARATOR ];

		$links = static function ( $crumbs ) use ( $args ) {
			if ( ! is_array( $crumbs ) || empty( $crumbs ) ) {
				return $crumbs;
			}

			$home = $args[ self::BREADCRUMB_HOME ];
			if ( '' !== $home && isset( $crumbs[0]['text'] ) ) {
				$crumbs[0]['text'] = $home;
			}

			if ( ! $args[ self::BREADCRUMB_SHOW_HOME ] ) {
				array_shift( $crumbs );
			}

			if ( ! $args[ self::BREADCRUMB_SHOW_CURRENT ] && ! empty( $crumbs ) ) {
				array_pop( $crumbs );
			}

			return $crumbs;
		};

		add_filter( 'wpseo_breadcrumb_separator', $separator );
		add_filter( 'wpseo_breadcrumb_links', $links );

		$html = (string) yoast_breadcrumb( '', '', false );

		remove_filter( 'wpseo_breadcrumb_separator', $separator );
		remove_filter( 'wpseo_breadcrumb_links', $links );

		return $html;
	}

	/**
	 * Build the { prefix, suffix } that wraps the live post title by splitting
	 * Yoast's title format on the `%%title%%` variable and resolving the
	 * remaining variables in each half. Returns null when the format has no
	 * `%%title%%`, so the caller falls back to the static title snapshot.
	 *
	 * @param string   $title_format Yoast title template (e.g. "%%title%% %%sep%% %%sitename%%").
	 * @param \WP_Post $post         Current post.
	 * @return array{prefix:string,suffix:string}|null
	 */
	private function build_title_template( string $title_format, $post ): ?array {
		$parts      = explode( '%%title%%', $title_format, 2 );
		$part_count = count( $parts );

		if ( 2 !== $part_count ) {
			return null;
		}

		return [
			'prefix' => $this->decode_entities( wpseo_replace_vars( $parts[0], $post ) ),
			'suffix' => $this->decode_entities( wpseo_replace_vars( $parts[1], $post ) ),
		];
	}
}
