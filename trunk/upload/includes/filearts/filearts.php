<?php
/**
* k4 Bulletin Board, filearts.php
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
* @version $Id: filearts.php 110 2005-06-13 20:48:58Z Peter Goodman $
* @package k42
*/

define('FILEARTS', TRUE);


define('FA_BASE_DIR', dirname(__FILE__));
define('FA_NL', "\r\n");

include FA_BASE_DIR . '/error.php';
include FA_BASE_DIR . '/iterator.php';
include FA_BASE_DIR . '/url.php';
include FA_BASE_DIR . '/controller.php';
include FA_BASE_DIR . '/session.php';
include FA_BASE_DIR . '/user.php';
include FA_BASE_DIR . '/timer.php';

include FA_BASE_DIR . '/database/database.php';
include FA_BASE_DIR . '/template/template.php';



class FAObject {
	function FAObject() {
		$args = func_get_args();
		call_user_func_array(array(&$this, '__construct'), $args);
	}

	function __construct() {
	}
}

?>