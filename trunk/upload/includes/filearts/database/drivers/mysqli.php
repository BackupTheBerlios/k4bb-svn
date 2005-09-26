<?php
/**
* k4 Bulletin Board, mysqli.php
*
* Copyright (c) 2005, Geoffrey Goodman
*
* Permission is hereby granted, free of charge, to any person obtaining
* a copy of this software and associated documentation files (the
* "Software"), to deal in the Software without restriction, including
* without limitation the rights to use, copy, modify, merge, publish,
* distribute, sublicense, and/or sell copies of the Software, and to
* permit persons to whom the Software is furnished to do so, subject to
* the following conditions:
*
* The above copyright notice and this permission notice shall be
* included in all copies or substantial portions of the Software.
*
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
* EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
* MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
* NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS
* BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
* ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
* CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
* SOFTWARE.
*
* @author Geoffrey Goodman
* @author Peter Goodman
* @version $Id: mysqli.php 147 2005-07-09 17:12:40Z Peter Goodman $
* @package k42
*/

if (!defined('FILEARTS'))
	return;

class MysqliResultIterator extends FADBResult {
	var $id;
	var $mode;
	var $row = -1;
	var $current;
	var $size;

	function __construct($id, $mode) {
		$this->id = $id;
		$this->mode = $mode;
		$this->size = mysqli_num_rows($this->id);
	}

	function &current() {
		$current = $this->current;
		return $current;
	}

	function hasNext() {
		return ($this->row + 1 < $this->size) ? TRUE : FALSE;
	}

	function key() {
		return $this->row;
	}

	function &next() {
		$ret = FALSE;
		if ($this->hasNext()) {
			$this->current = mysqli_fetch_array($this->id, $this->mode);
			$this->row++;

			$ret = $this->current();
		}
		return $ret;
	}
	
	function free() {
		return mysqli_free_result($this->id);
	}

	function seek($offset) {
		mysqli_data_seek($this->id, $offset);
		return TRUE;
	}

	function numRows() {
		return $this->size;
	}

	function reset() {
		if ($this->row >= 0)
			$this->seek(0);
		
		$this->current = NULL;
		$this->row = -1;

		return TRUE;
	}
}

class MysqliStatement extends FADBStatement {
	//Use the generic one
}

class MysqliConnection extends FADBConnection {
	var $_link;
	var $valid = TRUE;

	function __construct() {
		if(!extension_loaded('mysqli'))
			trigger_error("The MySQL Improved (mysqli) extension is not loaded.", E_USER_ERROR);

		define('DBA_NUM', MYSQLI_NUM);
		define('DBA_ASSOC', MYSQLI_ASSOC);
	}
	
	function getNumQueries() {
		return $this->num_queries;
	}

	function affectedRows() {
		return mysqli_affected_rows($this->link);
	}

	function connect($info) {
		$ret = TRUE;
		
		if (!isset($info['server']) || !isset($info['user']) || !isset($info['pass']) || !isset($info['database'])) {
			trigger_error("Missing required connection information.", E_USER_ERROR);
			$ret = FALSE;
		}
		
		if ($ret) {
			$link = @mysqli_connect($info['server'], $info['user'], $info['pass'], $info['database']);
			
			if ($link == FALSE) {
				trigger_error("Unable to connect to the database", E_USER_ERROR);
				$ret = FALSE;
			}
			
			$this->link = $link;
		}

		return $ret;
	}

	function &prepareStatement($sql) {
		$ret = $this->createStatement($sql, $this);
		return $ret;
	}

	function executeUpdate($stmt) {
		$result = @mysqli_real_query($this->link, $stmt);

		if ($result == FALSE)
			trigger_error("Invalid query: ". mysqli_error($this->link), E_USER_ERROR);
		
		$this->num_queries++;

		return FALSE;
	}

	function executeQuery($stmt, $mode = DBA_ASSOC) {
		$result = @mysqli_query($this->link, $stmt);
		
		if (!$result) {
			if (mysqli_errno($this->link) == 0)
				trigger_error("Invalid query: Called executeQuery on an update", E_USER_NOTICE);
				
			trigger_error("Invalid query: ".mysqli_error($this->link), E_USER_ERROR);

			return FALSE;
		}
		
		/* Increment the number of queries */
		$this->num_queries++;

		$result = &new MysqliResultIterator($result, $mode);
		
		return $result;
	}

	function getInsertId($table = FALSE, $column = FALSE) {
		return mysqli_insert_id($this->link);
	}

	function isValid() {
		return $this->valid;
	}

	function quote($value) {
		return mysqli_escape_string($this->link, $value);
	}

	function createTemporary($table, $original = FALSE) {
		$this->executeUpdate("CREATE TEMPORARY TABLE ". $table);
	}

	function alterTable($table, $stmt) {
		$this->num_queries++;
		$this->executeUpdate("ALTER TABLE $table $stmt");
	}

	function beginTransaction() {
		mysqli_autocommit($this->link, FALSE);
	}

	function commitTransaction() {
		mysqli_commit($this->link);
	}
}

?>