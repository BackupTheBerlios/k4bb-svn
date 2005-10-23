<?php
/**
* k4 Bulletin Board, maps.class.php
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
* @author Geoffrey Goodman
* @author James Logsdon
* @version $Id: maps.class.php 149 2005-07-12 14:17:49Z Peter Goodman $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	return;
}

class AdminMapsGui extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			
			/* Get the parent id's */
			$parents = array();
			foreach($_COOKIE as $key => $val) {
				if(strpos($key, 'mapsgui') !== FALSE) {
					$parents[] = intval($_COOKIE[$key]);
				}
			}
						
			$all_maps = array();

			$maps = $request['dba']->executeQuery("SELECT * FROM ". K4MAPS ." WHERE row_level = 1 AND (varname <> 'forums' AND varname <> 'categories' AND varname <> 'forum0') ORDER BY name ASC");
			
			get_recursive_maps($request, $all_maps, $parents, $maps, 1);
			
			$all_maps = &new FAArrayIterator($all_maps);
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_PERMISSIONS');
			$request['template']->setVar('options_on', '_on');
			
			$request['template']->setFile('sidebar_menu', 'menus/options.html');
			$request['template']->setList('maps_list', $all_maps);
			$request['template']->setFile('content', 'maps_tree.html');
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminMapsUpdate extends FAAction {
	function execute(&$request) {		

		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {

			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDMAPID'), 'content', FALSE);
				return $action->execute($request);
			}

			$map	= $request['dba']->getRow("SELECT * FROM ". K4MAPS ." WHERE id = ". intval($_REQUEST['id']));			
			
			if(!is_array($map) || empty($map)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDMAPID'), 'content', FALSE);
				return $action->execute($request);
			}
			
			$stmt	= $request['dba']->prepareStatement("UPDATE ". K4MAPS ." SET can_view=?,can_add=?,can_edit=?,can_del=? WHERE id=?");
			
			$stmt->setInt(1, $_REQUEST['can_view']);
			$stmt->setInt(2, $_REQUEST['can_add']);
			$stmt->setInt(3, $_REQUEST['can_edit']);
			$stmt->setInt(4, $_REQUEST['can_del']);
			$stmt->setInt(5, $map['id']);

			$stmt->executeUpdate();
			
			reset_cache('maps');
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_PERMISSIONS');
			$request['template']->setVar('options_on', '_on');
			
			$request['template']->setFile('sidebar_menu', 'menus/options.html');
			$action = new K4InformationAction(new K4LanguageElement('L_UPDATEDMAPS'), 'content', FALSE, 'admin.php?act=permissions_gui', 3);
			return $action->execute($request);
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminMapsAddNode extends FAAction {
	function execute(&$request) {		

		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {

			if(!isset($_REQUEST['id'])) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDMAPID'), 'content', FALSE);
				return $action->execute($request);
			}

			$map	= $request['dba']->getRow("SELECT * FROM ". K4MAPS ." WHERE id = ". intval($_REQUEST['id']));			
			
			if(!is_array($map) || empty($map)) {
				$request['template']->setVar('maps_id', 0);
			} else {
				foreach($map as $key => $val){
					$request['template']->setVar('maps_'. $key, $val);
				}
			}
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_PERMISSIONS');
			$request['template']->setVar('options_on', '_on');
			
			$request['template']->setFile('sidebar_menu', 'menus/options.html');
			$request['template']->setFile('content', 'maps_addnew.html');
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}


class AdminMapsInsertNode extends FAAction {
	function execute(&$request) {		

		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			
			/**
			 * Error checking on request fields 
			 */
			if(!isset($_REQUEST['parent_id'])) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDMAPID'), 'content', FALSE);
				return $action->execute($request);
			}
			
			if(!isset($_REQUEST['varname'])) {
				$action = new K4InformationAction(new K4LanguageElement('L_MAPSNEEDVARNAME'), 'content', FALSE);
				return $action->execute($request);
			}

			if(!isset($_REQUEST['name'])) {
				$action = new K4InformationAction(new K4LanguageElement('L_MAPSNEEDNAME'), 'content', FALSE);
				return $action->execute($request);
			}
			
			/**
			 * Start building info for the queries
			 */

			$level		= 1;
			
			/* Is this a top level node? */
			if(intval($_REQUEST['parent_id']) == 0) {
				$parent			= array('id'=> 0,'category_id' => 0, 'forum_id' => 0, 'group_id' => 0, 'user_id' => 0);
					
			/* If we are actually dealing with a parent node */
			} else if(intval($_REQUEST['parent_id']) > 0) {
				
				/* Get the parent node */
				$parent			= $request['dba']->GetRow("SELECT * FROM ". K4MAPS ." WHERE id = ". intval($_REQUEST['parent_id']));
				
				/* Check if the parent node exists */
				if(!is_array($parent) || empty($parent)) {
					$action = new K4InformationAction(new K4LanguageElement('L_INVALIDMAPID'), 'content', FALSE);
					return $action->execute($request);
				}
				
				/* Set this nodes level */
				$level			= $parent['row_level']+1;
			} else {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDMAPID'), 'content', FALSE);
				return $action->execute($request);
			}

			/**
			 * Build the queries
			 */

			/* Prepare the queries */
			$insert				= $request['dba']->prepareStatement("INSERT INTO ". K4MAPS ." (row_level,name,varname,category_id,forum_id,user_id,group_id,can_view,can_add,can_edit,can_del,value,parent_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
			
			/* Set the inserts for adding the actual node */
			$insert->setInt(1, $level);
			$insert->setString(2, $_REQUEST['name']);
			$insert->setString(3, $_REQUEST['varname']);
			$insert->setInt(4, $parent['category_id']);
			$insert->setInt(5, $parent['forum_id']);
			$insert->setInt(6, $parent['user_id']);
			$insert->setInt(7, $parent['group_id']);
			$insert->setInt(8, $_REQUEST['can_view']);
			$insert->setInt(9, $_REQUEST['can_add']);
			$insert->setInt(10, $_REQUEST['can_edit']);
			$insert->setInt(11, $_REQUEST['can_del']);
			$insert->setString(12, isset($_REQUEST['value']) ? $_REQUEST['value'] : '');
			$insert->setInt(13, $parent['id']);
			

			/**
			 * Execute the queries
			 */

			/* Execute the queries */
			$insert->executeUpdate();
			
			if($parent['id'] > 0) {
				$request['dba']->executeUpdate("UPDATE ". K4MAPS ." SET num_children=num_children+1 WHERE id = ". intval($parent['id']));
			}

			reset_cache('maps');
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_PERMISSIONS');
			$request['template']->setVar('options_on', '_on');
			
			$request['template']->setFile('sidebar_menu', 'menus/options.html');

			/* Redirect the user */
			$action = new K4InformationAction(new K4LanguageElement('L_ADDEDMAPSITEM'), 'content', FALSE, 'admin.php?act=permissions_gui', 3);
			return $action->execute($request);

		} else {
			no_perms_error($request);
		}
	}
}

class K4Maps {
	function add(&$request, $info, $parent, $level) {
		
		/* Prepare the queries */
		$insert				= $request['dba']->prepareStatement("INSERT INTO ". K4MAPS ." (row_level,name,varname,category_id,forum_id,user_id,group_id,can_view,can_add,can_edit,can_del,value,parent_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
		
		/* Set the inserts for adding the actual node */
		$insert->setInt(1, $level);
		$insert->setString(2, isset($info['name']) ? $info['name'] : '');
		$insert->setString(3, $info['varname']);
		$insert->setInt(4, isset($info['category_id']) ? $info['category_id'] : 0);
		$insert->setInt(5, isset($info['forum_id']) ? $info['forum_id'] : 0);
		$insert->setInt(6, isset($info['user_id']) ? $info['user_id'] : 0);
		$insert->setInt(7, isset($info['group_id']) ? $info['group_id'] : 0);
		$insert->setInt(8, $info['can_view']);
		$insert->setInt(9, $info['can_add']);
		$insert->setInt(10, $info['can_edit']);
		$insert->setInt(11, $info['can_del']);
		$insert->setString(12, isset($info['value']) ? $info['value'] : '');
		$insert->setInt(13, $parent['id']);
		

		/**
		 * Execute the queries
		 */

		/* Execute the queries */
		$insert->executeUpdate();
		
		if($parent['id'] > 0) {
			$request['dba']->executeUpdate("UPDATE ". K4MAPS ." SET num_children=num_children+1 WHERE id = ". intval($parent['id']));
		}
	}
}

class AdminMapsRemoveNode extends FAAction {
	function recursive_remove($parent_id) {
		$maps = $request['dba']->executeQuery("SELECT * FROM ". K4MAPS ." WHERE parent_id = ". intval($parent_id));
		while($maps->next()) {
			$map = $maps->current();
			$request['dba']->executeUpdate("DELETE FROM ". K4MAPS ." WHERE id = ". intval($map['id']));
			if($map['num_children'] > 0) {
				$this->recursive_remove($map['id']);
			}
		}
	}
	function execute(&$request) {		

		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			
			/* Error check */
			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDMAPID'), 'content', FALSE);
				return $action->execute($request);
			}

			$map	= $request['dba']->getRow("SELECT * FROM ". K4MAPS ." WHERE id = ". intval($_REQUEST['id']));			

			/* Error check */
			if(!is_array($map) || empty($map)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDMAPID'), 'content', FALSE);
				return $action->execute($request);
			}
			
			/* Update this map's parent */
			if($map['parent_id'] > 0) {
				$num_children = intval($map['num_children']) + 1;
				$request['dba']->executeUpdate("UPDATE ". K4MAPS ." SET num_children=num_children-". $num_children ." WHERE id = ". intval($map['parent_id']));
			}
			
			/* Remove this mapp node */
			$request['dba']->executeUpdate("DELETE FROM ". K4MAPS ." WHERE id = ". intval($map['id']));
			
			/* Recursively remove all of its children */
			if($map['num_children'] > 0) {
				$this->recursive_remove($map['id']);
			}

			reset_cache('maps');

			k4_bread_crumbs($request['template'], $request['dba'], 'L_PERMISSIONS');
			$request['template']->setVar('options_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/options.html');
			
			/* Redirect the user */
			$action = new K4InformationAction(new K4LanguageElement('L_REMOVEDMAPSITEM'), 'content', FALSE, 'admin.php?act=permissions_gui', 3);
			return $action->execute($request);
						
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

?>