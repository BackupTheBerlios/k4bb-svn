<?php
/**
* k4 Bulletin Board, censors.class.php
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

class AdminWordCensors extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			$censors	= $request['dba']->executeQuery("SELECT * FROM ". K4WORDCENSORS ." ORDER BY word ASC");
			$request['template']->setList('censors', $censors);
			
			$request['template']->setFile('content', 'wordcensors_manage.html');

			k4_bread_crumbs($request['template'], $request['dba'], 'L_WORDCENSORS');
			$request['template']->setVar('posts_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/posts.html');
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminInsertCensor extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			global $_SETTINGS;

			if(!isset($_REQUEST['word']) || !$_REQUEST['word'] || $_REQUEST['word'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_SUPPLYWORD'), 'content', TRUE);
				return $action->execute($request);
			}

			if(!isset($_REQUEST['replacement']) || !$_REQUEST['replacement'] || $_REQUEST['replacement'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_SUPPLYREPLACEMENT'), 'content', TRUE);
				return $action->execute($request);
			}
			
			if($request['dba']->getValue("SELECT * FROM ". K4WORDCENSORS ." WHERE word = '". $request['dba']->quote($_REQUEST['word']) ."'") > 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_CENSOREXISTS'), 'content', TRUE);
				return $action->execute($request);
			}

			$request['dba']->executeUpdate("INSERT INTO ". K4WORDCENSORS ." (word,replacement,method) VALUES ('". $request['dba']->quote($_REQUEST['word']) ."','". $request['dba']->quote($_REQUEST['replacement']) ."',". intval($_REQUEST['method']) .")");
			
			$action = new K4InformationAction(new K4LanguageElement('L_ADDEDWORDCENSOR', $_REQUEST['word']), 'content', FALSE, 'admin.php?act=censors', 3);
						
			reset_cache('censors');

			k4_bread_crumbs($request['template'], $request['dba'], 'L_WORDCENSORS');
			$request['template']->setVar('posts_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/posts.html');

			return $action->execute($request);
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminUpdateCensor extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			global $_SETTINGS;
			
			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDCENSOR'), 'content', FALSE);
				return $action->execute($request);
			}

			$censor		= $request['dba']->getRow("SELECT * FROM ". K4WORDCENSORS ." WHERE id = ". intval($_REQUEST['id']));
			
			if(!is_array($censor) || empty($censor)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDCENSOR'), 'content', FALSE);
				return $action->execute($request);
			}

			if(!isset($_REQUEST['word']) || !$_REQUEST['word'] || $_REQUEST['word'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_SUPPLYWORD'), 'content', TRUE);
				return $action->execute($request);
			}

			if(!isset($_REQUEST['replacement']) || !$_REQUEST['replacement'] || $_REQUEST['replacement'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_SUPPLYREPLACEMENT'), 'content', TRUE);
				return $action->execute($request);
			}
			
			if($request['dba']->getValue("SELECT * FROM ". K4WORDCENSORS ." WHERE word = '". $request['dba']->quote($_REQUEST['word']) ."' AND replacement = '". $request['dba']->quote($_REQUEST['replacement']) ."' AND method = ". intval($_REQUEST['method']) ." AND id <> ". intval($censor['id'])) > 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_CENSOREXISTS'), 'content', TRUE);
				return $action->execute($request);
			}

			if($request['dba']->getValue("SELECT * FROM ". K4WORDCENSORS ." WHERE word = '". $request['dba']->quote($_REQUEST['word']) ."' AND id <> ". intval($censor['id'])) > 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_CENSOREXISTS'), 'content', TRUE);
				return $action->execute($request);
			}

			$request['dba']->executeUpdate("UPDATE ". K4WORDCENSORS ." SET word = '". $request['dba']->quote($_REQUEST['word']) ."', replacement = '". $request['dba']->quote($_REQUEST['replacement']) ."', method = ". intval($_REQUEST['method']) ." WHERE id = ". intval($censor['id']));
			
			$action = new K4InformationAction(new K4LanguageElement('L_UPDATEDWORDCENSOR', $censor['word']), 'content', FALSE, 'admin.php?act=censors', 3);
			
			reset_cache('censors');

			k4_bread_crumbs($request['template'], $request['dba'], 'L_WORDCENSORS');
			$request['template']->setVar('posts_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/posts.html');

			return $action->execute($request);
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminRemoveCensor extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			global $_SETTINGS;
			
			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDCENSOR'), 'content', FALSE);
				return $action->execute($request);
			}

			$censor		= $request['dba']->getRow("SELECT * FROM ". K4WORDCENSORS ." WHERE id = ". intval($_REQUEST['id']));
			
			if(!is_array($censor) || empty($censor)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDCENSOR'), 'content', FALSE);
				return $action->execute($request);
			}
			
			$request['dba']->executeUpdate("DELETE FROM ". K4WORDCENSORS ." WHERE id = ". intval($censor['id']));
			
			$action = new K4InformationAction(new K4LanguageElement('L_REMOVEDWORDCENSOR', $censor['word']), 'content', FALSE, 'admin.php?act=censors', 3);
			
			reset_cache('censors');

			k4_bread_crumbs($request['template'], $request['dba'], 'L_WORDCENSORS');
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