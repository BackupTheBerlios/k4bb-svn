<?php
/**
* k4 Bulletin Board, findpost.php
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
* @version $Id: findpost.php,v 1.4 2005/05/24 20:09:16 k4st Exp $
* @package k42
*/

ob_start();

require "includes/filearts/filearts.php";
require "includes/k4bb/k4bb.php";

class K4DefaultAction extends FAAction {
	function execute(&$request) {
		
		global $_QUERYPARAMS;
		
		$next		= FALSE;
		$prev		= FALSE;

		if(isset($_REQUEST['next']) && intval($_REQUEST['next']) == 1)
			$next = TRUE;
		if(isset($_REQUEST['prev']) && intval($_REQUEST['prev']) == 1)
			$prev = TRUE;

		/**
		 * Error Checking
		 */
		if(!isset($_REQUEST['id']) || !$_REQUEST['id'] || intval($_REQUEST['id']) <= 0) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], &$request['dba'], 'L_INVALIDPOST');
			
			$action = new K4InformationAction(new K4LanguageElement('L_POSTDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}

		$post	= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] ." FROM ". K4INFO ." i WHERE i.id = ". intval($_REQUEST['id']));
		
		if(!is_array($post) || !$post || empty($post)) {
			
			if($next || $prev)
				header("Location: ". referer());

			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], &$request['dba'], 'L_INVALIDPOST');
			
			$action = new K4InformationAction(new K4LanguageElement('L_POSTDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}

		if($post['row_type'] != TOPIC && $post['row_type'] != REPLY) {
			
			if($next || $prev)
				header("Location: ". referer());

			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], &$request['dba'], 'L_INVALIDPOST');
			
			$action = new K4InformationAction(new K4LanguageElement('L_POSTDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}
		
		/* If this is a topic */
		if($post['row_type'] == TOPIC) {
			
			/**
			 * We don't error check here because that would just be redundant. There is already
			 * error checking in viewtopic.php, and it will make sure that this isn't a draft,
			 * its info exits, etc.
			 */
			
			header("Location: viewtopic.php?id=". $post['id']);

		/* If this is a reply */	
		} else {
			
			if($next || $prev)
				header("Location: ". referer());

			$reply				= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['reply'] ." FROM ". K4REPLIES ." r LEFT JOIN ". K4INFO ." i ON i.id=r.reply_id WHERE r.reply_id = ". intval($post['id']));
			
			if(!$reply || !is_array($reply) || empty($reply)) {
				/* set the breadcrumbs bit */
				k4_bread_crumbs(&$request['template'], &$request['dba'], 'L_INVALIDPOST');
				
				$action = new K4InformationAction(new K4LanguageElement('L_POSTDOESNTEXIST'), 'content', FALSE);
				return $action->execute($request);

				return TRUE;
			}

			$topic				= $request['dba']->getRow("SELECT ". $_QUERYPARAMS['info'] . $_QUERYPARAMS['topic'] ." FROM ". K4TOPICS ." t LEFT JOIN ". K4INFO ." i ON t.topic_id = i.id WHERE i.id = ". intval($reply['topic_id']));
			
			if(!$topic || !is_array($topic) || empty($topic)) {
				/* set the breadcrumbs bit */
				k4_bread_crumbs(&$request['template'], &$request['dba'], 'L_INVALIDTOPIC');
				
				$action = new K4InformationAction(new K4LanguageElement('L_TOPICDOESNTEXIST'), 'content', FALSE);
				return $action->execute($request);

				return TRUE;
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
				
				$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);
				return $action->execute($request);
			}
			

			$num_replies		= @intval(($topic['row_right'] - $topic['row_left'] - 1) / 2);
			
			/* If the number of replies on this topic is greater than the posts per page for this forum */
			if($num_replies > $forum['postsperpage']) {
				
				$whereinline	= $request['dba']->getValue("SELECT COUNT(r.reply_id) FROM ". K4REPLIES ." r LEFT JOIN ". K4INFO ." i ON i.id = r.reply_id WHERE r.topic_id = ". intval($reply['topic_id']) ." AND i.created < ". intval($reply['created']) ." ORDER BY i.created ASC");
				
				$page		= ceil($whereinline / $forum['postsperpage']);
				$page		= $page <= 0 ? 1 : $page;

				header("Location: viewtopic.php?id=". $topic['id'] ."&page=". intval($page) ."&limit=". $forum['postsperpage'] ."&order=ASC&sort=created&daysprune=0#". $post['id']);
				exit;

			} else {
				header("Location: viewtopic.php?id=". $topic['id'] ."#". $post['id']);
				exit;
			}
		}

		return TRUE;
	}
}

$app = new K4controller('forum_base.html');

$app->setAction('', new K4DefaultAction);
$app->setDefaultEvent('');

$app->execute();

ob_flush();

?>