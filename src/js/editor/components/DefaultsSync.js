import apiFetch from '@wordpress/api-fetch';
import { useDispatch, useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { useCallback, useEffect, useRef } from '@wordpress/element';

import { useSeoData } from '../hooks/use-meta';
import { store as defaultsStore } from '../store';

/**
 * Milliseconds the editor state has to stay unchanged before the engine
 * recomputes its rendered values.
 */
export const SYNC_DELAY = 500;

/**
 * Keeps the defaults store in step with the unsaved editor state: whenever the
 * post title or the SEO values change, and after each save, the active engine
 * renders the titles and descriptions again from that state. One request runs
 * at a time; a state that changes meanwhile is sent once it settles.
 *
 * @return {null} Renders nothing.
 */
export default function DefaultsSync() {
	const { postId, postTitle, isSaving } = useSelect( ( select ) => {
		const editor = select( editorStore );

		return {
			postId: editor.getCurrentPostId(),
			postTitle: editor.getEditedPostAttribute( 'title' ),
			isSaving: editor.isSavingPost() && ! editor.isAutosavingPost(),
		};
	}, [] );
	const [ values ] = useSeoData();
	const { setDefaults } = useDispatch( defaultsStore );
	const latestState = useRef();
	const isInFlight = useRef( false );
	const hasPending = useRef( false );
	const isInitial = useRef( true );
	const wasSaving = useRef( false );

	latestState.current = { postId, postTitle, values };

	// The values object is rebuilt on every render, so its serialized form is
	// what tells an edit apart.
	const valuesKey = JSON.stringify( values );

	const sync = useCallback( () => {
		if ( isInFlight.current ) {
			hasPending.current = true;
			return;
		}

		const state = latestState.current;
		isInFlight.current = true;

		apiFetch( {
			path: `/outstand-seo/v1/defaults/${ state.postId }`,
			method: 'POST',
			data: { postTitle: state.postTitle, values: state.values },
		} )
			.then( ( response ) => setDefaults( response.values ) )
			// A failed request keeps the last rendered values.
			.catch( () => {} )
			.finally( () => {
				isInFlight.current = false;

				if ( hasPending.current ) {
					hasPending.current = false;
					sync();
				}
			} );
	}, [ setDefaults ] );

	useEffect( () => {
		// The store starts from the values rendered for the saved post.
		if ( isInitial.current ) {
			isInitial.current = false;
			return;
		}

		const timer = setTimeout( sync, SYNC_DELAY );

		return () => clearTimeout( timer );
	}, [ postId, postTitle, valuesKey, sync ] );

	useEffect( () => {
		if ( wasSaving.current && ! isSaving ) {
			sync();
		}

		wasSaving.current = isSaving;
	}, [ isSaving, sync ] );

	return null;
}
