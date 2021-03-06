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
* @version $Id: forums.class.php 158 2005-07-18 02:55:30Z Peter Goodman $
* @package k42
*/



if(!defined('IN_K4')) {
	return;
}

function forum_icon($forum, &$icon) {
	
	global $_MAPS;

	$icon			= 'forum_off';
	
	/* Set the forum Icon */
	if(isset($_COOKIE[K4LASTSEEN]) && isset($_SESSION['user']) && isset($_COOKIE[K4FORUMINFO])) {
		
		$forums		= get_forum_cookies();
		
		$forum['forum_id'] = isset($forum['forum_id']) ? $forum['forum_id'] : 0;

		$time		= isset($forums[$forum['forum_id']]) ? $forums[$forum['forum_id']] : 0;
				
		$icon		= (intval($time) < $forum['post_created']) ? 'forum_on' : 'forum_off';

	} else {
		$icon		= 'forum_on';
	}

	/* Check if this user's perms are less than is needed to post in this forum */
	if($forum['is_link'] == 0) {
		if(isset($_MAPS['forums'][$forum['forum_id']]['topics']['can_add']) && $_MAPS['forums'][$forum['forum_id']]['topics']['can_add'] > $_SESSION['user']->get('perms'))
			$icon			.= '_lock';
	}
	
	$new				= $icon == 'forum_on' ? TRUE : FALSE;

	return $new;
}

class MarkForumsRead extends FAAction {
	function execute(&$request) {
		
		/* Set the Breadcrumbs bit */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_MARKFORUMREAD');
		
		if(isset($_REQUEST['id']) && intval($_REQUEST['id']) > 0) {
			$forums							= $request['dba']->executeQuery("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($_REQUEST['id']));
			
			if($forums->numrows() == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);
				return $action->execute($request);
			}
		} else {
			$forums							= $request['dba']->executeQuery("SELECT * FROM ". K4FORUMS);
		}

		$cookiestr						= '';
		$cookieinfo						= get_forum_cookies();
		
		while($forums->next()) {
			$forum						= $forums->current();

			$cookieinfo[$forum['forum_id']] = time();
		}

		foreach($cookieinfo as $key => $val)
			$cookiestr					.= ','. $key .','. $val;

		setcookie(K4FORUMINFO, trim($cookiestr, ','), time() + 2592000, get_domain());
		
		$action = new K4InformationAction(new K4LanguageElement('L_MARKEDFORUMREAD', $forum['name']), 'content', TRUE, referer(), 3);

		return $action->execute($request);
	}
}
/**
 * Subscribe to a forum
 */
class SubscribeForum extends FAAction {
	function execute(&$request) {
		
		global $_QUERYPARAMS;
		
		if(!$request['user']->isMember()) {
			no_perms_error($request);
			return TRUE;
		}

		if(!isset($_REQUEST['id']) || !$_REQUEST['id'] || intval($_REQUEST['id']) == 0) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INVALIDFORUM');
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);

			return $action->execute($request);
			return TRUE;
		}
		
		/* Get our forum */
		$forum				= $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($_REQUEST['id']));
		
		if(!$forum || !is_array($forum) || empty($forum)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INVALIDFORUM');
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);

			return $action->execute($request);

			return TRUE;
		}

		$is_subscribed		= $request['dba']->getRow("SELECT * FROM ". K4SUBSCRIPTIONS ." WHERE user_id = ". intval($request['user']->get('id')) ." AND forum_id = ". intval($forum['forum_id']) ." AND post_id = 0");
		
		if(is_array($is_subscribed) && !empty($is_subscribed)) {
			k4_bread_crumbs($request['template'], $request['dba'], 'L_SUBSCRIPTION', $forum);
			$action = new K4InformationAction(new K4LanguageElement('L_ALREADYSUBSCRIBED'), 'content', FALSE);

			return $action->execute($request);
			return TRUE;
		}
		
		$subscribe			= $request['dba']->prepareStatement("INSERT INTO ". K4SUBSCRIPTIONS ." (user_id,user_name,forum_id,email,category_id) VALUES (?,?,?,?,?)");
		$subscribe->setInt(1, $request['user']->get('id'));
		$subscribe->setString(2, $request['user']->get('name'));
		$subscribe->setInt(3, $forum['forum_id']);
		$subscribe->setString(4, $request['user']->get('email'));
		$subscribe->setInt(5, $forum['category_id']);
		$subscribe->executeUpdate();

		/* Redirect the user */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_SUBSCRIPTIONS', $forum);
		$action = new K4InformationAction(new K4LanguageElement('L_SUBSCRIBEDFORUM', $forum['name']), 'content', FALSE, 'viewforum.php?f='. $forum['forum_id'], 3);

		return $action->execute($request);
		
		return TRUE;
	}
}

