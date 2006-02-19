<?php
/**
* k4 Bulletin Board, user.php
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
* @author Geoffrey Goodman
* @version $Id: user.php 158 2005-07-18 02:55:30Z Peter Goodman $
* @package k42
*/

if (!defined(IN_K4))
	return;

/**
 * Format user profile fields
 */
function format_profile_fields($member, $use_all = FALSE) {
	global $_PROFILEFIELDS;

	/**
	 * Get the custom user fields for this member
	 */
	$fields = array();
	foreach($_PROFILEFIELDS as $field) {
			
		if(($field['is_editable'] == 1 && !$use_all) || $use_all) {
			
			$result				= @force_unserialize(@$member[$field['name']]);
			$result				= is_array($result) ? array_values($result) : array();

			//if(isset($member[$field['name']])) {
				switch($field['inputtype']) {
					case 'text': {
						$field['html']		= '<input type="text" name="'. $field['name'] .'" id="'. $field['name'] .'" value="'. @$member[$field['name']] .'" size="'. $field['display_size'] .'" class="inputbox" />';							
						break;
					}
					case 'textarea': {
						$field['html']		= '<textarea name="'. $field['name'] .'" id="'. $field['name'] .'" cols="'. $field['display_size'] .'" class="inputbox">'. preg_replace("~<br />~", "\n", @$member[$field['name']]) .'</textarea>';
						break;
					}
					case 'select': {
						
						$field['html']		= '<select name="'. $field['name'] .'" id="'. $field['name'] .'">';
						foreach($result as $val)
							$field['html']	.= '	<option value="'. $val .'">'. $val .'</option>';
						$field['html']		.= '</select>';
						$field['html']		.= '<script type="text/javascript">d.setIndex(\''. @$member[$field['name']] .'\', \''. $field['name'] .'\');</script>';
						break;
					}
					case 'multiselect': {
						
						$field['html']		= '<select name="'. $field['name'] .'" id="'. $field['name'] .'" rows="'. $field['display_rows'] .'" cols="'. $field['display_size'] .'">';
						foreach($result as $val)
							$field['html']	.= '	<option value="'. $val .'">'. $val .'</option>';
						$field['html']		.= '</select>';
						
						$values				= '';
						foreach(force_unserialize(@$member[$field['name']]) as $val)
							$values			.= "'$val',";

						$field['html']		.= '<script type="text/javascript">d.setIndices(new Array('. $values .'\'), \''. $field['name'] .'\');</script>';
						break;
					}
					case 'radio': {
						foreach($result as $val)
							$field['html']	.= '<input type="radio" value="'. $val .'" name="'. $field['name'] .'" />'. $val;
						
						$field['html']		.= '<script type="text/javascript">d.setRadio(\''. $val .'\', \''. $field['name'] .'\');</script>';
						break;
					}
					case 'check': {
						$values				= force_unserialize(@$member[$field['name']]);
						$i = 0;
						foreach($result as $val) {
							$field['html']	.= '<input type="checkbox" value="'. $val .'" name="'. $field['name'] .'[]" id="'. $field['name'] .'_'. $i .'" />'. $val;
							if(in_array())
								$field['html']	.= '<script type="text/javascript">d.setCheckbox(\''. $val .'\', \''. $field['name'] .'_'. $i .'\');</script>';
							
							$i++;
						}
						break;
					}
				}
				$fields[] = $field;
			//}
		}
	}

	return $fields;
}

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
	$result				= explode('|', trim($temp['usergroups'], '|'));
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
		
		$groups	= explode('|', trim($user['usergroups'], '|'));
	}

	return $groups;
}

/**
 * Function to check if a user belongs to a usergroup
 */
function is_in_group($my_groups, $groups, $my_perms) {
	
	if($my_perms >= ADMIN)
		return TRUE;

	$my_groups			= !is_array($my_groups) ? explode('|', trim($my_groups, '|')) : $my_groups;
	$groups				= !is_array($groups) ? explode('|', trim($groups, '|')) : $groups;
	
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

	$result				= explode('|', trim($forum['moderating_groups'], '|'));
	$moderators			= !$result ? force_usergroups($forum['moderating_groups']) : $result;
	
					
	$groups				= array();

	foreach($moderators as $g) {
		if(isset($_USERGROUPS[$g]))
			$groups[]	= $g;
	}
	
	if(isset($user['usergroups'])) {
		
		$unserialize		= explode('|', trim($user['usergroups'], '|'));
		$my_groups			= !$unserialize ? force_usergroups($user['usergroups']) : $unserialize;

		/* Do we toggle our moderator's panel? */
		if(is_in_group($my_groups, $groups, $user['perms'])) {
			return TRUE;
		}
	}

	if($forum['moderating_users'] != '') {
		$users					= force_unserialize($forum['moderating_users']);
		if(is_array($users)) {
			foreach($users as $user_id => $username) {
				if($user['name'] == $username && $user['id'] == $user_id) {
					return TRUE;
				}
			}
		}
	}

	return FALSE;
}

?>