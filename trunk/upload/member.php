<?php
/**
* k4 Bulletin Board, member.php
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
* @version $Id: member.php,v 1.5 2005/05/16 02:10:03 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

require "includes/filearts/filearts.php";
require "includes/k4bb/k4bb.php";

class K4UserActionFilter extends FAFilter {
	function execute(&$action, &$request) {
		if ($request['event'] == 'profile') {
			$ret = FALSE;
			$valid_user = FALSE;

			$dba = &$request['dba'];
			$template = &$request['template'];
			$user_manager = &$request['user_manager'];

			if ($this->runGetFilter('id', new FARequiredFilter)) {
				$info = $user_manager->getInfo($_GET['id']);

				if ($info != FALSE)
					$valid_user = TRUE;

			} else if ($request['user']->isMember()) {
				$info = $request['user']->getInfoArray();
				$valid_user = TRUE;
			} else {
				$valid_user = FALSE;
			}

			if (!$valid_user) {
				k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
				$action = new K4InformationAction(new K4LanguageElement('L_USERDOESNTEXIST'), 'content', TRUE);
				$ret = TRUE;
			} else {
				$request['user_info'] = $info;
			}

			return $ret;
		}
	}
}

class K4InsertUserFilter extends FAFilter {
	function execute(&$action, &$request) {
		if ($request['event'] == 'register_user') {
			
			/* Create the ancestors bar (if we run into any trouble */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_REGISTER');

			if (!$request['user']->isMember()) {
				
				global $_USERFIELDS, $_SETTINGS, $_URL, $_DATASTORE;
				
				/* If we are not allowed to register */
				if(isset($_SETTINGS['allowregistration']) && $_SETTINGS['allowregistration'] == 0) {
					$action = new K4InformationAction(new K4LanguageElement('L_CANTREGISTERADMIN'), 'content', FALSE);
					return TRUE;
				}
				
				/* Collect the custom profile fields to display */
				$query_fields	= '';
				$query_params	= '';
			
				foreach($_USERFIELDS as $field) {
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

				if (!$this->runPostFilter('name', new FARegexFilter('~^[a-zA-Z]([a-zA-Z0-9]*[-_ ]?)*[a-zA-Z0-9]+$~'))) {
					$action = new K4InformationAction(new K4LanguageElement('L_SUPPLYUSERNAME'), 'content', TRUE);

					return TRUE;
				}
				if (!$this->runPostFilter('name', new FALengthFilter(intval($_SETTINGS['maxuserlength'])))) {
					$action = new K4InformationAction(new K4LanguageElement('L_USERNAMETOOLONG', intval($_SETTINGS['maxuserlength'])), 'content', TRUE);

					return TRUE;
				}
				if (!$this->runPostFilter('name', new FALengthFilter(intval($_SETTINGS['maxuserlength']), intval($_SETTINGS['minuserlength'])))) {
					$action = new K4InformationAction(new K4LanguageElement('L_USERNAMETOOSHORT', intval($_SETTINGS['minuserlength']), intval($_SETTINGS['maxuserlength'])), 'content', TRUE);

					return TRUE;
				}

				if($request['dba']->getValue("SELECT COUNT(*) FROM ". K4USERS ." WHERE name = '". $request['dba']->quote($_REQUEST['name']) ."'") > 0) {
					$action = new K4InformationAction(new K4LanguageElement('L_USERNAMETAKEN'), 'content', TRUE);

					return TRUE;
				}
				
				if($request['dba']->getValue("SELECT COUNT(*) FROM ". K4BADUSERNAMES ." WHERE name = '". $request['dba']->quote($_REQUEST['name']) ."'") > 0) {
					$action = new K4InformationAction(new K4LanguageElement('L_USERNAMENOTGOOD'), 'content', TRUE);

					return TRUE;
				}
				
				/* Password checks */
				if(!$this->runPostFilter('pass', new FARequiredFilter)) {
					$action = new K4InformationAction(new K4LanguageElement('L_SUPPLYPASSWORD'), 'content', TRUE);

					return TRUE;
				}

				if(!$this->runPostFilter('pass2', new FARequiredFilter)) {
					$action = new K4InformationAction(new K4LanguageElement('L_SUPPLYPASSCHECK'), 'content', TRUE);

					return TRUE;
				}

				if(!$this->runPostFilter('pass', new FACompareFilter('pass2'))) {
					$action = new K4InformationAction(new K4LanguageElement('L_PASSESDONTMATCH'), 'content', TRUE);

					return TRUE;
				}
				
				/***********GOT HERE**********/

				/* Email checks */
				if(!$this->runPostFilter('email', new FARequiredFilter)) {
					$action = new K4InformationAction(new K4LanguageElement('L_SUPPLYEMAIL'), 'content', TRUE);

					return TRUE;
				}

				if(!$this->runPostFilter('email2', new FARequiredFilter)) {
					$action = new K4InformationAction(new K4LanguageElement('L_SUPPLYEMAILCHECK'), 'content', TRUE);

					return TRUE;
				}

				if(!$this->runPostFilter('email', new FACompareFilter('email2'))) {
					$action = new K4InformationAction(new K4LanguageElement('L_EMAILSDONTMATCH'), 'content', TRUE);

					return TRUE;
				}

				if (!$this->runPostFilter('email', new FARegexFilter('~^([0-9a-zA-Z]+[-._+&])*[0-9a-zA-Z]+@([-0-9a-zA-Z]+[.])+[a-zA-Z]{2,6}$~'))) {
					$action = new K4InformationAction(new K4LanguageElement('L_NEEDVALIDEMAIL'), 'content', TRUE);

					return TRUE;
				}

				if($_SETTINGS['requireuniqueemail'] == 1) {
					if($request['dba']->getValue("SELECT COUNT(*) FROM ". K4USERS ." WHERE email = '". $request['dba']->quote($_REQUEST['email']) ."'") > 0) {
						$action = new K4InformationAction(new K4LanguageElement('L_EMAILTAKEN'), 'content', TRUE);

						return TRUE;
					}
				}
				
				/**
				 * Do the database inserting
				 */
				
				$name						= htmlentities($_REQUEST['name'], ENT_QUOTES);
				$priv_key					= md5(microtime() + rand());

				$insert_a					= &$request['dba']->prepareStatement("INSERT INTO ". K4USERS ." (name,email,pass,perms,priv_key,usergroups,created) VALUES (?,?,?,?,?,?,?)");
				
				$insert_a->setString(1, $name);
				$insert_a->setString(2, $_REQUEST['email']);
				$insert_a->setString(3, md5($_REQUEST['pass']));
				$insert_a->setInt(4, PENDING_MEMBER);
				$insert_a->setString(5, $priv_key);
				$insert_a->setString(6, 'a:1:{i:0;i:2;}'); // Registered Users
				$insert_a->setInt(7, time());
				
				$insert_a->executeUpdate();
				
				$user_id					= $request['dba']->getInsertId();

				$insert_b					= &$request['dba']->prepareStatement("INSERT INTO ". K4USERINFO ." (user_id,timezone". $query_fields .") VALUES (?,?". $query_params .")");
				$insert_b->setInt(1, $user_id);
				$insert_b->setInt(2, intval(@$_REQUEST['timezone']));

				$insert_b->executeUpdate();
				
				$datastore_update	= &$request['dba']->prepareStatement("UPDATE ". K4DATASTORE ." SET data=? WHERE varname=?");
				
				/* Set the datastore values */
				$datastore					= $_DATASTORE['forumstats'];
				$datastore['num_members']	= $request['dba']->getValue("SELECT COUNT(*) FROM ". K4USERS);
				
				$datastore_update->setString(1, serialize($datastore));
				$datastore_update->setString(2, 'forumstats');

				$datastore_update->executeUpdate();

				if(!@touch(CACHE_DS_FILE, time()-86460)) {
					@unlink(CACHE_DS_FILE);
				}
				
				/* Do we need to validate their email by having them follow a url? */
				if(intval($_SETTINGS['verifyemail']) == 1) {
					
					$verify_url				= $_URL;
					$verify_url->args		= array('act' => 'activate_accnt', 'key' => $priv_key);
					$verify_url->file		= 'member.php';
					$url					= $verify_url->__toString();

					$request['dba']->executeUpdate("UPDATE ". K4USERS ." SET usergroups = 'a:1:{i:0;i:1;}' WHERE id = ". intval($user_id));

					$email					= sprintf($request['template']->getVar('L_REGISTEREMAILRMSG'), $_REQUEST['name'], $_SETTINGS['bbtitle'], $url, $_SETTINGS['bbtitle']);
						
					$action = new K4InformationAction(new K4LanguageElement('L_SUCCESSREGISTEREMAIL', $_SETTINGS['bbtitle'], $_REQUEST['email']), 'content', FALSE, 'index.php', 5);
					//return $action->execute($request);
				} else {
					$request['dba']->executeUpdate("UPDATE ". K4USERS ." SET perms = 5, priv_key = '' WHERE id = ". intval($user_id));
										
					$action = new K4InformationAction(new K4LanguageElement('L_SUCCESSREGISTEREMAIL', $_SETTINGS['bbtitle']), 'content', FALSE, 'index.php', 5);
					//return $action->execute($request);

					$email					= sprintf($request['template']->getVar('L_REGISTEREMAILMSG'), $_REQUEST['name'], $_SETTINGS['bbtitle'], $_SETTINGS['bbtitle']);
				}

				/* Send our email, make the url email looking ;) */
				$verify_url->args			= array();
				$verify_url->file			= FALSE;
				$verify_url->anchor			= FALSE;
				$verify_url->scheme			= FALSE;
				$verify_url->path			= FALSE;
				
				/* Finally, mail our user */
				@mail($_REQUEST['email'], sprintf($request['template']->getVar('L_REGISTEREMAILTITLE'), $_SETTINGS['bbtitle']), $email, "From: \"". $_SETTINGS['bbtitle'] ." Forums\" <noreply@". $verify_url->__toString() .">");
				
				return TRUE;
			} else {
				$action = new K4InformationAction('L_CANTREGISTERLOGGEDIN', 'content', FALSE, 'index.php', 3);
				return TRUE;
			}

			return FALSE;
		}
	}
}

