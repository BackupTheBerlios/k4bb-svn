<?php
/**
* k4 Bulletin Board, masks.class.php
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
* @version $Id$
* @package k42
*/

class AdminPermissionMasks extends FAAction {
	function execute(&$request) {
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			
			global $_ALLFORUMS;

			k4_bread_crumbs($request['template'], $request['dba'], 'L_PERMISSIONMASKS');
			$request['template']->setVar('users_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/users.html');
			$request['template']->setList('forums', new FAArrayIterator($_ALLFORUMS));
			$request['template']->setFile('content', 'mask_selectoptions.html');
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminEditPermissionMask extends FAAction {
	function execute(&$request) {
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_PERMISSIONMASKS');

			if(!isset($_REQUEST['f']) || intval($_REQUEST['f']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFORUM'), 'content', FALSE);
				return $action->execute($request);
			}

			$forum		= $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($_REQUEST['f']));

			if(!is_array($forum) || empty($forum)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFORUM'), 'content', FALSE);
				return $action->execute($request);
			}			
			
			if(!isset($_REQUEST['g']) || intval($_REQUEST['g']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDUSERGROUP'), 'content', TRUE);
				return $action->execute($request);
			}

			$group		= $request['dba']->getRow("SELECT * FROM ". K4USERGROUPS ." WHERE id = ". intval($_REQUEST['g']));

			if(!is_array($group) || empty($group)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDUSERGROUP'), 'content', TRUE);
				return $action->execute($request);
			}
			
			// do we delete this mask?
			if(isset($_REQUEST['delete'])) {
				$request['dba']->executeUpdate("DELETE FROM ". K4MAPS ." WHERE group_id = ". intval($group['id']) ." AND forum_id = ". intval($forum['forum_id']));
				$action = new K4InformationAction(new K4LanguageElement('L_REMOVEDMASK', $group['name'], $forum['name']), 'content', FALSE, 'admin.php?act=masks', 3);
				return $action->execute($request);
			}

			$mask		= $request['dba']->executeQuery("SELECT * FROM ". K4MAPS ." WHERE forum_id = ". intval($forum['forum_id']) ." AND group_id = ". intval($group['id']));
			$maps		= $request['dba']->executeQuery("SELECT * FROM ". K4MAPS ." WHERE group_id = 0 AND forum_id = ". intval($forum['forum_id']));
			
			$mask_array = array();
			while($mask->next()) {
				$temp = $mask->current();
				$mask_array[$temp['varname']] = $temp;
			}
						
			$maps_array		= array();
			while($maps->next()) {
				$temp	= $maps->current();

				$temp['row_class']		= 'alt2';
				if(isset($mask_array[$temp['varname']])) {
					$temp			= $mask_array[$temp['varname']];
					$temp['row_class']	= 'alt1';
				}

				if($temp['row_level']-2 > 0) {
					$temp['level']	= str_repeat('<img src="Images/'. $request['template']->getVar('IMG_DIR') .'/Icons/threaded_bit.gif" alt="" border="0" />', $temp['row_level']-2);
				}
			
				$maps_array[] = $temp;
			}

			foreach($forum as $key=>$val)
				$request['template']->setVar('forum_'. $key, $val);
			
			foreach($group as $key=>$val)
				$request['template']->setVar('group_'. $key, $val);
			
			$request['template']->setVar('users_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/users.html');
			$request['template']->setList('mask_maps', new FAArrayIterator($maps_array));
			$request['template']->setFile('content', 'mask_permissions.html');
			
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminUpdatePermissionMasks extends FAAction {
	function execute(&$request) {
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_PERMISSIONMASKS');

			if(!isset($_REQUEST['f']) || intval($_REQUEST['f']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFORUM'), 'content', FALSE);
				return $action->execute($request);
			}

			$forum		= $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($_REQUEST['f']));

			if(!is_array($forum) || empty($forum)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFORUM'), 'content', FALSE);
				return $action->execute($request);
			}			
			
			if(!isset($_REQUEST['g']) || intval($_REQUEST['g']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDUSERGROUP'), 'content', TRUE);
				return $action->execute($request);
			}

			$group		= $request['dba']->getRow("SELECT * FROM ". K4USERGROUPS ." WHERE id = ". intval($_REQUEST['g']));

			if(!is_array($group) || empty($group)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDUSERGROUP'), 'content', TRUE);
				return $action->execute($request);
			}

			$maps		= $request['dba']->executeQuery("SELECT * FROM ". K4MAPS ." WHERE group_id = 0 AND forum_id = ". intval($forum['forum_id']));
			
			// delete all of the perms because we are going to readd them
			// by doing this, we are guranteed to store the minimum number 
			// of changed permissions
			$request['dba']->executeUpdate("DELETE FROM ". K4MAPS ." WHERE group_id = ". intval($group['id']) ." AND forum_id = ". intval($forum['forum_id']));
			$insert			= $request['dba']->prepareStatement("INSERT INTO ". K4MAPS ." (row_level,name,varname,category_id,forum_id,user_id,group_id,can_view,can_add,can_edit,can_del,value,parent_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");

			while($maps->next()) {
				$temp	= $maps->current();
				
				$add = FALSE;
				if(isset($_REQUEST[$temp['varname'] .'_can_view']) && $_REQUEST[$temp['varname'] .'_can_view'] != $temp['can_view']) $add = TRUE;
				if(isset($_REQUEST[$temp['varname'] .'_can_add']) && $_REQUEST[$temp['varname'] .'_can_add'] != $temp['can_add']) $add = TRUE;
				if(isset($_REQUEST[$temp['varname'] .'_can_edit']) && $_REQUEST[$temp['varname'] .'_can_edit'] != $temp['can_edit']) $add = TRUE;
				if(isset($_REQUEST[$temp['varname'] .'_can_del']) && $_REQUEST[$temp['varname'] .'_can_del'] != $temp['can_del']) $add = TRUE;
				
				if($add) {
					$insert->setInt(1, $temp['row_level']);
					$insert->setString(2, $temp['name']);
					$insert->setString(3, $temp['varname']);
					$insert->setInt(4, $temp['category_id']);
					$insert->setInt(5, $temp['forum_id']);
					$insert->setInt(6, $temp['user_id']);
					$insert->setInt(7, $group['id']);
					$insert->setInt(8, $_REQUEST[$temp['varname'] .'_can_view']);
					$insert->setInt(9, $_REQUEST[$temp['varname'] .'_can_add']);
					$insert->setInt(10, $_REQUEST[$temp['varname'] .'_can_edit']);
					$insert->setInt(11, $_REQUEST[$temp['varname'] .'_can_del']);
					$insert->setString(12, $temp['value']);
					$insert->setInt(13, $temp['parent_id']);

					$insert->executeUpdate();
				}
			}

			reset_cache('maps'); // usermasks are part of the maps

			$action = new K4InformationAction(new K4LanguageElement('L_UPDATEDPERMMASK', $group['name'], $forum['name']), 'content', TRUE, 'admin.php?act=masks', 3);
			return $action->execute($request);

		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

?>