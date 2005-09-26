<?php
/**
* k4 Bulletin Board, moderator.class.php
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
* @version $Id: moderator.class.php 154 2005-07-15 02:56:28Z Peter Goodman $
* @package k42
*/

error_reporting(E_ALL);

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
			$query_extra .= 'topic_id = '. intval($id);
			
			$i++;
		}

		$request['template']->setVisibility('check_checkboxes', TRUE);

		switch($_REQUEST['action']) {

			/**
			 * Lock topics
			 */
			case 'lock': {
				
				if($request['user']->get('perms') < get_map($request['user'], 'closed', 'can_add', array('forum_id' => $forum['forum_id']))) {
					no_perms_error($request);
					return TRUE;
				}

				$request['dba']->executeUpdate("UPDATE ". K4TOPICS ." SET topic_locked = 1 WHERE (". $query_extra .")");
				
				k4_bread_crumbs($request['template'], $request['dba'], 'L_LOCKTOPICS', $forum);
				$action = new K4InformationAction(new K4LanguageElement('L_LOCKEDTOPICS'), 'content', TRUE, referer(), 3);

				return $action->execute($request);

				break;
			}

			/**
			 * Stick topics
			 */
			case 'stick': {
				
				if($request['user']->get('perms') < get_map($request['user'], 'sticky', 'can_add', array('forum_id' => $forum['forum_id']))) {
					no_perms_error($request);
					return TRUE;
				}

				$request['dba']->executeUpdate("UPDATE ". K4TOPICS ." SET topic_type = ". TOPIC_STICKY .", topic_expire = 0 WHERE (". $query_extra .")");
				
				k4_bread_crumbs($request['template'], $request['dba'], 'L_STICKTOPICS', $forum);
				$action = new K4InformationAction(new K4LanguageElement('L_STUCKTOPICS'), 'content', TRUE, referer(), 3);

				return $action->execute($request);
				
				break;
			}

			/**
			 * Announce topics
			 */
			case 'announce': {
				
				if($request['user']->get('perms') < get_map($request['user'], 'announce', 'can_add', array('forum_id' => $forum['forum_id']))) {
					no_perms_error($request);
					return TRUE;
				}

				$request['dba']->executeUpdate("UPDATE ". K4TOPICS ." SET topic_type = ". TOPIC_ANNOUNCE .", topic_expire = 0 WHERE (". $query_extra .")");
				
				k4_bread_crumbs($request['template'], $request['dba'], 'L_ANNOUNCETOPICS', $forum);
				$action = new K4InformationAction(new K4LanguageElement('L_ANNOUNCEDTOPICS'), 'content', TRUE, referer(), 3);

				return $action->execute($request);
				
				break;
			}

			/**
			 * Feature topics
			 */
			case 'feature': {
				
				if($request['user']->get('perms') < get_map($request['user'], 'feature', 'can_add', array('forum_id' => $forum['forum_id']))) {
					no_perms_error($request);
					return TRUE;
				}

				$request['dba']->executeUpdate("UPDATE ". K4TOPICS ." SET is_feature = 1, topic_expire = 0 WHERE (". $query_extra .")");
				
				k4_bread_crumbs($request['template'], $request['dba'], 'L_FEATURETOPICS', $forum);
				$action = new K4InformationAction(new K4LanguageElement('L_FEATUREDTOPICS'), 'content', TRUE, referer(), 3);

				return $action->execute($request);

				break;
			}

			/**
			 * Remove any special formatting on topics
			 */
			case 'normal': {
				
				if($request['user']->get('perms') < get_map($request['user'], 'normalize', 'can_add', array('forum_id' => $forum['forum_id']))) {
					no_perms_error($request);
					return TRUE;
				}

				$request['dba']->executeUpdate("UPDATE ". K4TOPICS ." SET is_feature = 0, display = 1, queue = 0, topic_type = ". TOPIC_NORMAL .", topic_expire = 0, topic_locked = 0 WHERE (". $query_extra .")");
				
				k4_bread_crumbs($request['template'], $request['dba'], 'L_SETASNORMALTOPICS', $forum);
				$action = new K4InformationAction(new K4LanguageElement('L_NORMALIZEDTOPICS'), 'content', TRUE, referer(), 3);

				return $action->execute($request);

				break;
			}

			/**
			 * Insert the topics into the moderator's queue for checking
			 */
			case 'queue': {

				if($request['user']->get('perms') < get_map($request['user'], 'queue', 'can_add', array('forum_id' => $forum['forum_id']))) {
					no_perms_error($request);
					return TRUE;
				}

				$request['dba']->executeUpdate("UPDATE ". K4TOPICS ." SET queue = 1 WHERE (". $query_extra .")");
				
				k4_bread_crumbs($request['template'], $request['dba'], 'L_QUEUETOPICS', $forum);
				$action = new K4InformationAction(new K4LanguageElement('L_QUEUEDTOPICS'), 'content', TRUE, referer(), 3);

				return $action->execute($request);

				break;
			}

			/**
			 * Subscribe to all of the selected topics
			 */
			case 'subscribe': {
				foreach($topics as $topic_id) {
					$is_subscribed		= $request['dba']->getRow("SELECT * FROM ". K4SUBSCRIPTIONS ." WHERE user_id = ". intval($request['user']->get('id')) ." AND topic_id = ". intval($topic_id));
					if(!is_array($is_subscribed) || empty($is_subscribed)) {
						$subscribe			= &$request['dba']->prepareStatement("INSERT INTO ". K4SUBSCRIPTIONS ." (user_id,user_name,topic_id,forum_id,email,category_id) VALUES (?,?,?,?,?,?)");
						$subscribe->setInt(1, $request['user']->get('id'));
						$subscribe->setString(2, $request['user']->get('name'));
						$subscribe->setInt(3, $topic_id);
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
				
				if($request['user']->get('perms') < get_map($request['user'], 'delete', 'can_add', array('forum_id' => $forum['forum_id']))) {
					no_perms_error($request);
					return TRUE;
				}
				
				$users			= array();

				// find the users for topics first
				$t				= &$request['dba']->executeQuery("SELECT * FROM ". K4TOPICS ." WHERE ($query_extra)");
				while($t->next()) {
					$temp		= $t->current();
					$users[$temp['poster_id']] = isset($users[$temp['poster_id']]) ? $users[$temp['poster_id']] + 1 : 1;
				}

				$num_topics		= $t->size;

				$t->free();
				
				// find them for replies
				$r				= &$request['dba']->executeQuery("SELECT * FROM ". K4REPLIES ." WHERE ($query_extra)");
				while($t->next()) {
					$temp		= $t->current();
					$users[$temp['poster_id']] = isset($users[$temp['poster_id']]) ? $users[$temp['poster_id']] + 1 : 1;
				}
				
				$num_replies	= $r->size;

				$r->free();

				// loop through the users and change their post counts
				foreach($users as $id => $postcount) {
					$request['dba']->executeUpdate("UPDATE ". K4USERINFO ." SET num_posts = num_posts-{$postcount} WHERE user_id = {$id}");
				}

				// Remove everything
				$request['dba']->executeUpdate("DELETE FROM ". K4TOPICS ." WHERE ". $query_extra);
				$request['dba']->executeUpdate("DELETE FROM ". K4REPLIES ." WHERE ". $query_extra);
								
				// update the forum and the datastore
				
				/* Get that last topic in this forum that's not one of these topics */
				$last_topic			= $request['dba']->getRow("SELECT * FROM ". K4TOPICS ." WHERE (". str_replace('=', '<>', $query_extra) .") AND is_draft=0 AND queue=0 AND display=1 AND forum_id=". intval($forum['forum_id']) ." ORDER BY created DESC LIMIT 1");
				$last_topic			= !$last_topic || !is_array($last_topic) ? array('created'=>0,'name'=>'','poster_name'=>'','topic_id'=>0,'id'=>0,'poster_id'=>0,'posticon'=>'') : $last_topic;
				$last_topic['id']	= $last_topic['topic_id'];

				/* Get that last post in this forum that's not part of/from one of these topics */
				$last_post			= $request['dba']->getRow("SELECT * FROM ". K4REPLIES ." WHERE (". str_replace('=', '<>', $query_extra) .") AND forum_id=". intval($forum['forum_id']) ." ORDER BY created DESC LIMIT 1");
				$last_post			= !$last_post || !is_array($last_post) ? $last_topic : array_merge($last_post, array('id'=>$last_post['reply_id']));


				/**
				 * Update the forum and the datastore
				 */
							
				$forum_update		= &$request['dba']->prepareStatement("UPDATE ". K4FORUMS ." SET topics=topics-?,posts=posts-?,replies=replies-?,topic_created=?,topic_name=?,topic_uname=?,topic_id=?,topic_uid=?,topic_posticon=?,post_created=?,post_name=?,post_uname=?,post_id=?,post_uid=?,post_posticon=? WHERE forum_id=?");
				$datastore_update	= &$request['dba']->prepareStatement("UPDATE ". K4DATASTORE ." SET data=? WHERE varname=?");
					
				/* Set the forum values */
				$forum_update->setInt(1, $num_topics);
				$forum_update->setInt(2, $num_replies + $num_topics);
				$forum_update->setInt(3, $num_replies);
				$forum_update->setInt(4, $last_topic['created']);
				$forum_update->setString(5, $last_topic['name']);
				$forum_update->setString(6, $last_topic['poster_name']);
				$forum_update->setInt(7, $last_topic['topic_id']);
				$forum_update->setInt(8, $last_topic['poster_id']);
				$forum_update->setString(9, $last_topic['posticon']);
				$forum_update->setInt(10, $last_post['created']);
				$forum_update->setString(11, $last_post['name']);
				$forum_update->setString(12, $last_post['poster_name']);
				$forum_update->setInt(13, $last_post['id']); // this is fixed above
				$forum_update->setInt(14, $last_post['poster_id']);
				$forum_update->setString(15, $last_post['posticon']);
				$forum_update->setInt(16, $forum['forum_id']);
				
				/* Set the datastore values */
				$datastore					= $_DATASTORE['forumstats'];
				$datastore['num_topics']	= $request['dba']->getValue("SELECT COUNT(*) FROM ". K4TOPICS ." WHERE is_draft = 0 AND queue = 0 AND display = 1") - 1;
				$datastore['num_replies']	= $request['dba']->getValue("SELECT COUNT(*) FROM ". K4REPLIES ) - intval($num_replies);
				
				$datastore_update->setString(1, serialize($datastore));
				$datastore_update->setString(2, 'forumstats');
				
				/* Execute the forum and datastore update queries */
				$forum_update->executeUpdate();
				$datastore_update->executeUpdate();
				
				// change the file execution time on the datastore file
				if(!@touch(CACHE_DS_FILE, time()-86460)) {
					@unlink(CACHE_DS_FILE);
				}

				k4_bread_crumbs($request['template'], $request['dba'], 'L_DELETETOPICS', $forum);
				$action = new K4InformationAction(new K4LanguageElement('L_DELETEDTOPICS'), 'content', TRUE, referer(), 5);

				return $action->execute($request);

				break;
			}

			/**
			 * Move/copy topics to a destination forum
			 */
			case 'move': {
				
				if($request['user']->get('perms') < get_map($request['user'], 'move', 'can_add', array('forum_id' => $forum['forum_id']))) {
					no_perms_error($request);
					return TRUE;
				}

				if(count($topics) <= 0) {
					k4_bread_crumbs($request['template'], $request['dba'], 'L_MOVETOPICS', $forum);
					$action = new K4InformationAction(new K4LanguageElement('L_NEEDSELECTTOPIC'), 'content', FALSE);

					return $action->execute($request);
				}

				/* Get the topics */
				$result				= &$request['dba']->executeQuery("SELECT * FROM ". K4TOPICS ." WHERE is_draft=0 AND queue = 0 AND display = 1 AND forum_id = ". intval($forum['forum_id']) ." AND (". $query_extra .") ORDER BY created DESC");
				
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
		
		global $_QUERYPARAMS;

		/* Check the request ID */
		if(!isset($_REQUEST['id']) || !$_REQUEST['id'] || intval($_REQUEST['id']) == 0) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INVALIDFORUM');
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);

			return $action->execute($request);
			return TRUE;
		}
		
		/* Check the other request ID */
		if(!isset($_REQUEST['forum']) || !$_REQUEST['forum'] || intval($_REQUEST['forum']) == 0) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INVALIDFORUM');
			$action = new K4InformationAction(new K4LanguageElement('L_DESTFORUMDOESNTEXIST'), 'content', FALSE);

			return $action->execute($request);
			return TRUE;
		}
			
		$forum				= $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($_REQUEST['id']));
		$destination		= $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($_REQUEST['forum']));

		/* Check the forum data given */
		if(!$forum || !is_array($forum) || empty($forum)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INVALIDFORUM');
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);

			return $action->execute($request);
			return TRUE;
		}
			
		/* Make sure the we are trying to post into a forum */
		if(!($forum['row_type'] & FORUM)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_CANTMODNONFORUM'), 'content', FALSE);

			return $action->execute($request);
			return TRUE;
		}

		/* Check the forum data given */
		if(!$destination || !is_array($destination) || empty($destination)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INVALIDFORUM');
			$action = new K4InformationAction(new K4LanguageElement('L_DESTFORUMDOESNTEXIST'), 'content', FALSE);

			return $action->execute($request);
			return TRUE;
		}
			
		/* Make sure the we are trying to post into a forum */
		if(!($destination['row_type'] & FORUM)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_CANTMODNONFORUM'), 'content', FALSE);

			return $action->execute($request);
			return TRUE;
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

		if($request['user']->get('perms') < get_map($request['user'], 'move', 'can_add', array('forum_id' => $forum['forum_id']))) {
			no_perms_error($request);
			return TRUE;
		}

		if($request['user']->get('perms') < get_map($request['user'], 'move', 'can_add', array('forum_id' => $destination['forum_id']))) {
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
			$query_extra .= 'topic_id = '. intval($id);

			$i++;
		}

		if(!is_array($topics) || count($topics) == 0) {
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_NEESSELECTTOPICS'), 'content', FALSE);

			return $action->execute($request);
			return TRUE;
		}
		
		switch($_REQUEST['action']) {
			case 'move': {
				
				// move the topics and replies
				$request['dba']->executeUpdate("UPDATE ". K4TOPICS ." SET forum_id = ". intval($destination['forum_id']) .", category_id = ". intval($destination['category_id']) .", moved = 1 WHERE $query_extra");
				$request['dba']->executeUpdate("UPDATE ". K4REPLIES ." SET forum_id = ". intval($destination['forum_id']) .", category_id = ". intval($destination['category_id']) ." WHERE $query_extra");
				
				$num_topics			= count($topics);
				
				// find the number of replies
				$r					= &$request['dba']->executeQuery("SELECT * FROM ". K4REPLIES ." WHERE $query_extra");
				$num_replies		= $r->numRows();				
				
				// get the last topic & reply in our initial forum
				$last_topic			= $request['dba']->getRow("SELECT * FROM ". K4TOPICS ." WHERE is_draft=0 AND queue=0 AND display=1 AND forum_id=". intval($forum['forum_id']) ." ORDER BY created DESC LIMIT 1");
				$last_topic			= !$last_topic || !is_array($last_topic) ? array('created'=>0,'name'=>'','poster_name'=>'','id'=>0,'poster_id'=>0,'posticon'=>'') : array_merge($last_topic, array('id' => $last_topic['topic_id']));
				$last_post			= $request['dba']->getRow("SELECT * FROM ". K4REPLIES ." WHERE forum_id=". intval($forum['forum_id']) ." ORDER BY created DESC LIMIT 1");
				$last_post			= !$last_post || !is_array($last_post) ? $last_topic : array_merge($last_post, array('id' => $last_post['reply_id']));
				
				// Update this forum
				$forum_update		= &$request['dba']->prepareStatement("UPDATE ". K4FORUMS ." SET topics=?,posts=?,replies=?,topic_created=?,topic_name=?,topic_uname=?,topic_id=?,topic_uid=?,topic_posticon=?,post_created=?,post_name=?,post_uname=?,post_id=?,post_uid=?,post_posticon=? WHERE forum_id=?");
				
				$num_topics			= $request['dba']->getValue("SELECT COUNT(*) FROM ". K4TOPICS ." WHERE queue=0 AND is_draft=0 AND display=1 AND forum_id = ". intval($forum['forum_id']));
				$num_replies		= $request['dba']->getValue("SELECT COUNT(*) FROM ". K4REPLIES ." WHERE forum_id = ". intval($forum['forum_id']));	
				
				/* Set the forum values */
				$forum_update->setInt(1, $num_topics);
				$forum_update->setInt(2, intval($num_replies + $num_topics));
				$forum_update->setInt(3, $num_replies);
				$forum_update->setInt(4, $last_topic['created']);
				$forum_update->setString(5, $last_topic['name']);
				$forum_update->setString(6, $last_topic['poster_name']);
				$forum_update->setInt(7, $last_topic['id']);
				$forum_update->setInt(8, $last_topic['poster_id']);
				$forum_update->setString(9, $last_topic['posticon']);
				$forum_update->setInt(10, $last_post['created']);
				$forum_update->setString(11, $last_post['name']);
				$forum_update->setString(12, $last_post['poster_name']);
				$forum_update->setInt(13, $last_post['id']);
				$forum_update->setInt(14, $last_post['poster_id']);
				$forum_update->setString(15, $last_post['posticon']);
				$forum_update->setInt(16, $forum['forum_id']);
				
				$forum_update->executeUpdate();

				unset($last_topic, $last_post, $forum_update);
				
				// get the last topic & reply in our destination forum
				$last_topic			= $request['dba']->getRow("SELECT * FROM ". K4TOPICS ." WHERE is_draft=0 AND queue=0 AND display=1 AND forum_id=". intval($destination['forum_id']) ." ORDER BY created DESC LIMIT 1");
				$last_topic			= !$last_topic || !is_array($last_topic) ? array('created'=>0,'name'=>'','poster_name'=>'','id'=>0,'poster_id'=>0,'posticon'=>'') : array_merge($last_topic, array('id' => $last_topic['topic_id']));
				$last_post			= $request['dba']->getRow("SELECT * FROM ". K4REPLIES ." WHERE forum_id=". intval($destination['forum_id']) ." ORDER BY created DESC LIMIT 1");
				$last_post			= !$last_post || !is_array($last_post) ? $last_topic : array_merge($last_post, array('id' => $last_post['reply_id']));

				// update the destination forum
				$forum_update		= &$request['dba']->prepareStatement("UPDATE ". K4FORUMS ." SET topics=?,posts=?,replies=?,topic_created=?,topic_name=?,topic_uname=?,topic_id=?,topic_uid=?,topic_posticon=?,post_created=?,post_name=?,post_uname=?,post_id=?,post_uid=?,post_posticon=? WHERE forum_id=?");
				
				$num_topics			= $request['dba']->getValue("SELECT COUNT(*) FROM ". K4TOPICS ." WHERE queue=0 AND is_draft=0 AND display=1 AND forum_id = ". intval($destination['forum_id']));
				$num_replies		= $request['dba']->getValue("SELECT COUNT(*) FROM ". K4REPLIES ." WHERE forum_id = ". intval($destination['forum_id']));

				/* Set the forum values */
				$forum_update->setInt(1, $num_topics);
				$forum_update->setInt(2, intval($num_replies + $num_topics));
				$forum_update->setInt(3, $num_replies);
				$forum_update->setInt(4, $last_topic['created']);
				$forum_update->setString(5, $last_topic['name']);
				$forum_update->setString(6, $last_topic['poster_name']);
				$forum_update->setInt(7, $last_topic['id']);
				$forum_update->setInt(8, $last_topic['poster_id']);
				$forum_update->setString(9, $last_topic['posticon']);
				$forum_update->setInt(10, $last_post['created']);
				$forum_update->setString(11, $last_post['name']);
				$forum_update->setString(12, $last_post['poster_name']);
				$forum_update->setInt(13, $last_post['id']);
				$forum_update->setInt(14, $last_post['poster_id']);
				$forum_update->setString(15, $last_post['posticon']);
				$forum_update->setInt(16, $destination['forum_id']);
				
				$forum_update->executeUpdate();

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

				$select = "parent_id, forum_id, category_id, name, is_draft, is_feature, moved, queue, display, edited_time, edited_username, edited_userid, ratings_sum, ratings_num, rating, disable_html, disable_bbcode, disable_emoticons, disable_sig, disable_areply, disable_aurls, description, body_text, posticon, topic_type, topic_expire, topic_locked, poster_name, poster_id, poster_ip, reply_time, reply_uname, reply_id, reply_uid, is_poll, num_replies, views, last_viewed, attachments, last_post, row_level, row_type, created, moved_old_topic_id, moved_new_topic_id";
				
				// moved_old_topic_id=topic_id is for post tracking

				/**
				 * Create a temporary table and copy all of the topics into it
				 */
				if(is_a($request['dba'], 'SQLiteConnection') || is_a($request['dba'], 'MySQLConnection')) {
					$request['dba']->createTemporary(K4TEMPTABLE, K4TOPICS);
					$request['dba']->executeUpdate("INSERT INTO ". K4TEMPTABLE ." (topic_id,$select) SELECT topic_id,$select FROM ". K4TOPICS ." WHERE $query_extra");
				} elseif(is_a($request['dba'], 'MySQLiConnection')) {
					$request['dba']->executeUpdate("CREATE TEMPORARY TABLE ". K4TEMPTABLE ." (SELECT topic_id,$select FROM ". K4TOPICS ." WHERE $query_extra)");
				}
				
				$request['dba']->executeUpdate("UPDATE ". K4TEMPTABLE ." SET moved_old_topic_id=topic_id, forum_id = ". $destination['forum_id'] .", category_id = ". $destination['category_id']);

				/**
				 * Copy all of the topics back into the topics table 
				 */
				$request['dba']->executeUpdate("INSERT INTO ". K4TOPICS ." ($select) SELECT $select FROM ". K4TEMPTABLE);
				
				// remove all gloat info from the 'tracker' topics
				if($track)
					$request['dba']->executeUpdate("UPDATE ". K4TOPICS ." SET body_text='',edited_time=0,edited_username='',edited_userid=0 WHERE ($query_extra)");
				
				/**
				 * Create a temporary table for the replies
				 */
				$select	= "parent_id, topic_id, forum_id, category_id, name, body_text, poster_name, poster_id, poster_ip, edited_time, edited_username, edited_userid, disable_html, disable_bbcode, disable_emoticons, disable_sig, disable_areply, disable_aurls, num_replies, is_poll, posticon, row_level, row_type, created, moved_old_topic_id";
				$request['dba']->executeUpdate("DROP TABLE ". K4TEMPTABLE);
				
				if(is_a($request['dba'], 'SQLiteConnection') || is_a($request['dba'], 'MySQLConnection')) {
					$request['dba']->createTemporary(K4TEMPTABLE, K4REPLIES);
					$request['dba']->executeUpdate("INSERT INTO ". K4TEMPTABLE ." ($select) SELECT $select FROM ". K4REPLIES ." WHERE $query_extra");
				} elseif(is_a($request['dba'], 'MySQLiConnection')) {
					$request['dba']->executeUpdate("CREATE TEMPORARY TABLE ". K4TEMPTABLE ." (SELECT $select FROM ". K4REPLIES ." WHERE $query_extra)");
				}
				$request['dba']->executeUpdate("UPDATE ". K4TEMPTABLE ." SET forum_id = ". $destination['forum_id'] .", category_id = ". $destination['category_id']);

				/**
				 * Get all of our new topics and update the reply topic ids
				 */
				$new_topic_ids		= '';
				$topics				= $request['dba']->executeQuery("SELECT * FROM ". K4TOPICS ." WHERE (". str_replace('topic_id', 'moved_old_topic_id', $query_extra) .")");
				
				$num_topics			= $topics->numrows();
				$num_replies		= 0;
				while($topics->next()) {
					$topic			= $topics->current();
					
					// fixes the reply topic_id's
					$request['dba']->executeUpdate("UPDATE ". K4TEMPTABLE ." SET topic_id = ". $topic['topic_id'] ." WHERE topic_id = ". $topic['moved_old_topic_id']);
				
					// set the moved_new_topic_id to the old topics (tracking only)
					if($track)
						$request['dba']->executeUpdate("UPDATE ". K4TOPICS ." SET moved_new_topic_id = ". $topic['topic_id'] ." WHERE topic_id = ". $topic['moved_old_topic_id']);
					
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
					$replies		= $request['dba']->executeQuery("SELECT * FROM ". K4REPLIES ." WHERE ($query_extra)");
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
					$request['dba']->executeUpdate("DELETE FROM ". K4REPLIES ." WHERE ($query_extra)");

				/**
				 * Add the replies back into the replies table
				 */
				$request['dba']->executeUpdate("INSERT INTO ". K4REPLIES ." ($select) SELECT $select FROM ". K4TEMPTABLE);
								
				// get the last topic & reply in our initial forum
				$last_topic			= $request['dba']->getRow("SELECT * FROM ". K4TOPICS ." WHERE is_draft=0 AND queue=0 AND display=1 AND forum_id=". intval($forum['forum_id']) ." ORDER BY created DESC LIMIT 1");
				$last_topic			= !$last_topic || !is_array($last_topic) ? array('created'=>0,'name'=>'','poster_name'=>'','id'=>0,'poster_id'=>0,'posticon'=>'') : array_merge($last_topic, array('id' => $last_topic['topic_id']));
				$last_post			= $request['dba']->getRow("SELECT * FROM ". K4REPLIES ." WHERE forum_id=". intval($forum['forum_id']) ." ORDER BY created DESC LIMIT 1");
				$last_post			= !$last_post || !is_array($last_post) ? $last_topic : array_merge($last_post, array('id' => $last_post['reply_id']));
				
				// Update this forum
				$forum_update		= &$request['dba']->prepareStatement("UPDATE ". K4FORUMS ." SET posts=posts-?,replies=replies-?,topic_created=?,topic_name=?,topic_uname=?,topic_id=?,topic_uid=?,topic_posticon=?,post_created=?,post_name=?,post_uname=?,post_id=?,post_uid=?,post_posticon=? WHERE forum_id=?");
					
				/* Set the forum values */
				$forum_update->setInt(1, ($track ? $num_replies : 0));
				$forum_update->setInt(2, ($track ? $num_replies : 0));
				$forum_update->setInt(3, $last_topic['created']);
				$forum_update->setString(4, $last_topic['name']);
				$forum_update->setString(5, $last_topic['poster_name']);
				$forum_update->setInt(6, $last_topic['id']);
				$forum_update->setInt(7, $last_topic['poster_id']);
				$forum_update->setString(8, $last_topic['posticon']);
				$forum_update->setInt(9, $last_post['created']);
				$forum_update->setString(10, $last_post['name']);
				$forum_update->setString(11, $last_post['poster_name']);
				$forum_update->setInt(12, $last_post['id']);
				$forum_update->setInt(13, $last_post['poster_id']);
				$forum_update->setString(14, $last_post['posticon']);
				$forum_update->setInt(15, $forum['forum_id']);
				
				$forum_update->executeUpdate();

				unset($last_topic, $last_post, $forum_update);
				
				// get the last topic & reply in our destination forum
				$last_topic			= $request['dba']->getRow("SELECT * FROM ". K4TOPICS ." WHERE is_draft=0 AND queue=0 AND display=1 AND forum_id=". intval($destination['forum_id']) ." ORDER BY created DESC LIMIT 1");
				$last_topic			= !$last_topic || !is_array($last_topic) ? array('created'=>0,'name'=>'','poster_name'=>'','id'=>0,'poster_id'=>0,'posticon'=>'') : array_merge($last_topic, array('id' => $last_topic['topic_id']));
				$last_post			= $request['dba']->getRow("SELECT * FROM ". K4REPLIES ." WHERE forum_id=". intval($destination['forum_id']) ." ORDER BY created DESC LIMIT 1");
				$last_post			= !$last_post || !is_array($last_post) ? $last_topic : array_merge($last_post, array('id' => $last_post['reply_id']));

				// update the destination forum
				$forum_update		= &$request['dba']->prepareStatement("UPDATE ". K4FORUMS ." SET topics=topics+?,posts=posts+?,replies=replies+?,topic_created=?,topic_name=?,topic_uname=?,topic_id=?,topic_uid=?,topic_posticon=?,post_created=?,post_name=?,post_uname=?,post_id=?,post_uid=?,post_posticon=? WHERE forum_id=?");
					
				/* Set the forum values */
				$forum_update->setInt(1, $num_topics);
				$forum_update->setInt(2, intval($num_replies + $num_topics));
				$forum_update->setInt(3, $num_replies);
				$forum_update->setInt(4, $last_topic['created']);
				$forum_update->setString(5, $last_topic['name']);
				$forum_update->setString(6, $last_topic['poster_name']);
				$forum_update->setInt(7, $last_topic['id']);
				$forum_update->setInt(8, $last_topic['poster_id']);
				$forum_update->setString(9, $last_topic['posticon']);
				$forum_update->setInt(10, $last_post['created']);
				$forum_update->setString(11, $last_post['name']);
				$forum_update->setString(12, $last_post['poster_name']);
				$forum_update->setInt(13, $last_post['id']);
				$forum_update->setInt(14, $last_post['poster_id']);
				$forum_update->setString(15, $last_post['posticon']);
				$forum_update->setInt(16, $destination['forum_id']);
				
				$forum_update->executeUpdate();

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
		if(!isset($_REQUEST['topic_id']) || !$_REQUEST['topic_id'] || intval($_REQUEST['topic_id']) == 0) {
			$action = new K4InformationAction(new K4LanguageElement('L_TOPICDOESNTEXIST'), 'content', FALSE);
			return !USE_AJAX ? $action->execute($request) : exit();
		}			
		
		/* Get our topic */
		$topic				= $request['dba']->getRow("SELECT * FROM ". K4TOPICS ." WHERE topic_id = ". intval($_REQUEST['topic_id']));
		
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
			if($request['user']->get('perms') < get_map($request['user'], 'topics', 'can_edit', array('forum_id' => $topic['forum_id']))) {
				no_perms_error($request);
				return !USE_AJAX ? TRUE : exit();
			}
		} else {
			if($request['user']->get('perms') < get_map($request['user'], 'other_topics', 'can_edit', array('forum_id' => $topic['forum_id']))) {
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
		
		$ajax = (isset($_REQUEST['ajax']) && intval($_REQUEST['ajax']) == 1) ? TRUE : FALSE;
		
		/* set the breadcrumbs bit */
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');

		if (!$this->runPostFilter('name', new FALengthFilter(intval($_SETTINGS['topicmaxchars'])))) {
			$action = new K4InformationAction(new K4LanguageElement('L_TITLETOOSHORT', intval($_SETTINGS['topicminchars']), intval($_SETTINGS['topicmaxchars'])), 'content', TRUE);
			return !USE_AJAX ? $action->execute($request) : ajax_message('L_TITLETOOSHORT');
		}
		if (!$this->runPostFilter('name', new FALengthFilter(intval($_SETTINGS['topicmaxchars']), intval($_SETTINGS['topicminchars'])))) {
			$action = new K4InformationAction(new K4LanguageElement('L_TITLETOOSHORT', intval($_SETTINGS['topicminchars']), intval($_SETTINGS['topicmaxchars'])), 'content', TRUE);
			return !USE_AJAX ? $action->execute($request) : ajax_message('L_TITLETOOSHORT');
		}

		/* Check the request ID */
		if(!isset($_REQUEST['id']) || !$_REQUEST['id'] || intval($_REQUEST['id']) == 0) {
			$action = new K4InformationAction(new K4LanguageElement('L_TOPICDOESNTEXIST'), 'content', FALSE);
			return !USE_AJAX ? $action->execute($request) : ajax_message('L_TOPICDOESNTEXIST');
		}			

		/* Get our topic */
		$topic				= $request['dba']->getRow("SELECT * FROM ". K4TOPICS ." WHERE topic_id = ". intval($_REQUEST['id']));
		
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

		$name = $name == '' ? $topic['name'] : $name;

		if($name != $topic['name']) {
			
			$name = htmlentities(html_entity_decode($name, ENT_QUOTES), ENT_QUOTES);

			if(!is_moderator($request['user']->getInfoArray(), $forum)) {
				no_perms_error($request);
				return !USE_AJAX ? TRUE : ajax_message('L_NEEDPERMS');
			}

			if($topic['poster_id'] == $request['user']->get('id')) {
				if($request['user']->get('perms') < get_map($request['user'], 'topics', 'can_edit', array('forum_id' => $topic['forum_id']))) {
					no_perms_error($request);
					return !USE_AJAX ? TRUE : ajax_message('L_NEEDPERMS');
				}
			} else {
				if($request['user']->get('perms') < get_map($request['user'], 'other_topics', 'can_edit', array('forum_id' => $topic['forum_id']))) {
					no_perms_error($request);
					return !USE_AJAX ? TRUE : ajax_message('L_NEEDPERMS');
				}
			}

			/* If this topic is a redirect/ connects to one, update the original */
			if($topic['moved_new_topic_id'] > 0 || $topic['moved_old_topic_id'] > 0) {
				$redirect		= &$request['dba']->prepareStatement("UPDATE ". K4TOPICS ." SET name=?,edited_time=?,edited_username=?,edited_userid=? WHERE topic_id=?");
			
				$redirect->setString(1, $name);
				$redirect->setInt(2, time());
				$redirect->setString(3, $request['user']->get('name'));
				$redirect->setInt(4, $request['user']->get('id'));
				$redirect->setInt(5, ($topic['moved_new_topic_id'] > 0 ? $topic['moved_new_topic_id'] : $topic['moved_old_topic_id']));
				$redirect->executeUpdate();
			}

			$update_a		= &$request['dba']->prepareStatement("UPDATE ". K4TOPICS ." SET name=?,edited_time=?,edited_username=?,edited_userid=? WHERE topic_id=?");
			
			$update_a->setString(1, $name);
			$update_a->setInt(2, time());
			$update_a->setString(3, $request['user']->get('name'));
			$update_a->setInt(4, $request['user']->get('id'));
			$update_a->setInt(5, $topic['topic_id']);

			$update_a->executeUpdate();
			
			if($forum['topic_id'] == $topic['topic_id']) {
				$update_c	= &$request['dba']->prepareStatement("UPDATE ". K4FORUMS ." SET topic_name=? WHERE forum_id=?");
				$update_c->setString(1, $name);
				$update_c->setInt(2, $forum['forum_id']);
				$update_c->executeUpdate();
			}
			
			// id this is the last post in a forum
			if($forum['post_id'] == $topic['topic_id'] && $forum['post_created'] == $topic['created']) {
				$update_d	= &$request['dba']->prepareStatement("UPDATE ". K4FORUMS ." SET post_name=? WHERE forum_id=?");
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
			
			echo '<a href="viewtopic.php?id='. $topic['topic_id'] .'" title="'. $name .'" style="font-size: 13px;">'. (strlen($name) > 40 ? substr($name, 0, 40) .'...' : $name) .'</a>';
			exit;
		}
	}
}

?>