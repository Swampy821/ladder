<?php

class Table {
	private $name;
	private $columns;
	private $indexes;
	private $triggers;
	private $constraints;
	private $created;
	private $table_columns;
	private $options;
	private $insert_id;

	public static function factory($name, $created = FALSE, $options = NULL) {
		return new Table($name, $created, $options);
	}

	public static function exists($name) {
		$db = Database::factory();
		$res = $db->query(sprintf(
			'SHOW TABLES LIKE \'%s\'', $db->escape_value($name)
		));
		return mysql_num_rows($res) == 1;
	}

	public function __construct($name, $created = FALSE, $options = NULL) {
		$this->name = $name;
		$this->created = $created;
		$this->clear();

		if (! (bool) $created) {
			$this->column('id', 'integer', array('null' => FALSE, 'autoincrement' => TRUE));
			$this->index('PRIMARY', 'id');
		}

		if ($options === NULL) {
			$options = Config::item('table');
		}

		$this->options = $options;
	}

	public function __get($key) {
		if ($key === 'name') {
			return $this->name;
		} elseif ($key === 'insert_id') {
			return $this->insert_id;
		}
	}

	/**
	 * Fetch column information from the database.
	 * @return array
	 */
	public function get_columns($force_refresh = FALSE) {
		if (! (bool) $force_refresh AND (bool) $this->table_columns) {
			return $this->table_columns;
		}

		$db = Database::factory();
		$cols = array();
		$field_query = $db->query(sprintf('SHOW FULL COLUMNS FROM `%s`', $this->name));
		while ($field_row = mysql_fetch_object($field_query)) {
			$cols[$field_row->Field] = $field_row;
		}
		return $this->table_columns = $cols;
	}

	/**
	 * Remove all pending SQL statements.
	 * @return NULL
	 */
	public function clear() {
		$this->columns = $this->indexes = $this->triggers = $this->constraints = array(
			'add' => array(),
			'drop' => array(),
		);
		$this->columns['alter'] = array();
	}

	/**
	 * Automatically add the `created` and `modified` fields.
	 * @return Table
	 */
	public function timestamps() {
		return $this->column('created', 'datetime', array('null' => FALSE))
			->column('modified', 'datetime');
	}
	
	/**
	 * Add a column to this Table instance.
	 * @return Table
	 * @param string $name Name of the column to add.
	 * @param string $type Column type to add.
	 * @param array $options[optional] Options such as unique, null.
	 */
	public function column($name, $type, $options = array()) {
		$this->columns['add'][$name] = array($name, $type, $options);
		return $this;
	}

	/**
	 * Alter a column in this Table instance.
	 * @return Table
	 * @param string $name Name of the column to alter.
	 * @param string $type Column type to change to.
	 * @param array $options[optional] Options such as unique, null, limit etc.
	 */
	public function alter_column($name, $type, $options = array()) {
		$this->columns['alter'][$name] = array($name, $type, $options);
		return $this;
	}

	/**
	 * Drop a column from this Table instance.
	 * @return Table
	 * @param string $name The name of the column to drop.
	 */
	public function drop_column($name) {
		$this->columns['drop'][$name] = $name;
		return $this;
	}

	/**
	 * Add an index to this Table instance.
	 * @return Table
	 * @param string $name Name of index to add. If this is the only parameter, the column to index.
	 * @param array $columns[optional] The columns to use in the index.
	 * @param array $options[optional] Any options, such as primary, unique.
	 */
	public function index($name, $columns = array(), $options = array()) {
		// Allow them to alter the PRIMARY key.
		if ((arr::val($options, 'primary') === TRUE) OR ($name == 'primary')) {
			$name = 'PRIMARY';

			// Remove the default PRIMARY column if we're building a table.
			if (! $this->created AND array_key_exists('id', $this->columns['add']))
				unset($this->columns['add']['id']);
		}

		$this->indexes['add'][$name] = array($name, $columns, $options);
		return $this;
	}

	/**
	 * Drop an index from this Table instance.
	 * @return Table
	 * @param string $name The name of the index to drop.
	 */
	public function drop_index($name) {
		$this->indexes['drop'][$name] = $name;
		return $this;
	}

	/**
	 * Create a trigger on this Table instance.
	 * @return Table
	 * @param string $when Either 'before' or 'after'.
	 * @param string $event Any of 'insert', 'update' or 'delete'.
	 * @param string $sql The statements to execute on the trigger.
	 */
	public function trigger($when, $event, $sql) {
		$when = strtolower($when);
		$event = strtolower($event);
		$name = sprintf('%s_%s_%s_tr', $this->name, $when, substr($event, 0, 3));
		$this->triggers['add'][$name] = array($name, $when, $event, $this->name, $sql);
		return $this;
	}

	/**
	 * Drop a trigger from this Table instance.
	 * @return Table
	 * @param string $name The name of the trigger to drop.
	 */
	public function drop_trigger($when, $event) {
		$when = strtolower($when);
		$event = strtolower($event);
		$name = sprintf('%s_%s_%s_tr', $this->name, $when, substr($event, 0, 3));
		$this->triggers['drop'][$name] = $name;
		return $this;
	}

	/**
	 * Create a constraint on this Table instance.
	 * @return Table
	 * @param string $index The index in this table to constrain.
	 * @param string $reference_table The table to refer to.
	 * @param string|array $reference_fields Fields in $reference_table to constrain to.
	 * @param string|array $cascade[optional] Which actions to cascade.
	 */
	public function constraint($index, $reference_table, $reference_fields, $cascade = array()) {
		// Fix up any string/array params first.
		$reference_fields = (array) $reference_fields;
		$cascade = (array) $cascade;

		// Work out a name for the constraint.
		$name = sprintf('%s_%s_%s_%s', $this->name, $index, $reference_table, implode('_', $reference_fields));

		// Remember its details for later.
		$this->constraints['add'][$name] = array(
			$name, $index, $reference_table, $reference_fields, $cascade
		);

		return $this;
	}

