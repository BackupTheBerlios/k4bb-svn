<?php
/**
* k4 Bulletin Board, replies.class.php
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
* @author Geoffrey Goodman
* @author James Logsdon
* @version $Id: replies.class.php,v 1.10 2005/05/26 18:35:44 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	exit;
}

/**
 * Post / Preview a reply
 */
class PostReply extends FAAction {
	function getNumOnLevel($row_left, $row_right, $level) {
		return $this->dba->GetValue("SELECT COUNT(*) FROM ". K4INFO ." WHERE row_left > $row_left AND row_right < $row_right AND row_level = $level");
	}
	function execute(&$request) {
		
		global $_QUERYPARAMS, $_DATASTORE, $_SETTINGS;

		$this->dba			= &$request['dba'];
		
		/* Prevent post flooding */
		$last_topic		= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['topic'] ." FROM ". K4TOPICS ." t LEFT JOIN ". K4INFO ." i ON t.topic_id = i.id WHERE t.poster_ip = '". USER_IP ."' ORDER BY i.created DESC LIMIT 1");
		$last_reply		= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['reply'] ." FROM ". K4REPLIES ." r LEFT JOIN ". K4INFO ." i ON r.reply_id = i.id WHERE r.poster_ip = '". USER_IP ."' ORDER BY i.created DESC LIMIT 1");
		
		if(is_array($last_topic) && !empty($last_topic)) {
			if(intval($last_topic['created']) + POST_IMPULSE_LIMIT > time()) {
				/* set the breadcrumbs bit */
				k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
				$action = new K4InformationAction(new K4LanguageElement('L_MUSTWAITSECSTOPOST'), 'content', TRUE);

				return $action->execute($request);
				return TRUE;
			}
		}

		if(is_array($last_reply) && !empty($last_reply)) {
			if(intval($last_reply['created']) + POST_IMPULSE_LIMIT > time()) {
				/* set the breadcrumbs bit */
				k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
				$action = new K4InformationAction(new K4LanguageElement('L_MUSTWAITSECSTOPOST'), 'content', TRUE);

				return $action->execute($request);
				return TRUE;
			}
		}

		/* Check the request ID */
		if(!isset($_REQUEST['topic_id']) || !$_REQUEST['topic_id'] || intval($_REQUEST['topic_id']) == 0) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INVALIDTOPIC');
			$action = new K4InformationAction(new K4LanguageElement('L_TOPICDOESNTEXIST'), 'content', FALSE);

			return $action->execute($request);
			return TRUE;
		}

		/* Get our topic */
		$topic				= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['topic'] ." FROM ". K4TOPICS ." t LEFT JOIN ". K4INFO ." i ON t.topic_id = i.id WHERE i.id = ". intval($_REQUEST['topic_id']));
		
		if(!$topic || !is_array($topic) || empty($topic)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INVALIDTOPIC');
			$action = new K4InformationAction(new K4LanguageElement('L_TOPICDOESNTEXIST'), 'content', FALSE);

			return $action->execute($request);

			return TRUE;
		}
			
		$forum				= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['forum'] ." FROM ". K4FORUMS ." f LEFT JOIN ". K4INFO ." i ON f.forum_id = i.id WHERE i.id = ". intval($topic['forum_id']));
		
		/* Check the forum data given */
		if(!$forum || !is_array($forum) || empty($forum)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INVALIDFORUM');
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);

			return $action->execute($request);
			return TRUE;
		}
			
		/* Make sure the we are trying to delete from a forum */
		if(!($forum['row_type'] & FORUM)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_CANTDELFROMNONFORUM'), 'content', FALSE);

			return $action->execute($request);
			return TRUE;
		}

		/* Do we have permission to post to this topic in this forum? */
		if($request['user']->get('perms') < get_map($request['user'], 'replies', 'can_add', array('forum_id'=>$forum['id']))) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_PERMCANTPOST'), 'content', FALSE);
			return $action->execute($request);		
		}

		/* Does this user have permission to reply to this topic if it is locked? */
		if($topic['topic_locked'] == 1 && get_map($request['user'], 'closed', 'can_add', array('forum_id' => $forum['id'])) > $request['user']->get('perms')) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

			return $action->execute($request);
			return TRUE;
		}

		if(isset($_REQUEST['parent_id']) && intval($_REQUEST['parent_id']) != 0) {
			$reply				= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['reply'] ." FROM ". K4REPLIES ." r LEFT JOIN ". K4INFO ." i ON r.reply_id = i.id WHERE i.id = ". intval($_REQUEST['parent_id']));
			
			if(!$reply || !is_array($reply) || empty($reply)) {
				/* set the breadcrumbs bit */
				k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INVALIDREPLY');
				
				$action = new K4InformationAction(new K4LanguageElement('L_REPLYDOESNTEXIST'), 'content', FALSE);
				return $action->execute($request);
				
				return TRUE;
			}
		}

		$parent					= isset($reply) && is_array($reply) ? $reply : $topic;

		/* Do we have permission to post to this forum? */
		if($request['user']->get('perms') < get_map($request['user'], 'topics', 'can_add', array('forum_id'=>$forum['id']))) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_PERMCANTPOST'), 'content', FALSE);

			return $action->execute($request);
			return TRUE;
		}

		/* General error checking */
		if(!isset($_REQUEST['name']) || $_REQUEST['name'] == '') {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION', $forum);
			$action = new K4InformationAction(new K4LanguageElement('L_INSERTTOPICNAME'), 'content', TRUE);

			return $action->execute($request);
			return TRUE;
		}
		if(!isset($_REQUEST['message']) || $_REQUEST['message'] == '') {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION', $forum);
			$action = new K4InformationAction(new K4LanguageElement('L_INSERTTOPICMESSAGE'), 'content', TRUE);

			return $action->execute($request);
			return TRUE;
		}
				
		/**
		 * Start building info for the queries
		 */
		
