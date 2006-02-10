<?php
/**
* k4 Bulletin Board, usercp.class.php
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
* @package k4-2.0-dev
*/

if(!defined('IN_K4')) {
	return;
}

class K4UserControlPanel extends FAAction {
	function execute(&$request) {
		
		global $_QUERYPARAMS;

		/* Check if the user is logged in or not */
		if(!$request['user']->isMember()) {
			no_perms_error($request);
			return TRUE;
		}
			
		$view		= isset($_REQUEST['view']) ? $_REQUEST['view'] : false;
		
		$result		= $request['dba']->executeQuery("SELECT * FROM ". K4PMFOLDERS ." WHERE is_global = 1 OR user_id = ". intval($request['user']->get('id')));
		$request['template']->setList('pmfolders', $result);
		
		switch($view) {

			/**
			 * Settings and Options
			 */
			case 'profile': {
				$action				= &new K4UserCPProfile();
				break;
			}

			case 'password': {
				$action				= &new K4UserCPPassword();
				break;
			}

			case 'email': {
				$action				= &new K4UserCPEmail();
				break;
			}

			case 'options': {
				$action				= &new K4UserCPOptions();
				break;
			}

			case 'signature': {
				$action				= &new K4UserCPSignature();
				break;
			}

			case 'avatar': {
				$action				= &new K4UserCPAvatar();
				break;
			}

			case 'picture': {
				$action				= &new K4UserCPPersonalPicture();
				break;
			}

			case 'attachments': {
				$action				= &new K4ManageAttachments();
				break;
			}

			case 'subscriptions': {
				$action				= &new K4ManageSubscriptions();
				break;
			}
			
			case 'drafts': {
				$action				= &new K4ManageDrafts();
				break;
			}

			default: {
				
				/* Get the most recent current announcements */
				$announcements		= $request['dba']->executeQuery("SELECT * FROM ". K4POSTS ." WHERE (is_draft=0 AND queue=0 AND display=1) AND row_type=". TOPIC ." AND post_type = ". TOPIC_ANNOUNCE ." ORDER BY created DESC");
				$a_it				= &new TopicsIterator($request['dba'], $request['user'], $announcements, $request['template']->getVar('IMG_DIR'), array('postsperpage' => $request['user']->get('postsperpage') ) );
				$request['template']->setList('announcements', $a_it);
				
				/* Set the 'home page' file */
				$request['template']->setFile('usercp_content', 'usercp_home.html');

				$action				= &new K4ProfileAction();

				break;
			}

			/**
			 * Private Messaging
			 */

			case 'pmfolder': {
				$action				= &new K4ShowPMFolder();
				break;
			}
			case 'pmfolders': {
				$action				= &new K4CreatePMFolder();
				break;
			}
			case 'pmnewmessage': {
				$action				= &new K4ComposePMessage();
				break;
			}
			case 'pmsg': {
				$action				= &new K4ViewPMessage();
				break;
			}
			case 'pmperformact': {
				
				$actions = array('move', 'delete');
				
				if(isset($_REQUEST['action']) && in_array($_REQUEST['action'], $actions)) {
					
					// move PMs
					if($_REQUEST['action'] == 'move') {
						
						$action		= &new K4SelectPMMoveFolder();

					// delete PMs
					} else {
						
						$action		= &new K4DeletePMessages();

					}
				}
			}
		}
		
		/* Execute an action */
		if(isset($action)) {
			$action->execute($request);
		}

		/* Create the ancestors bar */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_USERCONTROLPANEL');

		$request['template']->setFile('content', 'usercp.html');
		
		return TRUE;
	}
}

class K4UserCPProfile extends FAAction {
	function execute(&$request) {

		if(count($fields) > 0) {
			$it = &new FAArrayIterator($fields);
			$request['template']->setList('member_profilefields', $it);
		}
		
		global $_QUERYPARAMS;

		$user = $request['dba']->getRow("SELECT {$_QUERYPARAMS['user']}{$_QUERYPARAMS['userinfo']}{$_QUERYPARAMS['usersettings']} FROM ". K4USERS ." u, ". K4USERINFO ." ui, ". K4USERSETTINGS ." us WHERE u.id=ui.user_id AND us.user_id=u.id AND u.id=". intval($request['user']->get('id')) ." LIMIT 1");
			
		if(!is_array($user) || empty($user)) {
			$action = new K4InformationAction(new K4LanguageElement('L_USERDOESNTEXIST'), 'content', TRUE);
			return $action->execute($request);
		}

		foreach($user as $key=>$val)
			$request['template']->setVar('member_'. $key, $val);
		
		$fields			= format_profile_fields($user);
		$request['template']->setList('member_profilefields', new FAArrayIterator($fields));

		// month/day/year
		$parts			= strlen($user['birthday']) == 10 ? explode("/", $user['birthday']) : explode("/", '0/0/');
		$request['template']->setVar('bday_month', intval($parts[0]));
		$request['template']->setVar('bday_day', intval($parts[1]));
		$request['template']->setVar('bday_year', $parts[2]);

		$request['template']->setFile('usercp_content', 'usercp_profile.html');

		return TRUE;
	}
}

