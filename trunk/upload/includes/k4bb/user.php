<?php
/**
* k4 Bulletin Board, user.php
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
* @author Geoffrey Goodman
* @version $Id: user.php 158 2005-07-18 02:55:30Z Peter Goodman $
* @package k42
*/

if (!defined(IN_K4))
	return;

/**
 * Get someone's user titles
 */
function get_user_title($user_title, $num_posts) {
	
	if($user_title == '') {

		global $_USERTITLES;
		
		$user_title = '';

		for($i = 0; $i < count($_USERTITLES); $i++) {
			if(intval($num_posts) >= intval($_USERTITLES[$i]['num_posts'])) {
				$user_title = $_USERTITLES[$i]['final_title'];
				break;
			}
		}
	}

	return $user_title;
}

/**
 * Get the highest permissioned group that a user belongs to
 */
function get_user_max_group($temp, $all_groups) {
	$result				= explode('|', $temp['usergroups']);
	$groups				= $temp['usergroups'] != '' ? (!$result ? array() : $result) : array();
	
	if(is_array($groups)) {
		
		/**
		 * Loop through all of the groups and all of this users groups
		 * Find the one with the highest permission and use it as the color
		 * for this person's username. The avatar is separate because not all
		 * groups will automatically have avatars, so get the highest possible
		 * set avatar for this user.
		 */
		foreach($groups as $g) {
			
			/* If the group variable isn't set, set it */
			if(!isset($group) && isset($all_groups[$g]))
				$group	= $all_groups[$g];
			
			if(!isset($avatar) && isset($all_groups[$g]) && $all_groups[$g]['avatar'] != '')
				$avatar	= $all_groups[$g]['avatar'];

			/**
			 * If the perms of this group are greater than that of the $group 'prev group', 
			 * set is as this users group 
			 */
			if(isset($all_groups[$g]['max_perm']) && isset($group['max_perm']) && ($all_groups[$g]['max_perm'] > $group['max_perm'])) {
				$group	= $all_groups[$g];
				
				/* Give this user an appropriate group avatar */
				if($all_groups[$g]['avatar'] != '')
					$avatar	= $all_groups[$g]['avatar'];
			}
		}
	}
	
	$group['avatar']		= isset($avatar) ? $avatar : '';

	return $group;
}

/**
 * Get the color corresponding to a users warning level
 */
function get_warning_color($curr_level) {
	$color			= 'FFFFFF';
	if($curr_level == 1) {
		$color		= 'FFFF00'; // yellow
	} else if($curr_level == 2) {
		$color		= 'FF9900'; // orange
	} else if($curr_level == 3) {
		$color		= 'FF0000'; // red
	} else if($curr_level >= 4) {
		$color		= '000000';
	}

	return $color;
}

/**
 * Email a user with the proper noreply email address
 */
function email_user($to, $subject, $message, $from = 'noreply', $headers = "") {
	global $_URL, $_SETTINGS;

	$verify_url					= new FAUrl($_URL->__toString());
	$verify_url->args			= array();
	$verify_url->file			= FALSE;
	$verify_url->anchor			= FALSE;
	$verify_url->scheme			= FALSE;
	$verify_url->path			= FALSE;
	$verify_url->host			= preg_replace('~www\.~i', '', $verify_url->host);
		
	return @mail($to, $subject, $message, "From: \"". $_SETTINGS['bbtitle'] ." Forums\" <". $from ."@". substr($verify_url->__toString(), 0, -1) .">" . $headers);
}

/**
 * Set a user a logged in
 */
function k4_set_login(&$dba, &$user, $remember) {

	// TODO: change last_seen in k4_users to last_login
	$stmt = $dba->prepareStatement("UPDATE ". K4USERS ." SET seen=?,last_seen=?,priv_key=? WHERE id=?");

	$seen = time();
	$priv_key = md5(uniqid(microtime()));

	$stmt->setInt(1, $seen);
	$stmt->setInt(2, $user->get('seen'));
	$stmt->setString(3, $priv_key);
	$stmt->setInt(4, $user->get('id'));
	$stmt->executeUpdate();

	$user->updateInfo(array('seen' => $seen, 'last_seen' => $user->get('seen'), 'priv_key' => $priv_key));

	if ($remember) {
		$expire = time() + (3600 * 24 * 30);

		setcookie(K4COOKIE_ID, $user->get('id'), $expire, get_domain());
		setcookie(K4COOKIE_KEY, $priv_key, $expire, get_domain());
	}

	$_SESSION['user'] = &$user;
}

