<?php
/**
* k4 Bulletin Board, online_users.class.php
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
	
	function K4OnlineUsersIterator(&$dba, $extra = NULL, $result = FALSE) {
		$this->__construct($dba, $extra, $result);
	}

	function __construct(&$dba, $extra = NULL, $result = FALSE) {
		global $_CONFIG, $_QUERYPARAMS, $_USERGROUPS;
		
		$this->groups	= $_USERGROUPS;
		$this->dba		= &$dba;
		$expired		= time() - ini_get('session.gc_maxlifetime');

		//$query			= "SELECT ". $_QUERYPARAMS['user'] . $_QUERYPARAMS['session'] ." FROM ". K4USERS ." u,". K4SESSIONS ." s WHERE s.seen >= $expired AND ((u.id = s.user_id) OR (s.user_id = 0 AND s.name <> '')) $extra GROUP BY s.name ORDER BY s.seen DESC"; // GROUP BY s.user_id
		$query			= "SELECT * FROM ". K4SESSIONS ." WHERE seen >= $expired AND ((user_id > 0) OR (user_id = 0 AND name <> '')) $extra GROUP BY name ORDER BY seen DESC";
		$this->result	= !$result ? $this->dba->executeQuery($query) : $result;

		Globals::setGlobal('num_online_members', $this->result->numRows());
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

		if($temp['name'] != '' && ((isset($temp['invisible']) && $temp['invisible'] == 0) || !isset($temp['invisible'])))
			return $temp;
	}
}

?>