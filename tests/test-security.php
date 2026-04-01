<?php
/**
 * Security regression tests for the disable-comments plugin.
 *
 * Each test class maps to one vulnerability documented in .ai/security/:
 *
 *   Test_Network_Admin_Context         → #4  Network-admin context detection (replaces old is_network_admin())
 *   Test_Settings_Privilege_Escalation → #1  Vertical privilege escalation via disable_comments_settings()
 *   Test_SubSite_Enumeration           → #5  Subsite information disclosure via get_sub_sites()
 *   Test_CrossSite_Comment_Deletion    → #2  Cross-site comment deletion via delete_comments_settings()
 *   Test_Role_Name_XSS                 → #3  DOM-based XSS via unescaped role names
 *
 * Run with: ./vendor/bin/phpunit tests/test-security.php
 * Requires a multisite install (set up via .wp-env.json or WP_TESTS_MULTISITE=1).
 *
 * @package Disable_Comments
 */

// Simulate an AJAX request for the entire file so that
// is_network_admin_ajax_context() enters its AJAX branch.
if ( ! defined( 'DOING_AJAX' ) ) {
	define( 'DOING_AJAX', true );
}

// ---------------------------------------------------------------------------
// Issue #4 — Network Admin Context Detection
// @see .ai/security/4-referer-spoofing-privilege-escalation.md
//
// is_network_admin() (public, forgeable via $_REQUEST) has been removed.
// Replaced by two private methods:
//   is_network_admin_ajax_context()  — context hint, checks $_GET only
//   can_network_admin_ajax_context() — context + manage_network_plugins cap
//
// Tests use Reflection to access the private methods.
// ---------------------------------------------------------------------------
class Test_Network_Admin_Context extends WP_UnitTestCase {

	/** @var Disable_Comments */
	private $plugin;

	/** @var ReflectionMethod */
	private $is_ctx;
	private $can_ctx;

	/** @var ReflectionProperty */
	private $networkactive_ref;
	private $original_networkactive;

	/** @var array Original $_GET snapshot. */
	private $original_get;

	public function setUp(): void {
		parent::setUp();
		$this->plugin      = Disable_Comments::get_instance();
		$this->original_get = $_GET;

		$this->is_ctx = new ReflectionMethod( $this->plugin, 'is_network_admin_ajax_context' );
		$this->is_ctx->setAccessible( true );

		$this->can_ctx = new ReflectionMethod( $this->plugin, 'can_network_admin_ajax_context' );
		$this->can_ctx->setAccessible( true );

		// Simulate network-active plugin for context detection tests.
		$this->networkactive_ref = new ReflectionProperty( $this->plugin, 'networkactive' );
		$this->networkactive_ref->setAccessible( true );
		$this->original_networkactive = $this->networkactive_ref->getValue( $this->plugin );
		$this->networkactive_ref->setValue( $this->plugin, true );
	}

	public function tearDown(): void {
		$this->networkactive_ref->setValue( $this->plugin, $this->original_networkactive );
		$_GET = $this->original_get;
		parent::tearDown();
	}

	/**
	 * Spoofed HTTP_REFERER alone must NOT grant network-admin context.
	 * The old is_network_admin() method trusted $_REQUEST; the new method ignores it.
	 */
	public function test_spoofed_referer_does_not_grant_context() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Requires multisite.' );
		}

		$subscriber = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber );

		$_SERVER['HTTP_REFERER'] = network_admin_url( 'settings.php?page=disable_comments_settings' );
		unset( $_GET['is_network_admin'] );

		$this->assertFalse(
			$this->is_ctx->invoke( $this->plugin ),
			'Spoofed HTTP_REFERER must not grant network-admin context.'
		);
	}

	/**
	 * $_GET['is_network_admin']=1 grants context (it's a routing hint only).
	 */
	public function test_get_param_grants_context() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Requires multisite.' );
		}

		$admin = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );

		$_GET['is_network_admin'] = '1';

		$this->assertTrue(
			$this->is_ctx->invoke( $this->plugin ),
			'$_GET[is_network_admin]=1 must grant context regardless of caps.'
		);
	}

	/**
	 * $_POST['is_network_admin']=1 (without $_GET) must NOT grant context.
	 * This was the reviewer's attack vector — POST data forgery.
	 */
	public function test_post_param_does_not_grant_context() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Requires multisite.' );
		}

		$admin = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );

		unset( $_GET['is_network_admin'] );
		$_POST['is_network_admin'] = '1';
		$_REQUEST['is_network_admin'] = '1';

		$this->assertFalse(
			$this->is_ctx->invoke( $this->plugin ),
			'$_POST[is_network_admin] must NOT grant context — only $_GET is trusted.'
		);

		unset( $_POST['is_network_admin'], $_REQUEST['is_network_admin'] );
	}

	/**
	 * When the plugin is not network-active, context must always be false.
	 */
	public function test_not_networkactive_always_false() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Requires multisite.' );
		}

		$ref = new ReflectionProperty( $this->plugin, 'networkactive' );
		$ref->setAccessible( true );
		$original = $ref->getValue( $this->plugin );
		$ref->setValue( $this->plugin, false );

		$_GET['is_network_admin'] = '1';

		$this->assertFalse(
			$this->is_ctx->invoke( $this->plugin ),
			'Not network-active → context must be false even with the GET param.'
		);

		$ref->setValue( $this->plugin, $original );
	}

	/**
	 * No GET param → context false, even for super-admin.
	 */
	public function test_no_param_returns_false_for_super_admin() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Requires multisite.' );
		}

		$super = $this->factory->user->create( array( 'role' => 'administrator' ) );
		grant_super_admin( $super );
		wp_set_current_user( $super );

		unset( $_GET['is_network_admin'] );

		$this->assertFalse(
			$this->is_ctx->invoke( $this->plugin ),
			'Absent GET param → false even for super-admins.'
		);
	}

	/**
	 * can_network_admin_ajax_context() requires BOTH context AND capability.
	 */
	public function test_can_requires_both_context_and_cap() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Requires multisite.' );
		}

		$_GET['is_network_admin'] = '1';

		// Subsite admin: context YES, cap NO → false.
		$admin = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );
		$this->assertFalse(
			$this->can_ctx->invoke( $this->plugin ),
			'Context + no cap → can must be false.'
		);

		// Super admin: context YES, cap YES → true.
		$super = $this->factory->user->create( array( 'role' => 'administrator' ) );
		grant_super_admin( $super );
		wp_set_current_user( $super );
		$this->assertTrue(
			$this->can_ctx->invoke( $this->plugin ),
			'Context + cap → can must be true.'
		);
	}
}

