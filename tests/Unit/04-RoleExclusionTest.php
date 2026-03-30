<?php
/**
 * Unit tests for role-based exclusion logic.
 *
 * Tests: is_exclude_by_role (via filter_comment_status and filter_existing_comments),
 *        logged-out user exclusion, super admin handling
 *
 * @package Disable_Comments
 */

class RoleExclusionTest extends WP_UnitTestCase {

	use PluginOptionsTrait;

	/** @var Disable_Comments */
	private $plugin;

	public function set_up() {
		parent::set_up();
		$this->plugin = Disable_Comments::get_instance();
	}

	public function tear_down() {
		wp_set_current_user( 0 );
		parent::tear_down();
	}

	// -------------------------------------------------------------------------
	// Exclusion disabled entirely
	// -------------------------------------------------------------------------

	public function test_exclusion_disabled_does_not_bypass_disable() {
		$post_id = $this->factory->post->create();
		$user_id = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );
		$this->set_options( array(
			'remove_everywhere'      => true,
			'enable_exclude_by_role' => false,
			'exclude_by_role'        => array( 'editor' ),
		) );

		$result = $this->plugin->filter_comment_status( true, $post_id );

		$this->assertFalse( $result );
	}

	// -------------------------------------------------------------------------
	// Exclusion enabled — user role matches
	// -------------------------------------------------------------------------

	public function test_excluded_role_bypasses_remove_everywhere() {
		$post_id = $this->factory->post->create();
		$user_id = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );
		$this->set_options( array(
			'remove_everywhere'      => true,
			'enable_exclude_by_role' => true,
			'exclude_by_role'        => array( 'editor' ),
		) );

		$result = $this->plugin->filter_comment_status( true, $post_id );

		$this->assertTrue( $result );
	}

	public function test_excluded_role_bypasses_post_type_disable() {
		$post_id = $this->factory->post->create( array( 'post_type' => 'post' ) );
		$user_id = $this->factory->user->create( array( 'role' => 'author' ) );
		wp_set_current_user( $user_id );
		$this->set_options( array(
			'remove_everywhere'      => false,
			'disabled_post_types'    => array( 'post' ),
			'enable_exclude_by_role' => true,
			'exclude_by_role'        => array( 'author' ),
		) );

		$result = $this->plugin->filter_comment_status( true, $post_id );

		$this->assertTrue( $result );
	}

	// -------------------------------------------------------------------------
	// Non-excluded role is still blocked
	// -------------------------------------------------------------------------

	public function test_non_excluded_role_still_blocked() {
		$post_id = $this->factory->post->create();
		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );
		$this->set_options( array(
			'remove_everywhere'      => true,
			'enable_exclude_by_role' => true,
			'exclude_by_role'        => array( 'editor' ),
		) );

		$result = $this->plugin->filter_comment_status( true, $post_id );

		$this->assertFalse( $result );
	}

	// -------------------------------------------------------------------------
	// Logged-out user exclusion
	// -------------------------------------------------------------------------

	public function test_logged_out_user_excluded_when_configured() {
		$post_id = $this->factory->post->create();
		wp_set_current_user( 0 ); // logged out
		$this->set_options( array(
			'remove_everywhere'      => true,
			'enable_exclude_by_role' => true,
			'exclude_by_role'        => array( 'logged-out-users' ),
		) );

		$result = $this->plugin->filter_comment_status( true, $post_id );

		$this->assertTrue( $result );
	}

	public function test_logged_out_user_blocked_when_not_in_exclude_list() {
		$post_id = $this->factory->post->create();
		wp_set_current_user( 0 );
		$this->set_options( array(
			'remove_everywhere'      => true,
			'enable_exclude_by_role' => true,
			'exclude_by_role'        => array( 'editor' ),
		) );

		$result = $this->plugin->filter_comment_status( true, $post_id );

		$this->assertFalse( $result );
	}

	// -------------------------------------------------------------------------
	// Exclusion also bypasses filter_existing_comments
	// -------------------------------------------------------------------------

	public function test_excluded_role_sees_existing_comments() {
		$post_id = $this->factory->post->create();
		$user_id = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );
		$this->set_options( array(
			'remove_everywhere'      => true,
			'enable_exclude_by_role' => true,
			'exclude_by_role'        => array( 'editor' ),
		) );
		$comments = array( $this->make_comment(), $this->make_comment() );

		$result = $this->plugin->filter_existing_comments( $comments, $post_id );

		$this->assertCount( 2, $result );
	}

	// -------------------------------------------------------------------------
	// Multiple excluded roles
	// -------------------------------------------------------------------------

	public function test_multiple_excluded_roles_any_match_bypasses() {
		$post_id  = $this->factory->post->create();
		$user_id  = $this->factory->user->create( array( 'role' => 'contributor' ) );
		wp_set_current_user( $user_id );
		$this->set_options( array(
			'remove_everywhere'      => true,
			'enable_exclude_by_role' => true,
			'exclude_by_role'        => array( 'editor', 'contributor' ),
		) );

		$result = $this->plugin->filter_comment_status( true, $post_id );

		$this->assertTrue( $result );
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	private function make_comment( $type = 'comment' ) {
		$c               = new stdClass();
		$c->comment_type = $type;
		return $c;
	}
}
