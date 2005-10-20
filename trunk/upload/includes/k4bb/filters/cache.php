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
	
	/**
	 * Get the usergroups 
	 */
	function cache_usergroups(&$cache, &$request) {
		$cache['usergroups']	= array();
		$result					= $request['dba']->executeQuery("SELECT * FROM ". K4USERGROUPS ." ORDER BY max_perm DESC");
		while($result->next()) {
			$temp								= $result->current();
			$cache['usergroups'][$temp['id']]	= $temp;
		}
		$result->free();
		unset($result);
	}

	/**
	 * Get the settings
	 */
	function cache_settings(&$cache, &$request) {
		$cache['settings']						= array();
		$result									= $request['dba']->executeQuery("SELECT * FROM ". K4SETTINGS);
		while($result->next()) {
			$temp								= $result->current();
			$cache['settings'][$temp['varname']]= $temp['value'];
		}
		$result->free();
	}
	
	/**
	 * Get acronyms
	 */
	function cache_acronyms(&$cache, &$request) {
		$cache['acronyms']						= array();
		$result									= $request['dba']->executeQuery("SELECT * FROM ". K4ACRONYMS);
		while($result->next()) {
			$temp								= $result->current();
			$cache['acronyms'][$temp['acronym']]= $temp['meaning'];
		}
		$result->free();
	}

	/**
	 * Get word censors
	 */
	function cache_censors(&$cache, &$request) {
		$cache['censors']					= array();
		$result								= $request['dba']->executeQuery("SELECT * FROM ". K4WORDCENSORS);
		while($result->next()) {
			$temp							= $result->current();
			$cache['censors'][]				= $temp;
		}
		$result->free();
	}

	/**
	 * Get the search spiders
	 */
	function cache_spiders(&$cache, &$request) {
		$cache['spiders']					= array();
		$cache['spider_agents']				= array();
		$result								= $request['dba']->executeQuery("SELECT * FROM ". K4SPIDERS);
		while($result->next()) {
			$temp							= $result->current();
			$cache['spiders'][]				= $temp;
			$cache['spider_agents'][]		= $temp['useragent'];
		}
		$cache['spider_agents']				= implode("|", $cache['spider_agents']);
		$result->free();
	}
	
	/**
	 * Get all flagged user id's
	 */
	function cache_flagged_users(&$cache, &$request) {
		$cache['flagged_users']					= array();
		$result									= $request['dba']->executeQuery("SELECT id FROM ". K4USERS ." WHERE flag_level > 0");
		while($result->next()) {
			$temp								= $result->current();
			$cache['flagged_users'][]			= $temp['id'];
		}
		$result->free();
	}

	/**
	 * Get all banned user information
	 */
	function cache_banned_users(&$cache, &$request) {
		$cache['banned_user_ids']				= array();
		$cache['banned_user_ips']				= array();
		$result									= $request['dba']->executeQuery("SELECT * FROM ". K4BANNEDUSERS );
		while($result->next()) {
			$temp								= $result->current();
			$cache['banned_user_ids'][]			= $temp['user_id'];
			$cache['banned_user_ips'][]			= $temp['user_ip'];
		}
		$result->free();
	}

	/**
	 * Get ALL of the categories/forums
	 */
	function cache_forums(&$cache, &$request) {
		$cache['forums']						= array();
		$categories								= $request['dba']->executeQuery("SELECT * FROM ". K4CATEGORIES ." ORDER BY row_order ASC");
		
		$forums									= $request['dba']->executeQuery("SELECT * FROM ". K4FORUMS ." WHERE parent_id = 0 AND row_level = 1 ORDER BY row_order ASC");
		$tmp_forums								= array();
		
		/* We want to get these top level forums in their proper order */
		while($forums->next()) {
			$temp								= $forums->current();
			$cache['forums']['f'. intval($temp['forum_id'])]		= $temp;
		}
		
		if($categories->hasNext()) {
			while($categories->next()) {
				$temp										= $categories->current();
				
				$cache['forums']['c'. intval($temp['category_id'])]	= $temp;
				
				$this->loop_forums($cache['forums'], $request['dba'], $request['dba']->executeQuery("SELECT * FROM ". K4FORUMS ." WHERE row_level = 2 AND category_id = ". intval($temp['category_id']) ." ORDER BY row_order ASC"));
				
			}
		}
		$categories->free();
	}

	/**
	 * Get ALL of the custom user profile fields
	 */
	function cache_profile_fields(&$cache, &$request) {
		$cache['profile_fields']					= array();
		$result									= $request['dba']->executeQuery("SELECT * FROM ". K4PROFILEFIELDS);
		while($result->next()) {
			$temp								= $result->current();
			
			$cache['profile_fields'][$temp['name']]			= $temp;
			$cache['profile_fields'][$temp['name']]['html']	= format_profilefield($temp);
			
			/* Add the extra values onto the end of the userinfo query params variable */
			$_QUERYPARAMS['userinfo']			.= ', ui.'. $temp['name'] .' AS '. $temp['name'];
		}
		$result->free();
	}
	
	/**
	 * Get ALL of the defined stylesets
	 */
	function cache_styles(&$cache, &$request) {
		$cache['styles']					= array();
		$result								= $request['dba']->executeQuery("SELECT * FROM ". K4STYLES);
		while($result->next()) {
			$temp							= $result->current();
			
			$cache['styles'][$temp['name']]	= $temp;
		}
		$result->free();
	}

	/**
	 * Get ALL of the defined FAQ Categories
	 */
	function cache_faq_categories(&$cache, &$request) {
		$cache['faq_categories']				= array();
		$result								= $request['dba']->executeQuery("SELECT * FROM ". K4FAQCATEGORIES);
		while($result->next()) {
			$temp							= $result->current();
			
			$cache['faq_categories'][$temp['category_id']]	= $temp;
		}
		$result->free();
	}

	/**
	 * Cache all of the MAP's
	 */
	function cache_maps(&$cache, &$request) {
		$cache['maps'] = get_maps($request['dba']);
	}

	/**
	 * Get the datastore 
	 */
	function cache_datastore(&$cache, &$request) {
		$cache['datastore']						= array();
		$result									= $request['dba']->executeQuery("SELECT * FROM ". K4DATASTORE);
		while($result->next()) {
			$temp								= $result->current();
			
			$unserialize_result					= @unserialize(stripslashes(str_replace('&quot;', '"', $temp['data'])));
			$cache['datastore'][$temp['varname']] = $temp['data'] != '' ? (!$unserialize_result ? array() : $unserialize_result) : array();
		}
		$result->free();
	}
	function execute(&$action, &$request) {
		
		$cache = array();
		global $_QUERYPARAMS;

		/**
		 * Should we have to rewrite the cache file? 
		 */
		// TODO: make it so that the db can recache itself //  || CACHE_IN_DB
		if(!CACHE_IN_DB && (!file_exists(CACHE_FILE) || rewrite_file(CACHE_DIR, CACHE_INTERVAL))) {



	
	

	
	

	

	/**
	 * Get all of the lazy loads that need to be executed
	 */
	$cache['mail_queue']						= array();
	
	$result									= $request['dba']->executeQuery("SELECT * FROM ". K4MAILQUEUE ." WHERE finished = 0 LIMIT 1");
	while($result->next()) {
		$temp								= $result->current();
		
		$cache['mail_queue'][]				= $temp;
	}
	$result->free();
			
			/* Memory saving */
			unset($result);
			
			/* Create the cache file */
			if(USE_CACHE)
				DBCache::createCache($cache);

		}
		
		/**
		 * Fileserver caching
		 */
		if(!CACHE_IN_DB && USE_CACHE) {
			
			/* Include the cache file */
			include_dir(CACHE_DIR);
			
			if(!isset($cache) || !is_array($cache) || empty($cache)) {
				trigger_error('FILE: The cache array does not exist or it is empty.', E_USER_ERROR);
			}
		}
		
		/**
		 * Database caching
		 */
		if(CACHE_IN_DB && USE_CACHE) {
			
			$result = $request['dba']->executeQuery("SELECT * FROM ". K4CACHE);
			
			if(!$result->hasNext())
				trigger_error('DB: The cache array does not exist or it is empty.', E_USER_ERROR);
			
			while($result->next()) {
				$temp = $result->current();
				
				//	echo strlen($temp['data']) .'<br />'.$temp['data'] .'<br /><br />';
				$cache[$temp['varname']] = unserialize($temp['data']);

				unset($temp); // memory saving
			}

		}

		
		/* Set the Global variables */
		$GLOBALS['_SETTINGS']				= $cache['settings'];
		$GLOBALS['_MAPS']					= $cache['maps'];
		$GLOBALS['_USERGROUPS']				= $cache['usergroups'];
		$GLOBALS['_ACRONYMS']				= $cache['acronyms'];
		$GLOBALS['_CENSORS']				= $cache['censors'];
		$GLOBALS['_SPIDERS']				= $cache['spiders'];
		$GLOBALS['_SPIDERAGENTS']			= $cache['spider_agents'];
		$GLOBALS['_USERFIELDS']				= $cache['profile_fields'];
		$GLOBALS['_ALLFORUMS']				= $cache['forums'];
		$GLOBALS['_FLAGGEDUSERS']			= $cache['flagged_users'];
		$GLOBALS['_BANNEDUSERIDS']			= $cache['banned_user_ids'];
		$GLOBALS['_BANNEDUSERIPS']			= $cache['banned_user_ips'];
		$GLOBALS['_STYLESETS']				= $cache['styles'];
		$GLOBALS['_FAQCATEGORIES']			= $cache['faq_categories'];
		$GLOBALS['_MAILQUEUE']				= isset($cache['mail_queue']) ? $cache['mail_queue'] : array();
		$GLOBALS['_DATASTORE']				= isset($cache['datastore']) ? $cache['datastore'] : array();

		
		$all_forums							= &new AllForumsIterator($cache['forums']);
		
		/* Execute the queue after we get/check the cached file(s) */
		execute_mail_queue($request['dba'], $cache['mail_queue']);

		/* Add all of the forums to the template */
		$request['template']->setList('all_forums', $all_forums);
	}

	function getDependencies() {
		return array('dba');
	}
}

?>