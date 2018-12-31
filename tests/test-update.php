<?php

class WPUBasePlugin_Update extends WP_UnitTestCase {

    public $demo_plugin;
    public $plugin_user = 'WordPressUtilities';
    public $plugin_id = 'wpubaseplugin';

    public function setUp() {
        parent::setUp();
        $this->demo_plugin = new WPUBasePlugin;
    }

    // Test update features
    public function test_update() {
        do_action('init');

        /* Is loaded */
        $tools = $this->demo_plugin->tools;
        $this->assertArrayHasKey('update', $tools);

        /* Reinit */
        $tools['update']->init($this->plugin_user, $this->plugin_id, $this->demo_plugin->version);

        /* Tags test */
        $tags_info = $tools['update']->get_plugin_update_info();
        $this->assertArrayHasKey(1, $tags_info);
        $this->assertInternalType('object', $tags_info[1]);
        $this->assertContains($this->plugin_id . "/zipball", $tags_info[1]->zipball_url);

        /* Commits tests */
        $commits_info = $tools['update']->get_plugin_commits_info();
        $this->assertArrayHasKey(0, $commits_info);
        $this->assertContains('@', $commits_info[0]->commit->author->email);

        /* Force an older version */
        $tools['update']->current_version = $tags_info[1]->name;

        /* Update info test */
        $info = $tools['update']->get_new_plugin_info();
        $this->assertArrayHasKey('sections', $info);
        $this->assertArrayHasKey('changelog', $info['sections']);
    }
}
