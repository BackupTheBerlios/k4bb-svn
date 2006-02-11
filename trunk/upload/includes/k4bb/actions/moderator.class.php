<?php
/**
* k4 Bulletin Board, moderator.class.php
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
* @version $Id: moderator.class.php 154 2005-07-15 02:56:28Z Peter Goodman $
* @package k42
*/



if(!defined('IN_K4')) {
	return;
}

class ModerateForum extends FAAction {
	function execute(&$request) {
		
		global $_QUERYPARAMS, $_DATASTORE;
		
		/* set the breadcrumbs bit */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');

		/* Check the request ID */
		if(!isset($_REQUEST['id']) || !$_REQUEST['id'] || intval($_REQUEST['id']) == 0) {
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}
			
		$forum				= $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($_REQUEST['id']));
		
		/* Check the forum data given */
		if(!$forum || !is_array($forum) || empty($forum)) {
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}
			
		/* Make sure the we are trying to post into a forum */
		if(!($forum['row_type'] & FORUM)) {
			$action = new K4InformationAction(new K4LanguageElement('L_CANTMODNONFORUM'), 'content', FALSE);
			return $action->execute($request);
		}

		/**
		 * Check for moderating permission
		 */
		
		if(!is_moderator($request['user']->getInfoArray(), $forum)) {
			no_perms_error($request);
			return TRUE;
		}
		
		if(!isset($_REQUEST['action']) || $_REQUEST['action'] == '') {
			$action = new K4InformationAction(new K4LanguageElement('L_NEEDSELECTACTION'), 'content', TRUE);
			return $action->execute($request);
		}

		if(!isset($_REQUEST['topics']) || $_REQUEST['topics'] == '') {
			$action = new K4InformationAction(new K4LanguageElement('L_NEESSELECTTOPICS'), 'content', TRUE);
			return $action->execute($request);
		}

		$topics		= explode("|", $_REQUEST['topics']);

		if(!is_array($topics) || count($topics) == 0) {
			$action = new K4InformationAction(new K4LanguageElement('L_NEESSELECTTOPICS'), 'content', TRUE);
			return $action->execute($request);
		}

		$query_extra	= '';
		$i				= 0;
		foreach($topics as $id) {
			
			$query_extra .= $i == 0 ? ' ' : ' OR ';
			$query_extra .= 'post_id = '. intval($id);
			
			$query_reply_extra .= $i == 0 ? ' ' : ' OR ';
			$query_reply_extra .= 'parent_id = '. intval($id);
			
			$i++;
		}

		$request['template']->setVisibility('check_checkboxes', TRUE);

