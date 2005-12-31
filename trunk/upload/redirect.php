<?php
/**
* k4 Bulletin Board, files.php
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
* @author Geoffrey Goodman
* @author James Logsdon
* @version $Id: redirect.php 154 2005-07-15 02:56:28Z Peter Goodman $
* @package k42
*/

ob_start();

require "includes/filearts/filearts.php";
require "includes/k4bb/k4bb.php";

class K4DefaultAction extends FAAction {
	function execute(&$request) {
		
		global $_QUERYPARAMS;
				
		if(!isset($_REQUEST['id']) || !$_REQUEST['id'] || intval($_REQUEST['id']) == 0) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INVALIDFORUM');
			
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}
			
		/* Get the current forum/category */
		$forum					= $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($_REQUEST['id']));

		if(!$forum || !is_array($forum) || empty($forum)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INVALIDFORUM');
			
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}

		if($forum['is_link'] == 1) {
			if($forum['is_forum'] == 1) {
				if(($forum['row_right'] - $forum['row_left']) > 0) {
					header("Location: viewforum.php?id=". intval($forum['forum_id']));
				}
			}

			if(!isset($forum['link_href']) || $forum['link_href'] == '') {
				k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
				
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDLINKFORUM'), 'content', FALSE);
				return $action->execute($request);
			}

			$request['dba']->executeUpdate("UPDATE ". K4FORUMS ." SET link_redirects=link_redirects+1 WHERE forum_id=". intval($forum['forum_id']));

			header("Location: ". $forum['link_href']);

		} else {
			header("Location: viewforum.php?id=". intval($forum['forum_id']));
		}
		
		return TRUE;
	}
}

$app	= new K4controller('forum_base.html');

$app->setAction('', new K4DefaultAction);
$app->setDefaultEvent('');

$app->execute();

ob_flush();

?>