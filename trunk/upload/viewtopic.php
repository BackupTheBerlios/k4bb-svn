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
* @version $Id: viewtopic.php,v 1.13 2005/05/26 18:34:54 k4st Exp $
* @package k42
*/

require "includes/filearts/filearts.php";
require "includes/k4bb/k4bb.php";

class K4DefaultAction extends FAAction {
	function execute(&$request) {
		
		global $_QUERYPARAMS, $_USERGROUPS, $_URL;
		
		/**
		 * Error Checking
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
		
		if($topic['is_draft'] == 1) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], &$request['dba'], 'L_INVALIDTOPICVIEW');
			
			$action = new K4InformationAction(new K4LanguageElement('L_CANTVIEWDRAFT'), 'content', FALSE);
			return $action->execute($request);
		}

		if($topic['queue'] == 1) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], &$request['dba'], 'L_INVALIDTOPICVIEW');
			
			$action = new K4InformationAction(new K4LanguageElement('L_TOPICPENDINGMOD'), 'content', FALSE);
			return $action->execute($request);
		}

		if($topic['display'] == 0) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], &$request['dba'], 'L_INVALIDTOPICVIEW');
			
			$action = new K4InformationAction(new K4LanguageElement('L_TOPICISHIDDEN'), 'content', FALSE);
			return $action->execute($request);
		}

		/* Get the current forum */
		$forum				= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['forum'] ." FROM ". K4FORUMS ." f LEFT JOIN ". K4INFO ." i ON f.forum_id = i.id WHERE i.id = ". intval($topic['forum_id']));

		if(!$forum || !is_array($forum) || empty($forum)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], &$request['dba'], 'L_INVALIDFORUM');
			
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}

		if(get_map($request['user'], 'forums', 'can_view', array()) > $request['user']->get('perms') || get_map($request['user'], 'topics', 'can_view', array('forum_id'=>$forum['id'])) > $request['user']->get('perms')) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], &$request['dba'], 'L_INFORMATION', $forum);
			
			$action = new K4InformationAction(new K4LanguageElement('L_PERMCANTVIEWTOPIC'), 'content', FALSE);
			return $action->execute($request);
		}
		
		/**
		 * Set the new breadcrumbs bit
		 */
		k4_bread_crumbs(&$request['template'], &$request['dba'], $topic['name'], iif($topic['topic_type'] == TOPIC_GLOBAL, FALSE, $forum));

		/** 
		 * Get the users Browsing this topic 
		 */
		/* Set the extra SQL query fields to check */
		$extra				= " AND s.location_file = '". $request['dba']->Quote($_URL->file) ."' AND s.location_id = ". intval($topic['id']);	
		
		$expired			= time() - ini_get('session.gc_maxlifetime');

		$num_online_total	= $request['dba']->getValue("SELECT COUNT(s.id) FROM ". K4SESSIONS ." s WHERE s.seen >= $expired $extra");
		
		if($num_online_total > 0) {

			$users_browsing		= &new K4OnlineUsersIterator($request['dba'], $extra);
		
			/* Set the users browsing list */
			$request['template']->setList('users_browsing', $users_browsing);

			$stats = array('num_online_members'	=> Globals::getGlobal('num_online_members'),
							'num_invisible'		=> Globals::getGlobal('num_online_invisible'),
							'num_online_total'	=> $num_online_total,
							);
		
			$stats['num_guests'] = ($stats['num_online_total'] - $stats['num_online_members'] - $stats['num_invisible']);

			$request['template']->setVar('num_online_members', $stats['num_online_members']);
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

		/* Update the number of views for this topic */
		$request['dba']->executeUpdate("UPDATE ". K4TOPICS ." SET views=views+1 $extra WHERE topic_id=". intval($topic['id']));
		
		$resultsperpage		= $forum['postsperpage'];
		$num_results		= @(($topic['row_right'] - $topic['row_left'] - 1) / 2);

		$perpage			= isset($_REQUEST['limit']) && ctype_digit($_REQUEST['limit']) && intval($_REQUEST['limit']) > 0 ? intval($_REQUEST['limit']) : $resultsperpage;
		$num_pages			= ceil($num_results / $perpage);
		$page				= isset($_REQUEST['page']) && ctype_digit($_REQUEST['page']) && intval($_REQUEST['page']) > 0 ? intval($_REQUEST['page']) : 1;
		$pager				= &new FAPaginator($_URL, $num_results, $page, $perpage);
		
		if($num_results > $perpage) {
			$request['template']->setPager('replies_pager', $pager);
		}
		
		/* Outside valid page range, redirect */
		if(!$pager->hasPage($page) && $num_results > $resultsperpage) {
			$action = new K4InformationAction(new K4LanguageElement('L_PASTPAGELIMIT'), 'content', FALSE, 'viewtopic.php?id='. $topic['id'] .'&limit='. $perpage .'&page='. $num_pages, 3);
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
		$result						= &$request['dba']->executeQuery("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['topic'] ." FROM ". K4TOPICS ." t LEFT JOIN ". K4INFO ." i ON t.topic_id = i.id WHERE ((lower(i.name) LIKE lower('%". $request['dba']->quote($topic['name']) ."%') OR lower(i.name) LIKE lower('%". $request['dba']->quote($topic['body_text']) ."%')) OR (lower(t.body_text) LIKE lower('%". $request['dba']->quote($topic['name']) ."%') OR lower(t.body_text) LIKE lower('%". $request['dba']->quote($topic['body_text']) ."%'))) AND t.is_draft = 0 AND i.id <> ". intval($topic['id']));
		
		if($result->numrows() > 0) {
			$it							= &new TopicsIterator($request['dba'], $request['user'], $result, $request['template']->getVar('IMG_DIR'), $forum);
			$request['template']->setList('similar_topics', $it);
			$request['template']->setFile('similar_topics', 'similar_topics.html');
		}

		/* set the topic iterator */
		$topic_list					= &new TopicIterator($request['dba'], $request['user'], $topic, TRUE);
		$request['template']->setList('topic', $topic_list);
		
		$request['template']->setVar('next_oldest', intval($request['dba']->getValue("SELECT id FROM ". K4INFO ." WHERE id < ". $topic['id'] ." AND row_type = ". TOPIC ." LIMIT 1")));
		$request['template']->setVar('next_newest',intval($request['dba']->getValue("SELECT id FROM ". K4INFO ." WHERE id > ". $topic['id'] ." AND row_type = ". TOPIC ." LIMIT 1")));
		
		/* Set the file we need */
		$request['template']->setFile('content', 'viewtopic.html');

		return TRUE;
	}
}

$app = new K4controller('forum_base.html');

$app->setAction('', new K4DefaultAction);
$app->setDefaultEvent('');

$app->setAction('track', new SubscribeTopic);
$app->setAction('untrack', new UnsubscribeTopic);
//$app->setAction('markforums', new MarkCategoryForumsRead);

$app->execute();

?>