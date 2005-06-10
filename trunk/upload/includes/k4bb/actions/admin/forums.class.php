<?php
/**
* k4 Bulletin Board, forums.class.php
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
* @version $Id: forums.class.php,v 1.12 2005/05/24 20:02:18 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	exit;
}

class AdminForums extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			global $_QUERYPARAMS;


			$categories = &new AdminCategoriesIterator($request['dba'], "SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['category'] ." FROM ". K4INFO ." i LEFT JOIN ". K4CATEGORIES ." c ON c.category_id = i.id WHERE i.row_type = ". CATEGORY ." ORDER BY i.row_order ASC");
			$request['template']->setList('categories', $categories);
			
			$request['template']->setFile('content', 'admin.html');
			$request['template']->setFile('admin_panel', 'forums_manage.html');
		} else {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

			return $action->execute($request);
		}

		return TRUE;
	}
}

class AdminAddForum extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			global $_QUERYPARAMS;

			/* Error checking */
			if(!isset($_REQUEST['category_id']) || intval($_REQUEST['category_id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDCATEGORY'), 'content', FALSE);

				return $action->execute($request);
				return TRUE;
			}

			$category					= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['category'] ." FROM ". K4CATEGORIES ." c LEFT JOIN ". K4INFO ." i ON c.category_id = i.id WHERE i.id = ". intval(@$_REQUEST['category_id']));			

			if(!is_array($category) || empty($category)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDCATEGORY'), 'content', FALSE);

				return $action->execute($request);
				return TRUE;
			}
			
			$request['template']->setVar('category_id', $category['id']);
			
			/* Do we have a parent forum Id? */
			if(isset($_REQUEST['forum_id']) && intval($_REQUEST['forum_id']) != 0) {
				$forum					= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['forum'] ." FROM ". K4FORUMS ." f LEFT JOIN ". K4INFO ." i ON f.forum_id = i.id WHERE i.id = ". intval(@$_REQUEST['forum_id']));			

				if(!is_array($forum) || empty($forum)) {
					$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFORUM'), 'content', FALSE);

					return $action->execute($request);
					return TRUE;
				}
				
				$request['template']->setVar('forum_id', $forum['id']);
			}

			$languages					= array();
			
			$dir						= dir(K4_BASE_DIR .'/lang');
			
			while(false !== ($file = $dir->read())) {

				if($file != '.' && $file != '..' && $file != 'CVS'  && $file != '.svn' && is_dir(K4_BASE_DIR .'/lang/'. $file) && is_readable(K4_BASE_DIR .'/lang/'. $file)) {
					$languages[]		= array('lang' => $file, 'name' => ucfirst($file));
				}
			}
			
			$languages					= &new FAArrayIterator($languages);

			$request['template']->setList('languages', $languages);

			$request['template']->setFile('content', 'admin.html');
			$request['template']->setFile('admin_panel', 'forums_add.html');
		} else {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

			return $action->execute($request);
		}

		return TRUE;
	}
}

class AdminInsertForum extends FAAction {

	var $dba;

	function getNumOnLevel($row_left, $row_right, $level) {
		return $this->dba->GetValue("SELECT COUNT(*) FROM ". K4INFO ." WHERE row_left > $row_left AND row_right < $row_right AND row_level = $level");
	}
	function execute(&$request) {		
		
		$this->dba			= &$request['dba'];

		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			global $_QUERYPARAMS;
			
			/* Error checking */
			if(!isset($_REQUEST['category_id']) || intval($_REQUEST['category_id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDCATEGORY'), 'content', FALSE);

				return $action->execute($request);
			}
			
			/* Attempt to get this category */
			$category					= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['category'] ." FROM ". K4CATEGORIES ." c LEFT JOIN ". K4INFO ." i ON c.category_id = i.id WHERE i.id = ". intval(@$_REQUEST['category_id']));			

			if(!is_array($category) || empty($category)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDCATEGORY'), 'content', FALSE);

				return $action->execute($request);
				return TRUE;
			}
			
			$forum						= $category;

			/* Do we have a parent forum Id? */
			if(isset($_REQUEST['forum_id']) && intval($_REQUEST['forum_id']) != 0) {
				
				/* Attempt to get this forum */
				$forum					= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['forum'] ." FROM ". K4FORUMS ." f LEFT JOIN ". K4INFO ." i ON f.forum_id = i.id WHERE i.id = ". intval(@$_REQUEST['forum_id']));			