	/**
	 * Drop a constraint from this Table instance.
	 * @return Table
	 * @param string $index The index in this table the constraint uses.
	 * @param string $reference_table The table it refers to.
	 * @param string|array $reference_fields The fields it refers to.
	 */
	public function drop_constraint($index, $reference_table, $reference_fields) {
		// Fix up any string/array params first.
		$reference_fields = (array) $reference_fields;

		// Work out a name for the constraint.
		$name = sprintf('%s_%s_%s', $index, $reference_table, implode('_', $reference_fields));

		// Remember it for later.
		$this->constraints['drop'][$name] = $name;

		return $this;
	}

	/**
	 * Perform all outstanding SQL statements.
	 * @return NULL
	 */
	public function execute() {
		// Initialise to false, we need to see if there's work to do.
		$todo = FALSE;

		// Check the columns array first.
		$check_keys = array('add', 'alter', 'drop');
		foreach ($check_keys as $key) {
			// If there's something to do, set the flag and break.
			if ((bool) $this->columns[$key]) {
				$todo = TRUE;
				break;
			}
		}

		// If there's still nothing to do, check the indexes.
		if (! $todo) {
			$check_keys = array('add', 'drop');
			foreach ($check_keys as $key) {
				if ((bool) $this->indexes[$key]) {
					$todo = TRUE;
					break;
				}
			}
		}

		// Still nothing? Look at constraints.
		if (! $todo) {
			$check_keys = array('add', 'drop');
			foreach ($check_keys as $key) {
				if ((bool) $this->constraints[$key]) {
					$todo = TRUE;
					break;
				}
			}
		}

		// Still nothing? Look at triggers.
		if (! $todo) {
			$check_keys = array('add', 'drop');
			foreach ($check_keys as $key) {
				if ((bool) $this->triggers[$key]) {
					$todo = TRUE;
					break;
				}
			}
		}

		// If nothing to do, no need to execute any SQL!
		if (! $todo)
			return FALSE;

		try {
			if (! $this->created) {
				sql::add_table(
					$this->name, $this->columns['add'],
					$this->indexes['add'], $this->triggers['add'],
					$this->constraints['add'], $this->options
				);

				$this->created = TRUE;
			} else {
				sql::alter(
					$this->name, $this->columns,
					$this->indexes, $this->triggers,
					$this->constraints, $this->options
				);
			}
		} catch (Exception $e) {
			$this->clear();
			throw $e;
		}

		$this->clear();

		return $this;
	}
	
	public function data($migration) {
		$this->execute(); // Ensure the table exists, and has the right fields...
		
		if ($migration === FALSE)
			return;
		
		require_once(sprintf('migrations/data/%s-%s.php',
			end(explode('_', get_class($migration))), strtolower($this->name)));
	}

	public function select($fields = NULL, $where = NULL, $fetch = 'assoc') {
		// Escape fields, or use * by default.
		if (! is_null($fields)) {
			$fields = '`'.implode('`, `', (array) $fields).'`';
		} else {
			$fields = '*';
		}

		if ((bool) $where) {
			// Use field => value pairs where available.
			if (is_array($where)) {
				$clauses = array();

				foreach ($where as $field => $value) {
					$clause = sql::escape($field, '`');
					$clause .= ' = ';
					$clause .= sql::escape($value);
					$clauses[] = $clause;
				}

				// Compile into a string.
				$where = 'WHERE '.implode(' AND ', $clauses);
			}
		}

		// Run the query.
		$query = Database::factory()->query(sprintf(
			'SELECT %s FROM `%s` %s',
			$fields, $this->name, $where
		));

		// Loop over the result set, saving to an array.
		$func = 'mysql_fetch_'.$fetch;
		while ((bool) $row = $func($query)) {
			$result[] = $row;
		}

		// Free the result set.
		mysql_free_result($query);

		// Return the rows!
		return $result;
	}
	
	public function insert($data, $extra = '') {
		$this->execute();
		sql::insert($this->name, $data, $extra);
		$this->insert_id = sql::insert_id();
		return $this;
	}
	
	public function update($data, $where) {
		$this->execute();
		sql::update($this->name, $data, $where);
		return $this;
	}
	
	public function delete($where) {
		$this->execute();
		sql::delete($this->name, $where);
		return $this;
	}

	public function truncate() {
		$this->execute();
		sql::truncate($this->name);
		return $this;
	}

	public function drop() {
		$this->execute();
		sql::drop_table($this->name);
	}

	public function rename($new_name) {
		sql::rename_table($this->name, $new_name);
		$this->name = $new_name;
		return $this;
	}

	public function import_csv($path) {
		// Check the file exists.
		if (! file_exists($path)) {
			throw new Exception('Cannot find file: '.$path);
		}

		// Open the CSV file.
		$csv = fopen($path, 'r');

		// Always assume the 1st row is the field names.
		$headers = fgetcsv($csv);

		// Loop over the file and insert records.
		while ($row = fgetcsv($csv)) {
			// Change any strings of 'null' into actual NULL values.
			foreach ($row as &$cell) {
				if (strtolower($cell) == 'null') {
					$cell = NULL;
				}
			}

			// Combine the data into an associative array.
			$data = array_combine($headers, $row);

			// Insert into the table.
			$this->insert($data);
		}

		// Close the file.
		fclose($csv);

		return $this;
	}
}