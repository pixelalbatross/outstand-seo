<?php
/**
 * Normalization round-trips for the TSF engine.
 *
 * @package Outstand\WP\SEO\Tests\Unit
 */

namespace Outstand\WP\SEO\Tests\Unit;

use Outstand\WP\SEO\Engines\TSF;

/**
 * Test case.
 *
 * @covers \Outstand\WP\SEO\Engines\TSF
 * @covers \Outstand\WP\SEO\Engines\AbstractEngine
 */
class TsfNormalizationTest extends \WP_UnitTestCase {

	/**
	 * Engine under test.
	 *
	 * @var TSF
	 */
	private TSF $engine;

	/**
	 * Post fixture.
	 *
	 * @var int
	 */
	private int $post_id;

	/**
	 * Set up.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();
		$this->engine  = new TSF();
		$this->post_id = self::factory()->post->create();
	}

	/**
	 * Passthrough string fields decode/encode verbatim.
	 *
	 * @return void
	 */
	public function test_passthrough_string_round_trip(): void {
		update_post_meta( $this->post_id, '_genesis_title', 'Stored Title' );
		$this->assertSame( 'Stored Title', $this->engine->normalize( $this->post_id )['title'] );

		$this->engine->denormalize( [ 'title' => 'New Title' ], $this->post_id );
		$this->assertSame( 'New Title', get_post_meta( $this->post_id, '_genesis_title', true ) );
	}

	/**
	 * TSF robots tri-state maps -1/0/1 <-> on/default/off.
	 *
	 * @return void
	 */
	public function test_robots_tristate_round_trip(): void {
		$cases = [
			'on'      => -1,
			'off'     => 1,
			'default' => 0,
		];

		foreach ( $cases as $canonical => $native ) {
			$this->engine->denormalize( [ 'noindex' => $canonical ], $this->post_id );
			$this->assertSame(
				$native,
				(int) get_post_meta( $this->post_id, '_genesis_noindex', true ),
				"encode noindex={$canonical}"
			);
			$this->assertSame(
				$canonical,
				$this->engine->normalize( $this->post_id )['noindex'],
				"decode native={$native}"
			);
		}
	}

	/**
	 * Boolean fields map to 0/1 and back to real booleans.
	 *
	 * @return void
	 */
	public function test_bool_round_trip(): void {
		$this->engine->denormalize( [ 'titleNoBlogname' => true ], $this->post_id );
		$this->assertSame( 1, (int) get_post_meta( $this->post_id, '_tsf_title_no_blogname', true ) );
		$this->assertTrue( $this->engine->normalize( $this->post_id )['titleNoBlogname'] );

		$this->engine->denormalize( [ 'titleNoBlogname' => false ], $this->post_id );
		$this->assertFalse( $this->engine->normalize( $this->post_id )['titleNoBlogname'] );
	}

	/**
	 * Integer passthrough (attachment id) casts to int on decode.
	 *
	 * @return void
	 */
	public function test_int_passthrough(): void {
		$this->engine->denormalize( [ 'ogImageId' => 42 ], $this->post_id );
		$this->assertSame( 42, $this->engine->normalize( $this->post_id )['ogImageId'] );
	}

	/**
	 * Normalize exposes every canonical field the engine declares.
	 *
	 * @return void
	 */
	public function test_normalize_returns_all_declared_fields(): void {
		$canonical = $this->engine->normalize( $this->post_id );
		foreach ( array_keys( $this->engine->get_field_map() ) as $field ) {
			$this->assertArrayHasKey( $field, $canonical, "missing {$field}" );
		}
		$this->assertArrayHasKey( 'primaryTerms', $canonical );
	}
}
