<?php
/**
* k4 Bulletin Board, censors.class.php
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
			
			$censors	= &$request['dba']->executeQuery("SELECT * FROM ". K4WORDCENSORS ." ORDER BY word ASC");
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