// ---------------------------------------------------------------------------
// Issue #1 — Vertical Privilege Escalation via disable_comments_settings()
// @see .ai/security/1-bac-settings-privilege-escalation.md
//
// Fixed behaviour: a capability check gates every save operation.
//   - Non-admin users receive a 403-style error.
//   - On multisite, network-wide operations require manage_network_plugins;
//     a sub-site admin who embeds is_network_admin=1 in the POST payload must
//     NOT be able to update network-wide options.
// ---------------------------------------------------------------------------
class Test_Settings_Privilege_Escalation extends WP_Ajax_UnitTestCase {

	/** @var array Original $_REQUEST / $_GET snapshots. */
	private $original_request;
	private $original_get;
	private $original_networkactive;

	public function setUp(): void {
		parent::setUp();
		$this->original_request = $_REQUEST;
		$this->original_get     = $_GET;

		// Simulate network-active plugin.
		$plugin = Disable_Comments::get_instance();
		$ref = new ReflectionProperty( $plugin, 'networkactive' );
		$ref->setAccessible( true );
		$this->original_networkactive = $ref->getValue( $plugin );
		$ref->setValue( $plugin, true );
	}

	public function tearDown(): void {
		$plugin = Disable_Comments::get_instance();
		$ref = new ReflectionProperty( $plugin, 'networkactive' );
		$ref->setAccessible( true );
		$ref->setValue( $plugin, $this->original_networkactive );
		$_REQUEST = $this->original_request;
		$_GET     = $this->original_get;
		parent::tearDown();
	}

	// -----------------------------------------------------------------------
	// Helper: call the handler, return the decoded JSON response array.
	// -----------------------------------------------------------------------
	private function do_ajax_settings( $data_string ) {
		$_POST['nonce'] = wp_create_nonce( 'disable_comments_save_settings' );
		$_POST['data']  = $data_string;

		try {
			$this->_handleAjax( 'disable_comments_save_settings' );
		} catch ( WPDieException $e ) {
			// Expected — some WP test-suite versions re-throw WPDieException.
		}

		return json_decode( $this->_last_response, true );
	}

	/**
	 * A subscriber (no manage_options) must receive an error response.
	 */
	public function test_subscriber_cannot_save_settings() {
		$subscriber = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber );

		$response = $this->do_ajax_settings( 'mode=remove_everywhere' );

