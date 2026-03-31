<?php
/**
 * Integration tests for network comment deletion.
 *
 * Requires multisite — skipped on single-site.
 *
 * Tests: delete across selected sites using switch_to_blog,
 *        per-site deletion isolation, site-switching context restore.
 *
 * @package Disable_Comments
 * @group   multisite
 */

class NetworkDeletionTest extends WP_UnitTestCase {

	/** @var Disable_Comments */
	private $plugin;

	/** @var int[] */
	private $site_ids = array();

	public function set_up() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite not active.' );
		}
		parent::set_up();
		$this->plugin         = Disable_Comments::get_instance();
		$this->plugin->is_CLI = true; // Direct method calls — bypass nonce + JSON output.
		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );
		grant_super_admin( $user_id );

		// Create two test sub-sites using the factory (handles unique path generation).
		$this->site_ids[] = $this->factory->blog->create( array( 'title' => 'Test Site 1' ) );
		$this->site_ids[] = $this->factory->blog->create( array( 'title' => 'Test Site 2' ) );
	}

	public function tear_down() {
		$this->plugin->is_CLI = false;
		foreach ( $this->site_ids as $site_id ) {
			wpmu_delete_blog( $site_id, true );
		}
		wp_set_current_user( 0 );
		parent::tear_down();
	}

	// -------------------------------------------------------------------------
	// Per-site deletion — only deletes from selected sites
	// -------------------------------------------------------------------------

	public function test_delete_everywhere_on_selected_site_only() {
		$site1 = $this->site_ids[0];
		$site2 = $this->site_ids[1];

		// Create comments on both sites.
		switch_to_blog( $site1 );
		$post1 = $this->factory->post->create();
		$this->factory->comment->create( array( 'comment_post_ID' => $post1 ) );
		restore_current_blog();

		switch_to_blog( $site2 );
		$post2 = $this->factory->post->create();
		$this->factory->comment->create( array( 'comment_post_ID' => $post2 ) );
		restore_current_blog();

		// Delete only from site1.
		$this->plugin->delete_comments_settings( array(
			'delete_mode'    => 'delete_everywhere',
			'is_network_admin' => '1',
			'disabled_sites' => array( "site_{$site1}" => true, "site_{$site2}" => false ),
		) );

		switch_to_blog( $site1 );
		$site1_count = get_comments( array( 'post_id' => $post1, 'count' => true ) );
		restore_current_blog();

		switch_to_blog( $site2 );
		$site2_count = get_comments( array( 'post_id' => $post2, 'count' => true ) );
		restore_current_blog();

		$this->assertEquals( 0, $site1_count );
		$this->assertEquals( 1, $site2_count );
	}

	// -------------------------------------------------------------------------
	// Current blog is restored after network deletion loop
	// -------------------------------------------------------------------------

	public function test_current_blog_restored_after_network_deletion() {
		$original_blog = get_current_blog_id();

		$this->plugin->delete_comments_settings( array(
			'delete_mode'    => 'delete_everywhere',
			'is_network_admin' => '1',
			'disabled_sites' => array( "site_{$this->site_ids[0]}" => true ),
		) );

		$this->assertEquals( $original_blog, get_current_blog_id() );
	}
}
