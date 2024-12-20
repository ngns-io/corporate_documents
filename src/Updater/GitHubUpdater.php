<?php
declare(strict_types=1);

namespace CorporateDocuments\Updater;

/**
 * Handle plugin updates from GitHub releases.
 *
 * @package    CorporateDocuments
 * @subpackage CorporateDocuments/includes
 */
class GitHubUpdater {

	/**
	 * GitHub username
	 *
	 * @var string
	 */
	private string $username;

	/**
	 * GitHub repository name
	 *
	 * @var string
	 */
	private string $repository;

	/**
	 * Plugin basename
	 *
	 * @var string
	 */
	private string $basename;

	/**
	 * GitHub API base URL
	 *
	 * @var string
	 */
	private const API_URL = 'https://api.github.com/repos';

	/**
	 * Cache key prefix
	 *
	 * @var string
	 */
	private const CACHE_KEY = 'cdox_github_update_';

	/**
	 * Initialize updater
	 *
	 * @param string $username   GitHub username
	 * @param string $repository GitHub repository name
	 * @param string $basename   Plugin basename
	 */
	public function __construct(
		string $username,
		string $repository,
		string $basename
	) {
		$this->username   = $username;
		$this->repository = $repository;
		$this->basename   = $basename;

		$this->init();
	}

	/**
	 * Initialize hooks
	 *
	 * @return void
	 */
	private function init(): void {
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_info' ), 20, 3 );
		add_filter( 'upgrader_pre_download', array( $this, 'pre_download' ), 10, 3 );
		add_action( 'upgrader_process_complete', array( $this, 'after_update' ), 10, 2 );
	}

	/**
	 * Check for updates
	 *
	 * @param object $transient Update transient
	 * @return object
	 */
	public function check_update( $transient ): object {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$release = $this->get_latest_release();
		if ( ! $release ) {
			return $transient;
		}

		// Compare versions
		$current_version = CDOX_VERSION;
		$latest_version  = ltrim( $release['tag_name'], 'v' );

		if ( version_compare( $current_version, $latest_version, '<' ) ) {
			$transient->response[ $this->basename ] = (object) array(
				'slug'         => dirname( $this->basename ),
				'new_version'  => $latest_version,
				'url'          => $release['html_url'],
				'package'      => $release['zipball_url'],
				'tested'       => $this->get_tested_wp_version( $release['body'] ),
				'requires'     => $this->get_wp_requirement( $release['body'] ),
				'requires_php' => $this->get_php_requirement( $release['body'] ),
			);
		}

		return $transient;
	}

	/**
	 * Provide plugin information for the WordPress updates screen
	 *
	 * @param mixed  $result
	 * @param string $action
	 * @param object $args
	 * @return object|mixed
	 */
	public function plugin_info( $result, string $action, object $args ) {
		if ( $action !== 'plugin_information' ) {
			return $result;
		}

		if ( dirname( $this->basename ) !== $args->slug ) {
			return $result;
		}

		$release = $this->get_latest_release();
		if ( ! $release ) {
			return $result;
		}

		return (object) array(
			'name'          => 'Corporate Documents',
			'slug'          => dirname( $this->basename ),
			'version'       => ltrim( $release['tag_name'], 'v' ),
			'author'        => sprintf(
				'<a href="https://github.com/%s">%s</a>',
				$this->username,
				$this->username
			),
			'homepage'      => sprintf(
				'https://github.com/%s/%s',
				$this->username,
				$this->repository
			),
			'requires'      => $this->get_wp_requirement( $release['body'] ),
			'requires_php'  => $this->get_php_requirement( $release['body'] ),
			'tested'        => $this->get_tested_wp_version( $release['body'] ),
			'last_updated'  => $release['published_at'],
			'sections'      => array(
				'description'  => $this->get_github_description(),
				'changelog'    => $this->format_github_changelog( $release['body'] ),
				'installation' => $this->get_installation_instructions(),
			),
			'download_link' => $release['zipball_url'],
		);
	}

