<?php
/**
 * Bootstrap for the security test suite.
 *
 * Replaces tests/bootstrap.php for phpunit-security.xml.
 *
 * Why a custom bootstrap?
 *
 * The wp-env test container ships a wp-tests-config.php that already defines
 * MULTISITE=true. When the WP PHPUnit installer runs populate_network(), that
 * function detects an existing multisite install and skips inserting the main
 * blog into wp_blogs, leaving it empty. WordPress then crashes in
 * ms-settings.php when it cannot find the current blog.
 *
 * Fix: after the install subprocess finishes, we check whether wp_blogs is
 * empty and, if so, insert the main blog record before loading wp-settings.php.
 *
 * @package Disable_Comments
 */

// Globalise the variables that WordPress expects when loaded via PHPUnit.
// See https://github.com/sebastianbergmann/phpunit/issues/325
global $wpdb, $current_site, $current_blog, $wp_rewrite, $shortcode_tags, $wp, $phpmailer, $wp_theme_directories;

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

$_config_file = $_tests_dir . '/wp-tests-config.php';

if ( ! is_readable( $_config_file ) ) {
	echo "Could not find wp-tests-config.php at '$_config_file'." . PHP_EOL;
	echo 'Run tests via: wp-env run tests-cli --env-cwd=wp-content/plugins/disable-comments -- bash -c "WP_TESTS_DIR=/wordpress-phpunit vendor/bin/phpunit --configuration phpunit-security.xml"' . PHP_EOL;
	exit( 1 );
}

// Load config first — defines WP_PHP_BINARY, DB_*, ABSPATH, MULTISITE, etc.
require_once $_config_file;

// PHPUnit polyfills: declare path so the WP test bootstrap can find them.
if ( ! defined( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ) ) {
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', dirname( __DIR__ ) . '/vendor/yoast/phpunit-polyfills' );
}

// Load WP test helpers.
require_once $_tests_dir . '/includes/functions.php';

// Load our own vendor autoloader (for yoast/phpunit-polyfills, etc.).
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

/**
 * Manually load the plugin being tested.
 */
function _security_bootstrap_load_plugin() {
	require dirname( __DIR__ ) . '/disable-comments.php';
}
tests_add_filter( 'muplugins_loaded', '_security_bootstrap_load_plugin' );

// -----------------------------------------------------------------------
// Run the WP install script in a separate process — drops and recreates
// all tables, then calls populate_network() for multisite.
//
// Known issue: when MULTISITE=true is pre-defined (as it is in this env's
// wp-tests-config.php), populate_network() skips the wp_blogs insert.
// We fix this below after the install exits.
// -----------------------------------------------------------------------
if ( '1' !== getenv( 'WP_TESTS_SKIP_INSTALL' ) ) {
	system(
		WP_PHP_BINARY . ' ' .
		escapeshellarg( $_tests_dir . '/includes/install.php' ) . ' ' .
		escapeshellarg( $_config_file ) . ' run_ms_tests no_core_tests',
		$_install_exit
	);
	if ( 0 !== $_install_exit ) {
		exit( $_install_exit );
	}
}

// -----------------------------------------------------------------------
// Fix: insert the main blog record if wp_blogs is empty.
//
// populate_network() in WordPress 6.x skips the wp_blogs INSERT when
// is_multisite() returns true (which happens because wp-tests-config.php
// pre-defines MULTISITE=true). Without at least one row in wp_blogs,
// ms-settings.php fatals on PHP 8.x when it tries to set blog properties
// on the false returned by get_site().
// -----------------------------------------------------------------------
$_fix_db = new mysqli( DB_HOST, DB_USER, DB_PASSWORD, DB_NAME );
if ( ! $_fix_db->connect_error ) {
	// Fix 1: insert the main blog record if wp_blogs is empty.
	$_blogs_count = (int) $_fix_db->query( 'SELECT COUNT(*) FROM wp_blogs' )->fetch_row()[0];
	if ( 0 === $_blogs_count ) {
		$_fix_domain = defined( 'WP_TESTS_DOMAIN' ) ? WP_TESTS_DOMAIN : 'localhost';
		$_fix_db->query(
			"INSERT INTO wp_blogs (site_id, blog_id, domain, path, registered, last_updated, public, archived, mature, spam, deleted, lang_id)" .
			" VALUES (1, 1, '" . $_fix_db->real_escape_string( $_fix_domain ) . "', '/', NOW(), NOW(), 1, 0, 0, 0, 0, 0)"
		);
	}
	// Fix 2: ensure site_admins is a properly serialized PHP array so that
	// is_super_admin() and grant_super_admin() don't fail with TypeError.
	// populate_network() may skip this when MULTISITE=true is pre-defined.
	$_admins_val = $_fix_db->query( "SELECT meta_value FROM wp_sitemeta WHERE meta_key = 'site_admins' LIMIT 1" )->fetch_row();
	if ( ! $_admins_val || ! is_array( @unserialize( $_admins_val[0] ) ) ) {
		$_admin_login = $_fix_db->query( "SELECT user_login FROM wp_users WHERE ID = 1 LIMIT 1" )->fetch_row()[0] ?? 'admin';
		$_serialized  = serialize( array( $_admin_login ) );
		if ( ! $_admins_val ) {
			$_fix_db->query(
				"INSERT INTO wp_sitemeta (site_id, meta_key, meta_value) VALUES (1, 'site_admins', '" . $_fix_db->real_escape_string( $_serialized ) . "')"
			);
		} else {
			$_fix_db->query(
				"UPDATE wp_sitemeta SET meta_value = '" . $_fix_db->real_escape_string( $_serialized ) . "' WHERE meta_key = 'site_admins' AND site_id = 1"
			);
		}
	}
	$_fix_db->close();
}
unset( $_fix_db, $_blogs_count, $_admins_val, $_admin_login, $_serialized );

// -----------------------------------------------------------------------
// Continue booting WordPress (mirrors the remainder of the standard WP
// test bootstrap after the install step).
// -----------------------------------------------------------------------
echo 'Running as multisite...' . PHP_EOL;
defined( 'MULTISITE' ) || define( 'MULTISITE', true );
defined( 'SUBDOMAIN_INSTALL' ) || define( 'SUBDOMAIN_INSTALL', false );

$GLOBALS['base'] = '/';

require ABSPATH . 'wp-settings.php';

// Load the WordPress administration APIs needed by WP_Ajax_UnitTestCase
// (set_current_screen, get_editable_roles, etc.).
require_once ABSPATH . 'wp-admin/includes/admin.php';

// Override PHPMailer.
require_once $_tests_dir . '/includes/mock-mailer.php';
$phpmailer = new MockPHPMailer( true );

// Remove default posts created during install.
_delete_all_posts();

// Load PHPUnit 6+ compatibility aliases (defines PHPUnit_Framework_Exception etc.).
require $_tests_dir . '/includes/phpunit6/compat.php';

// WP test-case infrastructure.
require $_tests_dir . '/includes/phpunit-adapter-testcase.php';
require $_tests_dir . '/includes/abstract-testcase.php';
require $_tests_dir . '/includes/testcase.php';
require $_tests_dir . '/includes/testcase-ajax.php';
require $_tests_dir . '/includes/factory.php';
require $_tests_dir . '/includes/exceptions.php';
