<?php
/**
* k4 Bulletin Board, viewforum.php
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
* @version $Id: viewforum.php,v 1.19 2005/05/26 18:34:54 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

require "includes/filearts/filearts.php";
require "includes/k4bb/k4bb.php";

class K4DefaultAction extends FAAction {
	function execute(&$request) {
		
		global $_QUERYPARAMS, $_USERGROUPS, $_ALLFORUMS, $_URL;

		if(!isset($_REQUEST['id']) || !$_REQUEST['id'] || intval($_REQUEST['id']) == 0) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], &$request['dba'], 'L_INVALIDFORUM');
			
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}
			
		/* Get the current forum/category */
		$forum					= $_ALLFORUMS[$_REQUEST['id']];
		$query					= $forum['row_type'] & FORUM ? "SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['forum'] ." FROM ". K4FORUMS ." f LEFT JOIN ". K4INFO ." i ON f.forum_id = i.id WHERE i.id = ". intval($_REQUEST['id']) : "SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['category'] ." FROM ". K4CATEGORIES ." c LEFT JOIN ". K4INFO ." i ON c.category_id = i.id WHERE i.id = ". intval($_REQUEST['id']);
		$forum					= $request['dba']->getRow($query);

		if(!$forum || !is_array($forum) || empty($forum)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], &$request['dba'], 'L_INVALIDFORUM');
			
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}

		if($forum['row_type'] == FORUM && @$forum['is_link'] == 1) {
			k4_bread_crumbs(&$request['template'], &$request['dba'], 'L_INFORMATION', $forum);
			
			if($forum['link_show_redirects'] == 1) {
				$action = new K4InformationAction(new K4LanguageElement('L_REDIRECTING'), 'content', FALSE, 'redirect.php?id='. $forum['id'], 3);
			} else {
				$action = new K4InformationAction(new K4LanguageElement('L_REDIRECTING'), 'content', FALSE, $forum['link_href'], 3);
			}

			return $action->execute($request);
		}
			
		/* Set the extra SQL query fields to check */
		$extra				= " AND s.location_file = '". $request['dba']->Quote($_URL->file) ."' AND s.location_id = ". intval($forum['id']);	
		
		$forum_can_view		= $forum['row_type'] & CATEGORY ? get_map($request['user'], 'categories', 'can_view', array()) : get_map($request['user'], 'forums', 'can_view', array());
		
		$expired			= time() - ini_get('session.gc_maxlifetime');

		$num_online_total	= $request['dba']->getValue("SELECT COUNT(s.id) as num_online_total FROM ". K4SESSIONS ." s WHERE s.seen >= $expired $extra");
				
		/* If there are more than 0 people browsing the forum, display the stats */
		if($num_online_total > 0 && $forum_can_view <= $request['user']->get('perms') && ($forum['row_type'] & CATEGORY || $forum['row_type'] & FORUM)) {

			$users_browsing						= &new K4OnlineUsersIterator($request['dba'], $extra);
		
			/* Set the users browsing list */
			$request['template']->setList('users_browsing', $users_browsing);

			$stats = array('num_online_members'	=> Globals::getGlobal('num_online_members'),
							'num_invisible'		=> Globals::getGlobal('num_online_invisible'),
							'num_online_total'	=> $num_online_total
							);
			
			$stats['num_guests']	= ($stats['num_online_total'] - $stats['num_online_members'] - $stats['num_invisible']);

			$element				= $forum['row_type'] & CATEGORY ? 'L_USERSBROWSINGCAT' : 'L_USERSBROWSINGFORUM';
					
			$request['template']->setVar('num_online_members',	$stats['num_online_members']);
			$request['template']->setVar('users_browsing',		$request['template']->getVar($element));
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

		if($forum_can_view > $request['user']->get('perms')) {
			
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], &$request['dba'], 'L_INFORMATION', $forum);
			
			$action = new K4InformationAction(new K4LanguageElement('L_PERMCANTVIEW'), 'content', FALSE);
			return $action->execute($request);
		}

		/* Set the breadcrumbs bit */
		k4_bread_crumbs(&$request['template'], &$request['dba'], NULL, $forum);
		
		/* Set all of the category/forum info to the template */
		$request['template']->setVarArray($forum);

		/* If we are looking at a category */
		if($forum['row_type'] & CATEGORY) {
			
			if(get_map($request['user'], 'categories', 'can_view', array()) > $request['user']->get('perms')) {
				
				/* set the breadcrumbs bit */
				k4_bread_crumbs(&$request['template'], &$request['dba'], 'L_INFORMATION', $forum);
				
				$action = new K4InformationAction(new K4LanguageElement('L_PERMCANTVIEW'), 'content', FALSE);
				return $action->execute($request);
			}

			/* Set the proper query params */
			$query_params	= $_QUERYPARAMS['info'] . $_QUERYPARAMS['category'];

			/* Set the Categories list */
			$categories = &new K4CategoriesIterator($request['dba'], "SELECT $query_params FROM ". K4INFO ." i LEFT JOIN ". K4CATEGORIES ." c ON c.category_id = i.id WHERE i.row_type = ". CATEGORY ." AND i.row_left = ". $forum['row_left'] ." AND i.row_right = ". $forum['row_right'] ." AND i.id = ". $forum['id'] ." ORDER BY i.row_order ASC");
			$request['template']->setList('categories', $categories);

			/* Hide the welcome message at the top of the forums.html template */
			$request['template']->setVisibility('welcome_msg', FALSE);
			
			/* Show the forum status icons */
			$request['template']->setVisibility('forum_status_icons', TRUE);

			/* Show the 'Mark these forums Read' link */
			$request['template']->setVisibility('mark_these_forums', TRUE);
			
			/* Set the forums template to content variable */
			$request['template']->setFile('content', 'forums.html');
		
		/* If we are looking at a forum */
		} else if($forum['row_type'] & FORUM) {						
			
			/* Add the forum info to the template */
			foreach($forum as $key => $val)
				$request['template']->setVar('forum_'. $key, $val);
			
			/* If this forum has sub-forums */
			if( (isset_forum_cache_item('subforums', $forum['id']) && $forum['subforums'] == 1)) {
				
				/* Cache this forum as having subforums */
				set_forum_cache_item('subforums', 1, $forum['id']);
				
				/* Show the table that holds the subforums */
				$request['template']->setVisibility('subforums', TRUE);
				
				/* Set the proper query params */
				$query_params	= $_QUERYPARAMS['info'] . $_QUERYPARAMS['forum'];
				
				/* Set the sub-forums list */
				$request['template']->setList('subforums', new K4ForumsIterator($request['dba'], "SELECT $query_params FROM ". K4INFO ." i LEFT JOIN ". K4FORUMS ." f ON f.forum_id = i.id WHERE i.row_left > ". $forum['row_left'] ." AND i.row_right < ". $forum['row_right'] ." AND i.row_type = ". FORUM ." AND i.parent_id = ". $forum['id'] ." ORDER BY i.row_order ASC"));
				$request['template']->setFile('content', 'subforums.html');
			}

			if(get_map($request['user'], 'topics', 'can_view', array('forum_id'=>$forum['id'])) > $request['user']->get('perms')) {
				
				/* set the breadcrumbs bit */
				k4_bread_crumbs(&$request['template'], &$request['dba'], 'L_INFORMATION', $forum);
				
				$action = new K4InformationAction(new K4LanguageElement('L_CANTVIEWFORUMTOPICS'), 'content_extra', FALSE);
				return $action->execute($request);
			}
			
			
			/**
			 * Forum settings
			 */

			/* Set the topics template to the content variable */
			$request['template']->setFile('content_extra', 'topics.html');
			
			/* Set what this user can/cannot do in this forum */
			$request['template']->setVar('forum_user_topic_options', sprintf($request['template']->getVar('L_FORUMUSERTOPICPERMS'),
			iif((get_map($request['user'], 'topics', 'can_add', array('forum_id'=>$forum['id'])) > $request['user']->get('perms')), $request['template']->getVar('L_CANNOT'), $request['template']->getVar('L_CAN')),
			iif((get_map($request['user'], 'topics', 'can_edit', array('forum_id'=>$forum['id'])) > $request['user']->get('perms')), $request['template']->getVar('L_CANNOT'), $request['template']->getVar('L_CAN')),
			iif((get_map($request['user'], 'topics', 'can_del', array('forum_id'=>$forum['id'])) > $request['user']->get('perms')), $request['template']->getVar('L_CANNOT'), $request['template']->getVar('L_CAN')),
			iif((get_map($request['user'], 'attachments', 'can_add', array('forum_id'=>$forum['id'])) > $request['user']->get('perms')), $request['template']->getVar('L_CANNOT'), $request['template']->getVar('L_CAN'))));

			$request['template']->setVar('forum_user_reply_options', sprintf($request['template']->getVar('L_FORUMUSERREPLYPERMS'),
			iif((get_map($request['user'], 'replies', 'can_add', array('forum_id'=>$forum['id'])) > $request['user']->get('perms')), $request['template']->getVar('L_CANNOT'), $request['template']->getVar('L_CAN')),
			iif((get_map($request['user'], 'replies', 'can_edit', array('forum_id'=>$forum['id'])) > $request['user']->get('perms')), $request['template']->getVar('L_CANNOT'), $request['template']->getVar('L_CAN')),
			iif((get_map($request['user'], 'replies', 'can_del', array('forum_id'=>$forum['id'])) > $request['user']->get('perms')), $request['template']->getVar('L_CANNOT'), $request['template']->getVar('L_CAN'))));
			
			/* Create an array with all of the possible sort orders we can have */						
			$sort_orders		= array('name', 'reply_time', 'num_replies', 'views', 'reply_uname', 'rating');
			
			
			/**
			 * Pagination
			 */

			/* Create the Pagination */
			$resultsperpage		= $forum['topicsperpage'];
			$num_results		= $forum['topics'];

			$perpage			= isset($_REQUEST['limit']) && ctype_digit($_REQUEST['limit']) && intval($_REQUEST['limit']) > 0 ? intval($_REQUEST['limit']) : $resultsperpage;
			$num_pages			= ceil($num_results / $perpage);
			$page				= isset($_REQUEST['page']) && ctype_digit($_REQUEST['page']) && intval($_REQUEST['page']) > 0 ? intval($_REQUEST['page']) : 1;
			$pager				= &new FAPaginator($_URL, $num_results, $page, $perpage);
			
			if($num_results > $perpage) {
				$request['template']->setPager('topics_pager', $pager);
			}

			/* Get the topics for this forum */
			$daysprune			= isset($_REQUEST['daysprune']) && ctype_digit($_REQUEST['daysprune']) ? iif(($_REQUEST['daysprune'] == -1), 0, intval($_REQUEST['daysprune'])) : 30;
			$sortorder			= isset($_REQUEST['order']) && ($_REQUEST['order'] == 'ASC' || $_REQUEST['order'] == 'DESC') ? $_REQUEST['order'] : 'DESC';
			$sortedby			= isset($_REQUEST['sort']) && in_array($_REQUEST['sort'], $sort_orders) ? $_REQUEST['sort'] : 'created';
			$start				= ($page - 1) * $perpage;
			
			if($forum['topics'] > 0) {
				
				/**
				 * Topic Setting
				 */

				/* get the topics */
				$topics				= &$request['dba']->prepareStatement("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['topic'] ." FROM ". K4TOPICS ." t LEFT JOIN ". K4INFO ." i ON t.topic_id = i.id WHERE i.created>=? AND t.is_draft=0 AND t.queue = 0 AND t.display = 1 AND i.row_type=". TOPIC ." AND t.forum_id = ". intval($forum['id']) ." AND (t.topic_type <> ". TOPIC_GLOBAL ." AND t.topic_type <> ". TOPIC_ANNOUNCE ." AND t.topic_type <> ". TOPIC_STICKY ." AND t.is_feature = 0) ORDER BY $sortedby $sortorder LIMIT ?,?");
				
				/* Set the query values */
				$topics->setInt(1, $daysprune * (3600 * 24));
				$topics->setInt(2, $start);
				$topics->setInt(3, $perpage);
				
				/* Execute the query */
				$result				= &$topics->executeQuery();
				
				/* Apply the topics iterator */
				$it					= &new TopicsIterator($request['dba'], $request['user'], $result, $request['template']->getVar('IMG_DIR'), $forum);
				$request['template']->setList('topics', $it);
				

				/**
				 * Get announcement/global topics
				 */
				if($page == 1) {
					$announcements		= &$request['dba']->executeQuery("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['topic'] ." FROM ". K4TOPICS ." t LEFT JOIN ". K4INFO ." i ON t.topic_id = i.id WHERE t.is_draft=0 AND t.queue = 0 AND t.display = 1 AND i.row_type=". TOPIC ." AND t.forum_id = ". intval($forum['id']) ." AND (t.topic_type = ". TOPIC_GLOBAL ." OR t.topic_type = ". TOPIC_ANNOUNCE .") ORDER BY i.created DESC");
					if($announcements->numrows() > 0) {
						$a_it				= &new TopicsIterator($request['dba'], $request['user'], $announcements, $request['template']->getVar('IMG_DIR'), $forum);
						$request['template']->setList('announcements', $a_it);
					}
				}
				
				/**
				 * Get sticky/feature topics
				 */
				$importants			= &$request['dba']->executeQuery("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['topic'] ." FROM ". K4TOPICS ." t LEFT JOIN ". K4INFO ." i ON t.topic_id = i.id WHERE t.is_draft=0 AND t.queue = 0 AND t.display = 1 AND i.row_type=". TOPIC ." AND t.forum_id = ". intval($forum['id']) ." AND (t.topic_type <> ". TOPIC_GLOBAL ." AND t.topic_type <> ". TOPIC_ANNOUNCE .") AND (t.topic_type = ". TOPIC_STICKY ." OR t.is_feature = 1) ORDER BY i.created DESC");
				if($importants->numrows() > 0) {
					$i_it				= &new TopicsIterator($request['dba'], $request['user'], $importants, $request['template']->getVar('IMG_DIR'), $forum);
					$request['template']->setList('importants', $i_it);
				}
				
				/* Outside valid page range, redirect */
				if(!$pager->hasPage($page) && $num_results > $resultsperpage) {
					$action = new K4InformationAction(new K4LanguageElement('L_PASTPAGELIMIT'), 'content', FALSE, 'viewforum.php?id='. $forum['id'] .'&limit='. $perpage .'&page='. $num_pages, 3);
					return $action->execute($request);
				}
			}

			/* If there are no topics, set the right messageto display */
			if($forum['topics'] <= 0) {
				$request['template']->setVisibility('no_topics', TRUE);
				$request['template']->setVar('topics_message', iif($daysprune == 0, $request['template']->getVar('L_NOPOSTSINFORUM'), sprintf($request['template']->getVar('L_FORUMNOPOSTSSINCE'), $daysprune)));
				return TRUE;
			}
			
			/**
			 * Moderator functions
			 */
			$request['template']->setVar('modpanel', 0);

			if(is_moderator($request['user'], $forum))
				$request['template']->setVar('modpanel', 1);
			
		} else {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], &$request['dba'], 'L_INVALIDFORUM');
			
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}
				
		/* Add the cookies for this forum's topics */
		bb_execute_topiccache();

		return TRUE;
	}
}

$app = new K4controller('forum_base.html');

$app->setAction('', new K4DefaultAction);
$app->setDefaultEvent('');

$app->setAction('markforums', new MarkCategoryForumsRead);
$app->setAction('track', new SubscribeForum);
$app->setAction('untrack', new UnsubscribeForum);

$app->execute();

?>