		switch($_REQUEST['action']) {

			/**
			 * Lock topics
			 */
			case 'lock': {
				
				if($request['user']->get('perms') < get_map( 'closed', 'can_add', array('forum_id' => $forum['forum_id']))) {
					no_perms_error($request);
					return TRUE;
				}

				$request['dba']->executeUpdate("UPDATE ". K4POSTS ." SET post_locked = 1 WHERE (". $query_extra .")");
				
				k4_bread_crumbs($request['template'], $request['dba'], 'L_LOCKTOPICS', $forum);
				$action = new K4InformationAction(new K4LanguageElement('L_LOCKEDTOPICS'), 'content', TRUE, referer(), 3);

				return $action->execute($request);

				break;
			}

			/**
			 * Stick topics
			 */
			case 'stick': {
				
				if($request['user']->get('perms') < get_map( 'sticky', 'can_add', array('forum_id' => $forum['forum_id']))) {
					no_perms_error($request);
					return TRUE;
				}

				$request['dba']->executeUpdate("UPDATE ". K4POSTS ." SET post_type = ". TOPIC_STICKY .", post_expire = 0 WHERE (". $query_extra .")");
				
				k4_bread_crumbs($request['template'], $request['dba'], 'L_STICKTOPICS', $forum);
				$action = new K4InformationAction(new K4LanguageElement('L_STUCKTOPICS'), 'content', TRUE, referer(), 3);

				return $action->execute($request);
				
				break;
			}

			/**
			 * Announce topics
			 */
			case 'announce': {
				
				if($request['user']->get('perms') < get_map( 'announce', 'can_add', array('forum_id' => $forum['forum_id']))) {
					no_perms_error($request);
					return TRUE;
				}

				$request['dba']->executeUpdate("UPDATE ". K4POSTS ." SET post_type = ". TOPIC_ANNOUNCE .", post_expire = 0 WHERE (". $query_extra .")");
				
				k4_bread_crumbs($request['template'], $request['dba'], 'L_ANNOUNCETOPICS', $forum);
				$action = new K4InformationAction(new K4LanguageElement('L_ANNOUNCEDTOPICS'), 'content', TRUE, referer(), 3);

				return $action->execute($request);
				
				break;
			}

			/**
			 * Feature topics
			 */
			case 'feature': {
				
				if($request['user']->get('perms') < get_map( 'feature', 'can_add', array('forum_id' => $forum['forum_id']))) {
					no_perms_error($request);
					return TRUE;
				}

				$request['dba']->executeUpdate("UPDATE ". K4POSTS ." SET is_feature = 1, post_expire = 0 WHERE (". $query_extra .")");
				
				k4_bread_crumbs($request['template'], $request['dba'], 'L_FEATURETOPICS', $forum);
				$action = new K4InformationAction(new K4LanguageElement('L_FEATUREDTOPICS'), 'content', TRUE, referer(), 3);

				return $action->execute($request);

				break;
			}

			/**
			 * Remove any special formatting on topics
			 */
			case 'normal': {
				
				if($request['user']->get('perms') < get_map( 'normalize', 'can_add', array('forum_id' => $forum['forum_id']))) {
					no_perms_error($request);
					return TRUE;
				}

				$request['dba']->executeUpdate("UPDATE ". K4POSTS ." SET is_feature = 0, display = 1, queue = 0, post_type = ". TOPIC_NORMAL .", post_expire = 0, post_locked = 0 WHERE (". $query_extra .")");
				
				k4_bread_crumbs($request['template'], $request['dba'], 'L_SETASNORMALTOPICS', $forum);
				$action = new K4InformationAction(new K4LanguageElement('L_NORMALIZEDTOPICS'), 'content', TRUE, referer(), 3);

				return $action->execute($request);

				break;
			}

			/**
			 * Insert the topics into the moderator's queue for checking
			 */
			case 'queue': {

				if($request['user']->get('perms') < get_map( 'queue', 'can_add', array('forum_id' => $forum['forum_id']))) {
					no_perms_error($request);
					return TRUE;
				}

				$request['dba']->executeUpdate("UPDATE ". K4POSTS ." SET queue = 1 WHERE (". $query_extra .")");
				
				k4_bread_crumbs($request['template'], $request['dba'], 'L_QUEUETOPICS', $forum);
				$action = new K4InformationAction(new K4LanguageElement('L_QUEUEDTOPICS'), 'content', TRUE, referer(), 3);

				return $action->execute($request);

				break;
			}

			/**
			 * Subscribe to all of the selected topics
			 */
			case 'subscribe': {
				foreach($topics as $post_id) {
					$is_subscribed		= $request['dba']->getRow("SELECT * FROM ". K4SUBSCRIPTIONS ." WHERE user_id = ". intval($request['user']->get('id')) ." AND post_id = ". intval($post_id));
					if(!is_array($is_subscribed) || empty($is_subscribed)) {
						$subscribe			= $request['dba']->prepareStatement("INSERT INTO ". K4SUBSCRIPTIONS ." (user_id,user_name,post_id,forum_id,email,category_id) VALUES (?,?,?,?,?,?)");
						$subscribe->setInt(1, $request['user']->get('id'));
						$subscribe->setString(2, $request['user']->get('name'));
						$subscribe->setInt(3, $post_id);
						$subscribe->setInt(4, $forum['forum_id']);
						$subscribe->setString(5, $request['user']->get('email'));
						$subscribe->setInt(6, $forum['category_id']);
						$subscribe->executeUpdate();
					}
				}

				k4_bread_crumbs($request['template'], $request['dba'], 'L_SUBSCRIPTION', $forum);
				$action = new K4InformationAction(new K4LanguageElement('L_SUBSCRIBEDTOPICS'), 'content', TRUE, referer(), 3);

				return $action->execute($request);

				break;
			}

			/**
			 * Add selected topics to the queue to be deleted
			 */
			case 'delete': {
				
				if($request['user']->get('perms') < get_map( 'delete', 'can_add', array('forum_id' => $forum['forum_id']))) {
					no_perms_error($request);
					return TRUE;
				}
				
				$users			= array();

				// find the users for topics first
				$t				= $request['dba']->executeQuery("SELECT * FROM ". K4POSTS ." WHERE row_type=". TOPIC ." AND ($query_extra)");
				while($t->next()) {
					$temp		= $t->current();
					$users[$temp['poster_id']] = isset($users[$temp['poster_id']]) ? $users[$temp['poster_id']] + 1 : 1;
					
					// remove ratings
					if($temp['rating'] > 0) {
						$request['dba']->executeUpdate("DELETE FROM ". K4RATINGS ." WHERE post_id = ". intval($temp['post_id']));
					}

					// remove attachments
					if($temp['attachments'] > 0) {
						remove_attachments($request, $temp, FALSE);
					}

					// remove bad post reports
					$request['dba']->executeUpdate("DELETE FROM ". K4BADPOSTREPORTS ." WHERE post_id = ". intval($temp['post_id']) );
				}

				$num_topics		= $t->numrows();
				$t->free();
				
				// find them for replies
				$r				= $request['dba']->executeQuery("SELECT * FROM ". K4POSTS ." WHERE row_type=". REPLY ." AND ($query_reply_extra)");
				while($r->next()) {
					$temp		= $r->current();
					$users[$temp['poster_id']] = isset($users[$temp['poster_id']]) ? $users[$temp['poster_id']] + 1 : 1;
					
					// remove attachments
					if($temp['attachments'] > 0) {
						remove_attachments($request, $temp, FALSE);
					}
					
					// remove bad post reports
					$request['dba']->executeUpdate("DELETE FROM ". K4BADPOSTREPORTS ." WHERE post_id = ". intval($temp['post_id']) );
				}

				$num_replies	= $r->numrows();
				$r->free();

				// loop through the users and change their post counts
				foreach($users as $id => $postcount) {
					$request['dba']->executeUpdate("UPDATE ". K4USERINFO ." SET num_posts = num_posts-{$postcount} WHERE user_id = {$id}");
				}

				// Remove everything
				$request['dba']->executeUpdate("DELETE FROM ". K4POSTS ." WHERE row_type=". TOPIC ." AND (". $query_extra .")");
				$request['dba']->executeUpdate("DELETE FROM ". K4POSTS ." WHERE row_type=". REPLY ." AND (". $query_reply_extra .")");
				
				/* Get that last post in this forum that's not part of/from one of these topics */
				$no_post			= array('created'=>0, 'name'=>'', 'poster_name'=>'', 'post_id'=>0, 'poster_id'=>0, 'posticon'=>'',);
				$lastpost_created	= $request['dba']->getRow("SELECT * FROM ". K4POSTS ." WHERE (". str_replace('=', '<>', $query_extra) .") AND forum_id=". intval($forum['forum_id']) ." ORDER BY created DESC LIMIT 1");
				$lastpost_created	= !$lastpost_created || !is_array($lastpost_created) || empty($lastpost_created) ? $no_post : $lastpost_created;


				/**
				 * Update the forum and the datastore
				 */
							
				$forum_update		= $request['dba']->prepareStatement("UPDATE ". K4FORUMS ." SET topics=topics-?,posts=posts-?,replies=replies-?,post_id=?,post_created=?,post_name=?,post_uname=?,post_uid=?,post_posticon=? WHERE forum_id=?");
				$datastore_update	= $request['dba']->prepareStatement("UPDATE ". K4DATASTORE ." SET data=? WHERE varname=?");
					
				/* Set the forum values */
				$forum_update->setInt(1, $num_topics);
				$forum_update->setInt(2, $num_replies + $num_topics);
				$forum_update->setInt(3, $num_replies);
				$forum_update->setInt(4, $lastpost_created['post_id']);
				$forum_update->setInt(5, $lastpost_created['created']);
				$forum_update->setString(6, $lastpost_created['name']);
				$forum_update->setString(7, $lastpost_created['poster_name']);
				$forum_update->setInt(8, $lastpost_created['poster_id']);
				$forum_update->setString(9, $lastpost_created['posticon']);
				$forum_update->setInt(10, $forum['forum_id']);
				
				/* Set the datastore values */
				$datastore					= $_DATASTORE['forumstats'];
				$datastore['num_topics']	= $request['dba']->getValue("SELECT COUNT(*) FROM ". K4POSTS ." WHERE is_draft = 0 AND queue = 0 AND display = 1 AND row_type=". TOPIC);
				$datastore['num_replies']	= $request['dba']->getValue("SELECT COUNT(*) FROM ". K4POSTS ." WHERE row_type=". REPLY);
				
				$datastore_update->setString(1, serialize($datastore));
				$datastore_update->setString(2, 'forumstats');
				
				/* Execute the forum and datastore update queries */
				$forum_update->executeUpdate();
				$datastore_update->executeUpdate();
				
				reset_cache('datastore');

				k4_bread_crumbs($request['template'], $request['dba'], 'L_DELETETOPICS', $forum);
				$action = new K4InformationAction(new K4LanguageElement('L_DELETEDTOPICS'), 'content', TRUE, referer(), 5);

				return $action->execute($request);

				break;
			}

			/**
			 * Move/copy topics to a destination forum
			 */
			case 'move': {
				
				if($request['user']->get('perms') < get_map( 'move', 'can_add', array('forum_id' => $forum['forum_id']))) {
					no_perms_error($request);
					return TRUE;
				}

				if(count($topics) <= 0) {
					k4_bread_crumbs($request['template'], $request['dba'], 'L_MOVETOPICS', $forum);
					$action = new K4InformationAction(new K4LanguageElement('L_NEEDSELECTTOPIC'), 'content', FALSE);

					return $action->execute($request);
				}

				/* Get the topics */
				$result				= $request['dba']->executeQuery("SELECT * FROM ". K4POSTS ." WHERE row_type=". TOPIC ." AND is_draft=0 AND queue = 0 AND display = 1 AND forum_id = ". intval($forum['forum_id']) ." AND (". $query_extra .") ORDER BY created DESC");
				
				/* Apply the topics iterator */
				$it					= &new TopicsIterator($request['dba'], $request['user'], $result, $request['template']->getVar('IMG_DIR'), $forum);
				$request['template']->setList('topics', $it);
				
				$request['template']->setVar('topics', $_REQUEST['topics']);
				$request['template']->setVar('forum_id', $forum['forum_id']);

				$request['template']->setVar('modpanel', 1);

				k4_bread_crumbs($request['template'], $request['dba'], 'L_MOVETOPICS', $forum);
				$request['template']->setFile('content', 'move_topics.html');

				break;
			}

			/* Invalid action has been taken */
			default: {
				
				k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
				$action = new K4InformationAction(new K4LanguageElement('L_NEEDSELECTACTION'), 'content', FALSE);

				return $action->execute($request);
				break;
			}
		}