class K4UserCPPassword extends FAAction {
	function execute(&$request) {
		
//		// DEMO VERSION
//		if(K4DEMOMODE) {
//			no_perms_error($request);
//			return TRUE;
//		}

		$request['template']->setFile('usercp_content', 'usercp_password.html');

		return TRUE;
	}
}

class K4UserCPEmail extends FAAction {
	function execute(&$request) {
		
		$request['template']->setFile('usercp_content', 'usercp_email.html');

		return TRUE;
	}
}

class K4UserCPOptions extends FAAction {
	function execute(&$request) {
		
		$result = $request['dba']->executeQuery("SELECT * FROM ". K4STYLES);

		$request['template']->setList('languages', new FAArrayIterator(get_files(K4_BASE_DIR .'/lang/', TRUE)));
		$request['template']->setList('imagesets', new FAArrayIterator(get_files(BB_BASE_DIR .'/Images/', TRUE, FALSE, array('admin'))));
		$request['template']->setList('templatesets', new FAArrayIterator(get_files(BB_BASE_DIR .'/templates/', TRUE, FALSE, array('Archive','RSS'))));
		$request['template']->setList('stylesets', $result);

		$request['template']->setFile('usercp_content', 'usercp_options.html');

		return TRUE;
	}
}

class K4UserCPSignature extends FAAction {
	function execute(&$request) {
		
		if(intval($request['template']->getVar('signaturesenabled')) == 0) {
			no_perms_error($request, 'usercp_content');
			return TRUE;
		}
		
		$body_text = '';
		if($request['user']->get('signature') != '') {
			//$bbcodex		= &new BBCodex($request['dba'], $request['user']->getInfoArray(), $request['user']->get('signature'), FALSE, TRUE, TRUE, TRUE, TRUE);
			$parser = &new BBParser;
			$body_text		= $parser->revert($request['user']->get('signature'));
		}

		create_editor($request, $body_text, 'signature', array('forum_id'=>0));

		$request['template']->setFile('usercp_content', 'usercp_signature.html');

		return TRUE;
	}
}

class K4UserCPAvatar extends FAAction {
	function execute(&$request) {
		
		$request['template']->setFile('usercp_content', 'usercp_avatar.html');

		return TRUE;
	}
}

class K4UserCPPersonalPicture extends FAAction {
	function execute(&$request) {
		
		$request['template']->setFile('usercp_content', 'usercp_picture.html');

		return TRUE;
	}
}

/**
 * Update the user profile information
 * Luckily none of this information is required, except for possible
 * custom profile fields
 */
