<?php
namespace wpubasefields_0_15_0;

/*
Class Name: WPU Base Fields
Description: A class to handle fields in WordPress
Version: 0.15.0
Class URI: https://github.com/WordPressUtilities/wpubaseplugin
Author: Darklg
Author URI: https://darklg.me/
License: MIT License
License URI: https://opensource.org/licenses/MIT
*/

class WPUBaseFields {
    private $script_id;
    private $version = '0.15.0';
    private $fields = array();
    private $field_groups = array();
    private $supported_types = array(
        'post',
        'page',
        'radio',
        'select',
        'editor',
        'textarea',
        'color',
        'checkboxes',
        'checkbox',
        'tel',
        'image',
        'file',
        'text',
        'email',
        'number',
        'url'
    );

    function __construct($fields = array(), $field_groups = array()) {
        $this->init($fields, $field_groups);
    }

    function init($fields = array(), $field_groups = array()) {
        if (empty($fields)) {
            return;
        }

        $this->script_id = str_replace('.', '_', 'wpubasefields_' . $this->version);

        /* Build fields */
        $this->build_fields($fields, $field_groups);

        /* Display fields */
        add_action('add_meta_boxes', array(&$this, 'display_boxes'));

        /* Display box */
        add_action('save_post', array(&$this, 'save_post'));

        /* Basic CSS */
        add_action('admin_enqueue_scripts', array(&$this, 'admin_enqueue_scripts'));

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
            if (!isset($group['capability'])) {
                $group['capability'] = 'edit_posts';
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
            if (!isset($field['type']) || !in_array($field['type'], $this->supported_types)) {
                $field['type'] = 'text';
            }
            if (!isset($field['column_start'])) {
                $field['column_start'] = false;
            }
            if (!isset($field['post_type'])) {
                $field['post_type'] = $field['type'] == 'page' ? array('page') : array('post');
            }
            if (!is_array($field['post_type'])) {
                $field['post_type'] = array($field['post_type']);
            }
            if (!isset($field['column_end'])) {
                $field['column_end'] = false;
            }
            if (!isset($field['required'])) {
                $field['required'] = false;
            }
            if (!isset($field['preview_format'])) {
                $field['preview_format'] = 'thumbnail';
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
            if (!current_user_can($group['capability'])) {
                continue;
            }
            add_meta_box('wpubasefields_group_' . $group_id, $group['label'], array(&$this, 'display_box_content'), $group['post_type'], 'advanced', 'default', array('group_id' => $group_id));
        }
    }

    function display_box_content($post, $args) {
        $html_content = '';
        foreach ($this->fields as $field_id => $field) {
            if ($field['group'] != $args['args']['group_id']) {
                continue;
            }

            if ($field['type'] == 'post' || $field['type'] == 'page') {
                $p = get_posts(array(
                    'post_type' => $field['post_type'],
                    'posts_per_page' => 500
                ));
                $field['data'] = array();
                foreach ($p as $post_item) {
                    $field['data'][$post_item->ID] = $post_item->post_title;
                }
                asort($field['data']);
            }

            /* Shared settings */
            $value = get_post_meta($post->ID, $field_id, 1);
            $field_name = 'wpubasefields_' . $field_id;
            $id_name = ' name="' . $field_name . '" id="' . $field_name . '" ';
            if ($field['required']) {
                $id_name .= ' required';
            }
            if ($field['placeholder'] && $field['type'] != 'select') {
                $id_name .= ' placeholder="' . esc_attr($field['placeholder']) . '"';
            }

            /* Build field HTML */
            $field_html = '';

            /* Label */
            $label_html = '';
            $label_html .= '<label class="wpubasefield-main-label" for="wpubasefields_' . $field_id . '">';
            $label_html .= esc_html($field['label']);
            if ($field['required']) {
                $label_html .= '<em title="' . esc_attr(__('Required', 'wpubasefields')) . '">*</em>';
            }
            $label_html .= '</label>';

            if ($field['type'] != 'checkbox') {
                $field_html .= $label_html;
            }

            /* Field */
            switch ($field['type']) {
            case 'radio':
                foreach ($field['data'] as $key => $var) {
                    $radio_item_id = $field_name . '__' . $key;
                    $field_html .= '<span class="wpubasefield-checkbox-wrapper">';
                    $field_html .= '<input ' . checked($value, $key, false) . ' value="' . esc_attr($key) . '" name="' . $field_name . '" id="' . $radio_item_id . '" type="radio" />';
                    $field_html .= '<label for="' . $radio_item_id . '">' . esc_html($var) . '</label>';
                    $field_html .= '</span>';
                }
                break;
            case 'checkboxes':
                if (!is_array($value)) {
                    $value = array();
                }
                foreach ($field['data'] as $key => $var) {
                    $check_item_id = $field_name . '__' . $key;
                    $field_html .= '<span class="wpubasefield-checkbox-wrapper">';
                    $field_html .= '<input ' . checked(in_array($key, $value), true, false) . ' value="' . esc_attr($key) . '" name="' . $field_name . '[]" id="' . $check_item_id . '" type="checkbox" />';
                    $field_html .= '<label for="' . $check_item_id . '">' . esc_html($var) . '</label>';
                    $field_html .= '</span>';
                }
                break;
            case 'select':
            case 'post':
            case 'page':
                $field_html .= '<select ' . $id_name . '>';
                $field_html .= '<option hidden>' . esc_html($field['placeholder']) . '</option>';
                foreach ($field['data'] as $key => $var) {
                    $field_html .= '<option ' . selected($value, $key, false) . ' value="' . $key . '">' . esc_html($var) . '</option>';
                }
                $field_html .= '</select>';
                break;
            case 'editor':
                $editor_args = array(
                    'media_buttons' => false,
                    'textarea_rows' => 5
                );
                ob_start();
                wp_editor($value, $field_name, $editor_args);
                $field_html = ob_get_clean();
                break;
            case 'textarea':
                $field_html .= '<textarea ' . $id_name . '>' . esc_html($value) . '</textarea>';
                break;
            case 'checkbox':
                $field_html .= '<span class="wpubasefield-checkbox-wrapper">';
                $field_html .= '<input ' . $id_name . ' class="field-checkbox" type="checkbox" value="1" ' . checked($value, '1', false) . ' />';
                $field_html .= $label_html;
                $field_html .= '</span>';
                break;
            case 'image':
            case 'file':
                $label_file = __('Select a file', 'wpubasefields');
                $label_remove = __('Remove file', 'wpubasefields');
                if ($field['type'] == 'image') {
                    $label_file = __('Select an image', 'wpubasefields');
                    $label_remove = __('Remove image', 'wpubasefields');
                }
                $preview = '';
                $icon = 'dashicons-media-default';
                if ($value && is_numeric($value)) {
                    $preview = basename(get_attached_file($value));
                }
                $has_preview = $preview ? '1' : '0';

                /* Display field */
                $field_html .= '<div class="wpubasefields-file-wrap__main" data-haspreview="' . $has_preview . '">';
                $field_html .= '<span class="wpubasefields-file-image">';
                if ($has_preview) {
                    $field_html .= wp_get_attachment_image($value, $field['preview_format']);
                }
                $field_html .= '</span>';
                $field_html .= '<span class="wpubasefields-file-wrap">';
                $field_html .= '<span class="wpubasefields-file-wrap__preview"><span>';
                $field_html .= '<span class="dashicons ' . $icon . '"></span>';
                $field_html .= '<span class="value">' . $preview . '</span>';
                $field_html .= '</span></span>';
                $field_html .= '<input ' . $id_name . ' type="hidden" value="' . $value . '" readonly />';
                $field_html .= '<button type="button" class="wpubasefields_select_file button"  class="button" title="' . esc_attr($label_file) . '">' . esc_html($label_file) . '</button>';
                $field_html .= '</span>';
                $field_html .= '<small><a class="wpubasefields-file-wrap__remove" href="#" role="button">' . esc_html($label_remove) . '</a></small>';
                $field_html .= '</div>';
                break;
            case 'text':
            case 'color':
            case 'number':
            case 'email':
            case 'url':
                $field_html .= '<input ' . $id_name . ' type="' . esc_attr($field['type']) . '" value="' . esc_attr($value) . '" />';
            }

            $field_html .= '<input class="wpubasefield-input-control" type="hidden" name="' . $field_name . '__control"  value="1" />';
            $field_html .= '<small class="wpubasefield-msg-invalid">' . __('This field is invalid', 'wpubasefields') . '</small>';

            if ($field_html) {
                if ($field['column_start']) {
                    $html_content .= '<li class="wpubasefield-input wpubasefield-input--columns"><ul>';
                }

                $field_attributes = array(
                    'class' => 'wpubasefield-input wpubasefield-input--' . $field['type'],
                    'data-valid' => '1',
                    'data-type' => $field['type']
                );
                if ($field['type'] == 'image') {
                    $field_attributes['data-image-preview'] = $field['preview_format'];
                }
                $html_content .= '<li';
                foreach ($field_attributes as $key => $var) {
                    $html_content .= ' ' . $key . '="' . esc_attr($var) . '"';
                }
                $html_content .= '>' . $field_html . '</li>';
                if ($field['column_end']) {
                    $html_content .= '</ul></li>';
                }
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

            if (!current_user_can($group['capability'])) {
                continue;
            }

            foreach ($this->fields as $field_id => $field) {
                if ($field['group'] != $group_id) {
                    continue;
                }

                /* No control value : field will not be touched */
                if (!isset($_POST['wpubasefields_' . $field_id . '__control'])) {
                    continue;
                }

                $value = ($field['type'] == 'checkbox') ? '0' : '';
                if (isset($_POST['wpubasefields_' . $field_id])) {
                    $value = ($field['type'] == 'checkbox') ? '1' : $_POST['wpubasefields_' . $field_id];
                }

                $posted_value = $this->check_field_value($value, $field);

                if ($posted_value !== false) {
                    update_post_meta($post_id, $field_id, $posted_value);
                }
            }
        }
    }

    private function check_field_value($value, $field) {
        switch ($field['type']) {
        case 'radio':
        case 'select':
            if (!array_key_exists($value, $field['data'])) {
                return false;
            }
            break;
        case 'checkboxes':
            if (!is_array($value)) {
                return false;
            }
            foreach ($value as $value_item) {
                if (!array_key_exists($value_item, $field['data'])) {
                    return false;
                }
            }
            break;
        case 'email':
            if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
                return false;
            }
            break;
        case 'color':
            if (!preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $value)) {
                return false;
            }
            break;
        case 'number':
        case 'image':
        case 'file':
            if (!is_numeric($value)) {
                return false;
            }
            break;
        case 'url':
            if (filter_var($value, FILTER_VALIDATE_URL) === false) {
                return false;
            }
            break;
        case 'checkbox':
            if ($value != '0' && $value != '1') {
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

    function admin_enqueue_scripts() {
        $screen = get_current_screen();
        if (!$screen) {
            return;
        }
        if ($screen->base != 'post') {
            return;
        }
        $need_display_assets = false;
        foreach ($this->field_groups as $group) {
            if (!current_user_can($group['capability'])) {
                continue;
            }
            if ($group['post_type'] == $screen->post_type || is_array($group['post_type']) && in_array($screen->post_type, $group['post_type'])) {
                $need_display_assets = true;
            }
        }

        if (!$need_display_assets) {
            return;
        }

        /* JS */
        wp_enqueue_media();
        wp_enqueue_script($this->script_id, plugins_url('assets/admin.js', __FILE__), array('jquery'), $this->version);

        /* CSS */
        wp_enqueue_style($this->script_id, plugins_url('assets/admin.css', __FILE__), array(), $this->version, false);
    }

}

$WPUBaseFields = new WPUBaseFields();
