# Ladder

## What is it?

Ladder started life as a very simple database migration system. It's grown over
time to the beast you see before you. It's written in PHP 5, and supports the 
popular MySQL database server.

## What would I use this for?

Well, we use it in conjunction with source control (SVN, Git, Mercurial, et al)
in order to track the changes we make to the database alongside our source code.

This allows us all to work on a project and know whether or not we have applied
various changes to our local development environments.

Migrations have at least two methods: `up()` and `down()`. The `up()` method is
run when the migration hasn't been applied and should have been. `down()` is run
when the migration is being removed from the database. Logically, a `down()`
method should do the opposite to its counterpart `up()` method. Dropping a
column instead of adding it, etc.

## Cool. How do I use it?

If you're reading this file, you've already got it (or you're reading it on Github/BitBucket…).
Have a look in the config/ directory. Copy each file ending in ".sample", and
remove the ".sample" part. Edit your copies of the files, plugging in your own
settings such as database server.

Also, in the root of the project (the directory where this file is), take a copy
of ladder.php.sample, rename it to ladder.php, change any settings in there
(usually works out of the box, unless you're going for an advanced setup),
and run the ladder.php script: `php ladder.php`.

If you don't see a list of valid commands, something has gone wrong. Sorry about
that!

## Does it do *x* or *y*?

I'd advise you to check the documentation, but unfortunately, you're reading it.
A brief list of supported features follows:

 1. Table creation / alteration.<br />
	Tables can be created - or altered if they already exist - seamlessly.

 1. Column additions, alterations, removal.<br />
	Columns can be added to tables, altered, moved, and removed.
	`Table->column()`, `Table->alter_column()`, and `Table->drop_column()`.

 1. Index creation and removal.<br />
	`Table->index()`, and `Table->drop_index()`.

 1. CSV import/unimport.<br />
	CSVs can be imported, either by inserting the data, or updating based on
	optional fields. They can also be un-imported when the migration is removed.
	See the properties:
	* Migration->import_data
	* Migration->import_update
	* Migration->import\_key\_fields
	* Migration->unimport_data
	* Migration->unimport\_key\_fields

 1. Key-value storage per migration.<br />
	This is a new one. It allows you to - for example - store the id of a new
	record you create in migration *x*, then later refer to that id in migration
	*y*. A real-world example of this might be creating a new news article, then
	later having to remove that exact article, even if a user has changed its
	content. Look at `Migration->set()`, `Migration->get()`, and `Migration->remove()`.

## Are there any examples?

If you create a new migration (`php ladder.php create *migration_name*`), the
template that is created contains a lot of the options you can use, but
commented out.

Alternatively, take a look at http://drarok.com/ladder/ - I'm adding some
examples there as requests come in.


##SQL Class Documentation.

####Create Table
This creates a single table with one column ID.
<pre>$this->create_table("EXAMPLE_TABLE");</pre>

####Adding columns to tables. 
This is how to create a table with multiple columns.
<pre>$this->create_table("EXAMPLE_TABLE")
->column("EXAMPLE_COLUMN_1", "integer")
->column("EXAMPLE_COLUMN_2", "varchar");
</pre>
Different Column types supported by Ladder
   * binary
   * varbinary
   * varchar
   * char
   * tinyint
   * smallint
   * mediumint
   * integer
   * bigint
   * timestamp
   * datetime
   * date
   * time
   * float
   * decimal
   * tinyblob
   * blob
   * mediumblob
   * longblob
   * tinytext
   * text
   * mediumtext
   * longtext
   * enum
    
Each column has a set of defaults for it which can be modified within the `ladder/system/helpers/sql.php` file.

Assigning different column options.
The options for columns are setting using an array in the 3rd argument of the column function. 
<pre>$this->table("EXAMPLE_TABLE")
->column("EXAMPLE_COLUMN_3","varchar",array('default'=>0, 'null'=>false, 'limit'=>255));
</pre>
This example also uses the `->table` method. This allows you to select the table you want to modify.

####Altering Columns
Altering columns is a lot like adding them you just use the `->alter_column` method.
<pre>$this->table("EXAMPLE_TABLE")
->alter_column("EXAMPLE_COLUMN_3","Integer",array('default'=>1,`null`=>true,'limit'=>8));
</pre>
####Dropping a Column
You drop a column using the `->drop_column()` method.
<pre>
$this->table("EXAMPLE_TABLE")->drop_column("EXAMPLE_COLUMN_3");
</pre>
####Dropping a Table
You drop a table using the `->drop()` method. 
<pre>$this->table("EXAMPLE_TABLE")->drop();
</pre>

####Running SQL statement. 
To run an SQL statement you use `->db->query()`
<pre>$this->db->query("DELETE FROM EXAMPLE_TABLE WHERE ID=1");
</pre>