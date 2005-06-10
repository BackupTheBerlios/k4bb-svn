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
* @version $Id$
* @package k42
*/

if (!defined(IN_K4))
	return;

function k4_set_login(&$dba, &$user, $remember) {
	// TODO: change last_seen in k4_users to last_login
	$stmt = &$dba->prepareStatement("UPDATE ".K4USERS." SET seen=?,last_seen=?,priv_key=? WHERE id=?");

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

		setcookie(K4COOKIE_ID, $user->get('id'), $expire);
		setcookie(K4COOKIE_KEY, $priv_key, $expire);
	}

	// TODO: load the user's language

	$_SESSION['user'] = &$user;
}

function k4_set_logout(&$dba, &$user) {
	$stmt = &$dba->prepareStatement("UPDATE ".K4USERS." SET seen=?,priv_key='' WHERE id=?");

	$seen = time();

	$stmt->setInt(1, $seen);
	$stmt->setInt(2, $user->get('id'));
	$stmt->executeUpdate();

	$expire = time() - (3600 * 24);

	setcookie(K4COOKIE_ID, '', $expire);
	setcookie(K4COOKIE_KEY, '', $expire);
	
	//unset($_SESSION['user']);

	$user = new K4Guest();
}

class K4Guest extends FAUser {
	function __construct() {
		parent::__construct(array('name' => '', 'email' => '', 'id' => 0, 'perms' => 1, 'styleset' => ''));
	}
}

class K4Member extends FAMember {
}

class K4UserManager extends FAObject {
	var $_info;

	function __construct(&$dba) {
		global $_QUERYPARAMS;

		$this->_info = &$dba->prepareStatement("SELECT {$_QUERYPARAMS['user']}{$_QUERYPARAMS['userinfo']} FROM ".K4USERS." u LEFT JOIN ".K4USERINFO." ui ON u.id=ui.user_id WHERE u.id=?");
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

	function __construct(&$dba) {
		global $_QUERYPARAMS;

		$this->_stmt = &$dba->prepareStatement("SELECT {$_QUERYPARAMS['user']}{$_QUERYPARAMS['userinfo']} FROM ".K4USERS." u LEFT JOIN ".K4USERINFO." ui ON u.id=ui.user_id WHERE u.id=? AND u.priv_key=?");
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

	function __construct(&$dba) {
		global $_QUERYPARAMS;

		$this->_stmt = &$dba->prepareStatement("SELECT {$_QUERYPARAMS['user']}{$_QUERYPARAMS['userinfo']} FROM ".K4USERS." u LEFT JOIN ".K4USERINFO." ui ON u.id=ui.user_id WHERE `name`=? AND `pass`=?");
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
		return new K4Guest(array('name' => '', 'email' => '', 'id' => 0, 'perms' => 1, 'styleset' => ''));
	}

	function &createMember($info) {
		$info['templateset'] = 'Descent';
		$info['styleset'] = 'Descent';
		$info['imageset'] = 'Descent';

		return new K4Member($info);
	}
}

?>