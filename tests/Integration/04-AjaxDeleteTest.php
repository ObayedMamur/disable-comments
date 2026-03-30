<?php
/**
 * Integration tests for the delete comments AJAX handler.
 *
 * Tests all four delete_mode values via CLI path (bypasses nonce),
 * verifies actual DB state after deletion, and checks allowed comment
 * type exclusion in each mode.
 *
 * @package Disable_Comments
 */

class AjaxDeleteTest extends WP_Ajax_UnitTestCase {

	use PluginOptionsTrait {
		set_options as trait_set_options;
	}

	/** @var Disable_Comments */
	private $plugin;

	public function set_up() {
		parent::set_up();
		$this->plugin = Disable_Comments::get_instance();
		$this->_setRole( 'administrator' );
		// Bypass nonce gate: tests call the method directly without WP_CLI.
		$this->plugin->is_CLI = true;
		$this->set_options( array(
			'remove_everywhere'     => false,
			'allowed_comment_types' => array(),
		) );
	}

	// -------------------------------------------------------------------------
	// delete_everywhere — deletes all comments
	// -------------------------------------------------------------------------

	public function test_delete_everywhere_removes_all_comments() {
		$post_id = $this->factory->post->create();
		$this->factory->comment->create_many( 3, array( 'comment_post_ID' => $post_id ) );
		$this->assertGreaterThan( 0, $this->get_comment_count() );

		$this->plugin->delete_comments_settings( array( 'delete_mode' => 'delete_everywhere' ) );

		$this->assertEquals( 0, $this->get_comment_count() );
	}

	public function test_delete_everywhere_resets_post_comment_count() {
		$post_id = $this->factory->post->create();
		$this->factory->comment->create_many( 2, array( 'comment_post_ID' => $post_id ) );

		$this->plugin->delete_comments_settings( array( 'delete_mode' => 'delete_everywhere' ) );

		$post = get_post( $post_id );
		$this->assertEquals( 0, (int) $post->comment_count );
	}

	public function test_delete_everywhere_preserves_allowed_comment_types() {
		$post_id = $this->factory->post->create();
		$this->factory->comment->create( array(
			'comment_post_ID' => $post_id,
			'comment_type'    => '',
		) );
		$this->factory->comment->create( array(
			'comment_post_ID' => $post_id,
			'comment_type'    => 'review',
		) );
		$this->set_options( array( 'allowed_comment_types' => array( 'review' ) ) );

		$this->plugin->delete_comments_settings( array( 'delete_mode' => 'delete_everywhere' ) );

		$remaining = get_comments( array( 'post_id' => $post_id, 'type' => 'review' ) );
		$this->assertCount( 1, $remaining );
		$this->assertEquals( 0, $this->get_comment_count_for_type( '' ) );
	}

	// -------------------------------------------------------------------------
	// selected_delete_types — delete by post type
	// -------------------------------------------------------------------------

	public function test_delete_by_post_type_removes_only_that_type() {
		$post_id = $this->factory->post->create( array( 'post_type' => 'post' ) );
		$page_id = $this->factory->post->create( array( 'post_type' => 'page' ) );
		$this->factory->comment->create( array( 'comment_post_ID' => $post_id ) );
		$this->factory->comment->create( array( 'comment_post_ID' => $page_id ) );

		$this->plugin->delete_comments_settings( array(
			'delete_mode'  => 'selected_delete_types',
			'delete_types' => array( 'post' ),
		) );

		$post_comments = get_comments( array( 'post_id' => $post_id ) );
		$page_comments = get_comments( array( 'post_id' => $page_id ) );
		$this->assertEmpty( $post_comments );
		$this->assertCount( 1, $page_comments );
	}

	public function test_delete_by_post_type_preserves_allowed_types() {
		$post_id = $this->factory->post->create( array( 'post_type' => 'post' ) );
		$this->factory->comment->create( array(
			'comment_post_ID' => $post_id,
			'comment_type'    => '',
		) );
		$this->factory->comment->create( array(
			'comment_post_ID' => $post_id,
			'comment_type'    => 'review',
		) );
		$this->set_options( array( 'allowed_comment_types' => array( 'review' ) ) );

		$this->plugin->delete_comments_settings( array(
			'delete_mode'  => 'selected_delete_types',
			'delete_types' => array( 'post' ),
		) );

		$remaining = get_comments( array( 'post_id' => $post_id ) );
		$this->assertCount( 1, $remaining );
		$this->assertEquals( 'review', $remaining[0]->comment_type );
	}

