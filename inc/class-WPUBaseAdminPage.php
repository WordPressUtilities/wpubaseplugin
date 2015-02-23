<?php

/*
Class Name: WPU Base Admin page
Description: A class to handle pages in WordPress
Version: 1.0.0
Author: Darklg
Author URI: http://darklg.me/
License: MIT License
License URI: http://opensource.org/licenses/MIT
*/

class WPUBaseAdminPage
{

    function __construct($parent) {
        $this->parent = $parent;
        add_action('admin_menu', array(&$this,
            'set_admin_menu'
        ));
        add_action('admin_bar_menu', array(&$this,
            'set_adminbar_menu'
        ) , 100);

        // Only on plugin admin page
        if (isset($_GET['page']) && $_GET['page'] == $this->parent->options['id']) {
            add_action('wp_loaded', array(&$this,
                'set_admin_page_main_postAction'
            ));
        }
    }

    function set_admin_menu() {
        add_menu_page($this->parent->options['name'], $this->parent->options['menu_name'], $this->parent->options['level'], $this->parent->options['id'], array(&$this,
            'set_admin_page_main'
        ));
    }

    function set_admin_page_main() {
        echo $this->get_wrapper_start($this->parent->options['name']);

        // Content
        echo '<p>' . __('Content', 'wpubaseplugin') . '</p>';

        // Default Form
        echo '<form action="" method="post"><div>';
        wp_nonce_field('action-main-form', 'action-main-form-' . $this->parent->options['id']);
        echo '<button class="button-primary" type="submit">' . __('Submit', 'wpubaseplugin') . '</button>';
        echo '</div></form>';

        echo $this->get_wrapper_end();
    }

    function set_adminbar_menu($admin_bar) {
        $admin_bar->add_menu(array(
            'id' => $this->parent->options['id'],
            'title' => $this->parent->options['menu_name'],
            'href' => admin_url('admin.php?page=' . $this->parent->options['id']) ,
            'meta' => array(
                'title' => $this->parent->options['menu_name'],
            ) ,
        ));
    }

    function set_admin_page_main_postAction() {
        if (empty($_POST) || !isset($_POST['action-main-form-' . $this->parent->options['id']]) || !wp_verify_nonce($_POST['action-main-form-' . $this->parent->options['id']], 'action-main-form')) {
            return;
        }
        $this->parent->messages->set_message('success_postaction', 'Success !');
    }

    private function get_wrapper_start($title) {
        return '<div class="wrap"><h2 class="title">' . $title . '</h2><br />';
    }

    private function get_wrapper_end() {
        return '</div>';
    }
}

