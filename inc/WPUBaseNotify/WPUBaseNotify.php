<?php
namespace wpubasenotify_0_2_0;

/*
Class Name: WPU Base Notify
Description: A class to send plain text alerts by email or webhook
Version: 0.2.0
Class URI: https://github.com/WordPressUtilities/wpubaseplugin
Author: Darklg
Author URI: https://darklg.me/
License: MIT License
License URI: https://opensource.org/licenses/MIT
*/

defined('ABSPATH') || die;

class WPUBaseNotify {

    private $prefix = 'wpubasenotify_';
    private $option_id = false;
    private $error_option_id = false;
    private $notifications = array();
    private $user_cap = 'manage_options';
    private $plugin_name = '';
    private $overrides = array();

    public function __construct($args = array()) {
        $this->init($args);
    }

    public function init($args = array()) {
        if (!is_array($args) || !isset($args['option_id']) || !$args['option_id']) {
            return;
        }

        $this->option_id = $args['option_id'];
        $this->error_option_id = $this->option_id . '_' . $this->prefix . 'errors';
        $this->plugin_name = isset($args['plugin_name']) ? $args['plugin_name'] : '';
        $this->user_cap = isset($args['user_cap']) ? $args['user_cap'] : $this->user_cap;

        /* Direct values, for plugins not using WPUBaseSettings */
        foreach (array('email_to', 'slack_webhook') as $key) {
            if (isset($args[$key])) {
                $this->overrides[$key] = $args[$key];
            }
        }

        /* Declared notifications */
        if (isset($args['notifications']) && is_array($args['notifications'])) {
            foreach ($args['notifications'] as $id => $notification) {
                if (!is_array($notification)) {
                    $notification = array('label' => $notification);
                }
                $this->notifications[$id] = array(
                    'label' => isset($notification['label']) ? $notification['label'] : $id,
                    'help' => isset($notification['help']) ? $notification['help'] : ''
                );
            }
        }

        add_action('admin_notices', array(&$this,
            'admin_notices'
        ));
    }

    /* ----------------------------------------------------------
      Public API
    ---------------------------------------------------------- */

    /* Should the calling code even bother computing this alert ? */
    public function is_enabled($id) {
        if (!$this->option_id) {
            return false;
        }
        /* Fail open : an undeclared id still sends, so the typo is noisy */
        if (!isset($this->notifications[$id])) {
            return true;
        }
        if ($this->is_channel_enabled($id, 'email')) {
            return true;
        }
        return $this->is_channel_enabled($id, 'slack') && $this->get_webhook();
    }

    /* Returns true if at least one channel succeeded */
    public function notify($id, $subject, $message = '') {
        if (!$this->option_id) {
            return false;
        }

        $is_declared = isset($this->notifications[$id]);
        $do_email = $is_declared ? $this->is_channel_enabled($id, 'email') : true;
        $do_slack = $is_declared ? $this->is_channel_enabled($id, 'slack') : (bool) $this->get_webhook();

        $sent = false;
        if ($do_email && $this->send_email($subject, $message)) {
            $sent = true;
        }
        if ($do_slack && $this->send_webhook($subject, $message)) {
            $sent = true;
        }

        return $sent;
    }

    /* ----------------------------------------------------------
      Settings
    ---------------------------------------------------------- */

    public function get_settings_section($section = 'notify') {
        return array(
            $section => array(
                'name' => __('Notifications', __NAMESPACE__),
                'wpubasesettings_checkall' => true
            )
        );
    }

    public function get_settings_fields($section = 'notify') {
        $fields = array(
            $this->prefix . 'email_to' => array(
                'label' => __('Notifications email', __NAMESPACE__),
                'help' => __('Leave empty to use the website administration email.', __NAMESPACE__),
                'type' => 'email',
                'section' => $section
            ),
            $this->prefix . 'slack_webhook' => array(
                'label' => __('Webhook URL', __NAMESPACE__),
                'help' => __('HTTPS incoming webhook : Slack, Mattermost or Discord (with the /slack suffix).', __NAMESPACE__),
                'type' => 'url',
                'section' => $section
            )
        );

        foreach ($this->notifications as $id => $notification) {
            $fields[$this->get_notification_field_id($id)] = array(
                'type' => 'checkboxlist',
                'section' => $section,
                'help' => $notification['help'],
                'label' => $notification['label'],
                'datas' => array(
                    'email' => __('E-mail', __NAMESPACE__),
                    'slack' => __('Webhook', __NAMESPACE__)
                ),
                'default' => array()
            );
        }

        return $fields;
    }

    /* ----------------------------------------------------------
      Channels
    ---------------------------------------------------------- */

    private function send_email($subject, $message) {
        $to = $this->get_email_to();
        if (!$to) {
            return false;
        }

        /* Plain text : no HTML context, so nothing to escape */
        if (wp_mail($to, $subject, $message)) {
            $this->clear_error('mail_failed');
            return true;
        }

        $this->set_error('mail_failed', 'email', sprintf(__('Notification email to %s could not be sent.', __NAMESPACE__), $to));
        return false;
    }

