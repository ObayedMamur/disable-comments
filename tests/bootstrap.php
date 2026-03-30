<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Disable_Comments
 */

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// wp-env sets WP_TESTS_DIR to the WordPress test library inside the container.
$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find WordPress test library at '$_tests_dir'." . PHP_EOL;
	echo "Run tests via wp-env: wp-env run cli --env-cwd=wp-content/plugins/disable-comments phpunit" . PHP_EOL;
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require dirname( __DIR__ ) . '/disable-comments.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// wp-phpunit's install.php populates wp_sitemeta before populate_network() runs,
// causing populate_network() to bail ("Network already installed."), leaving wp_blogs
// empty. WordPress 6.x ms-settings.php then crashes in PHP 8.2 with a fatal Error
// when trying to set properties on false ($current_blog). Pre-define MULTISITE=false
// here to pin the test runner to single-site mode. Multisite-specific tests call
// markTestSkipped() when is_multisite() is false.
if ( ! defined( 'MULTISITE' ) ) {
	define( 'MULTISITE', false );
}

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';

// wp-phpunit installs an error handler that converts E_WARNING to PHPUnit
// exceptions.  The plugin defines its constants (DC_VERSION etc.) inside
// __construct(), so singleton resets in tests trigger "constant already
// defined" warnings.  Install a thin wrapper that silences only those
// harmless redefinition warnings and forwards everything else to the
// wp-phpunit handler.
$_dc_prev_handler = set_error_handler(
	static function ( $errno, $errstr, $errfile, $errline ) use ( &$_dc_prev_handler ) {
		if ( E_WARNING === $errno && false !== strpos( $errstr, 'already defined' ) ) {
			return true; // Suppress — harmless during singleton resets.
		}
		if ( is_callable( $_dc_prev_handler ) ) {
			return ( $_dc_prev_handler )( $errno, $errstr, $errfile, $errline );
		}
		return false;
	}
);

// Load shared test helpers.
require_once __DIR__ . '/helpers/trait-plugin-options.php';
