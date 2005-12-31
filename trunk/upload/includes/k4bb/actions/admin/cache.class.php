<?php
/**
* k4 Bulletin Board, cache.class.php
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

class AdminManageCache extends FAAction {
	function execute(&$request) {
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			
			$cache			= $request['dba']->executeQuery("SELECT * FROM ". K4CACHE ." ORDER BY varname ASC");
			$cache_items	= array();

			$total_size		= 0;

			while($cache->next()) {
				$temp		= $cache->current();
				
				$temp['name'] = ucwords(implode(' ', explode('_', $temp['varname'])));
				
				if(CACHE_IN_DB) {
					$size		= strlen($temp['data']);
				} else {
					$file		= CACHE_DIR . $temp['varname'] .'.php';

					if(file_exists($file)) {
						$size	= filesize($file);
						$temp['modified'] = filemtime($file);
					}
				}

				$total_size	+= $size;
				$temp['size'] = number_format($size);

				$cache_items[] = $temp;
			}
			
			$request['template']->setVar('total_cache_size', $total_size);
			$request['template']->setVar('total_cache_size_mb', round($total_size / 1048576, 4));

			k4_bread_crumbs($request['template'], $request['dba'], 'L_CACHECONTROL');
			$request['template']->setVar('options_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/options.html');
			$request['template']->setList('cache_items', new FAArrayIterator($cache_items));
			$request['template']->setFile('content', 'cache.html');
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminRefreshCache extends FAAction {
	function execute(&$request) {
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			
			if(isset($_REQUEST['varname']) && $_REQUEST['varname'] != '') {
				
				reset_cache($_REQUEST['varname']);
				
				$name	= ucwords(implode(' ', explode('_', $_REQUEST['varname'])));
				$action = new K4InformationAction(new K4LanguageElement('L_REFRESHEDCACHEITEM', $name), 'content', FALSE, 'admin.php?act=cache', 3);
			} else {
				
				$action = new K4InformationAction(new K4LanguageElement('L_REFRESHEDCACHE'), 'content', FALSE, 'admin.php?act=cache', 3);
				$general_cache = new K4GeneralCacheFilter;
				
				$cache	= array();
				$methods = get_class_methods($general_cache);
				foreach($methods as $function) {
					if(substr($function, 0, 6) == 'cache_') {
						$general_cache->$function($cache, $request);
					}
				}
				if(USE_CACHE)
					DBCache::createCache($cache);
			}

			return $action->execute($request);		
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

?>