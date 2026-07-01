<?php
/**
 * Contract for a background SEO engine adapter.
 *
 * An engine adapter knows how to detect its plugin, suppress that plugin's
 * native editor UI, declare a canonical field map, translate between canonical
 * values and its native post meta, describe a schema for the block editor, and
 * render breadcrumbs.
 *
 * @package OutstandSEO
 */

namespace Outstand\WP\SEO\Engines;

/**
 * SEO engine adapter contract.
 */
interface EngineInterface {

	/**
	 * Stable engine slug (e.g. "tsf", "yoast").
	 *
	 * @return string
	 */
	public function get_slug(): string;

	/**
	 * Whether this engine's plugin is active.
	 *
	 * @return bool
	 */
	public function is_active(): bool;

	/**
	 * Suppress the engine's own post-editor UI (metabox and/or block panels).
	 */
	public function disable_native_editor_ui(): void;

	/**
	 * Canonical field map: canonical field name => native descriptor.
	 *
	 * Each descriptor:
	 *  - key   (string)         Native meta key.
	 *  - type  (string)         Native storage type ("string" | "integer"),
	 *                           used only for native-meta registration/sanitize.
	 *  - codec (CodecInterface) The transformer this engine injects for the
	 *                           field (see includes/Engines/Codec/). Its `kind()`
	 *                           drives the JS/REST schema.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function get_field_map(): array;

	/**
	 * `sprintf` pattern for the per-taxonomy primary-term meta key, or '' when
	 * the engine has no primary-term concept.
	 *
	 * @return string
	 */
	public function get_primary_term_key_pattern(): string;

	/**
	 * Register the engine's native post meta keys (not REST-exposed; the editor
	 * reads/writes the canonical `outstand_seo` REST field instead).
	 *
	 * @param string[] $post_types Public, REST-enabled post types.
	 */
	public function register_native_meta( array $post_types ): void;

	/**
	 * Read native meta for a post and return the canonical value map consumed by
	 * the editor (the `outstand_seo` REST field's value).
	 *
	 * @param int $post_id Post ID.
	 * @return array<string,mixed>
	 */
	public function normalize( int $post_id ): array;

	/**
	 * Write a canonical value map back to the engine's native meta.
	 *
	 * @param array<string,mixed> $canonical Canonical value map.
	 * @param int                 $post_id   Post ID.
	 */
	public function denormalize( array $canonical, int $post_id ): void;

	/**
	 * Declarative schema handed to the editor JS as `window.outstandSeo`.
	 *
	 * Shape:
	 *  - engine       (string) Engine slug.
	 *  - fields       (array)  canonical field => { kind }.
	 *  - primaryTerms (bool)   Whether the engine supports primary terms.
	 *
	 * @return array<string,mixed>
	 */
	public function get_js_config(): array;

	/**
	 * REST schema for the canonical `outstand_seo` object field.
	 *
	 * @return array<string,mixed>
	 */
	public function get_rest_schema(): array;

	/**
	 * Per-post default title/description snapshots the engine would generate for
	 * a post whose SEO fields are empty, handed to the editor JS so empty
	 * controls can show the default as a placeholder and count it.
	 *
	 * Shape:
	 *  - values        (array)      canonical field => generated default string.
	 *  - titleTemplate (array|null) { prefix, suffix } wrapping the live post
	 *                               title for real-time title reassembly, or
	 *                               null to use the static `values['title']`.
	 *
	 * @param int $post_id Current post ID.
	 * @return array<string,mixed>
	 */
	public function get_editor_defaults( int $post_id ): array;

	/**
	 * Rendered breadcrumb trail HTML for the active engine, or '' if none.
	 *
	 * @param array<string,mixed> $args Block attributes (e.g. home label).
	 * @return string
	 */
	public function get_breadcrumb_html( array $args ): string;
}
