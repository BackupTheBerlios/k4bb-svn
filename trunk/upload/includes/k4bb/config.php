<?php
 /**
* k4 Bulletin Board, k4_template.php
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
* @author Geoffrey Goodman
* @version $Id$
* @package k42
*/

$_CONFIG = array();
$_CONFIG['application']['action_var']	= 'act';
$_CONFIG['application']['lang']			= 'english';
//$_CONFIG['application']['dba_name']	= '';

//$_CONFIG['template']['path']			= dirname(__FILE__) . '/templates';
$_CONFIG['template']['force_compile']	= FALSE;
$_CONFIG['template']['ignore_white']	= FALSE;

$_CONFIG['ftp']['use_ftp']				= FALSE;
$_CONFIG['ftp']['username']				= '';
$_CONFIG['ftp']['password']				= '';
$_CONFIG['ftp']['server']				= '';

$_CONFIG['dba']['driver']				= 'mysqli';
$_CONFIG['dba']['database']				= 'k4_forum';
$_CONFIG['dba']['directory']			= '';
$_CONFIG['dba']['server']				= 'localhost';
$_CONFIG['dba']['user']					= 'test';
$_CONFIG['dba']['pass']					= 'test';

?>