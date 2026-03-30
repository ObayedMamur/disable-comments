<?php
/**
 * Integration tests for REST API comment filtering.
 *
 * Tests: filter_rest_comment_dispatch (block/allow by comment type),
 *        filter_rest_comment_query (empty results for non-allowed),
 *        disable_rest_API_comments (block insert)
 *
 * @package Disable_Comments
 */

class RestApiTest extends WP_Test_REST_TestCase {

	/** @var Disable_Comments */
	private $plugin;

	public function set_up() {
		parent::set_up();
		$this->plugin = Disable_Comments::get_instance();
	}

	public function tear_down() {
		$this->reset_singleton();
		parent::tear_down();
	}

	// -------------------------------------------------------------------------
	// filter_rest_comment_dispatch — remove_everywhere blocks regular comments
	// -------------------------------------------------------------------------

	public function test_rest_dispatch_blocked_when_remove_everywhere() {
		$this->set_options( array( 'remove_everywhere' => true ) );
		$this->plugin->init_filters();

		$request = new WP_REST_Request( 'POST', '/wp/v2/comments' );
		$request->set_param( 'type', 'comment' );

		$result = $this->plugin->filter_rest_comment_dispatch( null, rest_get_server(), $request );

		$this->assertInstanceOf( 'WP_Error', $result );
	}

	public function test_rest_dispatch_allows_non_comment_route() {
		$this->set_options( array( 'remove_everywhere' => true ) );

		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );

		$result = $this->plugin->filter_rest_comment_dispatch( null, rest_get_server(), $request );

		$this->assertNull( $result );
	}

	public function test_rest_dispatch_allows_allowed_comment_type() {
		$this->set_options( array(
			'remove_everywhere'     => true,
			'allowed_comment_types' => array( 'block_comment' ),
		) );

		$request = new WP_REST_Request( 'POST', '/wp/v2/comments' );
		$request->set_param( 'type', 'block_comment' );

		$result = $this->plugin->filter_rest_comment_dispatch( null, rest_get_server(), $request );

		$this->assertNull( $result );
	}

	public function test_rest_dispatch_no_block_when_not_remove_everywhere_and_no_rest_flag() {
		$this->set_options( array(
			'remove_everywhere'        => false,
			'remove_rest_API_comments' => 0,
		) );
		remove_filter( 'rest_pre_dispatch', array( $this->plugin, 'filter_rest_comment_dispatch' ) );

		$request = new WP_REST_Request( 'POST', '/wp/v2/comments' );

		$result = $this->plugin->filter_rest_comment_dispatch( null, rest_get_server(), $request );

		// When flags are off, hook should not be registered — but if called directly, should pass.
		$this->assertNull( $result );
	}

	// -------------------------------------------------------------------------
	// filter_rest_comment_query — returns empty result set
	// -------------------------------------------------------------------------

	public function test_rest_comment_query_returns_impossible_comment_in() {
		$this->set_options( array( 'remove_everywhere' => true ) );

		$request     = new WP_REST_Request( 'GET', '/wp/v2/comments' );
		$prepared    = array();

		$result = $this->plugin->filter_rest_comment_query( $prepared, $request );

		$this->assertArrayHasKey( 'comment__in', $result );
		$this->assertEquals( array( 0 ), $result['comment__in'] );
	}

	public function test_rest_comment_query_passes_through_for_allowed_type() {
		$this->set_options( array(
			'remove_everywhere'     => true,
			'allowed_comment_types' => array( 'review' ),
		) );

		$request  = new WP_REST_Request( 'GET', '/wp/v2/comments' );
		$request->set_param( 'type', 'review' );
		$prepared = array();

		$result = $this->plugin->filter_rest_comment_query( $prepared, $request );

		$this->assertArrayNotHasKey( 'comment__in', $result );
	}

	// -------------------------------------------------------------------------
	// disable_rest_API_comments — blocks insert
	// -------------------------------------------------------------------------

	public function test_disable_rest_insert_returns_null_for_regular_comment() {
		$this->set_options( array(
			'remove_rest_API_comments' => 1,
			'allowed_comment_types'    => array(),
		) );

		$request = new WP_REST_Request( 'POST', '/wp/v2/comments' );
		$request->set_param( 'type', 'comment' );

		$result = $this->plugin->disable_rest_API_comments( array( 'prepared' => true ), $request );

		$this->assertNull( $result );
	}

	public function test_disable_rest_insert_passes_through_allowed_type() {
		$this->set_options( array(
			'remove_rest_API_comments' => 1,
			'allowed_comment_types'    => array( 'review' ),
		) );

		$request = new WP_REST_Request( 'POST', '/wp/v2/comments' );
		$request->set_param( 'type', 'review' );
		$prepared = array( 'comment_type' => 'review' );

		$result = $this->plugin->disable_rest_API_comments( $prepared, $request );

		$this->assertEquals( $prepared, $result );
	}

	// -------------------------------------------------------------------------
	// filter_rest_endpoints — registered but currently a passthrough
	// -------------------------------------------------------------------------

	public function test_filter_rest_endpoints_returns_endpoints_unchanged() {
		$endpoints = array( '/wp/v2/comments' => array( 'GET' ) );

		$result = $this->plugin->filter_rest_endpoints( $endpoints );

		$this->assertEquals( $endpoints, $result );
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
			'sitewide_settings'        => false,
		);
		$options = array_merge( $defaults, $overrides );
		update_option( 'disable_comments_options', $options );
		$this->reset_singleton();
		$this->plugin = Disable_Comments::get_instance();
	}

	private function reset_singleton() {
		$reflection = new ReflectionProperty( Disable_Comments::class, 'instance' );
		$reflection->setAccessible( true );
		$reflection->setValue( null, null );
	}
}
