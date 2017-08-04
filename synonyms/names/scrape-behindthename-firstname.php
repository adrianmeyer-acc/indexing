<?php
  // *******************************************************************
  // PHP to scrape all first names from www.behindthename.com 
  // *******************************************************************

  require_once( 'scrape-utils.php' );
  require_once( '../../php/index.processor.php' );
  require_once( '../../php/html.document.php' );
	
  $GLOBALS[ 'ns' ] = array();

  // ------------------------------
	// name information
  // ------------------------------

	class NameInfo {
		var $name = '';
		var $synonyms = array();
		
		// ------------------------------
		// fetch names for one letter
		// ------------------------------
		function __construct( $aName ) {
			$GLOBALS[ 'ns' ][$aName] = $this;
			$this->name = $aName;
			$this->addSynonym( $aName );
		}
		
		// ------------------------------
		// add a synonym and check derivatives
		// ------------------------------
		function addSynonym( $aSynonym ){
			
			if ($this->name != $aSynonym ){
				$this->synonyms[ $aSynonym ] = true;
				$this->checkReverse( $aSynonym );
			}
	
			$unaccented = \Index\IndexTermProcessorBase::unaccent($aSynonym);
			if ($this->name != $unaccented ){
				$this->synonyms[ $unaccented ] = true;
				$this->checkReverse( $unaccented );
			}
	
			$germanized = \Index\IndexTermProcessorBase::germanize($aSynonym);
			if ($this->name != $germanized ){
				$this->synonyms[ $germanized ] = true;
				$this->checkReverse( $germanized );
			}
		}
		
		// ------------------------------
		// fetch names for one letter
		// ------------------------------
		function checkReverse( $aName ) {
			// add reverst to synonym
			if (!isset( $GLOBALS[ 'ns' ][$aName] )) {
				new NameInfo( $aName );
			}
			$GLOBALS[ 'ns' ][ $aName ]->synonyms[$this->name] = true;
		}
	}

  // ------------------------------
	// fetch names for one letter
  // ------------------------------
  function fetchLetter( $aLetter ) {
		
		$parser = new HTMLDocument();
		
		$pageNumber=0;
    $fetchedCount = 0;
		
    // loop through the content
    do {
			// get the list name nodes
			$pageNumber++;
			
			echo "Letter {$aLetter} - reading page {$pageNumber}";

			$url = "http://www.behindthename.com/names/letter/{$aLetter}";
      if ($pageNumber > 1) {
				$url.= "/{$pageNumber}";
			}

      // read the content
			$content = getHttp( $url );
			if ($content===FALSE) {
				echo "\nERROR: Could not load page {$pageNumber} for {$aLetter}\n";
				continue;
			}

			// parse the HTML
			$parser->load( $content );
      // read the names on the page			
  		$nameNodes = $parser->Root->findsByAttributeDown( 'class', 'browsename b0' );
			$nameNodes = array_merge( $nameNodes, $parser->Root->findsByAttributeDown( 'class', 'browsename b1' ));			
			echo " - ".count($nameNodes)." names\n";

      // read the total amount of names
      if ($pageNumber == 1) {
				$bodyElement = $parser->Root->findByAttributeDown( 'class', 'body' );
				$spanElements = $bodyElement->findsDown( 'span' );
				
				$resultLabel = array_pop($spanElements)->getText();
				$resultCount = substr( $resultLabel, 0, strpos( $resultLabel, 'results' ));
				$nameCount = (integer)preg_replace('/\D/', '', $resultCount );

  			echo "Letter {$aLetter} - reported name count is {$nameCount}\n";
			}

			// process name nodes
			foreach( $nameNodes as $node ) {
				
				// get the name from the first text node
				$name = mb_strtolower( html_entity_decode($node->Children[0]->Children[0]->Children[0]->getText()));
				new NameInfo($name);
				$fetchedCount++;
			}
			
		} while ($fetchedCount < $nameCount);		
	}
	
  // ------------------------------
	// fetch the synonyms
  // ------------------------------
  function fetchSynonyms( $aName ) {
		$parser = new HTMLDocument();
  	$url = "http://www.behindthename.com/names/extra.php?extra=r&terms=".urldecode( mb_strtoupper( $aName->name ));

    $content = getHttp( $url );
    if ($content===FALSE) {
			echo "ERROR: Could not fetch synonyms for: {$aName->name}\n";
			return;
		}

		// parse the HTML
		$parser->load( $content );		
		$nameNodes = $parser->Root->findsByAttributeDown( 'class', 'namelistcell' );
		
		// bild the sound-ex of original name
		$soundEx = soundex( $aName->name );

		// read all the synonyms
		foreach( $nameNodes as $node ) {
			$synonym = mb_strtolower( trim(html_entity_decode( $node->Children[0]->Children[0]->Children[0]->getText())));
			
			// only add synonym on similar sounding names
			if ($soundEx == soundex( $synonym )){
				$aName->addSynonym( $synonym );
			}
		}
	}

  // ****************************************************************
	// scrape behindthenames
  // ****************************************************************
  // loop through all letters to get names
  foreach (range('a', 'z') as $char) {
    fetchLetter( $char );
  }	

  // ****************************************************************
	// scrape synonyms
  // ****************************************************************
  // loop through all the names and find synonyms
  $total = count( $GLOBALS[ 'ns' ] );
	$progress = 0;
	$lastPercent = -1;
	$maxSynonymCount = 0;
  foreach ($GLOBALS[ 'ns' ] as $name) {
  	$progress++;

    if ($lastPercent < floor((100/$total)*$progress )) {
			$lastPercent = floor((100/$total)*$progress );
			echo "Loading synonyms: {$lastPercent}%\n";			
		}
		
    if (count($name->synonyms) == 0) {
			fetchSynonyms( $name );
		}
	}

  // sort the names
  function mb_sort($a, $b){
		return strcasecmp( $a->name, $b->name );
  }
	usort( $GLOBALS[ 'ns' ], 'mb_sort' );
	
  // ****************************************************************
  // write out the first name processor information
  // ****************************************************************
  $phpFile = fopen("index.processor.firstname.php", "w");
  fwrite( $phpFile, "\xEF\xBB\xBF" );

  foreach ($GLOBALS[ 'ns' ] as $name) {	

	  // skip over names that have no synonyms
	  if (count($name->synonyms)<=0) {
			continue;
		}
		
  	fwrite( $phpFile,  "        case '".str_replace("'", "''", $name->name )."': return array( " );
		ksort( $name->synonyms );
		
		$first = true;
		foreach( $name->synonyms as $synonym=>$dummy ) {
			if ( !$first ) {
       	fwrite( $phpFile,  "," );
			}
     	fwrite( $phpFile,  "'".str_replace("'", "''", $synonym )."'" );
			$first = false;
		}
		
  	fwrite( $phpFile,  " );\n" );
	}
	
	fclose($phpFile);

?>