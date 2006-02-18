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
		$topic		= $feed->channel;
		$posts		= $feed->items;
		
		k4_bread_crumbs($request['template'], $request['dba'], $feed->channel->title);

		$request['template']->setFile('content', 'viewtopic_lofi.html');
	}
}

class XMLPostsIterator extends FAArrayIterator {
	
}

$app = &new K4Controller('forum_base_lofi.html');
$app->setAction('', new K4DefaultAction);
$app->setDefaultEvent('');
$app->execute();

?>