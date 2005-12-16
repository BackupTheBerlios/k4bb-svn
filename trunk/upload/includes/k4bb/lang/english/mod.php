<?php
/**
* k4 Bulletin Board, files.php
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
* @version $Id: mod.php 158 2005-07-18 02:55:30Z Peter Goodman $
* @package k42
*/

if (!defined('IN_K4'))
	return;

$_LANG += array(

'L_MODCP'				=> 'Mod CP',

'L_SELECT'				=> 'Select',
'L_WITHTOPICS'			=> 'With selected topics',
'L_WITHTOPIC'			=> 'With this topic',
'L_SELECTTOPIC'			=> 'Click this to select this topic for moderation.',
'L_DELETEREPLY'			=> 'Delete Reply',
'L_DELETETOPIC'			=> 'Delete Topic',
'L_LOCKTOPIC'			=> 'Lock Topic',
'L_UNLOCKTOPIC'			=> 'Unlock Topic',

/**
 * Forum view moderator functions
 */

/* Permission Things */
'L_VIEW'				=> 'View',
'L_READ'				=> 'Read',
'L_POST'				=> 'Post',
'L_ATTACH'				=> 'Attach Files',
'L_EDIT'				=> 'Edit',
'L_ANNOUNCE'			=> 'Announce Topics',
'L_POLLCREATE'			=> 'Create Polls',

'L_ALL'					=> 'ALL',
'L_REG'					=> 'REGISTERED',
'L_PRIVATE'				=> 'PRIVATE',
'L_MODS'				=> 'MODERATORS',
'L_ADMINS'				=> 'ADMINS',
'L_GLOBAL'				=> 'Global Announcement',
'L_FEATURE'				=> 'Featured Topics',
'L_MOVE'				=> 'Moved Topics',
'L_FEATURETOPIC'		=> 'Feature Topic',
'L_NORMALIZE'			=> 'Normalize Topics',
'L_QUEUE'				=> 'Queue Topics',
'L_LOCKTOPICS'			=> 'Lock Topics',
'L_STICKTOPICS'			=> 'Stick Topics',
'L_ANNOUNCETOPICS'		=> 'Announce Topics',
'L_FEATURETOPICS'		=> 'Feature Topics',
'L_SETASNORMALTOPICS'	=> 'Set as Normal Topics',
'L_MOVECOPYTOPICS'		=> 'Move/Copy Topics',

'L_LOCKTOPIC'			=> 'Lock Topic',
'L_SPLITTOPIC'			=> 'Split Topic',
'L_QUEUETOPIC'			=> 'Queue Topic',
'L_LOCKTOPIC'			=> 'Lock Topic',
'L_STICKTOPIC'			=> 'Stick Topic',
'L_ANNOUNCETOPIC'		=> 'Announce Topic',
'L_FEATURETOPIC'		=> 'Feature Topic',
'L_SETASNORMALTOPIC'	=> 'Set as Normal Topic',
'L_MOVECOPYTOPIC'		=> 'Move/Copy Topic',
'L_SUBSCRIBETOTOPIC'	=> 'Subscribe to Topic',

'L_DELETETOPICS'		=> 'Delete Topics',
'L_QUEUE'				=> 'Queue Topics',
'L_DELETE'				=> 'Delete Topics/Posts',
'L_SOFTDELETE'			=> 'Soft Delete Topics/Posts',
'L_SUBSCRIBETOTOPICS'	=> 'Subscribe to Topics',
'L_PERFORMACTION'		=> 'Perform Action',
'L_NEEDSELECTACTION'	=> 'You must select an action to perform on these topics.',
'L_NEESSELECTTOPICS'	=> 'You must select at least one topic to moderate.',
'L_LOCKEDTOPICS'		=> 'Successfully locked the selected topics.',
'L_STUCKTOPICS'			=> 'Successfully set the selected topics satuses to Sticky.',
'L_ANNOUNCEDTOPICS'		=> 'Successfully set the selected topics as Announcements.',
'L_FEATUREDTOPICS'		=> 'Successfully set the selected topics to be featured topics.',
'L_NORMALIZEDTOPICS'	=> 'Successfully removed any special formatting on the selected topics.',
'L_QUEUEDTOPICS'		=> 'Successfully added the selected topics to the moderator\'s queue.',
'L_SUBSCRIBEDTOPICS'	=> 'Successfully subscribed to the selected topics.',
'L_DELETEDTOPICS'		=> 'Successfully deleted the selected topic(s).',
'L_MOVETOPICS'			=> 'Move Topics',
'L_MOVEDTOPICS'			=> 'Successfully moved/copied the selected topics from <strong>%s</strong> to <strong>%s</strong>.',
'L_QUEUETOPICS'			=> 'Queue Topics',

'L_COPYTOFORUM'			=> 'Create a copy of these topics in another forum.',
'L_MOVEWITHOUTTRACKER'	=> 'Move the topic to another forum without leaving a redirection link behind.',
'L_MOVEWITHTRACKER'		=> 'Move the topic to another forum and leave a redirect link behind.',
'L_MOVETOFORUM'			=> 'Move/Copy to Forum',
'L_MOVECOPY'			=> 'Move/Copy',
'L_TOPICPENDINGMOD'		=> 'The selected topic is pending moderation.',
'L_TOPICISHIDDEN'		=> 'The selected topic is currently hidden or is waiting to be removed.',
'L_CANTMODNONFORUM'		=> 'You cannot moderate a non-forum.',
'L_DESTFORUMDOESNTEXIST'=> 'The selected destination forum for the moderated posts does not exist.',

/* Forum view stuff */
'L_MODERATORSPANEL'		=> 'Moderator Panel',
'L_VIEWQUEUEDTOPICS'	=> 'View Queued Topics',
'L_VIEWLOCKEDTOPICS'	=> 'View Locked Topics',
'L_BANUSER'				=> 'Ban User',
'L_FLAGUSER'			=> 'Flag User',
'L_WARNUSER'			=> 'Warn User',
'L_MANAGEATTACHMENTS'	=> 'Manage Attachments',
'L_VIEWBADPOSTREPORTS'	=> 'View Bad Post Reports',
'L_BADPOSTREPORTS'		=> 'Bad Post Reports',
'L_NOBADPOSTREPORTS'	=> 'There are currently no reports of bad posts.',
'L_BADPOSTREPORTS'		=> 'Bad Post Reports',
'L_CANTMODACATEGORY'	=> 'You cannot moderator a Category.',
'L_TOPICTITLEIS'		=> 'The selected topic title is: <strong>%s</strong>.',

/* Bad post reports */
'L_REPORT'				=> 'Report',
'L_NUMREQUESTS'			=> 'Number of Requests',
'L_REPORTER'			=> 'Reporter',
'L_REPORTEDON'			=> 'Reported On',
'L_MODOPTIONS'			=> 'Moderating Options',
'L_DELETEREPORT'		=> 'Delete Report',
'L_DELETEREPLY'			=> 'Delete Reply',
'L_REPORTDOESNTEXIST'	=> 'The selected bad post report does not seem to exist.',
'L_REMOVEDBADPOSTREPORT'=> 'Successfully removed the selected bad post report.',

/* User banning, warning and flagging */
'L_NOBANNEDIPS'			=> 'There are currently no Banned IP Ranges',
'L_VIEWBANNEDIPS'		=> 'View Banned IPs',
'L_FINDAUSER'			=> 'Find a User',
'L_FINDUSERS'			=> 'Find Users',
'L_WARNHIGH'			=> 'High Warning Level',
'L_WARNMED'				=> 'Medium Warning Level',
'L_WARNLOW'				=> 'Low Warning Level',
'L_UNFLAGUSER'			=> 'Un-Flag User',
'L_FLAGGEDUSER'			=> 'You have successfully flagged the user <strong>%s</strong> as a user to watch.',
'L_UNFLAGGEDUSER'		=> 'You have successfully un-flagged the user <strong>%s</strong>.',
'L_UNBANUSER'			=> 'Unban User',
'L_SENDWARNING'			=> 'Send Warning',
'L_INSERTWARNING'		=> 'You must insert a warning.',
'L_WARNINGMESSAGE'		=> 'Warning Message',
'L_WARNINGTEXTAREA'		=> "%s,\n\nYou are being warned concerning your lack of morals and/or etiquette on this forum. If you fail to discontinue your poor behavior, you will be banned from the forums.\n\nSincerely,\n%s.",
'L_WARNINGBYEMAIL'		=> 'The following message will be sent to the selected user by email.',
'L_WARNING'				=> 'Warning',
'L_SENTWARNING'			=> 'Successfully sent a warning to <strong>%s</strong>.',
'L_UNBANNEDUSER'		=> 'Successfully unbanned the user <strong>%s</strong>.',
'L_BANNEDUSER'			=> 'Successfully banned the user <strong>%s</strong>.',
'L_BANTHISUSER'			=> 'Ban this User',
'L_REASON'				=> 'Reason',
'L_BANREASON'			=> 'Your reason for banning this user. Leave this feild blank if you do not want to *dignify* this person with a reason.',
'L_FOR'					=> 'For',

'L_INDEFINETELY'		=> 'an indefinite period of time',
'L_THENEXT24HOURS'		=> '24 hours',
'L_THENEXT48HOURS'		=> '48 hours',
'L_THENEXTFIVEDAYS'		=> 'five days',
'L_THENEXTTENDAYS'		=> 'ten days',
'L_THENEXTTWOWEEKS'		=> 'two weeks',
'L_THENEXTMONTH'		=> 'one month',
'L_THENEXTSIXMONTHS'	=> 'six months',
'L_THENEXTYEAR'			=> 'one year',

'L_BANNEDUSERID'		=> '<strong>%s</strong>,<br /><br />You have been banned from the forums for the following reasons (if any):<br />%s<br /><br />This ban will expire on %s.',
'L_BANNEDUSERIP'		=> 'Hello,<br /><br />This IP address has been banned from the forums for the following reasons (if any):<br />%s<br /><br />This ban will expire on %s.',
'L_YOURDEATH'			=> 'the date of your death',

'L_BANIPRANGE'			=> 'Ban IP Range',
'L_IPRANGE'				=> 'IP Range',
'L_BANNEDIPS'			=> 'Banned IP Addresses',
'L_LEFTBAN'				=> 'Lift Ban',
'L_BANNEDIPRANGE'		=> 'Successfully banned the selected IP range.',
'L_UNBANNEDIP'			=> 'Successfully unbanned the IP range for <strong>%s</strong>.',
'L_HOWTOBANIPRANGE'		=> 'Insert the IP address of the person you would like to ban. Insert a * for partial matches. <strong>Make sure to enter a full IP address (e.g. *.*.*.*)</strong>',
'L_MUSTINSERTIP'		=> 'Please insert an IP address to ban that is at least 7 characters long and at most 15 characters long.',

);

?>