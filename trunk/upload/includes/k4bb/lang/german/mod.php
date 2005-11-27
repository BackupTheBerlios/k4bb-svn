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
'L_SELECT'				=> 'Wähle',
'L_WITHTOPICS'			=> 'Mit den gewählten Themen',
'L_WITHTOPIC'			=> 'Mit diesem Thema',
'L_SELECTTOPIC'			=> 'Hier klicken um dieses Thema zur Moderation hinzuzufügen.',
'L_DELETEREPLY'			=> 'Antwort löschen',
'L_DELETETOPIC'			=> 'Thema löschen',
'L_LOCKTOPIC'			=> 'Thema schließen',
'L_UNLOCKTOPIC'			=> 'Thema öffnen',

/**
* Forum view moderator functions
*/

/* Permission Things */
'L_VIEW'				=> 'Zeigen',
'L_READ'				=> 'Lesen',
'L_POST'				=> 'Verfassen',
'L_ATTACH'				=> 'Dateien anhängen',
'L_EDIT'				=> 'Editieren',
'L_ANNOUNCE'			=> 'Themen ankündigen',
'L_POLLCREATE'			=> 'Umfragen starten',

'L_ALL'					=> 'JEDER',
'L_REG'					=> 'REGISTRIERTE',
'L_PRIVATE'				=> 'PRIVATE',
'L_MODS'				=> 'MODERATOREN',
'L_ADMINS'				=> 'ADMINISTRATOREN',
'L_GLOBAL'				=> 'Globale Ankündigung',
'L_NORMAL'				=> 'Normal',
'L_FEATURE'				=> 'Aufgezeigte Themen',
'L_MOVE'				=> 'Verschobene Themen',
'L_FEATURETOPIC'		=> 'Thema aufzeigen',
'L_NORMALIZE'			=> 'Themen normalisieren',
'L_QUEUE'				=> 'Themen revidieren',
'L_LOCKTOPICS'			=> 'Themen schließen',
'L_STICKTOPICS'			=> 'Themen anheften',
'L_ANNOUNCETOPICS'		=> 'Themen ankündigen',
'L_FEATURETOPICS'		=> 'Themen aufzeigen',
'L_SETASNORMALTOPICS'	=> 'Als normale Themen setzen',
'L_MOVECOPYTOPICS'		=> 'Themen verschieben/kopieren',

'L_LOCKTOPIC'			=> 'Thema schließen',
'L_SPLITTOPIC'			=> 'Thema teilen',
'L_QUEUETOPIC'			=> 'Thema revidieren',
'L_STICKTOPIC'			=> 'Thema anheften',
'L_ANNOUNCETOPIC'		=> 'Thema ankündigen',
'L_FEATURETOPIC'		=> 'Thema präsentieren',
'L_SETASNORMALTOPIC'	=> 'Als normales Thema setzen',
'L_MOVECOPYTOPIC'		=> 'Thema verschieben/kopieren',
'L_SUBSCRIBETOTOPIC'	=> 'Thema abonnieren',

'L_DELETETOPICS'		=> 'Themen löschen',
'L_DELETE'				=> 'Themen/Beiträge endgültig löschen',
'L_SOFTDELETE'			=> 'Themen/Beiträge löschen (Papierkorb)',
'L_SUBSCRIBETOTOPICS'	=> 'Themen abonnieren',
'L_PERFORMACTION'		=> 'Aktion ausführen',
'L_NEEDSELECTACTION'	=> 'Du musst eine zu ausführende Aktion für diese Themen wählen.',
'L_NEESSELECTTOPICS'	=> 'Du musst mindestens ein Thema zur Moderation auswählen.',
'L_LOCKEDTOPICS'		=> 'Die ausgewählten Themen wurden erfolgreich geschlossen.',
'L_STUCKTOPICS'			=> 'Die ausgewählten Themen wurden erfolgreich als angeheftet.',
'L_ANNOUNCEDTOPICS'		=> 'Die ausgewählten Themen wurden erfolgreich angekündigt.',
'L_FEATUREDTOPICS'		=> 'Die ausgewählten Themen wurden erfolgreich präsentiert.',
'L_NORMALIZEDTOPICS'	=> 'Die ausgewählten Themen wurden als normale Themen markiert.',
'L_QUEUEDTOPICS'		=> 'Die ausgewählten Themen wurden zur weiteren Revision hinzugefügt.',
'L_SUBSCRIBEDTOPICS'	=> 'Die ausgewählten Themen wurden erfolgreich abonniert.',
'L_DELETEDTOPICS'		=> 'Die ausgewählten Themen wurden erfolgreich gelöscht.',
'L_MOVETOPICS'			=> 'Themen verschieben',
'L_MOVEDTOPICS'			=> 'Die ausgewählten Themen wurden erfolgreich von <strong>%s</strong> nach <strong>%s</strong> verschoben/kopiert.',
'L_QUEUETOPICS'			=> 'Themen revidieren',

