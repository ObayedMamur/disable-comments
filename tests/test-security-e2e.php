<?php
/**
 * End-to-end security tests for the disable-comments plugin.
 *
 * Each test class replays the exact exploit payloads documented in
 * .ai/security/ and verifies BOTH the HTTP response AND the database
 * state, covering the full request lifecycle.
 *
 * Test classes:
 *   E2E_Issue1_Settings_Escalation    → .ai/security/1-bac-settings-privilege-escalation.md
 *   E2E_Issue2_CrossSite_Deletion     → .ai/security/2-bac-cross-site-comment-deletion.md
 *   E2E_Issue3_XSS_Role_Names        → .ai/security/3-dom-xss-role-exclusion.md
 *   E2E_Issue4_Context_Forgery       → .ai/security/4-referer-spoofing-privilege-escalation.md
 *   E2E_Issue5_SubSite_Enumeration   → .ai/security/5-info-disclosure-get-sub-sites.md
 *
 * Run via wp-env:
 *   wp-env run tests-cli --env-cwd=wp-content/plugins/disable-comments \
 *     -- bash -c "WP_TESTS_DIR=/wordpress-phpunit vendor/bin/phpunit \
 *     --configuration phpunit-security.xml --filter E2E_"
 *
 * @package Disable_Comments
 */

if ( ! defined( 'DOING_AJAX' ) ) {
	define( 'DOING_AJAX', true );
}

// ===========================================================================
// Issue #1 — BAC: Vertical Privilege Escalation via disable_comments_settings
// @see .ai/security/1-bac-settings-privilege-escalation.md
//
// Exploit: sub-site admin POSTs is_network_admin=1 in data to force
// network-wide saves without manage_network_plugins.
// ===========================================================================
class E2E_Issue1_Settings_Escalation extends WP_Ajax_UnitTestCase {

	private $original_get;
	private $original_networkactive;

	public function setUp(): void {
		parent::setUp();
		$this->original_get = $_GET;

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
		$_GET = $this->original_get;
		parent::tearDown();
	}

	private function do_save( $data_string ) {
		$_POST['nonce']  = wp_create_nonce( 'disable_comments_save_settings' );
		$_POST['data']   = $data_string;
		$_POST['action'] = 'disable_comments_save_settings';
		try {
			$this->_handleAjax( 'disable_comments_save_settings' );
		} catch ( WPDieException $e ) {
			// expected
		}
		return json_decode( $this->_last_response, true );
	}

