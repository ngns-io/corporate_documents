<?php
declare(strict_types=1);

namespace CorporateDocuments\Document;

use CorporateDocuments\Cache\CacheInterface;
use CorporateDocuments\Cache\TransientCache;

/**
 * Repository class for document management.
 *
 * @package    CorporateDocuments
 * @subpackage CorporateDocuments/Document
 */
class DocumentRepository {

	/**
	 * Cache instance.
	 *
	 * @var CacheInterface
	 */
	private CacheInterface $cache;

	/**
	 * Cache expiration in seconds.
	 *
	 * @var int
	 */
	private const CACHE_EXPIRATION = 3600; // 1 hour

	/**
	 * Initialize repository.
	 *
	 * @param CacheInterface|null $cache Optional cache implementation.
	 */
	public function __construct( ?CacheInterface $cache = null ) {
		$this->cache = $cache ?? new TransientCache();
	}

	/**
	 * Get documents based on criteria.
	 *
	 * @param array<string>        $types        Optional. Document types to filter by.
	 * @param int|null             $year         Optional. Year to filter by.
	 * @param string               $order        Optional. Sort order (ASC or DESC).
	 * @param array<string, mixed> $extra_args   Optional. Additional WP_Query arguments.
	 * @return array<Document>
	 */
	public function get_filtered_documents(
		array $types = array(),
		?int $year = null,
		string $order = 'DESC',
		array $extra_args = array()
	): array {
		$cache_key = $this->generate_cache_key(
			'documents',
			array(
				'types'      => $types,
				'year'       => $year,
				'order'      => $order,
				'extra_args' => $extra_args,
			)
		);

		$documents = $this->cache->get( $cache_key );

		if ( $documents === false ) {
			$query_args = array(
				'post_type'              => 'corporate_document',
				'post_status'            => 'publish',
				'posts_per_page'         => -1,
				'orderby'                => 'date',
				'order'                  => $this->validate_order( $order ),
				'no_found_rows'          => true,
				'update_post_term_cache' => true,
				'update_post_meta_cache' => true,
			);

			if ( ! empty( $types ) ) {
				$query_args['tax_query'] = array(
					array(
						'taxonomy' => 'document_type',
						'field'    => 'slug',
						'terms'    => $types,
					),
				);
			}

			if ( $year !== null ) {
				$query_args['year'] = $year;
			}

			$query_args = array_merge( $query_args, $extra_args );

			$query     = new \WP_Query( $query_args );
			$documents = $this->posts_to_documents( $query->posts );

			$this->cache->set( $cache_key, $documents, self::CACHE_EXPIRATION );
		}

		return $documents;
	}

	/**
	 * Get document years with counts.
	 *
	 * @param string $order Sort order for years (ASC or DESC).
	 * @return array<string, int>
	 */
	public function get_document_years( string $order = 'DESC' ): array {
		$cache_key = $this->generate_cache_key( 'years', array( 'order' => $order ) );
		$years     = $this->cache->get( $cache_key );

		if ( $years === false ) {
			global $wpdb;

			$order = $this->validate_order( $order );
			$query = $wpdb->prepare(
				"SELECT YEAR(post_date) as year, COUNT(*) as count
                FROM {$wpdb->posts}
                WHERE post_type = %s
                AND post_status = 'publish'
                GROUP BY year
                ORDER BY year {$order}",
				'corporate_document'
			);

			$results = $wpdb->get_results( $query );
			$years   = array();

			foreach ( $results as $result ) {
				$years[ $result->year ] = (int) $result->count;
			}

			$this->cache->set( $cache_key, $years, self::CACHE_EXPIRATION );
		}

		return $years;
	}

	/**
	 * Get document by ID.
	 *
	 * @param int $id Document ID.
	 * @return Document
	 * @throws \InvalidArgumentException If document not found.
	 */
	public function get_document( int $id ): Document {
		$post = get_post( $id );

		if ( ! $post || $post->post_type !== 'corporate_document' ) {
			throw new \InvalidArgumentException(
				sprintf(
					__( 'Document with ID %d not found.', 'cdox' ),
					$id
				)
			);
		}

		return Document::from_post( $post );
	}

	/**
	 * Increment document download count.
	 *
	 * @param int $id Document ID.
	 * @return void
	 * @throws \InvalidArgumentException If document not found.
	 */
	public function increment_download_count( int $id ): void {
		$document      = $this->get_document( $id );
		$current_count = $document->get_download_count();

		update_post_meta( $id, '_document_downloads', $current_count + 1 );

		// Clear cache for this document
		$this->clear_document_cache( $id );
	}

	/**
	 * Convert posts to document objects.
	 *
	 * @param array<\WP_Post> $posts Array of post objects.
	 * @return array<Document>
	 */
	private function posts_to_documents( array $posts ): array {
		return array_map(
			fn( \WP_Post $post ) => Document::from_post( $post ),
			$posts
		);
	}

	/**
	 * Generate cache key for given parameters.
	 *
	 * @param string               $prefix Key prefix.
	 * @param array<string, mixed> $params Parameters to include in key.
	 * @return string
	 */
	private function generate_cache_key( string $prefix, array $params ): string {
		return sprintf(
			'cdox_%s_%s',
			$prefix,
			md5( serialize( $params ) )
		);
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
	 * Clear cache for specific document.
	 *
	 * @param int $id Document ID.
	 * @return void
	 */
	private function clear_document_cache( int $id ): void {
		$document = get_post( $id );

		if ( ! $document ) {
			return;
		}

		// Clear year cache
		$year = get_the_date( 'Y', $document );
		$this->cache->delete( $this->generate_cache_key( 'years', array( 'order' => 'DESC' ) ) );
		$this->cache->delete( $this->generate_cache_key( 'years', array( 'order' => 'ASC' ) ) );

		// Clear document list caches
		$terms = get_the_terms( $id, 'document_type' );
		if ( $terms && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$this->cache->delete(
					$this->generate_cache_key(
						'documents',
						array(
							'types' => array( $term->slug ),
							'year'  => (int) $year,
						)
					)
				);
			}
		}
	}
}
