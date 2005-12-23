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
* @author Peter Goodman
* @version $Id: index.php 160 2005-07-18 16:28:46Z Peter Goodman $
* @package k42
*/

require "includes/filearts/filearts.php";
require "includes/k4bb/k4bb.php";

class K4DefaultAction extends FAAction {
	function execute(&$request) {
				
		//$action = new AdminCSSRequestAction();
		//return $action->execute($request);
		
		global $_DATASTORE, $_USERGROUPS, $_QUERYPARAMS;
		
		// Member/Guest specifics
		if(!$request['user']->isMember()) {
			$request['template']->setVar('welcome_title', sprintf($request['template']->getVar('L_WELCOMETITLE'), $request['template']->getVar('bbtitle')));
			$request['template']->setFile('quick_login', 'login_form_quick.html');
			$request['template']->setVisibility('welcome_msg', TRUE);
		}
		
		// The content panel
		$request['template']->setFile('content', 'forums.html');
		
		$forums	= &new K4ForumsIterator($request['dba'], "SELECT * FROM ". K4FORUMS ." WHERE parent_id=0 ORDER BY row_order ASC");
		//$categories	= &new K4ForumsIterator($request['dba'], "SELECT * FROM ". K4FORUMS ." WHERE row_type=". CATEGORY ." AND parent_id = 0 ORDER BY row_order ASC");
				
		$request['template']->setVisibility('no_forums', (!$forums->hasNext() ? TRUE : FALSE));
		$request['template']->setList('tlforums', $forums);
		//$request['template']->setList('categories', $categories);
		
		// Set the online users list
		$user_extra			= $request['user']->isMember() ? ' OR (seen > 0 AND user_id = '. intval($request['user']->get('id')) .')' : '';
		$expired							= time() - ini_get('session.gc_maxlifetime');
		$online_users						= $request['dba']->executeQuery("SELECT * FROM ". K4SESSIONS ." WHERE ((seen >= $expired) $user_extra) AND ((user_id > 0) OR (user_id = 0 AND name <> '')) GROUP BY name ORDER BY seen DESC");
		$online_users						= &new K4OnlineUsersIterator($request['dba'], '', $online_users);
		$request['template']->setList('online_users', $online_users);
		
		//$newest_user						= $request['dba']->getRow("SELECT name, id FROM ". K4USERS ." ORDER BY id DESC LIMIT 1");
		$expired							= time() - ini_get('session.gc_maxlifetime');

		$stats = array('num_online_members'	=> intval(Globals::getGlobal('num_online_members')),
						'num_invisible'		=> intval(Globals::getGlobal('num_online_invisible')),
						'num_topics'		=> intval($_DATASTORE['forumstats']['num_topics']),
						'num_replies'		=> intval($_DATASTORE['forumstats']['num_replies']),
						'num_members'		=> intval($_DATASTORE['forumstats']['num_members']),
						'num_guests'		=> $request['dba']->getValue("SELECT COUNT(*) FROM ". K4SESSIONS ." WHERE seen >= $expired AND user_id=0"),
						'newest_uid'		=> $_DATASTORE['forumstats']['newest_user_id'],
						'newest_user'		=> $_DATASTORE['forumstats']['newest_user_name'],
						);
		$stats['num_online_total'] = ($stats['num_online_members'] + $stats['num_invisible'] + $stats['num_guests']);

		$request['template']->setVar('num_online_members', $stats['num_online_members']);
		
		$request['template']->setVar('newest_member',	sprintf($request['template']->getVar('L_NEWESTMEMBER'),		$stats['newest_uid'], $stats['newest_user']));
		$request['template']->setVar('total_users',	sprintf($request['template']->getVar('L_TOTALUSERS'),			$stats['num_members']));
		$request['template']->setVar('total_posts',	sprintf($request['template']->getVar('L_TOTALPOSTS'),			($stats['num_topics'] + $stats['num_replies']), $stats['num_topics'], $stats['num_replies']));
		$request['template']->setVar('online_stats',	sprintf($request['template']->getVar('L_ONLINEUSERSTATS'),		$stats['num_online_total'], $stats['num_online_members'], $stats['num_guests'], $stats['num_invisible']));
		$request['template']->setVar('most_users_ever',sprintf($request['template']->getVar('L_MOSTUSERSEVERONLINE'),	$_DATASTORE['maxloggedin']['maxonline'], date("n/j/Y", bbtime($_DATASTORE['maxloggedin']['maxonlinedate'])), date("g:ia", bbtime($_DATASTORE['maxloggedin']['maxonlinedate']))));
		
		if($stats['num_online_total'] >= $_DATASTORE['maxloggedin']['maxonline']) {
			$maxloggedin	= array('maxonline' => $stats['num_online_total'], 'maxonlinedate' => time());
			$query			= $request['dba']->prepareStatement("UPDATE ". K4DATASTORE ." SET data = ? WHERE varname = ?");
			
			$query->setString(1, serialize($maxloggedin));
			$query->setString(2, 'maxloggedin');
			$query->executeUpdate();

			reset_cache('datastore');
		}
		
		// Show the forum status icons
		$request['template']->setVisibility('forum_status_icons', TRUE);
		$request['template']->setFile('content_extra', 'forum_status_icons.html');
		
		$groups				= array();

		// Set the usergroups legend list
		if(is_array($_USERGROUPS) && !empty($_USERGROUPS)) {
			foreach($_USERGROUPS as $group) {
				if($group['display_legend'] == 1)
					$groups[]	= $group;
			}
		}

		$groups				= &new FAArrayIterator($groups);
		$request['template']->setList('usergroups_legend', $groups);

		/* Set the forum stats */
		$request['template']->setFile('forum_info', 'forum_info.html');
		
		$request['template']->setVar('can_see_board', get_map( 'can_see_board', 'can_view', array()));

		k4_bread_crumbs($request['template'], $request['dba'], 'L_HOME');		
	}
}

$app = &new K4Controller('forum_base.html');
$app->setAction('', new K4DefaultAction);
$app->setDefaultEvent('');

$app->setAction('markforums', new MarkForumsRead);

$app->execute();

?>