	public function test_delete_by_post_type_ignores_nonexistent_type() {
		$post_id = $this->factory->post->create( array( 'post_type' => 'post' ) );
		$this->factory->comment->create( array( 'comment_post_ID' => $post_id ) );

		$this->plugin->delete_comments_settings( array(
			'delete_mode'  => 'selected_delete_types',
			'delete_types' => array( 'nonexistent_type_xyz' ),
		) );

		$this->assertCount( 1, get_comments( array( 'post_id' => $post_id ) ) );
	}

	// -------------------------------------------------------------------------
	// selected_delete_comment_types — delete by comment type
	// -------------------------------------------------------------------------

	public function test_delete_by_comment_type_removes_only_that_type() {
		$post_id = $this->factory->post->create();
		$this->factory->comment->create( array(
			'comment_post_ID' => $post_id,
			'comment_type'    => 'pingback',
		) );
		$this->factory->comment->create( array(
			'comment_post_ID' => $post_id,
			'comment_type'    => '',
		) );

		// get_all_comment_types queries DB for existing types
		$all_types = $this->plugin->_get_all_comment_types();
		$this->assertArrayHasKey( 'pingback', $all_types );

		$this->plugin->delete_comments_settings( array(
			'delete_mode'          => 'selected_delete_comment_types',
			'delete_comment_types' => array( 'pingback' ),
		) );

		$remaining = get_comments( array( 'post_id' => $post_id ) );
		$this->assertCount( 1, $remaining );
		$this->assertEquals( 'comment', $remaining[0]->comment_type );
	}

	// -------------------------------------------------------------------------
	// delete_spam — deletes only spam
	// -------------------------------------------------------------------------

	public function test_delete_spam_removes_only_spam_comments() {
		$post_id = $this->factory->post->create();
		$this->factory->comment->create( array(
			'comment_post_ID'  => $post_id,
			'comment_approved' => 'spam',
		) );
		$this->factory->comment->create( array(
			'comment_post_ID'  => $post_id,
			'comment_approved' => '1',
		) );

		$this->plugin->delete_comments_settings( array( 'delete_mode' => 'delete_spam' ) );

		$all_comments = get_comments( array( 'post_id' => $post_id, 'status' => 'all' ) );
		$this->assertCount( 1, $all_comments );
		$this->assertEquals( '1', $all_comments[0]->comment_approved );
	}

	public function test_delete_spam_preserves_allowed_type_spam() {
		$post_id = $this->factory->post->create();
		$this->factory->comment->create( array(
			'comment_post_ID'  => $post_id,
			'comment_approved' => 'spam',
			'comment_type'     => 'review',
		) );
		$this->factory->comment->create( array(
			'comment_post_ID'  => $post_id,
			'comment_approved' => 'spam',
			'comment_type'     => '',
		) );
		$this->set_options( array( 'allowed_comment_types' => array( 'review' ) ) );

		$this->plugin->delete_comments_settings( array( 'delete_mode' => 'delete_spam' ) );

		$remaining = get_comments( array( 'post_id' => $post_id, 'status' => 'spam' ) );
		$this->assertCount( 1, $remaining );
		$this->assertEquals( 'review', $remaining[0]->comment_type );
	}

	// -------------------------------------------------------------------------
	// AJAX nonce verification
	// -------------------------------------------------------------------------

	public function test_ajax_delete_fails_with_bad_nonce() {
		$this->plugin->is_CLI = false; // Test real AJAX path so wp_die() is called.
		$post_id = $this->factory->post->create();
		$this->factory->comment->create( array( 'comment_post_ID' => $post_id ) );
		$_POST['nonce'] = 'bad_nonce';
		$_POST['data']  = 'delete_mode=delete_everywhere';

		try {
			$this->_handleAjax( 'disable_comments_delete_comments' );
		} catch ( WPAjaxDieContinueException $e ) {
			// Expected: wp_send_json_success() calls wp_die('') internally.
		}

		$this->assertGreaterThan( 0, $this->get_comment_count() );
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	private function get_comment_count() {
		global $wpdb;
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->comments" );
	}

	private function get_comment_count_for_type( $type ) {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM $wpdb->comments WHERE comment_type = %s",
			$type
		) );
	}

	protected function set_options( array $overrides = array() ): void {
		$this->trait_set_options( $overrides );
		$this->plugin->is_CLI = true;
	}
}
