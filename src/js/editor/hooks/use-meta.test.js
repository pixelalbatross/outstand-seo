/**
 * Unit tests for the canonical field handle.
 */
import { useEntityProp } from '@wordpress/core-data';

import { useField, useSeoData } from './use-meta';

// The editor packages are webpack externals, so they are mocked as virtual modules.
jest.mock( '@wordpress/core-data', () => ( { useEntityProp: jest.fn() } ), {
	virtual: true,
} );
jest.mock(
	'@wordpress/data',
	() => ( {
		useSelect: ( selector ) =>
			selector( ( storeName ) =>
				storeName === 'outstand-seo/defaults'
					? {
							getDefault: ( name ) =>
								( {
									title: 'Hello World - Example Site',
									ogTitle: 'Hello World',
								} )[ name ] || '',
					  }
					: { getCurrentPostType: () => 'post' }
			),
	} ),
	{ virtual: true }
);
jest.mock( '@wordpress/editor', () => ( { store: 'core/editor' } ), {
	virtual: true,
} );
jest.mock( '../store', () => ( { store: 'outstand-seo/defaults' } ) );
jest.mock( '../config', () => ( {
	getField: ( name ) =>
		[ 'title', 'ogTitle' ].includes( name )
			? { kind: 'string' }
			: undefined,
} ) );

/**
 * Point the post's canonical SEO data at the given values.
 *
 * @param {Object} data Canonical SEO data.
 */
function setSeoData( data ) {
	useEntityProp.mockReturnValue( [ data, jest.fn() ] );
}

describe( 'useField', () => {
	it( 'reads the value the engine renders for the field', () => {
		setSeoData( { title: '' } );

		expect( useField( 'title' ) ).toMatchObject( {
			supported: true,
			value: '',
			default: 'Hello World - Example Site',
		} );
		expect( useField( 'ogTitle' ).default ).toBe( 'Hello World' );
	} );

	it( 'is empty and read-only for an unsupported field', () => {
		const setData = jest.fn();
		useEntityProp.mockReturnValue( [ {}, setData ] );

		const field = useField( 'focusKw' );
		field.setValue( 'Example keyphrase' );

		expect( field ).toMatchObject( { supported: false, default: '' } );
		expect( setData ).not.toHaveBeenCalled();
	} );
} );

describe( 'useSeoData', () => {
	it( 'reads missing data as an empty object', () => {
		useEntityProp.mockReturnValue( [ undefined, jest.fn() ] );

		expect( useSeoData()[ 0 ] ).toEqual( {} );
	} );

	it( 'merges single and paired writes into the current data', () => {
		const setData = jest.fn();
		useEntityProp.mockReturnValue( [ { description: 'Kept' }, setData ] );

		const [ , setValue, setValues ] = useSeoData();

		setValue( 'ogDescription', 'Custom social description' );
		setValues( { ogTitle: 'Custom title', twitterTitle: 'Custom title' } );

		expect( setData ).toHaveBeenNthCalledWith( 1, {
			description: 'Kept',
			ogDescription: 'Custom social description',
		} );
		expect( setData ).toHaveBeenNthCalledWith( 2, {
			description: 'Kept',
			ogTitle: 'Custom title',
			twitterTitle: 'Custom title',
		} );
	} );

	it( 'writes a supported field through its handle', () => {
		const setData = jest.fn();
		useEntityProp.mockReturnValue( [ {}, setData ] );

		useField( 'title' ).setValue( 'Custom title' );

		expect( setData ).toHaveBeenCalledWith( {
			title: 'Custom title',
		} );
	} );
} );
