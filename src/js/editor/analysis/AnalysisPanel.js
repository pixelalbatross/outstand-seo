import {
	TextControl,
	__experimentalText as Text,
	__experimentalVStack as VStack,
	__experimentalHStack as HStack,
} from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';

import { useField } from '../hooks/use-meta';
import { useAnalysis } from '../hooks/use-analysis';
import { STATUS_COLORS } from '../components/status';

/**
 * A colored status dot using the WordPress admin status palette.
 *
 * @param {Object} props        Component props.
 * @param {string} props.rating Rating bucket.
 */
function Dot( { rating } ) {
	return (
		<span
			aria-hidden="true"
			style={ {
				display: 'inline-block',
				flexShrink: 0,
				width: 12,
				height: 12,
				borderRadius: '50%',
				backgroundColor: STATUS_COLORS[ rating ] || STATUS_COLORS.ok,
			} }
		/>
	);
}

/**
 * Focus keyphrase input plus live, lightweight on-page checks.
 */
export default function AnalysisPanel() {
	const focusKw = useField( 'focusKw' );
	const title = useField( 'title' );
	const description = useField( 'description' );

	const { score, rating, checks } = useAnalysis( {
		keyphrase: focusKw.value || '',
		seoTitle: title.value || '',
		description: description.value || '',
	} );

	return (
		<VStack spacing={ 4 }>
			{ focusKw.supported && (
				<TextControl
					label={ __( 'Focus keyphrase', 'outstand-seo' ) }
					value={ focusKw.value || '' }
					onChange={ focusKw.setValue }
					__nextHasNoMarginBottom
					__next40pxDefaultSize
				/>
			) }
			<HStack justify="flex-start" alignment="center" spacing={ 2 }>
				<Dot rating={ rating } />
				<Text>
					{ sprintf(
						/* translators: %d: SEO score out of 100. */
						__( 'Overall score: %d/100', 'outstand-seo' ),
						score
					) }
				</Text>
			</HStack>
			<VStack spacing={ 2 }>
				{ checks.map( ( check ) => (
					<HStack
						key={ check.id }
						justify="flex-start"
						alignment="center"
						spacing={ 2 }
					>
						<Dot rating={ check.status } />
						<Text variant="muted">{ check.text }</Text>
					</HStack>
				) ) }
			</VStack>
		</VStack>
	);
}
