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



if(!defined('IN_K4')) {
	return;
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
		$reply				= $request['dba']->getRow("SELECT * FROM ". K4POSTS ." WHERE post_id = ". intval($_REQUEST['id']));
		
		if(!$reply || !is_array($reply) || empty($reply)) {
			$action = new K4InformationAction(new K4LanguageElement('L_REPLYDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}

		$topic				= $request['dba']->getRow("SELECT * FROM ". K4POSTS ." WHERE post_id = ". intval($reply['post_id']));
		
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
		if($topic['post_locked'] == 1 && get_map( 'closed', 'can_edit', array('forum_id' => $forum['forum_id'])) > $request['user']->get('perms')) {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);
			return $action->execute($request);
		}

		/* set the breadcrumbs bit */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_EDITREPLY', $topic, $forum);
		
		if($reply['poster_id'] == $request['user']->get('id')) {
			if(get_map( 'replies', 'can_edit', array('forum_id'=>$forum['forum_id'])) > $request['user']->get('perms')) {
				$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);
				return $action->execute($request);
			}
		} else {
			if(get_map( 'other_replies', 'can_edit', array('forum_id'=>$forum['forum_id'])) > $request['user']->get('perms')) {
				$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);
				return $action->execute($request);
			}
		}
		
		$bbcode				= &new BBCodex($request['dba'], $request['user']->getInfoArray(), $reply['body_text'], $forum['forum_id'], TRUE, TRUE, TRUE, TRUE);
		
		/* Get and set the emoticons and post icons to the template */
		$emoticons			= $request['dba']->executeQuery("SELECT * FROM ". K4EMOTICONS ." WHERE clickable = 1");
		$posticons			= $request['dba']->executeQuery("SELECT * FROM ". K4POSTICONS);

		$request['template']->setList('emoticons', $emoticons);
		$request['template']->setList('posticons', $posticons);

		$request['template']->setVar('emoticons_per_row', $request['template']->getVar('smcolumns'));
		$request['template']->setVar('emoticons_per_row_remainder', $request['template']->getVar('smcolumns')-1);
		
		/* Get the posting options */
		topic_post_options($request['template'], $request['user'], $forum);
		post_attachment_options($request, $forum, $reply);
		
		$reply['body_text'] = $bbcode->revert();

		foreach($reply as $key => $val)
			$request['template']->setVar('reply_'. $key, $val);
		
		/* Assign the forum information to the template */
		foreach($forum as $key => $val)
			$request['template']->setVar('forum_'. $key, $val);

		/* Set the the button display options */
		$request['template']->setVisibility('edit_reply', TRUE);
		$request['template']->setVisibility('post_id', TRUE);
		$request['template']->setVisibility('post_reply', FALSE);
		$request['template']->setVisibility('edit_post', TRUE);
		
		/* Set the form actiob */
		$request['template']->setVar('newreply_act', 'newreply.php?act=updatereply');
		
		/* Get 10 replies that are above this reply */
		$replies	= $request['dba']->executeQuery("SELECT * FROM ". K4POSTS ." WHERE post_id = ". intval($topic['post_id']) ." AND post_id < ". intval($reply['post_id']) ." ORDER BY created DESC LIMIT 10");

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
		$reply				= $request['dba']->getRow("SELECT * FROM ". K4POSTS ." WHERE post_id = ". intval($_REQUEST['id']));
		
		if(!$reply || !is_array($reply) || empty($reply)) {
			$action = new K4InformationAction(new K4LanguageElement('L_REPLYDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}
		
		$topic				= $request['dba']->getRow("SELECT * FROM ". K4POSTS ." WHERE post_id = ". intval($reply['post_id']));
		
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
			if(get_map( 'replies', 'can_del', array('forum_id'=>$forum['forum_id'])) > $request['user']->get('perms')) {
				no_perms_error($request);
				return TRUE;
			}
		} else {
			if(get_map( 'other_replies', 'can_del', array('forum_id'=>$forum['forum_id'])) > $request['user']->get('perms')) {
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
		$last_topic			= $request['dba']->getRow("SELECT * FROM ". K4POSTS ." WHERE is_draft=0 AND forum_id=". intval($reply['forum_id']) ." ORDER BY created DESC LIMIT 1");
		$last_topic			= !$last_topic || !is_array($last_topic) ? array('created'=>0,'id'=>0,'poster_name'=>'','poster_id'=>0,'post_id'=>0,'name'=>'','posticon'=>'clear.gif') : array_merge($last_topic, array('id'=>$last_topic['post_id']));
		
		/* Get that last post in this forum that's not part of/from this topic */
		$lastpost_created			= $request['dba']->getRow("SELECT * FROM ". K4POSTS ." WHERE post_id <> ". intval($reply['post_id']) ." AND forum_id=". intval($reply['forum_id']) ." ORDER BY created DESC LIMIT 1");
		$lastpost_created			= !$lastpost_created || !is_array($lastpost_created) ? $last_topic : array_merge($lastpost_created, array('id'=>$lastpost_created['post_id']));
		
		/* Should the last post be the last topic? */
		$lastpost_created			= $lastpost_created['created'] < $last_topic['created'] ? $last_topic : $lastpost_created;
		
		/* Delete the reply */
		$request['dba']->executeUpdate("DELETE FROM ". K4POSTS ." WHERE post_id = ". intval($reply['post_id']));
		$request['dba']->executeUpdate("UPDATE ". K4POSTS ." SET row_order=row_order-1 WHERE row_order > ". intval($reply['row_order']) ." AND post_id = ". intval($reply['forum_id']));

		/**
		 * Should we update the topic?
		 */
		if($topic['num_replies'] > 0) {
			$topic_last_reply	= $request['dba']->getRow("SELECT * FROM ". K4POSTS ." WHERE post_id <> ". intval($reply['post_id']) ." AND post_id=". intval($topic['post_id']) ." ORDER BY created DESC LIMIT 1");
			$topic_update		= $request['dba']->prepareStatement("UPDATE ". K4POSTS ." SET lastpost_created=?,lastpost_uname=?,lastpost_uid=?,lastpost_id=?,num_replies=? WHERE post_id=?");
			$topic_update->setInt(1, $topic_last_reply['created']);
			$topic_update->setString(2, $topic_last_reply['poster_name']);
			$topic_update->setInt(3, $topic_last_reply['poster_id']);
			$topic_update->setInt(4, $topic_last_reply['post_id']);
			$topic_update->setInt(5, $request['dba']->getValue("SELECT COUNT(*) FROM ". K4POSTS ." WHERE post_id = ". intval($topic['post_id']))); // use this to make sure we get the right count
			$topic_update->setInt(6, $topic['post_id']);
			$topic_update->executeUpdate();
		} else {
			$request['dba']->executeUpdate("UPDATE ". K4POSTS ." SET num_replies=0,lastpost_created=0,lastpost_uname='',lastpost_uid=0,lastpost_id=0,lastpost_created=". intval($topic['created']) ." WHERE post_id=". intval($topic['post_id']));
		}
		
		/* Remove any bad post reports */
		$request['dba']->executeUpdate("DELETE FROM ". K4BADPOSTREPORTS ." WHERE post_id = ". intval($reply['post_id']));
		
		/**
		 * Update the forum and the datastore
		 */
		
		$request['dba']->beginTransaction();

		$forum_update		= $request['dba']->prepareStatement("UPDATE ". K4FORUMS ." SET posts=posts-?,replies=replies-?,post_created=?,post_name=?,post_uname=?,post_id=?,post_uid=?,post_posticon=? WHERE forum_id=?");
		$datastore_update	= $request['dba']->prepareStatement("UPDATE ". K4DATASTORE ." SET data=? WHERE varname=?");
			
		/* Set the forum values */
		$forum_update->setInt(1, 1);
		$forum_update->setInt(2, 1);
		$forum_update->setInt(3, $lastpost_created['created']);
		$forum_update->setString(4, $lastpost_created['name']);
		$forum_update->setString(5, $lastpost_created['poster_name']);
		$forum_update->setInt(6, $lastpost_created['id']);
		$forum_update->setInt(7, $lastpost_created['poster_id']);
		$forum_update->setString(8, $lastpost_created['posticon']);
		$forum_update->setInt(9, $forum['forum_id']);
		
		/* Set the datastore values */
		$datastore					= $_DATASTORE['forumstats'];
		$datastore['num_replies']	= $request['dba']->getValue("SELECT COUNT(*) FROM ". K4POSTS) - 1;
		
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
		
		// delete this replies attachments
		remove_attachments($request, $reply);
		
		$request['dba']->commitTransaction();

		reset_cache('datastore');
		
		/* Redirect the user */
		$action = new K4InformationAction(new K4LanguageElement('L_DELETEDREPLY', $reply['name'], $topic['name']), 'content', FALSE, 'viewtopic.php?id='. $topic['post_id'], 3);
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
		$this->start_level	= $topic_level;

		parent::__construct($this->result);
	}

	function current() {
		$temp					= parent::current();

		$temp['inset_level']	= str_repeat('&nbsp; &nbsp; &nbsp;', intval($temp['row_level'] - $this->start_level));
		
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

	function current() {
		$temp					= parent::current();
		
		$temp['posticon']		= isset($temp['posticon']) && @$temp['posticon'] != '' ? iif(file_exists(BB_BASE_DIR .'/tmp/upload/posticons/'. @$temp['posticon']), @$temp['posticon'], 'clear.gif') : 'clear.gif';
		$temp['post_id']		= 'r'. $temp['post_id'];

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
			$temp['attachment_files'] = new K4AttachmentsIterator($this->dba, $this->user, $temp['post_id'], $temp['post_id']);
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