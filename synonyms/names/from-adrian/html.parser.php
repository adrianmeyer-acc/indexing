<?php
// =========================================================================================================
/** 
 * DOM based light weight/high speed HTML parser
 * change-history:
 * - <br><b>0.11.0</b>
 * - - <i>Added htmlNode->clear(). Removes TagName, Attributes and all child nodes.</i>
 * - - <i>Added htmlNode->getElementByTagName(). Find the 1st child node by tag name
 * - - <i>Added htmlNode->getElementById(). Find the 1st child node by ID attribute
 * - - <i>Added htmlNode->getElementByName(). Find the 1st child node by NAME attribute
 * - - <i>Added htmlNode->getElementsByTagName(). Find the child nodes by tag name
 * - - <i>Added htmlNode->getElementsById(). Find the child nodes by ID attribute
 * - - <i>Added htmlNode->getElementsByName(). Find the child nodes by NAME attribute
 * - <br><b>0.10.0</b>
 * - - <i>Corrected parsing of boolean attributes</i>
 * - - <i>Added htmlNode->addSibling(). Adds a sibling right after the node the methodis called on.</i>
 * - - <i>Added htmlNode->getText(). Returns the content of the text nodes (ttText).</i>
 * - - <i>Added Parser Option poNone: Default to no options</i>
 * - - <i>Added htmlNode->getInnerHtml(). Returns the HTML of all the children.</i>
 * - - <i>Corrected indexing in node->insertChildNode()</i>
 * - <br><b>0.9.0</b>
 * - - <i>Added htmlNode->insertParentNode(). Injects a new parent between the node and the existing parent.</i>
 * - - <i>Added htmlNode->setText(). Clears all the child nodes and sets the text within a tag</i>
 * - - <i>Added htmlNode->inserChildNode() to add a child at a given position</i>
 * - - <i>Added Parser Option poTrimText: Calls trim() on the text elements befor they are analyzed for content</i>
 * - - <i>Added Parser Option poRemoveCRLF: Removes and combination of CRLF, LFCR, CR or LF</i>
 * - - <i>Added htmlParser->ParseOptions property</i>
 * - <br><b>0.8.0</b>
 * - - <i>Added walkDown() to travers DOM and calling callback</i>
 * - - <i>Added setAttribute() to set an attribute. Attribute name is case insensitive</i>
 * - - <i>Corrected hierarchy when finding unclosed open tags</i>
 * - <br><b>0.7.2</b>
 * - - <i>Corrected line number and position</i>
 * - - <i>Corrected comment start to not include --</i>
 * - <br><b>0.7.1</b>
 * - - <i>Added retention of line number and position of HTML node</i>
 * - <br><b>0.7.0</b>
 * - - <i>Added retention of quotes for attributes</i>
 * - <br><b>0.6.0</b>
 * - - <i>Corrected compatibility with php 4.3.9 by adjusting object reference calls</i>
 * - <br><b>0.5.0</b>
 * - - <i>Renamed parser->getElementByName() to parser->getElementByTagName()</i>
 * - - <i>Renamed parser->getElementsByName() to parser->getElementsByTagName()</i>
 * - - <i>Added parser->getElementByName() to look for name attribute</i>
 * - - <i>Added parser->getElementsByName() to look for name attribute</i>
 * - <br><b>0.4.0</b>
 * - - <i>Added node->findNodeByAttribute() and node->findNodesByAttribute()</i>
 * - - <i>Added parser->getElementById() and parser->getElementsById()</i>
 * - <br><b>0.3.0</b>
 * - - <i>Added parser->getElementByName() and parser->getElementsByName()</i>
 * - - <i>Corrected boolean attributes</i>
 * - - <i>Added node->getAttribute() to give case insensitive access to attributes</i>
 * - <br><b>0.2.0</b>
 * - - <i>Added parsing of attributes</i>
 * - - <i>Added returning html of DOM structure parser->getHtml()</i>
 * - - <I>Added comments for PhpDocumentor</i>
 * - <br><b>0.1.0</b>
 * - - <i>Created initial version focusing on basics: parser, tag identification, tag structure, DOM</i>
 * 
 * @author Adrian Meyer <adrian.meyer@unc.edu>
 * @package component-framework
 * @category parser
 * @license Freeware
 * @version 0.11.0
 *
 */
// =========================================================================================================
 
/**
 * Top level key used for all globals
 * @global string gHtmlParser
 */
define('gHtmlParser', 'html-parser' );

/**
 * Tag types for the HTML nodes
 * - ttRoot: <i>Root node as specified in rootTagName used during parsing</i>
 * - ttUnknown: <i>Fallback type if tag cannot be identified</i>
 * - ttComment: <i>Comment tag in the format of <!-- comment --></i>
 * - ttDocType: <i>Document type tag in the format of <!DOCTYPE ...>. Identification of this tag is case insensitive</i>
 * - ttText: <i>Tag used to store plain text</i>
 * - ttStart: <i>Tag type used during parsing when the format is <name> containing no / at the beginning or end</i>
 * - ttEnd: <i>Tag type used during parsing when the format is </name>. The parser will try to find the matching start tag and change it to ttNormal</i>
 * - ttNormal: <i>Tag type used for "normal" hierarchical tags in the format of <tagName></tagName></i>
 * - ttSingle: <i>Tag type used for tags with a / at the end. Example: <br/></i>
 * - ttSimple: <i>Tag type used for tags that looked like start tags but did not have an end tag. Example: <hr></i>
 * @global array $GLOBALS[gHtmlParser]['tagTypes']
 */
$GLOBALS[gHtmlParser]['tagTypes'] = array( 'ttRoot', 'ttUnknown', 'ttComment', 'ttDocType', 'ttText', 'ttStart', 'ttEnd', 'ttNormal', 'ttSingle', 'ttSimple' );
foreach( $GLOBALS[gHtmlParser]['tagTypes'] as $key => $value ) /** @ignore */ define( $value, $key ); 

