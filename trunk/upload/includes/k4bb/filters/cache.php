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

if (!defined('IN_K4'))
	return;

/* Add general global variables and things */
class K4GeneralCacheFilter extends FAFilter {
	function loop_forums(&$forums, $dba, $result) {
		
		while($result->next()) {
			$temp						= $result->current();
			$forums['f'. $temp['forum_id']]	= $temp;

			if($temp['subforums'] > 0) {
				$this->loop_forums($forums, $dba, $dba->executeQuery("SELECT * FROM ". K4FORUMS ." WHERE row_level = ". intval($temp['row_level'] + 1) ." AND parent_id = ". intval($temp['forum_id']) ." ORDER BY row_order ASC"));
			}
		}

		$result->free();
	}
	function execute(&$action, &$request) {
		
		$cache										= array();
		global $_QUERYPARAMS;
		
		/**
		 * Should we have to rewrite the cache file? 
		 */
		if(!file_exists(CACHE_FILE) || rewrite_file(CACHE_FILE, CACHE_INTERVAL)) {

			/**
			 * Get the usergroups 
			 */
			
			$cache[K4USERGROUPS]	= array();
			$result					= &$request['dba']->executeQuery("SELECT * FROM ". K4USERGROUPS ." ORDER BY max_perm DESC");
			while($result->next()) {
				$temp								= $result->current();
				$cache[K4USERGROUPS][$temp['id']]	= $temp;
			}
			$result->free();
			

			/**
			 * Get the settings
			 */
			
			$cache[K4SETTINGS]						= array();
			$result									= &$request['dba']->executeQuery("SELECT * FROM ". K4SETTINGS);
			while($result->next()) {
				$temp								= $result->current();
				$cache[K4SETTINGS][$temp['varname']]= $temp['value'];
			}
			$result->free();
			
			/**
			 * Get acronyms
			 */
			
			$cache[K4ACRONYMS]						= array();
			$result									= &$request['dba']->executeQuery("SELECT * FROM ". K4ACRONYMS);
			while($result->next()) {
				$temp								= $result->current();
				$cache[K4ACRONYMS][$temp['acronym']]= $temp['meaning'];
			}
			$result->free();

			/**
			 * Get word censors
			 */
			
			$cache[K4WORDCENSORS]					= array();
			$result									= &$request['dba']->executeQuery("SELECT * FROM ". K4WORDCENSORS);
			while($result->next()) {
				$temp								= $result->current();
				$cache[K4WORDCENSORS][]				= $temp;
			}
			$result->free();

			/**
			 * Get the search spiders
			 */
			
			$cache[K4SPIDERS]					= array();
			$cache['SPIDERAGENTS']				= array();
			$result								= &$request['dba']->executeQuery("SELECT * FROM ". K4SPIDERS);
			while($result->next()) {
				$temp							= $result->current();
				$cache[K4SPIDERS][]				= $temp;
				$cache['SPIDERAGENTS'][]		= $temp['useragent'];
			}
			$cache['SPIDERAGENTS']				= implode("|", $cache['SPIDERAGENTS']);
			$result->free();
			
			/**
			 * Get all flagged user id's
			 */
			
			$cache['_FLAGGEDUSERS']					= array();
			$result									= &$request['dba']->executeQuery("SELECT id FROM ". K4USERS ." WHERE flag_level > 0");
			while($result->next()) {
				$temp								= $result->current();
				$cache['_FLAGGEDUSERS'][]			= $temp['id'];
			}
			$result->free();

			/**
			 * Get all banned user information
			 */
			
			$cache['_BANNEDUSERIDS']				= array();
			$cache['_BANNEDUSERIPS']				= array();
			$result									= &$request['dba']->executeQuery("SELECT * FROM ". K4BANNEDUSERS );
			while($result->next()) {
				$temp								= $result->current();
				$cache['_BANNEDUSERIDS'][]			= $temp['user_id'];
				$cache['_BANNEDUSERIPS'][]			= $temp['user_ip'];
			}
			$result->free();

			/**
			 * Get ALL of the categories/forums
			 */
			
			$cache['all_forums']					= array();
			$categories								= &$request['dba']->executeQuery("SELECT * FROM ". K4CATEGORIES ." ORDER BY row_order ASC");
			
			$forums									= &$request['dba']->executeQuery("SELECT * FROM ". K4FORUMS ." WHERE parent_id = 0 AND row_level = 1 ORDER BY row_order ASC");
			$tmp_forums								= array();
			
			/* We want to get these top level forums in their proper order */
			while($forums->next()) {
				$temp								= $forums->current();
				$cache['all_forums']['f'. intval($temp['forum_id'])]		= $temp;
			}
			
			if($categories->hasNext()) {
				while($categories->next()) {
					$temp										= $categories->current();
					
					$cache['all_forums']['c'. intval($temp['category_id'])]	= $temp;
					
					$this->loop_forums($cache['all_forums'], $request['dba'], $request['dba']->executeQuery("SELECT * FROM ". K4FORUMS ." WHERE row_level = 2 AND category_id = ". intval($temp['category_id']) ." ORDER BY row_order ASC"));
					
				}
			}
			$categories->free();
			
			/**
			 * Get ALL of the custom user profile fields
			 */
			
			$cache[K4PROFILEFIELDS]					= array();
			$result									= &$request['dba']->executeQuery("SELECT * FROM ". K4PROFILEFIELDS);
			while($result->next()) {
				$temp								= $result->current();
				
				$cache[K4PROFILEFIELDS][$temp['name']]			= $temp;
				$cache[K4PROFILEFIELDS][$temp['name']]['html']	= format_profilefield($temp);
				
				/* Add the extra values onto the end of the userinfo query params variable */
				$_QUERYPARAMS['userinfo']			.= ', ui.'. $temp['name'] .' AS '. $temp['name'];
			}
			$result->free();

			
			/**
			 * Get ALL of the defined stylesets
			 */
			
			$cache[K4STYLES]					= array();
			$result								= &$request['dba']->executeQuery("SELECT * FROM ". K4STYLES);
			while($result->next()) {
				$temp							= $result->current();
				
				$cache[K4STYLES][$temp['name']]	= $temp;
			}
			$result->free();

			/**
			 * Get ALL of the defined FAQ Categories
			 */
			
			$cache[K4FAQCATEGORIES]				= array();
			$result								= &$request['dba']->executeQuery("SELECT * FROM ". K4FAQCATEGORIES);
			while($result->next()) {
				$temp							= $result->current();
				
				$cache[K4FAQCATEGORIES][$temp['category_id']]	= $temp;
			}
			$result->free();

			
			/* Memory saving */
			unset($result);
			

			/* Get the MAP's */
			$cache[K4MAPS]							= get_maps($request['dba']);
			
			/* Create the cache file */
			if(USE_CACHE) {
				DBCache::createCache($cache, CACHE_FILE);
			}

		} 
		
		if(file_exists(CACHE_FILE) && !rewrite_file(CACHE_FILE, CACHE_INTERVAL) && USE_CACHE) {
			
			/* Include the cache file */
			include_once CACHE_FILE;
			
			if(!isset($cache) || !is_array($cache) || empty($cache)) {
				reset_cache(CACHE_FILE);
				trigger_error('The cache array does not exist or it is empty.', E_USER_ERROR);
			}

			/* Add the extra values onto the end of the userinfo query params variable */
			//foreach($cache[K4PROFILEFIELDS] as $field) {
			//	$_QUERYPARAMS['userinfo']			.= ', ui.'. $field['name'] .' AS '. $field['name'];
			//}

		}
		
		/* Set the Global variables */
		$GLOBALS['_SETTINGS']				= $cache[K4SETTINGS];
		$GLOBALS['_MAPS']					= $cache[K4MAPS];
		$GLOBALS['_USERGROUPS']				= $cache[K4USERGROUPS];
		$GLOBALS['_ACRONYMS']				= $cache[K4ACRONYMS];
		$GLOBALS['_CENSORS']				= $cache[K4WORDCENSORS];
		$GLOBALS['_SPIDERS']				= $cache[K4SPIDERS];
		$GLOBALS['_SPIDERAGENTS']			= $cache['SPIDERAGENTS'];
		$GLOBALS['_USERFIELDS']				= $cache[K4PROFILEFIELDS];
		$GLOBALS['_ALLFORUMS']				= $cache['all_forums'];
		$GLOBALS['_FLAGGEDUSERS']			= $cache['_FLAGGEDUSERS'];
		$GLOBALS['_BANNEDUSERIDS']			= $cache['_BANNEDUSERIDS'];
		$GLOBALS['_BANNEDUSERIPS']			= $cache['_BANNEDUSERIPS'];
		$GLOBALS['_STYLESETS']				= $cache[K4STYLES];
		$GLOBALS['_FAQCATEGORIES']			= $cache[K4FAQCATEGORIES];
		
		$all_forums							= &new AllForumsIterator($cache['all_forums']);

		$request['template']->setList('all_forums', $all_forums);
	}

