import { registerPlugin } from '@wordpress/plugins';
import {
	PluginSidebar,
	PluginSidebarMoreMenuItem,
	PluginDocumentSettingPanel,
} from '@wordpress/editor';
import { PanelBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import { seoIcon } from './icon';
import GeneralPanel from './panels/GeneralPanel';
import VisibilityPanel from './panels/VisibilityPanel';
import SocialPanel from './panels/SocialPanel';
import AnalysisPanel from './analysis/AnalysisPanel';

const PLUGIN_NAME = 'outstand-seo';
const SEO_SIDEBAR = 'outstand-seo';
const ANALYSIS_PANEL = 'outstand-seo-analysis';

/**
 * The Outstand SEO editor surfaces:
 *  - "SEO": a dedicated sidebar (its own icon) grouping the fields into
 *    TSF-style General / Visibility / Social sections.
 *  - "SEO Analysis": a document panel in the default Settings sidebar with the
 *    focus keyphrase and on-page checks.
 *
 * Both read the active engine's field map (window.outstandSeo) and write that
 * engine's native post meta.
 */
function OutstandSeoEditor() {
	const seoTitle = __( 'SEO', 'outstand-seo' );

	return (
		<>
			<PluginSidebarMoreMenuItem target={ SEO_SIDEBAR } icon={ seoIcon }>
				{ seoTitle }
			</PluginSidebarMoreMenuItem>
			<PluginSidebar
				name={ SEO_SIDEBAR }
				title={ seoTitle }
				icon={ seoIcon }
			>
				<PanelBody
					title={ __( 'General', 'outstand-seo' ) }
					initialOpen
				>
					<GeneralPanel />
				</PanelBody>
				<PanelBody
					title={ __( 'Social', 'outstand-seo' ) }
					initialOpen={ false }
				>
					<SocialPanel />
				</PanelBody>
				<PanelBody
					title={ __( 'Visibility', 'outstand-seo' ) }
					initialOpen={ false }
				>
					<VisibilityPanel />
				</PanelBody>
			</PluginSidebar>

			<PluginDocumentSettingPanel
				name={ ANALYSIS_PANEL }
				title={ __( 'SEO Analysis', 'outstand-seo' ) }
			>
				<AnalysisPanel />
			</PluginDocumentSettingPanel>
		</>
	);
}

registerPlugin( PLUGIN_NAME, {
	render: OutstandSeoEditor,
	icon: seoIcon,
} );
