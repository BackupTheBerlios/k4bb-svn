<?php
/**
* k4 Bulletin Board, init.php
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
* @version $Id: init.php 149 2005-07-12 14:17:49Z Peter Goodman $
* @package k42
*/



if(!defined('IN_K4'))
	return;

/* Functions */
require K4_BASE_DIR. '/functions.php';
require K4_BASE_DIR. '/fatal_error.php';
require K4_BASE_DIR. '/mimetype.php';
require K4_BASE_DIR. '/cache.php';
require K4_BASE_DIR. '/maps.php';
require K4_BASE_DIR. '/common.php';
require K4_BASE_DIR. '/heirarchy.php';
require K4_BASE_DIR. '/bbcode.php';
require K4_BASE_DIR. '/editor.php';
require K4_BASE_DIR. '/lazyload.php';
require K4_BASE_DIR. '/breadcrumbs.php';
require K4_BASE_DIR. '/globals.class.php';
require K4_BASE_DIR. '/poll_template.php';

/* Action Classes */
require K4_BASE_DIR. '/actions/online_users.class.php';
require K4_BASE_DIR. '/actions/forums.class.php';
require K4_BASE_DIR. '/actions/topics.class.php';
require K4_BASE_DIR. '/actions/posts.class.php';
require K4_BASE_DIR. '/actions/topic_review.class.php';
require K4_BASE_DIR. '/actions/replies.class.php';
require K4_BASE_DIR. '/actions/users.class.php';
require K4_BASE_DIR. '/actions/usergroups.class.php';
require K4_BASE_DIR. '/actions/attachments.class.php';
require K4_BASE_DIR. '/actions/usercp.class.php';
require K4_BASE_DIR. '/actions/privmessages.class.php';

/* Moderator Action Classes */
require K4_BASE_DIR. '/actions/moderator.class.php';
require K4_BASE_DIR. '/actions/reports.class.php';
require K4_BASE_DIR. '/actions/modusers.class.php';

/* Important Classes */
require K4_BASE_DIR . '/database.php';
require K4_BASE_DIR . '/user.php';

/* Filters */
require K4_BASE_DIR . '/filters/global.php';
require K4_BASE_DIR . '/filters/cache.php';

?>