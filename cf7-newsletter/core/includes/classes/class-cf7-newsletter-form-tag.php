<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) exit;


/**
 * Class Cf7_Newsletter_Form_Tag
 * 
 * Handles submits
 *
 *
 * @package		CF7NEWSLET
 * @subpackage	Classes/Cf7_Newsletter_Form_Tag
 * @author		Kilian Domaratius
 * @since		1.0.0
 */
class Cf7_Newsletter_Form_Tag {

    /**
     * Our Cf7_Newsletter_Form_Tag constructor
     */
    function __construct() {
        wpcf7_add_form_tag(
            'cf7_newsletter',
            array($this, 'cf7_newsletter_form_tag_handler'),
            array(
               'required' => true,
            )
        );
    }

    /**
     * Handles the form tag 'cf7_newsletter'
     *
     * @param $tag
     * @return string
     */
    public function cf7_newsletter_form_tag_handler($tag) {
        ob_start();
        $name = 'cf7_newsletter';
?>
        <span class="wpcf7-form-control-wrap" data-name="<?php echo $name; ?>">
            <input type="checkbox" name="<?php echo $name; ?>" value="" class="wpcf7-form-control wpcf7-checkbox wpcf7-validates-as-required" aria-required="true" required="true">
        </span>
<?php
        return ob_get_clean();
    }
}
