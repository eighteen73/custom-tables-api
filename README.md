# WordPress Custom Table API Class

Allows for easier creation of custom table CRUD pages by using an object oriented approach.

## An example

```php

use Eighteen73\CustomTablesApi\CustomTablesApi;

$custom_table = new CustomTablesApi(
	'my_custom_table',
	'My Custom Table Data',
	[
		'id' => [
			'type' => 'bigint',
			'length' => '20',
			'auto_increment' => true,
			'primary_key' => true,
		],
		'title' => [
			'type' => 'varchar',
			'length' => '50',
		],
		'status' => [
			'type' => 'varchar',
			'length' => '50',
		],
		'date' => [
			'type' => 'datetime',
		]
	],
	1,
	[
		'title' => [
			'label' => __( 'Title' ),
			'sortable' => 'title', // ORDER BY title ASC
		],
		'status' => [
			'label' => __( 'Status' ),
			'sortable' => [ 'status', false ], // ORDER BY status ASC
		],
		'date' => [
			'label' => __( 'Date' ),
			'sortable' => [ 'date', true ], // ORDER BY date DESC
		],
	]
);

add_action( 'ct_init', [ $custom_table, 'init' ] );
```

## Credits

Heavily based on [CT](https://github.com/rubengc/CT) by Ruben Garcia.
