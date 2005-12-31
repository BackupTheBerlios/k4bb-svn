<?php
/**
* k4 Bulletin Board, lang.php LANGUAGE PACK
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
* @version $Id: lang.php 142 2005-07-01 19:08:04Z Peter Goodman $
* @package k42
*/

if(!defined('IN_K4'))
	return;

define('LANG_DIR', dirname(__FILE__));

$_LANG			= array();

include LANG_DIR . '/general.php';
include LANG_DIR . '/blog.php';
include LANG_DIR . '/admin.php';
include LANG_DIR . '/mod.php';
include LANG_DIR . '/usercp.php';
include LANG_DIR . '/mail.php';

?>