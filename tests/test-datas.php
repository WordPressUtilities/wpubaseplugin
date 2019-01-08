<?php

class WPUBasePlugin_Datas extends WP_UnitTestCase {

    public $demo_plugin;
    public $plugin_key;
    public $plugin_user = 'WordPressUtilities';
    public $plugin_id = 'wpubaseplugin';
    public $table_name;
    public $fields = array(
        'email' => array(
            'public_name' => 'Email',
            'field_type' => 'email',
            'edit' => true,
            'create' => true
        ),
        'first_name' => array(
            'public_name' => 'First name',
            'edit' => true,
            'create' => true
        ),
        'last_name' => array(
            'public_name' => 'Name',
            'edit' => true,
            'create' => true
        ),
        'phone_number' => array(
            'public_name' => 'Phone',
            'field_type' => 'textarea',
            'edit' => true,
            'create' => true
        ),
        'age' => array(
            'public_name' => 'Age',
            'field_type' => 'number',
            'type' => 'number',
            'edit' => true,
            'create' => true
        )

    );

    public function setUp() {
        parent::setUp();
        $this->demo_plugin = new WPUBasePlugin;
        $this->plugin_key = 'mydatatable' . time();
        global $wpdb;
        $this->table_name = $wpdb->prefix . $this->plugin_key;
    }

    public function tearDown() {
        parent::tearDown();

        /* Drop table */
        $this->demo_plugin->tools['admindatas']->drop_database();
    }

    // Test datas features
    public function test_datas() {
        do_action('init');

        /* Is loaded */
        $tools = $this->demo_plugin->tools;
        $this->assertArrayHasKey('admindatas', $tools);
        $plugin = $tools['admindatas'];

        /* Init */
        $plugin->init(array(
            'handle_database' => false,
            'plugin_id' => $this->plugin_key,
            'plugin_pageid' => $this->plugin_key . '-main',
            'table_fields' => $this->fields,
            'table_name' => $this->plugin_key
        ));

        global $wpdb;

        /* Test Table Creation */
        $results = $wpdb->get_results($wpdb->prepare("SHOW TABLES LIKE %s", $this->table_name));
        $this->assertEquals(1, count($results));

        /* CREATION */
        /* - Valid */
        $insert = $plugin->create_line(array(
            'email' => 'test@yopmail.com',
            'first_name' => 'azerty',
            'last_name' => 'uiop',
            'phone_number' => '+33610101010',
            'age' => '99'
        ));
        $this->assertEquals(1, $insert);
        $edit_number = $wpdb->get_var("SELECT age FROM $this->table_name WHERE id = 1");
        $this->assertEquals(99, $edit_number);

        /* - Invalid */
        $insert = $plugin->create_line(array(
            /* Bad email */
            'email' => 'testyopmail.com'
        ));
        $this->assertFalse($insert);
        $insert = $plugin->create_line(array(
            /* Bad age */
            'age' => 'az'
        ));
        $this->assertFalse($insert);

        /* EDITION */
        /* - Valid */
        $new_email = 'test2@yopmail.com';
        $plugin->edit_line(1, array(
            'email' => $new_email
        ));
        $edit_email = $wpdb->get_var("SELECT email FROM $this->table_name WHERE id = 1");
        $this->assertEquals($new_email, $edit_email);

        /* - Invalid */
        $new_email_inv = 'test2yopmail.com';
        $plugin->edit_line(1, array(
            'email' => $new_email
        ));
        $edit_email = $wpdb->get_var("SELECT email FROM $this->table_name WHERE id = 1");
        $this->assertEquals($new_email, $edit_email);

        /* DELETION */
        $plugin->delete_lines(array(1));
        $row_deletion = $wpdb->get_row("SELECT * FROM $this->table_name WHERE id = 1");
        $this->assertNull($row_deletion);


    }

}
