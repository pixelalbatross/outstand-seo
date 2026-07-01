<?php
/**
 * Targeted post-type resolution and the `outstand_seo_post_types` filter.
 *
 * @package Outstand\WP\SEO\Tests\Unit
 */

namespace Outstand\WP\SEO\Tests\Unit;

use Outstand\WP\SEO\PostTypes;

/**
 * Test case.
 *
 * @covers \Outstand\WP\SEO\PostTypes
 */
class PostTypesTest extends \WP_UnitTestCase {

	/**
	 * Remove any filters this test added.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		remove_all_filters( 'outstand_seo_post_types' );
		parent::tear_down();
	}

	/**
	 * Defaults to public, REST-enabled post types.
	 *
	 * @return void
	 */
	public function test_defaults_to_public_rest_types(): void {
		$types = PostTypes::get();

		$this->assertContains( 'post', $types );
		$this->assertContains( 'page', $types );
	}

	/**
	 * The filter can add a post type.
	 *
	 * @return void
	 */
	public function test_filter_can_add(): void {
		add_filter(
			'outstand_seo_post_types',
			static function ( $types ) {
				$types[] = 'custom_thing';
				return $types;
			}
		);

		$this->assertContains( 'custom_thing', PostTypes::get() );
	}

	/**
	 * The filter can remove a post type.
	 *
	 * @return void
	 */
	public function test_filter_can_remove(): void {
		add_filter(
			'outstand_seo_post_types',
			static function ( $types ) {
				return array_values( array_diff( $types, [ 'page' ] ) );
			}
		);

		$types = PostTypes::get();
		$this->assertContains( 'post', $types );
		$this->assertNotContains( 'page', $types );
	}

	/**
	 * Output is normalized: unique, string, no empties.
	 *
	 * @return void
	 */
	public function test_output_normalized(): void {
		add_filter(
			'outstand_seo_post_types',
			static function ( $types ) {
				return array_merge( $types, [ 'post', '', 'post' ] );
			}
		);

		$types = PostTypes::get();

		$this->assertSame( array_values( array_unique( $types ) ), $types, 'no duplicates' );
		$this->assertNotContains( '', $types, 'no empties' );
	}
}