		$this->assertIsArray( $response, 'Response must be valid JSON.' );
		$this->assertFalse(
			$response['success'],
			'Subscriber must not be able to save plugin settings.'
		);
		$this->assertStringContainsStringIgnoringCase(
			'permissions',
			$response['data']['message'],
			'Error message must reference insufficient permissions.'
		);
	}

	/**
	 * A site administrator (manage_options, no multisite network privilege) must
	 * be able to save site-level settings on a single-site install.
	 */
	public function test_site_admin_can_save_site_level_settings() {
		if ( is_multisite() ) {
			$this->markTestSkipped( 'Single-site scenario; run on a non-multisite install.' );
		}

		$admin = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );

		$response = $this->do_ajax_settings( 'mode=remove_everywhere' );

		$this->assertIsArray( $response, 'Response must be valid JSON.' );
		$this->assertTrue(
			$response['success'],
			'Site admin must be able to save settings on a single-site install.'
		);
	}

	/**
	 * On multisite a sub-site admin who embeds is_network_admin=1 in the POST
	 * data payload must NOT cause network-wide options to be written.
	 *
	 * Exploit vector (from .ai/security/1-bac-settings-privilege-escalation.md):
	 *   data: "is_network_admin=1&sitewide_settings=1&mode=remove_everywhere"
	 *
	 * @group ms-required
	 */
	public function test_subsite_admin_cannot_escalate_to_network_wide_save() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Requires multisite.' );
		}

		// A plain site administrator: has manage_options, lacks manage_network_plugins.
		$admin = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );
		$this->assertFalse(
			current_user_can( 'manage_network_plugins' ),
			'Prerequisite: test user must lack manage_network_plugins.'
		);

		// Capture the state before the attempted exploit.
		update_site_option( 'disable_comments_sitewide_settings', 'original_value' );

		// Exploit: embed is_network_admin=1 in the data payload.
		$this->do_ajax_settings( 'is_network_admin=1&sitewide_settings=1&mode=remove_everywhere' );

		$this->assertEquals(
			'original_value',
			get_site_option( 'disable_comments_sitewide_settings' ),
			'Sub-site admin must not update network-wide sitewide_settings option via POST data escalation.'
		);
	}

	/**
	 * A subsite admin who forges ?is_network_admin=1 as a GET param must be
	 * blocked because they lack manage_network_plugins.
	 *
	 * @group ms-required
	 */
	public function test_subsite_admin_with_forged_get_param_is_blocked() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Requires multisite.' );
		}

		$admin = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );
		$this->assertFalse(
			current_user_can( 'manage_network_plugins' ),
			'Prerequisite: test user must lack manage_network_plugins.'
		);

		// Forge the GET param that the JS normally sets.
		$_GET['is_network_admin'] = '1';

		$response = $this->do_ajax_settings( 'mode=remove_everywhere' );

		$this->assertIsArray( $response, 'Response must be valid JSON.' );
		$this->assertFalse(
			$response['success'],
			'Subsite admin with forged ?is_network_admin=1 GET param must be denied.'
		);
	}

	/**
	 * When sitewide_settings=1 is active, a subsite admin must be completely
	 * blocked even without forging is_network_admin — the sitewide lock
	 * requires manage_network_plugins.
	 *
	 * @group ms-required
	 */
	public function test_subsite_admin_blocked_when_sitewide_settings_on() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Requires multisite.' );
		}

		$admin = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );

		// Activate the sitewide lock.
		update_site_option( 'disable_comments_sitewide_settings', '1' );
		// Refresh the singleton's cached value.
		$plugin = Disable_Comments::get_instance();
		$ref = new ReflectionProperty( $plugin, 'sitewide_settings' );
		$ref->setAccessible( true );
		$ref->setValue( $plugin, '1' );

		$response = $this->do_ajax_settings( 'mode=remove_everywhere' );

		$this->assertIsArray( $response, 'Response must be valid JSON.' );
		$this->assertFalse(
			$response['success'],
			'Subsite admin must be blocked when sitewide_settings is on.'
		);

		// Cleanup.
		update_site_option( 'disable_comments_sitewide_settings', false );
		$ref->setValue( $plugin, false );
	}

	/**
	 * When sitewide_settings='0' (explicitly disabled), a subsite admin must
	 * be allowed to save their own site's settings via manage_options.
	 *
	 * @group ms-required
	 */
	public function test_subsite_admin_allowed_when_sitewide_settings_zero() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Requires multisite.' );
		}

		$admin = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );

		$plugin = Disable_Comments::get_instance();
		$ref = new ReflectionProperty( $plugin, 'sitewide_settings' );
		$ref->setAccessible( true );
		$ref->setValue( $plugin, '0' );

		$response = $this->do_ajax_settings( 'mode=remove_everywhere' );

		$this->assertIsArray( $response, 'Response must be valid JSON.' );
		$this->assertTrue(
			$response['success'],
			'Subsite admin must be allowed when sitewide_settings is "0".'
		);

		$ref->setValue( $plugin, false );
	}

	/**
	 * When sitewide_settings=false (default/never set), a subsite admin must
	 * be allowed to save their own site's settings.
	 *
	 * @group ms-required
	 */
	public function test_subsite_admin_allowed_when_sitewide_settings_default() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Requires multisite.' );
		}

		$admin = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );

		$plugin = Disable_Comments::get_instance();
		$ref = new ReflectionProperty( $plugin, 'sitewide_settings' );
		$ref->setAccessible( true );
		$ref->setValue( $plugin, false );

		$response = $this->do_ajax_settings( 'mode=remove_everywhere' );

		$this->assertIsArray( $response, 'Response must be valid JSON.' );
		$this->assertTrue(
			$response['success'],
			'Subsite admin must be allowed when sitewide_settings is false (default).'
		);
	}

	/**
	 * A super-admin who explicitly marks the request as network-admin must be
	 * able to save network-wide settings.
	 *
	 * @group ms-required
	 */
	public function test_super_admin_can_save_network_wide_settings() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Requires multisite.' );
		}

		$super = $this->factory->user->create( array( 'role' => 'administrator' ) );
		grant_super_admin( $super );
		wp_set_current_user( $super );

		// is_network_admin_ajax_context() checks $_GET only — match the real
		// JS behaviour which appends ?is_network_admin=1 as a GET param.
		$_GET['is_network_admin'] = '1';

		$response = $this->do_ajax_settings( 'sitewide_settings=1&mode=remove_everywhere' );

		$this->assertIsArray( $response, 'Response must be valid JSON.' );
		$this->assertTrue(
			$response['success'],
			'Super-admin must be able to save network-wide settings.'
		);
	}
}

