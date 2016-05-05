<?php

/*
Plugin Name: WPU Base Plugin
Plugin URI: http://github.com/Darklg/WPUtilities
Description: A framework for a WordPress plugin
Version: 1.25
Author: Darklg
Author URI: http://darklg.me/
License: MIT License
License URI: http://opensource.org/licenses/MIT
*/

class WPUBasePlugin {

    private $utilities_classes = array(
        'messages' => array(
            'namespace' => 'messages_1_3',
            'name' => 'WPUBaseMessages'
        ),
        'admindatas' => array(
            'namespace' => 'admindatas_2_3',
            'name' => 'WPUBaseAdminDatas'
        ),
        'adminpage' => array(
            'namespace' => 'adminpage_1_2_4',
            'name' => 'WPUBaseAdminPage'
        ),
        'settings' => array(
            'namespace' => 'wpubasesettings_0_5',
            'name' => 'WPUBaseSettings'
        ),
        'cron' => array(
            'namespace' => 'wpubasecron_0_1',
            'name' => 'WPUBaseCron'
        )
    );

    private $plugins = array(
        'wpuoptions' => array(
            'path' => 'wpuoptions/wpuoptions.php',
            'message_url' => '<a target="_blank" href="https://github.com/WordPressUtilities/wpuoptions">WPU Options</a>'
        )
    );

    /* ----------------------------------------------------------
      Construct
    ---------------------------------------------------------- */

    public function __construct() {
        // Set plugin options
        $this->set_options();

        // Init
        add_action('init', array(&$this,
            'init'
        ), 10);
    }

    /* ----------------------------------------------------------
      Options
    ---------------------------------------------------------- */

    public function set_options() {
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

    public function load_tools() {
        $this->tools = array();
        // Check for utilities class
        foreach ($this->utilities_classes as $id => $item) {
            include dirname(__FILE__) . '/inc/' . $item['name'] . '.php';
            $className = $item['namespace'] . '\\' . $item['name'];
            $this->tools[$id] = new $className;
        }
    }

    public function check_dependencies() {
        include_once ABSPATH . 'wp-admin/includes/plugin.php';

        // Check for Plugins activation
        foreach ($this->plugins as $id => $plugin) {
            $this->plugins[$id]['installed'] = true;
            if (!is_plugin_active($plugin['path'])) {
                $this->plugins[$id]['installed'] = false;
                $this->tools['messages']->set_message($id . '__not_installed', sprintf(__('The plugin %s should be installed.', 'wpubaseplugin'), $plugin['message_url']), 'error');
            }
        }
    }

    /* ----------------------------------------------------------
      Init
    ---------------------------------------------------------- */

    public function init() {

        $admin_pages = array(
            'main' => array(
                'menu_name' => 'Base plugin',
                'name' => 'Main page',
                'function_content' => array(&$this,
                    'page_content__main'
                ),
                'function_action' => array(&$this,
                    'page_action__main'
                )
            ),
            'subpage' => array(
                'parent' => 'main',
                'name' => 'Subpage page',
                'function_content' => array(&$this,
                    'page_content__subpage'
                ),
                'function_action' => array(&$this,
                    'page_action__subpage'
                )
            )
        );

        // Load tools
        $this->load_tools();

        // Check dependencies
        $this->check_dependencies();

        // Hooks
        if (is_admin()) {
            $this->set_admin_hooks();
        } else {
            $this->set_public_hooks();
        }

        // Init admin page
        $this->tools['adminpage']->init($this, $admin_pages);

    }

    /* ----------------------------------------------------------
      Hooks
    ---------------------------------------------------------- */

    private function set_public_hooks() {
        add_action('wp_enqueue_scripts', array(&$this,
            'load_assets_css'
        ), 10);
        add_action('wp_enqueue_scripts', array(&$this,
            'load_assets_js'
        ), 10);
    }

    private function set_admin_hooks() {

        add_action('wp_dashboard_setup', array(&$this,
            'add_dashboard_widget'
        ));

        // Only on plugin admin pages
        if (isset($_GET['page']) && strpos($_GET['page'], $this->options['id']) !== false) {
            add_action('admin_print_styles', array(&$this,
                'load_assets_css'
            ));
            add_action('admin_enqueue_scripts', array(&$this,
                'load_assets_js'
            ));
        }
    }

    /* ----------------------------------------------------------
      Pages
    ---------------------------------------------------------- */

    /* Main
     -------------------------- */

    public function page_content__main() {
        echo '<p>' . __('Content', 'wpubaseplugin') . ' main</p>';
        echo '<button class="button-primary" type="submit">' . __('Submit', 'wpubaseplugin') . '</button>';
    }

    public function page_action__main() {
        $this->parent->tools['messages']->set_message('success_postaction_main', 'Success Main !');
    }

    /* Subpage
     -------------------------- */

    public function page_content__subpage() {
        echo '<p>' . __('Content', 'wpubaseplugin') . ' subpage</p>';
        echo '<button class="button-primary" type="submit">' . __('Submit', 'wpubaseplugin') . '</button>';
    }

    public function page_action__subpage() {
        $this->parent->tools['messages']->set_message('success_postaction_subpage', 'Success subpage !');
    }

    /* ----------------------------------------------------------
      Admin
    ---------------------------------------------------------- */

    public function add_dashboard_widget() {
        wp_add_dashboard_widget($this->options['id'] . '_dashboard_widget', $this->options['name'], array(&$this,
            'content_dashboard_widget'
        ));
    }

    public function content_dashboard_widget() {
        echo '<p>Hello World !</p>';
    }

    /* ----------------------------------------------------------
      Assets
    ---------------------------------------------------------- */

    public function load_assets_js() {
        wp_enqueue_script($this->options['id'] . '_scripts', plugins_url('assets/js/script.js', __FILE__));
    }

    public function load_assets_css() {
        wp_register_style($this->options['id'] . '_style', plugins_url('assets/css/style.css', __FILE__));
        wp_enqueue_style($this->options['id'] . '_style');
    }

    /* ----------------------------------------------------------
      Activation / Desactivation
    ---------------------------------------------------------- */

    public function activate() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Create or update table search
        dbDelta("CREATE TABLE " . $this->data_table . " (
            id int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
            date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            value varchar(100) DEFAULT NULL
        ) DEFAULT CHARSET=utf8;");
    }

    public function deactivate() {


    }

    public function uninstall() {
        global $wpdb;
        // Drop table
        $wpdb->query('DROP TABLE IF EXISTS ' . $this->data_table);

        // Delete options

        // delete post metas
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
