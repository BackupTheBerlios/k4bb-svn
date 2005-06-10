<?php
/**
* k4 Bulletin Board, topics.class.php
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
* @version $Id: topics.class.php,v 1.19 2005/05/26 18:35:44 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	exit;
}

/**
 * Post / Preview a topic
 */
class PostTopic extends FAAction {
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
			
		/* Make sure the we are trying to post into a forum */
		if(!($forum['row_type'] & FORUM)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_CANTPOSTTONONFORUM'), 'content', FALSE);

			return $action->execute($request);
			return TRUE;
		}

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
//			$num_on_level		= $this->getNumOnLevel($forum['row_left'], $forum['row_right'], $forum['row_level']+1);
//			
//			/* If there are more than 1 nodes on the current level */
//			if($num_on_level > 0) {
//				$left			= $forum['row_right'];
//			} else {
//				$left			= $forum['row_left'] + 1;
//			}
//			
//			$right				= $left+1;
//		}

		/* Set this nodes level */
		$level				= $forum['row_level']+1;
		
		/* Set the topic created time */
		$created			= time();
		
		$_REQUEST['message']	= substr($_REQUEST['message'], 0, $_SETTINGS['postmaxchars']);
		/* Initialize the bbcode parser with the topic message */
		$bbcode	= &new BBCodex(&$request['dba'], $request['user']->getInfoArray(), $_REQUEST['message'], $forum['id'], 
			iif((isset($_REQUEST['disable_html']) && $_REQUEST['disable_html'] == 'on'), FALSE, TRUE), 
			iif((isset($_REQUEST['disable_bbcode']) && $_REQUEST['disable_bbcode'] == 'on'), FALSE, TRUE), 
			iif((isset($_REQUEST['disable_emoticons']) && $_REQUEST['disable_emoticons'] == 'on'), FALSE, TRUE), 
			iif((isset($_REQUEST['disable_aurls']) && $_REQUEST['disable_aurls'] == 'on'), FALSE, TRUE));
		
		/* Parse the bbcode */
		$body_text = $bbcode->parse();
		
		/**
		 * Figure out what type of topic type this is
		 */
		$topic_type			= isset($_REQUEST['topic_type']) && intval($_REQUEST['topic_type']) != 0 ? $_REQUEST['topic_type'] : TOPIC_NORMAL;

		if($topic_type == TOPIC_STICKY && $request['user']->get('perms') < get_map($request['user'], 'sticky', 'can_add', array('forum_id'=>$forum['id']))) {
			$topic_type		= TOPIC_NORMAL;
		} else if($topic_type == TOPIC_ANNOUNCE && $request['user']->get('perms') < get_map($request['user'], 'announce', 'can_add', array('forum_id'=>$forum['id']))) {
			$topic_type		= TOPIC_NORMAL;
		} else if($topic_type == TOPIC_GLOBAL && $request['user']->get('perms') < get_map($request['user'], 'global', 'can_add', array('forum_id'=>$forum['id']))) {
			$topic_type		= TOPIC_NORMAL;
		}

		$is_feature			= isset($_REQUEST['is_feature']) && $_REQUEST['is_feature'] == 'yes' ? 1 : 0;
		
		if($is_feature == 1 && $request['user']->get('perms') < get_map($request['user'], 'feature', 'can_add', array('forum_id'=>$forum['id']))) {
			$is_feature		= 0;
		}