/**
 * Modes the parser is set to while looping through the HTML 
 * - pmInTag: <i>We are in a tag between < and ></i>
 * - pmComment: <i>We are parsing in a comment between <!-- --></i>
 * - pmNormal: <i>We are parsing outside of tags</i>
 * - pmScript: <i>We are parsing inside script</i>
 * @global array $GLOBALS[gHtmlParser]['parseModes']
 */
$GLOBALS[gHtmlParser]['parseModes'] = array( 'pmComment', 'pmInTag', 'pmNormal', 'pmScript' );
foreach( $GLOBALS[gHtmlParser]['parseModes'] as $key => $value ) /** @ignore */ define( $value, $key ); 

/**
 * Parser options controlling how different szenarios are handeled. These options are passed with an OR operator. Example: htmlNode->parseHtml( $myHtml, poRemoveCRLF | poTrimText );
 * - poRemoveCRLF: <i>CR or CRLF are removed from the text and replaced with SPACE</i>
 * - poTrimText: <i>The text elements are trimmed</i>
 * - poNone: <i>None of the parse options are set</i>
 * @global array $GLOBALS[gHtmlParser]['parseOptions']
 */
$GLOBALS[gHtmlParser]['parseOptions'] = array( 0 => 'poNone', 1 => 'poRemoveCRLF', 2 => 'poTrimText' );
foreach( $GLOBALS[gHtmlParser]['parseOptions'] as $key => $value ) /** @ignore */ define( $value, $key ); 
	
/**
 * Tag properties used when analysing tag names, types and data
 * - tName: <i>Name of tag as string. !-- is used for comments. !DOCTYPE is used for document type information</i>
 * - tType: <i>Type of tag using tag type globals</i>
 * - tData: <i>Data portion of tag. This can be attributes (before parseAttribues() is called), comment of document type information</i>
 * @global array $GLOBALS[gHtmlParser]['tagProperties']
 */
$GLOBALS[gHtmlParser]['tagProperties'] = array( 'tName', 'tType', 'tData' );
foreach( $GLOBALS[gHtmlParser]['tagProperties'] as $key => $value ) /** @ignore */ define( $value, $key ); 
	
/**
 * walkDown() resul constants to control the continuation or abort of DOM walk
 * - wdContinue: <i>Continue to walk the DOM</i>
 * - wdAbortBranch: <i>Abort walking the current branch but continue otherwise</i>
 * - wdAbort: <i>Abort the walk immediately</i>
 * @global array $GLOBALS[gHtmlParser]['walkAbort']
 */
$GLOBALS[gHtmlParser]['walkAbort'] = array( 'wdContinue', 'wdAbortBranch', 'wdAbort' );
foreach( $GLOBALS[gHtmlParser]['walkAbort'] as $key => $value ) /** @ignore */ define( $value, $key ); 

/**
 * Name used as root and to wrap passed HTML.
 * @global string rootTagName
 */
define( 'rootTagName', 'parserRoot' );
/**
 * Length of root tag to adjust character position on node->ParseStartPosition and node->ParseEndPosition
 * @see htmlNode::$ParseStartPosition
 * @see htmlNode::$ParseEndPosition
 * @global string rootTagLength
 */
define( 'rootTagLength', strlen('<'.rootTagName.'>'));

// =========================================================================================================
/**
 * HTML parser class
 * Responsibilities: Giving the user a one-stop-shopping access to all the features 
 * @package component-framework
 * @author Adrian Meyer <adrian.meyer@unc.edu>
 */
// =========================================================================================================
class HtmlParser {
  /** 
	 * Instance of htmlNode representing the root node of the document
	 * @var htmlNode
	 */
  var $RootNode = NULL;
  /** 
	 * Last HTML that was parsed
	 * @var string
	 */
	var $LastHtml = NULL;
  /** 
	 * Parser options 
	 * @var int
	 * @see $GLOBALS[gHtmlParser]['parseOptions']
	 */
	var $ParseOptions = poNone;

  // *************************************
  /** 
	 * Constructor
	 * Example:
	 * <code>
	 *   require_ones( 'php4-html-dom.php' );
	 *   $myParser = new htmlParser();
	 * </code>
	 * @return htmlParser
	 * @access public
	 */
  // *************************************
	function htmlParser() {
	  $dummy = NULL;
		$this->RootNode = new htmlNode($dummy);
	}
	
  // *************************************
  /** 
	 * Parse an HTML string
	 * Example:
	 * <code>
   *   $myParser->parse( '<b>Hello</b>&nbsp;<i>world</i>!' );
	 * </code>
	 * @param string $aHtml String containing HTML to be parsed
	 * @return void
	 * @access public
	 */
  // *************************************
	function parse($aHtml){
	  $dummy = NULL;
		$this->RootNode = new htmlNode( $dummy );
		$this->RootNode->parseHtml( '<'.rootTagName.'>'.$aHtml.'</'.rootTagName.'>', $this->ParseOptions );
		$this->RootNode->TagName = '';
		$this->RootNode->TagType = ttRoot;
		$this->RootNode->ParseStartPosition = 0;
		$this->RootNode->ParseEndPosition = strlen($aHtml);
		$this->RootNode->ParseLinePosition = 0;
		$this->RootNode->ParseLineNumber = 0;
		$this->LastHtml = $aHtml;
	}
	
  // *************************************
  /** 
	 * Read the current HTML
	 * Example:
	 * <code>
   * echo $myParser->getHtml();
	 * </code>
	 * @return string
	 * @access public
	 */
  // *************************************
	function getHtml(){
	  if ($this->RootNode)
		  return $this->RootNode->getHtml();
		else
		  return '';
	}
	
