<?php
/**
* k4 Bulletin Board, forums.class.php
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
* @version $Id: forums.class.php 156 2005-07-15 17:51:48Z Peter Goodman $
* @package k42
*/


if(!defined('IN_K4')) {
	return;
}

class AdminForumsHome extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			global $_QUERYPARAMS, $_ALLFORUMS;
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_FORUMS');
			$request['template']->setVar('forums_on', '_on');
			
			$request['template']->setFile('sidebar_menu', 'menus/forums.html');
			$request['template']->setList('forums', new FAArrayIterator($_ALLFORUMS));
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
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

class AdminForumSelect extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			k4_bread_crumbs($request['template'], $request['dba'], 'L_FORUMS');
			$request['template']->setVar('forums_on', '_on');
			
			$request['template']->setFile('sidebar_menu', 'menus/forums.html');
			$request['template']->setFile('content', 'forums_select.html');
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
			
			$parent = 0; // the direct parent of this forum

			/* Do we have a parent forum Id? */
			if(isset($_REQUEST['forum_id']) && intval($_REQUEST['forum_id']) != 0) {
				$forum					= $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval(@$_REQUEST['forum_id']));			

				if(!is_array($forum) || empty($forum)) {
					$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFORUM'), 'content', FALSE);
					return $action->execute($request);
				}
				
				$parent					= intval($_REQUEST['forum_id']);
				$request['template']->setVar('forum_id', $forum['forum_id']);
			}

			$languages = get_files(K4_BASE_DIR .'/lang', TRUE, FALSE, array('.svn', '.htaccess', '.', '..'));
			$languages					= &new FAArrayIterator($languages);
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_FORUMS');
			$request['template']->setVar('forum_parent', $parent);
			$request['template']->setVar('forums_on', '_on');
			$request['template']->setVar('forum_action', 'admin.php?act=forums_insert');
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
		
		$this->dba			= $request['dba'];

		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			
			global $_QUERYPARAMS;
			
			if(isset($_REQUEST['parent_id']) && intval($_REQUEST['parent_id']) > 0) {
				
				$forum					= $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($_REQUEST['parent_id']));			

				if(!is_array($forum) || empty($forum)) {
					$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFORUM'), 'content', FALSE);
					return $action->execute($request);
				}
				
				$parent_id		= $forum['forum_id'];

			} else {
				$forum			= array('forum_id' => 0, 'row_level' => 0, 'row_type' => FORUM, 'parent_id' => 0);
				$parent_id		= 0;
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

			$insert_a			= $request['dba']->prepareStatement("INSERT INTO ". K4FORUMS ." (name,description,pass,is_forum,is_link,link_href,link_show_redirects,forum_rules,topicsperpage,postsperpage,maxpolloptions,defaultlang,moderating_groups,prune_auto,prune_frequency,prune_post_age,prune_post_viewed_age,prune_old_polls,prune_announcements,prune_stickies,row_type,created,row_order,parent_id,moderating_users) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
			
			$parser				= &new BBParser;
			$forum_rules		= $parser->parse((isset($_REQUEST['forum_rules']) ? $_REQUEST['forum_rules'] : ''));
			$description		= $parser->parse((isset($_REQUEST['description']) ? $_REQUEST['description'] : ''));
			
			/* Build the query for the forums table */
			$insert_a->setString(1, $_REQUEST['name']);
			$insert_a->setString(2, $description);
			$insert_a->setString(3, $_REQUEST['pass']);
			$insert_a->setInt(4, (intval($_REQUEST['is_link']) == 1 ? 0 : 1));
			$insert_a->setInt(5, $_REQUEST['is_link']); 
			$insert_a->setString(6, $_REQUEST['link_href']);
			$insert_a->setInt(7, $_REQUEST['link_show_redirects']);
			$insert_a->setString(8, $forum_rules);
			$insert_a->setInt(9, $_REQUEST['topicsperpage']);
			$insert_a->setInt(10, $_REQUEST['postsperpage']);
			$insert_a->setInt(11, $_REQUEST['maxpolloptions']);
			$insert_a->setString(12, $_REQUEST['defaultlang']);
			$insert_a->setString(13, ((isset($_REQUEST['moderators']) && is_array($_REQUEST['moderators']) && !empty($_REQUEST['moderators'])) ? implode('|', $_REQUEST['moderators']) : ''));
			$insert_a->setInt(14, $_REQUEST['prune_auto']);
			$insert_a->setInt(15, $_REQUEST['prune_frequency']);
			$insert_a->setInt(16, $_REQUEST['prune_post_age']);
			$insert_a->setInt(17, $_REQUEST['prune_post_viewed_age']);
			$insert_a->setInt(18, $_REQUEST['prune_old_polls']);
			$insert_a->setInt(19, $_REQUEST['prune_announcements']);
			$insert_a->setInt(20, $_REQUEST['prune_stickies']);
			$insert_a->setInt(21, (isset($_REQUEST['row_type']) && ctype_digit($_REQUEST['row_type']) ? $_REQUEST['row_type'] : FORUM));
			$insert_a->setInt(22, time());
			$insert_a->setInt(23, $_REQUEST['row_order']);
			$insert_a->setInt(24, $parent_id);
			$insert_a->setString(25, $users_array);

			/* Insert the extra forum info */
			$insert_a->executeUpdate();

			/* Get this forum id */
			$forum_id			= $request['dba']->getInsertId(K4FORUMS, 'forum_id');
			
			// update the parent forum's subforums column
			$request['dba']->executeUpdate("UPDATE ". K4FORUMS ." SET subforums=1 WHERE forum_id=". intval($forum['forum_id']));
			
			$request['dba']->commitTransaction();

			reset_cache('all_forums');
			
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

			reset_cache('all_forums');
			reset_cache('maps');

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

			if(!isset($_REQUEST['forum_id']) || intval($_REQUEST['forum_id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFORUM'), 'content', FALSE);
				return $action->execute($request);
			}

			$forum					= $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($_REQUEST['forum_id']));

			if(!is_array($forum) || empty($forum)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFORUM'), 'content', FALSE);
				return $action->execute($request);
			}

			if(!isset($_REQUEST['row_order']) || $_REQUEST['row_order'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTFORUMORDER'), 'content', TRUE);
				return $action->execute($request);
			}

			if(!ctype_digit($_REQUEST['row_order'])) {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTFORUMORDERNUM'), 'content', TRUE);
				return $action->execute($request);
			}

			$update		= $request['dba']->prepareStatement("UPDATE ". K4FORUMS ." SET row_order=? WHERE forum_id=?");
			$update->setInt(1, $_REQUEST['row_order']);
			$update->setInt(2, $forum['forum_id']);

			$update->executeUpdate();
			
			reset_cache('all_forums');
			
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

			if(!isset($_REQUEST['forum_id']) || intval($_REQUEST['forum_id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFORUM'), 'content', FALSE);
				return $action->execute($request);
			}

			$forum					= $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($_REQUEST['forum_id']));

			if(!is_array($forum) || empty($forum)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFORUM'), 'content', FALSE);
				return $action->execute($request);
			}
			
			$parser = &new BBParser;
			$forum['forum_rules']		= $parser->revert($forum['forum_rules']);
			$forum['description']		= $parser->revert($forum['description']);
			
			$languages = get_files(K4_BASE_DIR .'/lang', TRUE, FALSE, array('.svn', '.htaccess', '.', '..'));
			
			// set the forum info to the template
			foreach($forum as $key => $val)
				$request['template']->setVar('forum_'. $key, $val);

			$groups		= $forum['moderating_groups'] != '' ? explode('|', $forum['moderating_groups']) : array();
			$groups_str	= '';
			
			if(is_array($groups)) {
				foreach($groups as $g) {
					if(isset($_USERGROUPS[$g])) {
						$groups_str	.= $g .'|';
					}
				}

				$request['template']->setVar('forum_moderating_groups', (strlen($groups_str) > 0 ? substr($groups_str, 0, -1) : ''));
			}

			$moderating_users			= '';
			if($forum['moderating_users'] != '') {
				$users					= force_unserialize($forum['moderating_users']);
				if(is_array($users)) {
					$users				= array_values($users);
					$moderating_users	= implode("\n", $users);
				}
			}
			
			// set the direct parent
			if($forum['parent_id'] > 0) {
				$forum['parent_id'] = intval($forum['parent_id']);
			}

			$request['template']->setVar('forum_parent', $forum['parent_id']);

			$request['template']->setVar('forum_moderating_users', $moderating_users);
			
			// forum languages
			$languages					= &new FAArrayIterator($languages);

			k4_bread_crumbs($request['template'], $request['dba'], 'L_FORUMS');
			$request['template']->setVar('forums_on', '_on');
			
			$request['template']->setVar('is_edit', 1);
			
			$request['template']->setVar('forum_action', 'admin.php?act=forums_update');
			$request['template']->setFile('sidebar_menu', 'menus/forums.html');
			$request['template']->setList('languages', $languages);
			$request['template']->setFile('content', 'forums_add.html');
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
			if(!isset($_REQUEST['forum_id']) || intval($_REQUEST['forum_id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFORUM'), 'content', FALSE);
				return $action->execute($request);
			}

			$forum					= $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($_REQUEST['forum_id']));

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

			if(isset($_REQUEST['parent_id']) && intval($_REQUEST['parent_id']) > 0) {
				
				$pforum					= $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($_REQUEST['parent_id']));			

				if(!is_array($forum) || empty($forum)) {
					$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFORUM'), 'content', FALSE);
					return $action->execute($request);
				}
				
				$parent_id		= $pforum['forum_id'];

			} else {
				$pforum			= array('forum_id' => 0, 'row_level' => 0, 'row_type' => FORUM, 'parent_id' => 0, 'subforums' => 0);
				$parent_id		= 0;
			}
			
			// the forum has moved location
			if($parent_id != $forum['parent_id']) {
				if($parent_id > 0) {
					
					// update the old parent forum
					if(intval($request['dba']->getValue("SELECT COUNT(*) FROM ". K4FORUMS ." WHERE parent_id=". intval($forum['parent_id']))) == 1) {
						$request['dba']->executeUpdate("UPDATE ". K4FORUMS ." SET subforums=0 WHERE forum_id=". intval($forum['parent_id']));
					}
					// update the new prent forum
					if($pforum['subforums'] == 0) {
						$request['dba']->executeUpdate("UPDATE ". K4FORUMS ." SET subforums=1 WHERE forum_id={$parent_id}");
					}
				}
			}

			$users_array		= '';
			/* Are there any moderating users? */
			if(isset($_REQUEST['moderating_users']) && $_REQUEST['moderating_users'] != '') {
				$users			= preg_replace("~(\r\n|\r|\n)~i", "\n", $_REQUEST['moderating_users']);
				$users			= explode("\n", $users);
				
				$users_array	= array();

				foreach($users as $username) {
					$u			= $request['dba']->getRow("SELECT * FROM ". K4USERS ." WHERE name = '". $request['dba']->quote($username) ."'");
					
					if(is_array($u) && !empty($u)) {
						
						if($u['perms'] < MODERATOR)
							$request['dba']->executeUpdate("UPDATE ". K4USERS ." SET perms=". MODERATOR ." WHERE id=". intval($u['id']));

						$users_array[$u['id']]	= $u['name'];
					}
				}
				
				$users_array	= count($users_array) > 0 ? serialize($users_array) : '';
			}

			/* Build the queries */
			$update_a			= $request['dba']->prepareStatement("UPDATE ". K4FORUMS ." SET name=?,description=?,pass=?,is_forum=?,is_link=?,link_href=?,link_show_redirects=?,forum_rules=?,topicsperpage=?,postsperpage=?,maxpolloptions=?,defaultlang=?,moderating_groups=?,prune_auto=?,prune_frequency=?,prune_post_age=?,prune_post_viewed_age=?,prune_old_polls=?,prune_announcements=?,prune_stickies=?,row_order=?,moderating_users=?,parent_id=?,row_type=? WHERE forum_id=?");
			$update_b			= $request['dba']->prepareStatement("UPDATE ". K4MAPS ." SET name=? WHERE varname=?");
						
			$parser = &new BBParser;
			$forum_rules		= $parser->parse((isset($_REQUEST['forum_rules']) ? $_REQUEST['forum_rules'] : ''));
			$description		= $parser->parse((isset($_REQUEST['description']) ? $_REQUEST['description'] : ''));
			
			/* Build the query for the forums table */
			$update_a->setString(1, $_REQUEST['name']);
			$update_a->setString(2, $description);
			$update_a->setString(3, $_REQUEST['pass']);
			$update_a->setInt(4, iif(intval($_REQUEST['is_link']) == 1, 0, 1));
			$update_a->setInt(5, $_REQUEST['is_link']);
			$update_a->setString(6, $_REQUEST['link_href']);
			$update_a->setInt(7, $_REQUEST['link_show_redirects']);
			$update_a->setString(8, $forum_rules);
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
			$update_a->setInt(23, $parent_id);
			$update_a->setInt(24, (isset($_REQUEST['row_type']) && ctype_digit($_REQUEST['row_type']) ? $_REQUEST['row_type'] : FORUM));
			$update_a->setInt(25, $forum['forum_id']);
			
			/* Simple update on the maps table */
			$update_b->setString(1, $_REQUEST['name']);
			$update_b->setString(2, 'forum'. $forum['forum_id']);

			/* Do all of the updates */
			$update_a->executeUpdate();
			$update_b->executeUpdate();
			
