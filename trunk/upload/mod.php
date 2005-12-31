<?php
/**
* k4 Bulletin Board, mod.php
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
* @version $Id: mod.php 110 2005-06-13 20:48:58Z Peter Goodman $
* @package k42
*/

ob_start();

require "includes/filearts/filearts.php";
require "includes/k4bb/k4bb.php";

class K4DefaultAction extends FAAction {
	function execute(&$request) {
		
		no_perms_error($request);

		return TRUE;
	}
}

$app = new K4controller('forum_base.html');

$app->setAction('', new K4DefaultAction);
$app->setDefaultEvent('');

$app->setAction('deletetopic', new DeletePost(TOPIC));
$app->setAction('deletereply', new DeletePost(REPLY));
$app->setAction('locktopic', new LockTopic(1));
$app->setAction('unlocktopic', new LockTopic(0));

$app->setAction('moderate_forum', new ModerateForum);
$app->setAction('topic_simpleupdate', new SimpleUpdateTopic);
$app->setAction('get_topic_title', new getTopicTitle);
$app->setAction('move_topics', new MoveTopics);

/* Bad post reports */
$app->setAction('viewbpreports', new ViewBadPostReports);
$app->setAction('deletereport', new DeleteBadPostReport);

/* User related moderations */
$app->setAction('findusers', new ModFindUsers);
$app->setAction('banuser', new ModBanUser);
$app->setAction('warnuser', new ModWarnUser);
$app->setAction('flaguser', new ModFlagUser);
$app->setAction('sendwarning', new ModSendWarning);
$app->setAction('hardbanuser', new HardBanUser);
$app->setAction('baniprange', new BanIPRange);
$app->setAction('liftban', new LiftIPBan);
$app->setAction('bannedips', new ModViewBanneIPs);

$app->execute();

ob_flush();

?>