function k4_set_logout(&$dba, &$user) {
	
	$stmt	= $dba->prepareStatement("UPDATE ". K4USERS ." SET seen=?,priv_key='' WHERE id=?");

	$seen	= time();

	$stmt->setInt(1, $seen);
	$stmt->setInt(2, $user->get('id'));
	$stmt->executeUpdate();

	$expire = time() - (3600 * 24);

	setcookie(K4COOKIE_ID, '', $expire, get_domain());
	setcookie(K4COOKIE_KEY, '', $expire, get_domain());
	
	//unset($_SESSION['user']);

	$user = new K4Guest();
}

/**
 * Function to force the usergroups out of a malformed serialized array
 */
function force_usergroups($user) {

	/* Auto-set our groups array so we can default back on it */
	$groups = array();
	
	/* If the usergroups variable is not equal to nothing */
	if(isset($user['usergroups']) && $user['usergroups'] != '') {
		
//		/* Look for something that identifies the scope of this serialized array */
//		preg_match("~\{(.*?)\}~ise", $user['usergroups'], $matches);
//
//		/* Check the results of our search */
//		if(is_array($matches) && isset($matches[1])) {
//			
//			/* Explode the matched value into its parts */
//			$parts	= explode(";", $matches[1]);
//			
//			if(count($parts) > 0) {
//				for($i = 0; $i < count($parts); $i++) {
//					preg_match("~i\:([0-9])\;i\:([0-9])~is", $parts[$i], $_matches);
//					
//					/** 
//					 * If the number of matches is greater than 3, means that there is 1 key and 1 val 
//					 * at least 
//					 */
//					if(count($_matches) > 3) {
//
//						/* loop through the matches, skip [0] because it represents the pattern */
//						for($i = 1; $i < count($_matches); $i++) {
//							
//							/**
//							 * This will remove this usergroup, and any ninexistant ones from this 
//							 * user's array 
//							 */
//							if($_matches[$i+1] != $group['id'] && $_matches[$i+1] != 0) {
//								$groups[$_matches[$i]] = $_matches[$i+1];
//							}
//
//							/* Increment, (+1) so that we always increment by odd numbers */
//							$i++;
//						}
//					}
//				}
//			}
//		}
		
		$groups	= explode('|', $user['usergroups']);
	}

	return $groups;
}

/**
 * Function to check if a user belongs to a usergroup
 */
function is_in_group($my_groups, $groups, $my_perms) {
	
	if($my_perms >= ADMIN)
		return TRUE;

	$my_groups			= !is_array($my_groups) ? explode('|', $my_groups) : $my_groups;
	$groups				= !is_array($groups) ? explode('|', $groups) : $groups;
	
	if(is_array($my_groups) && is_array($groups) && !empty($my_groups)) {
		foreach($my_groups as $group_id) {
			if(in_array($group_id, $groups)) {
				return TRUE;
			}
		}
	}

	return FALSE;
}

/**
 * Function to check if a user is a moderator of a forum
 */
function is_moderator($user, $forum) {
	global $_USERGROUPS;
	
	if(is_a($user, 'FAUser')) {
		$user		= $user->getInfoArray();
	} 
	if(!is_array($user)) {
		trigger_error('Invalid $user call for is_moderator.', E_USER_ERROR);
	}
	
	if($user['perms'] >= ADMIN)
		return TRUE;

	$result				= explode('|', $forum['moderating_groups']);
	$moderators			= !$result ? force_usergroups($forum['moderating_groups']) : $result;
	
					
	$groups				= array();

	foreach($moderators as $g) {
		if(isset($_USERGROUPS[$g]))
			$groups[]	= $g;
	}
	
	if(isset($user['usergroups'])) {
		
		$unserialize		= explode('|', $user['usergroups']);
		$my_groups			= !$unserialize ? force_usergroups($user['usergroups']) : $unserialize;

		/* Do we toggle our moderator's panel? */
		if(is_in_group($my_groups, $groups, $user['perms'])) {
			return TRUE;
		}
	}

	if($forum['moderating_users'] != '') {
		$users					= unserialize($forum['moderating_users']);
		if(is_array($users)) {
			foreach($users as $user_id => $username) {
				if($user['name'] == $username && $user['id'] == $user_id)
					return TRUE;
			}
		}
	}

	return FALSE;
}