/**
 * Unsubscribe from a forum
 */
class UnsubscribeForum extends FAAction {
	function execute(&$request) {
		
		global $_QUERYPARAMS;
		
		if(!$request['user']->isMember()) {
			no_perms_error($request);
			return TRUE;
		}

		if(!isset($_REQUEST['id']) || !$_REQUEST['id'] || intval($_REQUEST['id']) == 0) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INVALIDFORUM');
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);

			return $action->execute($request);
			return TRUE;
		}

		/* Get our forum */
		$forum				= $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($_REQUEST['id']));
		
		if(!$forum || !is_array($forum) || empty($forum)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INVALIDFORUM');
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);

			return $action->execute($request);

			return TRUE;
		}
		
		$subscribe			= $request['dba']->prepareStatement("DELETE FROM ". K4SUBSCRIPTIONS ." WHERE user_id=? AND post_id=0 AND forum_id=?");
		$subscribe->setInt(1, $request['user']->get('id'));
		$subscribe->setInt(2, $forum['forum_id']);
		$subscribe->executeUpdate();

		/* Redirect the user */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_SUBSCRIPTIONS', $forum);
		$action = new K4InformationAction(new K4LanguageElement('L_UNSUBSCRIBEDFORUM', $forum['name']), 'content', FALSE, referer(), 3); // 'viewforum.php?f='. $forum['forum_id']

		return $action->execute($request);
	}
}

class K4ForumsIterator extends FAProxyIterator {
	
	var $settings;
	var $do_recures;
	var $result;
	var $forums;
	var $usergroups;
	var $dba;
	var $level = 1;

	function K4ForumsIterator(&$dba, $query = NULL, $do_recurse = TRUE, $level = 1) {
		$this->__construct($dba, $query, $do_recurse, $level);
	}
 
	function __construct(&$dba, $query = NULL, $do_recurse = TRUE, $level = 1) {
		
		global $_SETTINGS, $_USERGROUPS;
		
		$this->dba			= &$dba;

		$query				= $query == NULL ? "" : $query;
		
		$this->usergroups	= $_USERGROUPS;
		$this->settings		= $_SETTINGS;
		$this->do_recurse	= $do_recurse;
		$this->level		= $level;
		$this->result		= $this->dba->executeQuery($query);
		
		parent::__construct($this->result);
	}