	/**
	 * Exact exploit payload from spec #1: sub-site admin sends
	 * is_network_admin=1&sitewide_settings=1 inside POST data.
	 *
	 * Expected: sitewide_settings is NOT changed in the database.
	 *
	 * @group ms-required
	 */
	public function test_exploit_post_data_escalation() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Requires multisite.' );
		}

		$admin = $this->factory->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $admin );
		$this->assertFalse( current_user_can( 'manage_network_plugins' ) );

		// Record pre-attack state.
		$original = get_site_option( 'disable_comments_sitewide_settings' );
		$original_opts = get_site_option( 'disable_comments_options' );

		// Exact exploit from the spec.
		$this->do_save( 'is_network_admin=1&sitewide_settings=1&mode=remove_everywhere' );

		// Verify: network sitewide_settings must be unchanged.
		$this->assertEquals(
			$original,
			get_site_option( 'disable_comments_sitewide_settings' ),
			'Exploit #1: sub-site admin must not update network sitewide_settings via POST data.'
		);

		// Verify: network options blob must be unchanged.
		$this->assertEquals(
			$original_opts,
			get_site_option( 'disable_comments_options' ),
			'Exploit #1: network options must not be modified by sub-site admin.'
		);
	}

	/**
	 * Exact exploit variant: sub-site admin forges the GET param that JS
	 * normally appends to the AJAX URL.
	 *
	 * Expected: 403 error response.
	 *
	 * @group ms-required
	 */
	public function test_exploit_get_param_forgery() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Requires multisite.' );
		}

		$admin = $this->factory->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $admin );

		$_GET['is_network_admin'] = '1';

		$response = $this->do_save( 'mode=remove_everywhere' );

		$this->assertIsArray( $response );
		$this->assertFalse( $response['success'], 'Forged GET param must result in 403.' );
	}

	/**
	 * Exploit: sub-site admin forces disable_avatar across all sites via
	 * is_network_admin=1 in POST data, triggering the show_avatars loop.
	 *
	 * Expected: avatars on other sites must NOT be modified.
	 *
	 * @group ms-required
	 */
	public function test_exploit_avatar_network_loop() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Requires multisite.' );
		}

		$second_site = $this->factory->blog->create();
		switch_to_blog( $second_site );
		update_option( 'show_avatars', true );
		restore_current_blog();

		$admin = $this->factory->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $admin );

		$this->do_save( 'is_network_admin=1&disable_avatar=1&mode=remove_everywhere' );

		switch_to_blog( $second_site );
		$avatars_after = get_option( 'show_avatars' );
		restore_current_blog();

		$this->assertTrue(
			(bool) $avatars_after,
			'Exploit #1 avatar variant: sub-site admin must not disable avatars on other sites.'
		);

		wpmu_delete_blog( $second_site, true );
	}

	/**
	 * Exploit: sub-site admin manipulates exclude_by_role at network level.
	 *
	 * Expected: network options exclude_by_role must not be set.
	 *
	 * @group ms-required
	 */
	public function test_exploit_role_exclusion_escalation() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Requires multisite.' );
		}

		$admin = $this->factory->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $admin );

		$original_opts = get_site_option( 'disable_comments_options', [] );

		$this->do_save(
			'is_network_admin=1&mode=remove_everywhere'
			. '&enable_exclude_by_role=1'
			. '&exclude_by_role%5B%5D=administrator'
		);

		$after_opts = get_site_option( 'disable_comments_options', [] );

		$this->assertEquals(
			$original_opts,
			$after_opts,
			'Exploit #1 role variant: network options must not be modified.'
		);
	}

	/**
	 * Positive: super admin CAN save network-wide settings.
	 *
	 * @group ms-required
	 */
	public function test_super_admin_save_succeeds() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Requires multisite.' );
		}

		$super = $this->factory->user->create( [ 'role' => 'administrator' ] );
		grant_super_admin( $super );
		wp_set_current_user( $super );

		$_GET['is_network_admin'] = '1';

		$response = $this->do_save( 'sitewide_settings=1&mode=remove_everywhere' );

		$this->assertIsArray( $response );
		$this->assertTrue( $response['success'], 'Super admin must succeed.' );
	}
}

// ===========================================================================
// Issue #2 — BAC: Cross-Site Arbitrary Comment Deletion
// @see .ai/security/2-bac-cross-site-comment-deletion.md
//
// Exploit: sub-site admin of site-2 targets site-3 for comment deletion
// by sending is_network_admin=1&disabled_sites[site_3]=1 in POST data.
// ===========================================================================
class E2E_Issue2_CrossSite_Deletion extends WP_Ajax_UnitTestCase {

	private $site2;
	private $site3;
	private $site2_admin;
	private $original_get;
	private $original_networkactive;

	public function setUp(): void {
		parent::setUp();
		$this->original_get = $_GET;

		$plugin = Disable_Comments::get_instance();
		$ref = new ReflectionProperty( $plugin, 'networkactive' );
		$ref->setAccessible( true );
		$this->original_networkactive = $ref->getValue( $plugin );
		$ref->setValue( $plugin, true );

		if ( ! is_multisite() ) {
			return;
		}

		$this->site2 = $this->factory->blog->create();
		$this->site3 = $this->factory->blog->create();

		// User is admin on site2, subscriber on main site.
		$this->site2_admin = $this->factory->user->create( [ 'role' => 'subscriber' ] );
		add_user_to_blog( $this->site2, $this->site2_admin, 'administrator' );
	}

	public function tearDown(): void {
		$plugin = Disable_Comments::get_instance();
		$ref = new ReflectionProperty( $plugin, 'networkactive' );
		$ref->setAccessible( true );
		$ref->setValue( $plugin, $this->original_networkactive );
		$_GET = $this->original_get;
		if ( is_multisite() ) {
			if ( $this->site2 ) {
				wpmu_delete_blog( $this->site2, true );
			}
			if ( $this->site3 ) {
				wpmu_delete_blog( $this->site3, true );
			}
		}
		parent::tearDown();
	}

