<?php
/**
* k4 Bulletin Board, timer.class.php
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
* @version $Id: timer.php 110 2005-06-13 20:48:58Z Peter Goodman $
* @package k42
*/

if (!defined('FILEARTS'))
	return;

class FATimer extends FAObject {
	var $start;
	var $precision;

	function __construct($precision = 4) {
		if (is_numeric($precision)) {
			$this->precision	= ceil($precision);
		}

		$this->start	= array_sum(explode(' ', microtime()));
	}

	function __toString() {
		return (String)(round(array_sum(explode(' ', microtime())) - $this->start, $this->precision));
	}
}

?>