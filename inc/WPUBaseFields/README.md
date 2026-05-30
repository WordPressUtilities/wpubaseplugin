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

## Field groups

A group targets either a post type or a taxonomy. Fields are displayed in a meta
box (post types) or in the term add/edit form (taxonomies).

```php
$field_groups = array(
    /* Post type group (default capability: edit_posts) */
    'infos' => array(
        'label' => 'Infos',
        'post_type' => 'post',          // string or array of post types
        'capability' => 'edit_posts'
    ),
    /* Taxonomy group */
    'category_meta' => array(
        'label' => 'Category settings',
        'taxonomy' => 'category'        // string or array of taxonomies
    )
);
```

## Field options

```php
$fields = array(
    'subtitle' => array(
        'group' => 'infos',
        'label' => 'Subtitle',
        'type' => 'text',               // see supported types below
        'placeholder' => 'Optional placeholder',
        'help' => 'Helper text shown under the field',
        'required' => true,
        'readonly' => false,
        'default_value' => '',
        'extra_attributes' => array('maxlength' => '120')
    )
);
```

Supported types: `text`, `textarea`, `editor`, `number`, `email`, `url`, `tel`,
`date`, `datetime`, `color`, `select`, `radio`, `checkbox`, `checkboxes`,
`image`, `file`, `wp_link`, `post`, `page`.

## Admin list columns

Display a field as a column in the WP-admin posts or terms list with
`admin_column`. Use `true` for defaults, or an array for options.

```php
$fields = array(
    /* Simple column */
    'subtitle' => array(
        'group' => 'infos',
        'label' => 'Subtitle',
        'admin_column' => true
    ),
    /* Column with options */
    'status' => array(
        'group' => 'infos',
        'type' => 'select',
        'label' => 'Status',
        'data' => array(
            'draft' => 'Draft',
            'live' => 'Live'
        ),
        'admin_column' => array(
            'label' => 'State',                 // optional, defaults to field label
            'sortable' => true,                 // post types only
            'callback' => false                 // optional custom renderer
        )
    ),
    /* Custom rendering */
    'price' => array(
        'group' => 'infos',
        'type' => 'number',
        'label' => 'Price',
        'admin_column' => array(
            'sortable' => true,
            'callback' => function ($value, $object_id, $field_id) {
                return $value !== '' ? number_format((float) $value, 2) . ' &euro;' : '&mdash;';
            }
        )
    )
);
```

Notes:

- Columns appear on the same post types / taxonomies as the field group, and
  respect the group `capability`.
- Default rendering resolves labels (`select`, `radio`, `checkboxes`), titles
  (`post`, `page`), thumbnails (`image`), file names (`file`), links
  (`wp_link`, `url`), a swatch (`color`) and trims long text (`editor`,
  `textarea`). Empty values render as `—`.
- `sortable` works for post types only (orders by meta value, numeric for
  `number` / `date` / `datetime`). Taxonomy columns are not sortable.
