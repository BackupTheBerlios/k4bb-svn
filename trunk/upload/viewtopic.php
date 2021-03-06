<?php
/**
* k4 Bulletin Board, viewtopic.php
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
* @version $Id: viewtopic.php 158 2005-07-18 02:55:30Z Peter Goodman $
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
		
		/* Should we redirect this user? */
		if($topic['moved_new_post_id'] > 0) {
			header("Location: viewtopic.php?id=". intval($topic['moved_new_post_id']));
		}

		/* Get the current forum */
		$forum				= $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($topic['forum_id']));

		if(!$forum || !is_array($forum) || empty($forum)) {
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}

		if($topic['is_draft'] == 1) {
			$action = new K4InformationAction(new K4LanguageElement('L_CANTVIEWDRAFT'), 'content', FALSE);
			return $action->execute($request);
		}

		if(get_map( 'forums', 'can_view', array()) > $request['user']->get('perms') || get_map( 'topics', 'can_view', array('forum_id'=>$forum['forum_id'])) > $request['user']->get('perms')) {
			$action = new K4InformationAction(new K4LanguageElement('L_PERMCANTVIEWTOPIC'), 'content', FALSE);
			return $action->execute($request);
		}
		
		// get the page number up here, the header call needs it!
		// this is also used down below for pagination
		$page = isset($_REQUEST['page']) && ctype_digit($_REQUEST['page']) && intval($_REQUEST['page']) > 0 ? intval($_REQUEST['page']) : 1;

		/**
		 * Are we in an archive??
		 */
		if($forum['row_type'] & ARCHIVEFORUM) {
			
			if(!file_exists(BB_BASE_DIR .'/archive/'. intval($forum['forum_id']). '/'. intval($topic['post_id']) .'-'. $page .'.xml')) {

				$archiver = new k4Archiver();
				$archiver->archiveTopicXML($request, $forum, $topic);
			}
			
			// redirect us!
			header("Location: archive.php?forum=". intval($forum['forum_id']) ."&topic=". intval($topic['post_id']) ."&page=". $page );
			exit;
		}

		/**
		 * Moderator functions
		 */
		$request['template']->setVar('modpanel', 0);
		$moderator				= FALSE;
		if(is_moderator($request['user']->getInfoArray(), $forum)) {
			$request['template']->setVar('modpanel', 1);
			$moderator			= TRUE;
		}
		
		/**
		 * More error checking
		 */
		

		if($topic['queue'] == 1 && !$moderator) {
			$action = new K4InformationAction(new K4LanguageElement('L_TOPICPENDINGMOD'), 'content', FALSE);
			return $action->execute($request);
		}

		if($topic['display'] == 0 && !$moderator) {
			$action = new K4InformationAction(new K4LanguageElement('L_TOPICISHIDDEN'), 'content', FALSE);
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
		
		k4_bread_crumbs($request['template'], $request['dba'], $topic['name'], $forum);
		
		/* Set if this breadcrumb should be 'new' or not */
		$new					= topic_icon($cookieinfo, $topic, '');
		$request['template']->setVar('breadcrumb_new', ($new == TRUE ? 'new' : ''));

		/**
		 * Now tell the cookies that we've read this topic
		 */		
		$cookieinfo[$topic['post_id']] = time();
		$cookiestr						= '';
		
		foreach($cookieinfo as $key => $val) {
			// make sure to weed out 30-day old topic views
			if( ((time() - intval($val)) / 30) <= 2592000 )
				$cookiestr					.= ','. $key .','. intval($val);
		}
		
		setcookie(K4TOPICINFO, trim($cookiestr, ','), time() + 2592000, $domain);		
		unset($cookieinfo, $cookiestr);
				
		/** 
		 * Get the users Browsing this topic 
		 */
		/* Set the extra SQL query fields to check */
		$extra				= " AND location_file = '". $request['dba']->quote($_URL->file) ."' AND location_id = ". intval($topic['post_id']);	
		$expired			= time() - ini_get('session.gc_maxlifetime');
		$user_extra			= $request['user']->isMember() ? ' OR (seen > 0 AND user_id = '. intval($request['user']->get('id')) .')' : '';

		$num_online_total	= $request['dba']->getValue("SELECT COUNT(id) FROM ". K4SESSIONS ." WHERE ((seen >= $expired $extra) $user_extra)");
		$num_online_total	= !$request['user']->isMember() ? $num_online_total+1 : $num_online_total;

		if($num_online_total > 0) {
			
			$query				= "SELECT * FROM ". K4SESSIONS ." WHERE ((seen >= $expired $extra) $user_extra) AND ((user_id > 0) OR (user_id = 0 AND name <> '')) GROUP BY name ORDER BY seen DESC";
			$users_browsing		= &new K4OnlineUsersIterator($request['dba'], '', $request['dba']->executeQuery($query));
		
			/* Set the users browsing list */
			$request['template']->setList('users_browsing', $users_browsing);

			$stats = array('num_online_members'	=> Globals::getGlobal('num_online_members'),
							'num_invisible'		=> Globals::getGlobal('num_online_invisible'),
							'num_online_total'	=> $num_online_total,
							);
		
			$stats['num_guests'] = ($stats['num_online_total'] - $stats['num_online_members'] - $stats['num_invisible']);

			$request['template']->setVar('num_online_members',	$stats['num_online_members']);
			$request['template']->setVar('users_browsing',		$request['template']->getVar('L_USERSBROWSINGTOPIC'));
			$request['template']->setVar('online_stats',		sprintf($request['template']->getVar('L_USERSBROWSINGSTATS'), $stats['num_online_total'], $stats['num_online_members'], $stats['num_guests'], $stats['num_invisible']));
		
			/* Set the User's Browsing file */
			$request['template']->setFile('users_browsing', 'users_browsing.html');
		
			$groups				= array();

			/* Set the usergroups legend list */
			foreach($_USERGROUPS as $group) {
				if($group['display_legend'] == 1)
					$groups[]	= $group;
			}

			$groups				= &new FAArrayIterator($groups);
			$request['template']->setList('usergroups_legend', $groups);
		}
		
		/**
		 * Is this topic expired?
		 */
		$extra						= '';
		if($topic['post_type'] > TOPIC_NORMAL && $topic['post_expire'] > 0) {
			if(($topic['created'] + (3600 * 24 * $topic['post_expire']) ) > time()) {
				
				$extra				= ",post_expire=0,post_type=". TOPIC_NORMAL;
			}
		}
		
		/* Add the topic info to the template */
		foreach($topic as $key => $val)
			$request['template']->setVar('topic_'. $key, $val);
		
		/* Add the forum info to the template */
		foreach($forum as $key => $val)
			$request['template']->setVar('forum_'. $key, $val);

		/* Update the number of views for this topic */
		$request['dba']->executeUpdate("UPDATE ". K4POSTS ." SET views=views+1 $extra WHERE post_id=". intval($topic['post_id']));
		
		$resultsperpage		= $request['user']->get('postsperpage') <= 0 ? $forum['postsperpage'] : $request['user']->get('postsperpage');
		$num_results		= $topic['num_replies'];

		$perpage			= isset($_REQUEST['limit']) && ctype_digit($_REQUEST['limit']) && intval($_REQUEST['limit']) > 0 ? intval($_REQUEST['limit']) : $resultsperpage;
		$perpage			= $perpage > 50 ? 50 : $perpage;
		$num_pages			= @ceil($num_results / $perpage);
		
		// the $page is set above so that the archive options can use it ;)
		
		$request['template']->setVar('page', $page);

		$url				= &new FAUrl($_URL->__toString());

		$pager				= &new FAPaginator($url, $num_results, $page, $perpage);
		
		if($num_results > $perpage) {
			$request['template']->setPager('replies_pager', $pager);

			/* Create a friendly url for our pager jump */
			$page_jumper	= $url;
			$page_jumper->args['limit'] = $perpage;
			$page_jumper->args['page']	= FALSE;
			$page_jumper->anchor		= FALSE;
			$request['template']->setVar('pagejumper_url', preg_replace('~&amp;~i', '&', $page_jumper->__toString()));
		}
		
		/* Outside valid page range, redirect */
		if(!$pager->hasPage($page) && $num_pages > 0) {
			$action = new K4InformationAction(new K4LanguageElement('L_PASTPAGELIMIT'), 'content', FALSE, 'viewtopic.php?id='. $topic['post_id'] .'&limit='. $perpage .'&page='. $num_pages, 3);
			return $action->execute($request);
		}
		
		$sort_orders				= array('name','created','id','poster_name');

		/* Get the replies for this topic */
		$topic['daysprune']			= isset($_REQUEST['daysprune']) && ctype_digit($_REQUEST['daysprune']) ? iif(($_REQUEST['daysprune'] == -1), 0, intval($_REQUEST['daysprune'])) : 0;
		$topic['sortorder']			= isset($_REQUEST['order']) && ($_REQUEST['order'] == 'ASC' || $_REQUEST['order'] == 'DESC') ? $_REQUEST['order'] : 'ASC';
		$topic['sortedby']			= isset($_REQUEST['sort']) && in_array($_REQUEST['sort'], $sort_orders) ? $_REQUEST['sort'] : 'created';
		$topic['start']				= ($page - 1) * $perpage;
		$topic['postsperpage']		= $perpage;
		
		/* Do we set the similar topics? */
		$similar_topics					= $request['dba']->executeQuery("SELECT * FROM ". K4POSTS ." WHERE ((lower(name) LIKE lower('%". $request['dba']->quote($topic['name']) ."%') OR lower(name) LIKE lower('%". $request['dba']->quote($topic['body_text']) ."%')) OR (lower(body_text) LIKE lower('%". $request['dba']->quote($topic['name']) ."%') OR lower(body_text) LIKE lower('%". $request['dba']->quote($topic['body_text']) ."%'))) AND row_type=". TOPIC ." AND is_draft = 0 AND post_id <> ". intval($topic['post_id']) ." ORDER BY lastpost_created DESC LIMIT 10");
		
		if($similar_topics->hasNext()) {
			//$it = new PostsIterator($request, $similar_topics);
			$it = &new TopicsIterator($request['dba'], $request['user'], $similar_topics, $request['template']->getVar('IMG_DIR'), $forum);
			$request['template']->setList('similar_topics', $it);
			$request['template']->setFile('similar_topics', 'similar_topics.html');
		}
		
		/* Do we show the replies or show the threaded view? */
		$show_replies = $request['user']->get('topic_threaded') == 1 ? FALSE : TRUE;
		$show_replies = $request['user']->get('topic_threaded') == 1 && isset($_REQUEST['p']) && intval($_REQUEST['p']) > 0 ? TRUE : $show_replies;
		$single_reply = $request['user']->get('topic_threaded') == 1 && isset($_REQUEST['p']) && intval($_REQUEST['p']) > 0 ? intval($_REQUEST['p']) : FALSE;

		/* set the topic iterator */
		//$topic_list			= new TopicIterator($request['dba'], $request['user'], $topic, $show_replies, $single_reply);
		$result = $request['dba']->executeQuery("SELECT * FROM ". K4POSTS ." WHERE (". ($page <= 1 ? "post_id=". $topic['post_id'] ." OR" : '') ." (parent_id=". intval($topic['post_id']) ." AND row_level>1)) AND created >= ". (3600 * 24 * intval($topic['daysprune'])) ." ORDER BY ". $topic['sortedby'] ." ". $topic['sortorder'] ." LIMIT ". intval($topic['start']) .",". intval($topic['postsperpage']));
		$posts = new PostsIterator($request, $result);
		$request['template']->setList('posts', $posts);
		
		$request['template']->setVar('next_oldest', intval($request['dba']->getValue("SELECT post_id FROM ". K4POSTS ." WHERE post_id < ". $topic['post_id'] ." LIMIT 1")));
		$request['template']->setVar('next_newest',intval($request['dba']->getValue("SELECT post_id FROM ". K4POSTS ." WHERE post_id > ". $topic['post_id'] ." LIMIT 1")));
		
		/* Show the threaded view if necessary */
		if($request['user']->get('topic_threaded') == 1) {
			if($topic['num_replies'] > 0) {
				$request['template']->setFile('topic_threaded', 'topic_threaded.html');
				
				$replies	= $request['dba']->executeQuery("SELECT * FROM ". K4POSTS ." WHERE parent_id=". intval($topic['post_id']) ." AND row_level>1 ORDER BY row_order ASC");
				$it			= &new ThreadedRepliesIterator($replies, $topic['row_level']);
				
				$request['template']->setList('threaded_replies', $it);
			}
		}

		/**
		 * Topic subscription stuff
		 */
		if($request['user']->isMember()) {
			$subscribed		= $request['dba']->executeQuery("SELECT * FROM ". K4SUBSCRIPTIONS ." WHERE post_id = ". intval($topic['post_id']) ." AND user_id = ". $request['user']->get('id'));
			$request['template']->setVar('is_subscribed', iif($subscribed->numRows() > 0, 1, 0));
		}
		
		/**
		 * HTML toggling stuff
		 */
		$topic_row				= 0;
		$reply_row				= 0;
		$perms					= $request['user']->get('perms');
		if( ($perms >= get_map('replies','can_add',array('forum_id'=>$topic['forum_id'])))
			|| ($perms >= get_map('topics','can_edit',array('forum_id'=>$topic['forum_id'])))
			|| ($perms >= get_map('topics','can_del',array('forum_id'=>$topic['forum_id'])))
			|| ($perms >= get_map('other_topics','can_edit',array('forum_id'=>$topic['forum_id'])))
			|| ($perms >= get_map('other_topics','can_del',array('forum_id'=>$topic['forum_id'])))
			) {
			$topic_row			= 1;
		}
		if( ($perms >= get_map('replies','can_add',array('forum_id'=>$topic['forum_id'])))
			|| ($perms >= get_map('replies','can_edit',array('forum_id'=>$topic['forum_id'])))
			|| ($perms >= get_map('replies','can_del',array('forum_id'=>$topic['forum_id'])))
			|| ($perms >= get_map('other_replies','can_edit',array('forum_id'=>$topic['forum_id'])))
			|| ($perms >= get_map('other_replies','can_del',array('forum_id'=>$topic['forum_id'])))
			) {
			$reply_row			= 1;
		}
		
		$request['template']->setVar('topic_row', $topic_row);
		$request['template']->setVar('reply_row', $reply_row);
		
		$request['template']->setVar('newreply_act', K4Url::getGenUrl('newreply', 'act=postreply'));
		$request['template']->setVar('U_TOPICRSSURL', K4Url::getGenUrl('rss', 't='. $topic['post_id']));
		
		/**
		 * Topic display
		 */
		$request['template']->setFile('topic_file', 'topic'. ($request['user']->get('topic_display') == 0 ? '' : '_linear') .'.html');
		$request['template']->setFile('reply_file', 'reply'. ($request['user']->get('topic_display') == 0 ? '' : '_linear') .'.html');
		
		/* Set the file we need */
		$request['template']->setVar('forum_forum_id', $forum['forum_id']);
		$request['template']->setFile('content', 'viewtopic.html');
		
		if(USE_WYSIWYG) {
			$request['template']->setList('emoticons', $request['dba']->executeQuery("SELECT * FROM ". K4EMOTICONS ." WHERE clickable = 1"));
			$request['template']->setVar('emoticons_per_row', $request['template']->getVar('smcolumns'));
			$request['template']->setVar('emoticons_per_row_remainder', $request['template']->getVar('smcolumns')-1);
		}

		/* Create our editor for the quick reply */
		create_editor($request, '', 'quickreply', $forum);
		
		// show the midsection of the forum
		$request['template']->setVisibility('forum_midsection', TRUE);

		return TRUE;
	}
}

