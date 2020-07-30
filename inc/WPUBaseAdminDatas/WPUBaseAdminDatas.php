<?php
namespace admindatas_3_5_1;

/*
Class Name: WPU Base Admin Datas
Description: A class to handle datas in WordPress admin
Version: 3.5.1
Author: Darklg
Author URI: http://darklg.me/
License: MIT License
License URI: http://opensource.org/licenses/MIT
*/

class WPUBaseAdminDatas {

    public $default_perpage = 20;
    public $sql_option_name = false;
    public $pagename;
    public $tablename;
    public $user_level = 'edit_posts';
    public $field_types = array(
        'text',
        'url',
        'textarea',
        'date',
        'number',
        'email'
    );

    public function __construct() {}

    public function init($settings = array()) {
        $this->apply_settings($settings);
        $this->check_database();
        add_action('admin_post_admindatas_' . $this->settings['plugin_id'], array(&$this,
            'delete_lines_postAction'
        ));
        if ($this->settings['can_edit']) {
            add_action('admin_post_admindatas_edit_' . $this->settings['plugin_id'], array(&$this,
                'edit_line_postAction'
            ));
        }
        if ($this->settings['can_create']) {
            add_action('admin_post_admindatas_create_' . $this->settings['plugin_id'], array(&$this,
                'create_line_postAction'
            ));
        }
    }

    public function apply_settings($settings) {
        $default_settings = array(
            'plugin_id' => 'my_plugin',
            'admin_url' => 'admin.php',
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
                $settings['table_fields'][$id]['type'] = isset($field['type']) ? $field['type'] : 'varchar';
            }
            if (!isset($field['field_type']) || !in_array($field['field_type'], $this->field_types)) {
                $settings['table_fields'][$id]['field_type'] = 'text';
            }
            if (!isset($field['edit'])) {
                $settings['table_fields'][$id]['edit'] = false;
            }
            if (!isset($field['create'])) {
                $settings['table_fields'][$id]['create'] = false;
            }
        }

        if (!isset($settings['user_level'])) {
            $settings['user_level'] = $this->user_level;
        }

        if (!isset($settings['handle_database'])) {
            $settings['handle_database'] = true;
        }

        if (!isset($settings['can_create']) || !current_user_can($settings['user_level'])) {
            $settings['can_create'] = false;
        }

        if (!isset($settings['can_edit']) || !current_user_can($settings['user_level'])) {
            $settings['can_edit'] = $settings['can_create'];
        }

        $this->settings = $settings;

        $this->user_level = $settings['user_level'];
        $this->pageid = $this->settings['plugin_id'];
        if (isset($this->settings['plugin_pageid'])) {
            $this->pageid = $this->settings['plugin_pageid'];
        }
        $this->pagename = admin_url($this->settings['admin_url'] . '?page=' . $this->pageid);
        $this->sql_option_name = $this->settings['plugin_id'] . '_' . $this->settings['table_name'] . '_version';

