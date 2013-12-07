<?php
$migration_id = (int) $params['migrate-to'];

$explain = new migration_explain;

$source = $explain->get_source_file($migration_id);

$explain_statement = $explain->explain_migration($source);

echo $explain_statement;


?>