class K4Guest extends FAUser {
	function guestInfo() {
		global $_SPIDERS, $_SPIDERAGENTS;
		
		$info	= array('name' => '', 'email' => '', 'id' => 0, 'usergroups' => '', 'perms' => 1, 'styleset' => '', 'topicsperpage' => 0, 'postsperpage' => 0, 'viewavatars' => 0,'viewflash'=>1,'viewemoticons'=>1,'viewsigs'=>1,'viewavatars'=> 1,'viewimages'=>1,'viewcensors'=>1,'invisible'=>0,'seen'=>time(),'last_seen'=>time(),'spider'=>FALSE);
		
		/* Check if this person is a search engine */
		if(preg_match("~(". $_SPIDERAGENTS .")~is", USER_AGENT)) {
			foreach($_SPIDERS as $spider) {
				if(eregi($spider['useragent'], USER_AGENT)) {
					$info['name']	= $spider['spidername'];
					$info['perms']	= $spider['allowaccess'] == 1 ? 1 : -1;
					$info['spider']	= TRUE;
				}
			}
		}

		return $info;
	}
	function __construct() {
		parent::__construct($this->guestInfo());
	}
}

class K4Member extends FAMember {
}

class K4UserManager extends FAObject {
	var $_info;
	
	function K4UserManager(&$dba) {
		$this->__construct($dba);
	}

	function __construct(&$dba) {
		global $_QUERYPARAMS;
		
		$this->_info = $dba->prepareStatement("SELECT {$_QUERYPARAMS['user']}{$_QUERYPARAMS['userinfo']}{$_QUERYPARAMS['usersettings']} FROM ". K4USERS ." u, ". K4USERINFO ." ui, ". K4USERSETTINGS ." us WHERE u.id=ui.user_id AND us.user_id=u.id AND u.id=? LIMIT 1");
	}

	function getInfo($id) {
		$ret = FALSE;

		$this->_info->setInt(1, $id);

		$result = $this->_info->executeQuery();

		if ($result->next())
			$ret = $result->current();

		return $ret;
	}
}

class K4CookieValidator extends FAUserValidator {
	var $_stmt;
	
	function K4CookieValidator(&$dba) {
		$this->__construct($dba);
	}

	function __construct(&$dba) {
		global $_QUERYPARAMS;

		$this->_stmt = $dba->prepareStatement("SELECT {$_QUERYPARAMS['user']}{$_QUERYPARAMS['userinfo']}{$_QUERYPARAMS['usersettings']} FROM ". K4USERS ." u, ". K4USERINFO ." ui, ". K4USERSETTINGS ." us WHERE u.id=ui.user_id AND us.user_id=u.id AND u.id=? AND u.priv_key=? LIMIT 1");
	}

	function validateLoginKey() {
		$ret = FALSE;

		if (isset($_COOKIE[K4COOKIE_ID], $_COOKIE[K4COOKIE_KEY])) {
			
			$this->_stmt->setInt(1, $_COOKIE[K4COOKIE_ID]);
			$this->_stmt->setString(2, $_COOKIE[K4COOKIE_KEY]);

			$result = $this->_stmt->executeQuery();

			if ($result->next())
				$ret = $result->current();
		}

		return $ret;
	}
}

class K4RequestValidator extends FAUserValidator {
	var $_stmt;
	
	function K4RequestValidator(&$dba) {
		$this->__construct($dba);
	}

	function __construct(&$dba) {
		global $_QUERYPARAMS;

		$this->_stmt = $dba->prepareStatement("SELECT {$_QUERYPARAMS['user']}{$_QUERYPARAMS['userinfo']}{$_QUERYPARAMS['usersettings']} FROM ". K4USERS ." u, ". K4USERINFO ." ui, ". K4USERSETTINGS ." us WHERE u.id=ui.user_id AND us.user_id=u.id AND u.name=? AND u.pass=? LIMIT 1");
	}

	function validateLoginKey() {
		$ret = FALSE;

		if (isset($_POST['username'], $_POST['password'])) {
			$this->_stmt->setString(1, $_POST['username']);
			$this->_stmt->setString(2, md5($_POST['password']));

			$result = $this->_stmt->executeQuery();

			if ($result->next())
				$ret = $result->current();
		}

		return $ret;
	}
}

class K4UserFactory extends FAUserFactory {

	function createGuest() {
		$ret = &new K4Guest();

		return $ret;
	}

	function createMember($info) {
		$ret = &new K4Member($info);
		return $ret;
	}
}

?>