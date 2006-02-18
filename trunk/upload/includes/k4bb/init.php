<?php
/**
* k4 Bulletin Board, init.php
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
require K4_BASE_DIR. '/bbparser.php';
require K4_BASE_DIR. '/editor.php';
require K4_BASE_DIR. '/lazyload.php';
require K4_BASE_DIR. '/breadcrumbs.php';
require K4_BASE_DIR. '/globals.class.php';
require K4_BASE_DIR. '/url.class.php';
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

/* Classes that do stuff */
require K4_BASE_DIR. '/actions/archive.class.php';

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