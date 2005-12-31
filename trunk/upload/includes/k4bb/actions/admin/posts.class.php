<?php
/**
* k4 Bulletin Board, posts.class.php
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
* @package k4-2.0-dev
*/

class AdminPosts extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			global $_QUERYPARAMS, $_ALLFORUMS;
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_POSTS');
			$request['template']->setVar('posts_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/posts.html');
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

?>