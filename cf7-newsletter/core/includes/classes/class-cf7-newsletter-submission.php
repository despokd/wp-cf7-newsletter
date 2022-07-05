<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) exit;

const POST_TYPE = 'cf7_nl_submission';

/**
 * Class Cf7_Newsletter_Submission
 *  
 * Handles submits
 *
 *
 * @package		CF7NEWSLET
 * @subpackage	Classes/Cf7_Newsletter_Submission
 * @author		Kilian Domaratius
 * @since		1.0.0
 */
class Cf7_Newsletter_Submission {

    public function __construct() {
        //add_action('wpcf7_before_send_mail', array($this, 'before_send_mail'), 10, 1);
        add_filter('wpcf7_mail_components', array($this, 'add_optin_link'), 10, 3);
        add_action('wp', array($this, 'optin_link_handler'));
    }

    /**
     * Adds the optin link to the mail
     *
     * @param $components
     * @param $contact_form
     * @param $instance
     * @return array
     */
    public function add_optin_link($components, $contact_form, $instance) {
        // search for newsletter field
        $newsletter_field = $contact_form->scan_form_tags(array('type' => 'cf7_newsletter'));
        if (empty($newsletter_field)) {
            return $components;
        }

        // add submission
        $submission_id = $this->add_submission($contact_form, $components, $instance);

        // get the optin link
        $optin_link = $this->get_optin_link($submission_id, $components['recipient']);

        // add optin link to mail
        $components['body'] .= "\n\n" . $optin_link;

        // log
        error_log($components['body']);

        return $components;
    }

    /**
     * Returns the optin link
     *
     * @param $submission
     * @return string
     */
    public function get_optin_link($submission_id, $submission_mail) {
        // get homepage url
        $homepage_url = get_home_url();

        return $homepage_url . '?cf7_nl_i=' . $submission_id . '&cf7_nl_m=' . $submission_mail;
    }

    /**
     * Before sending mail, we need to save the submission data
     *
     */
    public function before_send_mail($contact_form) {
        // get newsletter field
        $newsletter_field = $contact_form->scan_form_tags(array('type' => 'cf7_newsletter'));

        // check if form contains a newsletter field
        if ($newsletter_field) {
            $newsletter_field['body'] .= "\n\n" . '<a href="' . get_permalink(get_option('cf7_newsletter_optin_page')) . '">' . __('Opt in', 'cf7-newsletter') . '</a>';
        }
    }

    /**
     * Add a submission to custom post type "cf7_nl_submission".
     * 
     * @param $contact_form
     */
    public function add_submission($contact_form, $components, $instance) {
        $submission = array(
            'form_id' => $contact_form->id(),
            'form_values' => $contact_form->get_properties(),
            'submission_date' => current_time('mysql')
        );

        $submission_id = wp_insert_post(array(
            'post_type' => POST_TYPE,
            'post_status' => 'pending',
            'post_title' => $components['recipient'], true,
            'post_content' => json_encode($submission)
        ));

        // add custom fields
        foreach ($submission as $key => $value) {
            add_post_meta($submission_id, $key, $value);
        }

        return $submission_id;
    }

    /**
     * Add custom post type "cf7_nl_submission".
     */
    public function add_post_type() {
        register_post_type(POST_TYPE, array(
            'labels' => array(
                'name' => __('Submissions', 'cf7-newsletter'),
                'singular_name' => __('Submission', 'cf7-newsletter'),
                'edit_item' => __('View Submission', 'cf7-newsletter'),
                'search_items' => __('Search Submissions', 'cf7-newsletter'),
                'not_found' => __('No Submissions found', 'cf7-newsletter'),
                'not_found_in_trash' => __('No Submissions found in Trash', 'cf7-newsletter')
            ),
            'show_ui' => true,
            'show_in_menu' => false,
            'public' => false,
            'has_archive' => false,
            'hierarchical' => false,
            'exclude_from_search' => true,
            'capability_type' => 'post',
            'supports' => array('title, custom-fields, editor, author, thumbnail, page-attributes, comments, revisions, post-formats')
        ));

        // add to cf7 menu
        add_action('admin_menu', array($this, 'add_submissions_menu'));
    }

    /**
     * Add post type to cf7 admin menu.
     */
    public function add_submissions_menu() {
        add_submenu_page(
            'wpcf7', // parent slug
            '📰 ' . __('Submissions', 'cf7-newsletter'), // page title
            '📰 ' . __('Submissions', 'cf7-newsletter'), // menu title
            'manage_options', // capability
            'edit.php?post_type=' . POST_TYPE, // menu slug
        );
    }

    /**
     * Handle optin link.
     */
    public function optin_link_handler() {
        if (isset($_GET['cf7_nl_i']) && isset($_GET['cf7_nl_m'])) {
            $submission_id = $_GET['cf7_nl_i'];
            $submission_mail = $_GET['cf7_nl_m'];

            // get submission
            $submission = get_post($submission_id);

            // check if submission exists
            if ($submission) {
                // get submission data
                $submission_data = json_decode($submission->post_content, true);

                // check if submission data is valid
                if ($submission->post_title == $submission_mail) {
                    // update submission
                    wp_update_post(array(
                        'ID' => $submission_id,
                        'post_status' => 'publish'
                    ));

                    // send mail
                    $this->send_new_subscriber_mail($submission);

                    // add success message
                    _e('You have successfully subscribed to our newsletter.', 'cf7-newsletter');

                    return;
                }
            }
            // add failure message
            _e('Something went wrong. Please try again.', 'cf7-newsletter');
        }
    }

    /**
     * Send mail to admin for new subscriber.
     *
     * @param $submission_data
     */
    public function send_new_subscriber_mail($submission) {
        // get admin email
        $admin_email = get_option('admin_email');

        // get admin name
        $admin_name = get_option('blogname');

        // get admin mail subject
        $admin_mail_subject = __('New subscriber', 'cf7-newsletter') . $submission->post_title;

        // get data
        $submission_data = json_decode($submission->post_content, true);

        // get admin mail body
        $admin_mail_body = '
            <p>' . __('New subscriber', 'cf7-newsletter') . '</p>
            <table>
                <tr>
                    <td>' . __('Email', 'cf7-newsletter') . '</td>
                    <td>' . $submission->post_title . '</td>
                </tr>
            </table>
        ';

        try {
            // all submission data in table
            $admin_mail_body .= '<p><strong>' . __('Form data', 'cf7-newsletter') . '</strong></p>';
            $admin_mail_body .= '<table>';
            foreach ($submission_data as $key => $value) {
                $admin_mail_body .= '
                <tr>
                    <td>' . $key . '</td>
                    <td>' . $value . '</td>
                </tr>
            ';
            }
            $admin_mail_body .= '</table>';
        } catch (Exception $e) {
            // do nothing
        }


        // replace tokens
        $admin_mail_subject = str_replace('{submission_date}', $submission_data['submission_date'], $admin_mail_subject);
        $admin_mail_subject = str_replace('{submission_ip}', $submission_data['submission_ip'], $admin_mail_subject);
        $admin_mail_subject = str_replace('{submission_mail}', $submission_data['email'], $admin_mail_subject);

        $admin_mail_body = str_replace('{submission_date}', $submission_data['submission_date'], $admin_mail_body);
        $admin_mail_body = str_replace('{submission_ip}', $submission_data['submission_ip'], $admin_mail_body);
        $admin_mail_body = str_replace('{submission_mail}', $submission_data['email'], $admin_mail_body);

        // send mail
        wp_mail("$admin_name <$admin_email>", $admin_mail_subject, $admin_mail_body);
    }
}
