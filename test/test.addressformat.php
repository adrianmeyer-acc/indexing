<?php

  require_once( '../php/address.formatter.php' );  
	
	$data = new \address\AddressFormatterData();
	$data->Street = 'Bergstrasse';
	$data->StreetNumber = 25;
	$data->City = 'Thun';
	$data->Zip = '3100';
	$data->CountryISO2 = 'CH';
//	print_r( \address\AddressFormatter::format( $data ) );

	$data = new \address\AddressFormatterData();
	$data->BoxNumber = 25;
	$data->City = 'Thun';
	$data->Zip = '3100';
	$data->CountryISO2 = 'CH';
	print_r( \address\AddressFormatter::format( $data ) );

	$data = new \address\AddressFormatterData();
	$data->BoxNumber = 13;
	$data->City = 'Genève';
	$data->Zip = '1200';
	$data->CountryISO2 = 'CH';	
	print_r( \address\AddressFormatter::format( $data ) );


?>