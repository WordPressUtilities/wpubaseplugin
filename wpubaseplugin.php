<?php

/*
Plugin Name: WPU Base Plugin
Plugin URI: http://github.com/Darklg/WPUtilities
Description: A framework for a WordPress plugin
Version: 1.7.1
Author: Darklg
Author URI: http://darklg.me/
License: MIT License
License URI: http://opensource.org/licenses/MIT
*/

class WPUBasePlugin
{

    /* ----------------------------------------------------------
      Construct
    ---------------------------------------------------------- */

    function __construct() {
        $this->set_options();
        add_action('init', array(&$this,
            'init'
        ) , 10);
    }

    /* ----------------------------------------------------------
      Options
    ---------------------------------------------------------- */

    function set_options() {
        global $wpdb;
        $this->options = array(
            'id' => 'wpubaseplugin',
            'level' => 'manage_options'
        );

        load_plugin_textdomain($this->options['id'], false, dirname(plugin_basename(__FILE__)) . '/lang/');

        // Allow translation for plugin name
        $this->options['name'] = __('Base Plugin', 'wpubaseplugin');
        $this->options['menu_name'] = __('Base', 'wpubaseplugin');


        $this->data_table = $wpdb->prefix . $this->options['id'] . "_table";
    }


    /* ----------------------------------------------------------
      Dependencies
    ---------------------------------------------------------- */

    function check_dependencies() {
        include_once (ABSPATH . 'wp-admin/includes/plugin.php');

        // Check for Plugins activation
        $this->plugins = array(
            'wpuoptions' => array(
                'installed' => true,
                'path' => 'wpuoptions/wpuoptions.php',
                'message_url' => '<a target="_blank" href="https://github.com/WordPressUtilities/wpuoptions">WPU Options</a>',
            )
        );
        foreach ($this->plugins as $id => $plugin) {
            if (!is_plugin_active($plugin['path'])) {
                $this->plugins[$id]['installed'] = false;
                $this->messages->set_message($id . '__not_installed', sprintf(__('The plugin %s should be installed.', 'wpubaseplugin') , $plugin['message_url']) , 'error');
            }
        }
    }

    /* ----------------------------------------------------------
      Init
    ---------------------------------------------------------- */

    function init() {

        // Set messages
        require_once dirname(__FILE__) . '/inc/class-WPUBaseMessages.php';
        $this->messages = new WPUBaseMessages();

        // Set admin datas
        require_once dirname(__FILE__) . '/inc/class-WPUBaseAdminDatas.php';
        $this->admin_datas = new WPUBaseAdminDatas();

        // Check dependencies
        $this->check_dependencies();

        // Hooks
        if (is_admin()) {
            $this->set_admin_hooks();
        } else {
            $this->set_public_hooks();
        }
    }

    /* ----------------------------------------------------------
      Hooks
    ---------------------------------------------------------- */

    private function set_public_hooks() {
        add_action('wp_enqueue_scripts', array(&$this,
            'load_assets_css'
        ) , 10);
        add_action('wp_enqueue_scripts', array(&$this,
            'load_assets_js'
        ) , 10);
    }

    private function set_admin_hooks() {
        add_action('admin_menu', array(&$this,
            'set_admin_menu'
        ));
        add_action('admin_bar_menu', array(&$this,
            'set_adminbar_menu'
        ) , 100);
        add_action('wp_dashboard_setup', array(&$this,
            'add_dashboard_widget'
        ));

        // Only on plugin admin page
        if (isset($_GET['page']) && $_GET['page'] == $this->options['id']) {
            add_action('wp_loaded', array(&$this,
                'set_admin_page_main_postAction'
            ));
            add_action('admin_print_styles', array(&$this,
                'load_assets_css'
            ));
            add_action('admin_enqueue_scripts', array(&$this,
                'load_assets_js'
            ));
        }
    }


    /* ----------------------------------------------------------
      Admin
    ---------------------------------------------------------- */

    function set_admin_menu() {
        add_menu_page($this->options['name'], $this->options['menu_name'], $this->options['level'], $this->options['id'], array(&$this,
            'set_admin_page_main'
        ));
    }