'L_COPYTOFORUM'			=> 'Eine Kopie dieser Themen in einem anderen Forum erstellen.',
'L_MOVEWITHOUTTRACKER'	=> 'Thema in ein anderes Forum verschieben ohne eine Weiterleitung zu hinterlassen.',
'L_MOVEWITHTRACKER'		=> 'Thema in ein anderes Forum verschieben und eine Weiterleitung erstellen.',
'L_MOVETOFORUM'			=> 'Verschieben/Kopieren in Forum',
'L_MOVECOPY'			=> 'Verschieben/Kopieren',
'L_TOPICPENDINGMOD'		=> 'Das ausgewählte Thema befindet sich in Revision.',
'L_TOPICISHIDDEN'		=> 'Das gewählte Thema ist im Moment nicht sichtbar oder wartet darauf entfernt zu werden.',
'L_CANTMODNONFORUM'		=> 'Du kannst dieses Objekt nicht moderieren.',
'L_DESTFORUMDOESNTEXIST'=> 'Das Zielforum für die ausgewählten Beiträge existiert nicht.',

/* Forum view stuff */
'L_MODERATORSPANEL'		=> 'Moderator Konsole',
'L_VIEWQUEUEDTOPICS'	=> 'Revidierte Themen anzeigen',
'L_VIEWLOCKEDTOPICS'	=> 'Geschlossene Themen anzeigen',
'L_BANUSER'				=> 'Benutzer sperren',
'L_FLAGUSER'			=> 'Benutzer markieren',
'L_WARNUSER'			=> 'Benutzer warnen',
'L_MANAGEATTACHMENTS'	=> 'Dateianlagen verwalten',
'L_VIEWBADPOSTREPORTS'	=> 'Zensurmeldungen anzeigen',
'L_BADPOSTREPORTS'		=> 'Zensurmeldungen',
'L_NOBADPOSTREPORTS'	=> 'Zur Zeit existieren keine Zensurmeldungen.',
'L_CANTMODACATEGORY'	=> 'Du kannst keine Kategorie moderieren.',
'L_TOPICTITLEIS'		=> 'Der ausgewählte Thementitel ist: <strong>%s</strong>.',

/* Bad post reports */
'L_REPORT'				=> 'Melden',
'L_NUMREQUESTS'			=> 'Anzahl der Anfragen',
'L_REPORTER'			=> 'Meldung von',
'L_REPORTEDON'			=> 'Meldung über',
'L_MODOPTIONS'			=> 'Optionen der Moderation',
'L_DELETEREPORT'		=> 'Meldung löschen',
'L_DELETEREPLY'			=> 'Antwort löschen',
'L_REPORTDOESNTEXIST'	=> 'Die ausgewählte Zensurmeldung existiert nicht.',
'L_REMOVEDBADPOSTREPORT'=> 'Die ausgewählte Zensurmeldung wurde erfolgreich gelöscht.',

