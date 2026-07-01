/**
 * WordPress admin status palette (Color Studio — the same greens/ambers/reds
 * core uses in Site Health, notices, and the block editor). Applied via core
 * component props (e.g. `Text` `color`) rather than bespoke CSS.
 */
export const STATUS_COLORS = {
	empty: '#757575', // gray-40 (muted)
	under: '#dba617', // yellow-30 (warning)
	good: '#00a32a', // green-50 (success)
	ok: '#dba617', // yellow-30 (warning)
	bad: '#d63638', // red-50 (error)
	over: '#d63638', // red-50 (error)
};
