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
* @author Thomas "Thasmo" Deinhamer (thasmo at gmail dot com)
* @version $Id: mail.php 110 2005-06-13 20:48:58Z Peter Goodman $
* @package k42
*/

if (!defined('IN_K4'))
	return;

$_LANG += array(

	'L_FORUMS'				=> 'Foren',
	'L_REPLYTO'				=> 'Antworten auf',
'L_TOPICPOSTIN'			=> 'Topic posted in',
'L_TOPICSUBSCRIBEEMAIL'	=> "Hello %s,\n\n%s has just replied to a topic you have subscribed to entitled: %s.\n\nThe topic is located at:\n". K4_URL ."/viewtopic.php?id=%s\n\n\nThere may be other replies as well, however you will not receive any more notifications until you visit the forum topic again.\n\nYours,\n%s team.\n\n~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~\nUnsubscription Information:\n\nTo unsubscribe from this topic, please visit this page:\n". K4_URL ."/viewtopic.php?act=untrack&id=%s",
'L_FORUMSUBSCRIBEEMAIL'	=> "Hello %s,\n\n%s has just posted a topic in the forum you have subscribed to entitled: %s.\n\nThe topic is located at:\n". K4_URL ."/viewforum.php?id=%s\n\n\nThere may be other topics as well, however you will not receive any more notifications until you visit the forum again.\n\nYours,\n%s team.\n\n~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~\nUnsubscription Information:\n\nTo unsubscribe from this forum, please visit this page:\n". K4_URL ."/viewforum.php?act=untrack&id=%s",
'L_PASSWORDCHANGEEMAIL'	=> "Hello %s,\n\nyour new password is %s. To log in, simply go to: ". K4_URL ."/member.php?act=login.",
'L_REGISTEREMAILMSG'	=> "%s,\n\nThanks for registering at the %s forums! We are glad you have chosen to be a part of our community and we hope you enjoy your stay.\n\nThanks again,\n%s team.",
'L_REGISTEREMAILRMSG'	=> "%s,\n\nThanks for registering at the %s forums! We are glad you have chosen to be a part of our community and we hope you enjoy your stay.\n\nIn order to complete your registration, please go to the following website address to confirm your registration:\n\n%s\n\n\n\nThanks again,\n%s team.",

);

?>