				if(!is_array($forum) || empty($forum)) {
					$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFORUM'), 'content', FALSE);

					return $action->execute($request);
					return TRUE;
				}
			}

			/* Set the parent id */
			$parent_id					= $forum['id'];

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
			
			$abs_right			= $request['dba']->getValue("SELECT row_right FROM ". K4INFO ." WHERE parent_id = ". intval($forum['id']) ." ORDER BY row_right DESC LIMIT 1");

			/* Find out how many nodes are on the current level */
			$num_on_level		= $this->getNumOnLevel($forum['row_left'], $forum['row_right'], $forum['row_level']+1);
			
			/* If there are more than 1 nodes on the current level */
			if($num_on_level > 0) {
				$left			= $forum['row_right'];
			} else {
				$left			= $forum['row_left'] + 1;
			}

			$right				= $left + 1;
			
			$request['dba']->beginTransaction();

			/* Build the queries */
			$update_a			= &$this->dba->prepareStatement("UPDATE ". K4INFO ." SET row_right = row_right+2 WHERE row_left < ? AND row_right >= ?");
			$update_b			= &$this->dba->prepareStatement("UPDATE ". K4INFO ." SET row_left = row_left+2, row_right=row_right+2 WHERE row_left >= ?");
			
			/* Set the update values */
			$update_a->setInt(1, $left);
			$update_a->setInt(2, $left);
			$update_b->setInt(1, $left);
			
			/* Update the information table */
			$update_a->executeUpdate();
			$update_b->executeUpdate();
			
			$insert_a			= &$request['dba']->prepareStatement("INSERT INTO ". K4INFO ." (name,row_left,row_right,row_type,row_level,created,row_order,parent_id) VALUES (?,?,?,?,?,?,?,?)");
			$insert_b			= &$request['dba']->prepareStatement("INSERT INTO ". K4FORUMS ." (category_id,forum_id,description,pass,is_forum,is_link,link_href,link_show_redirects,forum_rules,topicsperpage,postsperpage,maxpolloptions,defaultlang,moderating_groups,prune_auto,prune_frequency,prune_post_age,prune_post_viewed_age,prune_old_polls,prune_announcements,prune_stickies) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
						
			/* Set the query values */
			$insert_a->setString(1, $_REQUEST['name']);
			$insert_a->setInt(2, $left);
			$insert_a->setInt(3, $right);
			$insert_a->setInt(4, FORUM);
			$insert_a->setInt(5, $forum['row_level']+1);
			$insert_a->setInt(6, time());
			$insert_a->setInt(7, $_REQUEST['row_order']);
			$insert_a->setInt(8, $parent_id);
			
			/* Add the forum to the info table */
			$insert_a->executeUpdate();
			
			/* Get this forum id */
			$forum_id			= $request['dba']->getInsertId();
			
			$forum_rules		= &new BBCodex(&$request['dba'], $request['user']->getInfoArray(), $_REQUEST['forum_rules'], FALSE, TRUE, TRUE, TRUE, TRUE);

			/* Build the query for the forums table */
			$insert_b->setInt(1, $category['id']);
			$insert_b->setInt(2, $forum_id);
			$insert_b->setString(3, $_REQUEST['description']);
			$insert_b->setString(4, $_REQUEST['pass']);
			$insert_b->setInt(5, iif(intval($_REQUEST['is_link']) == 1, 0, 1));
			$insert_b->setInt(6, $_REQUEST['is_link']); 
			$insert_b->setString(7, $_REQUEST['link_href']);
			$insert_b->setInt(8, $_REQUEST['link_show_redirects']);
			$insert_b->setString(9, $forum_rules->parse());
			$insert_b->setInt(10, $_REQUEST['topicsperpage']);
			$insert_b->setInt(11, $_REQUEST['postsperpage']);
			$insert_b->setInt(12, $_REQUEST['maxpolloptions']);
			$insert_b->setString(13, $_REQUEST['defaultlang']);
			$insert_b->setString(14, iif((isset($_REQUEST['moderators']) && is_array($_REQUEST['moderators']) && !empty($_REQUEST['moderators'])), serialize(@$_REQUEST['moderators']), ''));
			$insert_b->setInt(15, $_REQUEST['prune_auto']);
			$insert_b->setInt(16, $_REQUEST['prune_frequency']);
			$insert_b->setInt(17, $_REQUEST['prune_post_age']);
			$insert_b->setInt(18, $_REQUEST['prune_post_viewed_age']);
			$insert_b->setInt(19, $_REQUEST['prune_old_polls']);
			$insert_b->setInt(20, $_REQUEST['prune_announcements']);
			$insert_b->setInt(21, $_REQUEST['prune_stickies']);

			/* Insert the extra forum info */
			$insert_b->executeUpdate();
			
			if(!($forum['row_type'] & CATEGORY)) {
				$request['dba']->executeUpdate("UPDATE ". K4FORUMS ." SET subforums = 1 WHERE forum_id = ". $forum['id']);
			}
			
			$request['dba']->commitTransaction();

			if(!@touch(CACHE_FILE, time()-86460)) {
				@unlink(CACHE_FILE);
			}
			
			$action = new K4InformationAction(new K4LanguageElement('L_ADDEDFORUM', $_REQUEST['name']), 'content', FALSE, 'admin.php?act=forums_insertmaps&id='. $forum_id, 3);
			return $action->execute($request);

		} else {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

			return $action->execute($request);
		}

		return TRUE;
	}
}

