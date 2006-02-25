<?php
/**
* k4 Bulletin Board, replies.class.php
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

		$request['template']->setVar('attach_post_id', $reply['post_id']);

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
		if(!($forum['row_type'] & FORUM) || $forum['forum_id'] == GARBAGE_BIN) {
			$action = new K4InformationAction(new K4LanguageElement('L_CANTPOSTTONONFORUM'), 'content', FALSE);
			return $action->execute($request);
		}
		
		/* Does this user have permission to edit theirreply if the topic is locked? */
		if($topic['post_locked'] == 1 && get_map( 'closed', 'can_edit', array('forum_id' => $forum['forum_id'])) > $request['user']->get('perms')) {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);
			return $action->execute($request);
		}

		/* set the breadcrumbs bit */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_EDITREPLY', $reply, $forum);
		
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
		
		//$bbcode				= &new BBCodex($request['dba'], $request['user']->getInfoArray(), $reply['body_text'], $forum['forum_id'], TRUE, TRUE, TRUE, TRUE);
		$parser = &new BBParser;
		
		Globals::setGlobal('forum_id', $forum['forum_id']);
		Globals::setGlobal('maxpolloptions', $forum['maxpolloptions']);
		
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
		
		$reply['body_text'] = $parser->revert($reply['body_text']);

		foreach($reply as $key => $val)
			$request['template']->setVar('post_'. $key, $val);
		
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
		
		/* Get 10 replies that are above this reply to set as a topic review */
		// TODO: work on this a bit.
		$result		= $request['dba']->executeQuery("SELECT * FROM ". K4POSTS ." WHERE (post_id=". intval($topic['post_id']) ." OR parent_id=". intval($topic['post_id']) .") ORDER BY created DESC LIMIT 10");
		$it			= &new PostsIterator($request, $result);
		$request['template']->setList('topic_review', $it);

		/* set the breadcrumbs bit */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_EDITREPLY', $topic, $forum);
		
		/* Create our editor */
		create_editor($request, $reply['body_text'], 'post', $forum);

		/* Set the post topic form */
		//$request['template']->setFile('preview', 'post_preview.html');
		$request['template']->setFile('content', 'newreply.html');
		$request['template']->setVar('L_TITLETOOSHORT', sprintf($request['template']->getVar('L_TITLETOOSHORT'), $request['template']->getVar('topicminchars'), $request['template']->getVar('topicmaxchars')));

		return TRUE;
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