class ChangeTopicView extends FAAction {
	function execute(&$request) {
		
		/* Create the ancestors bar */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
		
		$mode			= 0;
		$modes			= array('threaded', 'linear', 'vertical', 'normal');
		$mode_var		= 'topic_display';

		if(isset($_REQUEST['mode']) && in_array($_REQUEST['mode'], $modes)) {
			
			if($_REQUEST['mode'] == 'linear' || $_REQUEST['mode'] == 'threaded') {
				$mode	= 1;
			}

			if($_REQUEST['mode'] == 'threaded' || $_REQUEST['mode'] == 'normal') {
				$mode_var = 'topic_threaded';
			}
			
			if($request['user']->isMember()) {
				if($request['user']->get($mode_var) != $mode) {
					$request['dba']->executeUpdate("UPDATE ". K4USERSETTINGS ." SET $mode_var=$mode WHERE user_id = ". intval($request['user']->get('id')));
				}
			}
		}
		
		$action = new K4InformationAction(new K4LanguageElement('L_UPDATEDTOPICVIEWS'), 'content', TRUE, referer(), 3);
		return $action->execute($request);
	}
}


$app = new K4controller('forum_base.html');

$app->setAction('', new K4DefaultAction);
$app->setDefaultEvent('');

$app->setAction('track', new SubscribeTopic);
$app->setAction('untrack', new UnsubscribeTopic);
$app->setAction('rate_topic', new RateTopic);
$app->setAction('changeview', new ChangeTopicView);
$app->setAction('report_post', new ReportBadPost);
$app->setAction('send_report', new SendBadPostReport);

$app->execute();

?>