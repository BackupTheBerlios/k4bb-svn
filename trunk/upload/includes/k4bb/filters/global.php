<?php
/**
* k4 Bulletin Board, global.php
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
* @version $Id$
* @package k42
*/

if (!defined('IN_K4'))
	return;

class K4RequestFilter extends FAFilter {
	function execute(&$action, &$request) {

		set_magic_quotes_runtime(0);

		foreach($_REQUEST as $key => $val) {
			if(!is_array($val))
				$_REQUEST[$key] = stripslashes($val);
		}
	}
}

class K4DatabaseFilter extends FAFilter {
	function execute(&$action, &$request) {
		global $_CONFIG;

		push_error_handler('k4_fatal_error');
		$dba = &db_connect($_CONFIG['dba']);
		pop_error_handler();

		if (false)
			$dba = &new K4SqlDebugger($dba);

		$request['dba'] =& $dba;
		
		// TODO: This should not be needed in the final version
		$GLOBALS['_DBA'] = &$dba;
	}

	function getId() {
		return 'dba';
	}
}

class K4SqlDebugPreFilter extends FAFilter {
	function execute(&$action, &$request) {
		if (isset($_GET['debug'])) {
			$request['dba'] = &new K4SqlDebugger($request['dba']);
		}
	}

	function getDependencies() {
		return array('dba');
	}

	function getId() {
		return 'dba';
	}
}

class K4SessionFilter extends FAFilter {
	function execute(&$action, &$request) {
		$session = &new FASession($request['dba'], K4SESSIONS);
		$request['session'] = &$session;
	}

	function getDependencies() {
		return array('dba');
	}

	function getId() {
		return 'session';
	}
}

class K4UserFilter extends FAFilter {
	function execute(&$action, &$request) {
		if (!isset($_SESSION['user']) || !is_a($_SESSION['user'], 'FAUser'))
			$_SESSION['user'] = &new K4Guest();

		$user = &$_SESSION['user'];
		$session = &$request['session'];

		if (!$user->isMember() && $session->isNew()) {
			$factory = &new K4UserFactory;
			$validator = &new K4CookieValidator($request['dba']);

			$user = &$factory->getUser($validator);

			if ($user->isMember())
				k4_set_login($request['dba'], $user, TRUE);
		}

		$request['user'] = &$user;

		$request['user_manager'] = &new K4UserManager($request['dba']);
	}

	function getDependencies() {
		return array('dba', 'session');
	}

	function getId() {
		return 'user';
	}
}

class K4LanguageFilter extends FAFilter {
	function execute(&$action, &$request) {
		global $_CONFIG;

		$lang = $_CONFIG['application']['lang'];

		if ($_SESSION['user']->isMember())
			$lang = $_SESSION['user']->get('language');

		k4_set_language($lang);
	}

	function getDependencies() {
		return array('user');
	}

	function getId() {
		return 'language';
	}
}

class K4LoginFilter extends FAFilter {
	function execute(&$action, &$request) {
		global $_LANG;

		if (isset($_POST['username'], $_POST['password'])) {
			// Ok, a login attempt is being made
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_LOGIN');

			$user = &$request['user'];

			if ($user->isMember()) {
				// Oops, trying to login when already logged in

				$action = new K4InformationAction(new K4LanguageElement('L_CANTBELOGGEDIN'), 'content');
			} else {
				$factory = &new K4UserFactory($request['dba']);
				$validator = &new K4RequestValidator($request['dba']);

				$user = $factory->getUser($validator);

				if ($user->isMember()) {
					// User successfully logged in
					
					if (isset($_POST['rememberme']) && $_POST['rememberme'] == 'on')
						$remember = TRUE;
					else 
						$remember = FALSE;

					k4_set_login($request['dba'], $user, $remember);
					//$request['user'] = &$user;

					$action = new K4InformationAction(new K4LanguageElement('L_LOGGEDINSUCCESS'), 'content', FALSE, $_SERVER['REQUEST_URI'], 3);
				} else {
					// User failed to log in
					$action = new K4InformationAction(new K4LanguageElement('L_INVALIDUSERPASS'), 'content');
				}
			}
		}
	}

	function getDependencies() {
		return array('language', 'dba');
	}

	function getId() {
		return 'login';
	}
}