class AdminInsertForumMaps extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			global $_MAPITEMS, $_QUERYPARAMS;
			
			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFORUM'), 'content', FALSE);

				return $action->execute($request);
				return TRUE;
			}

			$forum							= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['forum'] ." FROM ". K4FORUMS ." f LEFT JOIN ". K4INFO ." i ON f.forum_id = i.id WHERE i.id = ". intval($_REQUEST['id']));
			
			if(!is_array($forum) || empty($forum)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFORUM'), 'content', FALSE);
				return $action->execute($request);
			}

			$parent_id						= $request['dba']->getValue("SELECT id FROM ". K4MAPS ." WHERE varname = 'forums'");
			
			$request['dba']->beginTransaction();

			/* Insert the main forum MAP item */
			$map							= &new AdminInsertMap($request['dba']);
			
			/* Set the default data for this forum MAP element */
			$forum_array					= array_merge(array('name' => $forum['name'], 'varname' => 'forum'. $forum['id'], 'parent_id' => $parent_id), $_MAPITEMS['forum'][0]);
			
			/**
			 * Insert the main forum MAP information
			 */
			
			$result							= $map->insertNode($forum_array, $forum['category_id'], $forum['id']);

			if(is_string($result)) {
				$action = new K4InformationAction(new K4LanguageElement($result), 'content', FALSE);

				return $action->execute($request);
				return TRUE;
			}
			
			$forum_map_id					= $request['dba']->getInsertId();

			/**
			 * Insert the secondary forum MAP information
			 */
			if($forum['is_forum'] == 1) {
				for($i = 1; $i < count($_MAPITEMS['forum']); $i++) {
					
					if(isset($_MAPITEMS['forum'][$i]) && is_array($_MAPITEMS['forum'][$i])) {
						
						$forum_array			= array_merge(array('parent_id' => $forum_map_id), $_MAPITEMS['forum'][$i]);
						
						$forum_array['name']	= $request['template']->getVar('L_'. strtoupper($forum_array['varname']));
						
						$result					= $map->insertNode($forum_array, $forum['category_id'], $forum['id']);

						if(is_string($result)) {
							$action = new K4InformationAction(new K4LanguageElement($result), 'content', FALSE);

							return $action->execute($request);
							return TRUE;
						}
					}
				}
			}

			$request['dba']->commitTransaction();

			if(!@touch(CACHE_FILE, time()-86460)) {
				@unlink(CACHE_FILE);
			}

			/**
			 * If we've gotten to this point.. redirect
			 */
			$action = new K4InformationAction(new K4LanguageElement('L_ADDEDFORUMPERMS', $forum['name']), 'content', FALSE, 'admin.php?act=forums', 3);
			return $action->execute($request);

		} else {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

			return $action->execute($request);
		}

		return TRUE;
	}
}

class AdminSimpleForumUpdate extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			global $_MAPITEMS, $_QUERYPARAMS;

			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFORUM'), 'content', FALSE);

				return $action->execute($request);
				return TRUE;
			}

			$forum					= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['forum'] ." FROM ". K4FORUMS ." f LEFT JOIN ". K4INFO ." i ON f.forum_id = i.id WHERE i.id = ". intval($_REQUEST['id']));

			if(!is_array($forum) || empty($forum)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFORUM'), 'content', FALSE);

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

			$update		= &$request['dba']->prepareStatement("UPDATE ". K4INFO ." SET row_order=? WHERE id=?");
			$update->setInt(1, $_REQUEST['row_order']);
			$update->setInt(2, $forum['id']);

			$update->executeUpdate();
			
			if(!@touch(CACHE_FILE, time()-86460)) {
				@unlink(CACHE_FILE);
			}

			$action = new K4InformationAction(new K4LanguageElement('L_UPDATEDFORUM', $forum['name']), 'content', FALSE, 'admin.php?act=forums', 3);


			return $action->execute($request);

		} else {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

			return $action->execute($request);
		}

		return TRUE;
	}
}

