<?php
/**
 * Shared helper: reset plugin singleton and update options.
 *
 * Used by Unit, Integration, and CLI test classes to avoid repeating
 * the reflection-based singleton reset in every file.
 *
 * @package Disable_Comments
 */

trait PluginOptionsTrait {

	/**
	 * Update plugin options and reset the singleton so the next
	 * Disable_Comments::get_instance() reads the new values.
	 *
	 * @param array $overrides Keys to override in the default option set.
	 */
	protected function set_options( array $overrides = array() ): void {
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
		update_option( 'disable_comments_options', array_merge( $defaults, $overrides ) );
		$this->reset_singleton();
	}

	/**
	 * Reset the Disable_Comments singleton so get_instance() creates
	 * a fresh object reading current options from the DB.
	 */
	protected function reset_singleton(): void {
		$ref = new ReflectionProperty( Disable_Comments::class, 'instance' );
		$ref->setAccessible( true );
		$ref->setValue( null, null );
		$this->plugin = Disable_Comments::get_instance();
	}
}
