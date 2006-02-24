<?php
/**
* k4 Bulletin Board, common.php
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
* @version $Id: common.php 160 2005-07-18 16:28:46Z Peter Goodman $
* @package k42
*/

if(!defined('IN_K4')) {
	return;
}

define('VERSION', '2.0 PRC1');

/**
 * Constants that define what a category/forum/thread/etc is
 * DO NOT CHANGE
 */

define('CATEGORY', 1);
define('FORUM', 2);
define('TOPIC', 4);
define('REPLY', 8);
define('GALLERY', 16);
define('IMAGE', 32);
define('FAQCATEGORY', 64);
define('FAQANSWER', 128);
define('METAFORUM', 256);
define('ARCHIVEFORUM', 512);


/**
 * Constants that represent all of the tables
 * DO NOT CHANGE these if you don't know what you're doing
 */

define('K4CACHE',			'k4_cache');
define('K4SESSIONS',		'k4_sessions');
define('K4SETTINGGROUPS',	'k4_settinggroups');
define('K4SETTINGS',		'k4_settings');
define('K4CSS',				'k4_css');
define('K4STYLES',			'k4_styles');
define('K4USERS',			'k4_users');
define('K4USERINFO',		'k4_userinfo');
define('K4CATEGORIES',		'k4_categories');
define('K4FORUMS',			'k4_forums');
define('K4POSTS',			'k4_posts');
define('K4MAPS',			'k4_maps');
define('K4DATASTORE',		'k4_datastore');
define('K4POSTICONS',		'k4_posticons');
define('K4EMOTICONS',		'k4_emoticons');
define('K4USERGROUPS',		'k4_usergroups');
define('K4POLLQUESTIONS',	'k4_pollquestions');
define('K4POLLANSWERS',		'k4_pollanswers');
define('K4POLLVOTES',		'k4_pollvotes');
define('K4PROFILEFIELDS',	'k4_userprofilefields');
define('K4BADUSERNAMES',	'k4_badusernames');
define('K4SUBSCRIPTIONS',	'k4_subscriptions');
define('K4MAILQUEUE',		'k4_mailqueue');
define('K4TOPICQUEUE',		'k4_topicqueue');
define('K4RATINGS',			'k4_ratings');
define('K4ATTACHMENTS',		'k4_attachments');
define('K4USERSETTINGS',	'k4_usersettings');
define('K4AVATARS',			'k4_avatars');
define('K4PPICTURES',		'k4_personalpictues');
define('K4ACRONYMS',		'k4_acronyms');
define('K4WORDCENSORS',		'k4_wordcensors');
define('K4SPIDERS',			'k4_spiders');
define('K4BADPOSTREPORTS',	'k4_badpostreports');
define('K4BANNEDUSERS',		'k4_bannedusers');
define('K4PMFOLDERS',		'k4_pmfolders');
define('K4PRIVMESSAGES',	'k4_privmessages');
define('K4PRIVMSGDRAFTS',	'k4_privmessagedrafts');
define('K4PRIVMSGTRACKER',	'k4_privmessagetracker');
define('K4FAQCATEGORIES',	'k4_faqcategories');
define('K4FAQANSWERS',		'k4_faqanswers');
define('K4USERTITLES',		'k4_usertitles');
define('K4FORUMFILTERS',	'k4_forumfilters');
define('K4FILTERS',			'k4_filters');
define('K4TEMPTABLE',		'k4_'. substr(md5(uniqid(rand(), true)), 0, 16)); // special table

/**
 * User permission levels, DO NOT CHANGE
 */

define('UNDEFINED',			0);
define('GUEST',				1);
define('PENDING_MEMBER',	4);
define('MEMBER',			5);
define('SUPERMEMBER',		6);
define('MODERATOR',			7);
define('SUPERMOD',			8);
define('ADMIN',				9);
define('SUPERADMIN',		10);


/**
 * Warning and flagging levels, DO NOT CHANGE
 */
define('WARN_GREEN',		0);
define('WARN_YELLOW',		1);
define('WARN_ORANGE',		2);
define('WARN_RED',			3);


/**
 * Some information about the user, DO NOT CHANGE
 */
define('USER_AGENT',		isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : (isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? @gethostbyaddr($_SERVER['HTTP_X_FORWARDED_FOR']) : ''));
define('USER_IP',			get_ip());
define('USE_XMLHTTP',		allow_xmlhttp());
define('USE_TOTAL_XMLHTTP',	FALSE); // 
define('USE_WYSIWYG',		(FALSE && allow_WYSIWYG()));

/**
 * The current k4 Url
 */
define('K4_URL', current_url(TRUE));

/**
 * Topic Types, DO NOT CHANGE
 */
define('TOPIC_NORMAL',		1);
define('TOPIC_STICKY',		2);
define('TOPIC_ANNOUNCE',	3);


/**
 * Hard coded forum id's, DO NOT CHANGE
 */
define('GLBL_ANNOUNCEMENTS', 1);
define('GARBAGE_BIN', 2);


/**
 * Hard coded private message folder id's, DO NOT CHANGE
 */