class AdminEditForum extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			global $_QUERYPARAMS, $_USERGROUPS;

			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFORUM'), 'content', FALSE);

				return $action->execute($request);
				return TRUE;
			}

			$forum					= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['forum'] ." FROM ". K4FORUMS ." f LEFT JOIN ". K4INFO ." i ON f.forum_id = i.id WHERE i.id = ". intval($_REQUEST['id']));

			if(!is_array($forum) || empty($forum)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFORUM'), 'content', FALSE);

				return $action->execute($request);
				return TRUE;
			}
			
			$forum_rules				= &new BBCodex(&$request['dba'], $request['user']->getInfoArray(), $forum['forum_rules'], $forum['id'], TRUE, TRUE, TRUE, TRUE);
			$forum['forum_rules']		= $forum_rules->revert();

			foreach($forum as $key => $val)
				$request['template']->setVar('forum_'. $key, $val);
			
			$languages					= array();
			
			$dir						= dir(K4_BASE_DIR .'/lang');
			
			while(false !== ($file = $dir->read())) {

				if($file != '.' && $file != '..' && $file != 'CVS'  && $file != '.svn' && is_dir(K4_BASE_DIR .'/lang/'. $file) && is_readable(K4_BASE_DIR .'/lang/'. $file)) {
					$languages[]		= array('lang' => $file, 'name' => ucfirst($file));
				}
			}

			$groups		= $forum['moderating_groups'] != '' ? iif(!unserialize($forum['moderating_groups']), array(), unserialize($forum['moderating_groups'])) : array();
			$groups_str	= '';

			if(is_array($groups)) {
				foreach($groups as $g) {
					if(isset($_USERGROUPS[$g])) {
						$groups_str	.= $g .' ';
					}
				}

				$request['template']->setVar('forum_moderating_groups', iif(strlen($groups_str) > 0, substr($groups_str, 0, -1), ''));
			}

			$languages					= &new FAArrayIterator($languages);

			$request['template']->setList('languages', $languages);
			
			$request['template']->setFile('content', 'admin.html');
			$request['template']->setFile('admin_panel', 'forums_edit.html');
		} else {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

			return $action->execute($request);
		}

		return TRUE;
	}
}

