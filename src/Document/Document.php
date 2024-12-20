<?php
declare(strict_types=1);

namespace CorporateDocuments\Document;

/**
 * Document entity class for Corporate Documents plugin.
 *
 * @package    CorporateDocuments
 * @subpackage CorporateDocuments/Document
 */
class Document {
	/**
	 * Document ID.
	 *
	 * @var int
	 */
	private int $id;

	/**
	 * Document title.
	 *
	 * @var string
	 */
	private string $title;

	/**
	 * Document publication date.
	 *
	 * @var \DateTimeImmutable
	 */
	private \DateTimeImmutable $publication_date;

	/**
	 * Document file ID (attachment ID).
	 *
	 * @var int|null
	 */
	private ?int $file_id;

	/**
	 * Document types.
	 *
	 * @var array<\WP_Term>
	 */
	private array $document_types = array();

	/**
	 * Document download count.
	 *
	 * @var int
	 */
	private int $download_count = 0;

	/**
	 * Document file size in bytes.
	 *
	 * @var int|null
	 */
	private ?int $file_size = null;

	/**
	 * Document mime type.
	 *
	 * @var string|null
	 */
	private ?string $mime_type = null;

	/**
	 * Create a new document instance.
	 *
	 * @param int                $id              Document ID.
	 * @param string             $title           Document title.
	 * @param \DateTimeImmutable $publication_date Document publication date.
	 */
	public function __construct(
		int $id,
		string $title,
		\DateTimeImmutable $publication_date
	) {
		$this->id               = $id;
		$this->title            = $title;
		$this->publication_date = $publication_date;
	}

	/**
	 * Create document from WordPress post.
	 *
	 * @param \WP_Post $post Post object.
	 * @return self
	 * @throws \InvalidArgumentException If post is not a document.
	 */
	public static function from_post( \WP_Post $post ): self {
		if ( $post->post_type !== 'corporate_document' ) {
			throw new \InvalidArgumentException(
				__( 'Invalid post type for document.', 'cdox' )
			);
		}

		try {
			$document = new self(
				$post->ID,
				$post->post_title,
				new \DateTimeImmutable( $post->post_date )
			);

			// Set file ID
			$document->set_file_id(
				(int) get_post_meta( $post->ID, '_document_file_id', true )
			);

			// Set document types
			$terms = get_the_terms( $post->ID, 'document_type' );
			if ( $terms && ! is_wp_error( $terms ) ) {
				$document->set_document_types( $terms );
			}

			// Set download count
			$document->set_download_count(
				(int) get_post_meta( $post->ID, '_document_downloads', true )
			);

			// Set file information
			if ( $document->get_file_id() ) {
				$document->set_file_info();
			}

			return $document;
		} catch ( \Exception $e ) {
			throw new \InvalidArgumentException(
				sprintf(
					__( 'Failed to create document from post: %s', 'cdox' ),
					$e->getMessage()
				)
			);
		}
	}

	/**
	 * Get document ID.
	 *
	 * @return int
	 */
	public function get_id(): int {
		return $this->id;
	}

	/**
	 * Get document title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return $this->title;
	}

	/**
	 * Get formatted publication date.
	 *
	 * @param string $format Optional. Date format.
	 * @return string
	 */
	public function get_formatted_date( string $format = 'M d, Y' ): string {
		return $this->publication_date->format( $format );
	}

	/**
	 * Get document types.
	 *
	 * @return array<\WP_Term>
	 */
	public function get_document_types(): array {
		return $this->document_types;
	}

	/**
	 * Get document download count.
	 *
	 * @return int
	 */
	public function get_download_count(): int {
		return $this->download_count;
	}

	/**
	 * Get file size.
	 *
	 * @return int|null
	 */
	public function get_file_size(): ?int {
		return $this->file_size;
	}

	/**
	 * Get formatted file size.
	 *
	 * @return string
	 */
	public function get_formatted_file_size(): string {
		if ( ! $this->file_size ) {
			return '';
		}

		return size_format( $this->file_size );
	}

	/**
	 * Get file ID.
	 *
	 * @return int|null
	 */
	public function get_file_id(): ?int {
		return $this->file_id;
	}

	/**
	 * Get download URL.
	 *
	 * @return string
	 */
	public function get_download_url(): string {
		if ( ! $this->file_id ) {
			return '';
		}

		return wp_get_attachment_url( $this->file_id ) ?: '';
	}

	/**
	 * Get icon class based on mime type.
	 *
	 * @return string
	 */
	public function get_icon_class(): string {
		if ( ! $this->mime_type ) {
			return 'fa-file';
		}

		return match ( $this->mime_type ) {
			'application/pdf' => 'fa-file-pdf',
			'image/jpeg', 'image/png', 'image/gif' => 'fa-file-image',
			'video/mp4', 'video/quicktime' => 'fa-file-video',
			'text/csv' => 'fa-file-csv',
			'text/plain' => 'fa-file-alt',
			'text/xml', 'text/html' => 'fa-file-code',
			default => 'fa-file'
		};
	}

	/**
	 * Check if document has meta data.
	 *
	 * @return bool
	 */
	public function has_meta_data(): bool {
		return $this->file_size !== null || $this->download_count > 0;
	}

	/**
	 * Set file ID.
	 *
	 * @param int|null $file_id File ID.
	 * @return void
	 */
	private function set_file_id( ?int $file_id ): void {
		$this->file_id = $file_id;
	}

	/**
	 * Set document types.
	 *
	 * @param array<\WP_Term> $types Document types.
	 * @return void
	 */
	private function set_document_types( array $types ): void {
		$this->document_types = $types;
	}

	/**
	 * Set download count.
	 *
	 * @param int $count Download count.
	 * @return void
	 */
	private function set_download_count( int $count ): void {
		$this->download_count = $count;
	}

	/**
	 * Set file information.
	 *
	 * @return void
	 */
	private function set_file_info(): void {
		$file_path = get_attached_file( $this->file_id );

		if ( ! $file_path || ! file_exists( $file_path ) ) {
			return;
		}

		$this->file_size = filesize( $file_path );
		$this->mime_type = get_post_mime_type( $this->file_id );
	}
}
