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
* @version $Id: maps.class.php,v 1.9 2005/05/24 20:02:19 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	exit;
}

class AdminMapsGui extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			$maps	= &new MAPSIterator($request['dba'], $request['dba']->executeQuery("SELECT * FROM ". K4MAPS ." WHERE row_level = 1 ORDER BY row_left ASC"));
			$request['template']->setList('maps_list', $maps);
			
			$request['template']->setFile('content', 'admin.html');
			$request['template']->setFile('admin_panel', 'maps_tree.html');
		} else {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

			return $action->execute($request);
		}

		return TRUE;
	}
}

class AdminMapsInherit extends FAAction {
	function execute(&$request) {		

		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {

			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDMAPID'), 'content', FALSE);
				return $action->execute($request);
			}

			$map	= $request['dba']->getRow("SELECT * FROM ". K4MAPS ." WHERE id = ". intval($_REQUEST['id']));			
			
			if(!is_array($map) || empty($map)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDMAPID'), 'content', FALSE);
				return $action->execute($request);
			}
			
			if(isset($_REQUEST['inherit'])) {
				if($_REQUEST['inherit'] == 'true') {
					
					$request['dba']->executeUpdate("UPDATE ". K4MAPS ." SET inherit = 1 WHERE id = ". $map['id']);

					$action = new K4InformationAction(new K4LanguageElement('L_INHERITEDMAPS'), 'content', FALSE, 'admin.php?act=permissions_gui', 3);
					return $action->execute($request);
				} else if($_REQUEST['inherit'] == 'false') {
					
					$request['dba']->executeUpdate("UPDATE ". K4MAPS ." SET inherit = 0 WHERE id = ". $map['id']);

					$action = new K4InformationAction(new K4LanguageElement('L_UNINHERITEDMAPS'), 'content', FALSE, 'admin.php?act=permissions_gui', 3);


					return $action->execute($request);
				} else {
					$action = new K4InformationAction(new K4LanguageElement('L_MUSTSETINHERITOPTION'), 'content', TRUE);

					return $action->execute($request);
				}
			} else {
				$action = new K4InformationAction(new K4LanguageElement('L_MUSTSETINHERITOPTION'), 'content', TRUE);

				return $action->execute($request);
			}
		} else {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

			return $action->execute($request);
		}

		if(!@touch(CACHE_FILE, time()-86460)) {
			@unlink(CACHE_FILE);
		}

		return TRUE;
	}
}

class AdminMapsUpdate extends FAAction {
	function execute(&$request) {		

		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {

			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDMAPID'), 'content', FALSE);
				return $action->execute($request);
			}

			$map	= $request['dba']->getRow("SELECT * FROM ". K4MAPS ." WHERE id = ". intval($_REQUEST['id']));			
			
			if(!is_array($map) || empty($map)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDMAPID'), 'content', FALSE);
				return $action->execute($request);
			}
			
			$stmt	= &$request['dba']->prepareStatement("UPDATE ". K4MAPS ." SET can_view=?,can_add=?,can_edit=?,can_del=? WHERE id=? OR (row_left > ? AND row_right < ? AND inherit = 1)");
			
			$stmt->setInt(1, @$_REQUEST['can_view']);
			$stmt->setInt(2, @$_REQUEST['can_add']);
			$stmt->setInt(3, @$_REQUEST['can_edit']);
			$stmt->setInt(4, @$_REQUEST['can_del']);
			$stmt->setInt(5, $map['id']);
			$stmt->setInt(6, $map['row_left']);
			$stmt->setInt(7, $map['row_right']);

			$stmt->executeUpdate();

			$action = new K4InformationAction(new K4LanguageElement('L_UPDATEDMAPS'), 'content', FALSE, 'admin.php?act=permissions_gui', 3);
			return $action->execute($request);
		} else {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

			return $action->execute($request);
		}

		if(!@touch(CACHE_FILE, time()-86460)) {
			@unlink(CACHE_FILE);
		}

		return TRUE;
	}
}