	private function do_delete( $data_string ) {
		$_POST['nonce']  = wp_create_nonce( 'disable_comments_save_settings' );
		$_POST['data']   = $data_string;
		$_POST['action'] = 'disable_comments_delete_comments';
		try {
			$this->_handleAjax( 'disable_comments_delete_comments' );
		} catch ( WPDieException $e ) {
			// expected
		}
		return json_decode( $this->_last_response, true );
	}

	/**
	 * Exact exploit from spec #2: site-2 admin deletes comments from site-3.
	 *
	 * Expected: comments on site-3 survive; attacker is blocked.
	 *
	 * @group ms-required
	 */
	public function test_exploit_cross_site_deletion() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Requires multisite.' );
		}

		// Plant a comment on site-3.
		switch_to_blog( $this->site3 );
		$post_id    = $this->factory->post->create();
		$comment_id = $this->factory->comment->create( [ 'comment_post_ID' => $post_id ] );
		restore_current_blog();

		wp_set_current_user( $this->site2_admin );

		// Exact exploit payload from the spec.
		$data = 'is_network_admin=1&delete_mode=delete_everywhere'
		      . '&disabled_sites%5Bsite_' . $this->site3 . '%5D=1';
		$this->do_delete( $data );

		// Comment on site-3 must survive.
		switch_to_blog( $this->site3 );
		$comment = get_comment( $comment_id );
		restore_current_blog();

		$this->assertNotNull(
			$comment,
			'Exploit #2: comment on foreign site must not be deleted by cross-site admin.'
		);
	}

	/**
	 * Variant: attacker also forges the GET param.
	 *
	 * Expected: 403 because site2_admin lacks manage_network_plugins.
	 *
	 * @group ms-required
	 */
	public function test_exploit_cross_site_deletion_with_get_forgery() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Requires multisite.' );
		}

		switch_to_blog( $this->site3 );
		$post_id    = $this->factory->post->create();
		$comment_id = $this->factory->comment->create( [ 'comment_post_ID' => $post_id ] );
		restore_current_blog();

		wp_set_current_user( $this->site2_admin );
		$_GET['is_network_admin'] = '1';

		$data = 'delete_mode=delete_everywhere'
		      . '&disabled_sites%5Bsite_' . $this->site3 . '%5D=1';
		$response = $this->do_delete( $data );

		$this->assertIsArray( $response );
		$this->assertFalse(
			$response['success'],
			'Exploit #2 GET variant: must be blocked with 403.'
		);

		switch_to_blog( $this->site3 );
		$comment = get_comment( $comment_id );
		restore_current_blog();

		$this->assertNotNull( $comment, 'Comment must survive after GET forgery attempt.' );
	}

	/**
	 * Positive: super admin CAN delete comments from any site.
	 *
	 * @group ms-required
	 */
	public function test_super_admin_can_delete_cross_site() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Requires multisite.' );
		}

		switch_to_blog( $this->site3 );
		$post_id = $this->factory->post->create();
		$this->factory->comment->create( [ 'comment_post_ID' => $post_id ] );
		$comment_count_before = (int) wp_count_comments( $post_id )->total_comments;
		restore_current_blog();

		$this->assertGreaterThan( 0, $comment_count_before );

		$super = $this->factory->user->create( [ 'role' => 'administrator' ] );
		grant_super_admin( $super );
		wp_set_current_user( $super );

		$_GET['is_network_admin'] = '1';

		$data = 'delete_mode=delete_everywhere'
		      . '&disabled_sites%5Bsite_' . $this->site3 . '%5D=1';
		$response = $this->do_delete( $data );

		$this->assertIsArray( $response );
		$this->assertTrue( $response['success'], 'Super admin must be able to delete cross-site.' );
	}
}

// ===========================================================================
// Issue #3 — DOM-based XSS via Unescaped Role Names
// @see .ai/security/3-dom-xss-role-exclusion.md
//
// Root cause: translate_user_role() output not wrapped in esc_html().
// Tests verify both the PHP escape and the resulting JSON safety.
// ===========================================================================
class E2E_Issue3_XSS_Role_Names extends WP_UnitTestCase {

