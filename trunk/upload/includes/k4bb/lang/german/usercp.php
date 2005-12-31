<?php
/**
* k4 Bulletin Board, usercp.php
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
* @author Thomas "Thasmo" Deinhamer (thasmo at gmail dot com)
* @version $Id$
* @package k42
*/

if(!defined('IN_K4'))
	return;


$_LANG += array(

'L_MYCONTROLS'			=> 'Meine Einstellungen',
'L_EDITPROFILE'			=> 'Profil �ndern',
'L_EDITOPTIONS'			=> 'Einstellungen �ndern',
'L_EDITSIGNATURE'		=> 'Signatur �ndern',
'L_EDITAVATAR'			=> 'Avatar �ndern',
'L_CHANGEPASSWORD'		=> 'Password �ndern',
'L_CHANGEEMAIL'			=> 'Email Adresse �ndern',
'L_IMPORTANTANNOUNCEMENTS'=> 'Wichtige Ank�ndigungen',
'L_USERCPWELCOMEMSG'	=> 'Willkommen im Benutzerkontrollzentrum. Von hier aus kannst du dein Profil, deine Einstellungen betrachten und �ndern und weiters Abonnements betrachten bzw. aufl�sen. Du kannst auch private Nachrichten an Benutzer versenden. Bitte lies dir zuvor eventuell vorhandene wichtige Ank�ndigungen durch.',
'L_USERCPPROFILEMSG'	=> 'Willkommen im Benutzerkontrollzentrum. Hier kannst du dein Profil bearbeiten. Viele dieser Informationen sind f�r andere Benutzer der Foren sichtbar.',
'L_USERCPPASSWORDMSG'	=> 'Willkommen im Benutzerkontrollzentrum. Hier kannst du dein Passwort �ndern. Sei dir aber zuvor sicher dein altes Passwort nicht vergessen zu haben.',
'L_USERCPEMAILMSG'		=> 'Willkommen im Benutzerkontrollzentrum. Von hier aus kannst du deine Email Adresse �ndern. Bei aktivierter Validierung und somit ben�tigter Best�tigung der neue Email Adresse wird dir eine Email an die neue Adresse zugesandt.',
'L_USERCPOPTIONSMSG'	=> 'Willkommen im Benutzerkontrollzentrum. In diesem Bereich kannst du Einstellungen treffen die Auswirkung auf die Darstellung der Foren und Themen haben. Diese reichen von der zu verwendeten Sprache bis hin zum Board Style.',
'L_USERCPSIGNATUREMSG'	=> 'Willkommen im Benutzerkontrollzentrum. Hier wird dir erlaubt eine Signatur zu erstellen oder diese zu �ndern. Du kannst Bilder oder Rich Text in deine Signatur einbetten.',
'L_USERCPAVATARMSG'		=> 'Willkommen im Benutzerkontrollzentrum. Auf dieser Seite kannst du ein Avatar hinzuf�gen oder �ndern. Ein Avatar kann ein beliebiges Bild welches dich repr�sentiert, ein Foto oder sonst eine beliebige Grafik sein.',
'L_USERCPPICTUREMSG'	=> 'Willkommen im Benutzerkontrollzentrum. Hier ist es dir erlaubt ein pers�nliches Bild zu definieren. Es muss sich nicht unbedingt um ein Foto von dir handeln. :D',
'L_USERCPATTACHMENTSMSG'=> 'Willkommen im Benutzerkontrollzentrum. Von hier aus kannst du deine eigenen Dateianh�nge verwalten. Eine Statistik zeigt dir wieviel Speicherplatz du bereits belegt hast. Du kannst hier alte oder ungewollte Dateianh�nge l�schen.',
'L_USERCPSUBSCRIPTIONSMSG'=> 'Willkommen im Benutzerkontrollzentrum. Hier kannst du deine Abonnements verwalten. Wenn du ein Abonnement aufl�sen m�chtest bist du hier am richtigen Platz!',
'L_CONTROLPANEL'		=> 'Kontrollzentrum',
'L_SETTINGSOPTIONS'		=> 'Einstellungen &amp; Optionen',
'L_PASSWORDSETTINGS'	=> 'Passwort Einstellungen',
'L_EDITPERSONALPIC'		=> 'Pers�nliches Bild �ndern',
'L_CURRENTPASS'			=> 'Derzeitiges Passwort',
'L_CHECKCURRENTPASS'	=> 'Derzeitiges Passwort �berpr�fen',
'L_NEWPASS'				=> 'Neues Passwort',
'L_INSERTOLDPASSWORD'	=> 'Derzeitiges Passwort eingeben...',
'L_NOWSUPPLYNEWPASS'	=> 'Neues Passwort eingeben...',
'L_CHECKNEWPASS'		=> 'Neues Passwort �berpr�fen',
'L_CHANGEPASS'			=> 'Passwort �ndern',

'L_TOPICDISPLAY'		=> 'Themenansicht',
'L_TOPICTHREADEDMODE'	=> 'Listenmodus',
'L_NORMAL'				=> 'normal',
'L_THREADED'			=> 'aufgelistet',

'L_EMAILSETTINGS'		=> 'Email Einstellungen',
'L_CURRENTEMAIL'		=> 'Derzeitige Email Adresse',
'L_CHECKCURRENTEMAIL'	=> 'Derzeitige Email Adresse �berpr�fen',
'L_NEWEMAIL'			=> 'Neue Email Adresse',
'L_INSERTOLDEMAIL'		=> 'Derzeitige Email Adresse eingeben...',
'L_NOWSUPPLYNEWEMAIL'	=> 'Neue Email Adresse eingeben...',
'L_CHECKNEWEMAIL'		=> 'Neue Email Adresse �berpr�fen',

'L_HOMEPAGE'			=> 'Webseite',
'L_SIGNATURE'			=> 'Signatur',
'L_FULLNAME'			=> 'Vollst�ndiger Name',
'L_BIRTHDAY'			=> 'Geburtstag',
'L_MONTH'				=> 'Monat',
'L_DAY'					=> 'Tag',
'L_YEAR'				=> 'Jahr',
'L_AIM'					=> 'AIM Screen Name',
'L_MSN'					=> 'MSN Messenger Kennung',
'L_ICQ'					=> 'ICQ Nummer',
'L_YAHOO'				=> 'Yahoo! Messenger Kennung',
'L_JABBER'				=> 'Jabber Kennung',
'L_GOOGLETALK'			=> 'Google Talk Kennung',
'L_INSTANTMESSAGING'	=> 'Instant Messaging',
'L_PERSONALINFORMATION'	=> 'Pers�nliche Daten',
'L_ADDITIONALINFO'		=> 'Optionale Daten',
'L_LOCATION'			=> 'Ort',
'L_OCCUPATION'			=> 'Beruf',
'L_INTERESTS'			=> 'Interessen',
'L_BIOGRAPHY'			=> 'Biografie',
'L_SETTINGS'			=> 'Einstellungen',
'L_EDITAVATAR'			=> 'Avatar �ndern',
'L_BOARDSETTINGS'		=> 'Board Einstellungen',
'L_BOARDSTYLESET'		=> 'Board Styleset',
'L_BOARDIMAGESET'		=> 'Board Bilderset',
'L_BOARDLANGUAGE'		=> 'Board Sprache',
'L_INVISIBLEMODE'		=> 'Unsichtbarkeitsmodus',
'L_USEAVATAR'			=> 'Avatar verwenden?',
'L_YOURAVATAR'			=> 'Dein Avatar',
'L_YOURNAME'			=> 'Dein Name',
'L_UPLOADNEWAVATAR'		=> 'Neues Avatar hochladen',
'L_AVATARINFO'			=> 'Um ein Avatar verwenden zu k�nnen muss es hochgeladen werden. Das Avatar muss ein GIF Bild sein und darf die maximale H�he und Breite von 75 Pixeln nicht �berschreiten.',
'L_PRIVATE_MSGS'		=> 'Private&nbsp;Nachrichten',
'L_FOLDERS'				=> 'Ordner',
'L_SENDMESSAGE'			=> 'Nachricht senden',
'L_EDITFOLDERS'			=> 'Ordner �ndern',
'L_LISTMESSAGES'		=> 'Nachrichten auflisten',
'L_PMSINFOLDER'			=> 'Private Nachrichten in Ordner',
'L_FOLDEROPTIONS'		=> 'Ordner Optionen',
'L_PMSGSTATS'			=> 'Du hast %s Nachrichten gespeichert, von %s erlaubten.',
'L_MESSAGES'			=> 'Nachrichten',
'L_SUBJECT'				=> 'Betreff',
'L_FROM'				=> 'Von',
'L_BUDDYLIST'			=> 'Freunde Liste',
'L_TO'					=> 'An',
'L_FOLDERJUMP'			=> 'Gehe zu Ordner',
'L_VIEWMESSAGE'			=> 'Nachricht anzeigen',
'L_SHOWQUOTEDTEXT'		=> 'Zitierten Text anzeigen',
'L_FORWARD'				=> 'Vorw�rts',
'L_SENTON'				=> 'Gesendet am',
'L_EMPTYFOLDER'			=> 'Ordner leeren',
'L_SAVEMESSAGE'			=> 'Private Nachricht speichern',
'L_FRIENDS'				=> 'Freunde',
'L_ENNEMIES'			=> 'Feinde',
'L_FRIEND'				=> 'Freund',
'L_ADDBUDDYTOLIST'		=> 'Freund hinzuf�gen',
'L_DELETEMESSAGE'		=> 'Private Nachricht l�schen',
'L_NOIMPORTANTANNOUNCES'=> 'Zur Zeit gibt es keine wichtigen Ank�ndigungen.',
'L_YOURACTIVITY'		=> 'Deine Aktivit�t',
'L_MOSTACTIVEFORUM'		=> 'Aktivstes Forum',
'L_MOSTACTIVETOPIC'		=> 'Aktivstes Thema',

'L_GENERALOPTIONS'		=> 'Allgemeine Einstellungen',
'L_PMOPTIONS'			=> 'Private Nachrichten Optionen',
'L_POSTOPTIONS'			=> 'Beitragsoptionen',
'L_VIEWINGOPTIONS'		=> 'Anzeigeeinstellungen',
'L_POPUPPRIVATEMESSAGE'	=> 'Pop-Up Fenster bei neuer privater Nachricht?',
'L_NOTIFYPRIVATEMESSAGE'=> 'Benachrichtigen bei neuer privater Nachricht?',
'L_VIEWFLASH'			=> 'Flashfilme anzeigen',
'L_VIEWEMOTICONS'		=> 'Emoticons anzeigen',
'L_VIEWSIGNATURES'		=> 'Siganturen anzeigen',
'L_VIEWAVATARS'			=> 'Avatarbilder anzeigen',
'L_VIEWIMAGES'			=> 'Verlinkte Bilder anzeigen',
'L_RESULTSPERPAGEMSG'	=> 'Ein Wert gr��er als 0 �berschreibt die Standarteinstellungen.',
'L_SIGNATUREOPTIONS'	=> 'Signatureinstellungen',
'L_ATTACHSIGNATUREPOSTS'=> 'Signatur an Beitr�ge anh�ngen',
'L_AVATARSETTINGS'		=> 'Avatareinstellungen',
'L_VALIDAVATARIMAGETYPES'=> 'G�ltige Avatarbild Dateiformate',
'L_VALIDAVATARDIMENSIONS'=> 'G�ltige Avatarbild Dimensionen',
'L_AVATARWEBSITEURL'	=> 'Webseiten URL f�r das Avatarbild',
'L_OR'					=> 'ODER',
'L_YOURPICTURE'			=> 'Dein Bild',
'L_VALIDPPIMAGETYPES'	=> 'G�ltige Bilddateiformate',
'L_VALIDPPDIMENSIONS'	=> 'G�ltige Bilddimensionen',
'L_PPWEBSITEURL'		=> 'Webseiten URL f�r das Bild',
'L_PICTURESETTINGS'		=> 'Bildeinstellungen',
'L_ERRORUPDATINGPROFILE'=> 'Beim Versuch das Profil zu aktualisieren ist ein Fehler aufgetreten. Bitte den Administrator kontaktieren um das Problem zu l�sen.',
'L_AVATARSUCCESS'		=> 'Die Avatareinstellungen wurden erfolgreich aktualisiert.',
'L_SETTINGSSUCCESS'		=> 'Die Benutzereinstellungen wurden erfolgreich aktualisiert.',
'L_UPDATEDPROFILE'		=> 'Das Profil wurde erfolgreich aktualisiert.',

'L_PASSESDONTMATCH'		=> 'Die Passw�rter stimmen nicht �berein.',
'L_SUPPLYPASSCHECK'		=> 'Bitte das Passwort best�tigen.',
'L_SUPPLYPASS'			=> 'Bitte ein Passwort angeben.',
'L_NEWPASSESDONTMATCH'	=> 'Die neuen Passw�rter stimmen nicht �berein.',
'L_SUPPLYNEWPASSCHECK'	=> 'Bitte das neue Passwort best�tigen.',
'L_SUPPLYNEWPASS'		=> 'Bitte ein neues Passwort angeben.',
'L_INVALIDCURRPASS'		=> 'Ein falsches Passwort wurde angegeben.',

'L_SUPPLYEMAIL'			=> 'Bitte eine Email Adresse angeben.',
'L_SUPPLYEMAILCHECK'	=> 'Bitte die Email Adresse best�tigen.',
'L_SUPPLYVALIDEMAIL'	=> 'Bitte eine g�ltige Email Adresse angeben.',
'L_EMAILSDONTMATCH'		=> 'Die Email Adressen stimmen nicht �berein.',
'L_NEWEMAILSDONTMATCH'	=> 'Die neuen Email Adressen stimmen nicht �berein.',
'L_SUPPLYNEWEMAILCHECK'	=> 'Bitte die neue Email Adresse best�tigen.',
'L_SUPPLYNEWEMAIL'		=> 'Bitte eine neue Email Adresse angeben.',
'L_INVALIDCURREMAIL'	=> 'Eine falsche Email Adresse wurde angegeben.',

'L_PASSWORDSUCCESS'		=> 'Das Passwort wurde erfolgreich ge�ndert. Eine Email mit dem neuen Passwort wurde versandt.',
'L_ERRORUPDATINGPASS'	=> 'Beim Versuch die Passworteinstellungen zu aktualisieren ist ein Fehler aufgetreten.',
'L_PASSCHANGEDEMAIL'	=> "%s,\r\n\r\nDie Benutzerinformation inklusive dem Passwort ist wie folgt:\r\n\r\nBenutzername: %s\r\nPasswort: %s\r\n\r\nMit freundlichen Gr��en,\r\n%s Team",
'L_EMAILCHANGEDSUCCESS'	=> 'Die Email Adresse wurde erfolgreich ge�ndert. Bei ben�tigter Best�tigung der Email Adresse wirst du automatisch abgemeldet. Um dich wieder einzulogen musst du den Best�tigungscode in der Email welche dir an deine neue Email Adresse zugeschickt wurde verwenden.',
'L_EMAILCHANGEDEMAIL'	=> "%s,\r\n\r\nBitte diese Email Adresse mit Hilfe dieser URL best�tigen:\r\n\r\n%s\r\n\r\nMit freundlichen Gr��en,\r\n%s Team",
'L_INVALIDEMAILKEY'		=> 'Der Best�tigungscode ist ung�ltig.',
'L_VERIFIEDNEWEMAIL'	=> 'Danke f�r die Best�tigung der Email Adresse <strong>%s</strong>. Du wirst nun auf die Startseite der Foren weitergeleitet.',
'L_UPDATEDSIGNATURE'	=> 'Die Signatur wurde erfolgreich aktualisiert.',

'L_UPDATEDAVATAR'		=> 'Die Avatareinstellungen wurden erfolgreich aktualisiert.',
'L_USEANAVATAR'			=> 'Ein Avatar verwenden? <span class="minitext">(Bei Deaktivierung dieser Option geht das aktuelle Avatar verloren)</span>',
'L_WEBSITEAVATARBAD'	=> 'Das per URL angegebene Avatarbild kann nicht gelesen werden oder existiert nicht.',
'L_INVALIDAVATARDIMS'	=> 'Das Avatarbild verf�gt �ber ung�ltige Dimensionen. Die maximale Gr��e ist %s/%s.',
'L_INVALIDAVATARFILETYPE'=> 'Das Avatarbild verf�gt �ber ein ung�ltiges Dateiformat. G�ltige Dateiformate sind: %s.',
'L_AVATARCRITICALERROR'	=> 'Beim Versuch ein Avatarbild hinzuzuf�gen ist ein kritischer Fehler aufgetreten. Avatare mit dynamischem Inhalt wurden deaktiviert. Bitte den Administrator kontaktieren um das Problem zu l�sen.',
'L_BADAVATAR'			=> 'Das gew�hlte Avatarbild existiert nicht.',
'L_AVATARTOOBIG'		=> 'Das Avatarbild ist zu gro�. Die maximale Dateigr��e betr�gt %s Bytes.',

'L_UPDATEDPICTURE'		=> 'Einstellungen des pers�nlichen Bildes erfolgreich aktualisiert.',
'L_USEAPICTURE'			=> 'Pers�nliches Bild verwenden? <span class="minitext">(Bei Deaktivierung dieser Option geht das aktuelle pers�nliche Bild verloren)</span>',
'L_WEBSITEPICTUREBAD'	=> 'Das per URL angegebene Bild kann nicht gelesen werden oder existiert nicht.',
'L_INVALIDPICTUREDIMS'	=> 'Das Bild verf�gt �ber ung�ltige Dimensionen. Die maximale Gr��e ist %s/%s.',
'L_INVALIDPICTUREFILETYPE'=> 'Das Bild verf�gt �ber ein ung�ltiges Dateiformat. G�ltige Dateiformate sind: %s.',
'L_PICTURECRITICALERROR'=> 'Beim Versuch ein pers�nliches Bild hinzuzuf�gen ist ein kritischer Fehler aufgetreten. Bilder mit dynamischem Inhalt wurden deaktiviert. Bitte den Administrator kontaktieren um das Problem zu l�sen.',
'L_BADPICTURE'			=> 'Das gew�hlte Bild kann nicht gelesen werden oder existiert nicht.',
'L_PICTURETOOBIG'		=> 'Das pers�nliche Bild ist zu gro�. Die maximale Dateigr��e betr�gt %s Bytes.',

'L_UPDATESETTINGS'		=> 'Einstellungen aktualisieren',
'L_CURRENTSIGNATURE'	=> 'Derzeitige Signatur',
'L_VIEWWORDCENSORS'		=> 'Wortzensuren anzeigen',

'L_MANAGEATTACHMENTS'	=> 'Dateianh�nge verwalten',
'L_MANAGESUBSCRIPTIONS'	=> 'Abonnements verwalten',
'L_NOLIMIT'				=> 'Kein Limit',
'L_SUBSCRIPTIONS'		=> 'Abonnements',
'L_NOSUBSCRIPTIONS'		=> 'Keine Themen oder Foren abonniert.',
'L_UNSUBSCRIBE'			=> 'Abonnement k�ndigen',
'L_ATTACHMENTSETTINGS'	=> 'Dateianhang Einstellungen',
'L_CURRENTUSAGE'		=> 'Derzeitiger Verbrauch',
'L_QUOTA'				=> 'Menge',
'L_BYTES'				=> 'Bytes',
'L_ATTACHMENTS'			=> 'Dateianh�nge',
'L_NOATTACHMENTS'		=> 'Keine Dateianh�nge vorhanden.',
'L_VIEWATTACHMENT'		=> 'Dateianhang zeigen',
'L_REMOVEATTACHMENT'	=> 'Dateianhang l�schen',
'L_APPROX'				=> 'gesch�tzt',
'L_MEGABYTES'			=> 'MBs',

/* Private Messaging */
'L_PRIVATEMESSAGING'	=> 'Private Nachrichten',
'L_INBOX'				=> 'Posteingang',
'L_SENTITEMS'			=> 'Gesendete Objekte',
'L_SAVEDPMS'			=> 'Entw�rfe',
'L_EDITSTORAGEFOLDERS'	=> 'Ablegeordner bearbeiten',
'L_MESSAGETRACKER'		=> 'Nachrichtenverlauf',
'L_PMFOLDERDOESNTEXIST'	=> 'Der gew�hlte Ablageorder existiert nicht.',
'L_REACHEDMAXPMFOLDERS'	=> 'Die maximale Anzahl an Ablageordnern f�r private Nachrichten wurde erreicht.',
'L_NOCUSTOMPMFOLDERS'	=> 'Keine selbst erstellten Ablageordner f�r private Nachrichten vorhanden.',
'L_YOURSTORAGEFOLDERS'	=> 'Deine Ablegeordner',
'L_NEEDPMFOLDERNAME'	=> 'Ein Name f�r den Ablageordner muss definiert werden.',
'L_NEEDPMFOLDERDESC'	=> 'Eine Beschreibung f�r den Ablageordner muss definiert werden.',
'L_CREATESTORAGEFOLDER'	=> 'Ablageordner erstellen/bearbeiten',
'L_ADDEDPMFOLDER'		=> 'Der Ablageordner <strong>%s</strong> f�r private Nachrichten wurde erfolgreich erstellt.',
'L_COMPOSENEWMESSAGE'	=> 'Neue Nachricht verfassen',
'L_BADPMFOLDER'			=> 'Der gew�hlte Ablageordner existiert nicht.',
'L_UPDATEDPMFOLDER'		=> 'Den Ablageordner <strong>%s</strong> erfolgreich aktualisiert.',
'L_DELETEPMFOLDER'		=> 'Ablageordner l�schen',
'L_MOVEPMSTO'			=> 'Private Nachrichten verschieben nach',
'L_DELETEPRIVATEMESSAGES'=> 'Private Nachrichten l�schen',
'L_DELETEFOLDER'		=> 'Ordner l�schen',
'L_NOPRIVATEMESSAGES'	=> 'Es befinden sich keine Nachrichten in diesem Ordner.',
'L_POSTPRIVATEMESSAGE'	=> 'Private Nachricht senden',
'L_EDITPRIVATEMESSAGE'	=> 'Private Nachricht bearbeiten',
'L_SAVEINSENTITEMS'		=> 'Diese Nachricht im <em>Gesendete Nachrichten Ordner</em> ablegen.',
'L_INSERTPMTO'			=> 'Bitte mindestens einen vollst�ndigen Benutzernamen als Empf�nger definieren.',
'L_COMMASEPARATENAMES'	=> 'Trenne Benutzernamen mit einem Beistrich',
'L_CC'					=> 'CC (Kopie)',
'L_PMSUBJECTTOOSHORT'	=> 'Der Titel der privaten Nachticht ist zu lang/kurz. Der Titel muss mindestens %s Zeichen lang sein und darf h�chstens %s Zeichen beinhalten.',
'L_INSERTPMMESSAGE'		=> 'Bitte eine Nachricht (Text) f�r die private Nachricht eingeben.',
'L_INSERTPMSUBJECT'		=> 'Bitte einen Betreff f�r die private Nachricht eingeben.',
'L_TOOMANYPMS'			=> 'Es befinden sich %s private Nachrichten in allen Ordnern. Die maximale Anzahl ist %s. Um mehr Nachrichten ablegen zu k�nnen m�ssen eventuell Nachrichten gel�scht werden.',
'L_NEEDSENDPMTOSOMEONE'	=> 'Die private Nachricht muss an mindestens einen Benutzer gerichtet sein.',
'L_PMNOVALIDRECIEVERS'	=> 'Keinem der angegebenen Empf�nger ist es m�glich private Nachrichten zu betrachten. M�glicherweise wurden falsche Benutzernamen angegeben oder diese haben nicht die ben�tigten Berechtigungen private Nachrichten zu lesen.',
'L_SENTPRIVATEMSG'		=> 'Die private Nachricht S<strong>%s</strong> wurde erfolgreich versandt.',
'L_SAVEDPRIVATEMSG'		=> 'Die private Nachricht <strong>%s</strong> wurde erfolgreich abgelegt.',
'L_PMSGDOESNTEXIST'		=> 'Die gew�hlte private Nachricht existiert nicht.',

'L_DELETEPMESSAGES'		=> 'Nachrichten l�schen',
'L_MOVEPMESSAGES'		=> 'Nachrichten verschieben',

'L_PMNEEDSELECTONE'		=> 'Mindestens eine private Nachricht zum Verschieben/L�schen w�hlen.',
'L_MOVEDPMESSAGES'		=> 'Die gew�hlte private Nachricht wurde erfolgreich von <strong>%s</strong> nach <strong>%s</strong> verschoben.',
'L_PMBADORIGINALFOLDER'	=> 'Der Ursprungsordner der Nachrichten existiert nicht.',
'L_PMBADDESTFOLDER'		=> 'Der Zielordner f�r die Nachrichten existiert nicht.',
'L_MOVEPMSFROM'			=> 'Private Nachrichten verschieben von',
'L_DELETEDPMESSAGES'	=> 'Die gew�hlte private Nachricht wurde erfolgreich gel�scht.',
'L_DESTINATIONFOLDER'	=> 'Zielordner',

);

?>