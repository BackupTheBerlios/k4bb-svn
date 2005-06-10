<?php
/**
* k4 Bulletin Board, users.class.php
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
* @version $Id: users.class.php,v 1.1 2005/05/09 21:17:27 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	exit;
}

class AdminBadUserNames extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			$badnames	= &$request['dba']->executeQuery("SELECT * FROM ". K4BADUSERNAMES ." ORDER BY name ASC");
			$request['template']->setList('badnames', $badnames);
			
			$request['template']->setFile('content', 'admin.html');
			$request['template']->setFile('admin_panel', 'badnames_manage.html');
		} else {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

			return $action->execute($request);
		}

		return TRUE;
	}
}

class AdminInsertBadUserName extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			global $_SETTINGS;

			if(!isset($_REQUEST['name']) || !$_REQUEST['name'] || $_REQUEST['name'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_SUPPLYBADUSERNAME'), 'content', TRUE);

				return $action->execute($request);
				return TRUE;
			}
			
			if(strlen($_REQUEST['name']) < intval($_SETTINGS['minuserlength'])) {
				$action = new K4InformationAction(new K4LanguageElement('L_USERNAMETOOSHORT', intval($_SETTINGS['minuserlength']), intval($_SETTINGS['maxuserlength'])), 'content', TRUE);

				return $action->execute($request);
				return TRUE;
			}

			if(strlen($_REQUEST['name']) > intval($_SETTINGS['maxuserlength'])) {
				$action = new K4InformationAction(new K4LanguageElement('L_USERNAMETOOLONG', intval($_SETTINGS['maxuserlength'])), 'content', TRUE);

				return $action->execute($request);
				return TRUE;
			}
			
			if($request['dba']->getValue("SELECT * FROM ". K4BADUSERNAMES ." WHERE name = '". $request['dba']->quote($_REQUEST['name']) ."'") > 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_BADNAMEEXISTS'), 'content', TRUE);

				return $action->execute($request);
				return TRUE;
			}

			$request['dba']->executeUpdate("INSERT INTO ". K4BADUSERNAMES ." (name) VALUES ('". $request['dba']->quote($_REQUEST['name']) ."')");
			
			$action = new K4InformationAction(new K4LanguageElement('L_ADDEDBADUSERNAME', $_REQUEST['name']), 'content', FALSE, 'admin.php?act=usernames', 3);

			
			return $action->execute($request);
		} else {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

			return $action->execute($request);
		}

		return TRUE;
	}
}

class AdminUpdateBadUserName extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			global $_SETTINGS;
			
			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDBADNAME'), 'content', FALSE);

				return $action->execute($request);
				return TRUE;
			}

			$bad		= $request['dba']->getRow("SELECT * FROM ". K4BADUSERNAMES ." WHERE id = ". intval($_REQUEST['id']));
			
			if(!is_array($bad) || empty($bad)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDBADNAME'), 'content', FALSE);

				return $action->execute($request);
				return TRUE;
			}

			if(!isset($_REQUEST['name']) || !$_REQUEST['name'] || $_REQUEST['name'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_SUPPLYBADUSERNAME'), 'content', TRUE);

				return $action->execute($request);
				return TRUE;
			}
			
			if(strlen($_REQUEST['name']) < intval($_SETTINGS['minuserlength'])) {
				$action = new K4InformationAction(new K4LanguageElement('L_USERNAMETOOSHORT', intval($_SETTINGS['minuserlength']), intval($_SETTINGS['maxuserlength'])), 'content', TRUE);

				return $action->execute($request);
				return TRUE;
			}

			if(strlen($_REQUEST['name']) > intval($_SETTINGS['maxuserlength'])) {
				$action = new K4InformationAction(new K4LanguageElement('L_USERNAMETOOLONG', intval($_SETTINGS['maxuserlength'])), 'content', TRUE);

				return $action->execute($request);
				return TRUE;
			}

			if($request['dba']->getValue("SELECT * FROM ". K4BADUSERNAMES ." WHERE name = '". $request['dba']->quote($_REQUEST['name']) ."' AND id <> ". intval($bad['id'])) > 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_BADNAMEEXISTS'), 'content', TRUE);

				return $action->execute($request);
				return TRUE;
			}

			$request['dba']->executeUpdate("UPDATE ". K4BADUSERNAMES ." SET name = '". $request['dba']->quote($_REQUEST['name']) ."' WHERE id = ". intval($bad['id']));
			
			$action = new K4InformationAction(new K4LanguageElement('L_UPDATEDBADUSERNAME', $bad['name'], $_REQUEST['name']), 'content', FALSE, 'admin.php?act=usernames', 3);

			
			return $action->execute($request);
		} else {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

			return $action->execute($request);
		}

		return TRUE;
	}
}

class AdminRemoveBadUserName extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			global $_SETTINGS;
			
			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDBADNAME'), 'content', FALSE);

				return $action->execute($request);
				return TRUE;
			}

			$bad		= $request['dba']->getRow("SELECT * FROM ". K4BADUSERNAMES ." WHERE id = ". intval($_REQUEST['id']));
			
			$request['dba']->executeUpdate("DELETE FROM ". K4BADUSERNAMES ." WHERE id = ". intval($bad['id']));
			
			$action = new K4InformationAction(new K4LanguageElement('L_REMOVEDBADUSERNAME', $bad['name']), 'content', FALSE, 'admin.php?act=usernames', 3);

			
			return $action->execute($request);
		} else {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

			return $action->execute($request);
		}

		return TRUE;
	}
}

?>