<?php
/**
 * Direct unit tests for the reusable value codecs.
 *
 * @package Outstand\WP\SEO\Tests\Unit
 */

namespace Outstand\WP\SEO\Tests\Unit;

use Outstand\WP\SEO\Engines\Codec\BooleanInt;
use Outstand\WP\SEO\Engines\Codec\BooleanString;
use Outstand\WP\SEO\Engines\Codec\CsvFlag;
use Outstand\WP\SEO\Engines\Codec\CsvTriState;
use Outstand\WP\SEO\Engines\Codec\Passthrough;
use Outstand\WP\SEO\Engines\Codec\TriState;
use Outstand\WP\SEO\Engines\Codec\TwoStateTriState;

/**
 * Test case.
 *
 * @covers \Outstand\WP\SEO\Engines\Codec\Passthrough
 * @covers \Outstand\WP\SEO\Engines\Codec\BooleanInt
 * @covers \Outstand\WP\SEO\Engines\Codec\BooleanString
 * @covers \Outstand\WP\SEO\Engines\Codec\TriState
 * @covers \Outstand\WP\SEO\Engines\Codec\TwoStateTriState
 * @covers \Outstand\WP\SEO\Engines\Codec\CsvFlag
 * @covers \Outstand\WP\SEO\Engines\Codec\CsvTriState
 */
class CodecTest extends \WP_UnitTestCase {

	/**
	 * Passthrough string: verbatim, null/absent -> ''.
	 *
	 * @return void
	 */
	public function test_passthrough_string(): void {
		$c = new Passthrough( 'string' );

		$this->assertSame( 'string', $c->kind() );
		$this->assertSame( '', $c->decode( null ) );
		$this->assertSame( 'hi', $c->decode( 'hi' ) );
		$this->assertSame( 'out', $c->encode( 'out', 'ignored' ) );
	}

	/**
	 * Passthrough int: casts on both directions.
	 *
	 * @return void
	 */
	public function test_passthrough_int(): void {
		$c = new Passthrough( 'int' );

		$this->assertSame( 'int', $c->kind() );
		$this->assertSame( 42, $c->decode( '42' ) );
		$this->assertSame( 0, $c->decode( '' ) );
		$this->assertSame( 7, $c->encode( '7', null ) );
	}

	/**
	 * BooleanInt: 0/1 <-> bool.
	 *
	 * @return void
	 */
	public function test_boolean_int(): void {
		$c = new BooleanInt();

		$this->assertSame( 'bool', $c->kind() );
		$this->assertTrue( $c->decode( '1' ) );
		$this->assertFalse( $c->decode( '0' ) );
		$this->assertFalse( $c->decode( '' ) );
		$this->assertSame( 1, $c->encode( true, null ) );
		$this->assertSame( 0, $c->encode( false, null ) );
	}

	/**
	 * BooleanString: configurable literals (Yoast cornerstone).
	 *
	 * @return void
	 */
	public function test_boolean_string(): void {
		$c = new BooleanString( '1', 'false' );

		$this->assertSame( 'bool', $c->kind() );
		$this->assertTrue( $c->decode( '1' ) );
		$this->assertFalse( $c->decode( 'false' ) );
		$this->assertFalse( $c->decode( '' ) );
		$this->assertSame( '1', $c->encode( true, null ) );
		$this->assertSame( 'false', $c->encode( false, null ) );
	}

	/**
	 * TriState: full three-way mapping, both TSF and Yoast literals.
	 *
	 * @return void
	 */
	public function test_tri_state(): void {
		$tsf = new TriState( -1, 1, 0 );

		$this->assertSame( 'robotsTri', $tsf->kind() );
		$this->assertSame( 'on', $tsf->decode( -1 ) );
		$this->assertSame( 'off', $tsf->decode( 1 ) );
		$this->assertSame( 'default', $tsf->decode( 0 ) );
		$this->assertSame( 'default', $tsf->decode( '' ) );
		$this->assertSame( -1, $tsf->encode( 'on', null ) );
		$this->assertSame( 1, $tsf->encode( 'off', null ) );
		$this->assertSame( 0, $tsf->encode( 'default', null ) );

		$yoast = new TriState( '2', '1', '0' );
		$this->assertSame( 'on', $yoast->decode( '2' ) );
		$this->assertSame( '2', $yoast->encode( 'on', null ) );
	}

	/**
	 * TwoStateTriState: only 'off' persists; 'on' and 'default' collapse.
	 *
	 * @return void
	 */
	public function test_two_state_tri_state(): void {
		$c = new TwoStateTriState( '1', '0' );

		$this->assertSame( 'robotsTri', $c->kind() );
		$this->assertSame( 'off', $c->decode( '1' ) );
		$this->assertSame( 'default', $c->decode( '0' ) );
		$this->assertSame( 'default', $c->decode( '' ) );
		$this->assertSame( '1', $c->encode( 'off', null ) );
		$this->assertSame( '0', $c->encode( 'on', null ) );
		$this->assertSame( '0', $c->encode( 'default', null ) );
	}

	/**
	 * CsvFlag: presence of a token in a shared CSV, preserving siblings.
	 *
	 * @return void
	 */
	public function test_csv_flag(): void {
		$c = new CsvFlag( 'noimageindex' );

		$this->assertSame( 'bool', $c->kind() );
		$this->assertTrue( $c->decode( 'a,noimageindex,b' ) );
		$this->assertFalse( $c->decode( 'a,b' ) );
		$this->assertFalse( $c->decode( '' ) );

		$this->assertSame( 'a,noimageindex', $c->encode( true, 'a' ) );
		$this->assertSame( 'a', $c->encode( false, 'a,noimageindex' ) );
		// Idempotent add.
		$this->assertSame( 'noimageindex', $c->encode( true, 'noimageindex' ) );
	}

	/**
	 * CsvTriState: 'off' == token present; 'on'/'default' == absent.
	 *
	 * @return void
	 */
	public function test_csv_tri_state(): void {
		$c = new CsvTriState( 'noarchive' );

		$this->assertSame( 'robotsTri', $c->kind() );
		$this->assertSame( 'off', $c->decode( 'noarchive' ) );
		$this->assertSame( 'default', $c->decode( '' ) );
		$this->assertSame( 'x,noarchive', $c->encode( 'off', 'x' ) );
		$this->assertSame( 'x', $c->encode( 'default', 'x,noarchive' ) );
		$this->assertSame( 'x', $c->encode( 'on', 'x,noarchive' ) );
	}

	/**
	 * Several codecs sharing one CSV value merge their tokens when threaded.
	 *
	 * @return void
	 */
	public function test_shared_csv_threading(): void {
		$current = '';
		$current = ( new CsvTriState( 'noarchive' ) )->encode( 'off', $current );
		$current = ( new CsvFlag( 'noimageindex' ) )->encode( true, $current );
		$current = ( new CsvFlag( 'nosnippet' ) )->encode( true, $current );

		$tokens = explode( ',', $current );
		$this->assertContains( 'noarchive', $tokens );
		$this->assertContains( 'noimageindex', $tokens );
		$this->assertContains( 'nosnippet', $tokens );
	}
}