//			if($forum['category_id'] != $pcategory['category_id']) {
//				
//				$request['dba']->executeUpdate("UPDATE ". K4POSTS ." SET row_level=". ($pforum['row_level']+1) .", category_id=". $pforum['category_id'] ." WHERE forum_id=". $forum['forum_id']);
//				$request['dba']->executeUpdate("UPDATE ". K4POSTS ." SET row_level=row_level+". ($pforum['row_level'] < $forum['row_level'] ? $forum['row_level'] - $pforum['row_level'] : $pforum['row_level'] - $forum['row_level']) .", category_id=". $pforum['category_id'] ." WHERE forum_id=". $forum['forum_id']);
//				$request['dba']->executeUpdate("UPDATE ". K4MAPS ." SET category_id=". $pforum['category_id'] ." WHERE forum_id=". $forum['forum_id']);
//				$request['dba']->executeUpdate("UPDATE ". K4SUBSCRIPTIONS ." SET category_id=". $pforum['category_id'] ." WHERE forum_id=". $forum['forum_id']);
//				$request['dba']->executeUpdate("UPDATE ". K4BADPOSTREPORTS ." SET category_id=". $pforum['category_id'] ." WHERE forum_id=". $forum['forum_id']);
//				reset_cache('maps');
//			}

			reset_cache('all_forums');

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
		$forums			= $dba->executeQuery("SELECT * FROM ". K4FORUMS ." WHERE parent_id = ". $forum['forum_id']);
		
		/* Deal with this forum and any sub-forums */
		while($forums->next()) {
			$f				= $forums->current();
			
			$dba->executeUpdate("DELETE FROM ". K4MAPS ." WHERE forum_id = ". intval($f['forum_id']));
			$dba->executeUpdate("DELETE FROM ". K4FORUMS ." WHERE forum_id=". intval($f['forum_id']));
			$dba->executeUpdate("DELETE FROM ". K4POSTS ." WHERE forum_id=". intval($f['forum_id']));
			$dba->executeUpdate("DELETE FROM ". K4POSTS ." WHERE forum_id=". intval($f['forum_id']));
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

			if(!isset($_REQUEST['forum_id']) || intval($_REQUEST['forum_id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFORUM'), 'content', FALSE);
				return $action->execute($request);
			}

			$forum					= $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($_REQUEST['forum_id']));

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
			$request['dba']->executeUpdate("DELETE FROM ". K4POSTS ." WHERE forum_id=". intval($forum['forum_id']));
			$request['dba']->executeUpdate("DELETE FROM ". K4POSTS ." WHERE forum_id=". intval($forum['forum_id']));
			
			/* Remove any sub-forums */			
			$this->removeForums($forum, $request['dba']);

			$request['dba']->executeUpdate("DELETE FROM ". K4SUBSCRIPTIONS ." WHERE forum_id = ". intval($forum['forum_id']));
			$request['dba']->executeUpdate("DELETE FROM ". K4MAILQUEUE ." WHERE row_id = ". intval($forum['forum_id']) ." AND row_type = ". FORUM);
						
			$request['dba']->commitTransaction();

			reset_cache('all_forums');
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
			
			$all_maps	= array();

			if(!isset($_REQUEST['forum_id']) || intval($_REQUEST['forum_id']) == 0) {
				//$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFORUM'), 'content', FALSE);
				//return $action->execute($request);
				
				$maps		= $request['dba']->executeQuery("SELECT * FROM ". K4MAPS ." WHERE group_id = 0 AND forum_id = 0 AND varname = 'forum0'");
				
				// get the map's
				get_recursive_maps($request, $all_maps, FALSE, $maps, 1);

				$all_maps = &new FAArrayIterator($all_maps);
			} else {

				$forum					= $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($_REQUEST['forum_id']));
				
				if(!is_array($forum) || empty($forum)) {
					$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFORUM'), 'content', FALSE);
					return $action->execute($request);
				}
				
				foreach($forum as $key => $val)
					$request['template']->setVar('forum_'. $key, $val);

				$maps		= $request['dba']->executeQuery("SELECT * FROM ". K4MAPS ." WHERE group_id = 0 AND forum_id = 0 AND varname = 'forum0'");
				get_recursive_maps($request, $all_maps, FALSE, $maps, 1);
				
				$mask		= $request['dba']->executeQuery("SELECT * FROM ". K4MAPS ." WHERE forum_id = ". intval($forum['forum_id']) ." AND group_id = 0");
				
				$mask_array = array();
				while($mask->next()) {
					$temp = $mask->current();
					$mask_array[$temp['varname']] = $temp;
				}
							
				$maps_array		= array();
				foreach($all_maps as $temp) {
					
					$temp['row_class']		= 'alt2';
					
					if(isset($mask_array[$temp['varname']])) {
						$temp			= $mask_array[$temp['varname']];
						$temp['row_class']	= 'alt1';
					}

					if($temp['varname'] == 'forum0') {
						$temp			= $mask_array['forum'. intval($forum['forum_id'])];
						$temp['row_class']	= 'alt1';
					}
					
					if($temp['row_level']-2 > 0) {
						$temp['level']	= str_repeat('<img src="Images/'. $request['template']->getVar('IMG_DIR') .'/Icons/threaded_bit.gif" alt="" border="0" />', $temp['row_level']-2);
					}
				
					$maps_array[] = $temp;
				}

				$all_maps = &new FAArrayIterator($maps_array);
			}
			
			
			
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
				
				$offset = 0;
				$get_mask_maps = FALSE;

			} else {

				$forum							= $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($_REQUEST['id']));

				if(!is_array($forum) || empty($forum)) {
					$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFORUM'), 'content', FALSE);
					return $action->execute($request);
				}
				
				$get_mask_maps = TRUE;
				$offset = 2;
			}

