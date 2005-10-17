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
* @version $Id: replies.class.php 154 2005-07-15 02:56:28Z Peter Goodman $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	return;
}

/**
 * Post / Preview a reply
 */
class PostReply extends FAAction {
	function execute(&$request) {
		global $_QUERYPARAMS, $_DATASTORE, $_SETTINGS;

		$this->dba			= &$request['dba'];
		
		/* Prevent post flooding */
		$last_topic		= $request['dba']->getRow("SELECT * FROM ". K4TOPICS ." WHERE poster_ip = '". USER_IP ."' ". ($request['user']->isMember() ? "OR poster_id = ". intval($request['user']->get('id')) : '') ." ORDER BY created DESC LIMIT 1");
		$last_reply		= $request['dba']->getRow("SELECT * FROM ". K4REPLIES ." WHERE poster_ip = '". USER_IP ."' ". ($request['user']->isMember() ? "OR poster_id = ". intval($request['user']->get('id')) : '') ." ORDER BY created DESC LIMIT 1");
		
		/* set the breadcrumbs bit */
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

		/* Check the request ID */
		if(!isset($_REQUEST['topic_id']) || !$_REQUEST['topic_id'] || intval($_REQUEST['topic_id']) == 0) {
			$action = new K4InformationAction(new K4LanguageElement('L_TOPICDOESNTEXIST'), 'content', FALSE);
			return !USE_AJAX ? $action->execute($request) : ajax_message('L_TOPICDOESNTEXIST');
		}

		/* Get our topic */
		$topic				= $request['dba']->getRow("SELECT * FROM ". K4TOPICS ." WHERE topic_id = ". intval($_REQUEST['topic_id']));
		
		if(!$topic || !is_array($topic) || empty($topic)) {
			$action = new K4InformationAction(new K4LanguageElement('L_TOPICDOESNTEXIST'), 'content', FALSE);
			return !USE_AJAX ? $action->execute($request) : ajax_message('L_TOPICDOESNTEXIST');
		}

		$topic['id'] = $topic['topic_id'];
			
		$forum				= $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($topic['forum_id']));
		
