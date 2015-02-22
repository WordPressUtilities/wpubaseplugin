<?php

/*
Class Name: WPU Base Messages
Description: A class to handle messages in WordPress
Version: 1.0.0
Author: Darklg
Author URI: http://darklg.me/
License: MIT License
License URI: http://opensource.org/licenses/MIT
*/

class WPUBaseMessages
{

    private $notices_categories = array(
        'updated',
        'update-nag',
        'error'
    );

    function __construct() {

        // Set Messages
        global $current_user;
        $this->transient_prefix = sanitize_title(basename(__FILE__)) . $current_user->ID;
        $this->transient_msg = $this->transient_prefix . '__messages';

        // Add hook
        add_action('admin_notices', array(&$this,
            'admin_notices'
        ));
    }

    /* Set notices messages */
    function set_message($id, $message, $group = '') {
        $messages = (array)get_transient($this->transient_msg);
        if (!in_array($group, $this->notices_categories)) {
            $group = $this->notices_categories[0];
        }
        $messages[$group][$id] = $message;
        set_transient($this->transient_msg, $messages);
    }

    /* Display notices */
    function admin_notices() {
        $messages = (array)get_transient($this->transient_msg);
        if (!empty($messages)) {
            foreach ($messages as $group_id => $group) {
                if (is_array($group)) {
                    foreach ($group as $message) {
                        echo '<div class="' . $group_id . '"><p>' . $message . '</p></div>';
                    }
                }
            }
        }

        // Empty messages
        delete_transient($this->transient_msg);
    }
}

