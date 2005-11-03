<?php
/**
* k4 Bulletin Board, globals.class.php
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
* @author Peter Goodman
* @version $Id: globals.class.php 144 2005-07-05 02:29:07Z Peter Goodman $
* @package k42
*/

error_reporting(E_ALL ^ E_NOTICE);

if(!defined('IN_K4')) {
	return;
}

class AnonymousClass {
	function AnonymousClass($methods) {
		
		if(is_array($methods)) {
			foreach($methods as $var => $val) {
				$this->$var		= $val;
			}
		}
	}
}

class GlobalsStack {
	var $globals = array();

	function reset() {
		$this->globals = array();
	}

	function push($varname, $value) {
		return $this->globals[$varname] = $value;
	}

	function pop($varname) {
		return array_pop($this->globals);
	}
}

class Globals {
	function getStack() {
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