<?php
/**
* k4 Bulletin Board, calendar.php
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
* @author Thomas "Thasmo" Deinhamer (thasmo at gmail dot com)
* @version $Id: calendar.php Thomas Deinhamer $
* @package k42
*/

if (!defined('IN_K4'))
	return;

if (empty($_LANG) || !is_array($_LANG)) {
	$_LANG = array();
}


$_LANG += array(

/* General Phrases */
'L_CALENDAR'					=> 'Calendar',
'L_CALENDARDISABLED'			=> 'The calendar is currently disabled.',
'L_CALENDARERROR'				=> 'An error occured while viewing the calendar.',

);

?>