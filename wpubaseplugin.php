<?php

/*
Plugin Name: WPU Base Plugin
Plugin URI: https://github.com/WordPressUtilities/wpubaseplugin
Update URI: https://github.com/WordPressUtilities/wpubaseplugin
Description: A framework for a WordPress plugin
Version: 2.56.0
Author: Darklg
Author URI: https://darklg.me/
Text Domain: wpubaseplugin
Domain Path: /lang
Requires at least: 6.2
Requires PHP: 8.0
License: MIT License
License URI: https://opensource.org/licenses/MIT
*/

class WPUBasePlugin {

    public $version = '2.56.0';

    private $utilities_classes = array(
        'messages' => array(
            'namespace' => 'messages_1_3_3',
            'name' => 'WPUBaseMessages'
        ),
        'admindatas' => array(
            'namespace' => 'admindatas_3_10_0',
            'name' => 'WPUBaseAdminDatas'
        ),
        'adminpage' => array(
            'namespace' => 'adminpage_1_5_1',
            'name' => 'WPUBaseAdminPage'
        ),
        'settings' => array(
            'namespace' => 'wpubasesettings_0_17_6',
            'name' => 'WPUBaseSettings'
        ),
        'cron' => array(
            'namespace' => 'wpubasecron_0_2_9',
            'name' => 'WPUBaseCron'
        ),
        'fields' => array(
            'namespace' => 'wpubasefields_0_16_1',
            'name' => 'WPUBaseFields'
        ),
        'update' => array(
            'namespace' => 'wpubaseupdate_0_4_4',
            'name' => 'WPUBaseUpdate'
        ),
        'email' => array(
            'namespace' => 'wpubaseemail_0_2_0',
            'name' => 'WPUBaseEmail'
        ),
        'toolbox' => array(
            'namespace' => 'wpubasetoolbox_0_2_0',
            'name' => 'WPUBaseToolbox'
        ),
        'filecache' => array(
            'namespace' => 'wpubasefilecache_0_1_1',
            'name' => 'WPUBaseFileCache'
        )
    );

    private $plugins = array(
        'wpuoptions' => array(
            'path' => 'wpuoptions/wpuoptions.php',
            'message_url' => '<a target="_blank" href="https://github.com/WordPressUtilities/wpuoptions">WPU Options</a>'
        )
    );

    public $tools = array();
    public $options = array();

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
            $this->plugins[$id]['activated'] = true;
            if (!file_exists(WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $plugin['path'])) {
                $this->plugins[$id]['installed'] = false;
                $this->tools['messages']->set_message($id . '__not_installed', sprintf(__('The plugin %s should be installed.', 'wpubaseplugin'), $plugin['message_url']), 'error');
                continue;
            }
            if (!is_plugin_active($plugin['path'])) {
                $this->plugins[$id]['activated'] = false;
                $this->tools['messages']->set_message($id . '__not_activated', sprintf(__('The plugin %s should be activated.', 'wpubaseplugin'), $plugin['message_url']), 'error');
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

        // Init fields
        $fields = array(
            'demo' => array(
                'required' => true,
                'group' => 'group_1',
                'label' => 'Demo'
            ),
            'demo_file' => array(
                'group' => 'group_1',
                'type'=> 'file',
                'label' => 'Demo file'
            ),
            'demo_image' => array(
                'group' => 'group_1',
                'type'=> 'image',
                'label' => 'Demo image'
            ),
            'demo_page' => array(
                'group' => 'group_page',
                'label' => 'Demo field for pages'
            ),
            'demo_email' => array(
                'column_start' => true,
                'group' => 'group_1',
                'label' => 'Demo Email',
                'type' => 'email',
                'placeholder' => 'Email'
            ),
            'demo_url' => array(
                'column_end' => true,
                'group' => 'group_1',
                'label' => 'Demo URL',
                'type' => 'url'
            ),
            'demo_number' => array(
                'group' => 'group_1',
                'label' => 'Demo number',
                'type' => 'number'
            ),
            'demo_color' => array(
                'group' => 'group_1',
                'label' => 'Demo color',
                'type' => 'color'
            ),
            'demo_page_item' => array(
                'group' => 'group_1',
                'label' => 'Demo page',
                'type' => 'page'
            ),
            'demo_post_item' => array(
                'group' => 'group_1',
                'label' => 'Demo post',
                'type' => 'post'
            ),
            'demo_textarea' => array(
                'group' => 'group_1',
                'label' => 'Demo textarea',
                'type' => 'textarea'
            ),
            'demo_editor' => array(
                'group' => 'group_1',
                'label' => 'Demo editor',
                'type' => 'editor',
                'editor_args' => array(
                    'textarea_rows' => 6
                )
            ),
            'demo_checkbox' => array(
                'group' => 'group_1',
                'label' => 'Demo checkbox',
                'type' => 'checkbox'
            ),
            'demo_checkbox2' => array(
                'group' => 'group_1',
                'label' => 'Demo checkbox',
                'type' => 'checkbox'
            ),
            'demo_checkbox_text' => array(
                'group' => 'group_1',
                'toggle-display' => array(
                    'demo_checkbox2' => 'checked',
                    'demo_checkbox' => 'checked'
                ),
                'label' => 'Demo checkbox text',
                'type' => 'text'
            ),
            'demo2' => array(
                'group' => 'group_2',
                'label' => 'Demo 2'
            ),
            'select_data' => array(
                'type' => 'select',
                'group' => 'group_2',
                'label' => 'Select with Data',
                'data' => array(
                    'value_1' => 'Value 1',
                    'value_2' => 'Value 2',
                )
            ),
            'select_nodata' => array(
                'type' => 'select',
                'group' => 'group_2',
                'label' => 'Select without Data'
            ),
            'radio_nodata' => array(
                'type' => 'radio',
                'group' => 'group_2',
                'label' => 'Radio without Data'
            ),
            'checkboxes_data' => array(
                'type' => 'checkboxes',
                'group' => 'group_2',
                'label' => 'Checkbox with Data',
                'data' => array(
                    'value_1' => 'Value 1',
                    'value_2' => 'Value 2',
                )
            ),
        );
        $field_groups = array(
            'group_1'  => array(
                'label' => 'Group 1'
            ),
            'group_2'  => array(
                'label' => 'Group 2'
            ),
            'group_page'  => array(
                'label' => 'Group Pages',
                'post_type' => 'page'
            )
        );
        $this->tools['fields']->init($fields, $field_groups);

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
