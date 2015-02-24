<?php

/*
Class Name: WPU Base Admin page
Description: A class to handle pages in WordPress
Version: 1.1.0
Author: Darklg
Author URI: http://darklg.me/
License: MIT License
License URI: http://opensource.org/licenses/MIT
*/

class WPUBaseAdminPage
{

    private $pages = array(
        'main' => array(
            'name' => 'Main page',
            'menu_name' => 'Main page'
        ) ,
        'subpage' => array(
            'name' => 'Subpage page',
            'menu_name' => 'Subpage page',
            'parent' => 'main'
        )
    );

    function __construct($parent) {
        $this->parent = $parent;
        add_action('admin_menu', array(&$this,
            'set_admin_menu'
        ));
        add_action('admin_bar_menu', array(&$this,
            'set_adminbar_menu'
        ) , 100);

        // Only on a plugin admin page
        $page = $this->get_page();
        if (array_key_exists($page, $this->pages)) {
            add_action('wp_loaded', array(&$this,
                'set_admin_page_main_postAction'
            ));
        }
    }

    function set_admin_menu() {
        $parent = false;
        foreach ($this->pages as $id => $page) {
            $page_id_pref = $this->parent->options['id'] . '-';
            $page_id = $page_id_pref . $id;
            $page_action = array(&$this,
                'set_admin_page_main'
            );
            if (isset($page['parent']) && array_key_exists($page['parent'], $this->pages)) {
                add_submenu_page($page_id_pref . $page['parent'], $page['name'], $page['menu_name'], $page['level'], $page_id, $page_action);
            } else {
                add_menu_page($page['name'], $page['menu_name'], $page['level'], $page_id, $page_action);
            }
        }
    }

    function set_adminbar_menu($admin_bar) {
        foreach ($this->pages as $id => $page) {
            $page_id_pref = $this->parent->options['id'] . '-';

            $menu_details = array(
                'id' => $page_id_pref . $id,
                'title' => $page['menu_name'],
                'href' => admin_url('admin.php?page=' . $page_id_pref . $id) ,
                'meta' => array(
                    'title' => $page['menu_name'],
                ) ,
            );
            if (isset($page['parent']) && array_key_exists($page['parent'], $this->pages)) {
                $menu_details['parent'] = $page_id_pref . $page['parent'];
            }
            $admin_bar->add_menu($menu_details);
        }
    }

    function set_admin_page_main() {
        $page = $this->get_page();

        echo $this->get_wrapper_start($this->pages[$page]['name']);

        // Default Form
        echo '<form action="" method="post"><div>';

        wp_nonce_field('action-main-form-' . $page, 'action-main-form-' . $this->parent->options['id'] . '-' . $page);

        switch ($page) {
            case 'main':
                echo '<p>' . __('Content', 'wpubaseplugin') . '</p>';
                echo '<button class="button-primary" type="submit">' . __('Submit', 'wpubaseplugin') . '</button>';
                break;

            case 'subpage':
                echo '<p>' . __('Content', 'wpubaseplugin') . '</p>';
                echo '<button class="button-primary" type="submit">' . __('Submit', 'wpubaseplugin') . '</button>';
                break;

            default:
                break;
        }

        echo '</div></form>';

        echo $this->get_wrapper_end();
    }

    function set_admin_page_main_postAction() {
        $page = $this->get_page();
        $action_id = 'action-main-form-' . $this->parent->options['id'] . '-' . $page;
        if (empty($_POST) || !isset($_POST[$action_id]) || !wp_verify_nonce($_POST[$action_id], 'action-main-form-' . $page)) {
            return;
        }
        switch ($page) {
            case 'main':
                $this->parent->messages->set_message('success_postaction', 'Success Main !');
                break;

            case 'subpage':
                $this->parent->messages->set_message('success_postaction', 'Success Main 2 !');
                break;

            default:
                break;
        }
    }

    private function get_wrapper_start($title) {
        return '<div class="wrap"><h2 class="title">' . $title . '</h2><br />';
    }

    private function get_wrapper_end() {
        return '</div>';
    }

    private function get_page() {
        $page = '';
        if (isset($_GET['page'])) {
            $page = str_replace($this->parent->options['id'] . '-', '', $_GET['page']);
        }
        return $page;
    }
}

