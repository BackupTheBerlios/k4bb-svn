<?php
/**
* k4 Bulletin Board, user.inc.php
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
* @version $Id: user.php 138 2005-07-01 15:56:08Z Peter Goodman $
* @package k42
*/

if (!defined('FILEARTS'))
	return;

class FAUserValidator extends FAObject {

	function validateLoginKey($info) {
		return FALSE;
	}
}

class FAUserFactory extends FAObject {
	function &createGuest() {
		$user = &new FAUser();
		return $user;
	}

	function &createMember($info) {
		$member = &new FAMember($info);
		return $member;
	}

	function &getUser(&$validator) {
		$info = $validator->validateLoginKey();
		
		if (is_array($info))
			$user = &$this->createMember($info);
		else
			$user = &$this->createGuest();

		return $user;
	}
}

class FAUser extends FAObject {
	var $_info = array();

	function __construct($info) {
		assert(is_array($info));

		$this->setInfo($info);
	}

	function setInfo($info) {
		$this->_info = $info;
	}

	function get($key) {
		$value = '';

		if (isset($this->_info[$key]))
			$value = $this->_info[$key];

		return $value;
	}

	function set($key, $value) {
		$this->_info[$key] = $value;
	}

	function getInfoArray() {
		return $this->_info;
	}

	function isMember() {
		return FALSE;
	}

	function updateInfo($info) {
		assert(is_array($info));

		$this->_info = $info + $this->_info;
	}
}

class FAMember extends FAUser {

	function isMember() {
		return TRUE;
	}
}

?>