<?php

  // ------------------------------
	// fetch content from server simulating google bot
  // ------------------------------
  function getHttp( $aUrl ) {
		
		$response = new stdClass();
    // simulate google crawler
    $options = array( 
        CURLOPT_RETURNTRANSFER => true,     // return web page 
        CURLOPT_HEADER         => false,    // do not return headers 
        CURLOPT_FOLLOWLOCATION => true,     // follow redirects 
        CURLOPT_USERAGENT      => "Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)", // who am i 
        CURLOPT_AUTOREFERER    => true,     // set referer on redirect 
        CURLOPT_CONNECTTIMEOUT => 10,       // timeout on connect 
        CURLOPT_TIMEOUT        => 10,       // timeout on response 
        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects 
				CURLOPT_SSL_VERIFYPEER => false,    // do not verify SSL peer
    ); 
		
	  // try to fetch the content
		$retries = 0;
		do {
  		$retries++;
			
  		$ch = curl_init( $aUrl ); 
			curl_setopt_array( $ch, $options ); 
			$response->content = curl_exec( $ch ); 
			$response->err = curl_errno( $ch ); 
			$response->errmsg = curl_error( $ch ); 
			$response->header = curl_getinfo( $ch ); 
			curl_close( $ch );
			
			if ($response->errmsg!='') {
				echo "\n".$response->errmsg;
			}			
	
		} while ($response->header['http_code']<0 & $retries < 10);

		return $response->content; 
	}

?>