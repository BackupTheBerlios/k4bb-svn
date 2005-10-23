<?php
/**
* k4 Bulletin Board, cache.class.php
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