// ---------------------------------------------------------------------------
// Issue #5 — Subsite Enumeration Information Disclosure via get_sub_sites()
// @see .ai/security/5-info-disclosure-get-sub-sites.md
//
// Fixed behaviour: when the plugin is network-active, only users with
// manage_network_plugins can retrieve the subsite list.
// ---------------------------------------------------------------------------
class Test_SubSite_Enumeration extends WP_Ajax_UnitTestCase {

	/** @var array Original $_REQUEST / $_GET snapshots. */
	private $original_request;
	private $original_get;
	private $original_networkactive;

	public function setUp(): void {
		parent::setUp();
		$this->original_request = $_REQUEST;
		$this->original_get     = $_GET;

		$plugin = Disable_Comments::get_instance();
		$ref = new ReflectionProperty( $plugin, 'networkactive' );
		$ref->setAccessible( true );
		$this->original_networkactive = $ref->getValue( $plugin );
		$ref->setValue( $plugin, true );
	}

	public function tearDown(): void {
		$plugin = Disable_Comments::get_instance();
		$ref = new ReflectionProperty( $plugin, 'networkactive' );
		$ref->setAccessible( true );
		$ref->setValue( $plugin, $this->original_networkactive );
		$_REQUEST = $this->original_request;
		$_GET     = $this->original_get;
		parent::tearDown();
	}

	// -----------------------------------------------------------------------
	// Helper: call the handler, return decoded JSON response array.
	// -----------------------------------------------------------------------
	private function do_ajax_get_sub_sites() {
		// _handleAjax() rebuilds $_REQUEST from array_merge($_POST, $_GET),
		// so the nonce must live in $_GET (or $_POST) to survive the merge.
		$_GET['nonce'] = wp_create_nonce( 'disable_comments_save_settings' );
		$_GET['type']  = 'disabled';

		try {
			$this->_handleAjax( 'get_sub_sites' );
		} catch ( WPDieException $e ) {
			// Expected.
		}

		return json_decode( $this->_last_response, true );
	}

	/**
	 * A subscriber must receive an empty data set.
	 *
	 * @group ms-required
	 */
	public function test_subscriber_cannot_enumerate_subsites() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Requires multisite.' );
		}

		$subscriber = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber );

		$response = $this->do_ajax_get_sub_sites();

		$this->assertIsArray( $response, 'Response must be valid JSON.' );
		$this->assertEmpty(
			$response['data'],
			'Subscriber must not receive the subsite list.'
		);
		$this->assertEquals(
			0,
			(int) $response['totalNumber'],
			'Subscriber must see a total count of zero.'
		);
	}

	/**
	 * A user with only the editor role must also be denied.
	 *
	 * @group ms-required
	 */
	public function test_editor_cannot_enumerate_subsites() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Requires multisite.' );
		}

		$editor = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor );

		$response = $this->do_ajax_get_sub_sites();

		$this->assertIsArray( $response );
		$this->assertEmpty( $response['data'], 'Editor must not receive the subsite list.' );
		$this->assertEquals( 0, (int) $response['totalNumber'] );
	}

	/**
	 * A subsite admin (manage_options only) must be blocked when the plugin
	 * is network-active — get_sub_sites requires manage_network_plugins.
	 *
	 * @group ms-required
	 */
	public function test_subsite_admin_cannot_enumerate_when_networkactive() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Requires multisite.' );
		}

		$admin = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );
		$this->assertFalse(
			current_user_can( 'manage_network_plugins' ),
			'Prerequisite: test user must lack manage_network_plugins.'
		);

		$response = $this->do_ajax_get_sub_sites();

		$this->assertIsArray( $response );
		$this->assertEmpty(
			$response['data'],
			'Subsite admin must not receive the subsite list when plugin is network-active.'
		);
	}

	/**
	 * A super-admin must receive the subsite list.
	 *
	 * @group ms-required
	 */
	public function test_super_admin_can_retrieve_subsites() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Requires multisite.' );
		}

		$super = $this->factory->user->create( array( 'role' => 'administrator' ) );
		grant_super_admin( $super );
		wp_set_current_user( $super );

		$response = $this->do_ajax_get_sub_sites();

		$this->assertIsArray( $response );
		$this->assertGreaterThanOrEqual(
			1,
			(int) $response['totalNumber'],
			'Super-admin must see at least the main site in the subsite list.'
		);
	}
}

// ---------------------------------------------------------------------------
// Issue #2 — Cross-Site Arbitrary Comment Deletion via delete_comments_settings()
// @see .ai/security/2-bac-cross-site-comment-deletion.md
//
// Fixed behaviour:
//   1. Top-level capability check blocks non-admin users.
//   2. Per-blog authorisation check (switch_to_blog + current_user_can)
//      prevents deleting comments from sites the requester does not administer.
// ---------------------------------------------------------------------------
class Test_CrossSite_Comment_Deletion extends WP_Ajax_UnitTestCase {

