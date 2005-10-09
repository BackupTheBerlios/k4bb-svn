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
* @version $Id: topics.class.php 158 2005-07-18 02:55:30Z Peter Goodman $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	return;
}

/**
 * Post / Preview a topic
 */
class PostTopic extends FAAction {
	function execute(&$request) {

		global $_QUERYPARAMS, $_DATASTORE, $_SETTINGS;

		$this->dba			= &$request['dba'];

		/* Prevent post flooding */
		$last_topic		= $request['dba']->getRow("SELECT * FROM ". K4TOPICS ." WHERE poster_ip = '". USER_IP ."' ". ($request['user']->isMember() ? "OR poster_id = ". intval($request['user']->get('id')) : '') ." ORDER BY created DESC LIMIT 1");
		$last_reply		= $request['dba']->getRow("SELECT * FROM ". K4REPLIES ." WHERE poster_ip = '". USER_IP ."' ". ($request['user']->isMember() ? "OR poster_id = ". intval($request['user']->get('id')) : '') ." ORDER BY created DESC LIMIT 1");
		
		k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
		
		if(is_array($last_topic) && !empty($last_topic)) {
			if(intval($last_topic['created']) + POST_IMPULSE_LIMIT > time() && $request['user']->get('perms') < MODERATOR) {
				$action = new K4InformationAction(new K4LanguageElement('L_MUSTWAITSECSTOPOST'), 'content', TRUE);
				return !USE_AJAX ? $action->execute($request) : ajax_message('L_MUSTWAITSECSTOPOST');
			}
		}

		if(is_array($last_reply) && !empty($last_reply)) {
			if(intval($last_reply['created']) + POST_IMPULSE_LIMIT > time() && $request['user']->get('perms') < MODERATOR) {
				$action = new K4InformationAction(new K4LanguageElement('L_MUSTWAITSECSTOPOST'), 'content', TRUE);
				return !USE_AJAX ? $action->execute($request) : ajax_message('L_MUSTWAITSECSTOPOST');
			}
		}
		
		/**
		 * Error checking
		 */

		/* Check the request ID */
		if(!isset($_REQUEST['forum_id']) || !$_REQUEST['forum_id'] || intval($_REQUEST['forum_id']) == 0) {
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);
			return !USE_AJAX ? $action->execute($request) : ajax_message('L_FORUMDOESNTEXIST');
		}
			
