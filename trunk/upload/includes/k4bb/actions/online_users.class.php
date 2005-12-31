<?php
/**
* k4 Bulletin Board, online_users.class.php
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
* @author Peter Goodman
* @version $Id: online_users.class.php 152 2005-07-14 01:37:57Z Peter Goodman $
* @package k4-2.0-dev
*/

if(!defined('IN_K4')) {
	return;
}

class K4OnlineUsersIterator extends FAProxyIterator {
	var $dba;
	var $groups;
	var $bots;
	var $result;
	
	function K4OnlineUsersIterator(&$dba, $extra = NULL, &$result) {
		$this->__construct($dba, $extra, $result);
	}

	function __construct(&$dba, $extra = NULL, &$result) {
		global $_CONFIG, $_QUERYPARAMS, $_USERGROUPS;
		
		$this->groups	= $_USERGROUPS;
		$this->dba		= &$dba;
		$expired		= time() - ini_get('session.gc_maxlifetime');

		//$query			= "SELECT ". $_QUERYPARAMS['user'] . $_QUERYPARAMS['session'] ." FROM ". K4USERS ." u,". K4SESSIONS ." s WHERE s.seen >= $expired AND ((u.id = s.user_id) OR (s.user_id = 0 AND s.name <> '')) $extra GROUP BY s.name ORDER BY s.seen DESC"; // GROUP BY s.user_id
		$query			= "SELECT * FROM ". K4SESSIONS ." WHERE seen >= $expired AND ((user_id > 0) OR (user_id = 0 AND name <> '')) $extra GROUP BY name ORDER BY seen DESC";
		$this->result	= &$result;

		Globals::setGlobal('num_online_members', $this->result->numrows());
		Globals::setGlobal('num_online_invisible', 0);

		parent::__construct($this->result);
	}

	function current() {
		$temp = parent::current();
		
		if($temp['invisible'] == 1)
			Globals::setGlobal('num_online_invisible', Globals::getGlobal('num_online_invisible')+1);
		
		if($temp['user_id'] >= 0) {
			
			$group					= get_user_max_group($temp, $this->groups);
			
			$temp['color']			= !isset($group['color']) || $group['color'] == '' ? '000000' : $group['color'];
			$temp['font_weight']	= @$group['min_perm'] > MEMBER ? 'bold' : 'normal';
		}

		/* Should we free the result? */
		if(!$this->hasNext())
			$this->result->free();

		//if($temp['name'] != '') {
		//	if(((isset($temp['invisible']) && $temp['invisible'] == 0) || !isset($temp['invisible']))) {
				return $temp;
		//	}
		//}
	}
}

?>