		if($_REQUEST['submit'] == $request['template']->getVar('L_SUBMIT') || $_REQUEST['submit'] == $request['template']->getVar('L_SAVEDRAFT')) {
			
			/* Does this person have permission to post a draft? */
			if($_REQUEST['submit'] == $request['template']->getVar('L_SAVEDRAFT')) {
				if($request['user']->get('perms') < get_map($request['user'], 'post_save', 'can_add', array('forum_id'=>$forum['id']))) {
					k4_bread_crumbs(&$request['template'], $request['dba'], 'L_POSTTOPIC', $forum);
					$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

					return $action->execute($request);
					return TRUE;
				}
			}

			/**
			 * Build the queries
			 */
			
			$poster_name		= iif($request['user']->get('id') <= 0,  htmlentities((isset($_REQUEST['poster_name']) ? $_REQUEST['poster_name'] : '') , ENT_QUOTES), $request['user']->get('name'));

			$request['dba']->beginTransaction();
			
//			if(K4MPTT) {
//				/* Prepare the queries */
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
			$insert_b			= &$request['dba']->prepareStatement("INSERT INTO ". K4TOPICS ." (topic_id,forum_id,category_id,poster_name,poster_id,poster_ip,body_text,posticon,disable_html,disable_bbcode,disable_emoticons,disable_sig,disable_areply,disable_aurls,is_draft,topic_type,topic_expire,is_feature) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
			
			/* Set the inserts for adding the actual node */
			$insert_a->setString(1, htmlentities($_REQUEST['name'], ENT_QUOTES));
			$insert_a->setInt(2, $forum['id']);
			$insert_a->setInt(3, TOPIC);
			$insert_a->setInt(4, $level);
			$insert_a->setInt(5, $created);
						
			/* Add the main topic information to the database */
			$insert_a->executeUpdate();

			$topic_id			= $request['dba']->getInsertId();
			
			//topic_id,forum_id,category_id,poster_name,poster_id,body_text,posticon
			//disable_html,disable_bbcode,disable_emoticons,disable_sig,disable_areply,disable_aurls,is_draft
			$insert_b->setInt(1, $topic_id);
			$insert_b->setInt(2, $forum['id']);
			$insert_b->setInt(3, $forum['category_id']);
			$insert_b->setString(4, $poster_name);
			$insert_b->setInt(5, $request['user']->get('id'));
			$insert_b->setString(6, USER_IP);
			$insert_b->setString(7, $body_text);
			$insert_b->setString(8, iif(($request['user']->get('perms') >= get_map($request['user'], 'posticons', 'can_add', array('forum_id'=>$forum['id']))), (isset($_REQUEST['posticon']) ? $_REQUEST['posticon'] : 'clear.gif'), 'clear.gif'));
			$insert_b->setInt(9, iif((isset($_REQUEST['disable_html']) && $_REQUEST['disable_html'] == 'on'), 1, 0));
			$insert_b->setInt(10, iif((isset($_REQUEST['disable_bbcode']) && $_REQUEST['disable_bbcode'] == 'on'), 1, 0));
			$insert_b->setInt(11, iif((isset($_REQUEST['disable_emoticons']) && $_REQUEST['disable_emoticons'] == 'on'), 1, 0));
			$insert_b->setInt(12, iif((isset($_REQUEST['enable_sig']) && $_REQUEST['enable_sig'] == 'on'), 0, 1));
			$insert_b->setInt(13, iif((isset($_REQUEST['disable_areply']) && $_REQUEST['disable_areply'] == 'on'), 1, 0));
			$insert_b->setInt(14, iif((isset($_REQUEST['disable_aurls']) && $_REQUEST['disable_aurls'] == 'on'), 1, 0));
			$insert_b->setInt(15, iif($_REQUEST['submit'] == $request['template']->getVar('L_SAVEDRAFT'), 1, 0));
			// DO THIS 16 -> topic_type, 17 -> topic_expire
			$insert_b->setInt(16, $topic_type);
			$insert_b->setInt(17, iif($topic_type > TOPIC_NORMAL, intval((isset($_REQUEST['topic_expire']) ? $_REQUEST['topic_expire'] : 0)), 0) );
			$insert_b->setInt(18, $is_feature);
			$insert_b->executeUpdate();
			

			/** 
			 * Update the forum, and update the datastore 
			 */

			//topic_created,topic_name,topic_uname,topic_id,topic_uid,post_created,post_name,post_uname,post_id,post_uid
			$where				= $topic_type != TOPIC_GLOBAL ? "WHERE forum_id=?" : "WHERE forum_id=? OR forum_id<>0";
			$forum_update		= &$request['dba']->prepareStatement("UPDATE ". K4FORUMS ." SET topics=topics+1,posts=posts+1,topic_created=?,topic_name=?,topic_uname=?,topic_id=?,topic_uid=?,topic_posticon=?,post_created=?,post_name=?,post_uname=?,post_id=?,post_uid=?,post_posticon=? $where");
			$datastore_update	= &$request['dba']->prepareStatement("UPDATE ". K4DATASTORE ." SET data=? WHERE varname=?");
			
			/* If this isn't a draft, update the forums and datastore tables */
			if($_REQUEST['submit'] != $request['template']->getVar('L_SAVEDRAFT')) {
				
				/* Set the forum values */
				$forum_update->setInt(1, $created);
				$forum_update->setString(2, htmlentities($_REQUEST['name'], ENT_QUOTES));
				$forum_update->setString(3, $poster_name);
				$forum_update->setInt(4, $topic_id);
				$forum_update->setInt(5, $request['user']->get('id'));
				$forum_update->setString(6, iif(($request['user']->get('perms') >= get_map($request['user'], 'posticons', 'can_add', array('forum_id'=>$forum['id']))), (isset($_REQUEST['posticon']) ? $_REQUEST['posticon'] : 'clear.gif'), 'clear.gif'));
				$forum_update->setInt(7, $created);
				$forum_update->setString(8, htmlentities($_REQUEST['name'], ENT_QUOTES));
				$forum_update->setString(9, $poster_name);
				$forum_update->setInt(10, $topic_id);
				$forum_update->setInt(11, $request['user']->get('id'));
				$forum_update->setString(12, iif(($request['user']->get('perms') >= get_map($request['user'], 'posticons', 'can_add', array('forum_id'=>$forum['id']))), (isset($_REQUEST['posticon']) ? $_REQUEST['posticon'] : 'clear.gif'), 'clear.gif'));
				$forum_update->setInt(13, $forum['id']);
				
				/**
				 * Update the forums table and datastore table
				 */
				$forum_update->executeUpdate();
			}
			
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_POSTTOPIC', $forum);
			
			/* Added the topic */
			if($_REQUEST['submit'] == $request['template']->getVar('L_SUBMIT')) {
				
				/* Set the datastore values */
				$datastore					= $_DATASTORE['forumstats'];
				$datastore['num_topics']	= $request['dba']->getValue("SELECT COUNT(*) FROM ". K4TOPICS ." WHERE is_draft = 0");
				
				$datastore_update->setString(1, serialize($datastore));
				$datastore_update->setString(2, 'forumstats');
				$datastore_update->executeUpdate();
				
				/* Update the user post count */
				$request['dba']->executeUpdate("UPDATE ". K4USERINFO ." SET num_posts=num_posts+1,total_posts=total_posts+1 WHERE user_id=". intval($request['user']->get('id')));

				if(!@touch(CACHE_DS_FILE, time()-86460)) {
					@unlink(CACHE_DS_FILE);
				}
				

				/**
				 * Subscribe this user to the topic
				 */
				if(isset($_REQUEST['disable_areply']) && $_REQUEST['disable_areply'] == 'on') {
					$subscribe			= &$request['dba']->prepareStatement("INSERT INTO ". K4SUBSCRIPTIONS ." (user_id,user_name,topic_id,forum_id,email,category_id) VALUES (?,?,?,?,?,?)");
					$subscribe->setInt(1, $request['user']->get('id'));
					$subscribe->setString(2, $request['user']->get('name'));
					$subscribe->setInt(3, $topic_id);
					$subscribe->setInt(4, $forum['id']);
					$subscribe->setString(5, $request['user']->get('email'));
					$subscribe->setInt(6, $forum['category_id']);
					$subscribe->executeUpdate();
				}
				
				set_send_topic_mail($forum['id'], iif($poster_name == '', $request['template']->getVar('L_GUEST'), $poster_name));
				
				/* Commit the current transaction */
				$request['dba']->commitTransaction();
				
				/* Redirect the user */
				$action = new K4InformationAction(new K4LanguageElement('L_ADDEDTOPIC', htmlentities($_REQUEST['name'], ENT_QUOTES), $forum['name']), 'content', FALSE, 'viewtopic.php?id='. $topic_id, 3);

				return $action->execute($request);
			} else {
				
				/* Commit the current transaction */
				$request['dba']->commitTransaction();

				/* Redirect the user */
				$action = new K4InformationAction(new K4LanguageElement('L_SAVEDDRAFTTOPIC', htmlentities($_REQUEST['name'], ENT_QUOTES), $forum['name']), 'content', FALSE, 'viewforum.php?id='. $forum['id'], 3);

				return $action->execute($request);
			}
		} else {
			
			/**
			 * Post Previewing
			 */
			
			/* Get and set the emoticons and post icons to the template */
			$emoticons	= &$request['dba']->executeQuery("SELECT * FROM ". K4EMOTICONS ." WHERE clickable = 1");
			$posticons	= &$request['dba']->executeQuery("SELECT * FROM ". K4POSTICONS);
			
			/* Add the emoticons and the post icons to the template */
			$request['template']->setList('emoticons', $emoticons);
			$request['template']->setList('posticons', $posticons);
			
			/* Set some emoticon information */
			$request['template']->setVar('emoticons_per_row', $request['template']->getVar('smcolumns'));
			$request['template']->setVar('emoticons_per_row_remainder', $request['template']->getVar('smcolumns')-1);
			
			topic_post_options(&$request['template'], &$request['user'], $forum);

			/* Set the forum info to the template */
			foreach($forum as $key => $val)
				$request['template']->setVar('forum_'. $key, $val);
			
			$request['template']->setVar('newtopic_action', 'newtopic.php?act=posttopic');
			
			/* Set topic array items to be passed to the iterator */			
			$topic_preview	= array(
								'name' => htmlentities($_REQUEST['name'], ENT_QUOTES),
								'body_text' => $body_text,
								'poster_name' => $request['user']->get('name'),
								'poster_id' => $request['user']->get('id'),
								'row_left' => 0,
								'row_right' => 0,
								'topic_type' => $topic_type,
								'is_feature' => $is_feature,
								'posticon' => iif(($request['user']->get('perms') >= get_map($request['user'], 'posticons', 'can_add', array('forum_id'=>$forum['id']))), (isset($_REQUEST['posticon']) ? $_REQUEST['posticon'] : 'clear.gif'), 'clear.gif'),
								'disable_html' => iif((isset($_REQUEST['disable_html']) && $_REQUEST['disable_html'] == 'on'), 1, 0),
								'disable_sig' => iif((isset($_REQUEST['enable_sig']) && $_REQUEST['enable_sig'] == 'on'), 0, 1),
								'disable_bbcode' => iif((isset($_REQUEST['disable_bbcode']) && $_REQUEST['disable_bbcode'] == 'on'), 1, 0),
								'disable_emoticons' => iif((isset($_REQUEST['disable_emoticons']) && $_REQUEST['disable_emoticons'] == 'on'), 1, 0),
								'disable_areply' => iif((isset($_REQUEST['disable_areply']) && $_REQUEST['disable_areply'] == 'on'), 1, 0),
								'disable_aurls' => iif((isset($_REQUEST['disable_aurls']) && $_REQUEST['disable_aurls'] == 'on'), 1, 0)
								);

			/* Add the topic information to the template */
			$topic_iterator = &new TopicIterator($request['dba'], $request['user'], $topic_preview, FALSE);
			$request['template']->setList('topic', $topic_iterator);
			
			/* Assign the topic preview values to the template */
			$topic_preview['body_text'] = $_REQUEST['message'];
			
			foreach($topic_preview as $key => $val)
				$request['template']->setVar('topic_'. $key, $val);
			
			/* Assign the forum information to the template */
			foreach($forum as $key => $val)
				$request['template']->setVar('forum_'. $key, $val);

			/* Set the the button display options */
			$request['template']->setVisibility('save_draft', TRUE);
			$request['template']->setVisibility('edit_topic', TRUE);
			$request['template']->setVisibility('topic_id', TRUE);
			
			/* Should she show/hide the 'load draft' button? */
			$drafts		= $request['dba']->executeQuery("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['topic'] ." FROM ". K4TOPICS ." t LEFT JOIN ". K4INFO ." i ON t.topic_id = i.id WHERE t.forum_id = ". intval($forum['id']) ." AND t.is_draft = 1 AND t.poster_id = ". intval($request['user']->get('id')));
			if($drafts->numrows() > 0)
				$request['template']->setVisibility('load_button', TRUE);
			else
				$request['template']->setVisibility('load_button', FALSE);

			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_POSTTOPIC', $forum);
			
			/* Set the post topic form */
			$request['template']->setFile('preview', 'post_preview.html');
			$request['template']->setFile('content', 'newtopic.html');
		}

		return TRUE;
	}
}

/**
 * Post / Preview a draft topic
 */
class PostDraft extends FAAction {
	function execute(&$request) {
		
		global $_QUERYPARAMS, $_DATASTORE, $_SETTINGS;

		$this->dba			= &$request['dba'];

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
			
		/* Make sure the we are trying to post into a forum */
		if(!($forum['row_type'] & FORUM)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_CANTPOSTTONONFORUM'), 'content', FALSE);

			return $action->execute($request);
			return TRUE;
		}

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
		
		/* Get our topic */
		$draft				= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['topic'] ." FROM ". K4TOPICS ." t LEFT JOIN ". K4INFO ." i ON t.topic_id = i.id WHERE i.id = ". intval($_REQUEST['topic_id']) ." AND t.is_draft = 1 AND t.poster_id = ". intval($request['user']->get('id')));
		