		$forum				= $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($_REQUEST['forum_id']));
		
		/* Check the forum data given */
		if(!$forum || !is_array($forum) || empty($forum)) {
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);
			return !USE_AJAX ? $action->execute($request) : ajax_message('L_FORUMDOESNTEXIST');
		}
			
		/* Make sure the we are trying to post into a forum */
		if(!($forum['row_type'] & FORUM)) {
			$action = new K4InformationAction(new K4LanguageElement('L_CANTPOSTTONONFORUM'), 'content', FALSE);
			return !USE_AJAX ? $action->execute($request) : ajax_message('L_CANTPOSTTONONFORUM');
		}

		/* Do we have permission to post to this forum? */
		if($request['user']->get('perms') < get_map($request['user'], 'topics', 'can_add', array('forum_id'=>$forum['forum_id']))) {
			$action = new K4InformationAction(new K4LanguageElement('L_PERMCANTPOST'), 'content', FALSE);
			return !USE_AJAX ? $action->execute($request) : ajax_message('L_PERMCANTPOST');
		}

		/* General error checking */
		if(!isset($_REQUEST['name']) || $_REQUEST['name'] == '') {
			$action = new K4InformationAction(new K4LanguageElement('L_INSERTTOPICNAME'), 'content', TRUE);
			return !USE_AJAX ? $action->execute($request) : ajax_message('L_INSERTTOPICNAME');
		}

		if (!$this->runPostFilter('name', new FALengthFilter(intval($_SETTINGS['topicmaxchars'])))) {
			$action = new K4InformationAction(new K4LanguageElement('L_TITLETOOSHORT', intval($_SETTINGS['topicminchars']), intval($_SETTINGS['topicmaxchars'])), 'content', TRUE);
			return !USE_AJAX ? $action->execute($request) : ajax_message(new K4LanguageElement('L_TITLETOOSHORT', intval($_SETTINGS['topicminchars']), intval($_SETTINGS['topicmaxchars'])));
		}
		if (!$this->runPostFilter('name', new FALengthFilter(intval($_SETTINGS['topicmaxchars']), intval($_SETTINGS['topicminchars'])))) {
			$action = new K4InformationAction(new K4LanguageElement('L_TITLETOOSHORT', intval($_SETTINGS['topicminchars']), intval($_SETTINGS['topicmaxchars'])), 'content', TRUE);
			return !USE_AJAX ? $action->execute($request) : ajax_message(new K4LanguageElement('L_TITLETOOSHORT', intval($_SETTINGS['topicminchars']), intval($_SETTINGS['topicmaxchars'])));
		}

		if(!isset($_REQUEST['message']) || $_REQUEST['message'] == '') {
			$action = new K4InformationAction(new K4LanguageElement('L_INSERTTOPICMESSAGE'), 'content', TRUE);
			return !USE_AJAX ? $action->execute($request) : ajax_message('L_INSERTTOPICMESSAGE');
		}				

		/**
		 * Start building info for the queries
		 */

		/* Set this nodes level */
		$level					= $forum['row_level']+1;
		
		/* Set the topic created time */
		$created				= time();
		
		$_REQUEST['message']	= substr($_REQUEST['message'], 0, $_SETTINGS['postmaxchars']);
		
		/* Initialize the bbcode parser with the topic message */
		$bbcode	= &new BBCodex($request['dba'], $request['user']->getInfoArray(), $_REQUEST['message'], $forum['forum_id'], 
			iif((isset($_REQUEST['disable_html']) && $_REQUEST['disable_html']), FALSE, TRUE), 
			iif((isset($_REQUEST['disable_bbcode']) && $_REQUEST['disable_bbcode']), FALSE, TRUE), 
			iif((isset($_REQUEST['disable_emoticons']) && $_REQUEST['disable_emoticons']), FALSE, TRUE), 
			iif((isset($_REQUEST['disable_aurls']) && $_REQUEST['disable_aurls']), FALSE, TRUE));
		

		/* Parse the bbcode */
		$body_text	= $bbcode->parse();
				
		// permissions are taken into account inside the poller
		$poller		= &new K4BBPolls($body_text, '', $forum, 0);
				
		/**
		 * Figure out what type of topic type this is
		 */
		$topic_type			= isset($_REQUEST['topic_type']) && intval($_REQUEST['topic_type']) != 0 ? $_REQUEST['topic_type'] : TOPIC_NORMAL;

		if($topic_type == TOPIC_STICKY && $request['user']->get('perms') < get_map($request['user'], 'sticky', 'can_add', array('forum_id'=>$forum['forum_id']))) {
			$topic_type		= TOPIC_NORMAL;
		} else if($topic_type == TOPIC_ANNOUNCE && $request['user']->get('perms') < get_map($request['user'], 'announce', 'can_add', array('forum_id'=>$forum['forum_id']))) {
			$topic_type		= TOPIC_NORMAL;
		}
		
		$is_feature			= isset($_REQUEST['is_feature']) && $_REQUEST['is_feature'] ? 1 : 0;
		
		if($is_feature == 1 && $request['user']->get('perms') < get_map($request['user'], 'feature', 'can_add', array('forum_id'=>$forum['forum_id']))) {
			$is_feature		= 0;
		}
		
		/* set the breadcrumbs bit */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_POSTTOPIC', $forum);

		if((isset($_REQUEST['submit_type']) && ($_REQUEST['submit_type'] == 'post' || $_REQUEST['submit_type'] == 'draft')) || ( isset($_REQUEST['post']) || isset($_REQUEST['draft']) ) ) {

			/* Does this person have permission to post a draft? */
			$is_draft = 0;
			if($_REQUEST['submit_type'] == 'draft' || isset($_REQUEST['draft'])) {
				if($request['user']->get('perms') < get_map($request['user'], 'post_save', 'can_add', array('forum_id'=>$forum['forum_id']))) {
					$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);
					
					return $action->execute($request);
				}

				$is_draft = 1;
			
			}

			/**
			 * Build the queries
			 */
			
			$poster_name		= iif($request['user']->get('id') <= 0,  htmlentities((isset($_REQUEST['poster_name']) ? $_REQUEST['poster_name'] : '') , ENT_QUOTES), $request['user']->get('name'));

			$request['dba']->beginTransaction();
			
			$insert_a			= &$request['dba']->prepareStatement("INSERT INTO ". K4TOPICS ." (name,forum_id,category_id,poster_name,poster_id,poster_ip,body_text,posticon,disable_html,disable_bbcode,disable_emoticons,disable_sig,disable_areply,disable_aurls,is_draft,topic_type,topic_expire,is_feature,is_poll,last_post,row_type,row_level,created) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
			
			$is_poll	= 0;
			if($_REQUEST['submit_type'] == 'post' || isset($_REQUEST['post'])) {
				
				// put it here to avoid previewing
				$poll_text		= $poller->parse($request, $is_poll);
								
				if($body_text != $poll_text) {
					$body_text	= $poll_text;
					$is_poll	= 1;
				}
			}

			/* Make sure we're not double-posting */
			if(!empty($last_topic) && (($_REQUEST['name'] == $last_topic['name']) && ($body_text == $last_topic['body_text']))) {
				$action = new K4InformationAction(new K4LanguageElement('L_DOUBLEPOSTED'), 'content', TRUE, 'viewtopic.php?id='. $last_topic['topic_id'], 3);
				return !USE_AJAX ? $action->execute($request) : ajax_message('L_DOUBLEPOSTED');
			}
			
			//topic_id,forum_id,category_id,poster_name,poster_id,body_text,posticon
			//disable_html,disable_bbcode,disable_emoticons,disable_sig,disable_areply,disable_aurls,is_draft,is_poll
			$insert_a->setString(1, htmlentities(html_entity_decode($_REQUEST['name']), ENT_QUOTES));
			$insert_a->setInt(2, $forum['forum_id']);
			$insert_a->setInt(3, $forum['category_id']);
			$insert_a->setString(4, $poster_name);
			$insert_a->setInt(5, $request['user']->get('id'));
			$insert_a->setString(6, USER_IP);
			$insert_a->setString(7, $body_text);
			$insert_a->setString(8, iif(($request['user']->get('perms') >= get_map($request['user'], 'posticons', 'can_add', array('forum_id'=>$forum['forum_id']))), (isset($_REQUEST['posticon']) ? $_REQUEST['posticon'] : 'clear.gif'), 'clear.gif'));
			$insert_a->setInt(9, iif((isset($_REQUEST['disable_html']) && $_REQUEST['disable_html']), 1, 0));
			$insert_a->setInt(10, iif((isset($_REQUEST['disable_bbcode']) && $_REQUEST['disable_bbcode']), 1, 0));
			$insert_a->setInt(11, iif((isset($_REQUEST['disable_emoticons']) && $_REQUEST['disable_emoticons']), 1, 0));
			$insert_a->setInt(12, iif((isset($_REQUEST['enable_sig']) && $_REQUEST['enable_sig']), 0, 1));
			$insert_a->setInt(13, iif((isset($_REQUEST['disable_areply']) && $_REQUEST['disable_areply']), 1, 0));
			$insert_a->setInt(14, iif((isset($_REQUEST['disable_aurls']) && $_REQUEST['disable_aurls']), 1, 0));
			$insert_a->setInt(15, $is_draft);
			// DO THIS 16 -> topic_type, 17 -> topic_expire
			$insert_a->setInt(16, $topic_type);
			$insert_a->setInt(17, iif($topic_type > TOPIC_NORMAL, intval((isset($_REQUEST['topic_expire']) ? $_REQUEST['topic_expire'] : 0)), 0) );
			$insert_a->setInt(18, $is_feature);
			$insert_a->setInt(19, $is_poll);
			$insert_a->setInt(20, $created);
			$insert_a->setInt(21, TOPIC);
			$insert_a->setInt(22, $level);
			$insert_a->setInt(23, $created);

			$insert_a->executeUpdate();
			
			$topic_id			= $request['dba']->getInsertId(K4TOPICS, 'topic_id');

			/** 
			 * Update the forum, and update the datastore 
			 */

			//topic_created,topic_name,topic_uname,topic_id,topic_uid,post_created,post_name,post_uname,post_id,post_uid
			$where				= "WHERE forum_id=?";
			$forum_update		= &$request['dba']->prepareStatement("UPDATE ". K4FORUMS ." SET topics=topics+1,posts=posts+1,topic_created=?,topic_name=?,topic_uname=?,topic_id=?,topic_uid=?,topic_posticon=?,post_created=?,post_name=?,post_uname=?,post_id=?,post_uid=?,post_posticon=? $where");
			$datastore_update	= &$request['dba']->prepareStatement("UPDATE ". K4DATASTORE ." SET data=? WHERE varname=?");
			
			/* If this isn't a draft, update the forums and datastore tables */
			if($is_draft == 0) {
				
				/* Set the forum values */
				$forum_update->setInt(1, $created);
				$forum_update->setString(2, htmlentities(html_entity_decode($_REQUEST['name']), ENT_QUOTES));
				$forum_update->setString(3, $poster_name);
				$forum_update->setInt(4, $topic_id);
				$forum_update->setInt(5, $request['user']->get('id'));
				$forum_update->setString(6, iif(($request['user']->get('perms') >= get_map($request['user'], 'posticons', 'can_add', array('forum_id'=>$forum['forum_id']))), (isset($_REQUEST['posticon']) ? $_REQUEST['posticon'] : 'clear.gif'), 'clear.gif'));
				$forum_update->setInt(7, $created);
				$forum_update->setString(8, htmlentities(html_entity_decode($_REQUEST['name']), ENT_QUOTES));
				$forum_update->setString(9, $poster_name);
				$forum_update->setInt(10, $topic_id);
				$forum_update->setInt(11, $request['user']->get('id'));
				$forum_update->setString(12, iif(($request['user']->get('perms') >= get_map($request['user'], 'posticons', 'can_add', array('forum_id'=>$forum['forum_id']))), (isset($_REQUEST['posticon']) ? $_REQUEST['posticon'] : 'clear.gif'), 'clear.gif'));
				$forum_update->setInt(13, $forum['forum_id']);
				
				/**
				 * Update the forums table and datastore table
				 */
				$forum_update->executeUpdate();
			}
			
			// deal with attachments
			attach_files($request, $forum, $topic_id);
			
			/* Added the topic */
			if($is_draft == 0) {
				
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
				if(isset($_REQUEST['disable_areply']) && $_REQUEST['disable_areply']) {
					$subscribe			= &$request['dba']->prepareStatement("INSERT INTO ". K4SUBSCRIPTIONS ." (user_id,user_name,topic_id,forum_id,email,category_id) VALUES (?,?,?,?,?,?)");
					$subscribe->setInt(1, $request['user']->get('id'));
					$subscribe->setString(2, $request['user']->get('name'));
					$subscribe->setInt(3, $topic_id);
					$subscribe->setInt(4, $forum['forum_id']);
					$subscribe->setString(5, $request['user']->get('email'));
					$subscribe->setInt(6, $forum['category_id']);
					$subscribe->executeUpdate();
				}
				
				set_send_topic_mail($forum['forum_id'], iif($poster_name == '', $request['template']->getVar('L_GUEST'), $poster_name));
				
				/* Commit the current transaction */
				$request['dba']->commitTransaction();
				
				/* Redirect the user */
				$action = new K4InformationAction(new K4LanguageElement('L_ADDEDTOPIC', htmlentities(html_entity_decode($_REQUEST['name']), ENT_QUOTES), $forum['name']), 'content', FALSE, 'viewtopic.php?id='. $topic_id, 3);

				return $action->execute($request);
			} else {
				
				/* Commit the current transaction */
				$request['dba']->commitTransaction();

				/* Redirect the user */
				$action = new K4InformationAction(new K4LanguageElement('L_SAVEDDRAFTTOPIC', htmlentities(html_entity_decode($_REQUEST['name']), ENT_QUOTES), $forum['name']), 'content', FALSE, 'viewforum.php?f='. $forum['forum_id'], 3);

				return $action->execute($request);
			}
		} else {
			
			/**
			 * Post Previewing
			 */
			
			if(!USE_AJAX) {
				$request['template']->setVar('L_TITLETOOSHORT', sprintf($request['template']->getVar('L_TITLETOOSHORT'), $request['template']->getVar('topicminchars'), $request['template']->getVar('topicmaxchars')));

				/* Get and set the emoticons and post icons to the template */
				$emoticons	= &$request['dba']->executeQuery("SELECT * FROM ". K4EMOTICONS ." WHERE clickable = 1");
				$posticons	= &$request['dba']->executeQuery("SELECT * FROM ". K4POSTICONS);
				
				/* Add the emoticons and the post icons to the template */
				$request['template']->setList('emoticons', $emoticons);
				$request['template']->setList('posticons', $posticons);
				
				/* Set some emoticon information */
				$request['template']->setVar('emoticons_per_row', $request['template']->getVar('smcolumns'));
				$request['template']->setVar('emoticons_per_row_remainder', $request['template']->getVar('smcolumns')-1);
				
				topic_post_options($request['template'], $request['user'], $forum);

				/* Set the forum info to the template */
				foreach($forum as $key => $val)
					$request['template']->setVar('forum_'. $key, $val);
				
				/* Create our editor */
				create_editor($request, $_REQUEST['message'], 'post', $forum);

				$request['template']->setVar('newtopic_action', 'newtopic.php?act=posttopic');
			}
			/* Set topic array items to be passed to the iterator */			
			$topic_preview	= array(
								'name' => htmlentities(html_entity_decode($_REQUEST['name']), ENT_QUOTES),
								'body_text' => $body_text,
								'poster_name' => $request['user']->get('name'),
								'poster_id' => $request['user']->get('id'),
								'is_poll' => 0,
								'row_left' => 0,
								'row_right' => 0,
								'topic_type' => $topic_type,
								'is_feature' => $is_feature,
								'posticon' => iif(($request['user']->get('perms') >= get_map($request['user'], 'posticons', 'can_add', array('forum_id'=>$forum['forum_id']))), (isset($_REQUEST['posticon']) ? $_REQUEST['posticon'] : 'clear.gif'), 'clear.gif'),
								'disable_html' => iif((isset($_REQUEST['disable_html']) && $_REQUEST['disable_html']), 1, 0),
								'disable_sig' => iif((isset($_REQUEST['enable_sig']) && $_REQUEST['enable_sig']), 0, 1),
								'disable_bbcode' => iif((isset($_REQUEST['disable_bbcode']) && $_REQUEST['disable_bbcode']), 1, 0),
								'disable_emoticons' => iif((isset($_REQUEST['disable_emoticons']) && $_REQUEST['disable_emoticons']), 1, 0),
								'disable_areply' => iif((isset($_REQUEST['disable_areply']) && $_REQUEST['disable_areply']), 1, 0),
								'disable_aurls' => iif((isset($_REQUEST['disable_aurls']) && $_REQUEST['disable_aurls']), 1, 0)
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
			
			if(!USE_AJAX) {

				/* Set the the button display options */
				$request['template']->setVisibility('save_draft', TRUE);
				$request['template']->setVisibility('edit_topic', TRUE);
				$request['template']->setVisibility('post_topic', TRUE);
				$request['template']->setVisibility('topic_id', TRUE);
				
				/* Should she show/hide the 'load draft' button? */
				$drafts		= $request['dba']->executeQuery("SELECT * FROM ". K4TOPICS ." WHERE forum_id = ". intval($forum['forum_id']) ." AND is_draft = 1 AND poster_id = ". intval($request['user']->get('id')));
				if($drafts->numrows() > 0)
					$request['template']->setVisibility('load_button', TRUE);
				else
					$request['template']->setVisibility('load_button', FALSE);
				
				/* Set the post topic form */
				$request['template']->setVar('forum_forum_id', $forum['forum_id']);
				$request['template']->setFile('preview', 'post_preview.html');
				$request['template']->setFile('content', 'newtopic.html');
			} else {
				$templateset = $request['user']->isMember() ? $request['user']->get('templateset') : $forum['defaultstyle'];
				$html = $request['template']->run(BB_BASE_DIR .'/templates/'. $templateset .'/post_preview.html');
				echo $html;
				exit;
			}
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
		
		/* set the breadcrumbs bit */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');

		/* Check the request ID */
		if(!isset($_REQUEST['forum_id']) || !$_REQUEST['forum_id'] || intval($_REQUEST['forum_id']) == 0) {
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);
			return !USE_AJAX ? $action->execute($request) : ajax_message('L_FORUMDOESNTEXIST');
		}
			
		/* Check the request ID */
		if(!isset($_REQUEST['forum_id']) || !$_REQUEST['forum_id'] || intval($_REQUEST['forum_id']) == 0) {
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);
			return !USE_AJAX ? $action->execute($request) : ajax_message('L_FORUMDOESNTEXIST');
		}
			
		$forum				= $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($_REQUEST['forum_id']));
		
		/* Check the forum data given */
		if(!$forum || !is_array($forum) || empty($forum)) {
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);
			return !USE_AJAX ? $action->execute($request) : ajax_message('L_FORUMDOESNTEXIST');
		}
			
		/* Make sure the we are trying to post into a forum */
		if(!($forum['row_type'] & FORUM)) {
			$action = new K4InformationAction(new K4LanguageElement('L_CANTPOSTTONONFORUM'), 'content', FALSE);
			return !USE_AJAX ? $action->execute($request) : ajax_message('L_CANTPOSTTONONFORUM');
		}

		/* Do we have permission to post to this forum? */
		if($request['user']->get('perms') < get_map($request['user'], 'topics', 'can_add', array('forum_id'=>$forum['forum_id']))) {
			$action = new K4InformationAction(new K4LanguageElement('L_PERMCANTPOST'), 'content', FALSE);
			return !USE_AJAX ? $action->execute($request) : ajax_message('L_PERMCANTPOST');
		}

		/* General error checking */
		if(!isset($_REQUEST['name']) || $_REQUEST['name'] == '') {
			$action = new K4InformationAction(new K4LanguageElement('L_INSERTTOPICNAME'), 'content', TRUE);
			return !USE_AJAX ? $action->execute($request) : ajax_message('L_INSERTTOPICNAME');
		}

		if (!$this->runPostFilter('name', new FALengthFilter(intval($_SETTINGS['topicmaxchars'])))) {
			$action = new K4InformationAction(new K4LanguageElement('L_TITLETOOSHORT', intval($_SETTINGS['topicminchars']), intval($_SETTINGS['topicmaxchars'])), 'content', TRUE);
			return !USE_AJAX ? $action->execute($request) : ajax_message(new K4LanguageElement('L_TITLETOOSHORT', intval($_SETTINGS['topicminchars']), intval($_SETTINGS['topicmaxchars'])));
		}
		if (!$this->runPostFilter('name', new FALengthFilter(intval($_SETTINGS['topicmaxchars']), intval($_SETTINGS['topicminchars'])))) {
			$action = new K4InformationAction(new K4LanguageElement('L_TITLETOOSHORT', intval($_SETTINGS['topicminchars']), intval($_SETTINGS['topicmaxchars'])), 'content', TRUE);
			return !USE_AJAX ? $action->execute($request) : ajax_message(new K4LanguageElement('L_TITLETOOSHORT', intval($_SETTINGS['topicminchars']), intval($_SETTINGS['topicmaxchars'])));
		}

		if(!isset($_REQUEST['message']) || $_REQUEST['message'] == '') {
			$action = new K4InformationAction(new K4LanguageElement('L_INSERTTOPICMESSAGE'), 'content', TRUE);
			return !USE_AJAX ? $action->execute($request) : ajax_message('L_INSERTTOPICMESSAGE');
		}
		
		/* Get our topic */
		$draft				= $request['dba']->getRow("SELECT * FROM ". K4TOPICS ." WHERE topic_id = ". intval($_REQUEST['topic_id']) ." AND is_draft = 1 AND poster_id = ". intval($request['user']->get('id')));
		
		if(!$draft || !is_array($draft) || empty($draft)) {
			$action = new K4InformationAction(new K4LanguageElement('L_DRAFTDOESNTEXIST'), 'content', FALSE);
			return !USE_AJAX ? $action->execute($request) : ajax_message('L_DRAFTDOESNTEXIST');
		}

		/* set the breadcrumbs bit */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_POSTTOPIC', $forum);
		
		$created			= time();
		
		/* Initialize the bbcode parser with the topic message */
		$_REQUEST['message']	= substr($_REQUEST['message'], 0, $_SETTINGS['postmaxchars']);
		$bbcode	= &new BBCodex($request['dba'], $request['user']->getInfoArray(), $_REQUEST['message'], $forum['forum_id'], 
			iif((isset($_REQUEST['disable_html']) && $_REQUEST['disable_html']), FALSE, TRUE), 
			iif((isset($_REQUEST['disable_bbcode']) && $_REQUEST['disable_bbcode']), FALSE, TRUE), 
			iif((isset($_REQUEST['disable_emoticons']) && $_REQUEST['disable_emoticons']), FALSE, TRUE), 
			iif((isset($_REQUEST['disable_aurls']) && $_REQUEST['disable_aurls']), FALSE, TRUE));
		
		/* Parse the bbcode */
		$body_text	= $bbcode->parse();
		
		// permissions are taken into account inside the poller
		$poller		= &new K4BBPolls($body_text, $draft['body_text'], $forum, $draft['topic_id']);
		
		/**
		 * Figure out what type of topic type this is
		 */
		$topic_type			= isset($_REQUEST['topic_type']) && intval($_REQUEST['topic_type']) != 0 ? $_REQUEST['topic_type'] : TOPIC_NORMAL;

		if($topic_type == TOPIC_STICKY && $request['user']->get('perms') < get_map($request['user'], 'sticky', 'can_add', array('forum_id'=>$forum['forum_id']))) {
			$topic_type		= TOPIC_NORMAL;
		} else if($topic_type == TOPIC_ANNOUNCE && $request['user']->get('perms') < get_map($request['user'], 'announce', 'can_add', array('forum_id'=>$forum['forum_id']))) {
			$topic_type		= TOPIC_NORMAL;
		}

		$is_feature			= isset($_REQUEST['is_feature']) && $_REQUEST['is_feature'] == 'yes' ? 1 : 0;
		
		if($is_feature == 1 && $request['user']->get('perms') < get_map($request['user'], 'feature', 'can_add', array('forum_id'=>$forum['forum_id']))) {
			$is_feature		= 0;
		}
		
		/* If we are submitting or saving a draft */
		if((isset($_REQUEST['submit_type']) && ($_REQUEST['submit_type'] == 'post' || $_REQUEST['submit_type'] == 'draft')) || ( isset($_REQUEST['post']) || isset($_REQUEST['draft']) ) ) {
			
			// put it here to avoid previewing
			$is_poll	= 0;
			$poll_text		= $poller->parse($request, $is_poll);

			if($body_text != $poll_text) {
				$is_poll	= 1;
				$body_text	= $poll_text;
			}

			/**
			 * Build the queries to add the draft
			 */
			
			$poster_name		= iif($request['user']->get('id') <= 0,  htmlentities((isset($_REQUEST['poster_name']) ? $_REQUEST['poster_name'] : '') , ENT_QUOTES), $request['user']->get('name'));

			$update_a			= $request['dba']->prepareStatement("UPDATE ". K4TOPICS ." SET name=?,body_text=?,posticon=?,disable_html=?,disable_bbcode=?,disable_emoticons=?,disable_sig=?,disable_areply=?,disable_aurls=?,is_draft=?,topic_type=?,is_feature=?,is_poll=?,created=? WHERE topic_id=?");
			
			/* Set the informtion */
			$update_a->setInt(1, $created);
			$update_a->setInt(2, $draft['topic_id']);
			
			/* Set the topic information */
			$update_a->setString(1, htmlentities(html_entity_decode($_REQUEST['name']), ENT_QUOTES));
			$update_a->setString(2, $body_text);
			$update_a->setString(3, iif(($request['user']->get('perms') >= get_map($request['user'], 'posticons', 'can_add', array('forum_id'=>$forum['forum_id']))), (isset($_REQUEST['posticon']) ? $_REQUEST['posticon'] : 'clear.gif'), 'clear.gif'));
			$update_a->setInt(4, iif((isset($_REQUEST['disable_html']) && $_REQUEST['disable_html']), 1, 0));
			$update_a->setInt(5, iif((isset($_REQUEST['disable_bbcode']) && $_REQUEST['disable_bbcode']), 1, 0));
			$update_a->setInt(6, iif((isset($_REQUEST['disable_emoticons']) && $_REQUEST['disable_emoticons']), 1, 0));
			$update_a->setInt(7, iif((isset($_REQUEST['enable_sig']) && $_REQUEST['enable_sig']), 0, 1));
			$update_a->setInt(8, iif((isset($_REQUEST['disable_areply']) && $_REQUEST['disable_areply']), 1, 0));
			$update_a->setInt(9, iif((isset($_REQUEST['disable_aurls']) && $_REQUEST['disable_aurls']), 1, 0));
			$update_a->setInt(10, 0);
			$update_a->setInt(11, $topic_type);
			$update_a->setInt(12, $is_feature);
			$update_a->setInt(13, $is_poll);
			$update_a->setInt(14, $created);
			$update_a->setInt(15, $draft['topic_id']);
			
			/**
			 * Do the queries
			 */
			$update_a->executeUpdate();

			$forum_update		= &$request['dba']->prepareStatement("UPDATE ". K4FORUMS ." SET topics=topics+1,posts=posts+1,topic_created=?,topic_name=?,topic_uname=?,topic_id=?,topic_uid=?,topic_posticon=?,post_created=?,post_name=?,post_uname=?,post_id=?,post_uid=?,post_posticon=? WHERE forum_id=?");
			$datastore_update	= &$request['dba']->prepareStatement("UPDATE ". K4DATASTORE ." SET data=? WHERE varname=?");
			
			if((isset($_REQUEST['submit_type']) && $_REQUEST['submit_type'] == 'post') || isset($_REQUEST['post']))
				$request['dba']->executeUpdate("UPDATE ". K4USERINFO ." SET num_posts=num_posts+1,total_posts=total_posts+1 WHERE user_id=". intval($request['user']->get('id')));	
				
			/* Set the forum values */
			$forum_update->setInt(1, $created);
			$forum_update->setString(2, htmlentities(html_entity_decode($_REQUEST['name']), ENT_QUOTES));
			$forum_update->setString(3, $poster_name);
			$forum_update->setInt(4, $draft['topic_id']);
			$forum_update->setInt(5, $request['user']->get('id'));
			$forum_update->setString(6, iif(($request['user']->get('perms') >= get_map($request['user'], 'posticons', 'can_add', array('forum_id'=>$forum['forum_id']))), (isset($_REQUEST['posticon']) ? $_REQUEST['posticon'] : 'clear.gif'), 'clear.gif'));
			$forum_update->setInt(7, $created);
			$forum_update->setString(8, htmlentities(html_entity_decode($_REQUEST['name']), ENT_QUOTES));
			$forum_update->setString(9, $poster_name);
			$forum_update->setInt(10, $draft['topic_id']);
			$forum_update->setInt(11, $request['user']->get('id'));
			$forum_update->setString(12, iif(($request['user']->get('perms') >= get_map($request['user'], 'posticons', 'can_add', array('forum_id'=>$forum['forum_id']))), (isset($_REQUEST['posticon']) ? $_REQUEST['posticon'] : 'clear.gif'), 'clear.gif'));
			$forum_update->setInt(13, $forum['forum_id']);
			
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
			if(isset($_REQUEST['disable_areply']) && $_REQUEST['disable_areply']) {
				$subscribe			= &$request['dba']->prepareStatement("INSERT INTO ". K4SUBSCRIPTIONS ." (user_id,user_name,topic_id,forum_id,email,category_id) VALUES (?,?,?,?,?,?)");
				$subscribe->setInt(1, $request['user']->get('id'));
				$subscribe->setString(2, $request['user']->get('name'));
				$subscribe->setInt(3, $draft['id']);
				$subscribe->setInt(4, $forum['forum_id']);
				$subscribe->setString(5, $request['user']->get('email'));
				$subscribe->setInt(6, $forum['category_id']);
				$subscribe->executeUpdate();
			}

			// deal with attachments
			attach_files($request, $forum, $draft['topic_id']);
			
			// set up the topic queue
			set_send_topic_mail($forum['forum_id'], iif($poster_name == '', $request['template']->getVar('L_GUEST'), $poster_name));

			/* Redirect the user */
			$action = new K4InformationAction(new K4LanguageElement('L_ADDEDTOPIC', htmlentities(html_entity_decode($_REQUEST['name']), ENT_QUOTES), $forum['name']), 'content', FALSE, 'viewtopic.php?id='. $draft['topic_id'], 3);

			return $action->execute($request);
		
		/* If we are previewing */
		} else {
			
			/**
			 * Post Previewing
			 */
			
			if(!USE_AJAX) {

				$request['template']->setVar('L_TITLETOOSHORT', sprintf($request['template']->getVar('L_TITLETOOSHORT'), $request['template']->getVar('topicminchars'), $request['template']->getVar('topicmaxchars')));

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
				
				$request['template']->setVisibility('post_topic', TRUE);

				post_attachment_options($request, $forum, $draft);
				topic_post_options($request['template'], $request['user'], $forum);
			
				/* Create our editor */
				create_editor($request, $_REQUEST['message'], 'post', $forum);
			}

			/* Set topic iterator array elements to be passed to the template */
			$topic_preview	= array(
								'topic_id' => @$draft['id'],
								'name' => htmlentities(html_entity_decode($_REQUEST['name']), ENT_QUOTES),
								'posticon' => (isset($_REQUEST['posticon']) ? $_REQUEST['posticon'] : 'clear.gif'),
								'body_text' => $body_text,
								'poster_name' => html_entity_decode($draft['poster_name'], ENT_QUOTES),
								'poster_id' => $request['user']->get('id'),
								'is_poll' => $draft['is_poll'],
								'row_left' => 0,
								'row_right' => 0,
								'topic_type' => $topic_type,
								'is_feature' => $is_feature,
								'posticon' => iif(($request['user']->get('perms') >= get_map($request['user'], 'posticons', 'can_add', array('forum_id'=>$forum['forum_id']))), (isset($_REQUEST['posticon']) ? $_REQUEST['posticon'] : 'clear.gif'), 'clear.gif'),
								'disable_html' => iif((isset($_REQUEST['disable_html']) && $_REQUEST['disable_html']), 1, 0),
								'disable_sig' => iif((isset($_REQUEST['enable_sig']) && $_REQUEST['enable_sig']), 0, 1),
								'disable_bbcode' => iif((isset($_REQUEST['disable_bbcode']) && $_REQUEST['disable_bbcode']), 1, 0),
								'disable_emoticons' => iif((isset($_REQUEST['disable_emoticons']) && $_REQUEST['disable_emoticons']), 1, 0),
								'disable_areply' => iif((isset($_REQUEST['disable_areply']) && $_REQUEST['disable_areply']), 1, 0),
								'disable_aurls' => iif((isset($_REQUEST['disable_aurls']) && $_REQUEST['disable_aurls']), 1, 0)
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
			
			if(!USE_AJAX) {

				/* Set the the button display options */
				$request['template']->setVisibility('save_draft', FALSE);
				$request['template']->setVisibility('load_button', FALSE);
				$request['template']->setVisibility('edit_topic', TRUE);
				$request['template']->setVisibility('topic_id', TRUE);
				
				/* set the breadcrumbs bit */
				k4_bread_crumbs($request['template'], $request['dba'], 'L_POSTTOPIC', $forum);
				
				/* Set the post topic form */
				$request['template']->setVar('forum_forum_id', $forum['forum_id']);
				$request['template']->setFile('preview', 'post_preview.html');
				$request['template']->setFile('content', 'newtopic.html');
			} else {
				$templateset = $request['user']->isMember() ? $request['user']->get('templateset') : $forum['defaultstyle'];
				$html = $request['template']->run(BB_BASE_DIR .'/templates/'. $templateset .'/post_preview.html');
				echo $html;
				exit;
			}
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
		
		/* set the breadcrumbs bit */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');

		/* Get our draft */
		$draft				= $request['dba']->getRow("SELECT * FROM ". K4TOPICS ." WHERE topic_id = ". intval($_REQUEST['id']) ." AND is_draft = 1 AND poster_id = ". intval($request['user']->get('id')));
		
		if(!$draft || !is_array($draft) || empty($draft)) {
			$action = new K4InformationAction(new K4LanguageElement('L_DRAFTDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}
		
		$forum				= $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($draft['forum_id']));
		
		/* Check the forum data given */
		if(!$forum || !is_array($forum) || empty($forum)) {
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}
			
		/* Make sure the we are trying to post into a forum */
		if(!($forum['row_type'] & FORUM)) {
			$action = new K4InformationAction(new K4LanguageElement('L_CANTPOSTTONONFORUM'), 'content', FALSE);
			return $action->execute($request);
		}			

		/* set the breadcrumbs bit */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_DELETEDRAFT', $forum);
		
		/* Remove this draft from the information table */
		$h			= &new Heirarchy();
		
		/* Now remove the information stored in the topics table */
		$request['dba']->executeUpdate("DELETE FROM ". K4TOPICS ." WHERE topic_id = ". intval($draft['topic_id']) ." AND is_draft = 1");
		$request['dba']->executeUpdate("DELETE FROM ". K4ATTACHMENTS ." WHERE topic_id = ". intval($draft['topic_id']));

		/* Redirect the user */
		$action = new K4InformationAction(new K4LanguageElement('L_REMOVEDDRAFT', $draft['name'], $forum['name']), 'content', FALSE, 'viewforum.php?f='. $forum['forum_id'], 3);

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
		
		/* set the breadcrumbs bit */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');

		/* Get our topic */
		$topic				= $request['dba']->getRow("SELECT * FROM ". K4TOPICS ." WHERE topic_id = ". intval($_REQUEST['id']));
		
		if(!$topic || !is_array($topic) || empty($topic)) {
			$action = new K4InformationAction(new K4LanguageElement('L_DRAFTDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}
		
		$forum				= $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($topic['forum_id']));
		
		/* Check the forum data given */
		if(!$forum || !is_array($forum) || empty($forum)) {
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}
			
		/* Make sure the we are trying to post into a forum */
		if(!($forum['row_type'] & FORUM)) {
			$action = new K4InformationAction(new K4LanguageElement('L_CANTPOSTTONONFORUM'), 'content', FALSE);
			return $action->execute($request);
		}
		
		/* set the breadcrumbs bit */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_EDITTOPIC', $topic, $forum);
		
		if($topic['poster_id'] == $request['user']->get('id')) {
			if(get_map($request['user'], 'topics', 'can_edit', array('forum_id'=>$forum['forum_id'])) > $request['user']->get('perms')) {
				$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);
				return $action->execute($request);
			}
		} else {
			if(get_map($request['user'], 'other_topics', 'can_edit', array('forum_id'=>$forum['forum_id'])) > $request['user']->get('perms')) {
				$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);
				return $action->execute($request);
			}
		}

		/* Does this user have permission to edit this topic if it is locked? */
		if($topic['topic_locked'] == 1 && get_map($request['user'], 'closed', 'can_edit', array('forum_id' => $forum['forum_id'])) > $request['user']->get('perms')) {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);
			return $action->execute($request);
		}

		post_attachment_options($request, $forum, $topic);
		topic_post_options($request['template'], $request['user'], $forum);
		
		/* Get and set the emoticons and post icons to the template */
		$emoticons			= &$request['dba']->executeQuery("SELECT * FROM ". K4EMOTICONS ." WHERE clickable = 1");
		$posticons			= &$request['dba']->executeQuery("SELECT * FROM ". K4POSTICONS);

		$request['template']->setList('emoticons', $emoticons);
		$request['template']->setList('posticons', $posticons);

		$request['template']->setVar('emoticons_per_row', $request['template']->getVar('smcolumns'));
		$request['template']->setVar('emoticons_per_row_remainder', $request['template']->getVar('smcolumns')-1);
		
		$request['template']->setVar('newtopic_action', 'newtopic.php?act=updatetopic');
		
		/* Create our editor */
		create_editor($request, $topic['body_text'], 'post', $forum);

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

		/* Set the post topic form */
		$request['template']->setVar('forum_forum_id', $forum['forum_id']);
		//$request['template']->setFile('preview', 'post_preview.html');
		$request['template']->setFile('content', 'newtopic.html');
		$request['template']->setVar('L_TITLETOOSHORT', sprintf($request['template']->getVar('L_TITLETOOSHORT'), $request['template']->getVar('topicminchars'), $request['template']->getVar('topicmaxchars')));

		return TRUE;
	}
}

/**
 * Update a topic
 */
class UpdateTopic extends FAAction {
	function execute(&$request) {
		
		global $_QUERYPARAMS, $_DATASTORE, $_SETTINGS;
		
		/* set the breadcrumbs bit */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');

		/* Check the request ID */
		if(!isset($_REQUEST['forum_id']) || !$_REQUEST['forum_id'] || intval($_REQUEST['forum_id']) == 0) {
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);
			return !USE_AJAX ? $action->execute($request) : ajax_message('L_FORUMDOESNTEXIST');
		}
			
		$forum				= $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($_REQUEST['forum_id']));
		
		/* Check the forum data given */
		if(!$forum || !is_array($forum) || empty($forum)) {
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);
			return !USE_AJAX ? $action->execute($request) : ajax_message('L_FORUMDOESNTEXIST');
		}
			
		/* Make sure the we are trying to edit in a forum */
		if(!($forum['row_type'] & FORUM)) {
			$action = new K4InformationAction(new K4LanguageElement('L_CANTEDITTONONFORUM'), 'content', FALSE);
			return !USE_AJAX ? $action->execute($request) : ajax_message('L_CANTEDITTONONFORUM');
		}

		/* General error checking */
		if(!isset($_REQUEST['name']) || $_REQUEST['name'] == '') {
			$action = new K4InformationAction(new K4LanguageElement('L_INSERTTOPICNAME'), 'content', TRUE);
			return !USE_AJAX ? $action->execute($request) : ajax_message('L_INSERTTOPICNAME');
		}

		if (!$this->runPostFilter('name', new FALengthFilter(intval($_SETTINGS['topicmaxchars'])))) {
			$action = new K4InformationAction(new K4LanguageElement('L_TITLETOOSHORT', intval($_SETTINGS['topicminchars']), intval($_SETTINGS['topicmaxchars'])), 'content', TRUE);
			return !USE_AJAX ? $action->execute($request) : ajax_message(new K4LanguageElement('L_TITLETOOSHORT', intval($_SETTINGS['topicminchars']), intval($_SETTINGS['topicmaxchars'])));
		}
		if (!$this->runPostFilter('name', new FALengthFilter(intval($_SETTINGS['topicmaxchars']), intval($_SETTINGS['topicminchars'])))) {
			$action = new K4InformationAction(new K4LanguageElement('L_TITLETOOSHORT', intval($_SETTINGS['topicminchars']), intval($_SETTINGS['topicmaxchars'])), 'content', TRUE);
			return !USE_AJAX ? $action->execute($request) : ajax_message(new K4LanguageElement('L_TITLETOOSHORT', intval($_SETTINGS['topicminchars']), intval($_SETTINGS['topicmaxchars'])));
		}

		if(!isset($_REQUEST['message']) || $_REQUEST['message'] == '') {
			$action = new K4InformationAction(new K4LanguageElement('L_INSERTTOPICMESSAGE'), 'content', TRUE);
			return !USE_AJAX ? $action->execute($request) : ajax_message('L_INSERTTOPICMESSAGE');
		}
		
		/* Get our topic */
		$topic				= $request['dba']->getRow("SELECT * FROM ". K4TOPICS ." WHERE topic_id = ". intval($_REQUEST['topic_id']));
		
		if(!is_array($topic) || empty($topic)) {
			$action = new K4InformationAction(new K4LanguageElement('L_TOPICDOESNTEXIST'), 'content', FALSE);
			return !USE_AJAX ? $action->execute($request) : ajax_message('L_TOPICDOESNTEXIST');
		}

		$type				= $topic['is_poll'] == 1 ? 'polls' : 'topics';

		/* Does this person have permission to edit this topic? */
		if($topic['poster_id'] == $request['user']->get('id')) {
			if(get_map($request['user'], $type, 'can_edit', array('forum_id'=>$forum['forum_id'])) > $request['user']->get('perms')) {
				$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);
				return !USE_AJAX ? $action->execute($request) : ajax_message('L_YOUNEEDPERMS');
			}
		} else {
			if(get_map($request['user'], 'other_'. $type, 'can_edit', array('forum_id'=>$forum['forum_id'])) > $request['user']->get('perms')) {
				$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);
				return !USE_AJAX ? $action->execute($request) : ajax_message('L_YOUNEEDPERMS');
			}
		}
		
		/* Does this user have permission to edit this topic if it is locked? */
		if($topic['topic_locked'] == 1 && get_map($request['user'], 'closed', 'can_edit', array('forum_id' => $forum['forum_id'])) > $request['user']->get('perms')) {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);
			return !USE_AJAX ? $action->execute($request) : ajax_message('L_YOUNEEDPERMS');
		}

		/* set the breadcrumbs bit */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_EDITTOPIC', $topic, $forum);
				
		/* Initialize the bbcode parser with the topic message */
		$_REQUEST['message']	= substr($_REQUEST['message'], 0, $_SETTINGS['postmaxchars']);
		$bbcode	= &new BBCodex($request['dba'], $request['user']->getInfoArray(), $_REQUEST['message'], $forum['forum_id'], 
			iif((isset($_REQUEST['disable_html']) && $_REQUEST['disable_html']), FALSE, TRUE), 
			iif((isset($_REQUEST['disable_bbcode']) && $_REQUEST['disable_bbcode']), FALSE, TRUE), 
			iif((isset($_REQUEST['disable_emoticons']) && $_REQUEST['disable_emoticons']), FALSE, TRUE), 
			iif((isset($_REQUEST['disable_aurls']) && $_REQUEST['disable_aurls']), FALSE, TRUE));
		
		/* Parse the bbcode */
		$body_text	= $bbcode->parse();

		// permissions are taken into account inside the poller
		$poller		= &new K4BBPolls($body_text, $topic['body_text'], $forum, $topic['topic_id']);
				
		$request['template']->setVar('newtopic_action', 'newtopic.php?act=updatetopic');
		
		/* Get the topic type */
		$topic_type			= isset($_REQUEST['topic_type']) && intval($_REQUEST['topic_type']) != 0 ? $_REQUEST['topic_type'] : TOPIC_NORMAL;
		
		/* Check the topic type and check if this user has permission to post that type of topic */
		if($topic_type == TOPIC_STICKY && $request['user']->get('perms') < get_map($request['user'], 'sticky', 'can_add', array('forum_id'=>$forum['forum_id']))) {
			$topic_type		= TOPIC_NORMAL;
		} else if($topic_type == TOPIC_ANNOUNCE && $request['user']->get('perms') < get_map($request['user'], 'announce', 'can_add', array('forum_id'=>$forum['forum_id']))) {
			$topic_type		= TOPIC_NORMAL;
		}
		
		/* Is this a featured topic? */
		$is_feature			= isset($_REQUEST['is_feature']) && $_REQUEST['is_feature'] == 'yes' ? 1 : 0;
		if($is_feature == 1 && $request['user']->get('perms') < get_map($request['user'], 'feature', 'can_add', array('forum_id'=>$forum['forum_id']))) {
			$is_feature		= 0;
		}

		/* If we are saving this topic */
		if((isset($_REQUEST['submit_type']) && $_REQUEST['submit_type'] == 'post') || isset($_REQUEST['post'])) {
			
			// put it here to avoid previewing
			$is_poll		= 0;
			$poll_text		= $poller->parse($request, $is_poll);

			if($body_text != $poll_text) {
				$body_text	= $poll_text;
				$is_poll	= 1;
			}

			$posticon			= iif(($request['user']->get('perms') >= get_map($request['user'], 'posticons', 'can_add', array('forum_id'=>$forum['forum_id']))), (isset($_REQUEST['posticon']) ? $_REQUEST['posticon'] : 'clear.gif'), 'clear.gif');
			
			$time				= time();
			
			$name				= htmlentities(html_entity_decode($_REQUEST['name']), ENT_QUOTES);

			/**
			 * Build the queries to update the topic
			 */
			
			$update_a			= $request['dba']->prepareStatement("UPDATE ". K4TOPICS ." SET name=?,body_text=?,posticon=?,disable_html=?,disable_bbcode=?,disable_emoticons=?,disable_sig=?,disable_areply=?,disable_aurls=?,is_draft=?,edited_time=?,edited_username=?,edited_userid=?,is_feature=?,topic_type=?,topic_expire=?,is_poll=? WHERE topic_id=?");
			
			$update_a->setString(1, $name);
			$update_a->setString(2, $body_text);
			$update_a->setString(3, $posticon);
			$update_a->setInt(4, iif((isset($_REQUEST['disable_html']) && $_REQUEST['disable_html']), 1, 0));
			$update_a->setInt(5, iif((isset($_REQUEST['disable_bbcode']) && $_REQUEST['disable_bbcode']), 1, 0));
			$update_a->setInt(6, iif((isset($_REQUEST['disable_emoticons']) && $_REQUEST['disable_emoticons']), 1, 0));
			$update_a->setInt(7, iif((isset($_REQUEST['enable_sig']) && $_REQUEST['enable_sig']), 0, 1));
			$update_a->setInt(8, iif((isset($_REQUEST['disable_areply']) && $_REQUEST['disable_areply']), 1, 0));
			$update_a->setInt(9, iif((isset($_REQUEST['disable_aurls']) && $_REQUEST['disable_aurls']), 1, 0));
			$update_a->setInt(10, 0);
			$update_a->setInt(11, $time);
			$update_a->setString(12, iif($request['user']->get('id') <= 0,  htmlentities((isset($_REQUEST['poster_name']) ? $_REQUEST['poster_name'] : '') , ENT_QUOTES), $request['user']->get('name')));
			$update_a->setInt(13, $request['user']->get('id'));
			$update_a->setInt(14, $is_feature);
			$update_a->setInt(15, $topic_type);
			$update_a->setInt(16, iif($topic_type > TOPIC_NORMAL, intval((isset($_REQUEST['topic_expire']) ? $_REQUEST['topic_expire'] : 0)), 0) );
			$update_a->setInt(17, $is_poll);
			$update_a->setInt(18, $topic['topic_id']);
			
			/**
			 * Do the query
			 */
			$update_a->executeUpdate();
			
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

			/**
			 * Subscribe/Unsubscribe this user to the topic
			 */
			$is_subscribed		= $request['dba']->getRow("SELECT * FROM ". K4SUBSCRIPTIONS ." WHERE user_id = ". intval($request['user']->get('id')) ." AND topic_id = ". intval($topic['topic_id']));
			if(isset($_REQUEST['disable_areply']) && $_REQUEST['disable_areply']) {
				if(!is_array($is_subscribed) || empty($is_subscribed)) {
					$subscribe			= &$request['dba']->prepareStatement("INSERT INTO ". K4SUBSCRIPTIONS ." (user_id,user_name,topic_id,forum_id,email,category_id) VALUES (?,?,?,?,?,?)");
					$subscribe->setInt(1, $request['user']->get('id'));
					$subscribe->setString(2, $request['user']->get('name'));
					$subscribe->setInt(3, $topic['topic_id']);
					$subscribe->setInt(4, $forum['forum_id']);
					$subscribe->setString(5, $request['user']->get('email'));
					$subscribe->setInt(6, $forum['category_id']);
					$subscribe->executeUpdate();
				}
			} else if(!isset($_REQUEST['disable_areply']) || !$_REQUEST['disable_areply']) {
				if(is_array($is_subscribed) && !empty($is_subscribed)) {
					$subscribe			= &$request['dba']->prepareStatement("DELETE FROM ". K4SUBSCRIPTIONS ." WHERE user_id=? AND topic_id=?");
					$subscribe->setInt(1, $request['user']->get('id'));
					$subscribe->setInt(2, $topic['topic_id']);
					$subscribe->executeUpdate();
				}
			}

			// deal with attachments
			attach_files($request, $forum, $topic['topic_id']);

			/* Should we update the forum's last post info? */
			if($forum['topic_id'] == $topic['topic_id'] || ($forum['post_id'] == $topic['topic_id'] && $forum['post_created'] == $topic['created']) ) {
				
				// this deals with this forums last topic info
				if($forum['topic_id'] == $topic['topic_id']) {
					$forum_topic_update		= $request['dba']->prepareStatement("UPDATE ". K4FORUMS ." SET topic_name=?,topic_posticon=? WHERE forum_id=?");
					$forum_topic_update->setString(1, htmlentities(html_entity_decode($_REQUEST['name']), ENT_QUOTES));
					$forum_topic_update->setString(2, $posticon);
					$forum_topic_update->setInt(3, $forum['forum_id']);
					$forum_topic_update->executeUpdate();
				}
				
				// if this topic is the forums last post
				if($forum['post_id'] == $topic['topic_id'] && $forum['post_created'] == $topic['created']) {
					$forum_topic_update		= $request['dba']->prepareStatement("UPDATE ". K4FORUMS ." SET post_name=?,post_posticon=? WHERE forum_id=?");
					$forum_topic_update->setString(1, htmlentities(html_entity_decode($_REQUEST['name']), ENT_QUOTES));
					$forum_topic_update->setString(2, $posticon);
					$forum_topic_update->setInt(3, $forum['forum_id']);
					$forum_topic_update->executeUpdate();
				}
			}

			/* Redirect the user */
			$action = new K4InformationAction(new K4LanguageElement('L_UPDATEDTOPIC', htmlentities(html_entity_decode($_REQUEST['name']), ENT_QUOTES)), 'content', FALSE, 'viewtopic.php?id='. $topic['topic_id'], 3);

			return $action->execute($request);
		
		} else {
			
			/**
			 * Post Previewing
			 */
			
			if(!USE_AJAX) {
				$request['template']->setVar('L_TITLETOOSHORT', sprintf($request['template']->getVar('L_TITLETOOSHORT'), $request['template']->getVar('topicminchars'), $request['template']->getVar('topicmaxchars')));

				/* Get and set the emoticons and post icons to the template */
				$emoticons	= &$request['dba']->executeQuery("SELECT * FROM ". K4EMOTICONS ." WHERE clickable = 1");
				$posticons	= &$request['dba']->executeQuery("SELECT * FROM ". K4POSTICONS);

				$request['template']->setList('emoticons', $emoticons);
				$request['template']->setList('posticons', $posticons);

				$request['template']->setVar('emoticons_per_row', $request['template']->getVar('smcolumns'));
				$request['template']->setVar('emoticons_per_row_remainder', $request['template']->getVar('smcolumns')-1);
				
				post_attachment_options($request, $forum, $topic);
				topic_post_options($request['template'], $request['user'], $forum);

				/* Create our editor */
				create_editor($request, $_REQUEST['message'], 'post', $forum);
			}
			
			$topic_preview	= array(
								'topic_id' => @$topic['topic_id'],
								'name' => htmlentities(html_entity_decode($_REQUEST['name']), ENT_QUOTES),
								'posticon' => (isset($_REQUEST['posticon']) ? $_REQUEST['posticon'] : 'clear.gif'),
								'body_text' => $body_text,
								'poster_name' => html_entity_decode($topic['poster_name'], ENT_QUOTES),
								'poster_id' => $request['user']->get('id'),
								'is_poll' => $topic['is_poll'],
								'row_left' => 0,
								'row_right' => 0,
								'topic_type' => $topic_type,
								'is_feature' => $is_feature,
								'disable_html' => iif((isset($_REQUEST['disable_html']) && $_REQUEST['disable_html']), 1, 0),
								'disable_sig' => iif((isset($_REQUEST['enable_sig']) && $_REQUEST['enable_sig']), 0, 1),
								'disable_bbcode' => iif((isset($_REQUEST['disable_bbcode']) && $_REQUEST['disable_bbcode']), 1, 0),
								'disable_emoticons' => iif((isset($_REQUEST['disable_emoticons']) && $_REQUEST['disable_emoticons']), 1, 0),
								'disable_areply' => iif((isset($_REQUEST['disable_areply']) && $_REQUEST['disable_areply']), 1, 0),
								'disable_aurls' => iif((isset($_REQUEST['disable_aurls']) && $_REQUEST['disable_aurls']), 1, 0)
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
			
			if(!USE_AJAX) {
				/* Set the the button display options */
				$request['template']->setVisibility('save_draft', FALSE);
				$request['template']->setVisibility('load_button', FALSE);
				$request['template']->setVisibility('edit_topic', TRUE);
				$request['template']->setVisibility('topic_id', TRUE);
				$request['template']->setVisibility('post_topic', FALSE);
				$request['template']->setVisibility('edit_post', TRUE);
				
				/* set the breadcrumbs bit */
				k4_bread_crumbs($request['template'], $request['dba'], 'L_POSTTOPIC', $forum);
				
				/* Set the post topic form */
				$request['template']->setVar('forum_forum_id', $forum['forum_id']);
				$request['template']->setFile('preview', 'post_preview.html');
				$request['template']->setFile('content', 'newtopic.html');
			} else {
				$templateset = $request['user']->isMember() ? $request['user']->get('templateset') : $forum['defaultstyle'];
				echo $request['template']->run(BB_BASE_DIR .'/templates/'. $templateset .'/post_preview.html');
				exit;
			}
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
		
		/* set the breadcrumbs bit */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');

		if(!isset($_REQUEST['id']) || !$_REQUEST['id'] || intval($_REQUEST['id']) == 0) {
			$action = new K4InformationAction(new K4LanguageElement('L_TOPICDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}

		/* Get our topic */
		$topic				= $request['dba']->getRow("SELECT * FROM ". K4TOPICS ." WHERE topic_id = ". intval($_REQUEST['id']));
		
		if(!$topic || !is_array($topic) || empty($topic)) {
			$action = new K4InformationAction(new K4LanguageElement('L_TOPICDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}
			
		$forum				= $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($topic['forum_id']));
		
		/* Check the forum data given */
		if(!$forum || !is_array($forum) || empty($forum)) {
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}
			
		/* Make sure the we are trying to delete from a forum */
		if(!($forum['row_type'] & FORUM)) {
			$action = new K4InformationAction(new K4LanguageElement('L_CANTDELFROMNONFORUM'), 'content', FALSE);
			return $action->execute($request);
		}
		
		/* set the breadcrumbs bit */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_DELETETOPIC', $forum);
		
		/* Are we dealing with a topic or a poll? */
		$type				= $topic['is_poll'] == 1 ? 'polls' : 'topics';

		/* Does this person have permission to remove this topic? */
		if($topic['poster_id'] == $request['user']->get('id')) {
			if(get_map($request['user'], $type, 'can_del', array('forum_id'=>$forum['forum_id'])) > $request['user']->get('perms')) {
				no_perms_error($request);
				return TRUE;
			}
		} else {
			if(get_map($request['user'], 'other_'. $type, 'can_del', array('forum_id'=>$forum['forum_id'])) > $request['user']->get('perms')) {
				no_perms_error($request);
				return TRUE;
			}
		}

		if(!is_moderator($request['user']->getInfoArray(), $forum)) {
			no_perms_error($request);
			return TRUE;
		}

		/**
		 * Remove the topic and all of its replies
		 */
		
		/* Remove the topic and all replies from the information table */
		remove_item($topic['topic_id'], 'topic_id');
		
		// delete this topics attachments
		remove_attachments($request, $topic['topic_id']);
		$request['dba']->executeUpdate("DELETE FROM ". K4ATTACHMENTS ." WHERE topic_id = ". intval($topic['topic_id']) ." AND reply_id = 0");

		// delete any possible moved topic redirectors
		$request['dba']->executeUpdate("DELETE FROM ". K4TOPICS ." WHERE moved_new_topic_id = ". intval($topic['topic_id']));

		reset_cache(CACHE_DS_FILE);
		reset_cache(CACHE_EMAIL_FILE);
		
		/* Redirect the user */
		$action = new K4InformationAction(new K4LanguageElement('L_DELETEDTOPIC', $topic['name'], $forum['name']), 'content', FALSE, 'viewforum.php?f='. $forum['forum_id'], 3);

		return $action->execute($request);
	}
}

/**
 * Set the topic locking parameters
 */
class LockTopic extends FAAction {
	var $lock;
	function __construct($lock) {
		$this->lock		= intval($lock);
	}
	function execute(&$request) {
		
		global $_QUERYPARAMS, $_DATASTORE, $_USERGROUPS;
		
		/* set the breadcrumbs bit */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');

		if(!isset($_REQUEST['id']) || !$_REQUEST['id'] || intval($_REQUEST['id']) == 0) {
			$action = new K4InformationAction(new K4LanguageElement('L_TOPICDOESNTEXIST'), 'content', FALSE);
			return !USE_AJAX ? $action->execute($request) : exit();
		}

		/* Get our topic */
		$topic				= $request['dba']->getRow("SELECT * FROM ". K4TOPICS ." WHERE topic_id = ". intval($_REQUEST['id']));
		
		if(!$topic || !is_array($topic) || empty($topic)) {
			$action = new K4InformationAction(new K4LanguageElement('L_TOPICDOESNTEXIST'), 'content', FALSE);
			return !USE_AJAX ? $action->execute($request) : exit();
		}
			
		$forum				= $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($topic['forum_id']));
		
		/* Check the forum data given */
		if(!$forum || !is_array($forum) || empty($forum)) {
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);
			return !USE_AJAX ? $action->execute($request) : exit();
		}
			
		/* Make sure the we are trying to delete from a forum */
		if(!($forum['row_type'] & FORUM)) {
			$action = new K4InformationAction(new K4LanguageElement('L_CANTDELFROMNONFORUM'), 'content', FALSE);
			return !USE_AJAX ? $action->execute($request) : exit();
		}

		if(get_map($request['user'], 'closed', 'can_add', array('forum_id' => $forum['forum_id'])) > $request['user']->get('perms')) {
			$request['template']->setFile('content', '../login_form.html');
			$request['template']->setVisibility('no_perms', TRUE);
			return !USE_AJAX ? TRUE : exit();
		}

		if(!is_moderator($request['user']->getInfoArray(), $forum)) {
			$request['template']->setFile('content', '../login_form.html');
			$request['template']->setVisibility('no_perms', TRUE);
			return !USE_AJAX ? TRUE : exit();
		}
		
		/* set the breadcrumbs bit */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_LOCKTOPIC', $topic, $forum);
	
		/* Lock the topic */
		$lock		= &$request['dba']->prepareStatement("UPDATE ". K4TOPICS ." SET topic_locked=". $this->lock ." WHERE topic_id=?");
		$lock->setInt(1, $topic['topic_id']);
		$lock->executeUpdate();
		
		// remove any post report associated with this topic
		if($this->lock == 1)
			$request['dba']->executeUpdate("DELETE FROM ". K4BADPOSTREPORTS ." WHERE topic_id = ". intval($topic['topic_id']) ." AND reply_id = 0");

		/* Redirect the user */
		if(!USE_AJAX) {
			$action = new K4InformationAction(new K4LanguageElement($this->lock == 1 ? 'L_LOCKEDTOPIC' : 'L_UNLOCKEDTOPIC', $topic['name']), 'content', FALSE, 'viewtopic.php?id='. $topic['topic_id'], 3);
			return $action->execute($request);
		} else {
			echo $this->lock == 1 ? 'locked' : 'unlocked'; exit;
		}
	}
}


/**
 * Subscribe to a topic
 */
class SubscribeTopic extends FAAction {
	function execute(&$request) {
		
		global $_QUERYPARAMS;
		
		if(!$request['user']->isMember()) {
			no_perms_error($request);
			return TRUE;
		}
		
		/* set the breadcrumbs bit */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
		
		if(!isset($_REQUEST['id']) || !$_REQUEST['id'] || intval($_REQUEST['id']) == 0) {
			$action = new K4InformationAction(new K4LanguageElement('L_TOPICDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}
		
		/* Get our topic */
		$topic				= $request['dba']->getRow("SELECT * FROM ". K4TOPICS ." WHERE topic_id = ". intval($_REQUEST['id']));
		
		if(!$topic || !is_array($topic) || empty($topic)) {
			$action = new K4InformationAction(new K4LanguageElement('L_TOPICDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}
		
		$forum				= $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($topic['forum_id']));
		
		/* Check the forum data given */
		if(!$forum || !is_array($forum) || empty($forum)) {
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}

		$is_subscribed		= $request['dba']->getRow("SELECT * FROM ". K4SUBSCRIPTIONS ." WHERE user_id = ". intval($request['user']->get('id')) ." AND topic_id = ". intval($topic['topic_id']));
		
		if(is_array($is_subscribed) && !empty($is_subscribed)) {
			k4_bread_crumbs($request['template'], $request['dba'], 'L_SUBSCRIPTION', $topic, $forum);
			$action = new K4InformationAction(new K4LanguageElement('L_ALREADYSUBSCRIBED'), 'content', FALSE);
			return $action->execute($request);
		}
		
		$subscribe			= &$request['dba']->prepareStatement("INSERT INTO ". K4SUBSCRIPTIONS ." (user_id,user_name,topic_id,forum_id,email,category_id) VALUES (?,?,?,?,?,?)");
		$subscribe->setInt(1, $request['user']->get('id'));
		$subscribe->setString(2, $request['user']->get('name'));
		$subscribe->setInt(3, $topic['topic_id']);
		$subscribe->setInt(4, $topic['forum_id']);
		$subscribe->setString(5, $request['user']->get('email'));
		$subscribe->setInt(6, $topic['category_id']);
		$subscribe->executeUpdate();

		/* Redirect the user */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_SUBSCRIPTIONS', $topic, $forum);
		$action = new K4InformationAction(new K4LanguageElement('L_SUBSCRIBEDTOPIC', $topic['name']), 'content', FALSE, 'viewtopic.php?id='. $topic['topic_id'], 3);

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
			no_perms_error($request);
			return TRUE;
		}
		
		/* set the breadcrumbs bit */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
		
		if(!isset($_REQUEST['id']) || !$_REQUEST['id'] || intval($_REQUEST['id']) == 0) {
			$action = new K4InformationAction(new K4LanguageElement('L_TOPICDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}

		/* Get our topic */
		$topic				= $request['dba']->getRow("SELECT * FROM ". K4TOPICS ." WHERE topic_id = ". intval($_REQUEST['id']));
		
		if(!$topic || !is_array($topic) || empty($topic)) {
			$action = new K4InformationAction(new K4LanguageElement('L_TOPICDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}
		
		$forum				= $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($topic['forum_id']));
		
		/* Check the forum data given */
		if(!$forum || !is_array($forum) || empty($forum)) {
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}
		
		$subscribe			= &$request['dba']->prepareStatement("DELETE FROM ". K4SUBSCRIPTIONS ." WHERE user_id=? AND topic_id=?");
		$subscribe->setInt(1, $request['user']->get('id'));
		$subscribe->setInt(2, $topic['topic_id']);
		$subscribe->executeUpdate();

		/* Redirect the user */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_SUBSCRIPTIONS', $topic, $forum);
		$action = new K4InformationAction(new K4LanguageElement('L_UNSUBSCRIBEDTOPIC', $topic['name']), 'content', FALSE, referer(), 3); // 'viewtopic.php?id='. $topic['topic_id']

		return $action->execute($request);
	}
}

class RateTopic extends FAAction {
	function execute(&$request) {
		
		global $_QUERYPARAMS;
		
		if(!$request['user']->isMember()) {
			no_perms_error($request);
			return TRUE;
		}
		
		/* set the breadcrumbs bit */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
		
		if(!isset($_REQUEST['id']) || !$_REQUEST['id'] || intval($_REQUEST['id']) == 0) {
			$action = new K4InformationAction(new K4LanguageElement('L_TOPICDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}

		/* Get our topic */
		$topic				= $request['dba']->getRow("SELECT * FROM ". K4TOPICS ." WHERE topic_id = ". intval($_REQUEST['id']));
		
		if(!$topic || !is_array($topic) || empty($topic)) {
			$action = new K4InformationAction(new K4LanguageElement('L_TOPICDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}

		if(($topic['poster_id'] > 0) && $topic['poster_id'] == $request['user']->get('id')) {
			$action = new K4InformationAction(new K4LanguageElement('L_CANNOTRATEOWNPOSTS'), 'content', TRUE, referer(), 2);
			return $action->execute($request);
		}
		
		$forum				= $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($topic['forum_id']));
		
		if(!isset($_REQUEST['rating']) || $_REQUEST['rating'] < 0 || $_REQUEST['rating'] > 5) {
			$action = new K4InformationAction(new K4LanguageElement('L_SUPPLIEDBADRATING'), 'content', FALSE);
			return $action->execute($request);
		}

		/* Check the forum data given */
		if(!$forum || !is_array($forum) || empty($forum)) {
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}

		$has_rated		= $request['dba']->executeQuery("SELECT * FROM ". K4RATINGS ." WHERE topic_id = ". intval($topic['topic_id']) ." AND user_id = ". intval($request['user']->get('id')));
		if($has_rated->numRows() > 0) {
			$action = new K4InformationAction(new K4LanguageElement('L_ALREADYRATED', $topic['name']), 'content', FALSE);

			return $action->execute($request);
		}

		$add_rate		= &$request['dba']->prepareStatement("INSERT INTO ". K4RATINGS ." (topic_id,user_id,user_name) VALUES (?,?,?)");
		$add_rate->setInt(1, $topic['topic_id']);
		$add_rate->setInt(2, $request['user']->get('id'));
		$add_rate->setString(3, $request['user']->get('name'));

		$rating			= round(($topic['ratings_sum'] + $_REQUEST['rating']) / ($topic['ratings_num'] + 1), 0);
		
		$rate			= &$request['dba']->prepareStatement("UPDATE ". K4TOPICS ." SET ratings_sum=ratings_sum+?, ratings_num=ratings_num+1, rating=? WHERE topic_id=?");
		$rate->setInt(1, $_REQUEST['rating']);
		$rate->setInt(2, $rating);
		$rate->setInt(3, $topic['topic_id']);
		
		$add_rate->executeUpdate();
		$rate->executeUpdate();

		/* Redirect the user */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_RATETOPIC', $topic, $forum);
		$action = new K4InformationAction(new K4LanguageElement('L_RATEDTOPIC', $topic['name']), 'content', FALSE, referer(), 3);

		return $action->execute($request);
		
		return TRUE;
	}
}

/**
 * Make the topic image for a specified topic
 */
function topic_icon($seen_topics, &$topic, $img_dir) {
	
	global $_SETTINGS;

	$last_seen					= isset($_COOKIE[K4LASTSEEN]) ? $_COOKIE[K4LASTSEEN] : 0;
		
	$EXT						= '.gif';
		
	$type						= '';
	$use_dot					= (bool)($_SESSION['user']->get('id') == $topic['poster_id'] && $_SESSION['user']->get('id') != 0);
	$new						= (bool)($topic['last_post'] >= $last_seen);
	$hot						= (bool)(($topic['views'] >= 300) || ($topic['num_replies'] >= 30));
	
	if($topic['topic_type']		== TOPIC_ANNOUNCE) {
		$type					= 'announce';
		$use_dot				= FALSE;
		$hot					= FALSE;
	
	} elseif($topic['topic_type']		== TOPIC_STICKY) {
		$type					= 'sticky';
		$use_dot				= FALSE;
		$hot					= FALSE;
	
	} elseif($topic['is_feature']		== TOPIC_STICKY) {
		$type					= 'sticky';
		//$use_dot				= FALSE;
		//$hot					= FALSE;
	
	} elseif($topic['topic_type']		== TOPIC_NORMAL) {
		
		if($topic['moved']				== 1) {
			$type				= 'movedfolder';
			$use_dot			= FALSE;
			$hot				= FALSE;
		
		} elseif($topic['topic_locked'] == 1) {
			$use_dot			= FALSE;
			$type				= 'folder_lock';		
		} else {
			$type				= 'folder';
		}
	}

	if($topic['is_poll'] == 1) {
		$type					= 'poll';
	}

	if($topic['moved_new_topic_id'] > 0) {
		$type				= 'movedfolder';
		$use_dot			= FALSE;
		$hot				= FALSE;
	}

	if(isset($seen_topics[$topic['topic_id']])) {
		
		if($topic['last_post'] <= $seen_topics[$topic['topic_id']]) {
			$new				= FALSE;
		}
	} else {
		$new					= TRUE;
	}
	
	$image						= 'Images/'. $img_dir .'/Icons/Status/'. iif($use_dot, 'dot_', '') . iif($new, 'new', '') . iif($hot, 'hot', '') . $type . $EXT;
	
	$topic['topicicon']			= $image;

	unset($img_dir, $image, $seen_topics);

	return $new;
}

class TopicsIterator extends FAProxyIterator {
	
	var $result, $session, $img_dir, $forums, $dba, $user, $allforums, $cookieforums;
	
	function TopicsIterator(&$dba, &$user, $result, $img_dir, $forum) {
		$this->__construct($dba, $user, $result, $img_dir, $forum);
	}

	function __construct(&$dba, &$user, $result, $img_dir, $forum) {
		
		global $_ALLFORUMS, $_FLAGGEDUSERS;

		$this->result			= &$result;
		$this->session			= $_SESSION;
		$this->img_dir			= $img_dir;
		$this->forum			= $forum;
		$this->dba				= &$dba;
		$this->user				= &$user;
		$this->allforums		= $_ALLFORUMS;
		$this->cookietopics		= get_topic_cookies();
		$this->flagged_users	= $_FLAGGEDUSERS;
		
		parent::__construct($this->result);
	}

	function current() {
		$temp					= parent::current();

		/* Get this user's last seen time */
		//$last_seen				= is_a($this->session['user'], 'Member') ? iif($this->session['seen'] > $this->session['user']->info['last_seen'], $this->session['seen'], $this->session['user']->info['last_seen']) : $this->session['seen'];
		$last_seen				= time();

		/* Set the topic icons */
		$temp['posticon']		= $temp['posticon'] != '' ? iif(file_exists(BB_BASE_DIR .'/tmp/upload/posticons/'. $temp['posticon']), $temp['posticon'], 'clear.gif') : 'clear.gif';
		
		$new					= topic_icon($this->cookietopics, $temp, $this->img_dir);
		
		$temp['use_pager']		= 0;
		if($this->forum['postsperpage'] < $temp['num_replies']) {
			
			$limit				= $this->user->get('postsperpage') <= 0 ? $this->forum['postsperpage'] : $this->user->get('postsperpage');

			/* Create a pager */
			$temp['use_pager']	= 1;
			$temp['num_pages']	= @ceil($temp['num_replies'] / $limit);
			$temp['pager']		= paginate($temp['num_replies'], '&laquo;', '&lt;', '', '&gt;', '&raquo;', $limit, $temp['topic_id']);
		}

		if($temp['poster_id'] > 0) {
			if(in_array($temp['poster_id'], $this->flagged_users) && $_SESSION['user']->get('perms') >= MODERATOR) {
				$temp['post_user_background'] = 'background-color: #FFFF00;';
			}
		}
				
		/* Is this a sticky or an announcement and is it expired? */
		if($temp['topic_type'] > TOPIC_NORMAL && $temp['topic_expire'] > 0) {
			if(($temp['created'] + (3600 * 24 * $temp['topic_expire']) ) > time()) {
				
				$this->dba->executeUpdate("UPDATE ". K4TOPICS ." SET topic_expire=0,topic_type=". TOPIC_NORMAL ." WHERE topic_id = ". intval($temp['id']));
			}
		}

		$temp['forum_name']			= $this->allforums['f'. $temp['forum_id']]['name'];

		if($new) {
			$temp['is_new']			= 1;
		}

		$temp['num_replies']	= number_format($temp['num_replies']);
		$temp['views']			= number_format($temp['views']);
		
		/* Censor the topic name if needed */
		replace_censors($temp['name']);

		/* Should we free the result? */
		if(!$this->hasNext())
			$this->result->free();

		return $temp;
	}
}

class TopicIterator extends FAArrayIterator {
	
	var $dba, $result, $qp, $sr, $user, $reply_id;
	var $users = array();
	
	function TopicIterator(&$dba, &$user, $topic, $show_replies = TRUE, $reply_id = FALSE) {
		$this->__construct($dba, $user, $topic, $show_replies, $reply_id = FALSE);
	}

	function __construct(&$dba, &$user, $topic, $show_replies = TRUE, $reply_id = FALSE) {
		
		global $_QUERYPARAMS, $_USERGROUPS, $_USERFIELDS;
		
		$this->qp						= $_QUERYPARAMS;
		$this->sr						= (bool)$show_replies;
		$this->dba						= &$dba;
		$this->user						= &$user;
		$this->groups					= $_USERGROUPS;
		$this->fields					= $_USERFIELDS;
		$this->reply_id					= intval($reply_id);
				
		parent::__construct(array(0 => $topic));
	}

	function &current() {
		$temp							= parent::current();
				
		$temp['posticon']				= @$temp['posticon'] != '' ? (file_exists(BB_BASE_DIR .'/tmp/upload/posticons/'. @$temp['posticon']) ? @$temp['posticon'] : 'clear.gif') : 'clear.gif';

		if($temp['poster_id'] > 0) {
			
			if(!isset($this->users[$temp['poster_id']])) {
				$user						= $this->dba->getRow("SELECT ". $this->qp['user'] . $this->qp['userinfo'] ." FROM ". K4USERS ." u LEFT JOIN ". K4USERINFO ." ui ON u.id=ui.user_id WHERE u.id=". intval($temp['poster_id']));
				
				if(is_array($user) && !empty($user)) {
					$group						= get_user_max_group($user, $this->groups);
					$user['group_color']		= (!isset($group['color']) || $group['color'] == '') ? '000000' : $group['color'];
					$user['group_nicename']		= isset($group['nicename']) ? $group['nicename'] : '';
					$user['group_avatar']		= isset($group['avatar']) ? $group['avatar'] : '';
					$user['online']				= (time() - ini_get('session.gc_maxlifetime')) > $user['seen'] ? 'offline' : 'online';
					$this->users[$user['id']]	= $user;
				}
			} else {
				$user						= $this->users[$temp['poster_id']];
			}
			
			if(is_array($user) && !empty($user)) {

				if($user['flag_level'] > 0 && $_SESSION['user']->get('perms') >= MODERATOR)
					$temp['post_user_background'] = 'background-color: #FFFF00;';

				foreach($user as $key => $val)
					$temp['post_user_'. $key] = $val;
			
				$temp['profilefields']			= &new FAArrayIterator(get_profile_fields($this->fields, $temp));
			}

			if(!isset($temp['post_user_online']))
				$temp['post_user_online'] = 'offline';

			/* This array holds all of the userinfo for users that post to this topic */
			$this->users[$user['id']]			= $user;
			
		} else {
			$temp['post_user_id']			= 0;
			$temp['post_user_name']			= $temp['poster_name'];
		}
		
		/* Deal with acronyms */
		replace_acronyms($temp['body_text']);
		
		/* word censors */
		replace_censors($temp['body_text']);
		replace_censors($temp['name']);

		/* Do any polls if they exist */
		do_post_polls($temp, $this->dba);

		/* do we have any attachments? */
		if(isset($temp['attachments']) && $temp['attachments'] > 0) {
			$temp['attachment_files']		= &new K4AttachmentsIterator($this->dba, $this->user, $temp['topic_id'], 0);
		}

		if($this->sr && $temp['num_replies'] > 0) {
			$this->result					= &$this->dba->executeQuery("SELECT * FROM ". K4REPLIES ." WHERE topic_id = ". intval($temp['topic_id']) ." ". ($this->reply_id ? "AND reply_id = ". $this->reply_id : "") ." AND created >= ". (3600 * 24 * intval($temp['daysprune'])) ." ORDER BY ". $temp['sortedby'] ." ". $temp['sortorder'] ." LIMIT ". intval($temp['start']) .",". intval($temp['postsperpage']));
			$temp['replies']				= &new RepliesIterator($this->user, $this->dba, $this->result, $this->qp, $this->users, $this->groups, $this->fields);
		}
		
		return $temp;
	}
}


function topic_post_options(&$template, &$user, $forum) {
	
	/** 
	 * Set the posting allowances for a specific forum
	 */
	$template->setVar('forum_user_topic_options', sprintf($template->getVar('L_FORUMUSERTOPICPERMS'),
	iif((get_map($user, 'topics', 'can_add', array('forum_id'=>$forum['forum_id'])) > $user->get('perms')), $template->getVar('L_CANNOT'), $template->getVar('L_CAN')),
	iif((get_map($user, 'topics', 'can_edit', array('forum_id'=>$forum['forum_id'])) > $user->get('perms')), $template->getVar('L_CANNOT'), $template->getVar('L_CAN')),
	iif((get_map($user, 'topics', 'can_del', array('forum_id'=>$forum['forum_id'])) > $user->get('perms')), $template->getVar('L_CANNOT'), $template->getVar('L_CAN')),
	iif((get_map($user, 'attachments', 'can_add', array('forum_id'=>$forum['forum_id'])) > $user->get('perms')), $template->getVar('L_CANNOT'), $template->getVar('L_CAN'))));

	$template->setVar('forum_user_reply_options', sprintf($template->getVar('L_FORUMUSERREPLYPERMS'),
	iif((get_map($user, 'replies', 'can_add', array('forum_id'=>$forum['forum_id'])) > $user->get('perms')), $template->getVar('L_CANNOT'), $template->getVar('L_CAN')),
	iif((get_map($user, 'replies', 'can_edit', array('forum_id'=>$forum['forum_id'])) > $user->get('perms')), $template->getVar('L_CANNOT'), $template->getVar('L_CAN')),
	iif((get_map($user, 'replies', 'can_del', array('forum_id'=>$forum['forum_id'])) > $user->get('perms')), $template->getVar('L_CANNOT'), $template->getVar('L_CAN'))));
	
	$template->setVar('posting_code_options', sprintf($template->getVar('L_POSTBBCODEOPTIONS'),
	iif((get_map($user, 'html', 'can_add', array('forum_id'=>$forum['forum_id'])) > $user->get('perms')), $template->getVar('L_OFF'), $template->getVar('L_ON')),
	iif((get_map($user, 'bbcode', 'can_add', array('forum_id'=>$forum['forum_id'])) > $user->get('perms')), $template->getVar('L_OFF'), $template->getVar('L_ON')),
	iif((get_map($user, 'bbimgcode', 'can_add', array('forum_id'=>$forum['forum_id'])) > $user->get('perms')), $template->getVar('L_OFF'), $template->getVar('L_ON')),
	iif((get_map($user, 'bbflashcode', 'can_add', array('forum_id'=>$forum['forum_id'])) > $user->get('perms')), $template->getVar('L_OFF'), $template->getVar('L_ON')),
	iif((get_map($user, 'emoticons', 'can_add', array('forum_id'=>$forum['forum_id'])) > $user->get('perms')), $template->getVar('L_OFF'), $template->getVar('L_ON'))));
}

?>