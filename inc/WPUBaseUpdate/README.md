WPU Base Settings
---

Add settings in your plugin.

## Insert in the INIT hook

```php
$this->settings_update = new \wpupopin\WPUBaseUpdate(
    'WordPressUtilities',
    'wpupopin',
    $this->plugin_version);
```
