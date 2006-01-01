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

if (!defined('IN_K4'))
	return;

/* Add general global variables and things */
class K4GeneralCacheFilter extends FAFilter {
	function loop_forums(&$forums, $dba, $result, $level) {
		
		while($result->next()) {
			$temp						= $result->current();
			$temp['row_level']			= $level;
			$forums[$temp['forum_id']]	= $temp;
			
			if($temp['subforums'] > 0) {
				$this->loop_forums($forums, $dba, $dba->executeQuery("SELECT * FROM ". K4FORUMS ." WHERE parent_id = ". intval($temp['forum_id']) ." ORDER BY row_order ASC"), $level + 1);
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
	function cache_all_forums(&$cache, &$request) {

		$level					= 1;

		$cache['all_forums']	= array();
		$categories				= $request['dba']->executeQuery("SELECT * FROM ". K4FORUMS ." WHERE row_type=". CATEGORY ." AND parent_id=0 ORDER BY row_order ASC");
		$forums					= $request['dba']->executeQuery("SELECT * FROM ". K4FORUMS ." WHERE parent_id=0 AND row_type=". FORUM ." ORDER BY row_order ASC");
		$tmp_forums				= array();
		
		/* We want to get these top level forums in their proper order */
		while($forums->next()) {
			$temp						= $forums->current();
			$temp['row_level']			= $level;
			$cache['all_forums'][intval($temp['forum_id'])]		= $temp;
			$this->loop_forums($cache['all_forums'], $request['dba'], $request['dba']->executeQuery("SELECT * FROM ". K4FORUMS ." WHERE parent_id=". intval($temp['forum_id']) ." ORDER BY row_order ASC"), $level + 1);
		}
		
		if($categories->hasNext()) {
			while($categories->next()) {
				$temp					= $categories->current();
				$temp['row_level']		= $level;
				$cache['all_forums'][intval($temp['forum_id'])]	= $temp;
				
				$this->loop_forums($cache['all_forums'], $request['dba'], $request['dba']->executeQuery("SELECT * FROM ". K4FORUMS ." WHERE parent_id=". intval($temp['forum_id']) ." ORDER BY row_order ASC"), $level + 1);
				
			}
		}
		$categories->free();
	}

	/**
	 * Get ALL of the custom user profile fields
	 */
	function cache_profile_fields(&$cache, &$request) {
		
		global $_QUERYPARAMS;

		$cache['profile_fields']					= array();
		$result									= $request['dba']->executeQuery("SELECT * FROM ". K4PROFILEFIELDS);
		while($result->next()) {
			$temp								= $result->current();
			
			$cache['profile_fields'][$temp['name']]			= $temp;
			$cache['profile_fields'][$temp['name']]['html']	= format_profilefield($temp);
			
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
			
			$temp['data']						= str_replace('&quot;', '"', $temp['data']);
			$unserialize_result					= force_unserialize($temp['data']);
			$cache['datastore'][$temp['varname']] = $unserialize_result;
		}
		$result->free();
	}
	/**
	 * Get all of the lazy loads that need to be executed
	 */
	function cache_mail_queue(&$cache, &$request) {
		$cache['mail_queue']					= array();
		
		$result									= $request['dba']->executeQuery("SELECT * FROM ". K4MAILQUEUE ." WHERE finished = 0 LIMIT 1");
		while($result->next()) {
			$temp								= $result->current();
			
			$cache['mail_queue'][]				= $temp;
		}
		$result->free();
	}
	/**
	 * Get all of the user titles
	 */
	function cache_user_titles(&$cache, &$request) {
		$cache['user_titles']					= array();
		$result									= $request['dba']->executeQuery("SELECT * FROM ". K4USERTITLES ." ORDER BY num_posts DESC");
		while($result->next()) {
			$temp								= $result->current();
			$temp['image']						= $temp['image'] != '' ? '<img src="'. $temp['image'] .'" border="0" alt="'. $temp['title_text'] .'" />' : ($temp['num_pips'] > 0 ? str_repeat('<img src="Images/'. (isset($_SESSION['user']) && is_object($_SESSION['user']) ? $_SESSION['user']->get('styleset') : 'Descent') .'/Icons/pip.gif" border="0" alt="'. $temp['title_text'] .'" />', intval($temp['num_pips'])) : '');
			$temp['final_title']				= $temp['image'] != '' ? $temp['image'] : $temp['title_text'];
			$cache['user_titles'][]				= $temp;
		}
		$result->free();
	}
	function execute(&$action, &$request, $do_overwrite = FALSE) {
				
		$cache = array();
		
		/**
		 * Make sure the cache files exist
		 */
		if(!CACHE_IN_DB && USE_CACHE) {
			$d = dir(CACHE_DIR);
			if(count($d->read()) < 17) {
				
				/* Create the cache file using the class functions */
				$methods = get_class_methods($this);

				foreach($methods as $function) {
					if(substr($function, 0, 6) == 'cache_') {
						$this->$function($cache, $request);
					}	
				}

				/* Create the cache file */
				DBCache::createCache($cache);
			}
		}		

		/**
		 * Fileserver caching
		 */
		if(!CACHE_IN_DB && USE_CACHE) {
			
			/* Include the cache file */
			include_dir(CACHE_DIR);

//			if(!isset($cache) || !is_array($cache) || empty($cache)) {
//				trigger_error('FILE: The cache array does not exist or it is empty.', E_USER_ERROR);
//			}
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
				$cache[$temp['varname']] = force_unserialize($temp['data']);

				unset($temp); // memory saving
			}
			
			/* Set the Global variables */
			$GLOBALS['_SETTINGS']				= $cache['settings'];
			$GLOBALS['_MAPS']					= $cache['maps'];
			$GLOBALS['_USERGROUPS']				= $cache['usergroups'];
			$GLOBALS['_ACRONYMS']				= $cache['acronyms'];
			$GLOBALS['_CENSORS']				= $cache['censors'];
			$GLOBALS['_SPIDERS']				= $cache['spiders'];
			$GLOBALS['_SPIDERAGENTS']			= $cache['spider_agents'];
			$GLOBALS['_PROFILEFIELDS']			= $cache['profile_fields'];
			$GLOBALS['_ALLFORUMS']				= $cache['all_forums'];
			$GLOBALS['_FLAGGEDUSERS']			= $cache['flagged_users'];
			$GLOBALS['_BANNEDUSERIDS']			= $cache['banned_user_ids'];
			$GLOBALS['_BANNEDUSERIPS']			= $cache['banned_user_ips'];
			$GLOBALS['_STYLESETS']				= $cache['styles'];
			$GLOBALS['_FAQCATEGORIES']			= $cache['faq_categories'];
			$GLOBALS['_MAILQUEUE']				= isset($cache['mail_queue']) ? $cache['mail_queue'] : array();
			$GLOBALS['_DATASTORE']				= isset($cache['datastore']) ? $cache['datastore'] : array();
			$GLOBALS['_USERTITLES']				= $cache['user_titles'];
		}	
		
		/* Add the extra values onto the end of the userinfo query params variable */
		global $_QUERYPARAMS;
		foreach($GLOBALS['_PROFILEFIELDS'] as $temp) {
			$_QUERYPARAMS['userinfo']			.= ', ui.'. $temp['name'] .' AS '. $temp['name'];
		}
		$GLOBALS['_QUERYPARAMS'] = $_QUERYPARAMS;

		/* Execute the queue after we get/check the cached file(s) */
		//execute_mail_queue($request['dba'], $cache['mail_queue']);

		/* Add all of the forums to the template */
		global $_ALLFORUMS;
		
		$all_forums	 = new AllForumsIterator($_ALLFORUMS);
		$request['template']->setList('all_forums', $all_forums);
	}

	function getDependencies() {
		return array('dba');
	}
}

?>