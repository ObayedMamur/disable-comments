<?php
/**
 * Integration tests for admin menu, dashboard, and admin bar filtering.
 *
 * Tests: filter_admin_menu (comment menu removal), filter_dashboard,
 *        admin_css hook registration, filter_admin_bar hook registration
 *
 * @package Disable_Comments
 */

class AdminMenuTest extends WP_UnitTestCase {

	/** @var Disable_Comments */
	private $plugin;

	public function set_up() {
		parent::set_up();
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );
		$this->plugin = Disable_Comments::get_instance();
	}

	public function tear_down() {
		wp_set_current_user( 0 );
		parent::tear_down();
	}

	// -------------------------------------------------------------------------
	// filter_admin_menu — remove comment menu items when remove_everywhere
	// -------------------------------------------------------------------------

	public function test_filter_admin_menu_removes_comments_menu() {
		global $menu, $submenu;
		$menu[25]     = array( 'Comments', 'edit_posts', 'edit-comments.php' );
		$submenu['options-general.php'][] = array( 'Discussion', 'manage_options', 'options-discussion.php' );

		$this->plugin->filter_admin_menu();

		$this->assertArrayNotHasKey( 25, $menu );
	}

	public function test_filter_admin_menu_removes_discussion_submenu() {
		global $submenu;
		$submenu['options-general.php'] = array(
			array( 'General', 'manage_options', 'options-general.php' ),
			array( 'Discussion', 'manage_options', 'options-discussion.php' ),
		);

		$this->plugin->filter_admin_menu();

		$discussion_exists = false;
		if ( isset( $submenu['options-general.php'] ) ) {
			foreach ( $submenu['options-general.php'] as $item ) {
				if ( $item[2] === 'options-discussion.php' ) {
					$discussion_exists = true;
				}
			}
		}
		$this->assertFalse( $discussion_exists );
	}

	// -------------------------------------------------------------------------
	// filter_admin_menu registered via init_wploaded_filters when remove_everywhere
	// -------------------------------------------------------------------------

	public function test_filter_admin_menu_registered_when_remove_everywhere() {
		$this->set_options( array( 'remove_everywhere' => true ) );
		// Simulate admin context.
		set_current_screen( 'dashboard' );

		$this->plugin->init_wploaded_filters();

		$this->assertEquals( 9999, has_action( 'admin_menu', array( $this->plugin, 'filter_admin_menu' ) ) );
		set_current_screen( 'front' );
	}

	public function test_filter_admin_menu_not_registered_when_not_remove_everywhere() {
		$this->set_options( array( 'remove_everywhere' => false ) );
		remove_action( 'admin_menu', array( $this->plugin, 'filter_admin_menu' ) );
		set_current_screen( 'dashboard' );

		$this->plugin->init_wploaded_filters();

		$this->assertFalse( has_action( 'admin_menu', array( $this->plugin, 'filter_admin_menu' ) ) );
		set_current_screen( 'front' );
	}

	// -------------------------------------------------------------------------
	// filter_dashboard — removes recent comments dashboard widget
	// -------------------------------------------------------------------------

	public function test_filter_dashboard_removes_recent_comments_widget() {
		global $wp_meta_boxes;
		$wp_meta_boxes['dashboard']['normal']['core']['dashboard_recent_comments'] = array(
			'id'    => 'dashboard_recent_comments',
			'title' => 'Recent Comments',
		);

		$this->plugin->filter_dashboard();

		// remove_meta_box() sets the entry to false (does not unset it).
		$this->assertFalse(
			$wp_meta_boxes['dashboard']['normal']['core']['dashboard_recent_comments']
		);
	}

	// -------------------------------------------------------------------------
	// admin_css hooks — registered when remove_everywhere
	// -------------------------------------------------------------------------

	public function test_admin_css_hooks_registered_when_remove_everywhere() {
		$this->set_options( array( 'remove_everywhere' => true ) );
		set_current_screen( 'dashboard' );

		$this->plugin->init_wploaded_filters();

		$this->assertNotFalse( has_action( 'admin_print_styles-index.php', array( $this->plugin, 'admin_css' ) ) );
		$this->assertNotFalse( has_action( 'admin_print_styles-profile.php', array( $this->plugin, 'admin_css' ) ) );
		set_current_screen( 'front' );
	}

	// -------------------------------------------------------------------------
	// filter_admin_bar — hooks registered when remove_everywhere
	// -------------------------------------------------------------------------

	public function test_filter_admin_bar_registered_in_init_filters_when_remove_everywhere() {
		$this->set_options( array( 'remove_everywhere' => true ) );

		$this->plugin->init_filters();

		$this->assertNotFalse( has_action( 'admin_init', array( $this->plugin, 'filter_admin_bar' ) ) );
	}

	// -------------------------------------------------------------------------
	// plugin_actions_links — settings/tools links for single site
	// -------------------------------------------------------------------------

	public function test_plugin_actions_links_adds_settings_link() {
		$this->set_options( array(
			'remove_everywhere'  => false,
			'sitewide_settings'  => false,
		) );

		$links = $this->plugin->plugin_actions_links(
			array( '<a href="deactivate">Deactivate</a>' ),
			plugin_basename( DC_PLUGIN_ROOT_PATH . '/disable-comments.php' )
		);

		$settings_found = false;
		foreach ( $links as $link ) {
			if ( strpos( $link, 'Settings' ) !== false ) {
				$settings_found = true;
				break;
			}
		}
		$this->assertTrue( $settings_found );
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
