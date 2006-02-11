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
* @version $Id$
* @package k42
*/

if(!defined('IN_K4')) {
	return;
}

class AdminFilters extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			global $_FILTERS;
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_FILTERS');
			$request['template']->setVar('forums_on', '_on');
			
			$request['template']->setFile('sidebar_menu', 'menus/forums.html');
			$request['template']->setList('filters', new FAArrayIterator($_FILTERS));
			$request['template']->setFile('content', 'filters_manage.html');
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminAddFilter extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_ADDFILTER');
			
			$can_subselect = 1;
			if($_CONFIG['dba']['driver'] == 'mysql') {
				if($request['dba']->version() < 410) {
					$can_subselect = 0;
				}
			}
			
			$request['template']->setVar('can_subselect', $can_subselect);
			$request['template']->setVar('forums_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/forums.html');
			$request['template']->setFile('content', 'filters_add.html');
			$request['template']->setVar('filter_action', 'admin.php?act=filters_insert');

		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminEditFilter extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
			if(!isset($_REQUEST['filter_id']) || intval($_REQUEST['filter_id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFILTER'), 'content', FALSE);
				return $action->execute($request);
			}

			$filter_id = intval($_REQUEST['filter_id']);

			global $_FILTERS;

			if(!isset($_FILTERS[$filter_id]) || empty($_FILTERS[$filter_id])) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFILTER'), 'content', FALSE);
				return $action->execute($request);
			}

			$filter = $_FILTERS[$filter_id];

			foreach($filter as $key=>$val) {
				$request['template']->setVar('filter_'. $key, $val);
			}
			
			$can_subselect = 1;
			if($_CONFIG['dba']['driver'] == 'mysql') {
				if($request['dba']->version() < 410) {
					$can_subselect = 0;
				}
			}
			
			$request['template']->setVar('can_subselect', $can_subselect);
			$request['template']->setVar('is_edit', 1);

			k4_bread_crumbs($request['template'], $request['dba'], 'L_ADDFILTER');
			$request['template']->setVar('forums_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/forums.html');
			$request['template']->setFile('content', 'filters_add.html');
			$request['template']->setVar('filter_action', 'admin.php?act=filters_update');
			
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminInsertFilter extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
			
			if(!isset($_REQUEST['filter_name']) || $_REQUEST['filter_name'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_REQUIREDFIELD2', 'L_NAME'), 'content', TRUE);
				return $action->execute($request);
			}

			if(!isset($_REQUEST['filter_desc']) || $_REQUEST['filter_desc'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_REQUIREDFIELD2', 'L_DESCRIPTION'), 'content', TRUE);
				return $action->execute($request);
			}

			if(!isset($_REQUEST['filter_query']) || $_REQUEST['filter_query'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_REQUIREDFIELD2', 'L_QUERY'), 'content', TRUE);
				return $action->execute($request);
			}

			if(!isset($_REQUEST['num_inserts']) || $_REQUEST['num_inserts'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_REQUIREDFIELD2', 'L_NUMFILTERSUBSTITUTES'), 'content', TRUE);
				return $action->execute($request);
			}

			global $_CONFIG;

			if($_CONFIG['dba']['driver'] == 'mysql') {
				if($request['dba']->version() < 410 && strpos(strtolower($_REQUEST['filter_query']), '(select ') !== FALSE) {
					$action = new K4InformationAction(new K4LanguageElement('L_FILTERUSESSUBSELECTS'), 'content', TRUE);
					return $action->execute($request);
				}
			}
			
			$filter_query = str_replace('s%', 's**', str_replace('%%', '**%', $_REQUEST['filter_query']));

			$insert = $request['dba']->prepareStatement("INSERT INTO ". K4FILTERS ." (filter_name,filter_desc,filter_query,num_inserts,insert1_type,insert2_type,insert3_type,insert1_label,insert2_label,insert3_label) VALUES (?,?,?,?,?,?,?,?,?,?)");
			
			$insert->setString(1, $_REQUEST['filter_name']);
			$insert->setString(2, $_REQUEST['filter_desc']);
			$insert->setString(3, $filter_query);
			$insert->setInt(4, $_REQUEST['num_inserts']);
			$insert->setInt(5, $_REQUEST['insert1_type']);
			$insert->setInt(6, $_REQUEST['insert2_type']);
			$insert->setInt(7, $_REQUEST['insert3_type']);
			$insert->setString(8, $_REQUEST['insert1_label']);
			$insert->setString(9, $_REQUEST['insert2_label']);
			$insert->setString(10, $_REQUEST['insert3_label']);

			$insert->executeUpdate();

			reset_cache('filters');

			k4_bread_crumbs($request['template'], $request['dba'], 'L_ADDFILTER');
			
			$action = new K4InformationAction(new K4LanguageElement('L_ADDEDFILTER', $_REQUEST['filter_name']), 'content', FALSE, 'admin.php?act=filters_manage', 3);
			return $action->execute($request);

		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminUpdateFilter extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
			if(!isset($_REQUEST['filter_id']) || intval($_REQUEST['filter_id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFILTER'), 'content', FALSE);
				return $action->execute($request);
			}

			$filter_id = intval($_REQUEST['filter_id']);

			global $_FILTERS;

			if(!isset($_FILTERS[$filter_id]) || empty($_FILTERS[$filter_id])) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFILTER'), 'content', FALSE);
				return $action->execute($request);
			}

			$filter = $_FILTERS[$filter_id];
						
			if(!isset($_REQUEST['filter_name']) || $_REQUEST['filter_name'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_REQUIREDFIELD2', 'L_NAME'), 'content', TRUE);
				return $action->execute($request);
			}

			if(!isset($_REQUEST['filter_desc']) || $_REQUEST['filter_desc'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_REQUIREDFIELD2', 'L_DESCRIPTION'), 'content', TRUE);
				return $action->execute($request);
			}

			if(!isset($_REQUEST['filter_query']) || $_REQUEST['filter_query'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_REQUIREDFIELD2', 'L_QUERY'), 'content', TRUE);
				return $action->execute($request);
			}

			if(!isset($_REQUEST['num_inserts']) || $_REQUEST['num_inserts'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_REQUIREDFIELD2', 'L_NUMFILTERSUBSTITUTES'), 'content', TRUE);
				return $action->execute($request);
			}

			global $_CONFIG;

			if($_CONFIG['dba']['driver'] == 'mysql') {
				if($request['dba']->version() < 410 && strpos(strtolower($_REQUEST['filter_query']), '(select ') !== FALSE) {
					$action = new K4InformationAction(new K4LanguageElement('L_FILTERUSESSUBSELECTS'), 'content', TRUE);
					return $action->execute($request);
				}
			}

			$filter_query = str_replace('s%', 's**', str_replace('%%', '**%', $_REQUEST['filter_query']));
			
			$update = $request['dba']->prepareStatement("UPDATE ". K4FILTERS ." SET filter_name=?,filter_desc=?,filter_query=?,num_inserts=?,insert1_type=?,insert2_type=?,insert3_type=?,insert1_label=?,insert2_label=?,insert3_label=? WHERE filter_id=?");
			
			$update->setString(1, $_REQUEST['filter_name']);
			$update->setString(2, $_REQUEST['filter_desc']);
			$update->setString(3, $filter_query);
			$update->setInt(4, $_REQUEST['num_inserts']);
			$update->setInt(5, $_REQUEST['insert1_type']);
			$update->setInt(6, $_REQUEST['insert2_type']);
			$update->setInt(7, $_REQUEST['insert3_type']);
			$update->setString(8, $_REQUEST['insert1_label']);
			$update->setString(9, $_REQUEST['insert2_label']);
			$update->setString(10, $_REQUEST['insert3_label']);
			$update->setInt(11, $filter['filter_id']);

			$update->executeUpdate();

			reset_cache('filters');

			k4_bread_crumbs($request['template'], $request['dba'], 'L_EDITFILTER');
			
			$action = new K4InformationAction(new K4LanguageElement('L_UPDATEDFILTER', $filter['filter_name']), 'content', FALSE, 'admin.php?act=filters_manage', 3);
			return $action->execute($request);						
			
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminRemoveFilter extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
			
			if(!isset($_REQUEST['filter_id']) || intval($_REQUEST['filter_id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFILTER'), 'content', FALSE);
				return $action->execute($request);
			}

			$filter_id = intval($_REQUEST['filter_id']);

			global $_FILTERS;

			if(!isset($_FILTERS[$filter_id]) || empty($_FILTERS[$filter_id])) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFILTER'), 'content', FALSE);
				return $action->execute($request);
			}

			$filter = $_FILTERS[$filter_id];

			$request['dba']->executeUpdate("DELETE FROM ". K4FILTERS ." WHERE filter_id=". $filter_id);
			$request['dba']->executeUpdate("DELETE FROM ". K4FORUMFILTERS ." WHERE filter_id=". $filter_id);

			reset_cache('filters');
			reset_cache('forum_filters');

			k4_bread_crumbs($request['template'], $request['dba'], 'L_REMOVEFILTER');
			
			$action = new K4InformationAction(new K4LanguageElement('L_REMOVEDFILTER', $filter['filter_name']), 'content', FALSE, 'admin.php?act=filters_manage', 3);
			return $action->execute($request);

		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminFiltersSelectForum extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			global $_FILTERS;
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_EDITFORUMFILTERS');
			$request['template']->setVar('forums_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/forums.html');
			$request['template']->setFile('content', 'filters_selectforum.html');
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

?>