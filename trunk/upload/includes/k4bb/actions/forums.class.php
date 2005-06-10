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
* @version $Id: forums.class.php,v 1.10 2005/05/24 20:01:31 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	exit;
}

function forum_icon($instance, $temp) {
	
	$icon		= '';
	$return		= '';
	
	/* Set the forum Icon */
	if(isset($_COOKIE['forums'])) {
		
		//$forums				= $_COOKIE['forums'] != NULL && $_COOKIE['forums'] != '' ? (!unserialize($_COOKIE['forums']) ? array() : unserialize($_COOKIE['forums'])) : array();
		$forums					= array();

		if(isset($forums[$temp['id']])) {
			
			/* Get the value of the forum cookie */
			$cookie_val		= $forums[$temp['id']];
			
			/* If there are threads stored in this forum or not */
			if(is_array($cookie_val) && !empty($cookie_val)) {
				$icon		= 'on';
			} else {
				$icon		= 'off';
			}
		} else {
			
			if(strftime("%m%d%y", $temp['topic_created']) == strftime("%m%d%y", bbtime()) && $temp['topic_uid'] != $instance->user['id']) {
				
				$icon		= 'on';
			} else {
				$icon		= 'off';
			}

			$forums[$temp['id']]	= array();
			$forums					= serialize($forums);
			
			$return					= $temp['id'];
		}
	} else {
		
		/* If the last thread post time is equal to today */
		if(strftime("%m%d%y", $temp['topic_created']) == strftime("%m%d%y", bbtime()) ) {
			$icon		= 'on';
		} else {
			$icon		= 'off';
		}
		
		$forums		= array($temp['id'] => $temp);
		$forums		= serialize($forums);

		$return		= $temp['id'];
	}

	

	/* Check if this user's perms are less than is needed to post in this forum */

	if(@$instance->user['maps']['forums'][$temp['id']]['can_add'] > $instance->user['perms'])
		$icon			.= '_lock';
	
	/* Return the icon text to add to the IMG tag */
	return array($icon, $return);
}

class MarkForumsRead extends FAAction {
	function execute(&$request) {
		
		/* Set the Breadcrumbs bit */
		k4_bread_crumbs(&$request['template'], $request['dba'], 'L_MARKFORUMSREAD');
		
		$forums		= array();

		if(isset($_REQUEST['forums']) && is_array($_REQUEST['forums'])) {
			foreach($_REQUEST['forums'] as $forum) {
				$forums[$forum['id']] = array();
			}
		}
		
		/* Serialize the array */
		$forums			= serialize($forums);

		/* Cache some info to set a cookie on the next refresh */
		bb_setcookie_cache('forums', $forums, time() + ini_get('session.gc_maxlifetime'));

		$action = new K4InformationAction(new K4LanguageElement('L_MARKEDFORUMSREAD'), 'content', TRUE, 'index.php', 3);


		return $action->execute($request);

		return TRUE;
	}
}
/**
 * Subscribe to a forum
 */
class SubscribeForum extends FAAction {
	function execute(&$request) {
		
		global $_QUERYPARAMS;
		
		if(!$request['user']->isMember()) {
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
			$request['template']->setFile('content', 'login_form.html');
			$request['template']->setVisibility('no_perms', TRUE);
			return TRUE;
		}

		if(!isset($_REQUEST['id']) || !$_REQUEST['id'] || intval($_REQUEST['id']) == 0) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INVALIDFORUM');
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);

