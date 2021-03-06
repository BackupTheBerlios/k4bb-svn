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
		if(!isset($_REQUEST['post_id']) || intval($_REQUEST['post_id']) == 0) {			
			$action = new K4InformationAction(new K4LanguageElement('L_TOPICDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}
		
		/* Get our topic */
		$post = $request['dba']->getRow("SELECT * FROM ". K4POSTS ." WHERE post_id = ". intval($_REQUEST['post_id']));
		
		if(!$post || !is_array($post) || empty($post)) {
			$action = new K4InformationAction(new K4LanguageElement('L_POSTDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}

		/* Should we redirect this user? */
		if($post['moved_new_post_id'] > 0) {
			header("Location: viewpost.php?post_id=". intval($post['moved_new_post_id']));
		}

		/* Get the current forum */
		$forum = $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($post['forum_id']));

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
		
		$domain = get_domain();
		
		setcookie(K4FORUMINFO, trim($cookiestr, ','), time() + 2592000, $domain);
		
		unset($cookieinfo, $cookiestr);

		$cookieinfo						= get_topic_cookies();

		/**
		 * Set the new breadcrumbs bit
		 */
		
		k4_bread_crumbs($request['template'], $request['dba'], $post['name'], $forum);
		

		/**
		 * Now tell the cookies that we've read this topic
		 */		
		$cookieinfo[$post['post_id']] = time();
		$cookiestr						= '';
		
		foreach($cookieinfo as $key => $val) {
			// make sure to weed out 30-day old topic views
			if( ((time() - intval($val)) / 30) <= 2592000 )
				$cookiestr					.= ','. $key .','. intval($val);
		}
		
		setcookie(K4TOPICINFO, trim($cookiestr, ','), time() + 2592000, $domain);		
		unset($cookieinfo, $cookiestr);
				
		/**
		 * More error checking
		 */
		if($post['is_draft'] == 1 || $post['display'] == 0 || ($post['queue'] == 1 && !$moderator) ) {
			no_perms_error($request);
			return TRUE;
		}
		
		if(get_map('forums', 'can_view', array()) > $request['user']->get('perms') 
			|| get_map( ($post['row_type'] & TOPIC ? 'topics' : 'replies'), 'can_view', array('forum_id'=>$forum['forum_id'])) > $request['user']->get('perms')
			) {
			$action = new K4InformationAction(new K4LanguageElement('L_PERMCANTVIEWTOPIC'), 'content', FALSE);
			return $action->execute($request);
		}
		
		
		/**
		 * Is this topic expired?
		 */
		$extra						= '';
		if($post['post_type'] > TOPIC_NORMAL && $post['post_expire'] > 0) {
			if(($post['created'] + (3600 * 24 * $post['post_expire']) ) > time()) {
				
				$extra				= ",post_expire=0,post_type=". TOPIC_NORMAL;
			}
		}
		
		/* Add the topic info to the template */
		foreach($post as $key => $val)
			$request['template']->setVar('post_'. $key, $val);
		
		/* Add the forum info to the template */
		foreach($forum as $key => $val)
			$request['template']->setVar('forum_'. $key, $val);

		/* Update the number of views for this topic */
		$request['dba']->executeUpdate("UPDATE ". K4POSTS ." SET views=views+1 $extra WHERE post_id=". intval($post['post_id']));
		
		/* set the topic iterator */
		if($post['row_type'] & TOPIC) {
			$request['template']->setVar('next_oldest', intval($request['dba']->getValue("SELECT post_id FROM ". K4POSTS ." WHERE post_id < ". $post['post_id'] ." LIMIT 1")));
			$request['template']->setVar('next_newest', intval($request['dba']->getValue("SELECT post_id FROM ". K4POSTS ." WHERE post_id > ". $post['post_id'] ." LIMIT 1")));

			/**
			 * Topic subscription stuff
			 */
			if($request['user']->isMember()) {
				$subscribed		= $request['dba']->executeQuery("SELECT * FROM ". K4SUBSCRIPTIONS ." WHERE post_id = ". intval($post['post_id']) ." AND user_id = ". $request['user']->get('id'));
				$request['template']->setVar('is_subscribed', iif($subscribed->numRows() > 0, 1, 0));
			}
		}
		
		$request['template']->setVar('header_text', ($use_reply ? $reply['name'] : $post['name']));
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