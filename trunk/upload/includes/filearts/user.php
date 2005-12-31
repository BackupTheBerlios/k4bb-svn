<?php
/**
* k4 Bulletin Board, user.inc.php
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
	function createGuest() {
		$user = &new FAUser();
		return $user;
	}

	function createMember($info) {
		$member = &new FAMember($info);
		return $member;
	}

	function getUser(&$validator) {
		$info = $validator->validateLoginKey();
		
		if (is_array($info))
			$user = $this->createMember($info);
		else
			$user = $this->createGuest();

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