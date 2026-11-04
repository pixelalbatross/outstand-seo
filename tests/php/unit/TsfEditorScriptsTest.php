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
		remove_filter( 'the_seo_framework_scripts', [ $this->engine, 'keep_primary_term_scripts' ] );
		unset( $GLOBALS['current_screen'] );

		parent::tear_down();
	}

	/**
	 * The block editor gets no TSF scripts.
	 */
	public function test_block_editor_skips_tsf_scripts(): void {
		$this->set_post_screen( true );

		$this->assertFalse( apply_filters( 'the_seo_framework_register_scripts', true ) );
	}

	/**
	 * Classic post edit screens keep TSF's scripts registered.
	 */
	public function test_classic_editor_keeps_tsf_scripts(): void {
		$this->set_post_screen( false );

		$this->assertTrue( apply_filters( 'the_seo_framework_register_scripts', true ) );
	}

	/**
	 * Classic post edit screens load only the primary term selector and what it depends on.
	 */
	public function test_classic_editor_keeps_only_primary_term_scripts(): void {
		$this->set_post_screen( false );

		$ids = array_column( apply_filters( 'the_seo_framework_scripts', $this->scripts() ), 'id' );

		$this->assertSame( [ 'tsf-utils', 'tsf', 'tsf', 'tsf-tt', 'tsf-ays', 'tsf-pt', 'tsf-pt' ], $ids );
	}

	/**
	 * Other screens keep TSF's full script list.
	 */
	public function test_other_screens_keep_all_tsf_scripts(): void {
		set_current_screen( 'edit-post' );

		$this->assertSame( $this->scripts(), apply_filters( 'the_seo_framework_scripts', $this->scripts() ) );
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
		$this->assertSame( $this->scripts(), apply_filters( 'the_seo_framework_scripts', $this->scripts() ) );
	}

	/**
	 * Make the current screen a post edit screen.
	 *
	 * @param bool $block_editor Whether the screen runs the block editor.
	 */
	private function set_post_screen( bool $block_editor ): void {
		set_current_screen( 'post' );
		get_current_screen()->is_block_editor( $block_editor );
	}

	/**
	 * A post edit screen's script list, in the shape TSF passes to `the_seo_framework_scripts`.
	 *
	 * @return array<int,array<string,string>>
	 */
	private function scripts(): array {
		return [
			[
				'id'   => 'tsf-utils',
				'type' => 'js',
			],
			[
				'id'   => 'tsf',
				'type' => 'css',
			],
			[
				'id'   => 'tsf',
				'type' => 'js',
			],
			[
				'id'   => 'tsf-tt',
				'type' => 'js',
			],
			[
				'id'   => 'tsf-post',
				'type' => 'js',
			],
			[
				'id'   => 'tsf-title',
				'type' => 'js',
			],
			[
				'id'   => 'tsf-description',
				'type' => 'js',
			],
			[
				'id'   => 'tsf-ays',
				'type' => 'js',
			],
			[
				'id'   => 'tsf-pt',
				'type' => 'css',
			],
			[
				'id'   => 'tsf-pt',
				'type' => 'js',
			],
		];
	}
}
