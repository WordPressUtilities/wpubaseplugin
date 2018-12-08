WPU Base Update
---

Update your Github WordPress plugin from the plugins page admin.

## Insert in the INIT hook

```php
include dirname( __FILE__ ) . '/inc/WPUBaseUpdate/WPUBaseUpdate.php';
$this->settings_update = new \wpupopin\WPUBaseUpdate(
    'WordPressUtilities',
    'wpupopin',
    $this->plugin_version);
```
