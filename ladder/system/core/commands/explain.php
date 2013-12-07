<?php
/**
 * @todo Have the system run an explain when a migration up is ran. Possible make it an option like "php ladder.php migrate --explain"
 */

//Get the migraiton id from the parameter. 
$migration_id = (int) $params['migrate-to'];
//Define explain as a migration_explain object. 
$explain = new migration_explain;
//Get the source array by the id.
$source = $explain->get_source_file($migration_id);
//Get the explained statement from the source array. 
$explain_statement = $explain->explain_migration($source);
//Echo the command that is running. 
echo "\nRunning Explain on Migration #".$migration_id;
//Echo the explained statement. 
echo $explain_statement;


?>
