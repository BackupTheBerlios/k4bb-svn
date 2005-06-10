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
* @version $Id: categories.class.php,v 1.11 2005/05/24 20:02:18 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	exit;
}

class AdminCategories extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			global $_QUERYPARAMS;


			$categories			= &$request['dba']->executeQuery("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['category'] ." FROM ". K4INFO ." i LEFT JOIN ". K4CATEGORIES ." c ON c.category_id = i.id WHERE i.row_type = ". CATEGORY ." ORDER BY i.row_order ASC");

			$request['template']->setList('categories', $categories);
			
			$request['template']->setFile('content', 'admin.html');
			$request['template']->setFile('admin_panel', 'categories_manage.html');
		} else {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

			return $action->execute($request);
		}

		return TRUE;
	}
}

class AdminAddCategory extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
						
			$request['template']->setFile('content', 'admin.html');
			$request['template']->setFile('admin_panel', 'categories_add.html');
		} else {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

			return $action->execute($request);
		}

		return TRUE;
	}
}

class AdminInsertCategory extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			/* Error checking on the fields */
			if(!isset($_REQUEST['name']) || $_REQUEST['name'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTCATNAME'), 'content', TRUE);

				return $action->execute($request);
				return TRUE;
			}
						
			if(!isset($_REQUEST['description']) || $_REQUEST['description'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTCATDESC'), 'content', TRUE);

				return $action->execute($request);
				return TRUE;
			}
			
			if(!isset($_REQUEST['row_order']) || $_REQUEST['row_order'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTCATORDER'), 'content', TRUE);

				return $action->execute($request);
				return TRUE;
			}

			if(!ctype_digit($_REQUEST['row_order'])) {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTCATORDERNUM'), 'content', TRUE);

				return $action->execute($request);
				return TRUE;
			}
			
			$abs_right			= $request['dba']->getValue("SELECT row_right FROM ". K4INFO ." WHERE row_type = ". CATEGORY ." ORDER BY row_right DESC LIMIT 1");
			
			$left				= $abs_right && $abs_right !== false && $abs_right != 0 ? $abs_right+1 : 1;
			$right				= $left + 1;
			
			$request['dba']->beginTransaction();

			/* Build the queries */
			$insert_a			= &$request['dba']->prepareStatement("INSERT INTO ". K4INFO ." (name,row_left,row_right,row_type,row_level,created,row_order) VALUES (?,?,?,?,?,?,?)");
			$insert_b			= &$request['dba']->prepareStatement("INSERT INTO ". K4CATEGORIES ." (category_id,description) VALUES (?,?)");
			
			/* Set the query values */
			$insert_a->setString(1, $_REQUEST['name']);
			$insert_a->setInt(2, $left);
			$insert_a->setInt(3, $right);
			$insert_a->setInt(4, CATEGORY);
			$insert_a->setInt(5, 1);
			$insert_a->setInt(6, time());
			$insert_a->setInt(7, $_REQUEST['row_order']);
			
			/* Add the category to the info table */
			$insert_a->executeUpdate();
			
			$category_id		= $request['dba']->getInsertId();
			
			/* Build the query for the categories table */
			$insert_b->setInt(1, $category_id);
			$insert_b->setString(2, $_REQUEST['description']);
			
			/* Insert the extra category info */
			$insert_b->executeUpdate();
			
			$request['dba']->commitTransaction();

			if(!@touch(CACHE_FILE, time()-86460)) {
				@unlink(CACHE_FILE);
			}
			
			$action = new K4InformationAction(new K4LanguageElement('L_ADDEDCATEGORY', $_REQUEST['name']), 'content', FALSE, 'admin.php?act=categories_insertmaps&id='. $category_id, 3);
			return $action->execute($request);
		} else {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

			return $action->execute($request);
		}

		return TRUE;
	}
}

