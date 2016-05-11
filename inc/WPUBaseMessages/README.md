WPU Base Messages
---

Add notices in your plugin.

## Insert in the INIT hook

```php
if (is_admin()) {
    include 'inc/WPUBaseMessages.php';
    $this->messages = new \wpubaseplugin\WPUBaseMessages($this->options['plugin_id']);
}
add_action('wpuimporttwitter_admin_notices', array(&$this->messages,
    'admin_notices'
));
```