  // *************************************
  /** 
	 * Find a single node by tag name case insensitive
	 * Source:
	 * {@source}
	 * <br>Example:
	 * <code>
   * $myNode = $myParser->getElementByTagName( 'div' );
	 * if (isset($myNode))
	 *  echo $myNode->Attrubites['id'];
	 *</code>
	 * @param string $aTagName Tag name of node to be searched for
	 * @return htmlNode|NULL
	 * @access public
	 */
  // *************************************
	function getElementByTagName( $aTagName ){
	  if ($this->RootNode)
		  return $this->RootNode->findNodeDown( $aTagName );
		else
		  return NULL;
	}
	
  // *************************************
  /** 
	 * Find a single node by id case insensitive
	 * Source:
	 * {@source}
	 * <br>Example:
	 * <code>
   * $myNode = $myParser->getElementById( 'logo' );
	 * if (isset($myNode))
	 *   echo htmlspecialchars( $myNode->getHtml());
	 * </code>
	 * @param string $aId Id attribute of node to be searched for
	 * @return htmlNode|NULL
	 * @access public
	 */
  // *************************************
	function getElementById( $aId ){
	  if ($this->RootNode)
		  return $this->RootNode->findNodeByAttributeDown( 'id', $aId );
		else
		  return NULL;
	}	
		
  // *************************************
  /** 
	 * Find a single node by name case insensitive
	 * Source:
	 * {@source}
	 * <br>Example:
	 * <code>
   * $myTextInput = $myParser->getElementByName( 'firstName' );
	 * if (isset($myTextInput))
	 *   echo htmlspecialchars( $myTextInput->getHtml());
	 * </code>
	 * @param string $aName Name attribute of node to be searched for
	 * @return htmlNode|NULL
	 * @access public
	 */
  // *************************************
	function getElementByName( $aName ){
	  if ($this->RootNode)
		  return $this->RootNode->findNodeByAttributeDown( 'name', $aName );
		else
		  return NULL;
	}	
		
  // *************************************
  /** 
	 * Find multiple nodes by name case insensitive
	 * Source:
	 * {@source}
	 * <br>Example:
	 * <code>
   * $myNodes = $myParser->getElementsByTagName( 'img' );
	 * if (isset($myImages))
	 *   foreach( $myNodes as $key => $obj )
	 *     echo $myNodes[$key]->Attributes['src'].'<br>';
	 * </code>
	 * @param string $aTagName Tag name of nodes to be searched for
	 * @return array|NULL
	 * @access public
	 */
  // *************************************
	function getElementsByTagName( $aTagName ){
	  if ($this->RootNode) {
		  $tempResult = $this->RootNode->findNodesDown( $aTagName );
			if (count($tempResult)>0)
			  return $tempResult;
			else
			  return NULL;
		} else
		  return NULL;
	}
	
  // *************************************
  /** 
	 * Find multiple nodes by id case insensitive
	 * Source:
	 * {@source}
	 * <br>Example:
	 * <code>
   * $myNodes = $myParser->getElementsById( 'test' );
	 * if (isset($myImages))
	 *   foreach( $myNodes as $key => $obj )
	 *     echo htmlspecialchars( $myNodes[$key]->getHtml());
	 * </code>
	 * @param string $aId Id attribute of nodes to be searched for
	 * @return array|NULL
	 * @access public
	 */
  // *************************************
	function getElementsById( $aId ){
	  if ($this->RootNode) {
		  $tempResult = $this->RootNode->findNodesByAttributeDown( 'id', $aId );
			if (count($tempResult)>0)
			  return $tempResult;
			else
			  return NULL;
		} else
		  return NULL;
	}
	
  // *************************************
  /** 
	 * Find multiple nodes by name case insensitive
	 * Source:
	 * {@source}
	 * <br>Example:
	 * <code>
   * $myControls = $myParser->getElementsByName( 'zip' );
	 * if (isset($myControls))
	 *   foreach( $myControls as $key => $obj )
	 *     echo htmlspecialchars( $myControls[$key]->getHtml());
	 * </code>
	 * @param string $aName Name attribute of nodes to be searched for
	 * @return array|NULL
	 * @access public
	 */
  // *************************************
	function getElementsByName( $aName ){
	  if ($this->RootNode) {
		  $tempResult = $this->RootNode->findNodesByAttributeDown( 'name', $aName );
			if (count($tempResult)>0)
			  return $tempResult;
			else
			  return NULL;
		} else
		  return NULL;
	}
	
}
		
// =========================================================================================================
/**
 * HTML node class
 * Responsibilities: represents one single element or text segment of HTML in DOM structure
 * @package component-framework
 * @author Adrian Meyer <adrian.meyer@unc.edu>
 */
// =========================================================================================================
class htmlNode {
  /** 
	 * Attributes of node. The attributes are name value pairs. On boolean attributes the value is NULL
	 * @var array
	 */
  var $Attributes;
  /** 
	 * AttributeQuotes or Attributes. The attribute quotes are name value pairs with the quote type in the value part
	 * @var array
	 */
  var $AttributeQuotes;                       
  /** 
	 * List of child nodes pointing to instances of htmlNode. The keys are ignored
	 * @see htmlNode
	 * @var array
	 */
	var $ChildNodes;                       
  /** 
	 * Defines how the tag is displayed in HTML. <name> </name> <name/> <!-- comment -->,...
	 * @see $GLOBALS[gHtmlParser]['tagTypes']
	 * @var string
	 */
	var $TagType;                          
  /** 
	 * Name visible in HTML. For type ttText and ttRoot the name is blank
	 * @var string
	 */
	var $TagName;                          
  /** 
	 * Points to parent node
	 * @var htmlNode
	 */
	var $Parent;                           
  /** 
	 * Information between the < and > without the name of the tag.
	 * @var string
	 */
	var $TagData;                          
  /** 
	 * Starting position of tag from the parsing operation
	 * @var integer
	 */
	var $ParseStartPosition;
  /** 
	 * Ending position of tag from the parsing operation
	 * @var integer
	 */
	var $ParseEndPosition;

