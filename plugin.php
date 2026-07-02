<?php // phpcs:ignore Generic.Commenting.DocComment.MissingShort
/**
 * @wordpress-plugin
 * Plugin Name:       Outstand SEO
 * Description:       One block-editor SEO UI, powered by your engine in the background.
 * Plugin URI:        https://outstand.site/?utm_source=wp-plugins&utm_medium=outstand-seo&utm_campaign=plugin-uri
 * Requires at least: 6.7
 * Requires PHP:      8.2
 * Version:           1.2.0
 * Author:            Outstand
 * Author URI:        https://outstand.site/?utm_source=wp-plugins&utm_medium=outstand-seo&utm_campaign=author-uri
 * License:           GPL-3.0-or-later
 * License URI:       https://spdx.org/licenses/GPL-3.0-or-later.html
 * Update URI:        https://outstand.site/
 * GitHub Plugin URI: https://github.com/pixelalbatross/outstand-seo
 * Text Domain:       outstand-seo
 * Domain Path:       /languages
 */

namespace Outstand\WP\SEO;

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'OUTSTAND_SEO_VERSION', '1.2.0' );
define( 'OUTSTAND_SEO_BASENAME', plugin_basename( __FILE__ ) );
define( 'OUTSTAND_SEO_URL', plugin_dir_url( __FILE__ ) );
define( 'OUTSTAND_SEO_PATH', plugin_dir_path( __FILE__ ) );
define( 'OUTSTAND_SEO_DIST_URL', OUTSTAND_SEO_URL . 'build/' );
define( 'OUTSTAND_SEO_DIST_PATH', OUTSTAND_SEO_PATH . 'build/' );

if ( file_exists( OUTSTAND_SEO_PATH . 'vendor/autoload.php' ) ) {
	require_once OUTSTAND_SEO_PATH . 'vendor/autoload.php';
}

if ( class_exists( PucFactory::class ) ) {
	PucFactory::buildUpdateChecker(
		'https://github.com/pixelalbatross/outstand-seo/',
		__FILE__,
		'outstand-seo'
	)->setBranch( 'main' );
}

add_action(
	'plugins_loaded',
	function () {
		Plugin::get_instance()->enable();
	}
);