class K4ProfileAction extends FAAction {
	function execute(&$request) {
		global $_QUERYPARAMS, $_DATASTORE, $_USERGROUPS, $_USERFIELDS;
			
		$template = &$request['template'];
		$dba = &$request['dba'];

		$member = $request['user_info'];
		
		$member['num_topics']		= $dba->getValue("SELECT COUNT(*) FROM ". K4TOPICS ." WHERE poster_id = ". intval($member['id']));
		$member['num_replies']		= $dba->getValue("SELECT COUNT(*) FROM ". K4REPLIES ." WHERE poster_id = ". intval($member['id']));
		
		/**
		 * Get and set some user/forum statistics
		 */
		$user_created				= (time() - iif($member['created'] != 0, $member['created'], time()));
		$postsperday				= $user_created != 0 ? round((($member['num_posts'] / ($user_created / 86400))), 3) : 0;
		
		$member['posts_per_day']	= sprintf($template->getVar('L_POSTSPERDAY'), $postsperday );

		$num_posts					= ($_DATASTORE['forumstats']['num_topics'] + $_DATASTORE['forumstats']['num_replies']);
		$member['posts_percent']	= $num_posts != 0 && $member['num_posts'] != 0 ? sprintf($template->getVar('L_OFTOTALPOSTS'), round((($member['num_posts'] / $num_posts) * 100), 3) .'%' ) : sprintf($template->getVar('L_OFTOTALPOSTS'), '0%');
		
		$group						= get_user_max_group($member, $_USERGROUPS);
		$member['group_color']		= !isset($group['color']) || $group['color'] == '' ? '000000' : $group['color'];
		
		$member['online']			= (time() - ini_get('session.gc_maxlifetime')) > $member['seen'] ? 'offline' : 'online';
		
		$result						= @unserialize(@$member['usergroups']);
		$groups						= $member['usergroups'] != '' ? (!$result ? force_usergroups($member) : $result) : array();
		

		/**
		 * Get and set the user groups for this member
		 */
		$usergroups					= array();
		foreach($groups as $id) {
			if(isset($_USERGROUPS[$id]) && is_array($_USERGROUPS[$id]) && !empty($_USERGROUPS[$id])) {
				$usergroups[]		= $_USERGROUPS[$id];
			}
		}

		$template->setList('member_usergroups', new FAArrayIterator($usergroups));

		foreach($member as $key => $val)
			$template->setVar('member_'. $key, $val);
		
		/**
		 * Get the custom user fields for this member
		 */
		$fields = array();
		foreach($_USERFIELDS as $field) {
				
			if($field['display_profile'] == 1) {

				if(isset($member[$field['name']]) && $member[$field['name']] != '') {
					switch($field['inputtype']) {
						default:
						case 'text':
						case 'textarea':
						case 'select': {
							$field['value']		= $member[$field['name']];
							break;
						}
						case 'multiselect':
						case 'radio':
						case 'check': {
							$field['value']		= implode(", ", iif(!unserialize($member[$field['name']]), array(), unserialize($member[$field['name']])));
							break;
						}
					}
					$fields[] = $field;
				}
			}
		}

		if(count($fields) > 0) {
			if($fields % 2 == 1) {
				$fields[count($fields)-1]['colspan'] = 2;
			}

			$template->setList('member_profilefields', new FAArrayIterator($fields));
		}
		
		/**
		 * Set the info we need
		 */
		$template->setFile('content', 'member_profile.html');

		k4_bread_crumbs($template, $dba, 'L_USERPROFILE');
		
		return TRUE;
	}
}

