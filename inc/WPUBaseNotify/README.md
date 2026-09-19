WPU Base Notify
---

Send plain text alerts by email or webhook (Slack, Mattermost, Discord) when something needs attention.

## Declare your notifications

```php
require_once __DIR__ . '/inc/WPUBaseNotify/WPUBaseNotify.php';
$this->basenotify = new \wpu_myplugin\WPUBaseNotify(array(
    'option_id' => 'myplugin_options',
    'plugin_name' => 'My Plugin',
    'notifications' => array(
        '404_spike' => array(
            'label' => __('404 spike', 'myplugin'),
            'help' => __('Sent when yesterday 404s exceed the 7 days average.', 'myplugin')
        )
    )
));
```

## Add the fields to your settings

```php
$settings_details['sections'] += $this->basenotify->get_settings_section();
$settings = array_merge($settings, $this->basenotify->get_settings_fields());
```

Pass a section id to both methods to place the fields in one of your existing sections.

## Send where you need it

```php
if (!$this->basenotify->is_enabled('404_spike')) {
    return;
}

/* ... expensive detection logic ... */

$this->basenotify->notify('404_spike', 'Spike of 404 errors', 'Yesterday : 250 errors, 7 days average : 40.');
```

`is_enabled()` is a cheap guard meant to run **before** the detection logic : it returns false when every channel of this notification is off. An undeclared id returns true, so a typo stays noisy instead of silently dropping alerts.

`notify()` returns true if at least one channel succeeded.

## Behaviour

* Messages are plain text on both channels. The subject is sent bold on the webhook, as the subject by email.
* An empty email field falls back on the website administration email.
* Webhook URLs must use HTTPS.
* Email notifications are enabled by default, webhook ones are not.
* Delivery failures raise a persistent admin notice, which disappears as soon as the faulty setting is edited.

## Uninstall hook

```php
$this->basenotify->uninstall();
```
