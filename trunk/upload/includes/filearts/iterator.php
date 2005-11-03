<?php
/**
* FileArts, iterator.php
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
* @version $Id: iterator.php 147 2005-07-09 17:12:40Z Peter Goodman $
* @package k42
*/

error_reporting(E_ALL ^ E_NOTICE);

if(!defined('FILEARTS'))
	return;

class FAIterator extends FAObject {
	function current() { trigger_error("Pure virtual function", E_USER_ERROR); }
	function hasNext() { trigger_error("Pure virtual function", E_USER_ERROR); }
	function hasPrev() { trigger_error("Pure virtual function", E_USER_ERROR); }
	function key() { trigger_error("Pure virtual function", E_USER_ERROR); }
	function next() { trigger_error("Pure virtual function", E_USER_ERROR); }
	function reset() { trigger_error("Pure virtual function", E_USER_ERROR); }
}

/**
 * FAArrayIterator
 *
 * Basic iterator designed for arrays. Keep in mind that the iterator
 * interface is supposed to return numeric keys 0..N, so don't expect
 * associative arrays' keys to be returned with FAArrayIterator::key().
 */
class FAArrayIterator extends FAIterator {
	var $data = array();
	var $key = -1;

	function __construct($data = NULL) {
		if ($data === NULL) $data = array();
		
		assert('is_array($data)');
		
		$this->data = array_values($data);
	}

	function current() {
		$data = $this->data[$this->key];
		return $data;
	}
	function hasPrev() {
		return ($this->key - 1 >= 0);
	}
	function hasNext() {
		return ($this->key + 1 < sizeof($this->data));
	}
	function key() {
		return $this->key;
	}
	function next() {
		$ret = FALSE;
		if ($this->hasNext()) {
			$this->key++;
			$ret = $this->current();
		}
		return $ret;
	}
	function reset() {
		$this->key = -1;

		return TRUE;
	}
}

/**
 * FAProxyIterator
 *
 * An iterator whose sole purpose is to be extended.  Each method is
 * designed to allow derivation.  For example, if you want to add a column
 * to each row of a iterator's result-set, you could override the current
 * method of a FAProxyIterator.
 */
class FAProxyIterator extends FAIterator {
	var $it;

	function FAProxyIterator(&$it) {
		$this->__construct($it);
	}

	function __construct(&$it) {
		$this->it	= &$it;
	}

	function current() {
		$current = $this->it->current();
		return $current;
	}

	function hasPrev() {
		return $this->it->hasPrev();
	}
	function hasNext() {
		return $this->it->hasNext();
	}

	function key() {
		$key = $this->it->key();
		return $key;
	}

	function next() {
		$ret = FALSE;
		if ($this->hasNext()) {
			$this->it->next();
			$ret = $this->current();
		}
		return $ret;
	}

	function reset() {
		return $this->it->reset();
	}

	function seek($start, $limit) {		
		
		$start				= intval($this->it->size <= $start ? $this->it->size : $start);
		$limit				= intval(($start + $limit) > $this->it->size ? $this->it->size : ($start + $limit));
		
		$this->it->key		= -1;
		$this->it->current	= NULL;
		$this->it->size		= $limit - $start;
		
		$this->it->seek($start - 1);
		//$this->it->next();

		return TRUE;
	}
}

/**
 * FAChainedIterator
 *
 * An iterator that is able to chain multiple iterators one after the
 * other.  Useful if there is the need to tack items on to the beginning
 * or end of another iterator.
 */
class FAChainedIterator extends FAIterator {
	var $_index = 0;
	var $_chain = array();

	function __construct(&$it) {
		assert('is_a($it, "FAIterator")');

		$this->_chain[] = &$it;
	}

	function addIterator(&$it) {
		$this->_chain[] = &$it;
	}

	function current() {
		return $this->_chain[$this->_index]->current();
	}

	function hasNext() {
		$i = $this->_index;

		while (isset($this->_chain[$i])) {
			if ($this->_chain[$i]->hasNext())
				return TRUE;
			$i++;
		}

		return FALSE;
	}

	function key() {
		return $this->_chain[$this->_index]->key() + $this->_index;
	}

	function next() {
		while (isset($this->_chain[$this->_index])) {
			if ($this->_chain[$this->_index]->hasNext())
				return $this->_chain[$this->_index]->next();
			$this->_index++;
		}

		return FALSE;
	}

	function reset() {
		$this->_index = 0;
		return TRUE;
	}
}

?>
