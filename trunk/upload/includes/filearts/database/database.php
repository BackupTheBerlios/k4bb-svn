<?php
/**
* k4 Bulletin Board, database.php
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
* @version $Id: database.php 110 2005-06-13 20:48:58Z Peter Goodman $
* @package k42
*/

if (!defined('FILEARTS'))
	return;

define('DBA_BASE_DIR', dirname(__FILE__));

function db_connect($info) {
	static $connections = array();

	$name = serialize($info);

	if (isset($connections[$name]))
		return $connections[$name];

	if (!isset($info['driver']))
		return trigger_error("No database driver specified");

	$driver = DBA_BASE_DIR."/drivers/{$info['driver']}.php";
	$class = "{$info['driver']}Connection";

	if (!is_readable($driver))
		return trigger_error("Database driver does not exist: {$info['driver']}");

	require_once $driver;

	if (!class_exists($class))
		return trigger_error("Database driver does not exist: {$info['driver']}");

	$dba = &new $class();

	if (!is_a($dba, 'FADBConnection'))
		return trigger_error("Database driver does not extends FADBConnection: {$info['driver']}");
	
	// Error is thrown in the constructor (hopefully)
	$dba->connect($info);

	$connections[$name] = $dba;

	return $dba;
}

class FADBConnection extends FAObject {
	var $num_queries = 0;

	function connect($info) {}

	function createStatement($stmt, &$dba) {
		$ret = &new FADBStatement($stmt, $dba);
		return $ret;
	}

	function getNumQueries() {
		return $this->num_queries;
	}

	function getRow($query, $type = DBA_ASSOC) {
		$result = $this->executeQuery($query, $type);
		
		if ($result->next()) {
			return $result->current();
		}
		
		return FALSE;
	}

	function getValue($query) {
		$result = $this->executeQuery($query, DBA_NUM);
		
		if ($result->next()) {
			return $result->get(0);
		}
		
		return FALSE;
	}
}

class FADBResult extends FAIterator {
	function get($column) {
		if (isset($this->current[$column]))
			return $this->current[$column];
	}

	function getDate($column, $format = '%x') {
		if (isset($this->current[$column]))
			return strftime($format, $this->current[$column]);
	}

	function getFloat($column) {
		if (isset($this->current[$column]))
			return (float)$this->current[$column];
	}

	function getInt($column) {
		if (isset($this->current[$column]))
			return (int)$this->current[$column];
	}

	function getString($column) {
		if (isset($this->current[$column]))
			return (string)$this->current[$column];
	}

	function getTime($column, $format = '%X') {
		if (isset($this->current[$column]))
			return strftime($format, $this->current[$column]);
	}

	function getTimestamp($column, $format = 'Y-m-d H:i:s') {
		if (isset($this->current[$column]))
			return date($format, $this->current[$column]);
	}
}

class FADBStatement extends FAObject {
	var $db;
	var $params = 0;
	var $vars;
	var $stmt;

	function __construct($stmt, &$db) {
		$this->stmt = preg_replace('/(\?)/e', "\$this->stmtReplace();", $stmt);
		$this->db = &$db;
	}

	function executeQuery($mode = DBA_ASSOC) {
		$ret = $this->db->executeQuery($this->getSql(), $mode);
		return $ret;
	}

	function executeUpdate($mode = DBA_ASSOC) {
		return $this->db->executeUpdate($this->getSql(), $mode);
	}

	function stmtReplace() {
		$this->params++;

		return "{\$vars[{$this->params}]}";
	}

	function setFloat($n, $value) {
		$this->vars[$n] = floatval($value);
	}

	function setInt($n, $value) {
		$this->vars[$n] = intval($value);
	}

	function setNull($n) {
		$this->vars[$n] = 'NULL';
	}

	function setString($n, $value) {
		$this->vars[$n] = "'".$this->db->quote($value)."'";
	}

	function getSql() {
		$vars = $this->vars;

		eval("\$stmt = \"{$this->stmt}\";");

		return $stmt;
	}

	function __toString() {
		return $this->getSql();
	}
}

?>
