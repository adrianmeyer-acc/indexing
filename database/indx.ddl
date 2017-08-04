/*================================================================================*/
/* DDL SCRIPT                                                                     */
/*================================================================================*/
/*  Title    : ServiceChampion - Database Model                                   */
/*  FileName : indx.ecm                                                           */
/*  Platform : MySQL 5.6                                                          */
/*  Version  : 0.2.0                                                              */
/*  Date     : Donnerstag, 15. Juni 2017                                          */
/*================================================================================*/
/*================================================================================*/
/* MODEL SCRIPT BEGIN                                                             */
/*================================================================================*/

SET default_storage_engine=InnoDB;

/*================================================================================*/
/* CREATE TABLES                                                                  */
/*================================================================================*/

CREATE TABLE `indx_AbstractConceptLookup` (
  `concept_id` INTEGER(0) NOT NULL COMMENT 'Physical index concept identifier',
  `label` VARCHAR(100) COLLATE utf8_bin NOT NULL COMMENT 'Name of the concept',
  CONSTRAINT `PK_indx_AbstractConceptLookup` PRIMARY KEY (`concept_id`)
)
COMMENT 'Lists all possible index concepts, e.g. Customer, Issue';
INSERT INTO `indx_AbstractConceptLookup` (`concept_id`,`label`) VALUES
( 1, 'CONTACT' ),
( 2, 'TICKET' ),
( 3, 'MESSAGE' ),
( 4, 'OBJECT' ),
( 5, 'WIKI' );

CREATE TABLE `indx_AbstractStatusLookup` (
  `status_id` INTEGER(0) NOT NULL COMMENT 'Physical abstract status identifier',
  `label` VARCHAR(100) COLLATE utf8_bin NOT NULL COMMENT 'Name of the status',
  CONSTRAINT `PK_indx_AbstractStatusLookup` PRIMARY KEY (`status_id`)
)
COMMENT 'Lists all possible index abstract statuses';
INSERT INTO `indx_AbstractStatusLookup` (`status_id`,`label`) VALUES
( 1, 'OK' ),
( 2, 'STALE' ),
( 3, 'REBUILD' );

CREATE TABLE `indx_Abstract` (
  `abstract_id` INTEGER(0) AUTO_INCREMENT NOT NULL COMMENT 'Physical index abstract identifier',
  `abstract` LONGTEXT COMMENT 'The actual abstract in HTML markup',
  `identifier` VARCHAR(100) COLLATE utf8_bin NOT NULL COMMENT 'Identifier of the concept entity (customer_id, ticket_id, message_id)',
  `url` VARCHAR(2048) COMMENT 'The URL of the abstract',
  `icon` VARCHAR(2048) COMMENT 'The icon of the abstract',
  `sort_value` VARCHAR(2048) COMMENT 'The value the abstracts within a concept are sorted by',
  `concept_id` INTEGER(0) NOT NULL COMMENT 'Identifies the concept',
  `status_id` INTEGER(0) NOT NULL COMMENT 'Identifies the status',
  CONSTRAINT `PK_indx_Abstract` PRIMARY KEY (`abstract_id`)
)
COMMENT 'Lists indexing abstracts.';

CREATE TABLE `indx_FilterGroup` (
  `filtergroup_id` INTEGER(0) AUTO_INCREMENT NOT NULL COMMENT 'Physical index filter identifier',
  `groupname` VARCHAR(100) COLLATE utf8_bin NOT NULL COMMENT 'Identifies the filter group (state, gender,country,category)',
  CONSTRAINT `PK_indx_FilterGroup` PRIMARY KEY (`filtergroup_id`)
)
COMMENT 'Lists indexing abstracts.';