class K4LogoutFilter extends FAFilter {
	function execute(&$action, &$request) {
		global $_LANG;
		
		if (isset($_GET['logout'])) {
			// Ok, a logout attempt is being made
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_LOGOUT');

			$url = &new FAUrl($_SERVER['REQUEST_URI']);
			unset($url->args['logout']);

			$user = &$request['user'];

			if (!$user->isMember()) {
				// Oops, trying to login when already logged in

				$action = new K4InformationAction(new K4LanguageElement('L_NEEDLOGGEDIN'), 'content');
			} else {
				k4_set_logout($request['dba'], $user);

				$action = new K4InformationAction(new K4LanguageElement('L_LOGGEDOUTSUCCESS'), 'content', FALSE, $url->__toString(), 3);
			}			
		}
	}

	function getDependencies() {
		return array('login', 'dba', 'language');
	}

	function getId() {
		return 'logout';
	}
}

class K4TemplateFilter extends FAFilter {
	var $_filename;

	function __construct($filename) {
		$this->_filename = $filename;
	}

	function execute(&$action, &$request) {
		global $_LANG, $_CONFIG, $_SETTINGS, $_USERGROUPS;

		$logout = &new FAUrl($_SERVER['REQUEST_URI']);
		//$logout->path = FALSE;
		$logout->args['logout'] = 'yes';

		$templateset = ($request['user']->isMember()) ? $request['user']->get('templateset') : 'Descent';
		$imageset = ($request['user']->isMember()) ? $request['user']->get('imageset') : 'Descent';
		$styleset = ($request['user']->isMember()) ? $request['user']->get('styleset') : 'Descent';

		// TODO: query the user to determine the theme		
		$request['template_file'] = BB_BASE_DIR . "/templates/$templateset/{$this->_filename}";
		
		// Load k4 custom compilers
		$compiler = &$request['template']->getTemplateCompiler();
		$compiler->loadCompilers(K4_BASE_DIR . '/compilers/');

		// Set Theme specific template variables
		$request['template']->setVar('IMG_DIR', $imageset);
		$request['template']->setVar('css_styles', get_cached_styleset($request['dba'], $styleset, $_SETTINGS['styleset']));

		// Set global template variables
		$request['template']->setVarArray($_LANG);
		$request['template']->setVarArray($_SETTINGS);
		$request['template']->setVar('logout_link', $logout->__toString());

		// Set user information
		$request['template']->setVarArray($request['user']->getInfoArray(), 'user_');
		
		$request['template']->setList('usergroups', new FAArrayIterator($_USERGROUPS));

		// Set the settings to the template
		$request['template']->setVarArray($_SETTINGS);
	}

	function getDependencies() {
		return array('session', 'user', 'login', 'logout', 'language');
	}

	function getId() {
		return 'template';
	}
}

class K4GeneralInformation extends FAFilter {
	function execute(&$action, &$request) {
		
		$request['template']->setVar('VERSION', VERSION);
		//$request['template']->setVar('num_queries', $request['dba']->num_queries);
		
	}

	function getDependencies() {
		return array('template');
	}

	function getId() {
		return 'generalinfo';
	}
}

class K4SqlDebugPostFilter extends FAFilter {
	function execute(&$action, &$request) {
		if (isset($_GET['debug'])) {
			$action = new K4SqlDebugAction($action);
		}
	}
}

class K4SqlDebugAction extends FAAction {
	var $_action;

	function __construct($action) {
		$this->_action = $action;
	}

	function execute(&$request) {
		ob_start();
		$this->_action->execute($request);
		$page_preview = ob_get_contents();
		ob_end_clean();

		$template = &new FATemplate(FA_NOCACHE | FA_FORCE);
		$template->setVar('filename', $_SERVER['PHP_SELF']);
		$template->setVar('page_preview', $page_preview);
		$template->setList('queries', $request['dba']->getDebugIterator());
		$template->render(K4_BASE_DIR . '/templates/sqldebug.html');
	}
}

class K4InformationAction extends FAAction {
	var $_message;
	var $_block_id;
	var $_show_button;
	var $_url;
	var $_timeout;
	
	function __construct(&$message, $block_id, $show_button = TRUE, $url = '', $timeout = 0) {
		$this->_message = &$message;
		$this->_block_id = $block_id;
		$this->_show_button = $show_button;
		$this->_url = $url;
		$this->_timeout = $timeout;
	}

	function execute(&$request) {
		$js = '';
		
		if ($this->_timeout && $this->_url)
			$js = "setTimeout(\"location.href='{$this->_url}'\", {$this->_timeout} * 1000);";

		$request['template']->setVar('information', $this->_message->__toString());
		$request['template']->setVar('redirect', $js);
		$request['template']->setVisibility('info_back_button', $this->_show_button);		
		$request['template']->setFile($this->_block_id, 'information.html');
	}
}

?>