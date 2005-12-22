<?php
/**
* k4 Bulletin Board, calendar.class.php
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
* @author Thasmo (thasmo at gmail dot com)
* @version $Id$
* @package k42
*/



if(!defined('IN_K4'))
	return;

class K4Calendar extends FAObject {
	
	function K4Calendar() {
		$this->__construct();
	}
	
	function __construct() {
		$this->getDays();
	}
	
	function getDays($switch = NULL) {
		$days = date('t', time());
		return array('day' => range(1, $days));
	}
}

class K4CalendarIterator extends FAArrayIterator {
	var $settings;
	var $data = array();
	var $usergroups;

	function K4CalendarIterator() {
		$this->__construct();
	}
 	
	function __construct() {
		global $_SETTINGS, $_USERGROUPS;
		
		$this->usergroups = $_USERGROUPS;
		$this->settings = $_SETTINGS;
		
		parent::__construct($this->data);
	}
	
	function current() {
		$temp = parent::current();
		
		/* Return the formatted info */
		return $temp;
	}
}

?>