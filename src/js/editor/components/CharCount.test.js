/**
 * Unit tests for the character-count status bucketing.
 */
import { status, TITLE_RANGE, DESC_RANGE } from './char-status';

describe( 'status', () => {
	it( 'returns empty for zero length', () => {
		expect( status( 0, 30, 60 ) ).toBe( 'empty' );
	} );

	it( 'returns under below the minimum', () => {
		expect( status( 10, 30, 60 ) ).toBe( 'under' );
	} );

	it( 'returns good within the range (inclusive bounds)', () => {
		expect( status( 30, 30, 60 ) ).toBe( 'good' );
		expect( status( 45, 30, 60 ) ).toBe( 'good' );
		expect( status( 60, 30, 60 ) ).toBe( 'good' );
	} );

	it( 'returns over above the maximum', () => {
		expect( status( 61, 30, 60 ) ).toBe( 'over' );
	} );

	it( 'exposes recommended title/description ranges', () => {
		expect( TITLE_RANGE ).toEqual( { min: 30, max: 60 } );
		expect( DESC_RANGE ).toEqual( { min: 120, max: 160 } );
	} );
} );
