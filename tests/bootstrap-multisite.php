<?php
/**
 * PHPUnit bootstrap file — multisite mode.
 *
 * Why this exists (do NOT remove these comments):
 *
 * wp-phpunit's populate_network() skips the wp_blogs INSERT when is_multisite()
 * returns true (line ~1095 of schema.php: `if ( ! is_multisite() ) { INSERT … }`).
 * Result: after install.php runs, wp_blogs is always EMPTY in our multisite
 * test environment.
 *
 * When wp-settings.php then loads ms-settings.php, it calls
 * ms_load_current_site_and_network() which returns false (no blog found), then
 * ms_not_installed() which calls wp_die(). The default test die handler
 * (_wp_die_handler) merely *prints* the error and returns — it does NOT exit or
 * throw. Execution continues into the post-if block where
 * `$current_blog->site_id = 1` (with $current_blog = null) causes a PHP 8.2
 * fatal Error.
 *
 * Fix:
 *   1. Pre-populate the $current_blog / $current_site globals with minimal stubs
 *      so ms-settings.php skips the lookup block entirely.
 *   2. Hook muplugins_loaded (fires during wp-settings.php, after dbDelta has run)
 *      to INSERT the real row into wp_blogs and refresh the global from the DB.
 *
 * @package Disable_Comments
 */

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find WordPress test library at '$_tests_dir'." . PHP_EOL;
	exit( 1 );
}

require_once $_tests_dir . '/includes/functions.php';

// -------------------------------------------------------------------------
// 1. Pre-populate multisite globals so ms-settings.php skips its crash path.
//    The stubs use the constants that wp-tests-config.php will define.
//    WP_TESTS_DOMAIN is defined by wp-tests-config.php which the wp-phpunit
//    bootstrap will load.  Use the known default ('localhost:8891') here;
//    if it ever changes, the tests-cli container's wp-tests-config.php must
//    also change.
// -------------------------------------------------------------------------
$_dc_ms_domain = defined( 'WP_TESTS_DOMAIN' ) ? WP_TESTS_DOMAIN : 'localhost:8891';

global $current_blog, $current_site;

$current_site         = new stdClass();
$current_site->id     = 1;
$current_site->domain = $_dc_ms_domain;
$current_site->path   = '/';
$current_site->blog_id = 1;
$current_site->site_name = 'Test Network';

$current_blog           = new stdClass();
$current_blog->blog_id  = 1;
$current_blog->site_id  = 1;
$current_blog->domain   = $_dc_ms_domain;
$current_blog->path     = '/';
$current_blog->public   = 1;
$current_blog->archived = 0;
$current_blog->mature   = 0;
$current_blog->spam     = 0;
$current_blog->deleted  = 0;
$current_blog->lang_id  = 0;

unset( $_dc_ms_domain );

// -------------------------------------------------------------------------
// 2. After WordPress finishes loading (muplugins_loaded), insert the actual
//    wp_blogs row so the DB matches the stub globals.  Also refresh the
//    global $current_blog to a proper WP_Site object from the DB.
// -------------------------------------------------------------------------
tests_add_filter(
	'muplugins_loaded',
	static function () {
		global $wpdb, $current_blog;

		$domain = defined( 'DOMAIN_CURRENT_SITE' ) ? DOMAIN_CURRENT_SITE : 'localhost:8891';

		// Insert the main blog row — populate_network() skips this in multisite mode.
		$wpdb->query(
			$wpdb->prepare(
				"INSERT IGNORE INTO {$wpdb->blogs}
				 (blog_id, site_id, domain, path, registered, last_updated, public, archived, mature, spam, deleted, lang_id)
				 VALUES (%d, %d, %s, %s, NOW(), NOW(), 1, 0, 0, 0, 0, 0)",
				1,
				1,
				$domain,
				'/'
			)
		);

		// Fix site_admins: populate_network_meta() stores get_site_option('site_admins')
		// which is '' (empty string) in a fresh multisite install, not an array.
		// grant_super_admin() calls in_array() on this value and fatal-errors.
		$site_admins = get_site_option( 'site_admins' );
		if ( ! is_array( $site_admins ) ) {
			update_site_option( 'site_admins', array( 'admin' ) );
		}

		// Refresh to the real WP_Site object now that the row exists.
		$blog = get_site( 1 );
		if ( $blog ) {
			$current_blog = $blog;
		}
	},
	0  // Priority 0 — run before the plugin loader.
);

// -------------------------------------------------------------------------
// 3. Load the plugin under test.
// -------------------------------------------------------------------------
function _manually_load_plugin() {
	require dirname( __DIR__ ) . '/disable-comments.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// -------------------------------------------------------------------------
// 4. Start the WP testing environment (multisite mode).
//    MULTISITE stays true as set by wp-tests-config.php.
// -------------------------------------------------------------------------
require $_tests_dir . '/includes/bootstrap.php';

// Silence "constant already defined" warnings from singleton resets in tests.
$_dc_prev_handler = set_error_handler(
	static function ( $errno, $errstr, $errfile, $errline ) use ( &$_dc_prev_handler ) {
		if ( E_WARNING === $errno && false !== strpos( $errstr, 'already defined' ) ) {
			return true;
		}
		if ( is_callable( $_dc_prev_handler ) ) {
			return ( $_dc_prev_handler )( $errno, $errstr, $errfile, $errline );
		}
		return false;
	}
);

require_once __DIR__ . '/helpers/trait-plugin-options.php';
