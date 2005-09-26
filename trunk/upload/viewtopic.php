<?php
/**
* k4 Bulletin Board, viewtopic.php
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
		$topic				= $request['dba']->getRow("SELECT * FROM ". K4TOPICS ." WHERE topic_id = ". intval($_REQUEST['id']));
		
		if(!$topic || !is_array($topic) || empty($topic)) {
			$action = new K4InformationAction(new K4LanguageElement('L_TOPICDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}
		
		/* Should we redirect this user? */
		if($topic['moved_new_topic_id'] > 0) {
			header("Location: viewtopic.php?id=". intval($topic['moved_new_topic_id']));
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
		
		k4_bread_crumbs($request['template'], $request['dba'], $topic['name'], $forum);
		
		/* Set if this breadcrumb should be 'new' or not */
		$new					= topic_icon($cookieinfo, $topic, '');
		$request['template']->setVar('breadcrumb_new', ($new == TRUE ? 'new' : ''));

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
		
		if(get_map($request['user'], 'forums', 'can_view', array()) > $request['user']->get('perms') || get_map($request['user'], 'topics', 'can_view', array('forum_id'=>$forum['forum_id'])) > $request['user']->get('perms')) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION', $forum);
			
			$action = new K4InformationAction(new K4LanguageElement('L_PERMCANTVIEWTOPIC'), 'content', FALSE);
			return $action->execute($request);
		}
		
		/** 
		 * Get the users Browsing this topic 
		 */
		/* Set the extra SQL query fields to check */
		$extra				= " AND location_file = '". $request['dba']->quote($_URL->file) ."' AND location_id = ". intval($topic['topic_id']);	
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
		

		$resultsperpage		= $request['user']->get('postsperpage') <= 0 ? $forum['postsperpage'] : $request['user']->get('postsperpage');
		$num_results		= $topic['num_replies'];

		$perpage			= isset($_REQUEST['limit']) && ctype_digit($_REQUEST['limit']) && intval($_REQUEST['limit']) > 0 ? intval($_REQUEST['limit']) : $resultsperpage;
		$num_pages			= @ceil($num_results / $perpage);
		$page				= isset($_REQUEST['page']) && ctype_digit($_REQUEST['page']) && intval($_REQUEST['page']) > 0 ? intval($_REQUEST['page']) : 1;
		
		$request['template']->setVar('page', $page);
		
		$pager				= &new FAPaginator(new FAUrl($_URL->__toString()), $num_results, $page, $perpage);
		
		if($num_results > $perpage) {
			$request['template']->setPager('replies_pager', $pager);

			/* Create a friendly url for our pager jump */
			$page_jumper	= new FAUrl($_URL->__toString());
			$page_jumper->args['limit'] = $perpage;
			$page_jumper->args['page']	= FALSE;
			$page_jumper->anchor		= FALSE;
			$request['template']->setVar('pagejumper_url', preg_replace('~&amp;~i', '&', $page_jumper->__toString()));
		}
		
		/* Outside valid page range, redirect */
		if(!$pager->hasPage($page) && $num_pages > 0) {
			$action = new K4InformationAction(new K4LanguageElement('L_PASTPAGELIMIT'), 'content', FALSE, 'viewtopic.php?id='. $topic['topic_id'] .'&limit='. $perpage .'&page='. $num_pages, 3);
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
		$similar_topics					= &$request['dba']->executeQuery("SELECT * FROM ". K4TOPICS ." WHERE ((lower(name) LIKE lower('%". $request['dba']->quote($topic['name']) ."%') OR lower(name) LIKE lower('%". $request['dba']->quote($topic['body_text']) ."%')) OR (lower(body_text) LIKE lower('%". $request['dba']->quote($topic['name']) ."%') OR lower(body_text) LIKE lower('%". $request['dba']->quote($topic['body_text']) ."%'))) AND is_draft = 0 AND topic_id <> ". intval($topic['topic_id']) ." ORDER BY last_post DESC LIMIT 10");
		
		if($similar_topics->numrows() > 0) {
			$it							= &new TopicsIterator($request['dba'], $request['user'], $similar_topics, $request['template']->getVar('IMG_DIR'), $forum);
			$request['template']->setList('similar_topics', $it);
			$request['template']->setFile('similar_topics', 'similar_topics.html');
		}
		
		/* set the topic iterator */
		$topic_list					= &new TopicIterator($request['dba'], $request['user'], $topic, TRUE);
		$request['template']->setList('topic', $topic_list);
		
		$request['template']->setVar('next_oldest', intval($request['dba']->getValue("SELECT topic_id FROM ". K4TOPICS ." WHERE topic_id < ". $topic['topic_id'] ." LIMIT 1")));
		$request['template']->setVar('next_newest',intval($request['dba']->getValue("SELECT topic_id FROM ". K4TOPICS ." WHERE topic_id > ". $topic['topic_id'] ." LIMIT 1")));
		
		/**
		 * Topic subscription stuff
		 */
		if($request['user']->isMember()) {
			$subscribed					= $request['dba']->executeQuery("SELECT * FROM ". K4SUBSCRIPTIONS ." WHERE topic_id = ". intval($topic['topic_id']) ." AND user_id = ". $request['user']->get('id'));
			$request['template']->setVar('is_subscribed', iif($subscribed->numRows() > 0, 1, 0));
		}
		
		/**
		 * HTML toggling stuff
		 */
		$topic_row				= 0;
		$reply_row				= 0;
		$perms					= $request['user']->get('perms');
		$user					= $request['user'];
		if( ($perms >= get_map($user,'replies','can_add',array('forum_id'=>$topic['forum_id'])))
			|| ($perms >= get_map($user,'topics','can_edit',array('forum_id'=>$topic['forum_id'])))
			|| ($perms >= get_map($user,'topics','can_del',array('forum_id'=>$topic['forum_id'])))
			|| ($perms >= get_map($user,'other_topics','can_edit',array('forum_id'=>$topic['forum_id'])))
			|| ($perms >= get_map($user,'other_topics','can_del',array('forum_id'=>$topic['forum_id'])))
			) {
			$topic_row			= 1;
		}
		if( ($perms >= get_map($user,'replies','can_add',array('forum_id'=>$topic['forum_id'])))
			|| ($perms >= get_map($user,'replies','can_edit',array('forum_id'=>$topic['forum_id'])))
			|| ($perms >= get_map($user,'replies','can_del',array('forum_id'=>$topic['forum_id'])))
			|| ($perms >= get_map($user,'other_replies','can_edit',array('forum_id'=>$topic['forum_id'])))
			|| ($perms >= get_map($user,'other_replies','can_del',array('forum_id'=>$topic['forum_id'])))
			) {
			$reply_row			= 1;
		}
		
		$request['template']->setVar('topic_row', $topic_row);
		$request['template']->setVar('reply_row', $reply_row);
		
		$request['template']->setVar('newreply_act', 'newreply.php?act=postreply');
		
		/**
		 * Topic display
		 */
		if($request['user']->get('topic_display') == 0) {
			$request['template']->setFile('topic_file', 'topic.html');
			$request['template']->setFile('reply_file', 'reply.html');
		} else {
			$request['template']->setFile('topic_file', 'topic_linear.html');
			$request['template']->setFile('reply_file', 'reply_linear.html');
		}
		
		/* Set the file we need */
		$request['template']->setVar('forum_forum_id', $forum['forum_id']);
		$request['template']->setFile('content', 'viewtopic.html');
		
		if(USE_WYSIWYG) {
			$request['template']->setList('emoticons', $request['dba']->executeQuery("SELECT * FROM ". K4EMOTICONS ." WHERE clickable = 1"));
			$request['template']->setVar('emoticons_per_row', $request['template']->getVar('smcolumns'));
			$request['template']->setVar('emoticons_per_row_remainder', $request['template']->getVar('smcolumns')-1);
		}
		/* Create our editor for the quick reply */
		create_editor($request, '', 'post', $forum);
		
		return TRUE;
	}
}

class ChangeTopicView extends FAAction {
	function execute(&$request) {
		
		/* Create the ancestors bar */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
		
		$mode						= 0;
		
		if(isset($_REQUEST['mode']) && ($_REQUEST['mode'] == 'linear' || $_REQUEST['mode'] == 'vertical')) {
			
			if($_REQUEST['mode'] == 'linear')
				$mode				= 1;
			
			if($request['user']->isMember()) {
				if($request['user']->get('topic_display') != $mode)
					$request['dba']->executeUpdate("UPDATE ". K4USERSETTINGS ." SET topic_display=". intval($mode) ." WHERE user_id = ". intval($request['user']->get('id')));
			}

			/* Make sure to change the information in the $request */
			$user				= &new K4UserManager($request['dba']);
			$user				= $user->getInfo($request['user']->get('id'));
			$request['user']	= &new K4Member($user);
			$_SESSION['user']	= &new K4Member($user);
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