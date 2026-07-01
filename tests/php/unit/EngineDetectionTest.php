<?php
/**
 * Engine detection and active-engine resolution.
 *
 * @package Outstand\WP\SEO\Tests\Unit
 */

namespace Outstand\WP\SEO\Tests\Unit;

use Outstand\WP\SEO\Engines\EngineInterface;
use Outstand\WP\SEO\Engines\EngineManager;
use Outstand\WP\SEO\Engines\TSF;
use Outstand\WP\SEO\Engines\Yoast;

/**
 * Test case.
 *
 * @covers \Outstand\WP\SEO\Engines\EngineManager
 * @covers \Outstand\WP\SEO\Engines\TSF
 * @covers \Outstand\WP\SEO\Engines\Yoast
 */
class EngineDetectionTest extends \WP_UnitTestCase {

	/**
	 * The is_active() check reflects the underlying plugin presence check.
	 *
	 * @return void
	 */
	public function test_is_active_matches_plugin_presence(): void {
		$this->assertSame( function_exists( 'tsf' ), ( new TSF() )->is_active() );
		$this->assertSame( defined( 'WPSEO_VERSION' ), ( new Yoast() )->is_active() );
	}

	/**
	 * The get_active() resolver returns the first active engine in priority order (TSF, then
	 * Yoast), or null when none is active.
	 *
	 * @return void
	 */
	public function test_get_active_priority(): void {
		$active = EngineManager::get_active();

		if ( ( new TSF() )->is_active() ) {
			$this->assertInstanceOf( TSF::class, $active );
		} elseif ( ( new Yoast() )->is_active() ) {
			$this->assertInstanceOf( Yoast::class, $active );
		} else {
			$this->assertNull( $active );
		}

		if ( null !== $active ) {
			$this->assertInstanceOf( EngineInterface::class, $active );
		}
	}
}
