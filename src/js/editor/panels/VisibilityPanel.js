import {
	TextControl,
	SelectControl,
	ToggleControl,
	__experimentalText as Text,
	__experimentalVStack as VStack,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import { useField } from '../hooks/use-meta';

/**
 * Presentation strings for one tri-state robots directive. Engines declare
 * *which* directives they support (schema); labels live here so a new engine
 * reusing these directives needs no JS. Builds only the requested field's
 * strings, not the whole set.
 *
 * @param {string} field Canonical field name (noindex/nofollow/noarchive).
 * @return {?{label: string, on: string, off: string, help: string}} Labels, or null.
 */
function robotsLabel( field ) {
	switch ( field ) {
		case 'noindex':
			return {
				label: __( 'Indexing', 'outstand-seo' ),
				on: __( 'Index', 'outstand-seo' ),
				off: __( 'No-index', 'outstand-seo' ),
				help: __(
					'Whether search engines may list this page in their results.',
					'outstand-seo'
				),
			};
		case 'nofollow':
			return {
				label: __( 'Link following', 'outstand-seo' ),
				on: __( 'Follow', 'outstand-seo' ),
				off: __( 'No-follow', 'outstand-seo' ),
				help: __(
					'Whether search engines may follow the links on this page.',
					'outstand-seo'
				),
			};
		case 'noarchive':
			return {
				label: __( 'Archiving', 'outstand-seo' ),
				on: __( 'Archive', 'outstand-seo' ),
				off: __( 'No-archive', 'outstand-seo' ),
				help: __(
					'Whether search engines may keep a cached copy of this page.',
					'outstand-seo'
				),
			};
		default:
			return null;
	}
}

/**
 * A tri-state robots directive select (Default / permissive / restrictive),
 * rendered from the schema-supported field and its labels.
 *
 * @param {Object} props       Component props.
 * @param {string} props.field Canonical field name (noindex/nofollow/noarchive).
 */
function RobotsSelect( { field } ) {
	const ctrl = useField( field );
	const labels = robotsLabel( field );

	if ( ! ctrl.supported || ! labels ) {
		return null;
	}

	return (
		<SelectControl
			label={ labels.label }
			value={ ctrl.value || 'default' }
			options={ [
				{ label: __( 'Default', 'outstand-seo' ), value: 'default' },
				{ label: labels.on, value: 'on' },
				{ label: labels.off, value: 'off' },
			] }
			onChange={ ctrl.setValue }
			help={ labels.help }
			__nextHasNoMarginBottom
			__next40pxDefaultSize
		/>
	);
}

/**
 * A toggle bound to a canonical boolean field, rendered only when supported.
 *
 * @param {Object} props        Component props.
 * @param {string} props.field  Canonical field name.
 * @param {string} props.label  Toggle label.
 * @param {string} [props.help] Optional help text.
 */
function FieldToggle( { field, label, help } ) {
	const ctrl = useField( field );

	if ( ! ctrl.supported ) {
		return null;
	}

	return (
		<ToggleControl
			label={ label }
			help={ help }
			checked={ !! ctrl.value }
			onChange={ ctrl.setValue }
			__nextHasNoMarginBottom
		/>
	);
}

/**
 * Visibility section (mirrors TSF): canonical, robots meta, archive settings,
 * 301 redirect.
 */
export default function VisibilityPanel() {
	const canonical = useField( 'canonical' );
	const redirect = useField( 'redirect' );

	return (
		<VStack spacing={ 4 }>
			{ canonical.supported && (
				<TextControl
					label={ __( 'Canonical URL', 'outstand-seo' ) }
					type="url"
					value={ canonical.value || '' }
					onChange={ canonical.setValue }
					help={ __(
						'Point search engines to this URL as the preferred version of the page.',
						'outstand-seo'
					) }
					__nextHasNoMarginBottom
					__next40pxDefaultSize
				/>
			) }

			<VStack spacing={ 3 }>
				<Text weight={ 600 }>
					{ __( 'Robots Meta Settings', 'outstand-seo' ) }
				</Text>
				<Text variant="muted">
					{ __(
						'Tell search engines whether to index this page, follow its links, or keep a cached copy.',
						'outstand-seo'
					) }
				</Text>
				<RobotsSelect field="noindex" />
				<RobotsSelect field="nofollow" />
				<RobotsSelect field="noarchive" />
				<FieldToggle
					field="noimageindex"
					label={ __( 'No image index', 'outstand-seo' ) }
					help={ __(
						'Stop search engines from indexing images on this page.',
						'outstand-seo'
					) }
				/>
				<FieldToggle
					field="nosnippet"
					label={ __( 'No snippet', 'outstand-seo' ) }
					help={ __(
						'Stop search engines from showing a text snippet or preview for this page.',
						'outstand-seo'
					) }
				/>
			</VStack>

			<VStack spacing={ 3 }>
				<Text weight={ 600 }>
					{ __( 'Archive Settings', 'outstand-seo' ) }
				</Text>
				<FieldToggle
					field="excludeLocalSearch"
					label={ __(
						"Hide this page from this site's search results.",
						'outstand-seo'
					) }
				/>
				<FieldToggle
					field="excludeFromArchive"
					label={ __(
						"Hide this page from this site's archive listings.",
						'outstand-seo'
					) }
				/>
			</VStack>
			<FieldToggle
				field="cornerstone"
				label={ __( 'Cornerstone content', 'outstand-seo' ) }
				help={ __(
					'Mark this as one of your most important, in-depth pages on its topic — a pillar you want to rank highest and link related posts to.',
					'outstand-seo'
				) }
			/>

			{ redirect.supported && (
				<TextControl
					label={ __( '301 Redirect URL', 'outstand-seo' ) }
					type="url"
					value={ redirect.value || '' }
					onChange={ redirect.setValue }
					help={ __(
						'Send visitors and search engines to a different URL.',
						'outstand-seo'
					) }
					__nextHasNoMarginBottom
					__next40pxDefaultSize
				/>
			) }
		</VStack>
	);
}
