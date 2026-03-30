<?php
/**
 * Integration tests for XML-RPC comment handling.
 *
 * Tests: disable_xmlrc_comments (method removal),
 *        xmlrpc_methods filter registration conditional on settings flag,
 *        filter_wp_headers (X-Pingback removal)
 *
 * @package Disable_Comments
 */

class XmlRpcTest extends WP_UnitTestCase {

	/** @var Disable_Comments */
	private $plugin;

	public function set_up() {
		parent::set_up();
		$this->plugin = Disable_Comments::get_instance();
	}

	// -------------------------------------------------------------------------
	// disable_xmlrc_comments — method list manipulation
	// -------------------------------------------------------------------------

	public function test_wp_newComment_removed_from_methods() {
		$methods = array(
			'wp.newComment'   => '__return_null',
			'wp.getComments'  => '__return_null',
			'wp.deleteComment'=> '__return_null',
		);

		$result = $this->plugin->disable_xmlrc_comments( $methods );

		$this->assertArrayNotHasKey( 'wp.newComment', $result );
	}

	public function test_other_xmlrpc_methods_preserved() {
		$methods = array(
			'wp.newComment'   => '__return_null',
			'wp.getComments'  => '__return_null',
			'wp.editPost'     => '__return_null',
		);

		$result = $this->plugin->disable_xmlrc_comments( $methods );

		$this->assertArrayHasKey( 'wp.getComments', $result );
		$this->assertArrayHasKey( 'wp.editPost', $result );
	}

	public function test_empty_methods_list_returns_empty() {
		$result = $this->plugin->disable_xmlrc_comments( array() );

		$this->assertEmpty( $result );
	}

	// -------------------------------------------------------------------------
	// Hook registration — xmlrpc_methods filter only when flag set
	// -------------------------------------------------------------------------

	public function test_xmlrpc_filter_registered_when_flag_enabled() {
		$this->set_options( array( 'remove_xmlrpc_comments' => 1 ) );

		$this->plugin->init_filters();

		$this->assertNotFalse(
			has_filter( 'xmlrpc_methods', array( $this->plugin, 'disable_xmlrc_comments' ) )
		);
	}

	public function test_xmlrpc_filter_not_registered_when_flag_disabled() {
		$this->set_options( array( 'remove_xmlrpc_comments' => 0 ) );
		remove_filter( 'xmlrpc_methods', array( $this->plugin, 'disable_xmlrc_comments' ) );

		$this->plugin->init_filters();

		$this->assertFalse(
			has_filter( 'xmlrpc_methods', array( $this->plugin, 'disable_xmlrc_comments' ) )
		);
	}

	// -------------------------------------------------------------------------
	// filter_wp_headers — X-Pingback removal (companion to XML-RPC)
	// -------------------------------------------------------------------------

	public function test_x_pingback_header_stripped() {
		$headers = array(
			'X-Pingback'   => 'https://example.com/xmlrpc.php',
			'Content-Type' => 'text/html; charset=UTF-8',
		);

		$result = $this->plugin->filter_wp_headers( $headers );

		$this->assertArrayNotHasKey( 'X-Pingback', $result );
		$this->assertArrayHasKey( 'Content-Type', $result );
	}

	public function test_wp_headers_filter_registered_when_remove_everywhere() {
		$this->set_options( array( 'remove_everywhere' => true ) );

		$this->plugin->init_filters();

		$this->assertNotFalse(
			has_filter( 'wp_headers', array( $this->plugin, 'filter_wp_headers' ) )
		);
	}

	// -------------------------------------------------------------------------
	// is_xmlrpc_rest — composite flag check
	// -------------------------------------------------------------------------

	public function test_is_xmlrpc_rest_true_when_xmlrpc_flag_set() {
		$this->set_options( array(
			'remove_xmlrpc_comments'   => 1,
			'remove_rest_API_comments' => 0,
		) );

		$this->assertTrue( $this->plugin->is_xmlrpc_rest() );
	}

	public function test_is_xmlrpc_rest_true_when_rest_flag_set() {
		$this->set_options( array(
			'remove_xmlrpc_comments'   => 0,
			'remove_rest_API_comments' => 1,
		) );

		$this->assertTrue( $this->plugin->is_xmlrpc_rest() );
	}

	public function test_is_xmlrpc_rest_false_when_both_off() {
		$this->set_options( array(
			'remove_xmlrpc_comments'   => 0,
			'remove_rest_API_comments' => 0,
		) );

		$this->assertFalse( $this->plugin->is_xmlrpc_rest() );
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
		$reflection = new ReflectionProperty( Disable_Comments::class, 'instance' );
		$reflection->setAccessible( true );
		$reflection->setValue( null, null );
		$this->plugin = Disable_Comments::get_instance();
	}
}