CREATE TABLE `indx_FilterValue` (
  `filtervalue_id` INTEGER(0) AUTO_INCREMENT NOT NULL COMMENT 'Physical index filter identifier',
  `value` VARCHAR(100) COLLATE utf8_bin NOT NULL COMMENT 'Identifies the filter value (for state=[Bern|Zurich])',
  `sortvalue` VARCHAR(100) COLLATE utf8_bin NOT NULL COMMENT 'Value used for sorting within filter group',
  `filtergroup_id` INTEGER(0) NOT NULL COMMENT 'Identifies the group the value belongs to',
  CONSTRAINT `PK_indx_FilterValue` PRIMARY KEY (`filtervalue_id`)
)
COMMENT 'Lists indexing abstracts.';

CREATE TABLE `indx_AbstractFilterValue` (
  `abstract_id` INTEGER(0) NOT NULL COMMENT 'Identifies the abstract',
  `filtervalue_id` INTEGER(0) NOT NULL COMMENT 'Identifies the filter value',
  CONSTRAINT `PK_indx_AbstractFilterValue` PRIMARY KEY (`abstract_id`, `filtervalue_id`)
)
COMMENT 'Lists indexing abstracts.';

CREATE TABLE `indx_Term` (
  `term_id` INTEGER(0) AUTO_INCREMENT NOT NULL COMMENT 'Physical index abstract identifier',
  `term` VARCHAR(100) COLLATE utf8_bin NOT NULL COMMENT 'The actual term in upper case',
  `is_full` BOOL COMMENT 'Indicates if the term is a full term',
  `php_abstracts` LONGTEXT COMMENT 'Lists the matched abstracts in a php array',
  CONSTRAINT `PK_indx_Term` PRIMARY KEY (`term_id`)
)
COMMENT 'Lists all possible terms.';

CREATE TABLE `indx_MatchOptionLookup` (
  `matchoption_id` INTEGER(0) NOT NULL COMMENT 'Physical match option identifier',
  `label` VARCHAR(100) COLLATE utf8_bin NOT NULL COMMENT 'Name of the option',
  CONSTRAINT `PK_indx_MatchOptionLookup` PRIMARY KEY (`matchoption_id`)
)
COMMENT 'Lists all possible match option the term has to match in the segment. e.g. NONE, STARTS_WIDTH, ENDS_WIDTH, FULL';
INSERT INTO `indx_MatchOptionLookup` (`matchoption_id`,`label`) VALUES
( 9, 'NONE' ),
( 1, 'FULL' ),
( 2, 'SYNONYM' ),
( 3, 'STARTS_WIDTH' ),
( 4, 'ENDS_WIDTH' ),
( 5, 'CONTAINS' );

CREATE TABLE `indx_AbstractTerm` (
  `abstract_id` INTEGER(0) NOT NULL COMMENT 'Identifies the Abstract',
  `term_id` INTEGER(0) NOT NULL COMMENT 'Identifies the term',
  `matchoption_id` INTEGER(0) NOT NULL COMMENT 'Identifies the match option',
  CONSTRAINT `PK_indx_AbstractTerm` PRIMARY KEY (`abstract_id`, `term_id`)
);

CREATE TABLE `indx_TermFull` (
  `from_term_id` INTEGER(0) NOT NULL COMMENT 'Identifies the partial term',
  `to_term_id` INTEGER(0) NOT NULL COMMENT 'Identifies the full term',
  CONSTRAINT `PK_indx_TermFull` PRIMARY KEY (`from_term_id`, `to_term_id`)
)
COMMENT 'Lists the relationship between partial and complete matching terms';

CREATE TABLE `indx_TermSynonym` (
  `from_term_id` INTEGER(0) NOT NULL COMMENT 'Identifies the sysnonym',
  `to_term_id` INTEGER(0) NOT NULL COMMENT 'Identifies the full term',
  CONSTRAINT `PK_indx_TermSynonym` PRIMARY KEY (`from_term_id`, `to_term_id`)
)
COMMENT 'Lists the relationship between synonyms and terms';

/*================================================================================*/
/* CREATE INDEXES                                                                 */
/*================================================================================*/

CREATE UNIQUE INDEX `U01_indx_Abstract` ON `indx_Abstract` (`concept_id`, `identifier`);

