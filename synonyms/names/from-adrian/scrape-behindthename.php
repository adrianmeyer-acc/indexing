<?php

  // *******************************************************************
  // PHP to scrape all names from www.behindthename.com 
  // *******************************************************************

  require_once( 'html.parser.php' );
	
  // ------------------------------
	// name information
  // ------------------------------
	$names = Array();
	class NameInfo {
		var $name = '';
		var $isMale = false;
		var $isFemale = false;
		var $synonyms = array();
		var $languages = array();
	}

  // ------------------------------
	// fetch names for one letter
  // ------------------------------
  function fetchLetter( $aLetter, &$names ) {
		
		$parser = new HtmlParser();
		
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
			$parser->parse( $content );
  		$nameNodes = $parser->RootNode->findNodesByAttributeDown( 'class', 'browsename' );			
			echo " - ".count($nameNodes)." names\n";

      // read the total amount of names
      if ($pageNumber == 1) {
				$resultCount = $parser->getElementById( 'div_results' );		
				$nameCount = (integer)preg_replace('/\D/', '', $resultCount->getText());		
  			echo "Letter {$aLetter} - reported name count is {$nameCount}\n";
			}

			// process name nodes
			foreach( $nameNodes as $node ) {
				
				$info = new NameInfo();
				
				$info->name = html_entity_decode(
				  $node->ChildNodes[0]->ChildNodes[0]->ChildNodes[0]->getText());
					
				$info->isMale = (integer)( $node->findNodesByAttributeDown('class','masc') != NULL );
				$info->isFemale = (integer)( $node->findNodesByAttributeDown('class','fem') != NULL );
				
				$languageNodes = $node->findNodesByAttributeDown('class','usg' );
				foreach( $languageNodes as $language ) {
					$info->languages[] = $language->getText();
				}
					
				$names[ $info->name ] = $info;

				$fetchedCount++;
			}
			
		} while ($fetchedCount < $nameCount);		
	}
	
  // ------------------------------
	// fetch the synonyms
  // ------------------------------
  function fetchSynonyms( $aName ) {
		$parser = new HtmlParser();
		$url = "http://www.behindthename.com/php/extra.php?extra=r&terms=".
		  urldecode( $aName->name );

    $content = getHttp( $url );
    if ($content===FALSE) {
			echo "ERROR: Could not fetch synonyms for: {$aName->name}\n";
			return;
		}

		// parse the HTML
		$parser->parse( $content );		

		$nameNodes = $parser->RootNode->findNodesByAttributeDown( 'class', 'namelistcell' );			
    
		// read all the synonyms
    $synonyms = array();
		foreach( $nameNodes as $node ) {
      $synonyms[] = trim(html_entity_decode(
				  $node->ChildNodes[0]->ChildNodes[0]->ChildNodes[0]->getText()));
		}
		
		$aName->synonyms = array_unique( $synonyms );
	}

  // ------------------------------
	// fetch content from server simulating google bot
  // ------------------------------
  function getHttp( $aUrl ) {
		
    // simulate google crawler
		$header = stream_context_create(array(
			'http' => array(
				'method' => 'GET',
				'User-Agent' => "Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)"
			)
		));
		
	  // try to fetch the content
		$retries = 0;
		do {
  		$retries++;
  		$content = @file_get_contents($aUrl, false, $header);		
		} while ($content===FALSE || $retries > 10);

    return $content;		
	}
	

  // ****************************************************************
	// scrape behindthenames
  // ****************************************************************


  // loop through all letters to get names
  foreach (range('a', 'z') as $char) {
    fetchLetter( $char, $names );
  }	

//  fetchLetter( 'g', $names );

  $total = count( $names );
	$progress = 0;
	$lastPercent = -1;
	$maxSynonymCount = 0;
  // loop through all the names and find synonyms
  foreach ($names as $name) {
  	$progress++;

    if ($lastPercent < floor((100/$total)*$progress )) {
			$lastPercent = floor((100/$total)*$progress );
			echo "Loading synonyms: {$lastPercent}%\n";			
		}
		
    if (count($name->synonyms) == 0) {
			fetchSynonyms( $name );
		}
		
  	$maxSynonymCount = max($maxSynonymCount,count($name->synonyms));
	}
	
	$synonymHeader = array();
	for( $i=1;$i<=$maxSynonymCount;$i++ ) {
  	$synonymHeader[] = "syn_{$i}";
	}

  // echo the UTF-8 based info
	
  $tabFile = fopen("behindthename-all.txt", "w");
  fwrite( $tabFile, "\xEF\xBB\xBF" );
	fwrite( $tabFile, "name\tismale\tisfemale\tsynonyms\tlanguages\n" );

  $synonymFile = fopen("behindthename-synonyms.csv", "w");
  fwrite( $synonymFile, "\xEF\xBB\xBF" );
	fwrite( $synonymFile, "name,ismale,isfemale,".implode(',',$synonymHeader)."\n" );


  foreach ($names as $name) {	

	  // skip over names that have no synonyms
	  if (count($name->synonyms)<=0) {
			continue;
		}
		
  	fwrite( $tabFile, 
		  "{$name->name}\t".
			"{$name->isMale}\t".
			"{$name->isFemale}\t".
			implode( ',', $name->synonyms )."\t" .
			implode( ',', $name->languages )."\n" 
		);
			
  	fwrite( $synonymFile, 
		  "{$name->name},".
			"{$name->isMale},".
			"{$name->isFemale},".
			implode( ',', $name->synonyms ).
			str_repeat( ',', max(0,$maxSynonymCount-count($name->synonyms) - 1 ))."\n"
		);
	}
	
	fclose($tabFile);
	fclose($synonymFile);

?>