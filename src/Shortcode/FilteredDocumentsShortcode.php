<?php
declare(strict_types=1);

namespace CorporateDocuments\Shortcode;

use CorporateDocuments\Document\DocumentRepository;

/**
 * Filtered documents list shortcode handler.
 *
 * @since      1.0.0
 * @package    CorporateDocuments
 * @subpackage CorporateDocuments/Frontend/Shortcode
 */
class FilteredDocumentsShortcode {

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
	 * Render the filtered documents list.
	 *
	 * @param array<string, mixed>|null $atts    Shortcode attributes.
	 * @param string|null               $content Shortcode content.
	 * @return string
	 */
	public function render( ?array $atts = array(), ?string $content = null ): string {
		$atts = shortcode_atts(
			array(
				'type'                 => '',
				'show_year_filter'     => 'true',
				'show_date_column'     => 'true',
				'initial_current_year' => 'false',
				'show_type_filter'     => 'true',
				'show_order_filter'    => 'true',
			),
			$atts,
			'cdox_list_filtered_documents'
		);

		try {
			// Convert string attributes to boolean
			$show_year_filter     = filter_var( $atts['show_year_filter'], FILTER_VALIDATE_BOOLEAN );
			$show_date_column     = filter_var( $atts['show_date_column'], FILTER_VALIDATE_BOOLEAN );
			$initial_current_year = filter_var( $atts['initial_current_year'], FILTER_VALIDATE_BOOLEAN );
			$show_type_filter     = filter_var( $atts['show_type_filter'], FILTER_VALIDATE_BOOLEAN );
			$show_order_filter    = filter_var( $atts['show_order_filter'], FILTER_VALIDATE_BOOLEAN );

			// Get document types
			$document_types = $this->get_document_types( $atts['type'] );

			// Get initial documents
			$initial_args = array(
				'document_types' => $document_types,
				'year'           => $initial_current_year ? (int) date( 'Y' ) : null,
				'order'          => 'DESC',
			);

			$documents = $this->document_repository->get_filtered_documents(
				$initial_args['document_types'],
				$initial_args['year'],
				$initial_args['order']
			);

			// Start output buffer
			ob_start();
			?>
			<div class="cdox-filtered-documents">
				<?php
				$this->render_filter_form(
					$document_types,
					$show_year_filter,
					$show_type_filter,
					$show_order_filter,
					$initial_current_year
				);
				?>

				<div id="cdox-document-list" class="cdox-document-list <?php echo $show_date_column ? 'show-dates' : ''; ?>">
					<?php $this->render_documents_list( $documents, $show_date_column ); ?>
				</div>

				<div class="cdox-loading">
					<span class="cdox-loading-spinner"></span>
					<span class="cdox-loading-text"><?php esc_html_e( 'Loading...', 'cdox' ); ?></span>
				</div>
			</div>
			<?php
			return ob_get_clean();
		} catch ( \Exception $e ) {
			return sprintf(
				'<p class="cdox-error">%s</p>',
				esc_html__( 'Error loading documents.', 'cdox' )
			);
		}
	}

	/**
	 * Render the filter form.
	 *
	 * @param array<string> $document_types     Document types.
	 * @param bool          $show_year_filter   Whether to show year filter.
	 * @param bool          $show_type_filter   Whether to show type filter.
	 * @param bool          $show_order_filter  Whether to show order filter.
	 * @param bool          $initial_current_year Whether to select current year initially.
	 * @return void
	 */
	private function render_filter_form(
		array $document_types,
		bool $show_year_filter,
		bool $show_type_filter,
		bool $show_order_filter,
		bool $initial_current_year
	): void {
		require dirname( __DIR__ ) . '/views/filter-form.php';
	}

	/**
	 * Render the documents list.
	 *
	 * @param array<\CorporateDocuments\Document\Document> $documents       Documents list.
	 * @param bool                                         $show_date_column Whether to show date column.
	 * @return void
	 */
	private function render_documents_list( array $documents, bool $show_date_column ): void {
		require dirname( __DIR__ ) . '/views/document-list.php';
	}

	/**
	 * Get document types from shortcode attribute.
	 *
	 * @param string $types Comma-separated list of document types.
	 * @return array<string>
	 */
	private function get_document_types( string $types ): array {
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