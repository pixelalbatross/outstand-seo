import { createReduxStore, register } from '@wordpress/data';

import { DEFAULTS } from '../config';

/**
 * Holds the titles and descriptions the active engine renders for the post,
 * keyed by canonical field name. They start as rendered for the saved post and
 * are replaced as the engine recomputes them for the unsaved editor state.
 */
export const STORE_NAME = 'outstand-seo/defaults';

/**
 * Reducer for the defaults store.
 *
 * @param {Object} state  Rendered values.
 * @param {Object} action Dispatched action.
 * @return {Object} Next state.
 */
export function reducer( state = DEFAULTS, action ) {
	switch ( action.type ) {
		case 'SET_DEFAULTS':
			return action.values;
		default:
			return state;
	}
}

export const actions = {
	/**
	 * Replace the rendered values.
	 *
	 * @param {Object} values Rendered values, keyed by canonical field name.
	 * @return {Object} Action.
	 */
	setDefaults: ( values ) => ( { type: 'SET_DEFAULTS', values } ),
};

export const selectors = {
	/**
	 * The value the engine renders for a field, or '' when it renders none.
	 *
	 * @param {Object} state Rendered values.
	 * @param {string} name  Canonical field name.
	 * @return {string} Rendered value.
	 */
	getDefault: ( state, name ) => state[ name ] || '',
};

export const store = createReduxStore( STORE_NAME, {
	reducer,
	actions,
	selectors,
} );

register( store );
