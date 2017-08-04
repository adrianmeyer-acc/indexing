<?php
//====================================================================================
/** 
 * WWW Code builder page
 * @author Adrian Meyer <adrian.meyer@rocketmail.com>
 */
//====================================================================================

  require_once( 'php/global.utils.php' );
  require_once( 'php/service.database.php' );

  //====================================================================================
  /**
   * Code builder engine
   * Responsibilities: Hold all functionality to build model class and DB IO for framework
   */
  class CodeBuilder {

    /** the database name */
    var $database = NULL;

    /** all the tables */
    var $tables = NULL;

    // *************************************
    /** 
     * Create the code builder class
     * @return void
     */
    function __construct(){
      $this->loadDatabase();
      $this->loadTables();      
    }

    // *************************************
    /** 
     * Load the database name
     * @return void
     */
    function loadDatabase(){
      $call = \DatabaseService::newQuery( 'select database() as `db`' );
			$call->execute();
      $data = $call->getSingleRow();
      $this->database = $data['db'];
    }

    // *************************************
    /** 
     * Load the tables from the DB
     * @return void
     */
    function loadTables(){
      $call = \DatabaseService::newQuery( "
        select `TABLE_NAME`, `TABLE_COMMENT`
        from `INFORMATION_SCHEMA`.`TABLES`
        where `table_schema`='{$this->database}'
        and `TABLE_NAME` not like 'rev_%'
        and `TABLE_NAME` not like '%Lookup'
        or `TABLE_NAME` = 'acc_Account'
      " );
			$call->execute();
      $this->tables = $call->getAssociatedArray();
    }
    
    // *************************************
    /** 
     * Check if a table exists
     * @param string $aTableName The table name
     * @return bool
     */
    function tableExists( $aTableName ) {
      $call = \DatabaseService::newQuery( "
        select count(*) as `CNT`
        from `INFORMATION_SCHEMA`.`TABLES`
        where `table_schema`='{$this->database}'
        and concat(`TABLE_NAME`,'') = '{$aTableName}'" );
			$call->execute();
      $data = $call->getSingleRow();
      return $data['CNT'] > 0;
    }
    
    // *************************************
    /** 
     * Build PHP model code
     * @param string $aTableName The table name
     * @return string
     */
    function buildPhpModel($aTableName){

      $mainTable = $this->createTableBuilder( $aTableName );
      $this->LookupTables = array();
      $this->ForeignTables = array();

      // load the tables for the foreign keys
      foreach ( $mainTable->foreignKeys as $foreignKey){
        if (stripos($foreignKey['REFERENCED_TABLE_NAME'], 'Lookup' )!==FALSE){
          $this->LookupTables[$foreignKey['COLUMN_NAME']] = 
            $this->createTableBuilder( $foreignKey['REFERENCED_TABLE_NAME'] );
        } else {
          $this->ForeignTables[$foreignKey['COLUMN_NAME']] =
            $this->createTableBuilder( $foreignKey['REFERENCED_TABLE_NAME'] );
        }
      }
            
      $code = "<?php\n";
      $code.= "//====================================================================================\n";
      $code.= "/**\n";
      $code.= " * File containing code for {$mainTable->nameSpaced}\n";
      $code.= " * @author Adrian Meyer <adrian.meyer@rocketmail.com>\n";
      $code.= " */\n";
      $code.= "//====================================================================================\n";
      $code.= "\n";
      $code.= "  namespace {$mainTable->namePrefix};\n";
      $code.= "\n";
      // static lookup loads
      foreach( $this->LookupTables as $lookupTable ) {
        $code.= $lookupTable->generateLookupDefines();
        $code.= "\n";
      }
      $code.= "  /** Exception class for {$mainTable->nameSpaced} */\n";
      $code.= "  class {$mainTable->nameSimple}Exception extends \Exception{};\n";
      $code.= "\n";
      $code.= "  //====================================================================================\n";
      $code.= "  /**\n";
      $code.= "   * Model for {$mainTable->nameSpaced}\n";
      $code.= "   * Responsibilities: Hold all data and functionality associated with an {$mainTable->nameSpaced}\n";
      $code.= "   */\n";
      
      $code.= "  class {$mainTable->nameSimple}Model {\n";
      $code.= "\n";
      $code.= $mainTable->generateProperties();
      $code.= $mainTable->generateLoad();
      $code.= "\n";
      $code.= $mainTable->generateSave();
      $code.= "\n";
      
      // revision table if it exists
      if ($this->tableExists('rev_'.$aTableName)){
        $code.= $mainTable->generateSaveRevision();
        $code.= $mainTable->generateGetRevisions();
        $code.= "\n";
      }


      $code.= $mainTable->generateGetAll();
      $code.= "\n";

      // static lookup loads
      foreach( $this->LookupTables as $lookupTable ) {
        $code.= $lookupTable->generateLookupLoad();
        $code.= "\n";
      }
      
      $code.= "  }\n";
      $code.= "\n?>";

      return $code;
    }
    
    // *************************************
    /** 
     * Factory creating a table builder table instance
     * @param string $aTableName The table name
     * @return string
     */
    private function createTableBuilder( $aTableName ) {
      $tableBuilder = new CodeBuilderTable( $this->database, $this );
      $tableBuilder->loadTable( $aTableName );
      return $tableBuilder;
    }    
  }

  //====================================================================================
  /**
   * Code builder table
   * Responsibilities: Hold all table information
   */
  class CodeBuilderTable {

    /** Database name */
    var $database = NULL;

    /** Table name */
    var $tableName = NULL;

    /** Table comment */
    var $tableComment = NULL;

    /** list of columns */
    var $columns = NULL;

    /** List of foreign keys */
    var $foreignKeys = NULL;
    
    // *************************************
    /** 
     * Create the code builder class
     * @param string $aDatabase The satabase name
     * @param CodeBuilder $aCodeBuilder The code builder instance
     * @return void
     */
    function __construct( $aDatabase, $aCodeBuilder ){
      $this->database = $aDatabase;      
      $this->codeBuilder = $aCodeBuilder;      
    }

    // *************************************
    /** 
     * Load the tables from the DB
     * @param string $aTableName The table to be loaded
     * @return bool
     */
    function loadTable( $aTableName ) {
      $call = \DatabaseService::newQuery(  "
        select `TABLE_NAME`, `TABLE_COMMENT`
        from `INFORMATION_SCHEMA`.`TABLES`
        where `table_schema`='{$this->database}'
        and concat(`TABLE_NAME`,'') = '{$aTableName}'" );
			$call->execute();
      $tableInfo = $call->getSingleRow();
      
      $this->tableName = $tableInfo['TABLE_NAME'];
      $this->tableComment = $tableInfo['TABLE_COMMENT'];
			
			if ( $this->tableName == '' ) {
				return false;
			}
      
      $call = \DatabaseService::newQuery( "show full columns from `{$this->tableName}`" );
			$call->execute();
      $this->columns = $call->getAssociatedArray();      
      
      $call = \DatabaseService::newQuery( "          
        select TABLE_NAME,COLUMN_NAME,CONSTRAINT_NAME, REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME
        from INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        where TABLE_NAME = '{$this->tableName}'
        and REFERENCED_TABLE_NAME is not null" );
			$call->execute();
      $this->foreignKeys = $call->getAssociatedArray('COLUMN_NAME');
      
      $this->namePrefix = substr( $this->tableName, 0, strpos( $this->tableName, '_' ));
      $this->nameSimple = substr( $this->tableName, strpos( $this->tableName, '_' ) + 1 );
      $this->nameWords = preg_split('/(?=[A-Z])/',$this->nameSimple);
      $this->nameSpaced = strtolower(trim(implode(' ',$this->nameWords)));
      $this->tableAlias = strtolower( preg_replace('/[^A-Z]/', '', $this->nameSimple ));

      $this->primaryKey = '';
      foreach( $this->columns as $column ) {
        if ($column['Key']=='PRI') {
          $this->primaryKey = $column['Field'];
          $this->primaryLower = str_replace( '_id', '', $this->primaryKey );
          $this->primaryParameter = '$a'.ucfirst( $this->primaryLower ).'Id';
        }
      }
      
    }

    // *************************************
    /** 
     * generate the PHP code for object properties
     * @return string
     */
    function generateProperties(){
      $code = "";
      foreach( $this->columns as $column ) {
        $code.= "    /** {$column['Comment']} */\n";
        $code.= "    var \${$column['Field']} = ";
        
        switch( substr( $column['Type'], 0, 4 ) ) {
          case 'int(': $code.="0";
          break;

          case 'tiny': $code.="false";
          break;

          case 'varb': 
          case 'varc': $code.="''";
          break;

          default: $code.="NULL";
        }
        
        $code.= ";\n\n";
        
        // add label for lookup tables
        if (isset($this->codeBuilder->LookupTables[$column['Field']])) {          
          $code.= "    /** Label for ".str_replace( '_id', '', $column['Field'] )." */\n";
          $code.= "    var \$".str_replace( '_id', '_label', $column['Field'] )." = ''";
          $code.= ";\n\n";
        }
        
      }
      return $code;
    }

    // *************************************
    /** 
     * generate the load PHP code
     * @return string
     */
    function generateLoad(){
      $columnList = array();
      foreach( $this->columns as $column ) {
        // look for encrupted columns
        if ( stripos( $column['Type'], 'varbinary') !== false ) {
          $columnList[] = "AES_DECRYPT( {$this->tableAlias}.`{$column['Field']}`, \$dbkey ) as `{$column['Field']}`";
        } else {
          $columnList[] = "{$this->tableAlias}.`{$column['Field']}`";

          // add label select for FKs to lookup tables
          if (isset($this->codeBuilder->LookupTables[$column['Field']])) {
            $columnList[] = 
              "( ".
              $this->codeBuilder->LookupTables[$column['Field']]->generateLabelSelect($this->tableAlias).
              " ) as `".str_replace( '_id', '_label', $column['Field'] )."`";
          }
        }
      }
      
      $code = "    // *************************************\n";
      $code.= "    /**\n";
      $code.= "     * Load the {$this->nameSpaced} by a given {$this->primaryLower} ID\n";
      $code.= "     * @param integer {$this->primaryParameter} Identifies the {$this->nameSpaced} to be loaded\n";
      $code.= "     * @return bool Returns TRUE if an item was loaded. Otherwise FALSE\n";
      $code.= "     */\n";
      $code.= "    public function load( {$this->primaryParameter} ) {\n";
      $code.= "      \$call = \\DatabaseService::newQuery( '\n";
      $code.= "        select \n";
      $code.= "          ".implode( ",\n          ", $columnList )."\n";
      $code.= "        from \n";
      $code.= "          `{$this->tableName}` {$this->tableAlias}\n";
      $code.= "        where {$this->tableAlias}.`{$this->primaryKey}` = \${$this->primaryKey}' );\n";
      $code.= "      \$call->setParameter( '{$this->primaryKey}', {$this->primaryParameter} );\n";
      $code.= "      \$call->setParameter( 'dbkey', dbkey );\n";
      $code.= "      \$call->execute();\n";
      $code.= "      \$call->setObjectProperties( \$this );\n";
      $code.= "      return isset( \$this->{$this->primaryKey} );\n";
      $code.= "    }\n";
      return $code;
    }

    // *************************************
    /** 
     * generate the load PHP code
     * @return string
     */
    function generateGetRevisions(){
      
      $columnList = array();
      $encryptedColumn = false;
      foreach( $this->columns as $column ) {

        // add label select for FKs to lookup tables
        if (isset($this->codeBuilder->LookupTables[$column['Field']])) {
          $columnList[] = 
            "( ".
            $this->codeBuilder->LookupTables[$column['Field']]->generateLabelSelect($this->tableAlias).
            " ) as `".str_replace( '_id', '_label', $column['Field'] )."`";
        }

        // skip all ID and REV columns
        if (strlen($column['Field']) > 4) {
          if (( strpos($column['Field'], '_id', strlen($column['Field']) - 3) !== FALSE ||
                strpos($column['Field'], '_rev', strlen($column['Field']) - 4) !== FALSE ) &&
                $column['Field']!='revision_id' ) {
            continue;
          }
        }
        
        // look for encrupted columns
        if ( stripos( $column['Type'], 'varbinary') !== false ) {
          $columnList[] = "AES_DECRYPT( `{$column['Field']}`, \$dbkey ) as `{$column['Field']}`";
          $encryptedColumn = true;
        } else {
          $columnList[] = "`{$column['Field']}`";
        }
        
        // move revision identifier to the front
        if ( $column['Field']=='revision_id' ) {
          $data = array_pop( $columnList );
          $columnList = array_merge( array($data) ,$columnList );
        }
      }
      
      $code = "    // *************************************\n";
      $code.= "    /**\n";
      $code.= "     * Get all revisions of {$this->nameSpaced}\n";
      $code.= "     * @return void\n";
      $code.= "     */\n";
      $code.= "    public function getRevisions() {\n";
      $code.= "      \$call = \\DatabaseService::newQuery( '\n";
      $code.= "        select \n";
      $code.= "          ".implode( ",\n          ", $columnList )."\n";
      $code.= "        from \n";
      $code.= "          `rev_{$this->tableName}` {$this->tableAlias}\n";
      $code.= "        where {$this->tableAlias}.`{$this->primaryKey}` = \${$this->primaryKey}\n";
      $code.= "        order by revision_id' );\n";
      
      if ($encryptedColumn) {
        $code.= "      \$call->setParameter( 'dbkey', dbkey );\n";
      }
      $code.= "      \$call->setParameter( '{$this->primaryKey}', \$this->{$this->primaryKey} );\n";
      $code.= "      \$call->execute();\n";
      $code.= "      return \$call->getAssociatedArray('revision_id');\n";
      $code.= "    }\n";
      return $code;
    }

    // *************************************
    /** 
     * generate the load PHP code
     * @return string
     */
    function generateGetAll(){
      
      $columnList = array();
      $encryptedColumn = false;
      foreach( $this->columns as $column ) {
        // look for encrupted columns
        if ( stripos( $column['Type'], 'varbinary') !== false ) {
          $columnList[] = "AES_DECRYPT( `{$column['Field']}`, \$dbkey ) as `{$column['Field']}`";
          $encryptedColumn = true;
        } else {
          $columnList[] = "`{$column['Field']}`";
        }
      }
      
      $code = "    // *************************************\n";
      $code.= "    /**\n";
      $code.= "     * Load all ".Inflect::pluralize($this->nameSpaced)." \n";
      $code.= "     * @return void\n";
      $code.= "     */\n";
      $code.= "    static function getAll".Inflect::pluralize($this->nameSimple)."() {\n";
      $code.= "      \$call = \\DatabaseService::newQuery( '\n";
      $code.= "        select \n";
      $code.= "          ".implode( ",\n          ", $columnList )."\n";
      $code.= "        from \n";
      $code.= "          `{$this->tableName}`\n";
      $code.= "        order by 2' );\n";
      
      if ($encryptedColumn) {
        $code.= "      \$call->setParameter( 'dbkey', dbkey );\n";
      }
      $code.= "      \$call->execute();\n";
      $code.= "      return \$call->getAssociatedArray('{$this->primaryKey}');\n";
      $code.= "    }\n";
      return $code;
    }

    // *************************************
    /** 
     * generate the save PHP code
     * @return string
     */
    function generateSave() {

      $columnList = array();
      $paramList = array();
      $updateList = array();
      foreach( $this->columns as $column ) {

        $columnList[] = "`{$column['Field']}`";

        // look for encrupted columns
        if ( stripos( $column['Type'], 'varbinary') !== false ) {
          $paramList[] = "AES_ENCRYPT( \${$column['Field']}, \$dbkey )";
        } else {
          $paramList[] = "\${$column['Field']}";
        }

        if ( stripos( $column['Key'], 'PRI') !== false ) {
          $updateList[] = "`{$column['Field']}` = LAST_INSERT_ID( \${$column['Field']} )";
        } else 
        if ( stripos( $column['Type'], 'varbinary') !== false ) {
          $updateList[] = "`{$column['Field']}` = AES_ENCRYPT( \${$column['Field']}, \$dbkey )";
        } else {
          $updateList[] = "`{$column['Field']}` = \${$column['Field']}";
        }
        
        // check if we have a revision column
        if ($column['Field'] == 'revision_id') {
          $revisionParameter = "     * @param integer \$aRevisionId Identifies the revision used for saving\n";
          $revisionParams = "\$aRevisionId";
          $revisionSet = "      \$call->setParameter( 'revision_id', \$aRevisionId );\n";
          $revisionSave = "      \$this->saveRevision( \$aRevisionId );\n";
        } else {
          $revisionParameter = "";
          $revisionParams = "";
          $revisionSet = "";
          $revisionSave = "";
        }
      }

      $code = "    // *************************************\n";
      $code.= "    /**\n";
      $code.= "     * Save the {$this->nameSpaced}\n";
      $code.= $revisionParameter;
      $code.= "     * @return integer Returns the ID of the new or modified item\n";
      $code.= "     */\n";
      $code.= "    public function save( {$revisionParams} ){\n";
      $code.= "      \$call = \\DatabaseService::newQuery( '\n";
      $code.= "        INSERT INTO `{$this->tableName}` (\n";
      $code.= "          ".implode( ",\n          ", $columnList )."\n";
      $code.= "        ) VALUES (\n";
      $code.= "          ".implode( ",\n          ", $paramList )."\n";
      $code.= "        ) \n";
      $code.= "        ON DUPLICATE KEY UPDATE\n";
      $code.= "          ".implode( ",\n          ", $updateList )." ' );\n";
      $code.= "      \n";
      $code.= "      \$call->setParametersFromObject( \$this );\n";
      $code.= $revisionSet;
      $code.= "      \$call->setParameter( '{$this->primaryKey}', \$this->{$this->primaryKey} );\n";
      $code.= "      \$call->setParameter( 'dbkey', dbkey );\n";
      $code.= "      \$call->execute();\n";
      $code.= "      \$this->{$this->primaryKey} = \$call->LastId;\n";
      $code.= "      \n";
      $code.= $revisionSave;
      $code.= "      return \$this->{$this->primaryKey};\n";
      $code.= "    }  \n";
      return $code;
    }
    
    // *************************************
    /**
     * generate the save revision PHP code
     * @return string
     */
    function generateSaveRevision() {
      
      $fieldList = array();
      $valueList = array();
      foreach( $this->columns as $column ) {
        $fieldList[] = "`{$column['Field']}`";
        $valueList[] = "`{$column['Field']}`";
        
        if (isset($this->foreignKeys[$column['Field']])) {
          if ( $this->codeBuilder->tableExists( 
            'rev_'.$this->foreignKeys[$column['Field']]['REFERENCED_TABLE_NAME'] )) {

            $fieldList[] = "`".str_replace( '_id', '_rev', $column['Field'] )."`";

            $valueList[] = "(select `revision_id` from ".
              "`{$this->foreignKeys[$column['Field']]['REFERENCED_TABLE_NAME']}` rev ".
              "where {$this->tableAlias}.`{$column['Field']}` = rev.`{$column['Field']}` )";
          }
        }
      }
      
      $code = "    // *************************************\n";
      $code.= "    /** \n";
      $code.= "     * Save the revision\n";
      $code.= "     * @return void\n";
      $code.= "     */\n";
      $code.= "    private function saveRevision(){    \n";
      $code.= "        \n";
      $code.= "      \$call = \\DatabaseService::newQuery( '\n";
      $code.= "        INSERT INTO `rev_{$this->tableName}` (\n";
      $code.= "          ".implode( ",\n          ", $fieldList )."\n";
      $code.= "        ) SELECT\n";
      $code.= "          ".implode( ",\n          ", $valueList )."\n";
      $code.= "        FROM `{$this->tableName}` {$this->tableAlias}\n";
      $code.= "        WHERE `{$this->primaryKey}` = \${$this->primaryKey}' );\n";
      $code.= "      \$call->setParameter( '{$this->primaryKey}', \$this->{$this->primaryKey} );\n";
      $code.= "      \$call->execute();\n";
      $code.= "    } \n";
      $code.= "\n";
    
      return $code;
    }

    // *************************************
    /** 
     * generate the lookup table defines
     * @return string
     */
    function generateLookupDefines(){
      if (stripos( $this->tableName, 'Lookup' )!==FALSE){
        $call = \DatabaseService::newQuery( "select * from `{$this->tableName}`" );
				
        $data = $call->get2ColumnIndexedArray();
        
        $prefix = strtolower( preg_replace('/[^A-Z]/', '', str_replace( 'Lookup', '', $this->nameSimple )));

        $code = "  // ".trim(implode(' ',$this->nameWords))."\n";
        foreach( $data as $key => $item ) {
          $code.= "  define( \"{$prefix}". preg_replace('/[^A-Za-z0-9]/', '', $item )."\", {$key} );\n";
        }
        return $code;
        
      }
      return '';
    }

    // *************************************
    /** 
     * generate the lookup code with the current table information
     * @return string
     */
    function generateLookupLoad(){
      if (stripos( $this->tableName, 'Lookup' )!==FALSE){
       
        $prefix = strtolower( preg_replace('/[^A-Z]/', '', str_replace( 'Lookup', '', $this->nameSimple )));
        
        // name without the prefix and the Lookup at the end
        $name = substr( $this->tableName, strpos( $this->tableName, '_' ) + 1, -6 );
        $namePlural = Inflect::pluralize( $name );

        $columnList = array();
        foreach( $this->columns as $column ) {
          $columnList[] ="`{$column['Field']}`";
        }

        $code = "    // *************************************\n";
        $code.= "    /**\n";
        $code.= "     * ".str_replace( 'Lists', 'Get', $this->tableComment )."\n";
        $code.= "     * @return array\n";
        $code.= "     */\n";
        $code.= "    static function get{$namePlural}() {\n";
        $code.= "      \$call = services()->database()->newQuery(\n";
        $code.= "         'select ".implode(',',$columnList)."\n";
        $code.= "          from `{$this->tableName}`\n";
        $code.= "          order by {$columnList[0]}' );\n";
        $code.= "      \$call->execute();\n";
        $code.= "      return \$call->get2ColumnIndexedArray();\n";
        $code.= "    }\n";

        return $code;
      }
      return '';
    }
    
    // *************************************
    /** 
     * Generate the label select for a lookup table
     * @param string $aTableAlias The table alias to use
     * @return string
     */
    function generateLabelSelect( $aTableAlias ){
      return
        "select `label` from `{$this->tableName}` ".
        "where `{$this->primaryKey}`={$aTableAlias}.`{$this->primaryKey}`";
    }    
  }

  // *************************************
  /** 
   * Switch text from singular to plural form or reverse
   */
  class Inflect
  {
    /** regular expressions for plural patterns */    
    static $plural = array(
      '/(quiz)$/i'   => "$1zes",
      '/^(ox)$/i'=> "$1en",
      '/([m|l])ouse$/i'  => "$1ice",
      '/(matr|vert|ind)ix|ex$/i' => "$1ices",
      '/(x|ch|ss|sh)$/i' => "$1es",
      '/([^aeiouy]|qu)y$/i'  => "$1ies",
      '/(hive)$/i'   => "$1s",
      '/(?:([^f])fe|([lr])f)$/i' => "$1$2ves",
      '/(shea|lea|loa|thie)f$/i' => "$1ves",
      '/sis$/i'  => "ses",
      '/([ti])um$/i' => "$1a",
      '/(tomat|potat|ech|her|vet)o$/i'=> "$1oes",
      '/(bu)s$/i'=> "$1ses",
      '/(alias)$/i'  => "$1es",
      '/(octop)us$/i'=> "$1i",
      '/(ax|test)is$/i'  => "$1es",
      '/(us)$/i' => "$1es",
      '/s$/i'=> "s",
      '/$/'  => "s"
    );
    
    /** regular expressions for singular patterns */    
    static $singular = array(
      '/(quiz)zes$/i' => "$1",
      '/(matr)ices$/i'=> "$1ix",
      '/(vert|ind)ices$/i'=> "$1ex",
      '/^(ox)en$/i'   => "$1",
      '/(alias)es$/i' => "$1",
      '/(octop|vir)i$/i'  => "$1us",
      '/(cris|ax|test)es$/i'  => "$1is",
      '/(shoe)s$/i'   => "$1",
      '/(o)es$/i' => "$1",
      '/(bus)es$/i'   => "$1",
      '/([m|l])ice$/i'=> "$1ouse",
      '/(x|ch|ss|sh)es$/i'=> "$1",
      '/(m)ovies$/i'  => "$1ovie",
      '/(s)eries$/i'  => "$1eries",
      '/([^aeiouy]|qu)ies$/i' => "$1y",
      '/([lr])ves$/i' => "$1f",
      '/(tive)s$/i'   => "$1",
      '/(hive)s$/i'   => "$1",
      '/(li|wi|kni)ves$/i'=> "$1fe",
      '/(shea|loa|lea|thie)ves$/i'=> "$1f",
      '/(^analy)ses$/i'   => "$1sis",
      '/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i'  => "$1$2sis",
      '/([ti])a$/i'   => "$1um",
      '/(n)ews$/i'=> "$1ews",
      '/(h|bl)ouses$/i'   => "$1ouse",
      '/(corpse)s$/i' => "$1",
      '/(us)es$/i'=> "$1",
      '/s$/i' => ""
    );
    
    /** Irregular words */    
    static $irregular = array(
      'move'   => 'moves',
      'foot'   => 'feet',
      'goose'  => 'geese',
      'sex'=> 'sexes',
      'child'  => 'children',
      'man'=> 'men',
      'tooth'  => 'teeth',
      'person' => 'people',
      'valve'  => 'valves'
    );

    /** Uncounted words */    
    static $uncountable = array( 
      'sheep', 
      'fish',
      'deer',
      'series',
      'species',
      'money',
      'rice',
      'information',
      'equipment'
    );

    // *************************************
    /** 
     * Pluralize the string
     * @param string $string Input string
     * @return string
     */
    public static function pluralize($string)
    {
      // save some time in the case that singular and plural are the same
      if (in_array(strtolower($string), self::$uncountable))
        return $string;
      
      
      // check for irregular singular forms
      foreach (self::$irregular as $pattern => $result) {
        $pattern = '/' . $pattern . '$/i';
        
        if (preg_match($pattern, $string))
          return preg_replace($pattern, $result, $string);
      }
      
      // check for matches using regular expressions
      foreach (self::$plural as $pattern => $result) {
        if (preg_match($pattern, $string))
          return preg_replace($pattern, $result, $string);
      }
      
      return $string;
    }
    
    // *************************************
    /** 
     * Singularize the string
     * @param string $string Input string
     * @return string
     */
    public static function singularize($string)
    {
      // save some time in the case that singular and plural are the same
      if (in_array(strtolower($string), self::$uncountable))
        return $string;
      
      // check for irregular plural forms
      foreach (self::$irregular as $result => $pattern) {
        $pattern = '/' . $pattern . '$/i';
        
        if (preg_match($pattern, $string))
          return preg_replace($pattern, $result, $string);
      }
      
      // check for matches using regular expressions
      foreach (self::$singular as $pattern => $result) {
        if (preg_match($pattern, $string))
          return preg_replace($pattern, $result, $string);
      }
      
      return $string;
    }
    
    // *************************************
    /** 
     * Pluralize with number
     * @param integer $count Number of items
     * @param string $string Input string
     * @return string
     */
    public static function pluralize_if($count, $string)
    {
      if ($count == 1)
        return "1 $string";
      else
        return $count . " " . self::pluralize($string);
    }
  }

  $codeBuilder = new CodeBuilder();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<link rel="stylesheet" type="text/css" href="/styles/main.css"/>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Model Code Builder -  ©2017 Adrian Meyer</title>
<style>
  body { 
	  margin: 0px 0px 16px 0px;
		font-family: Arial, Helvetica, sans-serif }
  .box{
		border: solid 1px #E7E7E7;
		margin: 16px 16px 0px 16px;
		padding-bottom: 12px;
		background-color: #F7F7F7;
	}
  .box a{
		margin-left: 8px;
		text-decoration:none;
		color: #C30;
	}
  .box pre{
		background-color: white;
		margin: 12px 12px 0px 12px;
		border: 1px solid #AAA;
		padding: 8px;
	}
  .box-header{
		margin: 0px 0px 8px 0px;
		border-bottom: solid 1px #E7E7E7;
	}
  .box-header h2{
		margin: 8px 8px 0px 8px;
	}
  .box-header div{
		margin: 2px 8px 8px 8px;
		color: #666;
		font-style:italic;
	}
</style>
</head>
<body>
<div class="box">
  <div class='box-header'>
    <h2>PHP Model Builder</h2>
    <div>Select table to build code.</div>
  </div>
	<?php
    //=======================================================================  
    // list available tables
    //=======================================================================  
		$tableIndex = array();
    $html = '';
    foreach( $codeBuilder->tables as $table ) {
      $html.= "<a href='?table=".
        urlencode($table['TABLE_NAME'])."'>".
        htmlentities( $table['TABLE_NAME'] )."</a><br>";
			$tableIndex[$table['TABLE_NAME']] = true;
    }
    echo $html;
  ?>
</div>
<div class="box">
  <div class='box-header'>
    <h2>PHP Model Code</h2>
    <div>Generated PHP model code.</div>
  </div>
  <pre id="code"><?php
      //=======================================================================  
      // generate code if table was selected
      //=======================================================================  
      $tableName = \Utils::getPostOrGetValue('table');
      
      if (isset( $tableIndex[ $tableName ])) {
        echo 
          "<button onclick='selectAll()'>Select PHP Code</button><br/><span id='php-code'>".
            highlight_string( $codeBuilder->buildPhpModel($tableName), true )."</span>";
      } else {
				if (trim($tableName) == '') {
					echo "Please select a table.";
				} else {
					echo 
						'Table <strong>'.htmlentities($tableName).'</strong> does not exist!';
				}
      }
  ?></pre>
</div>
<script>

  function selectText(containerid) {
    if (document.selection) {
      var range = document.body.createTextRange();
      range.moveToElementText(document.getElementById(containerid));
      range.select();
    } else if (window.getSelection) {
      var range = document.createRange();
      range.selectNode(document.getElementById(containerid));
      window.getSelection().addRange(range);
    }
  }
      
  function selectAll(){
    selectText('php-code');
  }
</script>
</body>
</html>