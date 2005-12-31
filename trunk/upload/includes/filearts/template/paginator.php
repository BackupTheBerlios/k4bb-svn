<?php
/**
* k4 Bulletin Board, paginator.php
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
* @version $Id: paginator.php 137 2005-06-29 18:48:28Z Peter Goodman $
* @package k42
*/



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

	function current() {
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

	function next() {
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
		$page = intval(@ceil($this->count / $this->page_size));
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

	function getIterator($before, $after) {
		$ret = &new FAPageIterator($this, $before, $after);
		return $ret;
	}
}

?>