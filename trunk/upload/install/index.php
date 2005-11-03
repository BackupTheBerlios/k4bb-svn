<?php
/**
* k4 Bulletin Board, index.php
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
* @version $Id: index.php 144 2005-07-05 02:29:07Z Peter Goodman $
* @package k42
*/
	
error_reporting(E_ALL ^ E_NOTICE);
ignore_user_abort(TRUE);
@set_time_limit(0);

function one_dir_up($dir) {

	$dir		= str_replace('\\', '/', $dir);

	$folders	= explode('/', $dir);

	unset($folders[count($folders)-1]);
	
	$folders	= array_values($folders);

	$dir		= implode('/', $folders);
	
	return $dir;
}

define('INSTALLER_BASE_DIR', dirname(__FILE__));
define('FORUM_BASE_DIR', one_dir_up(INSTALLER_BASE_DIR));
define('INCLUDE_BASE_DIR', one_dir_up(INSTALLER_BASE_DIR) . '/includes');

define('K4_BASE_DIR', FORUM_BASE_DIR .'/includes/k4bb');
define('BB_BASE_DIR', FORUM_BASE_DIR);

define('IN_K4', TRUE);

require_once INCLUDE_BASE_DIR . '/filearts/filearts.php';
require_once INCLUDE_BASE_DIR . '/k4bb/init.php';

class K4Installer extends FAController {
	function execute() {
		$request = $this->getRequest();
		$request['template'] = &new FATemplate(FA_FORCE | FA_NOCACHE);
		
		$this->setRequest($request);

		parent::execute();
	}
}

class DatabaseVerifyFilter extends FAFilter {
	var $_error;

	function execute(&$action, &$request) {
		$ret = FALSE;

		if ($request['event'] == 'dbverify') {
			push_error_handler(array($this, 'verifyError'));

			$this->addPostFilter('bb_title', new FARequiredFilter);
			$this->addPostFilter('bb_description', new FARequiredFilter);

			$this->addPostFilter('dba_driver', new FARequiredFilter);
			$this->addPostFilter('dba_name', new FARequiredFilter);
			$this->addPostFilter('dba_server', new FARequiredFilter);
			$this->addPostFilter('dba_username', new FARequiredFilter);
			$this->addPostFilter('dba_password', new FARequiredFilter);

			$this->addPostFilter('admin_name', new FARequiredFilter);
			$this->addPostFilter('admin_email', new FARequiredFilter);
			$this->addPostFilter('admin_pass', new FARequiredFilter);

			if ($this->hasFailures()) {
				//print_r($_POST);
				//print_r($this->getFailures());
				trigger_error("Missing, or incomplete POST data");
			}

			if (!$this->_error) {
				// Setup the database info
				$db_info				= array();
				$db_info['driver']		= $_POST['dba_driver'];
				$db_info['database']	= $_POST['dba_name'];
				$db_info['directory']	= FORUM_BASE_DIR .'/tmp/sqlite';
				$db_info['server']		= $_POST['dba_server'];
				$db_info['user']		= $_POST['dba_username'];
				$db_info['pass']		= $_POST['dba_password'];
				$ftp_info				= array();
				$ftp_info['use']		= $_POST['use_ftp'];
				$ftp_info['user']		= $_POST['ftp_name'];
				$ftp_info['pass']		= $_POST['ftp_pass'];

				$dba = &db_connect($db_info);

				// Check to see if the schema is available
				$driver = $db_info['driver'];
				$schema = INSTALLER_BASE_DIR . "/schema/k4.{$driver}.schema";

				$request['schema'] = $schema;

				if (!is_readable($schema))
					trigger_error("Database schema missing for $driver", E_USER_ERROR);

				// Encrypt the admin pass
				$_POST['admin_pass'] = md5($_POST['admin_pass']);
			}

			if ($this->_error) {
				$request['template']->setVar('dberror', $this->_error);
				$action = new DatabaseSetupAction();

				echo $this->_error;

				$ret = TRUE;
			} else {
				$request['dba'] = &$dba;
				$request['db_info'] = $db_info;
				$request['ftp_info'] = $ftp_info;

				$action = new ConfigWriterAction();
			}

			pop_error_handler();
		}

		return $ret;
	}

	function verifyError(&$error) {
		$this->_error = $error->message;

		return TRUE;
	}
}