class AdminMapsAddNode extends FAAction {
	function execute(&$request) {		

		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {

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
			
			$request['template']->setFile('content', 'admin.html');
			$request['template']->setFile('admin_panel', 'maps_addnew.html');
		} else {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

			return $action->execute($request);
		}

		return TRUE;
	}
}

class AdminInsertMap {

	var $dba;

	function AdminInsertMap(&$dba) {

		$this->dba		= &$dba;
	}
	function getNumOnLevel($row_left, $row_right, $level) {
		return $this->dba->GetValue("SELECT COUNT(*) FROM ". K4MAPS ." WHERE row_left > $row_left AND row_right < $row_right AND row_level = $level");
	}
	function insertNode($info, $category_id = FALSE, $forum_id = FALSE, $group_id = FALSE, $user_id = FALSE) {
		
		/**
		 * Error checking on request fields 
		 */
		if(!isset($info['parent_id']))
			return 'L_INVALIDMAPID';
		
		if(!isset($info['varname']))
			return 'L_MAPSNEEDVARNAME';

		if(!isset($info['name']))
			return 'L_MAPSNEEDNAME';
		
		/**
		 * Start building info for the queries
		 */

		/* Get the last node to the furthest right in the tree */
		$last_node	= $this->dba->GetRow("SELECT * FROM ". K4MAPS ." WHERE row_level = 1 ORDER BY row_right DESC LIMIT 1");

		$level		= 1;
		
		/* Is this a top level node? */
		if(intval($info['parent_id']) == 0) {
			
			$left			= $last_node['row_right']+1;
			$level			= 1;
			$parent			= array('category_id' => intval($category_id), 'forum_id' => intval($forum_id), 'group_id' => intval($group_id), 'user_id' => intval($user_id));
			
			$parent_id		= 0;
				
		/* If we are actually dealing with a parent node */
		} else if(intval($info['parent_id']) > 0) {
			
			/* Get the parent node */
			$parent			= $this->dba->GetRow("SELECT * FROM ". K4MAPS ." WHERE id = ". intval($info['parent_id']));
			
			/* Check if the parent node exists */
			if(!is_array($parent) || empty($parent)) {
				return 'L_INVALIDMAPID';
			}
			/* Find out how many nodes are on the current level */
			$num_on_level	= $this->getNumOnLevel($parent['row_left'], $parent['row_right'], $parent['row_level']+1);
			
			/* If there are more than 1 nodes on the current level */
			if($num_on_level > 0) {
				$left			= $parent['row_right'];
			} else {
				$left			= $parent['row_left'] + 1;
			}

			$parent_id			= $parent['id'];
			
			/* Should we need to reset some of the $parent values? */
			$parent['category_id']	= !$category_id ? $parent['category_id'] : intval($category_id);
			$parent['forum_id']		= !$forum_id ? $parent['forum_id'] : intval($forum_id);
			$parent['group_id']		= !$group_id ? $parent['group_id'] : intval($group_id);
			$parent['user_id']		= !$user_id ? $parent['user_id'] : intval($user_id);

			/* Set this nodes level */
			$level			= $parent['row_level']+1;
		} else {
			return 'L_INVALIDMAPID';
		}

		$right = $left+1;
		
		/**
		 * Build the queries
		 */

		/* Prepare the queries */
		$update_a			= &$this->dba->prepareStatement("UPDATE ". K4MAPS ." SET row_right = row_right+2 WHERE row_left < ? AND row_right >= ?");
		$update_b			= &$this->dba->prepareStatement("UPDATE ". K4MAPS ." SET row_left = row_left+2, row_right=row_right+2 WHERE row_left >= ?");
		$insert				= &$this->dba->prepareStatement("INSERT INTO ". K4MAPS ." (row_left,row_right,row_level,name,varname,category_id,forum_id,user_id,group_id,can_view,can_add,can_edit,can_del,inherit,value,parent_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
		
		/* Set the insert variables needed */
		$update_a->setInt(1, $left);
		$update_a->setInt(2, $left);
		$update_b->setInt(1, $left);

		/* Set the inserts for adding the actual node */
		$insert->setInt(1, $left);
		$insert->setInt(2, $right);
		$insert->setInt(3, $level);
		$insert->setString(4, $info['name']);
		$insert->setString(5, $info['varname']);
		$insert->setInt(6, $parent['category_id']);
		$insert->setInt(7, $parent['forum_id']);
		$insert->setInt(8, $parent['user_id']);
		$insert->setInt(9, $parent['group_id']);
		$insert->setInt(10, @$info['can_view']);
		$insert->setInt(11, @$info['can_add']);
		$insert->setInt(12, @$info['can_edit']);
		$insert->setInt(13, @$info['can_del']);
		$insert->setInt(14, @$info['inherit']);
		$insert->setString(15, @$info['value']);
		$insert->setInt(16, $parent_id);
		

		/**
		 * Execute the queries
		 */

		/* Execute the queries */
		$update_a->executeUpdate();
		$update_b->executeUpdate();
		$insert->executeUpdate();
		
		if(!@touch(CACHE_FILE, time()-86460)) {
			@unlink(CACHE_FILE);
		}

	}
}

class AdminMapsInsertNode extends FAAction {
	function getNumOnLevel(&$dba, $row_left, $row_right, $level) {
		return $dba->GetValue("SELECT COUNT(*) FROM ". K4MAPS ." WHERE row_left > $row_left AND row_right < $row_right AND row_level = $level");
	}
	function execute(&$request) {		

		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			$map			= &new AdminInsertMap($request['dba']);
			
			$result			= $map->insertNode($_REQUEST);

			if(is_string($result)) {
				$action = new K4InformationAction(new K4LanguageElement($result), 'content', FALSE);

				return $action->execute($request);
				return TRUE;
			}

			/* Redirect the user */
			$action = new K4InformationAction(new K4LanguageElement('L_ADDEDMAPSITEM'), 'content', FALSE, 'admin.php?act=permissions_gui', 3);

			return $action->execute($request);
			
			if(!@touch(CACHE_FILE, time()-86460)) {
				@unlink(CACHE_FILE);
			}

		} else {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

			return $action->execute($request);
		}

