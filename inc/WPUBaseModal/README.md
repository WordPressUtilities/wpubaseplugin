WPU Base Modal
---

A class to display a modal in WordPress.


## Insert in construct

```php
/* Modal */
require_once __DIR__ . '/inc/WPUBaseModal/WPUBaseModal.php';
$this->wpubasemodal = new \mypluginid\WPUBaseModal('mypluginid');
```

## Insert when needed

```php
echo $this->wpubasemodal->get_modal_html(__('Hello world', 'mypluginid'));
```
