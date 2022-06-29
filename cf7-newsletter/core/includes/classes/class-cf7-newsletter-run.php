<?php

// Exit if accessed directly.
if (!defined('ABSPATH')) exit;

/**
 * Class Cf7_Newsletter_Run
 *
 * Thats where we bring the plugin to life
 *
 * @package		CF7NEWSLET
 * @subpackage	Classes/Cf7_Newsletter_Run
 * @author		Kilian Domaratius
 * @since		1.0.0
 */
class Cf7_Newsletter_Run {

	/**
	 * Our Cf7_Newsletter_Run constructor 
	 * to run the plugin logic.
	 *
	 * @since 1.0.0
	 */
	function __construct() {
		$this->add_hooks();
	}

	/**
	 * ######################
	 * ###
	 * #### WORDPRESS HOOKS
	 * ###
	 * ######################
	 */

	/**
	 * Registers all WordPress and plugin related hooks
	 *
	 * @access	private
	 * @since	1.0.0
	 * @return	void
	 */
	private function add_hooks() {

		// scripts and styles
		add_action('admin_enqueue_scripts', array($this, 'enqueue_backend_scripts_and_styles'), 20);

		// custom post types
		add_action('init', array($this, 'register_submission_post_type'));

		// cf7 form tags
		add_action('wpcf7_init', array($this, 'register_form_tag'));
	}

	/**
	 * ######################
	 * ###
	 * #### WORDPRESS HOOK CALLBACKS
	 * ###
	 * ######################
	 */

	/**
	 * Enqueue the backend related scripts and styles for this plugin.
	 * All of the added scripts andstyles will be available on every page within the backend.
	 *
	 * @access	public
	 * @since	1.0.0
	 *
	 * @return	void
	 */
	public function enqueue_backend_scripts_and_styles() {
		wp_enqueue_style('cf7newslet-backend-styles', CF7NEWSLET_PLUGIN_URL . 'core/includes/assets/css/backend-styles.css', array(), CF7NEWSLET_VERSION, 'all');
		wp_enqueue_script('cf7newslet-backend-scripts', CF7NEWSLET_PLUGIN_URL . 'core/includes/assets/js/backend-scripts.js', array(), CF7NEWSLET_VERSION, false);
		wp_localize_script('cf7newslet-backend-scripts', 'cf7newslet', array(
			'plugin_name'   	=> __(CF7NEWSLET_NAME, 'cf7-newsletter'),
		));
	}

	/**
	 * Register the custom post type "cf7_newsletter_submissions".
	 *
	 * @access	public
	 * @since	1.0.0
	 *
	 * @return	void
	 */
	public function register_submission_post_type() {
		(new Cf7_Newsletter_Submission())->add_post_type();
	}

	/**
	 * Register form tag for CF7 Newsletter.
	 */
	public function register_form_tag() {
		new Cf7_Newsletter_Form_Tag();
	}
}