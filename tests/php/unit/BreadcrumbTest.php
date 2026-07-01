<?php
/**
 * Engine breadcrumb output.
 *
 * @package Outstand\WP\SEO\Tests\Unit
 */

namespace Outstand\WP\SEO\Tests\Unit;

use Outstand\WP\SEO\Engines\TSF;
use Outstand\WP\SEO\Engines\Yoast;

/**
 * Test case.
 *
 * @covers \Outstand\WP\SEO\Engines\TSF
 * @covers \Outstand\WP\SEO\Engines\Yoast
 */
class BreadcrumbTest extends \WP_UnitTestCase {

	/**
	 * TSF returns a string; '' when the engine's breadcrumb function is absent.
	 *
	 * @return void
	 */
	public function test_tsf_breadcrumb_html(): void {
		$html = ( new TSF() )->get_breadcrumb_html( [] );

		$this->assertIsString( $html );
		if ( ! function_exists( 'tsf_breadcrumb' ) ) {
			$this->assertSame( '', $html );
		}
	}

	/**
	 * Yoast returns a string; '' when the engine's breadcrumb function is absent.
	 *
	 * @return void
	 */
	public function test_yoast_breadcrumb_html(): void {
		$html = ( new Yoast() )->get_breadcrumb_html( [] );

		$this->assertIsString( $html );
		if ( ! function_exists( 'yoast_breadcrumb' ) ) {
			$this->assertSame( '', $html );
		}
	}
}
