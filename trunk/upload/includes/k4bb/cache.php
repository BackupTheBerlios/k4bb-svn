<?php
/**
* k4 Bulletin Board, cache.php
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
* @version $Id: cache.php 154 2005-07-15 02:56:28Z Peter Goodman $
* @package k42
*/



if(!defined('IN_K4'))
	return;

/**
 * Change the last modified time on a cache file or remove it
 */
function reset_cache($cache_varname) {
	global $_DBA;

	$cache	= array();
	$c		= new K4GeneralCacheFilter();

	$cache_func = 'cache_'. $cache_varname;
	
	if(method_exists($c, $cache_func)) {
		
		$request = array('dba' => $_DBA);

		$c->$cache_func($cache, $request);
		
		if(!CACHE_IN_DB) {
			
			
			$contents	= "<?php \n \n\nif(!defined('IN_K4')) { \n\treturn; \n}";
			foreach($cache as $id => $data) {
				$id			= '_'. strtoupper(implode('', explode('_', $id)));
				$contents	.= "\n\n\$GLOBALS['{$id}'] = " . var_export($data, TRUE) .";";
			}
			$contents	.= "\n?>";

			$filename = CACHE_DIR . $cache_varname .'.php';
			$handle = @fopen($filename, "w");
			__chmod($filename, 0777);
			@fwrite($handle, $contents);
			@fclose($handle);
			@touch($filename);

		} else {

			$update		= $_DBA->prepareStatement("UPDATE ". K4CACHE ." SET data=?,modified=? WHERE varname=?");
			foreach($cache as $varname => $data) {
				$update->setString(1, serialize($data));
				$update->setString(2, time());
				$update->setString(3, $varname);
				$update->executeUpdate();
			}
			unset($cache); // memory saving
		}
	}
}

/**
 * Forums Caching functions
 */

function cache_forum($info) {

	if(!isset($_SESSION['bbcache']))
		$_SESSION['bbcache'] = array();
	
	$required	= array('forum_id', 'parent_id', 'row_type', 'row_order', 'name', 'created', 'subforums');
	$data		= array();
	foreach($required as $val) {
		if(isset($info[$val]))
			$data[$val]	= $info[$val];
	}

	$data['subforums']	= isset($info['subforums']) ? intval($info['subforums']) : 0;
	$_SESSION['bbcache']['forums'][$data['forum_id']]				= $data;
	$_SESSION['bbcache']['forums'][$data['forum_id']]['forum_time']	= time();
}

function set_forum_cache_item($name, $val, $id) {
	$_SESSION['bbcache']['forums'][$id][$name] = $val;
}

function isset_forum_cache_item($name, $id) {
	return isset($_SESSION['bbcache']['forums'][$id][$name]);
}

/**
 * Create a styleset in a file
 */
function create_styleset(&$request, $styleset, $default_styleset) {
	
	if(!file_exists(BB_BASE_DIR .'/tmp/stylesets/'. preg_replace("~\s~i", '_', $styleset) .'.css')) {

		$query			= $request['dba']->prepareStatement("SELECT c.name as name, c.properties as properties FROM ". K4CSS ." c LEFT JOIN ". K4STYLES ." s ON s.id = c.style_id WHERE s.name = ? ORDER BY c.name ASC");
		$css			= "/* k4 Bulletin Board ". VERSION ." CSS Generated Style Set :: ". $styleset ." */\n\n";

		/* Set the user's styleset to the query */
		$query->setString(1, $styleset);
		
		/* Get the result */
		$result			= $query->executeQuery();
		
		/* If this styleset doesn't exist, use the default one instead */
		if($result->numrows() == 0) {
			
			$styleset	= $default_styleset;

			/* Set the user's styleset to the query */
			$query->setString(1, $default_styleset);
			
			/* Get the result */
			$result		= $query->executeQuery();
		}
		
		/* Loop through the result iterator */
		while($result->next()) {
			$temp = $result->current();
			$css .= "\t\t". $temp['name'] ." { ". $temp['properties'] ." }\n";
		}
		
		$result->free();

		/* Create a cached file for the CSS info */
		$handle = @fopen(BB_BASE_DIR .'/tmp/stylesets/'. preg_replace("~\s~i", '_', $styleset) .'.css', "w");
		@__chmod(BB_BASE_DIR .'/tmp/stylesets/'. preg_replace("~\s~i", '_', $styleset) .'.css', 0777);
		@fwrite($handle, $css);
		@fclose($handle);
	}
	
	$which_styleset = '';

	if(file_exists(BB_BASE_DIR .'/tmp/stylesets/'. $styleset .'.css')) {
		$which_styleset = $styleset;
	} else {
		if(file_exists(BB_BASE_DIR .'/tmp/stylesets/'. $default_styleset .'.css')) {
			$which_styleset = $default_styleset;
		} else {
			trigger_error('Could not retrieve the default style set.', E_USER_ERROR);
		}
	}
}

