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
        add_action('wpcf7_before_send_mail', array($this, 'before_send_mail'), 10, 1);
        add_filter('wpcf7_mail_components', array($this, 'add_optin_link'), 10, 3);
    }

    /**
     * Adds the optin link to the mail
     *
     * @param $components
     * @param $contact_form
     * @param $submission
     * @return array
     */
    public function add_optin_link($components, $contact_form, $submission) {
        // check if the form is a newsletter form

    }

    /**
     * Before sending mail, we need to save the submission data
     *
     * @param $cf7
     */
    public function before_send_mail($contact_form, &$abort, $submission) {
        // get newsletter field
        $newsletter_field = $submission->get_posted_data('newsletter_field');

        // check if form contains a newsletter field
        if ($newsletter_field) {
            $submission['body'] .= "\n\n" . '<a href="' . get_permalink(get_option('cf7_newsletter_optin_page')) . '">' . __('Opt in', 'cf7-newsletter') . '</a>';

            // add submission
            $submission_id = $this->add_submission($submission);
        }
    }

    /**
     * Add a submission to custom post type "cf7_nl_submission".
     * 
     * @param $cf7
     */
    public function add_submission($cf7) {
        $submission = array(
            'form_id' => $cf7->id(),
            'form_values' => $cf7->posted_data(),
            'submission_date' => current_time('mysql'),
            'email' => $cf7->posted_data('email'),
            'opt-in' => false
        );

        $submission_id = wp_insert_post(array(
            'post_type' => POST_TYPE,
            'post_status' => 'draft',
            'post_title' => 'pending',
            'post_content' => json_encode($submission)
        ));

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
            'supports' => array('title, custom-fields')
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
}
