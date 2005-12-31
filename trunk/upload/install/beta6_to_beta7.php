<?php
/**
* k4 Bulletin Board, beta6_to_beta7.php
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
* @version $Id$
* @package k42
*/

require "../includes/filearts/filearts.php";
require "../includes/k4bb/k4bb.php";

//INSERT INTO k4_maps ( row_level, name, varname, value, is_global, category_id, forum_id, user_id, group_id, parent_id, can_view, can_add, can_edit, can_del, inherit, num_children, can_view_condition, can_edit_condition, can_add_condition, can_del_condition) VALUES ( 1, 'Private Messaging', 'private_messaging', '', 0, 0, 0, 0, 0, 0, 5, 0, 0, 0, 0, 15, 1, 1, 1, 1);
//INSERT INTO k4_maps ( row_level, name, varname, value, is_global, category_id, forum_id, user_id, group_id, parent_id, can_view, can_add, can_edit, can_del, inherit, num_children, can_view_condition, can_edit_condition, can_add_condition, can_del_condition) VALUES ( 2, 'User Avatars', 'pm_avatars', '', 0, 0, 0, 0, 0, ". $parent_id .", 5, 0, 0, 0, 0, 0, 1, 1, 1, 1);
//INSERT INTO k4_maps ( row_level, name, varname, value, is_global, category_id, forum_id, user_id, group_id, parent_id, can_view, can_add, can_edit, can_del, inherit, num_children, can_view_condition, can_edit_condition, can_add_condition, can_del_condition) VALUES ( 2, 'User Signatures', 'pm_signatures', '', 0, 0, 0, 0, 0, ". $parent_id .", 5, 0, 0, 0, 0, 0, 1, 1, 1, 1);
//INSERT INTO k4_maps ( row_level, name, varname, value, is_global, category_id, forum_id, user_id, group_id, parent_id, can_view, can_add, can_edit, can_del, inherit, num_children, can_view_condition, can_edit_condition, can_add_condition, can_del_condition) VALUES ( 2, 'HTML Code', 'pm_html', '', 0, 0, 0, 0, 0, ". $parent_id .", 9, 0, 0, 0, 0, 0, 1, 1, 1, 1);
//INSERT INTO k4_maps ( row_level, name, varname, value, is_global, category_id, forum_id, user_id, group_id, parent_id, can_view, can_add, can_edit, can_del, inherit, num_children, can_view_condition, can_edit_condition, can_add_condition, can_del_condition) VALUES ( 2, 'BB Code', 'pm_bbcode', '', 0, 0, 0, 0, 0, ". $parent_id .", 5, 0, 0, 0, 0, 0, 1, 1, 1, 1);
//INSERT INTO k4_maps ( row_level, name, varname, value, is_global, category_id, forum_id, user_id, group_id, parent_id, can_view, can_add, can_edit, can_del, inherit, num_children, can_view_condition, can_edit_condition, can_add_condition, can_del_condition) VALUES ( 2, 'BB IMG Code', 'pm_bbimgcode', '', 0, 0, 0, 0, 0, ". $parent_id .", 5, 0, 0, 0, 0, 0, 1, 1, 1, 1);
//INSERT INTO k4_maps ( row_level, name, varname, value, is_global, category_id, forum_id, user_id, group_id, parent_id, can_view, can_add, can_edit, can_del, inherit, num_children, can_view_condition, can_edit_condition, can_add_condition, can_del_condition) VALUES ( 2, 'BB Flash Code', 'pm_bbflashcode', '', 0, 0, 0, 0, 0, ". $parent_id .", 5, 0, 0, 0, 0, 0, 1, 1, 1, 1);
//INSERT INTO k4_maps ( row_level, name, varname, value, is_global, category_id, forum_id, user_id, group_id, parent_id, can_view, can_add, can_edit, can_del, inherit, num_children, can_view_condition, can_edit_condition, can_add_condition, can_del_condition) VALUES ( 2, 'Emoticons', 'pm_emoticons', '', 0, 0, 0, 0, 0, ". $parent_id .", 5, 0, 0, 0, 0, 0, 1, 1, 1, 1);
//INSERT INTO k4_maps ( row_level, name, varname, value, is_global, category_id, forum_id, user_id, group_id, parent_id, can_view, can_add, can_edit, can_del, inherit, num_children, can_view_condition, can_edit_condition, can_add_condition, can_del_condition) VALUES ( 2, 'Message Icons', 'pm_posticons', '', 0, 0, 0, 0, 0, ". $parent_id .", 5, 0, 0, 0, 0, 0, 1, 1, 1, 1);
//INSERT INTO k4_maps ( row_level, name, varname, value, is_global, category_id, forum_id, user_id, group_id, parent_id, can_view, can_add, can_edit, can_del, inherit, num_children, can_view_condition, can_edit_condition, can_add_condition, can_del_condition) VALUES ( 2, 'Message Saving', 'pm_message_save', '', 0, 0, 0, 0, 0, ". $parent_id .", 5, 0, 0, 0, 0, 0, 1, 1, 1, 1);
//INSERT INTO k4_maps ( row_level, name, varname, value, is_global, category_id, forum_id, user_id, group_id, parent_id, can_view, can_add, can_edit, can_del, inherit, num_children, can_view_condition, can_edit_condition, can_add_condition, can_del_condition) VALUES ( 2, 'Message Previewing', 'pm_preview', '', 0, 0, 0, 0, 0, ". $parent_id .", 5, 0, 0, 0, 0, 0, 1, 1, 1, 1);
//INSERT INTO k4_maps ( row_level, name, varname, value, is_global, category_id, forum_id, user_id, group_id, parent_id, can_view, can_add, can_edit, can_del, inherit, num_children, can_view_condition, can_edit_condition, can_add_condition, can_del_condition) VALUES ( 2, 'Message Forwarding', 'pm_forwarding', '', 0, 0, 0, 0, 0, ". $parent_id .", 5, 0, 0, 0, 0, 0, 1, 1, 1, 1);
//INSERT INTO k4_maps ( row_level, name, varname, value, is_global, category_id, forum_id, user_id, group_id, parent_id, can_view, can_add, can_edit, can_del, inherit, num_children, can_view_condition, can_edit_condition, can_add_condition, can_del_condition) VALUES ( 2, 'Message Tracking', 'pm_tracking', '', 0, 0, 0, 0, 0, ". $parent_id .", 5, 0, 0, 0, 0, 0, 1, 1, 1, 1);
//INSERT INTO k4_maps ( row_level, name, varname, value, is_global, category_id, forum_id, user_id, group_id, parent_id, can_view, can_add, can_edit, can_del, inherit, num_children, can_view_condition, can_edit_condition, can_add_condition, can_del_condition) VALUES ( 2, 'Custom Folders', 'pm_customfolders', '', 0, 0, 0, 0, 0, ". $parent_id .", 5, 5, 5, 5, 0, 0, 1, 1, 1, 1);
//INSERT INTO k4_maps ( row_level, name, varname, value, is_global, category_id, forum_id, user_id, group_id, parent_id, can_view, can_add, can_edit, can_del, inherit, num_children, can_view_condition, can_edit_condition, can_add_condition, can_del_condition) VALUES ( 2, 'Messages', 'pm_messages', '', 0, 0, 0, 0, 0, ". $parent_id .", 5, 5, 5, 5, 0, 0, 1, 1, 1, 1);
//INSERT INTO k4_maps ( row_level, name, varname, value, is_global, category_id, forum_id, user_id, group_id, parent_id, can_view, can_add, can_edit, can_del, inherit, num_children, can_view_condition, can_edit_condition, can_add_condition, can_del_condition) VALUES ( 2, 'Attachments', 'pm_attachments', '', 0, 0, 0, 0, 0, ". $parent_id .", 5, 5, 5, 5, 0, 0, 1, 1, 1, 1);


//INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.phptitle', 'text-align:left;width: 90%;font-weight: bold;font-size: 11px;color: #000000;background-color: #A9B8C2; border: 1px solid #A9B8C2;padding: 3px;', 1, 'The title part of a PHP field.');
//INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.phpcontent', 'text-align:left;width: 90%;font-size: 11px;color: #000000;background-color: #FFFFFF; border: 1px solid #A9B8C2;padding: 3px;', 1, 'The content part of a PHP field.');

//ALTER TABLE k4_replies ADD row_order INT UNSIGNED NOT NULL DEFAULT 0;
//ALTER TABLE k4_users ADD new_pms INT UNSIGNED NOT NULL DEFAULT 0;

class K4DefaultAction extends FAAction {
	function execute(&$request) {
		
		$request['dba']->executeUpdate("INSERT INTO k4_css (name, properties, style_id, description) VALUES ('.k4_ddmenutable', 'position: relative;top: -7px;background-color: #FFFFFF;border-left: 2px solid #4A7CB1;border-right: 2px solid #4A7CB1;border-bottom: 2px solid #4A7CB1;', 1, 'A table that holds drop down menus inside of a k4_maintitle element');");
//		/**
//		 * Update the MAPs table
//		 */
//		$request['dba']->alterTable(K4MAPS, 'ADD num_children INT UNSIGNED NOT NULL DEFAULT 0');
//		$request['dba']->alterTable(K4MAPS, 'ADD can_view_condition INT UNSIGNED NOT NULL DEFAULT 1');
//		$request['dba']->alterTable(K4MAPS, 'ADD can_edit_condition INT UNSIGNED NOT NULL DEFAULT 1');
//		$request['dba']->alterTable(K4MAPS, 'ADD can_add_condition INT UNSIGNED NOT NULL DEFAULT 1');
//		$request['dba']->alterTable(K4MAPS, 'ADD can_del_condition INT UNSIGNED NOT NULL DEFAULT 1');
//		$maps = $request['dba']->executeQuery("SELECT * FROM ". K4MAPS ." ORDER BY row_left ASC");
//		while($maps->next()) {
//			$map = $maps->current();
//
//			$num_children = @(($map['row_right'] - $map['row_left'] - 1) / 2);
//			if($num_children > 0) {
//				$request['dba']->executeUpdate("UPDATE ". K4MAPS ." SET num_children = ". intval($num_children) ." WHERE id = ". intval($map['id']));
//			}
//		}
//		$request['dba']->alterTable(K4MAPS, 'DROP row_left');
//		$request['dba']->alterTable(K4MAPS, 'DROP row_right');
//		
//
//		/**
//		 * Do some other stuff
//		 */
//		$request['dba']->alterTable(K4POSTS, 'ADD row_order INT UNSIGNED NOT NULL DEFAULT 0');
//		$request['dba']->alterTable(K4USERS, 'ADD new_pms INT UNSIGNED NOT NULL DEFAULT 0');
//		$request['dba']->alterTable(K4USERINFO, "ADD googletalk VARCHAR(255) NOT NULL DEFAULT ''");
//		
//		$action = &new K4InformationAction('Successfully updated your k4 Bulletin Board version from BETA 6 to BETA 7. <strong>Please remove this file immediately</strong>. If you wish to change your version number, go into /includes/k4bb/common.php and switch it on line 38.', 'content', FALSE);
//		return $action->execute($request);
	}
}

$app = &new K4Controller('forum_base.html');
$app->setAction('', new K4DefaultAction);
$app->setDefaultEvent('');

$app->execute();

?>