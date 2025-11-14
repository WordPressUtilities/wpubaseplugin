WPU Base fields
---

Add Custom fields to your plugin.

## Insert in the plugins_loaded hook

```php
$fields = array(
    'demo' => array(
        'group' => 'group_1',
        'label' => 'Demo',
        'placeholder' => 'My Placeholder',
        'required' => true
    ),
    'select' => array(
        'type' => 'select',
        'group' => 'group_2',
        'label' => 'Select with Data',
        'data' => array(
            'value_1' => 'Value 1',
            'value_2' => 'Value 2',
        )
    ),
    'select_nodata' => array(
        'type' => 'select',
        'group' => 'group_2',
        'label' => 'Select without Data'
    )
);
$field_groups = array(
    'group_1'  => array(
        'label' => 'Group 1'
    ),
    'group_2'  => array(
        'label' => 'Group 2'
    )
);
require_once __DIR__ . '/inc/WPUBaseFields/WPUBaseFields.php';
$this->basefields = new \wpubaseplugin\WPUBaseFields($fields, $field_groups);
```
