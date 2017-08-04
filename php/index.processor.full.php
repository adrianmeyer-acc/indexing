<?php
//====================================================================================
/** 
 * Indexing processor for only full term
 * @author Adrian Meyer <adrian.meyer@rocketmail.com>
 */
//====================================================================================
  
  namespace index;

  require_once( 'index.processor.php' );

  //====================================================================================
  /**
   * Indexing processor for full
   * Responsibilities: Explode term for full
   * @author Adrian Meyer <adrian.meyer@rocketmail.com>
   */
  class IndexProcessorFull extends IndexTermProcessorBase{
		
    // *************************************
    /**
     * Process the term
     * @param string $aTerm The term to be processed
     * @return array
     */
    static function process( $aTerm ) {
      $terms = \index\TermModel::cleanTerm( $aTerm );
      $result = array();
      
      // process the terms for full
      foreach ( $terms as $term ) {
				$result[$term][$term] = moFULL;
      }
			
      return $result;
		} 
	}


?>