<?php
/**
* k4 Bulletin Board, search.php
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
* @package k4-2.0-dev
*/

require "includes/filearts/filearts.php";
require "includes/k4bb/k4bb.php";

class K4DefaultAction extends FAAction {
	function execute(&$request) {
		
		k4_bread_crumbs($request['template'], $request['dba'], 'L_SEARCH');
		$request['template']->setFile('content', 'search.html');
		
		if(get_map( 'advsearch', 'can_view', array()) > $request['user']->get('perms')) {
			no_perms_error($request);
			return TRUE;
		}

		unset($_SESSION['search']['search_queries']);
		
		return TRUE;
	}
}

class K4SearchEverything extends FAAction {
	function execute(&$request) {
		
		global $_QUERYPARAMS, $_ALLFORUMS, $_URL;
		
		k4_bread_crumbs($request['template'], $request['dba'], 'L_SEARCHRESULTS');
		
		if(get_map( 'advsearch', 'can_add', array()) > $request['user']->get('perms')) {
			// something here?
		}
		
		/* Do we force it to rewrite the session? */
		if(isset($_REQUEST['rewrite_session']) && intval($_REQUEST['rewrite_session']) == 1 && isset($_SESSION['search']['search_queries']))
			unset($_SESSION['search']['search_queries']);

		/**
		 * Sort out author information
		 */
		$user_ids				= '';
		if(isset($_REQUEST['author']) && $_REQUEST['author'] != '') {
			
			$author				= k4_htmlentities(trim($_REQUEST['author']), ENT_QUOTES);
			if(!isset($_REQUEST['exact']) || !$_REQUEST['exact']) {
				$author				= str_replace('%', '*', $author);
				$author				= intval($request['template']->getVar('allowwildcards')) == 1 ? str_replace('*', '%', $author) : str_replace('*', ' ', $author);
				
				// wildcard/partial match
				$user_search		= "LOWER(name) LIKE LOWER('%". $request['dba']->quote($author) ."%')";
			} else {
				// exact match
				$user_search		= "name = '". $request['dba']->quote($author) ."'";
			}
			
			if(strlen($author) < $request['template']->getVar('minsearchlength') || strlen($author) > $request['template']->getVar('maxsearchlength')) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDSEARCHKEYWORDS', $request['template']->getVar('minsearchlength'), $request['template']->getVar('maxsearchlength')), 'content', TRUE, 'search.php', 5);
				return $action->execute($request);
			}

			$users				= $request['dba']->executeQuery("SELECT * FROM ". K4USERS ." WHERE $user_search");
			
			if($users->numrows() > 0) {
				
				$user_ids		= ' AND (';
				
				while($users->next()) {
					$user		= $users->current();
					$user_ids	.= $users->key() == 0 ? ' poster_id = '. intval($user['id']) : ' OR poster_id = '. intval($user['id']);
				}
				$user_ids		.= ') ';
			} else {
				$action = new K4InformationAction(new K4LanguageElement('L_NOAUTHORSBYNAME'), 'content', FALSE, 'search.php', 5);
				return $action->execute($request);
			}
		}
		
		/**
		 * Sort out forum information
		 */
		$forum_ids			= '';
		$category_ids		= '';
		$searchable_forums	= '';
		
		/* This will handle different types of forum data */
		if(isset($_REQUEST['forums']) && is_array($_REQUEST['forums']) && !empty($_REQUEST['forums'])) {
			$forums			= $_REQUEST['forums'];
		} elseif(isset($_REQUEST['forums']) && count(explode("|", $_REQUEST['forums'])) > 0) {
			$forums			= explode("|", $_REQUEST['forums']);
		} else {
			$forums			= array();
		}
		
