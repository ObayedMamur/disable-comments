<?php
/**
 * Unit tests for comment filtering methods.
 *
 * Tests: filter_existing_comments, filter_comment_status, filter_comments_number
 * These are pure logic tests — no DB required.
 *
 * @package Disable_Comments
 */

class CommentFilteringTest extends WP_UnitTestCase {

	use PluginOptionsTrait;

	/** @var Disable_Comments */
	private $plugin;

	public function set_up() {
		parent::set_up();
		$this->plugin = Disable_Comments::get_instance();
	}

	// -------------------------------------------------------------------------
	// filter_existing_comments
	// -------------------------------------------------------------------------

	public function test_filter_existing_comments_remove_everywhere_returns_empty() {
		$post_id = $this->factory->post->create();
		$this->set_options( array( 'remove_everywhere' => true ) );

		$result = $this->plugin->filter_existing_comments( array( $this->make_comment() ), $post_id );

		$this->assertEmpty( $result );
	}

	public function test_filter_existing_comments_disabled_post_type_returns_empty() {
		$post_id = $this->factory->post->create( array( 'post_type' => 'post' ) );
		$this->set_options( array(
			'remove_everywhere'   => false,
			'disabled_post_types' => array( 'post' ),
		) );

		$result = $this->plugin->filter_existing_comments( array( $this->make_comment() ), $post_id );

		$this->assertEmpty( $result );
	}

	public function test_filter_existing_comments_enabled_post_type_returns_all() {
		$post_id = $this->factory->post->create( array( 'post_type' => 'page' ) );
		$this->set_options( array(
			'remove_everywhere'   => false,
			'disabled_post_types' => array( 'post' ),
		) );
		$comments = array( $this->make_comment(), $this->make_comment() );

		$result = $this->plugin->filter_existing_comments( $comments, $post_id );

		$this->assertCount( 2, $result );
	}

	public function test_filter_existing_comments_show_existing_overrides_disabled() {
		$post_id = $this->factory->post->create();
		$this->set_options( array(
			'remove_everywhere'      => true,
			'show_existing_comments' => true,
		) );
		$comments = array( $this->make_comment(), $this->make_comment() );

		$result = $this->plugin->filter_existing_comments( $comments, $post_id );

		$this->assertCount( 2, $result );
	}

	public function test_filter_existing_comments_allowed_type_kept_when_disabled() {
		$post_id = $this->factory->post->create();
		$this->set_options( array(
			'remove_everywhere'     => true,
			'allowed_comment_types' => array( 'block_comment' ),
		) );
		$regular  = $this->make_comment( 'comment' );
		$allowed  = $this->make_comment( 'block_comment' );

		$result = $this->plugin->filter_existing_comments( array( $regular, $allowed ), $post_id );

		$this->assertCount( 1, $result );
		$this->assertEquals( 'block_comment', $result[0]->comment_type );
	}

	public function test_filter_existing_comments_empty_array_returns_empty() {
		$post_id = $this->factory->post->create();
		$this->set_options( array( 'remove_everywhere' => true ) );

		$result = $this->plugin->filter_existing_comments( array(), $post_id );

		$this->assertEmpty( $result );
	}

	// -------------------------------------------------------------------------
	// filter_comment_status
	// -------------------------------------------------------------------------

	public function test_filter_comment_status_remove_everywhere_returns_false() {
		$post_id = $this->factory->post->create();
		$this->set_options( array( 'remove_everywhere' => true ) );

		$result = $this->plugin->filter_comment_status( true, $post_id );

		$this->assertFalse( $result );
	}

	public function test_filter_comment_status_disabled_type_returns_false() {
		$post_id = $this->factory->post->create( array( 'post_type' => 'post' ) );
		$this->set_options( array(
			'remove_everywhere'   => false,
			'disabled_post_types' => array( 'post' ),
		) );

		$result = $this->plugin->filter_comment_status( true, $post_id );

		$this->assertFalse( $result );
	}

	public function test_filter_comment_status_enabled_type_preserves_open() {
		$post_id = $this->factory->post->create( array( 'post_type' => 'page' ) );
		$this->set_options( array(
			'remove_everywhere'   => false,
			'disabled_post_types' => array( 'post' ),
		) );

		$result = $this->plugin->filter_comment_status( true, $post_id );

		$this->assertTrue( $result );
	}

	public function test_filter_comment_status_enabled_type_preserves_closed() {
		$post_id = $this->factory->post->create( array( 'post_type' => 'page' ) );
		$this->set_options( array(
			'remove_everywhere'   => false,
			'disabled_post_types' => array( 'post' ),
		) );

		$result = $this->plugin->filter_comment_status( false, $post_id );

		$this->assertFalse( $result );
	}

	// -------------------------------------------------------------------------
	// filter_comments_number
	// -------------------------------------------------------------------------

	public function test_filter_comments_number_remove_everywhere_returns_zero() {
		$post_id = $this->factory->post->create();
		$this->set_options( array( 'remove_everywhere' => true ) );

		$result = $this->plugin->filter_comments_number( 5, $post_id );

		$this->assertEquals( 0, $result );
	}

	public function test_filter_comments_number_disabled_type_returns_zero() {
		$post_id = $this->factory->post->create( array( 'post_type' => 'post' ) );
		$this->set_options( array(
			'remove_everywhere'   => false,
			'disabled_post_types' => array( 'post' ),
		) );

		$result = $this->plugin->filter_comments_number( 3, $post_id );

		$this->assertEquals( 0, $result );
	}

	public function test_filter_comments_number_enabled_type_returns_original_count() {
		$post_id = $this->factory->post->create( array( 'post_type' => 'page' ) );
		$this->set_options( array(
			'remove_everywhere'   => false,
			'disabled_post_types' => array( 'post' ),
		) );

		$result = $this->plugin->filter_comments_number( 7, $post_id );

		$this->assertEquals( 7, $result );
	}

	public function test_filter_comments_number_show_existing_returns_actual_count() {
		$post_id = $this->factory->post->create();
		$this->set_options( array(
			'remove_everywhere'      => true,
			'show_existing_comments' => true,
		) );

		$result = $this->plugin->filter_comments_number( 4, $post_id );

		$this->assertEquals( 4, $result );
	}

	// -------------------------------------------------------------------------
	// Role exclusion bypass
	// -------------------------------------------------------------------------

	public function test_filter_comment_status_role_excluded_user_sees_open() {
		$user_id = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );
		$post_id = $this->factory->post->create( array( 'post_type' => 'post' ) );
		$this->set_options( array(
			'remove_everywhere'        => true,
			'enable_exclude_by_role'   => true,
			'exclude_by_role'          => array( 'editor' ),
		) );

		$result = $this->plugin->filter_comment_status( true, $post_id );

		$this->assertTrue( $result );
	}

	public function test_filter_comment_status_non_excluded_role_still_blocked() {
		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );
		$post_id = $this->factory->post->create( array( 'post_type' => 'post' ) );
		$this->set_options( array(
			'remove_everywhere'        => true,
			'enable_exclude_by_role'   => true,
			'exclude_by_role'          => array( 'editor' ),
		) );

		$result = $this->plugin->filter_comment_status( true, $post_id );

		$this->assertFalse( $result );
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	private function make_comment( $type = 'comment' ) {
		$comment              = new stdClass();
		$comment->comment_type = $type;
		return $comment;
	}
}
