<?php
/**
* k4 Bulletin Board, newreply.php
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
* @version $Id: newreply.php,v 1.4 2005/05/12 01:33:21 k4st Exp $
* @package k42
*/

require "includes/filearts/filearts.php";
require "includes/k4bb/k4bb.php";

class K4DefaultAction extends FAAction {
	function execute(&$request) {
		
		global $_QUERYPARAMS;
		
		/**
		 * Error checking 
		 */

		if(!isset($_REQUEST['id']) || !$_REQUEST['id'] || intval($_REQUEST['id']) == 0) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], &$request['dba'], 'L_INVALIDTOPIC');
			
			$action = new K4InformationAction(new K4LanguageElement('L_TOPICDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}

		/* Get our topic */
		$topic				= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['topic'] ." FROM ". K4TOPICS ." t LEFT JOIN ". K4INFO ." i ON t.topic_id = i.id WHERE i.id = ". intval($_REQUEST['id']));
		
		if(!$topic || !is_array($topic) || empty($topic)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], &$request['dba'], 'L_INVALIDTOPIC');
			
			$action = new K4InformationAction(new K4LanguageElement('L_TOPICDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}
			
		$forum				= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['forum'] ." FROM ". K4FORUMS ." f LEFT JOIN ". K4INFO ." i ON f.forum_id = i.id WHERE i.id = ". intval($topic['forum_id']));
		
		/* Check the forum data given */
		if(!$forum || !is_array($forum) || empty($forum)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], &$request['dba'], 'L_INVALIDFORUM');
			
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}
			
		/* Make sure the we are trying to delete from a forum */
		if(!($forum['row_type'] & FORUM)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], &$request['dba'], 'L_INFORMATION');
			
			$action = new K4InformationAction(new K4LanguageElement('L_CANTDELFROMNONFORUM'), 'content', FALSE);
			return $action->execute($request);
		}

		/* Do we have permission to post to this topic in this forum? */
		if($request['user']->get('perms') < get_map($request['user'], 'replies', 'can_add', array('forum_id'=>$forum['id']))) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], &$request['dba'], 'L_INFORMATION');
			
			$request['template']->setFile('content', 'login_form.html');
			$request['template']->setVisibility('no_perms', TRUE);
			return TRUE;

			//$action = new K4InformationAction(new K4LanguageElement('L_PERMCANTPOST'), 'content', FALSE);
			//return $action->execute($request);		
		}

		if(isset($_REQUEST['r']) && intval($_REQUEST['r']) != 0) {
			$reply				= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['reply'] ." FROM ". K4REPLIES ." r LEFT JOIN ". K4INFO ." i ON r.reply_id = i.id WHERE i.id = ". intval($_REQUEST['r']));
			
			if(!$reply || !is_array($reply) || empty($reply)) {
				/* set the breadcrumbs bit */
				k4_bread_crumbs(&$request['template'], &$request['dba'], 'L_INVALIDREPLY');
				
				$action = new K4InformationAction(new K4LanguageElement('L_REPLYDOESNTEXIST'), 'content', FALSE);
				return $action->execute($request);
			} else {
				
				$request['template']->setVisibility('parent_id', TRUE);
				$request['template']->setVar('parent_id', $reply['id']);
			}
		}
		
		/* Prevent post flooding */
		$last_topic		= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['topic'] ." FROM ". K4TOPICS ." t LEFT JOIN ". K4INFO ." i ON t.topic_id = i.id WHERE t.poster_ip = '". USER_IP ."' ORDER BY i.created DESC LIMIT 1");
		$last_reply		= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['reply'] ." FROM ". K4REPLIES ." r LEFT JOIN ". K4INFO ." i ON r.reply_id = i.id WHERE r.poster_ip = '". USER_IP ."' ORDER BY i.created DESC LIMIT 1");
		
		if(is_array($last_topic) && !empty($last_topic)) {
			if(intval($last_topic['created']) + POST_IMPULSE_LIMIT > time()) {
				/* set the breadcrumbs bit */
				k4_bread_crumbs(&$request['template'], &$request['dba'], 'L_INFORMATION');
				
				$action = new K4InformationAction(new K4LanguageElement('L_MUSTWAITSECSTOPOST'), 'content', TRUE);
				return $action->execute($request);
			}
		}

		if(is_array($last_reply) && !empty($last_reply)) {
			if(intval($last_reply['created']) + POST_IMPULSE_LIMIT > time()) {
				/* set the breadcrumbs bit */
				k4_bread_crumbs(&$request['template'], &$request['dba'], 'L_INFORMATION');
				
				$action = new K4InformationAction(new K4LanguageElement('L_MUSTWAITSECSTOPOST'), 'content', TRUE);
				return $action->execute($request);
			}
		}

		$parent			= isset($reply) && is_array($reply) ? $reply : $topic;
				
		/**
		 * Start setting useful template information
		 */
		

		/* Get and set the emoticons and post icons to the template */
		$emoticons	= &$request['dba']->executeQuery("SELECT * FROM ". K4EMOTICONS ." WHERE clickable = 1");
		$posticons	= &$request['dba']->executeQuery("SELECT * FROM ". K4POSTICONS);

		$request['template']->setList('emoticons', $emoticons);
		$request['template']->setList('posticons', $posticons);

		$request['template']->setVar('emoticons_per_row', $request['template']->getVar('smcolumns'));
		$request['template']->setVar('emoticons_per_row_remainder', $request['template']->getVar('smcolumns')-1);
		
		topic_post_options(&$request['template'], &$request['user'], $forum);

		/* Set the forum and topic info to the template */
		foreach($forum as $key => $val)
			$request['template']->setVar('forum_'. $key, $val);

		/* We set topic information to be reply information */
		foreach($topic as $key => $val) {
			
			/* Omit the body text variable */
			if($key != 'body_text')
				$request['template']->setVar('reply_'. $key, $val);
		}

		/* If this is a quote, put quote tags around the message */
		if(isset($_REQUEST['quote']) && intval($_REQUEST['quote']) == 1) {
			$bbcode			= &new BBCodex($request['dba'], $request['user'], $parent['body_text'], $forum['id'], TRUE, TRUE, TRUE, TRUE);
			$request['template']->setVar('reply_body_text', '[quote='. $parent['poster_name'] .']'. $bbcode->revert() .'[/quote]');
		}

		/* Set the title variable */
		if(isset($reply))
			$request['template']->setVar('reply_name', $request['template']->getVar('L_RE') .': '. $reply['name']);
		else
			$request['template']->setVar('reply_name', $request['template']->getVar('L_RE') .': '. $topic['name']);

		$request['template']->setVar('newtopic_action', 'newreply.php?act=postreply');

		/* set the breadcrumbs bit */
		k4_bread_crumbs(&$request['template'], &$request['dba'], 'L_POSTREPLY', $parent, $forum);
		
		foreach($parent as $key => $val)
			$request['template']->setVar('parent_'. $key, $val);
		
		$query				= "SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['reply'] ." FROM ". K4REPLIES ." r LEFT JOIN ". K4INFO ." i ON i.id = r.reply_id WHERE r.topic_id = ". intval($topic['id']) ." AND i.row_type = ". REPLY ." ORDER BY i.created DESC LIMIT 10";
		
		$replies			= &$request['dba']->executeQuery($query);
		
		/* Set the form actiob */
		$request['template']->setVar('newreply_act', 'newreply.php?act=postreply');

		$request['template']->setList('topic_review', new TopicReviewIterator($request['dba'], $topic, $replies, $request['user']->getInfoArray()));

		/* Set the post topic form */
		$request['template']->setFile('content', 'newreply.html');

		return TRUE;
	}
}


$app = new K4controller('forum_base.html');

$app->setAction('', new K4DefaultAction);
$app->setDefaultEvent('');

$app->setAction('postreply', new PostReply);
$app->setAction('editreply', new EditReply);
$app->setAction('updatereply', new UpdateReply);

$app->execute();

?>