		/* Now start looking thouroughly at the data */
		if(is_array($forums) && !empty($forums)) {
			
			$subforums				= isset($_REQUEST['searchsubforums']) && intval($_REQUEST['searchsubforums']) == 1 ? TRUE : FALSE;
			$allforums				= (intval(@$forums[0]) == -1) ? TRUE : FALSE;

			$searchable_forums		= $allforums ? '-1' : '';
			
			// do not include the first option
			for($i = 1; $i < count($forums); $i++) {
				$id					= intval($forums[$i]);
				
				// forums
				if(isset($_ALLFORUMS[$id])) {
					if(get_map( '', 'can_view', array('forum_id'=>$id)) <= $request['user']->get('perms')) {
						$forum_ids			.=  (!$subforums && $_ALLFORUMS[$id]['row_level'] > 2) ? '' : ' OR forum_id = '. intval($id);
						$searchable_forums	.= '|'. $id;
					}
				}
			}
			
			$category_ids			= $category_ids != '' ? ' AND ('. substr($category_ids, 4) .') ' : '';
			$forum_ids				= $forum_ids != '' ? ' AND ('. substr($forum_ids, 4) .') ' : '';

//			if(($allforums && !$subforums) || (!$allforums && !$subforums) ) {
//				$forum_ids			= $forum_ids != '' ? ' AND ('. substr($forum_ids, 4) .') ' : '';
//			} else {
//				$category_ids		= '';
//				$forum_ids			= '';
//				$searchable_forums	= '-1';
//			}
		}

		/* Set which forums we're looking through to the display options field */
		$request['template']->setVar('search_forums', trim($searchable_forums, '|'));

		/**
		 * Sort out keywords
		 */
		$keyword_query			= '';
		if(isset($_REQUEST['keywords']) && $_REQUEST['keywords'] != '' && !isset($_REQUEST['newposts'])) {
			
			// deal with wildcrds
			$keywords		= str_replace('%', '*', $_REQUEST['keywords']);
			$keywords		= intval($request['template']->getVar('allowwildcards')) == 1 ? str_replace('*', '%', $keywords) : str_replace('*', ' ', $keywords);
			
			// are the keywords too short or too long?
			if(strlen($keywords) < $request['template']->getVar('minsearchlength') || strlen($keywords) > $request['template']->getVar('maxsearchlength')) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDSEARCHKEYWORDS', $request['template']->getVar('minsearchlength'), $request['template']->getVar('maxsearchlength')), 'content', TRUE, 'search.php', 5);
				return $action->execute($request);
			}
			