class AdminInsertCategoryMaps extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			global $_MAPITEMS, $_QUERYPARAMS;

			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDCATEGORY'), 'content', FALSE);

				return $action->execute($request);
				return TRUE;
			}

			$category					= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['category'] ." FROM ". K4CATEGORIES ." c LEFT JOIN ". K4INFO ." i ON c.category_id = i.id WHERE i.id = ". intval($_REQUEST['id']));

			if(!is_array($category) || empty($category)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDCATEGORY'), 'content', FALSE);

				return $action->execute($request);
				return TRUE;
			}

			$parent_id					= $request['dba']->getValue("SELECT id FROM ". K4MAPS ." WHERE varname = 'categories'");
			
			$request['dba']->beginTransaction();

			/* Insert the main category MAP item */
			$map						= &new AdminInsertMap($request['dba']);
			
			/* Set the default data for this category MAP element */
			$category_array				= array_merge(array('name' => $category['name'], 'varname' => 'category'. $category['id'], 'parent_id' => $parent_id), $_MAPITEMS['category'][0]);
			
			/**
			 * Insert the main category MAP information
			 */

			$result			= $map->insertNode($category_array, $category['id']);

			if(is_string($result)) {
				$action = new K4InformationAction(new K4LanguageElement($error->message), 'content', FALSE);

				return $action->execute($request);
				return TRUE;
			}
			
			$category_map_id			= $request['dba']->getInsertId();

			/**
			 * Insert the secondary category MAP information
			 */
			for($i = 1; $i < count($_MAPITEMS['category'])-1; $i++) {
				
				if(isset($_MAPITEMS['category'][$i]) && is_array($_MAPITEMS['category'][$i])) {

					$category_array			= array_merge(array('parent_id' => $category_map_id), $_MAPITEMS['category'][$i]);
					
					$category_array['name']	= $request['template']->getVar('L_'. strtoupper($category_array['varname']));

					$result					= $map->insertNode($category_array, $category['id']);

					if(is_string($result)) {
						$action = new K4InformationAction(new K4LanguageElement($result), 'content', FALSE);

						return $action->execute($request);
						return TRUE;
					}
				}
			}
			
			if(!@touch(CACHE_FILE, time()-86460)) {
				@unlink(CACHE_FILE);
			}
			
			$request['dba']->commitTransaction();
			
			/**
			 * If we've gotten to this point.. redirect
			 */
			$action = new K4InformationAction(new K4LanguageElement('L_ADDEDCATEGORYPERMS', $category['name']), 'content', FALSE, 'admin.php?act=categories', 3);

			return $action->execute($request);

		} else {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

			return $action->execute($request);
		}

		return TRUE;
	}
}

class AdminSimpleCategoryUpdate extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			global $_MAPITEMS, $_QUERYPARAMS;

			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDCATEGORY'), 'content', FALSE);

				return $action->execute($request);
				return TRUE;
			}

			$category					= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['category'] ." FROM ". K4CATEGORIES ." c LEFT JOIN ". K4INFO ." i ON c.category_id = i.id WHERE i.id = ". intval($_REQUEST['id']));

			if(!is_array($category) || empty($category)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDCATEGORY'), 'content', FALSE);

				return $action->execute($request);
				return TRUE;
			}

			if(!isset($_REQUEST['row_order']) || $_REQUEST['row_order'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTCATORDER'), 'content', TRUE);

				return $action->execute($request);
				return TRUE;
			}

			if(!ctype_digit($_REQUEST['row_order']))
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTCATORDERNUM'), 'content', TRUE);

				return $action->execute($request);

			$update		= &$request['dba']->prepareStatement("UPDATE ". K4INFO ." SET row_order=? WHERE id=?");
			$update->setInt(1, $_REQUEST['row_order']);
			$update->setInt(2, $category['id']);

			$update->executeUpdate();
			
			if(!@touch(CACHE_FILE, time()-86460)) {
				@unlink(CACHE_FILE);
			}

			$action = new K4InformationAction(new K4LanguageElement('L_UPDATEDCATEGORY', $category['name']), 'content', FALSE, 'admin.php?act=categories', 3);


			return $action->execute($request);

		} else {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

			return $action->execute($request);
		}

		return TRUE;
	}
}