  /** 
	 * Line number of tag from the parsing operation
	 * @var integer
	 */
	var $ParseLineNumber;
  /** 
	 * Position within Line of tag from the parsing operation
	 * @var integer
	 */
	var $ParseLinePosition;
  /** 
	 * Defines position in the child array of the parent
	 * @var integer
	 */
	var $Index;

  // *************************************
  /** 
	 * Constructor
	 * @param htmlNode $aParent Parent node this new node belongs to
	 * @param string $aTagType Initial type of tag
	 * @return htmlNode
	 * @access public
	 * @see $GLOBALS[gHtmlParser]['tagTypes']
	 */
  // *************************************
	function __construct( $aParent, $aTagType = ttUnknown ){
		$this->clear();
		$this->Parent = $aParent;
		$this->TagType = $aTagType;
	}

  // *************************************
  /** 
	 * Adds a new htmlNode instance to the end of the ChildNodes array
	 * @param string $aTagType Initial type of tag
	 * @return htmlNode
	 * @access public
	 * @see $GLOBALS[gHtmlParser]['tagTypes']
	 */
  // *************************************
	function addChildNode( $aTagType = ttUnknown ) {
	  $tempNode = new htmlNode( $this, $aTagType );
		$tempNode->Index = count($this->ChildNodes);
		$this->ChildNodes[] = $tempNode;
		return $tempNode;
	}

  // *************************************
  /** 
	 * Adds a sibling to the htmlNode 
	 * @param string $aTagType Initial type of tag
	 * @param bool $aBefore The sibling will be added in front of the htmlNode
	 * @return htmlNode
	 * @access public
	 * @see $GLOBALS[gHtmlParser]['tagTypes']
	 */
  // *************************************
	function addSibling( $aTagType = ttUnknown, $aBefore = false ) {
	  if ($aBefore)
  	  $tempNode = $this->Parent->insertChildNode( $this->Index, $aTagType );
		else
  	  $tempNode = $this->Parent->insertChildNode( $this->Index + 1, $aTagType );
		return $tempNode;
	}


  // *************************************
  /** 
	 * Inserts a child node at a given position of the ChildNodes array. 0 ist the first item
	 * @param string $aTagType Initial type of tag
	 * @return htmlNode
	 * @access public
	 * @see $GLOBALS[gHtmlParser]['tagTypes']
	 */
  // *************************************
	function insertChildNode( $aPosition, $aTagType = ttUnknown ) {
	  $tempNode = new htmlNode( $this, $aTagType );
		$tempNode->Index = $aPosition;

		// split the array and adjust the indexes of the nodes		
    $nodesAfter = array_splice($this->ChildNodes,$aPosition);
    foreach( $nodesAfter as $key => $obj )
		  $nodesAfter[$key]->Index = $aPosition + 1;

		// add the new node and merge lists		
    $this->ChildNodes[] = $tempNode;
    $this->ChildNodes = array_merge($this->ChildNodes,$nodesAfter);
				
		return $tempNode;
	}
	
  // *************************************
  /** 
	 * Inserts a parentnode around the existing node
	 * @param string $aTagType Initial type of tag
	 * @return htmlNode
	 * @access public
	 * @see $GLOBALS[gHtmlParser]['tagTypes']
	 */
  // *************************************
	function insertParentNode( $aTagType ) {
	  $tempNode = new htmlNode( $this->Parent, $aTagType );
		$tempNode->Index = $this->Index;
		
		if ( isset( $this->Parent ))
  		$tempNode->Parent->ChildNodes[ $this->Index ] = $tempNode;
			
		$this->Index = 0;
		$tempNode->ChildNodes[] = $this;
    return $tempNode;		
	}

  // *************************************
  /** 
	 * Get the HTML from this node and all its children
	 * @return string
	 * @access public
	 */
  // *************************************
	function getHtml() {
	  $tempHtml='';

	  // process text
		if ($this->TagType == ttText )
 		  $tempHtml.=$this->TagData;
	
	  // process the node only if we have a tag name
	  elseif (strlen( $this->TagName  ) > 0 ) {

		  switch ($this->TagType) {
			  case ttComment: {
    		  $tempHtml.='<!-- '.$this->TagData.' -->';
					break;				
				}

			  case ttDocType: {
    		  $tempHtml.='<'.$this->TagName.' '.$this->TagData.'>';
					break;				
				}
				
			  case ttNormal:
			  case ttSingle:
			  case ttSimple: {
    		  $tempHtml.='<'.$this->TagName;
					
					// add attributes. if value is TRUE no = sign
					foreach ($this->Attributes as $key => $value) 
					  if (true===$value || is_null($value)) {
        		  $tempHtml.=' '.$key;
						} else {
        		  $tempHtml.=' '.$key.'='.$this->AttributeQuotes[$key].$value.$this->AttributeQuotes[$key];
  					}

 					if ( $this->TagType == ttSingle) $tempHtml.='/';
    		  $tempHtml.='>';
				}
			}
		}
		
		// add children
	  $tempHtml.=$this->getInnerHtml();
		
		// closing tag	
	  if ($this->TagType == ttNormal) 
 		  $tempHtml.='</'.$this->TagName.'>';
			
		return $tempHtml;
	}
	
  // *************************************
  /** 
	 * Get the inner HTML from this nodes children
	 * @return string
	 * @access public
	 */
  // *************************************
	function getInnerHtml() {
	  $tempHtml = '';

		// add children
		foreach ($this->ChildNodes as $key => $obj )
		  $tempHtml.=$this->ChildNodes[$key]->getHtml();

		return $tempHtml;
	}
  
