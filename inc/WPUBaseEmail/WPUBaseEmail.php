<?php
namespace wpubaseemail_0_3_2;

/*
Class Name: WPU Base Email
Description: A class to handle native Email in WordPress admin
Version: 0.3.2
Class URI: https://github.com/WordPressUtilities/wpubaseplugin
Author: Darklg
Author URI: https://darklg.me/
License: MIT License
License URI: https://opensource.org/licenses/MIT
*/

defined('ABSPATH') || die;

class WPUBaseEmail {
    private $settings = array();

    public function __construct($settings = array()) {
        $default_settings = array(
            'mail_id' => __NAMESPACE__,
        );
        if (!is_array($settings)) {
            $settings = array();
        }
        $settings = array_merge($default_settings, $settings);

        $this->settings = $settings;
    }

    function send_email($subject, $email_text, $to = false, $headers = array(), $attachments = array()) {

        $mail_id = $this->settings['mail_id'];

        /* To */
        if (!$to) {
            $to = apply_filters('wpubaseemail__send_email__default_to', get_option('admin_email'), $mail_id);
        }

        /* Content */
        ob_start();
        include __DIR__ . '/templates/template.php';
        $out_html = ob_get_clean();

        /* Headers */
        if (!is_array($headers)) {
            $headers = array();
        }
        $headers[] = 'Content-Type: text/html; charset=UTF-8';

        /* Send mail */
        wp_mail($to, $subject, $out_html, $headers, $attachments);
    }

}
