<?php
if(!current_user_can('manage_options')) {
	die('Access Denied');
}

/*
 * SQL install script
 */
global $install_sql;
$install_sql = <<<EOS_INSTALL
SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';


-- -----------------------------------------------------
-- Table `kkm_tag_categories`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `kkm_tag_categories` (
  `tcid` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Tag category ID' ,
  `name` VARCHAR(45) NOT NULL ,
  `color` VARCHAR(6) NOT NULL COMMENT 'RGB Hex color code for the category.' ,
  `mandatory_doc` TINYINT NOT NULL DEFAULT 2 COMMENT 'Flag for storing mandatory status when assigned to documents: 0-cannot be assigned, 1-Optional and hidden, 2-Optional and displayed, 3-Mandatory (and displayed)' ,
  `mandatory_comp` TINYINT NOT NULL DEFAULT 2 COMMENT 'Flag for storing mandatory status when assigned to composition: 0-cannot be assigned, 1-Optional and hidden, 2-Optional and displayed, 3-Mandatory (and displayed)' ,
  PRIMARY KEY (`tcid`) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `kkm_tags`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `kkm_tags` (
  `tid` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Tag ID' ,
  `tcid` INT NOT NULL COMMENT 'Tag category ID, foreign key: references tcid column in kkm_tag_categories table.' ,
  `parent` INT NULL COMMENT 'Parent tag ID. NULL if it\'s a top level tag.' ,
  `name` VARCHAR(45) NOT NULL ,
  `alias` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Alias tags can not have any descendants.' ,
  PRIMARY KEY (`tid`) ,
  INDEX `category_idx` (`tcid` ASC) ,
  UNIQUE INDEX `name_idx` (`name` ASC, `tcid` ASC) ,
  INDEX `parent_idx` (`parent` ASC) ,
  CONSTRAINT `tag_category_fk`
    FOREIGN KEY (`tcid` )
    REFERENCES `kkm_tag_categories` (`tcid` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `tag_parent_fk`
    FOREIGN KEY (`parent` )
    REFERENCES `kkm_tags` (`tid` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `kkm_tag_relations`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `kkm_tag_relations` (
  `trid` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Tag relation ID' ,
  `ancestor` INT UNSIGNED NOT NULL COMMENT 'TID of the parent tag' ,
  `descendant` INT UNSIGNED NOT NULL COMMENT 'TID of the child tag' ,
  PRIMARY KEY (`trid`) ,
  UNIQUE INDEX `tids` (`ancestor` ASC, `descendant` ASC) ,
  INDEX `ancestor_idx` (`ancestor` ASC) ,
  INDEX `descendant_idx` (`descendant` ASC) ,
  CONSTRAINT `tagrel_ancestor_fk`
    FOREIGN KEY (`ancestor` )
    REFERENCES `kkm_tags` (`tid` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `tagrel_descendant_fk`
    FOREIGN KEY (`descendant` )
    REFERENCES `kkm_tags` (`tid` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `kkm_compositions`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `kkm_compositions` (
  `compid` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `title` VARCHAR(255) NOT NULL ,
  PRIMARY KEY (`compid`) ,
  UNIQUE INDEX `title` (`title` ASC) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `kkm_comp_tags`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `kkm_comp_tags` (
  `ctagid` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `compid` INT UNSIGNED NOT NULL ,
  `tid` INT UNSIGNED NOT NULL ,
  PRIMARY KEY (`ctagid`) ,
  UNIQUE INDEX `ids` (`compid` ASC, `tid` ASC) ,
  INDEX `composition_idx` (`compid` ASC) ,
  INDEX `tag_idx` (`tid` ASC) ,
  CONSTRAINT `ctags_comp_fk`
    FOREIGN KEY (`compid` )
    REFERENCES `kkm_compositions` (`compid` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `ctags_tag_sk`
    FOREIGN KEY (`tid` )
    REFERENCES `kkm_tags` (`tid` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `kkm_documents`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `kkm_documents` (
  `docid` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `uploader` BIGINT NOT NULL COMMENT 'Contains WordPress user IDs.' ,
  `date` DATETIME NOT NULL COMMENT 'Date of upload.' ,
  `compid` INT UNSIGNED NOT NULL ,
  `source` INT UNSIGNED NULL COMMENT 'The parent document\'s docid. If the system generates a new document based on an uploaded file (eg. PDF from MIDI), it sets this field. Users may also upload variations (eg. image cropped to contain only the part of a specific instrument).' ,
  `generated` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0 - User uploaded content; 1 - Generated content' ,
  `oldver` INT UNSIGNED NULL COMMENT 'DocID of the previous version of the document.' ,
  `firstver` INT UNSIGNED NULL ,
  `activever` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Flag for marking the active version of the same document. 0 - Inactive (There\'s an other (complete=1) version of the document); 1 - Active (This will be listed by default)' ,
  `complete` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'This flag is set to 1 at the end of the multistage uploading process.' ,
  `deleted` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Flag for marking document as deleted. File is already removed from the filesystem if this is set to 1.' ,
  `filename` VARCHAR(100) NOT NULL COMMENT 'Sanitized filename without the ID.' ,
  `filesize` INT UNSIGNED NOT NULL ,
  `origin` VARCHAR(255) NULL COMMENT 'URL, book title or some other information about the origins of the document.' ,
  PRIMARY KEY (`docid`) ,
  INDEX `composition_idx` (`compid` ASC) ,
  INDEX `source_idx` (`source` ASC) ,
  INDEX `previousver_idx` (`oldver` ASC) ,
  INDEX `firstver_idx` (`firstver` ASC) ,
  CONSTRAINT `doc_composition_fk`
    FOREIGN KEY (`compid` )
    REFERENCES `kkm_compositions` (`compid` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `doc_source_fk`
    FOREIGN KEY (`source` )
    REFERENCES `kkm_documents` (`docid` )
    ON DELETE RESTRICT
    ON UPDATE NO ACTION,
  CONSTRAINT `doc_previousver_fk`
    FOREIGN KEY (`oldver` )
    REFERENCES `kkm_documents` (`docid` )
    ON DELETE RESTRICT
    ON UPDATE NO ACTION,
  CONSTRAINT `doc_firstver_fk`
    FOREIGN KEY (`firstver` )
    REFERENCES `kkm_documents` (`docid` )
    ON DELETE RESTRICT
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `kkm_doc_tags`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `kkm_doc_tags` (
  `dtagid` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `docid` INT UNSIGNED NOT NULL ,
  `tid` INT UNSIGNED NOT NULL ,
  PRIMARY KEY (`dtagid`) ,
  UNIQUE INDEX `ids` (`docid` ASC, `tid` ASC) ,
  INDEX `documentidx` (`docid` ASC) ,
  INDEX `tagidx` (`tid` ASC) ,
  CONSTRAINT `doctag_document_fk`
    FOREIGN KEY (`docid` )
    REFERENCES `kkm_documents` (`docid` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `doctag_tag_fk`
    FOREIGN KEY (`tid` )
    REFERENCES `kkm_tags` (`tid` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `kkm_collections`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `kkm_collections` (
  `collid` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `user` BIGINT NOT NULL COMMENT 'Contains WordPress user IDs.' ,
  `name` VARCHAR(255) NOT NULL ,
  `created` DATETIME NOT NULL COMMENT 'Creation date.' ,
  `modified` DATETIME NOT NULL COMMENT 'Modification date.' ,
  PRIMARY KEY (`collid`) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `kkm_collection_links`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `kkm_collection_links` (
  `colllinkid` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `collid` INT UNSIGNED NOT NULL ,
  `compid` INT UNSIGNED NOT NULL ,
  PRIMARY KEY (`colllinkid`) ,
  INDEX `collection_idx` (`collid` ASC) ,
  INDEX `composition_idx` (`compid` ASC) ,
  UNIQUE INDEX `ids` (`collid` ASC, `compid` ASC) ,
  CONSTRAINT `coll_collection_fk`
    FOREIGN KEY (`collid` )
    REFERENCES `kkm_collections` (`collid` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `coll_composition_fk`
    FOREIGN KEY (`compid` )
    REFERENCES `kkm_compositions` (`compid` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `kkm_reqoffs`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `kkm_reqoffs` (
  `roid` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `type` TINYINT(1) NOT NULL COMMENT '0-Request; 1-Offer' ,
  `compid` INT UNSIGNED NULL COMMENT 'Composition ID if avaliable.' ,
  `user` BIGINT NOT NULL COMMENT 'WP User ID of the user who made the offer/request.' ,
  `description` TEXT NOT NULL ,
  PRIMARY KEY (`roid`) ,
  INDEX `compid_idx` (`compid` ASC) ,
  CONSTRAINT `ro_compid_fk`
    FOREIGN KEY (`compid` )
    REFERENCES `kkm_compositions` (`compid` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `kkm_reqoff_links`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `kkm_reqoff_links` (
  `rolid` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `request` INT UNSIGNED NOT NULL ,
  `offer` INT UNSIGNED NOT NULL ,
  PRIMARY KEY (`rolid`) ,
  UNIQUE INDEX `ids` (`request` ASC, `offer` ASC) ,
  INDEX `request_idx` (`request` ASC) ,
  INDEX `offer_idx` (`offer` ASC) ,
  CONSTRAINT `rol_request_fk`
    FOREIGN KEY (`request` )
    REFERENCES `kkm_reqoffs` (`roid` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `rol_offer_fk`
    FOREIGN KEY (`offer` )
    REFERENCES `kkm_reqoffs` (`roid` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `kkm_tickets`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `kkm_tickets` (
  `ticid` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `docid` INT UNSIGNED NOT NULL COMMENT 'Document the request is made for.' ,
  `status` TINYINT(1) UNSIGNED NOT NULL COMMENT 'Status flag: 0-Open; 1-In progress; 2-Closed (won\'t fix); 3-Closed (fixed)' ,
  `opener` BIGINT NULL COMMENT 'WP User ID for the user who opened the ticket.' ,
  `worker` BIGINT NULL COMMENT 'WP User ID for the user who works on the ticket, or the user who has closed it.' ,
  PRIMARY KEY (`ticid`) ,
  INDEX `document_idx` (`docid` ASC) ,
  CONSTRAINT `tic_document_fk`
    FOREIGN KEY (`docid` )
    REFERENCES `kkm_documents` (`docid` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `kkm_converters`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `kkm_converters` (
  `convid` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `input` INT UNSIGNED NOT NULL COMMENT 'Tag ID for the input documents.' ,
  `enabled` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '0-Disabled; 1-Enabled' ,
  `module` VARCHAR(255) NOT NULL COMMENT 'Name of the converter module that is called for the conversion.' ,
  `options` TEXT NOT NULL COMMENT 'Serialized options array.' ,
  PRIMARY KEY (`convid`) ,
  INDEX `input_idx` (`input` ASC) ,
  CONSTRAINT `conv_input_fk`
    FOREIGN KEY (`input` )
    REFERENCES `kkm_tags` (`tid` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `kkm_conversion_logs`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `kkm_conversion_logs` (
  `logid` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `source` INT UNSIGNED NOT NULL ,
  `target` INT UNSIGNED NOT NULL ,
  `date` DATE NOT NULL ,
  `user` BIGINT NULL COMMENT 'User ID if the conversion was started by a user, or null if it was started by a system trigger.' ,
  `options` TEXT NULL ,
  `messages` TEXT NOT NULL ,
  `maxmsglvl` TINYINT UNSIGNED NOT NULL COMMENT 'Maximum message level present in the messages array: 0-nothing, 1-note, 2-warning, 3-error' ,
  PRIMARY KEY (`logid`) ,
  INDEX `log_source_idx` (`source` ASC) ,
  INDEX `log_target_idx` (`target` ASC) ,
  CONSTRAINT `log_source_fk`
    FOREIGN KEY (`source` )
    REFERENCES `kkm_documents` (`docid` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `log_target_fk`
    FOREIGN KEY (`target` )
    REFERENCES `kkm_documents` (`docid` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;



SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;




insert into `kkm_tag_categories` (`name`, `color`, `mandatory_doc`, `mandatory_comp`) values
	('Format', '683828', 3, 0),
	('Composer', '388832', 0, 2),
	('Performer', '550011', 0, 2),
	('Quality', 'c262d3', 1, 0);
alter table `kkm_tag_categories` auto_increment=100;



/* DEBUG */
	insert into `kkm_tags` (`name`, `tcid`, `parent`) values
		('Lyrics', 1, null),
		('Lyrics with Chords', 1, 1),
		('Guitar Tab (ASCII tab)', 1, null),
		('LilyPond', 1, null),
		('High quality', 4, null),
		('Low quality', 4, null);
	
	insert into `kkm_tag_relations` (`ancestor`, `descendant`) values
		(1, 2);
	
	insert into `kkm_compositions` (`title`) values
		('DUMMY COMPOSITION');

EOS_INSTALL;



/*
 * Remove comments from the script
 * and replace whitespaces with a single space character.
 * Semicolons inside column comments are escaped too.
 */
function kkm_install_sql_fix_callback($matches) {
	return preg_replace('/(?<!\\\\);/', '\\;', $matches[0]);
}
$install_sql = preg_replace_callback(
	'/comment\s*\'((?:[^\']|(?<=\\\\)\')+)\'/im',
	'kkm_install_sql_fix_callback',
	$install_sql
);
$install_sql = preg_replace('%/\*.*\*/%', '', $install_sql);
$install_sql = preg_replace('/^--.*$/m', '', $install_sql);
$install_sql = preg_replace('/\s+/', ' ', $install_sql);


/**
 * Runs the SQL install script and additional commands
 * to initialize the forum.
 */
function kkm_install() {
	global $wpdb, $install_sql;
	
	//Remove database tables if present
	kkm_uninstall(true);
	
	$wpdb->flush();
	$wpdb->query('begin'); //Start transaction
	
	/*
	 * Run install script command by command
	 */
	$error_occured = false;
	$sql_commands = preg_split('/(?<!\\\\);/', $install_sql);
	foreach ($sql_commands as $command) {
		$command = trim($command);
		if (!empty($command)) {
			$wpdb->flush();
			$wpdb->query($command);
			if (!empty($wpdb->last_error)) {
				echo($command."\n\n");
				echo($wpdb->last_error);
				$error_occured = true;
				break;
			}
		}
	}
	
	//Add format tags supported by the modules
	kkm_install_format_tags();
	
	if ($error_occured) {
		$wpdb->query('rollback'); //Roll back changes
		die(__('Cannot create database tables.','kkm'));
	} else {
		$wpdb->query('commit'); //Commit
	}
	
	/*
	 * Create dir struct for user data
	 */
	$dirs = array(
		'kkm_dir' =>		WP_CONTENT_DIR.'/kkm',
		'temp_dir' =>		WP_CONTENT_DIR.'/kkm/temp',
		'doc_dir' =>		WP_CONTENT_DIR.'/kkm/docs'
	);
	$stat = @stat(WP_CONTENT_DIR);
	$mode = $stat['mode'];// & 0000666;
	foreach ($dirs as $dir) {
		if (!is_dir($dir)) {
			mkdir($dir);
		}
		@chmod($dir, $mode);
	}
	
	/*
	 * Write .htaccess files
	 */
	$htaccess_content = "deny from all";
	file_put_contents($dirs['kkm_dir'].'/.htaccess', $htaccess_content);
}

/**
 * Loads the modules and inserts the supported format names
 * into the database as tags under the Format category.
 */
function kkm_install_format_tags() {
	global $wpdb;
	$tag_names = array();
	
	/*
	 * Load accepted formats of the parser classes.
	 */
	$parsers = kkm_modules::get_parsers();
	foreach ($parsers as $parser) {
		$accepted_formats = call_user_func(array($parser, 'get_accepted_formats'));
		$tag_names = array_merge($tag_names, $accepted_formats);
	}
	
	/*
	 * Load conversion format pairs of the converter classes.
	*/
	$converters = kkm_modules::get_converters();
	foreach ($converters as $converter) {
		$conversion_formats = call_user_func(array($converter, 'get_conversion_formats'));
		foreach ($conversion_formats as $format_pair) {
			$tag_names = array_merge($tag_names, $format_pair);
		}
	}
	
	/*
	 * Load accepted formats of the diff classes.
	 */
	$differs = kkm_modules::get_differs();
	foreach ($differs as $differ) {
		$accepted_formats = call_user_func(array($differ, 'get_accepted_formats'));
		$tag_names = array_merge($tag_names, $accepted_formats);
	}

	//Filter out duplicates
	$tag_names = array_unique($tag_names);
	
	/*
	 * Build and run SQL
	 */
	$rows = array();
	foreach ($tag_names as $name) {
		$rows[] = '(\''.$wpdb->escape($name).'\', '.kkm_tags::TYPE_FORMAT.', null)';
	}
	$wpdb->query(
		'insert into `kkm_tags` (`name`, `tcid`, `parent`) values '.implode(',', $rows).' on duplicate key update `tid`=`tid`;'
	);
}

/**
 * Parses the SQL script and removes every table created by it.
 * @param boolean $sql_only If set to true, only the database tables and views will be dropped.
 */
function kkm_uninstall($sql_only = false) {
	global $wpdb, $install_sql;
	$tables = array();
	$views = array();
	
	//Tables
	preg_match_all('/create +table +(?:if not exists +)?`(.*?)`/i', $install_sql, $preg_res, PREG_SET_ORDER);
	foreach ($preg_res as $res) {
		$tables[] = $res[1];
	}
	$tables = array_reverse($tables); //Tables should be dropped in reverse order due to foreign key constraints
	
	//Views
	preg_match_all('/create +(?:or replace +)?view +`(.*?)`/i', $install_sql, $preg_res, PREG_SET_ORDER);
	foreach ($preg_res as $res) {
		$views[] = $res[1];
	}
	
	if (!empty($tables)) {
		$wpdb->query('drop table if exists `'.implode('`,`', $tables).'`;');
	}
	if (!empty($views)) {
		$wpdb->query('drop view if exists `'.implode('`,`', $views).'`;');
	}
	
	//Delete files from the filesystem
	if (!$sql_only) {
		//TODO
	}
}
?>