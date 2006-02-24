<?php
/**
* k4 Bulletin Board, session.php
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
* @version $Id: session.php 158 2005-07-18 02:55:30Z Peter Goodman $
* @package k4bb
*/

if (!defined('FILEARTS'))
	return;

class Logger {
	function write($log) {
		if ($handle = fopen('log.txt', 'a')) {
			fwrite($handle, $log . "\n");
			fclose($handle);
		}
	}
}

class FASession extends FAObject {
	var $_is_new;
	
	var $_read_stmt;
	var $_write_stmt;
	var $_update_stmt;
	var $_destroy_stmt;
	var $_user_stmt;
	var $_gc_stmt;
	
	var $_dba;

	function __construct(&$dba) {
		$this->_is_new = TRUE;

		$this->_read_stmt		= $dba->prepareStatement("SELECT * FROM ". K4SESSIONS ." WHERE id=? AND user_ip=? GROUP BY user_id ORDER BY seen DESC LIMIT 1");
		$this->_write_stmt		= $dba->prepareStatement("INSERT INTO ". K4SESSIONS ." (id, seen, name, user_id, usergroups, invisible, user_agent, data, location_file, location_act, location_id, user_ip) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)");
		$this->_update_stmt		= $dba->prepareStatement("UPDATE ". K4SESSIONS ." SET name=?,user_id=?,usergroups=?,invisible=?,data=?,seen=?,user_agent=?,location_file=?,location_act=?,location_id=? WHERE id=? AND user_ip=?");
		$this->_update_user_stmt= $dba->prepareStatement("UPDATE ". K4USERS ." SET last_seen=?,seen=?,ip=? WHERE id=?");
		$this->_destroy_stmt	= $dba->prepareStatement("DELETE FROM ". K4SESSIONS ." WHERE sess_id=?");
		$this->_gc_stmt			= $dba->prepareStatement("DELETE FROM ". K4SESSIONS ." WHERE seen<?");
	}

	function start(&$dba) {
		static $instance;

		if (empty($instance)) {
			$instance = array(new FASession($dba));

			
			//session_start();
		
			Logger::write('Started session');
		} else {
			trigger_error("Session already started", E_USER_NOTICE);
		}

		return $instance[0];
	}
	
	function null() {
		return TRUE;
	}

	function open($dirname, $sessid) {
		Logger::write('Opened session.');
		return TRUE;
	}

	function close() {
		Logger::write('Closed session.');
		return TRUE;
	}

	function read() {
		$data = '';
		
		Logger::write('Reading session...');

		$this->_read_stmt->setString(1, session_id());
		$this->_read_stmt->setString(2, USER_IP);
		
		$result = $this->_read_stmt->executeQuery();
		
		Logger::write("\tRead session..");

		if ($result->next()) {
			//The session already exists
			$this->_is_new = FALSE;
			$data = $result->get('data');

			Logger::write("\tThis session is new.");
		}
		
		session_decode($data);
		
		return TRUE;
	}

	function write() {
		
		global $_URL, $_SPIDERAGENTS, $_SPIDERS;
		
		Logger::write('Writing session...');

		if(isset($_SESSION['user'])) {
			
			if($_SESSION['user']->isMember()) {
				$this->_update_user_stmt->setInt(1, $_SESSION['user']->get('seen'));
				$this->_update_user_stmt->setInt(2, time());
				$this->_update_user_stmt->setString(3, USER_IP);
				$this->_update_user_stmt->setInt(4, $_SESSION['user']->get('id'));
				$this->_update_user_stmt->executeUpdate();
			
				Logger::write("\tUpdated user");
			} else {
				$_SESSION['user']->set('last_seen', $_SESSION['user']->get('seen'));
				$_SESSION['user']->set('seen', time());
			}
			
//			// is this a search engine spider?
//			if(!isset($_SESSION['user']) || !$_SESSION['user']->isMember()) {
//				if(preg_match("~(". $_SPIDERAGENTS .")~is", USER_AGENT)) {
//					
//					Logger::write("\tRecognized bot.");
//					
//					foreach($_SPIDERS as $spider) {
//						if(eregi($spider['useragent'], USER_AGENT)) {
//							$_SESSION['user']->set('name', $spider['spidername']);
//							$_SESSION['user']->set('id', 0);
//							$_SESSION['user']->set('perms', ($spider['allowaccess'] == 1 ? 1 : -1));
//						}
//					}
//				}
//			}
		}
		
		if ($this->isNew()) {
			
			Logger::write("\tThis session is new");

			$this->_write_stmt->setString(1,	session_id());
			$this->_write_stmt->setInt(2,		time());
			$this->_write_stmt->setString(3,	$_SESSION['user']->get('name'));
			$this->_write_stmt->setInt(4,		$_SESSION['user']->get('id'));
			$this->_write_stmt->setString(5,	$_SESSION['user']->get('usergroups'));
			$this->_write_stmt->setInt(6,		$_SESSION['user']->get('invisible'));
			$this->_write_stmt->setString(7,	USER_AGENT);
			$this->_write_stmt->setString(8,	session_encode());
			$this->_write_stmt->setString(9,	$_URL->file);
			$this->_write_stmt->setString(10,	isset($_REQUEST['act']) ? $_REQUEST['act'] : '');
			$this->_write_stmt->setInt(11,		isset($_REQUEST['id']) ? $_REQUEST['id'] : 0);
			$this->_write_stmt->setString(12,	USER_IP);
			$this->_write_stmt->executeUpdate();

			Logger::write("\t\Wrote new session.");
		} else {
			$this->_update_stmt->setString(1,	$_SESSION['user']->get('name'));
			$this->_update_stmt->setInt(2,		$_SESSION['user']->get('id'));
			$this->_update_stmt->setString(3,	$_SESSION['user']->get('usergroups'));
			$this->_update_stmt->setInt(4,		$_SESSION['user']->get('invisible'));
			$this->_update_stmt->setString(5,	session_encode());
			$this->_update_stmt->setInt(6,		time());
			$this->_update_stmt->setString(7,	USER_AGENT);
			$this->_update_stmt->setString(8,	$_URL->file);
			$this->_update_stmt->setString(9,	@$_URL->args['act']);
			$this->_update_stmt->setInt(10,		@$_URL->args['id']);
			$this->_update_stmt->setString(11,	session_id());
			$this->_update_stmt->setString(12,	USER_IP);
			$this->_update_stmt->executeUpdate();

			Logger::write("\tUpdated session.");
		}

		Logger::write('Wrote session.');

		return TRUE;
	}

	function destroy($sessid) {
		$this->_destroy_stmt->setString(1, session_id());
		$this->_destroy_stmt->executeUpdate();
		$this->_is_new = FALSE;
		Logger::write('Destroyed session.');
		return TRUE;
	}

	function gc() {
		$this->_gc_stmt->setInt(1, time() - ini_get('session.gc_maxlifetime'));
		$this->_gc_stmt->executeUpdate();
		Logger::write('Garbage collected.');
		return TRUE;
	}

	function isNew() {
		Logger::write('[Checking if session is new]');
		return $this->_is_new;
	}
}

?>