//		if(K4MPTT) {		
//			/* Find out how many nodes are on the current level */
//			$num_on_level		= $this->getNumOnLevel($parent['row_left'], $parent['row_right'], $parent['row_level']+1);
//			
//			/* If there are more than 1 nodes on the current level */
//			if($num_on_level > 0) {
//				$left			= $parent['row_right'];
//			} else {
//				$left			= $parent['row_left'] + 1;
//			}
//	
//			$right				= $left+1;
//		} else {
//			$left				= 0;
//			$right				= 0;
//		}

		/* Set this nodes level */
		$level				= $parent['row_level']+1;
		
		/* Set the topic created time */
		$created			= time();
		
		/* Initialize the bbcode parser with the topic message */
		$_REQUEST['message']	= substr($_REQUEST['message'], 0, $_SETTINGS['postmaxchars']);
		$bbcode	= &new BBCodex(&$request['dba'], $request['user']->getInfoArray(), $_REQUEST['message'], $forum['id'], 
			iif((isset($_REQUEST['disable_html']) && $_REQUEST['disable_html'] == 'on'), FALSE, TRUE), 
			iif((isset($_REQUEST['disable_bbcode']) && $_REQUEST['disable_bbcode'] == 'on'), FALSE, TRUE), 
			iif((isset($_REQUEST['disable_emoticons']) && $_REQUEST['disable_emoticons'] == 'on'), FALSE, TRUE), 
			iif((isset($_REQUEST['disable_aurls']) && $_REQUEST['disable_aurls'] == 'on'), FALSE, TRUE));
		
		/* Parse the bbcode */
		$body_text = $bbcode->parse();
		
		if($_REQUEST['submit'] == $request['template']->getVar('L_SUBMIT')) {
						
			/**
			 * Build the queries
			 */
			
			$request['dba']->beginTransaction();

			/* Prepare the queries */
			
//			if(K4MPTT) {
//				$update_a			= &$request['dba']->prepareStatement("UPDATE ". K4INFO ." SET row_right = row_right+2 WHERE row_left < ? AND row_right >= ?");
//				$update_b			= &$request['dba']->prepareStatement("UPDATE ". K4INFO ." SET row_left = row_left+2, row_right=row_right+2 WHERE row_left >= ?");
//				
//				/* Set the insert variables needed */
//				$update_a->setInt(1, $left);
//				$update_a->setInt(2, $left);
//				$update_b->setInt(1, $left);
//	
//				$update_a->executeUpdate();
//				$update_b->executeUpdate();
//			}
			
			$insert_a			= &$request['dba']->prepareStatement("INSERT INTO ". K4INFO ." (name,parent_id,row_type,row_level,created) VALUES (?,?,?,?,?)");
			$insert_b			= &$request['dba']->prepareStatement("INSERT INTO ". K4REPLIES ." (reply_id,topic_id,forum_id,category_id,poster_name,poster_id,poster_ip,body_text,posticon,disable_html,disable_bbcode,disable_emoticons,disable_sig,disable_areply,disable_aurls) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
			
			
			/* Set the inserts for adding the actual node */
			$insert_a->setString(1, htmlentities($_REQUEST['name'], ENT_QUOTES));
			$insert_a->setInt(2, $parent['id']);
			$insert_a->setInt(3, REPLY);
			$insert_a->setInt(4, $level);
			$insert_a->setInt(5, $created);
			
			/* Add the main topic information to the database */
			$insert_a->executeUpdate();

			$poster_name		= iif($request['user']->get('id') <= 0, htmlentities((isset($_REQUEST['poster_name']) ? $_REQUEST['poster_name'] : '') , ENT_QUOTES), $request['user']->get('name'));

			$reply_id			= $request['dba']->getInsertId();
			
			//topic_id,forum_id,category_id,poster_name,poster_id,body_text,posticon
			//disable_html,disable_bbcode,disable_emoticons,disable_sig,disable_areply,disable_aurls,is_draft
			$insert_b->setInt(1, $reply_id);
			$insert_b->setInt(2, $topic['id']);
			$insert_b->setInt(3, $forum['id']);
			$insert_b->setInt(4, $forum['category_id']);
			$insert_b->setString(5, $poster_name);
			$insert_b->setInt(6, $request['user']->get('id'));
			$insert_b->setString(7, USER_IP);
			$insert_b->setString(8, $body_text);
			$insert_b->setString(9, iif(($request['user']->get('perms') >= get_map($request['user'], 'posticons', 'can_add', array('forum_id'=>$forum['id']))), (isset($_REQUEST['posticon']) ? $_REQUEST['posticon'] : 'clear.gif'), 'clear.gif'));
			$insert_b->setInt(10, iif((isset($_REQUEST['disable_html']) && $_REQUEST['disable_html'] == 'on'), 1, 0));
			$insert_b->setInt(11, iif((isset($_REQUEST['disable_bbcode']) && $_REQUEST['disable_bbcode'] == 'on'), 1, 0));
			$insert_b->setInt(12, iif((isset($_REQUEST['disable_emoticons']) && $_REQUEST['disable_emoticons'] == 'on'), 1, 0));
			$insert_b->setInt(13, iif((isset($_REQUEST['enable_sig']) && $_REQUEST['enable_sig'] == 'on'), 0, 1));
			$insert_b->setInt(14, iif((isset($_REQUEST['disable_areply']) && $_REQUEST['disable_areply'] == 'on'), 1, 0));
			$insert_b->setInt(15, iif((isset($_REQUEST['disable_aurls']) && $_REQUEST['disable_aurls'] == 'on'), 1, 0));

			$insert_b->executeUpdate();
			
			/** 
			 * Update the forum, and update the datastore 
			 */

			//topic_created,topic_name,topic_uname,topic_id,topic_uid,post_created,post_name,post_uname,post_id,post_uid
			$forum_update		= &$request['dba']->prepareStatement("UPDATE ". K4FORUMS ." SET replies=replies+1,posts=posts+1,post_created=?,post_name=?,post_uname=?,post_id=?,post_uid=?,post_posticon=? WHERE forum_id=?");
			$topic_update		= &$request['dba']->prepareStatement("UPDATE ". K4TOPICS ." SET num_replies=num_replies+1,reply_time=?,reply_uname=?,reply_id=?,reply_uid=? WHERE topic_id=?");
			$datastore_update	= &$request['dba']->prepareStatement("UPDATE ". K4DATASTORE ." SET data=? WHERE varname=?");
			$request['dba']->executeUpdate("UPDATE ". K4USERINFO ." SET num_posts=num_posts+1,total_posts=total_posts+1 WHERE user_id=". intval($request['user']->get('id')));
			
			/* Update the forums and datastore tables */

			/* Set the forum values */
			$forum_update->setInt(1, $created);
			$forum_update->setString(2, htmlentities($_REQUEST['name'], ENT_QUOTES));
			$forum_update->setString(3, $poster_name);
			$forum_update->setInt(4, $reply_id);
			$forum_update->setInt(5, $request['user']->get('id'));
			$forum_update->setString(6, iif(($request['user']->get('perms') >= get_map($request['user'], 'posticons', 'can_add', array('forum_id'=>$forum['id']))), (isset($_REQUEST['posticon']) ? $_REQUEST['posticon'] : 'clear.gif'), 'clear.gif'));
			$forum_update->setInt(7, $forum['id']);

			/* Set the topic values */
			$topic_update->setInt(1, $created);
			$topic_update->setString(2, $poster_name);
			$topic_update->setInt(3, $reply_id);
			$topic_update->setInt(4, $request['user']->get('id'));
			$topic_update->setInt(5, $topic['id']);
			
			/* Set the datastore values */
			$datastore					= $_DATASTORE['forumstats'];
			$datastore['num_replies']	= $request['dba']->getValue("SELECT COUNT(*) FROM ". K4REPLIES ." WHERE is_draft = 0");
			
			$datastore_update->setString(1, serialize($datastore));
			$datastore_update->setString(2, 'forumstats');
			
			/**
			 * Update the forums table and datastore table
			 */
			$forum_update->executeUpdate();
			$topic_update->executeUpdate();
			$datastore_update->executeUpdate();
			
			/* Added the reply */
			if(!@touch(CACHE_DS_FILE, time()-86460)) {
				@unlink(CACHE_DS_FILE);
			}
			
			set_send_reply_mail($topic['id'], iif($poster_name == '', $request['template']->getVar('L_GUEST'), $poster_name));
			
			/**
			 * Subscribe this user to the topic
			 */
			$is_subscribed		= $request['dba']->getRow("SELECT * FROM ". K4SUBSCRIPTIONS ." WHERE user_id = ". intval($request['user']->get('id')) ." AND topic_id = ". intval($topic['id']));
			if(isset($_REQUEST['disable_areply']) && $_REQUEST['disable_areply'] == 'on') {
				if(!is_array($is_subscribed) || empty($is_subscribed)) {
					$subscribe			= &$request['dba']->prepareStatement("INSERT INTO ". K4SUBSCRIPTIONS ." (user_id,user_name,topic_id,forum_id,email,category_id) VALUES (?,?,?,?,?,?)");
					$subscribe->setInt(1, $request['user']->get('id'));
					$subscribe->setString(2, $request['user']->get('name'));
					$subscribe->setInt(3, $topic['id']);
					$subscribe->setInt(4, $forum['id']);
					$subscribe->setString(5, $request['user']->get('email'));
					$subscribe->setInt(6, $forum['category_id']);
					$subscribe->executeUpdate();
				}
			}
			
			/* Commit the current transaction */
			$request['dba']->commitTransaction();
			
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_POSTREPLY', $parent, $forum);
			
			/* Redirect the user */
			$action = new K4InformationAction(new K4LanguageElement('L_ADDEDREPLY', htmlentities($_REQUEST['name'], ENT_QUOTES), $topic['name']), 'content', FALSE, 'findpost.php?id='. $reply_id, 3);

			return $action->execute($request);

		} else {
			
			/**
			 * Post Previewing
			 */
			
			/* Get and set the emoticons and post icons to the template */
			$emoticons	= &$request['dba']->executeQuery("SELECT * FROM ". K4EMOTICONS ." WHERE clickable = 1");
			$posticons	= &$request['dba']->executeQuery("SELECT * FROM ". K4POSTICONS);

			$request['template']->setList('emoticons', $emoticons);
			$request['template']->setList('posticons', $posticons);

			$request['template']->setVar('emoticons_per_row', $request['template']->getVar('smcolumns'));
			$request['template']->setVar('emoticons_per_row_remainder', $request['template']->getVar('smcolumns')-1);
			
			topic_post_options(&$request['template'], &$request['user'], $forum);

			/* Set the forum info to the template */
			foreach($forum as $key => $val)
				$request['template']->setVar('forum_'. $key, $val);
			
			/* Set template information for this iterator */								
			$reply_preview	= array(
								'name' => htmlentities($_REQUEST['name'], ENT_QUOTES),
								'body_text' => $body_text,
								'poster_name' => $request['user']->get('name'),
								'poster_id' => $request['user']->get('id'),
								'forum_id' => $forum['id'],
								'topic_id' => $topic['id'],
								'row_left' => 0,
								'row_right' => 0,
								'posticon' => iif(($request['user']->get('perms') >= get_map($request['user'], 'posticons', 'can_add', array('forum_id'=>$forum['id']))), (isset($_REQUEST['posticon']) ? $_REQUEST['posticon'] : 'clear.gif'), 'clear.gif'),
								'disable_html' => iif((isset($_REQUEST['disable_html']) && $_REQUEST['disable_html'] == 'on'), 1, 0),
								'disable_sig' => iif((isset($_REQUEST['enable_sig']) && $_REQUEST['enable_sig'] == 'on'), 0, 1),
								'disable_bbcode' => iif((isset($_REQUEST['disable_bbcode']) && $_REQUEST['disable_bbcode'] == 'on'), 1, 0),
								'disable_emoticons' => iif((isset($_REQUEST['disable_emoticons']) && $_REQUEST['disable_emoticons'] == 'on'), 1, 0),
								'disable_areply' => iif((isset($_REQUEST['disable_areply']) && $_REQUEST['disable_areply'] == 'on'), 1, 0),
								'disable_aurls' => iif((isset($_REQUEST['disable_aurls']) && $_REQUEST['disable_aurls'] == 'on'), 1, 0)
								);

			/* Add the reply information to the template (same as for topics) */
			$reply_iterator = &new TopicIterator($request['dba'], $request['user'], $reply_preview, FALSE);
			$request['template']->setList('topic', $reply_iterator);
			
			/* Assign the topic preview values to the template */
			$reply_preview['body_text'] = $_REQUEST['message'];
			
			foreach($reply_preview as $key => $val)
				$request['template']->setVar('reply_'. $key, $val);
			
			/* Set the the button display options */
			$request['template']->setVisibility('edit_reply', TRUE);
			
			/* Set the form actiob */
			$request['template']->setVar('newreply_act', 'newreply.php?act=postreply');			

			/* Set the appropriate parent id */
			if(isset($reply)) {
				$request['template']->setVisibility('parent_id', TRUE);
				$request['template']->setVar('parent_id', $parent['id']);
			}

			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_POSTREPLY', $parent, $forum);
			
			/* Get replies that are above this point */
			$replies	= &$request['dba']->executeQuery("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['reply'] ." FROM ". K4INFO ." i LEFT JOIN ". K4REPLIES ." r ON i.id = r.reply_id WHERE r.topic_id = ". intval($topic['id']) ." ORDER BY i.created DESC LIMIT 10");

			$request['template']->setList('topic_review', new TopicReviewIterator($request['dba'], $topic, $replies, $request['user']->getInfoArray));
			
			foreach($parent as $key => $val)
				$request['template']->setVar('parent_'. $key, $val);

			/* Set the post topic form */
			$request['template']->setFile('preview', 'post_preview.html');
			$request['template']->setFile('content', 'newreply.html');
		}

		return TRUE;
	}
}