class K4UpdateUserProfile extends FAAction {
	function execute(&$request) {
		
		global $_PROFILEFIELDS;
		
		foreach($_REQUEST as $key => $val) {
			if(!is_array($_REQUEST[$key]))
				$_REQUEST[$key]		= k4_htmlentities(html_entity_decode(strip_tags($val), ENT_QUOTES), ENT_QUOTES);
		}

		/* Check if the user is logged in or not */
		if(!$request['user']->isMember()) {
			no_perms_error($request);
			return TRUE;
		}

		$query			= "UPDATE ". K4USERINFO ." SET ";
		$error			= '';
		
		$fields			= array('fullname', 'icq', 'aim', 'msn', 'yahoo', 'jabber', 'googletalk');

		foreach($fields as $field) {
			if(isset($_REQUEST[$field])) {
				$query		.= $field ."='". $request['dba']->quote($_REQUEST[$field]) ."', ";
			}
		}

		// deal with the timezone
		if(isset($_REQUEST['timezone']) && $_REQUEST['timezone'] != '') {
			$query		.= "timezone = ". intval($_REQUEST['timezone']) .", ";
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
		
		// could this check get any uglier/more stupid?
		$birthday = '';
		if(isset($_REQUEST['month']) && isset($_REQUEST['day']) && isset($_REQUEST['year'])) {
			if((intval($_REQUEST['month']) != 0 && ctype_digit($_REQUEST['month'])) && (intval($_REQUEST['day']) != 0 && ctype_digit($_REQUEST['day'])) && (intval($_REQUEST['year']) != 0 && ctype_digit($_REQUEST['year']))) {
				
				$birthday = $request['dba']->quote($_REQUEST['month'] .'/'. $_REQUEST['day'] .'/'. $_REQUEST['year']);
				$birthday = strlen($birthday) == 10 ? $birthday : '';

			}
		}
		
		// finish off this query
		$query			.= "birthday = '". $birthday ."' WHERE user_id = ". intval($request['user']->get('id'));
		
		
		/* Create the ancestors bar */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_USERCONTROLPANEL');
		$request['template']->setFile('content', 'usercp.html');
		
		// error checking... too late? nah.
		if($error != '') {
			$action = new K4InformationAction(new K4LanguageElement('L_FOLLOWINGERRORS', $error), 'usercp_content', FALSE);
			return $action->execute($request);
		}
		
		/* Update the user */
		$request['dba']->executeUpdate($query);
		
		/* redirect us */
		$action = new K4InformationAction(new K4LanguageElement('L_UPDATEDPROFILE'), 'usercp_content', FALSE, 'member.php?act=usercp', 3);
		return $action->execute($request);
	}
}

class K4UpdateUserPassword extends FAAction {
	function execute(&$request) {
		
		// DEMO VERSION
		if(K4DEMOMODE) {
			no_perms_error($request);
			return TRUE;
		}

		global $_URL;
		
		foreach($_REQUEST as $key => $val)
			$_REQUEST[$key]		= k4_htmlentities(strip_tags($val), ENT_QUOTES);

		/* Check if the user is logged in or not */
		if(!$request['user']->isMember()) {
			no_perms_error($request);
			return TRUE;
		}
		
		/* Create the ancestors bar */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_USERCONTROLPANEL');
		$request['template']->setFile('content', 'usercp.html');

		/* current password checks */
		if(!$this->runPostFilter('password', new FARequiredFilter)) {
			$action = new K4InformationAction(new K4LanguageElement('L_SUPPLYPASSWORD'), 'usercp_content', TRUE);
			return $action->execute($request);
		}
		if(!$this->runPostFilter('password2', new FARequiredFilter)) {
			$action = new K4InformationAction(new K4LanguageElement('L_SUPPLYPASSCHECK'), 'usercp_content', TRUE);
			return $action->execute($request);
		}
		if(!$this->runPostFilter('password', new FACompareFilter('password2'))) {
			$action = new K4InformationAction(new K4LanguageElement('L_PASSESDONTMATCH'), 'usercp_content', TRUE);
			return $action->execute($request);
		}

		/* new password checks */
		if(!$this->runPostFilter('newpassword', new FARequiredFilter)) {
			$action = new K4InformationAction(new K4LanguageElement('L_SUPPLYNEWPASSWORD'), 'usercp_content', TRUE);
			return $action->execute($request);
		}
		if(!$this->runPostFilter('newpassword2', new FARequiredFilter)) {
			$action = new K4InformationAction(new K4LanguageElement('L_SUPPLYNEWPASSCHECK'), 'usercp_content', TRUE);
			return $action->execute($request);
		}
		if(!$this->runPostFilter('newpassword', new FACompareFilter('newpassword2'))) {
			$action = new K4InformationAction(new K4LanguageElement('L_NEWPASSESDONTMATCH'), 'usercp_content', TRUE);
			return $action->execute($request);
		}
		
		if(md5($_REQUEST['password']) != $request['user']->get('pass')) {
			$action = new K4InformationAction(new K4LanguageElement('L_INVALIDCURRPASS'), 'usercp_content', TRUE);
			return $action->execute($request);
		}
		
		$request['dba']->executeUpdate("UPDATE ". K4USERS ." SET pass = '". md5($_REQUEST['newpassword']) ."' WHERE id = ". intval($request['user']->get('id')));
		
		email_user($request['user']->get('email'), "null", sprintf($request['template']->getVar('L_PASSCHANGEDEMAIL'), $request['user']->get('name'), $request['user']->get('name'), $_REQUEST['newpassword'], $request['template']->getVar('bbtitle')));
		
		/* Make sure to change the information in the $request */
		$user				= &new K4UserManager($request['dba']);
		$user				= $user->getInfo($request['user']->get('id'));
		$request['user']	= &new K4Member($user);
		$_SESSION['user']	= &new K4Member($user);

		$action = new K4InformationAction(new K4LanguageElement('L_PASSWORDSUCCESS'), 'usercp_content', TRUE, 'member.php?act=usercp', 3);
		return $action->execute($request);
	}
}

class K4UpdateUserEmail extends FAAction {
	function execute(&$request) {
		
		global $_URL, $_SETTINGS;
		
		foreach($_REQUEST as $key => $val)
			$_REQUEST[$key]		= k4_htmlentities(strip_tags($val), ENT_QUOTES);

		/* Check if the user is logged in or not */
		if(!$request['user']->isMember()) {
			no_perms_error($request);
			return TRUE;
		}
		
		/* Create the ancestors bar */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_USERCONTROLPANEL');
		$request['template']->setFile('content', 'usercp.html');

		/* current password checks */
		if(!$this->runPostFilter('email', new FARequiredFilter)) {
			$action = new K4InformationAction(new K4LanguageElement('L_SUPPLYEMAIL'), 'usercp_content', TRUE);
			return $action->execute($request);
		}
		if(!$this->runPostFilter('email2', new FARequiredFilter)) {
			$action = new K4InformationAction(new K4LanguageElement('L_SUPPLYEMAILCHECK'), 'usercp_content', TRUE);
			return $action->execute($request);
		}
		if(!$this->runPostFilter('email', new FACompareFilter('email2'))) {
			$action = new K4InformationAction(new K4LanguageElement('L_EMAILSDONTMATCH'), 'usercp_content', TRUE);
			return $action->execute($request);
		}

		/* new email checks */
		if(!$this->runPostFilter('newemail', new FARequiredFilter)) {
			$action = new K4InformationAction(new K4LanguageElement('L_SUPPLYNEWEMAIL'), 'usercp_content', TRUE);
			return $action->execute($request);
		}
		if(!$this->runPostFilter('newemail2', new FARequiredFilter)) {
			$action = new K4InformationAction(new K4LanguageElement('L_SUPPLYNEWEMAILCHECK'), 'usercp_content', TRUE);
			return $action->execute($request);
		}
		if(!$this->runPostFilter('newemail', new FACompareFilter('newemail2'))) {
			$action = new K4InformationAction(new K4LanguageElement('L_NEWEMAILSDONTMATCH'), 'usercp_content', TRUE);
			return $action->execute($request);
		}
		
		if($_REQUEST['email'] != $request['user']->get('email')) {
			$action = new K4InformationAction(new K4LanguageElement('L_INVALIDCURREMAIL'), 'usercp_content', TRUE);
			return $action->execute($request);
		}
		
		if (!$this->runPostFilter('newemail', new FARegexFilter('~^([0-9a-zA-Z]+[-._+&])*[0-9a-zA-Z]+@([-0-9a-zA-Z]+[.])+[a-zA-Z]{2,6}$~'))) {
			$action = new K4InformationAction(new K4LanguageElement('L_NEEDVALIDEMAIL'), 'usercp_content', TRUE);
			return $action->execute($request);
		}

		if($_SETTINGS['requireuniqueemail'] == 1) {
			if($request['dba']->getValue("SELECT COUNT(*) FROM ". K4USERS ." WHERE email = '". $request['dba']->quote($_REQUEST['newemail']) ."'") > 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_EMAILTAKEN'), 'usercp_content', TRUE);
				return $action->execute($request);
			}
		}
		
		$key					= (intval($_SETTINGS['verifyemail']) == 1) ? md5(uniqid(rand(), true)) : '';
		
		$email_url				= $_URL;
		$email_url->args		= array('act'=>'validate_email', 'id'=>$request['user']->get('id'), 'key'=>$key);
		$email_url->file		= 'member.php';
		$email_url->anchor		= FALSE;

		$request['dba']->executeUpdate("UPDATE ". K4USERS ." SET email = '". $_REQUEST['newemail'] ."', reg_key = '". $key ."' WHERE id = ". intval($request['user']->get('id')));
		
		email_user($_REQUEST['newemail'], "null", sprintf($request['template']->getVar('L_EMAILCHANGEDEMAIL'), $request['user']->get('name'), $email_url->__toString(), $_SETTINGS['bbtitle']));
		
		/* Make sure to change the information in the $request */
		if(intval($_SETTINGS['verifyemail']) == 1) {

			$request['user']	= &new K4Guest();
			$_SESSION['user']	= &new K4Guest();
		} else {
			
			$user				= &new K4UserManager($request['dba']);
			$user				= $user->getInfo($request['user']->get('id'));
			$request['user']	= &new K4Member($user);
			$_SESSION['user']	= &new K4Member($user);
		}

		$action = new K4InformationAction(new K4LanguageElement('L_EMAILCHANGEDSUCCESS'), 'usercp_content', TRUE, 'index.php', 5);
		return $action->execute($request);
	}
}

class K4ValidateChangedEmail extends FAAction {
	function execute(&$request) {
		
		global $_URL, $_SETTINGS;
		
		foreach($_REQUEST as $key => $val) {
			$_REQUEST[$key]		= k4_htmlentities(strip_tags($val), ENT_QUOTES);
		}
		
		k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');

		if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
			$action = new K4InformationAction(new K4LanguageElement('L_USERDOESNTEXIST'), 'content', TRUE);
			return $action->execute($request);
		}

