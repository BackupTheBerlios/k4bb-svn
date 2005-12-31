<?php
/**
* k4 Bulletin Board, titles.class.php
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
* @package k42
*/

class AdminUsertTitles extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			global $_USERTITLES;

			k4_bread_crumbs($request['template'], $request['dba'], 'L_USERTITLES');
			$request['template']->setVar('users_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/users.html');
			
			$request['template']->setList('usertitles', new FAArrayIterator($_USERTITLES));

			$request['template']->setFile('content', 'titles_manage.html');
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminAddUserTitle extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_USERTITLES');
			$request['template']->setVar('users_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/users.html');

			$request['template']->setFile('content', 'titles_add.html');
			$request['template']->setVar('edit_title', 0);
			$request['template']->setVar('usertitle_action', 'admin.php?act=titles_insert');
		} else {
			no_perms_error($request);
		}
		
		return TRUE;
	}
}

class AdminInsertUserTitle extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			if(!isset($_REQUEST['title_text']) || $_REQUEST['title_text'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTUTITLE'), 'content', TRUE);
				return $action->execute($request);
			}
						
			$title_text		= html_entity_decode($_REQUEST['title_text'], ENT_QUOTES);

			// add the user title
			$insert			= $request['dba']->prepareStatement("INSERT INTO ". K4USERTITLES ." (title_text,num_posts,num_pips,image) VALUES (?,?,?,?)");
			$insert->setString(1, $title_text);
			$insert->setInt(2, isset($_REQUEST['num_posts']) ? intval($_REQUEST['num_posts']) : 0);
			$insert->setInt(3, isset($_REQUEST['num_pips']) ? intval($_REQUEST['num_pips']) : 0);
			$insert->setString(4, isset($_REQUEST['image']) ? $_REQUEST['image'] : '');
			$insert->executeUpdate();
			
			reset_cache('user_titles');

			$action = new K4InformationAction(new K4LanguageElement('L_ADDEDUSERTITLE', $title_text), 'content', FALSE, 'admin.php?act=usertitles', 3);
			return $action->execute($request);
			
		} else {
			no_perms_error($request);
		}
		
		return TRUE;
	}
}

class AdminEditUserTitle extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_USERTITLES');
			$request['template']->setVar('users_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/users.html');
			
			if(!isset($_REQUEST['title_id']) || intval($_REQUEST['title_id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_BADUSERTITLE'), 'content', TRUE);
				return $action->execute($request);
			}

			$title = $request['dba']->getRow("SELECT * FROM ". K4USERTITLES ." WHERE title_id = ". intval($_REQUEST['title_id']));

			if(!is_array($title) || empty($title)) {
				$action = new K4InformationAction(new K4LanguageElement('L_BADUSERTITLE'), 'content', TRUE);
				return $action->execute($request);
			}

			foreach($title as $key => $val)
				$request['template']->setVar('title_'. $key, $val);

			$request['template']->setFile('content', 'titles_add.html');
			$request['template']->setVar('edit_title', 1);
			$request['template']->setVar('usertitle_action', 'admin.php?act=titles_update');
		} else {
			no_perms_error($request);
		}
		
		return TRUE;
	}
}

class AdminUpdateUserTitle extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			if(!isset($_REQUEST['title_id']) || intval($_REQUEST['title_id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_BADUSERTITLE'), 'content', TRUE);
				return $action->execute($request);
			}

			$title = $request['dba']->getRow("SELECT * FROM ". K4USERTITLES ." WHERE title_id = ". intval($_REQUEST['title_id']));

			if(!is_array($title) || empty($title)) {
				$action = new K4InformationAction(new K4LanguageElement('L_BADUSERTITLE'), 'content', TRUE);
				return $action->execute($request);
			}

			if(!isset($_REQUEST['title_text']) || $_REQUEST['title_text'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTUTITLE'), 'content', TRUE);
				return $action->execute($request);
			}

			
			$title_text		= html_entity_decode($_REQUEST['title_text'], ENT_QUOTES);

			// add the user title
			$update			= $request['dba']->prepareStatement("UPDATE ". K4USERTITLES ." SET title_text=?,num_posts=?,num_pips=?,image=? WHERE title_id=?");
			$update->setString(1, $title_text);
			$update->setInt(2, isset($_REQUEST['num_posts']) ? intval($_REQUEST['num_posts']) : 0);
			$update->setInt(3, isset($_REQUEST['num_pips']) ? intval($_REQUEST['num_pips']) : 0);
			$update->setString(4, isset($_REQUEST['image']) ? $_REQUEST['image'] : '');
			$update->setInt(5, $title['title_id']);
			$update->executeUpdate();
			
			reset_cache('user_titles');

			$action = new K4InformationAction(new K4LanguageElement('L_UPDATEDUSERTITLE', $title['title_text']), 'content', FALSE, 'admin.php?act=usertitles', 3);
			return $action->execute($request);
			
		} else {
			no_perms_error($request);
		}
		
		return TRUE;
	}
}

