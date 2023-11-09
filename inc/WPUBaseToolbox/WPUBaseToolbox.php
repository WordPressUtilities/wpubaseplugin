<?php
namespace wpubasetoolbox_0_4_0;

/*
Class Name: WPU Base Toolbox
Description: Cool helpers for WordPress Plugins
Version: 0.4.0
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
            'fieldsets' => array(
                'default' => array(
                    'label' => ''
                )
            ),
            'form_attributes' => '',
            'form_classname' => 'cssc-form',
            'field_box_classname' => 'box',
            'submit_box_classname' => 'box--submit',
            'hidden_fields' => array(),
            'nonce_id' => $form_id,
            'nonce_name' => $form_id . '_nonce'
        );
        $args = array_merge($default_args, $args);

        $args = apply_filters('wpubasetoolbox_get_form_html_args_' . __NAMESPACE__, $args);
        if (!is_array($args['hidden_fields']) || !isset($args['hidden_fields'])) {
            $args['hidden_fields'] = array();
        }
        if (!is_array($args['fieldsets']) || !isset($args['fieldsets'])) {
            $args['fieldsets'] = array();
        }

        $extra_post_attributes = $args['form_attributes'];

        /* Clean & check fields */
        $has_file = false;
        foreach ($fields as $field_name => $field) {
            $fields[$field_name] = $this->get_clean_field($field_name, $field, $form_id, $args);
            if ($fields[$field_name]['type'] == 'file') {
                $has_file = true;
            }
        }

        /* Extra attributes */
        if ($has_file) {
            $extra_post_attributes .= ' enctype="multipart/form-data"';
        }

        /* Start form */
        $html .= '<form class="' . esc_attr($args['form_classname']) . '" id="' . esc_attr($form_id) . '" action="" method="post" ' . $extra_post_attributes . '>';

        /* Insert fields */
        foreach ($args['fieldsets'] as $fieldset_id => $fieldset) {
            $html .= '<fieldset data-fielset-id="' . $fieldset_id . '">';
            if (isset($fieldset['label']) && $fieldset['label']) {
                $html .= '<legend>' . esc_html($fieldset['label']) . '</legend>';
            }
            foreach ($fields as $field_name => $field) {
                if ($field['fieldset'] != $fieldset_id) {
                    continue;
                }
                $html .= $this->get_field_html($field_name, $field, $form_id, $args);
            }
            $html .= '</fieldset>';
        }

        /* Submit box */
        $html .= '<div class="' . esc_attr($args['submit_box_classname']) . '">';
        foreach ($args['hidden_fields'] as $field_id => $field_value) {
            $html .= '<input type="hidden" name="' . esc_attr($field_id) . '" value="' . esc_attr($field_value) . '" />';
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

    public function get_clean_field($field_name, $field, $form_id, $args) {
        if (!is_array($field)) {
            $field = array();
        }

        if (!isset($field['fieldset']) || !array_key_exists($field['fieldset'], $args['fieldsets'])) {
            $field['fieldset'] = array_key_first($args['fieldsets']);
        }

        $default_field = array(
            'label' => $field_name,
            'type' => 'text',
            'html_before_content' => '',
            'html_after_content' => '',
            'value' => '',
            'extra_attributes' => '',
            'data' => array(
                '0' => __('No'),
                '1' => __('Yes')
            ),
            'required' => false
        );
        $field = array_merge($default_field, $field);

        return $field;
    }

    public function get_field_html($field_name, $field, $form_id, $args = array()) {

        /* Data */
        /* Values */
        $field_id = $form_id . '__' . $field_name;
        $field_id_name = ' name="' . esc_attr($field_name) . '" id="' . esc_attr($field_id) . '" ' . $field['extra_attributes'];
        if ($field['required']) {
            $field_id_name .= ' required';
        }

        /* Label */
        $default_label = '<label for="' . esc_attr($field_id) . '">';
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
            $html .= '<textarea ' . $field_id_name . '>' . htmlentities($field['value'] ? $field['value'] : '') . '</textarea>';
            break;

        case 'select':
            $html .= $default_label;
            $html .= '<select ' . $field_id_name . '>';
            foreach ($field['data'] as $key => $var) {
                $html .= '<option ' . selected($key, $field['value'], false) . ' value="' . esc_attr($key) . '">' . esc_html($var) . '</option>';
            }
            $html .= '</select>';
            break;

        case 'radio':
            $html .= $default_label;
            foreach ($field['data'] as $key => $var) {
                $id_field = $field_id . '___' . $key;
                $html .= '<span>';
                $html .= '<input type="radio" id="' . esc_attr($id_field) . '" name="' . esc_attr($field_name) . '" value="' . esc_attr($key) . '" ' . ($key === $field['value'] ? 'checked' : '') . ' ' . ($field['required'] ? 'required' : '') . ' />';
                $html .= '<label for="' . esc_attr($id_field) . '">' . $var . '</label>';
                $html .= '</span>';
            }
            break;

        case 'checkbox':
            $checked = $field['value'] ? ' checked="checked"' : '';
            $html .= '<input ' . $field_id_name . ' type="' . esc_attr($field['type']) . '" value="1" ' . $checked . ' />';
            $html .= $default_label;
            break;

        default:
            $html .= $default_label;
            $html .= '<input ' . $field_id_name . ' type="' . esc_attr($field['type']) . '" value="' . esc_attr($field['value']) . '" />';
        }

        if ($html) {
            $field_html = $html;
            $html = '<p class="' . $args['field_box_classname'] . '" data-box-name="' . $field_name . '" data-box-type="' . esc_attr($field['type']) . '">';
            $html .= $field['html_before_content'];
            $html .= $field_html;
            $html .= $field['html_after_content'];
            $html .= '</p>';
        }

        return $html;
    }

}
