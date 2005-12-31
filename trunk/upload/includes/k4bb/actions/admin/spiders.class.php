<?php
/**
* k4 Bulletin Board, useragents.class.php
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

if(!defined('IN_K4')) {
	return;
}

class AdminSpiders extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_SPIDERS');
			$request['template']->setVar('misc_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/misc.html');

			$spiders	= $request['dba']->executeQuery("SELECT * FROM ". K4SPIDERS ." ORDER BY spidername ASC");
			$request['template']->setList('spiders', $spiders);
			
			$request['template']->setFile('content', 'spiders_manage.html');
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminInsertSpider extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			global $_SETTINGS;
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_SPIDERS');
			$request['template']->setVar('misc_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/misc.html');

			if(!isset($_REQUEST['useragent']) || !$_REQUEST['useragent'] || $_REQUEST['useragent'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_SUPPLYUSERAGENT'), 'content', TRUE);
				return $action->execute($request);
			}

			if(!isset($_REQUEST['spidername']) || !$_REQUEST['spidername'] || $_REQUEST['spidername'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_SUPPLYSPIDERNAME'), 'content', TRUE);
				return $action->execute($request);
			}
			
			if($request['dba']->getValue("SELECT * FROM ". K4SPIDERS ." WHERE useragent = '". $request['dba']->quote($_REQUEST['useragent']) ."'") > 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_SPIDEREXISTS'), 'content', TRUE);
				return $action->execute($request);
			}

			$request['dba']->executeUpdate("INSERT INTO ". K4SPIDERS ." (useragent,spidername,allowaccess) VALUES ('". $request['dba']->quote($_REQUEST['useragent']) ."','". $request['dba']->quote($_REQUEST['spidername']) ."', ". intval($_REQUEST['allowaccess']) .")");
			
			$action = new K4InformationAction(new K4LanguageElement('L_ADDEDSPIDER', $_REQUEST['useragent']), 'content', FALSE, 'admin.php?act=spiders', 3);
			
			reset_cache('spiders');
			
			return $action->execute($request);
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminUpdateSpider extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			global $_SETTINGS;
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_SPIDERS');
			$request['template']->setVar('misc_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/misc.html');

			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDSPIDER'), 'content', FALSE);
				return $action->execute($request);
			}

			$spider		= $request['dba']->getRow("SELECT * FROM ". K4SPIDERS ." WHERE id = ". intval($_REQUEST['id']));
			
			if(!is_array($spider) || empty($spider)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDSPIDER'), 'content', FALSE);
				return $action->execute($request);
			}

			if(!isset($_REQUEST['useragent']) || !$_REQUEST['useragent'] || $_REQUEST['useragent'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_SUPPLYUSERAGENT'), 'content', TRUE);
				return $action->execute($request);
			}

			if(!isset($_REQUEST['spidername']) || !$_REQUEST['spidername'] || $_REQUEST['spidername'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_SUPPLYSPIDERNAME'), 'content', TRUE);
				return $action->execute($request);
			}
			
			if($request['dba']->getValue("SELECT * FROM ". K4SPIDERS ." WHERE useragent = '". $request['dba']->quote($_REQUEST['useragent']) ."' AND spidername = '". $request['dba']->quote($_REQUEST['spidername']) ."' AND allowaccess = ". intval($_REQUEST['allowaccess'])) > 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_SPIDEREXISTS'), 'content', TRUE);
				return $action->execute($request);
			}

			if($request['dba']->getValue("SELECT * FROM ". K4SPIDERS ." WHERE useragent = '". $request['dba']->quote($_REQUEST['useragent']) ."' AND id <> ". intval($spider['id'])) > 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_SPIDEREXISTS'), 'content', TRUE);
				return $action->execute($request);
			}

			$request['dba']->executeUpdate("UPDATE ". K4SPIDERS ." SET useragent = '". $request['dba']->quote($_REQUEST['useragent']) ."', spidername = '". $request['dba']->quote($_REQUEST['spidername']) ."', allowaccess = ". intval($_REQUEST['allowaccess']) ." WHERE id = ". intval($spider['id']));
			
			$action = new K4InformationAction(new K4LanguageElement('L_UPDATEDSPIDER', $spider['spidername']), 'content', FALSE, 'admin.php?act=spiders', 3);

			reset_cache('spiders');

			return $action->execute($request);
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminRemoveSpider extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			global $_SETTINGS;
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_SPIDERS');
			$request['template']->setVar('misc_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/misc.html');

			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDSPIDER'), 'content', FALSE);
				return $action->execute($request);
			}

			$spider		= $request['dba']->getRow("SELECT * FROM ". K4SPIDERS ." WHERE id = ". intval($_REQUEST['id']));
			
			if(!is_array($spider) || empty($spider)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDSPIDER'), 'content', FALSE);
				return $action->execute($request);
			}
			
			$request['dba']->executeUpdate("DELETE FROM ". K4SPIDERS ." WHERE id = ". intval($spider['id']));
			
			$action = new K4InformationAction(new K4LanguageElement('L_REMOVEDSPIDER', $spider['spidername']), 'content', FALSE, 'admin.php?act=spiders', 3);
			
			reset_cache('spiders');
			
			return $action->execute($request);
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

?>