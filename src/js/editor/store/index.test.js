/**
 * Unit tests for the defaults store.
 */
import { actions, reducer, selectors } from './index';

// The editor packages are webpack externals, so they are mocked as virtual modules.
jest.mock(
	'@wordpress/data',
	() => ( { createReduxStore: jest.fn(), register: jest.fn() } ),
	{ virtual: true }
);
jest.mock( '../config', () => ( {
	DEFAULTS: { title: 'Hello World - Example Site' },
} ) );

describe( 'defaults store', () => {
	it( 'starts from the values rendered for the saved post', () => {
		expect( reducer( undefined, { type: 'INIT' } ) ).toEqual( {
			title: 'Hello World - Example Site',
		} );
	} );

	it( 'replaces the values on setDefaults', () => {
		const state = reducer(
			{ title: 'Hello World - Example Site' },
			actions.setDefaults( { title: 'Edited Title - Example Site' } )
		);

		expect( state ).toEqual( { title: 'Edited Title - Example Site' } );
	} );

	it( 'reads a rendered value, or empty when there is none', () => {
		const state = { title: 'Hello World - Example Site' };

		expect( selectors.getDefault( state, 'title' ) ).toBe(
			'Hello World - Example Site'
		);
		expect( selectors.getDefault( state, 'ogTitle' ) ).toBe( '' );
	} );
} );
