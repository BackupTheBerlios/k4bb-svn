<?php
/**
* k4 Bulletin Board, categories.class.php
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
* @version $Id: categories.class.php 154 2005-07-15 02:56:28Z Peter Goodman $
* @package k42
*/



if(!defined('IN_K4')) {
	return;
}

class AdminCategories extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			global $_QUERYPARAMS;


			$categories			= $request['dba']->executeQuery("SELECT * FROM ". K4CATEGORIES ." ORDER BY row_order ASC");

			$request['template']->setList('categories', $categories);
			
			$request['template']->setFile('content', 'categories_manage.html');

			k4_bread_crumbs($request['template'], $request['dba'], 'L_CATEGORIES');
			$request['template']->setVar('forums_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/forums.html');
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminAddCategory extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			$request['template']->setFile('content', 'categories_add.html');

			k4_bread_crumbs($request['template'], $request['dba'], 'L_CATEGORIES');
			$request['template']->setVar('forums_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/forums.html');
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminInsertCategory extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			
			/* Error checking on the fields */
			if(!isset($_REQUEST['name']) || $_REQUEST['name'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTCATNAME'), 'content', TRUE);
				return $action->execute($request);
			}
						
//			if(!isset($_REQUEST['description']) || $_REQUEST['description'] == '') {
//				$action = new K4InformationAction(new K4LanguageElement('L_INSERTCATDESC'), 'content', TRUE);
//				return $action->execute($request);
//			}
			
			if(!isset($_REQUEST['row_order']) || $_REQUEST['row_order'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTCATORDER'), 'content', TRUE);
				return $action->execute($request);
			}

			if(!ctype_digit($_REQUEST['row_order'])) {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTCATORDERNUM'), 'content', TRUE);
				return $action->execute($request);
			}
			
			$request['dba']->beginTransaction();

			/* Build the queries */
			$insert_a			= $request['dba']->prepareStatement("INSERT INTO ". K4CATEGORIES ." (name,description,row_type,row_level,created,row_order) VALUES (?,?,?,?,?,?)");
			
			/* Build the query for the categories table */
			$insert_a->setString(1, $_REQUEST['name']);
			$insert_a->setString(2, @$_REQUEST['description']);
			$insert_a->setInt(3, CATEGORY);
			$insert_a->setInt(4, 1);
			$insert_a->setInt(5, time());
			$insert_a->setInt(6, $_REQUEST['row_order']);
			
			/* Insert the extra category info */
			$insert_a->executeUpdate();

			$category_id		= $request['dba']->getInsertId(K4CATEGORIES, 'category_id');
			
			$request['dba']->commitTransaction();

			reset_cache('all_forums');
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_CATEGORIES');
			$request['template']->setVar('forums_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/forums.html');

			$action = new K4InformationAction(new K4LanguageElement('L_ADDEDCATEGORY', $_REQUEST['name']), 'content', FALSE, 'admin.php?act=categories_insertmaps&id='. $category_id, 3);
			return $action->execute($request);
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminInsertCategoryMaps extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			
			global $_MAPITEMS, $_QUERYPARAMS;

			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDCATEGORY'), 'content', FALSE);
				return $action->execute($request);
			}

			$category					= $request['dba']->getRow("SELECT * FROM ". K4CATEGORIES ." WHERE category_id = ". intval($_REQUEST['id']));

			if(!is_array($category) || empty($category)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDCATEGORY'), 'content', FALSE);
				return $action->execute($request);
			}

			$parent					= $request['dba']->getRow("SELECT * FROM ". K4MAPS ." WHERE varname = 'categories'");
			
			// begin the sql transaction
			$request['dba']->beginTransaction();
						
			/* Set the default data for this category MAP element */
			$category_array				= array_merge(array('name' => $category['name'], 'varname' => 'category'. $category['category_id'], 'parent_id' => $parent['id'], 'category_id'=>$category['category_id']), $_MAPITEMS['category'][0]);
			
			/* Insert the main category MAP item */
			$maps							= &new K4Maps();
			$maps->add($request, $category_array, $parent, $parent['row_level'] + 1);
			
			// get the parent now
			$parent							= $request['dba']->getRow("SELECT * FROM ". K4MAPS ." WHERE varname = 'category". $category['category_id'] ."' LIMIT 1");

			/**
			 * Insert the secondary category MAP information
			 */
			for($i = 1; $i < count($_MAPITEMS['category'])-1; $i++) {
				if(isset($_MAPITEMS['category'][$i]) && is_array($_MAPITEMS['category'][$i])) {
					$category_array			= array_merge(array('parent_id' => $category_map_id, 'category_id'=>$category['category_id']), $_MAPITEMS['category'][$i]);
					$category_array['name']	= $request['template']->getVar('L_'. strtoupper($category_array['varname']));
					$maps->add($request, $category_array, $parent, $parent['row_level'] + 1);
				}
			}
			
			reset_cache('all_forums');
			
			$request['dba']->commitTransaction();
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_CATEGORIES');
			$request['template']->setVar('forums_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/forums.html');

			$action = new K4InformationAction(new K4LanguageElement('L_ADDEDCATEGORYPERMS', $category['name']), 'content', FALSE, 'admin.php?act=categories', 3);

			return $action->execute($request);

		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminSimpleCategoryUpdate extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			
			global $_MAPITEMS, $_QUERYPARAMS;

			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDCATEGORY'), 'content', FALSE);
				return $action->execute($request);
			}

			$category					= $request['dba']->getRow("SELECT * FROM ". K4CATEGORIES ." WHERE category_id = ". intval($_REQUEST['id']));

			if(!is_array($category) || empty($category)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDCATEGORY'), 'content', FALSE);
				return $action->execute($request);
			}

			if(!isset($_REQUEST['row_order']) || $_REQUEST['row_order'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTCATORDER'), 'content', TRUE);
				return $action->execute($request);
			}

			if(!ctype_digit($_REQUEST['row_order'])) {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTCATORDERNUM'), 'content', TRUE);
				return $action->execute($request);
			}

			$update		= $request['dba']->prepareStatement("UPDATE ". K4CATEGORIES ." SET row_order=? WHERE category_id=?");
			$update->setInt(1, $_REQUEST['row_order']);
			$update->setInt(2, $category['category_id']);

			$update->executeUpdate();
			
			reset_cache('all_forums');

			k4_bread_crumbs($request['template'], $request['dba'], 'L_CATEGORIES');
			$request['template']->setVar('forums_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/forums.html');

			$action = new K4InformationAction(new K4LanguageElement('L_UPDATEDCATEGORY', $category['name']), 'content', FALSE, 'admin.php?act=categories', 3);
			return $action->execute($request);

		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminEditCategory extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			
			global $_QUERYPARAMS;

			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDCATEGORY'), 'content', FALSE);
				return $action->execute($request);
			}

			$category					= $request['dba']->getRow("SELECT * FROM ". K4CATEGORIES ." WHERE category_id = ". intval($_REQUEST['id']));

			if(!is_array($category) || empty($category)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDCATEGORY'), 'content', FALSE);
				return $action->execute($request);
			}
			
			foreach($category as $key => $val)
				$request['template']->setVar('category_'. $key, $val);
			
			$request['template']->setFile('content', 'categories_edit.html');

			k4_bread_crumbs($request['template'], $request['dba'], 'L_CATEGORIES');
			$request['template']->setVar('forums_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/forums.html');
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminUpdateCategory extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			
			global $_QUERYPARAMS;

			/* Error checking on the fields */
			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDCATEGORY'), 'content', FALSE);
				return $action->execute($request);
			}

			$category					= $request['dba']->getRow("SELECT * FROM ". K4CATEGORIES ." WHERE category_id = ". intval($_REQUEST['id']));

			if(!is_array($category) || empty($category)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDCATEGORY'), 'content', FALSE);
				return $action->execute($request);
			}

			if(!isset($_REQUEST['name']) || $_REQUEST['name'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTCATNAME'), 'content', TRUE);
				return $action->execute($request);
			}
						
//			if(!isset($_REQUEST['description']) || $_REQUEST['description'] == '') {
//				$action = new K4InformationAction(new K4LanguageElement('L_INSERTCATDESC'), 'content', TRUE);
//				return $action->execute($request);
//			}
			
			if(!isset($_REQUEST['row_order']) || $_REQUEST['row_order'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTCATORDER'), 'content', TRUE);
				return $action->execute($request);
			}

			if(!ctype_digit($_REQUEST['row_order'])) {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTCATORDERNUM'), 'content', TRUE);
				return $action->execute($request);
			}
						
			/* Build the queries */
			$update_a			= $request['dba']->prepareStatement("UPDATE ". K4CATEGORIES ." SET name=?,description=?, row_order=? WHERE category_id=?");
			$update_b			= $request['dba']->prepareStatement("UPDATE ". K4MAPS ." SET name=? WHERE varname=?");
			
			/* Build the query for the categories table */
			$update_a->setString(1, $_REQUEST['name']);
			$update_a->setString(2, @$_REQUEST['description']);
			$update_a->setInt(3, $_REQUEST['row_order']);
			$update_a->setInt(4, $category['category_id']);
			
			/* Simple update on the maps table */
			$update_b->setString(1, $_REQUEST['name']);
			$update_b->setString(2, 'category'. $category['category_id']);

			/* Do all of the updates */
			$update_a->executeUpdate();
			$update_b->executeUpdate();
						
			reset_cache('all_forums');

			k4_bread_crumbs($request['template'], $request['dba'], 'L_CATEGORIES');
			$request['template']->setVar('forums_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/forums.html');

			$action = new K4InformationAction(new K4LanguageElement('L_UPDATEDCATEGORY', $category['name']), 'content', FALSE, 'admin.php?act=categories', 3);
			return $action->execute($request);

		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminRemoveCategory extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			
			global $_QUERYPARAMS;

			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDCATEGORY'), 'content', FALSE);
				return $action->execute($request);
			}

			$category					= $request['dba']->getRow("SELECT * FROM ". K4CATEGORIES ." WHERE category_id = ". intval($_REQUEST['id']));

			if(!is_array($category) || empty($category)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDCATEGORY'), 'content', FALSE);
				return $action->execute($request);
			}
			
			$request['dba']->beginTransaction();

			$category_maps	= $request['dba']->getRow("SELECT * FROM ". K4MAPS ." WHERE varname = 'category". $category['category_id'] ."'");
			
			$request['dba']->executeUpdate("DELETE FROM ". K4CATEGORIES ." WHERE category_id=". intval($category['category_id']));
			
			$heirarchy		= &new Heirarchy();
			
			$remover		= &new AdminRemoveForum();

			$remover->removeForums(array('forum_id' => $category['category_id']), $request['dba'], $heirarchy);
			
			$heirarchy->removeNode($category_maps, K4MAPS);
			
			$request['dba']->executeUpdate("DELETE FROM ". K4SUBSCRIPTIONS ." WHERE category_id=". intval($category['category_id']));
			
			/* Commit the current transaction */
			$request['dba']->commitTransaction();

			reset_cache('all_forums');
			reset_cache('email_queue');

			k4_bread_crumbs($request['template'], $request['dba'], 'L_CATEGORIES');
			$request['template']->setVar('forums_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/forums.html');

			$action = new K4InformationAction(new K4LanguageElement('L_REMOVEDCATEGORY', $category['name']), 'content', FALSE, 'admin.php?act=categories', 3);
			return $action->execute($request);

		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminCategoryPermissions extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			
			global $_QUERYPARAMS;

			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDCATEGORY'), 'content', FALSE);
				return $action->execute($request);
			}

			$category					= $request['dba']->getRow("SELECT * FROM ". K4CATEGORIES ." WHERE category_id = ". intval($_REQUEST['id']));

			if(!is_array($category) || empty($category)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDCATEGORY'), 'content', FALSE);
				return $action->execute($request);
			}
			
			foreach($category as $key => $val)
				$request['template']->setVar('category_'. $key, $val);
			
			/* Get the parent id's */
			$parents = array();
			foreach($_COOKIE as $key => $val) {
				if(strpos($key, 'mapsgui') !== FALSE) {
					$parents[] = intval($_COOKIE[$key]);
				}
			}
						
			$all_maps = array();
			$maps = $request['dba']->executeQuery("SELECT * FROM ". K4MAPS ." WHERE category_id = ". intval($category['category_id']) ." AND forum_id = 0");
			get_recursive_maps($request, $all_maps, $parents, $maps, 2);
			$all_maps = &new FAArrayIterator($all_maps);
			
			$request['template']->setList('category_maps', $all_maps);
			$request['template']->setFile('content', 'categories_permissions.html');
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_CATEGORIES');
			$request['template']->setVar('forums_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/forums.html');
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminUpdateCategoryPermissions extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			
			global $_QUERYPARAMS;

			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDCATEGORY'), 'content', FALSE);
				return $action->execute($request);
			}

			$category					= $request['dba']->getRow("SELECT * FROM ". K4CATEGORIES ." WHERE category_id = ". intval($_REQUEST['id']));

			if(!is_array($category) || empty($category)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDCATEGORY'), 'content', FALSE);
				return $action->execute($request);
			}
			
			foreach($category as $key => $val)
				$request['template']->setVar('category_'. $key, $val);
			
			$category_map				= $request['dba']->getRow("SELECT * FROM ". K4MAPS ." WHERE varname = 'category". $category['category_id'] ."' AND category_id = ". intval($category['category_id']));
			$category_maps				= $request['dba']->executeQuery("SELECT * FROM ". K4MAPS ." WHERE category_id = ". intval($category['category_id']) ." AND forum_id = 0");

			while($category_maps->next()) {
				$c						= $category_maps->current();

				if(isset($_REQUEST[$c['varname'] .'_can_view']) && isset($_REQUEST[$c['varname'] .'_can_add']) && isset($_REQUEST[$c['varname'] .'_can_edit']) && isset($_REQUEST[$c['varname'] .'_can_del'])) {
					
					if(($_REQUEST[$c['varname'] .'_can_view'] != $c['can_view']) || ($_REQUEST[$c['varname'] .'_can_add'] != $c['can_add']) || ($_REQUEST[$c['varname'] .'_can_edit'] != $c['can_edit']) || ($_REQUEST[$c['varname'] .'_can_del'] != $c['can_del'])) {

						$update				= $request['dba']->prepareStatement("UPDATE ". K4MAPS ." SET can_view=?,can_add=?,can_edit=?,can_del=? WHERE varname=? AND category_id=?");
						$update->setInt(1, $_REQUEST[$c['varname'] .'_can_view']);
						$update->setInt(2, $_REQUEST[$c['varname'] .'_can_add']);
						$update->setInt(3, $_REQUEST[$c['varname'] .'_can_edit']);
						$update->setInt(4, $_REQUEST[$c['varname'] .'_can_del']);
						$update->setString(5, $c['varname']);
						$update->setInt(6, $category['category_id']);

						$update->executeUpdate();

						unset($update);
					}
				}
			}
			
			reset_cache('all_forums');

			k4_bread_crumbs($request['template'], $request['dba'], 'L_CATEGORIES');
			$request['template']->setVar('forums_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/forums.html');

			$action = new K4InformationAction(new K4LanguageElement('L_UPDATEDCATEGORYPERMS', $category['name']), 'content', FALSE, 'admin.php?act=categories', 3);
			return $action->execute($request);
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminCategoriesIterator extends FAProxyIterator {
	var $dba;
	var $result;

	function AdminCategoriesIterator(&$dba, $query = NULL) {
		$query				= $query == NULL ? "SELECT * FROM ". K4CATEGORIES ." ORDER BY row_order ASC" : $query;
		
		$this->result		= $dba->executeQuery($query);
		$this->dba			= &$dba;

		parent::__construct($this->result);
	}

	function current() {
		$temp = parent::current();
		
		$forums = &new K4ForumsIterator($this->dba, "SELECT * FROM ". K4FORUMS ." WHERE category_id = ". $temp['category_id'] ." ORDER BY row_order ASC");
		if($forums->hasNext()) {
			$temp['forums'] = &$forums;
		} else {
			$forums->free();
			unset($forums);
		}

		/* Should we free the result? */
		if(!$this->hasNext())
			$this->result->free();

		return $temp;
	}
}

?>