			return $action->execute($request);
			return TRUE;
		}
		
		/* Get our forum */
		$forum				= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['forum'] ." FROM ". K4FORUMS ." f LEFT JOIN ". K4INFO ." i ON f.forum_id = i.id WHERE i.id = ". intval($_REQUEST['id']));
		
		if(!$forum || !is_array($forum) || empty($forum)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INVALIDFORUM');
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);

			return $action->execute($request);

			return TRUE;
		}

		$is_subscribed		= $request['dba']->getRow("SELECT * FROM ". K4SUBSCRIPTIONS ." WHERE user_id = ". intval($request['user']->get('id')) ." AND forum_id = ". intval($forum['id']) ." AND topic_id = 0");
		
		if(is_array($is_subscribed) && !empty($is_subscribed)) {
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_SUBSCRIPTION', $forum);
			$action = new K4InformationAction(new K4LanguageElement('L_ALREADYSUBSCRIBED'), 'content', FALSE);

			return $action->execute($request);
			return TRUE;
		}
		
		$subscribe			= &$request['dba']->prepareStatement("INSERT INTO ". K4SUBSCRIPTIONS ." (user_id,user_name,forum_id,email,category_id) VALUES (?,?,?,?,?)");
		$subscribe->setInt(1, $request['user']->get('id'));
		$subscribe->setString(2, $request['user']->get('name'));
		$subscribe->setInt(3, $forum['id']);
		$subscribe->setString(4, $request['user']->get('email'));
		$subscribe->setInt(5, $forum['category_id']);
		$subscribe->executeUpdate();

		/* Redirect the user */
		k4_bread_crumbs(&$request['template'], $request['dba'], 'L_SUBSCRIPTIONS', $forum);
		$action = new K4InformationAction(new K4LanguageElement('L_SUBSCRIBEDFORUM', $forum['name']), 'content', FALSE, 'viewforum.php?id='. $forum['id'], 3);

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
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
			$request['template']->setFile('content', 'login_form.html');
			$request['template']->setVisibility('no_perms', TRUE);
			return TRUE;
		}

		if(!isset($_REQUEST['id']) || !$_REQUEST['id'] || intval($_REQUEST['id']) == 0) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INVALIDFORUM');
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);

			return $action->execute($request);
			return TRUE;
		}

		/* Get our forum */
		$forum				= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['forum'] ." FROM ". K4FORUMS ." f LEFT JOIN ". K4INFO ." i ON f.forum_id = i.id WHERE i.id = ". intval($_REQUEST['id']));
		
		if(!$topic || !is_array($topic) || empty($topic)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INVALIDFORUM');
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);

			return $action->execute($request);

			return TRUE;
		}
		
		$subscribe			= &$request['dba']->prepareStatement("DELETE FROM ". K4SUBSCRIPTIONS ." WHERE user_id=? AND topic_id=0 AND forum_id=?");
		$subscribe->setInt(1, $request['user']->get('id'));
		$subscribe->setInt(2, $forum['id']);
		$subscribe->executeUpdate();

		/* Redirect the user */
		k4_bread_crumbs(&$request['template'], $request['dba'], 'L_SUBSCRIPTIONS', $forum);
		$action = new K4InformationAction(new K4LanguageElement('L_UNSUBSCRIBEDFORUM', $forum['name']), 'content', FALSE, 'viewforum.php?id='. $forum['id'], 3);

		return $action->execute($request);
		
		return TRUE;
	}
}

class K4ForumsIterator extends FAProxyIterator {
	
	var $settings;
	var $do_recures;
	var $user;
	var $result;
	var $forums;
	var $usergroups;
	var $dba;
 
	function __construct(&$dba, $query = NULL, $do_recurse = TRUE) {
		
		global $_SETTINGS, $_QUERYPARAMS, $_USERGROUPS;
		
		$this->dba			= &$dba;

		$query				= $query == NULL ? "" : $query;
		
		$this->usergroups	= $_USERGROUPS;
		$this->user			= &Globals::getGlobal('user');
		$this->settings		= $_SETTINGS;
		$this->query_params	= $_QUERYPARAMS;
		$this->do_recurse	= $do_recurse;
		$this->result		= &$this->dba->executeQuery($query);

		//$this->forums		= isset($_COOKIE['forums']) && $_COOKIE['forums'] != NULL && $_COOKIE['forums'] != '' ? iif(!unserialize($_COOKIE['forums']), array(), unserialize($_COOKIE['forums'])) : array();

		parent::__construct($this->result);
	}

	function &current() {
		$temp	= parent::current();
		
		/* Cache this forum in the session */
		cache_forum($temp);

		/* Set the forum's icon */
		$return				= forum_icon($this, $temp);
		
		$temp['forum_icon']	= $return[0];
		
		/* Set a default cookie with the unread topic id in it */
		//if(ctype_digit($return[1])) {
		//	$this->forums[$temp['id']][$return[1]] = TRUE;
		//}

		/* Set a nice representation of what level we're on */
		$temp['level']		= @str_repeat('&nbsp;&nbsp;&nbsp;', $temp['row_level']-2);
						
		/* Should we query down to the next level of forums? */
		if($this->do_recurse) {
			$query_params = $this->query_params['info'] . $this->query_params['forum'];
			
			if($temp['subforums'] > 0 && $this->settings['showsubforums'] == 1) {
				$temp['subforums'] = &new ForumsIterator($this->dba, "SELECT $query_params FROM ". K4INFO ." i LEFT JOIN ". K4FORUMS ." f ON f.forum_id = i.id WHERE i.row_left > ". $temp['row_left'] ." AND i.row_right < ". $temp['row_right'] ." AND i.row_type = ". FORUM ." AND i.parent_id = ". $temp['id'] ." ORDER BY i.row_order ASC", FALSE);
			}
		}

		if($temp['moderating_groups'] != '') {
			
			$groups					= !unserialize($temp['moderating_groups']) ? array() : unserialize($temp['moderating_groups']);
			$temp['moderators']		= array();

			if(is_array($groups)) {
				foreach($groups as $g) {
					if(isset($this->usergroups[$g])) {
						$temp['moderators'][]	= $this->usergroups[$g];
					}
				}

				$temp['moderators']	= &new FAArrayIterator($temp['moderators']);
			} else {
				$temp['moderating_groups'] = '';
			}
		}
			
		/* Set cookies for all of the topics */
		//bb_settopic_cache_item('forums', serialize($this->forums), time() + 3600 * 25 * 5);

		/* Should we free the result? */
		if(!$this->hasNext())
			$this->result->free();
		
		/* Return the formatted forum info */
		return $temp;
	}
}

class AllForumsIterator extends FAArrayIterator {
	
	var $result;
 
	function __construct($forums) {

		parent::__construct($forums);
	}

	function &current() {
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