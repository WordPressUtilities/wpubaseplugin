<?php
namespace wpubasefields_0_1_0;

/*
Class Name: WPU Base Fields
Description: A class to handle fields in WordPress
Version: 0.1.0
Class URI: https://github.com/WordPressUtilities/wpubaseplugin
Author: Darklg
Author URI: https://darklg.me/
License: MIT License
License URI: https://opensource.org/licenses/MIT
*/

/* @TODO : Readme */

class WPUBaseFields {
    private $fields = array();
    private $groups = array();

    function __construct($fields = array(), $groups = array()) {
        $this->init($fields, $groups);
    }

    function init($fields = array(), $groups = array()) {
        if (empty($fields)) {
            return;
        }

        /* Build fields */
        $this->build_fields($fields, $groups);

        /* Display fields */
        add_action('add_meta_boxes', array(&$this, 'display_boxes'));

        /* Display box */
        add_action('save_post', array(&$this, 'save_post'));

    }

    function build_fields($fields = array()) {

        /* Groups */
        if (empty($groups)) {
            $groups = array(
                'default' => array(
                    'label' => 'Default'
                )
            );
        }
        foreach ($groups as $group_id => $group) {
            if (!isset($group['post_type'])) {
                $group['post_type'] = 'post';
            }
            if (!isset($group['label'])) {
                $group['label'] = $group_id;
            }
            $this->groups[$group_id] = $group;
        }

        /* Fields */
        foreach ($fields as $field_id => $field) {
            /* @TODO : Check that group exists */
            if (!isset($field['group'])) {
                $field['group'] = 'default';
            }
            if (!isset($field['label'])) {
                $field['label'] = $field_id;
            }
            /* @TODO : More params */
            $this->fields[$field_id] = $field;
        }

    }

    function display_boxes() {
        foreach ($this->groups as $group_id => $group) {
            add_meta_box('wpubasefields_group_' . $group_id, $group['label'], array(&$this, 'display_box_content'), $group['post_type'], 'advanced', 'default', array('group_id' => $group_id));
        }
    }

    function display_box_content($post, $args) {
        $html_content = '';
        foreach ($this->fields as $field_id => $field) {
            if ($field['group'] != $args['args']['group_id']) {
                continue;
            }

            $value = get_post_meta($post->ID, $field_id, 1);

            /* @TODO : Field types */
            $html_content .= '<li class="box">';
            $html_content .= '<label for="wpubasefields_' . $field_id . '">' . esc_html($field['label']) . '</label><br />';
            $html_content .= '<input name="wpubasefields_' . $field_id . '" id="wpubasefields_' . $field_id . '" type="text" value="' . esc_attr($value) . '" />';
            $html_content .= '</li>';
        }

        if (empty($html_content)) {
            return;
        }
        wp_nonce_field($args['id'] . '_nonce', $args['id'] . '_meta_box_nonce');

        echo '<ul>' . $html_content . '</ul>';

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

        foreach ($this->groups as $group_id => $group) {
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

                update_post_meta($post_id, $field_id, $_POST['wpubasefields_' . $field_id]);
            }
        }
    }
}

$WPUBaseFields = new WPUBaseFields();
