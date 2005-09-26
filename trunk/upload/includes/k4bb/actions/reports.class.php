<?php
/**
* k4 Bulletin Board, reports.class.php
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

class ReportBadPost extends FAAction {
	function execute(&$request) {
		
		if(isset($_REQUEST['t']) && intval($_REQUEST['t']) != 0) {
			$post					= $request['dba']->getRow("SELECT * FROM ". K4TOPICS ." WHERE topic_id = ". intval($_REQUEST['t']));
		} elseif(isset($_REQUEST['r']) && intval($_REQUEST['r']) != 0) {
			$post					= $request['dba']->getRow("SELECT * FROM ". K4REPLIES ." WHERE reply_id = ". intval($_REQUEST['r']));
		} else {

			/* set the breadcrumbs bit */
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
			
			$action = new K4InformationAction(new K4LanguageElement('L_POSTDOESNTEXIST'), 'content', TRUE);
			return $action->execute($request);
		}
		
		/* Has this post already been reported? */
		$report		= $request['dba']->getRow("SELECT * FROM ". K4BADPOSTREPORTS ." WHERE ". ($post['row_type'] & TOPIC ? 'topic_id = '. intval($post['topic_id']) .' AND reply_id = 0' : 'reply_id = '. intval($post['reply_id'])));
		
		if(is_array($report) && !empty($report)) {
			
			/* set the breadcrumbs bit */
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
			
			$action = new K4InformationAction(new K4LanguageElement('L_POSTHASBEENREPORTED', $post['name']), 'content', TRUE);
			
			$request['dba']->executeUpdate("UPDATE ". K4BADPOSTREPORTS ." SET num_requests=num_requests+1 WHERE id = ". intval($report['id']));

			return $action->execute($request);
		}

		if($post['row_type'] & TOPIC && ($post['is_draft'] == 1 || $post['queue'] == 1 || $post['display'] == 0)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
			
			$action = new K4InformationAction(new K4LanguageElement('L_CANTREPORTPOST'), 'content', TRUE);
			return $action->execute($request);
		}
		
		/* Assign the post information to the template */
		foreach($post as $key => $val)
			$request['template']->setVar('post_'. $key, $val);

		/* Create the ancestors bar */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_REPORTBADPOST');
		
		$request['template']->setFile('content', 'report_post.html');

		return TRUE;
	}
}

