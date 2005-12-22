<?php
/**
* k4 Bulletin Board, k4bb.php
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
* @author Geoffrey Goodman
* @author Peter Goodman
* @version $Id: k4bb.php 158 2005-07-18 02:55:30Z Peter Goodman $
* @package k42
*/

error_reporting(E_ALL ^ E_NOTICE);

/**
 * Function to get a parent directory
 */
if(!function_exists('one_dir_up')) {
	function one_dir_up($dir, $num_dirs = 1) {

		$dir		= str_replace('\\', '/', $dir);

		$folders	= explode('/', $dir);
		array_splice($folders, count($folders)-$num_dirs);
		
		$folders	= array_values($folders);

		$dir		= implode('/', $folders);
		
		return $dir;
	}
}


define('K4_BASE_DIR', dirname(__FILE__));
define('BB_BASE_DIR', file_exists(dirname($_SERVER['SCRIPT_FILENAME']) .'/index.php') && is_dir(dirname($_SERVER['SCRIPT_FILENAME']) .'/includes') ? dirname($_SERVER['SCRIPT_FILENAME']) : one_dir_up(dirname(__FILE__), 2));

define('IN_K4', TRUE);

@set_time_limit(0);
set_magic_quotes_runtime(0);

ini_set('session.name',			'sid');
ini_set('session.auto_start',	0);
ini_set('arg_separator.output', '&amp;');
ini_set('url_rewriter.tags',	'a=href,area=href,frame=src,input=src,fieldset=');

require K4_BASE_DIR . '/config.php';
require K4_BASE_DIR . '/init.php';

class K4Controller extends FAController {
	function __construct($template) {

		global $_URL;

		parent::__construct();

		$request					= $this->getRequest();
		$request['load_timer']		= &new FATimer(3);
		$request['template']		= &new K4Template();
		
		$this->addFilter(new K4RequestFilter);

		// Open our database
		$this->addFilter(new K4DatabaseFilter);

		// cache filters
		$this->addFilter(new K4GeneralCacheFilter);

		$this->addFilter(new K4SessionFilter);
		$this->addFilter(new K4UserFilter);
		$this->addFilter(new K4LanguageFilter);
		$this->addFilter(new K4BannedUsersFilter);
		$this->addFilter(new K4LoginFilter);
		$this->addFilter(new K4LogoutFilter);
		$this->addFilter(new K4TemplateFilter($template));
		
		// general template info
		$this->addFilter(new K4GeneralInformation);

		// SQL debugging filters
		$this->addFilter(new K4SqlDebugPreFilter);
		$this->addFilter(new K4SqlDebugPostFilter);

		// Mass emailer filter
		$this->addFilter(new K4MasMailFilter);

		// Board closed filter
		$this->addFilter(new K4CloseBoardFilter);
		
		$this->setInvalidAction(new K4InformationAction(new K4LanguageElement('L_PAGEDOESNTEXIST'), 'content', TRUE));

		/**
		 * Set some important template variables
		 */
		$request['template']->setVar('load_time', $request['load_timer']->__toString());
		
		$url			= &new FAUrl($_URL->__toString());
		
		$request['template']->setVar('curr_url', $url->__toString());
		
		$url->args		= array();
		$url->anchor	= $url->file = FALSE;
		
		$request['template']->setVar('forum_url', $url->__toString());

		$request['template']->setVar('style_cellspacing', K4_TABLE_CELLSPACING);
		$request['template']->setVarArray(array('quicklinks' => 'quicklinks', 'modcp' => 'modcp'));
		
		$request['template']->setVar('nojs', (isset($url->args['nojs']) && intval($url->args['nojs']) == 1 ? 1 : 0));
		$request['template']->setVar('anchor', (isset($url->anchor) && $url->anchor != '' ? $url->anchor : ''));
		$request['template']->setVar('domain', get_domain());
		
		$this->setRequest($request);
		
		return TRUE;
	}

