<?php
declare(strict_types=1);

namespace CorporateDocuments\Shortcode;

/**
 * Document types list shortcode handler.
 *
 * @since      1.0.0
 * @package    CorporateDocuments
 * @subpackage CorporateDocuments/Frontend/Shortcode
 */
class DocumentTypesShortcode {

	/**
	 * Render the document types list.
	 *
	 * @param array<string, mixed>|null $atts    Shortcode attributes.
	 * @param string|null               $content Shortcode content.
	 * @return string
	 */
	public function render( ?array $atts = array(), ?string $content = null ): string {
		$atts = shortcode_atts(
			array(
				'hide_empty' => 'false',
				'orderby'    => 'name',
				'order'      => 'ASC',
			),
			$atts,
			'cdox_list_documenttypes'
		);

		try {
			$terms = get_terms(
				array(
					'taxonomy'   => 'document_type',
					'hide_empty' => filter_var( $atts['hide_empty'], FILTER_VALIDATE_BOOLEAN ),
					'orderby'    => sanitize_key( $atts['orderby'] ),
					'order'      => $this->validate_order( $atts['order'] ),
				)
			);

			if ( is_wp_error( $terms ) ) {
				throw new \RuntimeException( $terms->get_error_message() );
			}

			if ( empty( $terms ) ) {
				return sprintf(
					'<p class="cdox-notice">%s</p>',
					esc_html__( 'No document types found.', 'cdox' )
				);
			}

			ob_start();
			?>
			<ul class="cdox-document-types-list">
				<?php foreach ( $terms as $term ) : ?>
					<li class="cdox-document-type-item">
						<a href="<?php echo esc_url( get_term_link( $term ) ); ?>" class="cdox-document-type-link">
							<?php echo esc_html( $term->name ); ?>
							<?php if ( $term->count > 0 ) : ?>
								<span class="cdox-document-type-count">
									<?php
									echo esc_html(
										sprintf(
											'(%d)',
											$term->count
										)
									);
									?>
								</span>
							<?php endif; ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php
			return ob_get_clean();
		} catch ( \Exception $e ) {
			return sprintf(
				'<p class="cdox-error">%s</p>',
				esc_html__( 'Error loading document types.', 'cdox' )
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
		return in_array( $order, array( 'ASC', 'DESC' ), true ) ? $order : 'ASC';
	}
}