		$user			= $request['dba']->getRow("SELECT * FROM ". K4USERS ." WHERE id = ". intval($_REQUEST['id']) ." LIMIT 1");
		
		if(!is_array($user) || empty($user)) {
			$action = new K4InformationAction(new K4LanguageElement('L_USERDOESNTEXIST'), 'content', TRUE);
			return $action->execute($request);
		}

		if(intval($_SETTINGS['verifyemail']) == 1) {
			
			if($_REQUEST['key'] != $user['reg_key']) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDEMAILKEY'), 'content', TRUE);
				return $action->execute($request);
			}

		}
		
		$request['dba']->executeUpdate("UPDATE ". K4USERS ." SET reg_key='' WHERE id = ". intval($request['user']->get('id')));
		$action = new K4InformationAction(new K4LanguageElement('L_VERIFIEDNEWEMAIL', $user['name']), 'content', TRUE, 'index.php', 3);
		return $action->execute($request);
	}
}

class K4UpdateUserOptions extends FAAction {
	function execute(&$request) {
		
		global $_URL, $_SETTINGS;
		
		foreach($_REQUEST as $key => $val)
			$_REQUEST[$key]		= k4_htmlentities(strip_tags($val), ENT_QUOTES);

		/* Check if the user is logged in or not */
		if(!$request['user']->isMember()) {
			no_perms_error($request);
			return TRUE;
		}

		/* Do half-checks on the styles/language stuff */
		$language		= !in_array($_REQUEST['language'], get_files(K4_BASE_DIR .'/lang/', TRUE, TRUE)) ? $request['user']->get('language') : $_REQUEST['language'];
		$imageset		= !in_array($_REQUEST['imageset'], get_files(BB_BASE_DIR .'/Images/', TRUE, TRUE)) ? $request['user']->get('imageset') : $_REQUEST['imageset'];
		$templateset	= !in_array($_REQUEST['templateset'], get_files(BB_BASE_DIR .'/templates/', TRUE, TRUE)) ? $request['user']->get('templateset') : $_REQUEST['templateset'];
		$styleset		= $request['dba']->getRow("SELECT * FROM ". K4STYLES ." WHERE id = ". intval($_REQUEST['styleset']) ." LIMIT 1");
		$styleset		= is_array($styleset) && !empty($styleset) ? $styleset['name'] : $request['user']->get('styleset');

		/* Create the ancestors bar */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_USERCONTROLPANEL');
		$request['template']->setFile('content', 'usercp.html');
				
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
		$query->setInt(17, $request['user']->get('id'));

		$query->executeUpdate();
		
		/* Update the session */
		$user				= &new K4UserManager($request['dba']);
		$user				= $user->getInfo($request['user']->get('id'));
		$request['user']	= &new K4Member($user);
		$_SESSION['user']	= &new K4Member($user);

		$action = new K4InformationAction(new K4LanguageElement('L_SETTINGSSUCCESS'), 'usercp_content', TRUE, 'member.php?act=usercp', 3);
		return $action->execute($request);
	}
}

