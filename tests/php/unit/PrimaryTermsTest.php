<?php
/**
 * Primary-term normalization (canonical `primaryTerms` map).
 *
 * @package Outstand\WP\SEO\Tests\Unit
 */

namespace Outstand\WP\SEO\Tests\Unit;

use Outstand\WP\SEO\Engines\TSF;

/**
 * Test case.
 *
 * @covers \Outstand\WP\SEO\Engines\AbstractEngine
 */
class PrimaryTermsTest extends \WP_UnitTestCase {

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
	 * Primary term round-trips for a hierarchical taxonomy (category).
	 *
	 * @return void
	 */
	public function test_primary_term_round_trip(): void {
		$term_id = self::factory()->category->create();

		$this->engine->denormalize( [ 'primaryTerms' => [ 'category' => $term_id ] ], $this->post_id );
		$this->assertSame(
			$term_id,
			(int) get_post_meta( $this->post_id, '_primary_term_category', true )
		);

		$canonical = $this->engine->normalize( $this->post_id );
		$this->assertSame( $term_id, $canonical['primaryTerms']['category'] );
	}

	/**
	 * Only hierarchical taxonomies appear in the primaryTerms map.
	 *
	 * @return void
	 */
	public function test_only_hierarchical_taxonomies_included(): void {
		$primary = $this->engine->normalize( $this->post_id )['primaryTerms'];

		$this->assertArrayHasKey( 'category', $primary );
		$this->assertArrayNotHasKey( 'post_tag', $primary );
	}
}
