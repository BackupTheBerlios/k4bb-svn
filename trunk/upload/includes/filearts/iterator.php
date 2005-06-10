<?php
/**
* k4 Bulletin Board, iterator.php
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
* @version $Id: iterator.php,v 1.3 2005/04/13 02:53:33 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('FILEARTS'))
	return;

class FAIterator extends FAObject {
	function &current() {}
	function hasNext() {}
	function key() {}
	function &next() {}
	function reset() {}
}

class FAArrayIterator extends FAIterator {
	var $data = array();
	var $key = -1;

	function __construct($data = NULL) {
		if ($data === NULL) $data = array();
		
		assert('is_array($data)');
		
		$this->data = array_values($data);
	}

	function &current() {
		return $this->data[$this->key];
	}
	function hasNext() {
		return ($this->key + 1 < sizeof($this->data));
	}
	function key() {
		return $this->key;
	}
	function &next() {
		if ($this->hasNext()) {
			$this->key++;
			return $this->current();
		}
	}
	function reset() {
		$this->key = -1;

		return TRUE;
	}
}

class FAProxyIterator extends FAIterator {
	var $it;
	var $size;
	var $row;

	function __construct(&$it) {
		$this->it	= &$it;
		$this->size	= &$this->it->size;
	}

	function &current() {
		$this->row	= &$this->it->row;
		return $this->it->current();
	}

	function hasNext() {
		return $this->it->hasNext();
	}

	function key() {
		return $this->it->key();
	}

	function &next() {
		if ($this->hasNext()) {
			$this->it->next();
			return $this->current();
		}
	}

	function reset() {
		return $this->it->reset();
	}
}

?>
