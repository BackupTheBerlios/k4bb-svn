<?php
/**
* k4 Bulletin Board, index.php
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
* @author Geoffrey Goodman
* @version $Id: index.php 110 2005-06-13 20:48:58Z Peter Goodman $
* @package k42
*/
	

ignore_user_abort(TRUE);
@set_time_limit(0);

define('INSTALLER_BASE_DIR', dirname(__FILE__));
define('FORUM_BASE_DIR', INSTALLER_BASE_DIR . "/../");
define('INCLUDE_BASE_DIR', INSTALLER_BASE_DIR . "/../includes");

include INCLUDE_BASE_DIR . '/filearts/filearts.php';
include INCLUDE_BASE_DIR . '/k4bb/functions.php';

class K4Installer extends FAController {
	function execute() {
		$request = $this->getRequest();
		$request['template'] = &new FATemplate(FA_FORCE | FA_NOCACHE);

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
				print_r($_POST);
				print_r($this->getFailures());
				trigger_error("Missing, or incomplete POST data");
			}

			if (!$this->_error) {
				// Setup the database info
				$db_info				= array();
				$db_info['driver']		= $_POST['dba_driver'];
				$db_info['database']	= $_POST['dba_name'];
				$db_info['directory']	= '';
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
		$config->setVar('db_directory', '');
		$config->setVar('db_server', $request['db_info']['server']);
		$config->setVar('db_user', $request['db_info']['user']);
		$config->setVar('db_pass', $request['db_info']['pass']);
		$config->setVar('use_ftp', $request['ftp_info']['use']);
		$config->setVar('ftp_user', $request['ftp_info']['name']);
		$config->setVar('ftp_pass', $request['ftp_info']['pass']);

		$buffer = $config->run(dirname(__FILE__) . '/templates/config.php');
		
		__chmod(INCLUDE_BASE_DIR . '/k4bb/config.php', 0755, $request['ftp_info']['name'], $request['ftp_info']['pass']);

		$config->writeBuffer(INCLUDE_BASE_DIR . '/k4bb/config.php', '<?php' . FA_NL . $buffer . FA_NL . '?>');

		$sqldata = &new FATemplate(FA_FORCE | FA_NOCACHE);
		$sqldata->setVarArray($_POST);

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

		$template = $request['template'];
		$template->render(INSTALLER_BASE_DIR . '/templates/success.html');
	}
}

$app = &new K4Installer();

$app->addFilter(new DatabaseVerifyFilter);

$app->setAction('dbsetup', new DatabaseSetupAction);

$app->setDefaultEvent('dbsetup');

$app->execute();

?>