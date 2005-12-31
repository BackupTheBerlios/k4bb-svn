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