		/* Check the forum data given */
		if(!$forum || !is_array($forum) || empty($forum)) {
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);
			return !USE_AJAX ? $action->execute($request) : ajax_message('L_FORUMDOESNTEXIST');
		}
			
		/* Make sure the we are trying to delete from a forum */
		if(!($forum['row_type'] & FORUM)) {
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);
			return !USE_AJAX ? $action->execute($request) : ajax_message('L_FORUMDOESNTEXIST');
		}

		/* Do we have permission to post to this topic in this forum? */
		if($request['user']->get('perms') < get_map($request['user'], 'replies', 'can_add', array('forum_id'=>$forum['forum_id']))) {
			$action = new K4InformationAction(new K4LanguageElement('L_PERMCANTPOST'), 'content', FALSE);
			return !USE_AJAX ? $action->execute($request) : ajax_message('L_PERMCANTPOST');	
		}

		/* Does this user have permission to reply to this topic if it is locked? */
		if($topic['topic_locked'] == 1 && get_map($request['user'], 'closed', 'can_add', array('forum_id' => $forum['forum_id'])) > $request['user']->get('perms')) {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);
			return !USE_AJAX ? $action->execute($request) : ajax_message('L_YOUNEEDPERMS');
		}

		if(isset($_REQUEST['parent_id']) && intval($_REQUEST['parent_id']) != 0) {
			$reply				= $request['dba']->getRow("SELECT * FROM ". K4REPLIES ." WHERE reply_id = ". intval($_REQUEST['parent_id']));
			
			if(!$reply || !is_array($reply) || empty($reply)) {
				$action = new K4InformationAction(new K4LanguageElement('L_REPLYDOESNTEXIST'), 'content', FALSE);
				return !USE_AJAX ? $action->execute($request) : ajax_message('L_REPLYDOESNTEXIST');
			}

			$reply['id'] = $reply['reply_id'];
		}

		/* Settings for the parent id */
		$parent					= isset($reply) && is_array($reply) ? $reply : $topic;

		/* Do we have permission to post to this forum? */
		if($request['user']->get('perms') < get_map($request['user'], 'topics', 'can_add', array('forum_id'=>$forum['forum_id']))) {
			$action = new K4InformationAction(new K4LanguageElement('L_PERMCANTPOST'), 'content', FALSE);
			return !USE_AJAX ? $action->execute($request) : ajax_message('L_PERMCANTPOST');
		}

		/* General error checking */
		if(!isset($_REQUEST['name']) || $_REQUEST['name'] == '') {
			$_REQUEST['name'] = 'Re: '. html_entity_decode($parent['name'], ENT_QUOTES);
			$_POST['name'] = 'Re: '. html_entity_decode($parent['name'], ENT_QUOTES);
		}

		if (!$this->runPostFilter('name', new FALengthFilter(intval($_SETTINGS['topicmaxchars'])))) {
			$action = new K4InformationAction(new K4LanguageElement('L_TITLETOOSHORT', intval($_SETTINGS['topicminchars']), intval($_SETTINGS['topicmaxchars'])), 'content', TRUE);
			return !USE_AJAX ? $action->execute($request) : ajax_message('L_TITLETOOSHORT');
		}
		if (!$this->runPostFilter('name', new FALengthFilter(intval($_SETTINGS['topicmaxchars']), intval($_SETTINGS['topicminchars'])))) {
			$action = new K4InformationAction(new K4LanguageElement('L_TITLETOOSHORT', intval($_SETTINGS['topicminchars']), intval($_SETTINGS['topicmaxchars'])), 'content', TRUE);
			return !USE_AJAX ? $action->execute($request) : ajax_message('L_TITLETOOSHORT');
		}

		if(!isset($_REQUEST['message']) || $_REQUEST['message'] == '') {
			$action = new K4InformationAction(new K4LanguageElement('L_INSERTREPLYMESSAGE'), 'content', TRUE);
			return !USE_AJAX ? $action->execute($request) : ajax_message('L_INSERTREPLYMESSAGE');
		}

		/* Exit right here to send no content to the browser if ajax is enabled */
		//if(USE_AJAX) exit;
		
				
		/**
		 * Start building info for the queries
		 */
		
		/* Set this nodes level */
		$level				= $parent['row_level']+1;
		
		/* Set the topic created time */
		$created			= time();
		
		/* Initialize the bbcode parser with the topic message */
		$_REQUEST['message']	= substr($_REQUEST['message'], 0, $_SETTINGS['postmaxchars']);
		$bbcode	= &new BBCodex($request['dba'], $request['user']->getInfoArray(), $_REQUEST['message'], $forum['forum_id'], 
			iif((isset($_REQUEST['disable_html']) && $_REQUEST['disable_html']), FALSE, TRUE), 
			iif((isset($_REQUEST['disable_bbcode']) && $_REQUEST['disable_bbcode']), FALSE, TRUE), 
			iif((isset($_REQUEST['disable_emoticons']) && $_REQUEST['disable_emoticons']), FALSE, TRUE), 
			iif((isset($_REQUEST['disable_aurls']) && $_REQUEST['disable_aurls']), FALSE, TRUE));
		
		/* Parse the bbcode */
		$body_text		= $bbcode->parse();
		
		// permissions are taken into account inside the poller
		$poller			= &new K4BBPolls($body_text, '', $forum, 0);
		$is_poll		= 0;
		$poll_text		= $poller->parse($request, $is_poll);
		
		if((isset($_REQUEST['submit_type']) && $_REQUEST['submit_type'] == 'post') || isset($_REQUEST['post'])) {
			
			if($body_text != $poll_text) {
				$body_text = $poll_text;
				$is_poll	= 1;
			}
			
			/* Make sure we're not double-posting */
			if(!empty($last_reply) && (($_REQUEST['name'] == $last_reply['name']) && ($body_text == $last_reply['body_text']))) {
				$action = new K4InformationAction(new K4LanguageElement('L_DOUBLEPOSTED'), 'content', TRUE, 'findpost.php?id='. $last_reply['reply_id'], 3);
				return !USE_AJAX ? $action->execute($request) : ajax_message('L_DOUBLEPOSTED');
			}

			/**
			 * Deal with the order of this reply
			 */
			$fix_order = FALSE;
			if($topic['num_replies'] == 0) {
				$row_order = 1;
			} else {
				if($parent['row_type'] & TOPIC) {
					$row_order = $topic['num_replies'] + 1;
				} else {
					$row_order = $parent['row_order'] + 1;
					$fix_order = $parent['reply_id'] == $topic['reply_id'] ? FALSE : TRUE;
				}
			}
			
			// fix the order of other replies if needed
			if($fix_order) {
				$request['dba']->executeUpdate("UPDATE ". K4REPLIES ." SET row_order = row_order+1 WHERE row_order >= ". intval($row_order));
			}
						
			/**
			 * Build the queries
			 */
			
			$request['dba']->beginTransaction();

			/* Prepare the query */
			$insert_a			= &$request['dba']->prepareStatement("INSERT INTO ". K4REPLIES ." (name,topic_id,forum_id,category_id,poster_name,poster_id,poster_ip,body_text,posticon,disable_html,disable_bbcode,disable_emoticons,disable_sig,disable_areply,disable_aurls,is_poll,row_type,row_level,created,parent_id,row_order) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
			
			$poster_name		= iif($request['user']->get('id') <= 0, htmlentities((isset($_REQUEST['poster_name']) ? $_REQUEST['poster_name'] : '') , ENT_QUOTES), $request['user']->get('name'));
			
			//topic_id,forum_id,category_id,poster_name,poster_id,body_text,posticon
			//disable_html,disable_bbcode,disable_emoticons,disable_sig,disable_areply,disable_aurls,is_draft
			$insert_a->setString(1, htmlentities(html_entity_decode($_REQUEST['name'], ENT_QUOTES), ENT_QUOTES));
			$insert_a->setInt(2, $topic['topic_id']);
			$insert_a->setInt(3, $forum['forum_id']);
			$insert_a->setInt(4, $forum['category_id']);
			$insert_a->setString(5, $poster_name);
			$insert_a->setInt(6, $request['user']->get('id'));
			$insert_a->setString(7, USER_IP);
			$insert_a->setString(8, $body_text);
			$insert_a->setString(9, (($request['user']->get('perms') >= get_map($request['user'], 'posticons', 'can_add', array('forum_id'=>$forum['forum_id']))) ? (isset($_REQUEST['posticon']) ? $_REQUEST['posticon'] : 'clear.gif') : 'clear.gif'));
			$insert_a->setInt(10, ((isset($_REQUEST['disable_html']) && $_REQUEST['disable_html']) ? 1 : 0));
			$insert_a->setInt(11, ((isset($_REQUEST['disable_bbcode']) && $_REQUEST['disable_bbcode']) ? 1 : 0));
			$insert_a->setInt(12, ((isset($_REQUEST['disable_emoticons']) && $_REQUEST['disable_emoticons']) ? 1 : 0));
			$insert_a->setInt(13, ((isset($_REQUEST['enable_sig']) && $_REQUEST['enable_sig']) ? 0 : 1));
			$insert_a->setInt(14, ((isset($_REQUEST['disable_areply']) && $_REQUEST['disable_areply']) ? 1 : 0));
			$insert_a->setInt(15, ((isset($_REQUEST['disable_aurls']) && $_REQUEST['disable_aurls']) ? 1 : 0));
			$insert_a->setString(16, $is_poll);
			$insert_a->setInt(17, REPLY);
			$insert_a->setInt(18, $level);
			$insert_a->setInt(19, $created);
			$insert_a->setInt(20, ($parent['row_type'] & TOPIC ? $parent['topic_id'] : $parent['reply_id']));
			$insert_a->setInt(21, $row_order);

			$insert_a->executeUpdate();

			$reply_id			= $request['dba']->getInsertId(K4REPLIES, 'reply_id');
			
			/** 
			 * Update the forum, and update the datastore 
			 */

			//topic_created,topic_name,topic_uname,topic_id,topic_uid,post_created,post_name,post_uname,post_id,post_uid
			$forum_update		= &$request['dba']->prepareStatement("UPDATE ". K4FORUMS ." SET replies=replies+1,posts=posts+1,post_created=?,post_name=?,post_uname=?,post_id=?,post_uid=?,post_posticon=? WHERE forum_id=?");
			$topic_update		= &$request['dba']->prepareStatement("UPDATE ". K4TOPICS ." SET num_replies=?,reply_time=?,reply_uname=?,reply_id=?,reply_uid=?,last_post=? WHERE topic_id=?");
			$datastore_update	= &$request['dba']->prepareStatement("UPDATE ". K4DATASTORE ." SET data=? WHERE varname=?");
			$request['dba']->executeUpdate("UPDATE ". K4USERINFO ." SET num_posts=num_posts+1,total_posts=total_posts+1 WHERE user_id=". intval($request['user']->get('id')));
			
			/* Update the forums and datastore tables */

			/* Set the forum values */
			$forum_update->setInt(1, $created);
			$forum_update->setString(2, htmlentities(html_entity_decode($_REQUEST['name']), ENT_QUOTES));
			$forum_update->setString(3, $poster_name);
			$forum_update->setInt(4, $reply_id);
			$forum_update->setInt(5, $request['user']->get('id'));
			$forum_update->setString(6, iif(($request['user']->get('perms') >= get_map($request['user'], 'posticons', 'can_add', array('forum_id'=>$forum['forum_id']))), (isset($_REQUEST['posticon']) ? $_REQUEST['posticon'] : 'clear.gif'), 'clear.gif'));
			$forum_update->setInt(7, $forum['forum_id']);

			/* Set the topic values */
			$topic_update->setInt(1, $request['dba']->getValue("SELECT COUNT(*) FROM ". K4REPLIES ." WHERE topic_id = ". intval($topic['topic_id']))); // make SURE to get the right count
			$topic_update->setInt(2, $created);
			$topic_update->setString(3, $poster_name);
			$topic_update->setInt(4, $reply_id);
			$topic_update->setInt(5, $request['user']->get('id'));
			$topic_update->setInt(6, $created);
			$topic_update->setInt(7, $topic['topic_id']);
			
			/* Set the datastore values */
			$datastore					= $_DATASTORE['forumstats'];
			$datastore['num_replies']	= $request['dba']->getValue("SELECT COUNT(*) FROM ". K4REPLIES);
			
			$datastore_update->setString(1, serialize($datastore));
			$datastore_update->setString(2, 'forumstats');
			
			/**
			 * Update the forums table and datastore table
			 */
			$forum_update->executeUpdate();
			$topic_update->executeUpdate();
			$datastore_update->executeUpdate();
			
			/* Added the reply */
			reset_cache(CACHE_DS_FILE);
			
			// deal with attachments
			attach_files($request, $forum, $topic['topic_id'], $reply_id);

			set_send_reply_mail($topic['topic_id'], iif($poster_name == '', $request['template']->getVar('L_GUEST'), $poster_name));
			
			/**
			 * Subscribe this user to the topic
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
			}
			
			/* Commit the current transaction */
			$request['dba']->commitTransaction();
			
			/* set the breadcrumbs bit */
			k4_bread_crumbs($request['template'], $request['dba'], 'L_POSTREPLY', $parent, $forum);
			
			/* Redirect the user */
			if(!USE_AJAX) {
				$action = new K4InformationAction(new K4LanguageElement('L_ADDEDREPLY', htmlentities(html_entity_decode($_REQUEST['name']), ENT_QUOTES), $topic['name']), 'content', FALSE, 'findpost.php?id='. $reply_id, 3);
				return $action->execute($request);
			} else {
				global $_URL;
				
				/**
				 * Now figure out the annoying stuff to pass to the
				 * page for the javascript to interpret
				 */
				
				$page = 1;
				if(isset($_REQUEST['page']))
					$page	= intval($_REQUEST['page']) <= 0 ? 1 : intval($_REQUEST['page']);
				$limit		= $request['user']->get('postsperpage') <= 0 ? $forum['postsperpage'] : $request['user']->get('postsperpage');
				
				/* Send a javascript redirect to the browser */
				if(ceil(($topic['num_replies']+1) / $limit) > $page) {
					$html	= '<div style="text-align: center;"><a href="viewtopic.php?id='. $topic['topic_id'] .'&page='. ceil(($topic['num_replies']+1) / $limit) .'&limit='. $limit .'?p='. $reply_id .'#p'. $reply_id .'" title="'. $request['template']->getVar('L_SEEYOURPOST') .'" style="font-weight: bold;">'. $request['template']->getVar('L_SEEYOURPOST') .'</a></div><br />';
					echo $html;
					exit;

				/* Display fancy template */
				} else {
					
					global $_QUERYPARAMS, $_USERGROUPS, $_USERFIELDS;

					$result	= $request['dba']->executeQuery("SELECT * FROM ". K4REPLIES ." WHERE reply_id = {$reply_id} LIMIT 1");
					$it		= &new RepliesIterator($request['user'], $request['dba'], $result, $_QUERYPARAMS, array(), $_USERGROUPS, $_USERFIELDS);
					$reply	= $it->next();
					
					$reply['topic_row'] = 1;
					$reply['reply_row'] = 1;
					$reply['row_class'] = 'alt1';

					foreach($topic as $key => $val)
						$reply['topic_'. $key]	= $val;

					foreach($reply as $key => $val)
						$request['template']->setVar($key, $val);

					$request['template']->setVisibility('in_topicview', FALSE);
					
					$templateset = $request['user']->isMember() ? $request['user']->get('templateset') : $forum['defaultstyle'];

					$html	= '<div class="k4_borderwrap"><table width="100%" cellpadding="0" cellspacing="'. K4_TABLE_CELLSPACING .'" border="0" class="k4_table">';
					$html	.= $request['template']->run(BB_BASE_DIR .'/templates/'. $templateset .'/reply'. ($request['user']->get('topic_display') == 0 ? '' : '_linear') .'.html');
					$html	.= '</table></div>';

					echo $html;
					exit;
				}
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

				$request['template']->setList('emoticons', $emoticons);
				$request['template']->setList('posticons', $posticons);

				$request['template']->setVar('emoticons_per_row', $request['template']->getVar('smcolumns'));
				$request['template']->setVar('emoticons_per_row_remainder', $request['template']->getVar('smcolumns')-1);
				
				topic_post_options($request['template'], $request['user'], $forum);
				
				/**
				 * Deal with reply attachments
				 */
				$num_attachments		= 0;
				/**
				 * Deal with file attachments
				 */
				if($request['template']->getVar('attach_inputs') == '') {
					if($request['user']->get('perms') >= get_map($request['user'], 'attachments', 'can_add', array('forum_id'=>$forum['forum_id']))) {
						$num_attachments	= $request['template']->getVar('nummaxattaches') - $num_attachments;
						
						$attach_inputs		= '';
						for($i = 1; $i <= $num_attachments; $i++) {
							$attach_inputs	.= '<br /><input type="file" class="inputbox" name="attach'. $i .'" id="attach'. $i .'" value="" size="55" />';
						}
						
						$request['template']->setVar('attach_inputs', $attach_inputs);
					}
				}
			}

			/* Create our editor */
			create_editor($request, $_REQUEST['message'], 'post', $forum);

			/* Set the forum info to the template */
			foreach($forum as $key => $val)
				$request['template']->setVar('forum_'. $key, $val);
			
			/* Set template information for this iterator */								
			$reply_preview	= array(
								'name' => htmlentities(html_entity_decode($_REQUEST['name']), ENT_QUOTES),
								'body_text' => $body_text,
								'poster_name' => $request['user']->get('name'),
								'poster_id' => $request['user']->get('id'),
								'forum_id' => $forum['forum_id'],
								'topic_id' => $topic['topic_id'],
								'is_poll' => 0,
								'row_left' => 0,
								'row_right' => 0,
								'posticon' => iif(($request['user']->get('perms') >= get_map($request['user'], 'posticons', 'can_add', array('forum_id'=>$forum['forum_id']))), (isset($_REQUEST['posticon']) ? $_REQUEST['posticon'] : 'clear.gif'), 'clear.gif'),
								'disable_html' => iif((isset($_REQUEST['disable_html']) && $_REQUEST['disable_html']), 1, 0),
								'disable_sig' => iif((isset($_REQUEST['enable_sig']) && $_REQUEST['enable_sig']), 0, 1),
								'disable_bbcode' => iif((isset($_REQUEST['disable_bbcode']) && $_REQUEST['disable_bbcode']), 1, 0),
								'disable_emoticons' => iif((isset($_REQUEST['disable_emoticons']) && $_REQUEST['disable_emoticons']), 1, 0),
								'disable_areply' => iif((isset($_REQUEST['disable_areply']) && $_REQUEST['disable_areply']), 1, 0),
								'disable_aurls' => iif((isset($_REQUEST['disable_aurls']) && $_REQUEST['disable_aurls']), 1, 0)
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
			
			foreach($parent as $key => $val)
				$request['template']->setVar('parent_'. $key, $val);

			if(!USE_AJAX) {
				/* set the breadcrumbs bit */
				k4_bread_crumbs($request['template'], $request['dba'], 'L_POSTREPLY', $parent, $forum);
				
				/* Get replies that are above this point */
				$replies	= &$request['dba']->executeQuery("SELECT * FROM ". K4REPLIES ." WHERE topic_id = ". intval($topic['topic_id']) ." ORDER BY created DESC LIMIT 10");
				
				$it = &new TopicReviewIterator($request['dba'], $topic, $replies, $request['user']->getInfoArray());
				$request['template']->setList('topic_review', $it);

				/* Set the post topic form */
				$request['template']->setFile('preview', 'post_preview.html');
				$request['template']->setFile('content', 'newreply.html');
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
 * Edit a reply
 */
class EditReply extends FAAction {
	function execute(&$request) {
		
		global $_QUERYPARAMS, $_DATASTORE;
		
		/* set the breadcrumbs bit */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');

		/* Get our reply */
		$reply				= $request['dba']->getRow("SELECT * FROM ". K4REPLIES ." WHERE reply_id = ". intval($_REQUEST['id']));
		
		if(!$reply || !is_array($reply) || empty($reply)) {
			$action = new K4InformationAction(new K4LanguageElement('L_REPLYDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}

		$topic				= $request['dba']->getRow("SELECT * FROM ". K4TOPICS ." WHERE topic_id = ". intval($reply['topic_id']));
		
		if(!$topic || !is_array($topic) || empty($topic)) {
			$action = new K4InformationAction(new K4LanguageElement('L_TOPICDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}
		
		$forum				= $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($reply['forum_id']));
		
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
		
		/* Does this user have permission to edit theirreply if the topic is locked? */
		if($topic['topic_locked'] == 1 && get_map($request['user'], 'closed', 'can_edit', array('forum_id' => $forum['forum_id'])) > $request['user']->get('perms')) {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);
			return $action->execute($request);
		}

		/* set the breadcrumbs bit */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_EDITREPLY', $topic, $forum);
		
		if($reply['poster_id'] == $request['user']->get('id')) {
			if(get_map($request['user'], 'replies', 'can_edit', array('forum_id'=>$forum['forum_id'])) > $request['user']->get('perms')) {
				$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);
				return $action->execute($request);
			}
		} else {
			if(get_map($request['user'], 'other_replies', 'can_edit', array('forum_id'=>$forum['forum_id'])) > $request['user']->get('perms')) {
				$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);
				return $action->execute($request);
			}
		}
		
		$bbcode				= &new BBCodex($request['dba'], $request['user']->getInfoArray(), $reply['body_text'], $forum['forum_id'], TRUE, TRUE, TRUE, TRUE);
		
		/* Get and set the emoticons and post icons to the template */
		$emoticons			= &$request['dba']->executeQuery("SELECT * FROM ". K4EMOTICONS ." WHERE clickable = 1");
		$posticons			= &$request['dba']->executeQuery("SELECT * FROM ". K4POSTICONS);

		$request['template']->setList('emoticons', $emoticons);
		$request['template']->setList('posticons', $posticons);

		$request['template']->setVar('emoticons_per_row', $request['template']->getVar('smcolumns'));
		$request['template']->setVar('emoticons_per_row_remainder', $request['template']->getVar('smcolumns')-1);
		
		/* Get the posting options */
		topic_post_options($request['template'], $request['user'], $forum);
		post_attachment_options($request, $forum, $topic, $reply);
		
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
		$replies	= &$request['dba']->executeQuery("SELECT * FROM ". K4REPLIES ." WHERE topic_id = ". intval($topic['topic_id']) ." AND reply_id < ". intval($reply['reply_id']) ." ORDER BY created DESC LIMIT 10");

		/* Set the topic preview for this reply editing */
		$it = &new TopicReviewIterator($request['dba'], $topic, $replies, $request['user']->getInfoArray());
		$request['template']->setList('topic_review', $it);

		/* set the breadcrumbs bit */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_EDITREPLY', $topic, $forum);
		
		/* Create our editor */
		create_editor($request, $bbcode->revert(), 'post', $forum);

		/* Set the post topic form */
		$request['template']->setFile('preview', 'post_preview.html');
		$request['template']->setFile('content', 'newreply.html');
		$request['template']->setVar('L_TITLETOOSHORT', sprintf($request['template']->getVar('L_TITLETOOSHORT'), $request['template']->getVar('topicminchars'), $request['template']->getVar('topicmaxchars')));

		return TRUE;
	}
}

/**
 * Update a reply
 */
class UpdateReply extends FAAction {
	function execute(&$request) {
		
		global $_QUERYPARAMS, $_DATASTORE, $_SETTINGS;
		
		/* set the breadcrumbs bit */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');

		/* Check the request ID */
		if(!isset($_REQUEST['forum_id']) || !$_REQUEST['forum_id'] || intval($_REQUEST['forum_id']) == 0) {
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);
			return !USE_AJAX ? $action->execute($request) : ajax_message('L_INVALIDFORUM');
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
		
		/* Get our topic and our reply */
		$topic				= $request['dba']->getRow("SELECT * FROM ". K4TOPICS ." WHERE topic_id = ". intval($_REQUEST['topic_id']));
		$reply				= $request['dba']->getRow("SELECT * FROM ". K4REPLIES ." WHERE reply_id = ". intval($_REQUEST['reply_id']));
		
		if(!$topic || !is_array($topic) || empty($topic)) {
			$action = new K4InformationAction(new K4LanguageElement('L_TOPICDOESNTEXIST'), 'content', FALSE);
			return !USE_AJAX ? $action->execute($request) : ajax_message('L_TOPICDOESNTEXIST');
		}
		
		/* Does this user have permission to edit theirreply if the topic is locked? */
		if($topic['topic_locked'] == 1 && get_map($request['user'], 'closed', 'can_edit', array('forum_id' => $forum['forum_id'])) > $request['user']->get('perms')) {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);
			return !USE_AJAX ? $action->execute($request) : ajax_message('L_YOUNEEDPERMS');
		}
		
		/* is this topic part of the moderator's queue? */
		if($topic['queue'] == 1) {
			$action = new K4InformationAction(new K4LanguageElement('L_TOPICPENDINGMOD'), 'content', FALSE);
			return !USE_AJAX ? $action->execute($request) : ajax_message('L_TOPICPENDINGMOD');
		}

		/* Is this topic hidden? */
		if($topic['display'] == 0) {
			$action = new K4InformationAction(new K4LanguageElement('L_TOPICISHIDDEN'), 'content', FALSE);
			return !USE_AJAX ? $action->execute($request) : ajax_message('L_TOPICISHIDDEN');
		}

		if(!$reply || !is_array($reply) || empty($reply)) {
			$action = new K4InformationAction(new K4LanguageElement('L_REPLYDOESNTEXIST'), 'content', FALSE);
			return !USE_AJAX ? $action->execute($request) : ajax_message('L_REPLYDOESNTEXIST');
		}

		/* Does this person have permission to edit this topic? */
		if($topic['poster_id'] == $request['user']->get('id')) {
			if(get_map($request['user'], 'replies', 'can_edit', array('forum_id'=>$forum['forum_id'])) > $request['user']->get('perms')) {
				$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);
				return !USE_AJAX ? $action->execute($request) : ajax_message('L_YOUNEEDPERMS');
			}
		} else {
			if(get_map($request['user'], 'other_replies', 'can_edit', array('forum_id'=>$forum['forum_id'])) > $request['user']->get('perms')) {
				$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);
				return !USE_AJAX ? $action->execute($request) : ajax_message('L_YOUNEEDPERMS');
			}
		}

		/* set the breadcrumbs bit */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_EDITREPLY', $topic, $forum);
				
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
		$poller		= &new K4BBPolls($body_text, $reply['body_text'], $forum, $reply['reply_id']);
		$is_poll	= 0;
		$poll_text	= $poller->parse($request, $is_poll);
		
		$request['template']->setVar('newreply_act', 'newreply.php?act=updatereply');

		if((isset($_REQUEST['submit_type']) && $_REQUEST['submit_type'] == 'post') || isset($_REQUEST['post'])) {
			
			if($body_text != $poll_text) {
				$body_text = $poll_text;
				$is_poll	= 1;
			}

			$posticon	= iif(($request['user']->get('perms') >= get_map($request['user'], 'posticons', 'can_add', array('forum_id'=>$forum['forum_id']))), (isset($_REQUEST['posticon']) ? $_REQUEST['posticon'] : 'clear.gif'), 'clear.gif');
			
			/**
			 * Build the queries to update the reply
			 */
			
			$update_a			= $request['dba']->prepareStatement("UPDATE ". K4REPLIES ." SET name=?,body_text=?,posticon=?,disable_html=?,disable_bbcode=?,disable_emoticons=?,disable_sig=?,disable_areply=?,disable_aurls=?,edited_time=?,edited_username=?,edited_userid=?,is_poll=? WHERE reply_id=?");
			
			$update_a->setString(1, htmlentities(html_entity_decode($_REQUEST['name']), ENT_QUOTES));
			$update_a->setString(2, $body_text);
			$update_a->setString(3, $posticon);
			$update_a->setInt(4, iif((isset($_REQUEST['disable_html']) && $_REQUEST['disable_html']), 1, 0));
			$update_a->setInt(5, iif((isset($_REQUEST['disable_bbcode']) && $_REQUEST['disable_bbcode']), 1, 0));
			$update_a->setInt(6, iif((isset($_REQUEST['disable_emoticons']) && $_REQUEST['disable_emoticons']), 1, 0));
			$update_a->setInt(7, iif((isset($_REQUEST['enable_sig']) && $_REQUEST['enable_sig']), 0, 1));
			$update_a->setInt(8, iif((isset($_REQUEST['disable_areply']) && $_REQUEST['disable_areply']), 1, 0));
			$update_a->setInt(9, iif((isset($_REQUEST['disable_aurls']) && $_REQUEST['disable_aurls']), 1, 0));
			$update_a->setInt(10, time());
			$update_a->setString(11, iif($request['user']->get('id') <= 0, htmlentities(@$_REQUEST['poster_name'], ENT_QUOTES), $request['user']->get('name')));
			$update_a->setInt(12, $request['user']->get('id'));
			$update_a->setInt(13, $is_poll);
			$update_a->setInt(14, $reply['reply_id']);
			
			/**
			 * Do the queries
			 */
			$update_a->executeUpdate();
			
			// deal with attachments
			attach_files($request, $forum, $topic['topic_id'], $reply['reply_id']);

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

			/* Is this reply the forum's last post? */
			if($forum['post_id'] == $reply['reply_id']) {
				$forum_topic_update		= $request['dba']->prepareStatement("UPDATE ". K4FORUMS ." SET post_name=?,post_posticon=? WHERE forum_id=?");
				$forum_topic_update->setString(1, htmlentities(html_entity_decode($_REQUEST['name']), ENT_QUOTES));
				$forum_topic_update->setString(2, $posticon);
				$forum_topic_update->setInt(3, $forum['forum_id']);
				$forum_topic_update->executeUpdate();
			}

			/* Redirect the user */
			$action = new K4InformationAction(new K4LanguageElement('L_UPDATEDREPLY', htmlentities(html_entity_decode($_REQUEST['name']), ENT_QUOTES)), 'content', FALSE, 'findpost.php?id='. $reply['reply_id'], 3);

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
				
				topic_post_options($request['template'], $request['user'], $forum);
				post_attachment_options($request, $forum, $topic, $reply);
				
				/* Create our editor */
				create_editor($request, $_REQUEST['message'], 'post', $forum);
			}

			/* Set the forum info to the template */
			foreach($forum as $key => $val)
				$request['template']->setVar('forum_'. $key, $val);
						
			$reply_preview	= array(
								'reply_id' => $reply['reply_id'],
								'name' => htmlentities(html_entity_decode($_REQUEST['name']), ENT_QUOTES),
								'body_text' => $body_text,
								'poster_name' => $request['user']->get('name'),
								'poster_id' => $request['user']->get('id'),
								'forum_id' => $forum['forum_id'],
								'is_poll' => $reply['is_poll'],
								'topic_id' => $topic['topic_id'],
								'row_left' => 0,
								'row_right' => 0,
								'posticon' => iif(($request['user']->get('perms') >= get_map($request['user'], 'posticons', 'can_add', array('forum_id'=>$forum['forum_id']))), (isset($_REQUEST['posticon']) ? $_REQUEST['posticon'] : 'clear.gif'), 'clear.gif'),
								'disable_html' => iif((isset($_REQUEST['disable_html']) && $_REQUEST['disable_html']), 1, 0),
								'disable_sig' => iif((isset($_REQUEST['enable_sig']) && $_REQUEST['enable_sig']), 0, 1),
								'disable_bbcode' => iif((isset($_REQUEST['disable_bbcode']) && $_REQUEST['disable_bbcode']), 1, 0),
								'disable_emoticons' => iif((isset($_REQUEST['disable_emoticons']) && $_REQUEST['disable_emoticons']), 1, 0),
								'disable_areply' => iif((isset($_REQUEST['disable_areply']) && $_REQUEST['disable_areply']), 1, 0),
								'disable_aurls' => iif((isset($_REQUEST['disable_aurls']) && $_REQUEST['disable_aurls']), 1, 0)
								);

			/* Add the reply information to the template (same as for topics) */
			$reply_iterator = &new TopicIterator($request['dba'], $request['user'], $reply_preview, FALSE);
			$request['template']->setList('topic', $reply_iterator);
			
			/* Assign the topic preview values to the template */
			$reply_preview['body_text'] = $_REQUEST['message'];
			
			foreach($reply_preview as $key => $val)
				$request['template']->setVar('reply_'. $key, $val);
			
			if(!USE_AJAX) {

				/* Set the the button display options */
				$request['template']->setVisibility('edit_reply', TRUE);
				$request['template']->setVisibility('reply_id', TRUE);
				$request['template']->setVisibility('post_reply', FALSE);
				$request['template']->setVisibility('edit_post', TRUE);
				
				/* Get the number of replies to this topic */
				//$num_replies		= @intval(($topic['row_right'] - $topic['row_left'] - 1) / 2);

				/* Get replies that are above this point */
				$query	= "SELECT * FROM ". K4REPLIES ." WHERE topic_id = ". intval($topic['topic_id']) ." AND reply_id < ". intval($reply['reply_id']) ." AND row_type = ". REPLY ." ORDER BY created DESC LIMIT 10";
				$replies	= &$request['dba']->executeQuery($query);
				$it = &new TopicReviewIterator($request['dba'], $topic, $replies, $request['user']->getInfoArray());
				
				$request['template']->setList('topic_review', $it);
				
				/* Set the post topic form */
				$request['template']->setFile('preview', 'post_preview.html');
				$request['template']->setFile('content', 'newreply.html');
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
class DeleteReply extends FAAction {
	function execute(&$request) {
		
		global $_QUERYPARAMS, $_DATASTORE, $_USERGROUPS;
		
		/* set the breadcrumbs bit */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');

		if(!isset($_REQUEST['id']) || !$_REQUEST['id'] || intval($_REQUEST['id']) == 0) {
			$action = new K4InformationAction(new K4LanguageElement('L_REPLYDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}

		/* Get our topic */
		$reply				= $request['dba']->getRow("SELECT * FROM ". K4REPLIES ." WHERE reply_id = ". intval($_REQUEST['id']));
		
		if(!$reply || !is_array($reply) || empty($reply)) {
			$action = new K4InformationAction(new K4LanguageElement('L_REPLYDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}
		
		$topic				= $request['dba']->getRow("SELECT * FROM ". K4TOPICS ." WHERE topic_id = ". intval($reply['topic_id']));
		
		/* Check the forum data given */
		if(!$topic|| !is_array($topic) || empty($topic)) {
			$action = new K4InformationAction(new K4LanguageElement('L_TOPICDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}
			
		$forum				= $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($reply['forum_id']));
		
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
		k4_bread_crumbs($request['template'], $request['dba'], 'L_DELETEREPLY', $topic, $forum);
		
		/* Does this person have permission to remove this topic? */
		if($reply['poster_id'] == $request['user']->get('id')) {
			if(get_map($request['user'], 'replies', 'can_del', array('forum_id'=>$forum['forum_id'])) > $request['user']->get('perms')) {
				no_perms_error($request);
				return TRUE;
			}
		} else {
			if(get_map($request['user'], 'other_replies', 'can_del', array('forum_id'=>$forum['forum_id'])) > $request['user']->get('perms')) {
				no_perms_error($request);
				return TRUE;
			}
		}
		
		$user_usergroups	= $request['user']->get('usergroups') != '' ? explode('|', $request['user']->get('usergroups')) : array();
		$forum_usergroups	= $forum['moderating_groups'] != '' ? explode('|', $forum['moderating_groups']) : array();
		
		if(!is_moderator($request['user']->getInfoArray(), $forum)) {
			no_perms_error($request);
			return TRUE;
		}
				
		//$num_replies		= @intval(($reply['row_right'] - $reply['row_left'] - 1) / 2);
		$num_replies		= $reply['num_replies'];
		
		/* Get that last topic in this forum */
		$last_topic			= $request['dba']->getRow("SELECT * FROM ". K4TOPICS ." WHERE is_draft=0 AND forum_id=". intval($reply['forum_id']) ." ORDER BY created DESC LIMIT 1");
		$last_topic			= !$last_topic || !is_array($last_topic) ? array('created'=>0,'id'=>0,'poster_name'=>'','poster_id'=>0,'reply_id'=>0,'name'=>'','posticon'=>'clear.gif') : array_merge($last_topic, array('id'=>$last_topic['topic_id']));
		
		/* Get that last post in this forum that's not part of/from this topic */
		$last_post			= $request['dba']->getRow("SELECT * FROM ". K4REPLIES ." WHERE reply_id <> ". intval($reply['reply_id']) ." AND forum_id=". intval($reply['forum_id']) ." ORDER BY created DESC LIMIT 1");
		$last_post			= !$last_post || !is_array($last_post) ? $last_topic : array_merge($last_post, array('id'=>$last_post['reply_id']));
		
		/* Should the last post be the last topic? */
		$last_post			= $last_post['created'] < $last_topic['created'] ? $last_topic : $last_post;
		
		/**
		 * Should we update the topic?
		 */
		if($topic['num_replies'] > 0) {
			$topic_last_reply	= $request['dba']->getRow("SELECT * FROM ". K4REPLIES ." WHERE reply_id <> ". intval($reply['reply_id']) ." AND topic_id=". intval($topic['topic_id']) ." ORDER BY created DESC LIMIT 1");
			$topic_update		= &$request['dba']->prepareStatement("UPDATE ". K4TOPICS ." SET reply_time=?,reply_uname=?,reply_uid=?,reply_id=?,num_replies=? WHERE topic_id=?");
			$topic_update->setInt(1, $topic_last_reply['created']);
			$topic_update->setString(2, $topic_last_reply['poster_name']);
			$topic_update->setInt(3, $topic_last_reply['poster_id']);
			$topic_update->setInt(4, $topic_last_reply['reply_id']);
			$topic_update->setInt(5, $request['dba']->getValue("SELECT COUNT(*) FROM ". K4REPLIES ." WHERE topic_id = ". intval($topic['topic_id']))); // use this to make sure we get the right count
			$topic_update->setInt(6, $topic['topic_id']);
			$topic_update->executeUpdate();
		} else {
			$request['dba']->executeUpdate("UPDATE ". K4TOPICS ." SET num_replies=0,reply_time=0,reply_uname='',reply_uid=0,reply_id=0,last_post=". intval($topic['created']) ." WHERE topic_id=". intval($topic['topic_id']));
		}
		
		/* Remove any bad post reports */
		$request['dba']->executeUpdate("DELETE FROM ". K4BADPOSTREPORTS ." WHERE reply_id = ". intval($reply['reply_id']));
		
		/**
		 * Update the forum and the datastore
		 */
		
		$request['dba']->beginTransaction();

		$forum_update		= &$request['dba']->prepareStatement("UPDATE ". K4FORUMS ." SET posts=posts-?,replies=replies-?,post_created=?,post_name=?,post_uname=?,post_id=?,post_uid=?,post_posticon=? WHERE forum_id=?");
		$datastore_update	= &$request['dba']->prepareStatement("UPDATE ". K4DATASTORE ." SET data=? WHERE varname=?");
			
		/* Set the forum values */
		$forum_update->setInt(1, 1);
		$forum_update->setInt(2, 1);
		$forum_update->setInt(3, $last_post['created']);
		$forum_update->setString(4, $last_post['name']);
		$forum_update->setString(5, $last_post['poster_name']);
		$forum_update->setInt(6, $last_post['id']);
		$forum_update->setInt(7, $last_post['poster_id']);
		$forum_update->setString(8, $last_post['posticon']);
		$forum_update->setInt(9, $forum['forum_id']);
		
		/* Set the datastore values */
		$datastore					= $_DATASTORE['forumstats'];
		$datastore['num_replies']	= $request['dba']->getValue("SELECT COUNT(*) FROM ". K4REPLIES) - 1;
		
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
		
		// delete this replies attachments
		remove_attachments($request, $topic['topic_id'], $reply['reply_id']);
		$request['dba']->executeUpdate("DELETE FROM ". K4ATTACHMENTS ." WHERE topic_id = ". intval($topic['topic_id']) ." AND reply_id = ". intval($reply['reply_id']));

		/* Now remove the information stored in the topics and replies table */
		$request['dba']->executeUpdate("DELETE FROM ". K4REPLIES ." WHERE reply_id = ". intval($reply['reply_id']));
		
		$request['dba']->commitTransaction();

		reset_cache(CACHE_DS_FILE);
		
		/* Redirect the user */
		$action = new K4InformationAction(new K4LanguageElement('L_DELETEDREPLY', $reply['name'], $topic['name']), 'content', FALSE, 'viewtopic.php?id='. $topic['topic_id'], 3);
		return $action->execute($request);
	}
}

class ThreadedRepliesIterator extends FAProxyIterator {
	
	var $result, $start_level;

	function ThreadedRepliesIterator(&$result, $topic_level) {
		$this->__construct($result, $topic_level);
	}

	function __construct(&$result, $topic_level) {
		
		$this->result		= &$result;
		$this->start_level	= intval($topic_level);

		parent::__construct($this->result);
	}

	function &current() {
		$temp					= parent::current();

		$temp['inset_level']	= str_repeat('&nbsp; &nbsp; &nbsp;', intval($temp['row_level'] - $this->start_level - 1));
		
		/* Should we free the result? */
		if(!$this->hasNext())
			$this->result->free();
		
		return $temp;
	}
}

class RepliesIterator extends FAProxyIterator {
	
	var $user, $dba, $result, $qp, $users, $groups, $fields;

	function RepliesIterator(&$user, &$dba, &$result, $queryparams, $users, $groups, $fields) {
		$this->__construct($user, $dba, $result, $queryparams, $users, $groups, $fields);
	}

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
				
				if(is_array($user) && !empty($user)) {
					$group						= get_user_max_group($user, $this->groups);
					$user['group_color']		= !isset($group['color']) || $group['color'] == '' ? '000000' : $group['color'];
					$user['group_nicename']		= $group['nicename'];
					$user['group_avatar']		= $group['avatar'];
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

				$temp['profilefields']	= &new FAArrayIterator(get_profile_fields($this->fields, $temp));
			}

			if(!isset($temp['post_user_online']))
				$temp['post_user_online'] = 'offline';

		} else {
			$temp['post_user_id']	= 0;
			$temp['post_user_name']	= $temp['poster_name'];
		}

		/* do we have any attachments? */
		if(isset($temp['attachments']) && $temp['attachments'] > 0) {
			$temp['attachment_files']		= &new K4AttachmentsIterator($this->dba, $this->user, $temp['topic_id'], $temp['reply_id']);
		}
		
		/* Deal with acronyms */
		replace_acronyms($temp['body_text']);
		
		/* word censors!! */
		replace_censors($temp['body_text']);
		replace_censors($temp['name']);

		/* Do any polls if they exist */
		do_post_polls($temp, $this->dba);

		/* Should we free the result? */
		if(!$this->hasNext())
			$this->result->free();
		
		return $temp;
	}
}

?>