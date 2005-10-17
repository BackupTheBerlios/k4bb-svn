<?php
/**
* k4 Bulletin Board, usergroups.class.php
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
* @version $Id: usergroups.class.php 144 2005-07-05 02:29:07Z Peter Goodman $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	return;
}

class AddUserToGroup extends FAAction {
	function execute(&$request) {
		
		global $_USERGROUPS, $_QUERYPARAMS;
		
		/* set the breadcrumbs bit */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');

		if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
			$action = new K4InformationAction(new K4LanguageElement('L_GROUPDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}

		if(!isset($_USERGROUPS[intval($_REQUEST['id'])])) {
			$action = new K4InformationAction(new K4LanguageElement('L_GROUPDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}

		if(!isset($_REQUEST['name']) || !$_REQUEST['name'] || $_REQUEST['name'] == '') {
			$action = new K4InformationAction(new K4LanguageElement('L_USERDOESNTEXIST'), 'content', TRUE);
			return $action->execute($request);
		}
		
		$group			= $_USERGROUPS[intval($_REQUEST['id'])];
		
		$member			= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['user'] . $_QUERYPARAMS['userinfo'] ." FROM ". K4USERS ." u LEFT JOIN ". K4USERINFO ." ui ON u.id=ui.user_id WHERE u.name = '". $request['dba']->quote($_REQUEST['name']) ."'");
		
		if(!$member || !is_array($member) || empty($member)) {
			$action = new K4InformationAction(new K4LanguageElement('L_USERDOESNTEXIST'), 'content', TRUE);
			return $action->execute($request);
		}
		
		/* Should we set the group moderator? */
		if($group['mod_name'] == '' || $group['mod_id'] == 0) {
			$admin		= $request['dba']->getRow("SELECT * FROM ". K4USERS ." WHERE perms >= ". intval(ADMIN) ." ORDER BY perms,id ASC LIMIT 1");
			$request['dba']->executeUpdate("UPDATE ". K4USERGROUPS  ." SET mod_name = '". $request['dba']->quote($admin['name']) ."', mod_id = ". intval($admin['id']) ." WHERE id = ". intval($group['id']));
		
			reset_cache(CACHE_FILE);
			
			$group['mod_name']	= $admin['name'];
			$group['mod_id']	= $admin['id'];
		}

		if($group['mod_id'] == $member['id']) {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUAREMODERATOR'), 'content', TRUE);
			return $action->execute($request);
		}
		
		$result					= explode('|', $member['usergroups']);
		$groups					= $member['usergroups'] != '' ? iif(!$result, force_usergroups($member), $result) : array();		
		
		$in_group				= FALSE;
		foreach($groups as $id) {
			if(isset($_USERGROUPS[$id]) && $id == $group['id']) {
				$in_group		= TRUE;
			}
		}

		if($in_group) {
			$action = new K4InformationAction(new K4LanguageElement('L_BELONGSTOGROUP'), 'content', TRUE);
			return $action->execute($request);
		}

		$groups[]				= intval($group['id']);
		
		$extra					= NULL;
		if($request['user']->get('perms') < $group['min_perm'])
			$extra				.= ', perms='. intval($group['min_perm']);
		
		/* Add this user to the group and change his perms if we need to */
		$request['dba']->executeUpdate("UPDATE ". K4USERS ." SET usergroups='". $request['dba']->quote(implode('|', $groups)) ."' $extra WHERE id = ". intval($member['id']));
		
		k4_bread_crumbs($request['template'], $request['dba'], 'L_ADDUSER');
		$action = new K4InformationAction(new K4LanguageElement('L_ADDEDUSERTOGROUP', $member['name'], $group['name']), 'content', FALSE, 'usergroups.php?id='. intval($group['id']), 3);
		return $action->execute($request);
	}
}

class RemoveUserFromGroup extends FAAction {
	function execute(&$request) {
		
		global $_USERGROUPS, $_QUERYPARAMS;
		
		if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_GROUPDOESNTEXIST'), 'content', FALSE);

			return $action->execute($request);
		}

		if(!isset($_USERGROUPS[intval($_REQUEST['id'])])) {
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_GROUPDOESNTEXIST'), 'content', FALSE);

			return $action->execute($request);
		}

		if(!isset($_REQUEST['user_id']) || intval($_REQUEST['user_id']) == 0) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_USERDOESNTEXIST'), 'content', TRUE);

			return $action->execute($request);
			return TRUE;
		}
		
		$group			= $_USERGROUPS[intval($_REQUEST['id'])];
		
		$member			= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['user'] . $_QUERYPARAMS['userinfo'] ." FROM ". K4USERS ." u LEFT JOIN ". K4USERINFO ." ui ON u.id=ui.user_id WHERE u.id = '". intval($_REQUEST['user_id']) ."'");
		
		if(!$member || !is_array($member) || empty($member)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_USERDOESNTEXIST'), 'content', TRUE);

			return $action->execute($request);
		}
		
		/* Should we set the group moderator? */
		if($group['mod_name'] == '' || $group['mod_id'] == 0) {
			$admin		= $request['dba']->getRow("SELECT * FROM ". K4USERS ." WHERE perms >= ". intval(ADMIN) ." ORDER BY perms,id ASC LIMIT 1");
			$request['dba']->executeUpdate("UPDATE ". K4USERGROUPS  ." SET mod_name = '". $request['dba']->quote($admin['name']) ."', mod_id = ". intval($admin['id']) ." WHERE id = ". intval($group['id']));
		
			if(!@touch(CACHE_FILE, time()-86460)) {
				@unlink(CACHE_FILE);
			}
			
			$group['mod_name']	= $admin['name'];
			$group['mod_id']	= $admin['id'];
		}

		if($group['mod_id'] == $member['id']) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_YOUAREMODERATOR'), 'content', TRUE);
			
			return $action->execute($request);
		}
		
		$result					= explode('|', $member['usergroups']);
		$groups					= $member['usergroups'] != '' ? iif(!$result, force_usergroups($member), $result) : array();		
		
		$groups					= array_values($groups);

		$in_group				= FALSE;
		$i						= 0;
		foreach($groups as $id) {
			if(isset($_USERGROUPS[$id]) && $id == $group['id']) {
				$in_group		= TRUE;
				
				// remove the person from the user group
				unset($groups[$i]);
			}

			$i++;
		}

		$groups					= array_values($groups);
		
		if(!$in_group) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_NOTBELONGSTOGROUP'), 'content', TRUE);

			return $action->execute($request);
		}
		
		$newgroup				= get_user_max_group(array('usergroups' => implode('|', $groups) ), $_USERGROUPS);
		
		$perms					= 5;
		
		if($request['user']->get('perms') > @$newgroup['max_perms']) {
			$perms				= @$newgroup['max_perms'];
		} else if($request['user']->get('perms') < @$newgroup['min_perms']) {
			$perms				= @$newgroup['min_perms'];
		}
		
		/* Add this user to the group and change his perms if we need to */
		$request['dba']->executeUpdate("UPDATE ". K4USERS ." SET usergroups='". $request['dba']->quote(implode('|', $groups)) ."', perms=". intval($perms) ." WHERE id = ". intval($member['id']));
		
		k4_bread_crumbs($request['template'], $request['dba'], 'L_REMOVEUSER');
		$action = new K4InformationAction(new K4LanguageElement('L_REMOVEDUSERFROMGROUP', $member['name'], $group['name']), 'content', FALSE, 'usergroups.php?id='. intval($group['id']), 3);

		return $action->execute($request);

		return TRUE;
	}
}

?>