		return TRUE;

	}
}

class MoveTopics extends FAAction {
	function execute(&$request) {
		
		/* set the breadcrumbs bit */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');

		/* Check the request ID */
		if(!isset($_REQUEST['id']) || !$_REQUEST['id'] || intval($_REQUEST['id']) == 0) {
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}
		
		/* Check the other request ID */
		if(!isset($_REQUEST['forum']) || !$_REQUEST['forum'] || intval($_REQUEST['forum']) == 0) {
			$action = new K4InformationAction(new K4LanguageElement('L_DESTFORUMDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}
			
		$forum				= $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($_REQUEST['id']));
		$destination		= $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($_REQUEST['forum']));

		/* Check the forum data given */
		if(!$forum || !is_array($forum) || empty($forum)) {
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}
			
		/* Make sure the we are trying to post into a forum */
		if(!($forum['row_type'] & FORUM)) {
			$action = new K4InformationAction(new K4LanguageElement('L_CANTMODNONFORUM'), 'content', FALSE);
			return $action->execute($request);
		}

		/* Check the forum data given */
		if(!$destination || !is_array($destination) || empty($destination)) {
			$action = new K4InformationAction(new K4LanguageElement('L_DESTFORUMDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}
			
		/* Make sure the we are trying to post into a forum */
		if(!($destination['row_type'] & FORUM)) {
			$action = new K4InformationAction(new K4LanguageElement('L_CANTMODNONFORUM'), 'content', FALSE);
			return $action->execute($request);
		}

		/**
		 * Check for moderating permission
		 */
		
		if(!is_moderator($request['user']->getInfoArray(), $forum)) {
			no_perms_error($request);
			return TRUE;
		}

		if(!is_moderator($request['user']->getInfoArray(), $destination)) {
			no_perms_error($request);
			return TRUE;
		}

		if($request['user']->get('perms') < get_map( 'move', 'can_add', array('forum_id' => $forum['forum_id']))) {
			no_perms_error($request);
			return TRUE;
		}

		if($request['user']->get('perms') < get_map( 'move', 'can_add', array('forum_id' => $destination['forum_id']))) {
			no_perms_error($request);
			return TRUE;
		}
		
		if(!isset($_REQUEST['action']) || $_REQUEST['action'] == '') {
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_NEEDSELECTACTION'), 'content', FALSE);
			return $action->execute($request);
		}

		if(!isset($_REQUEST['topics']) || $_REQUEST['topics'] == '') {
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_NEESSELECTTOPICS'), 'content', FALSE);
			return $action->execute($request);
		}

		$topics			= explode("|", trim($_REQUEST['topics'], '|'));
		$query_extra	= '';
		$i				= 0;

		foreach($topics as $id) {
			$query_extra .= $i == 0 ? ' ' : ' OR ';
			$query_extra .= 'post_id = '. intval($id);
			
			$query_reply_extra .= $i == 0 ? ' ' : ' OR ';
			$query_reply_extra .= 'parent_id = '. intval($id);

			$i++;
		}

		if(!is_array($topics) || count($topics) == 0) {
			$action = new K4InformationAction(new K4LanguageElement('L_NEESSELECTTOPICS'), 'content', FALSE);
			return $action->execute($request);
		}
		
		$no_post			= array('created'=>0, 'name'=>'', 'poster_name'=>'', 'post_id'=>0, 'poster_id'=>0, 'posticon'=>'',);

		switch($_REQUEST['action']) {
			case 'move': {
				
				// move the topics and replies
				$request['dba']->executeUpdate("UPDATE ". K4POSTS ." SET forum_id = ". intval($destination['forum_id']) .", moved=1 WHERE row_type=". TOPIC ." AND ($query_extra)");
				$request['dba']->executeUpdate("UPDATE ". K4POSTS ." SET forum_id = ". intval($destination['forum_id']) ." WHERE row_type=". REPLY ." AND ($query_reply_extra)");
				
				$num_topics			= count($topics);
				
				// find the number of replies
				$num_replies		= $request['dba']->getValue("SELECT COUNT(*) FROM ". K4POSTS ." WHERE row_type=". REPLY ." AND ($query_reply_extra)");
				
				// get the last post in our initial forum
				$lastpost_created	= $request['dba']->getRow("SELECT * FROM ". K4POSTS ." WHERE forum_id=". intval($forum['forum_id']) ." ORDER BY created DESC LIMIT 1");
				$lastpost_created	= !$lastpost_created || !is_array($lastpost_created) || empty($lastpost_created) ? $no_post : $lastpost_created;
				
				// Update this forum
				$forum_update		= $request['dba']->prepareStatement("UPDATE ". K4FORUMS ." SET topics=?,posts=?,replies=?,post_created=?,post_name=?,post_uname=?,post_id=?,post_uid=?,post_posticon=? WHERE forum_id=?");
				
				$num_topics			= $request['dba']->getValue("SELECT COUNT(*) FROM ". K4POSTS ." WHERE row_type=". TOPIC ." AND queue=0 AND is_draft=0 AND display=1 AND forum_id=". intval($forum['forum_id']));
				$num_replies		= $request['dba']->getValue("SELECT COUNT(*) FROM ". K4POSTS ." WHERE row_type=". REPLY ." AND forum_id=". intval($forum['forum_id']));	
				
				/* Set the forum values */
				$forum_update->setInt(1, $num_topics);
				$forum_update->setInt(2, intval($num_replies + $num_topics));
				$forum_update->setInt(3, $num_replies);
				$forum_update->setInt(4, $lastpost_created['created']);
				$forum_update->setString(5, $lastpost_created['name']);
				$forum_update->setString(6, $lastpost_created['poster_name']);
				$forum_update->setInt(7, $lastpost_created['post_id']);
				$forum_update->setInt(8, $lastpost_created['poster_id']);
				$forum_update->setString(9, $lastpost_created['posticon']);
				$forum_update->setInt(10, $forum['forum_id']);
				$forum_update->executeUpdate();
				
				// get the last post in our destination forum
				$lastpost_created	= $request['dba']->getRow("SELECT * FROM ". K4POSTS ." WHERE forum_id=". intval($destination['forum_id']) ." ORDER BY created DESC LIMIT 1");
				$lastpost_created	= !$lastpost_created || !is_array($lastpost_created) || empty($lastpost_created) ? $no_post : $lastpost_created;
				
				$num_topics			= $request['dba']->getValue("SELECT COUNT(*) FROM ". K4POSTS ." WHERE row_type=". TOPIC ." AND queue=0 AND is_draft=0 AND display=1 AND forum_id = ". intval($destination['forum_id']));
				$num_replies		= $request['dba']->getValue("SELECT COUNT(*) FROM ". K4POSTS ." WHERE row_type=". REPLY ." AND forum_id = ". intval($destination['forum_id']));

				/* Set the forum values, using the same prepared statement as above */
				$forum_update->setInt(1, $num_topics);
				$forum_update->setInt(2, intval($num_replies + $num_topics));
				$forum_update->setInt(3, $num_replies);
				$forum_update->setInt(4, $lastpost_created['created']);
				$forum_update->setString(5, $lastpost_created['name']);
				$forum_update->setString(6, $lastpost_created['poster_name']);
				$forum_update->setInt(7, $lastpost_created['post_id']);
				$forum_update->setInt(8, $lastpost_created['poster_id']);
				$forum_update->setString(9, $lastpost_created['posticon']);
				$forum_update->setInt(10, $destination['forum_id']);
				$forum_update->executeUpdate();
				
				unset($last_topic, $lastpost_created, $forum_update);

				// we're done	
				k4_bread_crumbs($request['template'], $request['dba'], 'L_MOVECOPYTOPICS', $forum);
				$action = new K4InformationAction(new K4LanguageElement('L_MOVEDTOPICS', $forum['name'], $destination['name']), 'content', FALSE, 'viewforum.php?f='. $destination['forum_id'], 3);
				return $action->execute($request);
				
				break;
			}

			/**
			 * Copy & Track share the same stuff, copy passes on to track
			 */
			case 'copy': {
				$track		= FALSE;
			}
			case 'movetrack': {
				
				$track		= !isset($track) ? TRUE : FALSE;

				$select		= "parent_id, forum_id, name, is_draft, is_feature, moved, queue, display, edited_time, edited_username, edited_userid, ratings_sum, ratings_num, rating, disable_html, disable_bbcode, disable_emoticons, disable_sig, disable_areply, disable_aurls, description, body_text, posticon, post_type, post_expire, post_locked, poster_name, poster_id, poster_ip, lastpost_created, lastpost_uname, lastpost_id, lastpost_uid, is_poll, num_replies, views, last_viewed, attachments, row_level, row_type, created, moved_old_post_id, moved_new_post_id";
				
				// moved_old_post_id=post_id is for post tracking

				/**
				 * Create a temporary table and copy all of the topics into it
				 */
				if(is_a($request['dba'], 'SQLiteConnection') || is_a($request['dba'], 'MySQLConnection')) {
					$request['dba']->createTemporary(K4TEMPTABLE, K4POSTS);
					$request['dba']->executeUpdate("INSERT INTO ". K4TEMPTABLE ." (post_id,$select) SELECT post_id,$select FROM ". K4POSTS ." WHERE row_type=". TOPIC ." AND ($query_extra)");
				} elseif(is_a($request['dba'], 'MySQLiConnection')) {
					$request['dba']->executeUpdate("CREATE TEMPORARY TABLE ". K4TEMPTABLE ." (SELECT post_id,$select FROM ". K4POSTS ." WHERE row_type=". TOPIC ." AND ($query_extra))");
				}
				
				$request['dba']->executeUpdate("UPDATE ". K4TEMPTABLE ." SET moved_old_post_id=post_id, forum_id = ". $destination['forum_id']);

				/**
				 * Copy all of the topics back into the topics table 
				 */
				$request['dba']->executeUpdate("INSERT INTO ". K4POSTS ." ($select) SELECT $select FROM ". K4TEMPTABLE);
				
				// remove all gloat info from the 'tracker' topics
				if($track)
					$request['dba']->executeUpdate("UPDATE ". K4POSTS ." SET body_text='',edited_time=0,edited_username='',edited_userid=0 WHERE ($query_extra)");
				
				/**
				 * Create a temporary table for the replies
				 */
				//$select	= "parent_id, post_id, forum_id, category_id, name, body_text, poster_name, poster_id, poster_ip, edited_time, edited_username, edited_userid, disable_html, disable_bbcode, disable_emoticons, disable_sig, disable_areply, disable_aurls, num_replies, is_poll, posticon, row_level, row_type, created, moved_old_post_id";
				@$request['dba']->executeUpdate("DROP TABLE ". K4TEMPTABLE);
				
				if(is_a($request['dba'], 'SQLiteConnection') || is_a($request['dba'], 'MySQLConnection')) {
					$request['dba']->createTemporary(K4TEMPTABLE, K4POSTS);
					$request['dba']->executeUpdate("INSERT INTO ". K4TEMPTABLE ." ($select) SELECT $select FROM ". K4POSTS ." WHERE row_type=". REPLY ." AND ($query_reply_extra)");
				} elseif(is_a($request['dba'], 'MySQLiConnection')) {
					$request['dba']->executeUpdate("CREATE TEMPORARY TABLE ". K4TEMPTABLE ." (SELECT $select FROM ". K4POSTS ." WHERE row_type=". REPLY ." AND ($query_reply_extra))");
				}
				$request['dba']->executeUpdate("UPDATE ". K4TEMPTABLE ." SET forum_id = ". $destination['forum_id']);

				/**
				 * Get all of our new topics and update the reply topic ids
				 */
				$new_post_ids		= '';
				$topics				= $request['dba']->executeQuery("SELECT * FROM ". K4POSTS ." WHERE (". str_replace('post_id', 'moved_old_post_id', $query_extra) .")");
				
				$num_topics			= $topics->numrows();
				$num_replies		= 0;
				while($topics->next()) {
					$topic			= $topics->current();
					
					// fixes the reply post_id's
					$request['dba']->executeUpdate("UPDATE ". K4TEMPTABLE ." SET parent_id = ". $topic['post_id'] ." WHERE parent_id = ". $topic['moved_old_post_id']);
				
					// set the moved_new_post_id to the old topics (tracking only)
					if($track)
						$request['dba']->executeUpdate("UPDATE ". K4POSTS ." SET moved_new_post_id = ". $topic['post_id'] ." WHERE post_id = ". $topic['moved_old_post_id']);
					
					// update user post counts, we don't change the total_posts because copies don't count
					// as 'posted' posts per se
					if(!$track && $topic['poster_id'] > 0)
						$request['dba']->executeUpdate("UPDATE ". K4USERINFO ." SET num_posts=num_posts+1 WHERE user_id = ". intval($topic['poster_id']));

					$num_replies	+= intval($topic['num_replies']);
				}
				$topics->free();
				
				/**
				 * Loop through the replies if we need to change post counts
				 */
				if(!$track) {
					$replies		= $request['dba']->executeQuery("SELECT * FROM ". K4POSTS ." WHERE ($query_extra)");
					while($replies->next()) {
						$reply		= $replies->current();

						// update user post counts, we don't change the total_posts because copies don't count
						// as 'posted' posts per se
						if(!$track && $reply['poster_id'] > 0) {
							$request['dba']->executeUpdate("UPDATE ". K4USERINFO ." SET num_posts=num_posts+1 WHERE user_id = ". intval($reply['poster_id']));
						}
					}
				}

				// delete the old replies (tracking only)
				if($track)
					$request['dba']->executeUpdate("DELETE FROM ". K4POSTS ." WHERE row_type=". REPLY ." AND ($query_reply_extra)");

				/**
				 * Add the replies back into the replies table
				 */
				$request['dba']->executeUpdate("INSERT INTO ". K4POSTS ." ($select) SELECT $select FROM ". K4TEMPTABLE);
				
				// TODO: something going's on here...				

				// get the last topic & reply in our initial forum
				$lastpost_created		= $request['dba']->getRow("SELECT * FROM ". K4POSTS ." WHERE forum_id=". intval($forum['forum_id']) ." ORDER BY created DESC LIMIT 1");
				$lastpost_created		= !$lastpost_created || !is_array($lastpost_created) || empty($lastpost_created) ? $no_post : $lastpost_created;
				
				// Update the original forum
				$forum_update			= $request['dba']->prepareStatement("UPDATE ". K4FORUMS ." SET posts=posts-?,replies=replies-?,post_created=?,post_name=?,post_uname=?,post_id=?,post_uid=?,post_posticon=? WHERE forum_id=?");
				$forum_update->setInt(1, ($track ? $num_replies : 0));
				$forum_update->setInt(2, ($track ? $num_replies : 0));
				$forum_update->setInt(3, $lastpost_created['created']);
				$forum_update->setString(4, $lastpost_created['name']);
				$forum_update->setString(5, $lastpost_created['poster_name']);
				$forum_update->setInt(6, $lastpost_created['post_id']);
				$forum_update->setInt(7, $lastpost_created['poster_id']);
				$forum_update->setString(8, $lastpost_created['posticon']);
				$forum_update->setInt(9, $forum['forum_id']);
				
				$forum_update->executeUpdate();
				
				// get the last topic & reply in our destination forum
				$lastpost_created	= $request['dba']->getRow("SELECT * FROM ". K4POSTS ." WHERE forum_id=". intval($destination['forum_id']) ." ORDER BY created DESC LIMIT 1");
				$lastpost_created	= !$lastpost_created || !is_array($lastpost_created) || empty($lastpost_created) ? $no_post : $lastpost_created;
				
				// update the destination forum
				$forum_update		= $request['dba']->prepareStatement("UPDATE ". K4FORUMS ." SET topics=topics+?,posts=posts+?,replies=replies+?,post_created=?,post_name=?,post_uname=?,post_id=?,post_uid=?,post_posticon=? WHERE forum_id=?");
				$forum_update->setInt(1, $num_topics);
				$forum_update->setInt(2, intval($num_replies + $num_topics));
				$forum_update->setInt(3, $num_replies);
				$forum_update->setInt(4, $lastpost_created['created']);
				$forum_update->setString(5, $lastpost_created['name']);
				$forum_update->setString(6, $lastpost_created['poster_name']);
				$forum_update->setInt(7, $lastpost_created['id']);
				$forum_update->setInt(8, $lastpost_created['poster_id']);
				$forum_update->setString(9, $lastpost_created['posticon']);
				$forum_update->setInt(10, $destination['forum_id']);
				
				$forum_update->executeUpdate();
				
				unset($last_topic, $lastpost_created, $forum_update);

				// we're done	
				k4_bread_crumbs($request['template'], $request['dba'], 'L_MOVECOPYTOPICS', $forum);
				$action = new K4InformationAction(new K4LanguageElement('L_MOVEDTOPICS', $forum['name'], $destination['name']), 'content', FALSE, 'viewforum.php?f='. $destination['forum_id'], 3);
				return $action->execute($request);

				break;
			}
			default: {
				header("Location: ". referer());
				break;
			}
		}

		return TRUE;
	}
}

class getTopicTitle extends FAAction {
	function execute(&$request) {
		
		global $_SETTINGS;

		/* set the breadcrumbs bit */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
		
		/* Check the request ID */
		if(!isset($_REQUEST['post_id']) || !$_REQUEST['post_id'] || intval($_REQUEST['post_id']) == 0) {
			$action = new K4InformationAction(new K4LanguageElement('L_TOPICDOESNTEXIST'), 'content', FALSE);
			return !USE_AJAX ? $action->execute($request) : exit();
		}			
		
		/* Get our topic */
		$topic				= $request['dba']->getRow("SELECT * FROM ". K4POSTS ." WHERE post_id = ". intval($_REQUEST['post_id']));
		
		if(!$topic || !is_array($topic) || empty($topic)) {
			$action = new K4InformationAction(new K4LanguageElement('L_TOPICDOESNTEXIST'), 'content', FALSE);
			return !USE_AJAX ? $action->execute($request) : exit();
		}
		
		/* Get its forum */
		$forum = $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($topic['forum_id']));
		
		if(!$forum || !is_array($forum) || empty($forum)) {
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);
			return !USE_AJAX ? $action->execute($request) : exit();
		}
		
		/* Is this user a moderator? */
		if(!is_moderator($request['user']->getInfoArray(), $forum)) {
			no_perms_error($request);
			return !USE_AJAX ? TRUE : exit();
		}

		if($topic['poster_id'] == $request['user']->get('id')) {
			if($request['user']->get('perms') < get_map( 'topics', 'can_edit', array('forum_id' => $topic['forum_id']))) {
				no_perms_error($request);
				return !USE_AJAX ? TRUE : exit();
			}
		} else {
			if($request['user']->get('perms') < get_map( 'other_topics', 'can_edit', array('forum_id' => $topic['forum_id']))) {
				no_perms_error($request);
				return !USE_AJAX ? TRUE : exit();
			}
		}
		
//		/* Return the title */
//		if(!USE_AJAX) {
//			k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
//			$action = new K4InformationAction(new K4LanguageElement('L_TOPICTITLEIS', $topic['name']), 'content', FALSE);
//			return $action->execute($request);
//		} else {
			echo $topic['name'];
			exit;
//		}
	}
}

class SimpleUpdateTopic extends FAAction {
	function execute(&$request) {

		global $_QUERYPARAMS, $_DATASTORE, $_SETTINGS;
		
		/* set the breadcrumbs bit */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
		
		/* Check the request ID */
		if(!isset($_REQUEST['id']) || !$_REQUEST['id'] || intval($_REQUEST['id']) == 0) {
			$action = new K4InformationAction(new K4LanguageElement('L_TOPICDOESNTEXIST'), 'content', FALSE);
			return !USE_AJAX ? $action->execute($request) : ajax_message('L_TOPICDOESNTEXIST');
		}			

		/* Get our topic */
		$topic				= $request['dba']->getRow("SELECT * FROM ". K4POSTS ." WHERE post_id = ". intval($_REQUEST['id']));
		
		if(!$topic || !is_array($topic) || empty($topic)) {
			$action = new K4InformationAction(new K4LanguageElement('L_TOPICDOESNTEXIST'), 'content', FALSE);
			return !USE_AJAX ? $action->execute($request) : ajax_message('L_TOPICDOESNTEXIST');
		}
		
		$forum = $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($topic['forum_id']));
		
		if(!$forum || !is_array($forum) || empty($forum)) {
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);
			return !USE_AJAX ? $action->execute($request) : ajax_message('L_FORUMDOESNTEXIST');
		}

		if(!isset($_REQUEST['name']) || $_REQUEST['name'] == '') {
			$name	= $topic['name'];
		} else {
			$name	= strip_tags($_REQUEST['name']);
		}

		$name = ($name == '' ? $topic['name'] : $name);
		
		if( ( strlen($name) < intval($_SETTINGS['topicminchars'])) || (strlen($name) > intval($_SETTINGS['topicmaxchars']) ) ) {
			$action = new K4InformationAction(new K4LanguageElement('L_TITLETOOSHORT', intval($_SETTINGS['topicminchars']), intval($_SETTINGS['topicmaxchars'])), 'content', TRUE);
			return !USE_AJAX ? $action->execute($request) : ajax_message(sprintf('L_TITLETOOSHORT', intval($_SETTINGS['topicminchars']), intval($_SETTINGS['topicmaxchars'])));
		}

		if($name != $topic['name']) {
			
			$name = k4_htmlentities($name, ENT_QUOTES);

			if(!is_moderator($request['user']->getInfoArray(), $forum)) {
				no_perms_error($request);
				return !USE_AJAX ? TRUE : ajax_message('L_NEEDPERMS');
			}

			if($topic['poster_id'] == $request['user']->get('id')) {
				if($request['user']->get('perms') < get_map( 'topics', 'can_edit', array('forum_id' => $topic['forum_id']))) {
					no_perms_error($request);
					return !USE_AJAX ? TRUE : ajax_message('L_NEEDPERMS');
				}
			} else {
				if($request['user']->get('perms') < get_map( 'other_topics', 'can_edit', array('forum_id' => $topic['forum_id']))) {
					no_perms_error($request);
					return !USE_AJAX ? TRUE : ajax_message('L_NEEDPERMS');
				}
			}

			/* If this topic is a redirect/ connects to one, update the original */
			if($topic['moved_new_post_id'] > 0 || $topic['moved_old_post_id'] > 0) {
				$redirect		= $request['dba']->prepareStatement("UPDATE ". K4POSTS ." SET name=?,edited_time=?,edited_username=?,edited_userid=? WHERE post_id=?");
			
				$redirect->setString(1, $name);
				$redirect->setInt(2, time());
				$redirect->setString(3, $request['user']->get('name'));
				$redirect->setInt(4, $request['user']->get('id'));
				$redirect->setInt(5, ($topic['moved_new_post_id'] > 0 ? $topic['moved_new_post_id'] : $topic['moved_old_post_id']));
				$redirect->executeUpdate();
			}

			$update_a		= $request['dba']->prepareStatement("UPDATE ". K4POSTS ." SET name=?,edited_time=?,edited_username=?,edited_userid=? WHERE post_id=?");
			
			$update_a->setString(1, $name);
			$update_a->setInt(2, time());
			$update_a->setString(3, $request['user']->get('name'));
			$update_a->setInt(4, $request['user']->get('id'));
			$update_a->setInt(5, $topic['post_id']);

			$update_a->executeUpdate();
			
			if($forum['post_id'] == $topic['post_id']) {
				$update_c	= $request['dba']->prepareStatement("UPDATE ". K4FORUMS ." SET post_name=? WHERE forum_id=?");
				$update_c->setString(1, $name);
				$update_c->setInt(2, $forum['forum_id']);
				$update_c->executeUpdate();
			}
			
			// id this is the last post in a forum
			if($forum['post_id'] == $topic['post_id'] && $forum['post_created'] == $topic['created']) {
				$update_d	= $request['dba']->prepareStatement("UPDATE ". K4FORUMS ." SET post_name=? WHERE forum_id=?");
				$update_d->setString(1, $name);
				$update_d->setInt(2, $forum['forum_id']);
				$update_d->executeUpdate();
			}
		}

		if(!USE_AJAX) {
			k4_bread_crumbs($request['template'], $request['dba'], 'L_EDITTOPIC', $forum);
			$action = new K4InformationAction(new K4LanguageElement('L_UPDATEDTOPIC', $topic['name']), 'content', FALSE, referer(), 3);
			return $action->execute($request);
		} else {
			
			echo '<a href="viewtopic.php?id='. $topic['post_id'] .'" title="'. $name .'" style="font-size: 13px;">'. (strlen($name) > 40 ? substr($name, 0, 40) .'...' : $name) .'</a>';
			exit;
		}
	}
}

?>