<?php
//====================================================================================
/** 
 * Indexing processor for detault indexing
 * @author Adrian Meyer <adrian.meyer@rocketmail.com>
 */
//====================================================================================
  
  namespace index;

  require_once( 'index.processor.php' );

  //====================================================================================
  /**
   * Indexing processor for detault indexing
   * Responsibilities: Explode term be default
   * @author Adrian Meyer <adrian.meyer@rocketmail.com>
   */
  class IndexProcessorDefault extends IndexTermProcessorBase{
		
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
      }
			
      return $result;
		} 
	}


?>