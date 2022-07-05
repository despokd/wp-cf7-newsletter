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
        $validation_error = wpcf7_get_validation_error($tag->name);

        $class = wpcf7_form_controls_class($tag->type);
        $atts = array(
            'class' => trim($class),
        );

        if ($validation_error) {
            $class .= ' wpcf7-not-valid';
        }

        $item_atts = array();

        $item_atts['type'] = 'checkbox';
        $item_atts['name'] = $tag->name;
        $item_atts['value'] = '1';

        if ($validation_error) {
            $item_atts['aria-invalid'] = 'true';
            $item_atts['aria-describedby'] = wpcf7_get_validation_error_reference(
                $tag->name
            );
        } else {
            $item_atts['aria-invalid'] = 'false';
        }

        $item_atts['checked'] = 'checked';

        $item_atts['class'] = $tag->get_class_option();
        $item_atts['id'] = $tag->get_id_option();

        $item_atts = wpcf7_format_atts($item_atts);

        $content = empty($tag->content)
            ? (string) reset($tag->values)
            : $tag->content;

        $content = trim($content);

        if ($content) {
            if ($tag->has_option('label_first')) {
                $html = sprintf(
                    '<span class="wpcf7-list-item-label">%2$s</span><input %1$s />',
                    $item_atts,
                    $content
                );
            } else {
                $html = sprintf(
                    '<input %1$s /><span class="wpcf7-list-item-label">%2$s</span>',
                    $item_atts,
                    $content
                );
            }

            $html = sprintf(
                '<span class="wpcf7-list-item"><label>%s</label></span>',
                $html
            );
        } else {
            $html = sprintf(
                '<span class="wpcf7-list-item"><input %1$s /></span>',
                $item_atts
            );
        }

        $html = sprintf(
            '<span class="wpcf7-form-control-wrap" data-name="%1$s"><span %2$s>%3$s</span>%4$s</span>',
            esc_attr($tag->name),
            wpcf7_format_atts($atts),
            $html,
            $validation_error
        );

        return $html;
    }



    function wpcf7_acceptance_mail_tag($replaced, $submitted, $html, $mail_tag) {
        $form_tag = $mail_tag->corresponding_form_tag();

        if (!$form_tag) {
            return $replaced;
        }

        if (!empty($submitted)) {
            $replaced = __('Consented', 'contact-form-7');
        } else {
            $replaced = __('Not consented', 'contact-form-7');
        }

        $content = empty($form_tag->content)
            ? (string) reset($form_tag->values)
            : $form_tag->content;

        if (!$html) {
            $content = wp_strip_all_tags($content);
        }

        $content = trim($content);

        if ($content) {
            $replaced = sprintf(
                /* translators: 1: 'Consented' or 'Not consented', 2: conditions */
                _x(
                    '%1$s: %2$s',
                    'mail output for acceptance checkboxes',
                    'contact-form-7'
                ),
                $replaced,
                $content
            );
        }

        return $replaced;
    }
}
