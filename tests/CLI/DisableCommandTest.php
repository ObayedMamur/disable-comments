<?php
/**
 * Tests for the WP-CLI 'disable-comments settings' command.
 *
 * We test via the underlying disable_comments_settings() PHP method
 * (the CLI command is a thin wrapper around it), verifying the argument
 * mappings that cli.php builds before calling the handler.
 *
 * @package Disable_Comments
 */

class DisableCommandTest extends WP_UnitTestCase {

	/** @var Disable_Comments */
	private $plugin;

	public function set_up() {
		parent::set_up();
		$this->plugin = Disable_Comments::get_instance();
	}

	// -------------------------------------------------------------------------
	// --types=all  →  remove_everywhere mode
	// -------------------------------------------------------------------------

	public function test_types_all_sets_remove_everywhere() {
		// CLI builds: mode => 'remove_everywhere'
		$this->plugin->disable_comments_settings( array(
			'mode' => 'remove_everywhere',
		) );

		$options = get_option( 'disable_comments_options' );

		$this->assertTrue( $options['remove_everywhere'] );
	}

	// -------------------------------------------------------------------------
	// --types=post,page  →  selected_types mode with explicit list
	// -------------------------------------------------------------------------

	public function test_types_specific_sets_selected_post_types() {
		$this->plugin->disable_comments_settings( array(
			'mode'           => 'selected_types',
			'disabled_types' => array( 'post', 'page' ),
		) );

		$options = get_option( 'disable_comments_options' );

		$this->assertFalse( $options['remove_everywhere'] );
		$this->assertContains( 'post', $options['disabled_post_types'] );
		$this->assertContains( 'page', $options['disabled_post_types'] );
	}

	// -------------------------------------------------------------------------
	// --add  →  merge new type into existing disabled types
	// -------------------------------------------------------------------------

	public function test_add_flag_merges_with_existing_types() {
		// Pre-existing disabled type.
		$this->plugin->disable_comments_settings( array(
			'mode'           => 'selected_types',
			'disabled_types' => array( 'post' ),
		) );
		// CLI --add: merges 'page' into ['post'].
		$existing = $this->plugin->get_disabled_post_types();
		$merged   = array_unique( array_merge( $existing, array( 'page' ) ) );

		$this->plugin->disable_comments_settings( array(
			'mode'           => 'selected_types',
			'disabled_types' => $merged,
		) );

		$options = get_option( 'disable_comments_options' );
		$this->assertContains( 'post', $options['disabled_post_types'] );
		$this->assertContains( 'page', $options['disabled_post_types'] );
	}

	// -------------------------------------------------------------------------
	// --remove  →  subtract type from existing disabled types
	// -------------------------------------------------------------------------

	public function test_remove_flag_subtracts_from_existing_types() {
		$this->plugin->disable_comments_settings( array(
			'mode'           => 'selected_types',
			'disabled_types' => array( 'post', 'page' ),
		) );
		// CLI --remove: diff(['post','page'], ['page']) = ['post'].
		$existing = $this->plugin->get_disabled_post_types();
		$remaining = array_diff( $existing, array( 'page' ) );

		$this->plugin->disable_comments_settings( array(
			'mode'           => 'selected_types',
			'disabled_types' => array_values( $remaining ),
		) );

		$options = get_option( 'disable_comments_options' );
		$this->assertContains( 'post', $options['disabled_post_types'] );
		$this->assertNotContains( 'page', $options['disabled_post_types'] );
	}

	// -------------------------------------------------------------------------
	// --xmlrpc flag
	// -------------------------------------------------------------------------

	public function test_xmlrpc_flag_enables_xmlrpc_disable() {
		$this->plugin->disable_comments_settings( array(
			'mode'                   => 'selected_types',
			'remove_xmlrpc_comments' => true,
		) );

		$options = get_option( 'disable_comments_options' );
		$this->assertEquals( 1, (int) $options['remove_xmlrpc_comments'] );
	}

	public function test_xmlrpc_false_string_disables_xmlrpc_block() {
		// CLI passes 'false' as a string when flag is explicitly negated.
		$this->plugin->disable_comments_settings( array(
			'mode'                   => 'selected_types',
			'remove_xmlrpc_comments' => 0,
		) );

		$options = get_option( 'disable_comments_options' );
		$this->assertEquals( 0, (int) $options['remove_xmlrpc_comments'] );
	}

	// -------------------------------------------------------------------------
	// --rest-api flag
	// -------------------------------------------------------------------------

	public function test_rest_api_flag_enables_rest_disable() {
		$this->plugin->disable_comments_settings( array(
			'mode'                     => 'selected_types',
			'remove_rest_API_comments' => true,
		) );

		$options = get_option( 'disable_comments_options' );
		$this->assertEquals( 1, (int) $options['remove_rest_API_comments'] );
	}

	// -------------------------------------------------------------------------
	// --disable-avatar flag
	// -------------------------------------------------------------------------

	public function test_disable_avatar_flag_updates_show_avatars_option() {
		update_option( 'show_avatars', true );

		$this->plugin->disable_comments_settings( array(
			'mode'           => 'remove_everywhere',
			'disable_avatar' => '1',
		) );

		$this->assertFalse( (bool) get_option( 'show_avatars' ) );
	}

	public function test_enable_avatar_flag_restores_show_avatars() {
		update_option( 'show_avatars', false );

		$this->plugin->disable_comments_settings( array(
			'mode'           => 'remove_everywhere',
			'disable_avatar' => '0',
		) );

		$this->assertTrue( (bool) get_option( 'show_avatars' ) );
	}

	// -------------------------------------------------------------------------
	// Persists settings_saved and db_version
	// -------------------------------------------------------------------------

	public function test_cli_save_marks_settings_saved() {
		$this->plugin->disable_comments_settings( array(
			'mode' => 'remove_everywhere',
		) );

		$options = get_option( 'disable_comments_options' );
		$this->assertTrue( $options['settings_saved'] );
	}
}