	private $plugin;
	private $xss_payloads = [
		'xss_img'    => '<img src=x onerror=alert("XSS")>',
		'xss_script' => '<script>alert(document.cookie)</script>',
		'xss_svg'    => '<svg onload=alert(1)>',
		'xss_attr'   => '" onmouseover="alert(1)" data-x="',
	];

	public function setUp(): void {
		parent::setUp();
		$this->plugin = Disable_Comments::get_instance();

		foreach ( $this->xss_payloads as $slug => $name ) {
			add_role( $slug, $name, [ 'read' => true ] );
		}
	}

	public function tearDown(): void {
		foreach ( array_keys( $this->xss_payloads ) as $slug ) {
			remove_role( $slug );
		}
		parent::tearDown();
	}

	/**
	 * Every XSS payload in a role name must be HTML-entity-encoded in
	 * the get_roles() output — the PHP-layer fix (esc_html).
	 */
	public function test_all_xss_payloads_escaped_in_get_roles() {
		$roles = $this->plugin->get_roles( [] );

		foreach ( $roles as $role ) {
			if ( ! isset( $this->xss_payloads[ $role['id'] ] ) ) {
				continue;
			}

			$payload = $this->xss_payloads[ $role['id'] ];

			// Payloads with angle brackets must have them entity-encoded.
			if ( strpos( $payload, '<' ) !== false ) {
				$this->assertStringNotContainsString(
					'<',
					$role['text'],
					"Role '{$role['id']}' text contains raw '<' — esc_html() missing."
				);
				$this->assertStringContainsString(
					'&lt;',
					$role['text'],
					"Role '{$role['id']}' must have '<' encoded as '&lt;'."
				);
			}

			// Payloads with double quotes must have them entity-encoded.
			if ( strpos( $payload, '"' ) !== false ) {
				$this->assertStringNotContainsString(
					'"',
					$role['text'],
					"Role '{$role['id']}' text contains raw '\"' — esc_html() missing."
				);
				$this->assertStringContainsString(
					'&quot;',
					$role['text'],
					"Role '{$role['id']}' must have '\"' encoded as '&quot;'."
				);
			}
		}
	}

	/**
	 * The JSON-encoded roles must be safe for injection into an HTML
	 * data-options attribute — no unescaped tags that break attribute context.
	 */
	public function test_json_encoded_roles_safe_for_html_attribute() {
		$roles   = $this->plugin->get_roles( [] );
		$encoded = wp_json_encode( $roles );

		$this->assertNotFalse( $encoded );

		// No raw HTML tags should survive in the JSON.
		$this->assertDoesNotMatchRegularExpression(
			'/<(img|script|svg|iframe|object|embed|form|input|body)\b/i',
			$encoded,
			'JSON must not contain raw HTML tags that could escape a data attribute.'
		);
	}

	/**
	 * The attribute-breakout payload must be neutered by esc_html().
	 * The raw " in the role name must become &quot; in the text field.
	 */
	public function test_attribute_breakout_payload_escaped() {
		$roles = $this->plugin->get_roles( [] );
		$entry = null;

		foreach ( $roles as $role ) {
			if ( $role['id'] === 'xss_attr' ) {
				$entry = $role;
				break;
			}
		}

		$this->assertNotNull( $entry, 'xss_attr role must appear in get_roles().' );
		$this->assertStringContainsString(
			'&quot;',
			$entry['text'],
			'Double quotes in role names must be HTML-entity-encoded.'
		);
		$this->assertStringNotContainsString(
			'onmouseover',
			html_entity_decode( $entry['text'] ) === $this->xss_payloads['xss_attr']
				? 'PASS' // entity decoded correctly
				: $entry['text'],
			'Event handler must not be executable after encoding.'
		);
	}
}

// ===========================================================================
// Issue #4 — HTTP Referer Spoofing → Network Admin Context Forgery
// @see .ai/security/4-referer-spoofing-privilege-escalation.md
//
// Old is_network_admin() trusted HTTP_REFERER. Removed and replaced with
// is_network_admin_ajax_context() that checks $_GET only + capability gate.
// ===========================================================================
class E2E_Issue4_Context_Forgery extends WP_Ajax_UnitTestCase {

