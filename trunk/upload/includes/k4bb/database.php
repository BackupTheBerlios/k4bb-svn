<?php
/**
* k4 Bulletin Board, database.php
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
* @author Geoffrey Goodman
* @version $Id: database.php 110 2005-06-13 20:48:58Z Peter Goodman $
* @package k42
*/

class K4SqlDebugger extends FAObject {
	var $_obj;
	var $_queries = array();
	var $_results = array();
	var $_lines = array();
	var $_files = array();

	function __construct(&$obj) {
		$this->_obj = &$obj;
	}

	function getBacktrace() {
		$result = debug_backtrace();
		
		$key = count($result)-5;
		
		//if(isset($result[$key-1]['line']))
		//	$key -= 1;

		$this->_lines[] = $result[$key]['line'];
		$this->_files[] = $result[$key]['file'];
	}

	function affectedRows() {
		return $this->_obj->affectedRows();
	}

	function connect($info) {
		return $this->_obj->connect($info);
	}

	function prepareStatement($sql) {
		$ret = $this->_obj->createStatement($sql, $this);
		return $ret;
	}

	function executeUpdate($stmt) {
		$result = $this->_obj->executeUpdate($stmt);

		$this->_queries[] = $stmt;
		$this->_results[] = $result;
		
		$this->getBackTrace();

		return $result;
	}

	function executeQuery($stmt, $mode = DBA_ASSOC) {
		$result = $this->_obj->executeQuery($stmt, $mode);

		$this->_queries[] = $stmt;
		$this->_results[] = $result;
		
		$this->getBackTrace();

		return $result;
	}

	function getInsertId($table, $column) {
		return $this->_obj->getInsertId($table, $column);
	}

	function isValid() {
		return $this->_obj->isValid();
	}

	function quote($value) {
		return $this->_obj->quote($value);
	}

	function getNumQueries() {
		return $this->_obj->getNumQueries();
	}

	function getNumRows() {
		return $this->_obj->getNumRows();
	}

	function getRow($query, $type = DBA_ASSOC) {
		$result = $this->_obj->getRow($query, $type);

		$this->_queries[] = $query;
		$this->_results[] = $result;
		
		$this->getBackTrace();

		return $result;
	}
	
	function createTemporary($table, $original = FALSE) {
		return $this->_obj->createTemporary($table, $original);
	}

	function alterTable($table, $stmt) {
		return $this->_obj->alterTable($table, $stmt);
	}

	function version() {
		return $this->_obj->version();
	}

	function getValue($query) {
		$result = $this->_obj->getValue($query);

		$this->_queries[] = $query;
		$this->_results[] = $result;
		
		$this->getBackTrace();

		return $result;
	}

	function getDebugIterator() {
		$ret = &new K4SqlDebuggerIterator($this->_queries, $this->_results, $this->_lines, $this->_files);
		return $ret;
	}
}

class K4SqlDebuggerIterator extends FAArrayIterator {
	var $_results;
	var $_lines;
	var $_files;

	function __construct(&$queries, &$results, &$lines, &$files) {
		parent::__construct($queries);
		$this->_results = &$results;
		$this->_lines = &$lines;
		$this->_files = &$files;
	}

	function current() {
		$current = array();
		$query = parent::current();
		$query = preg_replace('~(\s|\b)(TEMPORARY|ALTER|CREATE|SELECT|SET|LEFT|RIGHT|ORDER|BY|GROUP|ASC|DESC|JOIN|ON|LIMIT|UPDATE|DELETE|COUNT|FROM|WHERE|AND|AS)(\s|\b)~i', '<strong class="debug_sql_method">\\1\\2\\3</strong>', $query);

		$result = $this->_results[$this->key()];

		$current['query'] = $query;

		if (is_a($result, 'FAIterator')) {
			$current['results'] = $this->formatArray($result->current());
			$current['num_rows'] = $this->_results[$this->key()]->numRows();
		} else {
			$current['results'] = '--';
			$current['num_rows'] = 0;
		}

		$current['file'] = basename($this->_files[$this->key()]);
		$current['line'] = $this->_lines[$this->key()];

		return $current;
	}

	function formatArray($array) {
		ob_start();
		print_r($array);
		$buffer = ob_get_contents();
		ob_end_clean();

		return $buffer;
	}
}

?>