		if(!$draft || !is_array($draft) || empty($draft)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INVALIDDRAFT');
			$action = new K4InformationAction(new K4LanguageElement('L_DRAFTDOESNTEXIST'), 'content', FALSE);

			return $action->execute($request);

			return TRUE;
		}

		/* set the breadcrumbs bit */
		k4_bread_crumbs(&$request['template'], $request['dba'], 'L_POSTTOPIC', $forum);
		
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
		
		/**
		 * Figure out what type of topic type this is
		 */
		$topic_type			= isset($_REQUEST['topic_type']) && intval($_REQUEST['topic_type']) != 0 ? $_REQUEST['topic_type'] : TOPIC_NORMAL;

		if($topic_type == TOPIC_STICKY && $request['user']->get('perms') < get_map($request['user'], 'sticky', 'can_add', array('forum_id'=>$forum['id']))) {
			$topic_type		= TOPIC_NORMAL;
		} else if($topic_type == TOPIC_ANNOUNCE && $request['user']->get('perms') < get_map($request['user'], 'announce', 'can_add', array('forum_id'=>$forum['id']))) {
			$topic_type		= TOPIC_NORMAL;
		} else if($topic_type == TOPIC_GLOBAL && $request['user']->get('perms') < get_map($request['user'], 'global', 'can_add', array('forum_id'=>$forum['id']))) {
			$topic_type		= TOPIC_NORMAL;
		}

		$is_feature			= isset($_REQUEST['is_feature']) && $_REQUEST['is_feature'] == 'yes' ? 1 : 0;
		
		if($is_feature == 1 && $request['user']->get('perms') < get_map($request['user'], 'feature', 'can_add', array('forum_id'=>$forum['id']))) {
			$is_feature		= 0;
		}

