<?php
namespace wpubasetoolbox_0_1_0;

/*
Class Name: WPU Base Toolbox
Description: Cool helpers for WordPress Plugins
Version: 0.1.0
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
        if (!isset($args['button_label'])) {
            $args['button_label'] = __('Submit');
        }

        $html .= '<form id="' . $form_id . '" action="" method="post">';
        foreach ($fields as $field_name => $field) {
            $html .= $this->get_field_html($field_name, $field, $form_id, $args);
        }
        if (isset($args['hidden_fields']) && is_array($args['hidden_fields'])) {
            foreach ($args['hidden_fields'] as $field_id => $field_value) {
                $html .= '<input type="hidden" name="' . $field_id . '" value="' . esc_attr($field_value) . '" />';
            }
        }

        $html .= '<button type="submit"><span>' . $args['button_label'] . '</span></button>';
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
            'required' => false
        );
        $field = array_merge($default_field, $field);

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
            $html = '<p data-box-name="' . $field_name . '" data-box-type="' . esc_attr($field['type']) . '">';
            $html .= $field_html;
            $html .= '</p>';
        }

        return $html;
    }

}