class K4UpdateUserSignature extends FAAction {
	function execute(&$request) {
		
		global $_SETTINGS;
		
		foreach($_REQUEST as $key => $val)
			$_REQUEST[$key]		= k4_htmlentities(strip_tags($val), ENT_QUOTES);

		/* Check if the user is logged in or not */
		if(!$request['user']->isMember()) {
			no_perms_error($request);
			return TRUE;
		}

		if(intval($_SETTINGS['signaturesenabled']) == 0) {
			no_perms_error($request, 'usercp_content');
			return TRUE;
		}
		
		/* Create the ancestors bar */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_USERCONTROLPANEL');
		$request['template']->setFile('content', 'usercp.html');
		
		/* Should we update whether this user attaches their signature to posts, etc ? */
		if(isset($_REQUEST['attachsig']) && (intval($_REQUEST['attachsig']) == 1 || intval($_REQUEST['attachsig']) == 0) && intval($_REQUEST['attachsig']) != $request['user']->get('attachsig')) {
			$request['dba']->executeUpdate("UPDATE ". K4USERSETTINGS ." SET attachsig = ". intval($_REQUEST['attachsig']));
		}
		
		/* Update the actual signature */
		//$bbcode				= &new BBCodex($request['dba'], $request['user']->getInfoArray(), $_REQUEST['message'], FALSE, FALSE, (bool)intval($_SETTINGS['allowbbcodesignatures']), (bool)intval($_SETTINGS['allowemoticonssignature']), (bool)intval($_SETTINGS['autoparsesignatureurls']));
		$signature = $_REQUEST['message'];
		if((bool)intval($_SETTINGS['allowbbcodesignatures'])) {
			$parser = &new BBParser;
			$signature			= $parser->parse($signature);
		}
		$signature			= substr($request['dba']->quote($signature), 0, 255);
		
		/* Update the signature */
		$request['dba']->executeUpdate("UPDATE ". K4USERINFO ." SET signature = '{$signature}' WHERE user_id = ". intval($request['user']->get('id')));

		/* Make sure to change the information in the $request */
		$user				= &new K4UserManager($request['dba']);
		$user				= $user->getInfo($request['user']->get('id'));
		$request['user']	= &new K4Member($user);
		$_SESSION['user']	= &new K4Member($user);
		
		/* Redirect */
		$action = new K4InformationAction(new K4LanguageElement('L_UPDATEDSIGNATURE'), 'usercp_content', TRUE, 'member.php?act=usercp', 3);
		return $action->execute($request);
	}
}

class K4UpdateUserFile extends FAAction {
	
	var $bigname, $smallname, $table_column, $table;
	