		/* If we are submitting or saving a draft */
		if($_REQUEST['submit'] == $request['template']->getVar('L_SUBMIT') || $_REQUEST['submit'] == $request['template']->getVar('L_SAVEDRAFT')) {

			/**
			 * Build the queries to add the draft
			 */
			
			$poster_name		= iif($request['user']->get('id') <= 0,  htmlentities((isset($_REQUEST['poster_name']) ? $_REQUEST['poster_name'] : '') , ENT_QUOTES), $request['user']->get('name'));

			$update_a			= $request['dba']->prepareStatement("UPDATE ". K4INFO ." SET name=?,created=? WHERE id=?");
			$update_b			= $request['dba']->prepareStatement("UPDATE ". K4TOPICS ." SET body_text=?,posticon=?,disable_html=?,disable_bbcode=?,disable_emoticons=?,disable_sig=?,disable_areply=?,disable_aurls=?,is_draft=?,topic_type=?,is_feature=? WHERE topic_id=?");
			
			/* Set the informtion */
			$update_a->setString(1, htmlentities($_REQUEST['name'], ENT_QUOTES));
			$update_a->setInt(2, $created);
			$update_a->setInt(3, $draft['id']);
			
			/* Set the topic information */
			$update_b->setString(1, $body_text);
			$update_b->setString(2, iif(($request['user']->get('perms') >= get_map($request['user'], 'posticons', 'can_add', array('forum_id'=>$forum['id']))), (isset($_REQUEST['posticon']) ? $_REQUEST['posticon'] : 'clear.gif'), 'clear.gif'));
			$update_b->setInt(3, iif((isset($_REQUEST['disable_html']) && $_REQUEST['disable_html'] == 'on'), 1, 0));
			$update_b->setInt(4, iif((isset($_REQUEST['disable_bbcode']) && $_REQUEST['disable_bbcode'] == 'on'), 1, 0));
			$update_b->setInt(5, iif((isset($_REQUEST['disable_emoticons']) && $_REQUEST['disable_emoticons'] == 'on'), 1, 0));
			$update_b->setInt(6, iif((isset($_REQUEST['enable_sig']) && $_REQUEST['enable_sig'] == 'on'), 0, 1));
			$update_b->setInt(7, iif((isset($_REQUEST['disable_areply']) && $_REQUEST['disable_areply'] == 'on'), 1, 0));
			$update_b->setInt(8, iif((isset($_REQUEST['disable_aurls']) && $_REQUEST['disable_aurls'] == 'on'), 1, 0));
			$update_b->setInt(9, 0);
			$update_b->setInt(10, $topic_type);
			$update_b->setInt(11, $is_feature);
			$update_b->setInt(12, $draft['id']);
			
			/**
			 * Do the queries
			 */
			$update_a->executeUpdate();
			$update_b->executeUpdate();

			$forum_update		= &$request['dba']->prepareStatement("UPDATE ". K4FORUMS ." SET topics=topics+1,posts=posts+1,topic_created=?,topic_name=?,topic_uname=?,topic_id=?,topic_uid=?,topic_posticon=?,post_created=?,post_name=?,post_uname=?,post_id=?,post_uid=?,post_posticon=? WHERE forum_id=?");
			$datastore_update	= &$request['dba']->prepareStatement("UPDATE ". K4DATASTORE ." SET data=? WHERE varname=?");
			
			if($_REQUEST['submit'] == $request['template']->getVar('L_SUBMIT'))
				$request['dba']->executeUpdate("UPDATE ". K4USERINFO ." SET num_posts=num_posts+1,total_posts=total_posts+1 WHERE user_id=". intval($request['user']->get('id')));	
				
			/* Set the forum values */
			$forum_update->setInt(1, $created);
			$forum_update->setString(2, htmlentities($_REQUEST['name'], ENT_QUOTES));
			$forum_update->setString(3, $poster_name);
			$forum_update->setInt(4, $draft['id']);
			$forum_update->setInt(5, $request['user']->get('id'));
			$forum_update->setString(6, iif(($request['user']->get('perms') >= get_map($request['user'], 'posticons', 'can_add', array('forum_id'=>$forum['id']))), (isset($_REQUEST['posticon']) ? $_REQUEST['posticon'] : 'clear.gif'), 'clear.gif'));
			$forum_update->setInt(7, $created);
			$forum_update->setString(8, htmlentities($_REQUEST['name'], ENT_QUOTES));
			$forum_update->setString(9, $poster_name);
			$forum_update->setInt(10, $draft['id']);
			$forum_update->setInt(11, $request['user']->get('id'));
			$forum_update->setString(12, iif(($request['user']->get('perms') >= get_map($request['user'], 'posticons', 'can_add', array('forum_id'=>$forum['id']))), (isset($_REQUEST['posticon']) ? $_REQUEST['posticon'] : 'clear.gif'), 'clear.gif'));
			$forum_update->setInt(13, $forum['id']);
			
			/* Set the datastore values */
			$datastore					= $_DATASTORE['forumstats'];
			$datastore['num_topics']	= $request['dba']->getValue("SELECT COUNT(*) FROM ". K4TOPICS ." WHERE is_draft = 0");
			
			$datastore_update->setString(1, serialize($datastore));
			$datastore_update->setString(2, 'forumstats');
			
			/**
			 * Update the forums table and datastore table
			 */
			$forum_update->executeUpdate();
			$datastore_update->executeUpdate();
			
			if(!@touch(CACHE_DS_FILE, time()-86460)) {
				@unlink(CACHE_DS_FILE);
			}

			/**
			 * Subscribe this user to the topic
			 */
			if(isset($_REQUEST['disable_areply']) && $_REQUEST['disable_areply'] == 'on') {
				$subscribe			= &$request['dba']->prepareStatement("INSERT INTO ". K4SUBSCRIPTIONS ." (user_id,user_name,topic_id,forum_id,email,category_id) VALUES (?,?,?,?,?,?)");
				$subscribe->setInt(1, $request['user']->get('id'));
				$subscribe->setString(2, $request['user']->get('name'));
				$subscribe->setInt(3, $draft['id']);
				$subscribe->setInt(4, $forum['id']);
				$subscribe->setString(5, $request['user']->get('email'));
				$subscribe->setInt(6, $forum['category_id']);
				$subscribe->executeUpdate();
			}

			set_send_topic_mail($forum['id'], iif($poster_name == '', $request['template']->getVar('L_GUEST'), $poster_name));

			/* Redirect the user */
			$action = new K4InformationAction(new K4LanguageElement('L_ADDEDTOPIC', htmlentities($_REQUEST['name'], ENT_QUOTES), $forum['name']), 'content', FALSE, 'viewtopic.php?id='. $draft['id'], 3);

			return $action->execute($request);
		
		/* If we are previewing */
		} else {
			
			/**
			 * Post Previewing
			 */
			
			/* Get and set the emoticons and post icons to the template */
			$emoticons	= &$request['dba']->executeQuery("SELECT * FROM ". K4EMOTICONS ." WHERE clickable = 1");
			$posticons	= &$request['dba']->executeQuery("SELECT * FROM ". K4POSTICONS);
			
			/* Add the emoticons and posticons */
			$request['template']->setList('emoticons', $emoticons);
			$request['template']->setList('posticons', $posticons);
			
			/* Set some emoticon information */
			$request['template']->setVar('emoticons_per_row', $request['template']->getVar('smcolumns'));
			$request['template']->setVar('emoticons_per_row_remainder', $request['template']->getVar('smcolumns')-1);
			
			$request['template']->setVar('newtopic_action', 'newtopic.php?act=postdraft');

			topic_post_options(&$request['template'], &$request['user'], $forum);
			
			/* Set topic iterator array elements to be passed to the template */
			$topic_preview	= array(
								'id' => @$draft['id'],
								'name' => htmlentities($_REQUEST['name'], ENT_QUOTES),
								'posticon' => (isset($_REQUEST['posticon']) ? $_REQUEST['posticon'] : 'clear.gif'),
								'body_text' => $body_text,
								'poster_name' => html_entity_decode($draft['poster_name'], ENT_QUOTES),
								'poster_id' => $request['user']->get('id'),
								'row_left' => 0,
								'row_right' => 0,
								'topic_type' => $topic_type,
								'is_feature' => $is_feature,
								'posticon' => iif(($request['user']->get('perms') >= get_map($request['user'], 'posticons', 'can_add', array('forum_id'=>$forum['id']))), (isset($_REQUEST['posticon']) ? $_REQUEST['posticon'] : 'clear.gif'), 'clear.gif'),
								'disable_html' => iif((isset($_REQUEST['disable_html']) && $_REQUEST['disable_html'] == 'on'), 1, 0),
								'disable_sig' => iif((isset($_REQUEST['enable_sig']) && $_REQUEST['enable_sig'] == 'on'), 0, 1),
								'disable_bbcode' => iif((isset($_REQUEST['disable_bbcode']) && $_REQUEST['disable_bbcode'] == 'on'), 1, 0),
								'disable_emoticons' => iif((isset($_REQUEST['disable_emoticons']) && $_REQUEST['disable_emoticons'] == 'on'), 1, 0),
								'disable_areply' => iif((isset($_REQUEST['disable_areply']) && $_REQUEST['disable_areply'] == 'on'), 1, 0),
								'disable_aurls' => iif((isset($_REQUEST['disable_aurls']) && $_REQUEST['disable_aurls'] == 'on'), 1, 0)
								);

			/* Add the topic information to the template */
			$topic_iterator = &new TopicIterator($request['dba'], $request['user'], $topic_preview, FALSE);
			$request['template']->setList('topic', $topic_iterator);
			
			/* Assign the topic preview values to the template */
			$topic_preview['body_text'] = $_REQUEST['message'];
			foreach($topic_preview as $key => $val)
				$request['template']->setVar('topic_'. $key, $val);
			
			/* Assign the forum information to the template */
			foreach($forum as $key => $val)
				$request['template']->setVar('forum_'. $key, $val);

			/* Set the the button display options */
			$request['template']->setVisibility('save_draft', FALSE);
			$request['template']->setVisibility('load_button', FALSE);
			$request['template']->setVisibility('edit_topic', TRUE);
			$request['template']->setVisibility('topic_id', TRUE);
			
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_POSTTOPIC', $forum);
			
			/* Set the post topic form */
			$request['template']->setFile('preview', 'post_preview.html');
			$request['template']->setFile('content', 'newtopic.html');
		}

		return TRUE;
	}
}


/**
 * Delete a topic draft
 */
class DeleteDraft extends FAAction {
	function execute(&$request) {
		
		global $_QUERYPARAMS, $_DATASTORE;

		$this->dba			= &$request['dba'];
		
		/* Get our draft */
		$draft				= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['topic'] ." FROM ". K4TOPICS ." t LEFT JOIN ". K4INFO ." i ON t.topic_id = i.id WHERE i.id = ". intval($_REQUEST['id']) ." AND t.is_draft = 1 AND t.poster_id = ". intval($request['user']->get('id')));
		
		if(!$draft || !is_array($draft) || empty($draft)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INVALIDDRAFT');
			$action = new K4InformationAction(new K4LanguageElement('L_DRAFTDOESNTEXIST'), 'content', FALSE);

			return $action->execute($request);

			return TRUE;
		}
		
		$forum				= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['forum'] ." FROM ". K4FORUMS ." f LEFT JOIN ". K4INFO ." i ON f.forum_id = i.id WHERE i.id = ". intval($draft['forum_id']));
		
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

		/* set the breadcrumbs bit */
		k4_bread_crumbs(&$request['template'], $request['dba'], 'L_DELETEDRAFT', $forum);
		
		/* Remove this draft from the information table */
		$h			= &new Heirarchy();
		//$h->removeNode($draft, K4INFO);
		$h->removeItem($draft, K4INFO);
		
		/* Now remove the information stored in the topics table */
		$request['dba']->executeUpdate("DELETE FROM ". K4TOPICS ." WHERE topic_id = ". intval($draft['id']) ." AND is_draft = 1");
		
		/* Redirect the user */
		$action = new K4InformationAction(new K4LanguageElement('L_REMOVEDDRAFT', $draft['name'], $forum['name']), 'content', FALSE, 'viewforum.php?id='. $forum['id'], 3);

		return $action->execute($request);

		return TRUE;
	}
}

/**
 * Edit a topic
 */