class AdminEditCategory extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			global $_QUERYPARAMS;

			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDCATEGORY'), 'content', FALSE);

				return $action->execute($request);
				return TRUE;
			}

			$category					= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['category'] ." FROM ". K4CATEGORIES ." c LEFT JOIN ". K4INFO ." i ON c.category_id = i.id WHERE i.id = ". intval($_REQUEST['id']));

			if(!is_array($category) || empty($category)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDCATEGORY'), 'content', FALSE);

				return $action->execute($request);
				return TRUE;
			}
			
			foreach($category as $key => $val)
				$request['template']->setVar('category_'. $key, $val);
			
			$request['template']->setFile('content', 'admin.html');
			$request['template']->setFile('admin_panel', 'categories_edit.html');
		} else {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

			return $action->execute($request);
		}

		return TRUE;
	}
}

class AdminUpdateCategory extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			global $_QUERYPARAMS;

			/* Error checking on the fields */
			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDCATEGORY'), 'content', FALSE);

				return $action->execute($request);
				return TRUE;
			}

			$category					= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['category'] ." FROM ". K4CATEGORIES ." c LEFT JOIN ". K4INFO ." i ON c.category_id = i.id WHERE i.id = ". intval($_REQUEST['id']));

			if(!is_array($category) || empty($category)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDCATEGORY'), 'content', FALSE);

				return $action->execute($request);
				return TRUE;
			}

			if(!isset($_REQUEST['name']) || $_REQUEST['name'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTCATNAME'), 'content', TRUE);

				return $action->execute($request);
				return TRUE;
			}
						
			if(!isset($_REQUEST['description']) || $_REQUEST['description'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTCATDESC'), 'content', TRUE);

				return $action->execute($request);
				return TRUE;
			}
			
			if(!isset($_REQUEST['row_order']) || $_REQUEST['row_order'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTCATORDER'), 'content', TRUE);

				return $action->execute($request);
				return TRUE;
			}

			if(!ctype_digit($_REQUEST['row_order'])) {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTCATORDERNUM'), 'content', TRUE);

				return $action->execute($request);
				return TRUE;
			}
						
			/* Build the queries */
			$update_a			= &$request['dba']->prepareStatement("UPDATE ". K4INFO ." SET name=?,row_order=? WHERE id=?");
			$update_b			= &$request['dba']->prepareStatement("UPDATE ". K4CATEGORIES ." SET description=? WHERE category_id=?");
			$update_c			= &$request['dba']->prepareStatement("UPDATE ". K4MAPS ." SET name=? WHERE varname=?");

			/* Set the query values */
			$update_a->setString(1, $_REQUEST['name']);
			$update_a->setInt(2, $_REQUEST['row_order']);
			$update_a->setInt(3, $category['id']);
			
			/* Build the query for the categories table */
			$update_b->setString(1, $_REQUEST['description']);
			$update_b->setInt(2, $category['id']);
			
			/* Simple update on the maps table */
			$update_c->setString(1, $_REQUEST['name']);
			$update_c->setString(2, 'category'. $category['id']);

			/* Do all of the updates */
			$update_a->executeUpdate();
			$update_b->executeUpdate();
			$update_c->executeUpdate();
			
			if(!@touch(CACHE_FILE, time()-86460)) {
				@unlink(CACHE_FILE);
			}

			$action = new K4InformationAction(new K4LanguageElement('L_UPDATEDCATEGORY', $category['name']), 'content', FALSE, 'admin.php?act=categories', 3);


			return $action->execute($request);

		} else {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

			return $action->execute($request);
		}

		return TRUE;
	}
}

