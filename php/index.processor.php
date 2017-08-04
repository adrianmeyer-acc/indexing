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
     * @param string $aTerm The term to be processed
     * @param integer $aMaxLength The minimum length of the explode
     * @param integer $aFullMatchOption Sets the match option of the full term hits, sometimes also moSYNONYM
     * @return void
     */
    static function termExplode( $aTerm, $aMinLength=3, $aFullMatchOption = moFULL, $aPartialMatchOption = moSTARTSWIDTH ) {
      $result = array();
			for( $i=mb_strlen($aTerm); $i>=max(1,$aMinLength); $i-- ){
				$partial = mb_substr( $aTerm, 0, $i );

				if ($i==mb_strlen($aTerm)) {
					$result[$partial] = $aFullMatchOption;
				} else {
					$result[$partial] = $aPartialMatchOption;
				}
			}
			return $result;
		}
		
    // *************************************
    /** 
     * Remove accents from a string
     * @param string $aString The string to be processed
     * @return string
     */
		static function unaccent($aString) {
			if (strpos($aString = htmlentities($aString, ENT_QUOTES, 'UTF-8'), '&') !== false) {
				$aString = html_entity_decode( preg_replace(
				'~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|tilde|uml);~i', '$1', 
				$aString), ENT_QUOTES, 'UTF-8');
			}
	
			return $aString;
		}
		
    // *************************************
    /** 
     * Convert umlauts to ae, ue, oe 
     * @param string $aString The string to be processed
     * @return string
     */
		static function germanize($aString) {
			if (strpos($aString = htmlentities($aString, ENT_QUOTES, 'UTF-8'), '&') !== false) {
				$aString = html_entity_decode( preg_replace(
				'~&([a-z]{1,2})(?:uml);~i', '$1e', 
				$aString), ENT_QUOTES, 'UTF-8');
			}
	
			return self::unaccent( $aString );
		}		
	}

?>