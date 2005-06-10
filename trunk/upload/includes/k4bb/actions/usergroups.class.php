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
* @version $Id: usergroups.class.php,v 1.2 2005/05/24 20:01:31 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	exit;
}

class AddUserToGroup extends FAAction {
	function execute(&$request) {
		
		global $_USERGROUPS, $_QUERYPARAMS;
		
		if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_GROUPDOESNTEXIST'), 'content', FALSE);

			return $action->execute($request);
		}

		if(!isset($_USERGROUPS[intval($_REQUEST['id'])])) {
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_GROUPDOESNTEXIST'), 'content', FALSE);

			return $action->execute($request);
		}

		if(!isset($_REQUEST['name']) || !$_REQUEST['name'] || $_REQUEST['name'] == '') {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_USERDOESNTEXIST'), 'content', TRUE);

			return $action->execute($request);
			return TRUE;
		}
		
		$group			= $_USERGROUPS[intval($_REQUEST['id'])];
		
		$member			= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['user'] . $_QUERYPARAMS['userinfo'] ." FROM ". K4USERS ." u LEFT JOIN ". K4USERINFO ." ui ON u.id=ui.user_id WHERE u.name = '". $request['dba']->quote($_REQUEST['name']) ."'");
		
		if(!$member || !is_array($member) || empty($member)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_USERDOESNTEXIST'), 'content', TRUE);

			return $action->execute($request);
			return TRUE;
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
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_YOUAREMODERATOR'), 'content', TRUE);

			return $action->execute($request);
			return TRUE;
		}
		
		$result					= @unserialize(@$member['usergroups']);
		$groups					= $member['usergroups'] != '' ? iif(!$result, force_usergroups($member), $result) : array();		
		
		$in_group				= FALSE;
		foreach($groups as $id)
			if(isset($_USERGROUPS[$id]) && $id == $group['id'])
				$in_group		= TRUE;
		
		if($in_group) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_BELONGSTOGROUP'), 'content', TRUE);

			return $action->execute($request);
			return TRUE;
		}

		$groups[]				= intval($group['id']);
		
		$extra					= NULL;
		if($request['user']->get('perms') < $group['min_perm'])
			$extra				.= ', perms='. intval($group['min_perm']);
		
		/* Add this user to the group and change his perms if we need to */
		$request['dba']->executeUpdate("UPDATE ". K4USERS ." SET usergroups='". $request['dba']->quote(serialize($groups)) ."' $extra WHERE id = ". intval($member['id']));
		
		k4_bread_crumbs(&$request['template'], $request['dba'], 'L_ADDUSER');
		$action = new K4InformationAction(new K4LanguageElement('L_ADDEDUSERTOGROUP', $member['name'], $group['name']), 'content', FALSE, 'usergroups.php?id='. intval($group['id']), 3);

		return $action->execute($request);

		return TRUE;
	}
}

?>