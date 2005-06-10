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
* @version $Id: users.class.php,v 1.11 2005/05/26 18:35:44 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	exit;
}

/**
 * Get the highest permissioned group that a user belongs to
 */
function get_user_max_group($temp, $all_groups) {
	$groups				= $temp['usergroups'] != '' ? iif(!unserialize($temp['usergroups']), array(), unserialize($temp['usergroups'])) : array();
			
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

function email_user($id) {
	if(ctype_digit($id) && intval($id) > 0) {

		global $_DBA, $lang, $_SETTINGS;

		$user		= $_DBA->getRow("SELECT * FROM ". K4USERS ." WHERE id = ". intval($id));

		if($user && is_array($user) && !empty($user)) {
			@mail($request['user']->get('email'), sprintf($lang('L_REGISTEREMAILTITLE'), $_SETTINGS['bbtitle']), $email, "From: \"". $_SETTINGS['bbtitle'] ." Forums\" <noreply@". $verify_url->__toString() .">");
		}
	}
}


class ValidateUserByEmail extends FAAction {
	function execute(&$request) {
		
		/* Create the ancestors bar (if we run into any trouble */
		k4_bread_crumbs(&$request['template'], $request['dba'], 'L_VALIDATEMEMBERSHIP');

		if (!$request['user']->isMember()) {
			
			global $_SETTINGS;
			
			if(!isset($_REQUEST['key']) || strlen($_REQUEST['key']) != 32) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDREGID'), 'content', FALSE);

				return $action->execute($request);
				return TRUE;
			}

			$u			= $request['dba']->getRow("SELECT * FROM ". K4USERS ." WHERE priv_key = '". $request['dba']->quote($_REQUEST['key']) ."' AND perms = ". intval(PENDING_MEMBER));

			if(!is_array($u) || empty($u)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDREGID'), 'content', FALSE);

				return $action->execute($request);
				return TRUE;
			}
			
			$request['dba']->executeUpdate("UPDATE ". K4USERS ." SET priv_key = '', perms = ". MEMBER .", usergroups = 'a:1:{i:0;i:2;}' WHERE id = ". intval($u['id']));
			
			$action = new K4InformationAction(new K4LanguageElement('L_REGVALIDATEDEMAIL'), 'content', FALSE, 'index.php', 3);

			
			return $action->execute($request);

			return TRUE;
		} else {
			$action = new K4InformationAction(new K4LanguageElement('L_CANTBELOGGEDIN'), 'content', FALSE);

			return $action->execute($request);
			return TRUE;
		}

		return TRUE;
	}
}

class RemindMeEvent extends FAAction {
	function execute(&$request) {
		
		/* Create the ancestors bar (if we run into any trouble */
		k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');

		if (!$request['user']->isMember()) {
			$request['template']->setFile('content', 'remindme_form.html');

			return TRUE;
		} else {
			$action = new K4InformationAction(new K4LanguageElement('L_CANTBELOGGEDIN'), 'content', FALSE);

			return $action->execute($request);
		}

		return TRUE;
	}
}

class EmailUser extends FAAction {
	function execute(&$request) {
		
		global $_QUERYPARAMS;

		/**
		 * Error checking on this member
		 */
		if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_USERDOESNTEXIST'), 'content', TRUE);

			return $action->execute($request);
			return TRUE;
		}

		$member = $request['dba']->getRow("SELECT ". $_QUERYPARAMS['user'] . $_QUERYPARAMS['userinfo'] ." FROM ". K4USERS ." u LEFT JOIN ". K4USERINFO ." ui ON u.id = ui.user_id WHERE u.id = ". intval($_REQUEST['id']));

		if(!$member || !is_array($member) || empty($member)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_USERDOESNTEXIST'), 'content', TRUE);

			return $action->execute($request);
			return TRUE;
		}

		if(!$request['user']->isMember()) {
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
			$request['template']->setFile('content', 'login_form.html');
			$request['template']->setVisibility('no_perms', TRUE);
			return TRUE;
		}
		
		foreach($member as $key => $val)
			$request['template']->setVar('member_'. $key, $val);
				
		/**
		 * Set the info we need
		 */
		k4_bread_crumbs(&$request['template'], $request['dba'], 'L_EMAILUSER');
		$request['template']->setFile('content', 'email_user.html');
		
