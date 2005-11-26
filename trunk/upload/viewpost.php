<?php
/**
* k4 Bulletin Board, k4_template.php
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
* @version $Id$
* @package k42
*/

require "includes/filearts/filearts.php";
require "includes/k4bb/k4bb.php";

class K4DefaultAction extends FAAction {
	function execute(&$request) {
		
		global $_QUERYPARAMS, $_USERGROUPS, $_URL;
		
		/* set the breadcrumbs bit */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');

		/**
		 * Error Checking
		 */
		if(!isset($_REQUEST['t']) || !$_REQUEST['t'] || intval($_REQUEST['t']) == 0) {			
			$action = new K4InformationAction(new K4LanguageElement('L_TOPICDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}
		
		/* Get our topic */
		$topic				= $request['dba']->getRow("SELECT * FROM ". K4TOPICS ." WHERE topic_id = ". intval($_REQUEST['t']));
		
		if(!$topic || !is_array($topic) || empty($topic)) {
			$action = new K4InformationAction(new K4LanguageElement('L_TOPICDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}

		$use_reply = FALSE;
		
		/* Do we try to get a reply? */
		if(isset($_REQUEST['r']) && intval($_REQUEST['r']) != 0) {			
			$reply		= $request['dba']->getRow("SELECT * FROM ". K4REPLIES ." WHERE reply_id = ". intval($_REQUEST['r']) ." AND topic_id = ". intval($topic['topic_id']));
		
			if(!$reply || !is_array($reply) || empty($reply)) {
				$action = new K4InformationAction(new K4LanguageElement('L_REPLYDOESNTEXIST'), 'content', FALSE);
				return $action->execute($request);
			}
			$use_reply = TRUE;
		}

		/* Should we redirect this user? */
		if($topic['moved_new_topic_id'] > 0) {
			header("Location: viewpost.php?t=". intval($topic['moved_new_topic_id']) . ($use_reply ? '&r='. $reply['reply_id'] : ''));
		}

		/* Get the current forum */
		$forum				= $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($topic['forum_id']));

		if(!$forum || !is_array($forum) || empty($forum)) {
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}
		
		/**
		 * This sets the last time that we've seen this forum
		 */
		$cookieinfo						= get_forum_cookies();
		$cookieinfo[$forum['forum_id']] = time();
		$cookiestr						= '';
		
		foreach($cookieinfo as $key => $val)
			$cookiestr					.= ','. $key .','. intval($val);
		
		setcookie(K4FORUMINFO, trim($cookiestr, ','), time() + 2592000, get_domain());
		
		unset($cookieinfo, $cookiestr);

		$cookieinfo						= get_topic_cookies();

		/**
		 * Set the new breadcrumbs bit
		 */
		
		k4_bread_crumbs($request['template'], $request['dba'], ($use_reply ? $reply['name'] : $topic['name']), $forum);
		

		/**
		 * Now tell the cookies that we've read this topic
		 */		
		$cookieinfo[$topic['topic_id']] = time();
		$cookiestr						= '';
		
		foreach($cookieinfo as $key => $val) {
			// make sure to weed out 30-day old topic views
			if( ((time() - intval($val)) / 30) <= 2592000 )
				$cookiestr					.= ','. $key .','. intval($val);
		}
		
		setcookie(K4TOPICINFO, trim($cookiestr, ','), time() + 2592000, get_domain());		
		unset($cookieinfo, $cookiestr);
				
		/**
		 * More error checking
		 */
		if($topic['is_draft'] == 1) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INVALIDTOPICVIEW');
			
			$action = new K4InformationAction(new K4LanguageElement('L_CANTVIEWDRAFT'), 'content', FALSE);
			return $action->execute($request);
		}

		if($topic['queue'] == 1 && !$moderator) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INVALIDTOPICVIEW');
			
			$action = new K4InformationAction(new K4LanguageElement('L_TOPICPENDINGMOD'), 'content', FALSE);
			return $action->execute($request);
		}

		if($topic['display'] == 0 && !$moderator) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INVALIDTOPICVIEW');
			
			$action = new K4InformationAction(new K4LanguageElement('L_TOPICISHIDDEN'), 'content', FALSE);
			return $action->execute($request);
		}
		
		if(get_map('forums', 'can_view', array()) > $request['user']->get('perms') 
			|| get_map( 'topics', 'can_view', array('forum_id'=>$forum['forum_id'])) > $request['user']->get('perms')
			|| ($use_reply ? (get_map( 'replies', 'can_view', array('forum_id'=>$forum['forum_id'])) > $request['user']->get('perms')) : FALSE)
			) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION', $forum);
			
			$action = new K4InformationAction(new K4LanguageElement('L_PERMCANTVIEWTOPIC'), 'content', FALSE);
			return $action->execute($request);
		}
		
		
		/**
		 * Is this topic expired?
		 */
		$extra						= '';
		if($topic['topic_type'] > TOPIC_NORMAL && $topic['topic_expire'] > 0) {
			if(($topic['created'] + (3600 * 24 * $topic['topic_expire']) ) > time()) {
				
				$extra				= ",topic_expire=0,topic_type=". TOPIC_NORMAL;
			}
		}
		
		/* Add the topic info to the template */
		foreach($topic as $key => $val)
			$request['template']->setVar('topic_'. $key, $val);
		
		/* Add the forum info to the template */
		foreach($forum as $key => $val)
			$request['template']->setVar('forum_'. $key, $val);

		/* Update the number of views for this topic */
		$request['dba']->executeUpdate("UPDATE ". K4TOPICS ." SET views=views+1 $extra WHERE topic_id=". intval($topic['topic_id']));
		
		/* set the topic iterator */
		if(!$use_reply) {
			$topic_list			= new TopicIterator($request['dba'], $request['user'], $topic, FALSE);
			$request['template']->setList('topic', $topic_list);
			
			$request['template']->setVar('next_oldest', intval($request['dba']->getValue("SELECT topic_id FROM ". K4TOPICS ." WHERE topic_id < ". $topic['topic_id'] ." LIMIT 1")));
			$request['template']->setVar('next_newest', intval($request['dba']->getValue("SELECT topic_id FROM ". K4TOPICS ." WHERE topic_id > ". $topic['topic_id'] ." LIMIT 1")));
			

			/**
			 * Topic subscription stuff
			 */
			if($request['user']->isMember()) {
				$subscribed		= $request['dba']->executeQuery("SELECT * FROM ". K4SUBSCRIPTIONS ." WHERE topic_id = ". intval($topic['topic_id']) ." AND user_id = ". $request['user']->get('id'));
				$request['template']->setVar('is_subscribed', iif($subscribed->numRows() > 0, 1, 0));
			}
		} else {

			/* Add the reply information to the template (same as for topics) */
			$reply_iterator = &new TopicIterator($request['dba'], $request['user'], $reply, FALSE);
			$request['template']->setList('topic', $reply_iterator);
		}
		
		$request['template']->setVar('header_text', ($use_reply ? $reply['name'] : $topic['name']));
		$request['template']->setVar('show_close_button', 1);
		$request['template']->setFile('content', 'post_preview.html');

		return TRUE;
	}
}

$app = new K4controller('misc_base.html');

$app->setAction('', new K4DefaultAction);
$app->setDefaultEvent('');

$app->execute();

?>