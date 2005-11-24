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
* @version $Id: member.php 154 2005-07-15 02:56:28Z Peter Goodman $
* @package k42
*/



require "includes/filearts/filearts.php";
require "includes/k4bb/k4bb.php";

class K4UserActionFilter extends FAFilter {
	function execute(&$action, &$request) {
		if ($request['event'] == 'profile' || $request['event'] == 'usercp') {
			$ret			= FALSE;
			$valid_user		= FALSE;

			$dba			= $request['dba'];
			$template		= $request['template'];
			$user_manager	= $request['user_manager'];

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
			k4_bread_crumbs($request['template'], $request['dba'], 'L_REGISTER');
			
			if(intval($request['template']->getVar('allowregistration')) == 0) {
				if(!USE_AJAX) {
					no_perms_error($request);
					return TRUE;
				} else {
					ajax_message('L_YOUNEEDPERMS');
					exit;
				}
			}

			if (!$request['user']->isMember()) {
				
				global $_PROFILEFIELDS, $_SETTINGS, $_URL, $_DATASTORE;
				
				/* If we are not allowed to register */
				if(isset($_SETTINGS['allowregistration']) && $_SETTINGS['allowregistration'] == 0) {
					$action = new K4InformationAction(new K4LanguageElement('L_CANTREGISTERADMIN'), 'content', FALSE);
					return !USE_AJAX ? TRUE : ajax_message('L_CANTREGISTERADMIN');
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
				
				/* Exit right here to send no content to the browser if ajax is enabled */
				if(USE_AJAX) exit;

				/**
				 * Do the database inserting
				 */
				
				$name						= htmlentities(strip_tags($_REQUEST['username']), ENT_QUOTES);
				$reg_key					= md5(uniqid(rand(), TRUE));

				$insert_a					= $request['dba']->prepareStatement("INSERT INTO ". K4USERS ." (name,email,pass,perms,reg_key,usergroups,created) VALUES (?,?,?,?,?,?,?)");
				
				$insert_a->setString(1, $name);
				$insert_a->setString(2, $_REQUEST['email']);
				$insert_a->setString(3, md5($_REQUEST['password']));
				$insert_a->setInt(4, '|'. PENDING_MEMBER .'|');
				$insert_a->setString(5, $reg_key);
				$insert_a->setString(6, '2'); // Registered Users
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
				
				/* Do we need to validate their email by having them follow a url? */
				if(intval($_SETTINGS['verifyemail']) == 1) {
					
					$verify_url				= $_URL;
					$verify_url->args		= array('act' => 'activate_accnt', 'key' => $reg_key);
					$verify_url->file		= 'member.php';
					$url					= str_replace('&amp;', '&', $verify_url->__toString());

					$request['dba']->executeUpdate("UPDATE ". K4USERS ." SET usergroups = '1' WHERE id = ". intval($user_id));

					$email					= sprintf($request['template']->getVar('L_REGISTEREMAILRMSG'), $name, $_SETTINGS['bbtitle'], $url, $_SETTINGS['bbtitle']);
						
					$action = new K4InformationAction(new K4LanguageElement('L_SUCCESSREGISTEREMAIL', $_SETTINGS['bbtitle'], $_REQUEST['email']), 'content', FALSE, 'index.php', 5);
					//return $action->execute($request);
				} else {
					$request['dba']->executeUpdate("UPDATE ". K4USERS ." SET perms = ". MEMBER .", priv_key = '', reg_key = '' WHERE id = ". intval($user_id));
										
					$action = new K4InformationAction(new K4LanguageElement('L_SUCCESSREGISTER', $_SETTINGS['bbtitle']), 'content', FALSE, 'index.php', 5);
					//return $action->execute($request);

					$email					= sprintf($request['template']->getVar('L_REGISTEREMAILMSG'), $name, $_SETTINGS['bbtitle'], $_SETTINGS['bbtitle']);
				}

				/* Finally, mail our user */
				email_user($_REQUEST['email'], sprintf($request['template']->getVar('L_REGISTEREMAILTITLE'), $_SETTINGS['bbtitle']), $email);
				
				return TRUE;
			} else {
				$action = new K4InformationAction(new K4LanguageElement('L_CANTREGISTERLOGGEDIN'), 'content', FALSE, 'index.php', 3);
				return TRUE;
			}

			return FALSE;
		}
	}
}

class K4ProfileAction extends FAAction {
	function execute(&$request) {
		
		global $_QUERYPARAMS, $_DATASTORE, $_USERGROUPS, $_PROFILEFIELDS;
		
		/* unset any search queries if we are about to go look at this users posts */
		unset($_SESSION['search_queries']);	
		
		if($request['user']->get('perms') < get_map( 'member_profile','can_view',array())) {
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
			$request['template']->setFile('content', '../login_form.html');
			$request['template']->setVisibility('no_perms', TRUE);
			return TRUE;
		}

		$member						= $request['user_info'];
		
		$member['num_topics']		= $request['dba']->getValue("SELECT COUNT(*) FROM ". K4TOPICS ." WHERE poster_id = ". intval($member['id']) ." AND moved_new_topic_id=0 AND is_draft=0 AND queue=0 AND display=1");
		$member['num_replies']		= $request['dba']->getValue("SELECT COUNT(*) FROM ". K4REPLIES ." WHERE poster_id = ". intval($member['id']));
		
		/**
		 * Get and set some user/forum statistics
		 */
		$num_reg_days				= ceil((time() - $member['created']) / 86400);
		$postsperday				= ceil($member['num_posts'] / $num_reg_days);
		
		$member['posts_per_day']	= sprintf($request['template']->getVar('L_POSTSPERDAY'), $postsperday );

		$num_posts					= ($_DATASTORE['forumstats']['num_topics'] + $_DATASTORE['forumstats']['num_replies']);
		$member['posts_percent']	= $num_posts != 0 && $member['num_posts'] != 0 ? sprintf($request['template']->getVar('L_OFTOTALPOSTS'), round((($member['num_posts'] / $num_posts) * 100), 3) .'%' ) : sprintf($request['template']->getVar('L_OFTOTALPOSTS'), '0%');
		
		$group						= get_user_max_group($member, $_USERGROUPS);
		$member['group_color']		= !isset($group['color']) || $group['color'] == '' ? '000000' : $group['color'];
		$member['group_nicename']	= $group['nicename'];
		$member['group_avatar']		= $group['avatar'];

		$member['online']			= (time() - ini_get('session.gc_maxlifetime')) > $member['seen'] ? 'offline' : 'online';
		
		$result						= explode('|', $member['usergroups']);
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
		
		$it = &new FAArrayIterator($usergroups);
		$request['template']->setList('member_usergroups', $it);

		foreach($member as $key => $val)
			$request['template']->setVar('member_'. $key, $val);
		
		/**
		 * Get the custom user fields for this member
		 */
		$fields = array();
		foreach($_PROFILEFIELDS as $field) {
				
			if($field['display_profile'] == 1) {

				if(isset($member[$field['name']])) { //  && $member[$field['name']] != ''
					switch($field['inputtype']) {
						default:
						case 'text':
						case 'textarea':
						case 'select': {
							$field['value']		= $member[$field['name']] != '' ? $member[$field['name']] : $request['template']->getVar('L_NOINFORMATION');
							break;
						}
						case 'multiselect':
						case 'radio':
						case 'check': {
							$field['value']		= $member[$field['name']] != '' ? implode(", ", iif(!unserialize($member[$field['name']]), array(), unserialize($member[$field['name']]))) : $request['template']->getVar('L_NOINFORMATION');
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
			
			$it = &new FAArrayIterator($fields);
			$request['template']->setList('member_profilefields', $it);
		}
		
		/**
		 * Set the info we need
		 */
		$request['template']->setFile('content', 'member_profile.html');

		k4_bread_crumbs($request['template'], $dba, 'L_USERPROFILE');
		
		return TRUE;
	}
}

class K4RegisterAction extends FAAction {
	function execute(&$request) {
		
		/* Create the ancestors bar (if we run into any trouble */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_REGISTER');

		if (!$request['user']->isMember()) {
			
			global $_PROFILEFIELDS, $_SETTINGS;
			
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
		
			foreach($_PROFILEFIELDS as $field) {
				if($field['display_register'] == 1) {
					$fields[] = $field;
				}
			}
			
			$request['template']->setVar('regmessage', sprintf($request['template']->getVar('L_INORDERTOPOSTINFORUM'), $_SETTINGS['bbtitle']));
			
			$it = &new FAArrayIterator($fields);
			$request['template']->setList('profilefields', $it);
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

class K4MemberSortMenu extends FAFilter {
	function execute(&$request) {
		
		global $_URL;

		$url		= new FAUrl($_URL->__toString());

		$letters = array();
		
		$letter						= isset($_REQUEST['letter']) ? $_REQUEST['letter'] : '*';
		
		$url->args['act']			= 'list';
		$url->args['page']			= 1;
		$url->args['limit']			= 30;
		$url->args['letter']		= '*';
		$url->anchor				= FALSE;
		$url->host					= FALSE;
		$url->path					= FALSE;
		$url->user					= FALSE;
		$url->scheme				= FALSE;
		$url->file					= 'member.php';

		/* Push the star (*) symbol into the letters array */
		$letters[] = array('name' => '#', 'class' => iif($letter == '*', 'alt3', 'alt1'), 'action' => $url->__toString() );

		$self	= basename($_SERVER['PHP_SELF']);
		
		/* Populate the letters array with actual letters */
		foreach(range('A', 'Z') as $key => $val) {
			$url->args['letter']	= $val;
			$letters[]	= array('name' => $val, 'class' => (strtoupper($letter) == strtoupper($val) ? 'alt3' : 'alt1'), 'action' => $url->__toString());
		}

		/* Apply the letters */
		$it = &new FAArrayIterator($letters);
		$request['template']->setList('letters', $it);

		return TRUE;
	}
}

class K4MemberList extends FAAction {
	function execute(&$request) {
		
		global $_QUERYPARAMS, $_URL;
		
		/* Create the ancestors bar */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_MEMBERLIST');
		
		if(get_map( 'memberlist', 'can_view', array()) > $request['user']->get('perms')) {
			no_perms_error($request);
			return TRUE;
		}

		$letters	= &new K4MemberSortMenu();
		$letters->execute($request);
		
		$request['template']->setFile('content', 'memberlist.html');
		
		if(isset($_GET['letter']) && $_REQUEST['letter'] != '*') {
			$like	= $request['dba']->quote(strtolower($_REQUEST['letter'])) .'%';
			$letter	= strtolower($_REQUEST['letter']);
		} else {
			$letter	= '*';
			$like	= '%';
		}
		
		$orders		= array('name', 'created', 'last_seen');
		
		$page				= isset($_REQUEST['page']) && ctype_digit($_REQUEST['page']) && intval($_REQUEST['page']) > 0 ? intval($_REQUEST['page']) : 1;
		$limit		= isset($_REQUEST['limit']) ? intval($_REQUEST['limit']) : intval($request['template']->getVar('memberlistperpage'));
		//$start		= isset($_REQUEST['start']) ? intval($_REQUEST['start']) : 0;
		$start		= ($limit * $page) - $limit;
		$sort		= isset($_REQUEST['order']) && in_array($_REQUEST['order'], $orders) ? $_REQUEST['order'] : 'id';
		$order		= isset($_REQUEST['order']) && $_REQUEST['order'] == 'DESC' ? 'DESC' : 'ASC';

		$num_results	= $request['dba']->getValue("SELECT COUNT(*) FROM ". K4USERS ." WHERE name LIKE '{$like}'");
		$result			= $request['dba']->executeQuery("SELECT ". $_QUERYPARAMS['user'] . $_QUERYPARAMS['userinfo'] ." FROM ". K4USERS ." u LEFT JOIN ". K4USERINFO ." ui ON u.id=ui.user_id WHERE name LIKE '{$like}' ORDER BY $sort $order LIMIT $start,$limit");
		
		$url		= new FAUrl($_URL->__toString());
		
		/* Create the Pagination */
		$num_pages			= ceil($num_results / $limit);
		$pager				= &new FAPaginator($url, $num_results, $page, $limit);
		
		if($num_results > $limit) {
			$request['template']->setPager('memberlist_pager', $pager);

			/* Create a friendly url for our pager jump */
			$page_jumper	= new FAUrl($_URL->__toString());
			$page_jumper->args['limit'] = $limit;
			$page_jumper->args['page']	= FALSE;
			$page_jumper->anchor		= FALSE;
			$request['template']->setVar('pagejumper_url', preg_replace('~&amp;~i', '&', $page_jumper->__toString()));
		}
				
		$it			= new MemberListIterator($result);
		
		$request['template']->setVar('ml_letter', $letter);
		$request['template']->setVar('ml_sort', $sort);
		$request['template']->setVar('ml_order', $order);
		$request['template']->setVar('ml_limit', $limit);
		$request['template']->setList('memberlist', $it);
		
		return TRUE;
	}
}

class MemberListIterator extends FAProxyIterator {
	var $result;
	
	function MemberListIterator(&$result) {
		$this->__construct($result);
	}

	function __construct(&$result) {
		
		global $_USERGROUPS;

		$this->result			= &$result;
		$this->groups			= $_USERGROUPS;
		
		parent::__construct($this->result);
	}

	function current() {
		$temp					= parent::current();
			
		$group						= get_user_max_group($temp, $this->groups);
		$temp['group_color']		= (!isset($group['color']) || $group['color'] == '') ? '000000' : $group['color'];
		$temp['group_nicename']		= $group['nicename'];
		$temp['group_avatar']		= $group['avatar'];
		$temp['online']				= (time() - ini_get('session.gc_maxlifetime')) > $temp['seen'] ? 'offline' : 'online';
		
		/* Should we free the result? */
		if(!$this->hasNext())
			$this->result->free();

		return $temp;
	}
}

/**
 * Show the form to resend a members password to their email
 */
class ResendPasswordForm extends FAAction {
	function execute(&$request) {
		
		/* Create the ancestors bar */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_SENDPASSWORD');
		
		/* Check if the user is logged in or not */
		if($request['user']->isMember()) {
			
			no_perms_error($request);
		
		} else {
			$request['template']->setFile('content', 'send_password.html');
		}
		
		return TRUE;
	}
}

/**
 * Reset a users password and send them an email about it
 */
class SendPasswordToUser extends FAAction {
	function execute(&$request) {
		
		/* Create the ancestors bar */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_SENDPASSWORD');
		
		/* Check if the user is logged in or not */
		if($request['user']->isMember()) {
			no_perms_error($request);
			return TRUE;
		}
		
		if(!$this->runPostFilter('name', new FARequiredFilter)) {
			$action = new K4InformationAction(new K4LanguageElement('L_SUPPLYUSERNAME'), 'content', TRUE);
			return $action->execute($request);
		}

		if(!$this->runPostFilter('email', new FARequiredFilter)) {
			$action = new K4InformationAction(new K4LanguageElement('L_SUPPLYEMAIL'), 'content', TRUE);
			return $action->execute($request);
		}

		if (!$this->runPostFilter('email', new FARegexFilter('~^([0-9a-zA-Z]+[-._+&])*[0-9a-zA-Z]+@([-0-9a-zA-Z]+[.])+[a-zA-Z]{2,6}$~'))) {
			$action = new K4InformationAction(new K4LanguageElement('L_NEEDVALIDEMAIL'), 'content', TRUE);
			return $action->execute($request);
		}

		$user = $request['dba']->getRow("SELECT * FROM ". K4USERS ." WHERE name = '". $request['dba']->quote(htmlentities($_REQUEST['name'], ENT_QUOTES)) ."'");
		
		if(!is_array($user) || empty($user)) {
			$action = new K4InformationAction(new K4LanguageElement('L_INVALIDUSERNAMESPW', htmlentities($_REQUEST['name'], ENT_QUOTES)), 'content', TRUE);
			return $action->execute($request);
		}

		if($user['email'] != $_REQUEST['email']) {
			$action = new K4InformationAction(new K4LanguageElement('L_INVALIDEMAILSPW', $_REQUEST['email']), 'content', TRUE);
			return $action->execute($request);
		}
		
		$newpass = substr(md5(uniqid(rand(), true)), 0, (intval($request['template']->getVar('minuserlength')) > 8 ? intval($request['template']->getVar('minuserlength')) : 8));

		$request['dba']->executeUpdate("UPDATE ". K4USERS ." SET pass = '". md5($newpass) ."' WHERE id = ". intval($user['id']));
		
		email_user($user['email'], $request['template']->getVar('bbtitle') .' - '. $request['template']->getVar('L_PASSWORDCHANGE'), sprintf($request['template']->getVar('L_PASSWORDCHANGEEMAIL'), $user['name'], $newpass));
		
		$action = new K4InformationAction(new K4LanguageElement('L_SENTNEWPASSWORD'), 'content', TRUE);
		return $action->execute($request);
	}
}

/**
 * Display the form to resend the validation email
 */
class ResendValidationEmail extends FAAction {
	function execute(&$request) {
		
		/* Create the ancestors bar */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_RESENDVALIDATIONEMAIL');
		
		/* Check if the user is logged in or not */
		if($request['user']->isMember()) {
			
			no_perms_error($request);
		
		} else {
			$request['template']->setFile('content', 'resend_validation_email.html');
		}
		
		return TRUE;
	}
}

/**
 * Reset a users registration validation link
 */
class SendValidationEmailToUser extends FAAction {
	function execute(&$request) {
		
		/* Create the ancestors bar */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_RESENDVALIDATIONEMAIL');
		
		/* Check if the user is logged in or not */
		if($request['user']->isMember()) {
			no_perms_error($request);
			return TRUE;
		}
		
		if(!$this->runPostFilter('email', new FARequiredFilter)) {
			$action = new K4InformationAction(new K4LanguageElement('L_SUPPLYEMAIL'), 'content', TRUE);
			return $action->execute($request);
		}

		if (!$this->runPostFilter('email', new FARegexFilter('~^([0-9a-zA-Z]+[-._+&])*[0-9a-zA-Z]+@([-0-9a-zA-Z]+[.])+[a-zA-Z]{2,6}$~'))) {
			$action = new K4InformationAction(new K4LanguageElement('L_NEEDVALIDEMAIL'), 'content', TRUE);
			return $action->execute($request);
		}

		$user = $request['dba']->getRow("SELECT * FROM ". K4USERS ." WHERE email = '". $request['dba']->quote($_REQUEST['email']) ."'");
		
		if(!is_array($user) || empty($user)) {
			$action = new K4InformationAction(new K4LanguageElement('L_INVALIDEMAILRVE', $_REQUEST['email']), 'content', TRUE);
			return $action->execute($request);
		}
		if($user['reg_key'] == '') {
			$action = new K4InformationAction(new K4LanguageElement('L_USERREGGEDRVE'), 'content', TRUE);
			return $action->execute($request);
		}
		
		//  .'/member.php?act=activate_accnt&key='. $user['reg_key']

		$url					= new FAUrl(K4_URL);
		$url->file				= 'member.php';
		$url->args				= array('act' => 'activate_accnt', 'key' => $user['reg_key']);

		$email					= sprintf($request['template']->getVar('L_REGISTEREMAILRMSG'), $user['name'], $request['template']->getVar('bbtitle'), str_replace('&amp;', '&', $url->__toString()), $request['template']->getVar('bbtitle'));
		
		email_user($user['email'], $request['template']->getVar('bbtitle') .' - '. $request['template']->getVar('L_RESENDVALIDATIONEMAIL'), $email);
		
		$action = new K4InformationAction(new K4LanguageElement('L_RESENTREGEMAIL', $_REQUEST['email']), 'content', TRUE);
		return $action->execute($request);
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

/* User Control Panel */
$app->setAction('usercp', new K4UserControlPanel);
$app->setAction('update_profile', new K4UpdateUserProfile);
$app->setAction('update_password', new K4UpdateUserPassword);
$app->setAction('update_email', new K4UpdateUserEmail);
$app->setAction('validate_email', new K4ValidateChangedEmail);
$app->setAction('update_options', new K4UpdateUserOptions);
$app->setAction('update_signature', new K4UpdateUserSignature);
$app->setAction('update_avatar', new K4UpdateUserFile('avatar', 'avatar', 'avatar', K4AVATARS));
$app->setAction('update_picture', new K4UpdateUserFile('personalpic', 'pp', 'picture', K4PPICTURES));

/* Private Messaging */
$app->setAction('insert_pmfolder', new K4InsertPMFolder);
$app->setAction('editpmfolder', new K4EditPMFolder);
$app->setAction('update_pmfolder', new K4UpdatePMFolder);
$app->setAction('pm_delchoosefolder', new K4PreDeleteFolder);
$app->setAction('pm_savemessage', new K4SendPMessage);
$app->setAction('pm_movemessages', new K4MovePMessages);

$app->setAction('list', new K4MemberList);

/* User problem stuff */
$app->setAction('forgotpw', new ResendPasswordForm);
$app->setAction('sendpassword', new SendPasswordToUser);
$app->setAction('resendemail', new ResendValidationEmail);
$app->setAction('sendvalidationemail', new SendValidationEmailToUser);

//$app->setAction('forgotpw', new );

$app->execute();

?>