	/** @var int Blog ID of a secondary test site. */
	private $second_site_id;

	/** @var int User ID of a user who administers only the second site. */
	private $second_site_admin_id;

	/** @var array Original $_REQUEST / $_GET snapshots. */
	private $original_request;
	private $original_get;
	private $original_networkactive;

	public function setUp(): void {
		parent::setUp();
		$this->original_request = $_REQUEST;
		$this->original_get     = $_GET;

		$plugin = Disable_Comments::get_instance();
		$ref = new ReflectionProperty( $plugin, 'networkactive' );
		$ref->setAccessible( true );
		$this->original_networkactive = $ref->getValue( $plugin );
		$ref->setValue( $plugin, true );

		if ( ! is_multisite() ) {
			return;
		}

		// Create a secondary site.
		$this->second_site_id = $this->factory->blog->create();

		// Create a user with no privilege on the main site, but administrator on site 2.
		$this->second_site_admin_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		add_user_to_blog( $this->second_site_id, $this->second_site_admin_id, 'administrator' );
	}

	public function tearDown(): void {
		$plugin = Disable_Comments::get_instance();
		$ref = new ReflectionProperty( $plugin, 'networkactive' );
		$ref->setAccessible( true );
		$ref->setValue( $plugin, $this->original_networkactive );
		if ( is_multisite() && $this->second_site_id ) {
			wpmu_delete_blog( $this->second_site_id, true );
		}
		$_REQUEST = $this->original_request;
		$_GET     = $this->original_get;
		parent::tearDown();
	}

	// -----------------------------------------------------------------------
	// Helper: call the delete handler, return decoded JSON response.
	// -----------------------------------------------------------------------
	private function do_ajax_delete( $data_string ) {
		$_POST['nonce'] = wp_create_nonce( 'disable_comments_save_settings' );
		$_POST['data']  = $data_string;

		try {
			$this->_handleAjax( 'disable_comments_delete_comments' );
		} catch ( WPDieException $e ) {
			// Expected.
		}

		return json_decode( $this->_last_response, true );
	}

	/**
	 * A subscriber must be blocked at the top-level capability check.
	 */
	public function test_subscriber_cannot_invoke_delete_handler() {
		$subscriber = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber );

		$response = $this->do_ajax_delete( 'delete_mode=delete_everywhere' );

		$this->assertIsArray( $response, 'Response must be valid JSON.' );
		$this->assertFalse(
			$response['success'],
			'Subscriber must not be able to invoke the comment-delete handler.'
		);
		$this->assertStringContainsStringIgnoringCase(
			'permissions',
			$response['data']['message']
		);
	}

	/**
	 * A sub-site admin (admin on site 2 only, subscriber on main site) who submits
	 * is_network_admin=1 must NOT be able to delete comments from a third site
	 * they have never been a member of.
	 *
	 * This tests the per-blog authorisation fix:
	 *   switch_to_blog($id); if (!is_super_admin() && !current_user_can('manage_options')) continue;
	 *
	 * @group ms-required
	 */
	public function test_subsite_admin_cannot_delete_comments_from_foreign_site() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Requires multisite.' );
		}

		// Create a third site the attacker has NEVER been added to.
		$third_site_id = $this->factory->blog->create();

		// Add a comment on the third site.
		switch_to_blog( $third_site_id );
		$post_id    = $this->factory->post->create();
		$comment_id = $this->factory->comment->create( array( 'comment_post_ID' => $post_id ) );
		restore_current_blog();

		// Log in as the admin of the second site (subscriber on main site).
		wp_set_current_user( $this->second_site_admin_id );

		// Exploit: force network-admin context via embedded flag and target site 3.
		$data = 'is_network_admin=1&delete_mode=delete_everywhere' .
		        '&disabled_sites%5Bsite_' . $third_site_id . '%5D=1';
		$this->do_ajax_delete( $data );

		// The comment on the third site must still exist.
		switch_to_blog( $third_site_id );
		$comment = get_comment( $comment_id );
		restore_current_blog();

		$this->assertNotNull(
			$comment,
			'Comment on a foreign site must not be deleted by a sub-site admin who is not a member.'
		);

		// Cleanup.
		wpmu_delete_blog( $third_site_id, true );
	}

	/**
	 * A super-admin must be authorised to delete comments from any site.
	 *
	 * @group ms-required
	 */
	public function test_super_admin_can_delete_from_any_site() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Requires multisite.' );
		}

		// Add a comment on the second site.
		switch_to_blog( $this->second_site_id );
		$post_id = $this->factory->post->create();
		$this->factory->comment->create( array( 'comment_post_ID' => $post_id ) );
		restore_current_blog();

		$super = $this->factory->user->create( array( 'role' => 'administrator' ) );
		grant_super_admin( $super );
		wp_set_current_user( $super );

		// is_network_admin_ajax_context() checks $_GET only — match the real
		// JS behaviour which appends ?is_network_admin=1 as a GET param.
		$_GET['is_network_admin'] = '1';
		$data = 'delete_mode=delete_everywhere' .
		        '&disabled_sites%5Bsite_' . $this->second_site_id . '%5D=1';
		$response = $this->do_ajax_delete( $data );

		$this->assertIsArray( $response );
		$this->assertTrue(
			$response['success'],
			'Super-admin must be able to delete comments from any site.'
		);
	}

	/**
	 * When sitewide_settings='1', a subsite admin must be blocked from
	 * delete_comments_settings() — same sitewide guard as save handler.
	 *
	 * @group ms-required
	 */
	public function test_subsite_admin_blocked_from_delete_when_sitewide_on() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Requires multisite.' );
		}

		$admin = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );

		$plugin = Disable_Comments::get_instance();
		$ref = new ReflectionProperty( $plugin, 'sitewide_settings' );
		$ref->setAccessible( true );
		$ref->setValue( $plugin, '1' );

		$response = $this->do_ajax_delete( 'delete_mode=delete_everywhere' );

		$this->assertIsArray( $response, 'Response must be valid JSON.' );
		$this->assertFalse(
			$response['success'],
			'Subsite admin must be blocked from deleting when sitewide_settings is on.'
		);

		$ref->setValue( $plugin, false );
	}
}