        global $wpdb;
        $this->tablename = $wpdb->prefix . $this->settings['table_name'];
    }

    /* ----------------------------------------------------------
      Database Creation
    ---------------------------------------------------------- */

    public function drop_database() {
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS " . $this->tablename);
        delete_option($this->sql_option_name);
    }

    public function check_database() {
        if ($this->settings['handle_database']) {
            return;
        }
        global $wpdb;

        // Assemble fields
        $fields_query = array(
            'id mediumint(8) unsigned NOT NULL auto_increment',
            'creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
            'PRIMARY KEY (id)'
        );

        // Build query
        /* Two spaces to avoid temporary table WP fix */
        $sql_query = "CREATE  TABLE " . $this->tablename;
        $sql_query .= " (\n" . implode(",\n", $fields_query) . "\n)";
        $sql_query .= " DEFAULT CHARSET=utf8;";

        // If query has changed since last time
        $sql_md5 = md5(serialize($this->settings['table_fields']));
        $sql_option_value = get_option($this->sql_option_name);
        if ($sql_md5 != $sql_option_value) {
            // Update or create table
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';

            // Create table
            maybe_create_table($this->tablename, $sql_query);

            foreach ($this->settings['table_fields'] as $column_name => $col) {
                switch ($col['type']) {
                case 'varchar':
                    $col_sql = 'varchar(100) DEFAULT NULL';
                    break;
                case 'number':
                    $col_sql = 'MEDIUMINT UNSIGNED';
                    break;
                case 'timestamp':
                    $col_sql = 'TIMESTAMP';
                    break;
                default:
                    $col_sql = $col['sql'];
                }

                maybe_add_column($this->tablename, $column_name, 'ALTER TABLE ' . $this->tablename . ' ADD ' . $column_name . ' ' . $col_sql);
            }

            // Update option hash
            update_option($this->sql_option_name, $sql_md5);
        }
    }

    /* ----------------------------------------------------------
      Utilities : Requests
    ---------------------------------------------------------- */

    public function get_pager_limit($perpage = false, $req_details = '') {
        global $wpdb;

        // Ensure good format for table name
        if (empty($this->tablename) || !preg_match('/^([A-Za-z0-9_-]+)$/', $this->tablename)) {
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
        $elements_count = $wpdb->get_var("SELECT COUNT(*) FROM " . $this->tablename . $req_details);

        // Get max page number
        $max_pages = ceil($elements_count / $perpage);

        // Obtain Page Number
        $pagenum = (isset($_GET['pagenum']) && is_numeric($_GET['pagenum']) ? $_GET['pagenum'] : 1);
        $pagenum = min($pagenum, $max_pages);

        // Set SQL limit
        $limit_min = max(0, ($pagenum * $perpage - $perpage));
        $limit = 'LIMIT ' . $limit_min . ', ' . $perpage;

        return array(
            'pagenum' => $pagenum,
            'max_pages' => $max_pages,
            'max_elements' => $elements_count,
            'limit' => $limit
        );
    }

    /* ----------------------------------------------------------
      Lines
    ---------------------------------------------------------- */

    public function get_line($line_id) {
        return $this->get_line_by('id', $line_id, '%d');
    }

    public function get_line_by($field, $value = '', $value_type = '%s') {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $this->tablename . " WHERE " . $field . "=" . $value_type, $value), ARRAY_A);
    }

    /* ----------------------------------------------------------
      Create line
    ---------------------------------------------------------- */

    public function create_line_postAction() {
        $_return_value = false;
        if (current_user_can($this->user_level) && !empty($_POST) && isset($_POST['admindatas_fields'], $_POST['page']) && is_array($_POST['admindatas_fields'])) {
            $action_id = 'action-create-form-admin-datas-' . $_POST['page'];
            if (isset($_POST[$action_id]) && wp_verify_nonce($_POST[$action_id], 'action-create-form-' . $_POST['page'])) {
                $_return_value = $this->create_line($_POST['admindatas_fields']);
            }
        }
        if (isset($_POST['page']) && is_numeric($_return_value)) {
            $_back_url = $this->pagename . '&item_id=' . $_return_value;
            if (isset($_POST['backquery'])) {
                $_back_url = add_query_arg(array('backquery' => $_POST['backquery']), $_back_url);
            }
            wp_redirect($_back_url);
            die;
        }
    }

    public function create_line($datas) {
        $_datas_create = array();
        $_return_value = false;
        foreach ($this->settings['table_fields'] as $id => $field) {
            if (!isset($datas[$id])) {
                continue;
            }
            /* Invalid field */
            if (!$this->validate_field_value($field, $datas[$id])) {
                continue;
            }
            $_datas_create[$id] = $datas[$id];
        }
        if (!empty($_datas_create)) {
            global $wpdb;
            $_return_value = $wpdb->insert(
                $this->tablename,
                $_datas_create
            );
            if ($wpdb->last_error !== '') {
                $wpdb->print_error();
            }
        }
        if ($_return_value) {
            $_return_value = $wpdb->insert_id;
        }
        return $_return_value;
    }

    /* ----------------------------------------------------------
      Edit line
    ---------------------------------------------------------- */

    public function edit_line_postAction() {
        if (current_user_can($this->user_level) && !empty($_POST) && isset($_POST['edit_line'], $_POST['admindatas_fields'], $_POST['page']) && is_numeric($_POST['edit_line']) && is_array($_POST['admindatas_fields'])) {
            $action_id = 'action-edit-form-admin-datas-' . $_POST['page'];
            if (isset($_POST[$action_id]) && wp_verify_nonce($_POST[$action_id], 'action-edit-form-' . $_POST['page'])) {
                $this->edit_line($_POST['edit_line'], $_POST['admindatas_fields']);
            }
        }
        if (isset($_POST['page'], $_POST['edit_line']) && is_numeric($_POST['edit_line'])) {
            $_back_url = $this->pagename . '&item_id=' . $_POST['edit_line'];
            if (isset($_POST['backquery'])) {
                $_back_url = add_query_arg(array('backquery' => $_POST['backquery']), $_back_url);
            }
            wp_redirect($_back_url);
            die;
        }
    }

    public function edit_line($item_id, $new_datas) {
        $_datas = $this->get_line($item_id);
        $_datas_update = array();
        foreach ($new_datas as $key => $var) {
            /* Same value */
            if ($_datas[$key] == $var) {
                continue;
            }
            /* Fake field */
            if (!isset($this->settings['table_fields'][$key])) {
                continue;
            }
            /* Invalid field */
            if (!$this->validate_field_value($this->settings['table_fields'][$key], $var)) {
                continue;
            }
            $_datas_update[$key] = $var;
        }

        if (!empty($_datas_update)) {
            global $wpdb;
            $wpdb->update(
                $this->tablename,
                $_datas_update,
                array('ID' => $item_id)
            );
        }
    }

    /* ----------------------------------------------------------
      Validate
    ---------------------------------------------------------- */

    public function validate_field_value($field, $value) {
        switch ($field['field_type']) {
        case 'email':
            return !!filter_var($value, FILTER_VALIDATE_EMAIL);
            break;

        case 'url':
            return !!filter_var($value, FILTER_VALIDATE_URL);
            break;

        case 'number':
            return is_numeric($value);
            break;

        default:
            return true;
        }

    }

    /* ----------------------------------------------------------
      Delete lines
    ---------------------------------------------------------- */

    public function delete_lines_postAction() {
        if (current_user_can($this->user_level) && !empty($_POST) && isset($_POST['select_line'], $_POST['page']) && is_array($_POST['select_line'])) {
            $action_id = 'action-main-form-admin-datas-' . $_POST['page'];
            if (isset($_POST[$action_id]) && wp_verify_nonce($_POST[$action_id], 'action-main-form-' . $_POST['page'])) {
                $this->delete_lines($_POST['select_line']);
            }
        }
        if (isset($_POST['page'])) {
            wp_redirect($this->pagename);
            die;
        }
    }

    public function delete_lines($lines = array()) {
        $_lines = array();
        foreach ($lines as $line) {
            // Stop if a line is not valid
            if (!is_numeric($line)) {
                break;
            }
            $_lines[] = $line;
        }
        if (!empty($_lines)) {
            global $wpdb;
            $wpdb->query(
                "DELETE FROM " . $this->tablename . " WHERE ID IN(" . implode(",", $_lines) . ");"
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

    public function get_admin_view($values = array(), $args = array()) {
        if (isset($_GET['item_id']) && is_numeric($_GET['item_id'])) {
            $_content = $this->get_admin_item($_GET['item_id']);
        } elseif (isset($_GET['create'])) {
            $_content = $this->get_admin_item();
        } else {
            $args['is_admin_view'] = true;
            $_content = $this->get_admin_table($values, $args);
        }

        return $_content;
    }

    public function get_admin_item($item_id = false) {
        $_html = '';

        $_back_query = '';
        $_back_url_args = array();
        $page_id = $this->get_page_id();
        $datas = $item_id ? $this->get_line($item_id) : array();

        /* Save back query if valid */
        if (isset($_GET['backquery'])) {
            $_back_args = json_decode(base64_decode($_GET['backquery']));
            if (is_object($_back_args)) {
                $_back_url_args = (array) $_back_args;
                $_back_query = $_GET['backquery'];
            }
        }

        /* Diff between edit & create form */
        $_has_form = ($this->settings['can_edit'] && $item_id) || ($this->settings['can_create'] && !$item_id);

        /* Actions */
        $_form_action = $item_id ? 'admindatas_edit_' : 'admindatas_create_';
        $_form_nonce = $item_id ? 'action-edit-form-' : 'action-create-form-';

        if ($_has_form) {
            $_html .= '<form action="' . admin_url('admin-post.php') . '" method="post">';
            $_html .= '<input type="hidden" name="action" value="' . $_form_action . $this->settings['plugin_id'] . '">';
            $_html .= '<input type="hidden" name="page" value="' . esc_attr($page_id) . '" />';
            if ($_back_query) {
                $_html .= '<input type="hidden" name="backquery" value="' . esc_attr($_back_query) . '" />';
            }
            if ($item_id) {
                $_html .= '<input type="hidden" name="edit_line" value="' . esc_attr($item_id) . '" />';
            }
            $_html .= wp_nonce_field($_form_nonce . $page_id, $_form_nonce . 'admin-datas-' . $page_id, true, false);
        }

        if ($item_id) {
            $_html .= '<h3>#' . $item_id . '</h3>';
        } else {
            $_html .= '<h3>' . __('New Post') . '</h3>';
        }
        $_html .= '<a href="' . add_query_arg($_back_url_args, $this->pagename) . '">' . __('Back') . '</a>';

        $_html .= '<table class="form-table"><tbody>';
        foreach ($this->settings['table_fields'] as $id => $field) {
            $value = isset($datas[$id]) ? htmlspecialchars($datas[$id], ENT_QUOTES, "UTF-8") : '';

            $_fieldId = 'admindatas_fields_' . $id;
            $_html .= '<tr>';
            $_html .= '<th scope="row"><label for="' . $_fieldId . '">' . $field['public_name'] . ' :</label></th>';
            $_html .= '<td>';
            if ($_has_form) {
                switch ($field['field_type']) {
                case 'textarea':
                    $_html .= '<textarea rows="5" cols="30" id="' . $_fieldId . '" name="admindatas_fields[' . $id . ']">' . $value . '</textarea>';
                    break;
                default:
                    $_html .= '<input type="' . $field['field_type'] . '" id="' . $_fieldId . '" name="admindatas_fields[' . $id . ']" value="' . $value . '" />';
                }
            } else {
                $_html .= '<span>' . esc_html($value) . '</span>';
            }
            $_html .= '</td>';
            $_html .= '</tr>';
        }
        $_html .= '</tbody></table>';

        if ($_has_form) {
            $_html .= get_submit_button(__('Submit'), '', 'submit', false);
            $_html .= '</form>';
        }

        return $_html;
    }

    public function get_admin_table($values = array(), $args = array()) {
        global $wpdb;

        $pagination = '';

        $is_admin_view = isset($args['is_admin_view']) && $args['is_admin_view'];

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
        $args['primary_column'] = 'id';
        if (isset($args['columns']) && is_array($args['columns'])) {
            foreach ($args['columns'] as $id => $field) {
                if (!isset($args['primary_column'])) {
                    $args['primary_column'] = $id;
                }
                $base_columns[$id] = $field;
            }
        }
        $base_columns = array_merge($base_columns, $default_columns);

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
            $args['order'] = isset($_GET['order']) && in_array($_GET['order'], array('asc', 'desc')) ? $_GET['order'] : 'desc';
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
        $total_nb = 0;
        if (empty($values) || !is_array($values)) {
            $columns = array_keys($args['columns']);
            $query = "SELECT " . implode(", ", $columns) . " FROM " . $this->tablename . " " . $sql_where . " " . $sql_order . " " . $args['limit'];
            $query_total = "SELECT count(" . $columns[0] . ")  FROM " . $this->tablename . " " . $sql_where;
            $values = $wpdb->get_results($query);
            $total_nb = $wpdb->get_var($query_total);
        }

        $page_id = $this->get_page_id();

        $url_items_clear = array(
            'order' => $args['order'],
            'orderby' => $args['orderby'],
            'page' => $page_id
        );
        $url_items = $url_items_clear;
        $url_items['pagenum'] = '%#%';
        $url_items['where_glue'] = $where_glue;
        $url_items['where_text'] = $where_text;

        /* Back query used in single page */
        $url_items_edit = $url_items;
        unset($url_items_edit['pagenum']);
        $_back_query = base64_encode(json_encode($url_items_edit));

        $page_links = paginate_links(array(
            'base' => add_query_arg($url_items),
            'format' => '',
            'prev_text' => '&laquo;',
            'next_text' => '&raquo;',
            'total' => $args['max_pages'],
            'current' => $args['pagenum']
        ));

        $start_element = max(0, ($args['pagenum'] - 1) * $args['perpage'] + 1);
        $end_element = min($args['pagenum'] * $args['perpage'], $args['max_elements']);
        $pagination = '<div style="margin:1em 0" class="tablenav">';
        $pagination .= '<div class="alignleft">';
        $pagination .= sprintf(__('Items %s - %s', $this->settings['plugin_id']), $start_element, $end_element);
        if ($total_nb) {
            $pagination .= ' / ' . $total_nb;
        }
        $pagination .= '</div>';
        if ($page_links) {
            $pagination .= '<div class="tablenav-pages alignright actions bulkactions">' . $page_links . '</div>';
        }
        $pagination .= '<br class="clear" /></div>';

        $search_form = '<form class="admindatas-search-form" action="' . $this->pagename . '" method="get"><p class="search-box">';
        $search_form .= '<input type="hidden" name="page" value="' . esc_attr($page_id) . '" />';
        $search_form .= '<input type="hidden" name="order" value="' . esc_attr($args['order']) . '" />';
        $search_form .= '<input type="hidden" name="orderby" value="' . esc_attr($args['orderby']) . '" />';
        $search_form .= '<input type="search" name="where_text" value="' . esc_attr($where_text) . '" />';
        $search_form .= get_submit_button(__('Search'), '', 'submit', false);
        if ($where_text) {
            $search_form .= '<br /><small><a href="' . add_query_arg($url_items_clear, $this->pagename) . '">' . __('Clear') . '</a></small>';
        }
        $search_form .= '</p><br class="clear" /></form><div class="clear"></div>';

        $has_id = isset($values[0]) && is_object($values[0]) && isset($values[0]->id);

        $content = '<form action="' . admin_url('admin-post.php') . '" method="post">';
        $content .= '<input type="hidden" name="action" value="admindatas_' . $this->settings['plugin_id'] . '">';
        $content .= '<input type="hidden" name="page" value="' . esc_attr($page_id) . '" />';
        $content .= wp_nonce_field('action-main-form-' . $page_id, 'action-main-form-admin-datas-' . $page_id, true, false);
        if ($has_id && $is_admin_view && $this->settings['can_create']) {
            $new_url = add_query_arg(array('backquery' => $_back_query), $this->pagename . '&create=1');
            $content .= '<p><a class="page-title-action" href="' . $new_url . '">' . __('New Post') . '</a></p>';
        }
        $content .= '<table class="wp-list-table widefat striped">';
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
            if ($has_id && $is_admin_view) {
                $labels .= '<th></th>';
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
                $content .= '<td data-colname="' . esc_attr($args['columns'][$cell_id]) . '" class="' . ($cell_id == $args['primary_column'] ? 'column-primary' : '') . '">';
                $content .= apply_filters('wpubaseadmindatas_cellcontent', $val, $cell_id, $this->settings);
                if ($cell_id == $args['primary_column']) {
                    $content .= '<button type="button" class="toggle-row"><span class="screen-reader-text">' . __('Show more details') . '</span></button>';
                }
                $content .= '</td>';
            }
            if ($has_id && $is_admin_view) {
                $edit_url = add_query_arg(array('backquery' => $_back_query), $this->pagename . '&item_id=' . $vals->id);
                $content .= '<td><a href="' . $edit_url . '">' . ($this->settings['can_edit'] ? __('Edit') : __('View')) . '</a></td>';
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

/* ----------------------------------------------------------
      Helpers
    ---------------------------------------------------------- */

    public function get_page_id() {
        $screen = get_current_screen();
        $page_id = '';
        if (property_exists($screen, 'parent_base')) {
            $page_id = $screen->parent_base;
        }
        return $page_id;
    }
}
