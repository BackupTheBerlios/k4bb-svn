<?php
/**
* k4 Bulletin Board, sqlite.php
*
* Copyright (c) 2005, Peter Goodman
*
* This library is free software; you can redistribute it and/orextension=php_gd2.dll
* modify it under the terms of the GNU Lesser General Public
* License as published by the Free Software Foundation; either
* version 2.1 of the License, or (at your option) any later version.
* 
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
* Lesser General Public License for more details.
* 
* Licensed under the LGPL license
* http://www.gnu.org/copyleft/lesser.html
*
* @author Peter Goodman
* @author Geoffrey Goodman
* @version $Id: sqlite.php 147 2005-07-09 17:12:40Z Peter Goodman $
* @package k42
*/

if (!defined('FILEARTS'))
	return;

class SQLiteResultIterator extends FADBResult {
	var $id;
	var $mode;
	var $row = -1;
	var $current;

	function __construct($id, $mode) {
		$this->id	= $id;
		$this->mode = $mode;
		$this->size = sqlite_num_rows($this->id);
	}

	function current() {
		$current = $this->current;
		return $current;
	}

	function hasNext() {
		return ($this->row + 1 < $this->size) ? TRUE : FALSE;
	}

	function hasPrev() {
		return ($this->row - 1 >= 0) ? TRUE : FALSE;
	}

	function key() {
		return $this->row;
	}


	function next() {
		$ret = FALSE;
		if ($this->hasNext()) {
			$this->current = sqlite_fetch_array($this->id, $this->mode);
			$this->row++;

			$ret = $this->current();
		}
		return $ret;
	}
	
	function numRows() {
		return $this->size;
	}

	function seek($offset) {
		return sqlite_seek($this->id, $offset);
	}

	function free() {
		return TRUE;
	}

	function reset() {
		if ($this->row >= 0)
			$this->seek(0);

		$this->row = -1;

		return TRUE;
	}
}

class SQLiteStatement extends FADBStatement {
	//Use the generic one
}

class SQLiteConnection extends FADBConnection {
	var $link;
	var $valid = TRUE;

	function __construct() {
		if(!extension_loaded('sqlite'))
			trigger_error("The SQLite extension is not loaded.", E_USER_ERRROR);

		define('DBA_NUM', SQLITE_NUM);
		define('DBA_ASSOC', SQLITE_ASSOC);
	}

	function affectedRows() {
		return sqlite_changes($this->link);
	}

	function connect($info) {
		if (!isset($info['database']) || !isset($info['directory']) || $info['directory'] == '') {
			$this->valid = FALSE;
			trigger_error("Missing required connection information.", E_USER_ERROR);

			return FALSE;
		}
		
		if(!function_exists('sqlite_open')) {
			$this->valid = FALSE;
			trigger_error("Please make sure that SQLite is properly installed.", E_USER_ERROR);

			return FALSE;
		}
		
		$link = @sqlite_open($info['directory'] .'/'. $info['database'], 0666);
	
		if (!is_resource($link)) {
			$this->valid = FALSE;
			trigger_error("Unable to connect to the database.", E_USER_ERROR);

			return FALSE;
		}

		$this->link = $link;

		return TRUE;
	}

	function prepareStatement($sql) {
		$ret = $this->createStatement($sql, $this);
		return $ret;
	}

	function executeUpdate($stmt) {
		
		$result = sqlite_query($stmt, $this->link);

		if ($result == FALSE) {
			trigger_error("Invalid query: ". E_USER_ERROR);

			return FALSE;
		}
		
		/* Increment the number of queries */
		$this->num_queries++;

		return TRUE;
	}

	function executeQuery($stmt, $mode = DBA_ASSOC) {

		$result = sqlite_query($stmt, $this->link);

		if (!is_resource($result)) {
			if (sqlite_last_error($this->link) == 0) {
				trigger_error("Invalid query: Called executeQuery on an update", E_USER_WARNING);

				return FALSE;
			}
				
			trigger_error("Invalid query: ". sqlite_error_string(sqlite_last_error($this->link)), E_USER_ERROR);

			return FALSE;
		}
		
		/* Increment the number of queries */
		$this->num_queries++;
		
		$result = &new SQLiteResultIterator($result, $mode);
		
		return $result;
	}
	
	function getNumQueries() {
		return $this->num_queries;
	}
	
	function getInsertId($table = FALSE, $column = FALSE) {
		return sqlite_last_insert_rowid($this->link);
	}

	function isValid() {
		return $this->valid;
	}

	function quote($value) {
		return sqlite_escape_string($value);
	}
	