/**
 * Edit a reply
 */
class EditReply extends FAAction {
	function execute(&$request) {
		
		global $_QUERYPARAMS, $_DATASTORE;
		
		/* Get our reply */
		$reply				= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['reply'] ." FROM ". K4REPLIES ." r LEFT JOIN ". K4INFO ." i ON r.reply_id = i.id WHERE i.id = ". intval($_REQUEST['id']));
		
		if(!$reply || !is_array($reply) || empty($reply)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INVALIDREPLY');
			$action = new K4InformationAction(new K4LanguageElement('L_REPLYDOESNTEXIST'), 'content', FALSE);

			return $action->execute($request);

			return TRUE;
		}

		$topic				= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['topic'] ." FROM ". K4TOPICS ." t LEFT JOIN ". K4INFO ." i ON t.topic_id = i.id WHERE i.id = ". intval($reply['topic_id']));
		
		if(!$topic || !is_array($topic) || empty($topic)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INVALIDTOPIC');
			$action = new K4InformationAction(new K4LanguageElement('L_TOPICDOESNTEXIST'), 'content', FALSE);

			return $action->execute($request);

			return TRUE;
		}
		
		$forum				= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['forum'] ." FROM ". K4FORUMS ." f LEFT JOIN ". K4INFO ." i ON f.forum_id = i.id WHERE i.id = ". intval($reply['forum_id']));
		
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
			$action = new K4InformationAction(new K4LanguageElement('L_CANTPOSTTONONFORUM'), 'content', FALSE);

			return $action->execute($request);
			return TRUE;
		}
		
		/* Does this user have permission to edit theirreply if the topic is locked? */
		if($topic['topic_locked'] == 1 && get_map($request['user'], 'closed', 'can_edit', array('forum_id' => $forum['id'])) > $request['user']->get('perms')) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

			return $action->execute($request);
			return TRUE;
		}

		/* set the breadcrumbs bit */
		k4_bread_crumbs(&$request['template'], $request['dba'], 'L_EDITREPLY', $topic, $forum);
		
		if($reply['poster_id'] == $request['user']->get('id')) {
			if(get_map($request['user'], 'replies', 'can_edit', array('forum_id'=>$forum['id'])) > $request['user']->get('perms')) {
				$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

				return $action->execute($request);
				return TRUE;
			}
		} else {
			if(get_map($request['user'], 'other_replies', 'can_edit', array('forum_id'=>$forum['id'])) > $request['user']->get('perms')) {
				$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

				return $action->execute($request);
				return TRUE;
			}
		}
		
		$bbcode				= &new BBCodex(&$request['dba'], $request['user']->getInfoArray(), $reply['body_text'], $forum['id'], TRUE, TRUE, TRUE, TRUE);
		
		/* Get and set the emoticons and post icons to the template */
		$emoticons			= &$request['dba']->executeQuery("SELECT * FROM ". K4EMOTICONS ." WHERE clickable = 1");
		$posticons			= &$request['dba']->executeQuery("SELECT * FROM ". K4POSTICONS);

		$request['template']->setList('emoticons', $emoticons);
		$request['template']->setList('posticons', $posticons);

		$request['template']->setVar('emoticons_per_row', $request['template']->getVar('smcolumns'));
		$request['template']->setVar('emoticons_per_row_remainder', $request['template']->getVar('smcolumns')-1);
		
		/* Get the posting options */
		topic_post_options(&$request['template'], &$request['user'], $forum);
		
		$reply['body_text'] = $bbcode->revert();

		foreach($reply as $key => $val)
			$request['template']->setVar('reply_'. $key, $val);
		
		/* Assign the forum information to the template */
		foreach($forum as $key => $val)
			$request['template']->setVar('forum_'. $key, $val);

		/* Set the the button display options */
		$request['template']->setVisibility('edit_reply', TRUE);
		$request['template']->setVisibility('reply_id', TRUE);
		$request['template']->setVisibility('post_reply', FALSE);
		$request['template']->setVisibility('edit_post', TRUE);
		
		/* Set the form actiob */
		$request['template']->setVar('newreply_act', 'newreply.php?act=updatereply');
		
		/* Get 10 replies that are above this reply */
		$replies	= &$request['dba']->executeQuery("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['reply'] ." FROM ". K4REPLIES ." r LEFT JOIN ". K4INFO ." i ON i.id = r.reply_id WHERE r.topic_id = ". intval($topic['id']) ." AND i.id < ". intval($reply['id']) ." AND i.row_type = ". REPLY ." ORDER BY i.created DESC LIMIT 10");

		/* Set the topic preview for this reply editing */
		$request['template']->setList('topic_review', new TopicReviewIterator($request['dba'], $topic, $replies, $request['user']->getInfoArray()));

		/* set the breadcrumbs bit */
		k4_bread_crumbs(&$request['template'], $request['dba'], 'L_EDITREPLY', $topic, $forum);
		
		/* Set the post topic form */
		$request['template']->setFile('preview', 'post_preview.html');
		$request['template']->setFile('content', 'newreply.html');

		return TRUE;
	}
}

