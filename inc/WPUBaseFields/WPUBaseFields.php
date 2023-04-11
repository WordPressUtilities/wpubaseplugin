<?php
namespace wpubasefields_0_3_0;

/*
Class Name: WPU Base Fields
Description: A class to handle fields in WordPress
Version: 0.3.0
Class URI: https://github.com/WordPressUtilities/wpubaseplugin
Author: Darklg
Author URI: https://darklg.me/
License: MIT License
License URI: https://opensource.org/licenses/MIT
*/

class WPUBaseFields {
    private $fields = array();
    private $field_groups = array();

    function __construct($fields = array(), $field_groups = array()) {
        $this->init($fields, $field_groups);
    }

    function init($fields = array(), $field_groups = array()) {
        if (empty($fields)) {
            return;
        }

        /* Build fields */
        $this->build_fields($fields, $field_groups);

        /* Display fields */
        add_action('add_meta_boxes', array(&$this, 'display_boxes'));

        /* Display box */
        add_action('save_post', array(&$this, 'save_post'));

        /* Basic CSS */
        add_action('admin_head', array(&$this, 'admin_head'));

    }

    function build_fields($fields = array(), $field_groups = array()) {

        $default_field_group = array(
            'label' => 'Default',
            'post_type' => 'post'
        );

        /* Groups */
        if (!is_array($field_groups)) {
            $field_groups = array();
        }
        foreach ($field_groups as $group_id => $group) {
            if (!isset($group['post_type'])) {
                $group['post_type'] = 'post';
            }
            if (!isset($group['label'])) {
                $group['label'] = $group_id;
            }
            $this->field_groups[$group_id] = $group;
        }

        $need_default_group = false;

        /* Fields */
        foreach ($fields as $field_id => $field) {
            /* Check group */
            if (!isset($field['group'])) {
                error_log('Field group is not defined');
                $need_default_group = true;
                $field['group'] = 'default';
            }
            if (!isset($this->field_groups[$field['group']])) {
                error_log('Field group does not exists');
                $need_default_group = true;
                $field['group'] = 'default';
            }
            /* Check label */
            if (!isset($field['label'])) {
                $field['label'] = $field_id;
            }
            if (!isset($field['type'])) {
                $field['type'] = 'text';
            }
            if (!isset($field['required'])) {
                $field['required'] = false;
            }
            if (!isset($field['placeholder'])) {
                $field['placeholder'] = $field['type'] == 'select' ? __('Select a value', 'wpubasefields') : '';
            }
            if (!isset($field['data']) || !is_array($field['data'])) {
                $field['data'] = array('No', 'Yes');
            }
            $this->fields[$field_id] = $field;
        }

        /* Add default group if needed */
        if ($need_default_group || empty($field_groups)) {
            $this->field_groups['default'] = $default_field_group;
        }

    }

    function display_boxes() {
        foreach ($this->field_groups as $group_id => $group) {
            add_meta_box('wpubasefields_group_' . $group_id, $group['label'], array(&$this, 'display_box_content'), $group['post_type'], 'advanced', 'default', array('group_id' => $group_id));
        }
    }

    function display_box_content($post, $args) {
        $html_content = '';
        foreach ($this->fields as $field_id => $field) {
            if ($field['group'] != $args['args']['group_id']) {
                continue;
            }

            /* Shared settings */
            $value = get_post_meta($post->ID, $field_id, 1);
            $id_name = ' name="wpubasefields_' . $field_id . '" id="wpubasefields_' . $field_id . '" ';
            if ($field['required']) {
                $id_name .= ' required';
            }
            if ($field['placeholder'] && $field['type'] != 'select') {
                $id_name .= ' placeholder="' . esc_attr($field['placeholder']) . '"';
            }

            /* Build field HTML */
            $field_html = '';

            /* Label */
            $field_html .= '<label class="wpubasefield-main-label" for="wpubasefields_' . $field_id . '">';
            $field_html .= esc_html($field['label']);
            if ($field['required']) {
                $field_html .= '<em title="' . esc_attr(__('Required', 'wpubasefields')) . '">*</em>';
            }
            $field_html .= '</label>';

            /* Field */
            switch ($field['type']) {
            case 'select':
                $field_html .= '<select ' . $id_name . '>';
                $field_html .= '<option hidden>' . esc_html($field['placeholder']) . '</option>';
                foreach ($field['data'] as $key => $var) {
                    $field_html .= '<option ' . selected($value, $key, false) . ' value="' . $key . '">' . $var . '</option>';
                }
                $field_html .= '</select>';
                break;
            case 'textarea':
                $field_html .= '<textarea ' . $id_name . '>' . esc_html($value) . '</textarea>';
                break;
            case 'text':
            case 'email':
            case 'url':
                $field_html .= '<input ' . $id_name . ' type="' . esc_attr($field['type']) . '" value="' . esc_attr($value) . '" />';
            }

            if ($field_html) {
                $html_content .= '<li class="wpubasefield-input" type="' . esc_attr($field['type']) . '">' . $field_html . '</li>';
            }
        }

        if (empty($html_content)) {
            return;
        }

        /* Display box content */
        wp_nonce_field($args['id'] . '_nonce', $args['id'] . '_meta_box_nonce');
        echo '<ul class="wpubasefield-list">' . $html_content . '</ul>';

    }

    function save_post($post_id) {
        if (!$post_id) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        foreach ($this->field_groups as $group_id => $group) {
            $nnce = 'wpubasefields_group_' . $group_id;
            if (!isset($_POST[$nnce . '_meta_box_nonce']) || !wp_verify_nonce($_POST[$nnce . '_meta_box_nonce'], $nnce . '_nonce')) {
                return;
            }

            foreach ($this->fields as $field_id => $field) {
                if ($field['group'] != $group_id) {
                    continue;
                }

                if (!isset($_POST['wpubasefields_' . $field_id])) {
                    continue;
                }

                $posted_value = $this->check_field_value($_POST['wpubasefields_' . $field_id], $field);

                if ($posted_value !== false) {
                    update_post_meta($post_id, $field_id, $posted_value);
                }
            }
        }
    }

    private function check_field_value($value, $field) {
        switch ($field['type']) {
        case 'select':
            if (!array_key_exists($value, $field['data'])) {
                return false;
            }
            break;
        case 'email':
            if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
                return false;
            }
            break;
        case 'url':
            if (filter_var($value, FILTER_VALIDATE_URL) === false) {
                return false;
            }
            break;
        default:

        }

        return $value;

    }

    /* ----------------------------------------------------------
      Admin
    ---------------------------------------------------------- */

    function admin_head() {
        echo '<style>';
        echo '.wpubasefield-main-label{display:block;margin-bottom:0.3em;}';
        echo '.wpubasefield-input{max-width:500px}';
        echo '.wpubasefield-input[type="textarea"]{max-width:100%}';
        echo '.wpubasefield-input + .wpubasefield-input {margin-top:1em;}';
        echo '.wpubasefield-input input,.wpubasefield-input select,.wpubasefield-input textarea{width:100%}';
        echo '.wpubasefield-input textarea{min-height:5em}';
        echo '</style>';
    }

}

$WPUBaseFields = new WPUBaseFields();
