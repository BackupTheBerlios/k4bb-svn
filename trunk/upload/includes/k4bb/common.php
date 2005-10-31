<?php
/**
* k4 Bulletin Board, common.php
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
* @version $Id: common.php 160 2005-07-18 16:28:46Z Peter Goodman $
* @package k42
*/

if(!defined('IN_K4')) {
	return;
}

define('VERSION', '2.0 Beta 7.1');

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
define('K4TOPICS',			'k4_topics');
define('K4REPLIES',			'k4_replies');
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
 * Warning and flagging levels
 */
define('WARN_GREEN',		0);
define('WARN_YELLOW',		1);
define('WARN_ORANGE',		2);
define('WARN_RED',			3);


/**
 * Some information about the user DO NOT CHANGE
 */
define('USER_AGENT',		isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : (isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? @gethostbyaddr($_SERVER['HTTP_X_FORWARDED_FOR']) : ''));
define('USER_IP',			get_ip());
define('USE_AJAX',			allow_AJAX());
define('USE_WYSIWYG',		(TRUE && allow_WYSIWYG()));

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
 * Hard coded forum id's
 */
define('GLBL_ANNOUNCEMENTS', 1);
define('GARBAGE_BIN', 2);
define('MASTER_FORUM_PERM', 39); // TODO: make a better solution for this map item


/**
 * Hard coded private message folder id's
 */
define('PM_INBOX', 1);
define('PM_SENTITEMS', 2);
define('PM_SAVEDITEMS', 3);

/**
 * The interval between cache reloads, and all of the cache files
 */

define('USE_CACHE',			TRUE);
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
define('K4_TABLE_CELLSPACING', 1);


/**
 * MAPs conditionals
 */
define('MAPS_EQUALS',		1);
define('MAPS_GREATER',		2);
define('MAPS_GEQ',			4);
define('MAPS_LESS',			8);
define('MAPS_LEQ',			16);

/**
 * Define all basic MAP items for categories, forums, etc.
 */
$_MAPITEMS['category'][]	= array('can_view' => GUEST, 'can_add' => SUPERADMIN, 'can_edit' => SUPERADMIN, 'can_del' => SUPERADMIN);