		return TRUE;
	}
}

class SendEmailToUser extends FAAction {
	function execute(&$request) {
		
		global $_QUERYPARAMS;

		/**
		 * Error checking on this member
		 */
		if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_USERDOESNTEXIST'), 'content', TRUE);

			return $action->execute($request);
			return TRUE;
		}

		$member = $request['dba']->getRow("SELECT ". $_QUERYPARAMS['user'] . $_QUERYPARAMS['userinfo'] ." FROM ". K4USERS ." u LEFT JOIN ". K4USERINFO ." ui ON u.id = ui.user_id WHERE u.id = ". intval($_REQUEST['id']));

		if(!$member || !is_array($member) || empty($member)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_USERDOESNTEXIST'), 'content', TRUE);

			return $action->execute($request);
			return TRUE;
		}

		if(!$request['user']->isMember()) {
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
			$request['template']->setFile('content', 'login_form.html');
			$request['template']->setVisibility('no_perms', TRUE);
			return TRUE;
		}
		
		if(!isset($_REQUEST['subject']) || $_REQUEST['subject'] == '') {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_INSERTMAILSUBJECT'), 'content', TRUE);

			return $action->execute($request);
			return TRUE;
		}

		if(!isset($_REQUEST['message']) || $_REQUEST['message'] == '') {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_INSERTMAILMESSAGE'), 'content', TRUE);

			return $action->execute($request);
			return TRUE;
		}
		k4_bread_crumbs(&$request['template'], $request['dba'], 'L_EMAILUSER');

		if(!@mail($member['email'], htmlentities(stripslashes($_REQUEST['subject']), ENT_QUOTES), htmlentities(stripslashes($_REQUEST['message']), ENT_QUOTES), "Content-type: text/html; charset=iso-8859-1\r\nFrom: \"". iif($request['user']->get('realname') == '', $request['user']->get('name'), $request['user']->get('realname')) ." - k4 Bulletin Board Mailer\" <". $request['user']->get('email') .">")) {
			$action = new K4InformationAction(new K4LanguageElement('L_ERROREMAILING', $member['name']), 'content', FALSE);

			return $action->execute($request);
		} else {
			$action = new K4InformationAction(new K4LanguageElement('L_EMAILSENT', $member['name']), 'content', FALSE, 'member.php?id='. $member['id'], 3);

			return $action->execute($request);
		}
		
		return TRUE;
	}
}

class FindPostsByUser extends FAAction {
	function execute(&$request) {
		
		global $_QUERYPARAMS, $_ALLFORUMS;

		/**
		 * Error checking on this member
		 */
		if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_USERDOESNTEXIST'), 'content', TRUE);

			return $action->execute($request);
			return TRUE;
		}

		$member = $request['dba']->getRow("SELECT ". $_QUERYPARAMS['user'] . $_QUERYPARAMS['userinfo'] ." FROM ". K4USERS ." u LEFT JOIN ". K4USERINFO ." ui ON u.id = ui.user_id WHERE u.id = ". intval($_REQUEST['id']));

		if(!$member || !is_array($member) || empty($member)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_USERDOESNTEXIST'), 'content', TRUE);

			return $action->execute($request);
			return TRUE;
		}
		
		foreach($member as $key => $val)
			$request['template']->setVar('member_'. $key, $val);
				
		/**
		 * Set the info we need
		 */
		k4_bread_crumbs(&$request['template'], $request['dba'], 'L_FINDPOSTS');
		$request['template']->setFile('content', 'user_posts.html');
		
		//$posts		

		return TRUE;
	}
}

class UsersIterator extends FAProxyIterator {
	
	var $result;
	var $groups;

	function UsersIterator(&$result) {
		global $_USERGROUPS;

		$this->result			= &$result;
		$this->groups			= $_USERGROUPS;
		
		parent::__construct($this->result);
	}

	function &current() {
		$temp					= parent::current();
		$group					= get_user_max_group($temp, $this->groups);
		
		$temp['group_color']	= !isset($group['color']) || $group['color'] == '' ? '000000' : $group['color'];
		$temp['group_nicename']	= $group['nicename'];
		$temp['group_avatar']	= $group['avatar'];

		return $temp;
	}
}

?>