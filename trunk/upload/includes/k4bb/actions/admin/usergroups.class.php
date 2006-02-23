<?php
/**
* k4 Bulletin Board, usergroups.class.php
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
* @version $Id: usergroups.class.php 144 2005-07-05 02:29:07Z Peter Goodman $
* @package k42
*/



if(!defined('IN_K4')) {
	return;
}

class AdminUserGroups extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_USERGROUPS');
			$request['template']->setVar('users_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/users.html');

			$request['template']->setFile('content', 'usergroups_manage.html');
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminAddUserGroup extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			
			$request['template']->setFile('content', 'usergroups_add.html');

			k4_bread_crumbs($request['template'], $request['dba'], 'L_USERGROUPS');
			$request['template']->setVar('usergroups_action', 'admin.php?act=usergroups_insert');
			$request['template']->setVar('users_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/users.html');
			$request['template']->setVar('input_id', 'color');
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminInsertUserGroup extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			
			/* Error checking on the fields */
			if(!isset($_REQUEST['name']) || $_REQUEST['name'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTGROUPNAME'), 'content', TRUE);
				return $action->execute($request);
			}

			if(!isset($_REQUEST['nicename']) || $_REQUEST['nicename'] == '') {
				//$action = new K4InformationAction(new K4LanguageElement('L_INSERTGROUPNICENAME'), 'content', TRUE);
				//return $action->execute($request);
				
				// makes it not require the nice name
				$_REQUEST['nicename'] = '';
			}
			
			$g = $request['dba']->getRow("SELECT * FROM ". K4USERGROUPS ." WHERE name = '". $request['dba']->quote($_REQUEST['name']) ."'");			
			
			if(is_array($g) && !empty($g)) {
				$action = new K4InformationAction(new K4LanguageElement('L_GROUPNAMEEXISTS'), 'content', TRUE);
				return $action->execute($request);
			}

			if(!isset($_REQUEST['description']) || $_REQUEST['description'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTGROUPDESC'), 'content', TRUE);
				return $action->execute($request);
			}
			
			if(!isset($_REQUEST['mod_name']) || $_REQUEST['mod_name'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTMODNAME'), 'content', TRUE);
				return $action->execute($request);
			}

			$moderator			= $request['dba']->getRow("SELECT * FROM ". K4USERS ." WHERE name = '". $request['dba']->quote($_REQUEST['mod_name']) ."'");
			
			if(!is_array($moderator) || empty($moderator)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDMODNAME'), 'content', TRUE);
				return $action->execute($request);
			}

			if(!isset($_REQUEST['color']) || $_REQUEST['color'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTGROUPCOLOR'), 'content', TRUE);
				return $action->execute($request);
			}
			
			$filename		= '';

			if(isset($_FILES['avatar_upload']) && is_array($_FILES['avatar_upload']))
				$filename	= $_FILES['avatar_upload']['tmp_name'];
			
			if(isset($_REQUEST['avatar_browse']) && $_REQUEST['avatar_browse'] != '') {
				$filename	= $_REQUEST['avatar_browse'];
			}
			
			if($filename != '') {

				$file_ext		= explode(".", $filename);
				$exts			= array('gif', 'jpg', 'jpeg', 'bmp', 'png', 'tiff');
				
				if(count($file_ext) >= 2) {
					$file_ext		= $file_ext[count($file_ext) - 1];

					if(!in_array(strtolower($file_ext), $exts)) {
						$action = new K4InformationAction(new K4LanguageElement('L_INVALIDAVATAREXT'), 'content', TRUE);
						return $action->execute($request);
					}
				} else {
					$action = new K4InformationAction(new K4LanguageElement('L_INVALIDAVATAREXT'), 'content', TRUE);
					return $action->execute($request);
				}
			}
			
			/* Build the queries */
			$insert_a			= $request['dba']->prepareStatement("INSERT INTO ". K4USERGROUPS ." (name,nicename,description,mod_name,mod_id,created,min_perm,max_perm,display_legend,color,avatar) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
			$update_a			= $request['dba']->prepareStatement("UPDATE ". K4USERS ." SET usergroups=?,perms=? WHERE id=?");

			/* Set the query values */
			$insert_a->setString(1, $_REQUEST['name']);
			$insert_a->setString(2, $_REQUEST['nicename']);
			$insert_a->setString(3, $_REQUEST['description']);
			$insert_a->setString(4, $moderator['name']);
			$insert_a->setInt(5, $moderator['id']);
			$insert_a->setInt(6, time());
			$insert_a->setInt(7, $_REQUEST['min_perm']);
			$insert_a->setInt(8, $_REQUEST['max_perm']);
			$insert_a->setInt(9, $_REQUEST['display_legend']);
			$insert_a->setString(10, $_REQUEST['color']);
			$insert_a->setString(11, $filename);
			
			/* Add the category to the info table */
			$insert_a->executeUpdate();
			
			$group_id			= $request['dba']->getInsertId(K4USERGROUPS, 'id');
			
			$usergroups			= $moderator['usergroups'] != '' ? explode('|', $moderator['usergroups']) : array();
			
			if(is_array($usergroups)) {
				$usergroups[]	= $group_id;
			} else {
				$usergroups		= array($group_id);
			}

			$update_a->setString(1, implode('|', $usergroups));
			$update_a->setInt(2, iif(intval($_REQUEST['min_perm']) > $moderator['perms'], $_REQUEST['min_perm'], $moderator['perms']));
			$update_a->setInt(3, $moderator['id']);
			
			/* Update the user's information */
			$update_a->executeUpdate();
			
			if(isset($_FILES['avatar_upload']) && is_array($_FILES['avatar_upload'])) {
				$dir		= BB_BASE_DIR . '/tmp/upload/group_avatars';
				
				__chmod($dir, 0777);
				@move_uploaded_file($_FILES['avatar_upload']['tmp_name'], $dir .'/'. $filename);
			}
			
			reset_cache('usergroups');

			k4_bread_crumbs($request['template'], $request['dba'], 'L_USERGROUPS');
			$request['template']->setVar('users_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/users.html');

			$action = new K4InformationAction(new K4LanguageElement('L_ADDEDUSERGROUP', $_REQUEST['name']), 'content', FALSE, 'admin.php?act=usergroups', 3);
			return $action->execute($request);

		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminRemoveUserGroup extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			
			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDUSERGROUP'), 'content', TRUE);
				return $action->execute($request);
			}

			$group		= $request['dba']->getRow("SELECT * FROM ". K4USERGROUPS ." WHERE id = ". intval($_REQUEST['id']));

			if(!is_array($group) || empty($group)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDUSERGROUP'), 'content', TRUE);
				return $action->execute($request);
			}

			/* Get all users of this usergroup */
			$users		= $request['dba']->executeQuery("SELECT * FROM ". K4USERS ." WHERE usergroups LIKE '%;i:". intval($group['id']) .";%'");
			
			while($users->next()) {
				$user	= $users->current();
				$result	= explode('|', $request['user']->get('usergroups'));
				$groups	= $request['user']->get('usergroups') != '' ? iif(!$result, force_usergroups($user), $result) : array();
				
				/* Are we dealing with an array? */
				if(is_array($groups)) {
					
					/* make a new array because if we unset values in the $groups array, it will kill the for() */
					$new_groups = array();
					
					/* Loop through the array */
					for($i = 0; $i < count($groups); $i++) {
						
						/* This will remove this usergroup, and any ninexistant ones from this user's array */
						if($groups[$i] != $group['id'] && $groups[$i] != 0) {
							$new_groups[] = $groups[$i];
						}
					}
					
					/* Reset the groups variable */
					$groups = $new_groups;
				}
				
				$request['dba']->executeUpdate("UPDATE ". K4USERS ." SET usergroups = '". $request['dba']->quote(implode('|', $groups)) ."' WHERE id = ". intval($user['id']));
			}
			
			/* Remove the usergroup */
			$request['dba']->executeUpdate("DELETE FROM ". K4USERGROUPS ." WHERE id = ". intval($group['id']));
			
			reset_cache('usergroups');

			k4_bread_crumbs($request['template'], $request['dba'], 'L_USERGROUPS');
			$request['template']->setVar('users_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/users.html');

			$action = new K4InformationAction(new K4LanguageElement('L_REMOVEDUSERGROUP', $group['name']), 'content', FALSE, 'admin.php?act=usergroups', 3);
			return $action->execute($request);

		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminEditUserGroup extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			
			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDUSERGROUP'), 'content', TRUE);
				return $action->execute($request);
			}

			$group		= $request['dba']->getRow("SELECT * FROM ". K4USERGROUPS ." WHERE id = ". intval($_REQUEST['id']));

			if(!is_array($group) || empty($group)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDUSERGROUP'), 'content', TRUE);
				return $action->execute($request);
			}
			
			/** 
			 * Get the mega admin user if we need him/her, normally their id
			 * should be 1, but you can never be too sure
			 */
			$mega_admin		= $request['dba']->getRow("SELECT * FROM ". K4USERS ." WHERE perms = 10 ORDER BY id ASC LIMIT 1");
			
			/* If the mega admin fails, set the mega admin to whoever is logged in using this feature */
			if(!is_array($mega_admin) || empty($mega_admin))
				$mega_admin	= $request['user']->getInfoArray();

			$group['mod_name']	= $group['mod_name'] == '' ? $mega_admin['name'] : $group['mod_name'];
			$group['mod_id']	= $group['mod_id'] == 0 ? $mega_admin['id'] : $group['mod_id'];

			foreach($group as $key => $val) {
				$request['template']->setVar('group_'. $key, $val);
			}

			k4_bread_crumbs($request['template'], $request['dba'], 'L_USERGROUPS');
			$request['template']->setVar('usergroups_action', 'admin.php?act=usergroups_update');
			$request['template']->setVar('users_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/users.html');
			$request['template']->setVar('is_edit', 1);
			$request['template']->setVar('input_id', 'color');
			$request['template']->setFile('content', 'usergroups_add.html');
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminUpdateUserGroup extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			
			/* Error checking on the fields */
			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDUSERGROUP'), 'content', TRUE);
				return $action->execute($request);
			}

			$group		= $request['dba']->getRow("SELECT * FROM ". K4USERGROUPS ." WHERE id = ". intval($_REQUEST['id']));

			if(!is_array($group) || empty($group)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDUSERGROUP'), 'content', TRUE);
				return $action->execute($request);
			}

			if(!isset($_REQUEST['name']) || $_REQUEST['name'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTGROUPNAME'), 'content', TRUE);
				return $action->execute($request);
			}

			if(!isset($_REQUEST['nicename']) || $_REQUEST['nicename'] == '') {
				//$action = new K4InformationAction(new K4LanguageElement('L_INSERTGROUPNICENAME'), 'content', TRUE);
				//return $action->execute($request);

				// makes it not require the nice name
				$_REQUEST['nicename'] = '';
			}
			
			$g = $request['dba']->getRow("SELECT * FROM ". K4USERGROUPS ." WHERE name = '". $request['dba']->quote($_REQUEST['name']) ."' AND id != ". intval($group['id']));			
			
			if(is_array($g) && !empty($g)) {
				$action = new K4InformationAction(new K4LanguageElement('L_GROUPNAMEEXISTS'), 'content', TRUE);
				return $action->execute($request);
			}

			if(!isset($_REQUEST['description']) || $_REQUEST['description'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTGROUPDESC'), 'content', TRUE);
				return $action->execute($request);
			}
			
			if(!isset($_REQUEST['mod_name']) || $_REQUEST['mod_name'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTMODNAME'), 'content', TRUE);
				return $action->execute($request);
			}

			$moderator			= $request['dba']->getRow("SELECT * FROM ". K4USERS ." WHERE name = '". $request['dba']->quote($_REQUEST['mod_name']) ."'");
			
			if(!is_array($moderator) || empty($moderator)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDMODNAME'), 'content', TRUE);
				return $action->execute($request);
			}

			if(!isset($_REQUEST['color']) || $_REQUEST['color'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTGROUPCOLOR'), 'content', TRUE);
				return $action->execute($request);
			}
			
			$filename		= '';

			if(isset($_FILES['avatar_upload']) && is_array($_FILES['avatar_upload']))
				$filename	= $_FILES['avatar_upload']['tmp_name'];
			
			if(isset($_REQUEST['avatar_browse']) && $_REQUEST['avatar_browse'] != '') {
				$filename	= $_REQUEST['avatar_browse'];
			}
			
			if($filename != '') {

				$file_ext		= explode(".", $filename);
				$exts			= array('gif', 'jpg', 'jpeg', 'bmp', 'png', 'tiff');
				
				if(count($file_ext) >= 2) {
					$file_ext		= $file_ext[count($file_ext) - 1];

					if(!in_array(strtolower($file_ext), $exts)) {
						$action = new K4InformationAction(new K4LanguageElement('L_INVALIDAVATAREXT'), 'content', TRUE);

						return $action->execute($request);
					}
				} else {
					$action = new K4InformationAction(new K4LanguageElement('L_INVALIDAVATAREXT'), 'content', TRUE);

					return $action->execute($request);
				}
			}
			
			/* Build the queries */
			$update_a			= $request['dba']->prepareStatement("UPDATE ". K4USERGROUPS ." SET name=?,nicename=?,description=?,mod_name=?,mod_id=?,min_perm=?,max_perm=?,display_legend=?,color=?,avatar=? WHERE id=?");
			$update_b			= $request['dba']->prepareStatement("UPDATE ". K4USERS ." SET usergroups=?,perms=? WHERE id=?");

			/* Set the query values */
			$update_a->setString(1, $_REQUEST['name']);
			$update_a->setString(2, $_REQUEST['nicename']);
			$update_a->setString(3, $_REQUEST['description']);
			$update_a->setString(4, $moderator['name']);
			$update_a->setInt(5, $moderator['id']);
			$update_a->setInt(6, $_REQUEST['min_perm']);
			$update_a->setInt(7, $_REQUEST['max_perm']);
			$update_a->setInt(8, $_REQUEST['display_legend']);
			$update_a->setString(9, $_REQUEST['color']);
			$update_a->setString(10, $filename);
			$update_a->setInt(11, $group['id']);
			
			$update_a->executeUpdate();
			
			// update all user perms
			$request['dba']->executeUpdate("UPDATE ". K4USERS ." SET perms=". intval($_REQUEST['min_perm']) ." WHERE usergroups LIKE '|". intval($group['id']) ."|' AND perms < ". intval($_REQUEST['min_perm']));
			
			$group_id			= $request['dba']->getInsertId(K4USERGROUPS, 'id');
			
			// update the mod
			$usergroups			= $moderator['usergroups'] != '' ? explode('|', $moderator['usergroups']) : array();
			if(is_array($usergroups)) {
				$usergroups[]	= $group_id;
			} else {
				$usergroups		= array($group_id);
			}

			$update_b->setString(1, '|'. implode('|', $usergroups) .'|');
			$update_b->setInt(2, (intval($_REQUEST['min_perm']) > $moderator['perms'] ? $_REQUEST['min_perm'] : $moderator['perms']));
			$update_b->setInt(3, $moderator['id']);
			
			/**
			 * Update the user's information, if the mod name changes, the previous moderator will
			 * still be a member of the group, just not the moderator.
			 */
			$update_b->executeUpdate();
			
			if(isset($_FILES['avatar_upload']) && is_array($_FILES['avatar_upload'])) {
				$dir		= BB_BASE_DIR . '/tmp/upload/group_avatars';
				
				@chmod($dir, 0777);
				move_uploaded_file($_FILES['avatar_upload']['tmp_name'], $dir .'/'. $filename);
			}
			
			reset_cache('usergroups');

			k4_bread_crumbs($request['template'], $request['dba'], 'L_USERGROUPS');
			$request['template']->setVar('users_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/users.html');

			$action = new K4InformationAction(new K4LanguageElement('L_UPDATEDUSERGROUP', $_REQUEST['name']), 'content', FALSE, 'admin.php?act=usergroups', 3);
			return $action->execute($request);

		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

?>