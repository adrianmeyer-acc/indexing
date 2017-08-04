<?php
//====================================================================================
/**
 * File containing code for term
 * @author Adrian Meyer <adrian.meyer@rocketmail.com>
 */
//====================================================================================

  namespace index;

  /** Exception class for term */
  class TermException extends \Exception{};

  //====================================================================================
  /**
   * Model for term
   * Responsibilities: Hold all data and functionality associated with an term
   */
  class TermModel {

    /** Physical index abstract identifier */
    var $term_id = 0;

    /** The actual term in upper case */
    var $term = '';

    /** Lists the matched abstracts in a php array */
    var $php_abstracts = NULL;
		
    /** Lists all the terms that are the next full match up */
		var $FullTerms = array();

    // *************************************
    /**
     * Load the term by a term
		 * @param string $aTerm The term to load
     * @return \index\TermModel
     */
    static function loadByTerm( $aTerm ){			
      $call = \DatabaseService::newQuery( '
        select 
          t.`term_id`,
          t.`term`,
          t.`php_abstracts`
        from 
          `indx_Term` t
        where t.`term` = $term' );
      $call->setParameter( 'term', $aTerm );
      $call->execute();

			$instance = new TermModel();
      $call->setObjectProperties( $instance );
			
			if ( $instance->term_id > 0) {
				return $instance;
			}
			return false;
    }  

    // *************************************
    /**
     * Load the possible full terms for the term
     * @return void
     */
    function loadFullTerms(){			
      $call = \DatabaseService::newQuery( '
        select 
				  t.*
        from 
          `indx_Term` t,
          `indx_TermFull` tf
        where t.`term_id` = tf.`to_term_id`
				and tf.`from_term_id` = $term_id' );
      $call->setParameter( 'term_id', $this->term_id );
      $call->execute();
      $this->FullTerms = $call->getObjectArray( '\index\TermModel', 'term_id' );
    }  

    // *************************************
    /**
     * Save the terms
		 * @param array $aTermList The terms to be saved
     * @return void
     */
    static function createOrLoadTerms( &$aTermList ){

      // save the terms
			\DatabaseService::startTransaction();
      try {
				
				// split into 1000 term working lists
				$chunks = array_chunk( $aTermList, 2000, true );
        			
				foreach ($chunks as $chunk) {
					
					// create dummy select list with the terms in this chunk
          array_walk($chunk, function (&$item, $key, $prefix) {
					  $item = "select '".\DatabaseService::escapeString( $key )."' a ";
          }, NULL );
	
	        $sql = "
				    insert into `indx_Term` (term) 
            select a from ( \n";
  			  $sql .= implode( "\n UNION ALL ", $chunk );
  			  $sql .= ") a where not exists( select * from `indx_Term` t where t.`term` = a.a )	";

          // insert terms that are missing
          $callInsert = \DatabaseService::newQuery( $sql );
					$callInsert->execute();

					// create list with just the terms 
          array_walk($chunk, function (&$item, $key, $prefix) {
					  $item = "'".\DatabaseService::escapeString( $key )."'";
          }, NULL );

	        $sql = "
				    select `term`, `term_id` from `indx_Term`
            where `term` in ( ";
  			  $sql .= implode( ",", $chunk ).")";

          // insert terms that are missing
          $callSelect = \DatabaseService::newQuery( $sql );
					$callSelect->execute();
					
					$data = $callSelect->get2ColumnIndexedArray();
					foreach( $data as $key => $id ) {
						$aTermList[ $key] = $id;
					}
				} 
				
  			\DatabaseService::commit();
			} catch (Exception $e) {
  			\DatabaseService::rollback();
			}
    }  

    // *************************************
    /**
     * Rebuild the abstract list of the term list
		 * @param array $aTermList The terms list to be updated
     * @return VOID
     */
    static function rebuildAbstractLists( $aTermList, $aTermMatchOptions ) {

	    // save the cached list
      function doProcess( &$aTerms, &$aTermMatchOptions ) {
				if ( count($aTerms )<=0 ) {
					return;
				}

				$callSelect = \DatabaseService::newQuery( '
					select `term_id`, `abstract_id`
					from `indx_AbstractTerm`
					where `term_id` in ('.implode( ',', $aTerms).')' );
				$callSelect->execute();
				$data = $callSelect->get2Column2DIntegerArray();
				

				$callUpdate = \DatabaseService::newQuery( '
					UPDATE `indx_Term` 
					SET `is_full` = $is_full, `php_abstracts` = $php_abstracts
					WHERE `term_id` = $term_id' );

				foreach( $data as $termId => $abstracts ) {
					// insert the serialized PHP array of abstract IDs      
					$callUpdate->setParameter( 'term_id', $termId );
					$callUpdate->setParameter( 'php_abstracts', serialize( $abstracts ) );
					$callUpdate->setParameter( 'is_full', (integer)($aTermMatchOptions[$termId]==moFULL) );
					$callUpdate->execute();
				}
			}

      // save the terms      			
			\DatabaseService::startTransaction();
      try {				
				// split into 1000 term working lists
				$chunks = array_chunk( $aTermList, 1000, true );
        			
				foreach ($chunks as $chunk) {
					doProcess( $chunk, $aTermMatchOptions );
				}

  			\DatabaseService::commit();
			} catch (Exception $e ) {
  			\DatabaseService::rollback();
			}
		}

    // *************************************
    /**
     * Rebuild the abstract list of the term list
		 * @param array $aTermList The terms list to be updated
     * @return VOID
     */
    static function saveFullLinks( $aFullTermList, $aTermList ) {

	    // save the cached list
      $doSaveFullLinks = function( &$aLinks ) {
				if ( count($aLinks )<=0 ) {
					return;
				}

				$sql = "
					insert into `indx_TermFull` (`from_term_id`, `to_term_id`) 
					select * from ( \n";
				$sql .= implode( "\n UNION ALL ", $aLinks );
				$sql .= ") a where not exists( ";
				$sql .= "select * from `indx_TermFull` tf ";
				$sql .= "where tf.`from_term_id` = a.a and tf.`to_term_id` = a.b )";

				// insert terms that are missing
				$callInsert = \DatabaseService::newQuery( $sql );
				$callInsert->execute();
			};

      // save the terms  links     			
			\DatabaseService::startTransaction();
      try {				

        $items = array();
				foreach( $aFullTermList as $from=>$toList) {					
					foreach( $toList as $to=>$dummy ) {					
						$items[] = "select {$aTermList[$from]} a,{$aTermList[$to]} b";
						
						if ( count($items)>=1000) {
							$doSaveFullLinks($items);
							$items = array();
						}
					}
				}
				$doSaveFullLinks($items);

  			\DatabaseService::commit();
			} catch (Exception $e ) {
  			\DatabaseService::rollback();
			}
		}
		
    // *************************************
    /**
     * Rebuild the links between terms and synonyms
		 * @param array $aSynonymList The synonyms mapped
		 * @param array $aTermList The terms list with the IDs
     * @return VOID
     */
    static function saveSynonymLinks( $aSynonymList, $aTermList ) {

	    // save the cached list
      $doSaveSynonymLinks = function( &$aLinks ) {
				if ( count($aLinks )<=0 ) {
					return;
				}

				$sql = "
					insert into `indx_TermSynonym` (`from_term_id`, `to_term_id`) 
					select * from ( \n";
				$sql .= implode( "\n UNION ALL ", $aLinks );
				$sql .= ") a where not exists( ";
				$sql .= "select * from `indx_TermSynonym` tf ";
				$sql .= "where tf.`from_term_id` = a.a and tf.`to_term_id` = a.b )";

				// insert terms that are missing
				$callInsert = \DatabaseService::newQuery( $sql );
				$callInsert->execute();
			};

      // save the terms  links     			
			\DatabaseService::startTransaction();
      try {				

        $items = array();
				foreach( $aSynonymList as $toTerm=>$synonyms) {
					
					// there might be terms that have less than 3 characters. Make sure we skip
					if (!isset($aTermList[$toTerm])) {
						continue;				
					};
					
					foreach( $synonyms as $synonym=>$dummy ) {
						$items[] = "select {$aTermList[$synonym]} a,{$aTermList[$toTerm]} b";
						
						if ( count($items)>=1000) {
							$doSaveSynonymLinks($items);
							$items = array();
						}
					}
				}
				$doSaveSynonymLinks($items);

  			\DatabaseService::commit();
			} catch (Exception $e ) {
  			\DatabaseService::rollback();
			}
		}
		
    // *************************************
    /**
     * Get the full term suggestions given on partial terms
		 * @param array $aTermList The terms list to be used as baseline
		 * @param integer $aLimit The limit of the counts
     * @return VOID
     */
    static function getSuggestions( $aTermList, $aLimit ) {
			if (count($aTermList)==0) {
				return;
			}

      // --------------------------
      // add the basics
      // --------------------------
			$sql = "select";
			$sql.= "\n  count(distinct at1.`abstract_id`) as cnt,";
			$sql.= "\n  group_concat(distinct a1.`abstract_id` order by a1.`concept_id`, a1.`abstract_id` desc) as abstract_ids,";
			$sql.= "\n  at1.`term_id`";
			foreach ( $aTermList as $index => $item ) {
				$i=$index+2;
				$sql.= ",\n  at{$i}.`term_id`";
			}

      // --------------------------
      // add the actual terms
      // --------------------------
			$sql.= ",\n  ( select `term` from `indx_Term` where `term_id` = at1.`term_id` ) as term1";
			foreach ( $aTermList as $index => $item ) {
				$i=$index+2;
				$sql.= ",\n  ( select `term` from `indx_Term` where `term_id` = at{$i}.`term_id` ) as term{$i}";				
			}
			
      // --------------------------
      // main abstract table. All terms have to share this abstract
      // --------------------------
			$sql.= "\nfrom";
			$sql.= "\n  `indx_Abstract` a1,";

      // --------------------------
      // tables to get from partial term to the 1st full term
      // --------------------------
			foreach ( $aTermList as $index => $item ) {
				$i=$index+1;
				$sql.= "\n  `indx_TermFull` tf{$i},";
			}
			
      // --------------------------
      // links between terms and abstracts
      // --------------------------
			$sql.= "\n  `indx_AbstractTerm` at1";
			foreach ( $aTermList as $index => $item ) {
				$i=$index+2;
				$sql.= ",\n  `indx_AbstractTerm` at{$i}";
			}

      // --------------------------
			// linke up terms and abstract associations
      // --------------------------
			foreach ( $aTermList as $index => $item ) {
				$i=$index+1;
				if ($index==0) {
					$sql.="\nwhere ";
				} else {
					$sql.="\nand ";
				}

				$sql.= "tf{$i}.`from_term_id` = {$item->term_id}";
				$sql.= "\nand at{$i}.`term_id` = tf{$i}.`to_term_id`";
			}

      // --------------------------
			// check for matching abstracts across terms
      // --------------------------
			$sql.= "\nand a1.`abstract_id` = at1.`abstract_id`";
			foreach ( $aTermList as $index => $item ) {
				$i=$index+2;
  			$sql.= "\nand a1.`abstract_id` = at{$i}.`abstract_id`";
			}

      // --------------------------
			// suggest only different terms
      // --------------------------
			for($i=1;$i<=count($aTermList);$i++) {
				for($j=$i+1;$j<=count($aTermList)+1;$j++) {
					$sql.= "\nand at{$i}.`term_id` <> at{$j}.`term_id`";
				}
			}

      // --------------------------
			// This link makes sure that synonym to synonym or full term to synonym though
			// abstracts are not shown as suggestions. otherwise synonyms will be shown as suggestions
			// because they of course also match n times same as full term
      // --------------------------
			for($i=0;$i<=count($aTermList);$i++) {
				$j = $i+1;
				if ($i<count($aTermList)) {
  				$sql.= "\nand at{$j}.matchoption_id in (1,2)";
				} else {
  				$sql.= "\nand at{$j}.matchoption_id = 1";
				}
			}

      // --------------------------
			// make sure there are no synonym links between suggested terms
      // --------------------------
			for($i=1;$i<=count($aTermList);$i++) {
				for($j=$i+1;$j<=count($aTermList)+1;$j++) {
					$sql.= "\nand not exists( select * from indx_TermSynonym where from_term_id=at{$i}.term_id and to_term_id=at{$j}.term_id )";
				}
			}

			$sql.= "\ngroup by at1.`term_id`";
			foreach ( $aTermList as $index => $item ) {
				$i=$index+2;
				$sql.= ", at{$i}.`term_id`";
			}
			$sql.= "\norder by 1 desc";
			$sql.= ", term1";				
			foreach ( $aTermList as $index => $item ) {
				$i=$index+2;
				$sql.= ", term{$i}";
			}			

			$sql.= "\nlimit ".$aLimit;

			$call = \DatabaseService::newQuery( $sql );
			$call->execute();
			return $call->getAssociatedArray();
		}		
		

    // *************************************
    /**
     * Clean up a term for indexing
     * @param string $aTerm the UTF-8 encoded term 
     * @param bool $aRetainCase If false the term will be changed to lower case 
     * @return array Returns a list of terms
     */
    static function cleanTerm( $aTerm, $aRetainCase = false ) {			
			$output = '';
			$spaceAdded = false;
			$charCount = 0;

	    $list = array();
			if ($aRetainCase) {
      	preg_match_all('/./u', $aTerm, $list );
			} else {
      	preg_match_all('/./u', mb_strtolower( $aTerm, 'UTF-8' ), $list );
			}
			
			foreach( $list[0] as $i => $char ) {
        // skip over space				
				if ( !mb_ereg_match('/^\s*$/',$char) ) {
					$output.=$char;
					$spaceAdded = false;
					$charCount++;
					continue;
					
				} else {
					
					// avoid multiple spaces
					if (!$spaceAdded) {
						$output.=' ';
					}
					
					$spaceAdded = true;
				}
			}

			$result = array();
			// remote trailing "funny characters"
			foreach( explode( ' ', trim( $output )) as $item ){
//				$item = preg_replace('/[^\p{L}\p{Nd}\p{Mn}_]+$/', '', $item);
				$item = mb_ereg_replace('/[^\p{L}\p{Nd}\p{Mn}_]+$/', '', $item);
				if (mb_strlen($item)>=1) {
					$result[] = mb_substr( $item, 0, 99 );
				}
			}
			
			return $result;
		}
  }

?>