/* User banning, warning and flagging */
'L_NOBANNEDIPS'			=> 'Zur Zeit sind keine IPs gesperrt.',
'L_VIEWBANNEDIPS'		=> 'Gesperrte IPs zeigen',
'L_FINDAUSER'			=> 'Benutzer finden',
'L_FINDUSERS'			=> 'Benutzer finden',
'L_WARNHIGH'			=> 'Hohe Warnstufe',
'L_WARNMED'				=> 'Mittlere Warnstufe',
'L_WARNLOW'				=> 'Niedrige Warnstufe',
'L_UNFLAGUSER'			=> 'Benutzerkennzeichnung aufheben',
'L_FLAGGEDUSER'			=> 'Der Benutzer <strong>%s</strong> wurde erfolgreich zur Überwachung gekennzeichnet.',
'L_UNFLAGGEDUSER'		=> 'Der Benutzer <strong>%s</strong> wurde erfolgreich von der Überwachung aufgehoben.',
'L_UNBANUSER'			=> 'Benutzersperre aufheben',
'L_SENDWARNING'			=> 'Benutzer warnen',
'L_INSERTWARNING'		=> 'Du musst eine Warnnachricht eingeben.',
'L_WARNINGMESSAGE'		=> 'Warnnachricht',
'L_WARNINGTEXTAREA'		=> "%s,\n\nDu wurdest aufgrund mangelnder Moral oder dem Verstoß gegen die Verhaltensregeln von diesem Forum ausgeschlossen.\n\nHochachtungsvoll,\n%s.",
'L_WARNINGBYEMAIL'		=> 'Folgende Nachricht wird an den ausgewählten Benutzer per e-Mail gesendet.',
'L_WARNING'				=> 'Warnung',
'L_SENTWARNING'			=> 'Erfolgreich eine Warnung an <strong>%s</strong> gesendet.',
'L_UNBANNEDUSER'		=> 'Erfolgreich Sperre von dem Benutzer <strong>%s</strong> aufgehoben.',
'L_BANNEDUSER'			=> 'Erfolgreich den Benutzer <strong>%s</strong> gesperrt.',
'L_BANTHISUSER'			=> 'Diesen Benutzer sperren',
'L_REASON'				=> 'Grund',
'L_BANREASON'			=> 'Der Grund zur Sperre des Benutzers. Feld freilassen um keinen Grund anzugeben.',
'L_FOR'					=> 'Für',

'L_INDEFINETELY'		=> 'für eine unbestimmte Zeitdauer',
'L_THENEXT24HOURS'		=> '24 Stunden',
'L_THENEXT48HOURS'		=> '48 Stunden',
'L_THENEXTFIVEDAYS'		=> '5 Tage',
'L_THENEXTTENDAYS'		=> '10 Tage',
'L_THENEXTTWOWEEKS'		=> '2 Wochen',
'L_THENEXTMONTH'		=> '1 Monat',
'L_THENEXTSIXMONTHS'	=> '6 Monate',
'L_THENEXTYEAR'			=> '1 Jahr',

'L_BANNEDUSERID'		=> '<strong>%s</strong>,<br /><br />Du wurdest von den Foren aus diesen Gründen ausgeschlossen:<br />%s<br /><br />Diese Sperre verfällt mit %s.',
'L_BANNEDUSERIP'		=> 'Hallo,<br /><br /> Diese IP Adresse wurde von den Foren aus diesen Gründen ausgeschlossen::<br />%s<br /><br />Diese Sperre verfällt mit %s.',
'L_YOURDEATH'			=> 'Die Ewigkeit dauert lange, besonders gegen Ende. - Woody Allen',

'L_BANIPRANGE'			=> 'IP Bereich sperren',
'L_IPRANGE'				=> 'IP Bereich',
'L_BANNEDIPS'			=> 'Gesperrte IP Adressen',
'L_LEFTBAN'				=> 'Sperre aufheben',
'L_BANNEDIPRANGE'		=> 'Der gewählte IP Bereich wurde erfolgreich gesperrt.',
'L_UNBANNEDIP'			=> 'Der IP Bereich für <strong>%s</strong> wurde erfolgreich entsperrt.',
'L_HOWTOBANIPRANGE'		=> 'IP Adresse der zu sperrenden Person angeben. Verwende * für teilweise Übereinstimmung. <strong>Eine vollständige IP Adresse angeben (z.B. *.*.*.*)</strong>',
'L_MUSTINSERTIP'		=> 'Bitte eine zu sperrende IP Adresse angeben die mindestens 7 Zeichen und höchstens 15 Zeichen lang ist.',

);

?>