/**
 * Update a reply
 */
class UpdateReply extends FAAction {
	function execute(&$request) {
		
		global $_QUERYPARAMS, $_DATASTORE, $_SETTINGS;

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

		/* General error checking */
		if(!isset($_REQUEST['name']) || $_REQUEST['name'] == '') {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION', $forum);
			$action = new K4InformationAction(new K4LanguageElement('L_INSERTTOPICNAME'), 'content', TRUE);

			return $action->execute($request);
			return TRUE;
		}
		if(!isset($_REQUEST['message']) || $_REQUEST['message'] == '') {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION', $forum);
			$action = new K4InformationAction(new K4LanguageElement('L_INSERTTOPICMESSAGE'), 'content', TRUE);

			return $action->execute($request);
			return TRUE;
		}
		
		/* Get our topic and our reply */
		$topic				= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['topic'] ." FROM ". K4TOPICS ." t LEFT JOIN ". K4INFO ." i ON t.topic_id = i.id WHERE i.id = ". intval($_REQUEST['topic_id']));
		$reply				= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['reply'] ." FROM ". K4REPLIES ." r LEFT JOIN ". K4INFO ." i ON r.reply_id = i.id WHERE i.id = ". intval($_REQUEST['reply_id']));
		
		if(!$topic || !is_array($topic) || empty($topic)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INVALIDTOPIC');
			$action = new K4InformationAction(new K4LanguageElement('L_TOPICDOESNTEXIST'), 'content', FALSE);

			return $action->execute($request);

			return TRUE;
		}
		
		/* Does this user have permission to edit theirreply if the topic is locked? */
		if($topic['topic_locked'] == 1 && get_map($request['user'], 'closed', 'can_edit', array('forum_id' => $forum['id'])) > $request['user']->get('perms')) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

			return $action->execute($request);
			return TRUE;
		}
		
		/* is this topic part of the moderator's queue? */
		if($topic['queue'] == 1) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INVALIDTOPICVIEW');
			$action = new K4InformationAction(new K4LanguageElement('L_TOPICPENDINGMOD'), 'content', FALSE);

			return $action->execute($request);
			
			return TRUE;
		}

		/* Is this topic hidden? */
		if($topic['display'] == 0) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INVALIDTOPICVIEW');
			$action = new K4InformationAction(new K4LanguageElement('L_TOPICISHIDDEN'), 'content', FALSE);

			return $action->execute($request);
			
			return TRUE;
		}

		if(!$reply || !is_array($reply) || empty($reply)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INVALIDREPLY');
			$action = new K4InformationAction(new K4LanguageElement('L_REPLYDOESNTEXIST'), 'content', FALSE);

			return $action->execute($request);

			return TRUE;
		}

		/* Does this person have permission to edit this topic? */
		if($topic['poster_id'] == $request['user']->get('id')) {
			if(get_map($request['user'], 'replies', 'can_edit', array('forum_id'=>$forum['id'])) > $request['user']->get('perms')) {
				$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

				return $action->execute($request);
				return TRUE;
			}
		} else {
			if(get_map($request['user'], 'other_replies', 'can_edit', array('forum_id'=>$forum['id'])) > $request['user']->get('perms')) {
				$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

				return $action->execute($request);
				return TRUE;
			}
		}

		/* set the breadcrumbs bit */
		k4_bread_crumbs(&$request['template'], $request['dba'], 'L_EDITREPLY', $topic, $forum);
				
		/* Initialize the bbcode parser with the topic message */
		$_REQUEST['message']	= substr($_REQUEST['message'], 0, $_SETTINGS['postmaxchars']);
		$bbcode	= &new BBCodex(&$request['dba'], $request['user']->getInfoArray(), $_REQUEST['message'], $forum['id'], 
			iif((isset($_REQUEST['disable_html']) && $_REQUEST['disable_html'] == 'on'), FALSE, TRUE), 
			iif((isset($_REQUEST['disable_bbcode']) && $_REQUEST['disable_bbcode'] == 'on'), FALSE, TRUE), 
			iif((isset($_REQUEST['disable_emoticons']) && $_REQUEST['disable_emoticons'] == 'on'), FALSE, TRUE), 
			iif((isset($_REQUEST['disable_aurls']) && $_REQUEST['disable_aurls'] == 'on'), FALSE, TRUE));
		
		/* Parse the bbcode */
		$body_text = $bbcode->parse();
		
		$request['template']->setVar('newreply_act', 'newreply.php?act=updatereply');

		if($_REQUEST['submit'] == $request['template']->getVar('L_SUBMIT')) {

			/**
			 * Build the queries to update the reply
			 */
			
			$update_a			= $request['dba']->prepareStatement("UPDATE ". K4INFO ." SET name=? WHERE id=?");
			$update_b			= $request['dba']->prepareStatement("UPDATE ". K4REPLIES ." SET body_text=?,posticon=?,disable_html=?,disable_bbcode=?,disable_emoticons=?,disable_sig=?,disable_areply=?,disable_aurls=?,edited_time=?,edited_username=?,edited_userid=? WHERE reply_id=?");
			
			$update_a->setString(1, htmlentities($_REQUEST['name'], ENT_QUOTES));
			$update_a->setInt(2, $reply['id']);
			
			$update_b->setString(1, $body_text);
			$update_b->setString(2, iif(($request['user']->get('perms') >= get_map($request['user'], 'posticons', 'can_add', array('forum_id'=>$forum['id']))), (isset($_REQUEST['posticon']) ? $_REQUEST['posticon'] : 'clear.gif'), 'clear.gif'));
			$update_b->setInt(3, iif((isset($_REQUEST['disable_html']) && $_REQUEST['disable_html'] == 'on'), 1, 0));
			$update_b->setInt(4, iif((isset($_REQUEST['disable_bbcode']) && $_REQUEST['disable_bbcode'] == 'on'), 1, 0));
			$update_b->setInt(5, iif((isset($_REQUEST['disable_emoticons']) && $_REQUEST['disable_emoticons'] == 'on'), 1, 0));
			$update_b->setInt(6, iif((isset($_REQUEST['enable_sig']) && $_REQUEST['enable_sig'] == 'on'), 0, 1));
			$update_b->setInt(7, iif((isset($_REQUEST['disable_areply']) && $_REQUEST['disable_areply'] == 'on'), 1, 0));
			$update_b->setInt(8, iif((isset($_REQUEST['disable_aurls']) && $_REQUEST['disable_aurls'] == 'on'), 1, 0));
			$update_b->setInt(9, time());
			$update_b->setString(10, iif($request['user']->get('id') <= 0, htmlentities(@$_REQUEST['poster_name'], ENT_QUOTES), $request['user']->get('name')));
			$update_b->setInt(11, $request['user']->get('id'));
			$update_b->setInt(12, $reply['id']);
			
			/**
			 * Do the queries
			 */
			$update_a->executeUpdate();
			$update_b->executeUpdate();
			
			/**
			 * Subscribe/Unsubscribe this user to the topic
			 */
			$is_subscribed		= $request['dba']->getRow("SELECT * FROM ". K4SUBSCRIPTIONS ." WHERE user_id = ". intval($request['user']->get('id')) ." AND topic_id = ". intval($topic['id']));
			if(isset($_REQUEST['disable_areply']) && $_REQUEST['disable_areply'] == 'on') {
				if(!is_array($is_subscribed) || empty($is_subscribed)) {
					$subscribe			= &$request['dba']->prepareStatement("INSERT INTO ". K4SUBSCRIPTIONS ." (user_id,user_name,topic_id,forum_id,email,category_id) VALUES (?,?,?,?,?,?)");
					$subscribe->setInt(1, $request['user']->get('id'));
					$subscribe->setString(2, $request['user']->get('name'));
					$subscribe->setInt(3, $topic['id']);
					$subscribe->setInt(4, $forum['id']);
					$subscribe->setString(5, $request['user']->get('email'));
					$subscribe->setInt(6, $forum['category_id']);
					$subscribe->executeUpdate();
				}
			} else if(!isset($_REQUEST['disable_areply']) || !$_REQUEST['disable_areply']) {
				if(is_array($is_subscribed) && !empty($is_subscribed)) {
					$subscribe			= &$request['dba']->prepareStatement("DELETE FROM ". K4SUBSCRIPTIONS ." WHERE user_id=? AND topic_id=?");
					$subscribe->setInt(1, $request['user']->get('id'));
					$subscribe->setInt(2, $topic['id']);
					$subscribe->executeUpdate();
				}
			}

			/* Redirect the user */
			$action = new K4InformationAction(new K4LanguageElement('L_UPDATEDREPLY', htmlentities($_REQUEST['name'], ENT_QUOTES)), 'content', FALSE, 'findpost.php?id='. $reply['id'], 3);

			return $action->execute($request);
		
		} else {
			
			/**
			 * Post Previewing
			 */
			
			/* Get and set the emoticons and post icons to the template */
			$emoticons	= &$request['dba']->executeQuery("SELECT * FROM ". K4EMOTICONS ." WHERE clickable = 1");
			$posticons	= &$request['dba']->executeQuery("SELECT * FROM ". K4POSTICONS);

			$request['template']->setList('emoticons', $emoticons);
			$request['template']->setList('posticons', $posticons);

			$request['template']->setVar('emoticons_per_row', $request['template']->getVar('smcolumns'));
			$request['template']->setVar('emoticons_per_row_remainder', $request['template']->getVar('smcolumns')-1);
			
			topic_post_options(&$request['template'], &$request['user'], $forum);

			/* Set the forum info to the template */
			foreach($forum as $key => $val)
				$request['template']->setVar('forum_'. $key, $val);
						
			$reply_preview	= array(
								'id' => $reply['id'],
								'name' => htmlentities($_REQUEST['name'], ENT_QUOTES),
								'body_text' => $body_text,
								'poster_name' => $request['user']->get('name'),
								'poster_id' => $request['user']->get('id'),
								'forum_id' => $forum['id'],
								'topic_id' => $topic['id'],
								'row_left' => 0,
								'row_right' => 0,
								'posticon' => iif(($request['user']->get('perms') >= get_map($request['user'], 'posticons', 'can_add', array('forum_id'=>$forum['id']))), (isset($_REQUEST['posticon']) ? $_REQUEST['posticon'] : 'clear.gif'), 'clear.gif'),
								'disable_html' => iif((isset($_REQUEST['disable_html']) && $_REQUEST['disable_html'] == 'on'), 1, 0),
								'disable_sig' => iif((isset($_REQUEST['enable_sig']) && $_REQUEST['enable_sig'] == 'on'), 0, 1),
								'disable_bbcode' => iif((isset($_REQUEST['disable_bbcode']) && $_REQUEST['disable_bbcode'] == 'on'), 1, 0),
								'disable_emoticons' => iif((isset($_REQUEST['disable_emoticons']) && $_REQUEST['disable_emoticons'] == 'on'), 1, 0),
								'disable_areply' => iif((isset($_REQUEST['disable_areply']) && $_REQUEST['disable_areply'] == 'on'), 1, 0),
								'disable_aurls' => iif((isset($_REQUEST['disable_aurls']) && $_REQUEST['disable_aurls'] == 'on'), 1, 0)
								);

			/* Add the reply information to the template (same as for topics) */
			$reply_iterator = &new TopicIterator($request['dba'], $request['user'], $reply_preview, FALSE);
			$request['template']->setList('topic', $reply_iterator);
			
			/* Assign the topic preview values to the template */
			$reply_preview['body_text'] = $_REQUEST['message'];
			
			foreach($reply_preview as $key => $val)
				$request['template']->setVar('reply_'. $key, $val);
			
			/* Set the the button display options */
			$request['template']->setVisibility('edit_reply', TRUE);
			$request['template']->setVisibility('reply_id', TRUE);
			$request['template']->setVisibility('post_reply', FALSE);
			$request['template']->setVisibility('edit_post', TRUE);
			
			/* Get the number of replies to this topic */
			//$num_replies		= @intval(($topic['row_right'] - $topic['row_left'] - 1) / 2);

			/* Get replies that are above this point */
			$query	= "SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['reply'] ." FROM ". K4REPLIES ." r LEFT JOIN ". K4INFO ." i ON i.id = r.reply_id WHERE r.topic_id = ". intval($topic['id']) ." AND i.id < ". intval($reply['id']) ." AND i.row_type = ". REPLY ." ORDER BY i.created DESC LIMIT 10";
			$replies	= &$request['dba']->executeQuery($query);

			$request['template']->setList('topic_review', new TopicReviewIterator($request['dba'], $topic, $replies, $request['user']->getInfoArray()));
			
			/* Set the post topic form */
			$request['template']->setFile('preview', 'post_preview.html');
			$request['template']->setFile('content', 'newreply.html');
		}

		return TRUE;
	}
}