class EditTopic extends FAAction {
	function execute(&$request) {
		
		global $_QUERYPARAMS, $_DATASTORE;
		
		/* Get our topic */
		$topic				= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['topic'] ." FROM ". K4TOPICS ." t LEFT JOIN ". K4INFO ." i ON t.topic_id = i.id WHERE i.id = ". intval($_REQUEST['id']));
		
		if(!$topic || !is_array($topic) || empty($topic)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INVALIDDRAFT');
			$action = new K4InformationAction(new K4LanguageElement('L_DRAFTDOESNTEXIST'), 'content', FALSE);

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
			
		/* Make sure the we are trying to post into a forum */
		if(!($forum['row_type'] & FORUM)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_CANTPOSTTONONFORUM'), 'content', FALSE);

			return $action->execute($request);
			return TRUE;
		}
		
		/* set the breadcrumbs bit */
		k4_bread_crumbs(&$request['template'], $request['dba'], 'L_EDITTOPIC', $forum);
		
		if($topic['poster_id'] == $request['user']->get('id')) {
			if(get_map($request['user'], 'topics', 'can_edit', array('forum_id'=>$forum['id'])) > $request['user']->get('perms')) {
				$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

				return $action->execute($request);
				return TRUE;
			}
		} else {
			if(get_map($request['user'], 'other_topics', 'can_edit', array('forum_id'=>$forum['id'])) > $request['user']->get('perms')) {
				$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

				return $action->execute($request);
				return TRUE;
			}
		}

		/* Does this user have permission to edit this topic if it is locked? */
		if($topic['topic_locked'] == 1 && get_map($request['user'], 'closed', 'can_edit', array('forum_id' => $forum['id'])) > $request['user']->get('perms')) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

			return $action->execute($request);
			return TRUE;
		}
		
		$bbcode				= &new BBCodex(&$request['dba'], $request['user']->getInfoArray(), $topic['body_text'], $forum['id'], TRUE, TRUE, TRUE, TRUE);
		
		/* Get and set the emoticons and post icons to the template */
		$emoticons			= &$request['dba']->executeQuery("SELECT * FROM ". K4EMOTICONS ." WHERE clickable = 1");
		$posticons			= &$request['dba']->executeQuery("SELECT * FROM ". K4POSTICONS);

		$request['template']->setList('emoticons', $emoticons);
		$request['template']->setList('posticons', $posticons);

		$request['template']->setVar('emoticons_per_row', $request['template']->getVar('smcolumns'));
		$request['template']->setVar('emoticons_per_row_remainder', $request['template']->getVar('smcolumns')-1);
		
		$request['template']->setVar('newtopic_action', 'newtopic.php?act=updatetopic');

		topic_post_options(&$request['template'], &$request['user'], $forum);
		
		$topic['body_text'] = $bbcode->revert();

		foreach($topic as $key => $val)
			$request['template']->setVar('topic_'. $key, $val);
		
		/* Assign the forum information to the template */
		foreach($forum as $key => $val)
			$request['template']->setVar('forum_'. $key, $val);

		/* Set the the button display options */
		$request['template']->setVisibility('save_draft', FALSE);
		$request['template']->setVisibility('load_button', FALSE);
		$request['template']->setVisibility('edit_topic', TRUE);
		$request['template']->setVisibility('topic_id', TRUE);
		$request['template']->setVisibility('post_topic', FALSE);
		$request['template']->setVisibility('edit_post', TRUE);

		/* set the breadcrumbs bit */
		k4_bread_crumbs(&$request['template'], $request['dba'], 'L_EDITTOPIC', $forum);
		
		/* Set the post topic form */
		$request['template']->setFile('preview', 'post_preview.html');
		$request['template']->setFile('content', 'newtopic.html');

		return TRUE;
	}
}

/**
 * Update a topic
 */
class UpdateTopic extends FAAction {
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
		
