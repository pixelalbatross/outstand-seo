/**
 * Extends the core/breadcrumbs block so the active SEO engine's trail can be
 * controlled from the editor.
 *
 * Adds an engine `home` label attribute (gated by capability) and surfaces a
 * Notice for any core control the active engine ignores — core's own
 * ToolsPanelItems cannot be removed from outside, so we disclose them instead.
 */
import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, Notice } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';

const BLOCK = 'core/breadcrumbs';

/**
 * Engine + capability map localized by PHP as `window.outstandSeoBreadcrumbs`.
 * Shape: { engine: string, capabilities: { <arg>: boolean } }.
 */
const bc = window.outstandSeoBreadcrumbs || { engine: '', capabilities: {} };
const CAPS = bc.capabilities || {};
const can = ( arg ) => Boolean( CAPS[ arg ] );

/**
 * Human-readable name for the active engine, falling back to its slug.
 */
const ENGINE_NAMES = {
	tsf: __( 'The SEO Framework', 'outstand-seo' ),
	yoast: __( 'Yoast SEO', 'outstand-seo' ),
};
const engineName = ENGINE_NAMES[ bc.engine ] || bc.engine;

/**
 * Core controls that no-op under the active engine when it ignores the arg.
 * These are core's own ToolsPanelItems — they cannot be removed from outside,
 * so we list them in a Notice rather than let them mislead editors.
 */
const IGNORED_LABELS = {
	separator: __( 'Separator', 'outstand-seo' ),
	show_home: __( 'Show home breadcrumb', 'outstand-seo' ),
	show_current: __( 'Show current breadcrumb', 'outstand-seo' ),
	show_on_home: __( 'Show on homepage', 'outstand-seo' ),
	prefers_taxonomy: __( 'Prefer taxonomy terms', 'outstand-seo' ),
};

/**
 * Add the engine `home` label attribute to core/breadcrumbs.
 */
addFilter(
	'blocks.registerBlockType',
	'outstand-seo/breadcrumbs-home-attr',
	( settings, name ) => {
		if ( BLOCK !== name || ! bc.engine ) {
			return settings;
		}

		return {
			...settings,
			attributes: {
				...settings.attributes,
				home: { type: 'string', default: '' },
			},
		};
	}
);

/**
 * Inject the engine home-label control (gated by capability) and a Notice
 * listing any visible core controls the active engine ignores.
 */
const withEngineControls = createHigherOrderComponent(
	( BlockEdit ) => ( props ) => {
		if ( BLOCK !== props.name || ! bc.engine ) {
			return <BlockEdit { ...props } />;
		}

		const { attributes, setAttributes } = props;

		const ignored = Object.keys( IGNORED_LABELS )
			.filter( ( arg ) => ! can( arg ) )
			.map( ( arg ) => IGNORED_LABELS[ arg ] );

		return (
			<>
				<BlockEdit { ...props } />
				<InspectorControls>
					<PanelBody title={ __( 'SEO', 'outstand-seo' ) }>
						{ can( 'home' ) && (
							<TextControl
								label={ __( 'Home label', 'outstand-seo' ) }
								help={ __(
									'Override the first crumb label. Leave empty for the engine default.',
									'outstand-seo'
								) }
								value={ attributes.home || '' }
								onChange={ ( home ) =>
									setAttributes( { home } )
								}
								__nextHasNoMarginBottom
								__next40pxDefaultSize
							/>
						) }
						{ ignored.length > 0 && (
							<Notice status="warning" isDismissible={ false }>
								{ sprintf(
									/* translators: %1$s: active SEO engine name. %2$s: comma-separated control names. */
									__(
										'The following controls are not supported by %1$s: %2$s.',
										'outstand-seo'
									),
									engineName,
									ignored.join( ', ' )
								) }
							</Notice>
						) }
					</PanelBody>
				</InspectorControls>
			</>
		);
	},
	'withEngineControls'
);

addFilter(
	'editor.BlockEdit',
	'outstand-seo/breadcrumbs-controls',
	withEngineControls
);