class SendBadPostReport extends FAAction {
	function execute(&$request) {
		
		// was valid topic/reply info given?
		if(isset($_REQUEST['t']) && intval($_REQUEST['t']) != 0) {
			$post					= $request['dba']->getRow("SELECT * FROM ". K4TOPICS ." WHERE topic_id = ". intval($_REQUEST['t']));
		} elseif(isset($_REQUEST['r']) && intval($_REQUEST['r']) != 0) {
			$post					= $request['dba']->getRow("SELECT * FROM ". K4REPLIES ." WHERE reply_id = ". intval($_REQUEST['r']));
		} else {

			/* set the breadcrumbs bit */
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
			
			$action = new K4InformationAction(new K4LanguageElement('L_POSTDOESNTEXIST'), 'content', TRUE);
			return $action->execute($request);
		}
		
		if($post['row_type'] & TOPIC && ($post['is_draft'] == 1 || $post['queue'] == 1 || $post['display'] == 0)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
			
			$action = new K4InformationAction(new K4LanguageElement('L_CANTREPORTPOST'), 'content', TRUE);
			return $action->execute($request);
		}

		// error check the report
		if(!isset($_REQUEST['report']) || $_REQUEST['report'] == '') {
			/* set the breadcrumbs bit */
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
			
			$action = new K4InformationAction(new K4LanguageElement('L_INSERTBADPOSTREPORT'), 'content', TRUE);
			return $action->execute($request);
		}
		
		/* Has this post already been reported? */
		$report		= $request['dba']->getRow("SELECT * FROM ". K4BADPOSTREPORTS ." WHERE ". ($post['row_type'] & TOPIC ? 'topic_id = '. intval($post['topic_id']) .' AND reply_id = 0' : 'reply_id = '. intval($post['reply_id'])));
		
		if(is_array($report) && !empty($report)) {
			
			/* set the breadcrumbs bit */
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
			
			$action = new K4InformationAction(new K4LanguageElement('L_POSTHASBEENREPORTED', $post['name']), 'content', TRUE);
			
			$request['dba']->executeUpdate("UPDATE ". K4BADPOSTREPORTS ." SET num_requests=num_requests+1 WHERE id = ". intval($report['id']));

			return $action->execute($request);
		}
		
		$insert		= $request['dba']->prepareStatement("INSERT INTO ". K4BADPOSTREPORTS ." (category_id,forum_id,topic_id,reply_id,message,user_id,user_name,poster_id,poster_name,created) VALUES (?,?,?,?,?,?,?,?,?,?)");
		
		// category_id,forum_id,topic_id,reply_id,message,user_id,user_name,poster_id,poster_name,created
		$insert->setInt(1, $post['category_id']);
		$insert->setInt(2, $post['forum_id']);
		$insert->setInt(3, $post['topic_id']);
		$insert->setInt(4, @$post['reply_id']);
		$insert->setString(5, htmlentities($_REQUEST['report'], ENT_QUOTES));
		$insert->setInt(6, $request['user']->get('id'));
		$insert->setString(7, $request['user']->get('name'));
		$insert->setInt(8, $post['poster_id']);
		$insert->setString(9, $post['poster_name']);
		$insert->setInt(10, time());
		
		/* Insert the report */
		$insert->executeUpdate();

		/* Create the ancestors bar */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_REPORTBADPOST');
		
		$url	= $post['row_type'] & TOPIC ? 'viewtopic.php?id='. $post['topic_id'] : 'findpost.php?id='. $post['reply_id'];

		$action = new K4InformationAction(new K4LanguageElement('L_REPORTEDPOST', $post['name']), 'content', TRUE, $url, 3);
		return $action->execute($request);

		return TRUE;
	}
}

class ViewBadPostReports extends FAAction {
	function execute(&$request) {
		
		global $_URL;

		if(!isset($_REQUEST['id']) || !$_REQUEST['id'] || intval($_REQUEST['id']) == 0) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);

			return $action->execute($request);
		}
			
		$forum				= $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($_REQUEST['id']));
		
		/* Check the forum data given */
		if(!$forum || !is_array($forum) || empty($forum)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);

			return $action->execute($request);
		}
			
		/* Make sure the we are trying to post into a forum */
		if(!($forum['row_type'] & FORUM)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_CANTMODACATEGORY'), 'content', FALSE);

			return $action->execute($request);
		}
		
		foreach($forum as $key => $val)
			$request['template']->setVar('forum_'. $key, $val);
		

		/**
		 * Moderator Functions
		 */

		$extra				= 'AND queue = 0';
		
		$request['template']->setVar('modpanel', 0);
		
		/* is this user a moderator */
		if(is_moderator($request['user']->getInfoArray(), $forum)) {
			
			$mod_url			= new FAUrl($_URL->__toString());
			$mod_url->file		= 'viewforum.php';
			$mod_url->args		= array('f' => $forum['forum_id']);
			$request['template']->setVar('mod_url', $mod_url->__toString());

			$request['template']->setVar('modpanel', 1);
		} else {
			no_perms_error($request);
			return TRUE;
		}
		
		k4_bread_crumbs($request['template'], $request['dba'], 'L_BADPOSTREPORTS', $forum);

		$num_results			= $request['dba']->getValue("SELECT COUNT(*) FROM ". K4BADPOSTREPORTS ." WHERE forum_id = ". intval($forum['forum_id']));
		
		/**
		 * Pagination
		 */

		/* Create the Pagination */
		$perpage			= isset($_REQUEST['limit']) && ctype_digit($_REQUEST['limit']) && intval($_REQUEST['limit']) > 0 ? intval($_REQUEST['limit']) : 10;
		$num_pages			= ceil($num_results / $perpage);
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
		
		/* Outside valid page range, redirect */
		if(!$pager->hasPage($page) && $num_pages > 0) {
			$action = new K4InformationAction(new K4LanguageElement('L_PASTPAGELIMIT'), 'content', FALSE, 'mod.php?id='. $forum['forum_id'] .'&limit='. $perpage .'&page='. $num_pages, 3);
			return $action->execute($request);
		}

		/* Get the bad post reports for this forum */
		$start				= ($page - 1) * $perpage;
		
		$reports			= &$request['dba']->executeQuery("SELECT * FROM ". K4BADPOSTREPORTS ." WHERE forum_id = ". intval($forum['forum_id'] ." ORDER BY created ASC LIMIT $start,$perpage"));
		$it					= &new BadPostReportIterator($request['dba'], $reports);
		$request['template']->setList('badpost_reports', $it);

		$request['template']->setFile('content', 'badpost_reports.html');
	}
}

