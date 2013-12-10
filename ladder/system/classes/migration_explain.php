<?php
class migration_explain extends Migration{
    /**
     * Unused __contruct. Just here to stop the __construct of Migration from running. 
     */
    public function __construct() {}
    /**
     * 
     * @param Integer $migration_id Migration ID of file. 
     * @todo Better comment function.
     * @return Array Exploaded array of the "up()" funtion in the migration. 
     */
    public function get_source_file($migration_id)
    {
        //Get the file name of the migration file. 
        $migration_file = $this->file_name($migration_id);
        //Open the migration file. 
        $file = fopen($migration_file,"r");
        //Read the migration file into the $source String. 
        $source = fread($file, filesize($migration_file));
        //trim off all prior to "public function up()".
        $source = strstr($source, "public function up()");
        //Get the end point of the string by looking for "public function down()".
        $end_point = strpos($source, "public function down()");
        //Trim up the end of the string. 
        $source = substr($source,0, $end_point);
        //Explode the string by ";";
        $source = explode(";", $source);
        //Return the exploaded string.
        return $source;
    }
    /**
     *  Changes Color for CLI return
     * 
     * @param String $string String in which to wrap color around. 
     * @param String $color Color in which to chang eto 
     * @todo Possibly add more colors. 
     * @return string Returns color wrapped string for output in CLI
     * 
     */
    private function change_color($string, $color='green')
    {
            //Change Color To Red
            if($color==='red') {
                    $color_start = "\033[0;31m";
                    $color_end = "\033[0m";
            }
            //Change Color to green
            if($color==='green') {
                    $color_start = "\033[0;32m";
                    $color_end = "\033[0m";
            }
            //Change Color to light bold green
            if($color==='green-bold') {
                    $color_start = "\033[1;32m";
                    $color_end = "\033[0m";
            }
            //Change Color to yellow.
            if($color==='yellow') {
                    $color_start = "\033[0;33m";
                    $color_end = "\033[0m";
            }
            //Change Color to purple
            if($color==='purple') {
                    $color_start = "\033[0;34m";
                    $color_end = "\033[0m";
            }
            //Change Color to Cyan
            if($color==='cyan') {
                    $color_start = "\033[0;36m";
                    $color_end = "\033[0m";
            }
            //Apply Color to string
            $new_string = $color_start.$string.$color_end;
            if(Config::item('config.explain_colors')===FALSE)
            {
                $new_string = $string;
            }
            //Return string.
            return $new_string;
    }
    /**
     * Does an "explode" with multiple needles. 
     * 
     * @param String $source String in which to expload
     * @param Array $needles Array of needles in which to explode.
     * @param String $glue_symbol Symbol that is not anywhere in the string. Default is ~.
     * @todo Add additional validation to make sure all fields are entered correctly.
     * @return Array Exploaded string. 
     */
    private function explode_multi($source,array $needles, $glue_symbol="~")
    {
            //Assign a temp string to be manipulated. 
            $temp_string = $source;
            //Loop through needles
            foreach($needles as $nd)
            {
                    //Explode string by specific needle and put it into temporary array.
                    $temp_array = explode($nd, $temp_string);
                    //Glue the string back together as the temporary string with teh delimeter being the $glue_symbol.
                    $temp_string = implode($glue_symbol,$temp_array);
            }
            //Explode the string based on the glue symbol and store it into the temporary array.
            $temp_array = explode($glue_symbol,$temp_string);
            //Return the temporary array.
            return $temp_array;
            
    }
    /**
     * Runs explain command
     * 
     * @
     * @param Array $source Source array which is the migration file exploded by ";". 
     * @param Boolean $add Is migration run through the add or not. 
     * @todo Echo out options for column creation
     * @return String String to echo to CLI.
     */
    public function explain_migration(array $source, $add=false)
    {
        //Define empty $explained_string to be added to. 
        $explained_string = '';
        //Check to make sure the source is appropriate array. 
        if(is_array($source) && count($source))
        {
            //Loop through source array.
            foreach($source as $rs)
            {
                //Explode the source by "->" to get each command individually. 
                $commands = explode("->", $rs);
                //Reset the $table variable to null.
                $table = null;
                //Check to make sure the explode worked correctly. 
                if(count($commands))
                {
                    //Loop through each command.
                    foreach($commands as $ind)
                    {
                        
                        //Define Table Variable.
                        //Check to see if command has "table" or "create_table" in it.
                        if(strtolower(substr($ind, 0, 5))=="table" || strpos(strtolower($ind), "create_table")!==false)
                        {
                            //Explode the command by Quote and single quote
                            $tab = $this->explode_multi($ind, array("'",'"'));
                            //Define the table variable as the table name.
                            $table = $tab[1]; 
                        }
                       //Create Table Return.
                        //Check to see if command has "create_table" in it. 
                        if(strpos(strtolower($ind), "create_table")!==false) {
                            //Add a light green Create Table to the $explained_string.
                            if($add) {
                                    $explained_string .= $this->change_color("\n Created table \"".$table."\"\n", 'green-bold');
                            }else {
                                    $explained_string .= $this->change_color("\n Create table \"".$table."\"\n", 'green-bold');
                            }
                            
                        }
                        //Column Return
                        //Check to see if command is column
                        if(strpos(strtolower($ind), "column")===0) {
                            //Validate that table is set.   
                            if($table!='') {
                                //Explode the command by double and single quotes. 
                                $col_array = $this->explode_multi($ind, array("'",'"'));
                                //Get the column name and store it into $column_name variable. 
                                $column_name = $col_array[1];
                                //Get teh column type and store it into $column_type variable. 
                                $column_type = $col_array[3];
                                //Add a green "Add Column" string to $explained_string. 
                                if($add) {
                                        $explained_string .= $this->change_color(" Added column \"".$column_name."\" to \"".$table."\" with the type \"".$column_type."\"\n", 'green');
                                }else {
                                        $explained_string .= $this->change_color(" Add column \"".$column_name."\" to \"".$table."\" with the type \"".$column_type."\"\n", 'green');
                                }
                                
                            }else{
                                //If table is not set, let them know they have an error in their migration. 
                                return "\n You have an error in your migration. Please address this immediately!\n\n";
                            }
                        }
                        //Alter Column Return
                        //Check to see if command has alter_column in it. 
                        if(strpos(strtolower($ind),"alter_column")===0) {
                            //Check to make sure the table is set prior to running alter_column. 
                            if($table!='') {
                                //Explode the command by single and double quotes. 
                                $col_array = $this->explode_multi($ind, array("'",'"'));
                                //Get the column name and set it in the $column_name variable. 
                                $column_name = $col_array[1];
                                //Get the column type and set it in the $column_type variable. 
                                $column_type = $col_array[3];
                                //Return a yellow "Alter Column" string. 
                                if($add) {
                                        $explained_string .=$this->change_color(" Altered column \"".$column_name."\" in \"".$table."\" to type \"".$column_type."\"\n", 'yellow');
                                }else {
                                        $explained_string .=$this->change_color(" Alter column \"".$column_name."\" in \"".$table."\" to type \"".$column_type."\"\n", 'yellow');
                                }
                                
                            }else{
                                //If the table is not set, let the user know they have an error in their migration. 
                                return "\n You have an error in your migration. Please address this immediately!\n\n";
                            }
                        }
                        //Drop Table Return
                        //Check to see if the command has "drop" in it. 
                        if(strpos(strtolower($ind),"drop(")===0) {
                            //Check to make sure the table is set prior to running drop. 
                            if($table!='') {
                                //Return a red "drop table" string. 
                                if($add) {
                                        $explained_string .= $this->change_color("\n Dropped table \"".$table."\" \n",'red');  
                                }else {
                                        $explained_string .= $this->change_color("\n Drop table \"".$table."\" \n",'red');
                                }
                                
                            }else{
                                //If the table is not set, let the user know they have an error in their migration. 
                                return "\n You have an error in your migration. Please address this immediately!\n\n";
                            }
                        }
                        //Drop Column
                        //Check to see if the command has "drop_column" in it. 
                        if(strpos(strtolower($ind),"drop_column")===0) {
                            //Check to make sure the table is set prior to running drop. 
                            if($table!='') {
                                //Explode the command by Double and Single quotes. 
                                $col_array = $this->explode_multi($ind, array("'",'"'));
                                //Get the column name that is being dropped. 
                                $column_name = $col_array[1];
                                //Rern a red "drop column" string. 
                                if($add) {
                                        $explained_string .= $this->change_color(" Droped column \"".$column_name."\" from \"".$table."\" \n",'red');  
                                }else {
                                        $explained_string .= $this->change_color(" Drop column \"".$column_name."\" from \"".$table."\" \n",'red');
                                }
                            }else{
                                //If the table is not set, let the user know they have an error in their migration. 
                                return "\n You have an error in your migration. Please address this immediately!\n\n";
                            }
                        }
                        //SQL Query
                        //Check to see if the command has "Query" in it.
                        if(strpos(strtolower($ind),"query(")===0) {
                            //Explod ethe command by double and single quotes. 
                           $col_array = $this->explode_multi($ind, array("'",'"'));
                           //Define $sql_array as an empty array. 
                           $sql_array = array();
                           //Check to see if the sql statement itself had any double or single quotes. 
                           if(count($col_array)==3)
                           {
                               //Set the $sql_string to the sql statement being ran. 
                               $sql_string = $col_array[1];
                           }else{
                               //If it does have single and double quotes it will be exploaded with the rest.
                               //Loop through the all after the first and before the last element. 
                                for($i=1;$i<count($col_array)-2;$i++)
                                {
                                        //Add them to the $sql_array.
                                         $sql_array[] = $col_array[$i];
                                }
                                //Glue the $sql_array back together wtih a single quote;
                                $sql_string = implode("\'", $sql_array)."\"";
                           }
                           //Return a purple Run SQL string, 
                           if($add) {
                                $explained_string .= "\n Ran SQL Statement ".$this->change_color($sql_string,'purple')."\n";
                           }else {
                                $explained_string .= "\n Run SQL Statement ".$this->change_color($sql_string,'purple')."\n";
                           }
                        }
                        //Insert Return
                        //Check to see if the command has insert in it.
                        if(strpos(strtolower($ind),"insert(")===0) {
                            //Check to make sure the table is set. 
                            if($table!='') {
                                //Remove the word "insert(" from the string. 
                                $insert_array = str_replace("insert(", '', $ind);
                                //Trim excess right whitespace. 
                                $insert_array = rtrim($insert_array);
                                //Remove the last bracket from the insert array. 
                                $insert_array = substr($insert_array, 0, strlen($insert_array)-1);
                                //Add a $col_arr = to the front of the string. 
                                $insert_array = "\$col_arr = ".$insert_array.";";
                                //Run the strin gas php defining $col_arr as an array.
                                eval($insert_array);
                                //Check to make sure the array has elements in it. 
                                if(count($col_arr)) {
                                        //Add a cyan "Insert into table" string into explained_string
                                        if($add) {
                                                $explained_string .= $this->change_color("\n Inserted into \"".$table."\"\n",'cyan');
                                        }else{
                                                $explained_string .= $this->change_color("\n Insert into \"".$table."\"\n",'cyan');
                                        }
                                        //Loop through the $col_arr array.
                                        foreach($col_arr as $key=>$value) {
                                                //Add a line for each column filled to $explained_string. 
                                                $explained_string .= $this->change_color("      ".$key." = ".$value."\n",'cyan');
                                        }
                                        //Add a carriage return to $explained_string. 
                                        $explained_string .= "\n";
                                }                                
                            }else{
                                //If table is not set return an error. 
                                 return "\n You have an error in your migration. Please address this immediately!\n\n";   //Escape on error
                            }   
                        }     
                        
                         //Insert Return
                        //Check to see if the command has insert in it.
                        if(strpos(strtolower($ind),"index(")===0) {
                            //Check to make sure the table is set. 
                            if($table!='') {
                                $col_array = $this->explode_multi($ind, array("'",'"'));
                                $indexed_column = $col_array[1];
                                if($add) {
                                    $explained_string .= $this->change_color("\n indexed column \"".$indexed_column."\"\n",'green');
                                }else {
                                    $explained_string .= $this->change_color("\n index column \"".$indexed_column."\"\n",'green');
                                }
                            }else{
                                //If table is not set return an error. 
                                 return "\n You have an error in your migration. Please address this immediately!\n\n";   //Escape on error
                            }   
                        }   
                    }
                }
            }
        }else{
            //If the migration is empty return "Migration Empty"
            $explained_string = "Migration Empty";
        }
        //Check if the migration is empty. 
        if($explained_string=='')
        {
            //If the migration is empty reutrn "Migration Empty"
            $explained_string = "Migration Empty";
        }
        //Add a closing line. 
        $explained_string.= "\n---------------------------------\n";
        //Add an opening line
        $explained_string = "\n---------------------------------\n".$explained_string;
        //Return the explained string. 
        return $explained_string;
    }
}
?>