	function createTemporary($table, $original = FALSE) {
		
		if($original) {
			$row					= $this->getRow("SELECT sql, name, type FROM sqlite_master WHERE tbl_name = '". $original ."' ORDER BY type DESC"); //table sql

			if(is_array($row) && !empty($row)) {
			
				$origsql			= trim(preg_replace("/[\s]+/", " ", str_replace(",", ", ", preg_replace("/[\(]/", "( ", $row['sql'], 1))));
				$origsql			= preg_replace("~, PRIMARY KEY\((.+?)\)~i", "", $origsql);
				$sql				= 'CREATE TEMPORARY '. substr(trim(preg_replace("'". $original ."'", $table, $origsql, 1)), 6);
				
				/* Create the temporary table */
				$this->executeUpdate($sql);
			}
		}
	}

	/* Modified and cleaned version of http://code.jenseng.com/db/ */
	function alterTable($table, $alterdefs) {
		
		$this->num_queries++;

		if($alterdefs != '') {
			
			$row							= $this->getRow("SELECT sql, name, type FROM sqlite_master WHERE tbl_name = '". $table ."' ORDER BY type DESC"); //table sql

			if(is_array($row) && !empty($row)) {
			
				$tmpname						= 't'. time();
				$origsql						= trim(preg_replace("/[\s]+/", " ", str_replace(",", ", ", preg_replace("/[\(]/", "( ", $row['sql'], 1))));
				$createtemptableSQL				= 'CREATE TEMPORARY '. substr(trim(preg_replace("'". $table ."'", $tmpname, $origsql, 1)), 6);
				//echo substr(trim(preg_replace("'". $table ."'", $tmpname, $origsql, 1)), 6); exit;
				$createindexsql					= array();
				$i								= 0;
				$defs							= preg_split("/[,]+/", $alterdefs, -1, PREG_SPLIT_NO_EMPTY);
				$prevword						= $table;
				
				/* Doesn't work with decimal() columns.. e.g. decimal(5,2) */
				$oldcols						= preg_split("/[,]+/", substr(trim($createtemptableSQL), strpos(trim($createtemptableSQL),'(')+1), -1, PREG_SPLIT_NO_EMPTY);

				$newcols						= array();

				for($i = 0; $i < count($oldcols); $i++ ) {
					$colparts						= preg_split("/[\s]+/", $oldcols[$i], -1, PREG_SPLIT_NO_EMPTY);
					$oldcols[$i]					= $colparts[0];
					$newcols[$colparts[0]]			= $colparts[0];
				}

				$newcolumns = '';
				$oldcolumns = '';

				reset($newcols);

				while(list($key, $val) = each($newcols)) {
					if(strtoupper($val) != 'PRIMARY' && strtoupper($key) != 'PRIMARY' && strtoupper($val) != 'UNIQUE' && strtoupper($key) != 'UNIQUE') {
						$newcolumns		.= ($newcolumns ? ', ' : '') . $val;
						$oldcolumns		.= ($oldcolumns ? ', ' : '') . $key;
					}
				}

				$copytotempsql						= 'INSERT INTO '. $tmpname .'('. $newcolumns .') SELECT '. $oldcolumns .' FROM '. $table;
				$dropoldsql							= 'DROP TABLE '. $table;
				$createtesttableSQL					= $createtemptableSQL;

				foreach($defs as $def) {
					$defparts						= preg_split("/[\s]+/", $def, -1, PREG_SPLIT_NO_EMPTY);
					$action							= strtolower($defparts[0]);

					switch($action) {
						case 'add': {
							
							if(sizeof($defparts) <= 2) {
								trigger_error('An error occured near "'. $defparts[0] . iif($defparts[1], ' '. $defparts[1], '').'": syntax error.', E_USER_ERROR);
								return FALSE;
							}
							
							$createtesttableSQL				= substr($createtesttableSQL,0,strlen($createtesttableSQL)-1).',';
							
							for($i = 1; $i < sizeof($defparts); $i++) {
								$createtesttableSQL			.= ' '. $defparts[$i];
							}
							
							$createtesttableSQL				.= ');';

							break;
						}
						case 'change': {

							if(count($defparts) <= 3) {
								trigger_error('An error occured near "'. $defparts[0] . iif($defparts[1], ' '. $defparts[1], '') . iif($defparts[2], ' '. $defparts[2], '') .'": syntax error.', E_USER_ERROR);
								return FALSE;
							}
							if($severpos = strpos($createtesttableSQL, ' '. $defparts[1] .' ')) {
								
								if($newcols[$defparts[1]] != $defparts[1]) {
									trigger_error('Unknown column "'. $defparts[1] .'" in "'. $table .'"', E_USER_ERROR);
									return FALSE;
								}
								$newcols[$defparts[1]] = $defparts[2];
								$nextcommapos = strpos($createtesttableSQL, ',', $severpos);
								$insertval = '';
								for($i = 2; $i < count($defparts); $i++) {
									$insertval .= ' '. $defparts[$i];
								}

								if($nextcommapos) {
									$createtesttableSQL = substr($createtesttableSQL, 0, $severpos) . $insertval . substr($createtesttableSQL, $nextcommapos);
								} else {
									$createtesttableSQL = substr($createtesttableSQL, 0, $severpos - iif(strpos($createtesttableSQL,','), 0, 1)) . $insertval .')';
								}
							
							} else {
								trigger_error('Unknown column "'. $defparts[1] .'" in "'. $table .'"', E_USER_ERROR);
								return FALSE;
							}
							break;
						}

						case 'drop': {
							if(count($defparts) < 2){
								trigger_error('An error occured near "'. $defparts[0] . iif($defparts[1], ' '. $defparts[1], '') .'": syntax error.', E_USER_ERROR);
								return FALSE;
							}
							if($severpos = strpos($createtesttableSQL,' '. $defparts[1].' ')) {
								
								$nextcommapos			= strpos($createtesttableSQL, ',', $severpos);
								if($nextcommapos) {
									$createtesttableSQL = substr($createtesttableSQL,0,$severpos).substr($createtesttableSQL,$nextcommapos + 1);
								} else {
									$createtesttableSQL = substr($createtesttableSQL,0,$severpos-(strpos($createtesttableSQL,',')?0:1) - 1).')';
								}
								unset($newcols[$defparts[1]]);
							} else{
								trigger_error('Unknown column "'. $defparts[1] .'" in "'. $table .'".', E_USER_ERROR);
								return FALSE;
							}
							break;
						}
						default: {
							trigger_error('An error occured near "'. $prevword .'": syntax error.', E_USER_ERROR);
							return FALSE;
						}
					}

					$prevword = $defparts[count($defparts)-1];
				}
				
				preg_match("~, PRIMARY KEY\((.+?)\)~i", $createtesttableSQL, $matches);

				if($matches > 0) {
					$createtesttableSQL = preg_replace("~, PRIMARY KEY\((.+?)\)~i", "", $createtesttableSQL);
					$createtesttableSQL = preg_replace("~\);$~", ", PRIMARY KEY(". $matches[1] .") );", $createtesttableSQL);
				}
				
				/**
				 * this block of code generates a test table simply to verify that the columns 
				 * specifed are valid in an sql statement this ensures that no reserved words 
				 * are used as columns, for example.
				 */
				
				if(!$this->executeUpdate($createtesttableSQL)){
					trigger_error('The test table could not be created.<br /><br />'. $createtesttable, E_USER_ERROR);
					return FALSE;
				}

				$this->executeUpdate('DROP TABLE '. $tmpname);
				/* end block */

				$createnewtableSQL	= 'CREATE '. substr(trim(preg_replace("'". $tmpname ."'", $table, $createtesttableSQL, 1)), 17);
				
				$newcolumns			= '';
				$oldcolumns			= '';

				reset($newcols);

				while(list($key, $val) = each($newcols)) {
					if(strtoupper($val) != 'PRIMARY' && strtoupper($key) != 'PRIMARY' && strtoupper($val) != 'UNIQUE' && strtoupper($key) != 'UNIQUE') {
						$newcolumns		.= ($newcolumns ? ', ' : '') . $val;
						$oldcolumns		.= ($oldcolumns ? ', ' : '') . $key;
					}
				}

				$copytonewsql		= 'INSERT INTO '. $table .'('. $newcolumns .') SELECT '. $oldcolumns .' FROM '. $tmpname;
				
				/**
				 * Use a transaction here so that if one query fails, they all fail
				 */
				
				/* Begin the transaction */
				$this->beginTransaction();
				
				/* Create our temporary table */
				$this->executeUpdate($createtesttableSQL); //createtemptableSQL

				/* Copy the data to the temporary table */
				$this->executeUpdate($copytotempsql);

				/* Drop the table that we are modifying */
				$this->executeUpdate($dropoldsql);
				
				/* Recreate that original table with the column added/changed/droped */
				$this->executeUpdate($createnewtableSQL);

				/* Copy the data from our temporary table to our new table */
				$this->executeUpdate($copytonewsql);

				/* Drop our temporary table */
				$this->executeUpdate('DROP TABLE '. $tmpname);
				
				/* Finish the transaction */
				$this->commitTransaction();

			} else {
				trigger_error('Non-existant table: '. $table, E_USER_ERROR);
				return FALSE;
			}

			return true;
		}
	}
	function beginTransaction() {
		$this->executeUpdate("BEGIN TRANSACTION");
	}
	function commitTransaction() {
		$this->executeUpdate("COMMIT");
	}
	function version() {
		return intval(str_replace('.', '', sqlite_libversion() ));
	}
}

?>