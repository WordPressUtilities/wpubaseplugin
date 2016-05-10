<?php

/*
Class Name: WPU Base Admin page
Description: A class to handle pages in WordPress
Version: 1.3
Author: Darklg
Author URI: http://darklg.me/
License: MIT License
License URI: http://opensource.org/licenses/MIT
*/

namespace adminpage_1_3;

class WPUBaseAdminPage {

    public $page_hook = false;

    public function __construct() {}

    /* ----------------------------------------------------------
      Script
    ---------------------------------------------------------- */

    public function init($parent, $pages) {
        $this->parent = $parent;
        $this->pages = $pages;
        $this->prefix = $this->parent->options['id'] . '-';
        $this->pages = $this->set_pages($this->pages);
        add_action('admin_menu', array(&$this,
            'set_admin_menu'
        ));
        add_action('admin_bar_menu', array(&$this,
            'set_adminbar_menu'
        ), 100);

        // Only on a plugin admin page
        $page = $this->get_page();
        if (array_key_exists($page, $this->pages)) {
            add_action('admin_post_' . $this->parent->options['id'], array(&$this,
                'set_admin_page_main_postAction'
            ));
        }
    }

    public function set_pages($pages) {
        foreach ($pages as $id => $page) {
            $page['id'] = $this->prefix . $id;
            $page['url'] = admin_url('admin.php?page=' . $page['id']);
            if (!isset($page['name'])) {
                $page['name'] = $id;
            }
            if (!isset($page['menu_name'])) {
                $page['menu_name'] = $page['name'];
            }
            if (!isset($page['parent'])) {
                $page['parent'] = '';
            }
            if (!isset($page['display_banner_menu'])) {
                $page['display_banner_menu'] = false;
            }
            if (!isset($page['function_content'])) {
                $page['function_content'] = array(&$this,
                    'page_content__' . $id
                );
            }
            if (!isset($page['function_action'])) {
                $page['function_action'] = array(&$this,
                    'page_action__' . $id
                );
            }
            if (!isset($page['level'])) {
                $page['level'] = $this->parent->options['level'];
            }
            if (!isset($page['icon_url'])) {
                $page['icon_url'] = '';
            }
            if (!isset($page['has_file'])) {
                $page['has_file'] = false;
            }
            if (!isset($page['has_form'])) {
                $page['has_form'] = true;
            }
            if (!isset($page['page_help'])) {
                $page['page_help'] = false;
            }
            $pages[$id] = $page;
        }
        return $pages;
    }

    public function set_admin_menu() {
        $parent = false;
        foreach ($this->pages as $id => $page) {

            $page_id = $page['id'];
            $page_action = array(&$this,
                'set_admin_page_main'
            );
            if (array_key_exists($page['parent'], $this->pages)) {
                $this->page_hook = add_submenu_page($this->prefix . $page['parent'], $page['name'], $page['menu_name'], $page['level'], $page_id, $page_action);
            } else {
                add_menu_page($page['name'], $page['menu_name'], $page['level'], $page_id, $page_action, $page['icon_url']);
                $this->page_hook = add_submenu_page($page_id, $page['name'], $page['name'], $page['level'], $page_id, $page_action);
            }
            add_action('load-' . $this->page_hook, array(&$this, 'add_help'), 10, 1);
        }
    }

    public function add_help($arg) {

        $screen = get_current_screen();
        if (!isset($_GET['page']) || !is_object($screen) || !property_exists($screen, 'base')) {
            return;
        }

        $base_str = str_replace(array('wpubaseplugin-', 'toplevel_page_', '-main', 'base-plugin_page_'), '', $screen->base);
        if (isset($this->pages[$base_str]['page_help']) && !empty($this->pages[$base_str]['page_help'])) {

            $screen->add_help_tab(array(
                'id' => 'help-' . $base_str,
                'title' => __('Help'),
                'content' => $this->pages[$base_str]['page_help']
            ));
        }

        // $screen->set_help_sidebar(
        //     '<p><strong>' . __('For more information:') . '</strong></p>' .
        //     '<p><a href="http://wordpress.org/support/" target="_blank">' . _('Support Forums') . '</a></p>'
        // );
    }

    public function set_adminbar_menu($admin_bar) {
        foreach ($this->pages as $id => $page) {
            if (!$page['display_banner_menu']) {
                continue;
            }
            $menu_details = array(
                'id' => $page['id'],
                'title' => $page['menu_name'],
                'href' => $page['url'],
                'meta' => array(
                    'title' => $page['menu_name']
                )
            );
            if (isset($page['parent']) && array_key_exists($page['parent'], $this->pages)) {
                $menu_details['parent'] = $this->prefix . $page['parent'];
            }
            $admin_bar->add_menu($menu_details);
        }
    }

    public function set_admin_page_main() {
        $page = $this->get_page();

        echo $this->get_wrapper_start();

        // Default Form
        if ($this->pages[$page]['has_form']):
            echo '<form action="' . admin_url('admin-post.php') . '" method="post" ' . ($this->pages[$page]['has_file'] ? ' enctype="multipart/form-data"' : '') . '><div>';
            echo '<input type="hidden" name="action" value="' . $this->parent->options['id'] . '">';
            echo '<input type="hidden" name="page_name" value="' . $page . '" />';
            wp_nonce_field('action-main-form-' . $page, 'action-main-form-' . $this->parent->options['id'] . '-' . $page);
        endif;
        call_user_func($this->pages[$page]['function_content']);
        if ($this->pages[$page]['has_form']):
            echo '</div></form>';
        endif;

        echo $this->get_wrapper_end();
    }

    public function set_admin_page_main_postAction() {
        $page = $this->get_page();
        $action_id = 'action-main-form-' . $this->parent->options['id'] . '-' . $page;
        if (empty($_POST) || !isset($_POST[$action_id]) || !wp_verify_nonce($_POST[$action_id], 'action-main-form-' . $page)) {
            return;
        }
        call_user_func($this->pages[$page]['function_action']);
        wp_redirect($this->pages[$page]['url']);
    }

    private function get_wrapper_start() {
        return '<div class="wrap"><h2 class="title">' . get_admin_page_title() . '</h2><br />';
    }

    private function get_wrapper_end() {
        return '</div>';
    }

    private function get_page() {
        $page = '';
        if (isset($_GET['page'])) {
            $page = str_replace($this->parent->options['id'] . '-', '', $_GET['page']);
        }
        if (isset($_POST['page_name'])) {
            $page = str_replace($this->parent->options['id'] . '-', '', $_POST['page_name']);
        }
        return $page;
    }
}
