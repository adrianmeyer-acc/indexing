<?php
//====================================================================================
/**
 * File containing code for filter value
 * @author Adrian Meyer <adrian.meyer@rocketmail.com>
 */
//====================================================================================

  namespace index;

  /** Exception class for filter value */
  class FilterValueException extends \Exception{};

  //====================================================================================
  /**
   * Model for filter value
   * Responsibilities: Hold all data and functionality associated with an filter value
   */
  class FilterValueModel {

    /** Physical index filter identifier */
    var $filtervalue_id = 0;

    /** Identifies the filter value (for state=[Bern|Zurich]) */
    var $value = '';

    /** Value used for sorting within filter group */
    var $sortvalue = '';

    /** Identifies the group the value belongs to */
    var $filtergroup_id = 0;

    // *************************************
    /**
     * Save the filter value
     * @return integer Returns the ID of the new or modified item
     */
    public function save(  ){
      $call = \DatabaseService::newQuery( '
        INSERT INTO `indx_FilterValue` (
          `filtervalue_id`,
          `value`,
          `sortvalue`,
          `filtergroup_id`
        ) VALUES (
          $filtervalue_id,
          $value,
          $sortvalue,
          $filtergroup_id
        ) 
        ON DUPLICATE KEY UPDATE
          `filtervalue_id` = LAST_INSERT_ID( filtervalue_id ),
          `sortvalue` = $sortvalue' );
      
      $call->setParametersFromObject( $this );
      $call->setParameter( 'filtervalue_id', $this->filtervalue_id );
      $call->execute();
      $this->filtervalue_id = $call->LastId;
      
      return $this->filtervalue_id;
    }  


    // *************************************
    /**
     * Load all filter values 
		 * @param integer $aFilterGroupId The group identifier
     * @return void
     */
    static function getFilterValuesForGroup( $aFilterGroupId ) {
      $call = \DatabaseService::newQuery( '
        select 
          `filtervalue_id`,
          `value`,
          `sortvalue`
        from 
          `indx_FilterValue`
				where `filtergroup_id` = $filtergroup_id
        order by `sortvalue`' );
      $call->setParameter( 'filtergroup_id', $aFilterGroupId );
      $call->execute();
      return $call->getObjectArray( '\\index\\FilterValueModel', 'value');
    }
  }

?>