CREATE UNIQUE INDEX `U01_indx_FilterGroup` ON `indx_FilterGroup` (`groupname`);

CREATE UNIQUE INDEX `U01_indx_FilterValue` ON `indx_FilterValue` (`filtergroup_id`, `value`);

CREATE UNIQUE INDEX `U01_indx_Term` ON `indx_Term` (`term`);

CREATE INDEX `I01_indx_AbstractTerm` ON `indx_AbstractTerm` (`term_id`);

CREATE INDEX `I02_indx_AbstractTerm` ON `indx_AbstractTerm` (`term_id`, `matchoption_id`);

CREATE INDEX `I03_indx_AbstractTerm` ON `indx_AbstractTerm` (`abstract_id`, `matchoption_id`);

CREATE INDEX `I01_indx_TermFull` ON `indx_TermFull` (`from_term_id`);

/*================================================================================*/
/* CREATE FOREIGN KEYS                                                            */
/*================================================================================*/

ALTER TABLE `indx_Abstract`
  ADD CONSTRAINT `FK_indx_Abstract_indx_AbstractConceptLookup`
  FOREIGN KEY (`concept_id`) REFERENCES `indx_AbstractConceptLookup` (`concept_id`);

ALTER TABLE `indx_Abstract`
  ADD CONSTRAINT `FK_indx_Abstract_indx_AbstractStatusLookup`
  FOREIGN KEY (`status_id`) REFERENCES `indx_AbstractStatusLookup` (`status_id`);

ALTER TABLE `indx_FilterValue`
  ADD CONSTRAINT `FK_indx_FilterValue_indx_FilterGroup`
  FOREIGN KEY (`filtergroup_id`) REFERENCES `indx_FilterGroup` (`filtergroup_id`);

ALTER TABLE `indx_AbstractFilterValue`
  ADD CONSTRAINT `FK_indx_Filter_indx_Abstract`
  FOREIGN KEY (`abstract_id`) REFERENCES `indx_Abstract` (`abstract_id`);

ALTER TABLE `indx_AbstractFilterValue`
  ADD CONSTRAINT `FK_indx_Filter_indx_FilterValue`
  FOREIGN KEY (`filtervalue_id`) REFERENCES `indx_FilterValue` (`filtervalue_id`);

ALTER TABLE `indx_AbstractTerm`
  ADD CONSTRAINT `FK_indx_Term_Abstract`
  FOREIGN KEY (`term_id`) REFERENCES `indx_Term` (`term_id`);

ALTER TABLE `indx_AbstractTerm`
  ADD CONSTRAINT `FK_indx_AbstractTerm_MatchOptionLookup`
  FOREIGN KEY (`matchoption_id`) REFERENCES `indx_MatchOptionLookup` (`matchoption_id`);

ALTER TABLE `indx_AbstractTerm`
  ADD CONSTRAINT `FK_indx_Abstract_Term`
  FOREIGN KEY (`abstract_id`) REFERENCES `indx_Abstract` (`abstract_id`);

ALTER TABLE `indx_TermFull`
  ADD CONSTRAINT `FK_indx_TermFull_indx_Term`
  FOREIGN KEY (`to_term_id`) REFERENCES `indx_Term` (`term_id`);

ALTER TABLE `indx_TermFull`
  ADD CONSTRAINT `FK_indx_TermFull_indx_Term2`
  FOREIGN KEY (`from_term_id`) REFERENCES `indx_Term` (`term_id`);

ALTER TABLE `indx_TermSynonym`
  ADD CONSTRAINT `FK_indx_Synonym_Term_from`
  FOREIGN KEY (`from_term_id`) REFERENCES `indx_Term` (`term_id`);

ALTER TABLE `indx_TermSynonym`
  ADD CONSTRAINT `FK_indx_Synonym_Term_to`
  FOREIGN KEY (`to_term_id`) REFERENCES `indx_Term` (`term_id`);

