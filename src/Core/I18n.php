<?php
declare(strict_types=1);

namespace CorporateDocuments\Core;

/**
 * Define the internationalization functionality.
 *
 * @link       https://ngns.io/wordpress/plugins/corporate_documents/
 * @since      1.0.0
 *
 * @package    CorporateDocuments
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 */
class I18n {

	/**
	 * The domain specified for this plugin.
	 *
	 * @var string
	 */
	private string $domain;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $domain The domain identifier for this plugin.
	 */
	public function __construct( string $domain = 'cdox' ) {
		$this->domain = $domain;
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 * @return void
	 */
	public function load_plugin_textdomain(): void {
		load_plugin_textdomain(
			$this->domain,
			false,
			dirname( plugin_basename( __FILE__ ), 2 ) . '/languages/'
		);
	}

	/**
	 * Get the text domain for the plugin.
	 *
	 * @return string
	 */
	public function get_domain(): string {
		return $this->domain;
	}
}
