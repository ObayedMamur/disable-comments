<?php
/**
 * Unit tests for DB migration logic.
 *
 * Tests: check_db_upgrades — all version paths (v0→2, v2→5, v5→7, v7→8),
 *        check_upgrades (version option management)
 *
 * @package Disable_Comments
 */

class SettingsMigrationTest extends WP_UnitTestCase {

	public function tear_down() {
		delete_option( 'disable_comments_options' );
		delete_option( 'disable_comments_post_types' );
		delete_option( 'disable_comment_version' );
		$this->reset_singleton();
		parent::tear_down();
	}

	// -------------------------------------------------------------------------
	// v0 → v2: legacy option migration
	// -------------------------------------------------------------------------

	public function test_v0_to_v2_migrates_post_types_option() {
		update_option( 'disable_comments_post_types', array( 'post', 'page' ) );
		update_option( 'disable_comments_options', array( 'db_version' => 0 ) );
		$this->reset_singleton();
		$plugin = Disable_Comments::get_instance();

		$options = get_option( 'disable_comments_options' );

		$this->assertContains( 'post', $options['disabled_post_types'] );
		$this->assertContains( 'page', $options['disabled_post_types'] );
		$this->assertFalse( get_option( 'disable_comments_post_types' ) );
	}

	// -------------------------------------------------------------------------
	// v2 → v5: consolidate multi-setting flags into remove_everywhere
	// -------------------------------------------------------------------------

	public function test_v2_to_v5_sets_remove_everywhere_from_remove_admin_menu() {
		update_option( 'disable_comments_options', array(
			'db_version'                 => 2,
			'remove_admin_menu_comments' => true,
			'disabled_post_types'        => array(),
		) );
		$this->reset_singleton();
		Disable_Comments::get_instance();

		$options = get_option( 'disable_comments_options' );

		$this->assertTrue( $options['remove_everywhere'] );
		$this->assertArrayNotHasKey( 'remove_admin_menu_comments', $options );
		$this->assertArrayNotHasKey( 'remove_admin_bar_comments', $options );
		$this->assertArrayNotHasKey( 'remove_recent_comments', $options );
		$this->assertArrayNotHasKey( 'remove_discussion', $options );
		$this->assertArrayNotHasKey( 'remove_rc_widget', $options );
	}

	public function test_v2_to_v5_remove_everywhere_false_when_flag_absent() {
		update_option( 'disable_comments_options', array(
			'db_version'          => 2,
			'disabled_post_types' => array( 'post' ),
		) );
		$this->reset_singleton();
		Disable_Comments::get_instance();

		$options = get_option( 'disable_comments_options' );

		$this->assertFalse( $options['remove_everywhere'] );
	}

	// -------------------------------------------------------------------------
	// v7 → v8: add show_existing_comments with default false
	// -------------------------------------------------------------------------

	public function test_v7_to_v8_adds_show_existing_comments_false() {
		update_option( 'disable_comments_options', array(
			'db_version'          => 7,
			'remove_everywhere'   => false,
			'disabled_post_types' => array(),
		) );
		$this->reset_singleton();
		Disable_Comments::get_instance();

		$options = get_option( 'disable_comments_options' );

		$this->assertArrayHasKey( 'show_existing_comments', $options );
		$this->assertFalse( $options['show_existing_comments'] );
	}

	// -------------------------------------------------------------------------
	// Already up-to-date — no migration runs
	// -------------------------------------------------------------------------

	public function test_no_migration_when_db_version_current() {
		$initial = array(
			'db_version'          => Disable_Comments::DB_VERSION,
			'remove_everywhere'   => true,
			'disabled_post_types' => array( 'post' ),
		);
		update_option( 'disable_comments_options', $initial );
		$this->reset_singleton();
		Disable_Comments::get_instance();

		$options = get_option( 'disable_comments_options' );

		$this->assertTrue( $options['remove_everywhere'] );
		$this->assertContains( 'post', $options['disabled_post_types'] );
	}

	// -------------------------------------------------------------------------
	// check_upgrades — version option
	// -------------------------------------------------------------------------

	public function test_check_upgrades_writes_current_version() {
		delete_option( 'disable_comment_version' );
		$this->reset_singleton();
		$plugin = Disable_Comments::get_instance();

		$plugin->check_upgrades();

		$this->assertEquals( DC_VERSION, get_option( 'disable_comment_version' ) );
	}

	public function test_check_upgrades_updates_stale_version() {
		update_option( 'disable_comment_version', '1.0.0' );
		$this->reset_singleton();
		$plugin = Disable_Comments::get_instance();

		$plugin->check_upgrades();

		$this->assertEquals( DC_VERSION, get_option( 'disable_comment_version' ) );
	}

	// -------------------------------------------------------------------------
	// Final option shape after full migration
	// -------------------------------------------------------------------------

	public function test_required_option_keys_present_after_migration() {
		update_option( 'disable_comments_options', array( 'db_version' => 0 ) );
		$this->reset_singleton();
		Disable_Comments::get_instance();

		$options = get_option( 'disable_comments_options' );

		foreach ( array( 'remove_everywhere', 'extra_post_types', 'show_existing_comments', 'db_version' ) as $key ) {
			$this->assertArrayHasKey( $key, $options, "Missing key after migration: $key" );
		}
		$this->assertEquals( Disable_Comments::DB_VERSION, $options['db_version'] );
	}

	// -------------------------------------------------------------------------
	// Helper
	// -------------------------------------------------------------------------

	private function reset_singleton() {
		$reflection = new ReflectionProperty( Disable_Comments::class, 'instance' );
		$reflection->setAccessible( true );
		$reflection->setValue( null, null );
	}
}
