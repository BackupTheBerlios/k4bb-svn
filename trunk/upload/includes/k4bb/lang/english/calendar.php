<?php
/**
* k4 Bulletin Board, calendar.php
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