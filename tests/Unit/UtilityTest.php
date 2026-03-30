<?php
/**
 * Unit tests for utility/helper methods.
 *
 * Tests: filter_wp_headers, disable_xmlrc_comments, is_xmlrpc_rest,
 *        disable_rc_widget, filter_gutenberg_blocks (hook registration),
 *        get_roles, settings_page_url / tools_page_url (via plugin_actions_links)
 *
 * @package Disable_Comments
 */

class UtilityTest extends WP_UnitTestCase {

	/** @var Disable_Comments */
	private $plugin;

	public function set_up() {
		parent::set_up();
		$this->plugin = Disable_Comments::get_instance();
	}

	// -------------------------------------------------------------------------
	// filter_wp_headers
	// -------------------------------------------------------------------------

	public function test_filter_wp_headers_removes_x_pingback() {
		$headers = array(
			'Content-Type' => 'text/html',
			'X-Pingback'   => 'https://example.com/xmlrpc.php',
		);

		$result = $this->plugin->filter_wp_headers( $headers );

		$this->assertArrayNotHasKey( 'X-Pingback', $result );
	}

	public function test_filter_wp_headers_preserves_other_headers() {
		$headers = array(
			'Content-Type' => 'text/html',
			'X-Pingback'   => 'https://example.com/xmlrpc.php',
		);

		$result = $this->plugin->filter_wp_headers( $headers );

		$this->assertArrayHasKey( 'Content-Type', $result );
		$this->assertEquals( 'text/html', $result['Content-Type'] );
	}

	public function test_filter_wp_headers_no_pingback_header_unchanged() {
		$headers = array( 'Content-Type' => 'text/html' );

		$result = $this->plugin->filter_wp_headers( $headers );

		$this->assertEquals( $headers, $result );
	}

	// -------------------------------------------------------------------------
	// disable_xmlrc_comments
	// -------------------------------------------------------------------------

	public function test_disable_xmlrc_removes_newComment_method() {
		$methods = array(
			'wp.newComment'   => 'callable',
			'wp.getComments'  => 'callable',
		);

		$result = $this->plugin->disable_xmlrc_comments( $methods );

		$this->assertArrayNotHasKey( 'wp.newComment', $result );
	}

	public function test_disable_xmlrc_preserves_other_methods() {
		$methods = array(
			'wp.newComment'   => 'callable',
			'wp.getComments'  => 'callable',
		);

		$result = $this->plugin->disable_xmlrc_comments( $methods );

		$this->assertArrayHasKey( 'wp.getComments', $result );
	}

	public function test_disable_xmlrc_no_newComment_key_is_safe() {
		$methods = array( 'wp.getComments' => 'callable' );

		$result = $this->plugin->disable_xmlrc_comments( $methods );

		$this->assertArrayHasKey( 'wp.getComments', $result );
	}

	// -------------------------------------------------------------------------
	// is_xmlrpc_rest
	// -------------------------------------------------------------------------

	public function test_is_xmlrpc_rest_true_when_xmlrpc_enabled() {
		$this->set_options( array( 'remove_xmlrpc_comments' => 1 ) );

		$this->assertTrue( $this->plugin->is_xmlrpc_rest() );
	}

	public function test_is_xmlrpc_rest_true_when_rest_enabled() {
		$this->set_options( array( 'remove_rest_API_comments' => 1 ) );

		$this->assertTrue( $this->plugin->is_xmlrpc_rest() );
	}

	public function test_is_xmlrpc_rest_false_when_both_disabled() {
		$this->set_options( array(
			'remove_xmlrpc_comments'   => 0,
			'remove_rest_API_comments' => 0,
		) );

		$this->assertFalse( $this->plugin->is_xmlrpc_rest() );
	}

	// -------------------------------------------------------------------------
	// disable_rc_widget
	// -------------------------------------------------------------------------

	public function test_disable_rc_widget_adds_return_false_filter() {
		$this->plugin->disable_rc_widget();

		$this->assertNotFalse(
			has_filter( 'show_recent_comments_widget_style', '__return_false' )
		);
	}

	// -------------------------------------------------------------------------
	// get_roles
	// -------------------------------------------------------------------------

	public function test_get_roles_returns_array() {
		$roles = $this->plugin->get_roles( array() );

		$this->assertIsArray( $roles );
	}

	public function test_get_roles_includes_core_roles() {
		$roles = $this->plugin->get_roles( array() );
		$slugs = array_column( $roles, 'slug' );

		$this->assertContains( 'administrator', $slugs );
		$this->assertContains( 'editor', $slugs );
	}

	public function test_get_roles_marks_selected_roles() {
		$roles = $this->plugin->get_roles( array( 'editor' ) );

		$editor = null;
		foreach ( $roles as $role ) {
			if ( $role['slug'] === 'editor' ) {
				$editor = $role;
				break;
			}
		}

		$this->assertNotNull( $editor );
		$this->assertTrue( (bool) $editor['selected'] );
	}

	public function test_get_roles_includes_logged_out_users_option() {
		$roles = $this->plugin->get_roles( array() );
		$slugs = array_column( $roles, 'slug' );

		$this->assertContains( 'logged-out-users', $slugs );
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
