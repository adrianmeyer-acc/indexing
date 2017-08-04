<?php
//====================================================================================
/** 
 * Indexing processor for emails
 * @author Adrian Meyer <adrian.meyer@rocketmail.com>
 */
//====================================================================================
  
  namespace index;

  require_once( 'index.processor.php' );

  //====================================================================================
  /**
   * Indexing processor for emails
   * Responsibilities: Explode term for emails
   * @author Adrian Meyer <adrian.meyer@rocketmail.com>
   */
  class IndexProcessorEmail extends IndexTermProcessorBase{
		
    // *************************************
    /**
     * Process the term
     * @param string $aTerm The term to be processed
     * @return array
     */
    static function process( $aTerm ) {
      $terms = \index\TermModel::cleanTerm( $aTerm );
      $result = array();
      
      // process the terms building partials
      foreach ( $terms as $term ) {
				$result[$term] = self::termExplode( $term, 3, moFULL );
				
				// check if we have @ in email. Add the domain as a search option
				if ( $atPosition = strpos( $term, '@' )) {
					$result[$term] = $result[$term] + self::termExplode( substr( $term, $atPosition ), 4, moENDSWIDTH, moCONTAINS );
				}								
      }
			
      return $result;
		} 
	}


?>