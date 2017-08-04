<?php

/* Linux ODBC install on ubuntu

  SEE: https://gist.github.com/ghalusa/97bf0b45a27d6b025d670752a7c62ec6

  apt-get install php7.0-odbc tdsodbc
	
	service apache2 restart
	
	

	/etc/php/7.0/mods-available/odbc.ini
	

  apt-get install unixodbc
	
	

*/

  echo "<pre>";

  echo "<h2>Available PDO drivers</h2>";
  print_r(PDO::getAvailableDrivers()); 


	try {
		$dbh = new PDO("odbc:DRIVER=FreeTDS;Server=infoboard-host.ch,1433;Database=IB32DB_45055_Hapa_Sync", "Hapa", '1234#Abc');
		
		$q = $dbh->prepare("select Table_Name, table_type from information_schema.[tables]");
		$q->execute();
		$table_fields = $q->fetchAll();
		print_r($table_fields);


		$q = $dbh->prepare("select * from T_RowGroup_Err");
		$q->execute();
		$table_fields = $q->fetchAll(PDO::FETCH_COLUMN);
		print_r($table_fields);

			
	} catch (PDOException $exception) {
		echo $exception->getMessage();
		exit;
	} 


  echo "</pre>";

  phpinfo();

?>