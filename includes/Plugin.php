<?php

namespace Outstand\WP\SEO;

class Plugin {

	/**
	 * Singleton instance of the Plugin.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Returns singleton instance.
	 *
	 * @return Plugin The singleton instance.
	 */
	public static function get_instance(): Plugin {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Enable plugin functionality.
	 *
	 * @return void
	 */
	public function enable(): void {

		add_action( 'init', [ $this, 'load_textdomain' ], 1 );

		$modules = [
			new EditorBridge(),
			new Assets(),
			new Blocks(),
			new Blocks\Breadcrumbs(),
			new Schema\Graph(),
		];

		foreach ( $modules as $module ) {
			if ( $module instanceof BaseModule && $module->can_register() ) {
				$module->register();
			}
		}
	}

	/**
	 * Load the plugin's PHP translations from its languages folder.
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain( 'outstand-seo', false, dirname( OUTSTAND_SEO_BASENAME ) . '/languages' );
	}
}
