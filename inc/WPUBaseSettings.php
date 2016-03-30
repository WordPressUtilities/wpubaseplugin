<?php
namespace wpubasesettings_0_5;

/*
Class Name: WPU Base Settings
Description: A class to handle native settings in WordPress admin
Version: 0.5
Author: Darklg
Author URI: http://darklg.me/
License: MIT License
License URI: http://opensource.org/licenses/MIT
*/

class WPUBaseSettings {
    public function __construct($settings_details = array(), $settings = array()) {
        if (empty($settings_details) || empty($settings)) {
            return;
        }
        $this->set_datas($settings_details, $settings);
        add_action('admin_init', array(&$this,
            'add_settings'
        ));
        add_filter('option_page_capability_' . $this->settings_details['option_id'], array(&$this,
            'set_min_capability'
        ));
    }

    public function set_min_capability() {
        return $this->settings_details['user_cap'];
    }

    public function set_datas($settings_details, $settings) {
        if (!is_array($settings_details)) {
            $settings_details = array(
                'plugin_id' => 'wpubasesettingsdefault',
                'option_id' => 'wpubasesettingsdefault_options',
                'sections' => array(
                    'import' => array(
                        'name' => __('Import Settings', 'wpubasesettingsdefault')
                    )
                )
            );
        }
        if (!isset($settings_details['user_cap'])) {
            $settings_details['user_cap'] = 'manage_options';
        }
        foreach ($settings_details['sections'] as $id => $section) {
            if (!isset($section['user_cap'])) {
                $settings_details['sections'][$id]['user_cap'] = 'manage_options';
            }
        }
        $this->settings_details = $settings_details;
        if (!is_array($settings)) {
            $settings = array(
                'option_example' => array(
                    'label' => 'My label',
                    'help' => 'My help',
                    'type' => 'textarea'
                )
            );
        }

        $default_section = key($this->settings_details['sections']);
        foreach ($settings as $id => $input) {
            $settings[$id]['label'] = isset($input['label']) ? $input['label'] : '';
            $settings[$id]['label_check'] = isset($input['label_check']) ? $input['label_check'] : '';
            $settings[$id]['help'] = isset($input['help']) ? $input['help'] : '';
            $settings[$id]['type'] = isset($input['type']) ? $input['type'] : 'text';
            $settings[$id]['section'] = isset($input['section']) ? $input['section'] : $default_section;
            $settings[$id]['user_cap'] = $this->settings_details['sections'][$settings[$id]['section']]['user_cap'];
        }

        $this->settings = $settings;
    }

    public function add_settings() {
        register_setting($this->settings_details['option_id'], $this->settings_details['option_id'], array(&$this,
            'options_validate'
        ));
        foreach ($this->settings_details['sections'] as $id => $section) {
            if (current_user_can($section['user_cap'])) {
                add_settings_section($id, $section['name'], '', $this->settings_details['plugin_id']);
            }
        }

        foreach ($this->settings as $id => $input) {
            // Hide input if not in capacity
            if (!current_user_can($input['user_cap'])) {
                continue;
            }
            add_settings_field($id, $this->settings[$id]['label'], array(&$this,
                'render__field'
            ), $this->settings_details['plugin_id'], $this->settings[$id]['section'], array(
                'name' => $this->settings_details['option_id'] . '[' . $id . ']',
                'id' => $id,
                'label_for' => $id,
                'type' => $this->settings[$id]['type'],
                'help' => $this->settings[$id]['help'],
                'label_check' => $this->settings[$id]['label_check']
            ));
        }
    }

    public function options_validate($input) {
        $options = get_option($this->settings_details['option_id']);
        foreach ($this->settings as $id => $setting) {
            // Set a default value
            // - if not sent
            // - if user is not allowed
            if (!isset($input[$id]) || !current_user_can($setting['user_cap'])) {
                $input[$id] = isset($options[$id]) ? $options[$id] : '0';
            }
            $option_id = $input[$id];
            switch ($setting['type']) {
            case 'email':
                if (filter_var($input[$id], FILTER_VALIDATE_EMAIL) === false) {
                    $option_id = '';
                }
                break;
            case 'url':
                if (filter_var($input[$id], FILTER_VALIDATE_URL) === false) {
                    $option_id = '';
                }
                break;
            case 'number':
                if (!is_numeric($input[$id])) {
                    $option_id = 0;
                }
                break;
            default:
                $option_id = esc_html(trim($input[$id]));
            }

            $options[$id] = $option_id;
        }

        add_settings_error(
            $this->settings_details['option_id'],
            $this->settings_details['option_id'] . esc_attr('settings_updated'),
            __('Settings saved.'),
            'updated'
        );

        return $options;
    }

    public function render__field($args = array()) {
        $option_id = $this->settings_details['option_id'];
        $options = get_option($option_id);
        $name = ' name="' . $option_id . '[' . $args['id'] . ']" ';
        $id = ' id="' . $args['id'] . '" ';

        switch ($args['type']) {
        case 'checkbox':
            $checked_val = isset($options[$args['id']]) ? $options[$args['id']] : '0';
            echo '<label><input type="checkbox" ' . $name . ' ' . $id . ' ' . checked($checked_val, '1', 0) . ' value="1" /> ' . $args['label_check'] . '</label>';
            break;
        case 'textarea':
            echo '<textarea ' . $name . ' ' . $id . ' cols="50" rows="5">' . esc_attr($options[$args['id']]) . '</textarea>';
            break;
        case 'url':
        case 'number':
        case 'email':
        case 'text':
            echo '<input ' . $name . ' ' . $id . ' type="' . $args['type'] . '" value="' . esc_attr($options[$args['id']]) . '" />';
        }
        if (!empty($args['help'])) {
            echo '<div><small>' . $args['help'] . '</small></div>';
        }
    }
}

/*
    ## INIT ##
    $this->settings_details = array(
        'plugin_id' => 'wpuimporttwitter',
        'option_id' => 'wpuimporttwitter_options',
        'sections' => array(
            'import' => array(
                'name' => __('Import Settings', 'wpuimporttwitter')
            )
        )
    );
    $this->settings = array(
        'sources' => array(
            'label' => __('Sources', 'wpuimporttwitter'),
            'help' => __('One #hashtag or one @user per line.', 'wpuimporttwitter'),
            'type' => 'textarea'
        )
    );
    if (is_admin()) {
        include 'inc/WPUBaseSettings.php';
        new \wpuimporttwitter\WPUBaseSettings($this->settings_details,$this->settings);
    }

    ## IN ADMIN PAGE ##
    echo '<form action="' . admin_url('options.php') . '" method="post">';
    settings_fields($this->settings_details['option_id']);
    do_settings_sections($this->options['plugin_id']);
    echo submit_button(__('Save Changes', 'wpuimporttwitter'));
    echo '</form>';
*/
