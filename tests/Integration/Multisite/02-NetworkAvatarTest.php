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
		$this->plugin = Disable_Comments::get_instance();
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );
		grant_super_admin( $user_id );

		$this->site_ids[] = wpmu_create_blog( 'avatartest1.example.com', '/', 'Avatar Test 1', $user_id );
		$this->site_ids[] = wpmu_create_blog( 'avatartest2.example.com', '/', 'Avatar Test 2', $user_id );
	}

	public function tear_down() {
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
		$this->plugin->disable_comments_settings( array(
			'mode'             => 'remove_everywhere',
			'is_network_admin' => '1',
			'disable_avatar'   => '1',
		) );

		foreach ( $this->site_ids as $site_id ) {
			switch_to_blog( $site_id );
			$show = get_option( 'show_avatars' );
			restore_current_blog();
			$this->assertFalse( (bool) $show, "show_avatars should be false on site $site_id" );
		}
	}

	public function test_network_enable_avatar_updates_all_sites() {
		// First disable, then re-enable.
		$this->plugin->disable_comments_settings( array(
			'mode'             => 'remove_everywhere',
			'is_network_admin' => '1',
			'disable_avatar'   => '0',
		) );

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
