/**
 * Pure character-count helpers (no WordPress imports, so unit-testable in isolation).
 */

/**
 * Classify a character count against a recommended range.
 *
 * @param {number} n   Character count.
 * @param {number} min Recommended minimum.
 * @param {number} max Recommended maximum.
 * @return {('empty'|'under'|'good'|'over')} Status bucket.
 */
export function status( n, min, max ) {
	if ( ! n ) {
		return 'empty';
	}

	if ( n > max ) {
		return 'over';
	}

	if ( n < min ) {
		return 'under';
	}

	return 'good';
}

// Recommended ranges (characters).
export const TITLE_RANGE = { min: 30, max: 60 };
export const DESC_RANGE = { min: 120, max: 160 };
