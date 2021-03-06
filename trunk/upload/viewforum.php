<?php
/**
* k4 Bulletin Board, viewforum.php
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
* @version $Id: viewforum.php 158 2005-07-18 02:55:30Z Peter Goodman $
* @package k42
*/

require "includes/filearts/filearts.php";
require "includes/k4bb/k4bb.php";

class K4DefaultAction extends FAAction {
	function execute(&$request) {
		
		global $_QUERYPARAMS, $_USERGROUPS, $_URL;
		
		/* set the breadcrumbs bit */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');

		if((isset($_REQUEST['f']) && intval($_REQUEST['f']) != 0) || (isset($_REQUEST['c']) && intval($_REQUEST['c']) != 0)) {
			$thing = isset($_REQUEST['f']) ? 'f' : 'c';
			$forum = $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($_REQUEST[$thing]));
		} else {
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', TRUE);
			return $action->execute($request);
		}
		
		if(!$forum || !is_array($forum) || empty($forum)) {
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}

		if($forum['row_type'] & FORUM && $forum['is_link'] == 1) {
			if($forum['link_show_redirects'] == 1) {
				$action = new K4InformationAction(new K4LanguageElement('L_REDIRECTING'), 'content', FALSE, 'redirect.php?id='. $forum['forum_id'], 3);
			} else {
				$action = new K4InformationAction(new K4LanguageElement('L_REDIRECTING'), 'content', FALSE, $forum['link_href'], 3);
			}

			return $action->execute($request);
		}
			
		/* Set the extra SQL query fields to check */
		$extra				= " AND location_file = '". $request['dba']->Quote($_URL->file) ."' AND location_id = ". ($forum['row_type'] & CATEGORY ? intval($forum['category_id']) : intval($forum['forum_id']));	
		$user_extra			= $request['user']->isMember() ? ' OR (seen > 0 AND user_id = '. intval($request['user']->get('id')) .')' : '';

		$forum_can_view		= $forum['row_type'] & CATEGORY ? get_map( '', 'can_view', array('category_id' => $forum['category_id'])) : get_map( '', 'can_view', array('forum_id' => $forum['forum_id']));
		
		$expired			= time() - ini_get('session.gc_maxlifetime');

		$num_online_total	= $request['dba']->getValue("SELECT COUNT(id) FROM ". K4SESSIONS ." WHERE ((seen >= $expired $extra) $user_extra)");
		$num_online_total	= !$request['user']->isMember() ? $num_online_total+1 : $num_online_total;
				
		/* If there are more than 0 people browsing the forum, display the stats */
		if($num_online_total > 0 && $forum_can_view <= $request['user']->get('perms')) {
			
			$query				= "SELECT * FROM ". K4SESSIONS ." WHERE ((seen >= $expired $extra) $user_extra) AND ((user_id > 0) OR (user_id = 0 AND name <> '')) GROUP BY name ORDER BY seen DESC";
			$users_browsing		= &new K4OnlineUsersIterator($request['dba'], '', $request['dba']->executeQuery($query));
		
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
			$action = new K4InformationAction(new K4LanguageElement('L_PERMCANTVIEW'), 'content', FALSE);
			return $action->execute($request);
		}
		

		/**
		 * Breadcrumbs 
		 */

		/* Set the breadcrumbs bit */
		k4_bread_crumbs($request['template'], $request['dba'], NULL, $forum);

		/* Set if this breadcrumb should be 'new' or not */
		$icon			= NULL;
		$new			= $forum['row_type'] & FORUM ? forum_icon($forum, $icon) : FALSE;
		$request['template']->setVar('breadcrumb_new', ($new == TRUE ? 'new' : ''));
		
		
		/**
		 * Forum/cateogry checking
		 */

		/* Set all of the category/forum info to the template */
		$request['template']->setVarArray($forum);

