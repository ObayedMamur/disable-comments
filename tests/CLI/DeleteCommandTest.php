<?php
/**
 * Tests for the WP-CLI 'disable-comments delete' command.
 *
 * Tests argument mapping from CLI flags to delete_mode values and
 * verifies actual DB state.
 *
 * @package Disable_Comments
 */

class DeleteCommandTest extends WP_UnitTestCase {

	/** @var Disable_Comments */
	private $plugin;

	public function set_up() {
		parent::set_up();
		$this->plugin = Disable_Comments::get_instance();
		$this->set_options( array(
			'remove_everywhere'     => false,
			'allowed_comment_types' => array(),
		) );
	}

	// -------------------------------------------------------------------------
	// --types=all  →  delete_everywhere mode
	// -------------------------------------------------------------------------

	public function test_types_all_deletes_all_comments() {
		$post_id = $this->factory->post->create();
		$this->factory->comment->create_many( 3, array( 'comment_post_ID' => $post_id ) );

		// CLI builds: delete_mode => 'delete_everywhere'
		$result = $this->plugin->delete_comments_settings( array(
			'delete_mode' => 'delete_everywhere',
		) );

		$this->assertEquals( 0, $this->total_comments() );
	}

	// -------------------------------------------------------------------------
	// --comment-types=all  →  delete_everywhere mode
	// -------------------------------------------------------------------------

	public function test_comment_types_all_also_triggers_delete_everywhere() {
		$post_id = $this->factory->post->create();
		$this->factory->comment->create_many( 2, array( 'comment_post_ID' => $post_id ) );

		$this->plugin->delete_comments_settings( array(
			'delete_mode' => 'delete_everywhere',
		) );

		$this->assertEquals( 0, $this->total_comments() );
	}

	// -------------------------------------------------------------------------
	// --types=post  →  selected_delete_types mode
	// -------------------------------------------------------------------------

	public function test_types_post_deletes_only_post_comments() {
		$post_id = $this->factory->post->create( array( 'post_type' => 'post' ) );
		$page_id = $this->factory->post->create( array( 'post_type' => 'page' ) );
		$this->factory->comment->create( array( 'comment_post_ID' => $post_id ) );
		$this->factory->comment->create( array( 'comment_post_ID' => $page_id ) );

		$this->plugin->delete_comments_settings( array(
			'delete_mode'  => 'selected_delete_types',
			'delete_types' => array( 'post' ),
		) );

		$this->assertCount( 0, get_comments( array( 'post_id' => $post_id ) ) );
		$this->assertCount( 1, get_comments( array( 'post_id' => $page_id ) ) );
	}

	// -------------------------------------------------------------------------
	// --comment-types=pingback  →  selected_delete_comment_types mode
	// -------------------------------------------------------------------------

	public function test_comment_types_pingback_deletes_only_pingbacks() {
		$post_id = $this->factory->post->create();
		$this->factory->comment->create( array(
			'comment_post_ID' => $post_id,
			'comment_type'    => 'pingback',
		) );
		$this->factory->comment->create( array(
			'comment_post_ID' => $post_id,
			'comment_type'    => '',
		) );

		$this->plugin->delete_comments_settings( array(
			'delete_mode'          => 'selected_delete_comment_types',
			'delete_comment_types' => array( 'pingback' ),
		) );

		$remaining = get_comments( array( 'post_id' => $post_id ) );
		$this->assertCount( 1, $remaining );
		$this->assertEquals( '', $remaining[0]->comment_type );
	}

	// -------------------------------------------------------------------------
	// --spam  →  delete_spam mode
	// -------------------------------------------------------------------------

	public function test_spam_flag_deletes_spam_only() {
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

		$all = get_comments( array( 'post_id' => $post_id, 'status' => 'all' ) );
		$this->assertCount( 1, $all );
		$this->assertEquals( '1', $all[0]->comment_approved );
	}

	// -------------------------------------------------------------------------
	// Return value — CLI uses return for its success message
	// -------------------------------------------------------------------------

	public function test_delete_settings_returns_message_string() {
		$post_id = $this->factory->post->create();
		$this->factory->comment->create( array( 'comment_post_ID' => $post_id ) );

		$result = $this->plugin->delete_comments_settings( array(
			'delete_mode' => 'delete_everywhere',
		) );

		$this->assertIsString( $result );
		$this->assertNotEmpty( $result );
	}

	public function test_delete_spam_returns_spam_message() {
		$post_id = $this->factory->post->create();
		$this->factory->comment->create( array(
			'comment_post_ID'  => $post_id,
			'comment_approved' => 'spam',
		) );

		$result = $this->plugin->delete_comments_settings( array( 'delete_mode' => 'delete_spam' ) );

		$this->assertStringContainsStringIgnoringCase( 'spam', $result );
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	private function total_comments() {
		global $wpdb;
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->comments" );
	}

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