class AdminDeleteUserTitle extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_USERTITLES');
			$request['template']->setVar('users_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/users.html');
			
			if(!isset($_REQUEST['title_id']) || intval($_REQUEST['title_id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_BADUSERTITLE'), 'content', TRUE);
				return $action->execute($request);
			}

			$title = $request['dba']->getRow("SELECT * FROM ". K4USERTITLES ." WHERE title_id = ". intval($_REQUEST['title_id']));

			if(!is_array($title) || empty($title)) {
				$action = new K4InformationAction(new K4LanguageElement('L_BADUSERTITLE'), 'content', TRUE);
				return $action->execute($request);
			}
			
			$request['dba']->executeUpdate("DELETE FROM ". K4USERTITLES ." WHERE title_id = ". intval($title['title_id']));

			reset_cache('user_titles');

			$action = new K4InformationAction(new K4LanguageElement('L_UPDATEDUSERTITLE', $title['title_text']), 'content', FALSE, 'admin.php?act=usertitles', 3);
			return $action->execute($request);
			
		} else {
			no_perms_error($request);
		}
		
		return TRUE;
	}
}

class AdminUserTitleFindUsers extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			global $_QUERYPARAMS;

			k4_bread_crumbs($request['template'], $request['dba'], 'L_USERTITLES');
			$request['template']->setVar('users_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/users.html');

			// include the wildcards in the valid username match
			if(!isset($_REQUEST['username']) || $_REQUEST['username'] == '' || !preg_match('~^[a-zA-Z]([a-zA-Z0-9]*[-_ \*\%]?)*[a-zA-Z0-9]*$~', $_REQUEST['username'])) {
				$action = new K4InformationAction(new K4LanguageElement('L_USERDOESNTEXIST'), 'content', TRUE);
				return $action->execute($request);
			}

			$request['template']->setVar('search_name', $_REQUEST['username']);
			$username			= str_replace('*', '%', $request['dba']->quote($_REQUEST['username']));

			$result		= $request['dba']->executeQuery("SELECT ". $_QUERYPARAMS['user'] . $_QUERYPARAMS['userinfo'] ." FROM ". K4USERS ." u LEFT JOIN ". K4USERINFO ." ui ON u.id=ui.user_id WHERE lower(u.name) LIKE lower('%$username%') ORDER BY u.name ASC LIMIT 15");		
			$it			= new UsersIterator($result);

			$request['template']->setFile('content', 'titles_users.html');
			$request['template']->setList('matched_users', $it);
		} else {
			no_perms_error($request);
		}
		
		return TRUE;
	}
}

class AdminUserTitleUpdateUser extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_USERTITLES');
			$request['template']->setVar('users_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/users.html');

			if(!isset($_REQUEST['user_id']) || intval($_REQUEST['user_id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_USERDOESNTEXIST'), 'content', TRUE);
				return $action->execute($request);
			}

			$user		= $request['dba']->getRow("SELECT * FROM ". K4USERS ." WHERE id = ". intval($_REQUEST['user_id']));
			
			if(!is_array($user) || empty($user)) {
				$action = new K4InformationAction(new K4LanguageElement('L_USERDOESNTEXIST'), 'content', TRUE);
				return $action->execute($request);
			}
			
			$title		= isset($_REQUEST['user_title']) ? $_REQUEST['user_title'] : '';

			$request['dba']->executeUpdate("UPDATE ". K4USERINFO ." SET user_title = '". $request['dba']->quote($title) ."' WHERE user_id = ". intval($_REQUEST['user_id']));

			$action = new K4InformationAction(new K4LanguageElement('L_UPDATEDUSERTITLE', $user['name']), 'content', TRUE, 'admin.php?act=usertitles', 3);
			return $action->execute($request);
		} else {
			no_perms_error($request);
		}
		
		return TRUE;
	}
}

?>