<?php
/**
* k4 Bulletin Board, blog.php
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
* @author James Logsdon
* @author Thomas "Thasmo" Deinhamer (thasmo at gmail dot com)
* @version $Id: blog.php 110 2005-06-13 20:48:58Z Peter Goodman $
* @package k42
*/

if (!defined('IN_K4'))
	return;

if (empty($_LANG) || !is_array($_LANG)) {
	$_LANG = array();
}


$_LANG += array(

/* Permissions settings */
'L_BLOGS'					=> 'Blogs',
'L_OTHER_BLOGS'				=> 'Andere Blogs',
'L_COMMENTS'				=> 'Kommentare',
'L_OTHER_COMMENTS'			=> 'Andere Kommentare',
'L_PRIVATE_BLOG'			=> 'Privater Blog',
);

?>