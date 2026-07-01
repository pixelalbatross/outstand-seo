import { __experimentalText as Text } from '@wordpress/components';
import { sprintf, __ } from '@wordpress/i18n';

import { STATUS_COLORS } from './status';
import { status } from './char-status';

/**
 * Character counter shown under a text field, colored against a recommended
 * range (mirrors The SEO Framework's title/description guidelines). Uses the
 * core `Text` component with the WordPress admin status palette.
 *
 * @param {Object} props           Component props.
 * @param {string} props.value     Field value.
 * @param {string} [props.default] Engine default, counted when value is empty.
 * @param {number} props.min       Recommended minimum length.
 * @param {number} props.max       Recommended maximum length.
 */
export default function CharCount( { value = '', default: def = '', min, max } ) {
	const effective = value || def;
	const n = ( effective || '' ).length;
	const bucket = status( n, min, max );

	return (
		<Text
			size="12px"
			color={ STATUS_COLORS[ bucket ] }
		>
			{ sprintf(
				/* translators: 1: current character count, 2: recommended max. */
				__( '%1$d / %2$d characters', 'outstand-seo' ),
				n,
				max
			) }
		</Text>
	);
}
