<?php
declare(strict_types=1);

namespace CorporateDocuments\Cache;

/**
 * Cache interface for document repository.
 *
 * @package    CorporateDocuments
 * @subpackage CorporateDocuments/Cache
 */
interface CacheInterface {

	/**
	 * Get value from cache.
	 *
	 * @param string $key Cache key.
	 * @return mixed
	 */
	public function get( string $key ): mixed;

	/**
	 * Set value in cache.
	 *
	 * @param string $key   Cache key.
	 * @param mixed  $value Value to cache.
	 * @param int    $ttl   Time to live in seconds.
	 * @return bool
	 */
	public function set( string $key, mixed $value, int $ttl ): bool;

	/**
	 * Delete value from cache.
	 *
	 * @param string $key Cache key.
	 * @return bool
	 */
	public function delete( string $key ): bool;
}