  // *************************************
  /** 
	 * Walk the DOM in the hierarchy down.
	 * Example:
	 * <code>
   *   $myParser->RootNode->walkDown(myCallback);
   *   function myCallback( $aHtmlNode ) {
   *     echo $aHtmlNode->TagName."<br>";
   *     if ($aHtmlNode->TagName == 'b')
   *       return wdAbortBranch;		
   *   }
	 * </code>
	 * @param function($aHtmlNode) $aCallback Function that will be called for every node. Depending on its return value the walk will continue or abort
	 * @see $GLOBALS[gHtmlParser]['walkAbort']
	 * @return string
	 * @access public
	 */
  // *************************************
  function walkDown( $aCallback ) {
	  switch ( call_user_func( $aCallback, $this )) {
		  case wdAbort: return wdAbort;
		  case wdAbortBranch: return wdContinue;
		  case wdContinue: 
  		  foreach ( $this->ChildNodes as $key => $obj ) {
		  	  $tempResult = $this->ChildNodes[$key]->walkDown($aCallback);
  		    if ($tempResult == wdAbort) 
					  return wdAbort;				
				}
		}
	}
  
  // *************************************
  /** 
	 * Find an htmlNode by tag name in the hierarchy walking from bottom up. (Children to Parent)
	 * @param string $aTagName Name that should be searched for. This is case insensitive. Returns NULL if no match was found
	 * @return htmlNode|NULL
	 * @access public
	 */
  // *************************************
  function findNodeUp( $aTagName ) {
	  if ( strcasecmp( $this->TagName, $aTagName ) == 0 ) 
  	  $tempReturn = $this;
		elseif (isset($this->Parent)) 
  	  $tempReturn = $this->Parent->findNodeUp( $aTagName );
		else 
  	  $tempReturn = NULL;
		return $tempReturn;
	}

  // *************************************
  /** 
	 * Find an htmlNode by tag name in the hierarchy traversing down. (Parent to Children)
	 * @param string $aTagName Name that should be searched for. This is case insensitive. Returns NULL if no match was found
	 * @return htmlNode|NULL
	 * @access public
	 */
  // *************************************
  function findNodeDown( $aTagName ) {
	  if ( strcasecmp( $this->TagName, $aTagName ) == 0 ) 
  	  $tempReturn = $this;
    else {
  	  $tempReturn = NULL;
			// this syntax is needed since php4 does not support "ChildNodes as $node"
		  foreach ( $this->ChildNodes as $key => $obj ) {
			  $tempReturn = $this->ChildNodes[$key]->findNodeDown( $aTagName );
			  if (!is_null($tempReturn)) break;
			}
		}
    return $tempReturn;
	}

  // *************************************
  /** 
	 * Find an htmlNode by attribute name and value in the hierarchy traversing down. (Parent to Children)
	 * @param string $aAttribute Attribute name that should be searched for. [case insensitive]
	 * @param string $aValue Value that should be searched for. [case insensitive]
	 * @return htmlNode|NULL
	 * @access public
	 */
  // *************************************
  function findNodeByAttributeDown( $aAttribute, $aValue ) {
	  if ( strcasecmp( $this->getAttribute($aAttribute), $aValue ) == 0 ) 
  	  $tempReturn = $this;
    else {
  	  $tempReturn = NULL;
		  foreach ( $this->ChildNodes as $key => $obj ) {
			  $tempReturn = $this->ChildNodes[$key]->findNodeByAttributeDown( $aAttribute, $aValue );
			  if (!is_null($tempReturn)) break;
			}
		}
    return $tempReturn;
	}

