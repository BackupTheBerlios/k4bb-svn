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
* @author Thomas "Thasmo" Deinhamer (thasmo at gmail dot com)
* @version $Id: mod.php 158 2005-07-18 02:55:30Z Peter Goodman $
* @package k42
*/

if (!defined('IN_K4'))
	return;

$_LANG += array(

	'L_MODCP'				=> 'Moderator Kontrollzentrum',
	'L_SELECT'				=> 'W�hle',
	'L_WITHTOPICS'			=> 'Mit den gew�hlten Themen',
	'L_WITHTOPIC'			=> 'Mit diesem Thema',
	'L_SELECTTOPIC'			=> 'Hier klicken um dieses Thema zur Moderation hinzuzuf�gen.',
	'L_DELETEREPLY'			=> 'Antwort l�schen',
	'L_DELETETOPIC'			=> 'Thema l�schen',
	'L_LOCKTOPIC'			=> 'Thema schlie�en',
	'L_UNLOCKTOPIC'			=> 'Thema �ffnen',

/**
 * Forum view moderator functions
 */

/* Permission Things */
	'L_VIEW'				=> 'Zeigen',
	'L_READ'				=> 'Lesen',
	'L_POST'				=> 'Verfassen',
	'L_ATTACH'				=> 'Dateien anh�ngen',
	'L_EDIT'				=> 'Editieren',
	'L_ANNOUNCE'			=> 'Themen ank�ndigen',
	'L_POLLCREATE'			=> 'Umfragen starten',

	'L_ALL'					=> 'JEDER',
	'L_REG'					=> 'REGISTRIERTE',
	'L_PRIVATE'				=> 'PRIVATE',
	'L_MODS'				=> 'MODERATOREN',
	'L_ADMINS'				=> 'ADMINISTRATOREN',
	'L_GLOBAL'				=> 'Globale Ank�ndigung',
	'L_NORMAL'				=> 'Normal',
	'L_FEATURE'				=> 'Aufgezeigte Themen',
	'L_MOVE'				=> 'Verschobene Themen',
	'L_FEATURETOPIC'		=> 'Thema aufzeigen',
	'L_NORMALIZE'			=> 'Themen normalisieren',
'L_QUEUE'				=> 'Queue Topics',
	'L_LOCKTOPICS'			=> 'Themen schlie�en',
	'L_STICKTOPICS'			=> 'Themen anheften',
	'L_ANNOUNCETOPICS'		=> 'Themen ank�ndigen',
	'L_FEATURETOPICS'		=> 'Themen aufzeigen',
	'L_SETASNORMALTOPICS'	=> 'Als normale Themen setzen',
	'L_MOVECOPYTOPICS'		=> 'Themen verschieben/kopieren',

	'L_LOCKTOPIC'			=> 'Thema schlie�en',
	'L_SPLITTOPIC'			=> 'Thema teilen',
'L_QUEUETOPIC'			=> 'Queue Topic',
	'L_STICKTOPIC'			=> 'Thema anheften',
	'L_ANNOUNCETOPIC'		=> 'Thema ank�ndigen',
	'L_FEATURETOPIC'		=> 'Thema pr�sentieren',
	'L_SETASNORMALTOPIC'	=> 'Als normales Thema setzen',
	'L_MOVECOPYTOPIC'		=> 'Thema verschieben/kopieren',
	'L_SUBSCRIBETOTOPIC'	=> 'Thema abonnieren',

	'L_DELETETOPICS'		=> 'Themen l�schen',
'L_QUEUE'				=> 'Queue Topics',
	'L_DELETE'				=> 'Themen/Beitr�ge endg�ltig l�schen',
	'L_SOFTDELETE'			=> 'Themen/Beitr�ge l�schen (Papierkorb)',
	'L_SUBSCRIBETOTOPICS'	=> 'Themen abonnieren',
	'L_PERFORMACTION'		=> 'Aktion ausf�hren',
	'L_NEEDSELECTACTION'	=> 'Du musst eine zu ausf�hrende Aktion f�r diese Themen w�hlen.',
	'L_NEESSELECTTOPICS'	=> 'Du musst mindestens ein Thema zur Moderation ausw�hlen.',
	'L_LOCKEDTOPICS'		=> 'Die ausgew�hlten Themen wurden erfolgreich geschlossen.',
	'L_STUCKTOPICS'			=> 'Die ausgew�hlten Themen wurden erfolgreich als angeheftet.',
	'L_ANNOUNCEDTOPICS'		=> 'Die ausgew�hlten Themen wurden erfolgreich angek�ndigt.',
	'L_FEATUREDTOPICS'		=> 'Die ausgew�hlten Themen wurden erfolgreich pr�sentiert.',
	'L_NORMALIZEDTOPICS'	=> 'Die ausgew�hlten Themen wurden als normale Themen markiert.',
'L_QUEUEDTOPICS'		=> 'Successfully added the selected topics to the moderator\'s queue.',
	'L_SUBSCRIBEDTOPICS'	=> 'Die ausgew�hlten Themen wurden erfolgreich abonniert.',
	'L_DELETEDTOPICS'		=> 'Die ausgew�hlten Themen wurden erfolgreich gel�scht.',
	'L_MOVETOPICS'			=> 'Themen verschieben',
	'L_MOVEDTOPICS'			=> 'Die ausgew�hlten Themen wurden erfolgreich von <strong>%s</strong> nach <strong>%s</strong> verschoben/kopiert.',
'L_QUEUETOPICS'			=> 'Queue Topics',

	'L_COPYTOFORUM'			=> 'Eine Kopie dieser Themen in einem anderen Forum erstellen.',
	'L_MOVEWITHOUTTRACKER'	=> 'Thema in ein anderes Forum verschieben ohne eine Weiterleitung zu hinterlassen.',
	'L_MOVEWITHTRACKER'		=> 'Thema in ein anderes Forum verschieben und eine Weiterleitung erstellen.',
	'L_MOVETOFORUM'			=> 'Verschieben/Kopieren in Forum',
	'L_MOVECOPY'			=> 'Verschieben/Kopieren',
'L_TOPICPENDINGMOD'		=> 'The selected topic is pending moderation.',
	'L_TOPICISHIDDEN'		=> 'Das gew�hlte Thema ist im Moment nicht sichtbar oder wartet darauf entfernt zu werden.',
'L_CANTMODNONFORUM'		=> 'You cannot moderate a non-forum.',
'L_DESTFORUMDOESNTEXIST'=> 'The selected destination forum for the moderated posts does not exist.',

/* Forum view stuff */
	'L_MODERATORSPANEL'		=> 'Moderator Konsole',
'L_VIEWQUEUEDTOPICS'	=> 'View Queued Topics',
	'L_VIEWLOCKEDTOPICS'	=> 'Geschlossene Themen zeigen',
	'L_BANUSER'				=> 'Benutzer sperren',
	'L_FLAGUSER'			=> 'Benutzer markieren',
	'L_WARNUSER'			=> 'Benutzer warnen',
	'L_MANAGEATTACHMENTS'	=> 'Dateianlagen verwalten',
'L_VIEWBADPOSTREPORTS'	=> 'View Bad Post Reports',
'L_BADPOSTREPORTS'		=> 'Bad Post Reports',
'L_NOBADPOSTREPORTS'	=> 'There are currently no reports of bad posts.',
'L_BADPOSTREPORTS'		=> 'Bad Post Reports',
	'L_CANTMODACATEGORY'	=> 'Du kannst keine Kategorie moderieren.',
	'L_TOPICTITLEIS'		=> 'Der ausgew�hlte Thementitel ist: <strong>%s</strong>.',

/* Bad post reports */
	'L_REPORT'				=> 'Melden',
	'L_NUMREQUESTS'			=> 'Anzahl der Anfragen',
	'L_REPORTER'			=> 'Meldung von',
	'L_REPORTEDON'			=> 'Meldung �ber',
	'L_MODOPTIONS'			=> 'Optionen der Moderation',
	'L_DELETEREPORT'		=> 'Meldung l�schen',
	'L_DELETEREPLY'			=> 'Antwort l�schen',
'L_REPORTDOESNTEXIST'	=> 'The selected bad post report does not seem to exist.',
'L_REMOVEDBADPOSTREPORT'=> 'Successfully removed the selected bad post report.',

/* User banning, warning and flagging */
	'L_NOBANNEDIPS'			=> 'Zur Zeit sind keine IPs gesperrt.',
	'L_VIEWBANNEDIPS'		=> 'Gesperrte IPs zeigen',
	'L_FINDAUSER'			=> 'Benutzer finden',
	'L_FINDUSERS'			=> 'Benutzer finden',
	'L_WARNHIGH'			=> 'Hohe Warnstufe',
	'L_WARNMED'				=> 'Mittlere Warnstufe',
	'L_WARNLOW'				=> 'Niedrige Warnstufe',
	'L_UNFLAGUSER'			=> 'Benutzerkennzeichnung aufheben',
	'L_FLAGGEDUSER'			=> 'Der Benutzer <strong>%s</strong> wurde erfolgreich zur �berwachung gekennzeichnet.',
	'L_UNFLAGGEDUSER'		=> 'Der Benutzer <strong>%s</strong> wurde erfolgreich von der �berwachung aufgehoben.',
	'L_UNBANUSER'			=> 'Benutzersperre aufheben',
	'L_SENDWARNING'			=> 'Benutzer warnen',
	'L_INSERTWARNING'		=> 'Du musst eine Warnnachricht eingeben.',
	'L_WARNINGMESSAGE'		=> 'Warnnachricht',
'L_WARNINGTEXTAREA'		=> "%s,\n\nYou are being warned concerning your lack of morals and/or etiquette on this forum. If you fail to discontinue your poor behavior, you will be banned from the forums.\n\nSincerely,\n%s.",
	'L_WARNINGBYEMAIL'		=> 'Folgende Nachricht wird an den ausgew�hlten Benutzer per e-Mail gesendet.',
	'L_WARNING'				=> 'Warnung',
	'L_SENTWARNING'			=> 'Erfolgreich eine Warnung an <strong>%s</strong> gesendet.',
	'L_UNBANNEDUSER'		=> 'Erfolgreich Sperre von dem Benutzer <strong>%s</strong> aufgehoben.',
	'L_BANNEDUSER'			=> 'Erfolgreich den Benutzer <strong>%s</strong> gesperrt.',
	'L_BANTHISUSER'			=> 'Diesen Benutzer sperren',
	'L_REASON'				=> 'Grund',
'L_BANREASON'			=> 'Your reason for banning this user. Leave this field blank if you do not want to *dignify* this person with a reason.',
	'L_FOR'					=> 'F�r',

	'L_INDEFINETELY'		=> 'f�r eine unbestimmte Zeitdauer',
	'L_THENEXT24HOURS'		=> '24 Stunden',
	'L_THENEXT48HOURS'		=> '48 Stunden',
	'L_THENEXTFIVEDAYS'		=> '5 Tage',
	'L_THENEXTTENDAYS'		=> '10 Tage',
	'L_THENEXTTWOWEEKS'		=> '2 Wochen',
	'L_THENEXTMONTH'		=> '1 Monat',
	'L_THENEXTSIXMONTHS'	=> '6 Monate',
	'L_THENEXTYEAR'			=> '1 Jahr',

'L_BANNEDUSERID'		=> '<strong>%s</strong>,<br /><br />You have been banned from the forums for the following reasons (if any):<br />%s<br /><br />This ban will expire on %s.',
'L_BANNEDUSERIP'		=> 'Hello,<br /><br />This IP address has been banned from the forums for the following reasons (if any):<br />%s<br /><br />This ban will expire on %s.',
	'L_YOURDEATH'			=> 'Die Ewigkeit dauert lange, besonders gegen Ende. - Woody Allen',

	'L_BANIPRANGE'			=> 'IP Bereich sperren',
	'L_IPRANGE'				=> 'IP Bereich',
	'L_BANNEDIPS'			=> 'Gesperrte IP Adressen',
	'L_LEFTBAN'				=> 'Sperre aufheben',
	'L_BANNEDIPRANGE'		=> 'Der gew�hlte IP Bereich wurde erfolgreich gesperrt.',
	'L_UNBANNEDIP'			=> 'Der IP Bereich f�r <strong>%s</strong> wurde erfolgreich entsperrt.',
'L_HOWTOBANIPRANGE'		=> 'Insert the IP address of the person you would like to ban. Insert a * for partial matches. <strong>Make sure to enter a full IP address (e.g. *.*.*.*)</strong>',
'L_MUSTINSERTIP'		=> 'Please insert an IP address to ban that is at least 7 characters long and at most 15 characters long.',

);

?>