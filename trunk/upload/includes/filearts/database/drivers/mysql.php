<?php
/**
* k4 Bulletin Board, mysql.php
*
* Copyright (c) 2005, Geoffrey Goodman
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
* @author Geoffrey Goodman
* @author Peter Goodman
* @version $Id: mysql.php 147 2005-07-09 17:12:40Z Peter Goodman $
* @package k42
*/

if (!defined('FILEARTS'))
	return;

class MysqlResultIterator extends FADBResult {
	var $id;
	var $mode;
	var $row = -1;
	var $current;
	var $size;

	function __construct($id, $mode) {
		$this->id = $id;
		$this->mode = $mode;
		$this->size = mysql_num_rows($this->id);
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
			$this->current = mysql_fetch_array($this->id, $this->mode);
			$this->row++;

			$ret = $this->current();
		}

		return $ret;
	}
	
	function free() {
		return mysql_free_result($this->id);
	}

	function seek($offset) {
		return mysql_data_seek($this->id, $offset);
	}

	function numRows() {
		return $this->size;
	}

	function reset() {
		if ($this->row >= 0)
			$this->seek(0);

		$this->row = -1;

		return TRUE;
	}
}

class MysqlStatement extends FADBStatement {
	//Use the generic one
}

class MysqlConnection extends FADBConnection {
	var $link;
	var $valid = TRUE;

	function __construct() {
		if(!extension_loaded('mysql'))
			trigger_error("The MySQL extension is not loaded.", E_USER_ERRROR);

		define('DBA_NUM', MYSQL_NUM);
		define('DBA_ASSOC', MYSQL_ASSOC);
	}

	function affectedRows() {
		return mysql_affected_rows($this->link);
	}

	function connect($info) {
		$ret = TRUE;

		if (!isset($info['server']) || !isset($info['user']) || !isset($info['pass']) || !isset($info['database'])) {
			trigger_error("Missing required connection information.", E_USER_ERROR);
			$ret = FALSE;
		}
		
		if ($ret) {
			$link = @mysql_pconnect($info['server'], $info['user'], $info['pass']);
			
			if ($link == FALSE) {
				trigger_error("Unable to connect to the database", E_USER_ERROR);
				$ret = FALSE;
			} else if (!@mysql_select_db($info['database'], $link)) {
				trigger_error("Unable to select the database", E_USER_ERROR);
				$ret = FALSE;				
			}
			
			$this->link = $link;
		}

		return $ret;
	}

	function prepareStatement($sql) {
		$ret = $this->createStatement($sql, $this);
		return $ret;
	}

	function executeUpdate($stmt) {
		
		$result = @mysql_query($stmt, $this->link);

		if ($result == FALSE)
			trigger_error("Invalid query: ". mysql_error($this->link), E_USER_ERROR);
		
		/* Increment the number of queries */
		$this->num_queries++;

		return TRUE;
	}

	function executeQuery($stmt, $mode = DBA_ASSOC) {
		$result = @mysql_query($stmt, $this->link);

		if (!is_resource($result)) {
			if (mysql_errno() == 0)
				trigger_error("Invalid query: Called executeQuery on an update", E_USER_NOTICE);
				
			trigger_error("Invalid query: ". mysql_error($this->link), E_USER_ERROR);

			return FALSE;
		}
		
		/* Increment the number of queries */
		$this->num_queries++;

		$result = &new MysqlResultIterator($result, $mode);

		return $result;
	}
	
	function getNumQueries() {
		return $this->num_queries;
	}

	function getInsertId($table = FALSE, $column = FALSE) {
		/* Increment the number of queries */
		return mysql_insert_id($this->link);
	}

	function isValid() {
		return $this->valid;
	}

	function quote($value) {
		return mysql_escape_string($value);
	}

	function createTemporary($table, $original = FALSE) {

		if($original) {
			$tableinfo				= $this->executeQuery("DESCRIBE ". $original);

			if($tableinfo->numrows() > 0) {
				
				$origsql			= '';

				while($tableinfo->next()) {
					$col			= $tableinfo->current();
					$origsql		.= ','. $col['Field'] .' '. $col['Type'] .' '. (strtolower($col['Null']) == 'yes' ? '' : 'NOT NULL') .' DEFAULT \''. $col['Default'] .'\'';
				}
				$tableinfo->free();
				
				$sql				= 'CREATE TEMPORARY TABLE '. $table .' ('. substr($origsql, 1) .')';
				
				/* Create the temporary table */
				$this->executeUpdate($sql);
			}
		}
	}

	function alterTable($table, $stmt) {
		$this->num_queries++;
		$this->executeUpdate("ALTER TABLE $table $stmt");
	}

	function beginTransaction() {
		return TRUE;
	}

	function commitTransaction() {
		return TRUE;
	}

	function version() {
		return intval(str_replace('.', '', mysql_get_server_info($this->link)));
	}
}

?>