	/**
	 * Get latest release information from GitHub
	 *
	 * @return array|null
	 */
	private function get_latest_release(): ?array {
		$cache_key = self::CACHE_KEY . md5( $this->username . $this->repository );
		$cached    = get_transient( $cache_key );

		if ( $cached !== false ) {
			return $cached;
		}

		$response = wp_remote_get(
			sprintf(
				'%s/%s/%s/releases/latest',
				self::API_URL,
				$this->username,
				$this->repository
			),
			array(
				'headers' => array(
					'Accept' => 'application/vnd.github.v3+json',
				),
			)
		);

		if ( is_wp_error( $response ) ||
			wp_remote_retrieve_response_code( $response ) !== 200
		) {
			return null;
		}

		$release = json_decode( wp_remote_retrieve_body( $response ), true );
		set_transient( $cache_key, $release, 6 * HOUR_IN_SECONDS );

		return $release;
	}

	/**
	 * Extract WordPress version requirement from release notes
	 *
	 * @param string $body Release notes
	 * @return string
	 */
	private function get_wp_requirement( string $body ): string {
		if ( preg_match( '/Requires WordPress: ([\d.]+)/', $body, $matches ) ) {
			return $matches[1];
		}
		return '5.9';
	}

	/**
	 * Extract PHP version requirement from release notes
	 *
	 * @param string $body Release notes
	 * @return string
	 */
	private function get_php_requirement( string $body ): string {
		if ( preg_match( '/Requires PHP: ([\d.]+)/', $body, $matches ) ) {
			return $matches[1];
		}
		return '7.4';
	}

	/**
	 * Extract tested WordPress version from release notes
	 *
	 * @param string $body Release notes
	 * @return string
	 */
	private function get_tested_wp_version( string $body ): string {
		if ( preg_match( '/Tested up to: ([\d.]+)/', $body, $matches ) ) {
			return $matches[1];
		}
		return '6.4';
	}

	/**
	 * Format GitHub release notes as WordPress changelog
	 *
	 * @param string $body Release notes
	 * @return string
	 */
	private function format_github_changelog( string $body ): string {
		// Remove requirement lines
		$body = preg_replace( '/Requires (WordPress|PHP): [\d.]+\n?/', '', $body );
		$body = preg_replace( '/Tested up to: [\d.]+\n?/', '', $body );

		// Convert GitHub markdown to WordPress format
		$body = str_replace( '### ', '= ', $body );
		$body = str_replace( '* ', '* ', $body );

		return wp_kses_post( $body );
	}

	/**
	 * Get installation instructions
	 *
	 * @return string
	 */
	private function get_installation_instructions(): string {
		return wp_kses_post(
			'
            <h4>Automatic Installation</h4>
            <ol>
                <li>Go to Plugins > Add New in your WordPress admin</li>
                <li>Search for "Corporate Documents"</li>
                <li>Click "Install Now"</li>
                <li>Activate the plugin</li>
            </ol>

            <h4>Manual Installation</h4>
            <ol>
                <li>Download the latest release from GitHub</li>
                <li>Upload the plugin folder to /wp-content/plugins/</li>
                <li>Activate the plugin in WordPress</li>
            </ol>
        '
		);
	}

	/**
	 * Get plugin description from GitHub
	 *
	 * @return string
	 */
	private function get_github_description(): string {
		$response = wp_remote_get(
			sprintf(
				'%s/%s/%s/readme',
				self::API_URL,
				$this->username,
				$this->repository
			),
			array(
				'headers' => array( 'Accept' => 'application/vnd.github.v3.raw' ),
			)
		);

		if ( is_wp_error( $response ) ||
			wp_remote_retrieve_response_code( $response ) !== 200
		) {
			return '';
		}

		return wp_kses_post( wp_remote_retrieve_body( $response ) );
	}
}