			// has the person specified where to search?
			if(isset($_REQUEST['searchwhere']) && $_REQUEST['searchwhere'] != '' && $_REQUEST['searchwhere'] != 'subjectmessage') {
				
				if($_REQUEST['searchwhere'] == 'subject') {
					$keyword_query		= " AND LOWER(name) LIKE LOWER('%". $request['dba']->quote($keywords) ."%')";
				} else if($_REQUEST['searchwhere'] == 'message') {
					$keyword_query		= " AND LOWER(body_text) LIKE LOWER('%". $request['dba']->quote($keywords) ."%')";
				}
			} else {

				$keyword_query		= "  AND (LOWER(name) LIKE LOWER('%". $request['dba']->quote($keywords) ."%') OR LOWER(body_text) LIKE LOWER('%". $request['dba']->quote($keywords) ."%')) ";
			}
		}

		// set where we are searching to the template
		$request['template']->setVar('search_where', !isset($_REQUEST['searchwhere']) ? 'subjectmessage' : $_REQUEST['searchwhere']);
		
		// are there no keywords, user ids, etc?
		if($keyword_query == '' && $user_ids == '' && !isset($_SESSION['search']['search_queries']) && !isset($_REQUEST['newposts'])) {
			$action = new K4InformationAction(new K4LanguageElement('L_SEARCHINVALID'), 'content', TRUE, 'search.php', 3);
			return $action->execute($request);
		}

		/**
		 * Viewing preferences
		 */
		$sort_orders		= array('created', 'poster_name', 'name', 'forum_id');
		
		$viewas				= (isset($_SESSION['search']['search_queries']['viewas']) && $_SESSION['search']['search_queries']['viewas'] == 'topics') ? 'topics' : ((isset($_REQUEST['viewas']) && $_REQUEST['viewas'] == 'topics') ? 'topics' : 'posts');

		$resultsperpage		= $viewas == 'topics' ? intval($request['template']->getVar('searchtopicsperpage')) : intval($request['template']->getVar('searchpostsperpage'));
		$resultsperpage		= isset($_REQUEST['limit']) && ctype_digit($_REQUEST['limit']) && intval($_REQUEST['limit']) > 0 ? intval($_REQUEST['limit']) : $resultsperpage;
		$page				= isset($_REQUEST['page']) && ctype_digit($_REQUEST['page']) && intval($_REQUEST['page']) > 0 ? intval($_REQUEST['page']) : 1;
		
		$daysprune			= isset($_REQUEST['daysprune']) && ctype_digit($_REQUEST['daysprune']) ? ($_REQUEST['daysprune'] == -1 ? 0 : intval($_REQUEST['daysprune'])) : 0;
		$daysprune			= $daysprune > 0 ? time() - @($daysprune * 86400) : 0;
		
		$sortorder			= (isset($_SESSION['search']['search_queries']['order']) && $_SESSION['search']['search_queries']['order'] == 'ASC') ? 'ASC' : ((isset($_REQUEST['order']) && $_REQUEST['order'] == 'ASC') ? 'ASC' : 'DESC');
		
		$sortedby			= (isset($_SESSION['search']['search_queries']['sort']) && $_SESSION['search']['search_queries']['sort'] != 'DESC') ? $_SESSION['search']['search_queries']['sort'] : ((isset($_REQUEST['sort']) && $_REQUEST['sort'] != '') ? $_REQUEST['sort'] : 'created');
		$start				= ceil(@($page - 1) * $resultsperpage);
		
		/**
		 * Execute the search
		 */

		/* Create an array of the queries that we will use to weed out posts and pass through the session */
		
		$select				= "num_replies, forum_id, post_id, post_id, body_text, name, posticon, is_poll, poster_name, poster_id, views, lastpost_uname, lastpost_uid, created, row_type";
		$general_condition	= "is_draft=0 AND queue=0 AND display=1 AND moved_new_post_id=0 AND post_id>0";
		//$selectr			= "num_replies, forum_id, post_id, post_id, body_text, name, posticon, is_poll, poster_name, poster_id, poster_ip as views, poster_ip, category_id, created, row_type";
		
		$queries			= array(
			'posts'			=> "SELECT **SELECT** FROM ". K4POSTS ." WHERE {$general_condition} {$user_ids} {$forum_ids} {$category_ids} {$keyword_query} AND created >= {$daysprune} ORDER BY {$sortedby} {$sortorder}",
			'topics_only'	=> "SELECT **SELECT** FROM ". K4POSTS ." WHERE row_type=". TOPIC ." AND {$general_condition} {$user_ids} {$forum_ids} {$keyword_query} ORDER BY {$sortedby} {$sortorder}",
			'viewas'		=> $viewas,
			'limit'			=> $resultsperpage,
			'sort'			=> $sortedby,
			'order'			=> $sortorder,
			'author'		=> @$_REQUEST['author'],
			'keywords'		=> @$_REQUEST['keywords'],
			'subforums'		=> intval(@$_REQUEST['searchsubforums']),
							);
		//print_r($queries);
		// set these queries to the session
		if(isset($_SESSION['search']['search_queries']) && is_array($_SESSION['search']['search_queries']) && !empty($_SESSION['search']['search_queries'])) {
			$queries					= $_SESSION['search']['search_queries'];
		} else {
			$_SESSION['search']['search_queries'] = $queries;
		}
				
		/* Get topics and replies */
		if($queries['viewas'] == 'posts') {
						
			if(!isset($queries['num_results'])) {
				$num_results = $request['dba']->getValue(str_replace('**SELECT**', 'COUNT(post_id)', $queries['posts']));
				$_SESSION['search']['search_queries']['num_results'] = $num_results;
			} else {
				$num_results	= $queries['num_results'];
			}
			
			/* Set the iterator */
			$result				= $request['dba']->executeQuery(str_replace('**SELECT**', $select, $queries['posts']) . " LIMIT {$start},". intval($queries['limit']));
			$it					= &new SearchResultsIterator($request['dba'], $result);
			
		/* Get topics only */
		} else {
			
			if(!isset($queries['num_results'])) {
				$num_results = $request['dba']->getValue(str_replace('**SELECT**', 'COUNT(post_id)', $queries['topics_only']));
				$_SESSION['search']['search_queries']['num_results'] = $num_results;
			} else {
				$num_results	= $queries['num_results'];
			}
			
			/* get the topics */
			$topics				= $request['dba']->executeQuery(str_replace('**SELECT**', '*', $queries['topics_only']) ." LIMIT {$start},". $queries['limit']);
			
			/* Apply the topics iterator */
			$it					= &new TopicsIterator($request['dba'], $request['user'], $topics, $request['template']->getVar('IMG_DIR'), array('postsperpage' => $queries['limit']));
		}
		
		/**
		 * Pagination
		 */

		/* Create the Pagination */
		$url					= new FAUrl($_URL->__toString());
		$url->args['limit']		= $queries['limit'];
		$url->args['viewas']	= $queries['viewas'];
		$url->args['sort']		= $queries['sort'];
		$url->args['order']		= $queries['order'];
		$url->args['page']		= FALSE;
		$url->anchor			= FALSE;

		$num_pages				= ceil(@($num_results / $queries['limit']));
		$pager					= &new FAPaginator($url, $num_results, $page, $queries['limit']);
		
		$base_url = new FAUrl($_URL->__toString());
		
		if($num_results > $resultsperpage) {
			$request['template']->setPager('searchresults_pager', $pager);

			/* Create a friendly url for our pager jump */
			$request['template']->setVar('pagejumper_url', preg_replace('~&amp;~i', '&', $base_url->__toString()));
		}
		
		/* Outside valid page range, redirect */
		if(!$pager->hasPage($page) && $num_pages > 0) {
			
			$base_url->args['page']	= $num_pages;

			$action = new K4InformationAction(new K4LanguageElement('L_PASTPAGELIMIT'), 'content', FALSE, $base_url->__toString(), 3);
			return $action->execute($request);
		}
		
		// finish stuff off
		$request['template']->setVar('mod_panel', 0);
		$request['template']->setVar('search_panel', 1);
		$request['template']->setList('search_results', $it);

		/* Search data gathered */
		$request['template']->setVar('search_num_results', $num_results);
		$request['template']->setVar('search_author', $queries['author']);
		$request['template']->setVar('search_keywords', $queries['keywords']);
		$request['template']->setVar('search_viewas', $queries['viewas']);
		$request['template']->setVar('search_viewas_int', ($queries['viewas'] == 'posts' ? 1 : 2)); // for the if statements
		$request['template']->setVar('search_sort', $queries['sort']);
		$request['template']->setVar('search_limit', $queries['limit']);
		$request['template']->setVar('search_order', $queries['order']);
		$request['template']->setVar('search_subforums', $queries['subforums']);
		$request['template']->setVar('search_daysprune', (isset($_REQUEST['daysprune']) ? intval($_REQUEST['daysprune']) : 0));
		$request['template']->setVar('post_length', (isset($_REQUEST['post_length']) && intval($_REQUEST['post_length']) > 0 ? intval($_REQUEST['post_length']) : intval($request['template']->getVar('searchpostlength'))));
		

		$request['template']->setFile('content', 'search_results.html');
		$request['template']->setFile('content_extra', 'search_sort_menu.html');
		$request['template']->setVisibility('forum_midsection', FALSE);