class DeleteBadPostReport extends FAAction {
	function execute(&$request) {
		
		if(!isset($_REQUEST['id']) || !$_REQUEST['id'] || intval($_REQUEST['id']) == 0) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_REPORTDOESNTEXIST'), 'content', FALSE);

			return $action->execute($request);
		}
		
		$report				= $request['dba']->getRow("SELECT * FROM ". K4BADPOSTREPORTS ." WHERE id = ". intval($_REQUEST['id']));	
		
		if(!$report || !is_array($report) || empty($report)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_REPORTDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}

		$forum				= $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($report['forum_id']));
		
		/* Check the forum data given */
		if(!$forum || !is_array($forum) || empty($forum)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);

			return $action->execute($request);
		}
			
		/* Make sure the we are trying to post into a forum */
		if(!($forum['row_type'] & FORUM)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_CANTMODACATEGORY'), 'content', FALSE);

			return $action->execute($request);
		}

		/**
		 * Moderator Functions
		 */

		/* is this user a moderator */
		if(is_moderator($request['user']->getInfoArray(), $forum)) {
			k4_bread_crumbs($request['template'], $request['dba'], 'L_DELETEREPORT', $forum);
			$request['dba']->executeUpdate("DELETE FROM ". K4BADPOSTREPORTS ." WHERE id = ". intval($report['id']));
		
			$action = new K4InformationAction(new K4LanguageElement('L_REMOVEDBADPOSTREPORT'), 'content', FALSE, referer(), 3);

			return $action->execute($request);
		} else {
			no_perms_error($request);
			return TRUE;
		}
	}
}

class BadPostReportIterator extends FAProxyIterator {
	
	var $dba, $result, $forums, $users, $qp, $groups, $topics;
	
	function BadPostReportIterator(&$dba, &$result) {
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
				
		if($temp['reply_id'] == 0) {
			$topic				= $this->dba->getRow("SELECT * FROM ". K4TOPICS ." WHERE topic_id = ". intval($temp['topic_id']));

			$temp['topic_name']	= $topic['name'];
			$temp['url']		= 'viewtopic.php?id='. $topic['topic_id'];
			$temp['id']			= $topic['topic_id'];

			$this->topics[$temp['topic_id']] = $topic;

			$temp				= array_merge($temp, $topic);
		} else {
			$reply				= $this->dba->getRow("SELECT * FROM ". K4REPLIES ." WHERE reply_id = ". intval($temp['reply_id']));

			$temp['id']			= $reply['reply_id'];
			$temp['views']		= '--';
			$temp['url']		= 'findpost.php?id='. $reply['reply_id'];
			$temp['topic_name'] = !isset($this->topics[$reply['topic_id']]) ? $this->dba->getValue("SELECT name FROM ". K4TOPICS ." WHERE topic_id = ". intval($reply['topic_id'])) : $this->topics[$reply['topic_id']]['name'];
		
			$temp				= array_merge($temp, $reply);
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
		$temp['forum_name']		= $this->forums['f'. $temp['forum_id']]['name'];

		/* Should we free the result? */
		if(!$this->hasNext())
			$this->result->free();
		
		return $temp;
	}
}

?>