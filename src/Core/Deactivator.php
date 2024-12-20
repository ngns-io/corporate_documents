<?php
declare(strict_types=1);

namespace CorporateDocuments\Core;

/**
 * Fired during plugin deactivation.
 *
 * @link       https://ngns.io/wordpress/plugins/corporate_documents/
 * @since      1.0.0
 *
 * @package    CorporateDocuments
 */

/**
 * This class defines all code necessary to run during the plugin's deactivation.
 */
class Deactivator {

	/**
	 * List of capabilities to remove
	 *
	 * @var array<string>
	 */
	private static array $capabilities = array(
		'cdox_manage',
		'cdox_view_documents',
		'cdox_upload_documents',
	);

	/**
	 * Plugin deactivation hook callback.
	 *
	 * @since    1.0.0
	 * @return void
	 */
	public static function deactivate(): void {
		self::remove_capabilities();
		self::clear_scheduled_tasks();

		// Flush rewrite rules after removing custom post types and taxonomies
		flush_rewrite_rules();
	}

	/**
	 * Remove plugin-specific capabilities from roles
	 *
	 * @return void
	 */
	private static function remove_capabilities(): void {
		$role = get_role( 'administrator' );

		if ( ! $role ) {
			return;
		}

		foreach ( self::$capabilities as $capability ) {
			$role->remove_cap( $capability );
		}
	}

	/**
	 * Clear any scheduled tasks created by the plugin
	 *
	 * @return void
	 */
	private static function clear_scheduled_tasks(): void {
		$hooks = array(
			'cdox_cleanup_temp_files',
			'cdox_refresh_document_cache',
		);

		foreach ( $hooks as $hook ) {
			$timestamp = wp_next_scheduled( $hook );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, $hook );
			}
		}
	}
}
