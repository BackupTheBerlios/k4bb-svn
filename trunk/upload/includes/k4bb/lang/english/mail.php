<?php
/**
* k4 Bulletin Board, mail.php
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
* @author Geoffrey Goodman
* @author James Logsdon
* @version $Id: mail.php,v 1.1 2005/05/24 20:05:09 k4st Exp $
* @package k42
*/

if (!defined('IN_K4'))
	return;

$_LANG += array(

'L_FORUMS'				=> 'Forums',
'L_REPLYTO'				=> 'Reply to',
'L_TOPICPOSTIN'			=> 'Topic posted in',
'L_TOPICSUBSCRIBEEMAIL'	=> "Hello %s,\r\n\r\n%s has just replied to a topic you have subscribed to entitled: %s.\r\n\r\nThe topic is located at:\r\n". K4_URL ."/viewtopic.php?id=%s\r\n\r\n\r\nThere may be other replies as well, however you will not receive any more notifications until you visit the forum topic again.\r\n\r\nYours,\r\n%s team.\r\n\r\n~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~\r\nUnsubscription Information:\r\n\r\nTo unsubscribe from this topic, please visit this page:\r\n". K4_URL ."/viewtopic.php?act=untrack&id=%s",
'L_FORUMSUBSCRIBEEMAIL'	=> "Hello %s,\r\n\r\n%s has just posted a topic in the forum you have subscribed to entitled: %s.\r\n\r\nThe topic is located at:\r\n". K4_URL ."/viewforum.php?id=%s\r\n\r\n\r\nThere may be other topics as well, however you will not receive any more notifications until you visit the forum again.\r\n\r\nYours,\r\n%s team.\r\n\r\n~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~\r\nUnsubscription Information:\r\n\r\nTo unsubscribe from this forum, please visit this page:\r\n". K4_URL ."/viewforum.php?act=untrack&id=%s",

);

?>