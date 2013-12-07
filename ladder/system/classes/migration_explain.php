<?php
class migration_explain extends Migration{
    public function __construct() {
        
    }
    /**
     * 
     * @param Integer $migration_id Migration ID of file. 
     * @todo Better comment function.
     * @return String Source of migration file. 
     */
    public function get_source_file($migration_id)
    {
        $migration_file = $this->file_name($migration_id);
        $file = fopen($migration_file,"r");
        $source = fread($file, filesize($migration_file));
        $source = strstr($source, "public function up()");
        $end_point = strpos($source, "public function down()");
        $source = substr($source,0, $end_point);
        $source = explode(";", $source);
        return $source;
    }
    /**
     *  Changes Color for CLI return
     * 
     * @param String $string String in which to wrap color around. 
     * @param String $color Color in which to chang eto 
     * @todo Possibly add more colors. 
     * @todo Better comment function.
     * @return string Returns color wrapped string for output in CLI
     * 
     */
    private function change_color($string, $color='green')
    {
            if($color==='red') {
                    $color_start = "\033[0;31m";
                    $color_end = "\033[0m";
            }
            if($color==='green') {
                    $color_start = "\033[0;32m";
                    $color_end = "\033[0m";
            }
            if($color==='green-bold') {
                    $color_start = "\033[1;32m";
                    $color_end = "\033[0m";
            }
            if($color==='yellow') {
                    $color_start = "\033[0;33m";
                    $color_end = "\033[0m";
            }
            if($color==='purple') {
                    $color_start = "\033[0;34m";
                    $color_end = "\033[0m";
            }
            if($color==='cyan') {
                    $color_start = "\033[0;36m";
                    $color_end = "\033[0m";
            }
            $new_string = $color_start.$string.$color_end;
            return $new_string;
    }
    /**
     * Does an "explode" with multiple needles. 
     * 
     * @param String $source String in which to expload
     * @param Array $needles Array of needles in which to explode by
     * @param String $glue_symbol Symbol that is not anywhere in the string. Default is ~.
     * @return Array Exploaded string. 
     */
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
    /**
     * Runs explain command
     * 
     * @param String $source Source from file. 
     * @todo Echo out options for column creation
     * @todo Better comment entire function
     * @return String String to echo to CLI.
     */
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
                            $table = $tab[1]; 
                        }
                       //Create Table Return.
                        if(strpos(strtolower($ind), "create_table")!==false) {
                            $explained_string .= $this->change_color("\n Create table \"".$table."\"\n", 'green-bold');
                        }
                        //Column Return
                        if(strpos(strtolower($ind), "column")===0) {
                            if($table!='') {
                                $col_array = $this->explode_multi($ind, array("'",'"'));
                                $column_name = $col_array[1];
                                $column_type = $col_array[3];
                                $explained_string .= $this->change_color(" Add column \"".$column_name."\" to \"".$table."\" with the type \"".$column_type."\"\n", 'green');
                            }else{
                                return "\n You have an error in your migration. Please address this immediately!\n\n";
                            }
                        }
                        //Alter Column Return
                        if(strpos(strtolower($ind),"alter_column")===0) {
                            if($table!='') {
                                $col_array = $this->explode_multi($ind, array("'",'"'));
                                $column_name = $col_array[1];
                                $column_type = $col_array[3];
                                $explained_string .=$this->change_color(" Alter column \"".$column_name."\" in \"".$table."\" to type \"".$column_type."\"\n", 'yellow');
                            }else{
                                return "\n You have an error in your migration. Please address this immediately!\n\n";
                            }
                        }
                        //Drop Table Return
                        if(strpos(strtolower($ind),"drop(")===0) {
                            if($table!='') {
                                $explained_string .= $this->change_color("\n Drop table \"".$table."\" \n",'red');  
                            }
                        }
                        //Drop Column
                        if(strpos(strtolower($ind),"drop_column")===0) {
                            if($table!='') {
                                $col_array = $this->explode_multi($ind, array("'",'"'));
                                $column_name = $col_array[1];
                                $explained_string .= $this->change_color(" Drop column \"".$column_name."\" from \"".$table."\" \n",'red');  
                            }
                        }
                        //SQL Query
                        if(strpos(strtolower($ind),"query(")===0) {
                           $col_array = $this->explode_multi($ind, array("'",'"'));
                           $sql_array = array();
                           if(count($col_array)==3)
                           {
                               $sql_string = $col_array[1];
                           }else{
                                for($i=1;$i<count($col_array)-2;$i++)
                                {
                                         $sql_array[] = $col_array[$i];
                                }
                                $sql_string = implode("\"", $sql_array)."\"";
                           }
                           $explained_string .= "\n Run SQL Statement ".$this->change_color($sql_string,'purple')."\n";
                        }
                        //Insert Return
                        if(strpos(strtolower($ind),"insert(")===0) {
                            if($table!='') {
                                $insert_array = str_replace("insert(", '', $ind);
                                $insert_array = rtrim($insert_array);
                                $insert_array = substr($insert_array, 0, strlen($insert_array)-1);
                                $insert_array = "\$col_arr = ".$insert_array.";";
                                eval($insert_array);
                                if(count($col_arr)) {
                                        $explained_string .= $this->change_color("\n Insert into \"".$table."\"\n",'cyan');
                                        foreach($col_arr as $key=>$value) {
                                                $explained_string .= $this->change_color("      ".$key." = ".$value."\n",'cyan');
                                        }                                    
                                        $explained_string .= "\n";
                                }                                
                            }else{
                                 return "\n You have an error in your migration. Please address this immediately!\n\n";   //Escape on error
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
        $explained_string.= "n---------------------------------\n";
        $explained_string = "\n---------------------------------\n".$explained_string;
        return $explained_string;
    }
}
?>