	function getDependencies() {
		return array('dba');
	}
}

class K4DatastoreCacheFilter extends FAFilter {
	function execute(&$action, &$request) {
		
		$cache										= array();

		/**
		 * Should we rewrite the email cache file? 
		 */
		if(!file_exists(CACHE_DS_FILE) || rewrite_file(CACHE_DS_FILE, CACHE_INTERVAL)) {
			
			/**
			 * Get the datastore 
			 */
			
			$cache[K4DATASTORE]						= array();
			$result									= &$request['dba']->executeQuery("SELECT * FROM ". K4DATASTORE);
			while($result->next()) {
				$temp								= $result->current();
				
				$unserialize_result					= @unserialize(stripslashes(str_replace('&quot;', '"', $temp['data'])));
				$cache[K4DATASTORE][$temp['varname']] = $temp['data'] != '' ? (!$unserialize_result ? array() : $unserialize_result) : array();
			}
			$result->free();
			
			if(USE_CACHE) {
				DBCache::createCache(array(K4DATASTORE => $cache[K4DATASTORE]), CACHE_DS_FILE);
			}
		}

		if(file_exists(CACHE_DS_FILE) && !rewrite_file(CACHE_DS_FILE, CACHE_INTERVAL) && USE_CACHE) {
			
			/* Include the cache file */
			include_once CACHE_DS_FILE;
			
			if(!isset($cache) || !is_array($cache) || empty($cache)) {
				reset_cache(CACHE_DS_FILE);
				trigger_error('The cached datastore array does not exist or it is empty.', E_USER_ERROR);
			}
		}

		$GLOBALS['_DATASTORE']					= isset($cache[K4DATASTORE]) ? $cache[K4DATASTORE] : array();
	}
}

