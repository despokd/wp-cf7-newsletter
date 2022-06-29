<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'Cf7_Newsletter' ) ) :

	/**
	 * Main Cf7_Newsletter Class.
	 *
	 * @package		CF7NEWSLET
	 * @subpackage	Classes/Cf7_Newsletter
	 * @since		1.0.0
	 * @author		Kilian Domaratius
	 */
	final class Cf7_Newsletter {

		/**
		 * The real instance
		 *
		 * @access	private
		 * @since	1.0.0
		 * @var		object|Cf7_Newsletter
		 */
		private static $instance;

		/**
		 * CF7NEWSLET helpers object.
		 *
		 * @access	public
		 * @since	1.0.0
		 * @var		object|Cf7_Newsletter_Helpers
		 */
		public $helpers;

		/**
		 * CF7NEWSLET settings object.
		 *
		 * @access	public
		 * @since	1.0.0
		 * @var		object|Cf7_Newsletter_Settings
		 */
		public $settings;

		/**
		 * Throw error on object clone.
		 *
		 * Cloning instances of the class is forbidden.
		 *
		 * @access	public
		 * @since	1.0.0
		 * @return	void
		 */
		public function __clone() {
			_doing_it_wrong( __FUNCTION__, __( 'You are not allowed to clone this class.', 'cf7-newsletter' ), '1.0.0' );
		}

		/**
		 * Disable unserializing of the class.
		 *
		 * @access	public
		 * @since	1.0.0
		 * @return	void
		 */
		public function __wakeup() {
			_doing_it_wrong( __FUNCTION__, __( 'You are not allowed to unserialize this class.', 'cf7-newsletter' ), '1.0.0' );
		}

		/**
		 * Main Cf7_Newsletter Instance.
		 *
		 * Insures that only one instance of Cf7_Newsletter exists in memory at any one
		 * time. Also prevents needing to define globals all over the place.
		 *
		 * @access		public
		 * @since		1.0.0
		 * @static
		 * @return		object|Cf7_Newsletter	The one true Cf7_Newsletter
		 */
		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Cf7_Newsletter ) ) {
				self::$instance					= new Cf7_Newsletter;
				self::$instance->base_hooks();
				self::$instance->includes();
				self::$instance->helpers		= new Cf7_Newsletter_Helpers();
				self::$instance->settings		= new Cf7_Newsletter_Settings();

				//Fire the plugin logic
				new Cf7_Newsletter_Run();

				/**
				 * Fire a custom action to allow dependencies
				 * after the successful plugin setup
				 */
				do_action( 'CF7NEWSLET/plugin_loaded' );
			}

			return self::$instance;
		}

		/**
		 * Include required files.
		 *
		 * @access  private
		 * @since   1.0.0
		 * @return  void
		 */
		private function includes() {
			require_once CF7NEWSLET_PLUGIN_DIR . 'core/includes/classes/class-cf7-newsletter-helpers.php';
			require_once CF7NEWSLET_PLUGIN_DIR . 'core/includes/classes/class-cf7-newsletter-settings.php';

			// new classes
			require_once CF7NEWSLET_PLUGIN_DIR . 'core/includes/classes/class-cf7-newsletter-submission.php';
			require_once CF7NEWSLET_PLUGIN_DIR . 'core/includes/classes/class-cf7-newsletter-form-tag.php';

			require_once CF7NEWSLET_PLUGIN_DIR . 'core/includes/classes/class-cf7-newsletter-run.php';
		}

		/**
		 * Add base hooks for the core functionality
		 *
		 * @access  private
		 * @since   1.0.0
		 * @return  void
		 */
		private function base_hooks() {
			add_action( 'plugins_loaded', array( self::$instance, 'load_textdomain' ) );
		}

		/**
		 * Loads the plugin language files.
		 *
		 * @access  public
		 * @since   1.0.0
		 * @return  void
		 */
		public function load_textdomain() {
			load_plugin_textdomain( 'cf7-newsletter', FALSE, dirname( plugin_basename( CF7NEWSLET_PLUGIN_FILE ) ) . '/languages/' );
		}

	}

endif; // End if class_exists check.