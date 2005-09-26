<?php
/**
* k4 Bulletin Board, paginator.php
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
* @version $Id: paginator.php 137 2005-06-29 18:48:28Z Peter Goodman $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('FILEARTS'))
	return;

class FAPageIterator extends FAIterator {
	var $current;
	var $pager;
	var $before;
	var $after;
	
	function FAPageIterator(&$pager, $before, $after) {
		$this->__construct($pager, $before, $after);
	}

	function __construct(&$pager, $before, $after) {
		$this->pager = &$pager;

		if ($before == 'all')
			$before = $pager->page_num - 1;
		if ($after == 'all')
			$after = $pager->count - $pager->page_num;
		
		$this->before = $before;
		$this->after = $after;
		$this->reset();
	}

	function &current() {
		$ret = array('pagelink' => $this->pager->getPage($this->current), 'pagenum' => $this->current);
		return $ret;
	}

	function hasNext() {
		if ($this->current - $this->pager->page_num >= $this->after)
			return FALSE;
		if ($this->pager->hasPage($this->current + 1) !== FALSE)
			return TRUE;
	}

	function key() {
		return $this->current;
	}

	function &next() {
		$ret = FALSE;
		if ($this->hasNext()) {
			$this->current++;
			$ret = $this->current();
		}
		return $ret;
	}

	function reset() {
		$this->current = $this->pager->page_num - $this->before - 1;
		if ($this->current < 1)
			$this->current = 0;

		return TRUE;
	}
}

class FAPaginator extends FAObject {
	var $base_url;
	var $count;
	var $page_size;
	var $page_num;

	function __construct($base_url, $count, $page_num, $page_size = 15) {
		assert(is_a($base_url, 'FAUrl'));
		
		// shouldn't need to do this, but if the base_url is a reference to _URL
		// and it has been changed elsewhere, this will sort things out
		$base_url->args		= array_merge($base_url->args, $_GET);
		
		// remove everything but the file
		$base_url->anchor	= FALSE;
		$base_url->host		= FALSE;
		$base_url->user		= FALSE;
		$base_url->scheme	= FALSE;
		$base_url->path		= FALSE;

		$this->base_url		= $base_url;
		$this->count		= $count;
		$this->page_size	= $page_size;
		$this->page_num		= $page_num;
		
		if ($this->page_num <= 0)
			$this->page_num = 1;
	}

	function getPage($page) {
		if ($this->hasPage($page)) {
			$url = $this->base_url;
			$url->args['page']	= $page;
			$url->args['limit'] = $this->page_size;

			return $url->__toString();
		}
	}

	function getFirst() {
		$page = 1;
		if ($this->hasPage($page) && $page != $this->page_num)
			return array('pagenum' => $page, 'pagelink' => $this->getPage($page));
	}

	function getLast() {
		$page = ceil($this->count / $this->page_size);
		if ($this->hasPage($page) && $page != $this->page_num)
			return array('pagenum' => $page, 'pagelink' => $this->getPage($page));
	}

	function getNext($n = 1) {
		$page = $this->page_num + $n;
		if ($this->hasPage($page))
			return array('pagenum' => $page, 'pagelink' => $this->getPage($page));
	}

	function getPrev($n = 1) {
		$page = $this->page_num - $n;
		if ($this->hasPage($page))
			return array('pagenum' => $page, 'pagelink' => $this->getPage($page));
	}

	function hasPage($page) {
		$start = ($page - 1) * $this->page_size;
		if ($start >= 0 && $start < $this->count)
			return TRUE;

		return FALSE;
	}

	function &getIterator($before, $after) {
		$ret = &new FAPageIterator($this, $before, $after);
		return $ret;
	}
}

?>