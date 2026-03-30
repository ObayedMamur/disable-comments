<?php
/**
 * Integration tests for Site Health integration.
 *
 * Tests: add_site_health_info (panel structure, field presence, accurate values)
 *
 * @package Disable_Comments
 */

class SiteHealthTest extends WP_UnitTestCase {

	/** @var Disable_Comments */
	private $plugin;

	public function set_up() {
		parent::set_up();
		$this->plugin = Disable_Comments::get_instance();
	}

	// -------------------------------------------------------------------------
	// add_site_health_info — panel registration
	// -------------------------------------------------------------------------

	public function test_site_health_adds_disable_comments_section() {
		$this->set_options( array( 'remove_everywhere' => true ) );

		$result = $this->plugin->add_site_health_info( array() );

		$this->assertArrayHasKey( 'disable-comments', $result );
	}

	public function test_site_health_section_has_label() {
		$result = $this->plugin->add_site_health_info( array() );

		$this->assertArrayHasKey( 'label', $result['disable-comments'] );
		$this->assertNotEmpty( $result['disable-comments']['label'] );
	}

	public function test_site_health_section_has_fields() {
		$result = $this->plugin->add_site_health_info( array() );

		$this->assertArrayHasKey( 'fields', $result['disable-comments'] );
		$this->assertNotEmpty( $result['disable-comments']['fields'] );
	}

	// -------------------------------------------------------------------------
	// Field accuracy
	// -------------------------------------------------------------------------

	public function test_site_health_reflects_remove_everywhere_true() {
		$this->set_options( array( 'remove_everywhere' => true ) );

		$result = $this->plugin->add_site_health_info( array() );
		$fields = $result['disable-comments']['fields'];

		$this->assertArrayHasKey( 'status', $fields );
		$this->assertStringContainsStringIgnoringCase( 'all', (string) $fields['status']['value'] );
	}

	public function test_site_health_reflects_remove_everywhere_false() {
		$this->set_options( array(
			'remove_everywhere'   => false,
			'disabled_post_types' => array(),
		) );

		$result = $this->plugin->add_site_health_info( array() );
		$fields = $result['disable-comments']['fields'];

		$this->assertArrayHasKey( 'status', $fields );
		$this->assertStringContainsStringIgnoringCase( 'none', (string) $fields['status']['value'] );
	}

	public function test_site_health_includes_xmlrpc_status_field() {
		$this->set_options( array( 'remove_xmlrpc_comments' => 1 ) );

		$result = $this->plugin->add_site_health_info( array() );
		$fields = $result['disable-comments']['fields'];

		$this->assertArrayHasKey( 'xmlrpc', $fields );
	}

	public function test_site_health_includes_rest_api_status_field() {
		$this->set_options( array( 'remove_rest_API_comments' => 1 ) );

		$result = $this->plugin->add_site_health_info( array() );
		$fields = $result['disable-comments']['fields'];

		$this->assertArrayHasKey( 'rest_api', $fields );
	}

	// -------------------------------------------------------------------------
	// Preserves existing debug info sections
	// -------------------------------------------------------------------------

	public function test_site_health_preserves_existing_debug_sections() {
		$existing = array(
			'wordpress' => array(
				'label'  => 'WordPress',
				'fields' => array( 'version' => array( 'label' => 'Version', 'value' => '6.0' ) ),
			),
		);

		$result = $this->plugin->add_site_health_info( $existing );

		$this->assertArrayHasKey( 'wordpress', $result );
		$this->assertArrayHasKey( 'disable-comments', $result );
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