define('PM_INBOX', 1);
define('PM_SENTITEMS', 2);
define('PM_SAVEDITEMS', 3);

/**
 * The interval between cache reloads, and all of the cache files
 */

define('USE_CACHE',			TRUE); // don't set this to false, TODO: debug this
define('CACHE_INTERVAL',	86400); // 24 hours
define('CACHE_DIR',			BB_BASE_DIR .'/tmp/cache/');
define('POST_IMPULSE_LIMIT',45); // seconds allowed between posts, 15 at least
define('EMAIL_INTERVAL',	500); // 1000 at most (frequency).
define('TOPIC_INTERVAL',	15); // 20 at most (frequency).


/**
 * Cookie settings
 */
define('K4COOKIE_ID',		'k4_user_id');
define('K4COOKIE_KEY',		'k4_user_key');
define('K4LASTSEEN',		'k4_last_seen');
define('K4FORUMINFO',		'k4_forum_info');
define('K4TOPICINFO',		'k4_topic_info');


/**
 * Global Style Stuff
 */
define('K4_TABLE_CELLSPACING', 0);


/**
 * MAPs conditionals, DO NOT CHANGE
 */
define('MAPS_EQUALS',		1);
define('MAPS_GREATER',		2);
define('MAPS_GEQ',			4);
define('MAPS_LESS',			8);
define('MAPS_LEQ',			16);


/**
 * Parameters for meta forums, DO NOT CHANGE
 */
define('META_STRING',		1);
define('META_INT',			2);
define('META_TIME',			4);
define('META_ID',			8);
define('META_SAME',			16);


/**
 * Pagination constants for archiving / rss feeds
 */
define('XMLTOPICSPERPAGE',	30);
define('XMLPOSTSPERPAGE',	15);


/**
 * RSS Parser constants, DO NOT CHANGE
 */
define('XML_RSS',			1);
define('XML_CHANNEL',		2);
define('XML_IMAGE',			3);
define('XML_ITEM',			4);


/**
 * Server stuff, YOU CAN CHANGE!
 */
define('MAXALLOWEDSERVERLOAD', 1000); // in miliseconds

/**
 * Define all basic MAP items for categories, forums, etc.
 */
$_MAPITEMS['category'][]	= array('can_view' => GUEST, 'can_add' => SUPERADMIN, 'can_edit' => SUPERADMIN, 'can_del' => SUPERADMIN);
$_MAPITEMS['forum'][]		= array('can_view' => GUEST,			'can_add' => SUPERADMIN, 'can_edit' => SUPERADMIN, 'can_del' => SUPERADMIN);
$_MAPITEMS['blog'][]		= array('can_view' => GUEST,			'can_add' => MEMBER, 'can_edit' => MEMBER, 'can_del' => MEMBER);
$_MAPITEMS['blog'][]		= array('varname' => 'blogs',			'can_view' => GUEST, 'can_add' => MEMBER, 'can_edit' => MEMBER, 'can_del' => MEMBER);
$_MAPITEMS['blog'][]		= array('varname' => 'other_blogs',		'can_view' => GUEST, 'can_add' => MODERATOR, 'can_edit' => MODERATOR, 'can_del' => MODERATOR);
$_MAPITEMS['blog'][]		= array('varname' => 'comments',		'can_view' => GUEST, 'can_add' => MEMBER, 'can_edit' => MEMBER, 'can_del' => MEMBER);
$_MAPITEMS['blog'][]		= array('varname' => 'other_comments',	'can_view' => 0, 'can_add' => 0, 'can_edit' => SUPERMEMBER, 'can_del' => SUPERMEMBER);
$_MAPITEMS['blog'][]		= array('varname' => 'avatars',			'can_view' => GUEST, 'can_add' => 0, 'can_edit' => 0, 'can_del' => 0);
$_MAPITEMS['blog'][]		= array('varname' => 'signatures',		'can_view' => GUEST, 'can_add' => 0, 'can_edit' => 0, 'can_del' => 0);
$_MAPITEMS['blog'][]		= array('varname' => 'html',			'can_view' => 0, 'can_add' => ADMIN, 'can_edit' => 0, 'can_del' => 0, 'value' => 'br,a,pre,ul,li,ol,p');
$_MAPITEMS['blog'][]		= array('varname' => 'bbcode',			'can_view' => 0, 'can_add' => MEMBER, 'can_edit' => 0, 'can_del' => 0);
$_MAPITEMS['blog'][]		= array('varname' => 'bbimgcode',		'can_view' => 0, 'can_add' => MEMBER, 'can_edit' => 0, 'can_del' => 0);
$_MAPITEMS['blog'][]		= array('varname' => 'bbflashcode',		'can_view' => 0, 'can_add' => MEMBER, 'can_edit' => 0, 'can_del' => 0);
$_MAPITEMS['blog'][]		= array('varname' => 'emoticons',		'can_view' => 0, 'can_add' => MEMBER, 'can_edit' => 0, 'can_del' => 0);
$_MAPITEMS['blog'][]		= array('varname' => 'posticons',		'can_view' => 0, 'can_add' => MEMBER, 'can_edit' => 0, 'can_del' => 0);
$_MAPITEMS['blog'][]		= array('varname' => 'post_save',		'can_view' => 0, 'can_add' => MEMBER, 'can_edit' => 0, 'can_del' => 0);
$_MAPITEMS['blog'][]		= array('varname' => 'post_preview',	'can_view' => 0, 'can_add' => MEMBER, 'can_edit' => 0, 'can_del' => 0);
$_MAPITEMS['blog'][]		= array('varname' => 'trackback',		'can_view' => GUEST, 'can_add' => 0, 'can_edit' => 0, 'can_del' => 0);
$_MAPITEMS['blog'][]		= array('varname' => 'private_blog',	'can_view' => 0, 'can_add' => MEMBER, 'can_edit' => 0, 'can_del' => 0);

