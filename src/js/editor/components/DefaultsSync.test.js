/**
 * Unit tests for the defaults sync.
 */
import apiFetch from '@wordpress/api-fetch';
import { createRoot } from '@wordpress/element';
// eslint-disable-next-line import/no-extraneous-dependencies -- React ships with @wordpress/scripts; @wordpress/element has no act().
import { act } from 'react';

import { useSeoData } from '../hooks/use-meta';
import DefaultsSync, { SYNC_DELAY } from './DefaultsSync';

const mockSetDefaults = jest.fn();
let mockEditor = {};

// The editor packages are webpack externals, so they are mocked as virtual modules.
jest.mock( '@wordpress/api-fetch', () => jest.fn(), { virtual: true } );
jest.mock(
	'@wordpress/data',
	() => ( {
		useSelect: ( selector ) =>
			selector( () => ( {
				getCurrentPostId: () => mockEditor.postId,
				getEditedPostAttribute: () => mockEditor.postTitle,
				isSavingPost: () => mockEditor.isSaving,
				isAutosavingPost: () => mockEditor.isAutosaving,
			} ) ),
		useDispatch: () => ( { setDefaults: mockSetDefaults } ),
	} ),
	{ virtual: true }
);
jest.mock( '@wordpress/editor', () => ( { store: 'core/editor' } ), {
	virtual: true,
} );
jest.mock( '../hooks/use-meta', () => ( { useSeoData: jest.fn() } ) );
jest.mock( '../store', () => ( { store: 'outstand-seo/defaults' } ) );

global.IS_REACT_ACT_ENVIRONMENT = true;

let root;

/**
 * Render the sync with the given editor state.
 *
 * @param {Object}  state              Editor state.
 * @param {string}  state.postTitle    Edited post title.
 * @param {Object}  state.values       Edited SEO values.
 * @param {boolean} state.isSaving     Whether a save is running.
 * @param {boolean} state.isAutosaving Whether that save is an autosave.
 */
function render( {
	postTitle = 'Hello World',
	values = {},
	isSaving = false,
	isAutosaving = false,
} = {} ) {
	mockEditor = { postId: 7, postTitle, isSaving, isAutosaving };
	useSeoData.mockReturnValue( [ values ] );

	act( () => {
		root.render( <DefaultsSync /> );
	} );
}

/**
 * Let the sync delay pass and pending promises settle.
 */
async function settle() {
	await act( async () => {
		jest.advanceTimersByTime( SYNC_DELAY );
	} );
}

/**
 * A request that resolves only when the returned function is called.
 *
 * @return {Function} Resolves the request with the given values.
 */
function deferRequest() {
	let resolve;
	apiFetch.mockImplementationOnce(
		() =>
			new Promise( ( done ) => {
				resolve = done;
			} )
	);

	return async ( values ) => {
		await act( async () => {
			resolve( { values } );
		} );
	};
}

describe( 'DefaultsSync', () => {
	beforeEach( () => {
		jest.useFakeTimers();
		root = createRoot( document.createElement( 'div' ) );
	} );

	afterEach( () => {
		act( () => root.unmount() );
		jest.useRealTimers();
		apiFetch.mockReset();
		mockSetDefaults.mockReset();
	} );

	it( 'keeps the values rendered for the saved post on first render', async () => {
		render();
		await settle();

		expect( apiFetch ).not.toHaveBeenCalled();
	} );

	it( 'renders the edited state once it stops changing', async () => {
		apiFetch.mockResolvedValue( { values: { title: 'Edited Title' } } );

		render();
		render( { postTitle: 'Edited' } );
		render( {
			postTitle: 'Edited Title',
			values: { description: 'Custom description' },
		} );
		await settle();

		expect( apiFetch ).toHaveBeenCalledTimes( 1 );
		expect( apiFetch ).toHaveBeenCalledWith( {
			path: '/outstand-seo/v1/defaults/7',
			method: 'POST',
			data: {
				postTitle: 'Edited Title',
				values: { description: 'Custom description' },
			},
		} );
		expect( mockSetDefaults ).toHaveBeenCalledWith( {
			title: 'Edited Title',
		} );
	} );

	it( 'sends edits made during a request once it settles', async () => {
		const resolveFirst = deferRequest();
		apiFetch.mockResolvedValueOnce( { values: { title: 'Third' } } );

		render();
		render( { postTitle: 'First' } );
		await settle();
		render( { postTitle: 'Second' } );
		await settle();
		render( { postTitle: 'Third' } );
		await settle();

		expect( apiFetch ).toHaveBeenCalledTimes( 1 );

		await resolveFirst( { title: 'First' } );

		expect( apiFetch ).toHaveBeenCalledTimes( 2 );
		expect( apiFetch.mock.calls[ 1 ][ 0 ].data.postTitle ).toBe( 'Third' );
		expect( mockSetDefaults ).toHaveBeenLastCalledWith( {
			title: 'Third',
		} );
	} );

	it( 'keeps the last values when a request fails', async () => {
		apiFetch.mockRejectedValue( new Error( 'Request failed' ) );

		render();
		render( { postTitle: 'Edited Title' } );
		await settle();

		expect( mockSetDefaults ).not.toHaveBeenCalled();
	} );

	it( 'renders the saved state after a save', async () => {
		apiFetch.mockResolvedValue( { values: { title: 'Saved' } } );

		render();
		render( { isSaving: true } );
		await act( async () => {
			render( { isSaving: false } );
		} );

		expect( apiFetch ).toHaveBeenCalledTimes( 1 );
		expect( mockSetDefaults ).toHaveBeenCalledWith( { title: 'Saved' } );
	} );

	it( 'leaves autosaves alone', async () => {
		render();
		render( { isSaving: true, isAutosaving: true } );
		render();

		expect( apiFetch ).not.toHaveBeenCalled();
	} );
} );
