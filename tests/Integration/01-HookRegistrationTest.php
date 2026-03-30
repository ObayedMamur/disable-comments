<?php
/**
 * Integration tests for hook registration.
 *
 * Tests: init_filters (conditional hook registration based on settings),
 *        init_wploaded_filters (post type support removal, admin/frontend split)
 *
 * @package Disable_Comments
 */

class HookRegistrationTest extends WP_UnitTestCase {

	use PluginOptionsTrait;

	/** @var Disable_Comments */
	private $plugin;

	public function set_up() {
		parent::set_up();
		$this->plugin = Disable_Comments::get_instance();
	}

	public function tear_down() {
		// Restore comment/trackback support that tests may have removed globally.
		add_post_type_support( 'post', 'comments' );
		add_post_type_support( 'post', 'trackbacks' );
		parent::tear_down();
	}

	// -------------------------------------------------------------------------
	// Constructor-registered hooks (always present)
	// -------------------------------------------------------------------------

	public function test_constructor_registers_plugins_loaded_hook() {
		// Reset within this test so hooks are registered in the current $wp_filter snapshot.
		$reflection = new ReflectionProperty( Disable_Comments::class, 'instance' );
		$reflection->setAccessible( true );
		$reflection->setValue( null, null );
		$plugin = Disable_Comments::get_instance();

		$this->assertNotFalse( has_action( 'plugins_loaded', array( $plugin, 'init_filters' ) ) );
	}

	public function test_constructor_registers_wp_loaded_hooks() {
		// Reset within this test so hooks are registered in the current $wp_filter snapshot.
		$reflection = new ReflectionProperty( Disable_Comments::class, 'instance' );
		$reflection->setAccessible( true );
		$reflection->setValue( null, null );
		$plugin = Disable_Comments::get_instance();

		$this->assertNotFalse( has_action( 'wp_loaded', array( $plugin, 'start_plugin_usage_tracking' ) ) );
	}

	// -------------------------------------------------------------------------
	// init_filters — remove_everywhere = true
	// -------------------------------------------------------------------------

	public function test_remove_everywhere_registers_widgets_init() {
		$this->set_options( array( 'remove_everywhere' => true ) );

		$this->plugin->init_filters();

		$this->assertNotFalse( has_action( 'widgets_init', array( $this->plugin, 'disable_rc_widget' ) ) );
	}

	public function test_remove_everywhere_registers_wp_headers_filter() {
		$this->set_options( array( 'remove_everywhere' => true ) );

		$this->plugin->init_filters();

		$this->assertNotFalse( has_filter( 'wp_headers', array( $this->plugin, 'filter_wp_headers' ) ) );
	}

	public function test_remove_everywhere_registers_template_redirect_filter_query() {
		$this->set_options( array( 'remove_everywhere' => true ) );

		$this->plugin->init_filters();

		$this->assertEquals( 9, has_action( 'template_redirect', array( $this->plugin, 'filter_query' ) ) );
	}

	public function test_remove_everywhere_registers_rest_dispatch_filter() {
		$this->set_options( array( 'remove_everywhere' => true ) );

		$this->plugin->init_filters();

		$this->assertNotFalse( has_filter( 'rest_pre_dispatch', array( $this->plugin, 'filter_rest_comment_dispatch' ) ) );
	}

	// -------------------------------------------------------------------------
	// init_filters — selective: xmlrpc flag
	// -------------------------------------------------------------------------

	public function test_xmlrpc_flag_registers_xmlrpc_filter() {
		$this->set_options( array(
			'remove_everywhere'      => false,
			'remove_xmlrpc_comments' => 1,
		) );

		$this->plugin->init_filters();

		$this->assertNotFalse( has_filter( 'xmlrpc_methods', array( $this->plugin, 'disable_xmlrc_comments' ) ) );
	}

	public function test_no_xmlrpc_flag_does_not_register_xmlrpc_filter() {
		$this->set_options( array(
			'remove_everywhere'      => false,
			'remove_xmlrpc_comments' => 0,
		) );
		remove_filter( 'xmlrpc_methods', array( $this->plugin, 'disable_xmlrc_comments' ) );

		$this->plugin->init_filters();

		$this->assertFalse( has_filter( 'xmlrpc_methods', array( $this->plugin, 'disable_xmlrc_comments' ) ) );
	}

