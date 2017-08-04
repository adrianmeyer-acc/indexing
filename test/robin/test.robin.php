<?php

  require_once( 'index.processor.phone.php' );
	
  function formatNumber( $aNumber ) {

		echo "\nOriginal: ".$aNumber.": ";
		echo "\nInternational: ".\index\IndexProcessorPhone::formatInternational( $aNumber );
		echo "\nNational: ".\index\IndexProcessorPhone::formatNational( $aNumber );

		echo "\n";
	}


  // test a phone number
  formatNumber( "+39 05212345679" );


?>