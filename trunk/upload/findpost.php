<?php
/**
* k4 Bulletin Board, findpost.php
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
* @version $Id: findpost.php 154 2005-07-15 02:56:28Z Peter Goodman $
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
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INVALIDPOST');
			
			$action = new K4InformationAction(new K4LanguageElement('L_POSTDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}

		$post	= $request['dba']->getRow("SELECT * FROM ". K4POSTS ." WHERE post_id = ". intval($_REQUEST['id']));
		
		k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');

		if(!is_array($post) || empty($post)) {
			
			if($next || $prev)
				header("Location: ". referer());
			
			$action = new K4InformationAction(new K4LanguageElement('L_POSTDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}
		
		/* If this is a topic */
		if($post['row_type'] == TOPIC) {
			
			header("Location: viewtopic.php?id=". $post['post_id']);

		/* If this is a reply */	
		} else {
			
			if($next || $prev)
				header("Location: ". referer());

			$forum				= $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($post['forum_id']));
		
			/* Check the forum data given */
			if(!$forum || !is_array($forum) || empty($forum)) {
				$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);
				return $action->execute($request);
			}
				
			/* Make sure the we are trying to delete from a forum */
			if(!($forum['row_type'] & FORUM)) {
				$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);
				return $action->execute($request);
			}
			
			/* If the number of replies on this topic is greater than the posts per page for this forum */
			if($topic['num_replies'] > $forum['postsperpage']) {
				
				$whereinline	= $request['dba']->getValue("SELECT COUNT(*) FROM ". K4POSTS ." WHERE parent_id = ". intval($post['parent_id']) ." AND row_order <= ". intval($post['created']) ." ORDER BY created ASC");
				
				$page		= ceil($whereinline / $forum['postsperpage']);
				$page		= $page <= 0 ? 1 : $page;

				header("Location: viewtopic.php?id=". $post['post_id'] ."&page=". intval($page) ."&limit=". $forum['postsperpage'] ."&order=ASC&sort=created&daysprune=0&p=". $post['post_id'] ."#p". $post['post_id']);
				return;

			} else {
				header("Location: viewtopic.php?id=". $post['parent_id'] ."&p=". $post['post_id'] ."#p". $post['post_id']);
				return;
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