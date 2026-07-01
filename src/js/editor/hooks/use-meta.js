import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';

import { getField, getDefault } from '../config';

/**
 * Read/write the current post's canonical SEO data. The active engine
 * normalizes its native meta to this single object server-side, so the editor
 * is fully engine-agnostic.
 *
 * @return {[Object, Function, Function]} The canonical object, a single-key
 *                                        setter, and a multi-key setter.
 */
export function useSeoData() {
	const postType = useSelect(
		( select ) => select( editorStore ).getCurrentPostType(),
		[]
	);

	const [ data, setData ] = useEntityProp(
		'postType',
		postType,
		'outstand_seo'
	);

	const obj = data || {};

	// Write several keys in one dispatch. Calling setValue twice in the same
	// handler would each merge into the same stale `obj`, so the last write wins
	// and the earlier key is lost — use setValues for paired writes.
	const setValues = ( partial ) => {
		setData( { ...obj, ...partial } );
	};

	const setValue = ( key, value ) => {
		setValues( { [ key ]: value } );
	};

	return [ obj, setValue, setValues ];
}

/**
 * Read/write a single canonical SEO field. Unsupported fields report
 * `supported: false` so the panel can omit the control.
 *
 * @param {string} name Canonical field name.
 * @return {{supported: boolean, value: *, default: string, setValue: Function}} Field handle.
 */
export function useField( name ) {
	const [ data, setValue ] = useSeoData();

	if ( ! getField( name ) ) {
		return {
			supported: false,
			value: undefined,
			default: '',
			setValue: () => {},
		};
	}

	return {
		supported: true,
		value: data[ name ],
		default: getDefault( name ),
		setValue: ( next ) => setValue( name, next ),
	};
}
