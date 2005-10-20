<?php
/**
* k4 Bulletin Board, cache.php
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
* @version $Id: cache.php 154 2005-07-15 02:56:28Z Peter Goodman $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4'))
	return;

/**
 * Change the last modified time on a cache file or remove it
 */
function reset_cache($cache_varname) {
	if(!CACHE_IN_DB) {
		if(file_exists(CACHE_DIR . $cache_varname .'.php')) {
			if(!touch(CACHE_DIR . $cache_varname .'.php', time()-86460) &&
				!touch(CACHE_DIR, time()-86460)) {
				@unlink(CACHE_DIR . $cache_varname .'.php');
			}
		}
	} else {
		global $_DBA;

		//$update = $_DBA->executeUpdate("UPDATE ". K4CACHE ." SET ");
	}
}

/**
 * Forums Caching functions
 */

function cache_forum($info) {

	if(!isset($_SESSION['bbcache']))
		$_SESSION['bbcache'] = array();
	
	$required	= array('forum_id', 'category_id', 'parent_id', 'row_left', 'row_right', 'row_type', 'row_level', 'row_order', 'name', 'created', 'subforums');
	$data		= array();
	foreach($required as $val) {
		if(isset($info[$val]))
			$data[$val]	= $info[$val];
	}

	$data['subforums']	= isset($info['subforums']) ? intval($info['subforums']) : 0;
	
	$data['id']			= $data['row_type'] & FORUM ? $data['forum_id'] : $data['category_id'];

	$_SESSION['bbcache']['forums'][$data['id']]				= $data;
	$_SESSION['bbcache']['forums'][$data['id']]['forum_time']	= time();
}

function set_forum_cache_item($name, $val, $id) {
	$_SESSION['bbcache']['forums'][$id][$name] = $val;
}

function isset_forum_cache_item($name, $id) {
	return isset($_SESSION['bbcache']['forums'][$id][$name]);
}

/**
 * styleset Caching
 */
function get_cached_styleset(&$request, $styleset, $default_styleset) {
	
	if(!file_exists(BB_BASE_DIR .'/tmp/stylesets/'. preg_replace("~\s~i", '_', $styleset) .'.css')) {

		$query			= &$request['dba']->prepareStatement("SELECT c.name as name, c.properties as properties FROM ". K4CSS ." c LEFT JOIN ". K4STYLES ." s ON s.id = c.style_id WHERE s.name = ? ORDER BY c.name ASC");
		$css			= "/* k4 Bulletin Board ". VERSION ." CSS Generated Style Set :: ". $styleset ." */\r\n\r\n";

		/* Set the user's styleset to the query */
		$query->setString(1, $styleset);
		
		/* Get the result */
		$result			= &$query->executeQuery();
		
		/* If this styleset doesn't exist, use the default one instead */
		if($result->numrows() == 0) {
			
			$styleset	= $default_styleset;

			/* Set the user's styleset to the query */
			$query->setString(1, $default_styleset);
			
			/* Get the result */
			$result		= &$query->executeQuery();
		}
		
		/* Loop through the result iterator */
		while($result->next()) {
			$temp = $result->current();
			$css .= "\t\t". $temp['name'] ." { ". $temp['properties'] ." }\r\n";
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

	$css	= $request['template']->run('tmp/stylesets/'. $which_styleset .'.css');
	$request['template']->setVar('css_styles', $css);
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

				$contents	= "<?php \nerror_reporting(E_ALL); \n\nif(!defined('IN_K4')) { \n\treturn; \n}";
				$contents	.= "\n\n\$cache += " . var_export($data, TRUE) .";";
				$contents	.= "\n?>";
				
				/* Create our file */
				if(file_exists($filename)) {
					unlink($filename);
				}
				
				$handle = @fopen($filename, "w");
				__chmod($filename, 0777);
				@fwrite($handle, $contents);
				@fclose($handle);
				
				@touch($filename, time() - 86400);
				
				/* Error checking on our newly created file */
				if(!file_exists($filename) || !is_readable($filename) || !is_writeable($filename)) {
					
					trigger_error('An error occured while trying to create the forum cache file.', E_USER_ERROR);
				} else {
					
					$lines = file($filename);

					if(count($lines) <= 1 || empty($lines)) {
						trigger_error('An error occured while trying to create the forum cache file. It appears to be empty.', E_USER_ERROR);
					}
					
					return TRUE;

					/**
					 * Need the touch here because for some reason the file changes the mod time in
					 * some php versions
					 */
					@touch($filename, time());
				}
			}
		} else {
			
			global $_DBA;

			/**
			 * Are we caching in the database?
			 */
			
			$delete		= $_DBA->executeUpdate("DELETE FROM ". K4CACHE);
			$update		= $_DBA->prepareStatement("INSERT INTO ". K4CACHE ." (varname, data) VALUES (?,?)");
			foreach($cache as $varname => $data) {
				
				$update->setString(1, $varname);
				$update->setString(2, serialize($data));
				$update->executeUpdate();
			}
			
		}
	}
}

?>