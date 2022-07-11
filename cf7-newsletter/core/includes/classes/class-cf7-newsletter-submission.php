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
        // subscribe
        add_filter('wpcf7_mail_components', array($this, 'add_optin_link'), 10, 3);
        add_action('wpcf7_before_send_mail', array($this, 'add_submission_content'), 10, 3);
        add_action('wp', array($this, 'optin_link_handler'));

        // unsubscribe
        add_filter('wpcf7_mail_components', array($this, 'unsubscribe'), 10, 3);

        // activate custom fields, even if ACF deactivates them
        add_filter('acf/settings/remove_wp_meta_box', '__return_false');
    }

    /**
     * Adds the optin link to the mail
     *
     * @param $components
     * @param $contact_form
     * @param $mail_object
     * @return array
     */
    public function add_optin_link($components, $contact_form, $mail_object) {
        // search for newsletter field
        $newsletter_field = $contact_form->scan_form_tags(array('type' => 'cf7_newsletter'));
        if (empty($newsletter_field)) {
            return $components;
        }

        // search submission in custom post type
        $args = array(
            'post_type' => POST_TYPE,
            'post_status' => 'pending',
            'post_title' => $components['recipient']
        );
        $submissions = get_posts($args);
        if (!$submissions) {
            return $components;
        }
        $submission_id = $submissions[0]->ID;

        // get the optin link
        $optin_link = $this->get_optin_link($submission_id, $components['recipient']);

        // add optin link to mail
        $components['body'] .= "\n\n" . $optin_link;

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
     * Add a submission to custom post type "cf7_nl_submission".
     * 
     * @param $contact_form
     * @param $abort
     * @param $instance
     */
    public function add_submission_content($contact_form, $abort, $instance) {
        // check if form contains a newsletter field
        $newsletter_field = $contact_form->scan_form_tags(array('type' => 'cf7_newsletter'));
        if (empty($newsletter_field)) {
            return;
        }

        // create submission
        $recipient = $instance->get_posted_data('your-email');

        if (empty($recipient)) {
            try {
                $mail_fields = $contact_form->scan_form_tags(array('basetype' => 'email'));
                $recipient = $instance->get_posted_data($mail_fields[0]->raw_name);
            } catch (Exception $e) {
            }
        }

        if (empty($recipient)) {
            $recipient = 'n.a.';
        }

        $submission_id = wp_insert_post(array(
            'post_type' => POST_TYPE,
            'post_status' => 'pending',
            'post_title' => $recipient
        ));

        // add custom fields
        add_post_meta($submission_id, 'form_id',  $contact_form->id());
        add_post_meta($submission_id, 'form_title', $contact_form->title());
        add_post_meta($submission_id, 'submission_date',  current_time('mysql'));
        foreach ($instance->get_posted_data() as $key => $value) {
            add_post_meta($submission_id, $key, $value);
        }

        return;
    }

    /**
     * Add custom post type "cf7_nl_submission".
     */
    public function add_post_type() {
        register_post_type(POST_TYPE, array(
            'labels' => array(
                'name' => __('Submissions', 'cf7-newsletter'),
                'menu_name' => __('Newsletter', 'cf7-newsletter'),
                'singular_name' => __('Submission', 'cf7-newsletter'),
                'edit_item' => __('View Submission', 'cf7-newsletter'),
                'search_items' => __('Search Submissions', 'cf7-newsletter'),
                'not_found' => __('No Submissions found', 'cf7-newsletter'),
                'not_found_in_trash' => __('No Submissions found in Trash', 'cf7-newsletter')
            ),
            'show_ui' => true,
            'show_in_menu' => false,
            'menu_icon' => 'dashicons-email-alt',
            'public' => false,
            'has_archive' => false,
            'hierarchical' => false,
            'exclude_from_search' => false,
            'publicly_queryable' => false,
            'capability_type' => 'post',
            'supports' => array(
                'title',
                'custom-fields',
                'revisions'
            ),
            'query_var' => true
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
            'edit.php?post_type=' . POST_TYPE // menu slug
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

                // check if submission data is valid
                if ($submission->post_title == $submission_mail) {
                    // update submission
                    wp_update_post(array(
                        'ID' => $submission_id,
                        'post_status' => 'publish'
                    ));

                    // add custom field opt in date
                    add_post_meta($submission_id, 'opt_in_date', current_time('mysql'));

                    // send mail
                    $this->send_new_subscriber_mail($submission);

                    // add success message
?>
                    <script>
                        alert('<?php _e('You have been successfully subscribed to our newsletter.', 'cf7-newsletter'); ?>');
                        window.location.href = '<?php echo get_home_url(); ?>';
                    </script>
            <?php

                    return;
                }
            }
            // add failure message
            ?>
            <script>
                alert('<?php _e('Something went wrong. Please try again.', 'cf7-newsletter'); ?>');
                window.location.href = '<?php echo get_home_url(); ?>';
            </script>
<?php
        }
    }

    /**
     * Send mail to admin for new subscriber.
     *
     * @param $submission_data
     */
    public function send_new_subscriber_mail($submission) {
        // get admin email
        $admin_email = get_option('cf7_newsletter_admin_mail') ?? get_option('admin_email');

        // get admin name
        $admin_name = get_option('blogname');

        // get admin mail subject
        $admin_mail_subject = __('New subscriber', 'cf7-newsletter') . ' ' . $submission->post_title;

        // get data from custom fields
        $submission_data = get_post_meta($submission->ID);

        // get admin mail body
        $admin_mail_body =  __('New subscriber', 'cf7-newsletter') . ' ' . $submission->post_title . '

';

        try {
            // all submission data in table
            $admin_mail_body .= '' . __('Form data', 'cf7-newsletter') . '' . '
_______________
';
            foreach ($submission_data as $key => $value) {
                $admin_mail_body .= $key . ':   ' . $value[0] . '
';
            }
        } catch (Exception $e) {
            // do nothing
        }

        // send mail
        wp_mail("$admin_name <$admin_email>", $admin_mail_subject, $admin_mail_body);
    }

    /**
     * Unsubscribe: Remove post and send mail to admin.
     *
     * @param $components
     * @param $contact_form
     * @param $mail_object
     * @return array
     */
    public function unsubscribe($components, $contact_form, $mail_object) {
        // search for newsletter field
        $newsletter_field = $contact_form->scan_form_tags(array('type' => 'cf7_newsletter_unsubscribe'));
        if (empty($newsletter_field)) {
            return $components;
        }

        // search for email field
        $email_fields = $contact_form->scan_form_tags(array('base_type' => 'email'));

        // search for email field
        $email_field = 'error';
        foreach ($email_fields as $field) {
            if (!empty($field)) {
                $email_field = $mail_object->replace_tags("[$field->raw_name]", true);
                break;
            }
        }

        // check if email field is found
        if (empty($email_field)) {
            $email_field = $mail_object->replace_tags('[your-email]', true);
        }

        // get submission where post title is equal to email
        $submissions = get_posts(array(
            'post_type' => POST_TYPE,
            'post_status' => 'any',
            'post_title' => $email_field
        ));

        // check if submissions exists
        if (empty($submissions)) {
            return $components;
        }


        foreach ($submissions as $submission) {
            if ($submission->post_title === $email_field) {
                // add custom fields to mail
                $components['body'] .= '*' . __('Submission data', 'cf7-newsletter') . "*\n";
                $submission_data = get_post_meta($submission->ID);
                foreach ($submission_data as $key => $value) {
                    $components['body'] .= $key . ': ' . $value[0] . "\n";
                }

                // delete submission
                wp_delete_post($submission->ID, true);

                // send mail
                //$this->send_unsubscribe_mail($email->name);
            }
        }

        return $components;
    }

    /**
     * Send mail to admin for unsubscribe.
     *
     * @param $submission
     */
    public function send_unsubscribe_mail($email) {
        // get admin email
        $admin_email = get_option('cf7_newsletter_admin_mail') ?? get_option('admin_email');

        // get admin name
        $admin_name = get_option('blogname');

        // get admin mail subject
        $admin_mail_subject = __('Unsubscribe', 'cf7-newsletter') . $email;

        // get admin mail body
        $admin_mail_body = '
' . __('Unsubscribe', 'cf7-newsletter') . ' ' . $email . '

' . __('This mail was sent from CF7 Newsletter Plugin') . '
        ';

        // send mail
        wp_mail("$admin_name <$admin_email>", $admin_mail_subject, $admin_mail_body);
    }
}