		/* Get our topic */
		$topic				= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['topic'] ." FROM ". K4TOPICS ." t LEFT JOIN ". K4INFO ." i ON t.topic_id = i.id WHERE i.id = ". intval($_REQUEST['topic_id']));
		
		if(!$topic || !is_array($topic) || empty($topic)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INVALIDTOPIC');
			$action = new K4InformationAction(new K4LanguageElement('L_TOPICDOESNTEXIST'), 'content', FALSE);

			return $action->execute($request);

			return TRUE;
		}

		$type				= $topic['poll'] == 1 ? 'polls' : 'topics';

		/* Does this person have permission to edit this topic? */
		if($topic['poster_id'] == $request['user']->get('id')) {
			if(get_map($request['user'], $type, 'can_edit', array('forum_id'=>$forum['id'])) > $request['user']->get('perms')) {
				$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

				return $action->execute($request);
				return TRUE;
			}
		} else {
			if(get_map($request['user'], 'other_'. $type, 'can_edit', array('forum_id'=>$forum['id'])) > $request['user']->get('perms')) {
				$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

				return $action->execute($request);
				return TRUE;
			}
		}
		
		/* Does this user have permission to edit this topic if it is locked? */
		if($topic['topic_locked'] == 1 && get_map($request['user'], 'closed', 'can_edit', array('forum_id' => $forum['id'])) > $request['user']->get('perms')) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

			return $action->execute($request);
			return TRUE;
		}

		/* set the breadcrumbs bit */
		k4_bread_crumbs(&$request['template'], $request['dba'], 'L_EDITTOPIC', $forum);
				
		/* Initialize the bbcode parser with the topic message */
		$_REQUEST['message']	= substr($_REQUEST['message'], 0, $_SETTINGS['postmaxchars']);
		$bbcode	= &new BBCodex(&$request['dba'], $request['user']->getInfoArray(), $_REQUEST['message'], $forum['id'], 
			iif((isset($_REQUEST['disable_html']) && $_REQUEST['disable_html'] == 'on'), FALSE, TRUE), 
			iif((isset($_REQUEST['disable_bbcode']) && $_REQUEST['disable_bbcode'] == 'on'), FALSE, TRUE), 
			iif((isset($_REQUEST['disable_emoticons']) && $_REQUEST['disable_emoticons'] == 'on'), FALSE, TRUE), 
			iif((isset($_REQUEST['disable_aurls']) && $_REQUEST['disable_aurls'] == 'on'), FALSE, TRUE));
		
		/* Parse the bbcode */
		$body_text = $bbcode->parse();
		
		$request['template']->setVar('newtopic_action', 'newtopic.php?act=updatetopic');
		
		/* Get the topic type */
		$topic_type			= isset($_REQUEST['topic_type']) && intval($_REQUEST['topic_type']) != 0 ? $_REQUEST['topic_type'] : TOPIC_NORMAL;
		
		/* Check the topic type and check if this user has permission to post that type of topic */
		if($topic_type == TOPIC_STICKY && $request['user']->get('perms') < get_map($request['user'], 'sticky', 'can_add', array('forum_id'=>$forum['id']))) {
			$topic_type		= TOPIC_NORMAL;
		} else if($topic_type == TOPIC_ANNOUNCE && $request['user']->get('perms') < get_map($request['user'], 'announce', 'can_add', array('forum_id'=>$forum['id']))) {
			$topic_type		= TOPIC_NORMAL;
		} else if($topic_type == TOPIC_GLOBAL && $request['user']->get('perms') < get_map($request['user'], 'global', 'can_add', array('forum_id'=>$forum['id']))) {
			$topic_type		= TOPIC_NORMAL;
		}
		
		/* Is this a featured topic? */
		$is_feature			= isset($_REQUEST['is_feature']) && $_REQUEST['is_feature'] == 'yes' ? 1 : 0;
		if($is_feature == 1 && $request['user']->get('perms') < get_map($request['user'], 'feature', 'can_add', array('forum_id'=>$forum['id']))) {
			$is_feature		= 0;
		}

		/* If we are saving thos topic */
		if($_REQUEST['submit'] == $request['template']->getVar('L_SUBMIT')) {

			/**
			 * Build the queries to update the topic
			 */
			
			$update_a			= $request['dba']->prepareStatement("UPDATE ". K4INFO ." SET name=? WHERE id=?");
			$update_b			= $request['dba']->prepareStatement("UPDATE ". K4TOPICS ." SET body_text=?,posticon=?,disable_html=?,disable_bbcode=?,disable_emoticons=?,disable_sig=?,disable_areply=?,disable_aurls=?,is_draft=?,edited_time=?,edited_username=?,edited_userid=?,is_feature=?,topic_type=?,topic_expire=? WHERE topic_id=?");
			
			$update_a->setString(1, htmlentities($_REQUEST['name'], ENT_QUOTES));
			$update_a->setInt(2, $topic['id']);
			
			$update_b->setString(1, $body_text);
			$update_b->setString(2, iif(($request['user']->get('perms') >= get_map($request['user'], 'posticons', 'can_add', array('forum_id'=>$forum['id']))), (isset($_REQUEST['posticon']) ? $_REQUEST['posticon'] : 'clear.gif'), 'clear.gif'));
			$update_b->setInt(3, iif((isset($_REQUEST['disable_html']) && $_REQUEST['disable_html'] == 'on'), 1, 0));
			$update_b->setInt(4, iif((isset($_REQUEST['disable_bbcode']) && $_REQUEST['disable_bbcode'] == 'on'), 1, 0));
			$update_b->setInt(5, iif((isset($_REQUEST['disable_emoticons']) && $_REQUEST['disable_emoticons'] == 'on'), 1, 0));
			$update_b->setInt(6, iif((isset($_REQUEST['enable_sig']) && $_REQUEST['enable_sig'] == 'on'), 0, 1));
			$update_b->setInt(7, iif((isset($_REQUEST['disable_areply']) && $_REQUEST['disable_areply'] == 'on'), 1, 0));
			$update_b->setInt(8, iif((isset($_REQUEST['disable_aurls']) && $_REQUEST['disable_aurls'] == 'on'), 1, 0));
			$update_b->setInt(9, 0);
			$update_b->setInt(10, time());
			$update_b->setString(11, iif($request['user']->get('id') <= 0,  htmlentities((isset($_REQUEST['poster_name']) ? $_REQUEST['poster_name'] : '') , ENT_QUOTES), $request['user']->get('name')));
			$update_b->setInt(12, $request['user']->get('id'));
			$update_b->setInt(13, $is_feature);
			$update_b->setInt(14, $topic_type);
			$update_b->setInt(15, iif($topic_type > TOPIC_NORMAL, intval((isset($_REQUEST['topic_expire']) ? $_REQUEST['topic_expire'] : 0)), 0) );
			$update_b->setInt(16, $topic['id']);
			
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
			$action = new K4InformationAction(new K4LanguageElement('L_UPDATEDTOPIC', htmlentities($_REQUEST['name'], ENT_QUOTES)), 'content', FALSE, 'viewtopic.php?id='. $topic['id'], 3);

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
						
			$topic_preview	= array(
								'id' => @$topic['id'],
								'name' => htmlentities($_REQUEST['name'], ENT_QUOTES),
								'posticon' => (isset($_REQUEST['posticon']) ? $_REQUEST['posticon'] : 'clear.gif'),
								'body_text' => $body_text,
								'poster_name' => html_entity_decode($topic['poster_name'], ENT_QUOTES),
								'poster_id' => $request['user']->get('id'),
								'row_left' => 0,
								'row_right' => 0,
								'topic_type' => $topic_type,
								'is_feature' => $is_feature,
								'disable_html' => iif((isset($_REQUEST['disable_html']) && $_REQUEST['disable_html'] == 'on'), 1, 0),
								'disable_sig' => iif((isset($_REQUEST['enable_sig']) && $_REQUEST['enable_sig'] == 'on'), 0, 1),
								'disable_bbcode' => iif((isset($_REQUEST['disable_bbcode']) && $_REQUEST['disable_bbcode'] == 'on'), 1, 0),
								'disable_emoticons' => iif((isset($_REQUEST['disable_emoticons']) && $_REQUEST['disable_emoticons'] == 'on'), 1, 0),
								'disable_areply' => iif((isset($_REQUEST['disable_areply']) && $_REQUEST['disable_areply'] == 'on'), 1, 0),
								'disable_aurls' => iif((isset($_REQUEST['disable_aurls']) && $_REQUEST['disable_aurls'] == 'on'), 1, 0)
								);

			/* Add the topic information to the template */
			$topic_iterator = &new TopicIterator($request['dba'], $request['user'], $topic_preview, FALSE);
			$request['template']->setList('topic', $topic_iterator);
			
			/* Assign the topic preview values to the template */
			$topic_preview['body_text'] = $_REQUEST['message'];
			foreach($topic_preview as $key => $val)
				$request['template']->setVar('topic_'. $key, $val);
			
			/* Assign the forum information to the template */
			foreach($forum as $key => $val)
				$request['template']->setVar('forum_'. $key, $val);

			/* Set the the button display options */
			$request['template']->setVisibility('save_draft', FALSE);
			$request['template']->setVisibility('load_button', FALSE);
			$request['template']->setVisibility('edit_topic', TRUE);
			$request['template']->setVisibility('topic_id', TRUE);
			$request['template']->setVisibility('post_topic', FALSE);
			$request['template']->setVisibility('edit_post', TRUE);
			
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_POSTTOPIC', $forum);
			
			/* Set the post topic form */
			$request['template']->setFile('preview', 'post_preview.html');
			$request['template']->setFile('content', 'newtopic.html');
		}

		return TRUE;
	}
}

/**
 * Delete a topic
 */
class DeleteTopic extends FAAction {
	function execute(&$request) {
		
		global $_QUERYPARAMS, $_DATASTORE, $_USERGROUPS;
		
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
		
		/* set the breadcrumbs bit */
		k4_bread_crumbs(&$request['template'], $request['dba'], 'L_DELETETOPIC', $forum);
		
		/* Are we dealing with a topic or a poll? */
		$type				= $topic['poll'] == 1 ? 'polls' : 'topics';

		/* Does this person have permission to remove this topic? */
		if($topic['poster_id'] == $request['user']->get('id')) {
			if(get_map($request['user'], $type, 'can_del', array('forum_id'=>$forum['id'])) > $request['user']->get('perms')) {
				$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

				return $action->execute($request);
				return TRUE;
			}
		} else {
			if(get_map($request['user'], 'other_'. $type, 'can_del', array('forum_id'=>$forum['id'])) > $request['user']->get('perms')) {
				$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

				return $action->execute($request);
				return TRUE;
			}
		}
		
		$user_usergroups	= $request['user']->get('usergroups') != '' ? (!unserialize($request['user']->get('usergroups')) ? array() : unserialize($request['user']->get('usergroups'))) : array();
		$forum_usergroups	= $forum['moderating_groups'] != '' ? (!unserialize($forum['moderating_groups']) ? array() : unserialize($forum['moderating_groups'])) : array();
		
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
		
		/**
		 * Remove the topic and all of its replies
		 */
		
		/* Remove the topic and all replies from the information table */
		remove_item($topic['id'], 'topic_id');
		

		if(!@touch(CACHE_DS_FILE, time()-86460)) {
			@unlink(CACHE_DS_FILE);
		}
		if(!@touch(CACHE_EMAIL_FILE, time()-86460)) {
			@unlink(CACHE_EMAIL_FILE);
		}
		
		/* Redirect the user */
		$action = new K4InformationAction(new K4LanguageElement('L_DELETEDTOPIC', $topic['name'], $forum['name']), 'content', FALSE, 'viewforum.php?id='. $forum['id'], 3);

		return $action->execute($request);
		return TRUE;
	}
}

/**
 * Set the topic locking parameters
 */
class LockTopic extends FAAction {
	function execute(&$request) {
		
		global $_QUERYPARAMS, $_DATASTORE, $_USERGROUPS;
		
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

		if(get_map($request['user'], 'closed', 'can_add', array('forum_id' => $forum['id'])) > $request['user']->get('perms')) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

			return $action->execute($request);
			return TRUE;
		}
		
