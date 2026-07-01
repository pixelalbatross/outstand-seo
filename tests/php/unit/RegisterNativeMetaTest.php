<?php
/**
 * Native meta registration (not REST-exposed).
 *
 * @package Outstand\WP\SEO\Tests\Unit
 */

namespace Outstand\WP\SEO\Tests\Unit;

use Outstand\WP\SEO\Engines\TSF;

/**
 * Test case.
 *
 * @covers \Outstand\WP\SEO\Engines\AbstractEngine
 */
class RegisterNativeMetaTest extends \WP_UnitTestCase {

	/**
	 * Native keys register on the post type WITHOUT show_in_rest.
	 *
	 * @return void
	 */
	public function test_native_keys_registered_without_rest(): void {
		( new TSF() )->register_native_meta( [ 'post' ] );

		$registered = get_registered_meta_keys( 'post' );

		$this->assertArrayHasKey( '_genesis_title', $registered );
		$this->assertFalse( $registered['_genesis_title']['show_in_rest'] );
		$this->assertArrayHasKey( '_genesis_noindex', $registered );

		// The canonical field is a REST field, never a registered meta key.
		$this->assertArrayNotHasKey( 'outstand_seo', $registered );
	}

	/**
	 * Primary-term keys register for hierarchical taxonomies.
	 *
	 * @return void
	 */
	public function test_primary_term_keys_registered(): void {
		( new TSF() )->register_native_meta( [ 'post' ] );

		$registered = get_registered_meta_keys( 'post' );
		$this->assertArrayHasKey( '_primary_term_category', $registered );
	}
}
