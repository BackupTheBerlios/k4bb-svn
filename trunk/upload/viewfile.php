<?php
/**
* k4 Bulletin Board, viewfile.php
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
* @version $Id$
* @package k4-2.0-dev
*/

ob_start();

require "includes/filearts/filearts.php";
require "includes/k4bb/k4bb.php";

class K4DefaultAction extends FAAction {
	function execute(&$request) {
		
		
	}
}

class K4ViewPicture extends FAAction {
	var $bigname, $smallname, $table_column, $table;
	
	function __construct($table_column, $table) {
		$this->table_column	= $table_column;
		$this->table		= $table;
	}
	function execute(&$request) {
		
		global $_QUERYPARAMS;
		
		/* set the breadcrumbs bit */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
		
		if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
			$action = new K4InformationAction(new K4LanguageElement('L_BAD'. strtoupper($this->table_column)), 'content', FALSE);
			return $action->execute($request);
		}
		
		$avatar		= $request['dba']->getRow("SELECT * FROM ". $this->table ." WHERE user_id = ". intval($_REQUEST['id']));

		if(!is_array($avatar) || empty($avatar)) {
			$action = new K4InformationAction(new K4LanguageElement('L_BAD'. strtoupper($this->table_column)), 'content', FALSE);
			return $action->execute($request);
		}

		$user		= $request['dba']->getRow("SELECT {$_QUERYPARAMS['user']}{$_QUERYPARAMS['userinfo']}{$_QUERYPARAMS['usersettings']} FROM ((". K4USERS ." u LEFT JOIN ". K4USERINFO ." ui ON u.id=ui.user_id) LEFT JOIN ". K4USERSETTINGS ." us ON us.user_id=u.id) WHERE u.id=". intval($_REQUEST['id']));
		
		if(!is_array($user) || empty($user)) {
			$action = new K4InformationAction(new K4LanguageElement('L_USERDOESNTEXIST'), 'content', TRUE);
			return $action->execute($request);
		}

		/* Do we have permission to view attachments in this forum? */
		if(isset($user['attach'. $this->table_column]) && $user['attach'. $this->table_column] == 0) {
			no_perms_error($request);
			return TRUE;
		}

		// send our headers
		header("Content-Type: ". $avatar['mime_type']);
		header("Content-Length: " . $avatar['file_size']);
		
		$avatar_file		= BB_BASE_DIR .'/tmp/upload/'. $this->table_column .'s/'. intval($user['id']) .'.'. $avatar['file_type'];

		if($avatar['in_db'] == 1) {
			$contents		= $avatar['file_contents'];
		} else {
			
			if(file_exists($avatar_file)) {
				$contents	= file_get_contents($avatar_file);
			} else {
				$action = new K4InformationAction(new K4LanguageElement('L_BAD'. strtoupper($this->table_column)), 'content', FALSE);
				return $action->execute($request);
			}
		}

		echo $contents;
		
		unset($contents);

		exit;
	}
}

$app = &new K4Controller('forum_base.html');
$app->setAction('', new K4DefaultAction);
$app->setDefaultEvent('');

$app->setAction('attachment', new K4ViewAttachment);
$app->setAction('remove_attach', new K4RemoveAttachment);

$app->setAction('avatar', new K4ViewPicture('avatar', K4AVATARS));
$app->setAction('picture', new K4ViewPicture('picture', K4PPICTURES));

$app->execute();

ob_flush();

?>