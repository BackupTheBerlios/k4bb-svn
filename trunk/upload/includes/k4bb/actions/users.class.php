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
* @version $Id: users.class.php 147 2005-07-09 17:12:40Z Peter Goodman $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	return;
}

/**
 * Get the highest permissioned group that a user belongs to
 */
function get_user_max_group($temp, $all_groups) {
	$result				= @unserialize($temp['usergroups']);
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

class ValidateUserByEmail extends FAAction {
	function execute(&$request) {
		
		/* Create the ancestors bar (if we run into any trouble */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_VALIDATEMEMBERSHIP');

		if (!$request['user']->isMember()) {
			
			global $_SETTINGS;
			
			if(!isset($_REQUEST['key']) || strlen($_REQUEST['key']) != 32) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDREGID'), 'content', FALSE);

				return $action->execute($request);
				return TRUE;
			}

			$u			= $request['dba']->getRow("SELECT * FROM ". K4USERS ." WHERE reg_key = '". $request['dba']->quote($_REQUEST['key']) ."' AND perms = ". intval(PENDING_MEMBER));

			if(!is_array($u) || empty($u)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDREGID'), 'content', FALSE);

				return $action->execute($request);
				return TRUE;
			}
			
			$request['dba']->executeUpdate("UPDATE ". K4USERS ." SET reg_key = '', perms = ". MEMBER .", usergroups = 'a:1:{i:0;i:2;}' WHERE id = ". intval($u['id']));
			
			$action = new K4InformationAction(new K4LanguageElement('L_REGVALIDATEDEMAIL'), 'content', FALSE, 'index.php', 3);

			
			return $action->execute($request);
		} else {
			$action = new K4InformationAction(new K4LanguageElement('L_CANTBELOGGEDIN'), 'content', FALSE);
			return $action->execute($request);
		}

		return TRUE;
	}
}

class RemindMeEvent extends FAAction {
	function execute(&$request) {
		
		/* Create the ancestors bar (if we run into any trouble */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');

		if (!$request['user']->isMember()) {
			$request['template']->setFile('content', 'remindme_form.html');
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
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_USERDOESNTEXIST'), 'content', TRUE);
			return $action->execute($request);
		}

		$member = $request['dba']->getRow("SELECT ". $_QUERYPARAMS['user'] . $_QUERYPARAMS['userinfo'] ." FROM ". K4USERS ." u LEFT JOIN ". K4USERINFO ." ui ON u.id = ui.user_id WHERE u.id = ". intval($_REQUEST['id']));

		if(!$member || !is_array($member) || empty($member)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_USERDOESNTEXIST'), 'content', TRUE);
			return $action->execute($request);
		}

		if(!$request['user']->isMember()) {
			no_perms_error($request);
			return TRUE;
		}
		
		foreach($member as $key => $val)
			$request['template']->setVar('member_'. $key, $val);
				
		/**
		 * Set the info we need
		 */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_EMAILUSER');
		$request['template']->setFile('content', 'email_user.html');
		
		return TRUE;
	}
}

class SendEmailToUser extends FAAction {
	function execute(&$request) {
		
		global $_QUERYPARAMS;
		
		k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');

		/**
		 * Error checking on this member
		 */
		if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
			$action = new K4InformationAction(new K4LanguageElement('L_USERDOESNTEXIST'), 'content', TRUE);
			return $action->execute($request);
		}

		$member = $request['dba']->getRow("SELECT ". $_QUERYPARAMS['user'] . $_QUERYPARAMS['userinfo'] ." FROM ". K4USERS ." u LEFT JOIN ". K4USERINFO ." ui ON u.id = ui.user_id WHERE u.id = ". intval($_REQUEST['id']));

		if(!$member || !is_array($member) || empty($member)) {
			$action = new K4InformationAction(new K4LanguageElement('L_USERDOESNTEXIST'), 'content', TRUE);
			return $action->execute($request);
		}

		if(!$request['user']->isMember()) {
			no_perms_error($request);
			return TRUE;
		}
		
		if(!isset($_REQUEST['subject']) || $_REQUEST['subject'] == '') {
			$action = new K4InformationAction(new K4LanguageElement('L_INSERTMAILSUBJECT'), 'content', TRUE);
			return $action->execute($request);
		}

		if(!isset($_REQUEST['message']) || $_REQUEST['message'] == '') {
			$action = new K4InformationAction(new K4LanguageElement('L_INSERTMAILMESSAGE'), 'content', TRUE);
			return $action->execute($request);
		}

		k4_bread_crumbs($request['template'], $request['dba'], 'L_EMAILUSER');
		
		if(!email_user($member['email'], htmlentities(stripslashes($_REQUEST['subject']), ENT_QUOTES), htmlentities(stripslashes($_REQUEST['message']), ENT_QUOTES))) {
			$action = new K4InformationAction(new K4LanguageElement('L_ERROREMAILING', $member['name']), 'content', FALSE);
			return $action->execute($request);
		} else {
			$action = new K4InformationAction(new K4LanguageElement('L_EMAILSENT', $member['name']), 'content', FALSE, 'member.php?id='. $member['id'], 3);

			return $action->execute($request);
		}
		
		return TRUE;
	}
}

class UsersIterator extends FAProxyIterator {
	
	var $result;
	var $groups;
	
	function UsersIterator(&$result) {
		$this->__construct($result);
	}

	function __construct(&$result) {
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
		$temp['font_weight']	= @$group['min_perm'] > MEMBER ? 'bold' : 'normal';

		$temp['warn_color']		= get_warning_color($temp['warn_level']);
		
		if(!$this->hasNext())
			$this->result->free();

		return $temp;
	}
}

?>