/* Set a temporary session cache */
function bb_setcookie_cache($name, $value, $expire) {
	if(!isset($_SESSION['bbcache']))
		$_SESSION['bbcache'] = array();

	$_SESSION['bbcache']['cookies'][] = array('name' => $name, 'value' => $value, 'expire' => $expire);
}

/* Set a page-context temporary cached cookie item value for topics-only */
function bb_settopic_cache_item($name, $value, $expire) {
	if(!isset($_SESSION['bbcache']))
		$_SESSION['bbcache'] = array();

	$_SESSION['bbcache']['temp_cookies'][] = array('name' => $name, 'value' => $value, 'expire' => $expire);
}


/* Funtion to execute and unset all bbcache cookie items */
function bb_execute_cache() {
	if(isset($_SESSION['bbcache'])) {
		
		/* Cached cookie setting */
		if(isset($_SESSION['bbcache']['cookies'])) {
			for($i = 0; $i < count($_SESSION['bbcache']['cookies']); $i++) {
				
				$temp = $_SESSION['bbcache']['cookies'][$i];

				setcookie($temp['name'], $temp['value'], $temp['expire'], get_domain());
			}
		}

		/* Clear the bbcache session under 'cookies' */
		$_SESSION['bbcache']['cookies'] = array();
	}
}

/* Funtion to execute and unset all bbcache temp cookie (topic) items */
function bb_execute_topiccache() {
	if(isset($_SESSION['bbcache'])) {
		
		/* Cached cookie setting */
		if(isset($_SESSION['bbcache']['temp_cookies'])) {
			for($i = 0; $i < count($_SESSION['bbcache']['temp_cookies']); $i++) {
				
				$temp = $_SESSION['bbcache']['temp_cookies'][$i];

				@setcookie($temp['name'], $temp['value'], $temp['expire'], get_domain());
				//bb_setcookie_cache($temp['name'], '', time()-3600);
			}
		}

		/* Clear the bbcache session under 'cookies' */
		$_SESSION['bbcache']['temp_cookies'] = array();
	}
}

/**
 * Cache info from the database in XML-like format,
 * then compile it to a monstrous PHP array
 */
class DBCache {
	function createCache($cache) {
		
		/**
		 * Are we caching on the fileserver?
		 */
		if(!CACHE_IN_DB) {
			
			foreach($cache as $id => $data) {
				
				$filename	= CACHE_DIR . $id .'.php';

				$id			= '_'. strtoupper(implode('', explode('_', $id)));

				$contents	= "<?php \n \n\nif(!defined('IN_K4')) { \n\treturn; \n}";
				$contents	.= "\n\n\$GLOBALS['{$id}'] = " . var_export($data, TRUE) .";";
				$contents	.= "\n?>";
				
				/* Create our file */
				//if(file_exists($filename)) {
				//	unlink($filename);
				//}
				
				$handle = @fopen($filename, "w");
				@chmod($filename, 0777); // @__chmod
				@fwrite($handle, $contents);
				@fclose($handle);
				@touch($filename, time());
				
//				/* Error checking on our newly created file */
//				if(!file_exists($filename)) { //  || !is_readable($filename) || !is_writeable($filename)
//					
//					trigger_error('An error occured while trying to create one of the forum cache files. ('. basename($filename) .')', E_USER_ERROR);
//				} else {
//					
//					if(strlen(file_get_contents($filename)) == 0) {
//						trigger_error('An error occured while trying to create one of the forum cache files. '. basename($filename) .' appears to be empty.', E_USER_ERROR);
//					}
//
//					/**
//					 * Need the touch here because for some reason the file changes the mod time in
//					 * some php versions
//					 */
//					
//				}
			}
		} else {
			
			global $_DBA;

			/**
			 * Are we caching in the database?
			 */
			
			//$delete		= $_DBA->executeUpdate("DELETE FROM ". K4CACHE);
			//$update		= $_DBA->prepareStatement("INSERT INTO ". K4CACHE ." (varname, data) VALUES (?,?)");
			$update			= $_DBA->prepareStatement("UPDATE ". K4CACHE ." SET data=?,modified=? WHERE varname=?");
			foreach($cache as $varname => $data) {
				$update->setString(1, serialize($data));
				$update->setString(2, time());
				$update->setString(3, $varname);
				$update->executeUpdate();
			}
			
		}
	}
}

?>