		return TRUE;
	}
}

class AdminMapsRemoveNode extends FAAction {
	function execute(&$request) {		

		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
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
			
			$heirarchy		= &new Heirarchy();
			$heirarchy->removeNode($map, K4MAPS);
			
			/* Redirect the user */
			$action = new K4InformationAction(new K4LanguageElement('L_REMOVEDMAPSITEM'), 'content', FALSE, 'admin.php?act=permissions_gui', 3);

			return $action->execute($request);
						
			if(!@touch(CACHE_FILE, time()-86460)) {
				@unlink(CACHE_FILE);
			}

		} else {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

			return $action->execute($request);
		}

		return TRUE;
	}
}

class MAPSIterator extends FAProxyIterator {
	var $dba;
	var $start_level;
	function MAPSIterator(&$dba, $data = NULL, $start_level = 1) {

		$this->dba			= &$dba;
		$this->start_level	= $start_level;
		
		parent::__construct($data);
	}

	function &current() {
		$temp			= parent::current();
		
		$num_children	= @(($temp['row_right'] - $temp['row_left'] - 1) / 2);
		$temp['level']	= str_repeat('&nbsp;&nbsp;&nbsp;', $temp['row_level']-$this->start_level);

		$temp['name']	= $temp['inherit'] == 1 ? '<span style="color: green;">'. $temp['name'] .'</span>' : '<span style="color: firebrick;">'. $temp['name'] .'</span>';
		
		//print_r($_COOKIE['mapsgui_menu']); exit;

		if(isset($_COOKIE['mapsgui_'. $temp['id']]) && $_COOKIE['mapsgui_'. $temp['id']] == 'yes' && $temp['row_level'] == 1) {
			$temp['expanded']		= 1;
			$temp['maps_children']	= &new MAPSIterator(&$this->dba, $this->dba->executeQuery("SELECT * FROM ". K4MAPS ." WHERE row_level > 1 AND row_left > ". $temp['row_left'] ." AND row_right < ". $temp['row_right'] ." ORDER BY row_left ASC"));				
		} else {
			$temp['expanded']		= 0;
		}
		return $temp;

		if(!$this->hasNext())
			$data->free();
	}
}

?>