		/**
		 *
		 * CATEGORY
		 *
		 */
		if($forum['row_type'] & CATEGORY) {
			
			if(get_map( 'categories', 'can_view', array()) > $request['user']->get('perms')) {
				$action = new K4InformationAction(new K4LanguageElement('L_PERMCANTVIEW'), 'content', FALSE);
				return $action->execute($request);
			}

			/* Set the Categories list */
			$categories = &new K4ForumsIterator($request['dba'], "SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". $forum['forum_id'] ." ORDER BY row_order ASC");
			$request['template']->setList('tlforums', $categories);

			/* Hide the welcome message at the top of the forums.html template */
			$request['template']->setVisibility('welcome_msg', FALSE);
			
			/* Show the forum status icons */
			$request['template']->setVisibility('forum_status_icons', TRUE);

			/* Show the 'Mark these forums Read' link */
			$request['template']->setVisibility('mark_these_forums', TRUE);
			
			/* Set the forums template to content variable */
			$request['template']->setFile('content', 'forums.html');
		
		/**
		 *
		 * FORUM / META FORUM
		 *
		 */
		} else if($forum['row_type'] & FORUM || $forum['row_type'] & METAFORUM || $forum['row_type'] & ARCHIVEFORUM) {						
			
			/* Add the forum info to the template */
			foreach($forum as $key => $val)
				$request['template']->setVar('forum_'. $key, $val);
			
			/* If this forum has sub-forums */
			if( (isset_forum_cache_item('subforums', $forum['forum_id']) && $forum['subforums'] >= 1)) {
				
				/* Cache this forum as having subforums */
				set_forum_cache_item('subforums', 1, $forum['forum_id']);
				
				/* Show the table that holds the subforums */
				$request['template']->setVisibility('subforums', TRUE);
				
				/* Set the sub-forums list */
				$it = &new K4ForumsIterator($request['dba'], "SELECT * FROM ". K4FORUMS ." WHERE parent_id = ". $forum['forum_id'] ." ORDER BY row_order ASC");
				$request['template']->setList('forums', $it);
			}

			if(get_map( 'topics', 'can_view', array('forum_id' => $forum['forum_id'])) > $request['user']->get('perms')) {
				$action = new K4InformationAction(new K4LanguageElement('L_CANTVIEWFORUMTOPICS'), 'content_extra', FALSE);
				return $action->execute($request);
			}
			
			/**
			 * Forum settings
			 */

			/* Set the topics template to the content variable */
			$request['template']->setFile('content', 'viewforum.html');
			
			/* Set what this user can/cannot do in this forum */
			$request['template']->setVar('forum_user_topic_options', sprintf($request['template']->getVar('L_FORUMUSERTOPICPERMS'),
			((get_map( 'topics', 'can_add', array('forum_id'=>$forum['forum_id'])) > $request['user']->get('perms')) ? $request['template']->getVar('L_CANNOT') : $request['template']->getVar('L_CAN')),
			((get_map( 'topics', 'can_edit', array('forum_id'=>$forum['forum_id'])) > $request['user']->get('perms')) ? $request['template']->getVar('L_CANNOT') : $request['template']->getVar('L_CAN')),
			((get_map( 'topics', 'can_del', array('forum_id'=>$forum['forum_id'])) > $request['user']->get('perms')) ? $request['template']->getVar('L_CANNOT') : $request['template']->getVar('L_CAN')),
			((get_map( 'attachments', 'can_add', array('forum_id'=>$forum['forum_id'])) > $request['user']->get('perms')) ? $request['template']->getVar('L_CANNOT') : $request['template']->getVar('L_CAN'))));

			$request['template']->setVar('forum_user_reply_options', sprintf($request['template']->getVar('L_FORUMUSERREPLYPERMS'),
			((get_map( 'replies', 'can_add', array('forum_id'=>$forum['forum_id'])) > $request['user']->get('perms')) ? $request['template']->getVar('L_CANNOT') : $request['template']->getVar('L_CAN')),
			((get_map( 'replies', 'can_edit', array('forum_id'=>$forum['forum_id'])) > $request['user']->get('perms')) ? $request['template']->getVar('L_CANNOT') : $request['template']->getVar('L_CAN')),
			((get_map( 'replies', 'can_del', array('forum_id'=>$forum['forum_id'])) > $request['user']->get('perms')) ? $request['template']->getVar('L_CANNOT') : $request['template']->getVar('L_CAN'))));
			
			/* Create an array with all of the possible sort orders we can have */						
			$sort_orders		= array('name', 'lastpost_created', 'num_replies', 'views', 'lastpost_uname', 'rating', 'poster_name');
			
			//$extra_topics		= intval(@$_ALLFORUMS[GLBL_ANNOUNCEMENTS]['topics']);
			$extra_topics		= 0; // TODO: need only Announcements from global announcements

			/**
			 * Pagination
			 */

			/* Create the Pagination */
			$resultsperpage		= $request['user']->get('topicsperpage') <= 0 ? $forum['topicsperpage'] : $request['user']->get('topicsperpage');
			$num_results		= $forum['topics'] + $extra_topics;

			$perpage			= isset($_REQUEST['limit']) && ctype_digit($_REQUEST['limit']) && intval($_REQUEST['limit']) > 0 ? intval($_REQUEST['limit']) : $resultsperpage;
			$perpage			= $perpage > 100 ? 100 : $perpage;
			$num_pages			= intval(@ceil($num_results / $perpage));
			$page				= isset($_REQUEST['page']) && ctype_digit($_REQUEST['page']) && intval($_REQUEST['page']) > 0 ? intval($_REQUEST['page']) : 1;
			$pager				= &new FAPaginator($_URL, $num_results, $page, $perpage);
			
			if($num_results > $perpage) {
				$request['template']->setPager('topics_pager', $pager);

				/* Create a friendly url for our pager jump */
				$page_jumper	= new FAUrl($_URL->__toString());
				$page_jumper->args['limit'] = $perpage;
				$page_jumper->args['page']	= FALSE;
				$page_jumper->anchor		= FALSE;
				$request['template']->setVar('pagejumper_url', preg_replace('~&amp;~i', '&', $page_jumper->__toString()));
			}

			/* Get the topics for this forum */
			$daysprune = $_daysprune = isset($_REQUEST['daysprune']) && ctype_digit($_REQUEST['daysprune']) ? ($_REQUEST['daysprune'] == 0 ? 0 : intval($_REQUEST['daysprune'])) : 365;
			$daysprune			= $daysprune > 0 ? time() - @($daysprune * 86400) : 0;
			$sortorder			= isset($_REQUEST['order']) && ($_REQUEST['order'] == 'ASC' || $_REQUEST['order'] == 'DESC') ? $_REQUEST['order'] : 'DESC';
			$sortedby			= isset($_REQUEST['sort']) && in_array($_REQUEST['sort'], $sort_orders) ? $_REQUEST['sort'] : 'lastpost_created';
			$start				= ($page - 1) * $perpage;
			
			/* Apply the directional arrow to the sorting of topics */
			$request['template']->setVar('order', $sortorder == 'DESC' ? 'ASC' : 'DESC');
			$image				= '<img src="Images/'. $request['template']->getVar('IMG_DIR') .'/Icons/arrow_'. ($sortorder == 'DESC' ? 'down' : 'up') .'.gif" alt="" border="0" />';
			$request['template']->setVar($sortedby .'_sort', $image);
			

			/* If there are no topics, set the right message to display */
			if($forum['topics'] <= 0) {
				$request['template']->setVisibility('no_topics', TRUE);
				$request['template']->setVar('topics_message', ($daysprune == 0 ? $request['template']->getVar('L_NOPOSTSINFORUM') : sprintf($request['template']->getVar('L_FORUMNOPOSTSSINCE'), $_daysprune )));
			}

			if(( ($forum['topics'] + $extra_topics) > 0) || $forum['row_type'] > GALLERY) {
				
				/**
				 * Moderator Functions
				 */

				$extra				= 'AND queue = 0';
				
				$request['template']->setVar('modpanel', 0);
				
				/* is this user a moderator */
				if(is_moderator($request['user']->getInfoArray(), $forum) && $forum['row_type'] <= GALLERY) {
					
					$request['template']->setVar('modpanel', 1);

					if(isset($_REQUEST['queued']) || isset($_REQUEST['locked'])) {
						if(isset($_REQUEST['queued']))
							$extra		= 'AND queue = 1';
						elseif(isset($_REQUEST['locked']))
							$extra		= ' AND queue = 0 AND post_locked = 1';
					}
				}

				/**
				 * Topic Setting
				 */
				
				/* Make our query */
				$query = "SELECT * FROM ". K4POSTS ." WHERE created>=$daysprune AND is_draft=0 AND display=1 AND row_type=". TOPIC ." AND forum_id=". intval($forum['forum_id']) ." AND (post_type <> ". TOPIC_ANNOUNCE ." AND post_type <> ". TOPIC_STICKY ." AND is_feature = 0) $extra ORDER BY $sortedby $sortorder LIMIT $start,$perpage";
				if($forum['row_type'] & METAFORUM) {
					
					global $_FILTERS, $_FORUMFILTERS;
					
					$query = "SELECT * FROM ". K4POSTS ." WHERE row_type=". TOPIC ." AND forum_id<>". GARBAGE_BIN ." ";
					
					// loop through the filters being applied to this forum
					$forum_filters = array();
					if(isset($_FORUMFILTERS[$forum['forum_id']])) {

						foreach($_FORUMFILTERS[$forum['forum_id']] as $forum_filter) {
							if(isset($_FILTERS[$forum_filter['filter_id']])) {
								$forum_filters[] = array('name'=>$_FILTERS[$forum_filter['filter_id']]['filter_name']);
								$query .= " AND ". sprintf($_FILTERS[$forum_filter['filter_id']]['filter_query'], $request['dba']->quote($forum_filter['insert1']), $request['dba']->quote($forum_filter['insert2']), $request['dba']->quote($forum_filter['insert3'])) ." ";
							}
						}
					}
					
					$request['template']->setList('forum_filters', new FAArrayIterator($forum_filters));

					$query .= " $extra ORDER BY $sortedby $sortorder LIMIT $start,$perpage";

					$query = str_replace('**', '%', $query);
				}
				
				/* get the topics */
				$result				= $request['dba']->executeQuery($query);
				
				/* Apply the topics iterator */
				$it					= &new TopicsIterator($request['dba'], $request['user'], $result, $request['template']->getVar('IMG_DIR'), $forum);
				$request['template']->setList('topics', $it);
				
				// let's just make sure..
				if($result->hasNext()) {
					$request['template']->setVisibility('no_topics', FALSE);
				}

				if($forum['row_type'] <= GALLERY ) {

					/**
					 * Get announcement/global topics
					 */
					if($page == 1) {
						$announcements		= $request['dba']->executeQuery("SELECT * FROM ". K4POSTS ." WHERE (is_draft=0 AND display=1) AND row_type=". TOPIC ." AND post_type = ". TOPIC_ANNOUNCE ." AND (forum_id = ". intval($forum['forum_id']) ." OR forum_id = ". GLBL_ANNOUNCEMENTS .") $extra ORDER BY lastpost_created DESC");
						if($announcements->hasNext()) {
							$a_it				= &new TopicsIterator($request['dba'], $request['user'], $announcements, $request['template']->getVar('IMG_DIR'), $forum);
							$request['template']->setList('announcements', $a_it);
						}
					}
					
					/**
					 * Get sticky/feature topics
					 */
					$importants			= $request['dba']->executeQuery("SELECT * FROM ". K4POSTS ." WHERE is_draft=0 AND row_type=". TOPIC ." AND display = 1 AND forum_id = ". intval($forum['forum_id']) ." AND (post_type <> ". TOPIC_ANNOUNCE .") AND (post_type = ". TOPIC_STICKY ." OR is_feature = 1) $extra ORDER BY lastpost_created DESC");
					if($importants->hasNext()) {
						$i_it				= &new TopicsIterator($request['dba'], $request['user'], $importants, $request['template']->getVar('IMG_DIR'), $forum);
						$request['template']->setList('importants', $i_it);
					}
				}

				/* Outside valid page range, redirect */
				if(!$pager->hasPage($page) && $num_pages > 0) {
					$action = new K4InformationAction(new K4LanguageElement('L_PASTPAGELIMIT'), 'content', FALSE, 'viewforum.php?f='. $forum['forum_id'] .'&limit='. $perpage .'&page='. $num_pages, 3);
					return $action->execute($request);
				}
			}
			
			/**
			 * Forum Subscriptions
			 */
			if($request['user']->isMember() && $forum['topics'] > 0) {
				$subscribed = $request['dba']->executeQuery("SELECT * FROM ". K4SUBSCRIPTIONS ." WHERE forum_id = ". intval($forum['forum_id']) ." AND post_id = 0 AND user_id = ". $request['user']->get('id'));
				$request['template']->setVar('is_subscribed', ($subscribed->numRows() > 0 ? 1 : 0));
			}

		/**
		 *
		 * GALLERY
		 *
		 */
		} else if($forum['row_type'] & GALLERY) {
			
			$request['template']->setFile('content', 'viewgallery.html');
			
		/**
		 *
		 * ERROR
		 *
		 */
		} else {
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}

		/**
		 * Can we post in here?
		 */
		$can_post_in_forum = 1;
		if( $forum['forum_id'] == GARBAGE_BIN || $forum['row_type'] > GALLERY ) {
			$can_post_in_forum = 0;
		}

		$request['template']->setVar('can_post_in_forum', $can_post_in_forum);

		// urls
		$request['template']->setVar('U_FORUMRSSURL', K4Url::getGenUrl('rss', 'f='. $forum['forum_id']));
				
		/* Add the cookies for this forum's topics */
		bb_execute_topiccache();
		
		// show the midsection of the forum
		$request['template']->setVisibility('forum_midsection', TRUE);

		return TRUE;
	}
}

$app = new K4controller('forum_base.html');

$app->setAction('', new K4DefaultAction);
$app->setDefaultEvent('');

$app->setAction('markforum', new MarkForumsRead);
$app->setAction('track', new SubscribeForum);
$app->setAction('untrack', new UnsubscribeForum);

$app->execute();

?>