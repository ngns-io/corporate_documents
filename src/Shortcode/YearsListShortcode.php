<?php
declare(strict_types=1);

namespace CorporateDocuments\Shortcode;

use CorporateDocuments\Document\DocumentRepository;

/**
 * Years list shortcode handler.
 *
 * @since      1.0.0
 * @package    CorporateDocuments
 * @subpackage CorporateDocuments/Frontend/Shortcode
 */
class YearsListShortcode {

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
	 * Render the years list.
	 *
	 * @param array<string, mixed>|null $atts    Shortcode attributes.
	 * @param string|null               $content Shortcode content.
	 * @return string
	 */
	public function render( ?array $atts = array(), ?string $content = null ): string {
		$atts = shortcode_atts(
			array(
				'order'      => 'DESC',
				'show_count' => 'true',
				'link_years' => 'true',
			),
			$atts,
			'cdox_list_years'
		);

		try {
			$years = $this->document_repository->get_document_years(
				$this->validate_order( $atts['order'] )
			);

			if ( empty( $years ) ) {
				return sprintf(
					'<p class="cdox-notice">%s</p>',
					esc_html__( 'No documents found for any year.', 'cdox' )
				);
			}

			$show_count = filter_var( $atts['show_count'], FILTER_VALIDATE_BOOLEAN );
			$link_years = filter_var( $atts['link_years'], FILTER_VALIDATE_BOOLEAN );

			ob_start();
			?>
			<ul class="cdox-years-list">
				<?php foreach ( $years as $year => $count ) : ?>
					<li class="cdox-year-item">
						<?php if ( $link_years ) : ?>
							<a href="<?php echo esc_url( $this->get_year_link( $year ) ); ?>" class="cdox-year-link">
								<?php echo esc_html( $year ); ?>
								<?php if ( $show_count ) : ?>
									<span class="cdox-year-count">
										<?php
										echo esc_html(
											sprintf(
												'(%d)',
												$count
											)
										);
										?>
									</span>
								<?php endif; ?>
							</a>
						<?php else : ?>
							<span class="cdox-year">
								<?php echo esc_html( $year ); ?>
								<?php if ( $show_count ) : ?>
									<span class="cdox-year-count">
										<?php
										echo esc_html(
											sprintf(
												'(%d)',
												$count
											)
										);
										?>
									</span>
								<?php endif; ?>
							</span>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php
			return ob_get_clean();
		} catch ( \Exception $e ) {
			return sprintf(
				'<p class="cdox-error">%s</p>',
				esc_html__( 'Error loading document years.', 'cdox' )
			);
		}
	}

	/**
	 * Validate order parameter.
	 *
	 * @param string $order Order direction.
	 * @return string
	 */
	private function validate_order( string $order ): string {
		$order = strtoupper( $order );
		return in_array( $order, array( 'ASC', 'DESC' ), true ) ? $order : 'DESC';
	}

	/**
	 * Get year archive link.
	 *
	 * @param string $year Year.
	 * @return string
	 */
	private function get_year_link( string $year ): string {
		return add_query_arg(
			array(
				'post_type' => 'corporate_document',
				'year'      => $year,
			),
			home_url( '/' )
		);
	}
}