WPU Base Settings
---

Add settings in your plugin.

## Insert in the INIT hook

```php
$this->settings_details = array(
    'plugin_id' => 'wpuimporttwitter',
    'option_id' => 'wpuimporttwitter_options',
    'sections' => array(
        'import' => array(
            'name' => __('Import Settings', 'wpuimporttwitter')
        )
    )
);
$this->settings = array(
    'sources' => array(
        'label' => __('Sources', 'wpuimporttwitter'),
        'help' => __('One #hashtag or one @user per line.', 'wpuimporttwitter'),
        'type' => 'textarea'
    )
);
if (is_admin()) {
    include dirname( __FILE__ ) . '/inc/WPUBaseSettings.php';
    new \wpuimporttwitter\WPUBaseSettings($this->settings_details,$this->settings);
}
```

## Insert in your admin page content

```php
echo '<form action="' . admin_url('options.php') . '" method="post">';
settings_fields($this->settings_details['option_id']);
do_settings_sections($this->options['plugin_id']);
echo submit_button(__('Save Changes', 'wpuimporttwitter'));
echo '</form>';
``
