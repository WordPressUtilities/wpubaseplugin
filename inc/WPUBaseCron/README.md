WPU Base Cron
---

Add Cron in your plugin.

## Insert in the highest available hook

```php
include 'inc/WPUBaseCron.php';
$this->basecron = new \wpubaseplugin\WPUBaseCron();
```

## Insert in the plugins_loaded hook

```php
$this->basecron->init(array(
    'pluginname' => 'Base Plugin',
    'cronhook' => 'wpubaseplugin__cron_hook',
    'croninterval' => 900
));
```

## Insert in init hook (if ->init() was not triggered from plugins_loaded)

```php
$this->basecron->check_cron();
```

## uninstall hook ##

```php
$this->basecron->uninstall();
```