class AdminUpdateForum extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			global $_QUERYPARAMS;

			/* Error checking on the fields */
			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFORUM'), 'content', FALSE);

				return $action->execute($request);
				return TRUE;
			}

			$forum					= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['forum'] ." FROM ". K4FORUMS ." f LEFT JOIN ". K4INFO ." i ON f.forum_id = i.id WHERE i.id = ". intval($_REQUEST['id']));

			if(!is_array($forum) || empty($forum)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFORUM'), 'content', FALSE);

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
			$update_b			= &$request['dba']->prepareStatement("UPDATE ". K4FORUMS ." SET description=?,pass=?,is_forum=?,is_link=?,link_href=?,link_show_redirects=?,forum_rules=?,topicsperpage=?,postsperpage=?,maxpolloptions=?,defaultlang=?,moderating_groups=?,prune_auto=?,prune_frequency=?,prune_post_age=?,prune_post_viewed_age=?,prune_old_polls=?,prune_announcements=?,prune_stickies=? WHERE forum_id=?");
			$update_c			= &$request['dba']->prepareStatement("UPDATE ". K4MAPS ." SET name=? WHERE varname=?");

			/* Set the query values */
			$update_a->setString(1, $_REQUEST['name']);
			$update_a->setInt(2, $_REQUEST['row_order']);
			$update_a->setInt(3, $forum['id']);
			
			$forum_rules		= &new BBCodex(&$request['dba'], $request['user']->getInfoArray(), $_REQUEST['forum_rules'], $forum['id'], TRUE, TRUE, TRUE, TRUE);

			/* Build the query for the forums table */
			$update_b->setString(1, $_REQUEST['description']);
			$update_b->setString(2, $_REQUEST['pass']);
			$update_b->setInt(3, iif(intval($_REQUEST['is_link']) == 1, 0, 1));
			$update_b->setInt(4, $_REQUEST['is_link']);
			$update_b->setString(5, $_REQUEST['link_href']);
			$update_b->setInt(6, $_REQUEST['link_show_redirects']);
			$update_b->setString(7, $forum_rules->parse());
			$update_b->setInt(8, $_REQUEST['topicsperpage']);
			$update_b->setInt(9, $_REQUEST['postsperpage']);
			$update_b->setInt(10, $_REQUEST['maxpolloptions']);
			$update_b->setString(11, $_REQUEST['defaultlang']);
			$update_b->setString(12, iif(isset($_REQUEST['moderators']) && is_array($_REQUEST['moderators']) && !empty($_REQUEST['moderators']), serialize($_REQUEST['moderators']), ''));
			$update_b->setInt(13, $_REQUEST['prune_auto']);
			$update_b->setInt(14, $_REQUEST['prune_frequency']);
			$update_b->setInt(15, $_REQUEST['prune_post_age']);
			$update_b->setInt(16, $_REQUEST['prune_post_viewed_age']);
			$update_b->setInt(17, $_REQUEST['prune_old_polls']);
			$update_b->setInt(18, $_REQUEST['prune_announcements']);
			$update_b->setInt(19, $_REQUEST['prune_stickies']);
			$update_b->setInt(20, $forum['id']);
			
			/* Simple update on the maps table */
			$update_c->setString(1, $_REQUEST['name']);
			$update_c->setString(2, 'forum'. $forum['id']);

			/* Do all of the updates */
			$update_a->executeUpdate();
			$update_b->executeUpdate();
			$update_c->executeUpdate();
			
			if(!@touch(CACHE_FILE, time()-86460)) {
				@unlink(CACHE_FILE);
			}

			$action = new K4InformationAction(new K4LanguageElement('L_UPDATEDFORUM', $forum['name']), 'content', FALSE, 'admin.php?act=forums', 3);


			return $action->execute($request);

		} else {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

			return $action->execute($request);
		}

		return TRUE;
	}
}

class AdminRemoveForum extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			global $_QUERYPARAMS;

			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFORUM'), 'content', FALSE);

				return $action->execute($request);
				return TRUE;
			}

			$forum					= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['forum'] ." FROM ". K4FORUMS ." f LEFT JOIN ". K4INFO ." i ON f.forum_id = i.id WHERE i.id = ". intval($_REQUEST['id']));

			if(!is_array($forum) || empty($forum)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFORUM'), 'content', FALSE);
				return $action->execute($request);
			}

			$forums			= &$request['dba']->executeQuery("SELECT * FROM ". K4INFO ." WHERE row_left >= ". intval($forum['row_left']) ." AND row_right <= ". intval($forum['row_right']) ." AND row_type = ". FORUM);
			
			$request['dba']->beginTransaction();

			$heirarchy		= &new Heirarchy();
			
			/* Deal with this forum and any sub-forums */
			while($forums->next()) {
				$f				= $forums->current();
				$forum_maps		= $request['dba']->getRow("SELECT * FROM ". K4MAPS ." WHERE varname = 'forum". $f['id'] ."'");
				$heirarchy->removeNode($forum_maps, K4MAPS);

				$request['dba']->executeUpdate("DELETE FROM ". K4FORUMS ." WHERE forum_id=". intval($f['id']));
				$request['dba']->executeUpdate("DELETE FROM ". K4TOPICS ." WHERE forum_id=". intval($f['id']));
				$request['dba']->executeUpdate("DELETE FROM ". K4REPLIES ." WHERE forum_id=". intval($f['id']));
			}

			$request['dba']->executeUpdate("DELETE FROM ". K4SUBSCRIPTIONS ." WHERE forum_id = ". intval($forum['id']));
			$request['dba']->executeUpdate("DELETE FROM ". K4MAILQUEUE ." WHERE row_id = ". intval($forum['id']) ." AND row_type = ". FORUM);
			
			/* This will take care of everything in the K4INFO table */
			$heirarchy->removeNode($forum, K4INFO);
			$heirarchy->removeItem($forum, K4INFO, 'forum_id');
			
			$request['dba']->commitTransaction();

			if(!@touch(CACHE_FILE, time()-86460)) {
				@unlink(CACHE_FILE);
			}
			if(!@touch(CACHE_EMAIL_FILE, time()-86460)) {
				@unlink(CACHE_EMAIL_FILE);
			}

			$action = new K4InformationAction(new K4LanguageElement('L_REMOVEDFORUM', $forum['name']), 'content', FALSE, 'admin.php?act=forums', 3);
			return $action->execute($request);

		} else {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);
			return $action->execute($request);
		}

		return TRUE;
	}
}

