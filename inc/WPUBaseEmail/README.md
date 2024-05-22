WPU Base Email
---

Nicely formatted emails in your plugin.

## Insert in the INIT hook

```php
require_once __DIR__ . '/inc/WPUBaseEmail/WPUBaseEmail.php';
$this->baseemail = new \wpu_polls\WPUBaseEmail();
```

## Send an email where you need it

```php
$this->baseemail->send_email('test subject', 'test content');
``