	function current() {
		$temp	= parent::current();

		/* Cache this forum in the session */
		cache_forum($temp);
		
		/**
		 * Do the icon
		 */
		switch($temp['row_type']) {
			case FORUM: {
				$temp['forum_icon']	= 'forum_off';
				forum_icon($temp, $temp['forum_icon']);
				break;
			}
			case GALLERY: {
				$temp['forum_icon']	= 'forum_gallery';
				break;
			}
			case METAFORUM: {
				$temp['forum_icon']	= 'forum_meta';
				break;
			}
			case ARCHIVEFORUM: {
				$temp['forum_icon']	= 'forum_archive';
				break;
			}
		}

		/* Set a nice representation of what level we're on */
		$temp['level']		= @str_repeat('&nbsp;&nbsp;&nbsp;', $this->level);
						
		/* Should we query down to the next level of forums? */
		if($temp['row_type'] & CATEGORY) {
			$temp['forums'] = &new K4ForumsIterator($this->dba, "SELECT * FROM ". K4FORUMS ." WHERE parent_id = ". $temp['forum_id'] ." ORDER BY row_order ASC", TRUE, $this->level + 1);
		}

		if($this->do_recurse) {
			if($temp['subforums'] > 0 && $this->settings['showsubforums'] == 1) {
				$it = new K4ForumsIterator($this->dba, "SELECT * FROM ". K4FORUMS ." WHERE parent_id = ". intval($temp['forum_id']) ." ORDER BY row_order ASC", FALSE, $this->level + 1);
				if($it->hasNext()) {
					// add the iterator
					$temp['subforums_list'] = $it;
				} else {
					// if this forum doesn't actually have subforums, fix it
					$this->dba->executeUpdate("UPDATE ". K4FORUMS ." SET subforums=0 WHERE forum_id = ". intval($temp['forum_id']));
				}
			}
		}
		
		/**
		 * Get the moderators
		 */
		$temp['moderators']			= array();
		$temp['are_moderators']		= 0;
		
		if($temp['moderating_groups'] != '') {
			
			$groups					= explode('|', $temp['moderating_groups']);
			

			if(is_array($groups)) {
				foreach($groups as $g) {
					if(isset($this->usergroups[$g])) {
						$temp['U_USERGROUPURL'] = K4Url::getUserGroupUrl($g);
						$temp['moderators'][]	= $this->usergroups[$g];
					}
				}
				$temp['are_moderators']		= 1;
			}
		}
		if($temp['moderating_users'] != '') {
			$users					= force_unserialize($temp['moderating_users']);
			if(is_array($users) && !empty($users)) {
				foreach($users as $user_id => $username) {
					$temp['U_GMEMBERURL'] = K4Url::getMemberUrl($user_id);
					$temp['moderators'][]		= array('user_id' => $user_id, 'name' => $username);
				}
			
				$temp['are_moderators']		= 1;
			}
		}

		$temp['moderators']	= &new FAArrayIterator($temp['moderators']);
		
		/* Replace topic/post names with censors */
		replace_censors($temp['topic_name']);
		replace_censors($temp['post_name']);

		$temp['topics']		= number_format($temp['topics']);
		$temp['replies']	= number_format($temp['replies']);
		$temp['posts']		= number_format($temp['posts']);
			
//		/* Set cookies for all of the topics */
//		bb_settopic_cache_item('forums', serialize($this->forums), time() + 3600 * 25 * 5);
		
		$temp['safe_description']	= strip_tags($temp['description']);
		
		$temp['forum']				= $temp['row_type'] == CATEGORY ? 0 : 1;
		
		// custom url's
		$temp['U_FORUMURL'] = K4Url::getForumUrl($temp['forum_id']);
		$temp['U_TOPICURL'] = K4Url::getTopicUrl($temp['post_id']);
		$temp['U_POSTURL'] = K4Url::getPostUrl($temp['post_id']);
		$temp['U_FINDPOSTURL'] = K4Url::getPostUrl($temp['post_id']);
		$temp['U_MEMBERURL'] = K4Url::getMemberUrl($temp['post_uid']);
		$temp['U_REDIRECTURL'] = K4Url::getRedirectUrl($temp['forum_id']);
		
		/* Return the formatted forum info */
		return $temp;
	}
}

class AllForumsIterator extends FAArrayIterator {
	
	var $result;
 
	function __construct($forums) {
		
		$forums		= is_array($forums) ? array_values($forums) : array();

		parent::__construct($forums);
	}

	function current() {
		$temp	= parent::current();
		
		/* Cache this forum in the session */
		cache_forum($temp);
		
		/* Set a nice representation of what level we're on */
		$temp['indent_level']	= @str_repeat('&nbsp;&nbsp;&nbsp;', $temp['row_level']-1);
		
		/* Return the formatted forum info */
		return $temp;
	}
}


?>