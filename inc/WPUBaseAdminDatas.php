<?php

namespace admindatas_2_0_1;

/*
Class Name: WPU Base Admin Datas
Description: A class to handle datas in WordPress admin
Version: 2.0.1
Author: Darklg
Author URI: http://darklg.me/
License: MIT License
License URI: http://opensource.org/licenses/MIT
*/

class WPUBaseAdminDatas {

    public $default_perpage = 20;

    public function __construct() {
    }

    public function init($settings = array()) {
        $this->apply_settings($settings);
        $this->check_database();
    }

    public function apply_settings($settings) {
        $default_settings = array(
            'plugin_id' => 'my_plugin',
            'table_name' => 'my_table',
            'table_fields' => array(
                'creation' => array(
                    'name' => 'date',
                    'sql' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'
                ),
                'value' => array(
                    'name' => 'Value',
                    'sql' => 'varchar(100) DEFAULT NULL'
                )
            )
        );
        if (!is_array($settings)) {
            $settings = $default_settings;
        }
        foreach ($default_settings as $key => $val) {
            if (!isset($settings[$key])) {
                $settings[$key] = $val;
            }
        }

        // Remove id column
        if (isset($settings['table_fields']['id'])) {
            unset($settings['table_fields']['id']);
        }

        $this->settings = $settings;
    }

    /* ----------------------------------------------------------
      Database Creation
    ---------------------------------------------------------- */

    public function check_database() {
        global $wpdb;
        $tablename = $wpdb->prefix . $this->settings['table_name'];

        // Assemble fields
        $fields_query = array(
            'id mediumint(8) unsigned NOT NULL auto_increment'
        );
        foreach ($this->settings['table_fields'] as $id => $field) {
            $fields_query[] = $id . ' ' . $field['sql'];
        }
        $fields_query[] = 'PRIMARY KEY  (id)';

        // Build query
        $sql_query = "CREATE TABLE " . $tablename;
        $sql_query .= " (\n" . implode(",\n", $fields_query) . "\n)";
        $sql_query .= " DEFAULT CHARSET=utf8;";

        // If query has changed since last time
        $sql_option_name = $this->settings['plugin_id'] . '_' . $this->settings['table_name'] . '_version';
        $sql_md5 = md5($sql_query);
        $sql_option_value = get_option($sql_option_name);
        if ($sql_md5 != $sql_option_value) {
            // Update or create table
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            $delt = dbDelta($sql_query);

            // Update option hash
            update_option($sql_option_name, $sql_md5);
        }
    }

    /* ----------------------------------------------------------
      Utilities : Requests
    ---------------------------------------------------------- */

    public function get_pager_limit($perpage = false, $req_details = '') {
        global $wpdb;
        $tablename = $wpdb->prefix . $this->settings['table_name'];

        // Ensure good format for table name
        if (empty($tablename) || !preg_match('/^([A-Za-z0-9_-]+)$/', $tablename)) {
            return array(
                'pagenum' => 0,
                'max_pages' => 0,
                'limit' => ''
            );
        }

        // Ensure good format for perpage
        if (empty($perpage) || !is_numeric($perpage)) {
            $perpage = $this->default_perpage;
        }

        // Get number of elements in table
        $elements_count = $wpdb->get_var("SELECT COUNT(*) FROM " . $tablename . $req_details);

        // Get max page number
        $max_pages = ceil($elements_count / $perpage);

        // Obtain Page Number
        $pagenum = (isset($_GET['pagenum']) && is_numeric($_GET['pagenum']) ? $_GET['pagenum'] : 1);
        $pagenum = min($pagenum, $max_pages);

        // Set SQL limit
        $limit = 'LIMIT ' . ($pagenum * $perpage - $perpage) . ', ' . $perpage;

        return array(
            'pagenum' => $pagenum,
            'max_pages' => $max_pages,
            'max_elements' => $elements_count,
            'limit' => $limit
        );
    }

    /* ----------------------------------------------------------
      Utilities : Export
    ---------------------------------------------------------- */

    public function export_array_to_csv($array, $name) {
        if (isset($array[0])) {
            header('Content-Type: application/csv');
            header('Content-Disposition: attachment; filename=export-list-' . $name . '-' . date('y-m-d') . '.csv');
            header('Pragma: no-cache');
            echo implode(';', array_keys($array[0])) . "\n";
            foreach ($array as $line) {
                echo implode(';', $line) . "\n";
            }
            die;
        }
    }

