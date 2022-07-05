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
		$this->addSettingsInitAction();
		$this->addOptionsPageAction();
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
	public function addSettingsInitAction() {
		add_action('admin_init', array($this, 'settings_init'));
	}

	/**
	 * Register our wbnsoftgarden_options_page to the admin_menu action hook.
	 */
	public function addOptionsPageAction() {
		add_action('admin_menu', array($this, 'options_page'));
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

		// Register a new field
		add_settings_field(
			'cf7_newsletter_admin_mail', 					// ID
			__('Admin mail', 'cf7_newsletter'), 			// Setting title
			array($this, 'show_text_field_cf7_newsletter_admin_mail'), 				// Callback
			'cf7_newsletter', 								// Page
			'cf7_newsletter_general_section', 				// Section
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
}
