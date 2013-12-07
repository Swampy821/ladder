<?php

class Test1_Migration_00002 extends Migration {
	protected $min_version = '0.8.1';
	// protected $databases = FALSE;
	// protected $import_data = array();
	// protected $import_update = FALSE; // TRUE for all, or array of tables from import_data to update.
	// protected $import_key_fields = FALSE; // Array of 'table' => array('fields', 'for', 'where').
	// protected $unimport_data = TRUE; // or an array of tables to unimport.
	// protected $unimport_key_fields = FALSE; // or an array of table => array(key, fields).

	public function up() {
	    $this->create_table('Test_Table')
                        ->column('COLUMN_1', "String",array( ))
                        ->column("TEST_COL_2",'Integer');
                
                      $this->create_table("ANOTHER TABLE")->column("COLUMN 1", "Int");
                     
	}

	public function down() {
		 $this->table('new_table')->drop();
		// $value = $this->get('key');
	}
}