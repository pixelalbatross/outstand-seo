<?php
/**
 * Editor asset enqueue gating by targeted post type.
 *
 * @package Outstand\WP\SEO\Tests\Unit
 */

namespace Outstand\WP\SEO\Tests\Unit;

use Outstand\WP\SEO\Assets;
use Outstand\WP\SEO\Engines\EngineManager;

/**
 * Test case.
 *
 * @covers \Outstand\WP\SEO\Assets
 */
class AssetsTest extends \WP_UnitTestCase {

	/**
	 * Set up. The gate is only reachable with an active engine.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		if ( null === EngineManager::get_active() ) {
			$this->markTestSkipped( 'No SEO engine active in this environment.' );
		}
	}

	/**
	 * Clean up filters, screen, and any enqueued handle.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		remove_all_filters( 'outstand_seo_post_types' );
		wp_dequeue_script( Assets::HANDLE );
		wp_deregister_script( Assets::HANDLE );
		parent::tear_down();
	}

	/**
	 * Enqueues on a targeted post-type screen.
	 *
	 * @return void
	 */
	public function test_enqueues_on_targeted_post_type(): void {
		set_current_screen( 'post' );
		$post_type = get_current_screen()->post_type;

		if ( ! in_array( $post_type, \Outstand\WP\SEO\PostTypes::get(), true ) ) {
			$this->markTestSkipped( 'Screen post type is not targeted by default.' );
		}

		$assets = new Assets();
		$assets->register();
		$assets->enqueue_editor_assets();

		$this->assertTrue( wp_script_is( Assets::HANDLE, 'enqueued' ) );
	}

	/**
	 * An auto-draft's defaults render the title the editor opens it with.
	 *
	 * @return void
	 */
	public function test_auto_draft_defaults_use_editor_title(): void {
		set_current_screen( 'post' );

		$GLOBALS['post'] = get_post(
			self::factory()->post->create(
				[
					'post_title'  => 'Auto Draft',
					'post_status' => 'auto-draft',
				]
			)
		);

		$GLOBALS['post']->post_title = 'Editor Title';

		$assets = new Assets();
		$assets->register();
		$assets->enqueue_editor_assets();

		$inline = implode( '', (array) wp_scripts()->get_data( Assets::HANDLE, 'before' ) );
		$config = json_decode( (string) preg_replace( '/^window\.outstandSeo = (.*);$/s', '$1', $inline ), true );

		unset( $GLOBALS['post'] );

		$this->assertStringStartsWith( 'Editor Title', $config['defaults']['values']['title'] );
	}


	/**
	 * Skips the enqueue when the screen's post type is filtered out.
	 *
	 * @return void
	 */
	public function test_skips_when_post_type_filtered_out(): void {
		set_current_screen( 'post' );
		$post_type = get_current_screen()->post_type;

		add_filter(
			'outstand_seo_post_types',
			static function ( $types ) use ( $post_type ) {
				return array_values( array_diff( $types, [ $post_type ] ) );
			}
		);

		$assets = new Assets();
		$assets->register();
		$assets->enqueue_editor_assets();

		$this->assertFalse( wp_script_is( Assets::HANDLE, 'enqueued' ) );
	}
}
