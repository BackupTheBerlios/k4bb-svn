<?php
/**
* k4 Bulletin Board, mod.php
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

$app->setAction('deletetopic', new DeleteTopic);
$app->setAction('deletereply', new DeleteReply);
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