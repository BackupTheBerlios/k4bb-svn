/**
* k4 Bulletin Board, k4_template.php
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

define('CACHE_IN_DB', {$cache_in_db}); // only true if you have a SMALL forum
