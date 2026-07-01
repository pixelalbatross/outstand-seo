import { __, sprintf } from '@wordpress/i18n';

/**
 * Strip HTML to plain text for word/keyphrase analysis.
 *
 * @param {string} html Raw post content.
 * @return {string} Plain text.
 */
function toText( html ) {
	if ( ! html ) {
		return '';
	}

	const doc = document.implementation.createHTMLDocument( '' );
	doc.body.innerHTML = html;
	return ( doc.body.textContent || '' ).replace( /\s+/g, ' ' ).trim();
}

const includesPhrase = ( haystack, phrase ) =>
	!! phrase && haystack.toLowerCase().includes( phrase.toLowerCase() );

/**
 * Run lightweight on-page SEO checks. Pure and synchronous — cheap enough to
 * run on the main thread without a worker.
 *
 * @param {Object} input              Analysis input.
 * @param {string} input.keyphrase    Focus keyphrase.
 * @param {string} input.seoTitle     Effective SEO/meta title.
 * @param {string} input.description  Effective meta description.
 * @param {string} input.slug         Post slug.
 * @param {string} input.content      Raw post content (HTML).
 * @return {{score:number, checks:Array}} Score (0-100) and per-check results.
 */
export function analyze( { keyphrase, seoTitle, description, slug, content } ) {
	const text = toText( content );
	const words = text ? text.split( ' ' ).filter( Boolean ) : [];
	const wordCount = words.length;
	const hasLink = /<a[\s>]/i.test( content || '' );

	const checks = [];

	if ( ! keyphrase ) {
		checks.push( {
			id: 'keyphrase',
			status: 'bad',
			text: __( 'Set a focus keyphrase to run checks.', 'outstand-seo' ),
		} );
	} else {
		checks.push( {
			id: 'title',
			status: includesPhrase( seoTitle, keyphrase ) ? 'good' : 'bad',
			text: __( 'Keyphrase appears in the SEO title.', 'outstand-seo' ),
		} );
		checks.push( {
			id: 'description',
			status: includesPhrase( description, keyphrase ) ? 'good' : 'ok',
			text: __(
				'Keyphrase appears in the meta description.',
				'outstand-seo'
			),
		} );
		checks.push( {
			id: 'slug',
			status: includesPhrase( slug, keyphrase ) ? 'good' : 'ok',
			text: __( 'Keyphrase appears in the URL slug.', 'outstand-seo' ),
		} );
		checks.push( {
			id: 'body',
			status: includesPhrase( text, keyphrase ) ? 'good' : 'bad',
			text: __( 'Keyphrase appears in the content.', 'outstand-seo' ),
		} );
	}

	checks.push( {
		id: 'length',
		status: wordCount >= 300 ? 'good' : wordCount >= 150 ? 'ok' : 'bad',
		text: sprintf(
			/* translators: %d: word count. */
			__( 'Content length: %d words (aim for 300+).', 'outstand-seo' ),
			wordCount
		),
	} );

	const titleLen = ( seoTitle || '' ).length;
	checks.push( {
		id: 'title-length',
		status: titleLen >= 30 && titleLen <= 60 ? 'good' : 'ok',
		text: sprintf(
			/* translators: %d: character count. */
			__( 'SEO title length: %d chars (30–60).', 'outstand-seo' ),
			titleLen
		),
	} );

	const descLen = ( description || '' ).length;
	checks.push( {
		id: 'description-length',
		status: descLen >= 120 && descLen <= 160 ? 'good' : 'ok',
		text: sprintf(
			/* translators: %d: character count. */
			__( 'Meta description length: %d chars (120–160).', 'outstand-seo' ),
			descLen
		),
	} );

	checks.push( {
		id: 'links',
		status: hasLink ? 'good' : 'ok',
		text: __( 'Content contains at least one link.', 'outstand-seo' ),
	} );

	const weight = { good: 1, ok: 0.5, bad: 0 };
	const score = checks.length
		? Math.round(
				( checks.reduce( ( sum, c ) => sum + weight[ c.status ], 0 ) /
					checks.length ) *
					100
		  )
		: 0;

	return { score, checks };
}
