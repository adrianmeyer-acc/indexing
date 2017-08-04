<?php
//====================================================================================
/** 
 * Indexing Service
 * @author Adrian Meyer <adrian.meyer@rocketmail.com>
 */
//====================================================================================

  require_once( 'index.abstract.php' );
  require_once( 'index.filter.group.php' );
  require_once( 'index.filter.value.php' );
  require_once( 'index.term.php' );
  require_once( 'html.document.php' );

  // Translation Tokens
	define( 'lblSearching', 1 );
	define( 'lblSearchingFor', 2 );
	define( 'lblMatchingAll', 3 );
	define( 'lblMatchingPartial', 4 );
	define( 'lblInConcepts', 5 );

  //====================================================================================
  /**
   * Exception for indexer
   * Responsibilities: Handle indexing exceptions
   * @author Adrian Meyer <adrian.meyer@rocketmail.com>
   */
  class IndexException extends \Exception{};

  //====================================================================================
  /**
   * Service for index
   * Responsibilities: Manage all apsects of indexing
   * @author Adrian Meyer <adrian.meyer@rocketmail.com>
   */
  class IndexService {

    // internal singleton instance    
    private static $IndexServiceInstance = NULL;

    /** Labels for UI */
		private static $Labels = array();
  
    // *************************************
    /** 
     * Hide constructor for this singleton.
     * @return void
     */
    private function __construct() {
    }

    // *************************************
    /** 
     * Get the singleton instance
     * @return void
     */
    static function getInstance() {
      if (!isset(self::$IndexServiceInstance)) {
        self::$IndexServiceInstance = new IndexService();

        // default translations
        self::$Labels[(string)lblSearching] = 'Searching...';
        self::$Labels[(string)lblSearchingFor] = 'Searching for:';
        self::$Labels[(string)lblMatchingAll] = 'Showing all matching %s';
        self::$Labels[(string)lblMatchingPartial] = 'Showing first %d of %d %s';
        self::$Labels[(string)lblInConcepts] = 'in';
        self::$Labels['c-'.acCONTACT] = 'Contacts';
        self::$Labels['c-'.acTICKET] = 'Tickets';
        self::$Labels['c-'.acMESSAGE] = 'Messages';
        self::$Labels['c-'.acOBJECT] = 'Objects';				
        self::$Labels['c-'.acWIKI] = 'Wiki Pages';
      }
      return self::$IndexServiceInstance;
    }
    
    // *************************************
    /** 
     * Get the singleton instance
     * @param integer $aConcept Identifies the type of concept
     * @param integer $aIdentifier Identifies the entity to be indexed
     * @return \index\AbstractModel
     */
    static function buildAbstract( $aConcept, $aIdentifier ){
      $abstract = new \index\AbstractModel();
      $abstract->concept_id = $aConcept;
      $abstract->identifier = $aIdentifier;
      self::getInstance()->abstracts[$aConcept.':'.$aIdentifier] = $abstract;
      return $abstract;
    }

    
    // *************************************
    /** 
     * Get the label for a concept
     * @param integer $aConcept Identifies the type of concept
     * @param string $aLabel The label of the concept
     * @return void
     */
    static function setConceptLabel( $aConcept, $aLabel ){
			self::$Labels['c-'.$aConcept] = $aLabel;
    }

    // *************************************
    /** 
     * Get the label for a displayed label
     * @param string $aToken Identifies the token
     * @param string $aLabel The label of the concept
     * @return void
     */
    static function setLabel( $aToken, $aLabel ){
			self::$Labels[(string)$aToken] = $aLabel;
    }

    
    // *************************************
    /** 
     * Rebuild pending indexes
     * @return void
     */
    static function rebuildPending() {
      self::rebuild( self::getInstance()->abstracts );
    }
    
    // *************************************
    /** 
     * Rebuild the abstracts in the list
     * @return void
     */
    static private function rebuild( &$aList ) {
      // lists the terms as key and value as term_id once saved      
      $termList = array();
      
      // lists the terms as key and value with term that is the next possible full term
      $fullTermList = array();

      // lists the terms as key and value with the synonym
      $synonymTermList = array();
  
      $time_start = microtime(true); 

      echo "\nPreparing abstracts for rebuild...";  

      // create the abstract if they don't exist
      $abstractIds = \index\AbstractModel::prepareForRebuild( $aList );
  
      echo (microtime(true) - $time_start)."s";
      $time_start = microtime(true); 
      
      echo "\nBuilding term lists...";  
      
      // process the abstracts
      foreach( $aList as $abstract ) {
        $abstract->terms = array();
        $abstract->matchOptions = array();

        // process each segment                
        foreach( $abstract->segments as $index => $segment ) {
          
          // load all the segments
          $document = new HTMLDocument();
          $document->load( $segment );
          $termElements = $document->getElementsByTagName('span');
					
					// skip if there is only static data on segment
					if ($termElements == false){
						continue;
					}
                      
          // populate index for terms within <span> tags
          foreach( $termElements as $element ) {
						
            // explode the terms depending on the match requirements
            switch (strtolower( $element->getAttribute('match'))){
              case 'full':
								require_once( 'index.processor.full.php' );
								$explodedTerms = \index\IndexProcessorFull::process(html_entity_decode( $element->getText()));
              break;
							
              case 'phone':
								require_once( 'index.processor.phone.php' );
								$explodedTerms = \index\IndexProcessorPhone::process(html_entity_decode( $element->getText()));
							break;

              case 'email':
								require_once( 'index.processor.email.php' );
								$explodedTerms = \index\IndexProcessorEmail::process(html_entity_decode( $element->getText()));
              break;
							
              case 'lastname':
								require_once( 'index.processor.lastname.php' );
								$explodedTerms = \index\IndexProcessorLastname::process(html_entity_decode( $element->getText()));
              break;
							
              case 'firstname':
								require_once( 'index.processor.firstname.php' );
								$explodedTerms = \index\IndexProcessorFirstname::process(html_entity_decode( $element->getText()));
              break;
							
              default:
								require_once( 'index.processor.default.php' );
								$explodedTerms = \index\IndexProcessorDefault::process(html_entity_decode( $element->getText()));
            }
            
            // remove match attributes
            $element->setAttribute( 'match', false );
            
            foreach( $explodedTerms as $fullTerm => $terms ) {
							// remember the last full term to manage synonyms
							$lastFullTerm = $fullTerm;
							
              foreach ($terms as $partial=>$matchOption ) {

                // build global term list
                if (!isset($termList[$partial])) {
                  $termList[$partial] = TRUE;
                }
                
                // check for synonyms
                if ($matchOption==moSYNONYM) {
									$lastFullTerm = $partial;
                  // collect synonyms, create bi-directional link in indx_TermSynonym
                  $synonymTermList[$fullTerm][$partial] = true;
                  $synonymTermList[$partial][$fullTerm] = true;
								}

                // Check if term is already set on abstract, if not add it
                if (!isset($abstract->terms[$partial])) {
                  $abstract->terms[$partial] = $matchOption;
                  $fullTermList[$partial][$lastFullTerm] = true;
                } else {
                  // if already set and FULL match is required change to full match. 
									// just in case the same term had a partial hit on the same abstract before.
                  if ($matchOption==moFULL) {
                    $abstract->terms[$partial] = $matchOption;
                    // prepare data for indx_TermFull 
                    $fullTermList[$partial][$lastFullTerm] = true;
                  }
                }
              }
            }
						$abstract->segments[$index] = $document->getHtml();
						
          }
        }
      }      

      echo (microtime(true) - $time_start)."s";
      $time_start = microtime(true); 
      echo "\nSaving terms...";  

      // create the terms if they don't exist, This is done with bulk update for performance
      \index\TermModel::createOrLoadTerms( $termList );

      echo (microtime(true) - $time_start)."s";
      $time_start = microtime(true); 
      echo "\nSaving full term links...";  

      // create the links from partial to full terms
      \index\TermModel::saveFullLinks( $fullTermList, $termList );

      echo (microtime(true) - $time_start)."s";
      $time_start = microtime(true); 
      echo "\nSaving synonym links...";  

      // create the links from synonyms to full terms
      \index\TermModel::saveSynonymLinks( $synonymTermList, $termList );

      echo (microtime(true) - $time_start)."s";
      $time_start = microtime(true); 

      echo "\nAbstract associations...";

      // save the term associations
      $termMatchOptions = \index\AbstractModel::saveTermAssociations( $aList, $termList );
      $abstractList = array();
      foreach( $aList as $abstract ) {
        $abstractList[$abstract->abstract_id] = $abstract;
      }

      echo (microtime(true) - $time_start)."s";
      $time_start = microtime(true); 

      echo "\nRebuild abstract lists of terms...";  

      // rebuild the abstract list, This is done with bulk update for performance
      \index\TermModel::rebuildAbstractLists( $termList, $termMatchOptions );

      echo (microtime(true) - $time_start)."s";

      $time_start = microtime(true); 
      echo "\nEnable abstracts...";  

      \index\AbstractModel::finalizeAndEnable( $abstractList );
      
      echo (microtime(true) - $time_start)."s";
      $time_start = microtime(true); 
    }
		
    // *************************************
    /** 
     * Search for items
     * @return void
     */
    static function search( $aTerm, $aPreferredConceptId ) {      
      $cleanTerms = \index\TermModel::cleanTerm( $aTerm );
      
      // the output data
      $output = new StdClass();
      $output->term = $aTerm;
			$output->lbl = array();
			$output->lbl[lblSearching] = self::$Labels[lblSearching];
			$output->lbl[lblSearchingFor] = self::$Labels[lblSearchingFor];
      $output->ts = \index\TermModel::cleanTerm( $aTerm, true );
      
      $termList = array();
      $allTermsHit = true;

      // ----------------------------------------------------------------------------
      // process the terms building abstract_ids that hit
      // ----------------------------------------------------------------------------
      foreach ( $cleanTerms as $termItem ) {
        if ( $term = \index\TermModel::loadByTerm( $termItem )) {
          $term->loadFullTerms();
          $termList[] = $term;

          // build the abstract ID list
          if (!isset($abstractIds)) {
            $abstractIds = unserialize( $term->php_abstracts );
          } else {
            $abstractIds = array_intersect( $abstractIds, unserialize( $term->php_abstracts ));
          }
        } else {
          $abstractIds = array();
          $allTermsHit = false;
        }
      }
      
      // ----------------------------------------------------------------------------
      // process the terms and build suggestions with full terms
      // ----------------------------------------------------------------------------
      $abstractSuggestionIds = array();
      if (count($termList)>0) {
        
        // get 8 suggestions
        $suggestions = \index\TermModel::getSuggestions( $termList, 8 );        
        $abstractSuggestionIds = array();
        
        // build the list of full term suggestions
        foreach($suggestions as $item) {
          $element = new StdClass();
          $element->cnt = (integer)$item["cnt"];
          $element->ts = array();
          $abstractSuggestionIds = array_unique( array_merge( 
            $abstractSuggestionIds, explode( ',', $item["abstract_ids"] ))); 
          
          // only full term matches
          for( $i=1;$i<=count($termList)+1;$i++) {
            $element->ts[] = $item["term{$i}"];
          }
          
          $output->tcs[] = $element;
        }
      }
      
      // ----------------------------------------------------------------------------
      // add concept summaries
      // ----------------------------------------------------------------------------
      if ( isset($abstractIds) && count($abstractIds)>0) {
        $concepts = \index\AbstractModel::getCountsPerConcept($abstractIds);

        // check if we need to move a preferred concept to the front
        if (isset($concepts[$aPreferredConceptId])) {
          $concepts = array($aPreferredConceptId => $concepts[$aPreferredConceptId]) + $concepts;
        }
        
        // use strings as key because json will sort integer keys ascending
        $output->cs = array();
        foreach( $concepts as $key=>$concept) {
          $output->cs[$key]["li"] = self::$Labels[ lblInConcepts ];
          $output->cs[$key]["lc"] = self::$Labels[ 'c-'.$key ];
          $output->cs[$key]["cnt"] = $concept['concept_cnt'];
        }
      }

      // ----------------------------------------------------------------------------
      // add the top abstract suggestions
      // ----------------------------------------------------------------------------
      $output->as = array();
      
			if (count($abstractIds)>0 ) {
				
				// ho many suggections to display
				$suggestionCount = 8;
				
				$abstractsByConcepts = \index\AbstractModel::getForSuggestions( $abstractIds, $suggestionCount );
				
				// check if we need to move a preferred concept to the front
				if (isset($abstractsByConcepts[$aPreferredConceptId])) {
					$abstractsByConcepts = array($aPreferredConceptId => $abstractsByConcepts[$aPreferredConceptId]) + $abstractsByConcepts;
				}
				
				$abstracts = array();
				foreach( $abstractsByConcepts as &$concept ) {
					
					// first concept has priority, all abstracts except make room for the 1st abstract of all other concepts
					if (count($abstracts)==0) {
						for( $i=0; $i<count($concept);$i++ ){
							if ( $i>0 && $i > count($concept) - count($abstractsByConcepts) ) {
								$concept[$i]['prio'] = 3;
							} else {
								$concept[$i]['prio'] = 1;
							}
							$abstracts[] = &$concept[$i];
						}
					} else {
						for( $i=0; $i<count($concept);$i++ ){
							if ( $i == 0 ) {
								$concept[$i]['prio'] = 2;
							} else {
								$concept[$i]['prio'] = 4;
							}
							$abstracts[] = &$concept[$i];
						}
					}        
				}
				
				// remove the lower quality items from bottom up
				$removePriority = 4;
				while ( count($abstracts) > $suggestionCount ) {
	
					// remove from bottom up, based on priorities
					for( $i=count($abstracts)-1; $i>=0; $i-- ){
						if ( isset($abstracts[$i]) && $abstracts[$i]['prio'] == $removePriority) {
							unset($abstracts[$i]);
							continue 2;
						}
					}
					// remove higher prio items if no other are found
					$removePriority--;
				}
				
				$conceptPositions = array();
				
				// load abstracts 
				foreach( $abstracts as $abstract ) {
					$data = new StdClass();
					$data->i = $abstract['identifier'];
					$data->u = $abstract['url'];
					$data->ic = $abstract['icon'];
					$data->a = unserialize( str_replace( '\"', '"', $abstract['abstract'] ));
					
					// group the abstracts into concept hierarchies
					if (!isset($output->as[$abstract['concept_id']])) {
  					$concept = new StdClass();
						$concept->cid = $abstract['concept_id'];
						$concept->as = array();
						$output->as[$abstract['concept_id']] = $concept;
					}
					$output->as[$abstract['concept_id']]->as[] = $data;
				}
				
        // update the label
        foreach( $output->as as $concept_id=>$concept ) {
					
					// check if all or just some abstracts of the concept are displayed
					if ( $output->cs[$concept_id]['cnt'] == count( $concept->as ) ){
						$output->as[$concept_id]->l = sprintf(
							self::$Labels[ lblMatchingAll ],					
							self::$Labels[ 'c-'.$concept_id ] );
					} else {
						$output->as[$concept_id]->l = sprintf(
							self::$Labels[ lblMatchingPartial ],					
							count( $concept->as ),
							$output->cs[$concept_id]['cnt'],
							self::$Labels[ 'c-'.$concept_id ] );
					}
				}
			}

      return $output;
    }
  }  

?>