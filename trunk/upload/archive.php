<?php
/**
* k4 Bulletin Board, common.php
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
* @version $Id$
* @package k42
*/

require "includes/filearts/filearts.php";
require "includes/k4bb/k4bb.php";

class K4DefaultAction extends FAAction {
	function execute(&$request) {
		
		/* set the breadcrumbs bit */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');

		if(!isset($_REQUEST['forum']) || !isset($_REQUEST['topic'])) {
			$action = new K4InformationAction(new K4LanguageElement('L_TOPICDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}

		$forum_id	= intval($_REQUEST['forum']);
		$topic_id	= intval($_REQUEST['topic']);
		$page		= isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

		$file		= BB_BASE_DIR .'/archive/'. $forum_id .'/'. $topic_id .'-'. $page .'.xml';

		if(!file_exists($file)) {
			$action = new K4InformationAction(new K4LanguageElement('L_TOPICDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}

		$parser		= new RSS_Parser();
		$feed		= $parser->Parse($file);
		$channel	= $feed->channel;
		$posts		= $feed->items;
		
		k4_bread_crumbs($request['template'], $request['dba'], $channel->title);
		
		$request['template']->setVar('post_name', $channel->title);
		$request['template']->setVar('post_link', $channel->link);
		$request['template']->setVar('post_body_text', $channel->description);
		$request['template']->setVar('post_forum', $channel->subject);
		$request['template']->setVar('post_post_id', $channel->post_id);
		$request['template']->setVar('post_poster_name', $channel->author_name);
		$request['template']->setVar('post_poster_id', $channel->author_id);
		$request['template']->setVar('post_page', $channel->page);
		
		if($channel->num_pages > 1) {
			$separator = '';
			$html = '';
			for($i = 1; $i <= $channel->num_pages; $i++) {
				$html .= $separator. '<a href="archive.php?forum='. $forum_id .'&topic='. $topic_id .'&page='. $i .'" title="" '. ($i == $channel->page ? 'style="font-weight:bold;"' : '') .'>'. $i .'</a>';
				$separator = ', ';
			}
			$request['template']->setVar('archive_pager', $html);
			$request['template']->setList('posts', new XMLArchivedPostsIterator($posts));
		}

		$request['template']->setFile('content', 'viewtopic_lofi.html');
	}
}

class XMLArchivedPostsIterator extends FAArrayIterator {
	function XMLArchivedPostsIterator(&$posts) {
		$this->__construct($posts);
	}

	function current() {
		$temp					= parent::current();
		
		$post = array();
		$post['post_name']			= $temp->title;
		$post['post_link']			= $temp->link;
		$post['post_body_text']		= $temp->description;
		$post['post_forum']			= $temp->subject;
		$post['post_post_id']		= $temp->post_id;
		$post['post_poster_name']	= $temp->author_name;
		$post['post_poster_id']		= $temp->author_id;
				
		return $post;
	}
}

$app = &new K4Controller('forum_base_lofi.html');
$app->setAction('', new K4DefaultAction);
$app->setDefaultEvent('');
$app->execute();

?>