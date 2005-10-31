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
* @author Peter Goodman
* @version $Id: global.php 144 2005-07-05 02:29:07Z Peter Goodman $
* @package k42
*/

if (!defined('IN_K4'))
	return;

class K4RequestFilter extends FAFilter {
	function execute(&$action, &$request) {

		foreach($_REQUEST as $key => $val) {
			if(!is_array($val)) {
				$_REQUEST[$key] = stripslashes($val);
			}
		}
	}
}

class K4DatabaseFilter extends FAFilter {
	function execute(&$action, &$request) {
		
		global $_CONFIG;

		push_error_handler('k4_fatal_error');
		$dba = db_connect($_CONFIG['dba']);
		pop_error_handler();

		if (false)
			$dba = &new K4SqlDebugger($dba);

		$request['dba'] = &$dba;
		
		// TODO: This should not be needed in the final version
		$GLOBALS['_DBA'] = &$dba;
		
	}

	function getId() {
		return 'dba';
	}
}

class K4SqlDebugPreFilter extends FAFilter {
	function execute(&$action, &$request) {
		global $_URL;

		$url		= &new FAUrl($_URL->__toString());
		$url->args['debug'] = 1;
		
		$request['template']->setVar('debug_url', $url->__toString());
		
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
		$session = FASession::start($request['dba'], K4SESSIONS);
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
		
		global $_QUERYPARAMS;

		if (!isset($_SESSION['user']) || !is_a($_SESSION['user'], 'FAUser')) {
			$_SESSION['user'] = &new K4Guest();
		}

		$user		= &$_SESSION['user'];
		$session	= $request['session'];
		
		if (!$user->isMember() && $session->isNew()) {
			$factory = &new K4UserFactory;
			$validator = &new K4CookieValidator($request['dba']);
			
			$user = $factory->getUser($validator);
			
			if ($user->isMember())
				k4_set_login($request['dba'], $user, TRUE);
		}

		if($user->isMember() && !$session->isNew()) {
			$info = $request['dba']->getRow("SELECT {$_QUERYPARAMS['user']}{$_QUERYPARAMS['userinfo']}{$_QUERYPARAMS['usersettings']} FROM ". K4USERS ." u, ". K4USERINFO ." ui, ". K4USERSETTINGS ." us WHERE u.id=ui.user_id AND us.user_id=u.id AND u.id=". intval($_SESSION['user']->get('id')) ." LIMIT 1");

			if(is_array($info) && !empty($info)) {
				$user->setInfo($info);
			}
		}
		
		$request['user'] = &$user;
		$last_seen_days = ceil(@((time() - intval($request['user']->get('last_seen'))) / 86400));
		
		$request['user']->set('last_seen_days', $last_seen_days < 0 ? 0 : $last_seen_days);

		foreach($request['user']->getInfoArray() as $key => $val)
			$request['template']->setVar('user_'. $key, $val);

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
			$lang = ($_SESSION['user']->get('language') != '') ? $_SESSION['user']->get('language') : $lang;
		
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
			
			header("Cache-Control: must-revalidate");

			k4_bread_crumbs($request['template'], $request['dba'], 'L_LOGIN');

			$user = $request['user'];

			if ($user->isMember()) {
				// Oops, trying to login when already logged in

				$action = new K4InformationAction(new K4LanguageElement('L_CANTBELOGGEDIN'), 'content');
			} else {
				$factory = &new K4UserFactory($request['dba']);
				$validator = &new K4RequestValidator($request['dba']);

				$user = $factory->getUser($validator);

				if ($user->isMember()) {
					
					if(($user->get('reg_key') == '') || ($user->get('reg_key') != '' && $request['template']->getVar('canloginunverified') == 1) ) {
					
						// User successfully logged in
						
						if (isset($_POST['rememberme']) && $_POST['rememberme'] == 'on')
							$remember = TRUE;
						else 
							$remember = FALSE;

						k4_set_login($request['dba'], $user, $remember);
						//$request['user'] = &$user;

						$action = new K4InformationAction(new K4LanguageElement('L_LOGGEDINSUCCESS'), 'content', FALSE, $_SERVER['REQUEST_URI'], 3);
					} else {
						// this is a pending user who cannot log in
						k4_set_logout($request['dba'], $user);
						$action = new K4InformationAction(new K4LanguageElement('L_VERIFYEMAILTOLOGIN'), 'content');
					}
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

class K4BannedUsersFilter extends FAFilter {
	function execute(&$action, &$request) {
		global $_LANG, $_BANNEDUSERIDS, $_BANNEDUSERIPS;
		
		$banned			= FALSE;

		/**
		 * User ID banning
		 */
		if($request['user']->isMember()) {
			
			// this user is banned
			if(in_array($request['user']->get('id'), $_BANNEDUSERIDS) || $request['user']->get('banned') == 1) {
				
				$ban	= $request['dba']->getRow("SELECT * FROM ". K4BANNEDUSERS ." WHERE user_id = ". intval($request['user']->get('id')));
				
				if(is_array($ban) && !empty($ban)) {
					
					if($ban['expiry'] > time()) {

						$action = new K4InformationAction(new K4LanguageElement('L_BANNEDUSERID', $request['user']->get('name'), $ban['reason'], ($ban['expiry'] == 0 ? $_LANG['L_YOURDEATH'] : strftime("%m/%d/%Y", bbtime($ban['expiry']))) ), 'content', FALSE);
						$banned	= TRUE;
					} else {
						$request['dba']->executeUpdate("DELETE FROM ". K4BANNEDUSERS ." WHERE user_id = ". intval($request['user']->get('id')));
					}
				} else {
					// TODO: should I put anything here...?
				}
			}
		}
		
		/**
		 * User IP banning
		 */
		//if(in_array(USER_IP, $_BANNEDUSERIPS) && !$banned) {
		foreach($_BANNEDUSERIPS as $ip) {
			if(preg_match('~'. $ip .'~', USER_IP)) {
				$ban	= $request['dba']->getRow("SELECT * FROM ". K4BANNEDUSERS ." WHERE user_ip = '". $request['dba']->quote(USER_IP) ."'");
					
				$action = new K4InformationAction(new K4LanguageElement('L_BANNEDUSERIP', $ban['reason'], ($ban['expiry'] == 0 ? $_LANG['L_YOURDEATH'] : strftime("%m/%d/%Y", bbtime($ban['expiry']))) ), 'content', FALSE);
			}
		}
	}

	function getDependencies() {
		return array('login');
	}

	function getId() {
		return 'bannedusers';
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
			
			if (!$request['user']->isMember()) {
				// Oops, trying to login when already logged in

				$action = new K4InformationAction(new K4LanguageElement('L_NEEDLOGGEDIN'), 'content');
			} else {
				k4_set_logout($request['dba'], $request['user']);
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
		global $_LANG, $_CONFIG, $_SETTINGS, $_USERGROUPS, $_STYLESETS;

		$logout = &new FAUrl($_SERVER['REQUEST_URI']);
		//$logout->path = FALSE;
		$logout->args['logout'] = 'yes';
		
		$styleset		= ($request['user']->isMember()) ? $request['user']->get('styleset') : 'Descent';
		
		$templateset	= intval($_STYLESETS[$styleset]['use_templateset']) == 0 ? (($request['user']->isMember()) ? $request['user']->get('templateset') : $styleset) : $styleset;
		$imageset		= intval($_STYLESETS[$styleset]['use_imageset']) == 0 ? (($request['user']->isMember()) ? $request['user']->get('imageset') : $styleset) : $styleset;
		
		$request['user']->set('templateset', $templateset);
		
		if($request['user']->get('spider') == TRUE || isset($_REQUEST['archive']))
			$templateset = 'Archive';

		// set the main file
		// *** IF SOMEONE IS GOING TO MAKE A PORTAL, $request['template_file'] IS THE
		// *** VARIABLE TO CHANGE
		$request['template_file'] = BB_BASE_DIR . "/templates/$templateset/{$this->_filename}";
		
		// Load k4 custom compilers
		$compiler		= $request['template']->getTemplateCompiler();
		$compiler->loadCompilers(K4_BASE_DIR . '/compilers/');
		
		// Set Theme specific template variables
		$request['template']->setVar('IMG_DIR', $imageset);
		$request['template']->setVar('TPL_DIR', $templateset);
		
		// Set the CSS to the template
		get_cached_styleset($request, $styleset, $_SETTINGS['styleset']);

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
	
	function __construct($message, $block_id, $show_button = TRUE, $url = '', $timeout = 0) {
		$this->_message		= $message;
		$this->_block_id	= $block_id;
		$this->_show_button = $show_button;
		$this->_url			= $url;
		$this->_timeout		= $timeout;
	}

	function execute(&$request) {
		
		$request['template_file'] = BB_BASE_DIR . "/templates/". $request['user']->get('templateset') ."/information_base.html";

		$js = '';
		
		$this->_url			= str_replace('&amp;', '&', $this->_url);

		if ($this->_timeout && $this->_url) {
			$request['template']->setVarArray(
											array(
												'redirect_url' => $this->_url, 
												'redirect_time' => intval($this->_timeout * 1000),
												'redirect_html' => '<meta http-equiv="refresh" content="'. intval($this->_timeout) .';url='. $this->_url .'" />',
											));
		}
		
		if(!$request['template']->getVar('current_location')) {
			
			global $_LANG;
			
			$request['template']->setVar('current_location', $_LANG['L_INFORMATION']);
		}
		
		$message = is_a($this->_message, 'K4LanguageElement') ? $this->_message->__toString() : $this->_message;
		
		$request['template']->setVar('information', $message);
		$request['template']->setVisibility('info_back_button', $this->_show_button);		
		$request['template']->setFile('content', 'information.html'); //$this->_block_id
	}
}

class K4CloseBoardFilter extends FAFilter {
	function execute(&$action, &$request) {
		
		if(intval($request['template']->getVar('bbactive')) == 0) {
			
			global $_URL;
			
			// current filename and argument
			$file	= !isset($_URL->file) || !$_URL->file ? 'index.php' : $_URL->file;
			$act	= !isset($_URL->args['act']) ? '' : $_URL->args['act'];
			
			// previous argument
			$url		= new FAUrl(referer());
			$prev_act	= isset($url->args['act']) ? $url->args['act'] : '';
			
			// check
			if($request['user']->get('perms') < SUPERADMIN && ($file != 'admin.php' && $act != 'login' && $prev_act != 'login')) {
			
				$action = new K4InformationAction($request['template']->getVar('bbclosedreason'), 'content', FALSE);
				return $action->execute($request);
			}
		}		
	}

	function getDependencies() {
		return array('generalinfo');
	}
}

class K4MasMailFilter extends FAFilter {
	function execute(&$action, &$request) {
		global $_DATASTORE;
		
		if(isset($_DATASTORE['massmail']) && is_array($_DATASTORE['massmail'])) {
			
			$maxid = $_DATASTORE['massmail']['startid'] + EMAIL_INTERVAL;

			$users = $request['dba']->executeQuery("SELECT name, email FROM ". K4USERS ." WHERE id >= ". intval($_DATASTORE['massmail']['startid']) ." AND id < ". intval($maxid));
			
			if($users->numrows() > 0) {
				
				$bcc	= '';
				$to		= '';

				/* Send out a specific frequency of emails */
				while($users->next()) {
					$user = $users->current();

					if($user['email'] != '') {
						email_user($user['email'], $_DATASTORE['massmail']['subject'], $_DATASTORE['massmail']['message'], $_DATASTORE['massmail']['from']);
//						if($to != '') {
//							$bcc .= $user['email'] .", ";
//						} else {
//							$to = $user['email'];
//						}
					}
				}
				
//				$bcc = $bcc != '' ? "\nBcc:". trim(trim($bcc), ',') : '';
//				email_user($to, $_DATASTORE['massmail']['subject'], $_DATASTORE['massmail']['message'], $_DATASTORE['massmail']['from'], $bcc);

				/* Change the properties of the massmail */
				$_DATASTORE['massmail']['startid'] = $maxid;
					
				// update the datastore
				$update = $request['dba']->prepareStatement("UPDATE ". K4DATASTORE ." SET data = ? WHERE varname = 'massmail'");
				$update->setString(1, serialize($_DATASTORE['massmail']));
				$update->executeUpdate();

			} else {
				$request['dba']->executeUpdate("DELETE FROM ". K4DATASTORE ." WHERE varname = 'massmail'");
			}
			
			reset_cache('datastore');

		}		
	}

	function getDependencies() {
		return array('dba', 'template');
	}

	function getId() {
		return 'massmail';
	}
}

?>