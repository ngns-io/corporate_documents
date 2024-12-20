<?php
declare(strict_types=1);

namespace CorporateDocuments\Updater;

/**
 * Handle plugin updates from a custom update server.
 *
 * @package    CorporateDocuments
 * @subpackage CorporateDocuments/includes
 */
class Updater {

	/**
	 * The plugin current version
	 *
	 * @var string
	 */
	private string $current_version;

	/**
	 * The plugin remote update path
	 *
	 * @var string
	 */
	private string $update_path;

	/**
	 * Plugin Slug (plugin_directory/plugin_file.php)
	 *
	 * @var string
	 */
	private string $plugin_slug;

	/**
	 * Plugin name (plugin_file)
	 *
	 * @var string
	 */
	private string $slug;

	/**
	 * Cache key for update info
	 *
	 * @var string
	 */
	private string $cache_key;

	/**
	 * Initialize a new instance of the WordPress Auto-Update class
	 *
	 * @param string $update_path Update server URL.
	 * @param string $plugin_slug Plugin slug.
	 */
	public function __construct( string $update_path, string $plugin_slug ) {
		$this->current_version = CDOX_VERSION;
		$this->update_path     = $update_path;
		$this->plugin_slug     = $plugin_slug;
		$this->slug            = str_replace( '/', '', dirname( $this->plugin_slug ) );
		$this->cache_key       = 'cdox_update_' . md5( $this->plugin_slug );

		$this->init();
	}

	/**
	 * Initialize updater hooks
	 *
	 * @return void
	 */
	private function init(): void {
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_info' ), 20, 3 );
		add_action( 'upgrader_process_complete', array( $this, 'purge_update_cache' ), 10, 2 );
	}

	/**
	 * Check for plugin updates.
	 *
	 * @param object $transient Transient data.
	 * @return object
	 */
	public function check_update( object $transient ): object {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$remote_info = $this->get_remote_info();
		if ( ! $remote_info ) {
			return $transient;
		}

		if ( version_compare( $this->current_version, $remote_info->version, '<' ) ) {
			$obj               = new \stdClass();
			$obj->slug         = $this->slug;
			$obj->new_version  = $remote_info->version;
			$obj->url          = $remote_info->homepage;
			$obj->package      = $remote_info->download_url;
			$obj->tested       = $remote_info->tested_up_to ?? '';
			$obj->requires     = $remote_info->requires_at_least ?? '';
			$obj->requires_php = $remote_info->requires_php ?? '';

			$transient->response[ $this->plugin_slug ] = $obj;
		}

		return $transient;
	}

	/**
	 * Get plugin info for the WordPress plugin details popup
	 *
	 * @param false|object|array $result The result object or array.
	 * @param string             $action The type of information being requested.
	 * @param object             $args   Plugin API arguments.
	 * @return false|object
	 */
	public function plugin_info( $result, string $action, object $args ) {
		// Check if this request is for our plugin
		if ( $action !== 'plugin_information' || $args->slug !== $this->slug ) {
			return $result;
		}

		$remote_info = $this->get_remote_info();
		if ( ! $remote_info ) {
			return $result;
		}

		$information                = new \stdClass();
		$information->name          = $remote_info->name;
		$information->slug          = $this->slug;
		$information->version       = $remote_info->version;
		$information->author        = $remote_info->author;
		$information->homepage      = $remote_info->homepage;
		$information->requires_php  = $remote_info->requires_php;
		$information->tested        = $remote_info->tested_up_to;
		$information->download_link = $remote_info->download_url;
		$information->trunk         = $remote_info->download_url;
		$information->requires      = $remote_info->requires_at_least;
		$information->last_updated  = $remote_info->last_updated;
		$information->sections      = array(
			'description'  => $remote_info->sections->description,
			'installation' => $remote_info->sections->installation,
			'changelog'    => $remote_info->sections->changelog,
		);

		if ( ! empty( $remote_info->banners ) ) {
			$information->banners = $remote_info->banners;
		}

		return $information;
	}

	/**
	 * Get plugin information from remote server.
	 *
	 * @return object|false
	 */
	private function get_remote_info() {
		// Check cache first
		$cached = get_transient( $this->cache_key );
		if ( $cached !== false ) {
			return $cached;
		}

		// Fetch update info
		$request = wp_remote_get(
			$this->update_path,
			array(
				'timeout' => 10,
				'headers' => array(
					'Accept' => 'application/json',
				),
			)
		);

		if ( is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) !== 200 ) {
			return false;
		}

		$info = json_decode( wp_remote_retrieve_body( $request ) );

		// Cache for 6 hours
		set_transient( $this->cache_key, $info, 6 * HOUR_IN_SECONDS );

		return $info;
	}

	/**
	 * Clear the update cache after an update.
	 *
	 * @param \WP_Upgrader $upgrader WordPress upgrader instance.
	 * @param array        $options  Update options.
	 * @return void
	 */
	public function purge_update_cache( \WP_Upgrader $upgrader, array $options ): void {
		if ( $options['action'] === 'update' && $options['type'] === 'plugin' ) {
			delete_transient( $this->cache_key );
		}
	}
}
