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
			$request['template']->setVar('users_action', 'admin.php?act=users_insert');
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
			
			global $_PROFILEFIELDS, $_SETTINGS;

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
									$query_params	.= ", '". $request['dba']->quote(k4_htmlentities($_REQUEST[$field['name']], ENT_QUOTES)) ."'";
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
			if (!$this->runPostFilter('uname', new FARequiredFilter)) {
				$action = new K4InformationAction(new K4LanguageElement('L_BADUSERNAME'), 'content', TRUE);
			}
			
			if (!$this->runPostFilter('uname', new FARegexFilter('~^[a-zA-Z]([a-zA-Z0-9]*[-_ ]?)*[a-zA-Z0-9]*$~'))) {
				$action = new K4InformationAction(new K4LanguageElement('L_BADUSERNAME'), 'content', TRUE);
			}
			if (!$this->runPostFilter('uname', new FALengthFilter(intval($_SETTINGS['maxuserlength'])))) {
				$action = new K4InformationAction(new K4LanguageElement('L_USERNAMETOOLONG', intval($_SETTINGS['maxuserlength'])), 'content', TRUE);
			}
			if (!$this->runPostFilter('uname', new FALengthFilter(intval($_SETTINGS['maxuserlength']), intval($_SETTINGS['minuserlength'])))) {
				$action = new K4InformationAction(new K4LanguageElement('L_USERNAMETOOSHORT', intval($_SETTINGS['minuserlength']), intval($_SETTINGS['maxuserlength'])), 'content', TRUE);
			}

			if($request['dba']->getValue("SELECT COUNT(*) FROM ". K4USERS ." WHERE name = '". $request['dba']->quote($_REQUEST['uname']) ."'") > 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_USERNAMETAKEN'), 'content', TRUE);
			}
			
			if($request['dba']->getValue("SELECT COUNT(*) FROM ". K4BADUSERNAMES ." WHERE name = '". $request['dba']->quote($_REQUEST['uname']) ."'") > 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_USERNAMENOTGOOD'), 'content', TRUE);
			}
			
			if(isset($action)) return $action->execute($request);

			/* Check the appropriatness of the username */
			$name		= $_REQUEST['uname'];
			replace_censors($name);

			if($name != $_REQUEST['uname']) {
				$action = new K4InformationAction(new K4LanguageElement('L_INNAPROPRIATEUNAME'), 'content', TRUE);
			}

			/* Password checks */
			if(!$this->runPostFilter('pass', new FARequiredFilter)) {
				$action = new K4InformationAction(new K4LanguageElement('L_SUPPLYPASSWORD'), 'content', TRUE);
			}

			if(!$this->runPostFilter('pass2', new FARequiredFilter)) {
				$action = new K4InformationAction(new K4LanguageElement('L_SUPPLYPASSCHECK'), 'content', TRUE);
			}

			if(!$this->runPostFilter('pass', new FACompareFilter('pass2'))) {
				$action = new K4InformationAction(new K4LanguageElement('L_PASSESDONTMATCH'), 'content', TRUE);
			}
			
			/* Email checks */
			if(!$this->runPostFilter('email', new FARequiredFilter)) {
				$action = new K4InformationAction(new K4LanguageElement('L_SUPPLYEMAIL'), 'content', TRUE);
			}

			if(!$this->runPostFilter('email2', new FARequiredFilter)) {
				$action = new K4InformationAction(new K4LanguageElement('L_SUPPLYEMAILCHECK'), 'content', TRUE);
			}

			if(!$this->runPostFilter('email', new FACompareFilter('email2'))) {
				$action = new K4InformationAction(new K4LanguageElement('L_EMAILSDONTMATCH'), 'content', TRUE);
			}

			if (!$this->runPostFilter('email', new FARegexFilter('~^([0-9a-zA-Z]+[-._+&])*[0-9a-zA-Z]+@([-0-9a-zA-Z]+[.])+[a-zA-Z]{2,6}$~'))) {
				$action = new K4InformationAction(new K4LanguageElement('L_NEEDVALIDEMAIL'), 'content', TRUE);
			}

			if($_SETTINGS['requireuniqueemail'] == 1) {
				if($request['dba']->getValue("SELECT COUNT(*) FROM ". K4USERS ." WHERE email = '". $request['dba']->quote($_REQUEST['email']) ."'") > 0) {
					$action = new K4InformationAction(new K4LanguageElement('L_EMAILTAKEN'), 'content', TRUE);
				}
			}

			/**
			 * Get the custom user fields for this member
			 */
			foreach($_PROFILEFIELDS as $field) {
				if($field['is_editable'] == 1) {
					if(isset($_REQUEST[$field['name']]) && $_REQUEST[$field['name']] != '') {
						$query	.= $field['name'] ." = '". $request['dba']->quote(preg_replace("~(\r\n|\r|\n)~", '<br />', $_REQUEST[$field['name']])) ."', ";
						if($field['is_required'] == 1 && @$_REQUEST[$field['name']] == '') {
							$error .= '<br />'. $request['template']->getVar('L_REQUIREDFIELDS') .': <strong>'. $field['title'] .'</strong>';
						}
					}
				}
			}

			// error checking... too late? nah.
			if($error != '') {
				$action = new K4InformationAction(new K4LanguageElement('L_FOLLOWINGERRORS', $error), 'usercp_content', FALSE);
			}

			if(isset($action)) return $action->execute($request);
			
			/**
			 *
			 * Add User
			 *
			 */

			$usergroups					= isset($_REQUEST['usergroups']) && is_array($_REQUEST['usergroups']) ? $_REQUEST['usergroups'] : array(2);
			
			$name						= k4_htmlentities(strip_tags($_REQUEST['uname']), ENT_QUOTES);
			$reg_key					= md5(uniqid(rand(), TRUE));

			$insert_a					= $request['dba']->prepareStatement("INSERT INTO ". K4USERS ." (name,email,pass,perms,reg_key,usergroups,created) VALUES (?,?,?,?,?,?,?)");
			
			$insert_a->setString(1, $name);
			$insert_a->setString(2, $_REQUEST['email']);
			$insert_a->setString(3, md5($_REQUEST['pass']));
			$insert_a->setInt(4, $_REQUEST['permissions']);
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
			
			
			/**
			 *
			 * User Profile
			 *
			 */
			
			$query			= "UPDATE ". K4USERINFO ." SET ";
			$error			= '';
			
			$fields			= array('fullname', 'icq', 'aim', 'msn', 'yahoo', 'jabber', 'googletalk');

			foreach($fields as $field) {
				if(isset($_REQUEST[$field]) && $_REQUEST[$field] != '') {
					$query		.= $field ." = '". $request['dba']->quote($_REQUEST[$field]) ."', ";
				}
			}

			// deal with the timezone
			if(isset($_REQUEST['timezone']) && $_REQUEST['timezone'] != '') {
				$query		.= "timezone = ". intval($_REQUEST['timezone']) .", ";
			}
						
			// could this check get any uglier/more stupid?
			$birthday = '';
			if(isset($_REQUEST['month']) && isset($_REQUEST['day']) && isset($_REQUEST['year'])) {
				if((intval($_REQUEST['month']) != 0 && ctype_digit($_REQUEST['month'])) && (intval($_REQUEST['day']) != 0 && ctype_digit($_REQUEST['day'])) && (intval($_REQUEST['year']) != 0 && ctype_digit($_REQUEST['year']))) {
					
					$birthday = $request['dba']->quote($_REQUEST['month'] .'/'. $_REQUEST['day'] .'/'. $_REQUEST['year']);
					$birthday = strlen($birthday) == 10 ? $birthday : '';

				}
			}
			
			// finish off this query
			$query			.= "birthday = '". $birthday ."' WHERE user_id = ". intval($user_id);
			
			/* Update the user */
			$request['dba']->executeUpdate($query);
			
			/**
			 *
			 * User Options
			 *
			 */

			/* Do half-checks on the styles/language stuff */
			$language		= !in_array($_REQUEST['language'], get_files(K4_BASE_DIR .'/lang/', TRUE, TRUE)) ? $request['user']->get('language') : $_REQUEST['language'];
			$imageset		= !in_array($_REQUEST['imageset'], get_files(BB_BASE_DIR .'/Images/', TRUE, TRUE)) ? $request['user']->get('imageset') : $_REQUEST['imageset'];
			$templateset	= !in_array($_REQUEST['templateset'], get_files(BB_BASE_DIR .'/templates/', TRUE, TRUE)) ? $request['user']->get('templateset') : $_REQUEST['templateset'];
			$styleset		= $request['dba']->getRow("SELECT * FROM ". K4STYLES ." WHERE id = ". intval($_REQUEST['styleset']) ." LIMIT 1");
			$styleset		= is_array($styleset) && !empty($styleset) ? $styleset['name'] : $request['user']->get('styleset');
					
			/* Change the users' invisible mode */
			if(isset($_REQUEST['invisible']) && (intval($_REQUEST['invisible']) == 0 || intval($_REQUEST['invisible']) == 1)&& intval($_REQUEST['invisible']) != $request['user']->get('invisible')) {
				$request['dba']->executeUpdate("UPDATE ". K4USERS ." SET invisible = ". intval($_REQUEST['invisible']) ." WHERE id = ". intval($request['user']->get('id')) );
			}
			
			/**
			 * Prepare the big query
			 */
			$query				= $request['dba']->prepareStatement("UPDATE ". K4USERSETTINGS ." SET templateset=?,styleset=?,imageset=?,language=?,topic_display=?,notify_pm=?,popup_pm=?,topicsperpage=?,postsperpage=?,viewimages=?,viewavatars=?,viewsigs=?,viewflash=?,viewemoticons=?,viewcensors=?,topic_threaded=? WHERE user_id = ?");
			$query->setString(1, $templateset);
			$query->setString(2, $styleset);
			$query->setString(3, $imageset);
			$query->setString(4, $language);
			$query->setInt(5, $_REQUEST['topic_display']);
			$query->setInt(6, $_REQUEST['notify_pm']);
			$query->setInt(7, $_REQUEST['popup_pm']);
			$query->setInt(8, $_REQUEST['topicsperpage']);
			$query->setInt(9, $_REQUEST['postsperpage']);
			$query->setInt(10, $_REQUEST['viewimages']);
			$query->setInt(11, $_REQUEST['viewavatars']);
			$query->setInt(12, $_REQUEST['viewsigs']);
			$query->setInt(13, $_REQUEST['viewflash']);
			$query->setInt(14, $_REQUEST['viewemoticons']);
			$query->setInt(15, $_REQUEST['viewcensors']);
			$query->setInt(16, $_REQUEST['topic_threaded']);
			$query->setInt(17, $user_id);

			$query->executeUpdate();
			
			/**
			 * 
			 * Datastore
			 *
			 */

			$datastore_update	= $request['dba']->prepareStatement("UPDATE ". K4DATASTORE ." SET data=? WHERE varname=?");

			/* Set the datastore values */
			$datastore					= $_DATASTORE['forumstats'];
			$datastore['num_members']	= $request['dba']->getValue("SELECT COUNT(*) FROM ". K4USERS);
			$datastore['newest_user_id'] = $user_id;
			$datastore['newest_user_name'] = $name;
			
			$datastore_update->setString(1, serialize($datastore));
			$datastore_update->setString(2, 'forumstats');
			
			$datastore_update->executeUpdate();

			reset_cache('datastore');

			$action = new K4InformationAction(new K4LanguageElement('L_ADDEDUSER', $_REQUEST['name']), 'content', FALSE, 'admin.php?act=users', 3);
			return $action->execute($request);

		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminEditUser extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			global $_QUERYPARAMS;

			k4_bread_crumbs($request['template'], $request['dba'], 'L_USERS');
			$request['template']->setVar('users_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/users.html');
			
			$result = $request['dba']->executeQuery("SELECT * FROM ". K4STYLES);

			$request['template']->setList('languages', new FAArrayIterator(get_files(K4_BASE_DIR .'/lang/', TRUE)));
			$request['template']->setList('imagesets', new FAArrayIterator(get_files(BB_BASE_DIR .'/Images/', TRUE, FALSE, array('admin'))));
			$request['template']->setList('templatesets', new FAArrayIterator(get_files(BB_BASE_DIR .'/templates/', TRUE, FALSE, array('Archive','RSS'))));
			$request['template']->setList('stylesets', $result);

			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_USERDOESNTEXIST'), 'content', TRUE);
				return $action->execute($request);
			}
			
			$user = $request['dba']->getRow("SELECT {$_QUERYPARAMS['user']}{$_QUERYPARAMS['userinfo']}{$_QUERYPARAMS['usersettings']} FROM ". K4USERS ." u, ". K4USERINFO ." ui, ". K4USERSETTINGS ." us WHERE u.id=ui.user_id AND us.user_id=u.id AND u.id=". intval($_REQUEST['id']) ." LIMIT 1");
			
			if(!is_array($user) || empty($user)) {
				$action = new K4InformationAction(new K4LanguageElement('L_USERDOESNTEXIST'), 'content', TRUE);
				return $action->execute($request);
			}

			foreach($user as $key => $val)
				$request['template']->setVar('edit_user_'. $key, $val);

			$request['template']->setVar('is_edit', 1);
			
			$request['template']->setVar('users_action', 'admin.php?act=users_update');
			$request['template']->setFile('content', 'users_add.html');
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminUpdateUser extends FAAction {
		function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			global $_PROFILEFIELDS, $_SETTINGS;

			k4_bread_crumbs($request['template'], $request['dba'], 'L_USERS');
			$request['template']->setVar('users_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/users.html');
			
			if(!isset($_REQUEST['user_id']) || intval($_REQUEST['user_id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_USERDOESNTEXIST'), 'content', TRUE);
				return $action->execute($request);
			}
			
			$user = $request['dba']->getRow("SELECT * FROM ". K4USERS ." WHERE id=". intval($_REQUEST['user_id']) ." LIMIT 1");
			
			if(!is_array($user) || empty($user)) {
				$action = new K4InformationAction(new K4LanguageElement('L_USERDOESNTEXIST'), 'content', TRUE);
				return $action->execute($request);
			}
			
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
									$query_fields	.= ', '. $field['name'] ."='". $request['dba']->quote(k4_htmlentities($_REQUEST[$field['name']], ENT_QUOTES)) ."'";
								}
								break;
							}
							case 'multiselect':
							case 'radio':
							case 'check': {
								if(is_array($_REQUEST[$field['name']]) && !empty($_REQUEST[$field['name']])) {
									$query_fields	.= ','. $field['name'] ."='". $request['dba']->quote(serialize($_REQUEST[$field['name']])) ."'";
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
			if (!$this->runPostFilter('uname', new FARequiredFilter)) {
				$action = new K4InformationAction(new K4LanguageElement('L_BADUSERNAME'), 'content', TRUE);
			}
			
			if (!$this->runPostFilter('uname', new FARegexFilter('~^[a-zA-Z]([a-zA-Z0-9]*[-_ ]?)*[a-zA-Z0-9]*$~'))) {
				$action = new K4InformationAction(new K4LanguageElement('L_BADUSERNAME'), 'content', TRUE);
			}
			if (!$this->runPostFilter('uname', new FALengthFilter(intval($_SETTINGS['maxuserlength'])))) {
				$action = new K4InformationAction(new K4LanguageElement('L_USERNAMETOOLONG', intval($_SETTINGS['maxuserlength'])), 'content', TRUE);
			}
			if (!$this->runPostFilter('uname', new FALengthFilter(intval($_SETTINGS['maxuserlength']), intval($_SETTINGS['minuserlength'])))) {
				$action = new K4InformationAction(new K4LanguageElement('L_USERNAMETOOSHORT', intval($_SETTINGS['minuserlength']), intval($_SETTINGS['maxuserlength'])), 'content', TRUE);
			}
			
			if($_REQUEST['uname'] != $user['name']) {
				if($request['dba']->getValue("SELECT COUNT(*) FROM ". K4USERS ." WHERE name = '". $request['dba']->quote($_REQUEST['uname']) ."'") > 0) {
					$action = new K4InformationAction(new K4LanguageElement('L_USERNAMETAKEN'), 'content', TRUE);
				}
			}
			
			if($request['dba']->getValue("SELECT COUNT(*) FROM ". K4BADUSERNAMES ." WHERE name = '". $request['dba']->quote($_REQUEST['uname']) ."'") > 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_USERNAMENOTGOOD'), 'content', TRUE);
			}
			
			if(isset($action)) return $action->execute($request);

			/* Check the appropriatness of the username */
			$name		= $_REQUEST['uname'];
			replace_censors($name);

			if($name != $_REQUEST['uname']) {
				$action = new K4InformationAction(new K4LanguageElement('L_INNAPROPRIATEUNAME'), 'content', TRUE);
			}
			
			/* Email checks */
			if(!$this->runPostFilter('email', new FARequiredFilter)) {
				$action = new K4InformationAction(new K4LanguageElement('L_SUPPLYEMAIL'), 'content', TRUE);
			}
			
			if (!$this->runPostFilter('email', new FARegexFilter('~^([0-9a-zA-Z]+[-._+&])*[0-9a-zA-Z]+@([-0-9a-zA-Z]+[.])+[a-zA-Z]{2,6}$~'))) {
				$action = new K4InformationAction(new K4LanguageElement('L_NEEDVALIDEMAIL'), 'content', TRUE);
			}
			
			if($_SETTINGS['requireuniqueemail'] == 1 && $_REQUEST['email'] != $user['email']) {
				if($request['dba']->getValue("SELECT COUNT(*) FROM ". K4USERS ." WHERE email = '". $request['dba']->quote($_REQUEST['email']) ."'") > 0) {
					$action = new K4InformationAction(new K4LanguageElement('L_EMAILTAKEN'), 'content', TRUE);
				}
			}

			/**
			 * Get the custom user fields for this member
			 */
			foreach($_PROFILEFIELDS as $field) {
				if($field['is_editable'] == 1) {
					if(isset($_REQUEST[$field['name']]) && $_REQUEST[$field['name']] != '') {
						$query	.= $field['name'] ." = '". $request['dba']->quote(preg_replace("~(\r\n|\r|\n)~", '<br />', $_REQUEST[$field['name']])) ."', ";
						if($field['is_required'] == 1 && @$_REQUEST[$field['name']] == '') {
							$error .= '<br />'. $request['template']->getVar('L_REQUIREDFIELDS') .': <strong>'. $field['title'] .'</strong>';
						}
					}
				}
			}

			// error checking... too late? nah.
			if($error != '') {
				$action = new K4InformationAction(new K4LanguageElement('L_FOLLOWINGERRORS', $error), 'usercp_content', FALSE);
			}

			if(isset($action)) return $action->execute($request);
			
			/**
			 *
			 * Update User
			 *
			 */

			$usergroups					= isset($_REQUEST['usergroups']) && is_array($_REQUEST['usergroups']) ? $_REQUEST['usergroups'] : array(2);
			
			$name						= k4_htmlentities(strip_tags($_REQUEST['uname']), ENT_QUOTES);
			$reg_key					= md5(uniqid(rand(), TRUE));

			$insert_a					= $request['dba']->prepareStatement("UPDATE ". K4USERS ." SET name=?,email=?,perms=?,usergroups=? WHERE id=?");
			
			$insert_a->setString(1, $name);
			$insert_a->setString(2, $_REQUEST['email']);
			$insert_a->setInt(3, $_REQUEST['permissions']);
			$insert_a->setString(4, implode('|', $usergroups)); // Registered Users
			$insert_a->setInt(5, $user['id']);
			
			$insert_a->executeUpdate();
			
			$insert_b					= $request['dba']->prepareStatement("UPDATE ". K4USERINFO ." SET timezone=? ". $query_fields ." WHERE user_id=?");
			$insert_b->setInt(1, intval(@$_REQUEST['timezone']));
			$insert_b->setInt(2, $user['id']);
			
			$insert_b->executeUpdate();
			
			
			/**
			 *
			 * User Profile
			 *
			 */
			
			$query			= "UPDATE ". K4USERINFO ." SET ";
			$error			= '';
			
			$fields			= array('fullname', 'icq', 'aim', 'msn', 'yahoo', 'jabber', 'googletalk');

			foreach($fields as $field) {
				if(isset($_REQUEST[$field]) && $_REQUEST[$field] != '') {
					$query		.= $field ."='". $request['dba']->quote($_REQUEST[$field]) ."', ";
				}
			}

			// deal with the timezone
			if(isset($_REQUEST['timezone']) && $_REQUEST['timezone'] != '') {
				$query		.= "timezone = ". intval($_REQUEST['timezone']) .", ";
			}
						
			// could this check get any uglier/more stupid?
			$birthday = '';
			if(isset($_REQUEST['month']) && isset($_REQUEST['day']) && isset($_REQUEST['year'])) {
				if((intval($_REQUEST['month']) != 0 && ctype_digit($_REQUEST['month'])) && (intval($_REQUEST['day']) != 0 && ctype_digit($_REQUEST['day'])) && (intval($_REQUEST['year']) != 0 && ctype_digit($_REQUEST['year']))) {
					
					$birthday = $request['dba']->quote($_REQUEST['month'] .'/'. $_REQUEST['day'] .'/'. $_REQUEST['year']);
					$birthday = strlen($birthday) == 10 ? $birthday : '';

				}
			}
			
			// finish off this query
			$query			.= "birthday = '". $birthday ."' WHERE user_id = ". intval($user['id']);
			
			/* Update the user */
			$request['dba']->executeUpdate($query);
			
			/**
			 *
			 * User Options
			 *
			 */

			/* Do half-checks on the styles/language stuff */
			$language		= !in_array($_REQUEST['language'], get_files(K4_BASE_DIR .'/lang/', TRUE, TRUE)) ? $request['user']->get('language') : $_REQUEST['language'];
			$imageset		= !in_array($_REQUEST['imageset'], get_files(BB_BASE_DIR .'/Images/', TRUE, TRUE)) ? $request['user']->get('imageset') : $_REQUEST['imageset'];
			$templateset	= !in_array($_REQUEST['templateset'], get_files(BB_BASE_DIR .'/templates/', TRUE, TRUE)) ? $request['user']->get('templateset') : $_REQUEST['templateset'];
			$styleset		= $request['dba']->getRow("SELECT * FROM ". K4STYLES ." WHERE id = ". intval($_REQUEST['styleset']) ." LIMIT 1");
			$styleset		= is_array($styleset) && !empty($styleset) ? $styleset['name'] : $request['user']->get('styleset');
					
			/* Change the users' invisible mode */
			if(isset($_REQUEST['invisible']) && (intval($_REQUEST['invisible']) == 0 || intval($_REQUEST['invisible']) == 1)&& intval($_REQUEST['invisible']) != $request['user']->get('invisible')) {
				$request['dba']->executeUpdate("UPDATE ". K4USERS ." SET invisible = ". intval($_REQUEST['invisible']) ." WHERE id = ". intval($request['user']->get('id')) );
			}
			
			/**
			 * Prepare the big query
			 */
			$query				= $request['dba']->prepareStatement("UPDATE ". K4USERSETTINGS ." SET templateset=?,styleset=?,imageset=?,language=?,topic_display=?,notify_pm=?,popup_pm=?,topicsperpage=?,postsperpage=?,viewimages=?,viewavatars=?,viewsigs=?,viewflash=?,viewemoticons=?,viewcensors=?,topic_threaded=? WHERE user_id = ?");
			$query->setString(1, $templateset);
			$query->setString(2, $styleset);
			$query->setString(3, $imageset);
			$query->setString(4, $language);
			$query->setInt(5, $_REQUEST['topic_display']);
			$query->setInt(6, $_REQUEST['notify_pm']);
			$query->setInt(7, $_REQUEST['popup_pm']);
			$query->setInt(8, $_REQUEST['topicsperpage']);
			$query->setInt(9, $_REQUEST['postsperpage']);
			$query->setInt(10, $_REQUEST['viewimages']);
			$query->setInt(11, $_REQUEST['viewavatars']);
			$query->setInt(12, $_REQUEST['viewsigs']);
			$query->setInt(13, $_REQUEST['viewflash']);
			$query->setInt(14, $_REQUEST['viewemoticons']);
			$query->setInt(15, $_REQUEST['viewcensors']);
			$query->setInt(16, $_REQUEST['topic_threaded']);
			$query->setInt(17, $user['id']);

			$query->executeUpdate();
			
			/**
			 * 
			 * Datastore
			 *
			 */
			
			if($_DATASTORE['forumstats']['newest_user_id'] == $user['id']) {
				$datastore_update	= $request['dba']->prepareStatement("UPDATE ". K4DATASTORE ." SET data=? WHERE varname=?");
				$datastore					= $_DATASTORE['forumstats'];
				$datastore['newest_user_name']	= $name;
				$datastore_update->setString(1, serialize($datastore));
				$datastore_update->setString(2, 'forumstats');
				$datastore_update->executeUpdate();
				reset_cache('datastore');
			}

			/**
			 *
			 * User Name
			 *
			 */
			
			if($name != $user['name']) {
				$request['dba']->executeUpdate("UPDATE ". K4POSTS ." SET poster_name='". $request['dba']->quote($name) ."' WHERE poster_id=". intval($user['id']));
				$request['dba']->executeUpdate("UPDATE ". K4POSTS ." SET edited_username='". $request['dba']->quote($name) ."' WHERE edited_userid=". intval($user['id']));
				$request['dba']->executeUpdate("UPDATE ". K4POSTS ." SET lastpost_uname='". $request['dba']->quote($name) ."' WHERE lastpost_uid=". intval($user['id']));
				$request['dba']->executeUpdate("UPDATE ". K4FORUMS ." SET post_uname='". $request['dba']->quote($name) ."' WHERE post_uid=". intval($user['id']));
				$request['dba']->executeUpdate("UPDATE ". K4POLLVOTES ." SET user_name='". $request['dba']->quote($name) ."' WHERE user_id=". intval($user['id']));
				$request['dba']->executeUpdate("UPDATE ". K4RATINGS ." SET user_name='". $request['dba']->quote($name) ."' WHERE user_id=". intval($user['id']));
				$request['dba']->executeUpdate("UPDATE ". K4USERGROUPS ." SET mod_name='". $request['dba']->quote($name) ."' WHERE mod_id=". intval($user['id']));
				$request['dba']->executeUpdate("UPDATE ". K4BADPOSTREPORTS ." SET user_name='". $request['dba']->quote($name) ."' WHERE user_id=". intval($user['id']));
				$request['dba']->executeUpdate("UPDATE ". K4BADPOSTREPORTS ." SET poster_name='". $request['dba']->quote($name) ."' WHERE poster_id=". intval($user['id']));
				$request['dba']->executeUpdate("UPDATE ". K4BANNEDUSERS ." SET user_name='". $request['dba']->quote($name) ."' WHERE user_id=". intval($user['id']));
				$request['dba']->executeUpdate("UPDATE ". K4PRIVMESSAGES ." SET poster_name='". $request['dba']->quote($name) ."' WHERE poster_id=". intval($user['id']));
				$request['dba']->executeUpdate("UPDATE ". K4PRIVMESSAGES ." SET member_name='". $request['dba']->quote($name) ."' WHERE member_id=". intval($user['id']));
			}
			
			/**
			 *
			 * DONE
			 *
			 */

			$action = new K4InformationAction(new K4LanguageElement('L_UPDATEDUSER', $name), 'content', FALSE, 'admin.php?act=users', 3);
			return $action->execute($request);

		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminFindUser extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_FINDUSER');
			$request['template']->setVar('users_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/users.html');

			$request['template']->setFile('content', 'users_find.html');
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminFetchFoundUsers extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			// use the moderator functions
			$mod_action = new ModFindUsers();
			$mod_action->execute($request);
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_FINDUSER');
			$request['template']->setVar('users_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/users.html');
			
			$request['template']->setFile('content', 'users_found.html');
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminBanUser extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			// use the moderator functions
			$mod_action = new ModBanUser();
			$mod_action->execute($request);
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_BANUSER');
			$request['template']->setVar('users_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/users.html');
			$request['template']->setFile('content', ($request['template']->getFile('content') == 'banuser.html' ? '../banuser.html' : 'finduser.html'));
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminWarnUser extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			// use the moderator functions
			$mod_action = new ModWarnUser();
			$mod_action->execute($request);
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_WARNUSER');
			$request['template']->setVar('users_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/users.html');
			$request['template']->setFile('content', ($request['template']->getFile('content') == 'warnuser.html' ? '../warnuser.html' : 'finduser.html'));
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminFlagUser extends FAAction {
	function execute(&$request) {		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			// use the moderator functions
			$mod_action = new ModFlagUser();
			$mod_action->execute($request);
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_FLAGUSER');
			$request['template']->setVar('users_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/users.html');
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