class DatabaseAndFileChecks extends FAAction {
	function tpl_ret($bool) {
		$str	= $bool ? '<strong style="color: #009900;">YES</strong>' : '<strong style="color: #FF0000;">NO</strong>';
		return $str;
	}
	function execute(&$request) {
		$url = &new FAUrl($_SERVER['PHP_SELF']);
		$url->args[FA_EVENT_VAR] = 'setup';
		
		$db_check = array(
								'has_mysql'		=> $this->tpl_ret(function_exists('mysql_connect')),
								'has_mysqli'	=> $this->tpl_ret(function_exists('mysqli_connect')),
								'has_sqlite'	=> $this->tpl_ret(function_exists('sqlite_open')),
								'has_pgsql'		=> $this->tpl_ret(function_exists('pg_connect')),
								);
		
		$db_passed = FALSE;
		foreach($db_check as $check) {
			if($check == $this->tpl_ret(TRUE)) {
				$db_passed = TRUE;
				break;
			}
		}
		
		$tmp_dir = is_readable('../tmp') && is_writable('../tmp') 
			&& is_readable('../tmp/cache') && is_writable('../tmp/cache') 
			&& is_readable('../tmp/upload') && is_writable('../tmp/upload')
			&& is_readable('../tmp/cache') && is_writable('../tmp/cache')
			&& is_readable('../tmp/sqlite') && is_writable('../tmp/sqlite')
			&& is_readable('../tmp/stylesets') && is_writable('../tmp/stylesets');
		
		$rss_dir = is_readable('../upload/templates/RSS/rss-0.92/compiled') && is_writable('../upload/templates/RSS/rss-0.92/compiled')
			&& is_readable('../upload/templates/RSS/rss-2.0/compiled') && is_writable('../upload/templates/RSS/rss-2.0/compiled');

		$fs_check = array(
								'has_chmod_tmp'		=> $this->tpl_ret($tmp_dir),
								'has_chmod_k4bb'	=> $this->tpl_ret(is_readable('../includes/k4bb') && is_writable('../includes/k4bb')),
								'has_chmod_tc'		=> $this->tpl_ret(is_readable('../templates/Descent/compiled') && is_writable('../templates/Descent/compiled')),
								'has_chmod_tac'		=> $this->tpl_ret(is_readable('../templates/Descent/admin/compiled') && is_writable('../templates/Descent/admin/compiled')),
								'has_chmod_tamc'	=> $this->tpl_ret(is_readable('../templates/Descent/admin/menus/compiled') && is_writable('../templates/Descent/admin/menus/compiled')),
								'has_chmod_tacc'	=> $this->tpl_ret(is_readable('../templates/Descent/admin/css/compiled') && is_writable('../templates/Descent/admin/css/compiled')),
								'has_chmod_ac'		=> $this->tpl_ret(is_readable('../templates/Archive/compiled') && is_writable('../templates/Archive/compiled')),
								'has_chmod_rc'		=> $this->tpl_ret($rss_dir),
								);

		$fs_passed = TRUE;
		foreach($fs_check as $check) {
			if($check == $this->tpl_ret(FALSE)) {
				$fs_passed = FALSE;
				break;
			}
		}
		
		$request['template']->setVar('db_passed', $this->tpl_ret($db_passed));
		$request['template']->setVar('fs_passed', $this->tpl_ret($fs_passed));
		$request['template']->setVarArray($db_check);
		$request['template']->setVarArray($fs_check);

		$template = $request['template'];
		$template->setVar('install_action', $url->__toString());
		$template->render(INSTALLER_BASE_DIR . '/templates/welcome.html');
	}
}

class DatabaseSetupAction extends FAAction {
	function execute(&$request) {
		$url = &new FAUrl($_SERVER['PHP_SELF']);
		$url->args[FA_EVENT_VAR] = 'dbverify';

		$template = $request['template'];
		$template->setVar('install_action', $url->__toString());
		$template->render(INSTALLER_BASE_DIR . '/templates/installer.html');
	}
}

class ConfigWriterAction extends FAAction {
	function execute(&$request) {
		$config = &new FATemplate(FA_FORCE | FA_NOCACHE);
		$config->setVar('db_driver', $request['db_info']['driver']);
		$config->setVar('db_database', $request['db_info']['database']);
		$config->setVar('db_directory', $request['db_info']['directory']);
		$config->setVar('db_server', $request['db_info']['server']);
		$config->setVar('db_user', $request['db_info']['user']);
		$config->setVar('db_pass', $request['db_info']['pass']);
		
		// ftp settings
		$config->setVar('use_ftp', $request['ftp_info']['use']);
		$config->setVar('ftp_user', $request['ftp_info']['user']);
		$config->setVar('ftp_pass', $request['ftp_info']['pass']);

		$config->setVar('cache_in_db', $_POST['store_cache'] == 'db' ? 'TRUE' : 'FALSE');
				
		$_CONFIG					= array();
		$_CONFIG['ftp']['use_ftp']	= $request['ftp_info']['use'] == 'TRUE' ? TRUE : FALSE;
		$_CONFIG['ftp']['username']	= $request['ftp_info']['user'];
		$_CONFIG['ftp']['password']	= $request['ftp_info']['pass'];

		$GLOBALS['_CONFIG']			= $_CONFIG;
		$GLOBALS['_DBA']			= $request['dba'];

		$buffer						= $config->run(dirname(__FILE__) . '/templates/config.php');

		$config->writeBuffer(INCLUDE_BASE_DIR . '/k4bb/config.php', '<?php' . FA_NL . $buffer . FA_NL . '?>');

		$sqldata					= &new FATemplate(FA_FORCE | FA_NOCACHE);
		$_POST['admin_created']		= time();
		$sqldata->setVarArray($_POST);

		$sqldata->setVar('IMG_DIR', '{$IMG_DIR}');

		$buffer		= file_get_contents($request['schema']);
		$queries	= explode(';', $buffer);

		foreach ($queries as $query) {
			if (trim($query))
				$request['dba']->executeUpdate(trim($query));
		}

		$buffer = $sqldata->run(dirname(__FILE__) . '/schema/k4.data.schema');
		$queries = explode(FA_NL, $buffer);

		foreach ($queries as $query) {
			if ($query)
				$request['dba']->executeUpdate($query);
		}
		
		// create the cache
		$general_cache	= new K4GeneralCacheFilter;
		$cache			= array();
		$methods		= get_class_methods($general_cache);
		foreach($methods as $function) {
			if(substr($function, 0, 6) == 'cache_') {
				$general_cache->$function($cache, $request);
			}
		}
		
		define('CACHE_IN_DB', $_POST['store_cache'] == 'db' ? TRUE : FALSE);

		DBCache::createCache($cache);
		
		// all done :D
		$request['template']->render(INSTALLER_BASE_DIR . '/templates/success.html');
	}
}

$app = &new K4Installer();

$app->addFilter(new DatabaseVerifyFilter);

$app->setAction('dbsetup', new DatabaseSetupAction);

$app->setAction('welcome', new DatabaseAndFileChecks);
$app->setDefaultEvent('welcome');


$app->execute();

?>