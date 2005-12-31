<?php
/**
* k4 Bulletin Board, admin.php
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
* @version $Id: admin.php 149 2005-07-12 14:17:49Z Peter Goodman $
* @package k42
*/

require "includes/filearts/filearts.php";
require "includes/k4bb/k4bb.php";
require "includes/k4bb/admin_init.php";

class K4DefaultAction extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_WELCOME');
			//$request['template']->setVar('adv_view', 1);
			$request['template_file'] = 'templates/'. $request['user']->get('templateset') .'/admin/admin_frameset.html';

		} else {
			no_perms_error($request);
			return TRUE;
		}

		return TRUE;
	}
}

class AdminHeadSection extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_WELCOME');
			$request['template']->setVar('adv_view', 1);
			
			$request['template']->setFile('content', 'admin_head.html');
		} else {
			no_perms_error($request);
			return TRUE;
		}

		return TRUE;
	}
}

class AdminMenuSection extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_WELCOME');
			$request['template']->setVar('adv_view', 1);
			$request['template']->setFile('content', 'admin_menu.html');

		} else {
			no_perms_error($request);
			return TRUE;
		}

		return TRUE;
	}
}

$app = new K4controller('admin/admin_base.html');

$app->setAction('', new K4DefaultAction);
$app->setDefaultEvent('');

/* Frameset */
$app->setAction('admin_header', new AdminHeadSection);
$app->setAction('admin_menu', new AdminMenuSection);

/* Admin File Browser */
$app->setAction('file_browser', new AdminFileBrowser);

/* Options */
$app->setAction('options', new AdminOptionGroups);
$app->setAction('options_view', new AdminSettings);
$app->setAction('update_options', new AdminUpdateOptions);

/* Cache Control */
$app->setAction('cache', new AdminManageCache);
$app->setAction('cache_refresh', new AdminRefreshCache);

/* The GUI for the K4MAPS permission system */
$app->setAction('permissions_gui', new AdminMapsGui);
//$app->setAction('maps_inherit', new AdminMapsInherit);
$app->setAction('maps_update', new AdminMapsUpdate);
$app->setAction('maps_add', new AdminMapsAddNode);
$app->setAction('maps_insert', new AdminMapsInsertNode);
$app->setAction('maps_remove', new AdminMapsRemoveNode);

/* Post Icons */
$app->setAction('posticons', new AdminPostIcons);
$app->setAction('posticons_add', new AdminAddPostIcon);
$app->setAction('posticons_edit', new AdminEditPostIcon);
$app->setAction('posticons_insert', new AdminInsertPostIcon);
$app->setAction('posticons_remove', new AdminRemovePostIcon);
$app->setAction('posticons_update', new AdminUpdatePostIcon);

/* Emoticons */
$app->setAction('emoticons', new AdminEmoticons);
$app->setAction('emoticons_add', new AdminAddEmoticon);
$app->setAction('emoticons_edit', new AdminEditEmoticon);
$app->setAction('emoticons_insert', new AdminInsertEmoticon);
$app->setAction('emoticons_remove', new AdminRemoveEmoticon);
$app->setAction('emoticons_update', new AdminUpdateEmoticon);
$app->setAction('emoticons_clickable', new AdminUpdateEmoticonclick);

/* Forums */
$app->setAction('forums_home', new AdminForumsHome);
$app->setAction('forums', new AdminForums);
$app->setAction('forum_select', new AdminForumSelect);
$app->setAction('forums_add', new AdminAddForum);
$app->setAction('forums_insert', new AdminInsertForum);
$app->setAction('forums_insertmaps', new AdminInsertForumMaps);
$app->setAction('forums_simpleupdate', new AdminSimpleForumUpdate);
$app->setAction('forums_edit', new AdminEditForum);
$app->setAction('forums_update', new AdminUpdateForum);
$app->setAction('forums_remove', new AdminRemoveForum);
$app->setAction('forums_permissions', new AdminForumPermissions);
$app->setAction('forums_updateperms', new AdminUpdateForumPermissions);

/* Users */
$app->setAction('users', new AdminUsers);

/* User Groups */
$app->setAction('usergroups', new AdminUserGroups);
$app->setAction('usergroups_add', new AdminAddUserGroup);
$app->setAction('usergroups_insert', new AdminInsertUserGroup);
$app->setAction('usergroups_remove', new AdminRemoveUserGroup);
$app->setAction('usergroups_edit', new AdminEditUserGroup);
$app->setAction('usergroups_update', new AdminUpdateUserGroup);

/* Permission Masks */
$app->setAction('masks', new AdminPermissionMasks);
$app->setAction('mask_edit', new AdminEditPermissionMask);
$app->setAction('masks_updateperms', new AdminUpdatePermissionMasks);

/* Profile Fields */
$app->setAction('userfields', new AdminUserProfileFields);
$app->setAction('userfields_add', new AdminAddUserField);
$app->setAction('userfields_add2', new AdminAddUserFieldTwo);
$app->setAction('userfields_insert', new AdminInsertUserField);
$app->setAction('userfields_remove', new AdminRemoveUserField);
$app->setAction('userfields_edit', new AdminEditUserField);
$app->setAction('userfields_update', new AdminUpdateUserField);
$app->setAction('userfields_simpleupdate', new AdminSimpleUpdateUserFields);