/**
 * Delete a topic
 */
class DeleteReply extends FAAction {
	function execute(&$request) {
		
		global $_QUERYPARAMS, $_DATASTORE, $_USERGROUPS;
		
		if(!isset($_REQUEST['id']) || !$_REQUEST['id'] || intval($_REQUEST['id']) == 0) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INVALIDREPLY');
			$action = new K4InformationAction(new K4LanguageElement('L_REPLYDOESNTEXIST'), 'content', FALSE);

			return $action->execute($request);
			return TRUE;
		}

		/* Get our topic */
		$reply				= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['reply'] ." FROM ". K4REPLIES ." r LEFT JOIN ". K4INFO ." i ON r.reply_id = i.id WHERE i.id = ". intval($_REQUEST['id']));
		
		if(!$reply || !is_array($reply) || empty($reply)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INVALIDREPLY');
			$action = new K4InformationAction(new K4LanguageElement('L_REPLYDOESNTEXIST'), 'content', FALSE);

			return $action->execute($request);

			return TRUE;
		}
		
		$topic				= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['topic'] ." FROM ". K4TOPICS ." t LEFT JOIN ". K4INFO ." i ON t.topic_id = i.id WHERE i.id = ". intval($reply['topic_id']));
		
		/* Check the forum data given */
		if(!$topic|| !is_array($topic) || empty($topic)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INVALIDTOPIC');
			$action = new K4InformationAction(new K4LanguageElement('L_TOPICDOESNTEXIST'), 'content', FALSE);

			return $action->execute($request);
			return TRUE;
		}
			
		$forum				= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['forum'] ." FROM ". K4FORUMS ." f LEFT JOIN ". K4INFO ." i ON f.forum_id = i.id WHERE i.id = ". intval($reply['forum_id']));
		
		/* Check the forum data given */
		if(!$forum || !is_array($forum) || empty($forum)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INVALIDFORUM');
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);

			return $action->execute($request);
			return TRUE;
		}
			
		/* Make sure the we are trying to delete from a forum */
		if(!($forum['row_type'] & FORUM)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_CANTDELFROMNONFORUM'), 'content', FALSE);

			return $action->execute($request);
			return TRUE;
		}
		
		/* set the breadcrumbs bit */
		k4_bread_crumbs(&$request['template'], $request['dba'], 'L_DELETEREPLY', $topic, $forum);
		
		/* Does this person have permission to remove this topic? */
		if($reply['poster_id'] == $request['user']->get('id')) {
			if(get_map($request['user'], 'replies', 'can_del', array('forum_id'=>$forum['id'])) > $request['user']->get('perms')) {
				$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

				return $action->execute($request);
				return TRUE;
			}
		} else {
			if(get_map($request['user'], 'other_replies', 'can_del', array('forum_id'=>$forum['id'])) > $request['user']->get('perms')) {
				$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

				return $action->execute($request);
				return TRUE;
			}
		}
		
		$user_usergroups	= $request['user']->get('usergroups') != '' ? iif(!unserialize($request['user']->get('usergroups')), array(), unserialize($request['user']->get('usergroups'))) : array();
		$forum_usergroups	= $forum['moderating_groups'] != '' ? iif(!unserialize($forum['moderating_groups']), array(), unserialize($forum['moderating_groups'])) : array();
		
		/* Check if this user belongs to one of this forums moderatign groups, if any exist */
		if(is_array($forum_usergroups) && !empty($forum_usergroups)) {
			if(is_array($user_usergroups) && !empty($user_usergroups)) {

				$error		= true;

				foreach($user_usergroups as $group) {
					if(!in_array($group, $forum_usergroups) && !$error) {
						$error	= false;
					} else {
						$error	= $_USERGROUPS[$group];
					}
				}
				
				if(!$error) {
					$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

					return $action->execute($request);
					return TRUE;
				}
			} else {
				$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

				return $action->execute($request);
				return TRUE;
			}
		}
				
		//$num_replies		= @intval(($reply['row_right'] - $reply['row_left'] - 1) / 2);
		$num_replies		= $reply['num_replies'];
		
		/* Get that last topic in this forum */
		$last_topic			= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['topic'] ." FROM ". K4TOPICS ." t LEFT JOIN ". K4INFO ." i ON t.topic_id = i.id WHERE t.is_draft=0 AND t.forum_id=". intval($reply['forum_id']) ." ORDER BY i.created DESC LIMIT 1");
		$last_topic			= !$last_topic || !is_array($last_topic) ? array() : $last_topic;
		
		/* Get that last post in this forum that's not part of/from this topic */
		$last_post			= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['reply'] ." FROM ". K4REPLIES ." r LEFT JOIN ". K4INFO ." i ON r.reply_id = i.id WHERE r.reply_id <> ". intval($reply['id']) ." AND r.forum_id=". intval($reply['forum_id']) ." ORDER BY i.created DESC LIMIT 1");
		$last_post			= !$last_post || !is_array($last_post) ? $last_topic : $last_post;
		
		/* Should the last post be the last topic? */
		$last_post			= @$last_post['created'] < @$last_topic['created'] ? $last_topic : $last_post;
		
		/**
		 * Should we update the topic?
		 */
		if($topic['num_replies'] > 1) {
			$topic_last_reply	= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['reply'] ." FROM ". K4REPLIES ." r LEFT JOIN ". K4INFO ." i ON r.reply_id = i.id WHERE r.reply_id <> ". intval($reply['id']) ." AND r.topic_id=". intval($topic['id']) ." ORDER BY i.created DESC LIMIT 1");
			$topic_update		= &$request['dba']->prepareStatement("UPDATE ". K4TOPICS ." SET reply_time=?,reply_uname=?,reply_uid=?,reply_id=? WHERE topic_id=?");
			$topic_update->setInt(1, $topic_last_reply['created']);
			$topic_update->setString(2, $topic_last_reply['poster_name']);
			$topic_update->setInt(3, $topic_last_reply['poster_id']);
			$topic_update->setInt(4, $topic_last_reply['reply_id']);
			$topic_update->setInt(5, $topic['id']);
			$topic_update->executeUpdate();
		} else {
			$request['dba']->executeUpdate("UPDATE ". K4TOPICS ." SET num_replies=0,reply_time=0,reply_uname='',reply_uid=0,reply_id=0 WHERE topic_id=". intval($topic['id']));
		}

		/**
		 * Update the forum and the datastore
		 */
		
		$request['dba']->beginTransaction();

		$forum_update		= &$request['dba']->prepareStatement("UPDATE ". K4FORUMS ." SET posts=posts-?,replies=replies-?,post_created=?,post_name=?,post_uname=?,post_id=?,post_uid=?,post_posticon=? WHERE forum_id=?");
		$datastore_update	= &$request['dba']->prepareStatement("UPDATE ". K4DATASTORE ." SET data=? WHERE varname=?");
			
		/* Set the forum values */
		$forum_update->setInt(1, 1);
		$forum_update->setInt(2, 1);
		$forum_update->setInt(3, @$last_post['created']);
		$forum_update->setString(4, @$last_post['name']);
		$forum_update->setString(5, @$last_post['poster_name']);
		$forum_update->setInt(6, @$last_post['id']);
		$forum_update->setInt(7, @$last_post['poster_id']);
		$forum_update->setString(8, @$last_post['posticon']);
		$forum_update->setInt(9, @$forum['id']);
		
		/* Set the datastore values */
		$datastore					= $_DATASTORE['forumstats'];
		$datastore['num_replies']	= $request['dba']->getValue("SELECT COUNT(*) FROM ". K4REPLIES ." WHERE is_draft = 0") - 1;
		
		$datastore_update->setString(1, serialize($datastore));
		$datastore_update->setString(2, 'forumstats');
		
		/* Execute the forum and datastore update queries */
		$forum_update->executeUpdate();
		$datastore_update->executeUpdate();

		/**
		 * Change user post counts
		 */
		
		/* Update the user that posted this topic */
		if($reply['poster_id'] > 0)
			$request['dba']->executeUpdate("UPDATE ". K4USERINFO ." SET num_posts=num_posts-1 WHERE user_id=". intval($topic['poster_id']));

		/**
		 * Remove the reply and move any of its replies up
		 */
		
		/* Remove the topic and all replies from the information table */
		$h				= &new Heirarchy();
		$h->moveUp($reply, K4INFO);
		
		/* Now remove the information stored in the topics and replies table */
		$request['dba']->executeUpdate("DELETE FROM ". K4REPLIES ." WHERE reply_id = ". intval($reply['id']));
		
		$request['dba']->commitTransaction();

		if(!@touch(CACHE_DS_FILE, time()-86460)) {
			@unlink(CACHE_DS_FILE);
		}
		
		/* Redirect the user */
		$action = new K4InformationAction(new K4LanguageElement('L_DELETEDREPLY', $reply['name'], $topic['name']), 'content', FALSE, 'viewtopic.php?id='. $topic['id'], 3);

		return $action->execute($request);
		return TRUE;
	}
}

