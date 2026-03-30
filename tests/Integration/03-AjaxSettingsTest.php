<?php
/**
 * Integration tests for the settings AJAX handler.
 *
 * Tests: disable_comments_settings() via CLI path (bypasses nonce),
 *        covering all setting flags, persistence, and sanitization.
 *
 * @package Disable_Comments
 */

class AjaxSettingsTest extends WP_Ajax_UnitTestCase {

	use PluginOptionsTrait;

	/** @var Disable_Comments */
	private $plugin;

	public function set_up() {
		parent::set_up();
		$this->plugin = Disable_Comments::get_instance();
		// Grant admin caps for AJAX calls.
		$this->_setRole( 'administrator' );
		// Bypass nonce gate: tests call the method directly without WP_CLI.
		$this->plugin->is_CLI = true;
	}

	// -------------------------------------------------------------------------
	// CLI path — remove_everywhere mode
	// -------------------------------------------------------------------------

	public function test_save_remove_everywhere_mode() {
		$this->plugin->disable_comments_settings( array(
			'mode' => 'remove_everywhere',
		) );

		$options = get_option( 'disable_comments_options' );

		$this->assertTrue( $options['remove_everywhere'] );
	}

	public function test_save_selected_types_mode() {
		$this->plugin->disable_comments_settings( array(
			'mode'           => 'selected_types',
			'disabled_types' => array( 'post', 'page' ),
		) );

		$options = get_option( 'disable_comments_options' );

		$this->assertFalse( $options['remove_everywhere'] );
		$this->assertContains( 'post', $options['disabled_post_types'] );
		$this->assertContains( 'page', $options['disabled_post_types'] );
	}

	public function test_save_selected_types_rejects_nonexistent_post_type() {
		$this->plugin->disable_comments_settings( array(
			'mode'           => 'selected_types',
			'disabled_types' => array( 'post', 'nonexistent_type_xyz' ),
		) );

		$options = get_option( 'disable_comments_options' );

		$this->assertContains( 'post', $options['disabled_post_types'] );
		$this->assertNotContains( 'nonexistent_type_xyz', $options['disabled_post_types'] );
	}

	// -------------------------------------------------------------------------
	// XML-RPC and REST API flags
	// -------------------------------------------------------------------------

	public function test_save_xmlrpc_comments_flag() {
		$this->plugin->disable_comments_settings( array(
			'mode'                   => 'selected_types',
			'remove_xmlrpc_comments' => 1,
		) );

		$options = get_option( 'disable_comments_options' );

		$this->assertEquals( 1, $options['remove_xmlrpc_comments'] );
	}

	public function test_save_rest_api_comments_flag() {
		$this->plugin->disable_comments_settings( array(
			'mode'                     => 'selected_types',
			'remove_rest_API_comments' => 1,
		) );

		$options = get_option( 'disable_comments_options' );

		$this->assertEquals( 1, $options['remove_rest_API_comments'] );
	}

	public function test_save_disables_xmlrpc_when_flag_zero() {
		$this->plugin->disable_comments_settings( array(
			'mode'                   => 'selected_types',
			'remove_xmlrpc_comments' => 0,
		) );

		$options = get_option( 'disable_comments_options' );

		$this->assertEquals( 0, $options['remove_xmlrpc_comments'] );
	}

	// -------------------------------------------------------------------------
	// Show existing comments
	// -------------------------------------------------------------------------

	public function test_save_show_existing_comments() {
		$this->plugin->disable_comments_settings( array(
			'mode'                   => 'remove_everywhere',
			'show_existing_comments' => true,
		) );

		$options = get_option( 'disable_comments_options' );

		$this->assertTrue( (bool) $options['show_existing_comments'] );
	}

	// -------------------------------------------------------------------------
	// Allowed comment types
	// -------------------------------------------------------------------------

	public function test_save_allowed_comment_types() {
		$this->plugin->disable_comments_settings( array(
			'mode'                  => 'remove_everywhere',
			'allowed_comment_types' => array( 'review', 'block_comment' ),
		) );

		$options = get_option( 'disable_comments_options' );

		$this->assertContains( 'review', $options['allowed_comment_types'] );
		$this->assertContains( 'block_comment', $options['allowed_comment_types'] );
	}

	public function test_save_empty_allowed_comment_types_stores_empty_array() {
		$this->plugin->disable_comments_settings( array(
			'mode'                  => 'remove_everywhere',
			'allowed_comment_types' => array(),
		) );

		$options = get_option( 'disable_comments_options' );

		$this->assertIsArray( $options['allowed_comment_types'] );
		$this->assertEmpty( $options['allowed_comment_types'] );
	}

	// -------------------------------------------------------------------------
	// Role exclusion
	// -------------------------------------------------------------------------

	public function test_save_role_exclusion() {
		$this->plugin->disable_comments_settings( array(
			'mode'                   => 'remove_everywhere',
			'enable_exclude_by_role' => true,
			'exclude_by_role'        => array( 'editor', 'author' ),
		) );

		$options = get_option( 'disable_comments_options' );

		$this->assertTrue( (bool) $options['enable_exclude_by_role'] );
		$this->assertContains( 'editor', $options['exclude_by_role'] );
		$this->assertContains( 'author', $options['exclude_by_role'] );
	}

	// -------------------------------------------------------------------------
	// Settings persistence — settings_saved flag
	// -------------------------------------------------------------------------

	public function test_save_sets_settings_saved_flag() {
		$this->plugin->disable_comments_settings( array(
			'mode' => 'remove_everywhere',
		) );

		$options = get_option( 'disable_comments_options' );

		$this->assertTrue( $options['settings_saved'] );
	}

	public function test_save_sets_current_db_version() {
		$this->plugin->disable_comments_settings( array(
			'mode' => 'remove_everywhere',
		) );

		$options = get_option( 'disable_comments_options' );

		$this->assertEquals( Disable_Comments::DB_VERSION, $options['db_version'] );
	}

	// -------------------------------------------------------------------------
	// AJAX path — nonce failure returns error
	// -------------------------------------------------------------------------

	public function test_ajax_save_settings_fails_with_bad_nonce() {
		$this->plugin->is_CLI = false; // Test real AJAX path so wp_die() is called.
		$_POST['nonce'] = 'bad_nonce';
		$_POST['data']  = 'mode=remove_everywhere';

		try {
			$this->_handleAjax( 'disable_comments_save_settings' );
		} catch ( WPAjaxDieContinueException $e ) {
			// Expected: wp_send_json_success() calls wp_die('') internally.
		}

		// Options should NOT have been saved (nonce failed, save block was skipped).
		$options = get_option( 'disable_comments_options' );
		$this->assertEmpty( $options['remove_everywhere'] ?? false );
	}

	// -------------------------------------------------------------------------
	// AJAX path — valid nonce saves settings
	// -------------------------------------------------------------------------

	public function test_ajax_save_settings_succeeds_with_valid_nonce() {
		$this->plugin->is_CLI = false; // Test real AJAX path so wp_die() is called.
		$_POST['nonce'] = wp_create_nonce( 'disable_comments_save_settings' );
		$_POST['data']  = http_build_query( array(
			'mode' => 'remove_everywhere',
		) );

		try {
			$this->_handleAjax( 'disable_comments_save_settings' );
		} catch ( WPAjaxDieContinueException $e ) {
			// wp_send_json_success() calls wp_die('') internally — catch it here.
		}
		$response = json_decode( $this->_last_response, true );
		$this->assertTrue( $response['success'] );
	}

}
