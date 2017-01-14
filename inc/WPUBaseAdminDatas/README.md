WPU Base Admin Datas
---

Add admin datas and a database in your plugin.

## Insert in the INIT hook

```php
$this->baseadmindatas = new \wpubaseplugin\WPUBaseAdminDatas();
$this->baseadmindatas->init(array(
    'plugin_id' => 'my_plugin',
    'table_name' => 'my_table',
    'table_fields' => array(
        'value' => array(
            'public_name' => 'Value',
            'sql' => 'varchar(100) DEFAULT NULL'
        )
    )
));
```

## Display table :

- Default :

```php
echo $this->baseadmindatas->get_admin_table();
```

- Advanced :

```php
$array_values = false; // ($array_values are automatically retrieved if not a valid array)
echo $this->baseadmindatas->get_admin_table(
    $array_values,
    array(
        'perpage' => 10,
        'columns' => array('creation' => 'Creation date')
    )
);
```

## TODO

* Uninstall
*  Default field type ( date => timestamp, text => varchar(100) )
