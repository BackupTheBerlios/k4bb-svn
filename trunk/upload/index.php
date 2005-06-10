<?php
/**
* k4 Bulletin Board, index.php
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
* @author Geoffrey Goodman
* @version $Id$
* @package k42
*/

require "includes/filearts/filearts.php";
require "includes/k4bb/k4bb.php";

class K4DefaultAction extends FAAction {
	function execute(&$request) {
		global $_DATASTORE, $_USERGROUPS;
		
		$template =& $request['template'];
		$dba = &$request['dba'];
		
		// Member/Guest specifics
		if(!$request['user']->isMember()) {
			$template->setVar('welcome_title', sprintf($template->getVar('L_WELCOMETITLE'), $template->getVar('bbtitle')));
			$template->setFile('quick_login', 'login_form_quick.html');
			$template->setVisibility('welcome_msg', TRUE);
		}

		// The content panel
		$template->setFile('content', 'forums.html');

		$categories = &new K4CategoriesIterator($request['dba']);
		$template->setList('categories', $categories);
		
		// Set the online users list
		$online_users						= &new K4OnlineUsersIterator($dba);
		$template->setList('online_users', $online_users);
		
		$newest_user						= $dba->getRow("SELECT name, id FROM ". K4USERS ." ORDER BY id DESC LIMIT 1");
		$expired							= time() - ini_get('session.gc_maxlifetime');

		$stats = array('num_online_members'	=> Globals::getGlobal('num_online_members'),
						'num_invisible'		=> Globals::getGlobal('num_online_invisible'),
						'num_topics'		=> intval($_DATASTORE['forumstats']['num_topics']),
						'num_replies'		=> intval($_DATASTORE['forumstats']['num_replies']),
						'num_members'		=> intval($_DATASTORE['forumstats']['num_members']),
						'num_guests'		=> $dba->getValue("SELECT COUNT(*) FROM ". K4SESSIONS ." WHERE seen >= $expired AND user_id=0"),
						'newest_uid'		=> $newest_user['id'],
						'newest_user'		=> $newest_user['name'],
						);
		$stats['num_online_total'] = ($stats['num_online_members'] + $stats['num_invisible'] + $stats['num_guests']);

		$template->setVar('num_online_members', $stats['num_online_members']);
		
		$template->setVar('newest_member',	sprintf($template->getVar('L_NEWESTMEMBER'),		$stats['newest_uid'], $stats['newest_user']));
		$template->setVar('total_users',	sprintf($template->getVar('L_TOTALUSERS'),			$stats['num_members']));
		$template->setVar('total_posts',	sprintf($template->getVar('L_TOTALPOSTS'),			($stats['num_topics'] + $stats['num_replies']), $stats['num_topics'], $stats['num_replies']));
		$template->setVar('online_stats',	sprintf($template->getVar('L_ONLINEUSERSTATS'),		$stats['num_online_total'], $stats['num_online_members'], $stats['num_guests'], $stats['num_invisible']));
		$template->setVar('most_users_ever',sprintf($template->getVar('L_MOSTUSERSEVERONLINE'),	$_DATASTORE['maxloggedin']['maxonline'], date("n/j/Y", bbtime($_DATASTORE['maxloggedin']['maxonlinedate'])), date("g:ia", bbtime($_DATASTORE['maxloggedin']['maxonlinedate']))));
		
		if($stats['num_online_total'] >= $_DATASTORE['maxloggedin']['maxonline']) {
			$maxloggedin	= array('maxonline' => $stats['num_online_total'], 'maxonlinedate' => time());
			$query			= $dba->prepareStatement("UPDATE ". K4DATASTORE ." SET data = ? WHERE varname = ?");
			
			$query->setString(1, serialize($maxloggedin));
			$query->setString(2, 'maxloggedin');
			$query->executeUpdate();

			if(!@touch(CACHE_DS_FILE, time()-86460)) {
				@unlink(CACHE_DS_FILE);
			}
		}
		
		// Show the forum status icons
		$template->setVisibility('forum_status_icons', TRUE);
		
		$groups				= array();

		// Set the usergroups legend list
		foreach($_USERGROUPS as $group) {
			if($group['display_legend'] == 1)
				$groups[]	= $group;
		}

		$groups				= &new FAArrayIterator($groups);
		$template->setList('usergroups_legend', $groups);

		/* Set the forums template to content variable */
		$template->setFile('forum_info', 'forum_info.html');
		
		$template->setVar('can_see_board', get_map($request['user'], 'can_see_board', 'can_view', array()));
		
		k4_bread_crumbs($template, $dba, 'L_HOME');
		
		/*
		// alter the information table
		$request['dba']->alterTable(K4INFO, "ADD reply_id INT UNSIGNED NOT NULL DEFAULT 0");
		$request['dba']->alterTable(K4INFO, "ADD topic_id INT UNSIGNED NOT NULL DEFAULT 00");
		$request['dba']->alterTable(K4INFO, "ADD forum_id INT UNSIGNED NOT NULL DEFAULT 0");
		$request['dba']->alterTable(K4INFO, "ADD category_id INT UNSIGNED NOT NULL DEFAULT 0");
		
		// add reply info
		$replies = $request['dba']->executeQuery("SELECT * FROM ". K4REPLIES);
		while($replies->next()) {
			$temp		= $replies->current();
			$request['dba']->executeUpdate("UPDATE ". K4INFO ." SET reply_id = ". $temp['reply_id'] .", topic_id = ". $temp['topic_id'] .", forum_id = ". $temp['forum_id'] .", category_id = ". $temp['category_id'] ." WHERE id = ". $temp['reply_id']);
		}

		// add topic info
		$topics = $request['dba']->executeQuery("SELECT * FROM ". K4TOPICS);
		while($topics->next()) {
			$temp		= $topics->current();
			$request['dba']->executeUpdate("UPDATE ". K4INFO ." SET topic_id = ". $temp['topic_id'] .", forum_id = ". $temp['forum_id'] .", category_id = ". $temp['category_id'] ." WHERE id = ". $temp['topic_id']);
		}

		// add forum info
		$forums = $request['dba']->executeQuery("SELECT * FROM ". K4FORUMS);
		while($forums->next()) {
			$temp		= $forums->current();
			$request['dba']->executeUpdate("UPDATE ". K4INFO ." SET forum_id = ". $temp['forum_id'] .", category_id = ". $temp['category_id'] ." WHERE id = ". $temp['forum_id']);
		}

		// add category info
		$cats = $request['dba']->executeQuery("SELECT * FROM ". K4CATEGORIES);
		while($cats->next()) {
			$temp		= $cats->current();
			$request['dba']->executeUpdate("UPDATE ". K4INFO ." SET category_id = ". $temp['category_id'] ." WHERE id = ". $temp['category_id']);
		}
		*/
	}
}

$app = &new K4Controller('forum_base.html');
$app->setAction('', new K4DefaultAction);
$app->setDefaultEvent('');

$app->setAction('markforums', new MarkForumsRead);

$app->execute();

?>