    /* ----------------------------------------------------------
      Utilities : Display
    ---------------------------------------------------------- */

    public function get_admin_table($values = array(), $args = array()) {
        global $wpdb;

        $pagination = '';
        $tablename = $wpdb->prefix . $this->settings['table_name'];

        if (!is_array($args)) {
            $args = array();
        }

        // Per page
        if (!isset($args['perpage']) || !is_numeric($args['perpage'])) {
            $args['perpage'] = $this->default_perpage;
        }

        // Default columns
        if (!isset($args['columns'])) {
            // Add ID
            $args['columns'] = array('id' => 'ID');
            foreach ($this->settings['table_fields'] as $id => $field) {
                $args['columns'][$id] = $field['name'];
            }
        }

        // Default pagenum & max pages
        if (!isset($args['pagenum']) || !isset($args['max_pages']) || !isset($args['limit']) || !isset($args['max_elements'])) {
            $pager = $this->get_pager_limit($args['perpage']);
            if (!isset($args['pagenum'])) {
                $args['pagenum'] = $pager['pagenum'];
            }
            if (!isset($args['max_pages'])) {
                $args['max_pages'] = $pager['max_pages'];
            }
            if (!isset($args['limit'])) {
                $args['limit'] = $pager['limit'];
            }
            if (!isset($args['max_elements'])) {
                $args['max_elements'] = $pager['max_elements'];
            }
        }

        // Default list
        if (empty($values) || !is_array($values)) {
            $values = $wpdb->get_results("SELECT " . implode(", ", array_keys($args['columns'])) . " FROM " . $tablename . " " . $args['limit']);
        }

        $page_links = paginate_links(array(
            'base' => add_query_arg('pagenum', '%#%'),
            'format' => '',
            'prev_text' => '&laquo;',
            'next_text' => '&raquo;',
            'total' => $args['max_pages'],
            'current' => $args['pagenum']
        ));

        if ($page_links) {
            $start_element = ($args['pagenum'] - 1) * $args['perpage'] + 1;
            $end_element = min($args['pagenum'] * $args['perpage'], $args['max_elements']);
            $pagination = '<div style="margin: 1em 0" class="tablenav">';
            $pagination .= '<div class="alignleft">' . sprintf(__('Items %s - %s'), $start_element, $end_element) . '</div>';
            $pagination .= '<div class="tablenav-pages alignright actions bulkactions">' . $page_links . '</div>';
            $pagination .= '<br class="clear" /></div>';
        }

        $content = '<table class="wp-list-table widefat fixed striped">';
        if (isset($args['columns']) && is_array($args['columns']) && !empty($args['columns'])) {
            $labels = '<tr><th>' . implode('</th><th>', $args['columns']) . '</th></tr>';
            $content .= '<thead>' . $labels . '</thead>';
            $content .= '<tfoot>' . $labels . '</tfoot>';
        }
        $content .= '<tbody id="the-list">';
        foreach ($values as $id => $vals) {
            $content .= '<tr>';
            foreach ($vals as $val) {
                $content .= '<td>' . (empty($val) ? '&nbsp;' : $val) . '</td>';
            }
            $content .= '</tr>';
        }
        $content .= '</tbody>';
        $content .= '</table>';
        $content .= $pagination;

        return $content;
    }
}

/*
 * Init class :
 * $WPUBaseAdminDatas = new WPUBaseAdminDatas();
 * $WPUBaseAdminDatas->init(array(
 *     'plugin_id' => 'my_plugin',
 *     'table_name' => 'my_table',
 *     'table_fields' => array(
 *         'creation' => array(
 *             'name' => 'date',
 *             'sql' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'
 *         ),
 *         'value' => array(
 *             'name' => 'Value',
 *             'sql' => 'varchar(100) DEFAULT NULL'
 *         )
 *     )
 * ));
 *
 *
 * Display table :
 * - Default :
 * echo $WPUBaseAdminDatas->get_admin_table();
 *
 * - Advanced :
 * $array_values = false; ($array_values are automatically retrieved if not a valid array)
 * echo $WPUBaseAdminDatas->get_admin_table(
 *     $array_values,
 *     array(
 *         'perpage' => 10,
 *         'columns' => array('creation' => 'Creation date')
 *     )
 * );
 */

/*
 * Todo :
 ** uninstall
 ** Default field type ( date => timestamp, text => varchar(100) )
 */
