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
* @version $Id: forums.class.php 156 2005-07-15 17:51:48Z Peter Goodman $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	return;
}

class AdminForums extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			global $_QUERYPARAMS, $_ALLFORUMS;
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_FORUMS');
			$request['template']->setVar('forums_on', '_on');
			
			$request['template']->setFile('sidebar_menu', 'menus/forums.html');
			$request['template']->setList('forums', new FAArrayIterator($_ALLFORUMS));
			$request['template']->setFile('content', 'forums_manage.html');
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminAddForum extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			
			global $_QUERYPARAMS;
			
			if(isset($_REQUEST['category_id']) && intval($_REQUEST['category_id']) != 0) {

				/* Error checking */
				if(!isset($_REQUEST['category_id']) || intval($_REQUEST['category_id']) == 0) {
					$action = new K4InformationAction(new K4LanguageElement('L_INVALIDCATEGORY'), 'content', FALSE);
					return $action->execute($request);
				}

				$category					= $request['dba']->getRow("SELECT * FROM ". K4CATEGORIES ." WHERE category_id = ". intval(@$_REQUEST['category_id']));			

				if(!is_array($category) || empty($category)) {
					$action = new K4InformationAction(new K4LanguageElement('L_INVALIDCATEGORY'), 'content', FALSE);
					return $action->execute($request);
				}
				
				$request['template']->setVar('category_id', $category['category_id']);
				
				/* Do we have a parent forum Id? */
				if(intval($_REQUEST['category_id']) != 0) {
					if(isset($_REQUEST['forum_id']) && intval($_REQUEST['forum_id']) != 0) {
						$forum					= $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval(@$_REQUEST['forum_id']));			

						if(!is_array($forum) || empty($forum)) {
							$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFORUM'), 'content', FALSE);
							return $action->execute($request);
						}
						
						$request['template']->setVar('forum_id', $forum['forum_id']);
					}
				}
			}

			$languages					= array();
			
			$dir						= dir(K4_BASE_DIR .'/lang');
			
			while(false !== ($file = $dir->read())) {

				if($file != '.' && $file != '..' && $file != 'CVS'  && $file != '.svn' && is_dir(K4_BASE_DIR .'/lang/'. $file) && is_readable(K4_BASE_DIR .'/lang/'. $file)) {
					$languages[]		= array('lang' => $file, 'name' => ucfirst($file));
				}
			}
			
			$languages					= &new FAArrayIterator($languages);
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_FORUMS');
			$request['template']->setVar('forums_on', '_on');
			
			$request['template']->setFile('sidebar_menu', 'menus/forums.html');
			$request['template']->setList('languages', $languages);
			$request['template']->setFile('content', 'forums_add.html');
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminInsertForum extends FAAction {

	var $dba;

	function execute(&$request) {		
		
		$this->dba			= &$request['dba'];

		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			
			global $_QUERYPARAMS;
			
			if((isset($_REQUEST['category_id']) && intval($_REQUEST['category_id']) != 0) || (isset($_REQUEST['forum_id']) && intval($_REQUEST['forum_id']) != 0)) {

				/* Error checking */
				if(!isset($_REQUEST['category_id']) || intval($_REQUEST['category_id']) == 0) {
					$action = new K4InformationAction(new K4LanguageElement('L_INVALIDCATEGORY'), 'content', FALSE);
					return $action->execute($request);
				}
				
				/* Attempt to get this category */
				$category					= $request['dba']->getRow("SELECT * FROM ". K4CATEGORIES ." WHERE category_id = ". intval(@$_REQUEST['category_id']));			

				if(!is_array($category) || empty($category)) {
					$action = new K4InformationAction(new K4LanguageElement('L_INVALIDCATEGORY'), 'content', FALSE);
					return $action->execute($request);
				}
				
				$forum						= $category;

				/* Do we have a parent forum Id? */
				if(isset($_REQUEST['forum_id']) && intval($_REQUEST['forum_id']) != 0) {
					
					/* Attempt to get this forum */
					$forum					= $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval(@$_REQUEST['forum_id']));			

					if(!is_array($forum) || empty($forum)) {
						$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFORUM'), 'content', FALSE);
						return $action->execute($request);
					}
				}

				/* Set the parent id */
				$parent_id					= $forum == $category ? $forum['category_id'] : $forum['forum_id'];
			} else {
				
				$category					= array('category_id' => 0, 'row_level' => 0, 'row_type' => CATEGORY, 'parent_id' => 0);
				$forum						= array('forum_id' => 0, 'row_level' => 0, 'row_type' => FORUM, 'parent_id' => 0);
				$parent_id					= 0;
			}

			/* Error checking on the fields */
			if(!isset($_REQUEST['name']) || $_REQUEST['name'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTFORUMNAME'), 'content', TRUE);
				return $action->execute($request);
			}
//			if(!isset($_REQUEST['description']) || $_REQUEST['description'] == '') {
//				$action = new K4InformationAction(new K4LanguageElement('L_INSERTFORUMDESC'), 'content', TRUE);
//				return $action->execute($request);
//			}
			if(!isset($_REQUEST['row_order']) || $_REQUEST['row_order'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTFORUMORDER'), 'content', TRUE);
				return $action->execute($request);
			}
			if(!ctype_digit($_REQUEST['row_order'])) {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTFORUMORDERNUM'), 'content', TRUE);
				return $action->execute($request);
			}
			

			$users_array		= '';

			/* Are there any moderating users? */
			if(isset($_REQUEST['moderating_users']) && $_REQUEST['moderating_users'] != '') {
				$users			= preg_replace("~(\r\n|\r|\n)~i", "\n", $_REQUEST['moderating_users']);
				$users			= explode("\n", $users);
				
				$users_array	= array();

				foreach($users as $username) {
					$u			= $request['dba']->getRow("SELECT * FROM ". K4USERS ." WHERE name = '". $request['dba']->quote($username) ."'");
					
					// TODO: incremement this users perms if they are not sufficient to moderate
					if(is_array($u) && !empty($u)) {
						$users_array[$u['id']]	= $u['name'];
					}
				}
				
				$users_array	= count($users_array) > 0 ? serialize($users_array) : '';
			}
						
			$request['dba']->beginTransaction();

			$insert_a			= &$request['dba']->prepareStatement("INSERT INTO ". K4FORUMS ." (name,category_id,description,pass,is_forum,is_link,link_href,link_show_redirects,forum_rules,topicsperpage,postsperpage,maxpolloptions,defaultlang,moderating_groups,prune_auto,prune_frequency,prune_post_age,prune_post_viewed_age,prune_old_polls,prune_announcements,prune_stickies,row_type,row_level,created,row_order,parent_id,moderating_users) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
			
			$forum_rules		= &new BBCodex($request['dba'], $request['user']->getInfoArray(), $_REQUEST['forum_rules'], FALSE, TRUE, TRUE, TRUE, TRUE);
			$description		= &new BBCodex($request['dba'], $request['user']->getInfoArray(), @$_REQUEST['description'], FALSE, TRUE, TRUE, TRUE, TRUE);
			
			/* Build the query for the forums table */
			$insert_a->setString(1, $_REQUEST['name']);
			$insert_a->setInt(2, $category['category_id']);
			$insert_a->setString(3, $description->parse());
			$insert_a->setString(4, $_REQUEST['pass']);
			$insert_a->setInt(5, iif(intval($_REQUEST['is_link']) == 1, 0, 1));
			$insert_a->setInt(6, $_REQUEST['is_link']); 
			$insert_a->setString(7, $_REQUEST['link_href']);
			$insert_a->setInt(8, $_REQUEST['link_show_redirects']);
			$insert_a->setString(9, $forum_rules->parse());
			$insert_a->setInt(10, $_REQUEST['topicsperpage']);
			$insert_a->setInt(11, $_REQUEST['postsperpage']);
			$insert_a->setInt(12, $_REQUEST['maxpolloptions']);
			$insert_a->setString(13, $_REQUEST['defaultlang']);
			$insert_a->setString(14, iif((isset($_REQUEST['moderators']) && is_array($_REQUEST['moderators']) && !empty($_REQUEST['moderators'])), implode('|', $_REQUEST['moderators']), ''));
			$insert_a->setInt(15, $_REQUEST['prune_auto']);
			$insert_a->setInt(16, $_REQUEST['prune_frequency']);
			$insert_a->setInt(17, $_REQUEST['prune_post_age']);
			$insert_a->setInt(18, $_REQUEST['prune_post_viewed_age']);
			$insert_a->setInt(19, $_REQUEST['prune_old_polls']);
			$insert_a->setInt(20, $_REQUEST['prune_announcements']);
			$insert_a->setInt(21, $_REQUEST['prune_stickies']);
			$insert_a->setInt(22, FORUM);
			$insert_a->setInt(23, $forum['row_level']+1);
			$insert_a->setInt(24, time());
			$insert_a->setInt(25, $_REQUEST['row_order']);
			$insert_a->setInt(26, $parent_id);
			$insert_a->setString(27, $users_array);

			/* Insert the extra forum info */
			$insert_a->executeUpdate();

			/* Get this forum id */
			$forum_id			= $request['dba']->getInsertId(K4FORUMS, 'forum_id');
			
			if(!($forum['row_type'] & CATEGORY)) {
				$request['dba']->executeUpdate("UPDATE ". K4FORUMS ." SET subforums = 1 WHERE forum_id = ". $forum['forum_id']);
			}
			
			$request['dba']->commitTransaction();

			reset_cache('forums');
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_FORUMS');
			$request['template']->setVar('forums_on', '_on');
			
			$request['template']->setFile('sidebar_menu', 'menus/forums.html');
			$action = new K4InformationAction(new K4LanguageElement('L_ADDEDFORUM', $_REQUEST['name']), 'content', FALSE, 'admin.php?act=forums_insertmaps&id='. $forum_id, 3);
			return $action->execute($request);

		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminInsertForumMaps extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			
			global $_MAPITEMS, $_QUERYPARAMS;
			
			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFORUM'), 'content', FALSE);
				return $action->execute($request);
			}

			$forum							= $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($_REQUEST['id']));
			
			if(!is_array($forum) || empty($forum)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFORUM'), 'content', FALSE);
				return $action->execute($request);
			}

			$parent						= $request['dba']->getRow("SELECT * FROM ". K4MAPS ." WHERE varname = 'forums'");
			
			// begin the transaction
			$request['dba']->beginTransaction();

			/* Set the default data for this forum MAP element */
			$forum_array					= array_merge(array('name' => $forum['name'], 'varname' => 'forum'. $forum['forum_id'], 'parent_id' => $parent['id'], 'user_id'=>0,'forum_id'=>$forum['forum_id'],'category_id'=>$forum['category_id']), $_MAPITEMS['forum'][0]);
			
			/**
			 * Insert the main forum MAP information
			 */
			
			$maps							= &new K4Maps();
			$maps->add($request, $forum_array, $parent, $parent['row_level'] + 1);
			
			// get the parent now
			$parent							= $request['dba']->getRow("SELECT * FROM ". K4MAPS ." WHERE varname = 'forum". $forum['forum_id'] ."' LIMIT 1");
			
			/**
			 * Insert the secondary forum MAP information
			 */
			if($forum['is_forum'] == 1) {
				for($i = 1; $i <= count($_MAPITEMS['forum']); $i++) {
					
					if(isset($_MAPITEMS['forum'][$i]) && is_array($_MAPITEMS['forum'][$i])) {
						
						$forum_array			= array_merge(array('parent_id' => $parent['id'],'forum_id'=>$forum['forum_id'],'category_id'=>$forum['category_id']), $_MAPITEMS['forum'][$i]);
						$forum_array['name']	= $request['template']->getVar('L_'. strtoupper($forum_array['varname']));
						
						// add this map item
						$maps->add($request, $forum_array, $parent, $parent['row_level'] + 1);

					}
				}
			}
			
			// commit the sql transaction
			$request['dba']->commitTransaction();

			reset_cache('forums');

			/**
			 * If we've gotten to this point.. redirect
			 */
			k4_bread_crumbs($request['template'], $request['dba'], 'L_FORUMS');
			$request['template']->setVar('forums_on', '_on');
			
			$request['template']->setFile('sidebar_menu', 'menus/forums.html');
			$action = new K4InformationAction(new K4LanguageElement('L_ADDEDFORUMPERMS', $forum['name']), 'content', FALSE, 'admin.php?act=forums', 3);
			return $action->execute($request);

		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminSimpleForumUpdate extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			
			global $_MAPITEMS, $_QUERYPARAMS;

			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFORUM'), 'content', FALSE);
				return $action->execute($request);
			}

			$forum					= $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($_REQUEST['id']));

			if(!is_array($forum) || empty($forum)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFORUM'), 'content', FALSE);
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

			$update		= &$request['dba']->prepareStatement("UPDATE ". K4FORUMS ." SET row_order=? WHERE forum_id=?");
			$update->setInt(1, $_REQUEST['row_order']);
			$update->setInt(2, $forum['forum_id']);

			$update->executeUpdate();
			
			reset_cache('forums');
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_FORUMS');
			$request['template']->setVar('forums_on', '_on');
			
			$request['template']->setFile('sidebar_menu', 'menus/forums.html');
			$action = new K4InformationAction(new K4LanguageElement('L_UPDATEDFORUM', $forum['name']), 'content', FALSE, 'admin.php?act=forums', 3);
			return $action->execute($request);

		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminEditForum extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			
			global $_QUERYPARAMS, $_USERGROUPS;

			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFORUM'), 'content', FALSE);
				return $action->execute($request);
			}

			$forum					= $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($_REQUEST['id']));

			if(!is_array($forum) || empty($forum)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFORUM'), 'content', FALSE);
				return $action->execute($request);
			}
			
			$forum_rules				= &new BBCodex($request['dba'], $request['user']->getInfoArray(), $forum['forum_rules'], $forum['forum_id'], TRUE, TRUE, TRUE, TRUE);
			$forum['forum_rules']		= $forum_rules->revert();
			$description				= &new BBCodex($request['dba'], $request['user']->getInfoArray(), $forum['description'], FALSE, TRUE, TRUE, TRUE, TRUE);
			$forum['description']		= $description->revert();

			foreach($forum as $key => $val)
				$request['template']->setVar('forum_'. $key, $val);
			
			$languages					= array();
			
			$dir						= dir(K4_BASE_DIR .'/lang');
			
			while(false !== ($file = $dir->read())) {

				if($file != '.' && $file != '..' && $file != 'CVS'  && $file != '.svn' && is_dir(K4_BASE_DIR .'/lang/'. $file) && is_readable(K4_BASE_DIR .'/lang/'. $file)) {
					$languages[]		= array('lang' => $file, 'name' => ucfirst($file));
				}
			}

			$groups		= $forum['moderating_groups'] != '' ? explode('|', $forum['moderating_groups']) : array();
			$groups_str	= '';

			if(is_array($groups)) {
				foreach($groups as $g) {
					if(isset($_USERGROUPS[$g])) {
						$groups_str	.= $g .' ';
					}
				}

				$request['template']->setVar('forum_moderating_groups', iif(strlen($groups_str) > 0, substr($groups_str, 0, -1), ''));
			}

			$moderating_users			= '';
			if($forum['moderating_users'] != '') {
				$users					= unserialize($forum['moderating_users']);
				if(is_array($users)) {
					$users				= array_values($users);
					$moderating_users	= implode("\n", $users);
				}
			}

			$request['template']->setVar('forum_moderating_users', $moderating_users);

			$languages					= &new FAArrayIterator($languages);
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_FORUMS');
			$request['template']->setVar('forums_on', '_on');
			
			$request['template']->setFile('sidebar_menu', 'menus/forums.html');
			$request['template']->setList('languages', $languages);
			$request['template']->setFile('content', 'forums_edit.html');
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminUpdateForum extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			
			global $_QUERYPARAMS;

			/* Error checking on the fields */
			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFORUM'), 'content', FALSE);
				return $action->execute($request);
			}

			$forum					= $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($_REQUEST['id']));

			if(!is_array($forum) || empty($forum)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFORUM'), 'content', FALSE);
				return $action->execute($request);
			}

			if(!isset($_REQUEST['name']) || $_REQUEST['name'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTFORUMNAME'), 'content', TRUE);
				return $action->execute($request);
			}
						
//			if(!isset($_REQUEST['description']) || $_REQUEST['description'] == '') {
//				$action = new K4InformationAction(new K4LanguageElement('L_INSERTFORUMDESC'), 'content', TRUE);
//				return $action->execute($request);
//			}
			
			if(!isset($_REQUEST['row_order']) || $_REQUEST['row_order'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTFORUMORDER'), 'content', TRUE);
				return $action->execute($request);
			}

			if(!ctype_digit($_REQUEST['row_order'])) {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTFORUMORDERNUM'), 'content', TRUE);
				return $action->execute($request);
			}

			$users_array		= '';
			/* Are there any moderating users? */
			if(isset($_REQUEST['moderating_users']) && $_REQUEST['moderating_users'] != '') {
				$users			= preg_replace("~(\r\n|\r|\n)~i", "\n", $_REQUEST['moderating_users']);
				$users			= explode("\n", $users);
				
				$users_array	= array();

				foreach($users as $username) {
					$u			= $request['dba']->getRow("SELECT * FROM ". K4USERS ." WHERE name = '". $request['dba']->quote($username) ."'");
					
					// TODO: incremement this users perms if they are not sufficient to moderate
					if(is_array($u) && !empty($u)) {
						$users_array[$u['id']]	= $u['name'];
					}
				}
				
				$users_array	= count($users_array) > 0 ? serialize($users_array) : '';
			}

			/* Build the queries */
			$update_a			= &$request['dba']->prepareStatement("UPDATE ". K4FORUMS ." SET name=?,description=?,pass=?,is_forum=?,is_link=?,link_href=?,link_show_redirects=?,forum_rules=?,topicsperpage=?,postsperpage=?,maxpolloptions=?,defaultlang=?,moderating_groups=?,prune_auto=?,prune_frequency=?,prune_post_age=?,prune_post_viewed_age=?,prune_old_polls=?,prune_announcements=?,prune_stickies=?,row_order=?,moderating_users=? WHERE forum_id=?");
			$update_b			= &$request['dba']->prepareStatement("UPDATE ". K4MAPS ." SET name=? WHERE varname=?");
						
			$forum_rules		= new BBCodex($request['dba'], $request['user']->getInfoArray(), $_REQUEST['forum_rules'], $forum['forum_id'], TRUE, TRUE, TRUE, TRUE);
			$description		= new BBCodex($request['dba'], $request['user']->getInfoArray(), @$_REQUEST['description'], $forum['forum_id'], TRUE, TRUE, TRUE, TRUE);
			
			/* Build the query for the forums table */
			$update_a->setString(1, $_REQUEST['name']);
			$update_a->setString(2, $description->parse());
			$update_a->setString(3, $_REQUEST['pass']);
			$update_a->setInt(4, iif(intval($_REQUEST['is_link']) == 1, 0, 1));
			$update_a->setInt(5, $_REQUEST['is_link']);
			$update_a->setString(6, $_REQUEST['link_href']);
			$update_a->setInt(7, $_REQUEST['link_show_redirects']);
			$update_a->setString(8, $forum_rules->parse());
			$update_a->setInt(9, $_REQUEST['topicsperpage']);
			$update_a->setInt(10, $_REQUEST['postsperpage']);
			$update_a->setInt(11, $_REQUEST['maxpolloptions']);
			$update_a->setString(12, $_REQUEST['defaultlang']);
			$update_a->setString(13, (isset($_REQUEST['moderators']) && is_array($_REQUEST['moderators']) && !empty($_REQUEST['moderators']) ? implode('|', $_REQUEST['moderators']) : ''));
			$update_a->setInt(14, $_REQUEST['prune_auto']);
			$update_a->setInt(15, $_REQUEST['prune_frequency']);
			$update_a->setInt(16, $_REQUEST['prune_post_age']);
			$update_a->setInt(17, $_REQUEST['prune_post_viewed_age']);
			$update_a->setInt(18, $_REQUEST['prune_old_polls']);
			$update_a->setInt(19, $_REQUEST['prune_announcements']);
			$update_a->setInt(20, $_REQUEST['prune_stickies']);
			$update_a->setInt(21, $_REQUEST['row_order']);
			$update_a->setString(22, $users_array);
			$update_a->setInt(23, $forum['forum_id']);
			
			/* Simple update on the maps table */
			$update_b->setString(1, $_REQUEST['name']);
			$update_b->setString(2, 'forum'. $forum['forum_id']);

			/* Do all of the updates */
			$update_a->executeUpdate();
			$update_b->executeUpdate();
						
			reset_cache('forums');

			k4_bread_crumbs($request['template'], $request['dba'], 'L_FORUMS');
			$request['template']->setVar('forums_on', '_on');
			
			$request['template']->setFile('sidebar_menu', 'menus/forums.html');
			$action = new K4InformationAction(new K4LanguageElement('L_UPDATEDFORUM', $forum['name']), 'content', FALSE, 'admin.php?act=forums', 3);
			return $action->execute($request);

		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminRemoveForum extends FAAction {
	function removeForums($forum, &$dba) {
		$forums			= &$dba->executeQuery("SELECT * FROM ". K4FORUMS ." WHERE parent_id = ". $forum['forum_id']);
		
		/* Deal with this forum and any sub-forums */
		while($forums->next()) {
			$f				= $forums->current();
			
			$dba->executeUpdate("DELETE FROM ". K4MAPS ." WHERE forum_id = ". intval($f['forum_id']));
			$dba->executeUpdate("DELETE FROM ". K4FORUMS ." WHERE forum_id=". intval($f['forum_id']));
			$dba->executeUpdate("DELETE FROM ". K4TOPICS ." WHERE forum_id=". intval($f['forum_id']));
			$dba->executeUpdate("DELETE FROM ". K4REPLIES ." WHERE forum_id=". intval($f['forum_id']));
			$dba->executeUpdate("DELETE FROM ". K4SUBSCRIPTIONS ." WHERE forum_id = ". intval($f['forum_id']));
			$dba->executeUpdate("DELETE FROM ". K4MAILQUEUE ." WHERE row_id = ". intval($f['forum_id']) ." AND row_type = ". FORUM);

			if($f['subforums'] >= 1) {
				$this->removeForums($f, $dba, $heirarchy);
			}
		}
	}
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			
			global $_QUERYPARAMS;

			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFORUM'), 'content', FALSE);
				return $action->execute($request);
			}

			$forum					= $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($_REQUEST['id']));

			if(!is_array($forum) || empty($forum)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFORUM'), 'content', FALSE);
				return $action->execute($request);
			}

			if($forum['can_delete'] == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_CANTDELETETHISFORUM'), 'content', FALSE);
				return $action->execute($request);
			}

			$request['dba']->beginTransaction();
			
			/* Remove this forum */
			$request['dba']->executeUpdate("DELETE FROM ". K4MAPS ." WHERE forum_id=". intval($forum['forum_id']));
			$request['dba']->executeUpdate("DELETE FROM ". K4FORUMS ." WHERE forum_id=". intval($forum['forum_id']));
			$request['dba']->executeUpdate("DELETE FROM ". K4TOPICS ." WHERE forum_id=". intval($forum['forum_id']));
			$request['dba']->executeUpdate("DELETE FROM ". K4REPLIES ." WHERE forum_id=". intval($forum['forum_id']));
			
			/* Remove any sub-forums */			
			$this->removeForums($forum, $request['dba']);

			$request['dba']->executeUpdate("DELETE FROM ". K4SUBSCRIPTIONS ." WHERE forum_id = ". intval($forum['forum_id']));
			$request['dba']->executeUpdate("DELETE FROM ". K4MAILQUEUE ." WHERE row_id = ". intval($forum['forum_id']) ." AND row_type = ". FORUM);
						
			$request['dba']->commitTransaction();

			reset_cache('forums');
			reset_cache('email_queue');
			reset_cache('datastore');
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_FORUMS');
			$request['template']->setVar('forums_on', '_on');
			
			$request['template']->setFile('sidebar_menu', 'menus/forums.html');
			$action = new K4InformationAction(new K4LanguageElement('L_REMOVEDFORUM', $forum['name']), 'content', FALSE, 'admin.php?act=forums', 3);
			return $action->execute($request);

		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminForumPermissions extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			
			global $_QUERYPARAMS;

			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFORUM'), 'content', FALSE);
				return $action->execute($request);
			}

			$forum					= $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($_REQUEST['id']));

			if(!is_array($forum) || empty($forum)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFORUM'), 'content', FALSE);
				return $action->execute($request);
			}
			
			foreach($forum as $key => $val)
				$request['template']->setVar('forum_'. $key, $val);
						
			/* Get the parent id's */
			$parents = array();
			foreach($_COOKIE as $key => $val) {
				if(strpos($key, 'mapsgui') !== FALSE) {
					$parents[] = intval($_COOKIE[$key]);
				}
			}
						
			$all_maps = array();
			$maps = &$request['dba']->executeQuery("SELECT * FROM ". K4MAPS ." WHERE group_id = 0 AND forum_id = ". intval($forum['forum_id']));
			
			// get the map's
			get_recursive_maps($request, $all_maps, $parents, $maps, 2);
			
			$all_maps = &new FAArrayIterator($all_maps);
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_FORUMS');
			$request['template']->setVar('forums_on', '_on');
			
			$request['template']->setFile('sidebar_menu', 'menus/forums.html');
			$request['template']->setList('forum_maps', $all_maps);
			$request['template']->setFile('content', 'forums_permissions.html');
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminUpdateForumPermissions extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			
			global $_QUERYPARAMS;

			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFORUM'), 'content', FALSE);
				return $action->execute($request);
			}

			$forum							= $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($_REQUEST['id']));

			if(!is_array($forum) || empty($forum)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFORUM'), 'content', FALSE);
				return $action->execute($request);
			}
			
			foreach($forum as $key => $val)
				$request['template']->setVar('forum_'. $key, $val);
			
			$forum_map						= $request['dba']->getRow("SELECT * FROM ". K4MAPS ." WHERE varname = 'forum". $forum['forum_id'] ."' AND forum_id = ". intval($forum['forum_id']));
			$forum_maps						= $request['dba']->executeQuery("SELECT * FROM ". K4MAPS ." WHERE forum_id = ". intval($forum['forum_id']));
			
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
						$update->setInt(6, $forum['forum_id']);

						$update->executeUpdate();

						unset($update);
					}
				}
			}
			
			reset_cache('forums');
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_FORUMS');
			$request['template']->setVar('forums_on', '_on');
			
			$request['template']->setFile('sidebar_menu', 'menus/forums.html');
			$action = new K4InformationAction(new K4LanguageElement('L_UPDATEDFORUMPERMS', $forum['name']), 'content', FALSE, 'admin.php?act=forums', 3);
			return $action->execute($request);

		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

?>