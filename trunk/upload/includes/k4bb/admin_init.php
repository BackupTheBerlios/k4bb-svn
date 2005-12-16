<?php
/**
* k4 Bulletin Board, k4_template.php
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
* @version $Id$
* @package k42
*/

if(!defined('IN_K4'))
	return;

/* Admin Action Classes */
require K4_BASE_DIR. '/actions/admin/maps.class.php';
require K4_BASE_DIR. '/actions/admin/posticons.class.php';
require K4_BASE_DIR. '/actions/admin/emoticons.class.php';
require K4_BASE_DIR. '/actions/admin/files.class.php';
require K4_BASE_DIR. '/actions/admin/forums.class.php';
require K4_BASE_DIR. '/actions/admin/usergroups.class.php';
require K4_BASE_DIR. '/actions/admin/profilefields.class.php';
require K4_BASE_DIR. '/actions/admin/users.class.php';
require K4_BASE_DIR. '/actions/admin/options.class.php';
require K4_BASE_DIR. '/actions/admin/acronyms.class.php';
require K4_BASE_DIR. '/actions/admin/censors.class.php';
require K4_BASE_DIR. '/actions/admin/spiders.class.php';
require K4_BASE_DIR. '/actions/admin/css.class.php';
require K4_BASE_DIR. '/actions/admin/faq.class.php';
require K4_BASE_DIR. '/actions/admin/email.class.php';
require K4_BASE_DIR. '/actions/admin/posts.class.php';
require K4_BASE_DIR. '/actions/admin/masks.class.php';
require K4_BASE_DIR. '/actions/admin/titles.class.php';
require K4_BASE_DIR. '/actions/admin/cache.class.php';
require K4_BASE_DIR. '/actions/admin/announcements.class.php';

?>