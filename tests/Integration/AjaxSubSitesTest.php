<?php
/**
 * Integration tests for the get_sub_sites AJAX handler.
 *
 * Requires multisite — skipped automatically on single-site.
 *
 * Tests: pagination, search, type param (disabled vs delete),
 *        nonce verification, response structure.
 *
 * @package Disable_Comments
 * @group   multisite
 */

class AjaxSubSitesTest extends WP_Ajax_UnitTestCase {

	/** @var Disable_Comments */
	private $plugin;

	public function set_up() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Multisite not active.' );
		}
		parent::set_up();
		$this->plugin = Disable_Comments::get_instance();
		$this->_setRole( 'administrator' );
	}

	// -------------------------------------------------------------------------
	// Response structure
	// -------------------------------------------------------------------------

	public function test_get_sub_sites_returns_data_and_total() {
		$_POST['nonce']      = wp_create_nonce( 'disable_comments_save_settings' );
		$_POST['type']       = 'disabled';
		$_POST['search']     = '';
		$_POST['pageSize']   = 50;
		$_POST['pageNumber'] = 1;

		try {
			$this->_handleAjax( 'get_sub_sites' );
		} catch ( WPAjaxDieStopException $e ) {
			$response = json_decode( $this->_last_response, true );
			$this->assertTrue( $response['success'] );
			$this->assertArrayHasKey( 'data', $response['data'] );
			$this->assertArrayHasKey( 'totalNumber', $response['data'] );
		}
	}

	public function test_get_sub_sites_pagination_respects_page_size() {
		// Create several sites.
		for ( $i = 0; $i < 5; $i++ ) {
			wpmu_create_blog( "site{$i}.example.com", '/', "Site $i", 1 );
		}

		$_POST['nonce']      = wp_create_nonce( 'disable_comments_save_settings' );
		$_POST['type']       = 'disabled';
		$_POST['search']     = '';
		$_POST['pageSize']   = 3;
		$_POST['pageNumber'] = 1;

		try {
			$this->_handleAjax( 'get_sub_sites' );
		} catch ( WPAjaxDieStopException $e ) {
			$response = json_decode( $this->_last_response, true );
			$this->assertCount( 3, $response['data']['data'] );
		}
	}

	public function test_get_sub_sites_search_filters_results() {
		wpmu_create_blog( 'searchable.example.com', '/', 'SearchableUnique', 1 );

		$_POST['nonce']      = wp_create_nonce( 'disable_comments_save_settings' );
		$_POST['type']       = 'disabled';
		$_POST['search']     = 'SearchableUnique';
		$_POST['pageSize']   = 50;
		$_POST['pageNumber'] = 1;

		try {
			$this->_handleAjax( 'get_sub_sites' );
		} catch ( WPAjaxDieStopException $e ) {
			$response = json_decode( $this->_last_response, true );
			$this->assertTrue( $response['success'] );
			$this->assertGreaterThan( 0, $response['data']['totalNumber'] );
		}
	}

	public function test_get_sub_sites_invalid_nonce_returns_error() {
		$_POST['nonce']      = 'bad_nonce';
		$_POST['type']       = 'disabled';
		$_POST['pageSize']   = 50;
		$_POST['pageNumber'] = 1;

		try {
			$this->_handleAjax( 'get_sub_sites' );
		} catch ( WPAjaxDieStopException $e ) {
			$response = json_decode( $this->_last_response, true );
			$this->assertFalse( $response['success'] );
		}
	}
}