// ---------------------------------------------------------------------------
// Issue #3 — DOM-based XSS via Unescaped Role Names in get_roles()
// @see .ai/security/3-dom-xss-role-exclusion.md
//
// Root cause: translate_user_role() output is not wrapped in esc_html() before
// being JSON-encoded into a data-options HTML attribute.  A role with a payload
// name breaks out of the attribute and — before the JS-side fix — executed when
// the admin selected the role in the UI.
//
// Fix required (PHP layer): esc_html( translate_user_role( $details['name'] ) )
// Fix applied (JS layer):   escapedLabels via $('<span>').text(label).html()
//
// This test verifies the PHP fix is in place.  If it fails, the PHP-side
// escape is missing and the attribute injection vector remains open.
// ---------------------------------------------------------------------------
class Test_Role_Name_XSS extends WP_UnitTestCase {

	/** @var Disable_Comments */
	private $plugin;

	/** @var string Slug for the temporary XSS test role. */
	private $xss_role_slug = 'dc_security_test_xss_role';

	public function setUp(): void {
		parent::setUp();
		$this->plugin = Disable_Comments::get_instance();

		// Register a role whose display name is an HTML injection payload.
		// This simulates an attacker adding a malicious role via a role-management
		// plugin, LDAP sync, or multisite sub-site permissions.
		add_role(
			$this->xss_role_slug,
			'<img src=x onerror=alert(document.cookie)>',
			array( 'read' => true )
		);
	}

	public function tearDown(): void {
		remove_role( $this->xss_role_slug );
		parent::tearDown();
	}

	/**
	 * No role's text field in the get_roles() output must contain a raw HTML tag.
	 *
	 * Failure means translate_user_role() output is not passed through esc_html(),
	 * leaving the data-options attribute injectable via a crafted role name.
	 */
	public function test_role_names_contain_no_raw_html_tags() {
		$roles = $this->plugin->get_roles( array() );

		foreach ( $roles as $role ) {
			$this->assertStringNotContainsString(
				'<img',
				$role['text'],
				sprintf(
					'Role "%s" text contains a raw <img> tag. Missing esc_html() on translate_user_role() output at disable-comments.php:1125.',
					$role['id']
				)
			);
			$this->assertStringNotContainsString(
				'<script',
				$role['text'],
				sprintf( 'Role "%s" text contains a raw <script> tag.', $role['id'] )
			);
			// Note: esc_html() escapes < and > so any event-handler attributes become
			// unreachable — checking for the literal "onerror=" substring is not
			// meaningful once the surrounding tag characters are entity-encoded.
		}
	}

	/**
	 * The payload role's display text must have its angle brackets HTML-entity-encoded.
	 *
	 * Pass:  text === '&lt;img src=x onerror=alert(document.cookie)&gt;'
	 * Fail:  text === '<img src=x onerror=alert(document.cookie)>'
	 *        → attribute injection / XSS vector is still open.
	 */
	public function test_xss_payload_in_role_name_is_entity_encoded() {
		$roles     = $this->plugin->get_roles( array() );
		$xss_entry = null;

		foreach ( $roles as $role ) {
			if ( $role['id'] === $this->xss_role_slug ) {
				$xss_entry = $role;
				break;
			}
		}

		$this->assertNotNull(
			$xss_entry,
			'The XSS test role must appear in the roles list returned by get_roles().'
		);

		// After esc_html(), < and > must be converted to &lt; and &gt;.
		$this->assertStringContainsString(
			'&lt;',
			$xss_entry['text'],
			'Opening angle bracket must be HTML-entity-encoded (&lt;) — add esc_html() around translate_user_role() in get_roles().'
		);
		$this->assertStringContainsString(
			'&gt;',
			$xss_entry['text'],
			'Closing angle bracket must be HTML-entity-encoded (&gt;) — add esc_html() around translate_user_role() in get_roles().'
		);
	}

