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
* @version $Id: users.class.php 148 2005-07-11 16:04:28Z Peter Goodman $
* @package k42
*/

if(!defined('IN_K4')) {
	return;
}

class AdminUsers extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_USERS');
			$request['template']->setVar('users_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/users.html');

			//$request['template']->setFile('content', 'badnames_manage.html');
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminAddUser extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_USERS');
			$request['template']->setVar('users_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/users.html');
			
			$result = $request['dba']->executeQuery("SELECT * FROM ". K4STYLES);

			$request['template']->setList('languages', new FAArrayIterator(get_files(K4_BASE_DIR .'/lang/', TRUE)));
			$request['template']->setList('imagesets', new FAArrayIterator(get_files(BB_BASE_DIR .'/Images/', TRUE, FALSE, array('admin'))));
			$request['template']->setList('templatesets', new FAArrayIterator(get_files(BB_BASE_DIR .'/templates/', TRUE, FALSE, array('Archive','RSS'))));
			$request['template']->setList('stylesets', $result);
			
			$request['template']->setFile('content', 'users_add.html');
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminInsertUser extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_USERS');
			$request['template']->setVar('users_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/users.html');
			
			/* Collect the custom profile fields to display */
			$query_fields	= '';
			$query_params	= '';
		
			foreach($_PROFILEFIELDS as $field) {
				if($field['display_register'] == 1) {
					
					/* This insures that we only put in what we need to */
					if(isset($_REQUEST[$field['name']])) {
						
						switch($field['inputtype']) {
							default:
							case 'text':
							case 'textarea':
							case 'select': {
								if($_REQUEST[$field['name']] != '') {
									$query_fields	.= ', '. $field['name'];
									$query_params	.= ", '". $request['dba']->quote(htmlentities($_REQUEST[$field['name']], ENT_QUOTES)) ."'";
								}
								break;
							}
							case 'multiselect':
							case 'radio':
							case 'check': {
								if(is_array($_REQUEST[$field['name']]) && !empty($_REQUEST[$field['name']])) {
									$query_fields	.= ', '. $field['name'];
									$query_params	.= ", '". $request['dba']->quote(serialize($_REQUEST[$field['name']])) ."'";
								}
								break;
							}
						}						
					}
				}
			}
			
			/**
			 * Error checking
			 */

			/* Username checks */
			if (!$this->runPostFilter('username', new FARequiredFilter)) {
				$action = new K4InformationAction(new K4LanguageElement('L_BADUSERNAME'), 'content', TRUE);
				return !USE_AJAX ? TRUE : ajax_message('L_BADUSERNAME');
			}
			
			if (!$this->runPostFilter('username', new FARegexFilter('~^[a-zA-Z]([a-zA-Z0-9]*[-_ ]?)*[a-zA-Z0-9]*$~'))) {
				$action = new K4InformationAction(new K4LanguageElement('L_BADUSERNAME'), 'content', TRUE);
				return !USE_AJAX ? TRUE : ajax_message('L_BADUSERNAME');
			}
			if (!$this->runPostFilter('username', new FALengthFilter(intval($_SETTINGS['maxuserlength'])))) {
				$action = new K4InformationAction(new K4LanguageElement('L_USERNAMETOOLONG', intval($_SETTINGS['maxuserlength'])), 'content', TRUE);
				return !USE_AJAX ? TRUE : ajax_message('L_USERNAMETOOSHORT');
			}
			if (!$this->runPostFilter('username', new FALengthFilter(intval($_SETTINGS['maxuserlength']), intval($_SETTINGS['minuserlength'])))) {
				$action = new K4InformationAction(new K4LanguageElement('L_USERNAMETOOSHORT', intval($_SETTINGS['minuserlength']), intval($_SETTINGS['maxuserlength'])), 'content', TRUE);
				return !USE_AJAX ? TRUE : ajax_message(new K4LanguageElement('L_USERNAMETOOSHORT', intval($_SETTINGS['minuserlength']), intval($_SETTINGS['maxuserlength'])));
			}

			if($request['dba']->getValue("SELECT COUNT(*) FROM ". K4USERS ." WHERE name = '". $request['dba']->quote($_REQUEST['username']) ."'") > 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_USERNAMETAKEN'), 'content', TRUE);
				return !USE_AJAX ? TRUE : ajax_message('L_USERNAMETAKEN');
			}
			
			if($request['dba']->getValue("SELECT COUNT(*) FROM ". K4BADUSERNAMES ." WHERE name = '". $request['dba']->quote($_REQUEST['username']) ."'") > 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_USERNAMENOTGOOD'), 'content', TRUE);
				return !USE_AJAX ? TRUE : ajax_message('L_USERNAMENOTGOOD');
			}
			
			/* Check the appropriatness of the username */
			$name		= $_REQUEST['username'];
			replace_censors($name);

			if($name != $_REQUEST['username']) {
				$action = new K4InformationAction(new K4LanguageElement('L_INNAPROPRIATEUNAME'), 'content', TRUE);
				return !USE_AJAX ? TRUE : ajax_message('L_INNAPROPRIATEUNAME');
			}

			/* Password checks */
			if(!$this->runPostFilter('password', new FARequiredFilter)) {
				$action = new K4InformationAction(new K4LanguageElement('L_SUPPLYPASSWORD'), 'content', TRUE);
				return !USE_AJAX ? TRUE : ajax_message('L_SUPPLYPASSWORD');
			}

			if(!$this->runPostFilter('password2', new FARequiredFilter)) {
				$action = new K4InformationAction(new K4LanguageElement('L_SUPPLYPASSCHECK'), 'content', TRUE);
				return !USE_AJAX ? TRUE : ajax_message('L_SUPPLYPASSCHECK');
			}

			if(!$this->runPostFilter('password', new FACompareFilter('password2'))) {
				$action = new K4InformationAction(new K4LanguageElement('L_PASSESDONTMATCH'), 'content', TRUE);
				return !USE_AJAX ? TRUE : ajax_message('L_PASSESDONTMATCH');
			}
			
			/* Email checks */
			if(!$this->runPostFilter('email', new FARequiredFilter)) {
				$action = new K4InformationAction(new K4LanguageElement('L_SUPPLYEMAIL'), 'content', TRUE);
				return !USE_AJAX ? TRUE : ajax_message('L_SUPPLYEMAIL');
			}

			if(!$this->runPostFilter('email2', new FARequiredFilter)) {
				$action = new K4InformationAction(new K4LanguageElement('L_SUPPLYEMAILCHECK'), 'content', TRUE);
				return !USE_AJAX ? TRUE : ajax_message('L_SUPPLYEMAILCHECK');
			}

			if(!$this->runPostFilter('email', new FACompareFilter('email2'))) {
				$action = new K4InformationAction(new K4LanguageElement('L_EMAILSDONTMATCH'), 'content', TRUE);
				return !USE_AJAX ? TRUE : ajax_message('L_EMAILSDONTMATCH');
			}

			if (!$this->runPostFilter('email', new FARegexFilter('~^([0-9a-zA-Z]+[-._+&])*[0-9a-zA-Z]+@([-0-9a-zA-Z]+[.])+[a-zA-Z]{2,6}$~'))) {
				$action = new K4InformationAction(new K4LanguageElement('L_NEEDVALIDEMAIL'), 'content', TRUE);
				return !USE_AJAX ? TRUE : ajax_message('L_NEEDVALIDEMAIL');
			}

			if($_SETTINGS['requireuniqueemail'] == 1) {
				if($request['dba']->getValue("SELECT COUNT(*) FROM ". K4USERS ." WHERE email = '". $request['dba']->quote($_REQUEST['email']) ."'") > 0) {
					$action = new K4InformationAction(new K4LanguageElement('L_EMAILTAKEN'), 'content', TRUE);
					return !USE_AJAX ? TRUE : ajax_message('L_EMAILTAKEN');
				}
			}

			$usergroups					= isset($_REQUEST['usergroups']) && is_array($_REQUEST['usergroups']) ? $_REQUEST['usergroups'] : array(2);
			
			$name						= htmlentities(strip_tags($_REQUEST['username']), ENT_QUOTES);
			$reg_key					= md5(uniqid(rand(), TRUE));

			$insert_a					= $request['dba']->prepareStatement("INSERT INTO ". K4USERS ." (name,email,pass,perms,reg_key,usergroups,created) VALUES (?,?,?,?,?,?,?)");
			
			$insert_a->setString(1, $name);
			$insert_a->setString(2, $_REQUEST['email']);
			$insert_a->setString(3, md5($_REQUEST['password']));
			$insert_a->setInt(4, PENDING_MEMBER);
			$insert_a->setString(5, $reg_key);
			$insert_a->setString(6, implode('|', $usergroups)); // Registered Users
			$insert_a->setInt(7, time());
			
			$insert_a->executeUpdate();
			
			$user_id					= intval($request['dba']->getInsertId(K4USERS, 'id'));

			$insert_b					= $request['dba']->prepareStatement("INSERT INTO ". K4USERINFO ." (user_id,timezone". $query_fields .") VALUES (?,?". $query_params .")");
			$insert_b->setInt(1, $user_id);
			$insert_b->setInt(2, intval(@$_REQUEST['timezone']));

			$request['dba']->executeUpdate("INSERT INTO ". K4USERSETTINGS ." (user_id) VALUES (". $user_id .")");

			$insert_b->executeUpdate();
			
			$datastore_update	= $request['dba']->prepareStatement("UPDATE ". K4DATASTORE ." SET data=? WHERE varname=?");
			
			/* Set the datastore values */
			$datastore					= $_DATASTORE['forumstats'];
			$datastore['num_members']	= $request['dba']->getValue("SELECT COUNT(*) FROM ". K4USERS);
			
			$datastore_update->setString(1, serialize($datastore));
			$datastore_update->setString(2, 'forumstats');

			$datastore_update->executeUpdate();

			reset_cache('datastore');

		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminBadUserNames extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			$badnames	= $request['dba']->executeQuery("SELECT * FROM ". K4BADUSERNAMES ." ORDER BY name ASC");
			$request['template']->setList('badnames', $badnames);
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_DISALLOWNAMES');
			$request['template']->setVar('users_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/users.html');

			$request['template']->setFile('content', 'badnames_manage.html');
		} else {
			no_perms_error($request);
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
			}
			
			if(strlen($_REQUEST['name']) < intval($_SETTINGS['minuserlength'])) {
				$action = new K4InformationAction(new K4LanguageElement('L_USERNAMETOOSHORT', intval($_SETTINGS['minuserlength']), intval($_SETTINGS['maxuserlength'])), 'content', TRUE);
				return $action->execute($request);
			}

			if(strlen($_REQUEST['name']) > intval($_SETTINGS['maxuserlength'])) {
				$action = new K4InformationAction(new K4LanguageElement('L_USERNAMETOOLONG', intval($_SETTINGS['maxuserlength'])), 'content', TRUE);
				return $action->execute($request);
			}
			
			if($request['dba']->getValue("SELECT * FROM ". K4BADUSERNAMES ." WHERE name = '". $request['dba']->quote($_REQUEST['name']) ."'") > 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_BADNAMEEXISTS'), 'content', TRUE);
				return $action->execute($request);
			}

			$request['dba']->executeUpdate("INSERT INTO ". K4BADUSERNAMES ." (name) VALUES ('". $request['dba']->quote($_REQUEST['name']) ."')");
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_DISALLOWNAMES');
			$request['template']->setVar('users_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/users.html');

			$action = new K4InformationAction(new K4LanguageElement('L_ADDEDBADUSERNAME', $_REQUEST['name']), 'content', FALSE, 'admin.php?act=usernames', 3);
			return $action->execute($request);

		} else {
			no_perms_error($request);
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
			}

			$bad		= $request['dba']->getRow("SELECT * FROM ". K4BADUSERNAMES ." WHERE id = ". intval($_REQUEST['id']));
			
			if(!is_array($bad) || empty($bad)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDBADNAME'), 'content', FALSE);
				return $action->execute($request);
			}

			if(!isset($_REQUEST['name']) || !$_REQUEST['name'] || $_REQUEST['name'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_SUPPLYBADUSERNAME'), 'content', TRUE);
				return $action->execute($request);
			}
			
			if(strlen($_REQUEST['name']) < intval($_SETTINGS['minuserlength'])) {
				$action = new K4InformationAction(new K4LanguageElement('L_USERNAMETOOSHORT', intval($_SETTINGS['minuserlength']), intval($_SETTINGS['maxuserlength'])), 'content', TRUE);
				return $action->execute($request);
			}

			if(strlen($_REQUEST['name']) > intval($_SETTINGS['maxuserlength'])) {
				$action = new K4InformationAction(new K4LanguageElement('L_USERNAMETOOLONG', intval($_SETTINGS['maxuserlength'])), 'content', TRUE);
				return $action->execute($request);
			}

			if($request['dba']->getValue("SELECT * FROM ". K4BADUSERNAMES ." WHERE name = '". $request['dba']->quote($_REQUEST['name']) ."' AND id <> ". intval($bad['id'])) > 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_BADNAMEEXISTS'), 'content', TRUE);
				return $action->execute($request);
			}

			$request['dba']->executeUpdate("UPDATE ". K4BADUSERNAMES ." SET name = '". $request['dba']->quote($_REQUEST['name']) ."' WHERE id = ". intval($bad['id']));
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_DISALLOWNAMES');
			$request['template']->setVar('users_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/users.html');

			$action = new K4InformationAction(new K4LanguageElement('L_UPDATEDBADUSERNAME', $bad['name'], $_REQUEST['name']), 'content', FALSE, 'admin.php?act=usernames', 3);
			return $action->execute($request);

		} else {
			no_perms_error($request);
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
			}

			$bad		= $request['dba']->getRow("SELECT * FROM ". K4BADUSERNAMES ." WHERE id = ". intval($_REQUEST['id']));
			
			if(!is_array($bad) || empty($bad)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDBADNAME'), 'content', FALSE);
				return $action->execute($request);
			}
			
			$request['dba']->executeUpdate("DELETE FROM ". K4BADUSERNAMES ." WHERE id = ". intval($bad['id']));
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_DISALLOWNAMES');
			$request['template']->setVar('users_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/users.html');

			$action = new K4InformationAction(new K4LanguageElement('L_REMOVEDBADUSERNAME', $bad['name']), 'content', FALSE, 'admin.php?act=usernames', 3);
			return $action->execute($request);

		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

?>