<?php

  mb_internal_encoding('UTF-8');
  mb_regex_encoding('UTF-8'); 

  /**
   * Top level key used for all globals
   * @global string gHTMLDocument
   */
  define('gHTMLDocument', 'html-doc' );

  /**
   * Defines and labels for log levels
   */
  define( 'llDebug', 1 );
  define( 'llWarning', 2 );
  define( 'llError', 3 );
  $GLOBALS[gHTMLDocument]['logLevels'] = array( 
    llDebug => 'DEBG', 
    llWarning => 'WARN', 
    llError => 'ERR ' 
  );

  /**
   * Defines the bookmarks used for parsing
   */
  define( 'pbmPrevious', 0 );
  define( 'pbmTagStart', 1 );
  define( 'pbmTagEnd', 2 );
  define( 'pbmTagClosing', 3 );
  define( 'pbmAttributeName', 4 );
  define( 'pbmAttributeValue', 5 );
  define( 'pbmDocumentStart', 6 );
  define( 'pbmScriptStart', 7 );
  define( 'pbmStyleStart', 8 );
  define( 'pbmCSSProperty', 9 );
  define( 'pbmCSSValue', 10 );
  define( 'pbmCSSComment', 11 );
  define( 'pbmConditionStart', 12 );

  /**
   * Defines the return values of the traverse callback
   */
  define( 'trContinue', 1 );    // will continue to traverse the DOM
  define( 'trAbortBranch', 2 ); // aborts the current branch but continues through rest of DOM
  define( 'trAbort', 3 );       // exits the recursive traverse

  /**
   * Define some parse options
   */
  define( 'poStrictAttributeNames', false ); // if false allow to pass with a warning on none-w3-compliant attribute names
  define( 'poIgnoreOrphanCloseTags', true ); // if true orphan closing tags are ignored
  define( 'poIgnoreDuplicateLT', true );     // if true ignore duplicate LT (<<)
  define( 'poIgnoreSlashTags', true );       // if true ignore </> tags

  /**
   * Defines the tag types
   */
  define( 'ttUnknown', 0 );
  define( 'ttNormal', 1 );       
  define( 'ttSimple', 2 );         
  define( 'ttSingle', 3 );
  define( 'ttText', 4 );
  define( 'ttComment', 5 );
  define( 'ttCondition', 6 );
  define( 'ttDocumentType', 7 );
  define( 'ttXml', 7 );
  
  $GLOBALS[gHTMLDocument]['tagTypes'] = array( 
    ttUnknown => 'Unknown',
    ttNormal => 'Normal',                // prepresents <tag></tag>  
    ttSimple => 'Normal (Simple)',       // prepresents <tag>
    ttSingle => 'Normal (Single)',       // prepresents <tag/>
    ttText => 'Text',                    // prepresents text between tags, text in TagData
    ttComment => 'Comment',              // represents <!--some comment-->, Comment in TagData
    ttCondition => 'Condition',          // prepresents <!--[if....]><![endif]>, Content within condition in TagData
    ttDocumentType => 'Document Type',   // prepresents <!DOCTYPE...>, Content between DOCTYPE and > in TagData
    ttXml => 'XML Definition'            // prepresents <!XML...>, Content between XML and > in TagData
  );

  // cache for checked characters
  $GLOBALS[gHTMLDocument]['uniOrd'] = array();

  function uniord( $c ){    
		if (ord($c{0}) >=0 && ord($c{0}) <= 127)
			return ord($c{0});
		if (ord($c{0}) >= 192 && ord($c{0}) <= 223)
			return (ord($c{0})-192)*64 + (ord($c{1})-128);
		if (ord($c{0}) >= 224 && ord($c{0}) <= 239)
			return (ord($c{0})-224)*4096 + (ord($c{1})-128)*64 + (ord($c{2})-128);
		if (ord($c{0}) >= 240 && ord($c{0}) <= 247)
			return (ord($c{0})-240)*262144 + (ord($c{1})-128)*4096 + (ord($c{2})-128)*64 + (ord($c{3})-128);
		if (ord($c{0}) >= 248 && ord($c{0}) <= 251)
			return (ord($c{0})-248)*16777216 + (ord($c{1})-128)*262144 + (ord($c{2})-128)*4096 + (ord($c{3})-128)*64 + (ord($c{4})-128);
		if (ord($c{0}) >= 252 && ord($c{0}) <= 253)
			return (ord($c{0})-252)*1073741824 + (ord($c{1})-128)*16777216 + (ord($c{2})-128)*262144 + (ord($c{3})-128)*4096 + (ord($c{4})-128)*64 + (ord($c{5})-128);
		if (ord($c{0}) >= 254 && ord($c{0}) <= 255)    //  error
			return FALSE;
		return 0;
  }	
  
  /**==============================================================================================================
   * HTML logger
   * Responsibilities: Manages all loggin capabilities
   * @author Adrian Meyer <adrian.meyer@rocketmail.com>
   * ==============================================================================================================*/
  abstract class HTMLLogger {
    
    var $LastError = '';
		var $TimeProfiling = false;
		var $TimeTotals = Array();
		var $TimeMeasures = Array();
		
    /*********************************************************
     * initialize time measurements
     *********************************************************/
		public function timeStart( $aTimeMeasure ) {
			$time = microtime(true);
			if (!isset($this->TimeTotals[ $aTimeMeasure ])) {
				$this->TimeTotals[ $aTimeMeasure ] = 0.0;
			}
			$this->TimeMeasures[ $aTimeMeasure ] = $time;
		}

    /*********************************************************
     * initialize time measurements
     *********************************************************/
		public function timeStop( $aTimeMeasure ) {
			$time = microtime(true);
			if (isset($this->TimeMeasures[ $aTimeMeasure ])) {
				$this->TimeTotals[ $aTimeMeasure ] += ( $time - $this->TimeMeasures[ $aTimeMeasure ] );
			}
		}
    
    /*********************************************************
     * send debug message
     *********************************************************/
    public function debug( $aMessage ){
      $this->doLog( llDebug, $aMessage );
    }

    /*********************************************************
     * send warning message
     *********************************************************/
    public function warn( $aMessage ){
      $this->doLog( llWarning, $aMessage );
    }

    /*********************************************************
     * send error message
     *********************************************************/
    public function error( $aMessage ){
      $this->doLog( llError, $aMessage );
    }

    /*********************************************************
     * log a passed message including callstack
     *********************************************************/
    private function doLog( $aLogLevel, $aMessage ){
      $trace = debug_backtrace();
      
      if (isset($this->ParseInfo)){
        $aMessage = $this->ParseInfo->getDebugInfo()." - ".$aMessage;
      }
      
      // remember last error
      if ($aLogLevel == llError){
        $this->LastError = $aMessage;
      }
      
      if (isset($trace[1]['line'])){
        $this->log( $aLogLevel, basename( $trace[1]['file'] ), $trace[1]['line'], $aMessage );
       } else {
        $this->log( $aLogLevel, NULL, NULL, $aMessage );
      }
    }
    
    /*********************************************************
     * abstract for message handler
     *********************************************************/
    abstract function log( $aLogLevel, $aFileName, $aLineNumber, $aMessage );
  }
  
  class HTMLLoggerStub extends HTMLLogger {
    function log( $aLogLevel, $aFileName, $aLineNumber, $aMessage ) {
      // do nothing
    }
  }

  /**==============================================================================================================
   * HTML parsing bookmark
   * Responsibilities: Hold information about a bookmark
   * @author Adrian Meyer <adrian.meyer@rocketmail.com>
   * ==============================================================================================================*/
  class HTMLParseBookmark {
    var $pointer = 0;
    var $nextPointer = 0;
    var $charPosition = 0;
    var $lineNumber = 1;
    var $lineNumberPosition = -1;
  }

  /**==============================================================================================================
   * HTML parsing information
   * Responsibilities: Manages all information about parsing the HTML
   * @author Adrian Meyer <adrian.meyer@rocketmail.com>
   * ==============================================================================================================*/
  class HTMLParseInfo {
    var $pointer = -1;       // will always point to the current character 
    var $nextPointer = 0;   // will always point to the next character position

    var $charCurrent = '';  // the current character
    var $charPosition = -1;  // the actual multibyte character position

    var $lineNumber = 1;
    var $lineNumberPosition = -1;
    
    var $html = '';					// UTF8 encoded string
    var $element = NULL;    // current HTML element while parsing
    var $currentParent = NULL;
    
    var $bookmarks = Array();
    var $Logger = NULL;

    /*********************************************************
     * load multibyte UTF-8 HTML data
     *********************************************************/
    function load( $aHtml ) {
      $this->html = $aHtml;
      $position = 0;
      $pointer = 0;
      $nextPointer = 0;
    }
    
    /*********************************************************
     * set a bookmark
     *********************************************************/
    function setBookmark( $aBookmark, $aSetToAfter = false ) {
      if (!isset($this->bookmarks[$aBookmark])) {
        $this->bookmarks[$aBookmark] = new HTMLParseBookmark();
      }
      if ($aSetToAfter) {
        $this->bookmarks[$aBookmark]->pointer = $this->nextPointer;
        $this->bookmarks[$aBookmark]->charPosition = $this->charPosition + 1;
        $this->bookmarks[$aBookmark]->lineNumber = $this->lineNumber;
        $this->bookmarks[$aBookmark]->lineNumberPosition = $this->lineNumberPosition + 1;
      } else {        
        $this->bookmarks[$aBookmark]->pointer = $this->pointer;
        $this->bookmarks[$aBookmark]->charPosition = $this->charPosition;
        $this->bookmarks[$aBookmark]->lineNumber = $this->lineNumber;
        $this->bookmarks[$aBookmark]->lineNumberPosition = $this->lineNumberPosition;
      }
    }
    
    /*********************************************************
     * get the bookmark distance to the current position
     *********************************************************/
    function getBookmarkDistance( $aBookmark ) {
      if (!isset($this->bookmarks[$aBookmark])) {
        return false;
      }
      return $this->charPosition - $this->bookmarks[$aBookmark]->charPosition;
    }
		
    /*********************************************************
     * get the segment based on a bookmark
     *********************************************************/
    function getBookmarkSegment( $aBookmark ){
      if (isset($this->bookmarks[$aBookmark])) {
        return substr( 
          $this->html, 
          $this->bookmarks[$aBookmark]->pointer, 
          $this->pointer - $this->bookmarks[$aBookmark]->pointer );
      }
      return '';
    }

    /*********************************************************
     * move to next character
     *********************************************************/
    function nextChar(){
   		if ($this->Logger->TimeProfiling) $this->Logger->TimeStart('nextChar()');

      // check if we have more characters
      if(isset($this->html[$this->nextPointer])) {
				
				$this->setBookmark( pbmPrevious );
      
        $this->pointer = $this->nextPointer;
        $this->charPosition++;
        
        // check the ordinal value of the first byte in the string
        $char = ord($this->html[$this->nextPointer]);
        
        // single byte character
        if($char < 128){
          $this->charCurrent = $this->html[$this->nextPointer++];
          
        } else {
          if($char < 224){ 
            $bytes = 2;
          }elseif($char < 240){ 
            $bytes = 3;
          }elseif($char < 248){ 
            $bytes = 4;
          }elseif($char == 252){ 
            $bytes = 5;
          }else{ 
            $bytes = 6;
          }

          $this->charCurrent = substr($this->html, $this->nextPointer, $bytes);
          $this->nextPointer += $bytes;
        }
          
        // check if we have changed to a new line on the input
        if ( $this->charCurrent == chr(0x0A)) {
          $this->lineNumber++;
          $this->lineNumberPosition = $this->charPosition;
        }

     		if ($this->Logger->TimeProfiling) $this->Logger->TimeStop('nextChar()');
        return true;
      }
   		if ($this->Logger->TimeProfiling) $this->Logger->TimeStop('nextChar()');

			$this->charCurrent = NULL;
      return false;      
    }      
    
    /*********************************************************
     * Step back to previous
     *********************************************************/
    function back() {
      if (isset($this->bookmarks[pbmPrevious])) {
				$this->nextPointer = $this->pointer;
        $this->pointer = $this->bookmarks[pbmPrevious]->pointer;
        $this->charPosition = $this->bookmarks[pbmPrevious]->charPosition;
        $this->lineNumber = $this->bookmarks[pbmPrevious]->lineNumber;
        $this->lineNumberPosition = $this->bookmarks[pbmPrevious]->lineNumberPosition;
      }
    }
   

    /*********************************************************
     * check a string at the current position. On match move position
     *********************************************************/
    function checkAhead( $aString ){
   		if ($this->Logger->TimeProfiling) $this->Logger->TimeStart('checkAhead()');
      // compare case insensitive
			if ( substr_compare( $this->html, $aString, $this->nextPointer, strlen($aString), true ) === 0 ) {
				
        for( $i=0; $i<strlen($aString);$i++ ){
          $this->nextChar();
        }
     		if ($this->Logger->TimeProfiling) $this->Logger->TimeStop('checkAhead()');
        return true;
      } 
   		if ($this->Logger->TimeProfiling) $this->Logger->TimeStop('checkAhead()');
      return false;
    }

    /*********************************************************
     * find a string starting at the current position. On match move position
     *********************************************************/
    function findAhead( $aString, $aCaseInsensitive=false, $aMovePosition=true ){
   		if ($this->Logger->TimeProfiling) $this->Logger->TimeStart('findAhead()');

      $pointer = $this->pointer;
      $characterCount = 0;
      // step through UTF characters to text
      while (isset($this->html[$pointer+strlen($aString)-1])) {

        // check is we still match
				if ( substr_compare( $this->html, $aString, $pointer, strlen( $aString ), $aCaseInsensitive ) === 0 ) {				
          if ($aMovePosition) {
            for ($i=0;$i<$characterCount;$i++){
              $this->nextChar();
            }
          }
       		if ($this->Logger->TimeProfiling) $this->Logger->TimeStop('findAhead()');
          return $pointer;
        }

        $characterCount++;

        // check the ordinal value of the first byte in the string
        $char = ord($this->html[$pointer]);
        
        // single byte character
        if($char < 128){
          $pointer++;
        }else{
          if($char < 224){ 
            $bytes = 2;
          }elseif($char < 240){ 
            $bytes = 3;
          }elseif($char < 248){ 
            $bytes = 4;
          }elseif($char == 252){ 
            $bytes = 5;
          }else{ $bytes = 6;}
          $pointer += $bytes;
        }
      }
			
   		if ($this->Logger->TimeProfiling) $this->Logger->TimeStop('findAhead()');
      return false;
    }
    

    /*********************************************************
     * find a string starting at the current position. On match move position
     *********************************************************/
    function findAheadFirst( $aStrings ){
   		if ($this->Logger->TimeProfiling) $this->Logger->TimeStart('findAheadFirst()');

      $pointer = $this->pointer;
      $characterCount = 0;
			
      // step through UTF8 characters to test
      while (isset($this->html[$pointer])) {

        $characterCount++;

        // check the ordinal value of the first byte in the string
        $char = ord($this->html[$pointer]);
        
        // single byte character
        if($char < 128){
          $pointer++;
        }else{
          if($char < 224){ 
            $bytes = 2;
          }elseif($char < 240){ 
            $bytes = 3;
          }elseif($char < 248){ 
            $bytes = 4;
          }elseif($char == 252){ 
            $bytes = 5;
          }else{ $bytes = 6;}
          $pointer += $bytes;
        }

        // check if anything hits
				foreach( $aStrings as $index => $term ) {
          if (isset($this->html[$pointer+strlen($term)-1])) {
						if ( substr_compare( $this->html, $term, $pointer, strlen( $term ), false ) === 0 ) {
							for ($i=0;$i<$characterCount;$i++){
								$this->nextChar();
							}
							if ($this->Logger->TimeProfiling) $this->Logger->TimeStop('findAheadFirst()');
							return $index + 1;
						}
				  }
				}
      }
   		if ($this->Logger->TimeProfiling) $this->Logger->TimeStop('findAheadFirst()');
      return false;
    }

    /*********************************************************
     * check a string at the current position. On match move position
     *********************************************************/
    function getDebugInfo() {
      return "line {$this->lineNumber} column ".($this->charPosition - $this->lineNumberPosition);
    }
  }

  /**==============================================================================================================
   * HTML element
   * Responsibilities: Manage functionality of HTML element
   * @author Adrian Meyer <adrian.meyer@rocketmail.com>
   * ==============================================================================================================*/
  class HTMLElement {
    
    var $TagName = '';    
    var $TagType = ttUnknown;
    var $TagData = NULL;
    
    var $Children = array();
    var $Parent = NULL;
    var $Attributes = array();
    var $IsScript = FALSE;
    var $IsStyle = FALSE;
    var $StyleSheet = NULL;

    /*********************************************************
     * Add child element
     *********************************************************/
    function addChild( $aHTMLElement ){
      $this->Children[] = $aHTMLElement;
      $aHTMLElement->Parent = $this;
    }
    
    /*********************************************************
     * Add child element
     *********************************************************/
    function addSiblingBefore( $aSiblingElement ){  

      for ( $i=0; $i<count($this->Parent->Children); $i++ ){
        if ($this->Parent->Children[$i]===$this) {
          $aSiblingElement->Parent = $this->Parent;
          array_splice( $this->Parent->Children, $i, 0, array($aSiblingElement));
          return;
        }
      }
    }

    /*********************************************************
     * Wrap the children between the from and to index with the wrap element
     *********************************************************/
    function wrapChildren( $aWrapElement, $aFromIndex, $aToIndex ) {
      // bail if passed index of child does not exist
      if (!isset($this->Children[$aFromIndex])) {
        return;
      }

      // inject the sibling at the from position
      $this->Children[$aFromIndex]->addSiblingBefore( $aWrapElement );

      // move everything within range to wrap tag
      $position = $aFromIndex + 1;
      while( isset( $this->Children[$position] ) && $position<=$aToIndex ){
        $aWrapElement->addChild($this->Children[$position]);
        $this->Children[$position]->Parent = $aWrapElement;
        unset($this->Children[$position]);
        $position++;
      }      
    }

    /*********************************************************
     * Add child element
     *********************************************************/
    function addAttribute( $aHTMLAttribute ){
      $this->Attributes[] = $aHTMLAttribute;
      end($this->Attributes);
      $this->AttributeIndex[ mb_strtolower( $aHTMLAttribute->Name ) ] = key($this->Attributes);
    }

    /*********************************************************
     * Return an attribue
     *********************************************************/
    function getAttribute( $aName ){
      if (isset( $this->AttributeIndex[mb_strtolower($aName)] )){
        return $this->Attributes[$this->AttributeIndex[mb_strtolower($aName)]]->Value;
      } else {
        return false;
      }
    }


    /*********************************************************
     * Add child element
     *********************************************************/
    function clearAttributes(){
      $this->Attributes = array();
      $this->AttributeIndex = array();
    }

    // *************************************
    /** 
     * Find an element by tag name in the hierarchy walking from bottom up. (Children to Parent)
     * @param string $aTagName Name that should be searched for. This is case insensitive. Returns NULL if no match was found
     * @return HTMLElement|NULL
     * @access public
     */
    function findUp( $aTagName ) {
      if ( strcasecmp( $this->TagName, $aTagName ) == 0 ) 
        $tempReturn = $this;
      elseif (isset($this->Parent)) 
        $tempReturn = $this->Parent->findUp( $aTagName );
      else 
        $tempReturn = NULL;
      return $tempReturn;
    }
  
    // *************************************
    /** 
     * Find an element by tag name in the hierarchy traversing down. (Parent to Children)
     * @param string $aTagName Name that should be searched for. This is case insensitive. Returns NULL if no match was found
     * @return HTMLElement|NULL
     * @access public
     */
    function findDown( $aTagName ) {
      if ( strcasecmp( $this->TagName, $aTagName ) == 0 ) 
        $tempReturn = $this;
      else {
        $tempReturn = NULL;
        // this syntax is needed since php4 does not support "Children as $node"
        foreach ( $this->Children as $key => $obj ) {
          $tempReturn = $this->Children[$key]->findDown( $aTagName );
          if (!is_null($tempReturn)) break;
        }
      }
      return $tempReturn;
    }
  
    // *************************************
    /** 
     * Find an element by attribute name and value in the hierarchy traversing down. (Parent to Children)
     * @param string $aAttribute Attribute name that should be searched for. [case insensitive]
     * @param string $aValue Value that should be searched for. [case insensitive]
     * @return HTMLElement|NULL
     * @access public
     */
    function findByAttributeDown( $aAttribute, $aValue ) {
      if ( strcasecmp( $this->getAttribute($aAttribute), $aValue ) == 0 ) 
        $tempReturn = $this;
      else {
        $tempReturn = NULL;
        foreach ( $this->Children as $key => $obj ) {
          $tempReturn = $this->Children[$key]->findByAttributeDown( $aAttribute, $aValue );
          if (!is_null($tempReturn)) break;
        }
      }
      return $tempReturn;
    }
  
    // *************************************
    /** 
     * Find multiple element(s) by tag name in the hierarchy traversing down. (Parent to Children)
     * @param string $aTagName Name that should be searched for. This is case insensitive. Returns NULL if no match was found
     * @param bool $aStopOnMatch If true and the tag name matched the child items are not analyzed
     * @return array|NULL
     * @access public
     */
    function findsDown( $aTagName, $aStopOnMatch = false ) {
      $tempReturn = array();
      
      // add self if a hit
      if ( strcasecmp( $this->TagName, $aTagName ) == 0 ) {
        $tempReturn[] = $this;
        if ( $aStopOnMatch ) return $tempReturn;
      }
      
      // add matching children
      foreach ( $this->Children as $key => $obj ) {
        $childReturn = $this->Children[$key]->findsDown( $aTagName );
        if (!is_null($childReturn)) 
          $tempReturn = array_merge( $tempReturn, $childReturn );
      }
      
      // return by checking for matches
      if (isset( $tempReturn ))
        return $tempReturn;
      else
        return NULL;
    }
  
    // *************************************
    /** 
     * Find multiple element(s) by attribute name and value in the hierarchy traversing down. (Parent to Children)
     * @param string $aAttribute Attribute name that should be searched for. [case insensitive]
     * @param string $aValue Value that should be searched for. [case insensitive]
     * @return array|NULL
     * @access public
     */
    function findsByAttributeDown( $aAttribute, $aValue ) {
      $tempReturn = array();
      
      // add self if a hit
      if ( strcasecmp( $this->getAttribute($aAttribute), $aValue ) == 0 )
        $tempReturn[] = $this;
        
      // add matching children
      foreach ( $this->Children as $key => $obj ) {
        $childReturn = $this->Children[$key]->findsByAttributeDown( $aAttribute, $aValue );
        if (!is_null($childReturn)) 
          $tempReturn = array_merge( $tempReturn, $childReturn );
      }
          
      // return by checking for matches
      if (isset( $tempReturn ))
        return $tempReturn;
      else
        return NULL;
    }

    /*********************************************************
     * set an attribue, value of FALSE will remove attribute; 
     * value of TRUE will create attribute without value
     *********************************************************/
    function setAttribute( $aName, $aValue ){
      $nameLower = mb_strtolower($aName);
      if (isset( $this->AttributeIndex[$nameLower] )){        
        if ($aValue===false) {
          unset( $this->Attributes[$this->AttributeIndex[$nameLower]] );
          unset( $this->AttributeIndex[$nameLower] );          
        } else {        

          $this->Attributes[$this->AttributeIndex[$nameLower]]->Value = $aValue;
        }
      } else {
        if ($aValue!==false) {
          $attribute = new HTMLAttribute();
          $attribute->Name = $aName;
          $attribute->Value = $aValue;
          $attribute->Quote = '"';
          $this->addAttribute( $attribute );
        }
      }
    }

    /*********************************************************
     * Add style definition
     *********************************************************/
    function addStyleDefinition( $aStyleDefinition ) {
      if (!isset($this->StyleSheet )){
        $this->StyleSheet = new HTMLStyleSheet();
      }
      $this->StyleSheet->Definitions[] = $aStyleDefinition;
    }

    /*********************************************************
     * Clear out element
     *********************************************************/
    function clear() {
      $this->TagType = ttUnknown;
      $this->TagName = '';
      $this->TagData = '';
      $this->StyleSheet = NULL;
      $this->Attributes = array();
      $this->Children = array();
    }
    
    /*********************************************************
     * Hide the element BUT NOT the kids
     *********************************************************/
    function hide() {
      $this->TagType = ttUnknown;
      $this->TagName = '';
      $this->TagData = '';
      $this->StyleSheet = NULL;
    }
    
    /*********************************************************
     * traverse through the DOM with a given callback
     *********************************************************/
     function traverse( $aCallback ) {
       switch( $aCallback( $this ) ){
         case trAbort: return trAbort;
         case trAbortBranch: return trContinue;

         default:
           foreach( $this->Children as $child ) {
             if ($child->traverse($aCallback) == trAbort) {
               return trAbort;
             }
           }
         ;
       }
     }
    
    /*********************************************************
     * Move all children to the same parent
     *********************************************************/
    function moveChildrenToParent(){
      
      $items = array();
      
      foreach ($this->Parent->Children as $item ) {
        $items[] = $item;
        
        // merge children after the position of self on the parent
        if ( $item==$this ){
          foreach ($this->Children as $child) {
            $items[] = $child;
            $child->Parent = $this->Parent;
          }
        }        
      }
      $this->Children = array();
      $this->Parent->Children = $items;
    }

    /*********************************************************
     * Get the html for pre
     *********************************************************/
    function getHtmlPre(){
      
      switch (  $this->TagType ) {
        case ttNormal:
        case ttSimple:
          return "<{$this->TagName}".$this->getHtmlAttributes().">";

        case ttSingle:
          return "<{$this->TagName}".$this->getHtmlAttributes()."/>";

        case ttDocumentType:
          return "<{$this->TagName}{$this->TagData}>";

        case ttXml:
          return "<{$this->TagName}{$this->TagData}>";

        case ttComment:
          return "<{$this->TagName}";

        case ttCondition:
          return "<!--[{$this->TagData}]>";
      }
    }

    /*********************************************************
     * Get the html for content
     *********************************************************/
    function getHtmlContent(){
      switch ( $this->TagType ) {
        case ttComment: 
        case ttText: 
          return $this->TagData;

        case ttNormal:
          if (isset( $this->StyleSheet ))  {
            return $this->StyleSheet->getHtmlFormatted();
          }
      } 
    }

    /*********************************************************
     * Get the html for post
     *********************************************************/
    function getHtmlPost(){
      
      switch (  $this->TagType ) {
        case ttNormal:
        return "</{$this->TagName}>";
  
        case ttComment:
          return "-->";
          
        case ttCondition:
          return "<![endif]-->";
      }
    }

    /*********************************************************
     * Get the text of the nost or all children
     *********************************************************/
    function getText() {
      if ($this->TagType==ttText) {
        return $this->TagData;
      } else {
        
        $text = '';
        foreach( $this->Children as $child ) {
          $text.=$child->getText();
        }
        return $text;
      }
    }

    /*********************************************************
     * Get the html for attributes
     *********************************************************/
    function getHtmlAttributes(){
      if (count($this->Attributes) > 0){
        $html = '';
        foreach( $this->Attributes as $attribute ){
          
          // space and build attributes
          $html.= ' ';          
          $html.= $attribute->Name;

          if ( $attribute->Value!==true ){
            $html.= '=';
            $html.= $attribute->Quote;
            $html.= $attribute->Value;
            $html.= $attribute->Quote;
          }
        }
        return $html;
      }
      
      return '';
    }      
  }

  /**==============================================================================================================
   * HTML Style
   * Responsibilities: Manage functionality of one style sheet
   * @author Adrian Meyer <adrian.meyer@rocketmail.com>
   * ==============================================================================================================*/
  class HTMLStyleSheet {

    var $Definitions = array();
    
    /*********************************************************
     * get the HTML for a style sheet
     *********************************************************/
    function getHtmlFormatted() {
      if (count($this->Definitions) > 0) {
        $css = '';
        foreach($this->Definitions as $definition) {
           $css.="\n";

          foreach($definition->Names as $index=>$name) {
            // multiple names
            if ($index>0){
              $css.=", {$name}";              
            } else {
              $css.="  {$name}";              
            }
          }
           $css.=" {";

          foreach($definition->Properties as $property) {
              $css.="\n    {$property->Name}: {$property->Value};";
          }

           $css.="\n  }";
        }
        return $css."\n";
      }      
      return 'Hello';
    }  
    
    /*********************************************************
     * find a definition
     *********************************************************/
    function findDefinition( $aName ){
      foreach($this->Definitions as $definition){
        
        foreach( $definition->Names as $name ){
          if ( strcasecmp($name, $aName) == 0 ) {
            return $definition;
          }
        } 
      }
      return false;
    }
  }
  
  /**==============================================================================================================
   * HTML Style Definition
   * Responsibilities: Manage functionality of one style definition
   * @author Adrian Meyer <adrian.meyer@rocketmail.com>
   * ==============================================================================================================*/
  class HTMLStyleDefinition {

    var $Properties = array();
    
    /*********************************************************
     * add a new name to the namelist
     * @param array $aName the MB array ofr the name
     *********************************************************/
    function addName( $aName ) {      
      $this->Names[] = $aName;
    }

    /*********************************************************
     * add a new style property
     *********************************************************/
    function addProperty( $aName, $aValue = '' ) {
      $property = new HTMLStyleProperty();  
      $property->Name = $aName;
      $property->Value = $aValue;
      $this->Properties[] = $property;
      return $property;
    }

    /*********************************************************
     * remove property
     * returns TRUE if properties were removed
     *********************************************************/
    function removeProperty( $aName ) {
      $removeList = array();
      
      // find all properties with the passed name
      foreach( $this->Properties as $index=>$property ) {
        if (strcasecmp( $aName, $property->Name ) == 0) {
          $removeList[] = $index;
        }
      }

      // nuke properties
      foreach( $removeList as $index ) {
        unset( $this->Properties[$index] );
      }
      return count($removeList)>0;
    }


    /*********************************************************
     * Return an property value, FALSE is not set
     *********************************************************/
    function getProperty( $aName ){
      foreach( $this->Properties as $property ) {
        if (strcasecmp( $aName, $property->Name ) == 0) {
          return $property->Value;
        }
      }
      return false;
    }
    
    /*********************************************************
     * parse out style attribute and return properties
     *********************************************************/
    function parseInlineStyle( $aAttributeValue ) {

      $position = 0;
      $startPos = 0;
      $currentProperty = NULL;
      do {
        
        // check for name
        if ($aAttributeValue[$position] == ':' ) {
          $name = trim(substr( $aAttributeValue, $startPos, $position - $startPos ));
          $startPos = $position + 1;
          
          if ( strlen($name) > 0 ){
            $currentProperty = $this->addProperty( $name );
          }
        } else 

        // check for value end of end of string
        if ($aAttributeValue[$position] == ';' || $position == strlen($aAttributeValue) - 1 ) {

          if ($aAttributeValue[$position] == ';') {
            $charCount = $position - $startPos;
          } else {
            $charCount = $position - $startPos + 1;
          }

          $value = trim(substr( $aAttributeValue, $startPos, $charCount ));        
          $startPos = $position + 1;

          if (isset( $currentProperty )) {
            $currentProperty->Value = $value;
          }
        }

        $position++;
      } while ( $position < strlen($aAttributeValue));      
    }

    /*********************************************************
     * return the definition as an inline style
     *********************************************************/
    function getInlineStyle() {
      $css = false;
      foreach( $this->Properties as $property ) {
        if ( $css !==false ) {
          $css.= ';';          
        }
        
        $css.= $property->Name;
        if (strlen($property->Value)>0) {
          $css.= ':'.$property->Value;
        }
      }
      return $css;
    }
    
  }

  /**==============================================================================================================
   * HTML Style property
   * Responsibilities: Manage functionality of one style property
   * @author Adrian Meyer <adrian.meyer@rocketmail.com>
   * ==============================================================================================================*/
  class HTMLStyleProperty {
    var $Name = '';
    var $Value = '';
  }

  /**==============================================================================================================
   * HTML Attribute
   * Responsibilities: Manage functionality of HTML element
   * @author Adrian Meyer <adrian.meyer@rocketmail.com>
   * ==============================================================================================================*/
  class HTMLAttribute {
    var $Name = '';
    
    var $Quote = '';
    
    var $Value = false;
  }

  /**==============================================================================================================
   * HTML document
   * Responsibilities: Manage a complete HTML Document Object Model (DOM)
   * @author Adrian Meyer <adrian.meyer@rocketmail.com>
   * ==============================================================================================================*/
  class HTMLDocument {

    /** 
     * Callback when an element is added. function( $aHTMLElement )
     * @var callback
     */
    var $AddElementCallback = NULL;

    /*********************************************************
     * CONSTRUCTOR
     *********************************************************/
    function __construct( $aLogger = NULL ){

      // constants for detecting white spaces
      $this->TAG_SpaceChar = array_flip( array_merge( 
        array( chr(0x20), chr(0x9), chr(0xD), chr(0xA ), chr(0xC ))
      ));
      
      // individual characters
      $this->CHAR_GT = '>';
      $this->CHAR_LT = '<';
      $this->CHAR_SLASH = '/';
      $this->CHAR_EXCL = '!';
      $this->CHAR_QUES = '?';
      $this->CHAR_EQ = '=';
      $this->CHAR_QS = "'";
      $this->CHAR_QD = '"';

      // string constants
      $this->STR_STYLE_END = '</style>';
      $this->STR_SCRIPT_END = '</script>';
            
      if (isset($aLogger)) {
        // grab the passed logger
        $this->Logger = $aLogger;
      } else {
        // setup blank logger
        $this->Logger = new HTMLLoggerStub();
      }
      
      $this->Root = new HTMLElement();
      $this->Logger->debug('Creating new instance');
    }

    /*********************************************************
     * DESTRUCTOR
     *********************************************************/
    function __destruct(){
      $this->Logger->debug('Destroying instance');
    }
    
    /*********************************************************
     * Load a document
     *********************************************************/
    function load( $aHtml = '' ){
      $this->Logger->debug('Load start');
      
      // check if we have content
      if ( trim($aHtml) == '' ){
        $this->Logger->error('Load: No HTML was passed');
        $result = false;
      } else {
        // create new root      
        $this->Root = new HTMLElement();

        // initialiye parsing information
        $this->Logger->debug('Load: Initializing parser information');
        $parseInfo = new HTMLParseInfo();
				$parseInfo->Logger = $this->Logger;
        $parseInfo->load( $aHtml );
        $parseInfo->currentParent = $this->Root;
        $parseInfo->setBookmark( pbmTagEnd, true );				
        $parseInfo->nextChar(); // initialize on the first character
  
        $this->Logger->debug('Load: Bytes to parse = '.strlen( $aHtml ));
        
        // start parsing. Set parse infor on logger
        $this->Logger->ParseInfo = $parseInfo;

	  		if ($this->Logger->TimeProfiling) $this->Logger->TimeStart('parse()');
        $result = $this->parse( $parseInfo );
  			if ($this->Logger->TimeProfiling) $this->Logger->TimeStop('parse()');

        $this->Logger->ParseInfo = NULL;
        
      }
      $this->Logger->debug('Load end');
      return $result;
    }

    /*********************************************************
    // Detecting starting of names. See https://www.w3.org/TR/2008/REC-xml-20081126/#NT-Name
     *********************************************************/
    function isNameStartChar($aChar) {
   		if ($this->Logger->TimeProfiling) $this->Logger->TimeStart('isNameStartChar()');
			
			
   		if ($this->Logger->TimeProfiling) $this->Logger->TimeStart('uniord()');
      $ord = uniord($aChar);
   		if ($this->Logger->TimeProfiling) $this->Logger->TimeStop('uniord()');
			
			$return = false;
      if ($ord==ord(':')) { $return = true;  } else 
      if ($ord==ord('_')) { $return = true; } else 
      if ($ord>=ord('A') && $ord<=ord('Z')) { $return = true; } else 
      if ($ord>=ord('a') && $ord<=ord('z')) { $return = true; } else 
      if ($ord>=0xC0 && $ord<=0xD6) { $return = true; } else 
      if ($ord>=0xD8 && $ord<=0xF6) { $return = true; } else 
      if ($ord>=0xF8 && $ord<=0x2FF) { $return = true; } else 
      if ($ord>=0x370 && $ord<=0x37D) { $return = true; } else 
      if ($ord>=0x37F && $ord<=0x1FFF) { $return = true; } else 
      if ($ord>=0x200C && $ord<=0x200D) { $return = true; } else 
      if ($ord>=0x2070 && $ord<=0x218F) { $return = true; } else 
      if ($ord>=0x2C00 && $ord<=0x2C00) { $return = true; } else 
      if ($ord>=0x3001 && $ord<=0xD7FF) { $return = true; } else 
      if ($ord>=0xF900 && $ord<=0xFDCF) { $return = true; } else 
      if ($ord>=0xFDF0 && $ord<=0xFFFD) { $return = true; } 

   		if ($this->Logger->TimeProfiling) $this->Logger->TimeStop('isNameStartChar()');
      return $return;
    }

    /*********************************************************
    // Detecting name characters. See https://www.w3.org/TR/2008/REC-xml-20081126/#NT-Name
     *********************************************************/
    function isNameChar($aChar) {
   		if ($this->Logger->TimeProfiling) $this->Logger->TimeStart('isNameChar()');

   		if ($this->Logger->TimeProfiling) $this->Logger->TimeStart('uniord()');
      $ord = uniord($aChar);
   		if ($this->Logger->TimeProfiling) $this->Logger->TimeStop('uniord()');

			$return = false;
      if ($ord==ord(':')) { $return = true;  } else 
      if ($ord==ord('_')) { $return = true; } else 
      if ($ord==ord('-')) { $return = true; } else 
      if ($ord==ord('@')) { $return = true; } else 
      if ($ord==ord('.')) { $return = true; } else 
      if ($ord==0xB7) { $return = true; } else 
      if ($ord>=ord('0') && $ord<=ord('9')) { $return = true; } else 
      if ($ord>=ord('A') && $ord<=ord('Z')) { $return = true; } else 
      if ($ord>=ord('a') && $ord<=ord('z')) { $return = true; } else 
      if ($ord>=0xC0 && $ord<=0xD6) { $return = true; } else 
      if ($ord>=0xD8 && $ord<=0xF6) { $return = true; } else 
      if ($ord>=0xF8 && $ord<=0x2FF) { $return = true; } else 
      if ($ord>=0x370 && $ord<=0x37D) { $return = true; } else 
      if ($ord>=0x37F && $ord<=0x1FFF) { $return = true; } else 
      if ($ord>=0x0300 && $ord<=0x036F) { $return = true; } else 
      if ($ord>=0x200C && $ord<=0x200D) { $return = true; } else 
      if ($ord>=0x203F && $ord<=0x2040) { $return = true; } else 
      if ($ord>=0x2070 && $ord<=0x218F) { $return = true; } else 
      if ($ord>=0x2C00 && $ord<=0x2C00) { $return = true; } else 
      if ($ord>=0x3001 && $ord<=0xD7FF) { $return = true; } else 
      if ($ord>=0xF900 && $ord<=0xFDCF) { $return = true; } else 
      if ($ord>=0xFDF0 && $ord<=0xFFFD) { $return = true; } 

   		if ($this->Logger->TimeProfiling) $this->Logger->TimeStop('isNameChar()');
      return $return;
    }
    
    // *************************************
    /** 
     * Find a single element by tag name case insensitive
     * @param string $aTagName Tag name of element to be searched for
     * @return HTMLElement|NULL
     * @access public
     */
    function getElementByTagName( $aTagName ){
      if ($this->Root)
        return $this->Root->findDown( $aTagName );
      else
        return NULL;
    }
    
    // *************************************
    /** 
     * Find a single element by id case insensitive
     * @param string $aId Id attribute of element to be searched for
     * @return HTMLElement|NULL
     * @access public
     */
    function getElementById( $aId ){
      if ($this->Root)
        return $this->Root->findByAttributeDown( 'id', $aId );
      else
        return NULL;
    }  
      
    // *************************************
    /** 
     * Find a single element by name case insensitive
     * @param string $aName Name attribute of element to be searched for
     * @return HTMLElement|NULL
     * @access public
     */
    function getElementByName( $aName ){
      if ($this->Root)
        return $this->Root->findByAttributeDown( 'name', $aName );
      else
        return NULL;
    }  
      
    // *************************************
    /** 
     * Find multiple elements by name case insensitive
     * @param string $aTagName Tag name of elements to be searched for
     * @return array|NULL
     * @access public
     */
    function getElementsByTagName( $aTagName ){
      if ($this->Root) {
        $tempResult = $this->Root->findsDown( $aTagName );
        if (count($tempResult)>0)
          return $tempResult;
        else
          return NULL;
      } else
        return NULL;
    }
    
    // *************************************
    /** 
     * Find multiple elements by id case insensitive
     * @param string $aId Id attribute of element to be searched for
     * @return array|NULL
     * @access public
     */
    function getElementsById( $aId ){
      if ($this->Root) {
        $tempResult = $this->Root->findsByAttributeDown( 'id', $aId );
        if (count($tempResult)>0)
          return $tempResult;
        else
          return NULL;
      } else
        return NULL;
    }
    
    // *************************************
    /** 
     * Find multiple elements by name case insensitive
     * @param string $aName Name attribute of elements to be searched for
     * @return array|NULL
     * @access public
     */
    function getElementsByName( $aName ){
      if ($this->Root) {
        $tempResult = $this->Root->findsByAttributeDown( 'name', $aName );
        if (count($tempResult)>0)
          return $tempResult;
        else
          return NULL;
      } else
        return NULL;
    }

    /*********************************************************
     * parse the data
     *********************************************************/
    function parse( $p ) {
      $this->Logger->debug('Parse start');

      // process the HTML 
      do {
        
				// check for < and a tag name character or [/|!|?]
        if ( $p->charCurrent == $this->CHAR_LT && 
				     isset($p->html[ $p->nextPointer ]) &&
				    ( $this->isNameStartChar( $p->html[ $p->nextPointer ] ) || 
  						$p->html[ $p->nextPointer ] == $this->CHAR_SLASH ||
  						$p->html[ $p->nextPointer ] == $this->CHAR_EXCL ||
  						$p->html[ $p->nextPointer ] == $this->CHAR_QUES )) {
          
          // grab text if there was any
          $this->grabText( $p );
          
          // remember start position
          $p->setBookmark( pbmTagStart, true );
          
          // bail if parsing start fails
          if ( !$this->parseTagStart( $p )){
            return false;
          }
          
          // remember where the tag ended
          $p->setBookmark( pbmTagEnd, true );
        }
        
      } while ($p->nextChar());

      // grab text if there was any at the end
      $this->grabText( $p, true );

      $this->Logger->debug('Parse end');
      return true;
    }

    /*********************************************************
     * get the content as Html
     *********************************************************/
     function getHtml ( $aElement=NULL ){
       $html = '';
       if ($aElement==NULL) {
         $this->getHtmlTraverse( $this->Root, $html );
       } else {
         $this->getHtmlTraverse( $aElement, $html );
       }
       return $html;       
     }
     
    /*********************************************************
     * traverse through the DOM with a given callback
     *********************************************************/
     function traverse( $aCallback ) {
       $this->Root->traverse( $aCallback );
     }

    /*********************************************************
     * traverse elements to get HTML
     *********************************************************/
     private function getHtmlTraverse( $aElement, &$aHtml ) {
       $aHtml .= $aElement->getHtmlPre();
       $aHtml .= $aElement->getHtmlContent();
       
       foreach($aElement->Children as $childNode) {
         $this->getHtmlTraverse( $childNode, $aHtml );
       }       
       
       $aHtml .= $aElement->getHtmlPost();       
     }

    
    /*********************************************************
     * parse the tag start
		 * @param boolean $aEOD Read until the end of data
     *********************************************************/
    private function grabText( $p, $aEOD=false ){
      // move forward to "overread" at the very end
			if ($aEOD) {
  			$p->pointer++;
  			$p->charPosition++;
			}

      // only grab text if we have to
			$charCount = $p->getBookmarkDistance(pbmTagEnd);
      if ( $charCount > 0 ) {

        $element = new HTMLElement();
        $element->TagType = ttText;        
        $element->TagData = $p->getBookmarkSegment( pbmTagEnd );
        $p->currentParent->addChild( $element );
        
        $this->Logger->debug('Text: grabbing '.$charCount.' multibyte characters.' );
      }
    }
    
    /*********************************************************
     * parse the tag start
     *********************************************************/
    private function parseTagStart( $p ) {
      $p->nextChar();
      
      // check if we are EOF
      if (!isset($p->charCurrent)) {
        $this->Logger->error( 'Tag: Unexpected end of document.' );
        return false;
      }      

      //-----------------------
      // check for double <<
      //-----------------------
      if ( $p->charCurrent == $this->CHAR_LT ) {        
        if (poIgnoreDuplicateLT) {
          $this->Logger->warn('Tag: duplicate LT ignored (<<).' );
          $p->back();
          return true;
        } else {
          $this->Logger->error('Tag: duplicate LT <<.' );
          return false;
        }
      }
      
      // create new element
      $p->element = new HTMLElement();
      
      //-----------------------
      // check for tag by detecting valid characters
      //-----------------------
      if( $this->isNameStartChar( $p->charCurrent )) {
        do {
        } while ($p->nextChar() && $this->isNameChar( $p->charCurrent ));

        // we start with simple since we don't know if this will be <tag></tag> or <tag> or <tag/>
        $p->element->TagType = ttSimple;
        $p->element->TagName = $p->getBookmarkSegment( pbmTagStart );
        
        $this->Logger->debug( "Tag: Detected '".$p->element->TagName."'" );

        // remember if we have a style. in an inline CSS the comments are not rendered
        $p->element->IsStyle = strcasecmp( $p->element->TagName, 'style' ) == 0;

        // remember if we have a script. In an inline script the comments are not rendered
        $p->element->IsScript = strcasecmp( $p->element->TagName, 'script' ) == 0;
        
	  		if ($this->Logger->TimeProfiling) $this->Logger->TimeStart('parseTagNormal()');
        if (!$this->parseTagNormal($p)){
  	  		if ($this->Logger->TimeProfiling) $this->Logger->TimeStop('parseTagNormal()');
          return false;
        }
	  		if ($this->Logger->TimeProfiling) $this->Logger->TimeStop('parseTagNormal()');
        return true;
        
      } else 
      
      // check for special tags starting with !
      if( $p->charCurrent == $this->CHAR_EXCL ) {
        $p->back();
      
        //-----------------------
        // check for DOCTYPE
        //-----------------------
        if ( $p->checkAhead( '!DOCTYPE' )) {
          $this->Logger->debug('Tag: DOCTYPE detected' );
          $p->element->TagType = ttDocumentType;
          $p->element->TagName = '!DOCTYPE';
          
          $p->setBookmark( pbmDocumentStart, true );
          
          // try to find 
          while ($p->nextChar()){
            if ($p->charCurrent == $this->CHAR_GT ) {
              $p->element->TagData = $p->getBookmarkSegment( pbmDocumentStart );
              $p->currentParent->addChild( $p->element );
              return true;
            }
          }
          
          $this->Logger->error( "Tag: !DOCTYPE tag not closed" );
          return false;
        } 

        //-----------------------
        // check for condition start
        //-----------------------
        if ($p->checkAhead( '!--[if' )) {
          $conditionStart = true;
        } else 
        if ($p->checkAhead( '![if' )) {
          $conditionStart = true;
        } else {
          $conditionStart = false;
        }
                
				// lets parse the condition
        if ( $conditionStart ) {
          $p->element->TagName = '!--[';
          $p->element->TagType = ttCondition;

          // parse out condition content
          if (!$this->parseTagCondition($p)){
            return false;
          }

          return true;
        };

        //-----------------------
        // check for comments 
        //-----------------------
        if ( $p->checkAhead( '!--' ) ) {
          $this->Logger->debug('Tag: Comment' );
          $p->element->TagName = '!--';

          // parse out comment
          if (!$this->parseTagComment($p,3)){
            return false;
          }

          return true;
        };        

        //-----------------------
        // check for orphan condition elements
        //-----------------------
        if ( $p->checkAhead( '![' )) {
          $this->Logger->debug('Tag: Comment' );
          $p->element->TagName = '!';

          // parse out comment
          if (!$this->parseTagComment($p,1)){
            return false;
          }

          return true;
        };        
      }

      //-----------------------
      // check for tags starting with ?
      //-----------------------
      if( $p->charCurrent == $this->CHAR_QUES ) {

        //-----------------------
        // check for XML
        //-----------------------
        if ( $p->checkAhead( 'XML' )) {
          $this->Logger->debug('Tag: XML detected' );
          $p->element->TagType = ttDocumentType;
          $p->element->TagName = '?xml';
          
          $p->setBookmark( pbmDocumentStart, true );
          
          // try to find 
          while ($p->nextChar()){
            if ($p->charCurrent == $this->CHAR_GT ) {
              $p->element->TagData = $p->getBookmarkSegment( pbmDocumentStart );
              $p->currentParent->addChild( $p->element );
              return true;
            }
          }
					
          $this->Logger->error( "Tag: ?XML tag not closed" );
          return false;
        } 
      }

      //-----------------------
      // check for ending tags starting with /
      //-----------------------
      if( $p->charCurrent == $this->CHAR_SLASH ) {
        $p->nextChar();
        $p->setBookmark( pbmTagClosing );
        
        // check if we have more content
        if (!isset($p->charCurrent) ) {
          $this->Logger->error( 'Tag: Incomplete closing tag at '.$p->getDebugInfo() );
          return false;
        }

        // check for characters to detect tag name
        if( $this->isNameStartChar( $p->charCurrent )) {

          // parse out the end tag
          return $this->parseTagEnd($p);
        }
        
        // check for tags with only a slash
        if ( poIgnoreSlashTags && $p->charCurrent == $this->CHAR_GT ) {
          $this->Logger->warn( 'Tag: Skipped over slash tag at '.$p->getDebugInfo() );
          return true;
        }
      }
      
      $this->Logger->error( 'Tag: Could not parse tag at '.$p->getDebugInfo() );
      return false;
    }
    
    /*********************************************************
     * parse the content of a 'normal' tag
     *********************************************************/
    private function parseTagNormal( $p ){
      
      // special handling of styles
      if ($p->element->IsStyle){

        if ( $p->charCurrent !== '>' ) {
					
       		if ($this->Logger->TimeProfiling) $this->Logger->TimeStart('parseTagContent()');
          // parse the attributes of the style tag
          if (!$this->parseTagContent( $p )){
         		if ($this->Logger->TimeProfiling) $this->Logger->TimeStop('parseTagContent()');
            return false;
          }
       		if ($this->Logger->TimeProfiling) $this->Logger->TimeStop('parseTagContent()');
        }

				// move and remember position
				$p->nextChar();
				$p->setBookmark( pbmStyleStart );

        // find style end tag
        if ( $styleEndPointer = $p->findAhead( $this->STR_STYLE_END, true, false )) {

          $p->element->TagType = ttNormal;
          
          // process style if we have content
          if ($p->pointer < $styleEndPointer ) {
            if (!$this->parseStyle( $p, $styleEndPointer )) {
              return false;
            }
          } else {
            $this->Logger->warn( 'Style: Empty style detected at '.$p->getDebugInfo() );
					}
          $p->currentParent->addChild( $p->element );
					$p->back();
          return true;
        }
      }

      // special handling of script
      if ($p->element->IsScript){

        if ( $p->charCurrent !== '>' ) {
       		if ($this->Logger->TimeProfiling) $this->Logger->TimeStart('parseTagContent()');
          // parse the attributes of the script tag
          if (!$this->parseTagContent( $p )){
         		if ($this->Logger->TimeProfiling) $this->Logger->TimeStop('parseTagContent()');
            return false;
          }
       		if ($this->Logger->TimeProfiling) $this->Logger->TimeStop('parseTagContent()');
        }

        // move start to fist char after <script>
        $p->nextChar();
        $p->setBookmark( pbmScriptStart );
        
        // find style end tag          
        if ( $p->findAhead( $this->STR_SCRIPT_END, true )) {
          $p->element->TagType = ttNormal;
          
          // process script if we have content
          if ($p->getBookmarkDistance( pbmScriptStart )>0) {
            $element = new HTMLElement();
            $element->TagType = ttText;
            $element->TagData = $p->getBookmarkSegment( pbmScriptStart );
            $p->element->addChild( $element );
            $this->Logger->debug('Script: grabbing '.$p->getBookmarkDistance( pbmScriptStart ).' script characters.' );
            
            for($i=0;$i<strlen($this->STR_SCRIPT_END);$i++ ){
              $p->nextChar();
            }
          }
          
          $p->currentParent->addChild( $p->element );
          return true;
        }
        
        $this->Logger->error( 'Script: Unexpected end of document.' );
        return false;        
      }
      
      // check if we are EOF
      if (!isset($p->charCurrent)) {
        $this->Logger->error( 'Tag: Unexpected end of document.' );
        return false;
      }
      
      // check if we neet to parse the content or if there is a > right away
      if ( $this->CHAR_GT != $p->charCurrent ){
     		if ($this->Logger->TimeProfiling) $this->Logger->TimeStart('parseTagContent()');
        if (!$this->parseTagContent( $p )){
       		if ($this->Logger->TimeProfiling) $this->Logger->TimeStop('parseTagContent()');
          return false;
        }
     		if ($this->Logger->TimeProfiling) $this->Logger->TimeStop('parseTagContent()');
      }

      // hook child 
      $p->currentParent->addChild( $p->element );

      // change parent unless we have a single tag <tag/>
      if ($p->element->TagType != ttSingle)    {
        $p->currentParent = $p->element;
      }

      return true;
    }
    
    /*********************************************************
     * parse the content of a 'normal' tag
     *********************************************************/
    private function parseTagContent( $p ){
      
      // search for n patterns: <white space><attr name><=><quote><value><quote>
      while ( $p->nextChar() ){

        // --------------------------------
        // 1. skip over whitespaces
        // --------------------------------
        while(isset( $this->TAG_SpaceChar[ $p->charCurrent]) && $p->nextChar()){
        }
        
        // check if we are EOF
        if (!isset($p->charCurrent)) {
          $this->Logger->error( 'Tag: Unexpected end of document.' );
          return false;
        }

        // --------------------------------
        // 2. check for / for single tag ending
        // --------------------------------
        if ( $this->CHAR_SLASH == $p->charCurrent ){

          // step over possible slaces between / and >
          while(isset( $this->TAG_SpaceChar[ $p->charCurrent]) && $p->nextChar()){
          }
          
          $p->nextChar();
          // check for >
          if ( $this->CHAR_GT != $p->charCurrent ){
            $this->Logger->error( 'Tag: / character out of place in tag' );
            return false;
          }

          $p->element->TagType=ttSingle;
           return true;
        }

        // --------------------------------
        // 3. check for closing >
        // --------------------------------
        if ( $this->CHAR_GT == $p->charCurrent ){
          return true;
        }

        // --------------------------------
        // 4. read the attribute name
        // --------------------------------
        if ( $this->isNameStartChar( $p->charCurrent ) || !poStrictAttributeNames ){
          
          if (!$this->isNameStartChar( $p->charCurrent )) {
            $this->Logger->warn( 'Attribute: character '.$p->charCurrent.' not allowed in attribute name' );
          }
          
          // remember position
          $p->setBookmark( pbmAttributeName );          
          $p->nextChar();

          // read until = or <space>
          while( 
            !isset( $this->TAG_SpaceChar[ $p->charCurrent]) && 
            $p->charCurrent != $this->CHAR_EQ &&
            $p->charCurrent != $this->CHAR_GT            
            ){
              
            // check if the character is allowed in name tag
            if ( !$this->isNameChar( $p->charCurrent)) {
              
              // check for strict names
              if (poStrictAttributeNames) {
                $this->Logger->error( 'Attribute: character '.$p->charCurrent.' not allowed in attribute name' );
                return false;
              } else {
                $this->Logger->warn( 'Attribute: character '.$p->charCurrent.' not allowed in attribute name' );
              }
            }

            $p->nextChar();
          }          
        } else {
          
          $this->Logger->error( 'Attribute: character '.$p->charCurrent.' not allowed in attribute name' );          

          return false;
        }
        

        // create new attribute          
        $attribute = new HTMLAttribute();
        $attribute->Name = $p->getBookmarkSegment( pbmAttributeName );
        $this->Logger->debug( 'Attribute: '.$attribute->Name );          

        // check if we are on a > after attribute without value
        if ( $p->charCurrent == $this->CHAR_GT ) {
          return true;
        }

        // check if we have attribute without values
        if ( isset( $this->TAG_SpaceChar[ $p->charCurrent])) {
          $attribute->Value = true;
          $p->element->addAttribute($attribute);
          continue;
        }
      
        // --------------------------------
        // 5. read over the =
        // --------------------------------
        if ( $p->charCurrent == $this->CHAR_EQ ) {
          if (!$p->nextChar()) {
            $this->Logger->error( 'Attribute: Unexpected end of document' );
            return false;
          }
        }

        // --------------------------------
        // 6. read the value
        // --------------------------------

        // check for quotes
        if ( $p->charCurrent == $this->CHAR_QS || $p->charCurrent == $this->CHAR_QD ) {
          $attribute->Quote = $p->charCurrent;
          
          $p->nextChar();
          $p->setBookmark( pbmAttributeValue );

          // read value until quote end
          while ( isset($p->charCurrent) && $p->charCurrent != $attribute->Quote ) {
            if (!$p->nextChar()) {
              break;
            }
          }
          
          if ( !isset($p->charCurrent)) {
            $this->Logger->error( 'Attribute: Closing quote '.$attribute->Quote.' missing' );
            return false;
          }

          if ( $p->charCurrent != $attribute->Quote ) {
            $this->Logger->error( 'Attribute: Closing quote '.$attribute->Quote.' not found' );
            return false;
          }
          
          // grab value
          $attribute->Value = $p->getBookmarkSegment( pbmAttributeValue );
          $p->element->addAttribute($attribute);
          
        // parse value without quotes
        } else {

          $p->setBookmark( pbmAttributeValue );

          // step until white space or > -- used to also look for / but that was removed to read URLs without quotes
          do {
            
            if (!$p->nextChar()){
              $this->Logger->error( 'Attribute: No ending of value without quotes' );
              return false;
            }
            
          }  while ( 
            $p->charCurrent != $this->CHAR_GT && !isset($this->TAG_SpaceChar[ $p->charCurrent])
          );
          
          // grab value
          $attribute->Value = $p->getBookmarkSegment( pbmAttributeValue );
          $p->element->addAttribute($attribute);

          // step back to detect > or / again          
          $p->back();
        }
      }
    }
    
    /*********************************************************
     * parse the end of a tag
     *********************************************************/
    private function parseTagEnd( $p ){
      do {
      } while ($p->nextChar() && $this->isNameChar( $p->charCurrent ));

      $tagName = $p->getBookmarkSegment( pbmTagClosing );
      $this->Logger->debug('Tag: Closing '.$tagName );

      // looking for opening tag          
      $element = $p->currentParent;
      // traverse up and try to find opening tag      
      while ($element->Parent != NULL && strcasecmp( $element->TagName, $tagName )!==0 ){        
        $element = $element->Parent;
      }

      // the closing tag could not be found
      // if the tag is not found it will be "dropped"
      if ($element->Parent==NULL){

        // check if we need to move on
        if (poIgnoreOrphanCloseTags) {
          $this->Logger->Warn( "The closing tag /{$tagName} (".$p->getDebugInfo().") does not have a start tag" );
          return true;
        } else {
          $this->Logger->Error( "The closing tag /{$tagName} (".$p->getDebugInfo().") does not have a start tag" );
          return false;
        }
      }

      // looking for opening tag          
      $element = $p->currentParent;

      // lets re-arrange the hierarchy and close tags
      while ($element->Parent != NULL && strcasecmp( $element->TagName, $tagName )!==0 ){        
        // define none-closed tags as ttSimple and move and flatten hierarchy to parent
        if ($element->TagType == ttSimple) {
          $element->moveChildrenToParent();
        }
        
        // call the add element callback on closing
        if (isset( $this->AddElementCallback )) {
          call_user_func( $this->AddElementCallback, $element );
        }
        $element = $element->Parent;
      }


      // move the current parent out of the hierarchy          
      $p->currentParent = $element->Parent;
      $element->TagType = ttNormal;
      
      // call the add element callback on closing
      if (isset( $this->AddElementCallback )) {
        call_user_func( $this->AddElementCallback, $element );
      }
      
      return true;
    }

    /*********************************************************
     * parse the content of a comment
     *********************************************************/
    private function parseTagComment( $p, $aCutFrontCount ){
      
      if ($p->findAhead('-->')){
        // add to parent
        $p->currentParent->addChild( $p->element );
        
        // grab comment
        $p->element->TagData = substr( $p->getBookmarkSegment( pbmTagStart ), $aCutFrontCount );
        $p->element->TagType = ttComment;
        $p->nextChar();
        $p->nextChar();

        return true;
      }
      $this->Logger->error( 'Tag: Comment not closed at '.$p->getDebugInfo() );
      return false;
    }
    
    /*********************************************************
     * parse the content of a condition
     *********************************************************/
    private function parseTagCondition( $p ){
      
      // remember last position in order to work for <!--[if and <![if
			$p->setBookmark( pbmConditionStart );
    			
      if ( $elementHit = $p->findAheadFirst( array( ']>', ']-->' ))){

        // add to parent and get data
        $p->currentParent->addChild( $p->element );
        $p->element->TagData = substr( $p->getBookmarkSegment( pbmConditionStart  ), 2 );					

        // incorrect condition with ending ]--> convert to ttComment
        if ( $elementHit == 2 ) {
					$p->element->TagType = ttComment;
					$p->element->TagData = '['.$p->element->TagData.']';
					$p->element->TagData = 'ciao';
          $p->element->TagName = '!--';
					
					// move past comment
					$p->nextChar();
					$p->nextChar();
					$p->nextChar();
					
          $this->Logger->debug('Tag: Comment' );
					return true;
  			}
				        
        // grab condition
        $p->element->TagData = substr( $p->getBookmarkSegment( pbmConditionStart  ), 2 );

        $this->Logger->debug( "Tag: Condition start '{$p->element->TagData}'" );
        
        // step out of condition        
        $p->nextChar();
        $p->nextChar();
				$p->setBookmark( pbmConditionStart );
       
        if ( $elementHit = $p->findAheadFirst( array( '<![endif', '<[endif]', '<!-->' ))) {
          
          $element = new HTMLElement();
          $element->TagType = ttText;        
          $element->TagData = $p->getBookmarkSegment( pbmConditionStart );

          $p->element->addChild( $element );
          $this->Logger->debug('Condition: grabbing '.$p->getBookmarkDistance(pbmConditionStart).' multibyte characters of condition content.' );
          
          // check if we hit on bad formed [endif]
          if ( $elementHit == 2 ) {
            $this->Logger->warn('Tag: non-w3c compliant [endif] tag found at '.$p->getDebugInfo() );
          } else 
          // check if we hit on bad formed <!-->
          if ( $elementHit == 3 ) {
            $this->Logger->warn('Tag: non-w3c compliant <!--> tag found at '.$p->getDebugInfo() );
          } else {          
            $this->Logger->debug('Tag: Condition end' );
          }

          // move past ending tag
          $p->findAhead( '>' );          
        } else {
          $this->Logger->error('Tag: No condition end found for condition start.' );
          return false;
        };
        
        return true;
      }
            
      $this->Logger->error( 'Tag: Condition tag not closed at '.$p->getDebugInfo() );
      return false;
    }

    /*********************************************************
     * parse the condition ending
     * NOT USED at this point. conent between conditions is managed a text
     *********************************************************/
    private function parseTagConditionEnd( $p ){
      
      // check for long and short form of condition end tag
      if ($p->findAhead(']-->')) {
        $endHit = true;
      } else 
      if ($p->findAhead(']>')) {
        $endHit = true;
      } else 
        $endHit = false;
      
      if ( $endHit ){
        // looking for opening tag          
        $element = $p->currentParent;

        while ($element->Parent != NULL && $element->TagType != ttCondition ){
          
          // define none-closed tags as ttSimple and move and flatten hierarchy to parent
          if ($element->TagType == ttSimple) {
            $element->moveChildrenToParent();
          }
          $element = $element->Parent;
        }

        // the closing tag could not be found
        if ($element->Parent==NULL){
          $this->Logger->Error( "The condition end tag (".$p->getDebugInfo().") does not have a condition start tag" );
          return false;
        }
        
        // move the current parent out of the hierarchy          
        $p->currentParent = $element->Parent;
        
        return true;
      }
      $this->Logger->error( 'Tag: Condition end tag not closed at '.$p->getDebugInfo() );
      return false;
    }
    
    /*********************************************************
     * parse the content of a style tag, see https://www.w3.org/TR/css-syntax-3/#parsing-overview
     *********************************************************/
    private function parseStyle( $p, $aEndPointer ){

      $this->Logger->debug( "Style: Parsing ".($aEndPointer - $p->pointer )." bytes" );
      $nextName = array();

      // create new style
      $p->styleDefinition = new HTMLStyleDefinition();
      
      // loop until the end
      while ( $aEndPointer > $p->pointer ) {

        //-----------------------
        // check for CDO-token
        //-----------------------
        if ( $p->checkAhead( '<!--' )) {
          $this->Logger->debug('Style: Skipping inline comment open <!--' );
          continue;
        }

        //-----------------------
        // check for CDC-token
        //-----------------------
        if ( $p->checkAhead( '-->' )) {
          $this->Logger->debug('Style: Skipping inline comment close -->' );
          continue;
        }

        //-----------------------
        // check for comment
        //-----------------------
        if ( $p->checkAhead( '/*' )) {
					$p->setBookmark( pbmCSSComment, true );

          // move to comment closing
          if ( !$p->findAhead( '*/' )) {
            $this->Logger->error('Style: Comment not closed' );
            return false;
          }

          $comment = $p->getBookmarkSegment( pbmCSSComment );
          $this->Logger->debug('Style: Comment '.$comment );
          $this->Logger->debug('Style: Comment '.$p->getBookmarkDistance(pbmCSSComment).' characters' );

          // move to after comment 
          $p->nextChar();
          $p->nextChar();

          continue;
        };
        
        //-----------------------
        // check for block
        //-----------------------
        if ($p->charCurrent == '{' ) {

          // Check if we have name. Otherwise assume { is irrelevant
          if ( !$this->styleGrabStyleName( $p, $nextName ) ) {
            $this->Logger->warn('Style: Stepping over extra {' );
            // move to after comment 
            $p->nextChar();
            continue;
          } 
          $nextName = array();
          
          $this->Logger->debug('Style: Block for definitions' );

          // remember start
					$p->setBookmark( pbmCSSProperty, true );
					$p->setBookmark( pbmCSSValue, true );
          $propertyName = '';
          $propertyValue = '';

          // find definition attributes
          while ( $p->pointer < $aEndPointer && $p->nextChar() && $p->charCurrent != '}' ) {
            // check for attribute name
            
            switch ( $p->charCurrent ) {
              case ':':
                $propertyName = trim($p->getBookmarkSegment( pbmCSSProperty ));
      					$p->setBookmark( pbmCSSValue, 1 );
              break;
              
              case '}':
              case ';':
                $propertyValue = trim($p->getBookmarkSegment( pbmCSSValue ));
      					$p->setBookmark( pbmCSSProperty, true );
                
                // add it to the styles
                 $this->Logger->debug( "Style: Adding property {$propertyName}: {$propertyValue}" );
                $p->styleDefinition->addProperty( $propertyName, $propertyValue );
              break;
            }
          }

          // add the style to the element       
          if ($p->charCurrent == '}'){
            if (count($p->styleDefinition->Properties)>0){
              $p->element->addStyleDefinition( $p->styleDefinition );
            }
            
            // setup new style
            $p->styleDefinition = new HTMLStyleDefinition();
          }
          
          // check if anything in the definitions is left open
          if ( $p->pointer >= $aEndPointer ) {
             $this->Logger->error( "Style: No closing } found for style definition." );
            return false;
          }
            
          $p->nextChar();
          continue;
        } 

        //-----------------------
        // check for text
        //-----------------------                
        if ($p->charCurrent == ',' ) {

          // add any possible name
          $this->styleGrabStyleName( $p, $nextName );
          $nextName = array();
          $p->nextChar();
          
        } else {

          // accumulate name
          $nextName[] = $p->charCurrent;
        }
        
        $p->nextChar();
      };

      // move to after </style>
      for ($i=0;$i<strlen( $this->STR_STYLE_END );$i++) {
        $p->nextChar();
      }
			$p->setBookmark( pbmTagEnd );
      $this->Logger->debug( "Style: End detected" );
      
      return true;
    }
    
    /*********************************************************
     * parse the content of a style tag, see https://www.w3.org/TR/css-syntax-3/#parsing-overview
     *********************************************************/
    private function styleGrabStyleName( $p, $aName ){

      // check ending white spaces
      while (count($aName)>0 ) {
        if ( isset( $this->TAG_SpaceChar[ $aName[count($aName)-1]] )) {
          array_pop( $aName );
        } else {
          break;
        }
      } 
      
      $aName = array_reverse( $aName );

      // check trailing white spaces
      while (count($aName)>0 ) {
        if ( isset( $this->TAG_SpaceChar[ $aName[count($aName)-1]] )) {
          array_pop( $aName );
        } else {
          break;
        }
      }
      
      if (count($aName)>0) {
        // grab the name without CR of LF        
        $name = implode('',array_filter( array_reverse( $aName ), function($char) { return $char!="\n" and $char!="\r"; }));
        $p->styleDefinition->addName($name);
        $this->Logger->debug( "Style: Adding Name: {$name}" );
        return true;
      }
      return false;
    }
  }

?>