class AdminRemoveCategory extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			global $_QUERYPARAMS;

			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDCATEGORY'), 'content', FALSE);

				return $action->execute($request);
				return TRUE;
			}

			$category					= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['category'] ." FROM ". K4CATEGORIES ." c LEFT JOIN ". K4INFO ." i ON c.category_id = i.id WHERE i.id = ". intval($_REQUEST['id']));

			if(!is_array($category) || empty($category)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDCATEGORY'), 'content', FALSE);

				return $action->execute($request);
				return TRUE;
			}
			
			$request['dba']->beginTransaction();

			$category_maps	= $request['dba']->getRow("SELECT * FROM ". K4MAPS ." WHERE varname = 'category". $category['id'] ."'");
			
			$request['dba']->executeUpdate("DELETE FROM ". K4CATEGORIES ." WHERE category_id=". intval($category['id']));
			
			/* Get this category's forums */
			$forums			= &$request['dba']->executeQuery("SELECT * FROM ". K4INFO ." WHERE row_left > ". intval($category['row_left']) ." AND row_right < ". intval($category['row_right']) ." AND row_type = ". FORUM);
			
			$heirarchy		= &new Heirarchy();
			
			/* Deal with this forum and any sub-forums */
			while($forums->next()) {
				$f				= $forums->current();
				$forum_maps		= $request['dba']->getRow("SELECT * FROM ". K4MAPS ." WHERE varname = 'forum". $f['id'] ."'");
				$heirarchy->removeNode($forum_maps, K4MAPS);
				//$heirarchy->removeItem($forum_maps, K4MAPS);

				$request['dba']->executeUpdate("DELETE FROM ". K4FORUMS ." WHERE forum_id=". intval($f['id']));
				$request['dba']->executeUpdate("DELETE FROM ". K4TOPICS ." WHERE forum_id=". intval($f['id']));
				$request['dba']->executeUpdate("DELETE FROM ". K4REPLIES ." WHERE forum_id=". intval($f['id']));
			}

			/* This will take care of everything in the K4INFO table */
			$heirarchy->removeNode($category, K4INFO);
			$heirarchy->removeItem($category, K4INFO, 'category_id');

			$heirarchy->removeNode($category_maps, K4MAPS);
			
			$request['dba']->executeUpdate("DELETE FROM ". K4SUBSCRIPTIONS ." WHERE category_id=". intval($category['id']));
			
			/* Commit the current transaction */
			$request['dba']->commitTransaction();

			if(!@touch(CACHE_FILE, time()-86460)) {
				@unlink(CACHE_FILE);
			}
			if(!@touch(CACHE_EMAIL_FILE, time()-86460)) {
				@unlink(CACHE_EMAIL_FILE);
			}

			$action = new K4InformationAction(new K4LanguageElement('L_REMOVEDCATEGORY', $category['name']), 'content', FALSE, 'admin.php?act=categories', 3);
			return $action->execute($request);

		} else {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

			return $action->execute($request);
		}

		return TRUE;
	}
}

class AdminCategoryPermissions extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			global $_QUERYPARAMS;

			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDCATEGORY'), 'content', FALSE);

				return $action->execute($request);
				return TRUE;
			}

			$category					= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['category'] ." FROM ". K4CATEGORIES ." c LEFT JOIN ". K4INFO ." i ON c.category_id = i.id WHERE i.id = ". intval($_REQUEST['id']));

			if(!is_array($category) || empty($category)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDCATEGORY'), 'content', FALSE);

				return $action->execute($request);
				return TRUE;
			}
			
			foreach($category as $key => $val)
				$request['template']->setVar('category_'. $key, $val);
			
			$category_maps				= &new MAPSIterator(&$request['dba'], $request['dba']->executeQuery("SELECT * FROM ". K4MAPS ." WHERE category_id = ". intval($category['id']) ." AND forum_id = 0 ORDER BY row_left ASC"), 2);
			
			$request['template']->setList('category_maps', $category_maps);

			$request['template']->setFile('content', 'admin.html');
			$request['template']->setFile('admin_panel', 'categories_permissions.html');
		} else {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

			return $action->execute($request);
		}

		return TRUE;
	}
}

