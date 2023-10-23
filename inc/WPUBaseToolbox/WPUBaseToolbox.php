<?php
namespace wpubasetoolbox_0_2_0;

/*
Class Name: WPU Base Toolbox
Description: Cool helpers for WordPress Plugins
Version: 0.2.0
Class URI: https://github.com/WordPressUtilities/wpubaseplugin
Author: Darklg
Author URI: https://darklg.me/
License: MIT License
License URI: https://opensource.org/licenses/MIT
*/

class WPUBaseToolbox {
    public function __construct() {}

    /* ----------------------------------------------------------
      Forms
    ---------------------------------------------------------- */

    /* Wrapper
    -------------------------- */

    public function get_form_html($form_id, $fields = array(), $args = array()) {
        $html = '';
        if (!is_array($fields) || !is_array($args)) {
            return '';
        }
        $default_args = array(
            'button_label' => __('Submit'),
            'button_classname' => 'cssc-button',
            'form_classname' => 'cssc-form',
            'field_box_classname' => 'box',
            'submit_box_classname' => 'box--submit',
            'nonce_id' => $form_id,
            'nonce_name' => $form_id . '_nonce'
        );
        $args = array_merge($default_args, $args);

        $args = apply_filters('wpubasetoolbox_get_form_html_args_' . __NAMESPACE__, $args);

        /* Start form */
        $html .= '<form class="' . esc_attr($args['form_classname']) . '" id="' . esc_attr($form_id) . '" action="" method="post">';

        /* Insert fields */
        foreach ($fields as $field_name => $field) {
            $html .= $this->get_field_html($field_name, $field, $form_id, $args);
        }

        /* Submit box */
        $html .= '<div class="' . esc_attr($args['submit_box_classname']) . '">';
        if (isset($args['hidden_fields']) && is_array($args['hidden_fields'])) {
            foreach ($args['hidden_fields'] as $field_id => $field_value) {
                $html .= '<input type="hidden" name="' . $field_id . '" value="' . esc_attr($field_value) . '" />';
            }
        }
        $html .= wp_nonce_field($args['nonce_id'], $args['nonce_name'], 0, 0);
        $html .= '<button class="' . esc_attr($args['button_classname']) . '" type="submit"><span>' . $args['button_label'] . '</span></button>';
        $html .= '</div>';

        /* End form */
        $html .= '</form>';

        return $html;
    }

    /* Field
    -------------------------- */

    public function get_field_html($field_name, $field, $form_id, $args = array()) {
        if (!is_array($field)) {
            $field = array();
        }

        $default_field = array(
            'label' => $field_name,
            'type' => 'text',
            'value' => '',
            'data' => array(
                '0' => __('No'),
                '1' => __('Yes')
            ),
            'required' => false
        );
        $field = array_merge($default_field, $field);

        /* Data */
        /* Values */
        $field_id = $form_id . '__' . $field_name;
        $field_id_name = ' name="' . $field_name . '" id="' . $field_id . '"';
        if ($field['required']) {
            $field_id_name .= ' required';
        }

        /* Label */
        $default_label = '<label for="' . $field_id . '">';
        $default_label .= $field['label'];
        if ($field['required']) {
            $default_label .= ' <em>*</em>';
        }
        $default_label .= '</label>';

        /* Content */
        $html = '';
        switch ($field['type']) {
        case 'textarea':
            $html .= $default_label;
            $html .= '<textarea ' . $field_id_name . '>' . htmlentities($field['value']) . '</textarea>';
            break;
        case 'select':
            $html .= $default_label;
            $html .= '<select ' . $field_id_name . '>';
            foreach ($field['data'] as $key => $var) {
                $html .= '<option ' . selected($key, $field['value'], false) . ' value="' . esc_attr($key) . '">' . esc_html($var) . '</option>';
            }
            $html .= '</select>';
            break;

        case 'email':
        case 'url':
        case 'number':
        case 'text':
            $html .= $default_label;
            $html .= '<input ' . $field_id_name . ' type="' . $field['type'] . '" value="' . esc_attr($field['value']) . '" />';
            break;
        default:

        }

        if ($html) {
            $field_html = $html;
            $html = '<p class="' . $args['field_box_classname'] . '" data-box-name="' . $field_name . '" data-box-type="' . esc_attr($field['type']) . '">';
            $html .= $field_html;
            $html .= '</p>';
        }

        return $html;
    }

}
