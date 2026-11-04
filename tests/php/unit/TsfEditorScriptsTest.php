<?php
/**
 * TSF post editor scripts while its native editor UI is disabled.
 *
 * @package Outstand\WP\SEO\Tests\Unit
 */

namespace Outstand\WP\SEO\Tests\Unit;

use Outstand\WP\SEO\Engines\TSF;

/**
 * Test case.
 *
 * @covers \Outstand\WP\SEO\Engines\TSF
 */
class TsfEditorScriptsTest extends \WP_UnitTestCase {

	/**
	 * Engine under test.
	 *
	 * @var TSF
	 */
	private TSF $engine;

	/**
	 * Set up.
	 */
	public function set_up(): void {
		parent::set_up();

		$this->engine = new TSF();
		$this->engine->disable_native_editor_ui();
	}

	/**
	 * Tear down.
	 */
	public function tear_down(): void {
		remove_filter( 'the_seo_framework_seobox_output', '__return_false' );
		remove_filter( 'the_seo_framework_register_scripts', [ $this->engine, 'skip_post_editor_scripts' ] );
		unset( $GLOBALS['current_screen'] );

		parent::tear_down();
	}

	/**
	 * Post edit screens get no TSF scripts.
	 */
	public function test_post_edit_screen_skips_tsf_scripts(): void {
		set_current_screen( 'post' );

		$this->assertFalse( apply_filters( 'the_seo_framework_register_scripts', true ) );
	}

	/**
	 * Other admin screens keep TSF's decision.
	 */
	public function test_other_screens_keep_tsf_scripts(): void {
		set_current_screen( 'edit-post' );

		$this->assertTrue( apply_filters( 'the_seo_framework_register_scripts', true ) );
		$this->assertFalse( apply_filters( 'the_seo_framework_register_scripts', false ) );
	}

	/**
	 * Without a screen (front end, CLI) TSF's decision stands.
	 */
	public function test_no_screen_keeps_tsf_scripts(): void {
		$this->assertTrue( apply_filters( 'the_seo_framework_register_scripts', true ) );
	}
}
