<?php

// Exit if accessed directly.
if (!defined('ABSPATH')) exit;

/**
 * Class Cf7_Newsletter_Settings
 *
 * This class contains all of the plugin settings.
 * Here you can configure the whole plugin data.
 *
 * @package		CF7NEWSLET
 * @subpackage	Classes/Cf7_Newsletter_Settings
 * @author		Kilian Domaratius
 * @since		1.0.0
 */
class Cf7_Newsletter_Settings {

	/**
	 * The plugin name
	 *
	 * @var		string
	 * @since   1.0.0
	 */
	private $plugin_name;

	/**
	 * Our Cf7_Newsletter_Settings constructor 
	 * to run the plugin logic.
	 *
	 * @since 1.0.0
	 */
	function __construct() {

		$this->plugin_name = CF7NEWSLET_NAME;
		$this->add_settings_init_action();
		$this->add_options_page_action();
		$this->add_debug_mode();
	}

	/**
	 * ######################
	 * ###
	 * #### CALLABLE FUNCTIONS
	 * ###
	 * ######################
	 */

	/**
	 * Return the plugin name
	 *
	 * @access	public
	 * @since	1.0.0
	 * @return	string The plugin name
	 */
	public function get_plugin_name() {
		return apply_filters('CF7NEWSLET/settings/get_plugin_name', $this->plugin_name);
	}

	/**
	 * Register admin page
	 */
	public function add_settings_init_action() {
		add_action('admin_init', array($this, 'settings_init'));
	}

	/**
	 * Register settings page
	 */
	public function add_options_page_action() {
		add_action('admin_menu', array($this, 'options_page'));
	}

	/**
	 * Register debug mode
	 */
	public function add_debug_mode() {
		add_action('wpcf7_feedback_response', array($this, 'debug_cf7_add_error'), 10, 2);
	}

	/**
	 * Add the top level menu page.
	 */
	public function options_page() {
		add_options_page(
			__('CF7 Newsletter settings', 'cf7-newsletter'),
			__('CF7 Newsletter', 'cf7-newsletter'),
			'manage_options', // capability
			'cf7_newsletter', // menu slug
			array($this, 'options_page_html') // function
		);
	}

	/**
	 * custom option and settings
	 */
	public function settings_init() {
		// Add default settings
		add_option('cf7_newsletter_admin_mail', get_option('admin_email'));

		// Register a new setting
		register_setting('cf7_newsletter_options', 'cf7_newsletter_admin_mail');
		register_setting('cf7_newsletter_options', 'cf7_newsletter_debug');

		// Register a new field
		add_settings_field(
			'cf7_newsletter_admin_mail', 					// ID
			__('Admin mail', 'cf7_newsletter'), 			// Setting title
			array($this, 'show_text_field_cf7_newsletter_admin_mail'), 				// Callback
			'cf7_newsletter', 								// Page
			'cf7_newsletter_general_section' 				// Section
		);
		add_settings_field(
			'cf7_newsletter_debug', 					// ID
			__('Admin mail', 'cf7_newsletter'), 			// Setting title
			array($this, 'show_text_field_cf7_newsletter_admin_mail'), 				// TODO Callback
			'cf7_newsletter', 								// Page
			'cf7_newsletter_general_section' 				// Section
		);
	}

	/**
	 * Show text field for admin mail
	 *
	 * @param array $args
	 */
	public function show_text_field_cf7_newsletter_admin_mail($args) {
		// Get the value of the setting we've registered with register_setting()
?>
		<input id="<?php echo esc_attr($args['label_for']); ?>" name="cf7_newsletter_admin_mail" type="text" value="<?php esc_attr(get_option('cf7_newsletter_admin_mail')) ?>">
	<?php
	}


	/**
	 * Top level menu callback function
	 */
	public function options_page_html() {
		// check user capabilities
		if (!current_user_can('manage_options')) {
			return;
		}

		// show error/update messages
		settings_errors('cf7_newsletter_messages');
	?>
		<div class="wrap">
			<h1><?php echo esc_html(get_admin_page_title()); ?></h1>
			<?php $this->show_info_text(); ?>

			<form action="options.php" method="post">
				<?php settings_fields('cf7_newsletter_options'); ?>
				<table class="form-table" role="presentation">
					<tbody>
						<tr valign="top">
							<th scope="row"><label for="cf7_newsletter_admin_mail"><?php _e('Admin mail'); ?></label></th>
							<td><input type="email" name="cf7_newsletter_admin_mail" value="<?php echo get_option('cf7_newsletter_admin_mail'); ?>" /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="cf7_newsletter_debug"><?php _e('Debug mode'); ?></label></th>
							<td><input type="checkbox" id="cf7_newsletter_debug" name="cf7_newsletter_debug" <?php echo (get_option('cf7_newsletter_debug') == 'on') ? 'checked' : ''; ?> /></td>
						</tr>
					</tbody>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
	<?php
	}


	/**
	 * Show plugin info text
	 */
	public function show_info_text() {
	?>
		<p>Add <code>[cf7_newsletter]</code> to your subscription form. The email will be used to send to subscriber and gets a opt in link at bottom.</p>
		<p>Add <code>[cf7_newsletter_unsubscribe]</code> to your unsubscribe form. The email will be used to send a notification to site admin.</p>
<?php

	}

	/**
	 * Output debug info at submission
	 * @link https://kau-boys.de/2464/wordpress/fehler-beim-senden-in-contact-form-7-debuggen
	 */
	public function debug_cf7_add_error($items, $result) {
		if ('mail_failed' === $result['status']) {
			global $phpmailer;
			$items['error_info'] = $phpmailer->ErrorInfo;
		}

		return $items;
	}
}