class K4RegisterAction extends FAAction {
	function execute(&$request) {
		
		/* Create the ancestors bar (if we run into any trouble */
		k4_bread_crumbs(&$request['template'], $request['dba'], 'L_REGISTER');

		if (!$request['user']->isMember()) {
			
			global $_USERFIELDS, $_SETTINGS;
			
			/* If we are not allowed to register */
			if(isset($_SETTINGS['allowregistration']) && $_SETTINGS['allowregistration'] == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_CANTREGISTERADMIN'), 'content', FALSE);
				return $action->execute($request);
			}
			
			/* If this person has yet to agree to the forum terms */
			if(!isset($_REQUEST['agreed']) || $_REQUEST['agreed'] != 'yes') {
				$request['template']->setFile('content', 'register_agree.html');

				// override the current location
				$request['template']->setVar('current_location', $request['template']->getVar('L_REGISTRATIONAGGREE'));
				return TRUE;
			}
			
			/* Collect the custom profile fields to display */
			$fields = array();
		
			foreach($_USERFIELDS as $field) {
				if($field['display_register'] == 1) {
					$fields[] = $field;
				}
			}
			
			$request['template']->setVar('regmessage', sprintf($request['template']->getVar('L_INORDERTOPOSTINFORUM'), $_SETTINGS['bbtitle']));

			$request['template']->setList('profilefields', new FAArrayIterator($fields));
			$request['template']->setFile('content', 'register.html');

			return TRUE;
		} else {
			$action = new K4InformationAction(new K4LanguageElement('L_CANTREGISTERLOGGEDIN'), 'content', FALSE);
			return $action->execute($request);
		}

		return FALSE;
	}
}

class ForumLogin extends FAAction {
	function execute(&$request) {
		
		/* Create the ancestors bar */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_LOGIN');
		
		/* Check if the user is logged in or not */
		if(!$request['user']->isMember()) {
			
			$request['template']->setVar('referer', referer());
			$request['template']->setFile('content', 'login_form.html');
		
		} else {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUARELOGGEDIN'), 'content', FALSE);
			return $action->execute($request);
		}
		
		return TRUE;
	}
}

/* Set our wrapper template */
$app	= new K4Controller('forum_base.html');

$app->addFilter(new K4UserActionFilter);
$app->addFilter(new K4InsertUserFilter);

$app->setAction('profile', new K4ProfileAction);
$app->setAction('register', new K4RegisterAction);
$app->setAction('login', new ForumLogin);
$app->setDefaultEvent('profile');

$app->setAction('activate_accnt', new ValidateUserByEmail);
$app->setAction('remindme', new RemindMeEvent);
$app->setAction('mail', new EmailUser);
$app->setAction('email_user', new SendEmailToUser);
$app->setAction('findposts', new FindPostsByUser);

//$app->setAction('forgotpw', new );

$app->execute();

?>