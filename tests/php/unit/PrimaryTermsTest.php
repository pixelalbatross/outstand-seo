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
	 * Primary term round-trips for a hierarchical taxonomy (category). The term
	 * is assigned to the post, since TSF only reads back an assigned term.
	 *
	 * @return void
	 */
	public function test_primary_term_round_trip(): void {
		$term_id = self::factory()->category->create();

		wp_set_post_categories( $this->post_id, [ 1, $term_id ] );

		$this->engine->denormalize( [ 'primaryTerms' => [ 'category' => $term_id ] ], $this->post_id );
		$this->assertSame(
			$term_id,
			(int) get_post_meta( $this->post_id, '_primary_term_category', true )
		);

		$canonical = $this->engine->normalize( $this->post_id );
		$this->assertSame( $term_id, $canonical['primaryTerms']['category'] );
	}

	/**
	 * Primary terms for non-hierarchical or unattached taxonomies are not saved.
	 *
	 * @return void
	 */
	public function test_primary_term_ignores_disallowed_taxonomies(): void {
		$this->engine->denormalize(
			[
				'primaryTerms' => [
					'post_tag'        => 5,
					'example_missing' => 6,
				],
			],
			$this->post_id
		);

		$this->assertSame( '', get_post_meta( $this->post_id, '_primary_term_post_tag', true ) );
		$this->assertSame( '', get_post_meta( $this->post_id, '_primary_term_example_missing', true ) );
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
