<?php
/**
* k4 Bulletin Board, globals.class.php
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
* @version $Id: globals.class.php 144 2005-07-05 02:29:07Z Peter Goodman $
* @package k42
*/

if(!defined('IN_K4'))
	return;

class GlobalsStack {
	var $globals = array();

	function reset() {
		$this->globals = array();
	}

	function push($varname, $value) {
		return $this->globals[$varname] = &$value;
	}

	function pop($varname) {
		return array_pop($this->globals);
	}
}

class Globals {
	function &getStack() {
		static $instance = NULL;

		if ($instance == NULL)
			$instance = new GlobalsStack();

		return $instance;
	}
	function getGlobals() {
		$stack = &Globals::getStack();

		return $stack->globals;
	}
	function setGlobal($varname, $val) {
		$stack = &Globals::getStack();

		return $stack->push($varname, $val);
	}
	function getGlobal($varname) {
		$stack = &Globals::getStack();
		
		if(isset($stack->globals[$varname]))
			return $stack->globals[$varname];

		return FALSE;
	}
	function is_set($varname) {
		$stack = &Globals::getStack();
		
		return isset($stack->globals[$varname]);
	}
	function un_set($varname) {
		$stack = &Globals::getStack();
		
		if(isset($stack->globals[$varname]))
			unset($stack->globals[$varname]);
		
		return TRUE;
	}
	function reset() {
		$stack = &Globals::getStack();
		$stack->reset();
	}
}

?>