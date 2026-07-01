<?php
/**
 * Engine-generated editor defaults (placeholders/counters). These call the live
 * TSF / Yoast generators, so each case is skipped unless its plugin is active.
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
class EditorDefaultsTest extends \WP_UnitTestCase {

	/**
	 * TSF defaults expose title/description snapshots and a title template.
	 *
	 * @return void
	 */
	public function test_tsf_defaults(): void {
		$engine = new TSF();
		if ( ! $engine->is_active() ) {
			$this->markTestSkipped( 'The SEO Framework is not active.' );
		}

		$post_id  = self::factory()->post->create( [ 'post_title' => 'Hello World' ] );
		$defaults = $engine->get_editor_defaults( $post_id );

		$this->assertArrayHasKey( 'values', $defaults );
		$this->assertArrayHasKey( 'title', $defaults['values'] );
		$this->assertArrayHasKey( 'description', $defaults['values'] );
		$this->assertIsString( $defaults['values']['title'] );

		// titleTemplate is either { prefix, suffix } or null.
		if ( null !== $defaults['titleTemplate'] ) {
			$this->assertArrayHasKey( 'prefix', $defaults['titleTemplate'] );
			$this->assertArrayHasKey( 'suffix', $defaults['titleTemplate'] );
		}
	}

	/**
	 * Yoast defaults resolve templates via wpseo_replace_vars.
	 *
	 * @return void
	 */
	public function test_yoast_defaults(): void {
		$engine = new Yoast();
		if ( ! $engine->is_active() ) {
			$this->markTestSkipped( 'Yoast SEO is not active.' );
		}

		$post_id  = self::factory()->post->create( [ 'post_title' => 'Hello World' ] );
		$defaults = $engine->get_editor_defaults( $post_id );

		$this->assertArrayHasKey( 'values', $defaults );
		$this->assertArrayHasKey( 'title', $defaults['values'] );
		$this->assertIsString( $defaults['values']['title'] );
	}
}
