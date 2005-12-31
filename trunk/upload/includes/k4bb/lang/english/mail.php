<?php
/**
* k4 Bulletin Board, mail.php
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
* @version $Id: mail.php 110 2005-06-13 20:48:58Z Peter Goodman $
* @package k42
*/

if (!defined('IN_K4'))
	return;

$_LANG += array(

'L_FORUMS'				=> 'Forums',
'L_REPLYTO'				=> 'Reply to',
'L_TOPICPOSTIN'			=> 'Topic posted in',
'L_TOPICSUBSCRIBEEMAIL'	=> "Hello %s,\n\n%s has just replied to a topic you have subscribed to entitled: %s.\n\nThe topic is located at:\n". K4_URL ."/viewtopic.php?id=%s\n\n\nThere may be other replies as well, however you will not receive any more notifications until you visit the forum topic again.\n\nYours,\n%s team.\n\n~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~\nUnsubscription Information:\n\nTo unsubscribe from this topic, please visit this page:\n". K4_URL ."/viewtopic.php?act=untrack&id=%s",
'L_FORUMSUBSCRIBEEMAIL'	=> "Hello %s,\n\n%s has just posted a topic in the forum you have subscribed to entitled: %s.\n\nThe topic is located at:\n". K4_URL ."/viewforum.php?id=%s\n\n\nThere may be other topics as well, however you will not receive any more notifications until you visit the forum again.\n\nYours,\n%s team.\n\n~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~\nUnsubscription Information:\n\nTo unsubscribe from this forum, please visit this page:\n". K4_URL ."/viewforum.php?act=untrack&id=%s",
'L_PASSWORDCHANGEEMAIL'	=> "Hello %s,\n\nyour new password is %s. To log in, simply go to: ". K4_URL ."/member.php?act=login.",
'L_REGISTEREMAILMSG'	=> "%s,\n\nThanks for registering at the %s forums! We are glad you have chosen to be a part of our community and we hope you enjoy your stay.\n\nThanks again,\n%s team.",
'L_REGISTEREMAILRMSG'	=> "%s,\n\nThanks for registering at the %s forums! We are glad you have chosen to be a part of our community and we hope you enjoy your stay.\n\nIn order to complete your registration, please go to the following website address to confirm your registration:\n\n%s\n\n\n\nThanks again,\n%s team.",


);

?>