		/* set the breadcrumbs bit */
		k4_bread_crumbs(&$request['template'], $request['dba'], 'L_LOCKTOPIC', $topic, $forum);
	
		/* Lock the topic */
		$lock		= &$request['dba']->prepareStatement("UPDATE ". K4TOPICS ." SET topic_locked=1 WHERE topic_id=?");
		$lock->setInt(1, $topic['id']);
		$lock->executeUpdate();

		/* Redirect the user */
		$action = new K4InformationAction(new K4LanguageElement('L_LOCKEDTOPIC', $topic['name']), 'content', FALSE, 'viewtopic.php?id='. $topic['id'], 3);

		return $action->execute($request);
		return TRUE;
	}
}

/**
 * Set the topic locking parameters
 */
class UnlockTopic extends FAAction {
	function execute(&$request) {
		
		global $_QUERYPARAMS, $_DATASTORE, $_USERGROUPS;
		
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

		if(get_map($request['user'], 'closed', 'can_add', array('forum_id' => $forum['id'])) > $request['user']->get('perms')) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

			return $action->execute($request);
			return TRUE;
		}
		
		/* set the breadcrumbs bit */
		k4_bread_crumbs(&$request['template'], $request['dba'], 'L_UNLOCKTOPIC', $topic, $forum);
	
		/* Lock the topic */
		$lock		= &$request['dba']->prepareStatement("UPDATE ". K4TOPICS ." SET topic_locked=0 WHERE topic_id=?");
		$lock->setInt(1, $topic['id']);
		$lock->executeUpdate();

		/* Redirect the user */
		$action = new K4InformationAction(new K4LanguageElement('L_UNLOCKEDTOPIC', $topic['name']), 'content', FALSE, 'viewtopic.php?id='. $topic['id'], 3);

		return $action->execute($request);
		return TRUE;
	}
}

/**
 * Subscribe to a topic
 */
class SubscribeTopic extends FAAction {
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
		
		$forum				= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['forum'] ." FROM ". K4FORUMS ." f LEFT JOIN ". K4INFO ." i ON f.forum_id = i.id WHERE i.id = ". intval($topic['forum_id']));
		
		/* Check the forum data given */
		if(!$forum || !is_array($forum) || empty($forum)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INVALIDFORUM');
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);

			return $action->execute($request);
			return TRUE;
		}

		$is_subscribed		= $request['dba']->getRow("SELECT * FROM ". K4SUBSCRIPTIONS ." WHERE user_id = ". intval($request['user']->get('id')) ." AND topic_id = ". intval($topic['id']));
		
		if(is_array($is_subscribed) && !empty($is_subscribed)) {
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_SUBSCRIPTION', $topic, $forum);
			$action = new K4InformationAction(new K4LanguageElement('L_ALREADYSUBSCRIBED'), 'content', FALSE);

			return $action->execute($request);
			return TRUE;
		}
		
		$subscribe			= &$request['dba']->prepareStatement("INSERT INTO ". K4SUBSCRIPTIONS ." (user_id,user_name,topic_id,forum_id,email,category_id) VALUES (?,?,?,?,?,?)");
		$subscribe->setInt(1, $request['user']->get('id'));
		$subscribe->setString(2, $request['user']->get('name'));
		$subscribe->setInt(3, $topic['id']);
		$subscribe->setInt(4, $topic['forum_id']);
		$subscribe->setString(5, $request['user']->get('email'));
		$subscribe->setInt(6, $topic['category_id']);
		$subscribe->executeUpdate();

		/* Redirect the user */
		k4_bread_crumbs(&$request['template'], $request['dba'], 'L_SUBSCRIPTIONS', $topic, $forum);
		$action = new K4InformationAction(new K4LanguageElement('L_SUBSCRIBEDTOPIC', $topic['name']), 'content', FALSE, 'viewtopic.php?id='. $topic['id'], 3);

		return $action->execute($request);
		
		return TRUE;
	}
}

/**
 * Unsubscribe from a topic
 */
class UnsubscribeTopic extends FAAction {
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
		
		$forum				= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['forum'] ." FROM ". K4FORUMS ." f LEFT JOIN ". K4INFO ." i ON f.forum_id = i.id WHERE i.id = ". intval($topic['forum_id']));
		
		/* Check the forum data given */
		if(!$forum || !is_array($forum) || empty($forum)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INVALIDFORUM');
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);

			return $action->execute($request);
			return TRUE;
		}
		
		$subscribe			= &$request['dba']->prepareStatement("DELETE FROM ". K4SUBSCRIPTIONS ." WHERE user_id=? AND topic_id=?");
		$subscribe->setInt(1, $request['user']->get('id'));
		$subscribe->setInt(2, $topic['id']);
		$subscribe->executeUpdate();

		/* Redirect the user */
		k4_bread_crumbs(&$request['template'], $request['dba'], 'L_SUBSCRIPTIONS', $topic, $forum);
		$action = new K4InformationAction(new K4LanguageElement('L_UNSUBSCRIBEDTOPIC', $topic['name']), 'content', FALSE, 'viewtopic.php?id='. $topic['id'], 3);

		return $action->execute($request);
		
		return TRUE;
	}
}

/**
 * Make the topic image for a specified topic
 */
function topic_image($topic, &$user, $img_dir) {
	
	global $_SETTINGS;
		
	$EXT						= '.gif';
		
	$type						= '';
	$use_dot					= (bool)($user->get('id') == $topic['poster_id']);
	$new						= (bool)($topic['reply_time'] >= $user->get('last_seen'));
	$hot						= (bool)(($topic['views'] >= 300) || ($topic['num_replies'] >= 30));
	
	if($topic['topic_type']		== TOPIC_GLOBAL) {
		$type					= 'announce';
		$use_dot				= FALSE;
		$hot					= FALSE;
	
	} elseif($topic['topic_type']		== TOPIC_ANNOUNCE) {
		$type					= 'announce';
		$use_dot				= FALSE;
		$hot					= FALSE;
	
	} elseif($topic['topic_type']		== TOPIC_STICKY) {
		$type					= 'sticky';
		$use_dot				= FALSE;
		$hot					= FALSE;
	
	} elseif($topic['is_feature']		== TOPIC_STICKY) {
		$type					= 'sticky';
		$use_dot				= FALSE;
		$hot					= FALSE;
	
	} elseif($topic['topic_type']		== TOPIC_NORMAL) {
		
		if($topic['moved']				== 1) {
			$type				= 'movedfolder';
			$use_dot			= FALSE;
			$hot				= FALSE;
		
		} elseif($topic['topic_locked'] == 1) {
			$type				= 'lockfolder';
		
		} else {
			$type				= 'folder';
		}
	}
	
	$image						= 'Images/'. $img_dir .'/Icons/Status/'. iif($use_dot, 'dot_', '') . iif($new, 'new', '') . iif($hot, 'hot', '') . $type . $EXT;
	
	return $image;
}

class TopicsIterator extends FAProxyIterator {
	
	var $result;
	var $session;
	var $img_dir;
	var $forums;
	var $dba;
	var $user;

	function __construct(&$dba, &$user, $result, $img_dir, $forum) {

		$this->result			= &$result;
		$this->session			= $_SESSION;
		$this->img_dir			= $img_dir;
		$this->forum			= $forum;
		$this->dba				= &$dba;
		$this->user				= &$user;
		
		parent::__construct($this->result);
	}

