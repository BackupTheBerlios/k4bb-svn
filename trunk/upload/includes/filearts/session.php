<?php
/**
* k4 Bulletin Board, session.php
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

if (!defined('FILEARTS'))
	return;

class FASession extends FAObject {
	var $_is_new;
	
	var $_read_stmt;
	var $_write_stmt;
	var $_update_stmt;
	var $_destroy_stmt;
	var $_user_stmt;
	var $_gc_stmt;
	
	var $_dba;

	function __construct(&$dba, $table) {
		$this->_is_new = TRUE;

		$this->_read_stmt		= $dba->prepareStatement("SELECT * FROM `$table` WHERE id=? GROUP BY user_id ORDER BY seen DESC LIMIT 1");
		$this->_write_stmt		= $dba->prepareStatement("INSERT INTO `$table` (id, seen, name, user_id, user_agent, data, location_file, location_act, location_id) VALUES(?,?,?,?,?,?,?,?,?)");
		$this->_update_stmt		= $dba->prepareStatement("UPDATE `$table` SET name=?,user_id=?,data=?,seen=?,user_agent=?,location_file=?,location_act=?,location_id=? WHERE id=?");
		$this->_destroy_stmt	= $dba->prepareStatement("DELETE FROM `$table` WHERE sess_id=?");
		$this->_gc_stmt			= $dba->prepareStatement("DELETE FROM `$table` WHERE seen<?");
		
		session_set_save_handler(array(&$this,'open'), array(&$this,'close'), array(&$this,'read'), array(&$this,'write'), array(&$this,'destroy'), array(&$this,'gc'));
		session_start();
	}

	function open($dirname, $sessid) {
		return TRUE;
	}

	function close() {
		return TRUE;
	}

	function read($sessid) {
		$data = '';

		$this->_read_stmt->setString(1, $sessid);
		
		$result = $this->_read_stmt->executeQuery();
		
		if ($result->next()) {
			//The session already exists
			$this->_is_new = FALSE;
			
			$data = $result->get('data');
		}

		return $data;
	}

	function write($sessid, $data) {
		
		global $_URL;

		if ($this->isNew()) {			
			//(id, seen, name, user_id, user_agent, data, location_file, location_act, location_id)
			$this->_write_stmt->setString(1,	$sessid);
			$this->_write_stmt->setInt(2,		time());
			$this->_write_stmt->setString(3,	$_SESSION['user']->get('name'));
			$this->_write_stmt->setInt(4,		$_SESSION['user']->get('id'));
			$this->_write_stmt->setString(5,	$_SERVER['HTTP_USER_AGENT']);
			$this->_write_stmt->setString(6,	$data);
			$this->_write_stmt->setString(7,	$_URL->file);
			$this->_write_stmt->setString(8,	@$_URL->args['act']);
			$this->_write_stmt->setInt(9,		@$_URL->args['id']);
			
			$this->_write_stmt->executeUpdate();
		} else {			
			//name=?,user_id=?,data=?,seen=?,user_agent=?,location_file=?,location_act=?,location_id=? WHERE id=?
			$this->_update_stmt->setString(1,	$_SESSION['user']->get('name'));
			$this->_update_stmt->setInt(2,		$_SESSION['user']->get('id'));
			$this->_update_stmt->setString(3,	serialize($data));
			$this->_update_stmt->setInt(4,		time());
			$this->_update_stmt->setString(5,	$_SERVER['HTTP_USER_AGENT']);
			$this->_update_stmt->setString(6,	$_URL->file);
			$this->_update_stmt->setString(7,	@$_URL->args['act']);
			$this->_update_stmt->setInt(8,		@$_URL->args['id']);
			$this->_update_stmt->setString(9,	$sessid);
			
			$this->_update_stmt->executeUpdate();
			
		}
					
		return TRUE;
	}

	function destroy($sessid) {
		$this->_destroy_stmt->setString(1, $sessid);
		$this->_destroy_stmt->executeUpdate();

		return TRUE;
	}

	function gc($maxlifetime) {
		$this->_gc_stmt->setInt(1, time() - $maxlifetime);
		$this->_gc_stmt->executeUpdate();

		return TRUE;
	}

	function isNew() {
		return $this->_is_new;
	}
}

?>