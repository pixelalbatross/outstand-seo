<?php

namespace Outstand\WP\SEO;

/**
 * Registers the plugin's blocks from the compiled output.
 */
class Blocks extends BaseModule {

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_action( 'init', [ $this, 'register_blocks' ] );
	}

	/**
	 * Register every block found in the build output.
	 *
	 * The wp-scripts build compiles `src/<name>/` into `build/<name>/` with a
	 * generated block.json (and copies render.php). Non-block entries (e.g.
	 * `build/js/`) carry no block.json and are skipped.
	 *
	 * @return void
	 */
	public function register_blocks(): void {
		$manifests = glob( OUTSTAND_SEO_DIST_PATH . '*/block.json' );

		if ( empty( $manifests ) ) {
			return;
		}

		foreach ( $manifests as $manifest ) {
			register_block_type( dirname( $manifest ) );
		}
	}
}