/**
 * Query Parameters for things such as forums, categories, users, etc
 */
$_QUERYPARAMS['user']		= "u.id AS id, u.ip as ip, u.name AS name, u.email AS email, u.pass AS pass, u.priv_key AS priv_key, u.created AS created, u.login AS login, u.seen AS seen, u.last_seen AS last_seen, u.perms AS perms, u.invisible AS invisible, u.usergroups AS usergroups, u.reg_key AS reg_key, u.warn_level AS warn_level, u.flag_level AS flag_level, u.banned AS banned, u.new_pms AS new_pms ";
$_QUERYPARAMS['userinfo']	= ", ui.user_id AS user_id, ui.fullname AS fullname, ui.num_posts AS num_posts, ui.total_posts AS total_posts, ui.timezone AS timezone, ui.user_title AS user_title, ui.icq AS icq, ui.aim AS aim, ui.msn AS msn, ui.yahoo AS yahoo, ui.jabber AS jabber, ui.googletalk AS googletalk, ui.avatar AS avatar, ui.picture AS picture, ui.signature AS signature, ui.birthday AS birthday, ui.lastpage AS lastpage ";
$_QUERYPARAMS['session']	= ", s.id AS sid, s.seen AS seen, s.name AS name, s.user_id AS user_id, s.data AS data, s.location_file AS location_file, s.location_act AS location_act, s.location_id AS location_id, s.user_agent as user_agent ";
$_QUERYPARAMS['maps']		= "m.id AS id, m.row_level AS row_level, m.name AS name, m.varname AS varname, m.is_global AS is_global, m.category_id AS category_id, m.forum_id AS forum_id, m.user_id AS user_id, m.group_id AS group_id, m.can_view AS can_view, m.can_add AS can_add, m.can_edit AS can_edit, m.can_del AS can_del, m.inherit AS inherit, m.value as value, m.parent_id AS parent_id ";
$_QUERYPARAMS['pfield']		= ", pf.name AS name, pf.title AS title, pf.description AS description, pf.default_value AS default_value, pf.inputtype AS inputtype, pf.user_maxlength AS user_maxlength, pf.inputoptions AS inputoptions, pf.min_perm AS min_perm, pf.display_register AS display_register, pf.display_profile AS display_profile, pf.display_topic AS display_topic, pf.display_post AS display_post, pf.display_image AS display_image, pf.display_memberlist AS display_memberlist, pf.display_size AS display_size, pf.display_rows AS display_rows, pf.display_order AS display_order, pf.is_editable AS is_editable, pf.is_private AS is_private, pf.is_required AS is_required, pf.special_pcre AS special_pcre ";
$_QUERYPARAMS['usersettings']= ", us.user_id AS user_id, us.language AS language, us.styleset AS styleset, us.imageset AS imageset, us.templateset AS templateset, us.topic_display AS topic_display, us.topic_threaded AS topic_threaded, us.notify_pm AS notify_pm, us.popup_pm AS popup_pm, us.viewflash AS viewflash, us.viewemoticons AS viewemoticons, us.viewsigs AS viewsigs, us.viewavatars AS viewavatars, us.viewimages AS viewimages, us.viewcensors AS viewcensors, us.attachsig AS attachsig, us.attachavatar AS attachavatar, us.topicsperpage AS topicsperpage, us.postsperpage AS postsperpage ";


// Filter out all 
function k4_error_filter(&$error) {
	if (!$error->type & E_USER_ERROR) {
		return TRUE;
	}
}


/**
 * Set our error handler
 */
// This is a stack.  The first handler pushed onto the stack
// will be the last handler called.  There is also no guarantee
// that it will even be called because handlers are allowed
// to 'handle' the error and thus prevent it from perpetuating
// up the stack.
push_error_handler('k4_fatal_error');
push_error_handler('k4_error_filter');


/**
 * Set some super-globals
 */
$_URL								= new FAUrl(current_url());
$_URL->args['nojs']					= (isset($_COOKIE['k4_canjs']) && intval($_COOKIE['k4_canjs']) == 1) ? 0 : (isset($_COOKIE['k4_canjs']) ? 1 : 0);

$GLOBALS['_URL']					= &$_URL;
$GLOBALS['_MAPITEMS']				= &$_MAPITEMS;

?>