	function __construct($bigname, $smallname, $table_column, $table) {
		$this->bigname		= $bigname;
		$this->smallname	= $smallname;
		$this->table_column	= $table_column;
		$this->table		= $table;
	}
	function execute(&$request) {
		
		global $_SETTINGS;
		
		foreach($_REQUEST as $key => $val)
			$_REQUEST[$key]		= k4_htmlentities(strip_tags($val), ENT_QUOTES);

		/* Check if the user is logged in or not */
		if(!$request['user']->isMember()) {
			no_perms_error($request);
			return TRUE;
		}

		/* Create the ancestors bar */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_USERCONTROLPANEL');
		$request['template']->setFile('content', 'usercp.html');

		if(intval($_SETTINGS[$this->bigname .'enabled']) == 0) {
			no_perms_error($request, 'usercp_content');
			return TRUE;
		}
		
		$max_size		= $_SETTINGS[$this->smallname .'maxsize'];
		$in_db			= $_SETTINGS['store'. $this->smallname .'sdb'] == 1 ? TRUE : FALSE;
		$upload_dir		= BB_BASE_DIR .'/tmp/upload/'. $this->table_column .'s/';
		
		// get our valid filetypes
		$filetypes		= explode(" ", trim(str_replace(',', ' ', $_SETTINGS[$this->smallname .'allowedfiles'])));
		$use_avatar		= isset($_REQUEST['use'. $this->table_column]) ? TRUE : FALSE;
		$add			= TRUE;

		$avatar			= $request['dba']->getRow("SELECT * FROM ". $this->table ." WHERE user_id = ". intval($request['user']->get('id')));
		
		//if((isset($_FILES) && is_array($_FILES)) || (isset($_REQUEST[$this->table_column .'_website']) && $_REQUEST[$this->table_column .'_website'] != '') ) {
			
			// have we specified a file to upload?
			if(isset($_FILES) && is_array($_FILES) && isset($_FILES[$this->table_column .'_file']) && is_array($_FILES[$this->table_column .'_file']) && !empty($_FILES[$this->table_column .'_file']) && $_FILES[$this->table_column .'_file']['error'] < 4) {
				
				__chmod($upload_dir, 0777);

				// check if this files is valid to upload	
				if($_FILES[$this->table_column .'_file']['size'] <= $max_size) {
					
					// get some file information
					$filetype				= file_extension($_FILES[$this->table_column .'_file']['name']);
					$filename				= $upload_dir . $request['user']->get('id') .'.'. $filetype;
					$filesize				= $_FILES[$this->table_column .'_file']['size'];
					
					if(in_array($filetype, $filetypes) && is_writeable($upload_dir)) {
						
						/**
						 * check the file dimensions
						 */
						$dimensions		= @getimagesize($_FILES[$this->table_column .'_file']['tmp_name']);
						
						if(!$dimensions || !is_array($dimensions) || empty($dimensions)) {
							$action		= new K4InformationAction(new K4LanguageElement('L_INVALID'. strtoupper($this->table_column) .'DIMS', $_SETTINGS[$this->smallname .'maxwidth'], $_SETTINGS[$this->smallname .'maxheight']), 'usercp_content', TRUE);
							$add		= FALSE;
						}
						
						if($dimensions[0] > $_SETTINGS[$this->smallname .'maxwidth'] || $dimensions[1] > $_SETTINGS[$this->smallname .'maxheight']) {
							$action		= new K4InformationAction(new K4LanguageElement('L_INVALID'. strtoupper($this->table_column) .'DIMS', $_SETTINGS[$this->smallname .'maxwidth'], $_SETTINGS[$this->smallname .'maxheight']), 'usercp_content', TRUE);
							$add		= FALSE;
						}
						
						if($add) {
							// did the upload go smoothly?
							if(@move_uploaded_file($_FILES[$this->table_column .'_file']['tmp_name'], $filename)) {
								
								// make sure that the file was actually uploaded
								if(file_exists($filename) && is_readable($filename)) {
									
									// change the file permissions on the just uploaded file
									__chmod($filename, 0777);
									
									$request['dba']->executeUpdate("UPDATE ". K4USERINFO ." SET ". $this->table_column ." = 1 WHERE user_id = ". intval($request['user']->get('id')));

									// prepare the sql query to insert it into the db
									
										
									$contents		= file_get_contents($filename);
									$mimetype		= get_mimetype($filename);
									$mimetype		= $_FILES[$this->table_column .'_file']['type'] != '' && strtolower($_FILES[$this->table_column .'_file']['type']) != $mimetype ? strtolower($_FILES[$this->table_column .'_file']['type']) : $mimetype;
									$url			= '';
									
									// remove the file if we are storing it in the db
									if($in_db) {
										@unlink($filename);
									}
								}
							}
						}
					} else {
						$action = new K4InformationAction(new K4LanguageElement('L_INVALID'. strtoupper($this->table_column) .'FILETYPE', $_SETTINGS[$this->smallname .'allowedfiles']), 'usercp_content', TRUE);
						$add	= FALSE;
					}
				} else {
					$action = new K4InformationAction(new K4LanguageElement('L_'. strtoupper($this->table_column) .'TOOBIG', $max_size), 'usercp_content', TRUE);
					$add	= FALSE;
				}
			/* Deal with files from a given website url */
			} elseif(isset($_REQUEST[$this->table_column .'_website']) && $_REQUEST[$this->table_column .'_website'] != '') {
				$url		= '';
				
				if(intval($_SETTINGS[$this->smallname .'allowwebsite']) == 1 && (is_array($url->args) && !empty($url->args) && intval($_SETTINGS[$this->smallname .'allowdynamicwebsite']) == 1) ) {
					$url			= new FAUrl($_REQUEST[$this->table_column .'_website']);
					$url			= $url->__toString();
					$contents		= @file_get_contents($url);

					if(!$contents) {
						$action		= new K4InformationAction(new K4LanguageElement('L_WEBSITE'. strtoupper($this->table_column) .'BAD'), 'usercp_content', TRUE);
						$add		= FALSE;
					}
					
					if($add) {
					
						/**
						 * check the file dimensions
						 * Luckily getimagesize will fail if the mimetype of the file in question
						 * is not an image.
						 */
						
						$dimensions		= @getimagesize($url);

						if(!$dimensions) {
							$action = new K4InformationAction(new K4LanguageElement('L_INVALID'. strtoupper($this->table_column) .'DIMS', $_SETTINGS[$this->smallname .'maxwidth'], $_SETTINGS[$this->smallname .'maxheight']), 'usercp_content', TRUE);
							$add		= FALSE;
						}

						if($dimensions[0] > $_SETTINGS[$this->smallname .'maxwidth'] || $dimensions[1] > $_SETTINGS[$this->smallname .'maxheight']) {
							$action = new K4InformationAction(new K4LanguageElement('L_INVALID'. strtoupper($this->table_column) .'DIMS', $_SETTINGS[$this->smallname .'maxwidth'], $_SETTINGS[$this->smallname .'maxheight']), 'usercp_content', TRUE);
							$add		= FALSE;
						}
						
						if(!extension_loaded('fileinfo')) {
							dl(K4_BASE_DIR . '/fileinfo/fileinfo.' . PHP_SHLIB_SUFFIX);
						}
						if(!extension_loaded('fileinfo')) {
							// TODO: contact admin if this happens
							$request['dba']->executeUpdate("UPDATE ". K4SETTINGS ." SET value = '0' WHERE varname = '". $this->smallname ."allowdynamicwebsite'");
							$action		= new K4InformationAction(new K4LanguageElement('L_'. strtoupper($this->table_column) .'CRITICALERROR'), 'usercp_content', TRUE);
							$add		= FALSE;
						}

						// return mime type from mimetype extension using FILEINFO_MIME (default)
						$result			= finfo_open();
						
						if(!$result) {
							// TODO: contact admin if this happens
							$request['dba']->executeUpdate("UPDATE ". K4SETTINGS ." SET value = '0' WHERE varname = '". $this->smallname ."allowdynamicwebsite'");
							$request['dba']->executeUpdate("UPDATE ". K4SETTINGS ." SET value = '0' WHERE varname = '". $this->smallname ."allowwebsite'");
							$action = new K4InformationAction(new K4LanguageElement('L_'. strtoupper($this->table_column) .'CRITICALERROR'), 'usercp_content', TRUE);
							$add		= FALSE;
						} else {
							$mimetype		= strtolower(finfo_file($result, $url));
							finfo_close($result);

							if($mimetype == '') {
								// TODO: contact admin if this happens
								$request['dba']->executeUpdate("UPDATE ". K4SETTINGS ." SET value = '0' WHERE varname = '". $this->smallname ."allowdynamicwebsite'");
								$request['dba']->executeUpdate("UPDATE ". K4SETTINGS ." SET value = '0' WHERE varname = '". $this->smallname ."allowwebsite'");
								$action = new K4InformationAction(new K4LanguageElement('L_'. strtoupper($this->table_column) .'CRITICALERROR'), 'usercp_content', TRUE);
								$add		= FALSE;
							}
						}
						if($add) {
							$filesize		= strlen($contents);
							$filetype		= get_fileextension($mimetype);
							
							if($filesize <= $max_size) {

								// TODO: users can file spam the forum

								if($filetype != 'txt' && in_array($filetype, $filetypes)) {
								
									$filename			= $upload_dir . $request['user']->get('id') .'.'. $filetype;
																		
									// if we are not storing the file in the db
									if(!$in_db) {
										
										// write the file to a given directory under this user's ID
										$fp				= fopen($filename, 'w');
										if (fwrite($fp, $contents) === FALSE) {
											$action		= new K4InformationAction(new K4LanguageElement('L_WEBSITE'. strtoupper($this->table_column) .'BAD'), 'usercp_content', TRUE);
											$add		= FALSE;
										}
										fclose($fp);
									}

								} else {
									$action = new K4InformationAction(new K4LanguageElement('L_INVALID'. strtoupper($this->table_column) .'FILETYPE', $_SETTINGS[$this->smallname .'allowedfiles']), 'usercp_content', TRUE);
									$add	= FALSE;
								}
							} else {
								$action = new K4InformationAction(new K4LanguageElement('L_'. strtoupper($this->table_column) .'TOOBIG', $max_size), 'usercp_content', TRUE);
								$add	= FALSE;
							}
						}
					}
				}
			} else {
				// update of the use file variable pertaining to what we are uploading
				$request['dba']->executeUpdate("UPDATE ". K4USERINFO ." SET ". $this->table_column ." = ". intval($use_avatar) ." WHERE user_id = ". $request['user']->get('id'));
				$add = FALSE;
			}
			
			/**
			 * If we should add this file to the database
			 */
			if($add) {
				
				if(is_array($avatar) && !empty($avatar)) {
					$query = "UPDATE ". $this->table ." SET file_type=?,mime_type=?,file_size=?,in_db=?,file_contents=? WHERE user_id=?";
				} else {
					$query = "INSERT INTO ". $this->table ." (file_type,mime_type,file_size,in_db,file_contents,user_id) VALUES (?,?,?,?,?,?)";
				}
				
				// add/update the file
				$insert			= $request['dba']->prepareStatement($query);
											
				//(user_id,file_type,mime_type,file_size,in_db,file_contents,avatar_url)
				$insert->setString(1, $filetype);
				$insert->setString(2, $mimetype);
				$insert->setInt(3, $filesize);
				$insert->setInt(4, $in_db);
				$insert->setString(5, ($in_db ? $contents : ''));
				$insert->setInt(6, $request['user']->get('id'));

				$insert->executeUpdate();
				
				/* memory saving */
				unset($url, $contents, $filetype, $mimetype, $filename, $insert);
			
			/* Purge any/all avatars */
			} else {			
				if(is_array($avatar) && !empty($avatar)) {
					$request['dba']->executeUpdate("UPDATE ". K4USERINFO ." SET ". $this->table_column ." = 0 WHERE user_id=". intval($request['user']->get('id')));
					$request['dba']->executeUpdate("DELETE FROM ". $this->table ." WHERE user_id = ". intval($request['user']->get('id')));
				}
				@unlink($upload_dir . $request['user']->get('id') .'.'. $avatar['file_type']);
			}

		//}

		/* Make sure to change the information in the $request */
		$user				= &new K4UserManager($request['dba']);
		$user				= $user->getInfo($request['user']->get('id'));
		$request['user']	= &new K4Member($user);
		$_SESSION['user']	= &new K4Member($user);
		
		/* Redirect or return an error */
		$action = !isset($action) ? new K4InformationAction(new K4LanguageElement('L_UPDATED'. strtoupper($this->table_column)), 'usercp_content', TRUE, 'member.php?act=usercp', 3) : $action;
		return $action->execute($request);
	}
}