class AdminForumPermissions extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			global $_QUERYPARAMS;

			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFORUM'), 'content', FALSE);

				return $action->execute($request);
				return TRUE;
			}

			$forum					= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['forum'] ." FROM ". K4FORUMS ." f LEFT JOIN ". K4INFO ." i ON f.forum_id = i.id WHERE i.id = ". intval($_REQUEST['id']));

			if(!is_array($forum) || empty($forum)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFORUM'), 'content', FALSE);

				return $action->execute($request);
				return TRUE;
			}
			
			foreach($forum as $key => $val)
				$request['template']->setVar('forum_'. $key, $val);
			
			$result					= &$request['dba']->executeQuery("SELECT * FROM ". K4MAPS ." WHERE forum_id = ". intval($forum['id']) ." ORDER BY row_left ASC");

			$forum_maps				= &new MAPSIterator(&$request['dba'], $result, 2);
			
			$request['template']->setList('forum_maps', $forum_maps);

			$request['template']->setFile('content', 'admin.html');
			$request['template']->setFile('admin_panel', 'forums_permissions.html');
		} else {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

			return $action->execute($request);
		}

		return TRUE;
	}
}

class AdminUpdateForumPermissions extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			global $_QUERYPARAMS;

			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFORUM'), 'content', FALSE);

				return $action->execute($request);
				return TRUE;
			}

			$forum							= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['forum'] ." FROM ". K4FORUMS ." f LEFT JOIN ". K4INFO ." i ON f.forum_id = i.id WHERE i.id = ". intval($_REQUEST['id']));

			if(!is_array($forum) || empty($forum)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFORUM'), 'content', FALSE);

				return $action->execute($request);
				return TRUE;
			}
			
			foreach($forum as $key => $val)
				$request['template']->setVar('forum_'. $key, $val);
			
			$forum_map						= $request['dba']->getRow("SELECT * FROM ". K4MAPS ." WHERE varname = 'forum". $forum['id'] ."' AND forum_id = ". intval($forum['id']));
			$forum_maps						= $request['dba']->executeQuery("SELECT * FROM ". K4MAPS ." WHERE forum_id = ". intval($forum['id']) ." AND row_left >= ". intval($forum_map['row_left']) ." AND row_right <= ". intval($forum_map['row_right']) ." ORDER BY row_left ASC");
			
			/* Loop through the forum map items */
			while($forum_maps->next()) {
				$f							= $forum_maps->current();

				if(isset($_REQUEST[$f['varname'] .'_can_view']) && isset($_REQUEST[$f['varname'] .'_can_add']) && isset($_REQUEST[$f['varname'] .'_can_edit']) && isset($_REQUEST[$f['varname'] .'_can_del'])) {
					
					if(($_REQUEST[$f['varname'] .'_can_view'] != $f['can_view']) || ($_REQUEST[$f['varname'] .'_can_add'] != $f['can_add']) || ($_REQUEST[$f['varname'] .'_can_edit'] != $f['can_edit']) || ($_REQUEST[$f['varname'] .'_can_del'] != $f['can_del'])) {

						$update				= &$request['dba']->prepareStatement("UPDATE ". K4MAPS ." SET can_view=?,can_add=?,can_edit=?,can_del=? WHERE varname=? AND forum_id=?");
						$update->setInt(1, $_REQUEST[$f['varname'] .'_can_view']);
						$update->setInt(2, $_REQUEST[$f['varname'] .'_can_add']);
						$update->setInt(3, $_REQUEST[$f['varname'] .'_can_edit']);
						$update->setInt(4, $_REQUEST[$f['varname'] .'_can_del']);
						$update->setString(5, $f['varname']);
						$update->setInt(6, $forum['id']);

						$update->executeUpdate();

						unset($update);
					}
				}
			}
			
			if(!@touch(CACHE_FILE, time()-86460)) {
				@unlink(CACHE_FILE);
			}

			$action = new K4InformationAction(new K4LanguageElement('L_UPDATEDFORUMPERMS', $forum['name']), 'content', FALSE, 'admin.php?act=forums', 3);


			return $action->execute($request);
		} else {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

			return $action->execute($request);
		}

		return TRUE;
	}
}

?>