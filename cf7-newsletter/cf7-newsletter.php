<?php
/**
 * CF7 Newsletter
 *
 * @package       CF7NEWSLET
 * @author        Kilian Domaratius
 * @version       1.0.0
 *
 * @wordpress-plugin
 * Plugin Name:   CF7 Newsletter
 * Plugin URI:    https://github.com/despokd/wp-cf7-newsletter
 * Description:   Catch CF7 submissions and provide double optin
 * Version:       1.0.0
 * Author:        Kilian Domaratius
 * Author URI:    https://kdomaratius.de
 * Text Domain:   cf7-newsletter
 * Domain Path:   /languages
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;
// Plugin name
define( 'CF7NEWSLET_NAME',			'CF7 Newsletter' );

// Plugin version
define( 'CF7NEWSLET_VERSION',		'1.0.0' );

// Plugin Root File
define( 'CF7NEWSLET_PLUGIN_FILE',	__FILE__ );

// Plugin base
define( 'CF7NEWSLET_PLUGIN_BASE',	plugin_basename( CF7NEWSLET_PLUGIN_FILE ) );

// Plugin Folder Path
define( 'CF7NEWSLET_PLUGIN_DIR',	plugin_dir_path( CF7NEWSLET_PLUGIN_FILE ) );

// Plugin Folder URL
define( 'CF7NEWSLET_PLUGIN_URL',	plugin_dir_url( CF7NEWSLET_PLUGIN_FILE ) );

/**
 * Load the main class for the core functionality
 */
require_once CF7NEWSLET_PLUGIN_DIR . 'core/class-cf7-newsletter.php';

/**
 * The main function to load the only instance
 * of our master class.
 *
 * @author  Kilian Domaratius
 * @since   1.0.0
 * @return  object|Cf7_Newsletter
 */
function CF7NEWSLET() {
	return Cf7_Newsletter::instance();
}

CF7NEWSLET();
