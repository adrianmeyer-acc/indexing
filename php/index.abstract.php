<?php
//====================================================================================
/**
 * File containing code for abstract
 * @author Adrian Meyer <adrian.meyer@rocketmail.com>
 */
//====================================================================================

  namespace index;

  // Abstract Concept Lookup
  define( "acCONTACT", 1 );
  define( "acTICKET", 2 );
  define( "acMESSAGE", 3 );
  define( "acOBJECT", 4 );
  define( "acWIKI", 5 );

  // Match Option Lookup
  define( "moFULL", 1 );
  define( "moSYNONYM", 2 );
  define( "moSTARTSWIDTH", 3 );
  define( "moENDSWIDTH", 4 );
  define( "moCONTAINS", 5 );
  define( "moNONE", 9 );

  // Abstract Status Lookup
  define( "asOK", 1 );
  define( "asSTALE", 2 );
  define( "asREBUILD", 3 );
	
  /** Exception class for abstract */
  class AbstractException extends \Exception{};

  //====================================================================================
  /**
   * Model for abstract
   * Responsibilities: Hold all data and functionality associated with an abstract
   */
  class AbstractModel {

    /** Physical index abstract identifier */
    var $abstract_id = 0;

    /** The actual abstract in HTML markup */
    var $abstract = NULL;

    /** Identifier of the concept entity (customer_id, ticket_id, message_id) */
    var $identifier = '';

    /** The URL of the abstract */
    var $url = '';

    /** The icon of the abstract */
    var $icon = '';
		
    /** The value the abstracts within a concept are sorted by */
		var $sort_value = '';

    /** Identifies the concept */
    var $concept_id = 0;

    /** Label for concept */
    var $concept_label = '';

    /** Identifies the status */
    var $status_id = 0;

    /** Label for status */
    var $status_label = '';
		
    /** List of segments */
		var $segments = array();

    /** Label for status */
		var $filters = array();
		
		static $callRebuild;

    // *************************************
    /**
     * Load the abstract by a given abstract ID
     * @param integer $aAbstractId Identifies the abstract to be loaded
     * @return bool Returns TRUE if an item was loaded. Otherwise FALSE
     */
    public function load( $aAbstractId ) {
      $call = \DatabaseService::newQuery( '
        select 
          a.`abstract_id`,
          a.`abstract`,
          a.`identifier`,
          a.`url`,
          a.`icon`,
          a.`sort_value`,
          a.`concept_id`,
          ( select `label` from `indx_AbstractConceptLookup` where `concept_id`=a.`concept_id` ) as `concept_label`,
          a.`status_id`,
          ( select `label` from `indx_AbstractStatusLookup` where `status_id`=a.`status_id` ) as `status_label`
        from 
          `indx_Abstract` a
        where a.`abstract_id` = $abstract_id' );
      $call->setParameter( 'abstract_id', $aAbstractId );
      $call->setParameter( 'dbkey', dbkey );
      $call->execute();
      $call->setObjectProperties( $this );
      return isset( $this->abstract_id );
    }
		
    // *************************************
    /**
     * Add a segment to the abstract
     * @param string $aSegment Identifies a segment with terms. Terms have to be between <span> to get added to the index
     * @return index\SegmentModel
     */
		public function addSegment( $aSegment ){
			$this->segments[] = $aSegment;
		}
		
    // *************************************
    /**
     * Add a segment to the abstract
     * @param string $aSegment Identifies the abstract to be loaded
     * @param string $aDelimiter Identifies the abstract to be loaded
     * @return index\SegmentModel
     */
		public function addFilter( $aFilterGroup, $aFilterValue, $aFilterValueSort ){
return;			
			// load all filter groups
			if (!isset($this->FilterGroups)) {
				$this->FilterGroups = FilterGroupModel::getAllFilterGroups();
			}
			
			// check if we have the group otherwise add it
			if (!isset($this->FilterGroups[$aFilterGroup])){
				$this->FilterGroups[$aFilterGroup] = FilterGroupModel::addGroup( $aFilterGroup );
			}

			// load possible values of group
			$this->FilterGroups[$aFilterGroup]->loadValues();
			
			if (!isset($this->FilterGroups[$aFilterGroup]->values[$aFilterValue])) {
  			$this->FilterGroups[$aFilterGroup]->addValue( $aFilterValue, $aFilterValueSort );
			}

			$this->addFilterValue( $this->FilterGroups[$aFilterGroup]->values[$aFilterValue]->filtervalue_id );
		}		
		
    // *************************************
    /**
     * Return the concept counts per term
		 * @param array $aTermList The terms that will get checked
     * @return void
     */
		static function getCountsPerConcept(&$aAbstractIds) {
      $call = \DatabaseService::newQuery( "
				select 
					a.`concept_id`,
					concat((select `label` from `indx_AbstractConceptLookup` acl where acl.`concept_id` = a.`concept_id` ), 's' ) as label,
					count( a.`abstract_id` ) as concept_cnt
				from `indx_Abstract` a
				where a.`abstract_id` in (".implode(',',$aAbstractIds).")
				group by `concept_id`
				order by 1" );

			$call->execute();
			return $call->getAssociatedArray('concept_id');
		}
		
		
    // *************************************
    /**
     * prepare the abstracts for rebuild
		 * @param array $aAbstractList The abstracts to be initialized
     * @return array list of abstract IDs
     */
    static function prepareForRebuild( &$aAbstractList ){
			$abstractIds = array();

      // save the terms
			\DatabaseService::startTransaction();
      try {
				
				// split into 1000 abstract working lists
				$chunks = array_chunk( $aAbstractList, 2000, true );
        			
				foreach ($chunks as $chunk) {
					$list = array();
					foreach( $chunk as $abstract ) {
						$list[] = "select ".
              "{$abstract->concept_id} a, ".
						  "'".\DatabaseService::escapeString( $abstract->identifier )."' b";
					}
					
	        $sql = "
				    insert into `indx_Abstract` (`concept_id`, `identifier`, `status_id`) 
            select a, b, ".asREBUILD." from ( \n";
  			  $sql .= implode( "\n UNION ALL ", $list );
  			  $sql .= ") a where not exists( select * from `indx_Abstract` ia where ia.`concept_id`=a.a and ia.`identifier`=a.b )	";

          // insert terms that are missing
          $callInsert = \DatabaseService::newQuery( $sql );
					$callInsert->execute();

          // read abstract IDs for this list
	        $sql = "
            select ia.`abstract_id`, a.a as concept_id, a.b as identifier from `indx_Abstract` ia, ( \n";
  			  $sql .= implode( "\n UNION ALL ", $list );
  			  $sql .= ") a where ia.`concept_id`=a.a and ia.`identifier`=a.b";
					
          // insert terms that are missing
          $callSelect = \DatabaseService::newQuery( $sql );
					$callSelect->execute();
					
					$data = $callSelect->getAssociatedArray();
					
					foreach( $data as $item ) {
						$aAbstractList[$item['concept_id'].':'.$item['identifier']]->abstract_id = $item['abstract_id'];
						$abstractIds[] = (integer)$item['abstract_id'];
					}
				} 

        // update the statuses				
				$sql = "update `indx_Abstract` set `status_id` = ".asREBUILD." where `abstract_id` in (";
				$sql .= implode( ",", $abstractIds );
				$sql .= ")";

				// insert terms that are missing
				$callUpdate = \DatabaseService::newQuery( $sql );
				$callUpdate->execute();

        // remove term associations				
				$sql = "delete from `indx_AbstractTerm` where `abstract_id` in (";
				$sql .= implode( ",", $abstractIds );
				$sql .= ")";

				$callPurge = \DatabaseService::newQuery( $sql );
				$callPurge->execute();

        // remote filter associations
				$sql = "delete from `indx_AbstractFilterValue` where `abstract_id` in (";
				$sql .= implode( ",", $abstractIds );
				$sql .= ")";

				$callPurge = \DatabaseService::newQuery( $sql );
				$callPurge->execute();				
				
  			\DatabaseService::commit();
			} catch (Exception $e) {
  			\DatabaseService::rollback();
			}			
			return $abstractIds;
		}

    // *************************************
    /**
     * enable and finalize the abstracts with the
		 * @param array $aAbstractList The abstracts to be enabled
     * @return array list of abstract IDs
     */
    static function finalizeAndEnable( &$aAbstracts ){

			$call = \DatabaseService::newQuery( '
				UPDATE `indx_Abstract` 
				SET `status_id` = '.asOK.', `url` = $url, `icon` = $icon, `abstract` = $abstract, `sort_value`=$sort_value
				WHERE `abstract_id` = $abstract_id ' );

      // save the terms
			\DatabaseService::startTransaction();
      try {
				
				foreach ($aAbstracts as $abstract ) {
  				$call->setParameter( 'abstract_id', $abstract->abstract_id );
  				$call->setParameter( 'url', $abstract->url );
  				$call->setParameter( 'icon', $abstract->icon );
  				$call->setParameter( 'abstract', serialize( $abstract->segments ) );			
  				$call->setParameter( 'sort_value', $abstract->sort_value );
  				$call->execute();
				} 
				
  			\DatabaseService::commit();
			} catch (Exception $e) {
  			\DatabaseService::rollback();
			}			
		}
		
    // *************************************
    /**
     * Save the terms for the abstract list
		 * @param array $aTermList the list of terms with the UPPERCASE term as index an value as term_id
     * @return array Returns list of term IDs as key and match option as value
     */
		static function saveTermAssociations( &$aAbstractList, &$aTermList ) {

	    // save the cached list
      function doSaveTerms( &$aValues ) {
				if ( count($aValues )<=0 ) {
					return;
				}
				
				// insert reference to filter
				$call = \DatabaseService::newQuery( '
					INSERT INTO `indx_AbstractTerm` (
						`abstract_id`,
						`term_id`,
						`matchoption_id`
					) VALUES '.implode( ',', $aValues ));
				
				$call->execute();		
			}

      $termMatchOptions = array();
			\DatabaseService::startTransaction();
      try {
				$values = array();			
				// collect all abstract associations
				foreach( $aAbstractList as $abstract ) {
					foreach ( $abstract->terms as $term => $matchOption ) {					
						$values[] = '('.$abstract->abstract_id.','.$aTermList[$term].','.$matchOption.')';
						
						// build the term match options. Full is the best match
						if ( !isset($termMatchOptions[$aTermList[$term]])) {
							$termMatchOptions[$aTermList[$term]] = $matchOption;
						} else {
							$termMatchOptions[$aTermList[$term]] = min( $termMatchOptions[$aTermList[$term]], $matchOption );
						}
						
						if (count($values)>=10000) {
							doSaveTerms( $values );
							$values = array();
						}
					}
				}
				doSaveTerms( $values );
			
  			\DatabaseService::commit();
			} catch (Exception $e) {
  			\DatabaseService::rollback();
			}			
			
			return $termMatchOptions;
		}		
		
    // *************************************
    /**
     * Add a filter value
		 * @param integer $aFilterValueId Identifies the filter value
     * @return void
     */
		function addFilterValue( $aFilterValueId ) {
      // insert reference to filter
      $call = \DatabaseService::newQuery( '
        INSERT INTO `indx_AbstractFilterValue` (
          `abstract_id`,
          `filtervalue_id`
        ) VALUES (
          $abstract_id,
          $filtervalue_id
        )' );
      
      $call->setParameter( 'abstract_id', $this->abstract_id );
      $call->setParameter( 'filtervalue_id', $aFilterValueId );
      $call->execute();
		}
		
    // *************************************
    /**
     * Load all abstracts for the suggestions
		 * @param array $aAbstractIds A list of the IDs to load
		 * @param integer $aLimit The limit amount
     * @return void
     */
    static function getForSuggestions( $aAbstractIds, $aLimit ) {
			$concepts = self::getAbstractConcepts();
			
			$sql = '';
			foreach( $concepts as $index => $concept ) {
				if ( $sql!='' ){
  				$sql.= ' UNION ALL select * from ( ';
				} else {
					$sql.= 'select * from ( ';
				}
				
				$sql.= '
					select 
						`abstract_id`,
						`abstract`,
						`identifier`,
						`url`,
						`icon`,
						`concept_id`
					from 
						`indx_Abstract`
					where `abstract_id` in ('.implode(',',$aAbstractIds).')
					and `status_id` = '.asOK.'
					and `concept_id` = '.$index.'
					order by `sort_value`
					limit '.$aLimit.') c'.$index;						
			}

      $call = \DatabaseService::newQuery( $sql );
      $call->execute();
      return $call->getAssociatedArrayGroupped( 'concept_id' );
    }

    // *************************************
    /**
     * Get all possible index concepts, e.g. Customer, Issue
     * @return array
     */
    static function getAbstractConcepts() {
      $call = \DatabaseService::newQuery(
         'select `concept_id`,`label`
          from `indx_AbstractConceptLookup`
          order by `concept_id`' );
      $call->execute();
      return $call->get2ColumnIndexedArray();
    }

    // *************************************
    /**
     * Get all possible index abstract statuses
     * @return array
     */
    static function getAbstractStatuses() {
      $call = \DatabaseService::newQuery(
         'select `status_id`,`label`
          from `indx_AbstractStatusLookup`
          order by `status_id`' );
      $call->execute();
      return $call->get2ColumnIndexedArray();
    }

  }

?>