$_MAPITEMS['forum'][]		= array('can_view' => GUEST,			'can_add' => SUPERADMIN, 'can_edit' => SUPERADMIN, 'can_del' => SUPERADMIN);
//$_MAPITEMS['forum'][]		= array('varname' => 'topics',			'can_view' => GUEST, 'can_add' => MEMBER, 'can_edit' => MEMBER, 'can_del' => SUPERMEMBER);
//$_MAPITEMS['forum'][]		= array('varname' => 'other_topics',	'can_view' => 0, 'can_add' => 0, 'can_edit' => SUPERMEMBER, 'can_del' => SUPERMEMBER);
//$_MAPITEMS['forum'][]		= array('varname' => 'polls',			'can_view' => GUEST, 'can_add' => MEMBER, 'can_edit' => MEMBER, 'can_del' => SUPERMEMBER);
//$_MAPITEMS['forum'][]		= array('varname' => 'other_polls',		'can_view' => 0, 'can_add' => 0, 'can_edit' => SUPERMEMBER, 'can_del' => SUPERMEMBER);
//$_MAPITEMS['forum'][]		= array('varname' => 'replies',			'can_view' => GUEST, 'can_add' => MEMBER, 'can_edit' => MEMBER, 'can_del' => SUPERMEMBER);
//$_MAPITEMS['forum'][]		= array('varname' => 'other_replies',	'can_view' => 0, 'can_add' => 0, 'can_edit' => SUPERMEMBER, 'can_del' => SUPERMEMBER);
//$_MAPITEMS['forum'][]		= array('varname' => 'attachments',		'can_view' => GUEST, 'can_add' => MEMBER, 'can_edit' => MEMBER, 'can_del' => MODERATOR);
//$_MAPITEMS['forum'][]		= array('varname' => 'vote_on_poll',	'can_view' => 0, 'can_add' => MEMBER, 'can_edit' => 0, 'can_del' => 0);
//$_MAPITEMS['forum'][]		= array('varname' => 'rate_topic',		'can_view' => 0, 'can_add' => MEMBER, 'can_edit' => 0, 'can_del' => MODERATOR);
//$_MAPITEMS['forum'][]		= array('varname' => 'sticky',			'can_view' => GUEST, 'can_add' => MODERATOR, 'can_edit' => MODERATOR, 'can_del' => MODERATOR);
//$_MAPITEMS['forum'][]		= array('varname' => 'announce',		'can_view' => GUEST, 'can_add' => ADMIN, 'can_edit' => ADMIN, 'can_del' => ADMIN);
////$_MAPITEMS['forum'][]		= array('varname' => 'global',			'can_view' => GUEST, 'can_add' => ADMIN, 'can_edit' => ADMIN, 'can_del' => ADMIN);
//$_MAPITEMS['forum'][]		= array('varname' => 'feature',			'can_view' => GUEST, 'can_add' => ADMIN, 'can_edit' => ADMIN, 'can_del' => ADMIN);
//$_MAPITEMS['forum'][]		= array('varname' => 'move',			'can_view' => 0, 'can_add' => MODERATOR, 'can_edit' => 0, 'can_del' => 0);
//$_MAPITEMS['forum'][]		= array('varname' => 'queue',			'can_view' => 0, 'can_add' => MODERATOR, 'can_edit' => 0, 'can_del' => 0);
//$_MAPITEMS['forum'][]		= array('varname' => 'normalize',		'can_view' => 0, 'can_add' => MODERATOR, 'can_edit' => 0, 'can_del' => 0);
//$_MAPITEMS['forum'][]		= array('varname' => 'delete',			'can_view' => 0, 'can_add' => MODERATOR, 'can_edit' => 0, 'can_del' => 0);
//$_MAPITEMS['forum'][]		= array('varname' => 'closed',			'can_view' => GUEST, 'can_add' => SUPERMEMBER, 'can_edit' => SUPERMEMBER, 'can_del' => SUPERMEMBER);
//$_MAPITEMS['forum'][]		= array('varname' => 'avatars',			'can_view' => GUEST, 'can_add' => 0, 'can_edit' => 0, 'can_del' => 0);
//$_MAPITEMS['forum'][]		= array('varname' => 'signatures',		'can_view' => GUEST, 'can_add' => 0, 'can_edit' => 0, 'can_del' => 0);
//$_MAPITEMS['forum'][]		= array('varname' => 'html',			'can_view' => 0, 'can_add' => ADMIN, 'can_edit' => 0, 'can_del' => 0, 'value' => 'br,a,pre,ul,li,ol,p');
//$_MAPITEMS['forum'][]		= array('varname' => 'bbcode',			'can_view' => 0, 'can_add' => MEMBER, 'can_edit' => 0, 'can_del' => 0);
//$_MAPITEMS['forum'][]		= array('varname' => 'bbimgcode',		'can_view' => 0, 'can_add' => MEMBER, 'can_edit' => 0, 'can_del' => 0);
//$_MAPITEMS['forum'][]		= array('varname' => 'bbflashcode',		'can_view' => 0, 'can_add' => MEMBER, 'can_edit' => 0, 'can_del' => 0);
//$_MAPITEMS['forum'][]		= array('varname' => 'emoticons',		'can_view' => 0, 'can_add' => MEMBER, 'can_edit' => 0, 'can_del' => 0);
//$_MAPITEMS['forum'][]		= array('varname' => 'posticons',		'can_view' => 0, 'can_add' => MEMBER, 'can_edit' => 0, 'can_del' => 0);
//$_MAPITEMS['forum'][]		= array('varname' => 'post_save',		'can_view' => 0, 'can_add' => MEMBER, 'can_edit' => 0, 'can_del' => 0);
//$_MAPITEMS['forum'][]		= array('varname' => 'post_preview',	'can_view' => 0, 'can_add' => MEMBER, 'can_edit' => 0, 'can_del' => 0);
//$_MAPITEMS['forum'][]		= array('varname' => 'rss_feed',		'can_view' => GUEST, 'can_add' => 0, 'can_edit' => 0, 'can_del' => 0);


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
//$_QUERYPARAMS['info']		= "i.id AS id, i.parent_id AS parent_id, i.row_left AS row_left, i.row_right AS row_right, i.row_type AS row_type, i.row_order AS row_order, i.created AS created, i.row_level as row_level";
//$_QUERYPARAMS['category']	= ", c.name AS name, c.category_id AS category_id, c.description AS description, c.suspended AS suspended, c.archive AS archive, c.moderating_groups AS moderating_groups, c.moderating_users AS moderating_users";
//$_QUERYPARAMS['forum']		= ", f.name AS name, f.forum_id AS forum_id, f.category_id AS category_id, f.description AS description, f.archive AS archive, f.subforums AS subforums, f.is_forum AS is_forum, f.is_tracker AS is_tracker, f.is_link AS is_link, f.link_redirects AS link_redirects, f.link_show_redirects AS link_show_redirects, f.link_href AS link_href, f.moderating_groups AS moderating_groups, f.moderating_users AS moderating_users, f.pass AS pass, f.topics AS topics, f.replies AS replies, f.posts AS posts, f.topic_created AS topic_created, f.topic_name AS topic_name, f.topic_uname AS topic_uname, f.topic_id AS topic_id, f.topic_uid AS topic_uid, f.post_created AS post_created, f.post_name AS post_name, f.post_uname AS post_uname, f.post_id AS post_id, f.post_uid AS post_uid, f.topicsperpage AS topicsperpage, f.postsperpage AS postsperpage, f.maxpolloptions AS maxpolloptions, f.defaultlang AS defaultlang, f.num_viewing AS num_viewing, f.forum_rules AS forum_rules, f.topic_posticon AS topic_posticon, f.post_posticon AS post_posticon, f.defaultstyle AS defaultstyle, f.prune_frequency AS prune_frequency, f.prune_post_age AS prune_post_age, f.prune_post_viewed_age AS prune_post_viewed_age, f.prune_old_polls AS prune_old_polls, f.prune_announcements AS prune_announcements, f.prune_stickies AS prune_stickies, f.can_delete AS can_delete";
$_QUERYPARAMS['user']		= "u.id AS id, u.ip as ip, u.name AS name, u.email AS email, u.pass AS pass, u.priv_key AS priv_key, u.created AS created, u.login AS login, u.seen AS seen, u.last_seen AS last_seen, u.perms AS perms, u.invisible AS invisible, u.usergroups AS usergroups, u.reg_key AS reg_key, u.warn_level AS warn_level, u.flag_level AS flag_level, u.banned AS banned, u.new_pms AS new_pms";
$_QUERYPARAMS['userinfo']	= ", ui.user_id AS user_id, ui.fullname AS fullname, ui.num_posts AS num_posts, ui.total_posts AS total_posts, ui.timezone AS timezone, ui.field1 AS field1, ui.field2 AS field2, ui.field3 AS field3, ui.field4 AS field4, ui.field5 AS field5, ui.icq AS icq, ui.aim AS aim, ui.msn AS msn, ui.yahoo AS yahoo, ui.jabber AS jabber, ui.avatar AS avatar, ui.picture AS picture, ui.signature AS signature, ui.birthday AS birthday, ui.lastpage AS lastpage, ui.googletalk as googletalk, ui.user_title AS user_title";
$_QUERYPARAMS['session']	= ", s.id AS sid, s.seen AS seen, s.name AS name, s.user_id AS user_id, s.data AS data, s.location_file AS location_file, s.location_act AS location_act, s.location_id AS location_id, s.user_agent as user_agent";
$_QUERYPARAMS['maps']		= "m.id AS id, m.row_level AS row_level, m.name AS name, m.varname AS varname, m.is_global AS is_global, m.category_id AS category_id, m.forum_id AS forum_id, m.user_id AS user_id, m.group_id AS group_id, m.can_view AS can_view, m.can_add AS can_add, m.can_edit AS can_edit, m.can_del AS can_del, m.inherit AS inherit, m.value as value, m.parent_id AS parent_id";
//$_QUERYPARAMS['topic']		= ", t.name AS name, t.topic_id AS topic_id, t.forum_id AS forum_id, t.category_id AS category_id, t.edited_time AS edited_time, t.edited_username AS edited_username, t.edited_userid AS edited_userid, t.rating AS rating, t.ratings_sum AS ratings_sum, t.ratings_num AS ratings_num, t.disable_html AS disable_html, t.disable_bbcode AS disable_bbcode, t.disable_emoticons AS disable_emoticons, t.disable_sig AS disable_sig, t.disable_areply AS disable_areply, t.disable_aurls AS disable_aurls, t.topic_locked AS topic_locked, t.description AS description, t.body_text AS body_text, t.posticon AS posticon, t.poster_name AS poster_name, t.poster_id AS poster_id, t.reply_time AS reply_time, t.reply_uname AS reply_uname, t.reply_id AS reply_id, t.reply_uid AS reply_uid, t.is_poll AS is_poll, t.views AS views, t.is_draft AS is_draft, t.last_viewed as last_viewed, t.topic_type as topic_type, t.poster_ip as poster_ip, t.topic_expire AS topic_expire, t.is_feature AS is_feature, t.display AS display, t.queue AS queue, t.moved AS moved, t.num_replies AS num_replies, t.attachments AS attachments";
//$_QUERYPARAMS['reply']		= ", r.name AS name, r.reply_id AS reply_id, r.topic_id AS topic_id, r.forum_id AS forum_id, r.category_id AS category_id, r.body_text AS body_text, r.poster_name AS poster_name, r.poster_id AS poster_id, r.poster_ip AS poster_ip, r.edited_time AS edited_time, r.edited_username AS edited_username, r.edited_userid AS edited_userid, r.disable_html AS disable_html, r.disable_bbcode AS disable_bbcode, r.disable_emoticons AS disable_emoticons, r.disable_sig AS disable_sig, r.disable_areply AS disable_areply, r.disable_aurls AS disable_aurls, r.posticon AS posticon, r.num_replies AS num_replies, r.is_poll AS is_poll";
$_QUERYPARAMS['pfield']		= ", pf.name AS name, pf.title AS title, pf.description AS description, pf.default_value AS default_value, pf.inputtype AS inputtype, pf.user_maxlength AS user_maxlength, pf.inputoptions AS inputoptions, pf.min_perm AS min_perm, pf.display_register AS display_register, pf.display_profile AS display_profile, pf.display_topic AS display_topic, pf.display_post AS display_post, pf.display_image AS display_image, pf.display_memberlist AS display_memberlist, pf.display_size AS display_size, pf.display_rows AS display_rows, pf.display_order AS display_order, pf.is_editable AS is_editable, pf.is_private AS is_private, pf.is_required AS is_required, pf.special_pcre AS special_pcre";
$_QUERYPARAMS['usersettings']= ", us.user_id AS user_id, us.language AS language, us.styleset AS styleset, us.imageset AS imageset, us.templateset AS templateset, us.topic_display AS topic_display, us.notify_pm AS notify_pm, us.popup_pm AS popup_pm, us.viewflash AS viewflash, us.viewemoticons AS viewemoticons, us.viewsigs AS viewsigs, us.viewavatars AS viewavatars, us.viewcensors AS viewcensors, us.attachsig AS attachsig, us.attachavatar AS attachavatar, us.topicsperpage AS topicsperpage, us.postsperpage AS postsperpage, us.topic_threaded AS topic_threaded";


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

$GLOBALS['_URL']					= $_URL;
$GLOBALS['_MAPITEMS']				= &$_MAPITEMS;

?>