	// -------------------------------------------------------------------------
	// init_filters — selective: rest api flag
	// -------------------------------------------------------------------------

	public function test_rest_api_flag_registers_rest_insert_filter() {
		$this->set_options( array(
			'remove_everywhere'        => false,
			'remove_rest_API_comments' => 1,
		) );

		$this->plugin->init_filters();

		$this->assertNotFalse( has_filter( 'rest_pre_insert_comment', array( $this->plugin, 'disable_rest_API_comments' ) ) );
	}

	// -------------------------------------------------------------------------
	// init_wploaded_filters — post type support removal
	// -------------------------------------------------------------------------

	public function test_disabled_post_type_support_removed() {
		add_post_type_support( 'post', 'comments' );
		$this->set_options( array(
			'remove_everywhere'   => false,
			'disabled_post_types' => array( 'post' ),
		) );

		$this->plugin->init_wploaded_filters();

		$this->assertFalse( post_type_supports( 'post', 'comments' ) );
	}

	public function test_disabled_post_type_trackback_support_removed() {
		// Ensure 'comments' support is present (required by the code path that removes 'trackbacks').
		add_post_type_support( 'post', 'comments' );
		add_post_type_support( 'post', 'trackbacks' );
		$this->set_options( array(
			'remove_everywhere'   => false,
			'disabled_post_types' => array( 'post' ),
		) );

		$this->plugin->init_wploaded_filters();

		$this->assertFalse( post_type_supports( 'post', 'trackbacks' ) );
	}

	public function test_non_disabled_post_type_keeps_comment_support() {
		add_post_type_support( 'page', 'comments' );
		$this->set_options( array(
			'remove_everywhere'   => false,
			'disabled_post_types' => array( 'post' ),
		) );

		$this->plugin->init_wploaded_filters();

		$this->assertTrue( post_type_supports( 'page', 'comments' ) );
	}

	public function test_show_existing_comments_keeps_post_type_support() {
		add_post_type_support( 'post', 'comments' );
		$this->set_options( array(
			'remove_everywhere'      => false,
			'disabled_post_types'    => array( 'post' ),
			'show_existing_comments' => true,
		) );

		$this->plugin->init_wploaded_filters();

		$this->assertTrue( post_type_supports( 'post', 'comments' ) );
	}

	// -------------------------------------------------------------------------
	// init_wploaded_filters — comment filter hooks added
	// -------------------------------------------------------------------------

	public function test_disabled_types_registers_comments_array_filter() {
		$this->set_options( array(
			'remove_everywhere'   => false,
			'disabled_post_types' => array( 'post' ),
		) );

		$this->plugin->init_wploaded_filters();

		$this->assertEquals( 20, has_filter( 'comments_array', array( $this->plugin, 'filter_existing_comments' ) ) );
	}

	public function test_disabled_types_registers_comments_open_filter() {
		$this->set_options( array(
			'remove_everywhere'   => false,
			'disabled_post_types' => array( 'post' ),
		) );

		$this->plugin->init_wploaded_filters();

		$this->assertEquals( 20, has_filter( 'comments_open', array( $this->plugin, 'filter_comment_status' ) ) );
	}

	public function test_remove_everywhere_registers_get_comments_number_filter() {
		$this->set_options( array( 'remove_everywhere' => true ) );

		$this->plugin->init_wploaded_filters();

		$this->assertEquals( 20, has_filter( 'get_comments_number', array( $this->plugin, 'filter_comments_number' ) ) );
	}

	public function test_no_disable_does_not_register_comment_filters() {
		$this->set_options( array(
			'remove_everywhere'   => false,
			'disabled_post_types' => array(),
		) );
		remove_filter( 'comments_open', array( $this->plugin, 'filter_comment_status' ) );
		remove_filter( 'comments_array', array( $this->plugin, 'filter_existing_comments' ) );

		$this->plugin->init_wploaded_filters();

		$this->assertFalse( has_filter( 'comments_open', array( $this->plugin, 'filter_comment_status' ) ) );
	}

}