//		if(isset($_SESSION['search']['search_query'])) {
//			unset($_SESSION['search']['search_result']);
//			unset($_SESSION['search']['search_num_results']);
//		}
//
//		if($num_pages > 1) {
//			$_SESSION['search']['search_result']		= &$result;
//			$_SESSION['search']['search_resultarray'] = array('num_results' => $num_results, 'resultsperpage' => $resultsperpage, 'num_pages' => $num_pages, 'viewas' => $viewas);
//		}
		
		/* Memory Saving */
		unset($result);

		return TRUE;
	}
}

class SearchResultsIterator extends FAProxyIterator {
	
	var $dba, $result, $forums, $users, $qp, $groups, $topic_names;
	
	function SearchResultsIterator(&$dba, &$result) {
		$this->__construct($dba, $result);
	}
	
	function __construct(&$dba, &$result) {
		global $_ALLFORUMS, $_QUERYPARAMS, $_USERGROUPS;
		
		$this->dba			= &$dba;
		$this->result		= &$result;
		$this->forums		= $_ALLFORUMS;
		$this->qp			= $_QUERYPARAMS;
		$this->groups		= $_USERGROUPS;
		$this->users		= array();
		$this->topic_names	= array();
		
		parent::__construct($this->result);
	}
	function current() {
		$temp					= parent::current();

		/* Set the topic icons */
		//$temp['posticon']		= $temp['posticon'] != '' ? iif(file_exists(BB_BASE_DIR .'/tmp/upload/posticons/'. $temp['posticon']), $temp['posticon'], 'clear.gif') : 'clear.gif';
		//$temp['topicicon']		= topic_image($temp, $this->user, $this->img_dir, $last_seen);
		
		if($temp['row_type'] == TOPIC) {
			$temp['topic_name']	= $temp['name'];
			$temp['url']		= 'viewtopic.php?id='. $temp['post_id'];

			$this->topic_names[$temp['post_id']] = $temp['name'];
		} else {
			$temp['views']		= '--';
			$temp['url']		= 'findpost.php?id='. $temp['post_id'];
			$temp['topic_name'] = !isset($this->topic_names[$temp['post_id']]) ? $this->dba->getValue("SELECT name FROM ". K4POSTS ." WHERE post_id = ". intval($temp['post_id'])) : $this->topic_names[$temp['post_id']];
			$this->topic_names[$temp['post_id']] = $temp['topic_name'];
		}

		if($temp['poster_id'] > 0) {
			$user						= !isset($this->users[$temp['poster_id']]) ? $this->dba->getRow("SELECT ". $this->qp['user'] . $this->qp['userinfo'] ." FROM ". K4USERS ." u LEFT JOIN ". K4USERINFO ." ui ON u.id=ui.user_id WHERE u.id=". intval($temp['poster_id'])) : $this->users[$temp['poster_id']];
			
			$group						= get_user_max_group($user, $this->groups);
			
			$user['group_color']		= (!isset($group['color']) || $group['color'] == '') ? '000000' : $group['color'];
			$user['group_nicename']		= $group['nicename'];
			$user['group_avatar']		= $group['avatar'];
			$user['online']				= (time() - ini_get('session.gc_maxlifetime')) > $user['seen'] ? 'offline' : 'online';
			
			foreach($user as $key => $val)
				$temp['poster_'. $key] = $val;

			$this->users[$temp['poster_id']] = $user;
		}
		
		$temp['body_text']		= preg_replace("~<!--(.+?)-->~is", "", $temp['body_text']);
		$temp['forum_name']		= isset($this->forums[$temp['forum_id']]['name']) ? $this->forums[$temp['forum_id']]['name'] : '--';

		/* Should we free the result? */
		if(!$this->hasNext())
			$this->result->free();
		
		return $temp;
	}
}

$app = &new K4Controller('forum_base.html');
$app->setAction('', new K4DefaultAction);
$app->setDefaultEvent('');

$app->setAction('find', new K4SearchEverything);

$app->execute();


?>