//			$forum_map						= $request['dba']->getRow("SELECT * FROM ". K4MAPS ." WHERE varname = 'forum". $forum['forum_id'] ."' AND forum_id = ". intval($forum['forum_id']));
//			$forum_maps						= $request['dba']->executeQuery("SELECT * FROM ". K4MAPS ." WHERE forum_id = ". intval($forum['forum_id']));
//			
//			/* Loop through the forum map items */
//			while($forum_maps->next()) {
//				$f							= $forum_maps->current();
//
//				if(isset($_REQUEST[$f['varname'] .'_can_view']) && isset($_REQUEST[$f['varname'] .'_can_add']) && isset($_REQUEST[$f['varname'] .'_can_edit']) && isset($_REQUEST[$f['varname'] .'_can_del'])) {
//					
//					if(($_REQUEST[$f['varname'] .'_can_view'] != $f['can_view']) || ($_REQUEST[$f['varname'] .'_can_add'] != $f['can_add']) || ($_REQUEST[$f['varname'] .'_can_edit'] != $f['can_edit']) || ($_REQUEST[$f['varname'] .'_can_del'] != $f['can_del'])) {
//
//						$update				= $request['dba']->prepareStatement("UPDATE ". K4MAPS ." SET can_view=?,can_add=?,can_edit=?,can_del=? WHERE varname=? AND forum_id=?");
//						$update->setInt(1, $_REQUEST[$f['varname'] .'_can_view']);
//						$update->setInt(2, $_REQUEST[$f['varname'] .'_can_add']);
//						$update->setInt(3, $_REQUEST[$f['varname'] .'_can_edit']);
//						$update->setInt(4, $_REQUEST[$f['varname'] .'_can_del']);
//						$update->setString(5, $f['varname']);
//						$update->setInt(6, $forum['forum_id']);
//
//						$update->executeUpdate();
//
//						unset($update);
//					}
//				}
//			}
			
			$all_maps	= array();
			$maps		= $request['dba']->executeQuery("SELECT * FROM ". K4MAPS ." WHERE group_id = 0 AND forum_id = 0 AND varname = 'forum0'");
			get_recursive_maps($request, $all_maps, FALSE, $maps, 1);
			
			// delete all of the perms because we are going to readd them
			// by doing this, we are guranteed to store the minimum number 
			// of changed permissions
			
			$forum_map_id		= 0;
			
			if($get_mask_maps) {
				$request['dba']->executeUpdate("DELETE FROM ". K4MAPS ." WHERE group_id = 0 AND forum_id = ". intval($forum['forum_id']));
				$insert			= $request['dba']->prepareStatement("INSERT INTO ". K4MAPS ." (row_level,name,varname,category_id,forum_id,user_id,can_view,can_add,can_edit,can_del,value,parent_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
			} else {
				$insert			= $request['dba']->prepareStatement("UPDATE ". K4MAPS ." SET row_level=?,name=?,varname=?,category_id=?,forum_id=?,user_id=?,can_view=?,can_add=?,can_edit=?,can_del=?,value=?,parent_id=? WHERE id=?");
			}
			foreach($all_maps as $temp) {
				
				// make an adjustment for the master forum perm varname
				if($get_mask_maps) {
					if($temp['varname'] == 'forum0') {
						$add				= TRUE;
						$temp['varname']	= 'forum'. intval($forum['forum_id']);
						$temp['name']		= $forum['name'];
					} else {
						$add		= FALSE;
					}
				} else {
					$add = TRUE;
				}
				
				if(!$get_mask_maps)
					$forum = $temp;
				
				if(isset($_REQUEST[$temp['varname'] .'_can_view']) && $_REQUEST[$temp['varname'] .'_can_view'] != $temp['can_view']) $add = TRUE;
				if(isset($_REQUEST[$temp['varname'] .'_can_add']) && $_REQUEST[$temp['varname'] .'_can_add'] != $temp['can_add']) $add = TRUE;
				if(isset($_REQUEST[$temp['varname'] .'_can_edit']) && $_REQUEST[$temp['varname'] .'_can_edit'] != $temp['can_edit']) $add = TRUE;
				if(isset($_REQUEST[$temp['varname'] .'_can_del']) && $_REQUEST[$temp['varname'] .'_can_del'] != $temp['can_del']) $add = TRUE;
				
				if($add) {

					$insert->setInt(1, $temp['row_level']+$offset);
					$insert->setString(2, $temp['name']);
					$insert->setString(3, $temp['varname']);
					$insert->setInt(4, $forum['category_id']);
					$insert->setInt(5, $forum['forum_id']);
					$insert->setInt(6, $temp['user_id']);
					$insert->setInt(7, $_REQUEST[$temp['varname'] .'_can_view']);
					$insert->setInt(8, $_REQUEST[$temp['varname'] .'_can_add']);
					$insert->setInt(9, $_REQUEST[$temp['varname'] .'_can_edit']);
					$insert->setInt(10, $_REQUEST[$temp['varname'] .'_can_del']);
					$insert->setString(11, $temp['value']);
					$insert->setInt(12, $forum_map_id);
					
					if(!$get_mask_maps)
						$insert->setInt(13, $temp['id']);

					$insert->executeUpdate();

					if($temp['varname'] == 'forum'. intval($forum['forum_id'])) {
						$forum_map_id		= $request['dba']->getValue("SELECT * FROM ". K4MAPS ." WHERE varname = 'forum". intval($forum['forum_id']) ."'");
					}
				}
			}
			
			reset_cache('all_forums');
			reset_cache('maps');
			
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