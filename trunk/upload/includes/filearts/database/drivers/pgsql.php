<?php
/**
* k4 Bulletin Board, pgsql.php
*
* Copyright (c) 2005, Peter Goodman
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
* @version $Id$
* @package k42
*/

if (!defined('FILEARTS'))
	return;

class PgSQLResultIterator extends FADBResult {
	var $id;
	var $mode;
	var $row = -1;
	var $current;
	var $size;

	function __construct($id, $mode) {
		$this->id = $id;
		$this->mode = $mode;
		$this->size = pg_num_rows($this->id);
	}

	function &current() {
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

	function &next() {
		$ret = FALSE;
		if ($this->hasNext()) {
			$this->current = pg_fetch_array($this->id, $this->mode);
			$this->row++;

			$ret = $this->current();
		}
		return $ret;
	}
	
	function free() {
		return pg_free_result($this->id);
	}

	function seek($offset) {
		return pg_data_seek($this->id, $offset);
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

class PgSQLStatement extends FADBStatement {
	//Use the generic one
}

class PgSQLConnection extends FADBConnection {
	var $link;
	var $valid = TRUE;

	function __construct() {
		if(!extension_loaded('pgsql'))
			trigger_error("The PostgreSQL extension is not loaded.", E_USER_ERRROR);

		define('DBA_NUM', PGSQL_NUM);
		define('DBA_ASSOC', PGSQL_ASSOC);
	}

	function affectedRows() {
		return pg_affected_rows($this->link);
	}

	function connect($info) {
		$ret = TRUE;

		if (!isset($info['server']) || !isset($info['user']) || !isset($info['pass']) || !isset($info['database'])) {
			trigger_error("Missing required connection information.", E_USER_ERROR);
			$ret = FALSE;
		}
		
		if ($ret) {
			$link = @pg_pconnect("dbname=". $info['database'] ." user=". $info['user'] ." password=". $info['pass']);
			
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
		
		$stmt	= preg_replace("~LIMIT ([0-9]+?),([0-9]+?)~i", "LIMIT \\2, OFFSET \\1");

		$result = @pg_query($this->link, $stmt);

		if ($result == FALSE)
			trigger_error("Invalid query: ". pg_last_error($this->link), E_USER_ERROR);
		
		/* Increment the number of queries */
		$this->num_queries++;

		return TRUE;
	}

	function executeQuery($stmt, $mode = DBA_ASSOC) {
		
		$stmt	= preg_replace("~LIMIT ([0-9]+?),([0-9]+?)~i", "LIMIT \\2, OFFSET \\1");

		$result = @pg_query($this->link, $stmt);

		if (!is_resource($result)) {
				
			trigger_error("Invalid query: ". pg_last_error($this->link), E_USER_ERROR);

			return FALSE;
		}
		
		/* Increment the number of queries */
		$this->num_queries++;

		$result = &new PgSQLResultIterator($result, $mode);

		return $result;
	}
	
	function getNumQueries() {
		return $this->num_queries;
	}

	function getInsertId($table, $column) {
		return $this->getValue("SELECT currval('{$table}_{$column}_seq')");
	}

	function isValid() {
		return $this->valid;
	}

	function quote($value) {
		return pg_escape_string($value);
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
		$this->executeUpdate("BEGIN");
	}

	function commitTransaction() {
		$this->executeUpdate("COMMIT");
	}
}

?>