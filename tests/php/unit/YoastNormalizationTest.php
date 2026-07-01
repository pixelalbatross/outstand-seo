<?php
/**
 * Normalization round-trips for the Yoast engine, incl. the shared robots-adv key.
 *
 * @package Outstand\WP\SEO\Tests\Unit
 */

namespace Outstand\WP\SEO\Tests\Unit;

use Outstand\WP\SEO\Engines\Yoast;

/**
 * Test case.
 *
 * @covers \Outstand\WP\SEO\Engines\Yoast
 * @covers \Outstand\WP\SEO\Engines\AbstractEngine
 */
class YoastNormalizationTest extends \WP_UnitTestCase {

	/**
	 * Engine under test.
	 *
	 * @var Yoast
	 */
	private Yoast $engine;

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
		$this->engine  = new Yoast();
		$this->post_id = self::factory()->post->create();
	}

	/**
	 * Yoast noindex tri-state maps 2/0/1 <-> on/default/off.
	 *
	 * @return void
	 */
	public function test_noindex_tristate_round_trip(): void {
		$cases = [
			'on'      => '2',
			'off'     => '1',
			'default' => '0',
		];

		foreach ( $cases as $canonical => $native ) {
			$this->engine->denormalize( [ 'noindex' => $canonical ], $this->post_id );
			$this->assertSame( $native, get_post_meta( $this->post_id, '_yoast_wpseo_meta-robots-noindex', true ) );
			$this->assertSame( $canonical, $this->engine->normalize( $this->post_id )['noindex'] );
		}
	}

	/**
	 * Yoast nofollow is two-state: only 'off' persists, 'on'/'default' clear it.
	 *
	 * @return void
	 */
	public function test_nofollow_two_state(): void {
		$this->engine->denormalize( [ 'nofollow' => 'off' ], $this->post_id );
		$this->assertSame( '1', get_post_meta( $this->post_id, '_yoast_wpseo_meta-robots-nofollow', true ) );
		$this->assertSame( 'off', $this->engine->normalize( $this->post_id )['nofollow'] );

		$this->engine->denormalize( [ 'nofollow' => 'on' ], $this->post_id );
		$this->assertSame( 'default', $this->engine->normalize( $this->post_id )['nofollow'] );
	}

	/**
	 * Cornerstone maps '1'/'false'.
	 *
	 * @return void
	 */
	public function test_cornerstone_round_trip(): void {
		$this->engine->denormalize( [ 'cornerstone' => true ], $this->post_id );
		$this->assertSame( '1', get_post_meta( $this->post_id, '_yoast_wpseo_is_cornerstone', true ) );
		$this->assertTrue( $this->engine->normalize( $this->post_id )['cornerstone'] );

		$this->engine->denormalize( [ 'cornerstone' => false ], $this->post_id );
		$this->assertSame( 'false', get_post_meta( $this->post_id, '_yoast_wpseo_is_cornerstone', true ) );
	}

	/**
	 * The noarchive + noimageindex + nosnippet share `_yoast_wpseo_meta-robots-adv`;
	 * writing all three merges their tokens into the one CSV value.
	 *
	 * @return void
	 */
	public function test_shared_csv_key_merges_tokens(): void {
		$this->engine->denormalize(
			[
				'noarchive'    => 'off',
				'noimageindex' => true,
				'nosnippet'    => true,
			],
			$this->post_id
		);

		$tokens = explode( ',', get_post_meta( $this->post_id, '_yoast_wpseo_meta-robots-adv', true ) );
		$this->assertContains( 'noarchive', $tokens );
		$this->assertContains( 'noimageindex', $tokens );
		$this->assertContains( 'nosnippet', $tokens );

		$canonical = $this->engine->normalize( $this->post_id );
		$this->assertSame( 'off', $canonical['noarchive'] );
		$this->assertTrue( $canonical['noimageindex'] );
		$this->assertTrue( $canonical['nosnippet'] );
	}

	/**
	 * Clearing one token on the shared key preserves the others.
	 *
	 * @return void
	 */
	public function test_shared_csv_key_preserves_other_tokens(): void {
		update_post_meta( $this->post_id, '_yoast_wpseo_meta-robots-adv', 'noimageindex,nosnippet' );

		// Turn archiving restrictive off -> should NOT drop the other two tokens.
		$this->engine->denormalize( [ 'noarchive' => 'default' ], $this->post_id );

		$tokens = explode( ',', get_post_meta( $this->post_id, '_yoast_wpseo_meta-robots-adv', true ) );
		$this->assertContains( 'noimageindex', $tokens );
		$this->assertContains( 'nosnippet', $tokens );
		$this->assertNotContains( 'noarchive', $tokens );
	}
}