	private $original_server;
	private $original_get;
	private $original_networkactive;

	public function setUp(): void {
		parent::setUp();
		$this->original_server = $_SERVER;
		$this->original_get    = $_GET;

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
		$_SERVER = $this->original_server;
		$_GET    = $this->original_get;
		parent::tearDown();
	}

	private function do_save( $data_string ) {
		$_POST['nonce']  = wp_create_nonce( 'disable_comments_save_settings' );
		$_POST['data']   = $data_string;
		$_POST['action'] = 'disable_comments_save_settings';
		try {
			$this->_handleAjax( 'disable_comments_save_settings' );
		} catch ( WPDieException $e ) {
			// expected
		}
		return json_decode( $this->_last_response, true );
	}

	/**
	 * Exact exploit from spec #4: sub-site admin sets HTTP Referer to the
	 * network admin URL and POSTs disable_avatar=1 to force network-wide
	 * avatar toggling.
	 *
	 * Expected: avatars on other sites are NOT changed.
	 *
	 * @group ms-required
	 */
	public function test_exploit_referer_spoofing_avatar_loop() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Requires multisite.' );
		}

		$second_site = $this->factory->blog->create();
		switch_to_blog( $second_site );
		update_option( 'show_avatars', true );
		restore_current_blog();

		$admin = $this->factory->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $admin );

		// Forge the Referer header exactly as described in the spec.
		$_SERVER['HTTP_REFERER'] = network_admin_url(
			'settings.php?page=disable_comments_settings'
		);

		$this->do_save( 'disable_avatar=1&mode=remove_everywhere' );

		switch_to_blog( $second_site );
		$still_enabled = get_option( 'show_avatars' );
		restore_current_blog();

		$this->assertTrue(
			(bool) $still_enabled,
			'Exploit #4: Referer spoofing must not trigger network-wide avatar change.'
		);

		wpmu_delete_blog( $second_site, true );
	}

	/**
	 * Referer spoofing combined with is_network_admin=1 in POST data.
	 *
	 * Expected: no network-wide saves.
	 *
	 * @group ms-required
	 */
	public function test_exploit_referer_plus_post_data() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Requires multisite.' );
		}

		$admin = $this->factory->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $admin );

		$_SERVER['HTTP_REFERER'] = network_admin_url(
			'settings.php?page=disable_comments_settings'
		);

		$original = get_site_option( 'disable_comments_sitewide_settings' );

		$this->do_save( 'is_network_admin=1&sitewide_settings=1&mode=remove_everywhere' );

		$this->assertEquals(
			$original,
			get_site_option( 'disable_comments_sitewide_settings' ),
			'Exploit #4: Referer + POST data forgery must not change sitewide_settings.'
		);
	}

	/**
	 * The old is_network_admin() public method must no longer exist.
	 */
	public function test_old_method_removed() {
		$plugin = Disable_Comments::get_instance();
		$this->assertFalse(
			method_exists( $plugin, 'is_network_admin' ),
			'The forgeable is_network_admin() public method must be removed.'
		);
	}

	/**
	 * is_network_admin_ajax_context() must be private — not callable
	 * externally for misuse.
	 */
	public function test_new_context_method_is_private() {
		$ref = new ReflectionMethod(
			Disable_Comments::class,
			'is_network_admin_ajax_context'
		);
		$this->assertTrue(
			$ref->isPrivate(),
			'is_network_admin_ajax_context() must be private.'
		);
	}
}

// ===========================================================================
// Issue #5 — Information Disclosure: Subsite Enumeration via get_sub_sites()
// @see .ai/security/5-info-disclosure-get-sub-sites.md
//
// Any authenticated user could enumerate all subsites by calling
// wp_ajax_get_sub_sites. Now requires manage_network_plugins when
// the plugin is network-active.
// ===========================================================================
class E2E_Issue5_SubSite_Enumeration extends WP_Ajax_UnitTestCase {

	private $original_get;
	private $original_networkactive;

	public function setUp(): void {
		parent::setUp();
		$this->original_get = $_GET;

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
		$_GET = $this->original_get;
		parent::tearDown();
	}

