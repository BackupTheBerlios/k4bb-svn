<?php
/**
* k4 Bulletin Board, database.php
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
* @version $Id$
* @package k42
*/

class K4SqlDebugger extends FAObject {
	var $_obj;
	var $_queries = array();
	var $_results = array();

	function __construct(&$obj) {
		$this->_obj = &$obj;
	}

	function affectedRows() {
		return $this->_obj->affectedRows();
	}

	function connect($info) {
		return $this->_obj->connect($info);
	}

	function &prepareStatement($sql) {
		return $this->_obj->createStatement($sql, &$this);
	}

	function executeUpdate($stmt) {
		$result =& $this->_obj->executeUpdate($stmt);

		$this->_queries[] = $stmt;
		$this->_results[] = $result;

		return $result;
	}

	function executeQuery($stmt, $mode = DBA_ASSOC) {
		$result =& $this->_obj->executeQuery($stmt, $mode);

		$this->_queries[] = $stmt;
		$this->_results[] = $result;

		return $result;
	}

	function getInsertId() {
		return $this->_obj->getInsertId();
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

		return $result;
	}

	function getValue($query) {
		$result = $this->_obj->getValue($query);

		$this->_queries[] = $query;
		$this->_results[] = $result;

		return $result;
	}

	function &getDebugIterator() {
		return new K4SqlDebuggerIterator($this->_queries, $this->_results);
	}
}

class K4SqlDebuggerIterator extends FAArrayIterator {
	var $_results;

	function __construct(&$queries, &$results) {
		parent::__construct($queries);
		$this->_results = &$results;
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