    function set_admin_page_main() {
        echo $this->get_wrapper_start($this->options['name']);

        // Content
        echo '<p>' . __('Content', 'wpubaseplugin') . '</p>';

        // Default Form
        echo '<form action="" method="post"><div>';
        wp_nonce_field('action-main-form', 'action-main-form-' . $this->options['id']);
        echo '<button class="button-primary" type="submit">' . __('Submit', 'wpubaseplugin') . '</button>';
        echo '</div></form>';

        echo $this->get_wrapper_end();
    }

    function set_adminbar_menu($admin_bar) {
        $admin_bar->add_menu(array(
            'id' => $this->options['id'],
            'title' => $this->options['menu_name'],
            'href' => admin_url('admin.php?page=' . $this->options['id']) ,
            'meta' => array(
                'title' => $this->options['menu_name'],
            ) ,
        ));
    }

    function set_admin_page_main_postAction() {
        if (empty($_POST) || !isset($_POST['action-main-form-' . $this->options['id']]) || !wp_verify_nonce($_POST['action-main-form-' . $this->options['id']], 'action-main-form')) {
            return;
        }
        $this->messages->set_message('success_postaction', 'Success !');
    }

    /* Widget Dashboard */

    function add_dashboard_widget() {
        wp_add_dashboard_widget($this->options['id'] . '_dashboard_widget', $this->options['name'], array(&$this,
            'content_dashboard_widget'
        ));
    }

    function content_dashboard_widget() {
        echo '<p>Hello World !</p>';
    }

    /* ----------------------------------------------------------
      Assets
    ---------------------------------------------------------- */

    function load_assets_js() {
        wp_enqueue_script($this->options['id'] . '_scripts', plugins_url('assets/js/script.js', __FILE__));
    }

    function load_assets_css() {
        wp_register_style($this->options['id'] . '_style', plugins_url('assets/css/style.css', __FILE__));
        wp_enqueue_style($this->options['id'] . '_style');
    }

    /* ----------------------------------------------------------
      Activation / Desactivation
    ---------------------------------------------------------- */

    function activate() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Create or update table search
        dbDelta("CREATE TABLE " . $this->data_table . " (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `value` varchar(100) DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) DEFAULT CHARSET=utf8;");
    }

    function deactivate() {
    }

    function uninstall() {
        global $wpdb;
        $wpdb->query('DROP TABLE ' . $this->data_table);
    }

    /* ----------------------------------------------------------
      Utilities : Public
    ---------------------------------------------------------- */

    private function public_message($message = '') {
        get_header();
        echo '<div class="' . $this->options['id'] . '-message">' . $message . '</div>';
        get_footer();
        exit();
    }

    /* ----------------------------------------------------------
      Utilities : Display
    ---------------------------------------------------------- */

    private function get_wrapper_start($title) {
        return '<div class="wrap"><h2 class="title">' . $title . '</h2><br />';
    }

    private function get_wrapper_end() {
        return '</div>';
    }

    private function get_admin_table($values, $args = array()) {
        $pagination = '';
        if (isset($args['pagenum'], $args['max_pages'])) {
            $page_links = paginate_links(array(
                'base' => add_query_arg('pagenum', '%#%') ,
                'format' => '',
                'prev_text' => '&laquo;',
                'next_text' => '&raquo;',
                'total' => $args['max_pages'],
                'current' => $args['pagenum']
            ));

            if ($page_links) {
                $pagination = '<div class="tablenav"><div class="tablenav-pages" style="margin: 1em 0">' . $page_links . '</div></div>';
            }
        }

        $content = '<table class="widefat">';
        if (isset($args['columns']) && is_array($args['columns']) && !empty($args['columns'])) {
            $labels = '<tr><th>' . implode('</th><th>', $args['columns']) . '</th></tr>';
            $content.= '<thead>' . $labels . '</thead>';
            $content.= '<tfoot>' . $labels . '</tfoot>';
        }
        $content.= '<tbody>';
        foreach ($values as $id => $vals) {
            $content.= '<tr>';
            foreach ($vals as $val) {
                $content.= '<td>' . $val . '</td>';
            }
            $content.= '</tr>';
        }
        $content.= '</tbody>';
        $content.= '</table>';
        $content.= $pagination;

        return $content;
    }
}

/* Launch plugin */
$WPUBasePlugin = new WPUBasePlugin();

/* Set activation/deactivation hook */
register_activation_hook(__FILE__, array(&$WPUBasePlugin,
    'activate'
));
register_deactivation_hook(__FILE__, array(&$WPUBasePlugin,
    'deactivate'
));