	/**
	 * The JSON-encoded data-options attribute must not contain unescaped HTML
	 * that could break out of the attribute context.
	 *
	 * wp_json_encode() by default does not apply JSON_HEX_TAG, so a raw '<' in
	 * a role name would pass through and could allow attribute-context injection.
	 */
	public function test_json_encoded_roles_are_safe_for_html_attribute() {
		$roles   = $this->plugin->get_roles( array() );
		$encoded = wp_json_encode( $roles );

		$this->assertNotFalse( $encoded, 'wp_json_encode() must succeed.' );

		// If esc_html() has been applied, angle brackets are already entities
		// before encoding, so the final JSON will contain \u003C / \u003E or
		// the literal entity strings — but NOT unescaped < directly followed by
		// an HTML tag name.
		$this->assertDoesNotMatchRegularExpression(
			'/<img\s/i',
			$encoded,
			'JSON-encoded roles must not contain a raw <img tag that could escape the data-options attribute.'
		);
		$this->assertDoesNotMatchRegularExpression(
			'/<script[\s>]/i',
			$encoded,
			'JSON-encoded roles must not contain a raw <script tag.'
		);
	}
}

// ---------------------------------------------------------------------------
// PR #161 Review Comment r3019649044 — get_sub_sites() per-site activation bypass
// @see https://github.com/WPDevelopers/disable-comments/pull/161#discussion_r3019649044
//
// When the plugin is activated per-site (NOT network-wide) on a multisite
// install, $this->networkactive is false. The old code fell back to
// manage_options for the capability check, allowing any subsite admin to
// enumerate all network sites. The fix uses is_multisite() instead, so the
// cap is always manage_network_plugins on multisite regardless of activation.
// ---------------------------------------------------------------------------
class Test_SubSite_Enumeration_PerSite extends WP_Ajax_UnitTestCase {

	private $original_request;
	private $original_get;
	private $original_networkactive;

	public function setUp() {
		parent::setUp();
		$this->original_request = $_REQUEST;
		$this->original_get     = $_GET;

		// Simulate per-site activation: networkactive = false
		$plugin = Disable_Comments::get_instance();
		$ref = new ReflectionProperty( $plugin, 'networkactive' );
		$ref->setAccessible( true );
		$this->original_networkactive = $ref->getValue( $plugin );
		$ref->setValue( $plugin, false );
	}

	public function tearDown() {
		$plugin = Disable_Comments::get_instance();
		$ref = new ReflectionProperty( $plugin, 'networkactive' );
		$ref->setAccessible( true );
		$ref->setValue( $plugin, $this->original_networkactive );
		$_REQUEST = $this->original_request;
		$_GET     = $this->original_get;
		parent::tearDown();
	}

	private function do_ajax_get_sub_sites() {
		$_GET['nonce'] = wp_create_nonce( 'disable_comments_save_settings' );
		$_GET['type']  = 'disabled';

		try {
			$this->_handleAjax( 'get_sub_sites' );
		} catch ( WPDieException $e ) {
			// Expected.
		}

		return json_decode( $this->_last_response, true );
	}

	/**
	 * A subsite admin (manage_options only) must NOT be able to enumerate
	 * network sites even when the plugin is activated per-site (networkactive=false).
	 *
	 * This is the exact bug reported in PR #161 review comment r3019649044:
	 * old code used $this->networkactive ? 'manage_network_plugins' : 'manage_options'
	 * which fell through to manage_options when per-site activated, allowing any
	 * subsite admin to list all sites.
	 *
	 * @group ms-required
	 */
	public function test_subsite_admin_cannot_enumerate_when_persite_activated() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Requires multisite.' );
		}

		$admin = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );
		$this->assertTrue(
			current_user_can( 'manage_options' ),
			'Prerequisite: test user must have manage_options.'
		);
		$this->assertFalse(
			current_user_can( 'manage_network_plugins' ),
			'Prerequisite: test user must lack manage_network_plugins.'
		);

		$response = $this->do_ajax_get_sub_sites();

		$this->assertIsArray( $response );
		$this->assertEmpty(
			$response['data'],
			'Subsite admin must NOT enumerate sites when plugin is per-site activated. ' .
			'Bug: capability check used $this->networkactive instead of is_multisite().'
		);
		$this->assertEquals(
			0,
			(int) $response['totalNumber'],
			'totalNumber must be 0 for non-super-admin even with per-site activation.'
		);
	}

	/**
	 * A super-admin can still enumerate even with per-site activation.
	 *
	 * @group ms-required
	 */
	public function test_super_admin_can_enumerate_when_persite_activated() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Requires multisite.' );
		}

		$super = $this->factory->user->create( array( 'role' => 'administrator' ) );
		grant_super_admin( $super );
		wp_set_current_user( $super );

		$response = $this->do_ajax_get_sub_sites();

		$this->assertIsArray( $response );
		$this->assertGreaterThanOrEqual(
			1,
			(int) $response['totalNumber'],
			'Super-admin must see sites even with per-site activation.'
		);
	}
}

