<?php
/**
 * The TsfemFocus codec: bidirectional interop with the TSF "Focus" extension's
 * shared, serialized post-meta blob, incl. stale-analysis hardening.
 *
 * @package Outstand\WP\SEO\Tests\Unit
 */

namespace Outstand\WP\SEO\Tests\Unit;

use Outstand\WP\SEO\Engines\Codec\TsfemFocus;

/**
 * Test case.
 *
 * @covers \Outstand\WP\SEO\Engines\Codec\TsfemFocus
 */
class TsfemFocusCodecTest extends \WP_UnitTestCase {

	/**
	 * Codec under test.
	 *
	 * @var TsfemFocus
	 */
	private TsfemFocus $codec;

	/**
	 * Set up.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();
		$this->codec = new TsfemFocus();
	}

	/**
	 * Simulate the value get_post_meta() returns after WP stores encode()'s
	 * output: WP unslashes, re-serializes, then unwraps one layer on read —
	 * net effect is stripslashes() of the encoded string.
	 *
	 * @param string $stored Encoded (addslashed-serialized) value.
	 * @return string
	 */
	private function read_back( string $stored ): string {
		return stripslashes( $stored );
	}

	/**
	 * First write into an empty blob creates the keyword slot and reads back.
	 *
	 * @return void
	 */
	public function test_write_and_read_on_empty_blob(): void {
		$stored = $this->codec->encode( 'my keyword', '' );

		$this->assertSame( 'my keyword', $this->codec->decode( $this->read_back( $stored ) ) );

		$blob = unserialize( $this->read_back( $stored ) ); // phpcs:ignore
		$this->assertSame( 'my keyword', $blob['focus']['kw'][0]['keyword'] );
		$this->assertSame( 0, $blob['focus']['kw'][0]['score'] );
	}

	/**
	 * Decoding empty / non-serialized values yields ''.
	 *
	 * @return void
	 */
	public function test_decode_absent_or_invalid(): void {
		$this->assertSame( '', $this->codec->decode( '' ) );
		$this->assertSame( '', $this->codec->decode( 'not-serialized' ) );
	}

	/**
	 * Writing preserves other extensions' data and other keyword slots, and
	 * resets the changed slot's stale derived analysis.
	 *
	 * @return void
	 */
	public function test_preserves_others_and_resets_stale(): void {
		$existing = [
			'local' => [ 'data' => 'keep me' ],
			'focus' => [
				'kw' => [
					0 => [
						'keyword'         => 'old kw',
						'score'           => 87,
						'scores'          => [ 1, 2, 3 ],
						'inflection_data' => [ 'x' ],
						'synonym_data'    => [ 'y' ],
					],
					1 => [
						'keyword' => 'second slot',
						'score'   => 42,
					],
				],
			],
		];
		// $current mirrors what get_post_meta() returns (plain serialized string).
		$current = serialize( $existing ); // phpcs:ignore

		$stored = $this->codec->encode( 'new kw', $current );
		$blob   = unserialize( $this->read_back( $stored ) ); // phpcs:ignore

		$this->assertSame( 'new kw', $this->codec->decode( $this->read_back( $stored ) ) );
		$this->assertSame( 'keep me', $blob['local']['data'], 'other extension preserved' );
		$this->assertSame( 'second slot', $blob['focus']['kw'][1]['keyword'], 'other kw slot preserved' );
		$this->assertSame( 'new kw', $blob['focus']['kw'][0]['keyword'] );
		$this->assertSame( 0, $blob['focus']['kw'][0]['score'], 'stale score reset' );
		$this->assertSame( [], $blob['focus']['kw'][0]['scores'], 'stale scores cleared' );
		$this->assertSame( [], $blob['focus']['kw'][0]['inflection_data'], 'stale inflections cleared' );
	}

	/**
	 * Re-writing the SAME keyword keeps the extension's derived analysis intact.
	 *
	 * @return void
	 */
	public function test_unchanged_keyword_keeps_derived_data(): void {
		$existing = [
			'focus' => [
				'kw' => [
					0 => [
						'keyword' => 'same kw',
						'score'   => 87,
					],
				],
			],
		];
		$current = serialize( $existing ); // phpcs:ignore

		$stored = $this->codec->encode( 'same kw', $current );
		$blob   = unserialize( $this->read_back( $stored ) ); // phpcs:ignore

		$this->assertSame( 87, $blob['focus']['kw'][0]['score'] );
	}
}
