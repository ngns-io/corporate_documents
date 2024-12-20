<?php
declare(strict_types=1);

namespace CorporateDocuments\Frontend;

use CorporateDocuments\Document\DocumentRepository;
use CorporateDocuments\Shortcode\{
	DocumentListShortcode,
	DocumentTypesShortcode,
	YearsListShortcode,
	FilteredDocumentsShortcode
};

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://evenhouseconsulting.com
 * @since      1.0.0
 *
 * @package    CorporateDocuments
 * @subpackage CorporateDocuments/frontend
 */
class Frontend {

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
	 * Registered shortcode handlers.
	 *
	 * @var array<string, object>
	 */
	private array $shortcode_handlers = array();

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
		$this->init_shortcode_handlers();
	}

	/**
	 * Initialize shortcode handlers.
	 *
	 * @return void
	 */
	private function init_shortcode_handlers(): void {
		$this->shortcode_handlers = array(
			'cdox_list_documents'          => new DocumentListShortcode( $this->document_repository ),
			'cdox_list_documenttypes'      => new DocumentTypesShortcode(),
			'cdox_list_years'              => new YearsListShortcode( $this->document_repository ),
			'cdox_list_filtered_documents' => new FilteredDocumentsShortcode( $this->document_repository ),
		);
	}

	/**
	 * Register the stylesheets for the public-facing side of the plugin.
	 *
	 * @return void
	 */
	public function enqueue_styles(): void {
		// Only load assets when needed.
		if ( ! $this->should_load_assets() ) {
			return;
		}

		wp_enqueue_style(
			$this->plugin_name,
			plugin_dir_url( __FILE__ ) . 'css/corporate-documents-public.css',
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
	 * Register the JavaScript for the public-facing side of the plugin.
	 *
	 * @return void
	 */
	public function enqueue_scripts(): void {
		// Only load assets when needed.
		if ( ! $this->should_load_assets() ) {
			return;
		}

		wp_enqueue_script(
			$this->plugin_name,
			plugin_dir_url( __FILE__ ) . 'js/corporate-documents-public.js',
			array( 'jquery' ),
			$this->version,
			true
		);

		wp_localize_script(
			$this->plugin_name,
			'cdoxAjax',
			array(
				'url'   => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'cdox_frontend_nonce' ),
				'i18n'  => array(
					'loading'   => __( 'Loading...', 'cdox' ),
					'error'     => __( 'Error loading documents.', 'cdox' ),
					'noResults' => __( 'No documents found.', 'cdox' ),
				),
			)
		);
	}

	/**
	 * Register all shortcodes.
	 *
	 * @return void
	 */
	public function register_shortcodes(): void {
		foreach ( $this->shortcode_handlers as $tag => $handler ) {
			add_shortcode( $tag, array( $handler, 'render' ) );
		}
	}

	/**
	 * Handle AJAX filter request.
	 *
	 * @return void
	 */
	public function handle_filter(): void {
		try {
			$this->verify_ajax_request();
			$filtered_content = $this->process_filter_request();
			wp_send_json_success( array( 'content' => $filtered_content ) );
		} catch ( \Exception $e ) {
			wp_send_json_error(
				array(
					'message' => $e->getMessage(),
				)
			);
		}
	}

	/**
	 * Track document download.
	 *
	 * @return void
	 */
	public function track_download(): void {
		try {
			$this->verify_ajax_request();

			$document_id = filter_input( INPUT_POST, 'document_id', FILTER_VALIDATE_INT );
			if ( ! $document_id ) {
				throw new \InvalidArgumentException( __( 'Invalid document ID', 'cdox' ) );
			}

			$this->document_repository->increment_download_count( $document_id );
			wp_send_json_success();
		} catch ( \Exception $e ) {
			wp_send_json_error(
				array(
					'message' => $e->getMessage(),
				)
			);
		}
	}

	/**
	 * Process document filter request.
	 *
	 * @return string
	 * @throws \InvalidArgumentException If required parameters are missing or invalid.
	 */
	private function process_filter_request(): string {
		// Sanitize and validate input parameters.
		$doc_types = $this->get_filtered_doc_types();
		$year      = $this->get_filtered_year();
		$order     = $this->get_filtered_order();
		$show_date = $this->get_show_date_option();

		// Get filtered documents.
		$documents = $this->document_repository->get_filtered_documents(
			$doc_types,
			$year,
			$order
		);

		// Render filtered documents list.
		ob_start();
		require plugin_dir_path( __FILE__ ) . 'views/document-list.php';
		return ob_get_clean();
	}

	/**
	 * Get filtered document types from request.
	 *
	 * @return array<string>
	 */
	private function get_filtered_doc_types(): array {
		$doc_types = sanitize_text_field( $_POST['cdoxfilterdoctypes'] ?? '' );

		if ( $doc_types === 'cdox-all-doctypes' ) {
			$all_types = sanitize_text_field( $_POST['cdoxfilter-all-doctypes'] ?? '' );
			return array_filter( explode( ',', $all_types ) );
		}

		return array( $doc_types );
	}

	/**
	 * Get filtered year from request.
	 *
	 * @return int|null
	 */
	private function get_filtered_year(): ?int {
		$year = sanitize_text_field( $_POST['cdoxfilteryear'] ?? '' );

		if ( $year === 'cdox-all-years' ) {
			return null;
		}

		return (int) $year;
	}

	/**
	 * Get sort order from request.
	 *
	 * @return string
	 */
	private function get_filtered_order(): string {
		$order = strtoupper( sanitize_text_field( $_POST['dateorder'] ?? 'DESC' ) );
		return in_array( $order, array( 'ASC', 'DESC' ), true ) ? $order : 'DESC';
	}

	/**
	 * Get show date column option from request.
	 *
	 * @return bool
	 */
	private function get_show_date_option(): bool {
		return filter_var(
			$_POST['showpubdate'] ?? false,
			FILTER_VALIDATE_BOOLEAN
		);
	}

	/**
	 * Verify AJAX request.
	 *
	 * @return void
	 * @throws \InvalidArgumentException If nonce verification fails.
	 */
	private function verify_ajax_request(): void {
		if ( ! check_ajax_referer( 'cdox_frontend_nonce', 'nonce', false ) ) {
			throw new \InvalidArgumentException(
				__( 'Security check failed.', 'cdox' )
			);
		}
	}

	/**
	 * Check if plugin assets should be loaded.
	 *
	 * @return bool
	 */
	private function should_load_assets(): bool {
		global $post;

		if ( ! $post instanceof \WP_Post ) {
			return false;
		}

		// Check if any of our shortcodes are used in the content.
		$shortcode_pattern = get_shortcode_regex( array( 'cdox_list_documents', 'cdox_list_filtered_documents' ) );
		return preg_match( "/$shortcode_pattern/", $post->post_content ) === 1;
	}
}
