/**
 * Unit tests for the live default title.
 */
import { useSelect } from '@wordpress/data';

import { getDefault } from '../config';
import { useTitleDefault } from './use-live-title';

let mockTitleTemplate = null;

// The editor packages are webpack externals, so they are mocked as virtual modules.
jest.mock( '@wordpress/data', () => ( { useSelect: jest.fn() } ), {
	virtual: true,
} );
jest.mock( '@wordpress/editor', () => ( { store: 'core/editor' } ), {
	virtual: true,
} );
jest.mock( '../config', () => ( {
	getDefault: jest.fn( () => 'Saved Title - Example Site' ),
	get TITLE_TEMPLATE() {
		return mockTitleTemplate;
	},
} ) );

const TEMPLATE = {
	prefix: 'Example Client - ',
	suffix: ' - Example Site',
	untitled: 'Example Client - Untitled - Example Site',
};

/**
 * Point the editor's post title at the given value.
 *
 * @param {string} title Post title.
 */
function setPostTitle( title ) {
	useSelect.mockImplementation( ( selector ) =>
		selector( () => ( { getEditedPostAttribute: () => title } ) )
	);
}

describe( 'useTitleDefault', () => {
	afterEach( () => {
		mockTitleTemplate = null;
	} );

	it( 'returns the static default without a title template', () => {
		setPostTitle( 'Hello World' );

		expect( useTitleDefault() ).toBe( 'Saved Title - Example Site' );
		expect( getDefault ).toHaveBeenCalledWith( 'title' );
	} );

	it( 'wraps the live post title in the template', () => {
		mockTitleTemplate = TEMPLATE;
		setPostTitle( 'Hello World' );

		expect( useTitleDefault() ).toBe(
			'Example Client - Hello World - Example Site'
		);
	} );

	it( 'returns the untitled title while the post has no title', () => {
		mockTitleTemplate = TEMPLATE;
		setPostTitle( '' );

		expect( useTitleDefault() ).toBe( TEMPLATE.untitled );
	} );
} );
