<?php

namespace admindatas_2_6_2;

/*
Class Name: WPU Base Admin Datas
Description: A class to handle datas in WordPress admin
Version: 2.6.2
Author: Darklg
Author URI: http://darklg.me/
License: MIT License
License URI: http://opensource.org/licenses/MIT
*/

class WPUBaseAdminDatas {

    public $default_perpage = 20;
    public $sql_option_name = false;

    public function __construct() {
    }

    public function init($settings = array()) {
        $this->apply_settings($settings);
        $this->check_database();
        add_action('admin_post_admindatas_' . $this->settings['plugin_id'], array(&$this,
            'delete_lines_postAction'
        ));
    }

    public function apply_settings($settings) {
        $default_settings = array(
            'plugin_id' => 'my_plugin',
            'table_name' => 'my_table',
            'table_fields' => array(
                'value_1' => array(
                    'public_name' => 'Value 1'
                ),
                'value_2' => array(
                    'public_name' => 'Value 2'
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
        // Remove creation column
        if (isset($settings['table_fields']['creation'])) {
            unset($settings['table_fields']['creation']);
        }

        // Build query
        foreach ($settings['table_fields'] as $id => $field) {
            if (!isset($field['public_name'])) {
                $settings['table_fields'][$id]['public_name'] = $id;
            }
            if (!isset($field['type'])) {
                $settings['table_fields'][$id]['type'] = isset($field['sql']) ? 'sql' : 'varchar';
            }
        }

        if (!isset($settings['handle_database'])) {
            $settings['handle_database'] = true;
        }

        $this->settings = $settings;

        $this->sql_option_name = $this->settings['plugin_id'] . '_' . $this->settings['table_name'] . '_version';
    }

    /* ----------------------------------------------------------
      Database Creation
    ---------------------------------------------------------- */

    public function drop_database() {
        global $wpdb;
        $tablename = $wpdb->prefix . $this->settings['table_name'];
        $wpdb->query("DROP TABLE IF EXISTS " . $tablename);
        delete_option($this->sql_option_name);
    }

    public function check_database() {
        if ($this->settings['handle_database']) {
            return;
        }
        global $wpdb;
        $tablename = $wpdb->prefix . $this->settings['table_name'];

        // Assemble fields
        $fields_query = array(
            'id mediumint(8) unsigned NOT NULL auto_increment',
            'creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
            'PRIMARY KEY (id)'
        );

        // Build query
        $sql_query = "CREATE TABLE " . $tablename;
        $sql_query .= " (\n" . implode(",\n", $fields_query) . "\n)";
        $sql_query .= " DEFAULT CHARSET=utf8;";

        // If query has changed since last time
        $sql_md5 = md5(serialize($this->settings['table_fields']));
        $sql_option_value = get_option($this->sql_option_name);
        if ($sql_md5 != $sql_option_value) {
            // Update or create table
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';

            // Create table
            maybe_create_table($tablename, $sql_query);

            foreach ($this->settings['table_fields'] as $column_name => $col) {
                switch ($col['type']) {
                case 'varchar':
                    $col_sql = 'varchar(100) DEFAULT NULL';
                    break;
                case 'timestamp':
                    $col_sql = 'TIMESTAMP';
                    break;
                default:
                    $col_sql = $col['sql'];
                }

                maybe_add_column($tablename, $column_name, 'ALTER TABLE ' . $tablename . ' ADD ' . $column_name . ' ' . $col_sql);
            }

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
      Delete lines
    ---------------------------------------------------------- */

    public function delete_lines_postAction() {
        if (!empty($_POST) && isset($_POST['select_line'], $_POST['page']) && is_array($_POST['select_line'])) {
            $action_id = 'action-main-form-admin-datas-' . $_POST['page'];
            if (isset($_POST[$action_id]) && wp_verify_nonce($_POST[$action_id], 'action-main-form-' . $_POST['page'])) {
                $this->delete_lines($_POST['select_line']);
            }
        }
        if (isset($_POST['page'])) {
            wp_redirect(admin_url('admin.php?page=' . esc_attr($_POST['page'])));
            die;
        }
    }

    public function delete_lines($lines = array()) {
        global $wpdb;
        $tablename = $wpdb->prefix . $this->settings['table_name'];
        $_lines = array();
        foreach ($lines as $line) {
            // Stop if a value is not valid
            if (!is_numeric($line)) {
                break;
            }
            $_lines[] = $line;
        }
        if (!empty($_lines)) {
            $wpdb->query(
                "DELETE FROM ${tablename} WHERE ID IN(" . implode(",", $_lines) . ");"
            );
        }
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

        // Add ID
        $default_columns = array(
            'creation' => 'Creation date',
            'id' => 'ID'
        );
        $base_columns = array();
        foreach ($this->settings['table_fields'] as $id => $field) {
            if (!isset($args['primary_column'])) {
                $args['primary_column'] = $id;
            }
            $base_columns[$id] = $field['public_name'];
        }
        $base_columns = $base_columns + $default_columns;

        // Default columns
        if (!isset($args['columns'])) {
            $args['columns'] = $base_columns;
        }

        // Filter results
        $where_glue = (isset($_GET['where_glue']) && in_array($_GET['where_glue'], array('AND', 'OR'))) ? $_GET['where_glue'] : 'AND';
        $where = array();
        $where_text = isset($_GET['where_text']) ? trim($_GET['where_text']) : '';
        if (!empty($where_text)) {
            $where_glue = 'OR';
            foreach ($args['columns'] as $id => $name) {
                if ($id != 'id' && $id != 'creation') {
                    $where[] = "$id LIKE '%" . esc_sql($where_text) . "%'";
                }
            }
        }

        // Order results
        if (!isset($args['order'])) {
            $args['order'] = isset($_GET['order']) && in_array($_GET['order'], array('asc', 'desc')) ? $_GET['order'] : 'asc';
        }

        if (!isset($args['orderby'])) {
            $args['orderby'] = isset($_GET['orderby']) && array_key_exists($_GET['orderby'], $base_columns) ? $_GET['orderby'] : 'id';
        }

        // Build filter query
        $sql_where = '';
        if (!empty($where)) {
            $sql_where .= " WHERE " . implode(" " . $where_glue . " ", $where) . " ";
        }

        // Build order
        $sql_order = ' ORDER BY ' . $args['orderby'] . ' ' . strtoupper($args['order']) . ' ';

        // Default pagenum & max pages
        if (!isset($args['pagenum']) || !isset($args['max_pages']) || !isset($args['limit']) || !isset($args['max_elements'])) {
            $pager = $this->get_pager_limit($args['perpage'], $sql_where);
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
            $values = $wpdb->get_results("SELECT " . implode(", ", array_keys($args['columns'])) . " FROM " . $tablename . " " . $sql_where . " " . $sql_order . " " . $args['limit']);
        }

        $screen = get_current_screen();
        $page_id = '';
        if (property_exists($screen, 'parent_base')) {
            $page_id = $screen->parent_base;
        }

        $url_items = array(
            'order' => $args['order'],
            'orderby' => $args['orderby'],
            'pagenum' => '%#%',
            'where_glue' => $where_glue,
            'where_text' => $where_text,
            'page' => $page_id
        );
        $page_links = paginate_links(array(
            'base' => add_query_arg($url_items),
            'format' => '',
            'prev_text' => '&laquo;',
            'next_text' => '&raquo;',
            'total' => $args['max_pages'],
            'current' => $args['pagenum']
        ));

        if ($page_links) {
            $start_element = ($args['pagenum'] - 1) * $args['perpage'] + 1;
            $end_element = min($args['pagenum'] * $args['perpage'], $args['max_elements']);
            $pagination = '<div style="margin:1em 0" class="tablenav">';
            $pagination .= '<div class="alignleft">' . sprintf(__('Items %s - %s', $this->settings['plugin_id']), $start_element, $end_element) . '</div>';
            $pagination .= '<div class="tablenav-pages alignright actions bulkactions">' . $page_links . '</div>';
            $pagination .= '<br class="clear" /></div>';
        }

        $search_form = '<form class="admindatas-search-form" action="' . admin_url("admin.php") . '" method="get"><p class="search-box">';
        $search_form .= '<input type="hidden" name="page" value="' . esc_attr($page_id) . '" />';
        $search_form .= '<input type="hidden" name="order" value="' . esc_attr($args['order']) . '" />';
        $search_form .= '<input type="hidden" name="orderby" value="' . esc_attr($args['orderby']) . '" />';
        $search_form .= '<input type="search" name="where_text" value="' . esc_attr($where_text) . '" />';
        ob_start();
        submit_button(__('Search'), '', 'submit', false);
        $search_form .= ob_get_clean();
        $search_form .= '</p><br class="clear" /></form><div class="clear"></div>';

        $has_id = is_object($values[0]) && isset($values[0]->id);

        $content = '<form action="' . admin_url('admin-post.php') . '" method="post">';
        $content .= '<input type="hidden" name="action" value="admindatas_' . $this->settings['plugin_id'] . '">';
        $content .= '<input type="hidden" name="page" value="' . esc_attr($page_id) . '" />';
        $content .= wp_nonce_field('action-main-form-' . $page_id, 'action-main-form-admin-datas-' . $page_id, true, false);

        $content .= '<table class="wp-list-table widefat fixed striped">';
        if (isset($args['columns']) && is_array($args['columns']) && !empty($args['columns'])) {
            $labels = '<tr>';
            if ($has_id) {
                $labels .= '<td class="manage-column column-cb check-column"><input type="checkbox" name="cb-select-all-%s" id="admindatas_sort_lines" value="" /></td>';
            }
            foreach ($args['columns'] as $id_col => $name_col) {
                $url_items_tmp = $url_items;
                $url_items_tmp['pagenum'] = 1;
                $url_items_tmp['orderby'] = $id_col;
                $url_items_tmp['order'] = $args['order'] == 'asc' ? 'desc' : 'asc';
                $sort_link = add_query_arg($url_items_tmp);
                $labels .= '<th class="sortable ' . ($id_col == $args['primary_column'] ? 'column-primary' : '') . ' ' . $args['order'] . ' ' . ($id_col == $args['orderby'] ? 'sorted' : '') . '"><a href="' . $sort_link . '"><span>' . $name_col . '</span><span class="sorting-indicator"></span></a></th>';
            }
            $labels .= '</tr>';
            $content .= '<thead>' . sprintf($labels, 1) . '</thead>';
            $content .= '<tfoot>' . sprintf($labels, 2) . '</tfoot>';
        }
        $content .= '<tbody id="the-list">';
        foreach ($values as $id => $vals) {
            $content .= '<tr>';
            if ($has_id) {
                $content .= '<th scope="row" class="check-column" class="column-cb check-column"><input type="checkbox" name="select_line[' . $vals->id . ']" value="' . $vals->id . '" /></th>';
            }
            foreach ($vals as $cell_id => $val) {
                $val = (empty($val) ? '&nbsp;' : $val);
                $content .= '<td class="' . ($cell_id == $args['primary_column'] ? 'column-primary' : '') . '">' . apply_filters('wpubaseadmindatas_cellcontent', $val, $cell_id, $this->settings) . '</td>';
            }
            $content .= '</tr>';
        }
        $content .= '</tbody>';
        $content .= '</table>';
        if ($has_id) {
            $content .= '<p class="admindatas-delete-button">' . get_submit_button(__('Delete'), 'delete', 'delete_lines', false) . '</p>';
        }
        $content .= '</form>';
        $content .= $search_form;
        $content .= $pagination;
        $content .= <<<HTML
<style>
.admindatas-search-form {margin:1em 0;}
@media (min-width:768px) {
    .admindatas-delete-button {float: left;}
}
@media (max-width:767px) {
    .admindatas-search-form .search-box {position: relative!important;height:auto;margin:0;}
}
</style>
HTML;
        return $content;
    }
}
