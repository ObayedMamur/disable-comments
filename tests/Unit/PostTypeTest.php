<?php
/**
 * Unit tests for post type management methods.
 *
 * Tests: get_disabled_post_types, is_post_type_disabled (via filter_comment_status),
 *        get_all_post_types
 *
 * @package Disable_Comments
 */

class PostTypeTest extends WP_UnitTestCase {

	/** @var Disable_Comments */
	private $plugin;

	public function set_up() {
		parent::set_up();
		$this->plugin = Disable_Comments::get_instance();
	}

	// -------------------------------------------------------------------------
	// get_disabled_post_types
	// -------------------------------------------------------------------------

	public function test_get_disabled_post_types_returns_configured_types() {
		$this->set_options( array(
			'disabled_post_types' => array( 'post', 'page' ),
		) );

		$types = $this->plugin->get_disabled_post_types();

		$this->assertContains( 'post', $types );
		$this->assertContains( 'page', $types );
	}

	public function test_get_disabled_post_types_returns_empty_by_default() {
		$this->set_options( array(
			'disabled_post_types' => array(),
		) );

		$types = $this->plugin->get_disabled_post_types();

		$this->assertEmpty( $types );
	}

	// -------------------------------------------------------------------------
	// is_post_type_disabled — tested via filter_comment_status
	// -------------------------------------------------------------------------

	public function test_disabled_post_type_closes_comments() {
		$post_id = $this->factory->post->create( array( 'post_type' => 'post' ) );
		$this->set_options( array(
			'remove_everywhere'   => false,
			'disabled_post_types' => array( 'post' ),
		) );

		$result = $this->plugin->filter_comment_status( true, $post_id );

		$this->assertFalse( $result );
	}

	public function test_non_disabled_post_type_keeps_comments_open() {
		$post_id = $this->factory->post->create( array( 'post_type' => 'page' ) );
		$this->set_options( array(
			'remove_everywhere'   => false,
			'disabled_post_types' => array( 'post' ),
		) );

		$result = $this->plugin->filter_comment_status( true, $post_id );

		$this->assertTrue( $result );
	}

	public function test_custom_post_type_can_be_disabled() {
		register_post_type( 'dc_test_cpt', array( 'supports' => array( 'comments' ) ) );
		$post_id = $this->factory->post->create( array( 'post_type' => 'dc_test_cpt' ) );
		$this->set_options( array(
			'remove_everywhere'   => false,
			'disabled_post_types' => array( 'dc_test_cpt' ),
		) );

		$result = $this->plugin->filter_comment_status( true, $post_id );

		$this->assertFalse( $result );
		unregister_post_type( 'dc_test_cpt' );
	}

	// -------------------------------------------------------------------------
	// get_all_post_types
	// -------------------------------------------------------------------------

	public function test_get_all_post_types_returns_array() {
		$types = $this->plugin->get_all_post_types();

		$this->assertIsArray( $types );
	}

	public function test_get_all_post_types_includes_post_and_page() {
		$types = $this->plugin->get_all_post_types();

		$this->assertArrayHasKey( 'post', $types );
		$this->assertArrayHasKey( 'page', $types );
	}

	public function test_get_all_post_types_excludes_attachment_by_default() {
		$types = $this->plugin->get_all_post_types();

		// attachment does not support comments by default in WP
		$this->assertArrayNotHasKey( 'attachment', $types );
	}

	public function test_get_all_post_types_includes_registered_cpt_with_comments() {
		register_post_type( 'dc_test_cpt2', array(
			'public'   => true,
			'supports' => array( 'comments' ),
		) );

		$types = $this->plugin->get_all_post_types();

		$this->assertArrayHasKey( 'dc_test_cpt2', $types );
		unregister_post_type( 'dc_test_cpt2' );
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
		);
		$options = array_merge( $defaults, $overrides );
		update_option( 'disable_comments_options', $options );
		$reflection = new ReflectionProperty( Disable_Comments::class, 'instance' );
		$reflection->setAccessible( true );
		$reflection->setValue( null, null );
		$this->plugin = Disable_Comments::get_instance();
	}
}
