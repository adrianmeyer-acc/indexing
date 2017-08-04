<?php
//====================================================================================
/** 
 * Database Service
 * @author Adrian Meyer <adrian.meyer@rocketmail.com>
 */
//====================================================================================

  define( 'dbkey', 'cc212a05bcfb45dc9410f9566da4e66c' );

  //====================================================================================
  /**
   * Exception for database
   * Responsibilities: Handle database exceptions
   * @author Adrian Meyer <adrian.meyer@rocketmail.com>
   */
  class DatabaseException extends \Exception{};
  
  //====================================================================================
  /**
   * Service for database
   * Responsibilities: Manage all apsects of database connections and calls
   * @author Adrian Meyer <adrian.meyer@rocketmail.com>
   */
  class DatabaseService {

    // internal singleton instance		
		private static $DatabaseServiceInstance = NULL;
  
    // *************************************
    /** 
     * Hide constructor for this singleton.
     * @return void
     */
	  private function __construct() {
    }

    // *************************************
    /** 
     * Get the singleton instance
     * @return void
     */
    static function getInstance() {
			if (!isset(self::$DatabaseServiceInstance)) {
			  self::$DatabaseServiceInstance = new DatabaseService();
  			self::$DatabaseServiceInstance->Connect();
			}
			return self::$DatabaseServiceInstance;
    }

    // *************************************
    /** 
     * Get the singleton instance
     * @return void
     */
		static function newQuery( $aQuery ) {
			$service = DatabaseService::getInstance();
			return $service->_newQuery( $aQuery );
		}
	
    // *************************************
    /** 
     * Connect to the database
     * @return void
     */
    public function connect() {
      // check if we are already connected
      if (isset($this->Connected)) {
        return;
      }

      // connect using mysqli
      $this->Mysqli = new \MySQLi( 'localhost', 'root', '123456', 'indx' );

      // check connection
      if ($this->Mysqli->connect_error) {
        throw new \DatabaseException( 'Could not connect: ('.
          $this->Mysqli->connect_errno.')'.$this->Mysqli->connect_error);
      }

      $this->initializeConnection( 'indx' );    
    }
    
    // *************************************
    /** 
     * Connect to an alternate database
     * @param string $aPassword The password for the user
     * @return void
     */
    public function connectAlternate( $aServer, $aDatabase, $aUsername, $aPassword ) {
  
      // connect to the database
      $this->Mysqli = new mysqli( 'p:'.$aServer, $aUsername, $aPassword, $aDatabase );
  
      if ($this->Mysqli->connect_error) {
        throw new \DatabaseException( 'Could not connect: ('.
          $this->Mysqli->connect_errno.')'.$this->Mysqli->connect_error);
      }
      
      $this->initializeConnection( $aDatabase );    
    }
    
    // *************************************
    /** 
     * Connect to an alternate database
     * @return void
     */
    private function initializeConnection() {
      $this->Mysqli->query("SET NAMES utf8");
      $this->Mysqli->set_charset('utf8');     
      $this->Mysqli->query("SET autocommit=1");
      
      $this->Connected = true;
    }
    
    // *************************************
    /** 
     * Create a new query
     * @param string $aQuery The actual query to be executed
     * @return DatabaseQuery
     */
    public function _newQuery( $aQuery ) {
      return new DatabaseQuery( $this, $aQuery );
    }
  
    // *************************************
    /** 
     * Create a new query and immediately execute it
     * @param string $aQuery The actual query to be executed
     * @return DatabaseQuery
     */
    public function _newQueryExec( $aQuery ) {
      $query = new DatabaseQuery( $this, $aQuery );
      $query->execute();
      return $query;
    }
  
    // *************************************
    /** 
     * Start a new transaction
     * @return void
     */
    static function startTransaction(){
      self::getInstance()->Mysqli->query( "START TRANSACTION" );
      self::getInstance()->Mysqli->query( "BEGIN" );
    }
  
    // *************************************
    /** 
     * Commit transaction
     * @return void
     */
    static function commit(){
      self::getInstance()->Mysqli->query( "COMMIT" );
      self::getInstance()->Mysqli->query( "SET AUTOCOMMIT = 1" );
    }
  
    // *************************************
    /** 
     * Rollback transaction
     * @return void
     */
    static function rollback(){
      self::getInstance()->Mysqli->query( "ROLLBACK" );
      self::getInstance()->Mysqli->query( "SET AUTOCOMMIT = 1" );
    }
		
    // *************************************
    /** 
     * Escape a string for SQL and to protect from SQL injection
     * @return void
     */
		static function escapeString( $aString ) {
			return self::getInstance()->Mysqli->escape_string( $aString );
		}
  }

  //====================================================================================
  /**
   * Class for database parameter
   * Responsibilities: Hold information for one parameter
   */
  class DatabaseParameter{
		var $Name = '';
		var $Value = '';
		
    // *************************************
    /** 
     * Get the type of the parameter
     * @return string
     */
		function getType() {
			if (is_bool($this->Value) ||
			    is_int($this->Value)) {
				return 'i';
			}

			if (is_float($this->Value) || 
			    is_numeric($this->Value)) {
				return 'd';
			}
			
			// for anything else use string
			return 's';
		}
	}

  //====================================================================================
  /**
   * Class for database queries
   * Responsibilities: Manage all apsects of database calls
   */
  class DatabaseQuery{
  
    /** 
     * Database service that created the query
     * @var DatabaseService
     */
    var $DatabaseService = NULL;
  
    /** 
     * the actual query that needs to tbe run
     * @var string
     */
    var $Query = '';
    
    /** 
     * The parameters used when executing the query
     * @var array
     */
    var $Parameters = array();
		
    /** 
     * Check if the statement was prepared
     * @var bool
     */
		var $Prepared = false;

    /** 
     * The parameters in the prepare order of the SQL
     * @var array
     */
		var $ParametersPrepareOrder = array();
  
    // *************************************
    /** 
     * constructor
     * @param DatabaseService $aDatabaseServices The creating database service (owner of this query)
     * @param string $aQuery The actual query to be executed
     * @return DatabaseQuery
     */
    function __construct( $aDatabaseService, $aQuery ){
      $this->DatabaseService = $aDatabaseService;
      $this->Query = $aQuery;
    }  
    
    // *************************************
    /** 
     * Execute the passed query
     * @param bool $aThrowExceptions If true the errors will be converted to exceptions
     * @return void
     */
    public function execute( $aThrowExceptions = true ) {    
      $queryEval = '';
    
      if (count( $this->Parameters ) > 0 ){
        
        // process all the parameters
        foreach ( $this->Parameters as $param ){
          
          // NULL parameter
          if ( is_null( $param->Value )) {
            $queryEval .= '$'.$param->Name.'="NULL"'.";\n";
            continue;
          }
  
          // string parameter
          if ( is_string( $param->Value )) {
            
            // use null on empty strings
            if ($param->Value == '' ) {
              $queryEval .= '$'.$param->Name.'="NULL"'.";\n";
            } else {
              $queryEval .= '$'.$param->Name.'=\'\\\''.
                $this->DatabaseService->Mysqli->escape_string( addslashes( $param->Value )).'\\\'\''.";\n";
            }
            continue;
          }
  
          $queryEval .= '$'.$param->Name.'='.$param->Value.";\n";
        }

        // add the query and evaluate the parameters    
        $queryEval .= '$query = "'.$this->Query.'";';
        eval( $queryEval );

        $this->QueryEval = $queryEval;
      } else {
        $query = $this->Query;
      }
    
      $this->QueryInternal = $query;
  
      // execute the query
      $this->Result = $this->DatabaseService->Mysqli->query( $query );
      if ( !$this->Result ) {
        if ( $aThrowExceptions ) {

// !!!!! uncomment the following line to debug query errors !!!!!
          echo '<pre>'.$this->QueryInternal.'</pre>';

          throw new DatabaseException( 'Query error: '. mysqli_error( $this->DatabaseService->Mysqli ));
        } else {
          trigger_error( 'Query error: '.mysqli_error( $this->DatabaseService->Mysqli ));
        }
      }
  
      // fetch the last identifier    
      $result = $this->DatabaseService->Mysqli->query( "SELECT LAST_INSERT_ID()" );
      $row = $result->fetch_row();
      $this->LastId = (integer)$row[0];
      $this->AffectedRows = mysqli_affected_rows ($this->DatabaseService->Mysqli );
    }

    // *************************************
    /** 
     * Switch to using prepare
     * @param bool $aThrowExceptions If true the errors will be converted to exceptions
     * @return void
     */
		function executePrepared( $aThrowExceptions = true ) {
			
      // ------------------------------------------------------------
			// check if we need to prepare the statement first
      // ------------------------------------------------------------
			if (!$this->Prepared) {
			
				$queryEval = '';
				$paramVars = array();
			
				if (count( $this->Parameters ) > 0 ){
					
					// build the parameter variable list
					foreach ( $this->Parameters as $param ){
						$paramVars[] = '\$'.$param->Name;
					}
					
					// search for the position of the parameter matches
					preg_match_all('/('.implode('|',$paramVars).')/', $this->Query, $matches, PREG_OFFSET_CAPTURE + PREG_PATTERN_ORDER );
					
					// check if we have any parameter matches
					if ( isset($matches[0]) && count($matches[0]) > 0 ) {
						
						// remember the prepare order and build eval statement
						foreach( $matches[0] as $match ) {
							$this->ParametersPrepareOrder[] = $this->Parameters[ substr( $match[0], 1 ) ];
							$queryEval .= $match[0]."='?';\n";
						}
					} 		
				} 
			
				$queryEval .= '$query = "'.$this->Query.'";';
				$return = @eval( $queryEval );
				
				// check if we had an error on the eval()
				if ( $error = error_get_last() ) {
					throw new DatabaseException( str_replace( 'variable', 'parameter', $error['message'] ));
				}
	
				// remember the built query
				$this->QueryInternal = $query;	
				
				// execute the query
				$this->Result = $this->DatabaseService->Mysqli->prepare( $query );
				if ( !$this->Result ) {
					if ( $aThrowExceptions ) {
						throw new DatabaseException( 'Query error: '. mysqli_error( $this->DatabaseService->Mysqli ));
					} else {
						trigger_error( 'Query error: '.mysqli_error( $this->DatabaseService->Mysqli ));
					}
				}
				
        // ------------------------------------------------------------
				// prapare and set the bind parameters
        // ------------------------------------------------------------
				$types = '';
				$values = array();
				foreach ( $this->ParametersPrepareOrder as $parameter ) {
					$types .= $parameter->getType();
					$values[] = &$parameter->Value;
				}

				call_user_func_array('mysqli_stmt_bind_param', array_merge(array($this->Result, $types), $values ));
				$this->Prepared = true;
			} 

      // execute the prepared statement
			$this->Result->execute();

      // fetch the last identifier    
      $result = $this->DatabaseService->Mysqli->query( "SELECT LAST_INSERT_ID()" );
      $row = $result->fetch_row();
      $this->LastId = (integer)$row[0];
      $this->AffectedRows = mysqli_affected_rows ($this->DatabaseService->Mysqli );


//			print_r($this->QueryInternal);
//			die;
		}
    
    // *************************************
    /** 
     * Set a parameter by value
     * @param string $aName The name of the parameter
     * @param string $aValue The value of the parameter
     * @return void
     */
    public function setParameter( $aName, $aValue ) {
			if (!isset($this->Parameters[ $aName ])) {
				$this->Parameters[ $aName ] = new DatabaseParameter();
			} 
      $this->Parameters[ $aName ]->Name = $aName;
      $this->Parameters[ $aName ]->Value = $aValue;
    }  
  
    // *************************************
    /** 
     * Set all the parameters from the passed object
     * @param object aObject The object to get the parameters from
     * @return void
     */
    public function setParametersFromObject( $aObject ) {  
      $properties = get_object_vars( $aObject );
      
      foreach ( $properties as $propery => $value ) {
        if ( !is_object($value) && !is_array($value)) {					
					if ( is_bool($value) ){
            $this->setParameter( $propery, (integer)$value );
					} else {
            $this->setParameter( $propery, $value );
					}
        }
      }
    }  
    
    // *************************************
    /** 
     * Get an associated array with all rows
     * @param string $aKeyColumn The name of the key column
     * @return void
     */
    public function getAssociatedArray( $aKeyColumn = NULL ) {
      $data = array();
      if($this->Result->num_rows >= 0) {
        while($row=$this->Result->fetch_assoc()) {
          if (isset($aKeyColumn)) {
            $data[$row[$aKeyColumn]]=$row;
          } else {
            $data[]=$row;
          }
        }
      }
      return $data;
    }

    // *************************************
    /** 
     * Return the dataset in a groupped 2D array 
     * @return array
     */
    public function getAssociatedArrayGroupped( $aGroupColumn ) {
      $data = array();
      if($this->Result->num_rows >= 0) {				
        while($row=$this->Result->fetch_assoc()) {
          $data[$row[$aGroupColumn]][]=$row;
        }
      }
      return $data;
		}

    
    // *************************************
    /** 
     * Get an array of objects
     * @param string $aObjectName The classname of the object to be created
     * @return array
     */
    public function getObjectArray( $aClassName, $aIndexColumn = NULL ) {
			$result = array();

      while( $object=$this->Result->fetch_object( $aClassName )) {
				if (isset( $object->$aIndexColumn )){
          $result[$object->$aIndexColumn] = $object;
				} else {
          $result[] = $object;
				}
      }
      return $result;
    }
    
    // *************************************
    /** 
     * Get a single row of data
     * @return array
     */
    public function getSingleRow() {
      
      $numericTypes = array(
        1=>'tinyint',
        2=>'smallint',
        3=>'int',
        4=>'float',
        5=>'double',
        8=>'bigint',
        9=>'mediumint',
        16=>'bit' );
      
      // fetch the data types to find numeric columns
      $numericColumns = array();
      $i = 0;
      while ($i<$this->Result->field_count) {
        $meta = $this->Result->fetch_field_direct($i);
        if ($meta) {
          if ( isset( $numericTypes[$meta->type])) {
            $numericColumns[ $meta->name ] = true;
          }
        }
        $i++;
      }
  
      $data = $this->Result->fetch_assoc();
      
      // convert data type to integer
      if ($data) {
        foreach( $data as $field => $value ) {
          if ($value !== NULL && isset($numericColumns[$field])) {
            $data[$field] = (integer) $value;
          }
        }
      }
      
      return $data;
    }
		
    // *************************************
    /** 
     * Return the passed data in the native PHP data type based on MySQL data type
     * @return array
     */
		public function convertDataType( $aDataType, $aValue ) {

			switch ( $aDataType )  {

				case 1:       // TINYINT, BOOL
				case 2:       // SMALLINT
				case 3:       // INTEGER
				case 8:       // BIGINT, SERIAL
				case 9:       // MEDIUMINT
				case 16:      // BIT
				  return (integer)$aValue;

				case 4:       // FLOAT
				case 5:       // DOUBLE
				case 246:     // DECIMAL, NUMERIC, FIXED
				  return (float)$aValue;
					
				case 7:      // TIMESTAMP
				case 10:      // DATE
				case 11:      // TIME
				case 12:      // DATETIME
				case 13:      // YEAR
           return strtotime( $aValue );
				
			}

        /*  
				
				Leave all others as string
				
				strings & binary
				------------
				CHAR: 254
				VARCHAR: 253
				ENUM: 254
				SET: 254
				BINARY: 254
				VARBINARY: 253
				TINYBLOB: 252
				BLOB: 252
				MEDIUMBLOB: 252
				TINYTEXT: 252
				TEXT: 252
				MEDIUMTEXT: 252
				LONGTEXT: 252			
				*/

			return $aValue;
			
		}
    
    // *************************************
    /** 
     * echo the query result in HTML table
     * @return void
     */
    function echoHtmlTable() {
  
      echo '<table border=1><tr>';
  
      if(! $this->Result) { 
        echo '<th>result not valid</th>'; 
      }  else {
        $i = 0;
        while ($i < $this->Result->field_count ) {
          $meta = $this->Result->fetch_field_direct($i);
          echo '<th style="white-space:nowrap">'.$meta->name.'</th>';
          $i++;
        }
        echo '</tr>';
       
        if($this->Result->num_rows == 0) {
          echo '<tr><td colspan="'.$this->Result->field_count.'"><strong><center>no result</center></strong></td></tr>';
        } else {
          while($row=$this->Result->fetch_assoc()) {
            echo '<tr style="white-space:nowrap">';
            foreach($row as $key=>$value) { 
              if ( $value == NULL ) { 
                $value = '&nbsp;'; 
              }
              echo '<td>'.$value.'</td>'; 
            }
            echo '</tr>';
          }
        }
      }
      echo '</table>';
    }
    
    // *************************************
    /** 
     * Return the dataset in indexed array using 1st column for index and a TRUE on the value
     * @return array
     */
    public function getFirstColumnIndexedArray() {
			$fields = $this->Result->fetch_fields();
			$result = array();

      while($row=$this->Result->fetch_row()) {
        $result[ $this->convertDataType( $fields[0]->type, $row[0] ) ] = TRUE;
      }
      return $result;
   }

    // *************************************
    /** 
     * Return the dataset in indexed array using 1st column for index and 2nd for value
     * @return array
     */
    public function get2ColumnIndexedArray() {
			$fields = $this->Result->fetch_fields();
			$result = array();

      while($row=$this->Result->fetch_row()) {
        $result[ $this->convertDataType( $fields[0]->type, $row[0] ) ] = 
				  $this->convertDataType( $fields[1]->type, $row[1] );
      }
      return $result;
   }
    
    // *************************************
    /** 
     * Return the dataset in indexed array using 1st column for index and 2nd for value
     * @return array
     */
    public function getFirstColumnIncrementArray() {
      $result = array();
      while($row=$this->Result->fetch_row()) {
        $result[] = $row[0];
      }
      return $result;
    }
    
    // *************************************
    /** 
     * Return the dataset using 1st column in simple array and convert to integer
     * @return array
     */
    public function getFirstColumnIntegerIncrementArray() {
      $result = array();
      while($row=$this->Result->fetch_row()) {
        $result[] = (integer)$row[0];
      }
      return $result;
    }

    // *************************************
    /** 
     * Return the dataset in a 2D array using the 1st and 2nd column converted to integer
     * @return array
     */
    public function get2Column2DIntegerArray() {
      $result = array();
      while($row=$this->Result->fetch_row()) {
        $result[(integer)$row[0]][] = (integer)$row[1];
      }
      return $result;
    }
		
    // *************************************
    /** 
     * Set the properties on an object
     * @param object $aObject The object to set the properties on
     * @return void
     */
    public function setObjectProperties( $aObject ) {
  
      $properties = get_object_vars( $aObject );
      $upperProperties = array_change_key_case( $properties, CASE_UPPER );
      $propertyMap = array_combine( array_keys($upperProperties),array_keys($properties));
      $data = $this->getSingleRow();

      if ( count( $data ) > 0 ) {
        foreach ( $data as $key=>$value ) {
          if ( isset( $propertyMap[ strtoupper($key) ] )) {
						$prop = $propertyMap[ strtoupper($key) ];
            if ($value!==NULL && is_integer($aObject->$prop )) {
              $aObject->$prop = intval($value);
            } else {
              $aObject->$prop = $value;
            }
          }
        }
      }
    }  
    
    // *************************************
    /** 
     * Get all the data in CSV format
     * @return void
     */
    public function getCSVData() {
      $result = '';
      $row = array();
      // fetch 1st row 
      if($row=$this->Result->fetch_assoc()) {
  
        // send header 
        $result .= '"'.implode( '","', array_keys( $row )).'"'."\n";

        // remember variable names        
        $variables = array_keys( $row );
        do {
          // preserve possible leading zeros on codes, THIS IS A HACK to get around the excel CSV importer removing leading zeros
          foreach( $row as $key => $value ) {
            if ( $variables[$key] == 'p_identifier' ) {
              $row[$key] = '="'.$value.'"';
            } else {
              $row[$key] = '"'.$value.'"';
            }
          }
          
          $result .= implode( ',', $row )."\n";
          
        }  while ( $row=$this->Result->fetch_row() ); 
      }
      return $result;
    }
  }  

?>