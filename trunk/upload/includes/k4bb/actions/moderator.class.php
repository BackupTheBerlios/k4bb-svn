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
* @version $Id: moderator.class.php,v 1.1 2005/05/24 20:01:31 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	exit;
}

class ModerateForum extends FAAction {
	function execute(&$request) {
		
		global $_QUERYPARAMS;

		/* Check the request ID */
		if(!isset($_REQUEST['id']) || !$_REQUEST['id'] || intval($_REQUEST['id']) == 0) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INVALIDFORUM');
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);

			return $action->execute($request);
			return TRUE;
		}
			
		$forum				= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['forum'] ." FROM ". K4FORUMS ." f LEFT JOIN ". K4INFO ." i ON f.forum_id = i.id WHERE i.id = ". intval($_REQUEST['id']));
		
		/* Check the forum data given */
		if(!$forum || !is_array($forum) || empty($forum)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INVALIDFORUM');
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);

			return $action->execute($request);
			return TRUE;
		}
			
		/* Make sure the we are trying to post into a forum */
		if(!($forum['row_type'] & FORUM)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_CANTMODNONFORUM'), 'content', FALSE);

			return $action->execute($request);
			return TRUE;
		}

		/**
		 * Check for moderating permission
		 */
		
		if(!is_moderator($request['user'], $forum)) {
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
			$request['template']->setFile('content', 'login_form.html');
			$request['template']->setVisibility('no_perms', TRUE);
			return TRUE;
		}
		
		if(!isset($_REQUEST['action']) || $_REQUEST['action'] == '') {
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_NEEDSELECTACTION'), 'content', FALSE);

			return $action->execute($request);
			return TRUE;
		}

		if(!isset($_REQUEST['topics']) || $_REQUEST['topics'] == '') {
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_NEESSELECTTOPICS'), 'content', FALSE);

			return $action->execute($request);
			return TRUE;
		}

		$topics		= explode("|", $_REQUEST['topics']);

		if(!is_array($topics) || count($topics) == 0) {
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_NEESSELECTTOPICS'), 'content', FALSE);

			return $action->execute($request);
			return TRUE;
		}

		$query_extra	= '';
		$move_extra		= '';
		$i				= 0;
		foreach($topics as $id) {
			$query_extra .= $i == 0 ? ' ' : ' OR ';
			$query_extra .= 'topic_id = '. intval($id);
			$move_extra .= $i == 0 ? ' ' : ' OR ';
			$move_extra .= 't.topic_id = '. intval($id);
			
			$i++;
		}

		switch($_REQUEST['action']) {

			/**
			 * Lock topics
			 */
			case 'lock': {
				
				if($request['user']->get('perms') < get_map($request['user'], 'closed', 'can_add', array('forum_id' => $forum['id']))) {
					k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
					$request['template']->setFile('content', 'login_form.html');
					$request['template']->setVisibility('no_perms', TRUE);
					return TRUE;
				}

				$request['dba']->executeUpdate("UPDATE ". K4TOPICS ." SET topic_locked = 1 WHERE ". $query_extra);
				
				k4_bread_crumbs(&$request['template'], $request['dba'], 'L_LOCKTOPICS', $forum);
				$action = new K4InformationAction(new K4LanguageElement('L_LOCKEDTOPICS'), 'content', TRUE, referer(), 3);

				return $action->execute($request);

				break;
			}

			/**
			 * Stick topics
			 */
			case 'stick': {
				
				if($request['user']->get('perms') < get_map($request['user'], 'sticky', 'can_add', array('forum_id' => $forum['id']))) {
					k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
					$request['template']->setFile('content', 'login_form.html');
					$request['template']->setVisibility('no_perms', TRUE);
					return TRUE;
				}

				$request['dba']->executeUpdate("UPDATE ". K4TOPICS ." SET topic_type = ". TOPIC_STICKY .", topic_expire = 0 WHERE ". $query_extra);
				
				k4_bread_crumbs(&$request['template'], $request['dba'], 'L_STICKTOPICS', $forum);
				$action = new K4InformationAction(new K4LanguageElement('L_STUCKTOPICS'), 'content', TRUE, referer(), 3);

				return $action->execute($request);
				
				break;
			}

			/**
			 * Announce topics
			 */
			case 'announce': {
				
				if($request['user']->get('perms') < get_map($request['user'], 'announce', 'can_add', array('forum_id' => $forum['id']))) {
					k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
					$request['template']->setFile('content', 'login_form.html');
					$request['template']->setVisibility('no_perms', TRUE);
					return TRUE;
				}

				$request['dba']->executeUpdate("UPDATE ". K4TOPICS ." SET topic_type = ". TOPIC_ANNOUNCE .", topic_expire = 0 WHERE ". $query_extra);
				
				k4_bread_crumbs(&$request['template'], $request['dba'], 'L_ANNOUNCETOPICS', $forum);
				$action = new K4InformationAction(new K4LanguageElement('L_ANNOUNCEDTOPICS'), 'content', TRUE, referer(), 3);

				return $action->execute($request);
				
				break;
			}

			/**
			 * Feature topics
			 */
			case 'feature': {
				
				if($request['user']->get('perms') < get_map($request['user'], 'feature', 'can_add', array('forum_id' => $forum['id']))) {
					k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
					$request['template']->setFile('content', 'login_form.html');
					$request['template']->setVisibility('no_perms', TRUE);
					return TRUE;
				}

				$request['dba']->executeUpdate("UPDATE ". K4TOPICS ." SET is_feature = 1, topic_expire = 0 WHERE ". $query_extra);
				
				k4_bread_crumbs(&$request['template'], $request['dba'], 'L_FEATURETOPICS', $forum);
				$action = new K4InformationAction(new K4LanguageElement('L_FEATUREDTOPICS'), 'content', TRUE, referer(), 3);

				return $action->execute($request);

				break;
			}

			/**
			 * Remove any special formatting on topics
			 */
			case 'normal': {
				
				if($request['user']->get('perms') < get_map($request['user'], 'normalize', 'can_add', array('forum_id' => $forum['id']))) {
					k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
					$request['template']->setFile('content', 'login_form.html');
					$request['template']->setVisibility('no_perms', TRUE);
					return TRUE;
				}

				$request['dba']->executeUpdate("UPDATE ". K4TOPICS ." SET is_feature = 0, display = 1, queue = 0, topic_type = ". TOPIC_NORMAL .", topic_expire = 0, topic_locked = 0 WHERE ". $query_extra);
				
				k4_bread_crumbs(&$request['template'], $request['dba'], 'L_SETASNORMALTOPICS', $forum);
				$action = new K4InformationAction(new K4LanguageElement('L_NORMALIZEDTOPICS'), 'content', TRUE, referer(), 3);

				return $action->execute($request);

				break;
			}

			/**
			 * Insert the topics into the moderator's queue for checking
			 */
			case 'queue': {

				if($request['user']->get('perms') < get_map($request['user'], 'queue', 'can_add', array('forum_id' => $forum['id']))) {
					k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
					$request['template']->setFile('content', 'login_form.html');
					$request['template']->setVisibility('no_perms', TRUE);
					return TRUE;
				}

				$request['dba']->executeUpdate("UPDATE ". K4TOPICS ." SET queue = 1 WHERE ". $query_extra);
				
				k4_bread_crumbs(&$request['template'], $request['dba'], 'L_QUEUETOPICS', $forum);
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
						$subscribe->setInt(4, $forum['id']);
						$subscribe->setString(5, $request['user']->get('email'));
						$subscribe->setInt(6, $forum['category_id']);
						$subscribe->executeUpdate();
					}
				}

				k4_bread_crumbs(&$request['template'], $request['dba'], 'L_SUBSCRIPTION', $forum);
				$action = new K4InformationAction(new K4LanguageElement('L_SUBSCRIBEDTOPICS'), 'content', TRUE, referer(), 3);

				return $action->execute($request);

				break;
			}

			/**
			 * Add selected topics to the queue to be deleted
			 */
			case 'delete': {
				
				if($request['user']->get('perms') < get_map($request['user'], 'delete', 'can_add', array('forum_id' => $forum['id']))) {
					k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
					$request['template']->setFile('content', 'login_form.html');
					$request['template']->setVisibility('no_perms', TRUE);
					return TRUE;
				}
				
				$users			= array();

				// find the users for topics first
				$t				= &$request['dba']->executeQuery("SELECT * FROM ". K4TOPICS ." WHERE $query_extra");
				while($t->next()) {
					$temp		= $t->current();
					$users[$temp['poster_id']] = isset($users[$temp['poster_id']]) ? $users[$temp['poster_id']] + 1 : 1;
				}

				$num_topics		= $t->size;

				$t->free();
				
				// find them for replies
				$r				= &$request['dba']->executeQuery("SELECT * FROM ". K4REPLIES ." WHERE $query_extra");
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
				$request['dba']->executeUpdate("DELETE FROM ". K4INFO ." WHERE ". $query_extra);
				
				// update the forum and the datastore
				
				/* Get that last topic in this forum that's not one of these topics */
				$last_topic			= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['topic'] ." FROM ". K4TOPICS ." t LEFT JOIN ". K4INFO ." i ON t.topic_id = i.id WHERE (". str_replace('=', '<>', $move_extra) .") AND t.is_draft=0 AND t.queue=0 AND t.display=1 AND t.forum_id=". intval($forum['id']) ." ORDER BY i.created DESC LIMIT 1");
				$last_topic			= !$last_topic || !is_array($last_topic) ? array('created'=>0,'name'=>'','poster_name'=>'','id'=>0,'poster_id'=>0,'posticon'=>'') : $last_topic;
				
				/* Get that last post in this forum that's not part of/from one of these topics */
				$last_post			= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['reply'] ." FROM ". K4REPLIES ." r LEFT JOIN ". K4INFO ." i ON r.reply_id = i.id WHERE (". str_replace('=', '<>', str_replace('t.', 'r.', $move_extra)) .") AND r.forum_id=". intval($forum['id']) ." ORDER BY i.created DESC LIMIT 1");
				$last_post			= !$last_post || !is_array($last_post) ? $last_topic : $last_post;



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
				$forum_update->setInt(7, $last_topic['id']);
				$forum_update->setInt(8, $last_topic['poster_id']);
				$forum_update->setString(9, $last_topic['posticon']);
				$forum_update->setInt(10, $last_post['created']);
				$forum_update->setString(11, $last_post['name']);
				$forum_update->setString(12, $last_post['poster_name']);
				$forum_update->setInt(13, $last_post['id']);
				$forum_update->setInt(14, $last_post['poster_id']);
				$forum_update->setString(15, $last_post['posticon']);
				$forum_update->setInt(16, $forum['id']);
				
				/* Set the datastore values */
				$datastore					= $_DATASTORE['forumstats'];
				$datastore['num_topics']	= $_DBA->getValue("SELECT COUNT(*) FROM ". K4TOPICS ." WHERE is_draft = 0 AND queue = 0 AND display = 1") - 1;
				$datastore['num_replies']	= $_DBA->getValue("SELECT COUNT(*) FROM ". K4REPLIES ." WHERE is_draft = 0") - intval($num_replies);
				
				$datastore_update->setString(1, serialize($datastore));
				$datastore_update->setString(2, 'forumstats');
				
				/* Execute the forum and datastore update queries */
				$forum_update->executeUpdate();
				$datastore_update->executeUpdate();
				
				// change the file execution time on the datastore file
				if(!@touch(CACHE_DS_FILE, time()-86460)) {
					@unlink(CACHE_DS_FILE);
				}

				k4_bread_crumbs(&$request['template'], $request['dba'], 'L_DELETETOPICS', $forum);
				$action = new K4InformationAction(new K4LanguageElement('L_DELETEDTOPICS'), 'content', TRUE, referer(), 5);

				return $action->execute($request);

				break;
			}

			/**
			 * Move/copy topics to a destination forum
			 */
			case 'move': {
				
				if($request['user']->get('perms') < get_map($request['user'], 'move', 'can_add', array('forum_id' => $forum['id']))) {
					k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
					$request['template']->setFile('content', 'login_form.html');
					$request['template']->setVisibility('no_perms', TRUE);
					return TRUE;
				}

				if(count($topics) <= 0) {
					k4_bread_crumbs(&$request['template'], $request['dba'], 'L_MOVETOPICS', $forum);
					$action = new K4InformationAction(new K4LanguageElement('L_NEEDSELECTTOPIC'), 'content', FALSE);

					return $action->execute($request);
				}

				/* Get the topics */
				$result				= &$request['dba']->executeQuery("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['topic'] ." FROM ". K4TOPICS ." t LEFT JOIN ". K4INFO ." i ON t.topic_id = i.id WHERE t.is_draft=0 AND t.queue = 0 AND t.display = 1 AND i.row_type=". TOPIC ." AND t.forum_id = ". intval($forum['id']) ." AND (". $move_extra .") ORDER BY created DESC");
				
				/* Apply the topics iterator */
				$it					= &new TopicsIterator($request['dba'], $request['user'], $result, $request['template']->getVar('IMG_DIR'), $forum);
				$request['template']->setList('topics', $it);
				
				$request['template']->setVar('topics', $_REQUEST['topics']);
				$request['template']->setVar('forum_id', $forum['id']);

				$request['template']->setVar('modpanel', 1);

				k4_bread_crumbs(&$request['template'], $request['dba'], 'L_MOVETOPICS', $forum);
				$request['template']->setFile('content', 'move_topics.html');

				break;
			}

			/* Invalid action has been taken */
			default: {
				
				k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
				$action = new K4InformationAction(new K4LanguageElement('L_NEEDSELECTACTION'), 'content', FALSE);

				return $action->execute($request);
				return TRUE;
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
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INVALIDFORUM');
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);

			return $action->execute($request);
			return TRUE;
		}
		
		/* Check the other request ID */
		if(!isset($_REQUEST['forum']) || !$_REQUEST['forum'] || intval($_REQUEST['forum']) == 0) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INVALIDFORUM');
			$action = new K4InformationAction(new K4LanguageElement('L_DESTFORUMDOESNTEXIST'), 'content', FALSE);

			return $action->execute($request);
			return TRUE;
		}
			
		$forum				= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['forum'] ." FROM ". K4FORUMS ." f LEFT JOIN ". K4INFO ." i ON f.forum_id = i.id WHERE i.id = ". intval($_REQUEST['id']));
		$destination		= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['forum'] ." FROM ". K4FORUMS ." f LEFT JOIN ". K4INFO ." i ON f.forum_id = i.id WHERE i.id = ". intval($_REQUEST['forum']));

		/* Check the forum data given */
		if(!$forum || !is_array($forum) || empty($forum)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INVALIDFORUM');
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);

			return $action->execute($request);
			return TRUE;
		}
			
		/* Make sure the we are trying to post into a forum */
		if(!($forum['row_type'] & FORUM)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_CANTMODNONFORUM'), 'content', FALSE);

			return $action->execute($request);
			return TRUE;
		}

		/* Check the forum data given */
		if(!$destination || !is_array($destination) || empty($destination)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INVALIDFORUM');
			$action = new K4InformationAction(new K4LanguageElement('L_DESTFORUMDOESNTEXIST'), 'content', FALSE);

			return $action->execute($request);
			return TRUE;
		}
			
		/* Make sure the we are trying to post into a forum */
		if(!($destination['row_type'] & FORUM)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_CANTMODNONFORUM'), 'content', FALSE);

			return $action->execute($request);
			return TRUE;
		}

		/**
		 * Check for moderating permission
		 */
		
		if(!is_moderator($request['user'], $forum)) {
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
			$request['template']->setFile('content', 'login_form.html');
			$request['template']->setVisibility('no_perms', TRUE);
			return TRUE;
		}

		if(!is_moderator($request['user'], $destination)) {
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
			$request['template']->setFile('content', 'login_form.html');
			$request['template']->setVisibility('no_perms', TRUE);
			return TRUE;
		}

		if($request['user']->get('perms') < get_map($request['user'], 'move', 'can_add', array('forum_id' => $forum['id']))) {
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
			$request['template']->setFile('content', 'login_form.html');
			$request['template']->setVisibility('no_perms', TRUE);
			return TRUE;
		}

		if($request['user']->get('perms') < get_map($request['user'], 'move', 'can_add', array('forum_id' => $destination['id']))) {
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
			$request['template']->setFile('content', 'login_form.html');
			$request['template']->setVisibility('no_perms', TRUE);
			return TRUE;
		}
		
		if(!isset($_REQUEST['action']) || $_REQUEST['action'] == '') {
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_NEEDSELECTACTION'), 'content', FALSE);

			return $action->execute($request);
			return TRUE;
		}

		if(!isset($_REQUEST['topics']) || $_REQUEST['topics'] == '') {
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_NEESSELECTTOPICS'), 'content', FALSE);

			return $action->execute($request);
			return TRUE;
		}

		$topics		= explode("|", $_REQUEST['topics']);
		$query_extra	= '';
		$move_extra		= '';
		$i				= 0;
		foreach($topics as $id) {
			$query_extra .= $i == 0 ? ' ' : ' OR ';
			$query_extra .= 'topic_id = '. intval($id);
			$move_extra .= $i == 0 ? ' ' : ' OR ';
			$move_extra .= 't.topic_id = '. intval($id);
			
			$i++;
		}

		if(!is_array($topics) || count($topics) == 0) {
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_NEESSELECTTOPICS'), 'content', FALSE);

			return $action->execute($request);
			return TRUE;
		}
		
		switch($_REQUEST['action']) {
			case 'move': {
				
				// move the topics and replies
				$request['dba']->executeUpdate("UPDATE ". K4TOPICS ." SET forum_id = ". intval($destination['id']) .", category_id = ". intval($destination['category_id']) .", moved = 1 WHERE $query_extra");
				$request['dba']->executeUpdate("UPDATE ". K4REPLIES ." SET forum_id = ". intval($destination['id']) .", category_id = ". intval($destination['category_id']) ." WHERE $query_extra");
				
				$num_topics			= count($topics);
				
				// find the number of replies
				$r					= &$request['dba']->executeQuery("SELECT * FROM ". K4REPLIES ." WHERE $query_extra");
				$num_replies		= $r->numRows();				
				
				// get the last topic & reply in our initial forum
				$last_topic			= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['topic'] ." FROM ". K4TOPICS ." t LEFT JOIN ". K4INFO ." i ON t.topic_id = i.id WHERE t.is_draft=0 AND t.queue=0 AND t.display=1 AND t.forum_id=". intval($forum['id']) ." ORDER BY i.created DESC LIMIT 1");
				$last_topic			= !$last_topic || !is_array($last_topic) ? array('created'=>0,'name'=>'','poster_name'=>'','id'=>0,'poster_id'=>0,'posticon'=>'') : $last_topic;
				$last_post			= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['reply'] ." FROM ". K4REPLIES ." r LEFT JOIN ". K4INFO ." i ON r.reply_id = i.id WHERE r.forum_id=". intval($forum['id']) ." ORDER BY i.created DESC LIMIT 1");
				$last_post			= !$last_post || !is_array($last_post) ? $last_topic : $last_post;
				
				// Update this forum
				$forum_update		= &$request['dba']->prepareStatement("UPDATE ". K4FORUMS ." SET topics=topics-?,posts=posts-?,replies=replies-?,topic_created=?,topic_name=?,topic_uname=?,topic_id=?,topic_uid=?,topic_posticon=?,post_created=?,post_name=?,post_uname=?,post_id=?,post_uid=?,post_posticon=? WHERE forum_id=?");
					
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
				$forum_update->setInt(16, $forum['id']);
				
				$forum_update->executeUpdate();

				unset($last_topic, $last_post, $forum_update);
				
				// get the last topic & reply in our destination forum
				$last_topic			= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['topic'] ." FROM ". K4TOPICS ." t LEFT JOIN ". K4INFO ." i ON t.topic_id = i.id WHERE t.is_draft=0 AND t.queue=0 AND t.display=1 AND t.forum_id=". intval($destination['id']) ." ORDER BY i.created DESC LIMIT 1");
				$last_topic			= !$last_topic || !is_array($last_topic) ? array('created'=>0,'name'=>'','poster_name'=>'','id'=>0,'poster_id'=>0,'posticon'=>'') : $last_topic;
				$last_post			= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['reply'] ." FROM ". K4REPLIES ." r LEFT JOIN ". K4INFO ." i ON r.reply_id = i.id WHERE r.forum_id=". intval($destination['id']) ." ORDER BY i.created DESC LIMIT 1");
				$last_post			= !$last_post || !is_array($last_post) ? $last_topic : $last_post;

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
				$forum_update->setInt(16, $destination['id']);
				
				$forum_update->executeUpdate();

				// we're done	
				k4_bread_crumbs(&$request['template'], $request['dba'], 'L_MOVECOPYTOPICS', $forum);
				$action = new K4InformationAction(new K4LanguageElement('L_MOVEDTOPICS', $forum['name'], $destination['name']), 'content', FALSE, 'viewforum.php?id='. $destination['id'], 3);

				return $action->execute($request);

				break;
			}
			case 'movetrack': {
				trigger_error("Not finished.", E_USER_ERROR);
				// copy the topics and replies (hefty queries)
				//$request['dba']->executeUpdate("UPDATE ". K4TOPICS ." SET forum_id = ". intval($destination['id']) .", category_id = ". intval($destination['category_id']) .", moved = 1 WHERE $query_extra");
				//$request['dba']->executeUpdate("UPDATE ". K4REPLIES ." SET forum_id = ". intval($destination['id']) .", category_id = ". intval($destination['category_id']) ." WHERE $query_extra");
								
				// find the topics
				$t					= &$request['dba']->executeQuery("SELECT * FROM ". K4TOPICS ." WHERE $query_extra");
				while($t->next()) {
					$temp			= $t->current();

					//$request['dba']->executeUpdate("");
					//$request['dba']->executeUpdate("");
				}
				$num_topics			= $t->numRows();

				// find the replies
				$r					= &$request['dba']->executeQuery("SELECT * FROM ". K4REPLIES ." WHERE $query_extra");
				$num_replies		= $r->numRows();				
				
				// get the last topic & reply in our initial forum
				$last_topic			= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['topic'] ." FROM ". K4TOPICS ." t LEFT JOIN ". K4INFO ." i ON t.topic_id = i.id WHERE t.is_draft=0 AND t.queue=0 AND t.display=1 AND t.forum_id=". intval($forum['id']) ." ORDER BY i.created DESC LIMIT 1");
				$last_topic			= !$last_topic || !is_array($last_topic) ? array('created'=>0,'name'=>'','poster_name'=>'','id'=>0,'poster_id'=>0,'posticon'=>'') : $last_topic;
				$last_post			= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['reply'] ." FROM ". K4REPLIES ." r LEFT JOIN ". K4INFO ." i ON r.reply_id = i.id WHERE r.forum_id=". intval($forum['id']) ." ORDER BY i.created DESC LIMIT 1");
				$last_post			= !$last_post || !is_array($last_post) ? $last_topic : $last_post;
				
				// Update this forum
				$forum_update		= &$request['dba']->prepareStatement("UPDATE ". K4FORUMS ." SET topics=topics-?,posts=posts-?,replies=replies-?,topic_created=?,topic_name=?,topic_uname=?,topic_id=?,topic_uid=?,topic_posticon=?,post_created=?,post_name=?,post_uname=?,post_id=?,post_uid=?,post_posticon=? WHERE forum_id=?");
					
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
				$forum_update->setInt(16, $forum['id']);
				
				$forum_update->executeUpdate();

				unset($last_topic, $last_post, $forum_update);
				
				// get the last topic & reply in our destination forum
				$last_topic			= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['topic'] ." FROM ". K4TOPICS ." t LEFT JOIN ". K4INFO ." i ON t.topic_id = i.id WHERE t.is_draft=0 AND t.queue=0 AND t.display=1 AND t.forum_id=". intval($destination['id']) ." ORDER BY i.created DESC LIMIT 1");
				$last_topic			= !$last_topic || !is_array($last_topic) ? array('created'=>0,'name'=>'','poster_name'=>'','id'=>0,'poster_id'=>0,'posticon'=>'') : $last_topic;
				$last_post			= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['reply'] ." FROM ". K4REPLIES ." r LEFT JOIN ". K4INFO ." i ON r.reply_id = i.id WHERE r.forum_id=". intval($destination['id']) ." ORDER BY i.created DESC LIMIT 1");
				$last_post			= !$last_post || !is_array($last_post) ? $last_topic : $last_post;

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
				$forum_update->setInt(16, $destination['id']);
				
				$forum_update->executeUpdate();

				// we're done	
				k4_bread_crumbs(&$request['template'], $request['dba'], 'L_MOVECOPYTOPICS', $forum);
				$action = new K4InformationAction(new K4LanguageElement('L_MOVEDTOPICS', $forum['name'], $destination['name']), 'content', FALSE, 'viewforum.php?id='. $destination['id'], 3);

				return $action->execute($request);

				break;
			}
			case 'copy': {
				trigger_error("Not finished.", E_USER_ERROR);

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

class SimpleUpdateTopic extends FAAction {
	function execute(&$request) {

		global $_QUERYPARAMS, $_DATASTORE;

		/* Check the request ID */
		if(!isset($_REQUEST['forum_id']) || !$_REQUEST['forum_id'] || intval($_REQUEST['forum_id']) == 0) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INVALIDFORUM');
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);

			return $action->execute($request);
			return TRUE;
		}
			
		$forum				= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['forum'] ." FROM ". K4FORUMS ." f LEFT JOIN ". K4INFO ." i ON f.forum_id = i.id WHERE i.id = ". intval($_REQUEST['forum_id']));
		
		/* Check the forum data given */
		if(!$forum || !is_array($forum) || empty($forum)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INVALIDFORUM');
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);

			return $action->execute($request);
			return TRUE;
		}
			
		/* Make sure the we are trying to edit in a forum */
		if(!($forum['row_type'] & FORUM)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_CANTEDITTONONFORUM'), 'content', FALSE);

			return $action->execute($request);
			return TRUE;
		}
		
		if(!isset($_REQUEST['id']) || !$_REQUEST['id'] || intval($_REQUEST['id']) == 0) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INVALIDTOPIC');
			$action = new K4InformationAction(new K4LanguageElement('L_TOPICDOESNTEXIST'), 'content', FALSE);

			return $action->execute($request);
			return TRUE;
		}			

		/* Get our topic */
		$topic				= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['topic'] ." FROM ". K4TOPICS ." t LEFT JOIN ". K4INFO ." i ON t.topic_id = i.id WHERE i.id = ". intval($_REQUEST['id']));
		
		if(!$topic || !is_array($topic) || empty($topic)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INVALIDTOPIC');
			$action = new K4InformationAction(new K4LanguageElement('L_TOPICDOESNTEXIST'), 'content', FALSE);

			return $action->execute($request);

			return TRUE;
		}

		if(!isset($_REQUEST['name']) || $_REQUEST['name'] == '') {
			$name	= $topic['name'];
		} else {
			$name	= $_REQUEST['name'];
		}
		
		if(!is_moderator($request['user'], $forum)) {
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
			$request['template']->setFile('content', 'login_form.html');
			$request['template']->setVisibility('no_perms', TRUE);
			return TRUE;
		}

		if($topic['poster_id'] == $request['user']->get('id')) {
			if($request['user']->get('perms') < get_map($request['user'], 'topics', 'can_edit', array('forum_id' => $forum['id']))) {
				k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
				$request['template']->setFile('content', 'login_form.html');
				$request['template']->setVisibility('no_perms', TRUE);
				return TRUE;
			}
		} else {
			if($request['user']->get('perms') < get_map($request['user'], 'other_topics', 'can_edit', array('forum_id' => $forum['id']))) {
				k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
				$request['template']->setFile('content', 'login_form.html');
				$request['template']->setVisibility('no_perms', TRUE);
				return TRUE;
			}
		}
		
		$update_a		= &$request['dba']->prepareStatement("UPDATE ". K4INFO ." SET name=? WHERE id=?");
		$update_b		= &$request['dba']->prepareStatement("UPDATE ". K4TOPICS ." SET edited_time=?,edited_username=?,edited_userid=? WHERE topic_id=?");
		
		$update_a->setString(1, $name);
		$update_a->setInt(2, $topic['id']);

		$update_b->setInt(1, time());
		$update_b->setString(2, $request['user']->get('name'));
		$update_b->setInt(3, $request['user']->get('id'));
		$update_b->setInt(4, $topic['id']);

		$update_a->executeUpdate();
		$update_b->executeUpdate();
		
		if($forum['topic_id'] == $topic['id']) {
			$update_c	= &$request['dba']->prepareStatement("UPDATE ". K4FORUMS ." SET topic_name=? WHERE forum_id=?");
			$update_c->setString(1, $name);
			$update_c->setInt(2, $forum['id']);
			$update_c->executeUpdate();
		}

		if($forum['post_id'] == $topic['id']) {
			$update_d	= &$request['dba']->prepareStatement("UPDATE ". K4FORUMS ." SET post_name=? WHERE forum_id=?");
			$update_d->setString(1, $name);
			$update_d->setInt(2, $forum['id']);
			$update_d->executeUpdate();
		}

		k4_bread_crumbs(&$request['template'], $request['dba'], 'L_EDITTOPIC', $forum);
		$action = new K4InformationAction(new K4LanguageElement('L_UPDATEDTOPIC', $topic['name']), 'content', FALSE, referer(), 3);

		return $action->execute($request);

		return TRUE;
	}
}

?>