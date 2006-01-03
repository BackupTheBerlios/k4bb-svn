<?php
/**
* k4 Bulletin Board, k4_template.php
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
* @version $Id$
* @package k42
*/

if(!defined('IN_K4')) {
	return;
}

/**
 * Post / Preview a topic
 */
class InsertPost extends FAAction {
	var $row_type;
	function InsertPost($row_type) {
		$this->row_type = $row_type;
	}
	function execute(&$request) {
		
		if((isset($_REQUEST['submit_type']) && ($_REQUEST['submit_type'] == 'post' || $_REQUEST['submit_type'] == 'preview' || $_REQUEST['submit_type'] == 'draft')) || ( isset($_REQUEST['post']) || isset($_REQUEST['draft']) ) ) {
			
			$submit_type = $_REQUEST['submit_type'];
			$should_submit = ( isset($_REQUEST['post']) || isset($_REQUEST['draft']) );

			global $_QUERYPARAMS, $_DATASTORE, $_SETTINGS;

			$this->dba			= $request['dba'];

			/* Prevent post flooding */
			$last_topic		= $request['dba']->getRow("SELECT * FROM ". K4POSTS ." WHERE poster_ip = '". USER_IP ."' ". ($request['user']->isMember() ? "OR poster_id = ". intval($request['user']->get('id')) : '') ." ORDER BY created DESC LIMIT 1");
			$last_reply		= $request['dba']->getRow("SELECT * FROM ". K4POSTS ." WHERE poster_ip = '". USER_IP ."' ". ($request['user']->isMember() ? "OR poster_id = ". intval($request['user']->get('id')) : '') ." ORDER BY created DESC LIMIT 1");
			
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
			if(!isset($_REQUEST['forum_id']) || intval($_REQUEST['forum_id']) == 0) {
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
			if($request['user']->get('perms') < get_map( 'topics', 'can_add', array('forum_id'=>$forum['forum_id']))) {
				$action = new K4InformationAction(new K4LanguageElement('L_PERMCANTPOST'), 'content', FALSE);
				return !USE_AJAX ? $action->execute($request) : ajax_message('L_PERMCANTPOST');
			}
			
			/* General error checking */
			if($this->row_type & TOPIC) {
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
			}
			if(!isset($_REQUEST['message']) || $_REQUEST['message'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTTOPICMESSAGE'), 'content', TRUE);
				return !USE_AJAX ? $action->execute($request) : ajax_message('L_INSERTTOPICMESSAGE');
			}				
			
			if($submit_type == 'post' || $submit_type == 'draft' || $should_submit) {

				/* set the breadcrumbs bit */
				k4_bread_crumbs($request['template'], $request['dba'], ($this->row_type & TOPIC ? 'L_POSTTOPIC' : 'L_POSTREPLY'), $forum);
				
				/**
				 * Start building info for the queries
				 */

				/* Set this nodes level */
				$level = 1;
				if($this->row_type & TOPIC) {
					$row_order	= 0;
					$parent_id	= $forum['forum_id'];
					$name		= k4_htmlentities($_REQUEST['name'], ENT_QUOTES);
				} else {
					$topic		= $request['dba']->getRow("SELECT * FROM ". K4POSTS ." WHERE post_id=". intval(@$_REQUEST['topic_id']));
						
					if(!$topic || !is_array($topic) || empty($topic)) {
						$action = new K4InformationAction(new K4LanguageElement('L_TOPICDOESNTEXIST'), 'content', FALSE);
						return !USE_AJAX ? $action->execute($request) : ajax_message('L_TOPICDOESNTEXIST');
					}
					
					$parent_id	= $topic['post_id'];
					$level		= $topic['row_level'] + 1;

					if(isset($_REQUEST['parent_id']) && intval($_REQUEST['parent_id']) != 0) {
						$parent		= $request['dba']->getRow("SELECT * FROM ". K4POSTS ." WHERE post_id = ". intval($_REQUEST['parent_id']));
						
						if(!$parent || !is_array($parent) || empty($parent)) {
							$action = new K4InformationAction(new K4LanguageElement('L_POSTDOESNTEXIST'), 'content', FALSE);
							return !USE_AJAX ? $action->execute($request) : ajax_message('L_POSTDOESNTEXIST');
						}

						$level = $parent['row_level'] + 1;
					}

					if(!isset($_REQUEST['name']) || $_REQUEST['name'] == '') {
						$name = 'Re: '. k4_htmlentities( (isset($parent) ? $parent['name'] : $topic['name']) );
					}

					$fix_order = FALSE;
					if($topic['num_replies'] == 0) {
						$row_order = 1;
					} else {
						if($parent['row_type'] & TOPIC) {
							$row_order = $topic['num_replies'] + 1;
						} else {
							$row_order = $parent['row_order'] + 1;
							$fix_order = $parent['post_id'] == $topic['post_id'] ? FALSE : TRUE;
						}
					}
					
					// fix the order of things below this reply
					if($fix_order) {
						$request['dba']->executeUpdate("UPDATE ". K4POSTS ." SET row_order=row_order+1 WHERE row_order >= ". intval($row_order) ." AND parent_id=". intval($topic['post_id']));
					}
				}
				
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
				
				if($this->row_type & TOPIC) {		
					/**
					 * Figure out what type of topic type this is
					 */
					$post_type			= isset($_REQUEST['post_type']) && intval($_REQUEST['post_type']) != 0 ? $_REQUEST['post_type'] : TOPIC_NORMAL;

					if($post_type == TOPIC_STICKY && $request['user']->get('perms') < get_map( 'sticky', 'can_add', array('forum_id'=>$forum['forum_id']))) {
						$post_type		= TOPIC_NORMAL;
					} else if($post_type == TOPIC_ANNOUNCE && $request['user']->get('perms') < get_map( 'announce', 'can_add', array('forum_id'=>$forum['forum_id']))) {
						$post_type		= TOPIC_NORMAL;
					}
					
					$is_feature			= isset($_REQUEST['is_feature']) && $_REQUEST['is_feature'] ? 1 : 0;
					
					if($is_feature == 1 && $request['user']->get('perms') < get_map( 'feature', 'can_add', array('forum_id'=>$forum['forum_id']))) {
						$is_feature		= 0;
					}
				} else {
					$post_type = $is_feature = 0;
				}
				
				/* Does this person have permission to post a draft? */
				$is_draft = 0;
				if($this->row_type & TOPIC && $_REQUEST['submit_type'] == 'draft' || isset($_REQUEST['draft'])) {
					if($request['user']->get('perms') < get_map( 'post_save', 'can_add', array('forum_id'=>$forum['forum_id']))) {
						$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);
						
						return $action->execute($request);
					}
					$is_draft = 1;
				}

				/**
				 * Build the queries
				 */
				
				$poster_name		= ($request['user']->get('id') <= 0 ? k4_htmlentities((isset($_REQUEST['poster_name']) ? $_REQUEST['poster_name'] : '') , ENT_QUOTES) : $request['user']->get('name'));
				
				$is_poll	= 0;
				if($submit_type == 'post' || isset($_REQUEST['post'])) {
					
					// put it here to avoid previewing
					$poll_text		= $poller->parse($request, $is_poll);
									
					if($body_text != $poll_text) {
						$body_text	= $poll_text;
						$is_poll	= 1;
					}
				}

				/* Make sure we're not double-posting */
				if(!empty($last_topic) && (($_REQUEST['name'] == $last_topic['name']) && ($body_text == $last_topic['body_text']))) {
					$action = new K4InformationAction(new K4LanguageElement('L_DOUBLEPOSTED'), 'content', TRUE, 'viewtopic.php?id='. $last_topic['post_id'], 3);
					return !USE_AJAX ? $action->execute($request) : ajax_message('L_DOUBLEPOSTED');
				}
				
				$request['dba']->beginTransaction();

				//post_id,forum_id,poster_name,poster_id,body_text,posticon
				//disable_html,disable_bbcode,disable_emoticons,disable_sig,disable_areply,disable_aurls,is_draft,is_poll
				$insert_a = $request['dba']->prepareStatement("INSERT INTO ". K4POSTS ." (name,forum_id,poster_name,poster_id,poster_ip,body_text,posticon,disable_html,disable_bbcode,disable_emoticons,disable_sig,disable_areply,disable_aurls,is_draft,post_type,post_expire,is_feature,is_poll,lastpost_created,row_type,row_level,created,row_order,parent_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
				$insert_a->setString(1, $name);
				$insert_a->setInt(2, $forum['forum_id']);
				$insert_a->setString(3, $poster_name);
				$insert_a->setInt(4, $request['user']->get('id'));
				$insert_a->setString(5, USER_IP);
				$insert_a->setString(6, $body_text);
				$insert_a->setString(7, (($request['user']->get('perms') >= get_map( 'posticons', 'can_add', array('forum_id'=>$forum['forum_id']))) ? (isset($_REQUEST['posticon']) ? $_REQUEST['posticon'] : 'clear.gif') : 'clear.gif'));
				$insert_a->setInt(8, ((isset($_REQUEST['disable_html']) && $_REQUEST['disable_html']) ? 1 : 0));
				$insert_a->setInt(9, ((isset($_REQUEST['disable_bbcode']) && $_REQUEST['disable_bbcode']) ? 1 : 0));
				$insert_a->setInt(10, ((isset($_REQUEST['disable_emoticons']) && $_REQUEST['disable_emoticons']) ? 1 : 0));
				$insert_a->setInt(11, ((isset($_REQUEST['enable_sig']) && $_REQUEST['enable_sig']) ? 0 : 1));
				$insert_a->setInt(12, ((isset($_REQUEST['disable_areply']) && $_REQUEST['disable_areply']) ? 1 : 0));
				$insert_a->setInt(13, ((isset($_REQUEST['disable_aurls']) && $_REQUEST['disable_aurls']) ? 1 : 0));
				$insert_a->setInt(14, $is_draft);
				$insert_a->setInt(15, $post_type);
				$insert_a->setInt(16, ($post_type > TOPIC_NORMAL ? intval((isset($_REQUEST['post_expire']) ? $_REQUEST['post_expire'] : 0)) : 0) );
				$insert_a->setInt(17, $is_feature);
				$insert_a->setInt(18, $is_poll);
				$insert_a->setInt(19, $created);
				$insert_a->setInt(20, $this->row_type);
				$insert_a->setInt(21, $level);
				$insert_a->setInt(22, $created);
				$insert_a->setInt(23, $row_order);
				$insert_a->setInt(24, $parent_id);

				$insert_a->executeUpdate();
				
				$post_id			= $request['dba']->getInsertId(K4POSTS, 'post_id');

				/** 
				 * Update the forum, and update the datastore 
				 */

				//topic_name,topic_uname,post_id,post_created,post_name,post_uname,post_id,post_uid
				$where				= "WHERE forum_id=?";
				$forum_update		= $request['dba']->prepareStatement("UPDATE ". K4FORUMS ." SET ". ($this->row_type & TOPIC ? 'topics=topics+1,' : '') ." posts=posts+1,post_created=?,post_name=?,post_uname=?,post_id=?,post_uid=?,post_posticon=? $where");
				$datastore_update	= $request['dba']->prepareStatement("UPDATE ". K4DATASTORE ." SET data=? WHERE varname=?");
				
				/* If this isn't a draft, update the forums and datastore tables */
				if($is_draft == 0) {
					
					/* Set the forum values */
					$forum_update->setInt(1, $created);
					$forum_update->setString(2, $name);
					$forum_update->setString(3, $poster_name);
					$forum_update->setInt(4, $post_id);
					$forum_update->setInt(5, $request['user']->get('id'));
					$forum_update->setString(6, (($request['user']->get('perms') >= get_map( 'posticons', 'can_add', array('forum_id'=>$forum['forum_id']))) ? (isset($_REQUEST['posticon']) ? $_REQUEST['posticon'] : 'clear.gif') : 'clear.gif'));
					$forum_update->setInt(7, $forum['forum_id']);
					
					/**
					 * Update the forums table and datastore table
					 */
					$forum_update->executeUpdate();
				}
				
				// deal with attachments
				$t_post = array('post_id'=>$post_id,'parent_id'=>$parent_id,'row_type'=>$this->row_type);
				if($request['template']->getVar('nojs') == 0) {
					attach_files($request, $forum, $t_post);
				}
				attach_limbo_files($request, $forum, $t_post);

				/* Added the topic */
				if($is_draft == 0) {
					
					/* Set the datastore values */
					$datastore					= $_DATASTORE['forumstats'];
					
					// do we change num topics or replies ?
					if($this->row_type & TOPIC) {
						$datastore['num_topics']	= $request['dba']->getValue("SELECT COUNT(*) FROM ". K4POSTS ." WHERE row_type=". TOPIC ." AND is_draft=0");
					} else {
						$datastore['num_replies']	= $request['dba']->getValue("SELECT COUNT(*) FROM ". K4POSTS ." WHERE row_type=". REPLY ." AND is_draft=0");
					}
					
					$datastore_update->setString(1, serialize($datastore));
					$datastore_update->setString(2, 'forumstats');
					$datastore_update->executeUpdate();
					
					if($this->row_type & REPLY) {
						$request['dba']->executeUpdate("UPDATE ". K4POSTS ." SET num_replies=num_replies+1 WHERE post_id=". $parent_id);
					}

					/* Update the user post count */
					$request['dba']->executeUpdate("UPDATE ". K4USERINFO ." SET num_posts=num_posts+1,total_posts=total_posts+1 WHERE user_id=". intval($request['user']->get('id')));

					reset_cache('datastore');
					
					/**
					 * Subscribe this user to the topic
					 */
					if(isset($_REQUEST['disable_areply']) && $_REQUEST['disable_areply']) {
						$subscribe			= $request['dba']->prepareStatement("INSERT INTO ". K4SUBSCRIPTIONS ." (user_id,post_id,forum_id,email) VALUES (?,?,?,?)");
						$subscribe->setInt(1, $request['user']->get('id'));
						$subscribe->setInt(2, $post_id);
						$subscribe->setInt(3, $forum['forum_id']);
						$subscribe->setString(4, $request['user']->get('email'));
						$subscribe->executeUpdate();
					}
					
					set_send_topic_mail($forum['forum_id'], ($poster_name == '' ? $request['template']->getVar('L_GUEST') : $poster_name));
					
					/* Commit the current transaction */
					$request['dba']->commitTransaction();
					

					if(!USE_AJAX || $this->row_type & TOPIC) {
						/* Redirect the user */
						$action = new K4InformationAction(new K4LanguageElement(($this->row_type & TOPIC ? 'L_ADDEDTOPIC' : 'L_ADDEDREPLY'), k4_htmlentities($_REQUEST['name'], ENT_QUOTES), $forum['name']), 'content', FALSE, 'findpost.php?id='. $post_id, 3);
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
							
							global $_USERGROUPS, $_PROFILEFIELDS;

							$result	= $request['dba']->executeQuery("SELECT * FROM ". K4POSTS ." WHERE post_id=". intval($post_id) ." LIMIT 1");
							$it		= &new PostsIterator($request, $result);
							$reply	= $it->next();
							
							$reply['topic_row'] = 1;
							$reply['reply_row'] = 1;
							$reply['row_class'] = 'alt1';
							
							foreach($topic as $key => $val)
								$reply['topic_'. $key]	= $val;

							foreach($reply as $key => $val)
								$request['template']->setVar($key, $val);

							$request['template']->setVisibility('in_topicview', FALSE);
							$request['template']->setVar('row_class', ($topic['num_replies'] % 2 == 0 ? 1 : 2));
							
							$templateset = $request['user']->isMember() ? $request['user']->get('templateset') : $forum['defaultstyle'];

							$html	= $request['template']->run(BB_BASE_DIR .'/templates/'. $templateset .'/reply'. ($request['user']->get('topic_display') == 0 ? '' : '_linear') .'.html');

							echo $html;
							exit;
						}
					}
				
				} else {
					
					/* Commit the current transaction */
					$request['dba']->commitTransaction();

					/* Redirect the user */
					$action = new K4InformationAction(new K4LanguageElement('L_SAVEDDRAFTTOPIC', $name, $forum['name']), 'content', FALSE, 'viewforum.php?f='. $forum['forum_id'], 3);

					return $action->execute($request);
				}
			} else {
				
				/**
				 * Post Previewing
				 */
				
				if(!USE_AJAX) {
					$request['template']->setVar('L_TITLETOOSHORT', sprintf($request['template']->getVar('L_TITLETOOSHORT'), $request['template']->getVar('topicminchars'), $request['template']->getVar('topicmaxchars')));

					/* Get and set the emoticons and post icons to the template */
					$emoticons	= $request['dba']->executeQuery("SELECT * FROM ". K4EMOTICONS ." WHERE clickable = 1");
					$posticons	= $request['dba']->executeQuery("SELECT * FROM ". K4POSTICONS);
					
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
				$post_preview	= array(
									'name' => k4_htmlentities(html_entity_decode($_REQUEST['name']), ENT_QUOTES),
									'body_text' => $body_text,
									'poster_name' => $request['user']->get('name'),
									'poster_id' => $request['user']->get('id'),
									'is_poll' => 0,
									'row_left' => 0,
									'row_right' => 0,
									'post_type' => $post_type,
									'is_feature' => $is_feature,
									'posticon' => (($request['user']->get('perms') >= get_map( 'posticons', 'can_add', array('forum_id'=>$forum['forum_id']))) ? (isset($_REQUEST['posticon']) ? $_REQUEST['posticon'] : 'clear.gif') : 'clear.gif'),
									'disable_html' => ((isset($_REQUEST['disable_html']) && $_REQUEST['disable_html']) ? 1 : 0),
									'disable_sig' => ((isset($_REQUEST['enable_sig']) && $_REQUEST['enable_sig']) ? 0 : 1),
									'disable_bbcode' => ((isset($_REQUEST['disable_bbcode']) && $_REQUEST['disable_bbcode']) ? 1 : 0),
									'disable_emoticons' => ((isset($_REQUEST['disable_emoticons']) && $_REQUEST['disable_emoticons']) ? 1 : 0),
									'disable_areply' => ((isset($_REQUEST['disable_areply']) && $_REQUEST['disable_areply']) ? 1 : 0),
									'disable_aurls' => ((isset($_REQUEST['disable_aurls']) && $_REQUEST['disable_aurls']) ? 1 : 0)
									);
								
				/* Assign the topic preview values to the template */
				$post_preview['body_text'] = $_REQUEST['message'];
				
				foreach($post_preview as $key => $val)
					$request['template']->setVar('post_'. $key, $val);
				
				/* Assign the forum information to the template */
				foreach($forum as $key => $val)
					$request['template']->setVar('forum_'. $key, $val);
				
				$request['template']->setVar('is_topic', ($this->row_type & TOPIC ? 1 : 0));

				if(!USE_AJAX) {

					/* Set the the button display options */
					$request['template']->setVisibility('save_draft', TRUE);
					$request['template']->setVisibility('edit_topic', TRUE);
					$request['template']->setVisibility('post_topic', TRUE);
					$request['template']->setVisibility('post_id', TRUE);
					
					/* Should she show/hide the 'load draft' button? */
					$drafts		= $request['dba']->executeQuery("SELECT * FROM ". K4POSTS ." WHERE forum_id=". intval($forum['forum_id']) ." AND is_draft=1 AND poster_id=". intval($request['user']->get('id')));
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
		}
		return TRUE;
	}
}

/**
 * Update a post
 */
class UpdatePost extends FAAction {
	var $row_type;
	function UpdatePost($row_type) {
		$this->row_type = $row_type;
	}
	function execute(&$request) {
		
		global $_QUERYPARAMS, $_DATASTORE, $_SETTINGS;
		
		/* set the breadcrumbs bit */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');

		/* Check the request ID */
		if(!isset($_REQUEST['forum_id']) || !$_REQUEST['forum_id'] || intval($_REQUEST['forum_id']) == 0) {
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);
			return !USE_AJAX ? $action->execute($request) : ajax_message('L_FORUMDOESNTEXIST');
		}
			
		$forum				= $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id=". intval($_REQUEST['forum_id']));
		
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
		if($this->row_type & TOPIC) {
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
		}
		if(!isset($_REQUEST['message']) || $_REQUEST['message'] == '') {
			$action = new K4InformationAction(new K4LanguageElement('L_INSERTTOPICMESSAGE'), 'content', TRUE);
			return !USE_AJAX ? $action->execute($request) : ajax_message('L_INSERTTOPICMESSAGE');
		}
		
		/* Get our post */
		$post				= $request['dba']->getRow("SELECT * FROM ". K4POSTS ." WHERE post_id = ". intval($_REQUEST['post_id']));
		
		if(!is_array($post) || empty($post)) {
			$action = new K4InformationAction(new K4LanguageElement('L_POSTDOESNTEXIST'), 'content', FALSE);
			return !USE_AJAX ? $action->execute($request) : ajax_message('L_POSTDOESNTEXIST');
		}

		$type = $this->row_type & TOPIC ? 'topics' : 'replies';

		/* Does this person have permission to edit this topic? */
		if($post['poster_id'] == $request['user']->get('id')) {
			if(get_map( $type, 'can_edit', array('forum_id'=>$forum['forum_id'])) > $request['user']->get('perms')) {
				$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);
				return !USE_AJAX ? $action->execute($request) : ajax_message('L_YOUNEEDPERMS');
			}
		} else {
			if(get_map( 'other_'. $type, 'can_edit', array('forum_id'=>$forum['forum_id'])) > $request['user']->get('perms')) {
				$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);
				return !USE_AJAX ? $action->execute($request) : ajax_message('L_YOUNEEDPERMS');
			}
		}

		if($post['is_poll'] == 1) {
			// TODO: something here.
		}
		
		/* Does this user have permission to edit this topic if it is locked? */
		if($topic['post_locked'] == 1 && get_map( 'closed', 'can_edit', array('forum_id' => $forum['forum_id'])) > $request['user']->get('perms')) {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);
			return !USE_AJAX ? $action->execute($request) : ajax_message('L_YOUNEEDPERMS');
		}

		/* set the breadcrumbs bit */
		k4_bread_crumbs($request['template'], $request['dba'], ($this->row_type & TOPIC ? 'L_EDITTOPIC' : 'L_EDITREPLY'), $topic, $forum);
				
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
		$poller		= &new K4BBPolls($body_text, $topic['body_text'], $forum, $topic['post_id']);
				
		$request['template']->setVar('newtopic_action', 'newtopic.php?act=updatetopic');
		
		if($this->row_type & TOPIC) {
			/* Get the topic type */
			$post_type			= isset($_REQUEST['post_type']) && intval($_REQUEST['post_type']) != 0 ? $_REQUEST['post_type'] : TOPIC_NORMAL;
			
			/* Check the topic type and check if this user has permission to post that type of topic */
			if($post_type == TOPIC_STICKY && $request['user']->get('perms') < get_map( 'sticky', 'can_add', array('forum_id'=>$forum['forum_id']))) {
				$post_type		= TOPIC_NORMAL;
			} else if($post_type == TOPIC_ANNOUNCE && $request['user']->get('perms') < get_map( 'announce', 'can_add', array('forum_id'=>$forum['forum_id']))) {
				$post_type		= TOPIC_NORMAL;
			}
		
			/* Is this a featured topic? */
			$is_feature			= isset($_REQUEST['is_feature']) && $_REQUEST['is_feature'] == 'yes' ? 1 : 0;
			if($is_feature == 1 && $request['user']->get('perms') < get_map( 'feature', 'can_add', array('forum_id'=>$forum['forum_id']))) {
				$is_feature		= 0;
			}
		} else {
			$post_type = TOPIC_NORMAL;
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

			$posticon			= iif(($request['user']->get('perms') >= get_map( 'posticons', 'can_add', array('forum_id'=>$forum['forum_id']))), (isset($_REQUEST['posticon']) ? $_REQUEST['posticon'] : 'clear.gif'), 'clear.gif');
			
			$time				= time();
			
			$name				= k4_htmlentities($_REQUEST['name'], ENT_QUOTES);

			/**
			 * Build the queries to update the topic
			 */
			
			$update_a			= $request['dba']->prepareStatement("UPDATE ". K4POSTS ." SET name=?,body_text=?,posticon=?,disable_html=?,disable_bbcode=?,disable_emoticons=?,disable_sig=?,disable_areply=?,disable_aurls=?,is_draft=?,edited_time=?,edited_username=?,edited_userid=?,is_feature=?,post_type=?,post_expire=?,is_poll=? WHERE post_id=?");
			
			$update_a->setString(1, $name);
			$update_a->setString(2, $body_text);
			$update_a->setString(3, $posticon);
			$update_a->setInt(4, ((isset($_REQUEST['disable_html']) && $_REQUEST['disable_html']) ? 1 : 0));
			$update_a->setInt(5, ((isset($_REQUEST['disable_bbcode']) && $_REQUEST['disable_bbcode']) ? 1 : 0));
			$update_a->setInt(6, ((isset($_REQUEST['disable_emoticons']) && $_REQUEST['disable_emoticons']) ? 1 : 0));
			$update_a->setInt(7, ((isset($_REQUEST['enable_sig']) && $_REQUEST['enable_sig']) ? 0 : 1));
			$update_a->setInt(8, ((isset($_REQUEST['disable_areply']) && $_REQUEST['disable_areply']) ? 1 : 0));
			$update_a->setInt(9, ((isset($_REQUEST['disable_aurls']) && $_REQUEST['disable_aurls']) ? 1 : 0));
			$update_a->setInt(10, 0);
			$update_a->setInt(11, $time);
			$update_a->setString(12, ($request['user']->get('id') <= 0 ? k4_htmlentities((isset($_REQUEST['poster_name']) ? $_REQUEST['poster_name'] : '') , ENT_QUOTES) : $request['user']->get('name')));
			$update_a->setInt(13, $request['user']->get('id'));
			$update_a->setInt(14, $is_feature);
			$update_a->setInt(15, $post_type);
			$update_a->setInt(16, ($post_type > TOPIC_NORMAL ? intval((isset($_REQUEST['post_expire']) ? $_REQUEST['post_expire'] : 0)) : 0) );
			$update_a->setInt(17, $is_poll);
			$update_a->setInt(18, $post['post_id']);
			
			$update_a->executeUpdate();
			
			/* If this topic is a redirect/ connects to one, update the original */
			if($this->row_type & TOPIC && ($post['moved_new_post_id'] > 0 || $post['moved_old_post_id'] > 0)) {
				$redirect		= $request['dba']->prepareStatement("UPDATE ". K4POSTS ." SET name=?,edited_time=?,edited_username=?,edited_userid=? WHERE post_id=?");
			
				$redirect->setString(1, $name);
				$redirect->setInt(2, time());
				$redirect->setString(3, $request['user']->get('name'));
				$redirect->setInt(4, $request['user']->get('id'));
				$redirect->setInt(5, ($post['moved_new_post_id'] > 0 ? $post['moved_new_post_id'] : $post['moved_old_post_id']));
				$redirect->executeUpdate();

				/**
				 * Subscribe/Unsubscribe this user to the topic
				 */
				$is_subscribed		= $request['dba']->getRow("SELECT * FROM ". K4SUBSCRIPTIONS ." WHERE user_id = ". intval($request['user']->get('id')) ." AND post_id = ". intval($post['post_id']));
				if(isset($_REQUEST['disable_areply']) && $_REQUEST['disable_areply']) {
					if(!is_array($is_subscribed) || empty($is_subscribed)) {
						$subscribe			= $request['dba']->prepareStatement("INSERT INTO ". K4SUBSCRIPTIONS ." (user_id,post_id,forum_id,email) VALUES (?,?,?,?)");
						$subscribe->setInt(1, $request['user']->get('id'));
						$subscribe->setInt(2, $topic['post_id']);
						$subscribe->setInt(3, $forum['forum_id']);
						$subscribe->setString(4, $request['user']->get('email'));
						$subscribe->executeUpdate();
					}
				} else if(!isset($_REQUEST['disable_areply']) || !$_REQUEST['disable_areply']) {
					if(is_array($is_subscribed) && !empty($is_subscribed)) {
						$subscribe			= $request['dba']->prepareStatement("DELETE FROM ". K4SUBSCRIPTIONS ." WHERE user_id=? AND post_id=?");
						$subscribe->setInt(1, $request['user']->get('id'));
						$subscribe->setInt(2, $topic['post_id']);
						$subscribe->executeUpdate();
					}
				}
			}

			// deal with attachments
			if($request['template']->getVar('nojs') == 0) {
				attach_files($request, $forum, $post);
			}

			/* Should we update the forum's last post info? */
			if($forum['lastpost_id'] == $post['post_id']) {
				
				// if this topic is the forums last post
				if($forum['lastpost_id'] == $post['post_id'] && $forum['lastpost_created'] == $post['created']) {
					$forum_topic_update		= $request['dba']->prepareStatement("UPDATE ". K4FORUMS ." SET lastpost_name=?,lastpost_posticon=? WHERE forum_id=?");
					$forum_topic_update->setString(1, $name);
					$forum_topic_update->setString(2, $posticon);
					$forum_topic_update->setInt(3, $forum['forum_id']);
					$forum_topic_update->executeUpdate();
				}
			}

			/* Redirect the user */
			$action = new K4InformationAction(new K4LanguageElement(($this->row_type & TOPIC ? 'L_UPDATEDTOPIC' : 'L_UPDATEDREPLY'), $name), 'content', FALSE, 'findpost.php?id='. $post['post_id'], 3);
			return $action->execute($request);
		
		} else {
			
			/**
			 * Post Previewing
			 */
			
			if(!USE_AJAX) {
				$request['template']->setVar('L_TITLETOOSHORT', sprintf($request['template']->getVar('L_TITLETOOSHORT'), $request['template']->getVar('topicminchars'), $request['template']->getVar('topicmaxchars')));

				/* Get and set the emoticons and post icons to the template */
				$emoticons	= $request['dba']->executeQuery("SELECT * FROM ". K4EMOTICONS ." WHERE clickable = 1");
				$posticons	= $request['dba']->executeQuery("SELECT * FROM ". K4POSTICONS);

				$request['template']->setList('emoticons', $emoticons);
				$request['template']->setList('posticons', $posticons);

				$request['template']->setVar('emoticons_per_row', $request['template']->getVar('smcolumns'));
				$request['template']->setVar('emoticons_per_row_remainder', $request['template']->getVar('smcolumns')-1);
				
				post_attachment_options($request, $forum, $post);
				topic_post_options($request['template'], $request['user'], $forum);

				/* Create our editor */
				create_editor($request, $_REQUEST['message'], 'post', $forum);
			}
			
			$topic_preview	= array(
								'post_id' => @$post['post_id'],
								'name' => $name,
								'posticon' => (isset($_REQUEST['posticon']) ? $_REQUEST['posticon'] : 'clear.gif'),
								'body_text' => $body_text,
								'poster_name' => html_entity_decode($topic['poster_name'], ENT_QUOTES),
								'poster_id' => $request['user']->get('id'),
								'is_poll' => $topic['is_poll'],
								'row_left' => 0,
								'row_right' => 0,
								'post_type' => $post_type,
								'is_feature' => $is_feature,
								'disable_html' => ((isset($_REQUEST['disable_html']) && $_REQUEST['disable_html']) ? 1 : 0),
								'disable_sig' => ((isset($_REQUEST['enable_sig']) && $_REQUEST['enable_sig']) ? 1 : 0),
								'disable_bbcode' => ((isset($_REQUEST['disable_bbcode']) && $_REQUEST['disable_bbcode']) ? 1 : 0),
								'disable_emoticons' => ((isset($_REQUEST['disable_emoticons']) && $_REQUEST['disable_emoticons']) ? 1 : 0),
								'disable_areply' => ((isset($_REQUEST['disable_areply']) && $_REQUEST['disable_areply']) ? 1 : 0),
								'disable_aurls' => ((isset($_REQUEST['disable_aurls']) && $_REQUEST['disable_aurls']) ? 1 : 0),
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
				$request['template']->setVisibility('post_id', TRUE);
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
 * Delete a post
 */
class DeletePost extends FAAction {
	var $row_type;
	function DeletePost($row_type) {
		$this->row_type = $row_type;
	}
	function execute(&$request) {
		
		global $_QUERYPARAMS, $_DATASTORE, $_USERGROUPS;
		
		/* set the breadcrumbs bit */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');

		if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
			$action = new K4InformationAction(new K4LanguageElement('L_POSTDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}

		/* Get our topic */
		$post		= $request['dba']->getRow("SELECT * FROM ". K4POSTS ." WHERE post_id = ". intval($_REQUEST['id']));
		
		if(!$post || !is_array($post) || empty($post)) {
			$action = new K4InformationAction(new K4LanguageElement('L_POSTDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}
					
		$forum		= $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($post['forum_id']));

		/* Check the forum data given */
		if(!$forum || !is_array($forum) || empty($forum)) {
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}
		
		$delete_topic = FALSE;
		if($forum['forum_id'] == GARBAGE_BIN && $this->row_type & TOPIC) {
			$delete_topic = TRUE;
		}
			
		/* Make sure the we are trying to delete from a forum */
		if(!($forum['row_type'] & FORUM)) {
			$action = new K4InformationAction(new K4LanguageElement('L_CANTDELFROMNONFORUM'), 'content', FALSE);
			return $action->execute($request);
		}
		
		/* set the breadcrumbs bit */
		k4_bread_crumbs($request['template'], $request['dba'], ($this->row_type & REPLY ? 'L_DELETEREPLY' : 'L_DELETETOPIC'), $post, $forum);
		
		$maps_var = $this->row_type & TOPIC ? 'topics' : 'replies';
		
		/* Does this person have permission to remove this post? */
		if($post['poster_id'] == $request['user']->get('id')) {
			if(get_map( $maps_var, 'can_del', array('forum_id'=>$forum['forum_id'])) > $request['user']->get('perms')) {
				no_perms_error($request);
				return TRUE;
			}
		} else {
			if(get_map( 'other_'. $maps_var, 'can_del', array('forum_id'=>$forum['forum_id'])) > $request['user']->get('perms')) {
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
		
		/* Begin the SQL transaction */
		$request['dba']->beginTransaction();
		
		/**
		 * Should we update the topic?
		 */
		if($this->row_type & REPLY) {
			$topic_last_reply	= $request['dba']->getRow("SELECT * FROM ". K4POSTS ." WHERE post_id <> ". intval($post['post_id']) ." AND parent_id=". intval($post['parent_id']) ." ORDER BY created DESC LIMIT 1");
			$topic_update		= $request['dba']->prepareStatement("UPDATE ". K4POSTS ." SET lastpost_created=?,lastpost_uname=?,lastpost_uid=?,lastpost_id=?,num_replies=? WHERE post_id=?");
			$topic_update->setInt(1, $topic_last_reply['created']);
			$topic_update->setString(2, $topic_last_reply['poster_name']);
			$topic_update->setInt(3, $topic_last_reply['poster_id']);
			$topic_update->setInt(4, $topic_last_reply['post_id']);
			$topic_update->setInt(5, intval($request['dba']->getValue("SELECT COUNT(*) FROM ". K4POSTS ." WHERE parent_id=". intval($post['parent_id'])) - 1) ); // use this to make sure we get the right count
			$topic_update->setInt(6, $post['parent_id']);
			$topic_update->executeUpdate();
		}
		
		/**
		 * Remove any bad post reports, get a count of replies, change
		 * user post counts and remove attachments! WOAH!
		 */
		$num_replies_to_remove = 1;
		if($this->row_type & REPLY) {
			$request['dba']->executeUpdate("DELETE FROM ". K4BADPOSTREPORTS ." WHERE post_id = ". intval($post['post_id']) );
		} else {
			$posts = $request['dba']->executeQuery("SELECT post_id,poster_id,attachments FROM ". K4POSTS ." WHERE ( (parent_id=". intval($post['post_id']) ." AND row_type=". REPLY .") OR parent_id=". intval($post['post_id']) .")");
			$num_replies_to_remove = intval($posts->numrows() - 1);
			while($posts->next()) {
				$p = $posts->current();
				
				// remove bad post report
				$request['dba']->executeUpdate("DELETE FROM ". K4BADPOSTREPORTS ." WHERE post_id = ". intval($p['post_id']) );
				
				// change user post count
				if($delete_topic || $this->row_type & REPLY) {
					$request['dba']->executeUpdate("UPDATE ". K4USERINFO ." SET num_posts=num_posts-1 WHERE user_id=". intval($p['poster_id']));
				}
			
				if($p['attachments'] > 0) {
					remove_attachments($request, $p, FALSE);
				}
			}
		}
		
		/**
		 * Delete/Move the post 
		 */
		if($delete_topic || $this->row_type & REPLY) {
			$request['dba']->executeUpdate("DELETE FROM ". K4POSTS ." WHERE post_id = ". intval($post['post_id']));
			
			// change or remove replies
			if($this->row_type & REPLY) {
				$request['dba']->executeUpdate("UPDATE ". K4POSTS ." SET row_order=row_order-1 WHERE row_order>". intval($post['row_order']) ." AND post_id=". intval($post['forum_id']));
			} else {
				$request['dba']->executeUpdate("DELETE FROM ". K4POSTS ." WHERE parent_id=". intval($post['post_id']));
				$request['dba']->executeUpdate("DELETE FROM ". K4RATINGS ." WHERE post_id = ". intval($post['post_id']));
			}
		} else {
			
			/* Move this topic and its replies to the garbage bin */
			if($this->row_type & TOPIC) {
				
				// parent_id is left as the current forum id
				$request['dba']->executeUpdate("UPDATE ". K4POSTS ." SET forum_id=". GARBAGE_BIN ." WHERE ( (parent_id=". intval($post['post_id']) ." AND row_type=". REPLY .") OR post_id=". intval($post['post_id']) .")");
				
				// update the garbage bin
				$newpost_created		= $request['dba']->getRow("SELECT * FROM ". K4POSTS ." WHERE forum_id=". GARBAGE_BIN ." ORDER BY created DESC LIMIT 1");
				$forum_update = $request['dba']->prepareStatement("UPDATE ". K4FORUMS ." SET posts=posts+?,replies=replies+?,topics=topics+?,post_created=?,post_name=?,post_uname=?,post_id=?,post_uid=?,post_posticon=? WHERE forum_id=?");
				$forum_update->setInt(1, ($this->row_type & REPLY ? $num_replies_to_remove : $num_replies_to_remove+1) );
				$forum_update->setInt(2, $num_replies_to_remove);
				$forum_update->setInt(3, ($this->row_type & REPLY ? 0 : 1));
				$forum_update->setInt(4, $newpost_created['created']);
				$forum_update->setString(5, $newpost_created['name']);
				$forum_update->setString(6, $newpost_created['poster_name']);
				$forum_update->setInt(7, $newpost_created['post_id']);
				$forum_update->setInt(8, $newpost_created['poster_id']);
				$forum_update->setString(9, $newpost_created['posticon']);
				$forum_update->setInt(10, GARBAGE_BIN);
				$forum_update->executeUpdate();
			}
		}

		/* Get that last post in this forum that's not part of/from this topic */
		$lastpost_created		= $request['dba']->getRow("SELECT * FROM ". K4POSTS ." WHERE forum_id=". intval($post['forum_id']) ." ORDER BY created DESC LIMIT 1");
		if(!is_array($lastpost_created) || empty($lastpost_created)) {
			$lastpost_created = array('created'=>0, 'name'=>'', 'poster_name'=>'', 'post_id'=>0, 'poster_id'=>0, 'posticon'=>'',);
		}

		/**
		 * Update the forum and the datastore
		 */

		$forum_update		= $request['dba']->prepareStatement("UPDATE ". K4FORUMS ." SET posts=posts-?,replies=replies-?,topics=topics-?,post_created=?,post_name=?,post_uname=?,post_id=?,post_uid=?,post_posticon=? WHERE forum_id=?");
		/* Set the forum values */
		$forum_update->setInt(1, ($this->row_type & REPLY ? $num_replies_to_remove : $num_replies_to_remove+1) );
		$forum_update->setInt(2, $num_replies_to_remove);
		$forum_update->setInt(3, ($this->row_type & REPLY ? 0 : 1));
		$forum_update->setInt(4, $lastpost_created['created']);
		$forum_update->setString(5, $lastpost_created['name']);
		$forum_update->setString(6, $lastpost_created['poster_name']);
		$forum_update->setInt(7, $lastpost_created['post_id']);
		$forum_update->setInt(8, $lastpost_created['poster_id']);
		$forum_update->setString(9, $lastpost_created['posticon']);
		$forum_update->setInt(10, $forum['forum_id']);
		$forum_update->executeUpdate();

		/* Set the datastore values */
		if($delete_topic || $this->row_type & REPLY) {
			
			$datastore_update	= $request['dba']->prepareStatement("UPDATE ". K4DATASTORE ." SET data=? WHERE varname=?");
			
			$datastore					= $_DATASTORE['forumstats'];
			$datastore['num_replies']	= $request['dba']->getValue("SELECT COUNT(*) FROM ". K4POSTS ." WHERE row_type=". REPLY);
			$datastore['num_topics']	= $request['dba']->getValue("SELECT COUNT(*) FROM ". K4POSTS ." WHERE row_type=". TOPIC);
			
			$datastore_update->setString(1, serialize($datastore));
			$datastore_update->setString(2, 'forumstats');
			
			/* Execute datastore update query */
			$datastore_update->executeUpdate();
			
			// Update the datastore cache
			reset_cache('datastore');
		}	
				
		$request['dba']->commitTransaction();		

		/* Redirect the user */
		$action = new K4InformationAction(new K4LanguageElement(($this->row_type & REPLY ? 'L_DELETEDREPLY' : 'L_DELETEDTOPIC'), $post['name']), 'content', FALSE, ($this->row_type & REPLY ? 'viewtopic.php?id='. $post['parent_id'] : 'viewforum.php?f='. $post['forum_id']), 3);
		return $action->execute($request);
	}
}

class PostsIterator extends FAProxyIterator {
	
	var $user, $dba, $result, $qp, $users, $groups, $fields;

	function PostsIterator(&$request, &$result) {
		$this->__construct($request, $result);
	}

	function __construct(&$request, &$result) {
		
		global $_QUERYPARAMS, $_USERGROUPS, $_PROFILEFIELDS;

		$this->users			= array();
		$this->result			= &$result;
		$this->groups			= $_USERGROUPS;
		$this->fields			= $_PROFILEFIELDS;
		$this->user				= &$request['user'];
		$this->dba				= &$request['dba'];
		$this->qp				= $_QUERYPARAMS;
		
		parent::__construct($this->result);
	}

	function current() {
		$temp					= parent::current();
		
		$temp['posticon']		= isset($temp['posticon']) && @$temp['posticon'] != '' ? (file_exists(BB_BASE_DIR .'/tmp/upload/posticons/'. @$temp['posticon']) ? @$temp['posticon'] : 'clear.gif') : 'clear.gif';
		
		if($temp['poster_id'] > 0) {
			
			if(!isset($this->users[$temp['poster_id']])) {
				$temp['post_display_user_ddmenu'] = 1; // display a ddmenu
				$user							= $this->dba->getRow("SELECT ". $this->qp['user'] . $this->qp['userinfo'] ." FROM ". K4USERS ." u LEFT JOIN ". K4USERINFO ." ui ON u.id=ui.user_id WHERE u.id=". intval($temp['poster_id']));
				
				if(is_array($user) && !empty($user)) {
					$group						= get_user_max_group($user, $this->groups);
					$user['group_color']		= !isset($group['color']) || $group['color'] == '' ? '000000' : $group['color'];
					$user['group_nicename']		= $group['nicename'];
					$user['group_avatar']		= $group['avatar'];
					$user['online']				= (time() - ini_get('session.gc_maxlifetime')) > $user['seen'] ? 'offline' : 'online';

					$this->users[$user['id']]	= $user;
				}
			} else {
				$temp['post_display_user_ddmenu'] = $this->result->hasPrev() ? 0 : 1; // use a different ddmenu
				$user						= $this->users[$temp['poster_id']];
			}
			
			if(is_array($user) && !empty($user)) {

				if($user['flag_level'] > 0 && $_SESSION['user']->get('perms') >= MODERATOR)
					$temp['post_user_background'] = 'background-color: #FFFF00;';
				
				foreach($user as $key => $val)
					$temp['post_user_'. $key] = $val;

				$temp['profilefields']	= new FAArrayIterator(get_profile_fields($this->fields, $temp));
				$temp['post_user_title'] = $user['user_title'];
				$temp['post_user_user_title'] = get_user_title($user['user_title'], $user['num_posts']);
			}

			if(!isset($temp['post_user_online']))
				$temp['post_user_online'] = 'offline';

		} else {
			$temp['post_user_id']	= 0;
			$temp['post_user_name']	= $temp['poster_name'];
		}

		/* do we have any attachments? */
		if(isset($temp['attachments']) && $temp['attachments'] > 0) {
			$temp['attachment_files'] = new K4AttachmentsIterator($this->dba, $this->user, $temp['post_id']);
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