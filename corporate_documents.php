<?php
/**
 * The plugin bootstrap file
 *
 * @link              https://github.com/ngns-io/corporate-documents
 * @since             1.0.0
 * @package           CorporateDocuments
 *
 * @wordpress-plugin
 * Plugin Name:       Corporate Documents
 * Plugin URI:        https://github.com/ngns-io/corporate-documents
 * Description:       Plugin to manage corporate documents
 * Version:           1.0.0
 * Author:            Evenhouse Consulting, Inc.
 * Author URI:        https://evenhouseconsulting.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       cdox
 * Domain Path:       /languages
 */

declare(strict_types=1);

namespace CorporateDocuments;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct access denied.' );
}

// Autoloader.
require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

/**
 * Constants
 */
define( 'CDOX_VERSION', '1.0.0' );
define( 'CDOX_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CDOX_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CDOX_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * The core plugin class.
 */
class CorporateDocuments {

	/**
	 * The loader that's responsible for maintaining and registering all hooks.
	 *
	 * @var Core\Loader
	 */
	protected Core\Loader $loader;

	/**
	 * The document repository instance.
	 *
	 * @var Document\DocumentRepository
	 */
	protected Document\DocumentRepository $document_repository;

	/**
	 * Plugin configuration.
	 *
	 * @var array<string, string>
	 */
	protected array $config = array(
		'plugin_name' => 'corporate-documents',
		'version'     => CDOX_VERSION,
		'domain'      => 'cdox',
	);

	/**
	 * Initialize the plugin.
	 */
	public function __construct() {
		$this->load_dependencies();
		$this->init_plugin();
	}

	/**
	 * Load required dependencies.
	 *
	 * @return void
	 */
	private function load_dependencies(): void {
		$this->loader              = new Core\Loader();
		$this->document_repository = new Document\DocumentRepository();
	}

	/**
	 * Initialize plugin components.
	 *
	 * @return void
	 */
	private function init_plugin(): void {
		$this->init_localization();
		$this->init_admin();
		$this->init_frontend();
		$this->init_updater();
	}

	/**
	 * Initialize localization.
	 *
	 * @return void
	 */
	private function init_localization(): void {
		$i18n = new Core\I18n( $this->config['domain'] );
		$this->loader->add_action( 'plugins_loaded', $i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Initialize admin functionality.
	 *
	 * @return void
	 */
	private function init_admin(): void {
		$plugin_admin = new Admin\Admin(
			$this->config['plugin_name'],
			$this->config['version']
		);

		// Core admin hooks.
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		// Post type and taxonomy handling.
		$post_type = new Admin\PostType( $this->document_repository );
		$this->loader->add_action( 'init', $post_type, 'register_post_type' );
		$this->loader->add_action( 'init', $post_type, 'register_taxonomy' );

		// Admin columns and filters.
		$this->loader->add_filter(
			'manage_corporate_document_posts_columns',
			$post_type,
			'set_columns'
		);
		$this->loader->add_action(
			'manage_corporate_document_posts_custom_column',
			$post_type,
			'render_column',
			10,
			2
		);
	}

	/**
	 * Initialize frontend functionality.
	 *
	 * @return void
	 */
	private function init_frontend(): void {
		$plugin_public = new Frontend\Frontend(
			$this->config['plugin_name'],
			$this->config['version'],
			$this->document_repository
		);

		// Assets.
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

		// Shortcodes.
		$this->loader->add_action( 'init', $plugin_public, 'register_shortcodes' );

		// AJAX handlers.
		$this->loader->add_action( 'wp_ajax_cdox_filter', $plugin_public, 'handle_filter' );
		$this->loader->add_action( 'wp_ajax_nopriv_cdox_filter', $plugin_public, 'handle_filter' );
	}

	/**
	 * Initialize updater.
	 *
	 * @return void
	 */
	private function init_updater(): void {
		if ( is_admin() ) {
			new Updater\GitHubUpdater(
				'ngns-io',           // Your GitHub username.
				'corporate-documents',     // Repository name.
				CDOX_PLUGIN_BASENAME
			);
		}
	}

	/**
	 * Run the plugin.
	 *
	 * @return void
	 */
	public function run(): void {
		$this->loader->run();
	}

	/**
	 * Get plugin name.
	 *
	 * @return string
	 */
	public function get_plugin_name(): string {
		return $this->config['plugin_name'];
	}

	/**
	 * Get plugin version.
	 *
	 * @return string
	 */
	public function get_version(): string {
		return $this->config['version'];
	}

	/**
	 * Get loader instance.
	 *
	 * @return Core\Loader
	 */
	public function get_loader(): Core\Loader {
		return $this->loader;
	}
}

// Initialize plugin.
if ( ! function_exists( 'CorporateDocuments\init_corporate_documents' ) ) :

	/**
	 * Initialize the plugin.
	 *
	 * @return CorporateDocuments Main plugin instance.
	 */
	function init_corporate_documents(): CorporateDocuments {
		static $plugin = null;

		if ( $plugin === null ) {
			// Register activation/deactivation hooks.
			register_activation_hook( __FILE__, array( Core\Activator::class, 'activate' ) );
			register_deactivation_hook( __FILE__, array( Core\Deactivator::class, 'deactivate' ) );

			// Create and run plugin.
			$plugin = new CorporateDocuments();
			$plugin->run();
		}

		return $plugin;
	}

endif;

// Start the plugin.
init_corporate_documents();
