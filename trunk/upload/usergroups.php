<?php
/**
* k4 Bulletin Board, usergroups.php
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
* @version $Id: usergroups.php 144 2005-07-05 02:29:07Z Peter Goodman $
* @package k42
*/

require "includes/filearts/filearts.php";
require "includes/k4bb/k4bb.php";

class K4DefaultAction extends FAAction {
	function execute(&$request) {
		
		global $_USERGROUPS, $_QUERYPARAMS, $_URL;
		
		/**
		 * Are we looking at the list of user groups?
		 */
		if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
			$result			= explode('|', $request['user']->get('usergroups'));
			$groups			= $request['user']->get('usergroups') && $request['user']->get('usergroups') != '' ? iif(!$result, force_usergroups($request['user']->getInfoArray()), $result) : array();
			
			$query			= "SELECT * FROM ". K4USERGROUPS ." WHERE display_legend = 1";
			
			if($request['user']->get('perms') < ADMIN) {
				foreach($groups as $id) {
					if(isset($_USERGROUPS[$id])) {
						$query .= ' OR id = '. intval($id);
					}
				}
			} else {
				$query		= "SELECT * FROM ". K4USERGROUPS;
			}

			$groups		= $request['dba']->executeQuery( $query );
			
			$request['template']->setList('usergroups', $groups);

			k4_bread_crumbs($request['template'], $request['dba'], 'L_USERGROUPS');
			$request['template']->setFile('content', 'usergroups.html');
		
		/**
		 * Are we looking at a specific user group?
		 */
		} else {

			/* Is this user group set? */
			if(!isset($_USERGROUPS[intval($_REQUEST['id'])])) {
				k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
				
				$action = new K4InformationAction(new K4LanguageElement('L_GROUPDOESNTEXIST'), 'content', FALSE);
				return $action->execute($request);
			}
			
			$group			= $_USERGROUPS[intval($_REQUEST['id'])];
			
			/**
			 * If the group admin has yet to be set, set it to our administrator
			 */
			if($group['mod_name'] == '' || $group['mod_id'] == 0) {
				
				/* Get our administrator */
				$admin		= $request['dba']->getRow("SELECT * FROM ". K4USERS ." WHERE perms >= ". intval(ADMIN) ." ORDER BY perms,id ASC LIMIT 1");
				$request['dba']->executeUpdate("UPDATE ". K4USERGROUPS  ." SET mod_name = '". $request['dba']->quote($admin['name']) ."', mod_id = ". intval($admin['id']) ." WHERE id = ". intval($group['id']));
				
				reset_cache('usergroups');
				
				/* Add this info to the group array so that we can access it later */
				$group['mod_name']	= $admin['name'];
				$group['mod_id']	= $admin['id'];
			}
			
			/* Get our admins max user group.. it _should_ be the administrators group */
			$g						= get_user_max_group($request['dba']->getRow("SELECT ". $_QUERYPARAMS['user'] . $_QUERYPARAMS['userinfo'] ." FROM ". K4USERS ." u LEFT JOIN ". K4USERINFO ." ui ON u.id=ui.user_id WHERE u.id = ". intval($group['mod_id'])), $_USERGROUPS);
			
			/* Set his group's color */
			$group['mod_color']		= !isset($g['color']) || $g['color'] == '' ? '000000' : $g['color'];
			
			/* Add this group's info to the database */
			foreach($group as $key => $val)
				$request['template']->setVar('group_'. $key, $val);
			
			/* Create the Pagination */
			$resultsperpage		= 10;
			$num_results		= $request['dba']->getValue("SELECT COUNT(*) FROM ". K4USERS ." WHERE usergroups LIKE '%|". intval($group['id']) ."|%' AND id <> ". intval($group['mod_id']));

			$perpage			= isset($_REQUEST['limit']) && ctype_digit($_REQUEST['limit']) && intval($_REQUEST['limit']) > 0 ? intval($_REQUEST['limit']) : $resultsperpage;
			$num_pages			= ceil($num_results / $perpage);
			$page				= isset($_REQUEST['page']) && ctype_digit($_REQUEST['page']) && intval($_REQUEST['page']) > 0 ? intval($_REQUEST['page']) : 1;
			$pager				= &new FAPaginator($_URL, $num_results, $page, $perpage);
			
			if($num_results > $perpage) {
				$request['template']->setPager('users_pager', $pager);

				/* Create a friendly url for our pager jump */
				$page_jumper	= new FAUrl($_URL->__toString());
				$page_jumper->args['limit'] = $perpage;
				$page_jumper->args['page']	= FALSE;
				$page_jumper->anchor		= FALSE;
				$request['template']->setVar('pagejumper_url', preg_replace('~&amp;~i', '&', $page_jumper->__toString()));
			}
			
			/* Outside valid page range, redirect */
			if(!$pager->hasPage($page) && $num_pages > 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_PASTPAGELIMIT'), 'content', FALSE, 'usergroups.php?id='. $group['id'] .'&limit='. $perpage .'&page='. $num_pages, 3);
				return $action->execute($request);
			}

			/* Get the members for this usergroup */
			$start				= ($page - 1) * $perpage;
			
			/* Get the members of this usergroup */
			$result				= $request['dba']->executeQuery("SELECT ". $_QUERYPARAMS['user'] . $_QUERYPARAMS['userinfo'] ." FROM ". K4USERS ." u LEFT JOIN ". K4USERINFO ." ui ON u.id=ui.user_id WHERE u.usergroups LIKE '%|". intval($group['id']) ."|%' AND u.id <> ". intval($group['mod_id']) ." LIMIT ". intval($start) .", ". intval($perpage));
			$users				= &new UsersIterator($result);

			$request['template']->setVar('num_group_members', $num_results);
			
			if($request['user']->get('id') == $group['mod_id']) {
				$request['template']->setVisibility('add_user', TRUE);
				$request['template']->setVar('is_mod', 1);
			}

			k4_bread_crumbs($request['template'], $request['dba'], $group['name']);
			$request['template']->setList('users_in_usergroup', $users);
			$request['template']->setFile('content', 'lookup_usergroup.html');
		}
		
		return TRUE;
	}
}

$app	= new K4controller('forum_base.html');

$app->setAction('', new K4DefaultAction);
$app->setDefaultEvent('');

$app->setAction('add_user_to_group', new AddUserToGroup);
$app->setAction('remove_user_from_group', new RemoveUserFromGroup);

$app->execute();

?>