	function current() {
		$temp					= parent::current();

		/* Get this user's last seen time */
		//$last_seen				= is_a($this->session['user'], 'Member') ? iif($this->session['seen'] > $this->session['user']->info['last_seen'], $this->session['seen'], $this->session['user']->info['last_seen']) : $this->session['seen'];
		$last_seen				= time();

		/* Set the topic icons */
		$temp['posticon']		= $temp['posticon'] != '' ? iif(file_exists(BB_BASE_DIR .'/tmp/upload/posticons/'. $temp['posticon']), $temp['posticon'], 'clear.gif') : 'clear.gif';
		$temp['topicicon']		= topic_image($temp, &$this->user, $this->img_dir, $last_seen);
		
		/* Set the number of replies */
		//$temp['num_replies']	= @(($temp['row_right'] - $temp['row_left'] - 1) / 2);
		
		if($this->forum['postsperpage'] < $temp['num_replies']) {
			
			/* Create a pager */
			$temp['pager']		= paginate($temp['num_replies'], '&laquo;', '&lt;', '', '&gt;', '&raquo;', $this->forum['postsperpage'], $temp['id']);
		}

		/* Check if this topic has been read or not */
		//if(($temp['created'] > $last_seen && $temp['poster_id'] != $this->session['user']->info['id'])
		//|| (isset($this->forums[$temp['forum_id']][$temp['id']]) && $this->forums[$temp['forum_id']][$temp['id']])
		//	) {
		//	
		//	$this->forums[$temp['forum_id']][$temp['id']]	= TRUE;
		//	
		//	$temp['name']									= '<strong>'. $temp['name'] .'</strong>';
		//}
		
		/* Is this a sticky or an announcement and is it expired? */
		if($temp['topic_type'] > TOPIC_NORMAL && $temp['topic_expire'] > 0) {
			if(($temp['created'] + (3600 * 24 * $temp['topic_expire']) ) > time()) {
				
				$this->dba->executeUpdate("UPDATE ". K4TOPICS ." SET topic_expire=0,topic_type=". TOPIC_NORMAL ." WHERE topic_id = ". intval($temp['id']));
			}
		}

		/* Should we free the result? */
		if(!$this->hasNext())
			$this->result->free();
			
			/* Reset the forums cookie if we're at the end of the iteration */
			//bb_settopic_cache_item('forums', serialize($this->forums), time() + 3600 * 25 * 5);
		//}

		return $temp;
	}
}

class TopicIterator extends FAArrayIterator {
	
	var $dba;
	var $result;
	var $users = array();
	var $qp;
	var $sr;
	var $user;

	function __construct(&$dba, &$user, $topic, $show_replies = TRUE) {
		
		global $_QUERYPARAMS, $_USERGROUPS, $_USERFIELDS;
		
		$this->qp						= $_QUERYPARAMS;
		$this->sr						= (bool)$show_replies;
		$this->dba						= &$dba;
		$this->user						= &$user;
		$this->groups					= $_USERGROUPS;
		$this->fields					= $_USERFIELDS;
		
		parent::__construct(array(0 => $topic));
	}

	function &current() {
		$temp							= parent::current();
				
		$temp['posticon']				= @$temp['posticon'] != '' ? (file_exists(BB_BASE_DIR .'/tmp/upload/posticons/'. @$temp['posticon']) ? @$temp['posticon'] : 'clear.gif') : 'clear.gif';

		if($temp['poster_id'] > 0) {
			$user						= $this->dba->getRow("SELECT ". $this->qp['user'] . $this->qp['userinfo'] ." FROM ". K4USERS ." u LEFT JOIN ". K4USERINFO ." ui ON u.id=ui.user_id WHERE u.id=". intval($temp['poster_id']));
			
			$group						= get_user_max_group($user, $this->groups);
			$user['group_color']		= (!isset($group['color']) || $group['color'] == '') ? '000000' : $group['color'];
			$user['group_nicename']		= $group['nicename'];
			$user['group_avatar']		= $group['avatar'];
			$user['online']				= (time() - ini_get('session.gc_maxlifetime')) > $user['seen'] ? 'offline' : 'online';
			
			foreach($user as $key => $val)
				$temp['post_user_'. $key] = $val;
			
			$fields						= array();
			
			foreach($this->fields as $field) {
				
				if($field['display_topic'] == 1) {

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
			
			$temp['profilefields'] = &new FAArrayIterator($fields);

			/* This array holds all of the userinfo for users that post to this topic */
			$this->users[$user['id']]	= $user;
			
		} else {
			$temp['post_user_id']	= 0;
			$temp['post_user_name']	= $temp['poster_name'];
		}
	
		
		/* Do we have any replies? */
		//$num_replies					= @(($temp['row_right'] - $temp['row_left'] - 1) / 2);

		if($this->sr && $temp['num_replies'] > 0) {
			$this->result				= &$this->dba->executeQuery("SELECT ". $this->qp['info'] . $this->qp['reply'] ." FROM ". K4REPLIES ." r LEFT JOIN ". K4INFO ." i ON i.id=r.reply_id WHERE r.topic_id = ". intval($temp['id']) ." AND i.created >= ". (3600 * 24 * intval($temp['daysprune'])) ." ORDER BY i.". $temp['sortedby'] ." ". $temp['sortorder'] ." LIMIT ". intval($temp['start']) .", ". intval($temp['postsperpage']));
			
			$temp['replies']			= &new RepliesIterator($this->user, $this->dba, $this->result, $this->qp, $this->users, $this->groups, $this->fields);

		}
		
		return $temp;
	}
}


function topic_post_options(&$template, &$user, $forum) {
	
	/** 
	 * Set the posting allowances for a specific forum
	 */
	$template->setVar('forum_user_topic_options', sprintf($template->getVar('L_FORUMUSERTOPICPERMS'),
	iif((get_map($user, 'topics', 'can_add', array('forum_id'=>$forum['id'])) > $user->get('perms')), $template->getVar('L_CANNOT'), $template->getVar('L_CAN')),
	iif((get_map($user, 'topics', 'can_edit', array('forum_id'=>$forum['id'])) > $user->get('perms')), $template->getVar('L_CANNOT'), $template->getVar('L_CAN')),
	iif((get_map($user, 'topics', 'can_del', array('forum_id'=>$forum['id'])) > $user->get('perms')), $template->getVar('L_CANNOT'), $template->getVar('L_CAN')),
	iif((get_map($user, 'attachments', 'can_add', array('forum_id'=>$forum['id'])) > $user->get('perms')), $template->getVar('L_CANNOT'), $template->getVar('L_CAN'))));

	$template->setVar('forum_user_reply_options', sprintf($template->getVar('L_FORUMUSERREPLYPERMS'),
	iif((get_map($user, 'replies', 'can_add', array('forum_id'=>$forum['id'])) > $user->get('perms')), $template->getVar('L_CANNOT'), $template->getVar('L_CAN')),
	iif((get_map($user, 'replies', 'can_edit', array('forum_id'=>$forum['id'])) > $user->get('perms')), $template->getVar('L_CANNOT'), $template->getVar('L_CAN')),
	iif((get_map($user, 'replies', 'can_del', array('forum_id'=>$forum['id'])) > $user->get('perms')), $template->getVar('L_CANNOT'), $template->getVar('L_CAN'))));
	
	$template->setVar('posting_code_options', sprintf($template->getVar('L_POSTBBCODEOPTIONS'),
	iif((get_map($user, 'html', 'can_add', array('forum_id'=>$forum['id'])) > $user->get('perms')), $template->getVar('L_OFF'), $template->getVar('L_ON')),
	iif((get_map($user, 'bbcode', 'can_add', array('forum_id'=>$forum['id'])) > $user->get('perms')), $template->getVar('L_OFF'), $template->getVar('L_ON')),
	iif((get_map($user, 'bbimgcode', 'can_add', array('forum_id'=>$forum['id'])) > $user->get('perms')), $template->getVar('L_OFF'), $template->getVar('L_ON')),
	iif((get_map($user, 'bbflashcode', 'can_add', array('forum_id'=>$forum['id'])) > $user->get('perms')), $template->getVar('L_OFF'), $template->getVar('L_ON')),
	iif((get_map($user, 'emoticons', 'can_add', array('forum_id'=>$forum['id'])) > $user->get('perms')), $template->getVar('L_OFF'), $template->getVar('L_ON'))));
}

?>