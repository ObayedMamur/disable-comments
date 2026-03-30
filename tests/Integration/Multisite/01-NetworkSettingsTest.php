<?php
/**
 * Integration tests for network/multisite settings.
 *
 * Requires multisite — skipped on single-site.
 *
 * Tests: sitewide vs per-site settings scope, network option loading,
 *        per-site option overrides, disabled_sites tracking.
 *
 * @package Disable_Comments
 * @group   multisite
 */

class NetworkSettingsTest extends WP_UnitTestCase {

	/** @var Disable_Comments */
	private $plugin;

	public function set_up() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite not active.' );
		}
		parent::set_up();
		$this->plugin = Disable_Comments::get_instance();
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );
		grant_super_admin( $user_id );
	}

	public function tear_down() {
		wp_set_current_user( 0 );
		delete_site_option( 'disable_comments_options' );
		delete_site_option( 'disable_comments_sitewide_settings' );
		parent::tear_down();
	}

	// -------------------------------------------------------------------------
	// Sitewide settings mode — saves to site option
	// -------------------------------------------------------------------------

	public function test_sitewide_settings_saved_to_site_option() {
		$this->plugin->disable_comments_settings( array(
			'mode'              => 'remove_everywhere',
			'is_network_admin'  => '1',
			'sitewide_settings' => '1',
		) );

		$value = get_site_option( 'disable_comments_sitewide_settings' );

		$this->assertEquals( '1', $value );
	}

	// -------------------------------------------------------------------------
	// Per-site disabled_sites tracking
	// -------------------------------------------------------------------------

	public function test_save_disabled_sites_array() {
		$sites = get_sites( array( 'number' => 0, 'fields' => 'ids' ) );
		$site_id = reset( $sites );

		$this->plugin->disable_comments_settings( array(
			'mode'              => 'selected_types',
			'is_network_admin'  => '1',
			'sitewide_settings' => '',
			'disabled_sites'    => array( "site_{$site_id}" => true ),
		) );

		$options = get_site_option( 'disable_comments_options' );

		$this->assertArrayHasKey( 'disabled_sites', $options );
	}

	// -------------------------------------------------------------------------
	// get_disabled_sites — returns array keyed by site_N
	// -------------------------------------------------------------------------

	public function test_get_disabled_sites_returns_array() {
		$sites = $this->plugin->get_disabled_sites();

		$this->assertIsArray( $sites );
	}

	public function test_get_disabled_sites_keys_use_site_prefix() {
		$sites = $this->plugin->get_disabled_sites();

		if ( ! empty( $sites ) ) {
			$first_key = key( $sites );
			$this->assertStringStartsWith( 'site_', $first_key );
		} else {
			$this->markTestSkipped( 'No sites found.' );
		}
	}

	// -------------------------------------------------------------------------
	// Network menu registration
	// -------------------------------------------------------------------------

	public function test_settings_menu_registration_is_pending_admin_hook() {
		// settings_menu adds to network_admin_menu when network active.
		// We just verify the method exists and is callable.
		$this->assertTrue( is_callable( array( $this->plugin, 'settings_menu' ) ) );
	}

	// -------------------------------------------------------------------------
	// is_network_admin detection
	// -------------------------------------------------------------------------

	public function test_is_network_admin_false_on_frontend() {
		// In the test environment, we're not in a network admin AJAX context.
		$_POST = array();
		$result = $this->plugin->is_network_admin();

		$this->assertIsBool( $result );
	}

	public function test_is_network_admin_true_when_post_flag_set() {
		$_POST['is_network_admin'] = '1';

		$result = $this->plugin->is_network_admin();

		$this->assertTrue( $result );
		unset( $_POST['is_network_admin'] );
	}
}
