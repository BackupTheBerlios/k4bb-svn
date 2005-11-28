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
'L_TOPICPOSTIN'			=> 'Beantwortetes Thema',
'L_TOPICSUBSCRIBEEMAIL'	=> "Hallo %s,\n\n%s hat gerade auf ein Thema, welches abonniert wurde, geantwortet: %s.\n\nDas Thema befindet sich hier:\n". K4_URL ."/viewtopic.php?id=%s\n\n\nMöglicherweise wurden inzwischen weitere Antworten verfasst. Bis zur erneuten Durchsicht des Themas werden keine weiteren Benachrichtigungen gesendet.\n\nMit freundlichen Grüßen,\n%s Team.\n\n~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~\nInformation zur Auflösung des Abonnements:\n\nZum Auflösen des Abonnements dieses Themas diese Seite besuchen:\n". K4_URL ."/viewtopic.php?act=untrack&id=%s",
'L_FORUMSUBSCRIBEEMAIL'	=> "Hallo %s,\n\n%s hat soeben ein Thema in einem Forum verfasst welches abonniert wurde: %s.\n\nDas Thema befindet sich hier:\n". K4_URL ."/viewforum.php?id=%s\n\n\nMöglicherweise wurden inzwischen weitere Themen verfasst. Bis zur erneuten Durchsicht des Forums werden keine weiteren Benachrichtigungen gesendet.\n\nMit freundlichen Grüßen,\n%s Team.\n\n~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~\nInformation zur Auflösung des Abonnements:\n\nZum Auflösen des Abonnements dieses Forums diese Seite besuchen:\n". K4_URL ."/viewforum.php?act=untrack&id=%s",
'L_PASSWORDCHANGEEMAIL'	=> "Hallo %s,\n\ndas neue Passwort ist %s. Zur Anmeldung: ". K4_URL ."/member.php?act=login.",
'L_REGISTEREMAILMSG'	=> "%s,\n\nDanke für die Registrierung auf den %s Foren! Wir freuen uns, dass du dich entschieden hast unseren Foren beizutreten und hoffen, dass du deinen Aufenthalt genießt.\n\nDanke sehr,\n%s Team.",
'L_REGISTEREMAILRMSG'	=> "%s,\n\nDanke für die Registrierung auf den %s Foren! Wir freuen uns, dass du dich entschieden hast unseren Foren beizutreten und hoffen, dass du deinen Aufenthalt genießt.\n\nUm die Registrierung abzuschließen bitten wir dich diese Seite unserer Foren zu besuchen:\n\n%s\n\n\n\nDanke sehr,\n%s Team.",

);

?>