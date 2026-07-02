<?php

namespace Outstand\WP\SEO;

use Outstand\WP\SEO\Engines\EngineManager;

/**
 * Enqueues editor assets for the SEO sidebar.
 */
class Assets extends BaseModule {
	use GetAssetInfo;

	/**
	 * Script handle.
	 *
	 * @var string
	 */
	const HANDLE = 'outstand-seo-editor';

	/**
	 * Breadcrumbs block extension script handle.
	 *
	 * @var string
	 */
	const BREADCRUMB_HANDLE = 'outstand-seo-breadcrumbs';

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		$this->setup_asset_vars(
			dist_path: OUTSTAND_SEO_DIST_PATH,
			fallback_version: OUTSTAND_SEO_VERSION
		);

		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ] );
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_breadcrumb_editor_assets' ] );
	}

	/**
	 * Enqueue the compiled sidebar script and localize the active engine config.
	 *
	 * @return void
	 */
	public function enqueue_editor_assets(): void {

		$engine = EngineManager::get_active();

		// No engine, no UI — the bridge already shows a notice.
		if ( null === $engine ) {
			return;
		}

		// Only load on the post types the SEO field is registered for, so the
		// sidebar never appears where its data cannot be saved.
		$screen = get_current_screen();
		if ( null === $screen || ! in_array( $screen->post_type, PostTypes::get(), true ) ) {
			return;
		}

		wp_enqueue_script(
			self::HANDLE,
			OUTSTAND_SEO_DIST_URL . 'js/editor.js',
			$this->get_asset_info( 'editor', 'dependencies' ),
			$this->get_asset_info( 'editor', 'version' ),
			true
		);

		$config = $engine->get_js_config();

		$post = get_post();
		if ( $post instanceof \WP_Post ) {
			$config['defaults'] = $engine->get_editor_defaults( $post->ID );
		}

		wp_add_inline_script(
			self::HANDLE,
			'window.outstandSeo = ' . wp_json_encode( $config ) . ';',
			'before'
		);

		wp_set_script_translations(
			self::HANDLE,
			'outstand-seo',
			OUTSTAND_SEO_PATH . 'languages'
		);
	}

	/**
	 * Enqueue the core/breadcrumbs extension and localize the active engine's
	 * breadcrumb capabilities.
	 *
	 * Loaded in every block editor (post editor, site editor, template parts)
	 * because the breadcrumbs block can be inserted anywhere — unlike the SEO
	 * sidebar, this is not gated to the SEO post types.
	 *
	 * @return void
	 */
	public function enqueue_breadcrumb_editor_assets(): void {

		$engine = EngineManager::get_active();

		// No engine → nothing overrides core/breadcrumbs, so no extension.
		if ( null === $engine ) {
			return;
		}

		if ( ! \WP_Block_Type_Registry::get_instance()->is_registered( 'core/breadcrumbs' ) ) {
			return;
		}

		wp_enqueue_script(
			self::BREADCRUMB_HANDLE,
			OUTSTAND_SEO_DIST_URL . 'js/breadcrumbs.js',
			$this->get_asset_info( 'breadcrumbs', 'dependencies' ),
			$this->get_asset_info( 'breadcrumbs', 'version' ),
			true
		);

		wp_add_inline_script(
			self::BREADCRUMB_HANDLE,
			'window.outstandSeoBreadcrumbs = ' . wp_json_encode(
				[
					'engine'       => $engine->get_slug(),
					'capabilities' => $engine->get_breadcrumb_capabilities(),
				]
			) . ';',
			'before'
		);

		wp_set_script_translations(
			self::BREADCRUMB_HANDLE,
			'outstand-seo',
			OUTSTAND_SEO_PATH . 'languages'
		);
	}
}
