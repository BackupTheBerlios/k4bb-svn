<?php
/**
* k4 Bulletin Board, rss.php
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

ob_start();

require "includes/filearts/filearts.php";
require "includes/k4bb/k4bb.php";

class K4DefaultAction extends FAAction {
	function execute(&$request) {
		
		/* set the breadcrumbs bit */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
		
		$rss_version = isset($_REQUEST['v']) && $_REQUEST['v'] == 2 ? '2.0' : '0.92';
		$request['template']->setVar('xml_definition', '<?xml version="1.0" encoding="utf-8"?>' ."\n");

		/**
		 * Forum
		 */
		if(isset($_REQUEST['f']) && intval($_REQUEST['f']) > 0) {
			$forum		= $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($_REQUEST['f']));
			
			if(!is_array($forum) || empty($forum)) {
				$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);
				return $action->execute($request);
			}
			
			if(get_map( 'topics', 'can_view', array('forum_id'=>$forum['forum_id'])) > $request['user']->get('perms')) {
				$action = new K4InformationAction(new K4LanguageElement('L_CANTVIEWFORUMTOPICS'), 'content_extra', FALSE);
				return $action->execute($request);
			}

			k4_bread_crumbs($request['template'], $request['dba'], NULL, $forum);
			
			$bbcode = new BBCodex($request['dba'], $request['user'], NULL, $forum['forum_id'], TRUE, TRUE, TRUE, TRUE);

			/**
			 * Pagination
			 */
			
			//$extra_topics		= intval(@$_ALLFORUMS['f'. GLBL_ANNOUNCEMENTS]['topics']);
			$extra_topics		= 0; // TODO: need only Announcements from global announcements

			/* Create the Pagination */
			$resultsperpage		= $request['user']->get('topicsperpage') <= 0 ? $forum['topicsperpage'] : $request['user']->get('topicsperpage');
			$num_results		= $forum['topics'] + $extra_topics;
			$perpage			= isset($_REQUEST['limit']) && ctype_digit($_REQUEST['limit']) && intval($_REQUEST['limit']) > 0 ? intval($_REQUEST['limit']) : $resultsperpage;
			$page				= isset($_REQUEST['page']) && ctype_digit($_REQUEST['page']) && intval($_REQUEST['page']) > 0 ? intval($_REQUEST['page']) : 1;
			/* Get the topics for this forum */
			$daysprune = $_daysprune = isset($_REQUEST['daysprune']) && ctype_digit($_REQUEST['daysprune']) ? ($_REQUEST['daysprune'] == 0 ? 0 : intval($_REQUEST['daysprune'])) : 365;
			$daysprune			= $daysprune > 0 ? time() - @($daysprune * 86400) : 0;
			$sortorder			= isset($_REQUEST['order']) && ($_REQUEST['order'] == 'ASC' || $_REQUEST['order'] == 'DESC') ? $_REQUEST['order'] : 'DESC';
			$sortedby			= isset($_REQUEST['sort']) && in_array($_REQUEST['sort'], $sort_orders) ? $_REQUEST['sort'] : 'last_post';
			$start				= ($page - 1) * $perpage;
			
			
			
			if($page == 1) {
				$announcements		= $request['dba']->executeQuery("SELECT * FROM ". K4TOPICS ." WHERE (is_draft=0 AND display=1) AND topic_type = ". TOPIC_ANNOUNCE ." AND (forum_id = ". intval($forum['forum_id']) ." OR forum_id = ". GLBL_ANNOUNCEMENTS .") ORDER BY last_post DESC");
			}
			
			$importants			= $request['dba']->executeQuery("SELECT * FROM ". K4TOPICS ." WHERE is_draft=0 AND display = 1 AND forum_id = ". intval($forum['forum_id']) ." AND (topic_type <> ". TOPIC_ANNOUNCE .") AND (topic_type = ". TOPIC_STICKY ." OR is_feature = 1) ORDER BY last_post DESC");
			
			/* get the topics */
			$result				= $request['dba']->prepareStatement("SELECT * FROM ". K4TOPICS ." WHERE created>=? AND is_draft=0 AND display = 1 AND forum_id = ". intval($forum['forum_id']) ." AND (topic_type <> ". TOPIC_ANNOUNCE ." AND topic_type <> ". TOPIC_STICKY ." AND is_feature = 0) ORDER BY $sortedby $sortorder LIMIT ?,?");

			/* Set the query values */
			$result->setInt(1, $daysprune);
			$result->setInt(2, $start);
			$result->setInt(3, $perpage);
			
			/* Execute the query */
			$topics				= $result->executeQuery();

			
			if(isset($announcements)) {
				$it = new FAChainedIterator($announcements);
				$it->addIterator($importants);
			} else {
				$it = new FAChainedIterator($importants);
			}
			$it->addIterator($topics);
			
			$request['template']->setList('topics', new RSSPostIterator($it, $bbcode));
			$request['template']->setVarArray($forum);
			$xml		= $request['template']->render(BB_BASE_DIR . '/templates/RSS/rss-'. $rss_version .'/forum.xml');
			
			header("Content-Type: text/xml");
			echo $xml;			
			exit;

		/**
		 * Topic
		 */
		} else if(isset($_REQUEST['t']) && intval($_REQUEST['t']) > 0) {
			
			$result		= $request['dba']->executeQuery("SELECT * FROM ". K4TOPICS ." WHERE topic_id = ". intval($_REQUEST['t']) ." LIMIT 1");
			$topic		= $result->next();
			$result->reset(); // reset the pointer of the iterator
			
			if(!is_array($topic) || empty($topic)) {
				$action = new K4InformationAction(new K4LanguageElement('L_TOPICDOESNTEXIST'), 'content', FALSE);
				return $action->execute($request);
			}
			
			if(get_map( 'topics', 'can_view', array('forum_id'=>$topic['forum_id'])) > $request['user']->get('perms')) {
				$action = new K4InformationAction(new K4LanguageElement('L_CANTVIEWFORUMTOPICS'), 'content_extra', FALSE);
				return $action->execute($request);
			}

			$forum		= $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($topic['forum_id']));
			
			if(!is_array($forum) || empty($forum)) {
				$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);
				return $action->execute($request);
			}
			
			$bbcode = new BBCodex($request['dba'], $request['user'], NULL, $topic['forum_id'], TRUE, TRUE, TRUE, TRUE);

			$it = new FAChainedIterator($result);

			if(get_map( 'replies', 'can_view', array('forum_id'=>$topic['forum_id'])) <= $request['user']->get('perms')) {
				if($topic['num_replies'] > 0) {
					
					$resultsperpage	= $request['user']->get('postsperpage') <= 0 ? $forum['postsperpage'] : $request['user']->get('postsperpage');
					$num_results	= $topic['num_replies'];
					$perpage		= isset($_REQUEST['limit']) && ctype_digit($_REQUEST['limit']) && intval($_REQUEST['limit']) > 0 ? intval($_REQUEST['limit']) : $resultsperpage;
					$num_pages		= @ceil($num_results / $perpage);
					$page			= isset($_REQUEST['page']) && ctype_digit($_REQUEST['page']) && intval($_REQUEST['page']) > 0 ? intval($_REQUEST['page']) : 1;
					$daysprune		= isset($_REQUEST['daysprune']) && ctype_digit($_REQUEST['daysprune']) ? iif(($_REQUEST['daysprune'] == -1), 0, intval($_REQUEST['daysprune'])) : 0;
					$sortorder		= isset($_REQUEST['order']) && ($_REQUEST['order'] == 'ASC' || $_REQUEST['order'] == 'DESC') ? $_REQUEST['order'] : 'ASC';
					$sortedby		= isset($_REQUEST['sort']) && in_array($_REQUEST['sort'], $sort_orders) ? $_REQUEST['sort'] : 'created';
					$start			= ($page - 1) * $perpage;
					
					$replies		= $request['dba']->executeQuery("SELECT * FROM ". K4REPLIES ." WHERE topic_id = ". intval($topic['topic_id']) ." AND created >= ". (3600 * 24 * intval($daysprune)) ." ORDER BY ". $sortedby ." ". $sortorder ." LIMIT ". intval($start) .",". intval($perpage));
					$it->addIterator($replies);
				}
			}

			$request['template']->setList('posts', new RSSPostIterator($it, $bbcode));
			$xml		= $request['template']->render(BB_BASE_DIR . '/templates/RSS/rss-'. $rss_version .'/topic.xml');
			
			header("Content-Type: text/xml");
			echo $xml;			
			exit;

		/**
		 * Error
		 */
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class RSSPostIterator extends FAProxyIterator {
	
	var $bbcode;

	function RSSPostIterator(&$it, $bbcode) {
		$this->__construct($it, $bbcode);
	}

	function __construct(&$it, $bbcode) {
		$this->bbcode	= $bbcode;

		parent::__construct($it);
	}

	function current() {
		$temp					= parent::current();
		
		//$this->bbcode->text		= $temp['body_text'];

		//$temp['body_text']		= $this->bbcode->revert();
		$temp['body_text'] = preg_replace("~<!--(.+?)-->~", "", $temp['body_text']);

		return $temp;
	}
}

$app = &new K4Controller('forum_base.html');
$app->setAction('', new K4DefaultAction);
$app->setDefaultEvent('');

$app->execute();

ob_flush();

?>