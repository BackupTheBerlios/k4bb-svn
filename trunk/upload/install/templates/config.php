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
* @version $Id: config.php 132 2005-06-23 17:04:42Z Peter Goodman $
* @package k42
*/

$_CONFIG = array();
$_CONFIG['application']['action_var']	= 'act';
$_CONFIG['application']['lang']			= 'english';
//$_CONFIG['application']['dba_name']	= '{$dba_name}';

//$_CONFIG['template']['path']			= dirname(__FILE__) . '/templates';
$_CONFIG['template']['force_compile']	= FALSE;
$_CONFIG['template']['ignore_white']	= FALSE;

$_CONFIG['ftp']['use_ftp']				= {$use_ftp};
$_CONFIG['ftp']['username']				= '{$ftp_user}';
$_CONFIG['ftp']['password']				= '{$ftp_pass}';
//$_CONFIG['ftp']['server']				= '';

$_CONFIG['dba']['driver']				= '{$db_driver}';
$_CONFIG['dba']['database']				= '{$db_database}';
$_CONFIG['dba']['directory']			= '{$db_directory}';
$_CONFIG['dba']['server']				= '{$db_server}';
$_CONFIG['dba']['user']					= '{$db_user}';
$_CONFIG['dba']['pass']					= '{$db_pass}';
