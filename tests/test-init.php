<?php

class WPUBasePlugin_Init extends WP_UnitTestCase
{

    public $demo_plugin;

    function setUp() {
        parent::setUp();
        $this->demo_plugin = new WPUBasePlugin;
    }

    function test_init_plugin() {
        // Simulate WordPress init
        do_action('init');
        $this->assertEquals(10, has_action('init', array(
            $this->demo_plugin,
            'init'
        )));
    }
}