  // *************************************
  /** 
	 * Find multiple htmlNode(s) by tag name in the hierarchy traversing down. (Parent to Children)
	 * @param string $aTagName Name that should be searched for. This is case insensitive. Returns NULL if no match was found
	 * @param bool $aStopOnMatch If true and the tag name matched the child items are not analyzed
	 * @return array|NULL
	 * @access public
	 */
  // *************************************
  function findNodesDown( $aTagName, $aStopOnMatch = false ) {
 	  $tempReturn = array();
		
	  // add self if a hit
	  if ( strcasecmp( $this->TagName, $aTagName ) == 0 ) {
  	  $tempReturn[] = $this;
			if ( $aStopOnMatch ) return $tempReturn;
		}
		
	  // add matching children
	  foreach ( $this->ChildNodes as $key => $obj ) {
		  $childReturn = $this->ChildNodes[$key]->findNodesDown( $aTagName );
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
	 * Find multiple htmlNode(s) by attribute name and value in the hierarchy traversing down. (Parent to Children)
	 * @param string $aAttribute Attribute name that should be searched for. [case insensitive]
	 * @param string $aValue Value that should be searched for. [case insensitive]
	 * @return array|NULL
	 * @access public
	 */
  // *************************************
  function findNodesByAttributeDown( $aAttribute, $aValue ) {
 	  $tempReturn = array();
		
	  // add self if a hit
	  if ( strcasecmp( $this->getAttribute($aAttribute), $aValue ) == 0 )
  	  $tempReturn[] = $this;
			
	  // add matching children
	  foreach ( $this->ChildNodes as $key => $obj ) {
		  $childReturn = $this->ChildNodes[$key]->findNodesByAttributeDown( $aAttribute, $aValue );
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
	 * Read an attribute by a given name. Case insensitive
	 * @param string $aAttribute Name of the attribute to be accessed. This is case insensitive. Returns NULL if no match was found
	 * @access public
	 * @return htmlNode|NULL
	 */
  // *************************************
	function getAttribute($aAttribute) {
	  foreach( $this->Attributes as $key => $value )
		  if (strcasecmp($aAttribute, $key ) == 0 )
			  return $value;
	}

  // *************************************
  /** 
	 * Write to an attribute by a given name. If a blank string or NULL is passed the attribute is removed
	 * @param string $aAttribute Name of the attribute to be set
	 * @param mixed $aValue Value of the attribute
	 * @access public
	 * @return NULL
	 */
  // *************************************
	function setAttribute($aAttribute,$aValue) {
	  $tempKey = $aAttribute;
	
	  foreach( $this->Attributes as $key => $value )
		  if (strcasecmp($aAttribute, $key ) == 0 ) {
			  $tempKey = $key;
				break;
			}
			
		// remove attribute if FALSE is passed
		if ($aValue === false) {
			unset( $this->Attributes[ $tempKey ] );
			unset( $this->AttributeQuotes[ $tempKey ] );
		} else 
		// set attribute to be blank on true
		if ($aValue === true) {
			$this->Attributes[ $tempKey ] = $aValue;
			$this->AttributeQuotes[ $tempKey ] = '';
		} else {
			$this->Attributes[ $tempKey ] = $aValue;
			$this->AttributeQuotes[ $tempKey ] = '"';
		}			
	}
	
  // *************************************
  /** 
	 * Get the content of the text tags
	 * Example:
	 * <code>
   * echo $node->getText();
	 * </code>
	 * @return string
	 * @access public
	 */
  // *************************************
	function getText() {
		if ( $this->TagType == ttText )
  	  $tempReturn = $this->TagData;
		else
  	  $tempReturn = '';
			
		foreach ($this->ChildNodes as $key => $obj )
		  $tempReturn.=$this->ChildNodes[$key]->getText();	  
		
		return $tempReturn;
	}

	// *************************************
  /** 
	 * Clear the entire content of a tag and set a new text only (no html markup allowed). Returns the new node with the text
	 * @param string $aText The text to be set on the node
	 * @access public
	 * @return htmlNode
	 */
  // *************************************
	function setText($aText) {
	  $this->clearChildNodes();
		$textNode = $this->addChildNode( ttText );
		$textNode->TagData = $aText;
		return $textNode;
	}
	
  // *************************************
  /** 
	 * Get the parsing position as a string
	 * @param bool $aIncludeAbsolute Will include the absolute character range of the node
	 * @access public
	 * @return string
	 */
  // *************************************
	function getParsePositionStr( $aIncludeAbsolute = false ) {
	  $tempReturn = "Line: {$this->ParseLineNumber}; Pos: {$this->ParseLinePosition}";
	  if ($aIncludeAbsolute)
  	  $tempReturn .= " (Absolute: {$this->ParseStartPosition}-{$this->ParseEndPosition})";
    return $tempReturn;
	}
	
  // *************************************
  /** 
	 * Clear TagName, all child node and attributes
	 * @access public
	 */
  // *************************************
	function clear() {
		$this->TagType = ttUnknown;
		$this->TagName = '';
    $this->clearChildNodes();
    $this->clearAttributes();
	}

  // *************************************
  /** 
	 * Clear all attributes
	 * @access public
	 */
  // *************************************
	function clearAttributes() {
	  $this->Attributes = Array();
	  $this->AttributeQuotes = Array();
	}
	
  // *************************************
  /** 
	 * Release and clear all child nodes
	 * @access public
	 */
  // *************************************
	function clearChildNodes() {
	  $this->ChildNodes = Array();
	}	

  // *************************************
  /** 
	 * Force an ending of node by finding it through TagName (case insensitive) walking hierarchy up
	 * @param string $aTagName Name that should be searched for
	 * @access private
	 * @return htmlNode|NULL
	 */
  // *************************************
  function forceCloseUp( $aTagName ) {
	  if ( strcasecmp( $this->TagName, $aTagName ) == 0 )
		  return $this;
		elseif (isset($this->Parent)) {
		  // is a tag between the match is a starting tag change it to simple
      if ($this->TagType == ttStart ){
			  $this->TagType = ttSimple;
				$this->moveDataToParent();
			}
		  return $this->Parent->forceCloseUp( $aTagName );						
		} else
		  return NULL;	  
	}
	
  // *************************************
  /** 
	 * Move the data of the node to the parent
	 * @access private
	 */
  // *************************************
	function moveDataToParent() {
	  if (isset($this->Parent)) {
			$splitPosition = $this->Index;
			
			// add everything before the item	
			$newChildNodes = array();		
			for ($i=0;$i<=$splitPosition;$i++) {
			  $this->Parent->ChildNodes[$i]->Index = count($newChildNodes);
				$newChildNodes[] = $this->Parent->ChildNodes[$i];
			}
				
			// add everything to the parent
			foreach ( $this->ChildNodes as $key => $obj ) {
			  $this->ChildNodes[$key]->Index = count($newChildNodes);
				$newChildNodes[] = $this->ChildNodes[$key];
				$this->ChildNodes[$key]->Parent = $this->Parent;
			}
			
			// add everything before the item			
			for ($i=$splitPosition + 1;$i<count($this->Parent->ChildNodes);$i++) {
			  $this->Parent->ChildNodes[$i]->Index = count($newChildNodes);
				$newChildNodes[] = $this->Parent->ChildNodes[$i];
			}

			$this->Parent->ChildNodes = $newChildNodes;
			$this->clearChildNodes();
		}	  
	}

  // *************************************
  /** 
	 * Parse a piece of HTML and build DOM
	 * @param string $aHtml HTML to be parsed
	 * @access private
	 * @see $GLOBALS[gHtmlParser]['parseModes']
	 */
  // *************************************
	function parseHtml( $aHtml, $aParseOptions = poNone ){
		$i = 0;
		$bookmark = $i;
		$bookmarkScript = $i;
		$parseMode = pmNormal;
		$lineNumber = 1;
		$tempLinePosition = -rootTagLength;
		$linePosition = 0;
		$quote = NULL;
		$tempNode = NULL;
		$tempParent = $this;
		
		while ($i < strlen( $aHtml )) {
		  $character = substr( $aHtml, $i, 1 );
   		$tempLinePosition++;
			switch ($character) {
				case '"':
				case "'": {
 					// just assume this is part of text if we are not in a tag
					if ($parseMode != pmInTag ) continue;					
           if ( $character == $quote ) 
             $quote = NULL;
           elseif ( is_null( $quote ))
             $quote = $character;
					
					break;
				}

				case "\n": {
				  $lineNumber++;
					$tempLinePosition = 0;
					break;
				}

				case '<': {
				  $linePosition = $tempLinePosition;
				
				  // continue parsing in case we are already in a tag. This is for < in attributes
          if ( $parseMode == pmInTag ) continue;

					// check for script ending if in script mode
          if ( $parseMode == pmScript ) {
  					if ( strcasecmp( substr( $aHtml, $i, 8 ), '</script' )!=0) 
						  continue;
					}

					// check for comments
					if ( substr( $aHtml, $i, 4 ) == '<!--' ) {
  					$bookmark = $i;						
					  $parseMode = pmComment;
						continue;
					}

          // check if we have text between the previous tag and the starting one
          if ($i>$bookmark) {

					  $text = substr( $aHtml, $bookmark, $i-$bookmark );
					  // we need to replace any combination of CRLF with spaces
					  if ($aParseOptions & poRemoveCRLF) {
						  $text = str_replace( "\r\n", " ", $text );
						  $text = str_replace( "\n\r", " ", $text );
						  $text = str_replace( "\r", " ", $text );
						  $text = str_replace( "\n", " ", $text );
						}
					
					  // we need to trim the text. This will also remove spaces and CRLFs at the beginnings or ends
					  if ($aParseOptions & poTrimText) {
						  $text = trim($text);
						}
						
						// if there is still some text left create a node an place the text in it.
						if ( strlen($text) > 0 ) {
							$tempNode = $tempParent->addChildNode( ttText );
							$tempNode->ParseStartPosition = $bookmark - rootTagLength;
							$tempNode->ParseEndPosition = $i - 1 - rootTagLength;
							$tempNode->ParseLineNumber = $lineNumber;
							$tempNode->ParseLinePosition = $linePosition;
							$tempNode->TagData = $text;
						}
					}

          // switch to open tag mode
					$parseMode = pmInTag;					
					// remember the starting position in the book mark
					$bookmark = $i;						
					break;
				}

				case '>': {
				  // if we are in comment check for end of comment
					if ( substr( $aHtml, $i-2, 3 ) == '-->' && $parseMode == pmComment ) {
					  $tempTag[tName] = '!--';
					  $tempTag[tType] = ttComment;
					  $tempTag[tData] = trim( substr( $aHtml, $bookmark + 4, $i-$bookmark-6 ));
					} else {

						// continue with > in quotes... for example attribute <div id="bad>idea">
            if ( !is_null( $quote )) continue;
						// continue with > in comments
					  if ($parseMode == pmComment) continue;
						// continue with > in text outside a tag. example <b>5 > 2</b>
					  if ($parseMode != pmInTag) continue;

						$tagHTML = substr( $aHtml, $bookmark + 1, $i-$bookmark-1 );
						$tempTag = $this->getTagProperties( $tagHTML );
					}
					
					// switch mode back to normal parsing
					$parseMode = pmNormal;
					
					// process the different tag types
				  unset( $tempNode );
					switch ($tempTag[tType]) {

					  // if we find a start tag add it to the same parent as a sibling. 
						// If no parent is set then we are in the root.						
					  case ttDocType: 
						case ttComment: 
						case ttSingle: 
						case ttStart:{
						  if ($this->TagType == ttUnknown ) {
							  $tempNode = $this;
  							$tempNode->TagType = $tempTag[tType];
							} else 
							  $tempNode = $tempParent->addChildNode( $tempTag[tType] );
							
							$tempNode->TagName = $tempTag[tName];
							$tempNode->TagData = $tempTag[tData];
							$tempNode->ParseStartPosition = $bookmark - rootTagLength;
							$tempNode->ParseEndPosition = $i - rootTagLength;
							$tempNode->ParseLineNumber = $lineNumber;
							$tempNode->ParseLinePosition = $linePosition;
							
							// parse out attributes except on doctype and comment tags
							if ( $tempTag[tType] != ttComment && $tempTag[tType] != ttDocType ) {
							  $tempNode->parseAttributes();

  							// only start hierarchy with a start tag
								if ( $tempTag[tType] == ttStart ) {
									$tempParent = $tempNode;
								}
									
								// handle script tag
								if (strcasecmp('script', $tempTag[tName]) == 0) {
								  $bookmarkScript = $i+1;
									if ($this->TagType == ttStart)
									  $parseMode = pmScript;
								}
							}
							
						  break;
						}
						
					  // if we find an end tag the node tree needs to be corrected by 
						// closing the closest matching tag in the hierarchy up
						case ttEnd: {
						  $tempStartNode = $tempParent->findNodeUp( $tempTag[tName] );
							if ($tempStartNode) {
								$tempParent->forceCloseUp( $tempTag[tName] );
								$tempStartNode->ParseEndPosition = $i;
								$tempStartNode->TagType = ttNormal;
								
								// handle script tag
								if (strcasecmp('script', $tempTag[tName]) == 0) {
								  $tempStartNode->clearChildNodes();
									$tempStartNode->setText( substr( $aHtml, $bookmarkScript, $bookmark - $bookmarkScript ));
									$bookmarkScript = $i+1;
								}
								
								$tempParent = $tempStartNode->Parent;
							}
						  break;
						}
					}
					$bookmark = $i+1;
					break;
				}
			}							
			$i++;
		}
	}
	
  // *************************************
  /** 
	 * Get all the tag properties 
	 * @param string $aTag Tag without the < and the >
	 * @access private
	 * @return array
	 * @see $GLOBALS[gHtmlParser]['tagProperties']
	 */
  // *************************************
	function getTagProperties($aTag) {
	  $tempTag = Array();
		$startPos = 0;
		$length = strlen( $aTag );

	  // check if we have a slash at the end. If we have this </strong/> it will be interpreted as </strong>
		if ( substr( $aTag, -1, 1 ) == '/' ) {
		  $tempTag[tType] = ttSingle;
			$length--;
		} else
		  $tempTag[tType] = ttStart;
			
	  // check if we have a slash at the beginning
		if ( substr( $aTag, 0, 1 ) == '/' ) {
		  $tempTag[tType] = ttEnd;
			$startPos++;
			$length--;
		}
		
	  $elements = explode( ' ', substr( trim( $aTag ), $startPos, $length ));
		$tempTag[tName] = $elements[0];
		$tempTag[tData] = substr( $aTag, $startPos + strlen($tempTag[tName]), $length - strlen($tempTag[tName]) );
		
	  // check for doc type tag
 		if ( strcasecmp( $tempTag[tName], '!DOCTYPE' ) == 0 ) 
 			$tempTag[tType] = ttDocType;
		
		return $tempTag;
	}
	
  // *************************************
  /** 
	 * Parse out the attributes from the tag data. TagData is cleared by this operation
	 * @param string $aTag Tag without the < and the >
	 * @access private
	 * @return array
	 * @see $GLOBALS[gHtmlParser]['tagProperties']
	 */
  // *************************************
	function parseAttributes() {
	  $this->clearAttributes();

    // check if there is something to do
		$html = trim( $this->TagData ).' ';
		$this->TagData = '';
		if (strlen( $html ) == 0 ) return;

		$bookmark = 0;
		$split = 0;
		$quote = NULL;
		$lastQuote = NULL;
		
    for ($i=0; $i < strlen( $html ); $i++ ) {
		  $character = substr( $html, $i, 1 );

      //!!-- do not change the order of these if statements --!!
			
      // reset quoting character
      if ( $character == $quote)
			  $quote = NULL;
			
			// continue if we are in quote
      elseif ( !is_null($quote)) 
			  continue;
			
			// check for quote
			elseif ( $character == '"' || $character == "'" ) {
			  $quote = $character;
    		$lastQuote = $character;
			}
			
			// check for name value splits
			elseif ( $character == '=' ) 
			  $split = $i + 1;

			// skip over multiple spaces
			elseif ( $character == ' ' && $bookmark==$i ) {
				$bookmark = $i+1;
				$split = $i+1;
			}
			
			// check for new attribute
			elseif ( $character == ' ' ) {
			  
				// do we have name value pair?
				if ($split!=$bookmark) {
 				  $name = substr( $html, $bookmark, $split - $bookmark - 1 );
					
					// remove quote if we have one
					if (isset($lastQuote)) 
					  $value = substr( $html, $split + 1, $i - $split - 2 );
					else
					  $value = substr( $html, $split, $i - $split );
				
				  $this->Attributes[ $name ] = $value;
					$this->AttributeQuotes[ $name ] = $lastQuote;
				} else {
				// process a boolean attribute by setting the value to TRUE
				  $this->Attributes[ substr( $html, $split, $i - $split ) ] = true;
					$this->AttributeQuotes[ substr( $html, $split, $i - $split ) ] = $lastQuote;
				}

        $lastQuote = NULL;
				$bookmark = $i+1;
				$split = $i+1;
			}
		}
	}
	
  // *************************************
  /** 
	 * Find a single node by tag name case insensitive from this node down
	 * @param string $aTagName Tag name of node to be searched for
	 * @return htmlNode|NULL
	 * @access public
	 */
  // *************************************
	function getElementByTagName( $aTagName ){
	  return $this->findNodeDown( $aTagName );
	}
	
  // *************************************
  /** 
	 * Find a single node by id case insensitive from this node down
	 * @param string $aId Id attribute of node to be searched for
	 * @return htmlNode|NULL
	 * @access public
	 */
  // *************************************
	function getElementById( $aId ){
	  return $this->findNodeByAttributeDown( 'id', $aId );
	}	
		
  // *************************************
  /** 
	 * Find a single node by name case insensitive from this node down
	 * @param string $aName Name attribute of node to be searched for
	 * @return htmlNode|NULL
	 * @access public
	 */
  // *************************************
	function getElementByName( $aName ){
	  return $this->findNodeByAttributeDown( 'name', $aName );
	}	
		
  // *************************************
  /** 
	 * Find multiple nodes by name case insensitive from this node down
   * $myNodes = $myParser->getElementsByTagName( 'img' );
	 * @param string $aTagName Tag name of nodes to be searched for
	 * @return array|NULL
	 * @access public
	 */
  // *************************************
	function getElementsByTagName( $aTagName ){
		$tempResult = $this->findNodesDown( $aTagName );
		if (count($tempResult)>0)
			return $tempResult;
		else
			return NULL;
	}
	
  // *************************************
  /** 
	 * Find multiple nodes by id case insensitive from this node down
	 * @param string $aId Id attribute of nodes to be searched for
	 * @return array|NULL
	 * @access public
	 */
  // *************************************
	function getElementsById( $aId ){
		$tempResult = $this->findNodesByAttributeDown( 'id', $aId );
		if (count($tempResult)>0)
			return $tempResult;
		else
			return NULL;
	}
	
  // *************************************
  /** 
	 * Find multiple nodes by name case insensitive from this node down
	 * @param string $aName Name attribute of nodes to be searched for
	 * @return array|NULL
	 * @access public
	 */
  // *************************************
	function getElementsByName( $aName ){
		$tempResult = $this->findNodesByAttributeDown( 'name', $aName );
		if (count($tempResult)>0)
			return $tempResult;
		else
			return NULL;
	}	
}

?>