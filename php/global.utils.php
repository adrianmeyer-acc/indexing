<?php
//====================================================================================
/**
 * Global utilities
 * Responsibilities: Collection of 'random' utility functions
 * @author Adrian Meyer <adrian.meyer@rocketmail.com>
 */
//====================================================================================

  //====================================================================================
  /**
   * Utility Service
   * Responsibilities: Create and manage services
   * @author Adrian Meyer <adrian.meyer@rocketmail.com>
   */  
  class Utils {
  
    // *************************************
    /** 
     * Check the format of an email address
     * @param string $aEmail The email to check
     * @return bool
     */
    static function checkEmailFormat( $aEmail ) {
      return filter_var( $aEmail, FILTER_VALIDATE_EMAIL );
    }
  
    // *************************************
    /** 
     * Check the format of a MYSQL date
     * @param string $aDate The date to be checked
     * @return bool
     */
    static function checkMySqlDateFormat( $aDate )
    {
       $format = 'Y-m-d H:i:s';
      $d = \DateTime::createFromFormat($format, $aDate);
      return $d && $d->format($format) == $aDate;
    }  
    
    // *************************************
    /** 
     * Check the format of a mac address
     * @param string $aMacAddress The mac address to check
     * @return bool
     */
    static function checkMacFormat( $aMacAddress ) {
      return (preg_match('/^(?:[0-9a-fA-F]{2}[:;.\-]?){6}$/', $aMacAddress) == 1);
    }
  
    // *************************************
    /** 
    * Creates a random string with a given length
    * @param integer $aLength A length for the random string
    * @param string $aAllowedCharacters The allowed characters to create radom string
    * @return string
    */
    public static function createRandomString( $aLength = 64, $aAllowedCharacters = "23456789BCDFGHKLMNPQRSTVWXZbcdfghklmnpqrstvwxz" ) {
      // only characters that won't spell anything bad and no digits that can be confused like 1,I or 0,O
      $characters = $aAllowedCharacters;
      mt_srand((double)microtime()*1000000);
      $newstring="";
      while(strlen($newstring)<$aLength){
        $newstring .= $characters[ mt_rand(0, strlen($characters)-1)];
      }
      return $newstring;
    }
  
    // *************************************
    /** 
    * Creates a random filename with a directory structure
    * @param string $aBaseDirectory A directory used as the base
    * @param string $aExtension A file extension to be returned
    * @param integer $aStorageLevel A level of directory structure to hash
    * @return string
    */
    public static function createHashPathFileName( $aBaseDirectory, $aExtension, $aStorageLevel = 3 ) {
  
      // top directory will be the level
      $levelDirectory = 'storage-level-'.$aStorageLevel;
      
      // create the hash
      $hash = Utils::createRandomString( $aStorageLevel, '23456789bcdfghklmnpqrstvwxz' );
    
      $directory =   $aBaseDirectory.'/'.$levelDirectory.'/'.implode( '/', str_split( $hash ));
      
      // create recursive directory
      mkdir( $directory, 0777, true );
      
      $randomTries = 0;
      do {
        $randomTries++;
        $filename = $directory.'/'.$hash.'-'.Utils::createRandomString(64, '23456789bcdfghklmnpqrstvwxz' ).'.'.$aExtension;
        if (!file_exists($filename)) {
          return $filename;
        }
        
      }  while ( $randomTries < 100 );
      
      throw new \Exception( 'Could not create hash file in directory '.$directory );
    }
    
    // *************************************
    /** 
     * Output an array in readable format
     * @param array $aArray An array
     * @param string $aVariableName The variable name of the array
     * @param string $aPrepend The parent array item. Used in recursions for multidimensional arrays
     * @param string $aLevel The hierachical level of the array item
     * @return NULL
     */
    static public function showArray($aArray,$aVariableName='array',$aPrepend='',$aLevel='0')
    {
      $str = '';
      if(is_array($aArray)) {
    
        // on the 1st level we use the variable name
        if ($aLevel=='0') 
          $aPrepend = $aVariableName;
        
        foreach($aArray as $k=>$v)
        {
          if (gettype($k) == 'string' )
            $str.= Utils::showArray( $v, $aVariableName, $aPrepend."['{$k}']", $aLevel + 1 );
          else
            $str.= Utils::showArray( $v, $aVariableName, $aPrepend."[{$k}]", $aLevel + 1 );
        }
      }
      else
        if (is_object( $aArray ))
          $str .= $aPrepend."&nbsp;=&nbsp;Objects: &lt;".get_class($aArray)."&gt;<br>";
        else
          $str .= $aPrepend."&nbsp;=&nbsp;{$aArray}<br>";
    
      return $str;
    }  
    
    // *************************************
    /** 
     * Looks for arrray value case insensitive. Returns NULL when nothing is found
     * @param array $aArray Array to be searched
     * @param string $aKey Key to look for
     * @param bool $aDeleteOnHit If true the item with the matching key is remved from the array
     * @return mixed
     */
    static function getArrayValue( &$aArray, $aKey, $aDeleteOnHit = false ){
      foreach( $aArray as $key => $value ) {
        if ( strcasecmp( $key, $aKey ) == 0 ) {
					if ($aDeleteOnHit) {
  					$value = $aArray[$key];
						unset($aArray[$key]);
            return $value;
					} else {
						return $aArray[$key];
					}
        }
      }
      return NULL;
    }
  
    // *************************************
    /** 
     * Looks for a POST or GET parameter case insensitive. Returns NULL when not found
     * @param string $aValueName The value to get from POST or GET
     * @param bool $aDeleteOnHit If true the item with the matching key is remved from the array
     * @return var
     */
    static function getPostOrGetValue( $aValueName, $aDeleteOnHit = false ) {
      $value = Utils::getArrayValue( $_POST, $aValueName, $aDeleteOnHit );
      if ( $value == NULL ) {
        return Utils::getArrayValue( $_GET, $aValueName, $aDeleteOnHit );
      } else {
        return $value;
      }
    }
    
    // *************************************
    /** 
     * Output an array in readable format
     * @param string $aFrom The from email address
     * @param string $aTo The email recipient
     * @param string $aSubject A message subject
     * @param string $aMessage The message in HTML format
     * @return NULL
     */
    static function sendHtmlEmail( $aFrom, $aTo, $aSubject, $aMessage ) {
    
      // message
      $message = '<html><head><title>'.htmlentities( $aSubject ).'</title></head><body>'.$aMessage.'</body></html>';
      
      // To send HTML mail, the Content-type header must be set
      $headers  = 'MIME-Version: 1.0' . "\r\n";
      $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
      $headers .= "From: {$aFrom}\r\n";
      
      return imap_mail( $aTo, $aSubject, $message, $headers, NULL, NULL, $aFrom );
      
    }
  
    // *************************************
    /** 
     * Set all the properties on an object pulling from an assocuated array
     * @param object $aObject The object to set the properties on
     * @param array $aData Associated array to get the data from
     * @return void
     */
    static public function setPropertiesOnObject( $aObject, $aData ) {
      if ( $aData ) {
        foreach ( $aData as $key=>$value ) {
          if ( property_exists( $aObject, $key )) {
            if ( $value !== NULL && is_integer($aObject->$key)) {
              $aObject->$key = intval($value);
            } else {
              $aObject->$key = $value;
            }
          }
        }
      }
    }  
    
    // *************************************
    /** 
     * Get all the properties on an object pushing them into an assocuated array
     * @param object $aObject The object to get the properties from
     * @param array $aData Associated array to copy the properties to
     * @return void
     */
    static public function getPropertiesFromObject( $aObject, &$aData ) {
      if ( isset( $aObject ) && isset( $aData )) {
        foreach($aObject as $property => $value ) {
          if (!is_object( $value )) {
            $aData[$property] = $value;
          }
        }
  
      }
    }  
  
    // *************************************
    /** 
     * Get all the properties on an object pushing them into an assocuated array
     * @param object $aFromObject The object to copy the properties from
     * @param object $aToFromObject The object to copy the properties to
     * @return void
     */
    static public function copyProperties( $aFromObject, $aToFromObject ) {
      if ( isset( $aFromObject ) && isset( $aToFromObject )) {
        foreach($aFromObject as $property => $value ) {
          if ( !is_object( $value )) {
            $aToFromObject->$property = $value;
          }
        }
  
      }
    }  
    
    // *************************************
    /** 
     * Prepend and append provided values to all the matches on words that start with any search word
     * @param string $aString The string that will be searched
     * @param string $aSearchWords SPACE separated word that will be matched. Multiple spaces will be ignored
     * @param string $aPrepend The value to be prepended at the matching position
     * @param string $aAppend The value to be appended to the the matching position
     * @param bool $aRequireAllMatches Only wrap if all words match
     * @param integer $aMinimumWordLenght Minimum word length to wrap
     * @return void
     */
    static function wrapStartingWithMatches( $aString, $aSearchWords, $aPrepend, $aAppend, $aRequireAllMatches = true, $aMinimumWordLenght=3 ) {
      
      // escape the reserved characters for regular expressions and break out words
      $words = array_filter( explode( ' ', preg_quote( strtoupper( $aSearchWords ))));
      
      // create a pattern with all the words following a word delimiter case insensitive
      $pattern = '/([- ,:;\(\[\{]{1,1})('.implode( '|', $words ).')/i';
  
      // prepare words for multiple check
      $words = array_flip( $words );
      foreach( $words as $word => $index ){
        if (mb_strlen($word)<$aMinimumWordLenght) {
          unset($words[$word]);
        }
      }
  
      // callback function for regular expression replacement
      $callback = function( $matches ) use ( $aPrepend, $aAppend, &$words ) {
        // remove word from word list to check for all replaced words
        unset($words[strtoupper(substr( $matches[0], 1 ))]);
        return substr( $matches[0], 0, 1 ).$aPrepend.substr( $matches[0], 1 ).$aAppend;;
      }; 
  
      $output = trim( preg_replace_callback( $pattern, $callback, ' '.$aString ));
      if ($aRequireAllMatches && count($words)>0){
        return $aString;
      } else {
        return $output;
      }
    }
    
    
    // *************************************
    /** 
     * Encodes a given string into HTML data
     * @param string $aString The string that will be encoded
     * @return string
     */
    static function encodeHtml( $aString ) {
      return htmlentities( $aString, ENT_COMPAT, 'utf-8' );
    }
  
    // *************************************
    /** 
     * Return the display label for a user
     * @param string $aEmail The email
     * @param string $aTitle The title
     * @param string $aFirstname The first name
     * @param string $aLastname The last name
     * @param string $aAccreditation List of accreditations
     * @return string
     */
    static public function getUserLabel( $aEmail, $aTitle, $aFirstname, $aLastname, $aAccreditation ){
  
      // assemble more information to be displayed    
      $label = array();
      
      // add fistname
      if (trim($aFirstname)!='') {
        $label[] = trim( $aFirstname );
      }
  
      // add lastname
      if (trim($aLastname)!='') {
        $label[] = trim( $aLastname );
      }
      
      if (count($label) > 0) {
        // prepend title
        if (trim($aTitle)!='') {
          array_unshift( $label, trim( $aTitle ));
        }
  
        // add accreditation
        if (trim($aAccreditation)!='') {
          $label[] = '- '.trim( $aAccreditation );
        }
      } else {
        // add email information
        if ($aEmail!='') {
          $label[] = $aEmail;
        } else {
          $label[] = '-';
        }
      }
      return implode( ' ', $label );
    }  
  
    // *************************************
    /** 
     * Return the server url
     * @return string
     */
    static function getServerUrl()
    {
      $secure = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
      $protocol = strtolower($_SERVER["SERVER_PROTOCOL"]);
      return substr($protocol, 0, strpos($protocol, "/")).$secure."://".$_SERVER['SERVER_NAME'];
    }
    
    // *************************************
    /** 
     * Union 2 associated arrays by combining data and expanding columns
     * Fill values from previous occurance
     * @param array $aArray1 The first array
     * @param array $aArray1 The second array
     * @param bool $aSortedByKey If TRUE the array is sorted by the key
     * @return string
     */
    static function unionAndFillArray( $aArray1, $aArray2 ) {
      
      // check the parameters
      if ( count($aArray1) <= 0 && count($aArray2) <= 0) {
        return NULL;
      }

      if ( count($aArray1) <= 0 && count($aArray2) > 0) {
        return $aArray2;
      }
      
      if ( count($aArray2) <= 0 && count($aArray1) > 0) {
        return $aArray1;
      }

      // get all the keys and sort them
      $keys = array_unique( array_merge( 
        array_keys( $aArray1 ), 
        array_keys( $aArray2 )));
      sort( $keys );

      // get all the columns
      $columns = array_unique( array_merge( 
        array_keys( reset($aArray1 )), 
        array_keys( reset($aArray2 ))));

      $data = array();
      $lastValues1 = array();
      $lastValues2 = array();
      foreach( $keys as $key ) {
        // check if key is in both
        if (isset($aArray1[$key])&&isset($aArray2[$key])) {
          $data[$key] = array_merge( 
            array_fill_keys( $columns, NULL ), 
            $aArray1[$key],
            $aArray2[$key] 
          );
          $lastValues1 = $aArray1[$key];
          $lastValues2 = $aArray2[$key];
        } else
        // check if key is array 1
        if (isset($aArray1[$key])) {
          $data[$key] = array_merge( 
            array_fill_keys( $columns, NULL ), 
            $aArray1[$key],
            $lastValues2 );
          $lastValues1 = $aArray1[$key];
        } else
        // check if key is array 2
        if (isset($aArray2[$key])) {
          $data[$key] = array_merge( 
            array_fill_keys( $columns, NULL ), 
            $lastValues1,
            $aArray2[$key] );
          $lastValues2 = $aArray2[$key];
        }         
      }
      
      return $data;
    }
    
    
    // *************************************
    /** 
     * Return the relative referrer URL if known. Otherwise the default
     * @param string $aDefault The default URI if referrer is unknown
     * @return string
     */    
     static function getRefererUrl( $aDefault='/' ){
      if (isset($_SERVER["HTTP_REFERER"])){
        $urlParts = parse_url( $_SERVER["HTTP_REFERER"] );
        
        $result = $urlParts['path'];
        
        if (isset($urlParts['query'])){
          $result.= '?'.$urlParts['query'];
        }
        
        return $result;
      }
      return $aDefault;
    }
    
    // *************************************
    /** 
     * Return the svg data for an embedded IMG tag
     * @param string $aSvgData The SVG data of the image
     * @return string
     */    
    static function getEmbeddedSvgSource( $aSvgData ) {
      $imageData = str_replace( '<?xml version="1.0" encoding="utf-8"?>', '', $aSvgData );
      $imageData = str_replace( '<!-- Generator: Adobe Illustrator 16.0.0, SVG Export Plug-In . SVG Version: 6.00 Build 0)  -->', '', $imageData );
      $imageData = str_replace( '<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">', '', $imageData );

      $imageData = urlencode(trim($imageData));
      
      // remove single quotes used in font filenames
      $imageData = str_replace('%27%22', "%22", $imageData );
      $imageData = str_replace('%22%27', "%22", $imageData );

      // revert back to single quote11
      $imageData = str_replace('%22', "'", $imageData );
      $imageData = str_replace('+', " ", $imageData );

      return "data:image/svg+xml;charset=utf-8,{$imageData}";
      
    }

		// *************************************
		/** 
		 * Returns a filename from a string that is valid with most OS
		 * @return string
		 */
		static function getValidFilename( $aString ){
			
			//characters that are  illegal on any of the 3 major OS's
			$reserved = preg_quote('\/:*?"<>|', '/');
			
			//replaces all characters up through space and all past ~ along with the above reserved characters
			return preg_replace("/([\\x00-\\x20\\x7f-\\xff{$reserved}])/e", "'_'", $aString);
		}
		
    // *************************************
    /** 
     * Returns the IP address of the client
     * @return string
     */
    public static function getClientIpAddress() {
      if (!empty($_SERVER['HTTP_CLIENT_IP']))             // check ip from shared internet
        $ip=$_SERVER['HTTP_CLIENT_IP'];
      elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   // check ip passed by proxy
        $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
      else
        $ip=$_SERVER['REMOTE_ADDR'];
      return $ip;
    }
  
    // *************************************
    /** 
     * returns the script URI 
     * @return string
     */
    public static function getScriptUri() 
    {
      $secure = isset($_SERVER['HTTPS']);
      return 'http'. ($secure ? 's' : null).'://'.$_SERVER['SERVER_NAME'].Utils::getScriptUrl();
    }
    
    // *************************************
    /** 
     * returns the script URL depending on what is available in the environment
     * @return string
     */
    public static function getScriptUrl() {
      $script_url = NULL;
      if (!EMPTY($_SERVER['SCRIPT_URL']))   
        $script_url = $_SERVER['SCRIPT_URL'];
      elseif (!EMPTY($_SERVER['REDIRECT_URL'])) 
        $script_url = $_SERVER['REDIRECT_URL'];
      elseif (!EMPTY($_SERVER['REQUEST_URI'])) {
        $p = PARSE_URL($_SERVER['REQUEST_URI']);
        $script_url = $p['path'];
      } else {
        throw new HttpException( 'Couldn\'t determine $_SERVER["SCRIPT_URL"].' );
      }
      return $script_url;
    }		
		
		// ------------------------------
		// fetch content from server simulating google bot
		// ------------------------------
		static function getHttp( $aUrl ) {
			
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
		
			} while ($response->header['http_code']<0 & $retries < 10);
	
			return $response; 
		}		
  }
  
?>