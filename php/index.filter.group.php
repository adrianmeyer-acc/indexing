<?php
//====================================================================================
/**
 * File containing code for filter group
 * @author Adrian Meyer <adrian.meyer@rocketmail.com>
 */
//====================================================================================

  namespace index;

  /** Exception class for filter group */
  class FilterGroupException extends \Exception{};

  //====================================================================================
  /**
   * Model for filter group
   * Responsibilities: Hold all data and functionality associated with an filter group
   */
  class FilterGroupModel {

    /** Physical index filter identifier */
    var $filtergroup_id = 0;

    /** Identifies the filter group (state, gender,country,category) */
    var $groupname = '';
		
    // *************************************
    /**
     * Load the filter group by a given filergroup ID
     * @param integer $aFilergroupId Identifies the filter group to be loaded
     * @return bool Returns TRUE if an item was loaded. Otherwise FALSE
     */
    public function load( $aFilergroupId ) {
      $call = services()->database()->newQuery( '
        select 
          fg.`filtergroup_id`,
          fg.`groupname`
        from 
          `indx_FilterGroup` fg
        where fg.`filtergroup_id` = $filtergroup_id' );
      $call->setParameter( 'filtergroup_id', $aFiltergroupId );
      $call->setParameter( 'dbkey', dbkey );
      $call->execute();
      $call->setObjectProperties( $this );
			
      return false;
    }

    // *************************************
    /**
     * Load the values of the group
     * @return void
     */
		public function loadValues() {
			if (!isset($this->values)) {
  			$this->values = FilterValueModel::getFilterValuesForGroup($this->filtergroup_id);				
			}
		}
		
		
    // *************************************
    /**
     * Add a filter value
		 * @param string $aFilterValue The name of the value
		 * @param string $aFilterValueSort The value to sort by
     * @return void
     */
    function addValue( $aFilterValue, $aFilterValueSort ){
			$filterValue = new FilterValueModel();
			$filterValue->filtergroup_id = $this->filtergroup_id;
			$filterValue->value = $aFilterValue;
			$filterValue->sortvalue = $aFilterValueSort;
			$filterValue->save();
			
			$this->loadValues();
			$this->values[$aFilterValue] = $filterValue;
		}
		
    // *************************************
    /**
     * add a filter group
		 * @param string $aGroupName The name of the group
     * @return integer Returns the ID of the new or existing item
     */
    static function addGroup( $aGroupName ){
      $call = \DatabaseService::newQuery( '
        INSERT INTO `indx_FilterGroup` (
          `groupname`
        ) VALUES (
          $groupname
        ) 
        ON DUPLICATE KEY UPDATE
          `filtergroup_id` = LAST_INSERT_ID( filtergroup_id )' );
      
      $call->setParameter( 'groupname', $aGroupName );
      $call->execute();
			
			$group = new FilterGroupModel();
			$group->filtergroup_id = $call->LastId;
			$group->groupname = $aGroupName;
			
      return $group;
    }  

    // *************************************
    /**
     * Load all filter groups 
     * @return void
     */
    static function getAllFilterGroups() {
      $call = \DatabaseService::newQuery( '
        select 
          `groupname`,
          `filtergroup_id`
        from 
          `indx_FilterGroup`
        order by 1' );
      $call->execute();
      return $call->getObjectArray( '\\index\\FilterGroupModel', 'groupname');
    }
  }

?>