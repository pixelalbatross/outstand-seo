/**
 * Unit tests for the on-page analysis checks.
 */
import { analyze } from './checks';

const byId = ( checks, id ) => checks.find( ( c ) => c.id === id );

describe( 'analyze', () => {
	it( 'flags a missing keyphrase and skips keyphrase checks', () => {
		const { checks, score } = analyze( {
			keyphrase: '',
			seoTitle: 'A title',
			description: 'A description',
			slug: 'a-title',
			content: '<p>Some words here</p>',
		} );

		expect( byId( checks, 'keyphrase' ).status ).toBe( 'bad' );
		expect( byId( checks, 'title' ) ).toBeUndefined();
		expect( typeof score ).toBe( 'number' );
		expect( score ).toBeGreaterThanOrEqual( 0 );
		expect( score ).toBeLessThanOrEqual( 100 );
	} );

	it( 'passes keyphrase checks when the phrase is present everywhere', () => {
		const { checks } = analyze( {
			keyphrase: 'blue widgets',
			seoTitle: 'Best Blue Widgets',
			description: 'All about blue widgets and more.',
			slug: 'best-blue-widgets',
			content: '<p>These blue widgets are great.</p>',
		} );

		expect( byId( checks, 'title' ).status ).toBe( 'good' );
		expect( byId( checks, 'description' ).status ).toBe( 'good' );
		expect( byId( checks, 'body' ).status ).toBe( 'good' );
		// A hyphenated slug can't contain a space-separated phrase, so it stays 'ok'.
		expect( byId( checks, 'slug' ).status ).toBe( 'ok' );
	} );

	it( 'is case-insensitive for keyphrase matching', () => {
		const { checks } = analyze( {
			keyphrase: 'BLUE Widgets',
			seoTitle: 'best blue widgets',
			description: '',
			slug: '',
			content: '',
		} );

		expect( byId( checks, 'title' ).status ).toBe( 'good' );
	} );

	it( 'strips HTML before counting words', () => {
		const { checks } = analyze( {
			keyphrase: '',
			seoTitle: '',
			description: '',
			slug: '',
			content: '<p>one two three four</p>',
		} );

		expect( byId( checks, 'length' ).text ).toContain( '4 words' );
		expect( byId( checks, 'length' ).status ).toBe( 'bad' );
	} );

	it( 'detects links in content', () => {
		const withLink = analyze( {
			keyphrase: '',
			seoTitle: '',
			description: '',
			slug: '',
			content: '<a href="/x">x</a>',
		} );
		const withoutLink = analyze( {
			keyphrase: '',
			seoTitle: '',
			description: '',
			slug: '',
			content: '<p>no links</p>',
		} );

		expect( byId( withLink.checks, 'links' ).status ).toBe( 'good' );
		expect( byId( withoutLink.checks, 'links' ).status ).toBe( 'ok' );
	} );

	it( 'rates title and description length within recommended ranges', () => {
		const good = analyze( {
			keyphrase: '',
			seoTitle: 'x'.repeat( 45 ),
			description: 'y'.repeat( 140 ),
			slug: '',
			content: '',
		} );

		expect( byId( good.checks, 'title-length' ).status ).toBe( 'good' );
		expect( byId( good.checks, 'description-length' ).status ).toBe(
			'good'
		);
	} );

	it( 'returns a perfect score when every check is good', () => {
		// Single-word keyphrase so the hyphenated slug can also match it.
		const longBody =
			'<p>' + 'widgets '.repeat( 300 ) + '<a href="/x">link</a></p>';

		const { score } = analyze( {
			keyphrase: 'widgets',
			seoTitle: 'Widgets ' + 'guide '.repeat( 6 ), // ~43 chars, 30–60.
			description: 'Widgets ' + 'info '.repeat( 28 ), // ~148 chars, 120–160.
			slug: 'widgets',
			content: longBody,
		} );

		expect( score ).toBe( 100 );
	} );
} );
