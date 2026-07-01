<?php
/**
 * TSF focus-keyword field binding: plugin-owned scalar by default, or the TSF
 * "Focus" extension's shared blob when that extension is active.
 *
 * @package Outstand\WP\SEO\Tests\Unit
 */

namespace Outstand\WP\SEO\Tests\Unit;

use Outstand\WP\SEO\Engines\Codec\Passthrough;
use Outstand\WP\SEO\Engines\Codec\TsfemFocus;
use Outstand\WP\SEO\Engines\TSF;

/**
 * Test case.
 *
 * @covers \Outstand\WP\SEO\Engines\TSF
 */
class FocusFieldDetectionTest extends \WP_UnitTestCase {

	/**
	 * Without the Focus extension, focusKw binds to the plugin-owned scalar key.
	 *
	 * @return void
	 */
	public function test_default_scalar_binding(): void {
		if ( defined( 'TSFEM_E_FOCUS_VERSION' ) ) {
			$this->markTestSkipped( 'Focus extension is active in this environment.' );
		}

		$field = ( new TSF() )->get_field_map()['focusKw'];

		$this->assertSame( '_outstand_seo_focus_kw', $field['key'] );
		$this->assertInstanceOf( Passthrough::class, $field['codec'] );
	}

	/**
	 * With the Focus extension active, focusKw binds to the shared TSFEM blob via
	 * the tsfemFocus codec. Runs isolated so the defined constant doesn't leak.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @return void
	 */
	public function test_focus_extension_binding(): void {
		if ( ! defined( 'TSFEM_E_FOCUS_VERSION' ) ) {
			define( 'TSFEM_E_FOCUS_VERSION', '1.6.0' );
		}

		$field = ( new TSF() )->get_field_map()['focusKw'];

		$this->assertSame( '_tsfem-extension-post-meta', $field['key'] );
		$this->assertInstanceOf( TsfemFocus::class, $field['codec'] );
	}
}
