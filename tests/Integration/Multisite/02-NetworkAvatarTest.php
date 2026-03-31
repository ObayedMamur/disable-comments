<?php
/**
 * Integration tests for network avatar settings.
 *
 * Requires multisite — skipped on single-site.
 *
 * Tests: disable_avatar toggles show_avatars across all network sites,
 *        single-site avatar update stays local.
 *
 * @package Disable_Comments
 * @group   multisite
 */

class NetworkAvatarTest extends WP_UnitTestCase {

	/** @var Disable_Comments */
	private $plugin;

	/** @var int[] */
	private $site_ids = array();

	public function set_up() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite not active.' );
		}
		parent::set_up();
		$this->plugin         = Disable_Comments::get_instance();
		$this->plugin->is_CLI = true; // Direct method calls — bypass nonce + JSON output.
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );
		grant_super_admin( $user_id );

		$this->site_ids[] = $this->factory->blog->create( array( 'title' => 'Avatar Test 1' ) );
		$this->site_ids[] = $this->factory->blog->create( array( 'title' => 'Avatar Test 2' ) );
	}

	public function tear_down() {
		$this->plugin->is_CLI = false;
		foreach ( $this->site_ids as $site_id ) {
			wpmu_delete_blog( $site_id, true );
		}
		wp_set_current_user( 0 );
		parent::tear_down();
	}

	// -------------------------------------------------------------------------
	// Network admin — disable_avatar=1 sets show_avatars=false on all sites
	// -------------------------------------------------------------------------

	public function test_network_disable_avatar_updates_all_sites() {
		// Avatar update iterates all sites only when is_network_admin() is true.
		// Simulate network admin screen context for the duration of this call.
		require_once ABSPATH . 'wp-admin/includes/screen.php';
		$old_screen = isset( $GLOBALS['current_screen'] ) ? $GLOBALS['current_screen'] : null;
		set_current_screen( 'dashboard-network' );

		$this->plugin->disable_comments_settings( array(
			'mode'             => 'remove_everywhere',
			'is_network_admin' => '1',
			'disable_avatar'   => '1',
		) );

		if ( $old_screen ) {
			$GLOBALS['current_screen'] = $old_screen;
		} else {
			unset( $GLOBALS['current_screen'] );
		}

		foreach ( $this->site_ids as $site_id ) {
			switch_to_blog( $site_id );
			$show = get_option( 'show_avatars' );
			restore_current_blog();
			$this->assertFalse( (bool) $show, "show_avatars should be false on site $site_id" );
		}
	}

	public function test_network_enable_avatar_updates_all_sites() {
		require_once ABSPATH . 'wp-admin/includes/screen.php';
		$old_screen = isset( $GLOBALS['current_screen'] ) ? $GLOBALS['current_screen'] : null;
		set_current_screen( 'dashboard-network' );

		// First disable, then re-enable.
		$this->plugin->disable_comments_settings( array(
			'mode'             => 'remove_everywhere',
			'is_network_admin' => '1',
			'disable_avatar'   => '0',
		) );

		if ( $old_screen ) {
			$GLOBALS['current_screen'] = $old_screen;
		} else {
			unset( $GLOBALS['current_screen'] );
		}

		foreach ( $this->site_ids as $site_id ) {
			switch_to_blog( $site_id );
			$show = get_option( 'show_avatars' );
			restore_current_blog();
			$this->assertTrue( (bool) $show, "show_avatars should be true on site $site_id" );
		}
	}

	// -------------------------------------------------------------------------
	// Single site — avatar change is local only
	// -------------------------------------------------------------------------

	public function test_single_site_avatar_change_stays_local() {
		$original_blog = get_current_blog_id();
		update_option( 'show_avatars', true );

		// Save as single-site (no is_network_admin flag).
		$this->plugin->disable_comments_settings( array(
			'mode'           => 'remove_everywhere',
			'disable_avatar' => '1',
		) );

		// Current site is updated.
		$this->assertFalse( (bool) get_option( 'show_avatars' ) );

		// Sub-sites should be unaffected.
		foreach ( $this->site_ids as $site_id ) {
			switch_to_blog( $site_id );
			$show = get_option( 'show_avatars' );
			restore_current_blog();
			// Sub-sites were not explicitly set, so they retain their default (true).
			$this->assertTrue( (bool) $show, "Sub-site $site_id should not have been changed" );
		}
	}
}
