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
* @author Geoffrey Goodman
* @author James Logsdon
* @version $Id: mod.php,v 1.1 2005/05/24 20:05:09 k4st Exp $
* @package k42
*/

if (!defined('IN_K4'))
	return;

$_LANG += array(

'L_SELECT'				=> 'Select',
'L_WITHTOPICS'			=> 'With selected topics',
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
'L_GLOBAL'				=> 'Global',
'L_NORMAL'				=> 'Normal',
'L_FEATURE'				=> 'Featured Topics',
'L_MOVE'				=> 'Moved Topics',
'L_FEATURETOPIC'		=> 'Feature Topic',
'L_NORMALIZE'			=> 'Normalize Topics',
'L_QUEUE'				=> 'Queue Topics',
'L_DELETETOPICS'		=> 'Delete Topics',
'L_LOCKTOPICS'			=> 'Lock Topics',
'L_STICKTOPICS'			=> 'Stick Topics',
'L_ANNOUNCETOPICS'		=> 'Announce Topics',
'L_FEATURETOPICS'		=> 'Feature Topics',
'L_SETASNORMALTOPICS'	=> 'Set as Normal Topics',
'L_MOVECOPYTOPICS'		=> 'Move/Copy Topics',
'L_DELETETOPICS'		=> 'Delete Topics',
'L_QUEUE'				=> 'Queue Topics',
'L_DELETE'				=> 'Delete Topics/Posts',
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
'L_MOVEDTOPICS'			=> 'Successfully moved the selected topics from <strong>%s</strong> to <strong>%s</strong>.',
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

);

?>