<?php
/**
* k4 Bulletin Board, users.class.php
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
* @version $Id: users.class.php 147 2005-07-09 17:12:40Z Peter Goodman $
* @package k42
*/



if(!defined('IN_K4')) {
	return;
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
			}

			$u = $request['dba']->getRow("SELECT * FROM ". K4USERS ." WHERE reg_key = '". $request['dba']->quote($_REQUEST['key']) ."'");

			if(!is_array($u) || empty($u)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDREGID'), 'content', FALSE);

				return $action->execute($request);
				return TRUE;
			}
			
			$request['dba']->executeUpdate("UPDATE ". K4USERS ." SET reg_key = '', perms = ". MEMBER .", usergroups = '2' WHERE id = ". intval($u['id']));
			
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

		$member		= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['user'] . $_QUERYPARAMS['userinfo'] ." FROM ". K4USERS ." u LEFT JOIN ". K4USERINFO ." ui ON u.id = ui.user_id WHERE u.id = ". intval($_REQUEST['id']));

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
		
		$message_header = "From: ". $request['user']->get('name') ."\n";
		$message_header .= "User ID: ". $request['user']->get('id') ."\n";
		$message_header .= "Email: ". $request['user']->get('email') ."\n\n";

		if(!email_user($member['email'], k4_htmlentities(stripslashes($_REQUEST['subject']), ENT_NOQUOTES), $message_header . k4_htmlentities(stripslashes($_REQUEST['message']), ENT_NOQUOTES))) {
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

	function current() {
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