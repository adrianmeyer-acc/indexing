<?php
//====================================================================================
/** 
 * Index Processor base class
 * @author Adrian Meyer <adrian.meyer@rocketmail.com>
 */
//====================================================================================

  namespace index;

  //====================================================================================
  /**
   * Index Processor base class for terms
   * Responsibilities: Manage the processing of a term
   * @author Adrian Meyer <adrian.meyer@rocketmail.com>
   */
  abstract class IndexTermProcessorBase {

    // *************************************
    /**
     * Process the term
     * @param string $aTerm The term to be processed
     * @return array
     */
		static abstract function process( $aTerm );
		
    // *************************************
    /** 
     * Explode term
     * @return void
     */
    static function termExplode( $aTerm, $aMaxLength=3, $aFullMatchOption = moFULL ) {
      $result = array();
			for( $i=mb_strlen($aTerm); $i>=max(1,$aMaxLength); $i-- ){
				$partial = mb_substr( $aTerm, 0, $i );

				if ($i==mb_strlen($aTerm)) {
					$result[$partial] = $aFullMatchOption;
				} else {
					$result[$partial] = moSTARTSWIDTH;
				}
			}
			return $result;
		}
	}
 

?>