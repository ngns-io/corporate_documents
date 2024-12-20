<?php
declare(strict_types=1);

namespace CorporateDocuments\Core;

/**
 * Fired during plugin activation.
 *
 * @link       https://ngns.io/wordpress/plugins/corporate_documents/
 * @since      1.0.0
 *
 * @package    CorporateDocuments
 */

/**
 * This class defines all code necessary to run during the plugin's activation.
 */
class Activator {

	/**
	 * List of required capabilities for the plugin
	 *
	 * @var array<string>
	 */
	private static array $capabilities = array(
		'cdox_manage',
		'cdox_view_documents',
		'cdox_upload_documents',
	);

	/**
	 * Default document types to be created
	 *
	 * @var array<string, array<string, string>>
	 */
	private static array $default_document_types = array(
		'annual-report'    => array(
			'name' => 'Annual Report',
			'slug' => 'annual-report',
		),
		'quarterly-report' => array(
			'name' => 'Quarterly Report',
			'slug' => 'quarterly-report',
		),
		'press-release'    => array(
			'name' => 'Press Release',
			'slug' => 'press-release',
		),
	);

	/**
	 * Runs activation tasks for the plugin
	 *
	 * @since    1.0.0
	 * @return void
	 */
	public static function activate(): void {
		self::add_capabilities();
		self::register_taxonomies();
		self::create_document_types();
		self::create_upload_directory();

		// Flush rewrite rules after creating custom post types and taxonomies.
		flush_rewrite_rules();
	}

	/**
	 * Add required capabilities to administrator role
	 *
	 * @return void
	 */
	private static function add_capabilities(): void {
		$role = get_role( 'administrator' );

		if ( ! $role ) {
			return;
		}

		foreach ( self::$capabilities as $capability ) {
			$role->add_cap( $capability );
		}
	}

	/**
	 * Register required taxonomies
	 *
	 * @return void
	 */
	private static function register_taxonomies(): void {
		if ( ! taxonomy_exists( 'document_type' ) ) {
			register_taxonomy(
				'document_type',
				'corporate_document',
				array(
					'label'             => __( 'Document Types', 'cdox' ),
					'hierarchical'      => true,
					'show_ui'           => true,
					'show_admin_column' => true,
					'query_var'         => true,
					'rewrite'           => array( 'slug' => 'document-type' ),
				)
			);
		}
	}

	/**
	 * Create default document types
	 *
	 * @return void
	 */
	private static function create_document_types(): void {
		foreach ( self::$default_document_types as $type ) {
			if ( ! term_exists( $type['slug'], 'document_type' ) ) {
				wp_insert_term(
					$type['name'],
					'document_type',
					array(
						'slug' => $type['slug'],
					)
				);
			}
		}
	}

	/**
	 * Create secure upload directory for documents
	 *
	 * @return void
	 * @throws \RuntimeException If directory creation fails
	 */
	private static function create_upload_directory(): void {
		$upload_dir    = wp_upload_dir();
		$documents_dir = $upload_dir['basedir'] . '/corporate-documents';

		if ( ! file_exists( $documents_dir ) ) {
			if ( ! wp_mkdir_p( $documents_dir ) ) {
				throw new \RuntimeException(
					sprintf(
						'Failed to create upload directory: %s',
						$documents_dir
					)
				);
			}

			// Create .htaccess to prevent direct access.
			$htaccess_content = "Order Deny,Allow\nDeny from all\n";
			$htaccess_file    = $documents_dir . '/.htaccess';

			if ( ! file_put_contents( $htaccess_file, $htaccess_content ) ) {
				throw new \RuntimeException(
					sprintf(
						'Failed to create .htaccess file in: %s',
						$documents_dir
					)
				);
			}
		}
	}
}
