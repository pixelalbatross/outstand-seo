<?php
/**
 * Resolves the post types Outstand SEO targets.
 *
 * @package OutstandSEO
 */

namespace Outstand\WP\SEO;

/**
 * Single source of truth for which post types get the SEO sidebar, native-meta
 * registration, and the canonical `outstand_seo` REST field.
 */
class PostTypes {

	/**
	 * Targeted post types. Defaults to public, REST-enabled types; site owners
	 * add or remove types via the `outstand_seo_post_types` filter.
	 *
	 * @return string[]
	 */
	public static function get(): array {
		$post_types = get_post_types(
			[
				'public'       => true,
				'show_in_rest' => true,
			]
		);

		/**
		 * Filters the post types Outstand SEO targets — the SEO sidebar, native
		 * meta registration, and the canonical `outstand_seo` REST field.
		 *
		 * @param string[] $post_types Post type slugs.
		 */
		$post_types = (array) apply_filters( 'outstand_seo_post_types', array_values( $post_types ) );

		return array_values( array_unique( array_filter( array_map( 'strval', $post_types ) ) ) );
	}
}