/**
 * Manage Attachments
 */
class K4ManageAttachments extends FAAction {
	function execute(&$request) {
		
		global $_ALLFORUMS, $_SETTINGS;

		$attachments	= $request['dba']->executeQuery("SELECT * FROM ". K4ATTACHMENTS ." WHERE user_id = ". intval($request['user']->get('id')) ." ORDER BY created DESC");
		$atms			= array();

		$size = 0;
		while($attachments->next()) {
			$attachment = $attachments->current();
			$attachment['forum']		= isset($_ALLFORUMS[$attachment['forum_id']]) ? $_ALLFORUMS[$attachment['forum_id']]['name'] : '<em>No Information</em>';
			$attachment['file_type']	= strtoupper($attachment['file_type']);
			$attachment['percent']		= ceil(@(intval($attachment['file_size']) / intval($_SETTINGS['maxattachquota'])) * 100);

			$size += $attachment['file_size'];

			$atms[] = $attachment;
		}
		$pixels	= ceil(@($size / intval($_SETTINGS['maxattachquota'])) * 400);
		$request['template']->setVar('attach_percent', ceil(@($pixels / 400) * 100));
		$request['template']->setVar('attach_width', ($pixels - 2) < 0 ? 0 : ($pixels - 2));
		$request['template']->setVar('attach_megs', intval($_SETTINGS['maxattachquota']) / 1048576);
		$request['template']->setList('attachments', new FAArrayIterator($atms));
		$request['template']->setFile('usercp_content', 'usercp_attachments.html');
	}
}