    private function send_webhook($subject, $message) {
        $webhook = $this->get_webhook();
        if (!$webhook) {
            return false;
        }

        $text = '*' . $subject . '*';
        if ($message !== '') {
            $text .= "\n" . $message;
        }

        $response = wp_remote_post($webhook, array(
            'timeout' => 5,
            'headers' => array('Content-Type' => 'application/json'),
            'body' => wp_json_encode(array('text' => $text))
        ));

        if (is_wp_error($response)) {
            $this->set_error('webhook_failed', 'webhook', sprintf(__('Notification webhook failed : %s', __NAMESPACE__), $response->get_error_message()));
            return false;
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code < 200 || $code > 299) {
            $body = trim(wp_remote_retrieve_body($response));
            $this->set_error('webhook_failed', 'webhook', sprintf(__('Notification webhook returned a %s error : %s', __NAMESPACE__), $code, $body));
            return false;
        }

        $this->clear_error('webhook_failed');
        return true;
    }

    /* ----------------------------------------------------------
      Values
    ---------------------------------------------------------- */

    private function get_email_to() {
        $email = isset($this->overrides['email_to']) ? $this->overrides['email_to'] : $this->get_value('email_to');
        if (!$email) {
            $email = get_option('admin_email');
        }
        return is_email($email) ? $email : false;
    }

    /* Raw value, even if unusable : the admin notices need to see it */
    private function get_raw_webhook() {
        if (isset($this->overrides['slack_webhook'])) {
            return trim($this->overrides['slack_webhook']);
        }
        return trim($this->get_value('slack_webhook'));
    }

    private function get_webhook() {
        $webhook = $this->get_raw_webhook();
        if (!$webhook || stripos($webhook, 'https://') !== 0) {
            return false;
        }
        return $webhook;
    }

    private function get_notification_field_id($id) {
        return $this->prefix . 'notif_' . $id;
    }

    private function is_channel_enabled($id, $channel) {
        $value = $this->get_option_value($this->get_notification_field_id($id));
        /* Never saved : fall back on the same default as the settings field */
        if ($value === null) {
            return $channel == 'email';
        }
        return is_array($value) && in_array($channel, $value);
    }

    private function get_value($key) {
        $value = $this->get_option_value($this->prefix . $key);
        return $value === null ? '' : $value;
    }

    private function get_option_value($key) {
        $options = get_option($this->option_id);
        if (!is_array($options) || !isset($options[$key])) {
            return null;
        }
        return $options[$key];
    }

    private function has_enabled_webhook_notification() {
        foreach ($this->notifications as $id => $notification) {
            if ($this->is_channel_enabled($id, 'slack')) {
                return true;
            }
        }
        return false;
    }

    /* ----------------------------------------------------------
      Errors
    ---------------------------------------------------------- */

    /* Errors are stored with a hash of the faulty config : they clear
       themselves as soon as that config is edited. */
    private function set_error($key, $context, $message) {
        $errors = $this->get_errors();
        $errors[$key] = array(
            'hash' => $this->get_context_hash($context),
            'context' => $context,
            'message' => $message,
            'time' => time()
        );
        update_option($this->error_option_id, $errors, false);
    }

    private function clear_error($key) {
        $errors = $this->get_errors();
        if (!isset($errors[$key])) {
            return;
        }
        unset($errors[$key]);
        update_option($this->error_option_id, $errors, false);
    }

    private function get_errors() {
        $errors = get_option($this->error_option_id);
        return is_array($errors) ? $errors : array();
    }

    private function get_context_hash($context) {
        if ($context == 'email') {
            return md5((string) $this->get_email_to());
        }
        return md5($this->get_raw_webhook());
    }

    public function admin_notices() {
        if (!$this->option_id || !current_user_can($this->user_cap)) {
            return;
        }

        $messages = array();

        /* Config problems : stateless, recomputed at each display */
        $raw_webhook = $this->get_raw_webhook();
        if ($raw_webhook && !$this->get_webhook()) {
            $messages[] = __('The notifications webhook URL must start with https://', __NAMESPACE__);
        }
        if (!$raw_webhook && $this->has_enabled_webhook_notification()) {
            $messages[] = __('Webhook notifications are enabled but no webhook URL is configured.', __NAMESPACE__);
        }

        /* Runtime failures : kept until the faulty config changes */
        foreach ($this->get_errors() as $error) {
            if (!isset($error['hash'], $error['message'])) {
                continue;
            }
            if ($error['hash'] !== $this->get_context_hash(isset($error['context']) ? $error['context'] : 'webhook')) {
                continue;
            }
            $messages[] = $error['message'];
        }

        foreach ($messages as $message) {
            if ($this->plugin_name) {
                $message = $this->plugin_name . ' : ' . $message;
            }
            echo '<div class="notice notice-error"><p>' . esc_html($message) . '</p></div>';
        }
    }

    public function uninstall() {
        delete_option($this->error_option_id);
    }
}
