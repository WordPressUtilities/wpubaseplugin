WPU Base Cron
---

Add Cron in your plugin.

## Insert in the plugins_loaded hook

```php
include 'inc/WPUBaseCron.php';
$this->basecron = new \wpubaseplugin\WPUBaseCron(array(
    'pluginname' => 'Base Plugin', // Default : [Namespace]
    'cronhook' => 'wpubaseplugin__cron_hook', // Default : [namespace__cron_hook]
    'croninterval' => 900 // Default : [3600]
));
```

## uninstall hook ##

```php
$this->basecron->uninstall();
```
