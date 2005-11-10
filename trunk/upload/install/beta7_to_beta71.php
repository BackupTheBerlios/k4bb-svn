<?php
/**
* k4 Bulletin Board, beta7_to_beta71.php
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

error_reporting(E_ALL);

require "../includes/filearts/filearts.php";
require "../includes/k4bb/k4bb.php";

class K4DefaultAction extends FAAction {
	function execute(&$request) {
		
		/**
		 * Create two new tables
		 */
		@$request['dba']->executeUpdate("DROP TABLE IF EXISTS k4_usertitles");
		@$request['dba']->executeUpdate("DROP TABLE IF EXISTS k4_cache");
		$request['dba']->executeUpdate("CREATE TABLE k4_usertitles (title_id INT UNSIGNED NOT NULL AUTO_INCREMENT,num_posts INT UNSIGNED NOT NULL DEFAULT 0,title_text VARCHAR(50) NOT NULL DEFAULT '',num_pips INT UNSIGNED NOT NULL DEFAULT 0,image VARCHAR(100) NOT NULL DEFAULT '',PRIMARY KEY(title_id));");
		$request['dba']->executeUpdate("CREATE TABLE k4_cache (varname CHAR(30) NOT NULL DEFAULT '',data LONGTEXT,modified INT UNSIGNED NOT NULL DEFAULT 0,PRIMARY KEY(varname));");
		
		
		/**
		 * Insert the default user titles
		 */
		$request['dba']->executeUpdate("INSERT INTO k4_usertitles (title_id,num_posts,title_text,num_pips,image) VALUES (1,0,'New',1,'');");
		$request['dba']->executeUpdate("INSERT INTO k4_usertitles (title_id,num_posts,title_text,num_pips,image) VALUES (2,10,'Still New',2,'');");
		$request['dba']->executeUpdate("INSERT INTO k4_usertitles (title_id,num_posts,title_text,num_pips,image) VALUES (3,100,'Well Known',3,'');");

		/**
		 * Insert the default cache items
		 */
		$request['dba']->executeUpdate("INSERT INTO k4_cache (varname, data, modified) VALUES ('acronyms', '', 0);");
		$request['dba']->executeUpdate("INSERT INTO k4_cache (varname, data, modified) VALUES ('banned_user_ids', '', 0);");
		$request['dba']->executeUpdate("INSERT INTO k4_cache (varname, data, modified) VALUES ('banned_user_ips', '', 0);");
		$request['dba']->executeUpdate("INSERT INTO k4_cache (varname, data, modified) VALUES ('censors', '', 0);");
		$request['dba']->executeUpdate("INSERT INTO k4_cache (varname, data, modified) VALUES ('datastore', '', 0);");
		$request['dba']->executeUpdate("INSERT INTO k4_cache (varname, data, modified) VALUES ('faq_categories', '', 0);");
		$request['dba']->executeUpdate("INSERT INTO k4_cache (varname, data, modified) VALUES ('flagged_users', '', 0);");
		$request['dba']->executeUpdate("INSERT INTO k4_cache (varname, data, modified) VALUES ('all_forums', '', 0);");
		$request['dba']->executeUpdate("INSERT INTO k4_cache (varname, data, modified) VALUES ('mail_queue', '', 0);");
		$request['dba']->executeUpdate("INSERT INTO k4_cache (varname, data, modified) VALUES ('maps', '', 0);");
		$request['dba']->executeUpdate("INSERT INTO k4_cache (varname, data, modified) VALUES ('profile_fields', '', 0);");
		$request['dba']->executeUpdate("INSERT INTO k4_cache (varname, data, modified) VALUES ('settings', '', 0);");
		$request['dba']->executeUpdate("INSERT INTO k4_cache (varname, data, modified) VALUES ('spiders', '', 0);");
		$request['dba']->executeUpdate("INSERT INTO k4_cache (varname, data, modified) VALUES ('spider_agents', '', 0);");
		$request['dba']->executeUpdate("INSERT INTO k4_cache (varname, data, modified) VALUES ('styles', '', 0);");
		$request['dba']->executeUpdate("INSERT INTO k4_cache (varname, data, modified) VALUES ('usergroups', '', 0);");
		$request['dba']->executeUpdate("INSERT INTO k4_cache (varname, data, modified) VALUES ('user_titles', '', 0);");
		
		/**
		 * Insert the master forum permissions
		 */
		$max_map_id = intval($request['dba']->getValue("SELECT MAX(id) FROM k4_maps") + 1);

		$request['dba']->executeUpdate("INSERT INTO k4_maps (id, row_level, name, varname, value, is_global, category_id, forum_id, user_id, group_id, parent_id, num_children, can_view, can_add, can_edit, can_del) VALUES (". $max_map_id .", 1, 'Master Forum Permissions', 'forum0', '', 0, 0, 0, 0, 0, 0, 28, 1, 10, 10, 10);");
		$request['dba']->executeUpdate("INSERT INTO k4_maps (id, row_level, name, varname, value, is_global, category_id, forum_id, user_id, group_id, parent_id, num_children, can_view, can_add, can_edit, can_del) VALUES (". intval($max_map_id+1) .", 2, 'Topics', 'topics', '', 0, 0, 0, 0, 0, ". $max_map_id .", 0, 1, 5, 5, 6);");
		$request['dba']->executeUpdate("INSERT INTO k4_maps (id, row_level, name, varname, value, is_global, category_id, forum_id, user_id, group_id, parent_id, num_children, can_view, can_add, can_edit, can_del) VALUES (". intval($max_map_id+2) .", 2, 'Other People''s Topics', 'other_topics', '', 0, 0, 0, 0, 0, ". $max_map_id .", 0, 0, 0, 6, 6);");
		$request['dba']->executeUpdate("INSERT INTO k4_maps (id, row_level, name, varname, value, is_global, category_id, forum_id, user_id, group_id, parent_id, num_children, can_view, can_add, can_edit, can_del) VALUES (". intval($max_map_id+3) .", 2, 'Polls', 'polls', '', 0, 0, 0, 0, 0, ". $max_map_id .", 0, 1, 5, 5, 6);");
		$request['dba']->executeUpdate("INSERT INTO k4_maps (id, row_level, name, varname, value, is_global, category_id, forum_id, user_id, group_id, parent_id, num_children, can_view, can_add, can_edit, can_del) VALUES (". intval($max_map_id+4) .", 2, 'Other People''s Polls', 'other_polls', '', 0, 0, 0, 0, 0, ". $max_map_id .", 0, 0, 0, 6, 6);");
		$request['dba']->executeUpdate("INSERT INTO k4_maps (id, row_level, name, varname, value, is_global, category_id, forum_id, user_id, group_id, parent_id, num_children, can_view, can_add, can_edit, can_del) VALUES (". intval($max_map_id+5) .", 2, 'Replies', 'replies', '', 0, 0, 0, 0, 0, ". $max_map_id .", 0, 1, 5, 5, 6);");
		$request['dba']->executeUpdate("INSERT INTO k4_maps (id, row_level, name, varname, value, is_global, category_id, forum_id, user_id, group_id, parent_id, num_children, can_view, can_add, can_edit, can_del) VALUES (". intval($max_map_id+6) .", 2, 'Other People''s Replies', 'other_replies', '', 0, 0, 0, 0, 0, ". $max_map_id .", 0, 0, 0, 6, 6);");
		$request['dba']->executeUpdate("INSERT INTO k4_maps (id, row_level, name, varname, value, is_global, category_id, forum_id, user_id, group_id, parent_id, num_children, can_view, can_add, can_edit, can_del) VALUES (". intval($max_map_id+7) .", 2, 'Attachments', 'attachments', '', 0, 0, 0, 0, 0, ". $max_map_id .", 0, 1, 5, 5, 7);");
		$request['dba']->executeUpdate("INSERT INTO k4_maps (id, row_level, name, varname, value, is_global, category_id, forum_id, user_id, group_id, parent_id, num_children, can_view, can_add, can_edit, can_del) VALUES (". intval($max_map_id+8) .", 2, 'Vote on Polls', 'vote_on_poll', '', 0, 0, 0, 0, 0, ". $max_map_id .", 0, 0, 5, 0, 0);");
		$request['dba']->executeUpdate("INSERT INTO k4_maps (id, row_level, name, varname, value, is_global, category_id, forum_id, user_id, group_id, parent_id, num_children, can_view, can_add, can_edit, can_del) VALUES (". intval($max_map_id+9) .", 2, 'Rate Topics', 'rate_topic', '', 0, 0, 0, 0, 0, ". $max_map_id .", 0, 0, 5, 0, 7);");
		$request['dba']->executeUpdate("INSERT INTO k4_maps (id, row_level, name, varname, value, is_global, category_id, forum_id, user_id, group_id, parent_id, num_children, can_view, can_add, can_edit, can_del) VALUES (". intval($max_map_id+10) .", 2, 'Sticky Topics', 'sticky', '', 0, 0, 0, 0, 0, ". $max_map_id .", 0, 1, 7, 7, 7);");
		$request['dba']->executeUpdate("INSERT INTO k4_maps (id, row_level, name, varname, value, is_global, category_id, forum_id, user_id, group_id, parent_id, num_children, can_view, can_add, can_edit, can_del) VALUES (". intval($max_map_id+11) .", 2, 'Announcement Topics', 'announce', '', 0, 0, 0, 0, 0, ". $max_map_id .", 0, 1, 9, 9, 9);");
		$request['dba']->executeUpdate("INSERT INTO k4_maps (id, row_level, name, varname, value, is_global, category_id, forum_id, user_id, group_id, parent_id, num_children, can_view, can_add, can_edit, can_del) VALUES (". intval($max_map_id+12) .", 2, 'Featured Topics', 'feature', '', 0, 0, 0, 0, 0, ". $max_map_id .", 0, 1, 9, 9, 9);");
		$request['dba']->executeUpdate("INSERT INTO k4_maps (id, row_level, name, varname, value, is_global, category_id, forum_id, user_id, group_id, parent_id, num_children, can_view, can_add, can_edit, can_del) VALUES (". intval($max_map_id+13) .", 2, 'Moved Topics', 'move', '', 0, 0, 0, 0, 0, ". $max_map_id .", 0, 0, 7, 0, 0);");
		$request['dba']->executeUpdate("INSERT INTO k4_maps (id, row_level, name, varname, value, is_global, category_id, forum_id, user_id, group_id, parent_id, num_children, can_view, can_add, can_edit, can_del) VALUES (". intval($max_map_id+14) .", 2, 'Queue Topics', 'queue', '', 0, 0, 0, 0, 0, ". $max_map_id .", 0, 0, 7, 0, 0);");
		$request['dba']->executeUpdate("INSERT INTO k4_maps (id, row_level, name, varname, value, is_global, category_id, forum_id, user_id, group_id, parent_id, num_children, can_view, can_add, can_edit, can_del) VALUES (". intval($max_map_id+15) .", 2, 'Normalize Topics', 'normalize', '', 0, 0, 0, 0, 0, ". $max_map_id .", 0, 0, 7, 0, 0);");
		$request['dba']->executeUpdate("INSERT INTO k4_maps (id, row_level, name, varname, value, is_global, category_id, forum_id, user_id, group_id, parent_id, num_children, can_view, can_add, can_edit, can_del) VALUES (". intval($max_map_id+16) .", 2, 'Delete', 'delete', '', 0, 0, 0, 0, 0, ". $max_map_id .", 0, 0, 7, 0, 0);");
		$request['dba']->executeUpdate("INSERT INTO k4_maps (id, row_level, name, varname, value, is_global, category_id, forum_id, user_id, group_id, parent_id, num_children, can_view, can_add, can_edit, can_del) VALUES (". intval($max_map_id+17) .", 2, 'Closed Topics', 'closed', '', 0, 0, 0, 0, 0, ". $max_map_id .", 0, 1, 6, 6, 6);");
		$request['dba']->executeUpdate("INSERT INTO k4_maps (id, row_level, name, varname, value, is_global, category_id, forum_id, user_id, group_id, parent_id, num_children, can_view, can_add, can_edit, can_del) VALUES (". intval($max_map_id+18) .", 2, 'User Avatars', 'avatars', '', 0, 0, 0, 0, 0, ". $max_map_id .", 0, 1, 0, 0, 0);");
		$request['dba']->executeUpdate("INSERT INTO k4_maps (id, row_level, name, varname, value, is_global, category_id, forum_id, user_id, group_id, parent_id, num_children, can_view, can_add, can_edit, can_del) VALUES (". intval($max_map_id+19) .", 2, 'User Signatures', 'signatures', '', 0, 0, 0, 0, 0, ". $max_map_id .", 0, 1, 0, 0, 0);");
		$request['dba']->executeUpdate("INSERT INTO k4_maps (id, row_level, name, varname, value, is_global, category_id, forum_id, user_id, group_id, parent_id, num_children, can_view, can_add, can_edit, can_del) VALUES (". intval($max_map_id+20) .", 2, 'HTML Code', 'html', 'br,a,pre,ul,li,ol,p', 0, 0, 0, 0, 0, ". $max_map_id .", 0, 0, 9, 0, 0);");
		$request['dba']->executeUpdate("INSERT INTO k4_maps (id, row_level, name, varname, value, is_global, category_id, forum_id, user_id, group_id, parent_id, num_children, can_view, can_add, can_edit, can_del) VALUES (". intval($max_map_id+21) .", 2, 'BB Code', 'bbcode', '', 0, 0, 0, 0, 0, ". $max_map_id .", 0, 0, 5, 0, 0);");
		$request['dba']->executeUpdate("INSERT INTO k4_maps (id, row_level, name, varname, value, is_global, category_id, forum_id, user_id, group_id, parent_id, num_children, can_view, can_add, can_edit, can_del) VALUES (". intval($max_map_id+22) .", 2, 'BB IMG Code', 'bbimgcode', '', 0, 0, 0, 0, 0, ". $max_map_id .", 0, 0, 5, 0, 0);");
		$request['dba']->executeUpdate("INSERT INTO k4_maps (id, row_level, name, varname, value, is_global, category_id, forum_id, user_id, group_id, parent_id, num_children, can_view, can_add, can_edit, can_del) VALUES (". intval($max_map_id+23) .", 2, 'BB Flash Code', 'bbflashcode', '', 0, 0, 0, 0, 0, ". $max_map_id .", 0, 0, 5, 0, 0);");
		$request['dba']->executeUpdate("INSERT INTO k4_maps (id, row_level, name, varname, value, is_global, category_id, forum_id, user_id, group_id, parent_id, num_children, can_view, can_add, can_edit, can_del) VALUES (". intval($max_map_id+24) .", 2, 'Emoticons', 'emoticons', '', 0, 0, 0, 0, 0, ". $max_map_id .", 0, 0, 5, 0, 0);");
		$request['dba']->executeUpdate("INSERT INTO k4_maps (id, row_level, name, varname, value, is_global, category_id, forum_id, user_id, group_id, parent_id, num_children, can_view, can_add, can_edit, can_del) VALUES (". intval($max_map_id+25) .", 2, 'Post Icons', 'posticons', '', 0, 0, 0, 0, 0, ". $max_map_id .", 0, 0, 5, 0, 0);");
		$request['dba']->executeUpdate("INSERT INTO k4_maps (id, row_level, name, varname, value, is_global, category_id, forum_id, user_id, group_id, parent_id, num_children, can_view, can_add, can_edit, can_del) VALUES (". intval($max_map_id+26) .", 2, 'Post Saving', 'post_save', '', 0, 0, 0, 0, 0, ". $max_map_id .", 0, 0, 5, 0, 0);");
		$request['dba']->executeUpdate("INSERT INTO k4_maps (id, row_level, name, varname, value, is_global, category_id, forum_id, user_id, group_id, parent_id, num_children, can_view, can_add, can_edit, can_del) VALUES (". intval($max_map_id+27) .", 2, 'Post Previewing', 'post_preview', '', 0, 0, 0, 0, 0, ". $max_map_id .", 0, 0, 5, 0, 0);");
		$request['dba']->executeUpdate("INSERT INTO k4_maps (id, row_level, name, varname, value, is_global, category_id, forum_id, user_id, group_id, parent_id, num_children, can_view, can_add, can_edit, can_del) VALUES (". intval($max_map_id+28) .", 2, 'RSS Feeds', 'rss_feed', '', 0, 0, 0, 0, 0, ". $max_map_id .", 0, 1, 0, 0, 0);");
		$request['dba']->executeUpdate("INSERT INTO k4_maps (id, row_level, name, varname, value, is_global, category_id, forum_id, user_id, group_id, parent_id, num_children, can_view, can_add, can_edit, can_del) VALUES (". intval($max_map_id+29) .", 2, 'Global Announcements', 'forum1', '', 0, 0, 1, 0, 0, 15, 0, 7, 10, 10, 10);");
		$request['dba']->executeUpdate("INSERT INTO k4_maps (id, row_level, name, varname, value, is_global, category_id, forum_id, user_id, group_id, parent_id, num_children, can_view, can_add, can_edit, can_del) VALUES (". intval($max_map_id+30) .", 2, 'Garbage Bin', 'forum2', '', 0, 0, 2, 0, 0, 15, 0, 7, 10, 10, 10);");
		$request['dba']->executeUpdate("INSERT INTO k4_maps (id, row_level, name, varname, value, is_global, category_id, forum_id, user_id, group_id, parent_id, num_children, can_view, can_add, can_edit, can_del) VALUES (". intval($max_map_id+31) .", 3, 'Topics', 'topics', '', 0, 0, 2, 0, 0, 68, 0, 1, 7, 7, 7);");
		$request['dba']->executeUpdate("INSERT INTO k4_maps (id, row_level, name, varname, value, is_global, category_id, forum_id, user_id, group_id, parent_id, num_children, can_view, can_add, can_edit, can_del) VALUES (". intval($max_map_id+32) .", 3, 'Other People''s Topics', 'other_topics', '', 0, 0, 2, 0, 0, 68, 0, 0, 0, 7, 7);");
		$request['dba']->executeUpdate("INSERT INTO k4_maps (id, row_level, name, varname, value, is_global, category_id, forum_id, user_id, group_id, parent_id, num_children, can_view, can_add, can_edit, can_del) VALUES (". intval($max_map_id+33) .", 3, 'Other People''s Replies', 'other_replies', '', 0, 0, 2, 0, 0, 68, 0, 0, 0, 6, 6);");
		$request['dba']->executeUpdate("INSERT INTO k4_maps (id, row_level, name, varname, value, is_global, category_id, forum_id, user_id, group_id, parent_id, num_children, can_view, can_add, can_edit, can_del) VALUES (". intval($max_map_id+34) .", 2, 'Test Category', 'category1', '', 0, 1, 0, 0, 0, 14, 0, 1, 10, 10, 10);");
		$request['dba']->executeUpdate("INSERT INTO k4_maps (id, row_level, name, varname, value, is_global, category_id, forum_id, user_id, group_id, parent_id, num_children, can_view, can_add, can_edit, can_del) VALUES (". intval($max_map_id+35) .", 2, 'Test Forum', 'forum3', '', 0, 1, 3, 0, 0, 15, 0, 1, 10, 10, 10);");
		
		/**
		 * Free up space in the MAPS table by removing all forum map perms except for the top ones
		 */
		$request['dba']->executeUpdate("DELETE FROM k4_maps WHERE forum_id > 0 AND varname NOT LIKE 'forum%'");
		
		/**
		 * Alter some tables
		 */
		$request['dba']->alterTable(K4MAPS, "DROP can_view_condition");
		$request['dba']->alterTable(K4MAPS, "DROP can_edit_condition");
		$request['dba']->alterTable(K4MAPS, "DROP can_add_condition");
		$request['dba']->alterTable(K4MAPS, "DROP can_del_condition");
		$request['dba']->alterTable(K4USERINFO, "ADD user_title VARCHAR(50) NOT NULL DEFAULT ''");

		/**
		 * Loop through the forums and change the way usergroups are stored
		 */
		$forums = $request['dba']->executeQuery("SELECT moderating_groups,forum_id FROM k4_forums");
		while($forums->next()) {
			$forum = $forums->current();
			$usergroups = $forum['usergroups'] != '' ? unserialize($forum['moderating_groups']) : array();
			$usergroups = !is_array($usergroups) ? array() : $usergroups;
			$usergroups = implode('|', $usergroups);
			$request['dba']->executeUpdate("UPDATE k4_forums SET moderating_groups = '". $usergroups ."' WHERE forum_id=". intval($forum['forum_id']));
		}
		$forums->free();

		/**
		 * Loop through the users and change the way their usergroups are stored.. LONG
		 */
		$users = $request['dba']->executeQuery("SELECT usergroups,id FROM k4_users");
		while($users->next()) {
			$user = $users->current();
			$usergroups = $user['usergroups'] != '' ? unserialize($user['usergroups']) : array();
			$usergroups = !is_array($usergroups) ? array() : $usergroups;
			$usergroups = implode('|', $usergroups);
			$request['dba']->executeUpdate("UPDATE k4_users SET usergroups = '". $usergroups ."' WHERE id=". intval($user['id']));
		}
		$users->free();
		
		/**
		 * Update the 'Descent' styleset
		 */
		$request['dba']->executeUpdate("DELETE FROM k4_css");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('*', 'font-family: verdana, geneva, lucida, arial, helvetica, sans-serif;', 1, 'This applies to every tag.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('body', 'background-color: #EEEEEE;padding: 0px;margin: 0px;font-size: 12px;', 1, 'Everything basically :D');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.alt1', 'padding: 4px;background-color: #FAFAFA;color: #000000;', 1, 'This goes for some of the lighter background colored things.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.alt2', 'padding: 4px;background-color: #F4F4F4;color: #000000;', 1, 'This goes for some of the darker background colored things.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.alt3', 'padding: 4px;background-color: #FAFAFA;color: #000000;', 1, 'This goes for some of the darkest background colored things.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.alt4', 'padding: 4px;border: 0px;background-color: #FAFBC7;color: #000000;', 1, 'This goes for notable background colored things.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.answer', 'border: 1px solid #999999;list-style-type: none;background-color: #FFFFFF;padding: 5px;top:-2px;position: relative;width: 90%;text-align: left;', 1, 'What an answer looks like in the FAQ section.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.button', 'background: url(Images/{\$IMG_DIR}/background_button.gif) left top repeat-x;font-size: 11px;font-family: verdana, geneva, lucida, arial, helvetica, sans-serif;border-right: 1px solid #B3B3B3;border-left: 1px solid #B3B3B3;border-top: 1px solid #F6F6F7;border-bottom: 1px solid #919194;', 1, 'This applies to all form buttons with this class.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.forum_base', 'width: 98%;', 1, 'This is what surounds the entire forum.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.k4_borderwrap', 'background: url(Images/{\$IMG_DIR}/background_left_curve.gif) left top no-repeat;', 1, 'This goes around primary tables within forum_base.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.k4_table', 'background-color: #FAFAFA;', 1, 'This applies to all primary tables within forum_base.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.forum_footer', 'padding-left: 10px;padding-right: 10px;padding-bottom: 10px;background-color: #FFFFFF;', 1, 'The footer stuff in the forum, not including contact info, etc.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.forum_header', 'padding-left: 10px;padding-right: 10px;background-color: #FFFFFF;', 1, 'The header, not including logo image');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.forum_main', 'padding: 20px 20px 20px 20px;background-color: #FFFFFF;border: 1px solid #C2C2C2;', 1, 'The main body part of the forum.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.inputbox', ' border : 1px solid #999999;font-size:11px;', 1, 'This applies to all of the inputfields.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.inputbox:focus', 'border : 1px solid #666666;font-size:11px;', 1, 'This only works in Mozilla browsers. It makes all input fields wit hthis class, when clicked, have a highlighted border.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.inputfailed', 'border: 1px solid #FF0000;font-size:11px;background-color:#FFEEFF;', 1, 'This is for failed input fields. It is only toggled by JavaScript.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.inputnone', 'background-color: #E4E7F5;font-size:11px;text-decoration: underline;border: 0px;color: #003366;', 1, 'This is for listing the attachments.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.logo_header td', 'background-color: #E1E1E2;margin:0px 0px 0px 0px;padding: 0px 0px 0px 0px;', 1, 'This applies to the area which contains the k4 (or yours if set) logo.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.minitext, .minitext *, *.minitext', 'color: #666666; font-size:10px; padding:0px;font-style: italic;', 1, 'This is the smallest text, and this applies to all elements within the smalltext.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.outset_box', 'background-color: #f7f7f7;padding: 10px;', 1, 'This goes for the white box with outset borders.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.outset_box_small', 'padding: 5px;background-color: #F7F5F1;border-top: 1px solid #B2B2B2;border-left: 1px solid #B2B2B2;border-bottom: 1px solid #000000;border-right: 1px solid #000000;', 1, 'This goes for anything with 1px outset borders.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.inset_box_small', 'padding: 5px;background-color: #f7f7f7;border-bottom: 1px solid #B2B2B2;border-right: 1px solid #B2B2B2;border-top: 1px solid #000000;border-left: 1px solid #000000;', 1, 'This goes for anything with 1px inset borders.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.inset_box', 'border: 1px inset;padding: 10px;background-color: #f7f7f7;border-bottom: 2px solid #B2B2B2;border-right: 2px solid #B2B2B2;border-top: 2px solid #000000;border-left: 2px solid #000000;', 1, 'This goes for the white box with inset borders.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.pagination td', 'border: 1px solid #CCCCCC;', 1, 'Table columns within the pagination boxes.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.panel', 'border: 0px;background-color: #E4E7F5;color: #000000;', 1, 'This is the main light background colored region.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.panelsurround', 'background-color: #D5D8E5;color: #000000;', 1, 'Some sort of surrounding panel.. not used often.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.question', 'list-style-type: none;margin: 0px 0px 1px 0px;background-color: #fcfcfc;padding: 5px;border: 1px solid #999999;color: #000000;font-size: 11px;width: 90%;', 1, 'What a question looks like in the FAQ section.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.quote', 'border: 1px solid #999999;', 1, 'This is for the bbcode quote elements.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.quote *', 'font-size: 11px;color: #666666;', 1, 'This applies to all quote elemenets, and all elements inside quotes.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.quote legend', 'font-weight: bold;color: #333333;', 1, 'This applies to the quotes legend.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.smalltext, .smalltext *, *.smalltext', 'font-size:11px; padding:0px;', 1, 'This is the second smallest text, and this applies to all elements within the minitext.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.special_panel', 'background-color: #B7CEE1;color: #000000;padding: 10px;border: outset 2px;', 1, 'Shows on suspended forums/categories.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.k4_subtitle a:hover', 'color: #4F5821;font-family: arial, helvetica, sans-serif;font-size: 12px;font-weight: bold;text-decoration: underline;', 1, 'This applies to all links in the secondary header regions when you hover your mouse over them.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.k4_subtitle a:link, .k4_subtitle a:visited, .k4_subtitle a:active', 'color: #4F5821;font-family: arial, helvetica, sans-serif;font-size: 12px;font-weight: bold;text-decoration: none;', 1, 'This applies to all links within the secondary header regions, on the default template.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.k4_subtitle', 'color: #4F5821;background-color: #C3D950;font-family: arial, helvetica, sans-serif;font-size: 12px;font-weight: bold;padding: 5px;cursor: default;', 1, 'This is the secondary header region.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.k4_maintitle', 'color: #FFFFFF;padding: 5px 10px 5px 5px;background: url(Images/{\$IMG_DIR}/background_right_curve.gif) right top no-repeat;font-weight: bold;font-family: tahoma, verdana, geneva, lucida, arial, helvetica, sans-serif;', 1, 'This is the primary header region.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.k4_maintitle a', 'font-size: 12px;color: #FFFFFF;', 1, 'This applies to all links within the primary header regions.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.k4_maintitle a:hover', 'font-size: 12px;color: #FFFFFF;', 1, 'This applies to all links when hovered within the primary header regions.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.k4_modmaintitle', 'background-color: #F0F0F0;color: #333333;font-weight: bold;font-family: tahoma, verdana, geneva, lucida, arial, helvetica, sans-serif;', 1, 'This is the primary mod header region.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.k4_modmaintitle a, .k4_maintitle div', 'font-size: 12px;color: #333333;', 1, 'This applies to all links within the primary mod header regions.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.k4_shadow', 'background-color: #34436C;', 1, 'This is the shadow that you can see at the bottom of central boxes.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.topiced_off td', 'background-color: #FEFEFE;padding: 0px;margin:0px;color: #000000;', 1, 'This goes for the table rows in the topiced and hybrid topic views when they are not selected.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.topiced_on, .topiced_on td', 'background-color: #CCCCCC;padding: 0px;margin:0px;height: 20px;', 1, 'This goes for the table rows in the topiced and hybrid topic views when you select one of them.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.visible', 'display: block;border: 1px solid #BDD786;padding: 3px;width: 95%;', 1, 'A category in the Advanced CSS editor which is visible.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('a', 'color: #000000;text-decoration: none;', 1, 'This applies to every link.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('a:hover', 'text-decoration: underline; color: #C83636;', 1, 'This applies to every link when you hover your mouse over them.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('a.bbcode_url', 'border-bottom: 1px dashed #CCCCCC;', 1, 'This applies to every bbcode link.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('fieldset', 'border: 1px solid #003366;padding: 5px;margin: 0px;', 1, 'This applies to every fieldset. (those cool table like things with a name which intersects the top border.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('form', 'margin:0px 0px 0px 0px;padding: 0px 0px 0px 0px;', 1, 'This applies to every form.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('h1', 'margin: 0px;padding: 0px;font-size: 20px;', 1, 'This applies to every h1 element.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('legend', 'color: #22229C;font: 11px tahoma, verdana, geneva, lucida, arial, helvetica, sans-serif;', 1, 'This applies to the names within the top border of all fieldsets.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('td', 'padding: 3px;', 1, 'This applies to every table column.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('td, th, p, li', 'font-size: 10pt;font-family: verdana, geneva, lucida, arial, helvetica, sans-serif;', 1, 'This applies to all table headers, table columns, paragraphs and list items simultaneously.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.bbcode_button', 'border: 2px outset; background-color: #F3F3F3;', 1, 'All of the buttons on the BB Code editor.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.date_color', 'color: #999999;', 1, 'The different colored text in dates');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.quotetitle', 'text-align:left;width: 90%;font-weight: bold;font-size: 11px;color: #000000;background-color: #A9B8C2; border: 1px solid #A9B8C2;padding: 3px;', 1, 'The title part of a Quote field.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.quotecontent', 'text-align:left;width: 90%;font-size: 11px;color: #000000;background-color: #FFFFFF; border: 1px solid #A9B8C2;padding: 3px;', 1, 'The content part of a Quote field.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.codetitle', 'text-align:left;width: 90%;font-weight: bold;font-size: 11px;color: #000000;background-color: #A9B8C2; border: 1px solid #A9B8C2;padding: 3px;', 1, 'The title part of a Code field.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.codecontent', 'font-family: Courier, sans-serif;text-align:left;width: 90%;font-size: 11px;color: #000000;background-color: #FFFFFF; border: 1px solid #A9B8C2;padding: 3px;', 1, 'The content part of a Code field.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.phptitle', 'text-align:left;width: 90%;font-weight: bold;font-size: 11px;color: #000000;background-color: #A9B8C2; border: 1px solid #A9B8C2;padding: 3px;', 1, 'The title part of a PHP field.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.phpcontent', 'font-family: Courier, sans-serif;text-align:left;width: 90%;font-size: 11px;color: #000000;background-color: #FFFFFF; border: 1px solid #A9B8C2;padding: 3px;', 1, 'The content part of a PHP field.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.phpcontent *', 'font-family: Courier, sans-serif;', 1, 'Everything inside a PHP field.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.forumrules', 'text-align:left;padding: 5px;font-size: 11px;background-color: #E4E7F5; border: 1px solid #045975;', 1, 'Forum rules box.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.forummessage', 'padding: 5px;font-size: 11px;background-color: #E4E7F5; border: 1px solid #045975;', 1, 'Forum special message box.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.header_text', 'padding: 5px;border-bottom: 1px dashed #CCCCCC;margin-bottom: 10px;width: 95%;', 1, 'Used in the member profile area.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('acronym', 'cursor:default;color: #336699;border-bottom: 1px dashed #336699;', 1, 'Acronyms.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('div.menu_global', 'padding: 5px; text-align: center;', 1, 'The box around main navigation menus');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('ul.menu_global', 'color: #333333;margin-left: 0;padding-left: 0;display: inline;', 1, 'The unordered list in main navigation menus');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.menu_global li', 'margin: 0;padding: 0px 5px 0px 7px;list-style: none;display: inline;', 1, 'Main Navigation Menu list items');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.menu_global li.first', 'margin: 0;border-left: none;list-style: none;display: inline;', 1, 'The first list item in the menu navigation.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.menu_global a, .menu_global a:hover, .header_breadcrumbs a, .header_breadrumbs a:hover', 'font-size: 11px;color: #000000; text-decoration: none;', 1, 'A hovered link in the menu navigation.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.Checkbox, .CheckboxChecked', 'display: block;width: 100%;height: 20px;', 1, 'Checkbox style.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.Checkbox', 'background:url(Images/{\$IMG_DIR}/Icons/topic_unselected.gif) center center no-repeat;', 1, 'Checkbox unchecked style.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.CheckboxChecked', 'background:url(Images/{\$IMG_DIR}/Icons/topic_selected.gif) center center no-repeat;', 1, 'Checkbox checked style.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.InputHidden', 'display: none;', 1, 'Hidden form style inputs');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.editor_button', 'margin: 1px 1px 1px 1px;border: 0px;', 1, 'Style for bbcode/wysiwyg editor buttons.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.editor_button_over', 'margin: 0px;border: 1px solid #0000FF;background-color: #A8C7DE;', 1, 'Style for bbcode/wysiwyg editor buttons when hovered.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.header_breadcrumbs', 'border: 1px solid #CCCCCC;padding: 5px; background-color: #FAFAFA;', 1, 'The breadcrumb bit box at the top and the menu bit at the bottom');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.header_breadcrumbs a:hover', 'background-color: #F4F4F4; text-decoration: none;', 1, 'Hovered links in the breadcrumb bit box at the top and the menu bit at the bottom');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.title_link', 'padding-left: 5px;color: #363636; font-size: 20px; font-weight: normal;', 1, 'The class for big text that shows category/forum/topic names.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('a.title_link:hover', 'text-decoration: none;', 1, 'The class for big text that shows category/forum/topic names when it is a link and when hovered.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.k4_ddmenutable', '', 1, 'A table that holds drop down menus inside of a k4_maintitle element');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('div.ddmenu_button', 'padding: 3px;font-weight:bold;width: auto;', 1, 'Drop-Down menu button openers.');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.k4_ddmenutable td', 'padding: 2px;', 1, 'Table cells in drop down menu link openers inside of a k4_maintitle element');");
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('div.k4_maintitle, .k4_maintitle div, .k4_maintitle span, .k4_maintitle strong', 'color: #FFFFFF;', 1, 'Applies to divs, strongs and spans in the main title elements.');");
		
		/**
		 * Recreate the cache
		 */
		$cache = array();
		$cache_class = new K4GeneralCacheFilter;
		$methods = get_class_methods($cache_class);
		foreach($methods as $function) {
			if(substr($function, 0, 6) == 'cache_') {
				$cache_class->$function($cache, $request);
			}
		}
		DBCache::createCache($cache);
		

		/**
		 * DONE!!
		 */
		echo 'Successfully updated your k4 Bulletin Board version from BETA 7 to BETA 7.1. <strong>Please remove this file immediately</strong>. If you wish to change your version number, go into /includes/k4bb/common.php and switch it on line 37.';
		exit;
	}
}

$app = &new K4BasicController('information_base.html');
$app->setAction('', new K4DefaultAction);
$app->setDefaultEvent('');

$app->execute();

?>