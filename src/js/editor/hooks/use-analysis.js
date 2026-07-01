import { useMemo } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';

import { analyze } from '../analysis/checks';

/**
 * Map a 0-100 score to a coarse rating bucket.
 *
 * @param {number} score Numeric score.
 * @return {('good'|'ok'|'bad')} Rating bucket.
 */
function toRating( score ) {
	if ( score >= 70 ) {
		return 'good';
	}

	if ( score >= 40 ) {
		return 'ok';
	}

	return 'bad';
}

/**
 * Run the lightweight on-page analysis against the current post.
 *
 * @param {Object} input             Canonical field values.
 * @param {string} input.keyphrase   Focus keyphrase.
 * @param {string} input.seoTitle    SEO title override (may be empty).
 * @param {string} input.description Meta description override (may be empty).
 * @return {{score:number, rating:string, checks:Array}} Analysis result.
 */
export function useAnalysis( { keyphrase, seoTitle, description } ) {
	const { content, postTitle, slug } = useSelect( ( select ) => {
		const editor = select( editorStore );
		return {
			content: editor.getEditedPostContent(),
			postTitle: editor.getEditedPostAttribute( 'title' ),
			slug:
				editor.getEditedPostAttribute( 'slug' ) ||
				editor.getEditedPostAttribute( 'generated_slug' ) ||
				'',
		};
	}, [] );

	const effectiveTitle = seoTitle || postTitle || '';

	return useMemo( () => {
		const { score, checks } = analyze( {
			keyphrase,
			seoTitle: effectiveTitle,
			description,
			slug,
			content,
		} );
		return { score, rating: toRating( score ), checks };
	}, [ keyphrase, effectiveTitle, description, slug, content ] );
}
