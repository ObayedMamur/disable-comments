<?php
/**
 * Unit tests for status detection methods.
 *
 * Tests: get_current_comment_status, get_detailed_comment_status, is_configured
 *
 * @package Disable_Comments
 */

class StatusTest extends WP_UnitTestCase {

	/** @var Disable_Comments */
	private $plugin;

	public function set_up() {
		parent::set_up();
		$this->plugin = Disable_Comments::get_instance();
	}

	// -------------------------------------------------------------------------
	// get_current_comment_status
	// -------------------------------------------------------------------------

	public function test_status_is_all_when_remove_everywhere() {
		$this->set_options( array( 'remove_everywhere' => true ) );

		$this->assertEquals( 'all', $this->plugin->get_current_comment_status() );
	}

	public function test_status_is_none_when_nothing_disabled() {
		$this->set_options( array(
			'remove_everywhere'   => false,
			'disabled_post_types' => array(),
		) );

		$this->assertEquals( 'none', $this->plugin->get_current_comment_status() );
	}

	public function test_status_is_posts_when_only_post_disabled() {
		$this->set_options( array(
			'remove_everywhere'   => false,
			'disabled_post_types' => array( 'post' ),
		) );

		$this->assertEquals( 'posts', $this->plugin->get_current_comment_status() );
	}

	public function test_status_is_pages_when_only_page_disabled() {
		$this->set_options( array(
			'remove_everywhere'   => false,
			'disabled_post_types' => array( 'page' ),
		) );

		$this->assertEquals( 'pages', $this->plugin->get_current_comment_status() );
	}

	public function test_status_is_posts_pages_when_both_disabled() {
		$this->set_options( array(
			'remove_everywhere'   => false,
			'disabled_post_types' => array( 'post', 'page' ),
		) );

		$this->assertEquals( 'posts,pages', $this->plugin->get_current_comment_status() );
	}

	public function test_status_is_cpt_slug_when_custom_type_only() {
		register_post_type( 'dc_status_cpt', array( 'supports' => array( 'comments' ) ) );
		$this->set_options( array(
			'remove_everywhere'   => false,
			'disabled_post_types' => array( 'dc_status_cpt' ),
		) );

		$this->assertEquals( 'dc_status_cpt', $this->plugin->get_current_comment_status() );
		unregister_post_type( 'dc_status_cpt' );
	}

	public function test_status_is_multiple_for_three_or_more_types() {
		register_post_type( 'dc_extra', array( 'supports' => array( 'comments' ) ) );
		$this->set_options( array(
			'remove_everywhere'   => false,
			'disabled_post_types' => array( 'post', 'page', 'dc_extra' ),
		) );

		$this->assertEquals( 'multiple', $this->plugin->get_current_comment_status() );
		unregister_post_type( 'dc_extra' );
	}

	// -------------------------------------------------------------------------
	// is_configured
	// -------------------------------------------------------------------------

	public function test_is_configured_true_when_settings_saved() {
		$this->set_options( array(
			'remove_everywhere'   => true,
			'settings_saved'      => true,
		) );

		$this->assertTrue( $this->plugin->is_configured() );
	}

	public function test_is_configured_false_when_no_settings_saved() {
		$this->set_options( array(
			'remove_everywhere'   => false,
			'disabled_post_types' => array(),
		) );

		$this->assertFalse( $this->plugin->is_configured() );
	}

	// -------------------------------------------------------------------------
	// get_detailed_comment_status — structure validation
	// -------------------------------------------------------------------------

	public function test_detailed_status_returns_array() {
		$this->set_options( array( 'remove_everywhere' => true ) );

		$status = $this->plugin->get_detailed_comment_status();

		$this->assertIsArray( $status );
	}

	public function test_detailed_status_has_required_keys() {
		$this->set_options( array( 'remove_everywhere' => true ) );

		$status = $this->plugin->get_detailed_comment_status();

		$required_keys = array(
			'status',
			'disabled_post_types',
			'remove_everywhere',
			'xmlrpc_disabled',
			'rest_api_disabled',
			'total_post_types',
			'is_configured',
			'network_active',
			'role_exclusion_enabled',
			'excluded_roles',
		);
		foreach ( $required_keys as $key ) {
			$this->assertArrayHasKey( $key, $status, "Missing key: $key" );
		}
	}

	public function test_detailed_status_remove_everywhere_true() {
		$this->set_options( array(
			'remove_everywhere'   => true,
			'settings_saved'      => true,
		) );

		$status = $this->plugin->get_detailed_comment_status();

		$this->assertTrue( $status['remove_everywhere'] );
		$this->assertEquals( 'all', $status['status'] );
	}

	public function test_detailed_status_xmlrpc_disabled_flag() {
		$this->set_options( array(
			'remove_xmlrpc_comments' => 1,
		) );

		$status = $this->plugin->get_detailed_comment_status();

		$this->assertTrue( (bool) $status['xmlrpc_disabled'] );
	}

	public function test_detailed_status_rest_api_disabled_flag() {
		$this->set_options( array(
			'remove_rest_API_comments' => 1,
		) );

		$status = $this->plugin->get_detailed_comment_status();

		$this->assertTrue( (bool) $status['rest_api_disabled'] );
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	private function set_options( array $overrides ) {
		$defaults = array(
			'db_version'               => Disable_Comments::DB_VERSION,
			'remove_everywhere'        => false,
			'disabled_post_types'      => array(),
			'extra_post_types'         => array(),
			'allowed_comment_types'    => array(),
			'show_existing_comments'   => false,
			'enable_exclude_by_role'   => false,
			'exclude_by_role'          => array(),
			'remove_xmlrpc_comments'   => 0,
			'remove_rest_API_comments' => 0,
			'settings_saved'           => false,
		);
		$options = array_merge( $defaults, $overrides );
		update_option( 'disable_comments_options', $options );
		$reflection = new ReflectionProperty( Disable_Comments::class, 'instance' );
		$reflection->setAccessible( true );
		$reflection->setValue( null, null );
		$this->plugin = Disable_Comments::get_instance();
	}
}