// ---------------------------------------------------------------------------
// PR #161 Review Comment r3019664445 — PHP 5.6 compatibility (no type hints)
// @see https://github.com/WPDevelopers/disable-comments/pull/161#discussion_r3019664445
//
// The plugin declares "Requires PHP: 5.6" in readme.txt. Scalar type
// declarations (bool, int, string, void, etc.) cause fatal parse errors on
// PHP 5.6. This test scans the main plugin file to ensure no such
// declarations exist.
// ---------------------------------------------------------------------------
class Test_PHP56_Compatibility extends WP_UnitTestCase {

	/**
	 * The main plugin file must not contain PHP 7+ scalar type declarations
	 * in function/method signatures that would cause a parse error on PHP 5.6.
	 *
	 * Patterns checked:
	 *   - Parameter type hints: function foo(bool $x, int $y, string $z)
	 *   - Return type declarations: function foo(): bool, : void, : string
	 *   - Nullable types: function foo(?string $x)
	 *   - Union types: function foo(int|string $x)
	 */
	public function test_no_scalar_type_declarations_in_main_plugin_file() {
		$plugin_file = dirname( dirname( __FILE__ ) ) . '/disable-comments.php';
		$this->assertFileExists( $plugin_file );

		$contents = file_get_contents( $plugin_file );

		// Match parameter type declarations: (bool $var, int $var, string $var, etc.)
		$scalar_types = array( 'bool', 'int', 'float', 'string', 'void', 'never', 'mixed', 'null', 'false', 'true' );
		$pattern = '/function\s+\w+\s*\([^)]*\b(' . implode( '|', $scalar_types ) . ')\s+\$/m';

		$this->assertDoesNotMatchRegularExpression(
			$pattern,
			$contents,
			'Main plugin file must not use scalar parameter type hints (PHP 7.0+). ' .
			'The plugin requires PHP 5.6 per readme.txt.'
		);

		// Match return type declarations: ): bool, ): void, ): string
		$return_pattern = '/\)\s*:\s*\??\s*(' . implode( '|', $scalar_types ) . ')\b/m';

		$this->assertDoesNotMatchRegularExpression(
			$return_pattern,
			$contents,
			'Main plugin file must not use return type declarations (PHP 7.0+). ' .
			'The plugin requires PHP 5.6 per readme.txt.'
		);

		// Match nullable types: ?string, ?int
		$nullable_pattern = '/function\s+\w+\s*\([^)]*\?\s*(' . implode( '|', $scalar_types ) . ')\s+\$/m';

		$this->assertDoesNotMatchRegularExpression(
			$nullable_pattern,
			$contents,
			'Main plugin file must not use nullable type hints (PHP 7.1+). ' .
			'The plugin requires PHP 5.6 per readme.txt.'
		);
	}

	/**
	 * The plugin must not use null coalescing operator (??) which requires PHP 7.0+.
	 */
	public function test_no_null_coalescing_in_main_plugin_file() {
		$plugin_file = dirname( dirname( __FILE__ ) ) . '/disable-comments.php';
		$contents = file_get_contents( $plugin_file );

		// Match ?? but not inside strings or comments (rough heuristic)
		// We check for ?? that's NOT inside a quoted string
		$lines = explode( "\n", $contents );
		$violations = array();
		foreach ( $lines as $i => $line ) {
			$trimmed = trim( $line );
			// Skip comment-only lines
			if ( strpos( $trimmed, '//' ) === 0 || strpos( $trimmed, '*' ) === 0 || strpos( $trimmed, '/*' ) === 0 ) {
				continue;
			}
			// Check for ?? outside of string literals (simple heuristic)
			if ( strpos( $line, '??' ) !== false ) {
				// Remove string contents before checking
				$stripped = preg_replace( '/(["\'])(?:(?!\1).)*\1/', '', $line );
				if ( strpos( $stripped, '??' ) !== false ) {
					$violations[] = sprintf( 'Line %d: %s', $i + 1, trim( $line ) );
				}
			}
		}

		$this->assertEmpty(
			$violations,
			"Main plugin file must not use null coalescing operator ?? (PHP 7.0+).\n" .
			"Violations:\n" . implode( "\n", $violations )
		);
	}

	/**
	 * The CLI include file must also be PHP 5.6 compatible.
	 */
	public function test_no_scalar_type_declarations_in_cli_file() {
		$cli_file = dirname( dirname( __FILE__ ) ) . '/includes/cli.php';
		if ( ! file_exists( $cli_file ) ) {
			$this->markTestSkipped( 'CLI file not found.' );
		}

		$contents = file_get_contents( $cli_file );
		$scalar_types = array( 'bool', 'int', 'float', 'string', 'void', 'never', 'mixed' );
		$pattern = '/function\s+\w+\s*\([^)]*\b(' . implode( '|', $scalar_types ) . ')\s+\$/m';

		$this->assertDoesNotMatchRegularExpression(
			$pattern,
			$contents,
			'CLI file must not use scalar parameter type hints (PHP 7.0+).'
		);
	}
}
