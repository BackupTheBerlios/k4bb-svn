<?php
/**
* k4 Bulletin Board, admin.php
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
* @version $Id: admin.php 149 2005-07-12 14:17:49Z Peter Goodman $
* @package k42
*/

error_reporting(E_ALL);

require "includes/filearts/filearts.php";
require "includes/k4bb/k4bb.php";

@set_time_limit(120);

class K4DefaultAction extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_WELCOME');
			
		} else {
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
			$request['template']->setFile('content', '../login_form.html');
			$request['template']->setVisibility('no_perms', TRUE);
			return TRUE;
		}

		return TRUE;
	}
}

class AdminMenu extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			$request['template']->setFile('content', 'admin_menu.html');
		} else {
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
			$request['template']->setFile('content', '../login_form.html');
			$request['template']->setVisibility('no_perms', TRUE);
			return TRUE;
		}

		return TRUE;
	}
}

class AdminHead extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			$request['template']->setFile('content', 'admin_head.html');
			$request['template']->setVisibility('content_padding', FALSE);
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

/* Admin File Browser */
$app->setAction('file_browser', new AdminFileBrowser);

/* Options */
$app->setAction('options', new AdminOptionGroups);
$app->setAction('options_view', new AdminSettings);
$app->setAction('update_options', new AdminUpdateOptions);

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
$app->setAction('emoticons_clickable', new AdminUpdateEmoticonClick);

/* Categories */
$app->setAction('categories', new AdminCategories);
$app->setAction('categories_add', new AdminAddCategory);
$app->setAction('categories_insert', new AdminInsertCategory);
$app->setAction('categories_insertmaps', new AdminInsertCategoryMaps);
$app->setAction('categories_simpleupdate', new AdminSimpleCategoryUpdate);
$app->setAction('categories_edit', new AdminEditCategory);
$app->setAction('categories_update', new AdminUpdateCategory);
$app->setAction('categories_remove', new AdminRemoveCategory);
$app->setAction('categories_permissions', new AdminCategoryPermissions);
$app->setAction('categories_updateperms', new AdminUpdateCategoryPermissions);

/* Forums */
$app->setAction('forums', new AdminForums);
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

$app->execute();

?>