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
                        ->column('COLUMN_1', "varchar",array( ))
                    ->column('COLUMN_2', "varchar",array( ))
                    ->column('COLUMN_3', "varchar",array( ))
                    ->column('COLUMN_4', "varchar",array( ))
                    ->column('COLUMN_5', "varchar",array( ))
                    ->drop_column("COLUMN_2")
                    ->alter_column("TEST_COL_2",'varchar');
          
            
                     $insert_id = $this->table('users')
                    ->insert(array(
                        'email' => 'example@example.tld',
                        'password' => NULL,
                    ))
                    ->insert_id;
                             
                    $this->db->query("SELECT * FROM CP_TEST WHERE ID='1' and name='test'");
                    
                    $this->db->query("SELECT * FROM CP_TEST WHERE ID='1' and name='test'");
            
                      $this->table("Table2")->drop();
                      $this->create_table("ANOTHER TABLE")->column("COLUMN 1", "Int");
                     
	}

	public function down() {
		 $this->table('new_table')->drop();
		// $value = $this->get('key');
	}
}