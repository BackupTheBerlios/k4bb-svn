<?php
/**
* k4 Bulletin Board, profilefields.class.php
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
* @version $Id: profilefields.class.php 144 2005-07-05 02:29:07Z Peter Goodman $
* @package k42
*/



if(!defined('IN_K4')) {
	return;
}

class AdminUserProfileFields extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			$fields			= $request['dba']->executeQuery("SELECT * FROM ". K4PROFILEFIELDS ." ORDER BY display_order ASC");

			$request['template']->setList('fields', $fields);
			
			$request['template']->setFile('content', 'profilefields_manage.html');

			k4_bread_crumbs($request['template'], $request['dba'], 'L_USERPROFILEFIELDS');
			$request['template']->setVar('users_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/users.html');
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminAddUserField extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			$request['template']->setFile('content', 'profilefields_add1.html');

			k4_bread_crumbs($request['template'], $request['dba'], 'L_USERPROFILEFIELDS');
			$request['template']->setVar('users_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/users.html');
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminAddUserFieldTwo extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			$types = array('text', 'textarea', 'select', 'multiselect', 'radio', 'checkbox');
			
			if(!isset($_REQUEST['inputtype']) || $_REQUEST['inputtype'] == '' || !in_array($_REQUEST['inputtype'], $types)) {
				$action = new K4InformationAction(new K4LanguageElement('L_NEEDFIELDINPUTTYPE'), 'content', TRUE);
				return $action->execute($request);
			}

			$request['template']->setVisibility($_REQUEST['inputtype'], TRUE);
			$request['template']->setVar('inputtype', $_REQUEST['inputtype']);
			$request['template']->setFile('content', 'profilefields_add2.html');

			k4_bread_crumbs($request['template'], $request['dba'], 'L_USERPROFILEFIELDS');
			$request['template']->setVar('users_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/users.html');
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminInsertUserField extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			$types			= array('text', 'textarea', 'select', 'multiselect', 'radio', 'checkbox');
			
			if(!isset($_REQUEST['inputtype']) || $_REQUEST['inputtype'] == '' || !in_array($_REQUEST['inputtype'], $types)) {
				$action = new K4InformationAction(new K4LanguageElement('L_NEEDFIELDINPUTTYPE'), 'content', TRUE);
				return $action->execute($request);
			}

			$last_field		= $request['dba']->getValue("SELECT name FROM ". K4PROFILEFIELDS ." ORDER BY name DESC LIMIT 1");
			
			if(!$last_field || $last_field == '') {
				$name		= 'field1';
			} else {
				$name		= 'field'. (intval(substr($last_field, -1)) + 1);
			}
						
			$insert			= $request['dba']->prepareStatement("INSERT INTO ". K4PROFILEFIELDS ." (name,title,description,default_value,inputtype,user_maxlength,inputoptions,min_perm,display_register,display_profile,display_topic,display_post,display_memberlist,display_image,display_size,display_rows,display_order,is_editable,is_private,is_required,special_pcre) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

			$insert->setString(1, $name);
			$insert->setString(2, @$_REQUEST['title']);
			$insert->setString(3, @$_REQUEST['description']);
			$insert->setString(4, @$_REQUEST['default_value']);
			$insert->setString(5, @$_REQUEST['inputtype']);
			$insert->setInt(6, (intval(@$_REQUEST['user_maxlength']) > 0 ? intval(@$_REQUEST['user_maxlength']) : 255));
			$insert->setString(7, (isset($_REQUEST['inputoptions']) && @$_REQUEST['inputoptions'] != '' ? serialize(explode('\n', preg_replace("~(\r|\n|\r\n)~is", "\n", @$_REQUEST['inputoptions']))) : ''));
			$insert->setInt(8, @$_REQUEST['min_perm']);
			$insert->setInt(9, (isset($_REQUEST['display_register']) && @$_REQUEST['display_register'] == 'yes' ? 1 : 0));
			$insert->setInt(10, (isset($_REQUEST['display_profile']) && @$_REQUEST['display_profile'] == 'yes' ? 1 : 0));
			$insert->setInt(11, (isset($_REQUEST['display_topic']) && @$_REQUEST['display_topic'] == 'yes' ? 1 : 0));
			$insert->setInt(12, (isset($_REQUEST['display_post']) && @$_REQUEST['display_post'] == 'yes' ? 1 : 0));
			$insert->setInt(13, (isset($_REQUEST['display_memberlist']) && @$_REQUEST['display_memberlist'] == 'yes' ? 1 : 0));
			$insert->setString(14, @$_REQUEST['display_image']);
			$insert->setInt(15, @$_REQUEST['display_size']);
			$insert->setInt(16, @$_REQUEST['display_rows']);
			$insert->setInt(17, @$_REQUEST['display_order']);
			$insert->setInt(18, @$_REQUEST['is_editable']);
			$insert->setInt(19, @$_REQUEST['is_private']);
			$insert->setInt(20, @$_REQUEST['is_required']);
			$insert->setString(21, @$_REQUEST['special_pcre']);
			
			push_error_handler(create_function('', 'return TRUE;'));
			$ret = $request['dba']->executeQuery("SELECT ". $name ." FROM ". K4USERINFO ." LIMIT 1");
			pop_error_handler();
			
			if($ret === FALSE) {
				$update_type	= "ADD";
			} else {
				$update_type	= "CHANGE ". $name;
			}
			
			if($_REQUEST['inputtype'] != 'textarea') {
				$params			= "VARCHAR(". iif(intval(@$_REQUEST['user_maxlength']) > 0, intval(@$_REQUEST['user_maxlength']), 255) .") NOT NULL DEFAULT '". htmlentities(@$_REQUEST['default_value'], ENT_QUOTES) ."'";
			} else if($_REQUEST['inputtype'] == 'textarea') {
				$params			= "TEXT";
			}
			
			/* If there is a problem altering the userinfo table, don't continue past this point. */
			$request['dba']->alterTable(K4USERINFO, "$update_type $name $params");
			$insert->executeUpdate();
			
			reset_cache('profile_fields');
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_USERPROFILEFIELDS');
			$request['template']->setVar('users_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/users.html');

			$action = new K4InformationAction(new K4LanguageElement('L_ADDEDPROFILEFIELD', $_REQUEST['title']), 'content', FALSE, 'admin.php?act=userfields', 3);
			return $action->execute($request);
			
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminRemoveUserField extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			if(!isset($_REQUEST['field']) || $_REQUEST['field'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDUSERFIELD'), 'content', TRUE);
				return $action->execute($request);
			}
			
			$field		= $request['dba']->getRow("SELECT * FROM ". K4PROFILEFIELDS ." WHERE name = '". $request['dba']->quote($_REQUEST['field']) ."'");
			
			if(!$field || !is_array($field) || empty($field)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDUSERFIELD'), 'content', TRUE);
				return $action->execute($request);
			}

			if(!$request['dba']->executeQuery("SELECT ". $field['name'] ." FROM ". K4USERINFO ." LIMIT 1")) {

				/* Delete the profile field version of this because obviously it shouldn't exist */
				$request['dba']->executeUpdate("DELETE FROM ". K4PROFILEFIELDS ." WHERE name = '". $request['dba']->quote($field['name']) ."'");
				
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDUSERFIELD'), 'content', TRUE);
				return $action->execute($request);
			}
			
			/* Remove the field */
			$request['dba']->alterTable(K4USERINFO, "DROP ". $request['dba']->quote($field['name']));
						
			/* Remove the last of the profile field info if we've made it this far */
			$request['dba']->executeUpdate("DELETE FROM ". K4PROFILEFIELDS ." WHERE name = '". $request['dba']->quote($field['name']) ."'");
			
			reset_cache('profile_fields');
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_USERPROFILEFIELDS');
			$request['template']->setVar('users_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/users.html');

			$action = new K4InformationAction(new K4LanguageElement('L_REMOVEDPROFILEFIELD', $field['title']), 'content', FALSE, 'admin.php?act=userfields', 3);
			return $action->execute($request);

		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminEditUserField extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			if(!isset($_REQUEST['field']) || $_REQUEST['field'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDUSERFIELD'), 'content', TRUE);
				return $action->execute($request);
			}
			
			$field		= $request['dba']->getRow("SELECT * FROM ". K4PROFILEFIELDS ." WHERE name = '". $request['dba']->quote($_REQUEST['field']) ."'");
			
			if(!$field || !is_array($field) || empty($field)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDUSERFIELD'), 'content', TRUE);
				return $action->execute($request);
			}

			push_error_handler(create_function('', 'return TRUE;'));
			$ret = $request['dba']->executeQuery("SELECT ". $field['name'] ." FROM ". K4USERINFO ." LIMIT 1");
			pop_error_handler();

			if($ret === FALSE) {

				/* Delete the profile field version of this because obviously it shouldn't exist */
				$request['dba']->executeUpdate("DELETE FROM ". K4PROFILEFIELDS ." WHERE name = '". $request['dba']->quote($field['name']) ."'");
				
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDUSERFIELD'), 'content', TRUE);
				return $action->execute($request);
			}
						
			foreach($field as $key => $val) {
				
				/* If these are options, format them */
				if($key == 'inputoptions') {
					$val = $val != '' ? iif(!unserialize($val), array(), unserialize($val)) : array();
					if(is_array($val) && !empty($val)) {
						$val = str_replace("\n\n", "\n", implode("\n", $val));
					} else {
						$val = "";
					}
				}
				 
				$request['template']->setVar('field_'. $key, $val);
			}

			$request['template']->setVisibility($field['inputtype'], TRUE);
			$request['template']->setFile('content', 'profilefields_edit.html');

			k4_bread_crumbs($request['template'], $request['dba'], 'L_USERPROFILEFIELDS');
			$request['template']->setVar('users_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/users.html');
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminUpdateUserField extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			if(!isset($_REQUEST['field']) || $_REQUEST['field'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDUSERFIELD'), 'content', TRUE);
				return $action->execute($request);
			}
			
			$field		= $request['dba']->getRow("SELECT * FROM ". K4PROFILEFIELDS ." WHERE name = '". $request['dba']->quote($_REQUEST['field']) ."'");
			
			if(!$field || !is_array($field) || empty($field)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDUSERFIELD'), 'content', TRUE);
				return $action->execute($request);
			}
			
			push_error_handler(create_function('', 'return TRUE;'));
			$ret = $request['dba']->executeQuery("SELECT ". $field['name'] ." FROM ". K4USERINFO ." LIMIT 1");
			pop_error_handler();

			if($ret === FALSE) {

				/* Delete the profile field version of this because obviously it shouldn't exist */
				$request['dba']->executeUpdate("DELETE FROM ". K4PROFILEFIELDS ." WHERE name = '". $request['dba']->quote($field['name']) ."'");
				
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDUSERFIELD'), 'content', TRUE);
				return $action->execute($request);
			}

			$update			= $request['dba']->prepareStatement("UPDATE ". K4PROFILEFIELDS ." SET title=?, description=?, default_value=?, inputtype=?, user_maxlength=?, inputoptions=?, min_perm=?, display_register=?, display_profile=?, display_topic=?, display_post=?, display_memberlist=?, display_image=?, display_size=?, display_rows=?, display_order=?, is_editable=?, is_private=?, is_required=?, special_pcre=? WHERE name=?");

			$update->setString(1, @$_REQUEST['title']);
			$update->setString(2, @$_REQUEST['description']);
			$update->setString(3, @$_REQUEST['default_value']);
			$update->setString(4, @$_REQUEST['inputtype']);
			$update->setInt(5, (intval(@$_REQUEST['user_maxlength']) > 0 ? intval(@$_REQUEST['user_maxlength']) : 255));
			$update->setString(6, (isset($_REQUEST['inputoptions']) && @$_REQUEST['inputoptions'] != '' ? serialize(explode('\n', preg_replace("~(\r|\n|\r\n)~is", "\n", @$_REQUEST['inputoptions']))) : ''));
			$update->setInt(7, @$_REQUEST['min_perm']);
			$update->setInt(8, (isset($_REQUEST['display_register']) && @$_REQUEST['display_register'] == 'yes' ? 1 : 0));
			$update->setInt(9, (isset($_REQUEST['display_profile']) && @$_REQUEST['display_profile'] == 'yes' ? 1 : 0));
			$update->setInt(10, (isset($_REQUEST['display_topic']) && @$_REQUEST['display_topic'] == 'yes' ? 1 : 0));
			$update->setInt(11, (isset($_REQUEST['display_post']) && @$_REQUEST['display_post'] == 'yes' ? 1 : 0));
			$update->setInt(12, (isset($_REQUEST['display_memberlist']) && @$_REQUEST['display_memberlist'] == 'yes' ? 1 : 0));
			$update->setString(13, @$_REQUEST['display_image']);
			$update->setInt(14, @$_REQUEST['display_size']);
			$update->setInt(15, @$_REQUEST['display_rows']);
			$update->setInt(16, @$_REQUEST['display_order']);
			$update->setInt(17, @$_REQUEST['is_editable']);
			$update->setInt(18, @$_REQUEST['is_private']);
			$update->setInt(19, @$_REQUEST['is_required']);
			$update->setString(20, @$_REQUEST['special_pcre']);
			$update->setString(21, $field['name']);

			$update->executeUpdate();
			
			reset_cache('profile_fields');
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_USERPROFILEFIELDS');
			$request['template']->setVar('users_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/users.html');
			
			$action = new K4InformationAction(new K4LanguageElement('L_UPDATEDPROFILEFIELD', $_REQUEST['title']), 'content', FALSE, 'admin.php?act=userfields', 3);
			return $action->execute($request);
			
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminSimpleUpdateUserFields extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			$fields = $request['dba']->executeQuery("SELECT * FROM ". K4PROFILEFIELDS ." ORDER BY name ASC");
			
			while($fields->next()) {

				$field = $fields->current();

				if(isset($_REQUEST['display_order_'. $field['name']]) && intval($_REQUEST['display_order_'. $field['name']]) >= 0) {
					$update = $request['dba']->prepareStatement("UPDATE ". K4PROFILEFIELDS ." SET display_order=? WHERE name=?");
					$update->setInt(1, $_REQUEST['display_order_'. $field['name']]);
					$update->setString(2, $field['name']);
					$update->executeUpdate();
					unset($update);
				}
			}
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_USERPROFILEFIELDS');
			$request['template']->setVar('users_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/users.html');

			$action = new K4InformationAction(new K4LanguageElement('L_UPDATEDPROFILEFIELDS'), 'content', FALSE, 'admin.php?act=userfields', 3);
			return $action->execute($request);

		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

?>