<?php
/*
MAS90Mirror - basic PHP script to mirror data from Sage Software MAS 90/200 ERP to Microsoft SQL Server.

Here's how to setup:

1) Download + Install PHP on your server (php.net)

2) Create two system DSNs (make sure to use the 32 bit ODBC tool)
	-One using the MAS90 ProvideX driver connecting to your shared MAS90 folder; Name it whatever you like, but remember it, as you'll need to enter it in the PHP scripts
	-One using SQL Server connecting to your destination database

3) Create the tables that you want to mirror in SQL Server. You can use Crystal Reports designer to see the list of fields, as well as the required data type for each.

4) Make a copy of the import-template.php file for each table you want to mirror. It's best to rename it according to the table (ex: import-ar-customer.php)

5) Create a scheduled task to execute "php -f C:\Path_to_import_php_file.php". It's probably best to execute this during periods of low activity.

6) And you're done! This is a good temporary solution until you can upgrade to MAS 200 SQL and replicate the data instead of mirroring (http://www.sagemas.com/Products/Sage-ERP-MAS-200-SQL)
-----
*/

$mas90db = "DNS=MAS90;";

#destination db DSN - see README
$mssqldb_dsn = "MAS2SQL";
$mssqldb_username = "Administrator";
$mssqldb_password = "somegreatpassword";
$email_to_notify = "damato@atlasoutdoor.com";

#name of table to mirror
$table_name = "AR_Customer";

$start_time = time();
error_reporting(E_ALL);
ini_set('display_errors','On');

#helper
function addslashes_mssql($str){
	if (is_array($str)) {
	    foreach($str AS $id => $value) {
	        $str[$id] = addslashes_mssql($value);
	    }
	} else {
	    $str = str_replace("'", "''", $str);    
	}
	
	return $str;
}

#db connects
$db=odbc_connect($mas90db,"","");
$mssql=odbc_connect($mssqldb_dsn,$mssqldb_username,$mssql_password);

// Select all rows from the desired table
$sql="SELECT * FROM $table_name";
$query=odbc_exec($db, $sql);
$p = 0;


#Drop all rows from destination table (ensures data integrity)
$check = "TRUNCATE TABLE $table_name;";
odbc_exec($mssql,$check);	


while($row = odbc_fetch_array($query))
{	
	$p++;
	
	#Clear values
	$keys = "";
	$values = "";

	#Build the query
	foreach ($row as $k => $v)
	{
		$keys .= $k.",";
		$values .= "'".addslashes_mssql($v)."',";
	}

	#Join it all together
	$keys = substr($keys,0,strlen($keys)-1);
	$values = substr($values,0,strlen($values)-1);
	$insert = sprintf("INSERT INTO $table_name (%s) VALUES (%s);",$keys,$values);
	odbc_exec($mssql,$insert);	
}

$end_time = time();
$elapsed_time = date("i\m, s\s",($end_time-$start_time));;

$to      = $email_to_notify;
$subject = "$table_name mirrored succesfully";
$message = "$table_name mirrored successfully in $elapsed_time with $p rows inserted.";

$headers = 'From: MAS2SQL' . "\r\n" .
    'X-Mailer: PHP/' . phpversion();

mail($to, $subject, $message, $headers);

?>