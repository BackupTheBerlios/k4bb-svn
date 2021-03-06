<?php
/**
* k4 Bulletin Board, acronyms.class.php
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

class AdminAcronyms extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			$acronyms	= $request['dba']->executeQuery("SELECT * FROM ". K4ACRONYMS ." ORDER BY acronym ASC");
			$request['template']->setList('acronyms', $acronyms);
			
			$request['template']->setFile('content', 'acronyms_manage.html');

			k4_bread_crumbs($request['template'], $request['dba'], 'L_ACRONYMS');
			$request['template']->setVar('posts_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/posts.html');
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminInsertAcronym extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			global $_SETTINGS;

			if(!isset($_REQUEST['acronym']) || !$_REQUEST['acronym'] || $_REQUEST['acronym'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_SUPPLYACRONYM'), 'content', TRUE);
				return $action->execute($request);
			}

			if(!isset($_REQUEST['meaning']) || !$_REQUEST['meaning'] || $_REQUEST['meaning'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_SUPPLYMEANING'), 'content', TRUE);
				return $action->execute($request);
			}
			
			if($request['dba']->getValue("SELECT * FROM ". K4ACRONYMS ." WHERE acronym = '". $request['dba']->quote($_REQUEST['acronym']) ."'") > 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_ACRONYMEXISTS'), 'content', TRUE);
				return $action->execute($request);
			}

			$request['dba']->executeUpdate("INSERT INTO ". K4ACRONYMS ." (acronym,meaning) VALUES ('". $request['dba']->quote($_REQUEST['acronym']) ."','". $request['dba']->quote($_REQUEST['meaning']) ."')");
			
			$action = new K4InformationAction(new K4LanguageElement('L_ADDEDACRONYM', $_REQUEST['acronym']), 'content', FALSE, 'admin.php?act=acronyms', 3);
			
			reset_cache('acronyms');

			k4_bread_crumbs($request['template'], $request['dba'], 'L_ACRONYMS');
			$request['template']->setVar('posts_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/posts.html');
			
			return $action->execute($request);
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminUpdateAcronym extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			global $_SETTINGS;
			
			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDACRONYM'), 'content', FALSE);
				return $action->execute($request);
			}

			$acronym		= $request['dba']->getRow("SELECT * FROM ". K4ACRONYMS ." WHERE id = ". intval($_REQUEST['id']));
			
			if(!is_array($acronym) || empty($acronym)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDACRONYM'), 'content', FALSE);
				return $action->execute($request);
			}

			if(!isset($_REQUEST['acronym']) || !$_REQUEST['acronym'] || $_REQUEST['acronym'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_SUPPLYACRONYM'), 'content', TRUE);
				return $action->execute($request);
			}

			if(!isset($_REQUEST['meaning']) || !$_REQUEST['meaning'] || $_REQUEST['meaning'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_SUPPLYMEANING'), 'content', TRUE);
				return $action->execute($request);
			}
			
			if($request['dba']->getValue("SELECT * FROM ". K4WORDCENSORS ." WHERE acronym = '". $request['dba']->quote($_REQUEST['acronym']) ."' AND meaning = '". $request['dba']->quote($_REQUEST['meaning']) ."'") > 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_ACRONYMEXISTS'), 'content', TRUE);
				return $action->execute($request);
			}

			if($request['dba']->getValue("SELECT * FROM ". K4ACRONYMS ." WHERE acronym = '". $request['dba']->quote($_REQUEST['acronym']) ."' AND id <> ". intval($acronym['id'])) > 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_ACRONYMEXISTS'), 'content', TRUE);
				return $action->execute($request);
			}

			$request['dba']->executeUpdate("UPDATE ". K4ACRONYMS ." SET acronym = '". $request['dba']->quote($_REQUEST['acronym']) ."', meaning = '". $request['dba']->quote($_REQUEST['meaning']) ."' WHERE id = ". intval($acronym['id']));
			
			$action = new K4InformationAction(new K4LanguageElement('L_UPDATEDACRONYM', $acronym['acronym']), 'content', FALSE, 'admin.php?act=acronyms', 3);

			reset_cache('acronyms');
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_ACRONYMS');
			$request['template']->setVar('posts_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/posts.html');

			return $action->execute($request);
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminRemoveAcronym extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			global $_SETTINGS;
			
			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDACRONYM'), 'content', FALSE);

				return $action->execute($request);
				return TRUE;
			}

			$acronym		= $request['dba']->getRow("SELECT * FROM ". K4ACRONYMS ." WHERE id = ". intval($_REQUEST['id']));
			
			if(!is_array($acronym) || empty($acronym)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDACRONYM'), 'content', FALSE);

				return $action->execute($request);
			}
			
			$request['dba']->executeUpdate("DELETE FROM ". K4ACRONYMS ." WHERE id = ". intval($acronym['id']));
			
			$action = new K4InformationAction(new K4LanguageElement('L_REMOVEDACRONYM', $acronym['acronym']), 'content', FALSE, 'admin.php?act=acronyms', 3);
			
			reset_cache('acronyms');

			k4_bread_crumbs($request['template'], $request['dba'], 'L_ACRONYMS');
			$request['template']->setVar('posts_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/posts.html');
			
			return $action->execute($request);
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

?>