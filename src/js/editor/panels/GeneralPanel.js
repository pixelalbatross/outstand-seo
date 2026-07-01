import {
	TextControl,
	TextareaControl,
	ToggleControl,
	__experimentalVStack as VStack,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import { useField } from '../hooks/use-meta';
import { useTitleDefault } from '../hooks/use-live-title';
import CharCount from '../components/CharCount';
import { TITLE_RANGE, DESC_RANGE } from '../components/char-status';
import PrimaryTermPanel from './PrimaryTermPanel';

/**
 * General section (mirrors TSF): meta title, meta description, primary term.
 */
export default function GeneralPanel() {
	const title = useField( 'title' );
	const titleNoBlogname = useField( 'titleNoBlogname' );
	const description = useField( 'description' );
	const titleDefault = useTitleDefault();

	return (
		<VStack spacing={ 4 }>
			{ title.supported && (
				<VStack spacing={ 1 }>
					<TextControl
						label={ __( 'Meta Title', 'outstand-seo' ) }
						value={ title.value || '' }
						onChange={ title.setValue }
						placeholder={ titleDefault }
						help={ __(
							'Overrides the title shown for this page in search results.',
							'outstand-seo'
						) }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
					<CharCount
						value={ title.value }
						default={ titleDefault }
						{ ...TITLE_RANGE }
					/>
				</VStack>
			) }
			{ titleNoBlogname.supported && (
				<ToggleControl
					label={ __( 'Remove the site title?', 'outstand-seo' ) }
					help={ __(
						'Enable to drop the site name and arrange the title parts yourself.',
						'outstand-seo'
					) }
					checked={ !! titleNoBlogname.value }
					onChange={ titleNoBlogname.setValue }
					__nextHasNoMarginBottom
				/>
			) }
			{ description.supported && (
				<VStack spacing={ 1 }>
					<TextareaControl
						label={ __( 'Meta Description', 'outstand-seo' ) }
						value={ description.value || '' }
						onChange={ description.setValue }
						placeholder={ description.default }
						help={ __(
							'Overrides the summary shown beneath the title in search results.',
							'outstand-seo'
						) }
						__nextHasNoMarginBottom
					/>
					<CharCount
						value={ description.value }
						default={ description.default }
						{ ...DESC_RANGE }
					/>
				</VStack>
			) }
			<PrimaryTermPanel />
		</VStack>
	);
}
