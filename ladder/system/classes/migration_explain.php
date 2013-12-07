<?php
class migration_explain extends Migration{
    public function __construct() {
        
    }
    public function get_source_file($migration_id)
    {
       // while ($db->next_database()) {
            // We must instantiate a fresh one because of should_run.
       // $migration = Migration::factory($db, $migration_id);   

        $migration_file = $this->file_name($migration_id);

        $file = fopen($migration_file,"r");
        $source = fread($file, filesize($migration_file));
        $source = strstr($source, "public function up()");
        $end_point = strpos($source, "public function down()");
        $source = substr($source,0, $end_point);
        $source = explode(";", $source);
        return $source;
    }
    private function explode_multi($source,array $needles, $glue_symbol="~")
    {
            $temp_string = $source;
            $temp_array = array();
            foreach($needles as $nd)
            {
                    $temp_array = explode($nd, $temp_string);
                    $temp_string = implode($glue_symbol,$temp_array);
            }
            $temp_array = explode("~",$temp_string);
            return $temp_array;
            
    }
    public function explain_migration($source)
    {
        $explained_string = '';
        if(is_array($source) && count($source))
        {
            foreach($source as $rs)
            {
                $commands = explode("->", $rs);
                
                $table = null;
                
                if(count($commands))
                {
                    foreach($commands as $ind)
                    {
                        //Define Table Variable.
                        if(strtolower(substr($ind, 0, 5))=="table" || strpos(strtolower($ind), "create_table")!==false)
                        {
                            $tab = $this->explode_multi($ind, array("'",'"'));
                            //var_dump($tab);
                            $table = $tab[1]; 
                        }
                       //Create Table Return.
                        if(strpos(strtolower($ind), "create_table")!==false) {
                            $explained_string .= "\nCreated table ".$table."\n";
                        }
                        if(strpos(strtolower($ind), "column")!==false) {
                            if($table!='') {
                                $col_array = $this->explode_multi($ind, array("'",'"'));
                                //var_dump($col_array);
                                $column_name = $col_array[1];
                                $column_type = $col_array[3];
                                $explained_string .= "Added column \"".$column_name."\" to ".$table." with the type \"".$column_type."\"\n";
                            }else{
                                return "\nYou have an error in your migration. Please address this immediately!\n\n";
                            }
                            
                        }
                        
                        
                        
                        
                        
                        
                        
                        
                        
                        
                    }
                }
                
                
            }
            
            
        }else{
            $explained_string = "Migration Empty";
        }
        if($explained_string=='')
        {
            $explained_string = "Migration Empty";
        }
        $explained_string.= "\n---------------------------------\n";
        $explained_string = "\n---------------------------------\n".$explained_string;
        return $explained_string;
    }
    
    
    
}
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
?>
