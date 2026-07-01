import { Image } from '@10up/block-components/components/image';
import {
	TextControl,
	TextareaControl,
	SelectControl,
	Button,
	__experimentalVStack as VStack,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import { useField, useSeoData } from '../hooks/use-meta';
import CharCount from '../components/CharCount';
import { TITLE_RANGE, DESC_RANGE } from '../components/char-status';

const CARD_TYPES = [
	{ label: __( 'Default (from settings)', 'outstand-seo' ), value: '' },
	{ label: __( 'Summary', 'outstand-seo' ), value: 'summary' },
	{
		label: __( 'Summary with large image', 'outstand-seo' ),
		value: 'summary_large_image',
	},
];

/**
 * Image picker bound to a URL field and an attachment-ID field.
 *
 * @param {Object} props              Component props.
 * @param {string} props.label        Control label.
 * @param {string} props.urlField     Canonical URL field name.
 * @param {string} props.idField      Canonical attachment-ID field name.
 * @param {string} [props.instructions] Picker instructions.
 */
function ImageField( { label, urlField, idField, instructions } ) {
	const url = useField( urlField );
	const id = useField( idField );
	const [ , , setValues ] = useSeoData();

	if ( ! url.supported ) {
		return null;
	}

	// Write URL + ID in a single dispatch; two separate setValue calls would
	// each merge into the same stale object and the first would be lost.
	const onSelect = ( media ) => {
		const next = { [ urlField ]: media?.url || '' };
		if ( id.supported ) {
			next[ idField ] = media?.id || 0;
		}

		setValues( next );
	};

	const onRemove = () => {
		const next = { [ urlField ]: '' };
		if ( id.supported ) {
			next[ idField ] = 0;
		}

		setValues( next );
	};

	return (
		<VStack spacing={ 2 }>
			<Image
				id={ id.value || undefined }
				size="large"
				onSelect={ onSelect }
				labels={ { title: label, instructions } }
				style={ { maxWidth: '100%', height: 'auto' } }
			/>
			{ !! url.value && (
				<Button
					variant="link"
					isDestructive
					onClick={ onRemove }
					__next40pxDefaultSize
				>
					{ __( 'Remove image', 'outstand-seo' ) }
				</Button>
			) }
		</VStack>
	);
}

/**
 * Social fields: Open Graph + Twitter.
 */
export default function SocialPanel() {
	const ogTitle = useField( 'ogTitle' );
	const ogDescription = useField( 'ogDescription' );
	const twitterTitle = useField( 'twitterTitle' );
	const twitterDescription = useField( 'twitterDescription' );
	const twitterCardType = useField( 'twitterCardType' );

	return (
		<VStack spacing={ 4 }>
			<ImageField
				label={ __( 'Social Image', 'outstand-seo' ) }
				instructions={ __(
					'Use a 1.91:1 image at least 1200px wide for reliable results across networks.',
					'outstand-seo'
				) }
				urlField="ogImageUrl"
				idField="ogImageId"
			/>
			{ ogTitle.supported && (
				<VStack spacing={ 1 }>
					<TextControl
						label={ __( 'Open Graph Title', 'outstand-seo' ) }
						value={ ogTitle.value || '' }
						onChange={ ogTitle.setValue }
						placeholder={ ogTitle.default }
						help={ __(
							'Title shown when this page is shared on Facebook, LinkedIn, and other networks.',
							'outstand-seo'
						) }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
					<CharCount
						value={ ogTitle.value }
						default={ ogTitle.default }
						{ ...TITLE_RANGE }
					/>
				</VStack>
			) }
			{ ogDescription.supported && (
				<VStack spacing={ 1 }>
					<TextareaControl
						label={ __( 'Open Graph Description', 'outstand-seo' ) }
						value={ ogDescription.value || '' }
						onChange={ ogDescription.setValue }
						placeholder={ ogDescription.default }
						help={ __(
							'Description shown when this page is shared on Facebook, LinkedIn, and other networks.',
							'outstand-seo'
						) }
						__nextHasNoMarginBottom
					/>
					<CharCount
						value={ ogDescription.value }
						default={ ogDescription.default }
						{ ...DESC_RANGE }
					/>
				</VStack>
			) }
			{ twitterTitle.supported && (
				<VStack spacing={ 1 }>
					<TextControl
						label={ __( 'Twitter Title', 'outstand-seo' ) }
						value={ twitterTitle.value || '' }
						onChange={ twitterTitle.setValue }
						placeholder={ twitterTitle.default }
						help={ __(
							'Title shown when this page is shared on X (Twitter). Falls back to the Open Graph title.',
							'outstand-seo'
						) }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
					<CharCount
						value={ twitterTitle.value }
						default={ twitterTitle.default }
						{ ...TITLE_RANGE }
					/>
				</VStack>
			) }
			{ twitterDescription.supported && (
				<VStack spacing={ 1 }>
					<TextareaControl
						label={ __( 'Twitter Description', 'outstand-seo' ) }
						value={ twitterDescription.value || '' }
						onChange={ twitterDescription.setValue }
						placeholder={ twitterDescription.default }
						help={ __(
							'Description shown when this page is shared on X (Twitter). Falls back to the Open Graph description.',
							'outstand-seo'
						) }
						__nextHasNoMarginBottom
					/>
					<CharCount
						value={ twitterDescription.value }
						default={ twitterDescription.default }
						{ ...DESC_RANGE }
					/>
				</VStack>
			) }
			<ImageField
				label={ __( 'Twitter Image', 'outstand-seo' ) }
				urlField="twitterImageUrl"
				idField="twitterImageId"
			/>
			{ twitterCardType.supported && (
				<SelectControl
					label={ __( 'Twitter Card Type', 'outstand-seo' ) }
					value={ twitterCardType.value || '' }
					options={ CARD_TYPES }
					onChange={ twitterCardType.setValue }
					help={ __(
						'Sets how the preview looks on X and platforms like Discord: a small thumbnail or a large cover image.',
						'outstand-seo'
					) }
					__nextHasNoMarginBottom
					__next40pxDefaultSize
				/>
			) }
		</VStack>
	);
}
