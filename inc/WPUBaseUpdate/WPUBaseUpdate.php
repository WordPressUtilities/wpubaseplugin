<?php
namespace wpubaseupdate_0_1_1;

/*
Class Name: WPU Base Update
Description: A class to handle plugin update from github
Version: 0.1.1
Author: Darklg
Author URI: http://darklg.me/
License: MIT License
License URI: http://opensource.org/licenses/MIT
Thanks: https://gist.github.com/danielbachhuber/7684646
*/

class WPUBaseUpdate {

    private $github_username;
    private $github_project;
    private $current_version;
    private $transient_name;
    private $transient_expiration;

    public function __construct($github_username = false, $github_project = false, $current_version = false) {
        if (!$github_username || !$github_project || !$current_version) {
            return;
        }

        /* Settings */
        $this->github_username = $github_username;
        $this->github_project = $github_project;
        $this->current_version = $current_version;
        $this->github_path = $this->github_username . '/' . $this->github_project;
        $this->transient_name = strtolower($this->github_username . '_' . $this->github_project . '_info_plugin_update');
        $this->transient_expiration = HOUR_IN_SECONDS;

        /* Hook on plugin update */
        add_filter('site_transient_update_plugins', array($this,
            'filter_update_plugins'
        ));
        add_filter('transient_update_plugins', array($this,
            'filter_update_plugins'
        ));
        add_filter('upgrader_post_install', array($this,
            'upgrader_post_install'
        ), 10, 3);
    }

    public function filter_update_plugins($update_plugins) {

        if (!is_object($update_plugins)) {
            return $update_plugins;
        }

        if (!isset($update_plugins->response) || !is_array($update_plugins->response)) {
            $update_plugins->response = array();
        }

        $body_json = $this->get_plugin_update_info();
        if (is_array($body_json)) {
            foreach ($body_json as $plugin_version) {
                /* Skip older versions */
                if (version_compare($plugin_version->name, $this->current_version) <= 0) {
                    continue;
                }
                /* Add plugin details */
                $plugin_id = $this->github_project . '/' . $this->github_project . '.php';
                $update_plugins->response[$plugin_id] = (object) array(
                    'slug' => 'github-' . $this->github_project,
                    'new_version' => $plugin_version->name,
                    'plugin' => $plugin_id,
                    'destination_name' => $this->github_project,
                    'url' => 'https://github.com/' . $this->github_path,
                    'package' => $plugin_version->zipball_url
                );
                break;
            }
        }

        return $update_plugins;
    }

    /* Retrieve infos from github */
    private function get_plugin_update_info() {
        if (false === ($plugin_update_body = get_transient($this->transient_name))) {
            $plugin_update_body = wp_remote_retrieve_body(wp_remote_get('https://api.github.com/repos/' . $this->github_path . '/tags'));
            set_transient($this->transient_name, $plugin_update_body, $this->transient_expiration);
        }

        return json_decode($plugin_update_body);
    }

    public function upgrader_post_install($true, $hook_extra, $result) {
        /* Only for this plugin */
        $base_plugin_name = $this->github_username . '-' . $this->github_project;
        if (substr($result['destination_name'], 0, strlen($base_plugin_name)) === $base_plugin_name) {
            $new_destination = $result['local_destination'] . '/' . $this->github_project . '/';
            rename($result['destination'], $new_destination);
            $result['destination'] = $new_destination;
            $result['remote_destination'] = $new_destination;
            $result['destination_name'] = $this->github_project;
        }
        return $result;
    }
}