class AdminUpdateCategoryPermissions extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			global $_QUERYPARAMS;

			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDCATEGORY'), 'content', FALSE);

				return $action->execute($request);
				return TRUE;
			}

			$category					= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['category'] ." FROM ". K4CATEGORIES ." c LEFT JOIN ". K4INFO ." i ON c.category_id = i.id WHERE i.id = ". intval($_REQUEST['id']));

			if(!is_array($category) || empty($category)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDCATEGORY'), 'content', FALSE);

				return $action->execute($request);
				return TRUE;
			}
			
			foreach($category as $key => $val)
				$request['template']->setVar('category_'. $key, $val);
			
			$category_map				= $request['dba']->getRow("SELECT * FROM ". K4MAPS ." WHERE varname = 'category". $category['id'] ."' AND category_id = ". intval($category['id']));
			$category_maps				= $request['dba']->executeQuery("SELECT * FROM ". K4MAPS ." WHERE category_id = ". intval($category['id']) ." AND row_left >= ". intval($category_map['row_left']) ." AND row_right <= ". intval($category_map['row_right']) ." ORDER BY row_left ASC");

			while($category_maps->next()) {
				$c						= $category_maps->current();

				if(isset($_REQUEST[$c['varname'] .'_can_view']) && isset($_REQUEST[$c['varname'] .'_can_add']) && isset($_REQUEST[$c['varname'] .'_can_edit']) && isset($_REQUEST[$c['varname'] .'_can_del'])) {
					
					if(($_REQUEST[$c['varname'] .'_can_view'] != $c['can_view']) || ($_REQUEST[$c['varname'] .'_can_add'] != $c['can_add']) || ($_REQUEST[$c['varname'] .'_can_edit'] != $c['can_edit']) || ($_REQUEST[$c['varname'] .'_can_del'] != $c['can_del'])) {

						$update				= &$request['dba']->prepareStatement("UPDATE ". K4MAPS ." SET can_view=?,can_add=?,can_edit=?,can_del=? WHERE varname=? AND category_id=?");
						$update->setInt(1, $_REQUEST[$c['varname'] .'_can_view']);
						$update->setInt(2, $_REQUEST[$c['varname'] .'_can_add']);
						$update->setInt(3, $_REQUEST[$c['varname'] .'_can_edit']);
						$update->setInt(4, $_REQUEST[$c['varname'] .'_can_del']);
						$update->setString(5, $c['varname']);
						$update->setInt(6, $category['id']);

						$update->executeUpdate();

						unset($update);
					}
				}
			}
			
			if(!@touch(CACHE_FILE, time()-86460)) {
				@unlink(CACHE_FILE);
			}

			$action = new K4InformationAction(new K4LanguageElement('L_UPDATEDCATEGORYPERMS', $category['name']), 'content', FALSE, 'admin.php?act=categories', 3);


			return $action->execute($request);
		} else {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

			return $action->execute($request);
		}

		return TRUE;
	}
}

class AdminCategoriesIterator extends FAProxyIterator {
	var $dba;
	var $result;

	function AdminCategoriesIterator(&$dba, $query = NULL) {
		global $_CONFIG, $_QUERYPARAMS;
		
		$this->query_params	= $_QUERYPARAMS;
		
		$query_params		= $this->query_params['info'] . $this->query_params['category'];

		$query				= $query == NULL ? "SELECT $query_params FROM ". K4INFO ." i LEFT JOIN ". K4CATEGORIES ." c ON c.category_id = i.id AND i.row_type = ". CATEGORY ." ORDER BY i.row_order ASC" : $query;
		
		$this->result		= &$dba->executeQuery($query);
		$this->dba			= &$dba;

		parent::__construct($this->result);
	}

	function &current() {
		$temp = parent::current();
		
		if(($temp['row_right'] - $temp['row_left'] - 1) > 0) {
			
			$query_params	= $this->query_params['info'] . $this->query_params['forum'];

			$temp['forums'] = &new K4ForumsIterator(&$this->dba, "SELECT $query_params FROM ". K4INFO ." i LEFT JOIN ". K4FORUMS ." f ON f.forum_id = i.id WHERE i.row_left > ". $temp['row_left'] ." AND i.row_right < ". $temp['row_right'] ." AND i.row_type = ". FORUM ." ORDER BY i.row_left, i.row_order ASC");
		}

		/* Should we free the result? */
		if(!$this->hasNext())
			$this->result->free();

		return $temp;
	}
}

?>