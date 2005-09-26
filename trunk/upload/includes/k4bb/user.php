<?php
/**
* k4 Bulletin Board, user.php
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
* @version $Id: user.php 158 2005-07-18 02:55:30Z Peter Goodman $
* @package k42
*/

if (!defined(IN_K4))
	return;

/**
 * Email a user with the proper noreply email address
 */
function email_user($to, $subject, $message, $from = 'noreply', $headers = "") {
	global $_URL, $_SETTINGS;

	$verify_url					= new FAUrl($_URL->__toString());
	$verify_url->args			= array();
	$verify_url->file			= FALSE;
	$verify_url->anchor			= FALSE;
	$verify_url->scheme			= FALSE;
	$verify_url->path			= FALSE;
	$verify_url->host			= preg_replace('~www\.~i', '', $verify_url->host);
			
	return @mail($to, $subject, $message, "From: \"". $_SETTINGS['bbtitle'] ." Forums\" <". $from ."@". substr($verify_url->__toString(), 0, -1) .">" . $headers);
}

/**
 * Set a user a logged in
 */
function k4_set_login(&$dba, &$user, $remember) {

	// TODO: change last_seen in k4_users to last_login
	$stmt = &$dba->prepareStatement("UPDATE ". K4USERS ." SET seen=?,last_seen=?,priv_key=? WHERE id=?");

	$seen = time();
	$priv_key = md5(uniqid(microtime()));

	$stmt->setInt(1, $seen);
	$stmt->setInt(2, $user->get('seen'));
	$stmt->setString(3, $priv_key);
	$stmt->setInt(4, $user->get('id'));
	$stmt->executeUpdate();

	$user->updateInfo(array('seen' => $seen, 'last_seen' => $user->get('seen'), 'priv_key' => $priv_key));

	if ($remember) {
		$expire = time() + (3600 * 24 * 30);

		setcookie(K4COOKIE_ID, $user->get('id'), $expire, get_domain());
		setcookie(K4COOKIE_KEY, $priv_key, $expire, get_domain());
	}

	$_SESSION['user'] = &$user;
}

function k4_set_logout(&$dba, &$user) {
	$stmt = &$dba->prepareStatement("UPDATE ". K4USERS ." SET seen=?,priv_key='' WHERE id=?");

	$seen = time();

	$stmt->setInt(1, $seen);
	$stmt->setInt(2, $user->get('id'));
	$stmt->executeUpdate();

	$expire = time() - (3600 * 24);

	setcookie(K4COOKIE_ID, '', $expire, get_domain());
	setcookie(K4COOKIE_KEY, '', $expire, get_domain());
	
	//unset($_SESSION['user']);

	$user = new K4Guest();
}

class K4Guest extends FAUser {
	function guestInfo() {
		global $_SPIDERS, $_SPIDERAGENTS;
		
		$info	= array('name' => '', 'email' => '', 'id' => 0, 'usergroups' => serialize(array()), 'perms' => 1, 'styleset' => '', 'topicsperpage' => 0, 'postsperpage' => 0, 'viewavatars' => 0,'viewflash'=>1,'viewemoticons'=>1,'viewsigs'=>1,'viewavatars'=> 1,'viewimages'=>1,'viewcensors'=>1,'invisible'=>0,'seen'=>time(),'last_seen'=>time());
		
		/* Check if this person is a search engine */
		if(preg_match("~(". $_SPIDERAGENTS .")~is", USER_AGENT)) {
			foreach($_SPIDERS as $spider) {
				if(eregi($spider['useragent'], USER_AGENT)) {
					$info['name']	= $spider['spidername'];
					$info['perms']	= $spider['allowaccess'] == 1 ? 1 : -1;
				}
			}
		}

		return $info;
	}
	function __construct() {
		parent::__construct($this->guestInfo());
	}
}

class K4Member extends FAMember {
}

class K4UserManager extends FAObject {
	var $_info;
	
	function K4UserManager(&$dba) {
		$this->__construct($dba);
	}

	function __construct(&$dba) {
		global $_QUERYPARAMS;
		
		$this->_info = &$dba->prepareStatement("SELECT {$_QUERYPARAMS['user']}{$_QUERYPARAMS['userinfo']}{$_QUERYPARAMS['usersettings']} FROM ". K4USERS ." u, ". K4USERINFO ." ui, ". K4USERSETTINGS ." us WHERE u.id=ui.user_id AND us.user_id=u.id AND u.id=? LIMIT 1");
	}

	function getInfo($id) {
		$ret = FALSE;

		$this->_info->setInt(1, $id);

		$result = &$this->_info->executeQuery();

		if ($result->next())
			$ret = $result->current();

		return $ret;
	}
}

class K4CookieValidator extends FAUserValidator {
	var $_stmt;
	
	function K4CookieValidator(&$dba) {
		$this->__construct($dba);
	}

	function __construct(&$dba) {
		global $_QUERYPARAMS;

		$this->_stmt = &$dba->prepareStatement("SELECT {$_QUERYPARAMS['user']}{$_QUERYPARAMS['userinfo']}{$_QUERYPARAMS['usersettings']} FROM ". K4USERS ." u, ". K4USERINFO ." ui, ". K4USERSETTINGS ." us WHERE u.id=ui.user_id AND us.user_id=u.id AND u.id=? AND u.priv_key=? LIMIT 1");
	}

	function validateLoginKey() {
		$ret = FALSE;

		if (isset($_COOKIE[K4COOKIE_ID], $_COOKIE[K4COOKIE_KEY])) {
			
			$this->_stmt->setInt(1, $_COOKIE[K4COOKIE_ID]);
			$this->_stmt->setString(2, $_COOKIE[K4COOKIE_KEY]);

			$result = &$this->_stmt->executeQuery();

			if ($result->next())
				$ret = $result->current();
		}

		return $ret;
	}
}

class K4RequestValidator extends FAUserValidator {
	var $_stmt;
	
	function K4RequestValidator(&$dba) {
		$this->__construct($dba);
	}

	function __construct(&$dba) {
		global $_QUERYPARAMS;

		$this->_stmt = &$dba->prepareStatement("SELECT {$_QUERYPARAMS['user']}{$_QUERYPARAMS['userinfo']}{$_QUERYPARAMS['usersettings']} FROM ". K4USERS ." u, ". K4USERINFO ." ui, ". K4USERSETTINGS ." us WHERE u.id=ui.user_id AND us.user_id=u.id AND u.name=? AND u.pass=? LIMIT 1");
	}

	function validateLoginKey() {
		$ret = FALSE;

		if (isset($_POST['username'], $_POST['password'])) {
			$this->_stmt->setString(1, $_POST['username']);
			$this->_stmt->setString(2, md5($_POST['password']));

			$result = &$this->_stmt->executeQuery();

			if ($result->next())
				$ret = $result->current();
		}

		return $ret;
	}
}

class K4UserFactory extends FAUserFactory {

	function &createGuest() {
		$ret = &new K4Guest();

		return $ret;
	}

	function &createMember($info) {
		$ret = &new K4Member($info);
		return $ret;
	}
}

?>