/**
 * Engine schema localized by PHP as `window.outstandSeo`.
 *
 * Shape: { engine, fields: { <canonical>: { kind } }, primaryTerms: boolean,
 *          defaults: { values, titleTemplate } }.
 *
 * The active engine normalizes its native meta to canonical values server-side,
 * so this schema is fully engine-agnostic — panels never see native keys.
 */
const cfg = window.outstandSeo || {
	engine: '',
	fields: {},
	primaryTerms: false,
	defaults: {},
};

export const ENGINE = cfg.engine;
export const FIELDS = cfg.fields || {};

/**
 * Whether the active engine supports primary terms.
 */
export const PRIMARY_TERMS = Boolean( cfg.primaryTerms );

/**
 * Engine-generated default snapshots, keyed by canonical field name. Shown as
 * placeholders and counted when a field is empty.
 */
export const DEFAULTS = cfg.defaults?.values || {};

/**
 * { prefix, suffix } wrapping the live post title for real-time title
 * reassembly, or null when the engine has no live template (static snapshot).
 */
export const TITLE_TEMPLATE = cfg.defaults?.titleTemplate || null;

/**
 * Schema descriptor for a canonical field, or undefined if the active engine
 * does not support it.
 *
 * @param {string} name Canonical field name.
 * @return {Object|undefined} Field descriptor ({ kind }).
 */
export const getField = ( name ) => FIELDS[ name ];

/**
 * Whether the active engine supports a canonical field.
 *
 * @param {string} name Canonical field name.
 * @return {boolean} Support flag.
 */
export const hasField = ( name ) => Boolean( FIELDS[ name ] );

/**
 * The engine-generated default for a canonical field, or '' if none.
 *
 * @param {string} name Canonical field name.
 * @return {string} Default value.
 */
export const getDefault = ( name ) => DEFAULTS[ name ] || '';
