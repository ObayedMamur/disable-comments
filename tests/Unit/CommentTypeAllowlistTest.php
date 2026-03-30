<?php
/**
 * Unit tests for comment type allowlist methods.
 *
 * Tests: get_allowed_comment_types (via filter_existing_comments),
 *        has_allowed_comment_types, is_comment_type_allowed,
 *        get_available_comment_type_options, is_allowed_comment_type_request
 *
 * @package Disable_Comments
 */

class CommentTypeAllowlistTest extends WP_UnitTestCase {

	/** @var Disable_Comments */
	private $plugin;

	public function set_up() {
		parent::set_up();
		$this->plugin = Disable_Comments::get_instance();
	}

	// -------------------------------------------------------------------------
	// Allowlist content — filter_existing_comments with allowed types
	// -------------------------------------------------------------------------

	public function test_allowed_type_kept_when_comments_globally_disabled() {
		$post_id = $this->factory->post->create();
		$this->set_options( array(
			'remove_everywhere'     => true,
			'allowed_comment_types' => array( 'review' ),
		) );

		$regular = $this->make_comment( '' );
		$review  = $this->make_comment( 'review' );

		$result = $this->plugin->filter_existing_comments( array( $regular, $review ), $post_id );

		$this->assertCount( 1, $result );
		$this->assertEquals( 'review', $result[0]->comment_type );
	}

	public function test_multiple_allowed_types_all_kept() {
		$post_id = $this->factory->post->create();
		$this->set_options( array(
			'remove_everywhere'     => true,
			'allowed_comment_types' => array( 'review', 'block_comment' ),
		) );

		$comments = array(
			$this->make_comment( 'comment' ),
			$this->make_comment( 'review' ),
			$this->make_comment( 'block_comment' ),
		);

		$result = $this->plugin->filter_existing_comments( $comments, $post_id );

		$this->assertCount( 2, $result );
	}

	public function test_empty_allowlist_removes_all_when_disabled() {
		$post_id = $this->factory->post->create();
		$this->set_options( array(
			'remove_everywhere'     => true,
			'allowed_comment_types' => array(),
		) );

		$result = $this->plugin->filter_existing_comments(
			array( $this->make_comment( 'comment' ), $this->make_comment( 'review' ) ),
			$post_id
		);

		$this->assertEmpty( $result );
	}

	public function test_allowed_type_not_present_in_comments_returns_empty() {
		$post_id = $this->factory->post->create();
		$this->set_options( array(
			'remove_everywhere'     => true,
			'allowed_comment_types' => array( 'review' ),
		) );

		$result = $this->plugin->filter_existing_comments(
			array( $this->make_comment( 'comment' ) ),
			$post_id
		);

		$this->assertEmpty( $result );
	}

	// -------------------------------------------------------------------------
	// filter_comments_number with allowed types
	// -------------------------------------------------------------------------

	public function test_comments_number_returns_zero_when_no_allowed_types_and_disabled() {
		$post_id = $this->factory->post->create();
		$this->set_options( array(
			'remove_everywhere'     => true,
			'allowed_comment_types' => array(),
		) );

		$result = $this->plugin->filter_comments_number( 10, $post_id );

		$this->assertEquals( 0, $result );
	}

	// -------------------------------------------------------------------------
	// get_available_comment_type_options — public method
	// -------------------------------------------------------------------------

	public function test_get_available_comment_type_options_returns_array() {
		$options = $this->plugin->get_available_comment_type_options();

		$this->assertIsArray( $options );
	}

	public function test_get_available_comment_type_options_filterable() {
		add_filter( 'disable_comments_known_comment_types', function ( $types ) {
			$types['custom_type'] = 'Custom Type';
			return $types;
		} );

		$options = $this->plugin->get_available_comment_type_options();

		$this->assertArrayHasKey( 'custom_type', $options );

		remove_all_filters( 'disable_comments_known_comment_types' );
	}

	// -------------------------------------------------------------------------
	// is_allowed_comment_type_request — via disable_rest_API_comments
	// -------------------------------------------------------------------------

	public function test_disable_rest_API_comments_blocks_regular_comment() {
		$this->set_options( array(
			'remove_rest_API_comments' => 1,
			'allowed_comment_types'    => array(),
		) );
		$request = new WP_REST_Request( 'POST', '/wp/v2/comments' );

		$result = $this->plugin->disable_rest_API_comments( array( 'prepared' => true ), $request );

		$this->assertNull( $result );
	}

	public function test_disable_rest_API_comments_allows_allowed_type() {
		$this->set_options( array(
			'remove_rest_API_comments' => 1,
			'allowed_comment_types'    => array( 'block_comment' ),
		) );
		$request = new WP_REST_Request( 'POST', '/wp/v2/comments' );
		$request->set_param( 'type', 'block_comment' );
		$prepared = array( 'comment_type' => 'block_comment' );

		$result = $this->plugin->disable_rest_API_comments( $prepared, $request );

		$this->assertEquals( $prepared, $result );
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

	private function make_comment( $type = 'comment' ) {
		$c               = new stdClass();
		$c->comment_type = $type;
		return $c;
	}
}
