<?php
namespace wpubasecron_0_1_2;

/*
Class Name: WPU Base Cron
Description: A class to handle crons
Version: 0.1.2
Author: Darklg
Author URI: http://darklg.me/
License: MIT License
License URI: http://opensource.org/licenses/MIT
*/

class WPUBaseCron {
    public function __construct() {}

    public function init($settings = array()) {
        /* Settings */
        $this->pluginname = isset($settings['pluginname']) ? $settings['pluginname'] : 'WPUBaseCron';
        $this->cronhook = isset($settings['cronhook']) ? $settings['cronhook'] : 'wpubasecron';
        $this->croninterval = isset($settings['croninterval']) ? $settings['croninterval'] : 600;

        /* Internal values */
        $this->cronoption = $this->cronhook . '_croninterval';
        $this->cronschedule = $this->cronhook . '_schedule';

        /* Hooks */
        add_filter('cron_schedules', array(&$this,
            'add_schedule'
        ));
        add_action('init', array(&$this,
            'check_cron'
        ));
    }

    /* Create schedule */
    public function add_schedule($schedules) {
        $schedules[$this->cronschedule] = array(
            'interval' => $this->croninterval,
            'display' => $this->pluginname . ' - Custom'
        );
        return $schedules;
    }

    /* Schedule cron if possible */
    public function check_cron() {
        $croninterval = get_option($this->cronoption);
        $schedule = wp_next_scheduled($this->cronhook);
        // If no schedule cron or new interval
        if (!$schedule || $croninterval != $this->croninterval) {
            $this->install();
        }
    }

    /* Get next scheduled */
    public function get_next_scheduled() {
        $schedule = wp_next_scheduled($this->cronhook);
        $seconds = $schedule - time();
        $minutes = 0;
        if ($seconds >= 60) {
            $minutes = (int) ($seconds / 60);
            $seconds = $seconds % 60;
        }
        return array(
            'min' => $minutes,
            'sec' => $seconds
        );
    }

    /* Create cron */
    public function install() {
        wp_clear_scheduled_hook($this->cronhook);
        update_option($this->cronoption, $this->croninterval);
        wp_schedule_event(time() + $this->croninterval, $this->cronschedule, $this->cronhook);
    }

    /* Destroy cron */
    public function uninstall() {
        wp_clear_scheduled_hook($this->cronhook);
        delete_option($this->cronoption);
        flush_rewrite_rules();
    }
}
