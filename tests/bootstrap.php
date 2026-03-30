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

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';
