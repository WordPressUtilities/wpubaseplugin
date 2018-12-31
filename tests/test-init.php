<?php

class WPUBasePlugin_Init extends WP_UnitTestCase {

    public $demo_plugin;

    public function setUp() {
        parent::setUp();
        $this->demo_plugin = new WPUBasePlugin;
    }

    // Simulate WordPress init
    public function test_init_plugin() {
        do_action('init');
        $this->assertEquals(10, has_action('init', array(
            $this->demo_plugin,
            'init'
        )));
    }
}
