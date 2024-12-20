<?php

declare(strict_types=1);

namespace CorporateDocuments\Admin;

use CorporateDocuments\Document\DocumentRepository;

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://evenhouseconsulting.com
 * @since      1.0.0
 *
 * @package    CorporateDocuments
 * @subpackage CorporateDocuments/admin
 */
class Admin {
	/**
	 * The ID of this plugin.
	 *
	 * @var string
	 */
	private string $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @var string
	 */
	private string $version;

	/**
	 * Document repository instance.
	 *
	 * @var DocumentRepository
	 */
	private DocumentRepository $document_repository;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version     The version of this plugin.
	 */
	public function __construct( string $plugin_name, string $version ) {
		$this->plugin_name         = $plugin_name;
		$this->version             = $version;
		$this->document_repository = new DocumentRepository();
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @return void
	 */
	public function enqueue_styles(): void {
		$screen = get_current_screen();

		if ( ! $screen || ! $this->is_plugin_page( $screen->id ) ) {
			return;
		}

		wp_enqueue_style(
			$this->plugin_name . '-admin',
			plugin_dir_url( __FILE__ ) . 'css/corporate-documents-admin.css',
			array(),
			$this->version,
			'all'
		);

		wp_enqueue_style(
			$this->plugin_name . '-fa',
			plugin_dir_url( __FILE__ ) . 'css/fa/all.min.css',
			array(),
			$this->version,
			'all'
		);
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @return void
	 */
	public function enqueue_scripts(): void {
		$screen = get_current_screen();

		if ( ! $screen || ! $this->is_plugin_page( $screen->id ) ) {
			return;
		}

		wp_enqueue_script(
			$this->plugin_name . '-admin',
			plugin_dir_url( __FILE__ ) . 'js/corporate-documents-admin.js',
			array( 'jquery' ),
			$this->version,
			true
		);

		wp_localize_script(
			$this->plugin_name . '-admin',
			'cdoxAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'cdox_admin_nonce' ),
				'i18n'    => array(
					'confirmDelete'     => __( 'Are you sure you want to delete this document?', 'cdox' ),
					'confirmBulkDelete' => __( 'Are you sure you want to delete these documents?', 'cdox' ),
					'deleteSuccess'     => __( 'Document(s) deleted successfully.', 'cdox' ),
					'deleteError'       => __( 'Error deleting document(s).', 'cdox' ),
				),
			)
		);
	}

	/**
	 * Register the custom post type for documents.
	 *
	 * @return void
	 */
	public function register_post_types(): void {
		register_post_type(
			'corporate_document',
			array(
				'labels'             => array(
					'name'               => __( 'Corporate Documents', 'cdox' ),
					'singular_name'      => __( 'Corporate Document', 'cdox' ),
					'add_new'            => __( 'Add New Document', 'cdox' ),
					'add_new_item'       => __( 'Add New Corporate Document', 'cdox' ),
					'edit_item'          => __( 'Edit Corporate Document', 'cdox' ),
					'new_item'           => __( 'New Corporate Document', 'cdox' ),
					'view_item'          => __( 'View Corporate Document', 'cdox' ),
					'search_items'       => __( 'Search Corporate Documents', 'cdox' ),
					'not_found'          => __( 'No corporate documents found', 'cdox' ),
					'not_found_in_trash' => __( 'No corporate documents found in trash', 'cdox' ),
				),
				'public'             => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'query_var'          => true,
				'rewrite'            => array( 'slug' => 'corporate-document' ),
				'capability_type'    => 'post',
				'has_archive'        => true,
				'hierarchical'       => false,
				'menu_position'      => 20,
				'menu_icon'          => 'dashicons-media-document',
				'supports'           => array( 'title', 'editor', 'thumbnail' ),
				'show_in_rest'       => true,
				'map_meta_cap'       => true,
			)
		);
	}

	/**
	 * Register custom taxonomies.
	 *
	 * @return void
	 */
	public function register_taxonomies(): void {
		register_taxonomy(
			'document_type',
			array( 'corporate_document' ),
			array(
				'labels'            => array(
					'name'              => __( 'Document Types', 'cdox' ),
					'singular_name'     => __( 'Document Type', 'cdox' ),
					'search_items'      => __( 'Search Document Types', 'cdox' ),
					'all_items'         => __( 'All Document Types', 'cdox' ),
					'parent_item'       => __( 'Parent Document Type', 'cdox' ),
					'parent_item_colon' => __( 'Parent Document Type:', 'cdox' ),
					'edit_item'         => __( 'Edit Document Type', 'cdox' ),
					'update_item'       => __( 'Update Document Type', 'cdox' ),
					'add_new_item'      => __( 'Add New Document Type', 'cdox' ),
					'new_item_name'     => __( 'New Document Type Name', 'cdox' ),
					'menu_name'         => __( 'Document Types', 'cdox' ),
				),
				'hierarchical'      => true,
				'show_ui'           => true,
				'show_admin_column' => true,
				'query_var'         => true,
				'rewrite'           => array( 'slug' => 'document-type' ),
				'show_in_rest'      => true,
			)
		);
	}

	/**
	 * Add custom columns to the document list.
	 *
	 * @param array<string, string> $columns Default columns.
	 * @return array<string, string>
	 */
	public function add_custom_columns( array $columns ): array {
		$date = $columns['date'];
		unset( $columns['date'] );

		$columns['document_type'] = __( 'Document Type', 'cdox' );
		$columns['file_size']     = __( 'File Size', 'cdox' );
		$columns['downloads']     = __( 'Downloads', 'cdox' );
		$columns['date']          = $date;

		return $columns;
	}

	/**
	 * Fill custom column data.
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public function fill_custom_columns( string $column, int $post_id ): void {
		switch ( $column ) {
			case 'document_type':
				$terms = get_the_terms( $post_id, 'document_type' );
				if ( $terms && ! is_wp_error( $terms ) ) {
					$types = array_map(
						function ( $term ) {
							return esc_html( $term->name );
						},
						$terms
					);
					echo implode( ', ', $types );
				}
				break;

			case 'file_size':
				$attachment_id = get_post_meta( $post_id, '_document_file_id', true );
				if ( $attachment_id ) {
					$file_path = get_attached_file( $attachment_id );
					if ( file_exists( $file_path ) ) {
						echo size_format( filesize( $file_path ) );
					}
				}
				break;

			case 'downloads':
				$downloads = get_post_meta( $post_id, '_document_downloads', true );
				echo intval( $downloads );
				break;
		}
	}

	/**
	 * Add custom meta boxes.
	 *
	 * @return void
	 */
	public function add_meta_boxes(): void {
		add_meta_box(
			'document_details',
			__( 'Document Details', 'cdox' ),
			array( $this, 'render_document_meta_box' ),
			'corporate_document',
			'normal',
			'high'
		);
	}

	/**
	 * Render document meta box.
	 *
	 * @param \WP_Post $post Post object.
	 * @return void
	 */
	public function render_document_meta_box( \WP_Post $post ): void {
		wp_nonce_field( 'cdox_document_meta_box', 'cdox_document_meta_box_nonce' );

		$file_id     = get_post_meta( $post->ID, '_document_file_id', true );
		$expiry_date = get_post_meta( $post->ID, '_document_expiry_date', true );

		require_once plugin_dir_path( __FILE__ ) . 'views/document-meta-box.php';
	}

	/**
	 * Save document meta box data.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function save_meta_box_data( int $post_id ): void {
		if ( ! $this->can_save_meta_box_data( $post_id ) ) {
			return;
		}

		$this->handle_document_file_upload( $post_id );
		$this->save_document_expiry_date( $post_id );
	}

	/**
	 * Check if we can save meta box data.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	private function can_save_meta_box_data( int $post_id ): bool {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return false;
		}

		if ( ! isset( $_POST['cdox_document_meta_box_nonce'] ) ) {
			return false;
		}

		if ( ! wp_verify_nonce(
			sanitize_key( $_POST['cdox_document_meta_box_nonce'] ),
			'cdox_document_meta_box'
		) ) {
			return false;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if current page is a plugin page.
	 *
	 * @param string $screen_id Current screen ID.
	 * @return bool
	 */
	private function is_plugin_page( string $screen_id ): bool {
		$plugin_pages = array(
			'corporate_document',
			'edit-corporate_document',
			'edit-document_type',
		);

		return in_array( $screen_id, $plugin_pages, true );
	}

	/**
	 * Handle document file upload.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	private function handle_document_file_upload( int $post_id ): void {
		if ( ! isset( $_FILES['document_file'] ) ) {
			return;
		}

		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$uploaded_file    = $_FILES['document_file'];
		$upload_overrides = array( 'test_form' => false );

		$movefile = wp_handle_upload( $uploaded_file, $upload_overrides );

		if ( $movefile && ! isset( $movefile['error'] ) ) {
			$file_type = wp_check_filetype( basename( $movefile['file'] ), null );

			$attachment = array(
				'post_mime_type' => $file_type['type'],
				'post_title'     => sanitize_file_name( basename( $movefile['file'] ) ),
				'post_content'   => '',
				'post_status'    => 'inherit',
			);

			$attach_id = wp_insert_attachment( $attachment, $movefile['file'], $post_id );

			if ( ! is_wp_error( $attach_id ) ) {
				require_once ABSPATH . 'wp-admin/includes/image.php';
				$attach_data = wp_generate_attachment_metadata( $attach_id, $movefile['file'] );
				wp_update_attachment_metadata( $attach_id, $attach_data );
				update_post_meta( $post_id, '_document_file_id', $attach_id );
			}
		}
	}

	/**
	 * Save document expiry date.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	private function save_document_expiry_date( int $post_id ): void {
		if ( isset( $_POST['document_expiry_date'] ) ) {
			$expiry_date = sanitize_text_field( $_POST['document_expiry_date'] );
			update_post_meta( $post_id, '_document_expiry_date', $expiry_date );
		}
	}
}