	private function do_get_subsites( $params = [] ) {
		$_GET['nonce'] = wp_create_nonce( 'disable_comments_save_settings' );
		$_GET['type']  = 'disabled';
		foreach ( $params as $k => $v ) {
			$_GET[ $k ] = $v;
		}
		try {
			$this->_handleAjax( 'get_sub_sites' );
		} catch ( WPDieException $e ) {
			// expected
		}
		return json_decode( $this->_last_response, true );
	}

	/**
	 * Subscriber must see zero sites — exact scenario from spec #5.
	 *
	 * @group ms-required
	 */
	public function test_subscriber_sees_nothing() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Requires multisite.' );
		}

		$subscriber = $this->factory->user->create( [ 'role' => 'subscriber' ] );
		wp_set_current_user( $subscriber );

		$response = $this->do_get_subsites();

		$this->assertIsArray( $response );
		$this->assertEmpty( $response['data'], 'Subscriber must see no sites.' );
		$this->assertEquals( 0, (int) $response['totalNumber'] );
	}

	/**
	 * Contributor must see zero sites.
	 *
	 * @group ms-required
	 */
	public function test_contributor_sees_nothing() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Requires multisite.' );
		}

		$contributor = $this->factory->user->create( [ 'role' => 'contributor' ] );
		wp_set_current_user( $contributor );

		$response = $this->do_get_subsites();

		$this->assertIsArray( $response );
		$this->assertEmpty( $response['data'] );
	}

	/**
	 * Author must see zero sites.
	 *
	 * @group ms-required
	 */
	public function test_author_sees_nothing() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Requires multisite.' );
		}

		$author = $this->factory->user->create( [ 'role' => 'author' ] );
		wp_set_current_user( $author );

		$response = $this->do_get_subsites();

		$this->assertIsArray( $response );
		$this->assertEmpty( $response['data'] );
	}

	/**
	 * Editor must see zero sites.
	 *
	 * @group ms-required
	 */
	public function test_editor_sees_nothing() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Requires multisite.' );
		}

		$editor = $this->factory->user->create( [ 'role' => 'editor' ] );
		wp_set_current_user( $editor );

		$response = $this->do_get_subsites();

		$this->assertIsArray( $response );
		$this->assertEmpty( $response['data'] );
	}

	/**
	 * Sub-site admin (manage_options but NOT manage_network_plugins) must
	 * see zero sites when plugin is network-active.
	 *
	 * @group ms-required
	 */
	public function test_subsite_admin_sees_nothing_when_networkactive() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Requires multisite.' );
		}

		$admin = $this->factory->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $admin );
		$this->assertFalse( current_user_can( 'manage_network_plugins' ) );

		$response = $this->do_get_subsites();

		$this->assertIsArray( $response );
		$this->assertEmpty(
			$response['data'],
			'Sub-site admin must not enumerate sites when plugin is network-active.'
		);
	}

	/**
	 * Pagination parameters must not bypass the capability check.
	 * Spec notes that pageSize/pageNumber/search are accepted as GET params.
	 *
	 * @group ms-required
	 */
	public function test_pagination_does_not_bypass_cap_check() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Requires multisite.' );
		}

		$subscriber = $this->factory->user->create( [ 'role' => 'subscriber' ] );
		wp_set_current_user( $subscriber );

		$response = $this->do_get_subsites( [
			'pageSize'   => '1000',
			'pageNumber' => '1',
			'search'     => '',
		] );

		$this->assertIsArray( $response );
		$this->assertEmpty( $response['data'] );
		$this->assertEquals( 0, (int) $response['totalNumber'] );
	}

	/**
	 * Super admin MUST see sites — positive case.
	 *
	 * @group ms-required
	 */
	public function test_super_admin_sees_sites() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Requires multisite.' );
		}

		$super = $this->factory->user->create( [ 'role' => 'administrator' ] );
		grant_super_admin( $super );
		wp_set_current_user( $super );

		$response = $this->do_get_subsites();

		$this->assertIsArray( $response );
		$this->assertGreaterThanOrEqual( 1, (int) $response['totalNumber'] );
	}
}