/**
 * Manage Subscriptions
 */
class K4ManageSubscriptions extends FAAction {
	function execute(&$request) {
		global $_ALLFORUMS;

		$subscriptions	= $request['dba']->executeQuery("SELECT * FROM ". K4SUBSCRIPTIONS ." WHERE user_id = ". intval($request['user']->get('id')));
		$subs			= array();
		while($subscriptions->next()) {
			$subscription = $subscriptions->current();
			
			$subscription['forum_name'] = $_ALLFORUMS[$subscription['forum_id']]['name'];

			if(intval($subscription['post_id']) > 0) {
				$subscription['topic_name'] = $request['dba']->getValue("SELECT name FROM ". K4POSTS ." WHERE post_id = ". intval($subscription['post_id']));
			}
			
			$subs[] = $subscription;
		}
		
		$request['template']->setList('subscriptions', new FAArrayIterator($subs));
		$request['template']->setFile('usercp_content', 'usercp_subscriptions.html');

	}
}

class K4ManageDrafts extends FAAction {
	function execute(&$request) {
		global $_ALLFORUMS;

		$drafts			= $request['dba']->executeQuery("SELECT * FROM ". K4POSTS ." WHERE is_draft = 1 AND poster_id = ". intval($request['user']->get('id')));
		$drafts_array	= array();
		$forums			= array();
		
		while($drafts->next()) {
			$temp = $drafts->current();
			
			if(isset($_ALLFORUMS[$temp['forum_id']])) {
				
				if(!isset($forums[$temp['forum_id']])) {
					foreach($_ALLFORUMS[$temp['forum_id']] as $key => $val)
						$forums[$temp['forum_id']]['forum_'. $key] = $val;
				
				}
				if(isset($forums[$temp['forum_id']])) {
					$temp = array_merge($temp, $forums[$temp['forum_id']]);
				}

				$drafts_array[] = $temp;
			}
		}
		
		$drafts = new FAArrayIterator($drafts_array);

		$request['template']->setList('drafts', $drafts);
		$request['template']->setFile('usercp_content', 'post_drafts.html');
		
		return TRUE;
	}
}

?>