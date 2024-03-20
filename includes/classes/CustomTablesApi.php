<?php
/**
 * Main Class file for creating a custom table crud page.
 *
 * @package Eighteen73
 */

namespace Eighteen73\CustomTablesApi;

/**
 * Main Class file for creating a custom table crud page.
 */
class CustomTablesApi {

	protected string $table;

	protected string $name;

	protected string $plural;

	protected array|string $schema;

	protected bool $show_ui = true;

	protected bool $show_in_rest = false;

	protected array $columns = [];

	protected array $fields = [];

	protected string $parent_slug = '';

	protected array $metaboxes = [];

	protected array $supports = [];

	protected int $version;

	protected \CT_Table $CT_Table;

	protected array $defaults = [];

	protected array $search_fields = [];

	protected array $filters = [];

	/**
	 * Constructor.
	 */
	public function __construct(
		string $table,
		string $name,
		string $plural,
		array|string $schema,
		int $version = 1
	) {
		require_once __DIR__ . '/../../CT/init.php';

		$this->table = $table;
		$this->name = $name;
		$this->plural = $plural;
		$this->schema = $schema;
		$this->version = $version;
	}

	public function init(): void
	{
		$this->CT_Table = ct_register_table( $this->table, [
			'singular' => $this->name,
			'plural' => $this->plural,
			'show_ui' => $this->show_ui, // Make custom table visible on admin area (check 'views' parameter)
			'show_in_rest' => $this->show_in_rest, // Show in REST
			// 'rest_base'  => $this->table,  // Rest base URL, if not defined will user the table name
			'version' => $this->version,      // Change the version on schema changes to run the schema auto-updater
			// 'primary_key' => 'log_id',     // If not defined will be checked on the field that hsa primary_key as true on schema
			'schema' => $this->schema,
			'engine' => 'InnoDB',
			// View args
			'views' => [
				'add' => [
					//'columns' => 1 // This will force to the add view just to one column, default is 2
				],
				'list' => [
					// This will force the per page initial value
					'per_page' => 40,
					// The columns arg is a shortcut of the manage_columns and manage_sortable_columns commonly required hooks
					'columns' => $this->columns,
					'parent_slug' => $this->parent_slug,
					'menu_title' => $this->plural,
				]
			],

			// This support automatically generates a new DB table with {table_name}_meta with a similar structure like WP post meta
			'supports' => [
				$this->supports,
			]
		]);

		if ( ! empty( $this->defaults ) ) {
			add_filter( 'ct_' . $this->table . '_default_data', function ( array $default_data = [] ) {
				return $this->defaults + $default_data;
			});
		}

		if ( ! empty( $this->search_fields ) ) {
			add_filter( 'ct_query_' . $this->table . '_search_fields', function ( array $search_fields = [] ) {
				return $this->search_fields + $search_fields;
			});
		}

		if (! empty( $this->filters )) {
			$this->render_filters();
		}

		add_action( 'cmb2_admin_init', [ $this, 'render_metaboxes' ] );
	}

	public function parent(string $slug): self
	{
		$this->parent_slug = $slug;

		return $this;
	}

	public function schema( array|string $schema ): self
	{
		$this->schema = $schema;

		return $this;
	}

	public function filters( array $filters ): self
	{
		$this->filters = $filters;

		return $this;
	}

	public function supports_meta(): self
	{
		$this->supports[] = 'meta';
		$this->supports = array_unique( $this->supports );

		return $this;
	}

	public function columns( array $columns ): self
	{
		foreach ( $columns as $column ) {
			$this->column( $column['column'], $column['label'], $column['sortable'] ?? false, $column['direction'] ?? 'asc' );
		}

		return $this;
	}

	public function column( string $name, string $label, $sortable = false, string $direction = 'asc' ): self
	{
		$this->columns[$name] = [
			'label' => $label,
			'sortable' => $sortable ? [$name, strtolower($direction) === 'asc'] : null,
		];

		return $this;
	}

	public function fields( array $fields, string $context = 'normal', string $metabox = 'default' ): self
	{
		foreach ( $fields as $field ) {
			$this->field( $field['column'], $field, $context, $metabox );
		}

		return $this;
	}

	public function field( string $name, array $args = [], string $context = 'normal', string $metabox = 'default' ): self
	{
		if (! isset($this->metaboxes[$context][$metabox])) {
			$this->metabox($metabox, ucwords($metabox), $context);
		}

		$this->metaboxes[$context][$metabox]['fields'][$name] = $args;

		return $this;
	}

	public function searchable( array $search_fields ): self
	{
		$this->search_fields = $search_fields;

		return $this;
	}

	public function defaults( array $defaults ): self
	{
		$this->defaults = $defaults;

		return $this;
	}

	public function metabox( string $name, string $label, string $context = 'normal' ): self
	{
		$this->metaboxes[$context][$name] = [
			'label' => $label,
			'fields' => [],
		];

		return $this;
	}

	public function render_metaboxes(): void
	{
		foreach ( $this->metaboxes as $context => $metaboxes ) {
			foreach ( $metaboxes as $name => $metabox ) {
				$this->render_metabox( $context, $name );
			}
		}
	}

	protected function render_metabox( string $context, string $name ): void
	{
		$cmb = new_cmb2_box( [
			'id' => $this->table . '-' . $context . '-' . $name,
			'title' => __( $this->metaboxes[$context][$name]['label'], 'custom-tables-api' ),
			'object_types' => [ $this->table ],
			'context' => $context,
		] );

		foreach ( $this->metaboxes[$context][$name]['fields'] as $id => $field ) {
			$cmb->add_field( [
				'id' => $id,
				'name' => esc_html( $field['name'] ),
				'desc' => isset($field['desc']) ? esc_html( $field['desc'] ) : null,
			] + $field);
		}
	}

	protected function render_filters(): void
	{
		foreach ( $this->filters as $filter ) {
			//
		}
	}

	public function get( int $id ): array
	{
		global $ct_registered_tables;

		$table = $ct_registered_tables[$this->table];

		return $table->db->get( $id );
	}

	public function query( $args = array(), $output = OBJECT ): array|\stdClass
	{
		global $ct_registered_tables;

		$table = $ct_registered_tables[$this->table];

		return $table->db->query( $args, $output );
	}
}
