<?php
/**
 * Asset-manifest resolution trait.
 *
 * @package Outstand\WP\SEO\Tests\Unit
 */

namespace Outstand\WP\SEO\Tests\Unit;

use Outstand\WP\SEO\GetAssetInfo;

/**
 * Test case.
 *
 * @covers \Outstand\WP\SEO\GetAssetInfo
 */
class GetAssetInfoTest extends \WP_UnitTestCase {

	/**
	 * A fresh consumer of the trait.
	 *
	 * @return object
	 */
	private function consumer() {
		return new class() {
			use GetAssetInfo;
		};
	}

	/**
	 * Reads version and dependencies from a real build manifest.
	 *
	 * @return void
	 */
	public function test_reads_from_manifest(): void {
		$obj = $this->consumer();
		$obj->setup_asset_vars( OUTSTAND_SEO_DIST_PATH, 'fallback-1.0' );

		$this->assertIsString( $obj->get_asset_info( 'editor', 'version' ) );
		$this->assertNotSame( '', $obj->get_asset_info( 'editor', 'version' ) );
		$this->assertIsArray( $obj->get_asset_info( 'editor', 'dependencies' ) );

		$all = $obj->get_asset_info( 'editor' );
		$this->assertArrayHasKey( 'version', $all );
		$this->assertArrayHasKey( 'dependencies', $all );
	}

	/**
	 * Falls back to the given version when the manifest is missing.
	 *
	 * @return void
	 */
	public function test_falls_back_when_missing(): void {
		$obj = $this->consumer();
		$obj->setup_asset_vars( OUTSTAND_SEO_DIST_PATH, 'fallback-1.0' );

		$this->assertSame( 'fallback-1.0', $obj->get_asset_info( 'does-not-exist', 'version' ) );
		$this->assertSame( [], $obj->get_asset_info( 'does-not-exist', 'dependencies' ) );
	}

	/**
	 * Throws when asset vars were never set up.
	 *
	 * @return void
	 */
	public function test_throws_without_setup(): void {
		$this->expectException( \RuntimeException::class );
		$this->consumer()->get_asset_info( 'editor' );
	}
}