class K4MailCacheFilter extends FAFilter {
	function execute(&$action, &$request) {

		$cache										= array();
		
		/**
		 * Should we rewrite the email cache file? 
		 */
		if(!file_exists(CACHE_EMAIL_FILE) || rewrite_file(CACHE_EMAIL_FILE, CACHE_INTERVAL)) {
			
			/**
			 * Get all of the lazy loads that need to be executed
			 */
			$cache[K4MAILQUEUE]						= array();
			
			$result									= &$request['dba']->executeQuery("SELECT * FROM ". K4MAILQUEUE ." WHERE finished = 0 LIMIT 1");
			while($result->next()) {
				$temp								= $result->current();
				
				$cache[K4MAILQUEUE][]				= $temp;
			}
			$result->free();
			
			if(USE_CACHE) {
				DBCache::createCache(array(K4MAILQUEUE => $cache[K4MAILQUEUE]), CACHE_EMAIL_FILE);
			}
		}
		
		if(file_exists(CACHE_EMAIL_FILE) && !rewrite_file(CACHE_EMAIL_FILE, CACHE_INTERVAL) && USE_CACHE) {
			
			/* Include the cache file */
			include_once CACHE_EMAIL_FILE;
			
			if(!isset($cache) || !is_array($cache) || empty($cache)) {
				reset_cache(CACHE_EMAIL_FILE);
				trigger_error('The cached email queue array does not exist or it is empty.', E_USER_ERROR);
			}
		}		

		$GLOBALS['_MAILQUEUE']				= isset($cache[K4MAILQUEUE]) ? $cache[K4MAILQUEUE] : array();

		/* Execute the queue after we get/check the cached file(s) */
		execute_mail_queue($request['dba'], $cache[K4MAILQUEUE]);
	}
}

//class K4TopicCacheFilter extends FAFilter {
//	function execute(&$action, &$request) {
//
//		$cache										= array();
//
//		/**
//		 * Should we rewrite our topic deletion cache file?
//		 */
//		if(!file_exists(CACHE_TOPIC_FILE) || rewrite_file(CACHE_TOPIC_FILE, CACHE_INTERVAL)) {
//			
//			/**
//			 * Get all of the lazy loads that need to be executed
//			 */
//			$cache[K4TOPICQUEUE]					= array();
//			
//			$result									= &$request['dba']->executeQuery("SELECT * FROM ". K4TOPICQUEUE ." WHERE finished = 0 LIMIT 1");
//			while($result->next()) {
//				$temp								= $result->current();
//				
//				$cache[K4TOPICQUEUE][]				= $temp;
//			}
//			$result->free();
//			
//			if(USE_CACHE) {
//				DBCache::createCache(array(K4TOPICQUEUE => $cache[K4TOPICQUEUE]), CACHE_TOPIC_FILE);
//			}
//		} 
//		
//		if(file_exists(CACHE_EMAIL_FILE) && !rewrite_file(CACHE_EMAIL_FILE, CACHE_INTERVAL) && USE_CACHE) {
//			
//			/* Include the cache file */
//			include_once CACHE_TOPIC_FILE;
//			
//			if(!isset($cache) || !is_array($cache) || empty($cache)) {
//				@unlink(CACHE_TOPIC_FILE);
//				trigger_error('The cached topic queue array does not exist or it is empty.', E_USER_ERROR);
//			}
//		}
//		
//		$GLOBALS['_TOPICQUEUE']				= isset($cache[K4TOPICQUEUE]) ? $cache[K4TOPICQUEUE] : array();
//
//		/* Execute the queue after we get/check the cached file(s) */
//		execute_topic_queue($request['dba'], (isset($cache[K4TOPICQUEUE]) ? $cache[K4TOPICQUEUE] : array()));
//	}
//}

?>