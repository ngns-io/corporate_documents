<?php
declare(strict_types=1);

namespace CorporateDocuments\Shortcode;

use CorporateDocuments\Document\DocumentRepository;

/**
 * Document list shortcode handler.
 */
class DocumentListShortcode {

	/**
	 * Document repository instance.
	 *
	 * @var DocumentRepository
	 */
	private DocumentRepository $document_repository;

	/**
	 * Initialize the class.
	 *
	 * @param DocumentRepository $document_repository Document repository instance.
	 */
	public function __construct( DocumentRepository $document_repository ) {
		$this->document_repository = $document_repository;
	}

	/**
	 * Render the shortcode.
	 *
	 * @param array<string, mixed>|null $atts    Shortcode attributes.
	 * @param string|null               $content Shortcode content.
	 * @return string
	 */
	public function render( ?array $atts = array(), ?string $content = null ): string {
		$atts = shortcode_atts(
			array(
				'type'             => '',
				'show_date_column' => 'false',
			),
			$atts,
			'cdox_list_documents'
		);

		$show_date_column = filter_var( $atts['show_date_column'], FILTER_VALIDATE_BOOLEAN );
		$document_types   = $this->parse_document_types( $atts['type'] );

		try {
			$documents = $this->document_repository->get_documents( $document_types );

			ob_start();
			require plugin_dir_path( __DIR__ ) . 'views/document-list.php';
			return ob_get_clean();
		} catch ( \Exception $e ) {
			return sprintf(
				'<p class="cdox-error">%s</p>',
				esc_html__( 'Error loading documents.', 'cdox' )
			);
		}
	}

	/**
	 * Parse document types from shortcode attribute.
	 *
	 * @param string $types Comma-separated list of document types.
	 * @return array<string>
	 */
	private function parse_document_types( string $types ): array {
		if ( empty( $types ) ) {
			return array();
		}

		return array_map(
			'sanitize_text_field',
			array_filter(
				explode( ',', preg_replace( '/\s*,\s*/', ',', $types ) )
			)
		);
	}
}
