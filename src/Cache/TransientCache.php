<?php
declare(strict_types=1);

namespace CorporateDocuments\Cache;

/**
 * WordPress transient implementation of cache interface.
 *
 * @package    CorporateDocuments
 * @subpackage CorporateDocuments/Cache
 */
class TransientCache implements CacheInterface {

	/**
	 * Get value from cache.
	 *
	 * @param string $key Cache key.
	 * @return mixed
	 */
	public function get( string $key ): mixed {
		return get_transient( $key );
	}

	/**
	 * Set value in cache.
	 *
	 * @param string $key   Cache key.
	 * @param mixed  $value Value to cache.
	 * @param int    $ttl   Time to live in seconds.
	 * @return bool
	 */
	public function set( string $key, mixed $value, int $ttl ): bool {
		return set_transient( $key, $value, $ttl );
	}

	/**
	 * Delete value from cache.
	 *
	 * @param string $key Cache key.
	 * @return bool
	 */
	public function delete( string $key ): bool {
		return delete_transient( $key );
	}
}
