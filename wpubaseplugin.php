<?php

/*
Plugin Name: WPU Base Plugin
Plugin URI: https://github.com/WordPressUtilities/wpubaseplugin
Description: A framework for a WordPress plugin
Version: 2.33.0
Author: Darklg
Author URI: https://darklg.me/
License: MIT License
License URI: http://opensource.org/licenses/MIT
*/

class WPUBasePlugin {

    public $version = '2.33.0';

    private $utilities_classes = array(
        'messages' => array(
            'namespace' => 'messages_1_3_2',
            'name' => 'WPUBaseMessages'
        ),
        'admindatas' => array(
            'namespace' => 'admindatas_3_9_0',
            'name' => 'WPUBaseAdminDatas'
        ),
        'adminpage' => array(
            'namespace' => 'adminpage_1_5',
            'name' => 'WPUBaseAdminPage'
        ),
        'settings' => array(
            'namespace' => 'wpubasesettings_0_17_0',
            'name' => 'WPUBaseSettings'
        ),
        'cron' => array(
            'namespace' => 'wpubasecron_0_2_5',
            'name' => 'WPUBaseCron'
        ),
        'update' => array(
            'namespace' => 'wpubaseupdate_0_4_2',
            'name' => 'WPUBaseUpdate'
        )
    );

    private $plugins = array(
        'wpuoptions' => array(
            'path' => 'wpuoptions/wpuoptions.php',
            'message_url' => '<a target="_blank" href="https://github.com/WordPressUtilities/wpuoptions">WPU Options</a>'
        )
    );

    public $tools = array();

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
            'level' => 'manage_options',
            'basename' => plugin_basename(__FILE__)
        );

        load_plugin_textdomain($this->options['id'], false, dirname($this->options['basename']) . '/lang/');

        // Allow translation for plugin name
        $this->options['name'] = __('Base Plugin', 'wpubaseplugin');
        $this->options['menu_name'] = __('Base', 'wpubaseplugin');

    }

    /* ----------------------------------------------------------
      Dependencies
    ---------------------------------------------------------- */

    public function load_tools() {
        $this->tools = array();
        // Check for utilities class
        foreach ($this->utilities_classes as $id => $item) {
            if (isset($this->tools[$id])) {
                continue;
            }
            require_once dirname(__FILE__) . '/inc/' . $item['name'] . '/' . $item['name'] . '.php';
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
                'menu_name' => $this->options['name'],
                'name' => $this->options['menu_name'],
                'settings_link' => true,
                'settings_name' => 'Settings',
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

        $settings_details = array(
            'create_page' => true,
            'parent_page' => 'tools.php',
            'plugin_name' => 'WPUBasePlugin',
            'plugin_id' => 'wpubaseplugin',
            'user_cap' => 'manage_options',
            'option_id' => 'wpubaseplugin_options',
            'sections' => array(
                'settings' => array(
                    'name' => __('Base Settings', 'wpubaseplugin')
                )
            )
        );
        $settings = array(
            'test_field' => array(
                'label' => __('Test field', 'wpubaseplugin'),
                'lang' => 1
            )
        );

        // Init settings
        $this->tools['settings']->init($settings_details, $settings);

        // Init admin page
        $this->tools['adminpage']->init($this->options, $admin_pages);

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
        $this->tools['messages']->set_message('success_postaction_main', 'Success Main !');
    }

    /* Subpage
     -------------------------- */

    public function page_content__subpage() {
        echo '<p>' . __('Content', 'wpubaseplugin') . ' subpage</p>';
        echo '<button class="button-primary" type="submit">' . __('Submit', 'wpubaseplugin') . '</button>';
    }

    public function page_action__subpage() {
        $this->tools['messages']->set_message('success_postaction_subpage', 'Success subpage !');
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

    }

    public function deactivate() {

    }

    public function uninstall() {

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