	function execute() {
		
		parent::execute();
		
		$request	= $this->getRequest();
		
		/**
		 * Set some other important info to the template
		 */
		$request['template']->setVar('num_queries', $request['dba']->getNumQueries() + 1);
		
		// reset the nojs variable if this is a new session
		if($request['session']->isNew())
			$request['template']->setVar('nojs', 0);

		/**
		 * Set cookies to track our last seen time and to try to disable javascript
		 */
		setcookie(K4LASTSEEN, $request['user']->get('seen'), time() + 2592000, get_domain());
		setcookie('k4_canjs', 0, time() + 2592000, get_domain());
		
		/**
		 * Determine some GZIP settings
		 */
		$gzip		= intval($request['template']->getVar('enablegzip')) == 1 ? TRUE : FALSE;
		$level		= intval($request['template']->getVar('gzipcompresslevel'));
		$level		= $level < 0 || $level > 9 ? 1 : $level;
		
		/**
		 * Modified GZIP compression code from: http://www.webmasterworld.com/forum88/7469.htm
		 */
		$encoding = false;
		if(isset($_SERVER['HTTP_ACCEPT_ENCODING'])) {
			if (strpos(' '. $_SERVER['HTTP_ACCEPT_ENCODING'], 'x-gzip') !== false) {
				$encoding = 'x-gzip';
			}
			if (strpos(' '. $_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false) {
				$encoding = 'gzip';
			}
		}

		/**
		 * Start GZIP compression
		 */
		if($gzip && $encoding && function_exists('gzcompress')) {

			header('Content-Encoding: ' . $encoding);
			
			ob_start();
			ob_implicit_flush(0);
			
			echo $request['template']->render($request['template_file']);

			$gzip_contents = ob_get_contents();
			ob_end_clean();

			$gzip_size		= strlen($gzip_contents);
			$gzip_crc		= crc32($gzip_contents);

			$gzip_contents = gzcompress($gzip_contents, $level);
			$gzip_contents = substr($gzip_contents, 0, strlen($gzip_contents) - 4);

			echo "\x1f\x8b\x08\x00\x00\x00\x00\x00";
			echo $gzip_contents;
			echo pack('V', $gzip_crc);
			echo pack('V', $gzip_size);
		
		/**
		 * Normal Output
		 */
		} else {
			
			$request['template']->setVar('enablegzip', 0);
			$request['template']->render($request['template_file']);
		}
	}
}

/**
 * A basic controller
 */
class K4BasicController extends FAController {
	function execute() {
		$request = $this->getRequest();
		
		$request['template'] = &new FATemplate();
		$this->addFilter(new K4DatabaseFilter);
		
		$this->setRequest($request);

		parent::execute();
	}
}


/**
 * Create a custom k4 subclass of the FileArts template class
 */
class K4Template extends FATemplate {
}


/**
 * Class to get a language element for such things as errors
 */
class K4LanguageElement extends FAObject {
	var $_args;

	function __construct() {
		$this->_args = func_get_args();
	}

	function __toString() {
		global $_LANG;
		
		$this->_args[0] = isset($_LANG[$this->_args[0]]) ? $_LANG[$this->_args[0]] : $this->_args[0];
		
		if(count($this->_args) > 1) {
			$return			= call_user_func_array('sprintf', $this->_args);
		} else {
			$return			= $this->_args[0];
		}

		return $return;
	}
}


/**
 * Set the current language depending on user and forum settings
 */
function k4_set_language($lang) {
	global $_LANG, $_CONFIG;

	if (!isset($_CONFIG['application']['lang']))
		trigger_error("Configuration error, undefined language", E_USER_ERROR);

	if (!isset($_LANG['LANG']) || $_LANG['LANG'] != $lang) {
		$lang_file = K4_BASE_DIR . "/lang/$lang/lang.php";

		if (!@include($lang_file)) {
			trigger_error("Invalid language: $lang", E_USER_WARNING);
			
			if (!@include(K4_BASE_DIR . "/lang/". $_CONFIG['application']['lang'] ."/lang.php"))
				trigger_error("Configuration error, default language does not exist", E_USER_ERROR);
		
			global $_LANG;

			setlocale(LC_ALL, $_LANG['locale']);
		}
	}
}

?>
