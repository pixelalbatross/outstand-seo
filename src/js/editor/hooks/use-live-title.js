import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';

import { getDefault, TITLE_TEMPLATE } from '../config';

/**
 * The default meta title for the current post, reassembled live from the post
 * title as the user types (mirrors The SEO Framework). When the active engine
 * exposes no title template, falls back to the static server snapshot.
 *
 * @return {string} The default title.
 */
export function useTitleDefault() {
	const postTitle = useSelect(
		( select ) => select( editorStore ).getEditedPostAttribute( 'title' ),
		[]
	);

	if ( ! TITLE_TEMPLATE ) {
		return getDefault( 'title' );
	}

	const base = postTitle || getDefault( 'title' );

	return `${ TITLE_TEMPLATE.prefix }${ base }${ TITLE_TEMPLATE.suffix }`;
}