class RepliesIterator extends FAProxyIterator {
	
	var $result;
	var $_SESSION;
	var $img_dir;
	var $forums;

	function __construct(&$user, &$dba, &$result, $queryparams, $users, $groups, $fields) {
		
		$this->users			= $users;
		$this->qp				= $queryparams;
		$this->result			= &$result;
		$this->groups			= $groups;
		$this->fields			= $fields;
		$this->user				= &$user;
		$this->dba				= &$dba;
		
		parent::__construct($this->result);
	}

	function &current() {
		$temp					= parent::current();
		
		$temp['posticon']		= isset($temp['posticon']) && @$temp['posticon'] != '' ? iif(file_exists(BB_BASE_DIR .'/tmp/upload/posticons/'. @$temp['posticon']), @$temp['posticon'], 'clear.gif') : 'clear.gif';
		
		if($temp['poster_id'] > 0) {
			
			if(!isset($this->users[$temp['poster_id']])) {
			
				$user						= $this->dba->getRow("SELECT ". $this->qp['user'] . $this->qp['userinfo'] ." FROM ". K4USERS ." u LEFT JOIN ". K4USERINFO ." ui ON u.id=ui.user_id WHERE u.id=". intval($temp['poster_id']));
				
				$group						= get_user_max_group($user, $this->groups);
				$user['group_color']		= !isset($group['color']) || $group['color'] == '' ? '000000' : $group['color'];
				$user['group_nicename']		= $group['nicename'];
				$user['group_avatar']		= $group['avatar'];
				$user['online']				= (time() - ini_get('session.gc_maxlifetime')) > $this->user->get('seen') ? 'offline' : 'online';

				$this->users[$user['id']]	= $user;
			} else {
				
				$user						= $this->users[$temp['poster_id']];
			}
			
			foreach($user as $key => $val)
				$temp['post_user_'. $key] = $val;

			$fields						= array();
			foreach($this->fields as $field) {
				
				if($field['display_post'] == 1) {

					if(isset($temp['post_user_'. $field['name']]) && $temp['post_user_'. $field['name']] != '') {
						switch($field['inputtype']) {
							default:
							case 'text':
							case 'textarea':
							case 'select': {
								$field['value']		= $temp['post_user_'. $field['name']];
								break;
							}
							case 'multiselect':
							case 'radio':
							case 'check': {
								$result				= unserialize($temp['post_user_'. $field['name']]);
								$field['value']		= implode(", ", (!$result ? array() : $result));
								break;
							}
						}
						$fields[] = $field;
					}
				}
			}
			$temp['profilefields']	= &new FAArrayIterator($fields);
		} else {
			$temp['post_user_id']	= 0;
			$temp['post_user_name']	= $temp['poster_name'];
		}
		
		/* Should we free the result? */
		if(!$this->hasNext())
			$this->result->free();
		
		return $temp;
	}
}

?>