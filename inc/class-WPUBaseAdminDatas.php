<?php

/*
Class Name: WPU Base Messages
Description: A class to handle data display in WordPress admin
Version: 1.0.0
Author: Darklg
Author URI: http://darklg.me/
License: MIT License
License URI: http://opensource.org/licenses/MIT
*/

class WPUBaseAdminDatas
{
    function __construct() {
    }

    /* ----------------------------------------------------------
      Utilities : Requests
    ---------------------------------------------------------- */

    function get_pager_limit($perpage, $tablename = '') {
        global $wpdb;

        // Ensure good format for table name
        if (empty($tablename) || !preg_match('/^([A-Za-z0-9_-]+)$/', $tablename)) {
            return array(
                'pagenum' => 0,
                'max_pages' => 0,
                'limit' => '',
            );
        }

        // Ensure good format for perpage
        if (empty($perpage) || !is_numeric($perpage)) {
            $perpage = 20;
        }

        // Get number of elements in table
        $elements_count = $wpdb->get_var("SELECT COUNT(*) FROM " . $tablename);

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
            'limit' => $limit,
        );
    }

    /* ----------------------------------------------------------
      Utilities : Export
    ---------------------------------------------------------- */

    function export_array_to_csv($array, $name) {
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
}