/* Disallowed User Names */
$app->setAction('usernames', new AdminBadUserNames);
$app->setAction('usernames_insert', new AdminInsertBadUserName);
$app->setAction('usernames_update', new AdminUpdateBadUserName);
$app->setAction('usernames_remove', new AdminRemoveBadUserName);

/* User Titles */
$app->setAction('usertitles', new AdminUsertTitles);
$app->setAction('titles_add', new AdminAddUserTitle);
$app->setAction('titles_insert', new AdminInsertUserTitle);
$app->setAction('titles_edit', new AdminEditUserTitle);
$app->setAction('titles_update', new AdminUpdateUserTitle);
$app->setAction('titles_remove', new AdminDeleteUserTitle);
$app->setAction('titles_finduser', new AdminUserTitleFindUsers);
$app->setAction('titles_updateuser', new AdminUserTitleUpdateUser);

/* Add Users */
$app->setAction('users_add', new AdminAddUser);
$app->setAction('users_insert', new AdminInsertUser);
$app->setAction('users_find', new AdminFindUser);
$app->setAction('users_found', new AdminFetchFoundUsers);
$app->setAction('users_ban', new AdminBanUser);
$app->setAction('users_warn', new AdminWarnUser);
$app->setAction('users_flag', new AdminFlagUser);
$app->setAction('users_edit', new AdminEditUser);
$app->setAction('users_update', new AdminUpdateUser);
$app->setAction('users_disable', new AdminDisableUser);
$app->setAction('users_addtogroup', new AdminAddUserToGroup);
$app->setAction('users_insertuseringroup', new AdminInsertUserInGroup);

/* Acronym Management */
$app->setAction('acronyms', new AdminAcronyms);
$app->setAction('add_acronym', new AdminInsertAcronym);
$app->setAction('update_acronym', new AdminUpdateAcronym);
$app->setAction('acronym_remove', new AdminRemoveAcronym);

/* Word Censor Management */
$app->setAction('censors', new AdminWordCensors);
$app->setAction('add_censor', new AdminInsertCensor);
$app->setAction('update_censor', new AdminUpdateCensor);
$app->setAction('remove_censor', new AdminRemoveCensor);

/* Search Engine Spider Management */
$app->setAction('spiders', new AdminSpiders);
$app->setAction('add_spider', new AdminInsertSpider);
$app->setAction('update_spider', new AdminUpdateSpider);
$app->setAction('spider_remove', new AdminRemoveSpider);

/* Style Management */
$app->setAction('stylesets', new AdminManageStyleSets);
$app->setAction('css_addstyleset', new AdminAddStyleSet);
$app->setAction('css_insertstyleset', new AdminInsertStyleSet);
$app->setAction('css_editstyleset', new AdminEditStyleset);
$app->setAction('css_updatestyleset', new AdminUpdateStyleSet);
$app->setAction('css_removestyleset', new AdminRemoveStyleSet);
$app->setAction('css', new AdminManageCSSStyles);
$app->setAction('css_addstyle', new AdminAddCSSClass);
$app->setAction('css_insertstyle', new AdminInsertCSSClass);
$app->setAction('css_editstyle', new AdminEditCSSClass);
$app->setAction('css_updatestyle', new AdminUpdateCSSClass);
$app->setAction('css_updateallclasses', new AdminUpdateAllCSSClasses);
$app->setAction('css_removestyle', new AdminRemoveCSSClass);
$app->setAction('css_editor', new AdminCSSEditor);
$app->setAction('css_toggle', new AdminToggleInlineCSS);
$app->addFilter(new AdminCSSRequestFilter);

/* Frequently Asked Questions */
$app->setAction('faq_categories', new AdminFAQCategories);
$app->setAction('faq_addcategory', new AdminAddFAQCategory);
$app->setAction('faq_insertcategory', new AdminInsertFAQCategory);
$app->setAction('faq_csimpleupdate', new AdminFAQCategorySimpleUpdate);
$app->setAction('faq_cedit', new AdminEditFAQCategory);
$app->setAction('faq_cupdate', new AdminUpdateFAQCategory);
$app->setAction('faq_cremove', new AdminRemoveFAQCategory);
$app->setAction('faq_answers', new AdminFAQAnswers);
$app->setAction('faq_addanswer', new AdminFAQAddAnswer);
$app->setAction('faq_insertanswer', new AdminFAQInsertAnswer);
$app->setAction('faq_aedit', new AdminEditFAQAnswer);
$app->setAction('faq_aupdate', new AdminUpdateFAQAnswer);
$app->setAction('faq_aremove', new AdminRemoveFAQAnswer);

/* Email Users */
$app->setAction('email', new AdminEmailUsers);
$app->setAction('email_users', new AdminSetSendEmails);

/* Posts */
$app->setAction('posts', new AdminPosts);

/* Announcements */
$app->setAction('announcements', new AdminManageAnnouncements);

$app->execute();

?>