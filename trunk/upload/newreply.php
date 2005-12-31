<?php
/**
* k4 Bulletin Board, newreply.php
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
* @version $Id: newreply.php 154 2005-07-15 02:56:28Z Peter Goodman $
* @package k42
*/

require "includes/filearts/filearts.php";
require "includes/k4bb/k4bb.php";

class K4DefaultAction extends FAAction {
	function execute(&$request) {
		
		global $_QUERYPARAMS, $_URL;
		
		/**
		 * Error checking 
		 */
		
		/* set the breadcrumbs bit */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');

		if(!isset($_REQUEST['id']) || !$_REQUEST['id'] || intval($_REQUEST['id']) == 0) {
			$action = new K4InformationAction(new K4LanguageElement('L_TOPICDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}

		/* Get our topic */
		$topic				= $request['dba']->getRow("SELECT * FROM ". K4POSTS ." WHERE post_id = ". intval($_REQUEST['id']));
		
		if(!$topic || !is_array($topic) || empty($topic)) {
			$action = new K4InformationAction(new K4LanguageElement('L_TOPICDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}
		
		// set the topic id to the template
		$request['template']->setVar('topic_id', $topic['post_id']);
		
		// get the forum
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

		/* Do we have permission to post to this topic in this forum? */
		if($request['user']->get('perms') < get_map( 'replies', 'can_add', array('forum_id'=>$forum['forum_id']))) {
			no_perms_error($request);
			return TRUE;
		}

		if(isset($_REQUEST['r']) && intval($_REQUEST['r']) != 0) {
			$reply				= $request['dba']->getRow("SELECT * FROM ". K4POSTS ." WHERE post_id = ". intval($_REQUEST['r']));
			
			if(!$reply || !is_array($reply) || empty($reply)) {
				$action = new K4InformationAction(new K4LanguageElement('L_REPLYDOESNTEXIST'), 'content', FALSE);
				return $action->execute($request);
			} else {
				$request['template']->setVisibility('parent_id', TRUE);
				$request['template']->setVar('parent_id', $reply['post_id']);
			}
		}
		
		/* Prevent post flooding */
		$last_topic		= $request['dba']->getRow("SELECT * FROM ". K4POSTS ." WHERE poster_ip = '". USER_IP ."' ORDER BY created DESC LIMIT 1");
		$last_reply		= $request['dba']->getRow("SELECT * FROM ". K4POSTS ." WHERE poster_ip = '". USER_IP ."' ORDER BY created DESC LIMIT 1");
		
		if(is_array($last_topic) && !empty($last_topic)) {
			if(intval($last_topic['created']) + POST_IMPULSE_LIMIT > time() && $request['user']->get('perms') < MODERATOR) {
				$action = new K4InformationAction(new K4LanguageElement('L_MUSTWAITSECSTOPOST'), 'content', TRUE);
				return $action->execute($request);
			}
		}

		if(is_array($last_reply) && !empty($last_reply)) {
			if(intval($last_reply['created']) + POST_IMPULSE_LIMIT > time() && $request['user']->get('perms') < MODERATOR) {
				$action = new K4InformationAction(new K4LanguageElement('L_MUSTWAITSECSTOPOST'), 'content', TRUE);
				return $action->execute($request);
			}
		}

		$parent			= isset($reply) && is_array($reply) ? $reply : $topic;
				
		/**
		 * Start setting useful template information
		 */

		/* Get and set the emoticons and post icons to the template */
		$emoticons	= $request['dba']->executeQuery("SELECT * FROM ". K4EMOTICONS ." WHERE clickable = 1");
		$posticons	= $request['dba']->executeQuery("SELECT * FROM ". K4POSTICONS);

		$request['template']->setList('emoticons', $emoticons);
		$request['template']->setList('posticons', $posticons);

		$request['template']->setVar('emoticons_per_row', $request['template']->getVar('smcolumns'));
		$request['template']->setVar('emoticons_per_row_remainder', $request['template']->getVar('smcolumns')-1);
		
		/* Set to the template what posting perms this user has */
		topic_post_options($request['template'], $request['user'], $forum);

		/**
		 * Deal with reply attachments
		 */
		$num_attachments		= 0;
		
		/**
		 * Deal with file attachments
		 */
		if($request['template']->getVar('attach_inputs') == '') {
			if($request['user']->get('perms') >= get_map( 'attachments', 'can_add', array('forum_id'=>$forum['forum_id']))) {
				$num_attachments	= $request['template']->getVar('nummaxattaches') - $num_attachments;
				
				$attach_inputs		= '';
				for($i = 1; $i <= $num_attachments; $i++) {
					$attach_inputs	.= '<br /><input type="file" class="inputbox" name="attach'. $i .'" id="attach'. $i .'" value="" size="55" />';
				}
				
				$request['template']->setVar('attach_inputs', $attach_inputs);
			}
		}
		
		/* Set the forum and topic info to the template */
		foreach($forum as $key => $val)
			$request['template']->setVar('forum_'. $key, $val);

		/* We set topic information to be reply information */
		foreach($topic as $key => $val) {
			
			/* Omit the body text variable */
			if($key != 'body_text')
				$request['template']->setVar('post_'. $key, $val);
		}
		
		$body_text = '';

		/* If this is a quote, put quote tags around the message */
		if(isset($_REQUEST['quote']) && intval($_REQUEST['quote']) == 1) {
			
			// are we quoting a poll?
			if($parent['is_poll'] == 1) {
				
				// does this reply have a/some poll(s) ?
				preg_match_all('~\[poll=([0-9]+?)\]~i', $parent['body_text'], $poll_matches, PREG_SET_ORDER);

				if(count($poll_matches) > 0) {
					
					$url		= new FAUrl($_URL->__toString());
					$url->args	= array();
					$url->anchor= FALSE;
					$url->file	= 'viewpoll.php';

					foreach($poll_matches as $poll) {
						
						$parent['body_text'] = str_replace('[poll='. $poll[1] .']', $request['template']->getVar('L_POLL') .': [b][url='. $url->__toString() .'?id='. $poll[1] .']'. $request['dba']->getValue("SELECT question FROM ". K4POLLQUESTIONS ." WHERE id = ". intval($poll[1])) .'[/url][/b]', $parent['body_text']);

					}
				}
			}
			
			// revert the text with the bbcode parser
			$bbcode			= &new BBCodex($request['dba'], $request['user']->getInfoArray(), $parent['body_text'], $forum['forum_id'], TRUE, TRUE, TRUE, TRUE);
			$body_text		= '[quote='. ($parent['poster_name'] == '' ? $request['template']->getVar('L_GUEST') : $parent['poster_name']) .']'. $bbcode->revert() .'[/quote]';
		}

		/* Set the title variable */
		$request['template']->setVar('post_name', $request['template']->getVar('L_RE') .': '. (isset($reply) ? $reply['name'] : $topic['name']) );
		
		$request['template']->setVar('L_TITLETOOSHORT', sprintf($request['template']->getVar('L_TITLETOOSHORT'), $request['template']->getVar('topicminchars'), $request['template']->getVar('topicmaxchars')));

		/* set the breadcrumbs bit */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_POSTREPLY', $parent, $forum);
		
		foreach($parent as $key => $val)
			$request['template']->setVar('parent_'. $key, $val);
		
		$query				= "SELECT * FROM ". K4POSTS ." WHERE post_id = ". intval($topic['post_id']) ." ORDER BY created DESC LIMIT 10";
		
		$replies			= $request['dba']->executeQuery($query);
		
		/* Set the form actiob */
		$request['template']->setVar('newreply_act', 'newreply.php?act=postreply');
		
		$it					= &new TopicReviewIterator($request['dba'], $topic, $replies, $request['user']->getInfoArray() );
		$request['template']->setList('topic_review', $it);
		
		/* Set the post topic form */
		$request['template']->setFile('content', 'newreply.html');
		
		/* Create our editor */
		create_editor($request, $body_text, 'post', $forum);
		
		/* Clear up some memory */
		unset($it, $body_text, $forum, $replies, $bbcode, $last_topic, $last_reply, $topic);

		return TRUE;
	}
}


$app = new K4controller('forum_base.html');

$app->setAction('', new K4DefaultAction);
$app->setDefaultEvent('');

$app->setAction('postreply', new InsertPost(REPLY));
$app->setAction('editreply', new EditReply);
$app->setAction('updatereply', new UpdatePost(REPLY));

$app->execute();

?>