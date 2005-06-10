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
* @version $Id: online_users.class.php,v 1.8 2005/05/26 18:35:44 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	exit;
}

class K4OnlineUsersIterator extends FAProxyIterator {
	var $dba;
	var $groups;
	var $bots;
	var $result;
	
	function __construct(&$dba, $extra = NULL) {
		global $_CONFIG, $_QUERYPARAMS, $_USERGROUPS;
		
		$this->groups	= $_USERGROUPS;
		$this->dba		= &$dba;
		$expired		= time() - ini_get('session.gc_maxlifetime');

		$query			= "SELECT ". $_QUERYPARAMS['user'] . $_QUERYPARAMS['session'] ." FROM ". K4USERS ." u,". K4SESSIONS ." s WHERE s.seen >= $expired AND u.id=s.user_id $extra GROUP BY s.user_id ORDER BY s.seen DESC";
		
		$this->result	= &$this->dba->executeQuery($query);

		Globals::setGlobal('num_online_members', $this->result->numRows());
		Globals::setGlobal('num_online_invisible', 0);

		parent::__construct($this->result);
	}

	function &current() {
		$temp = parent::current();
		
		if($temp['invisible'] == 1)
			Globals::setGlobal('num_online_invisible', Globals::getGlobal('num_online_invisible')+1);
		
		if($temp['id'] != 0) {
			
			$group					= get_user_max_group($temp, $this->groups);
			
			$temp['color']			= !isset($group['color']) || $group['color'] == '' ? '000000' : $group['color'];
			$temp['font_weight']	= @$group['min_perm'] > MEMBER ? 'bold' : 'normal';
		}

		/* Should we free the result? */
		if($this->row == $this->size-1)
			$this->result->free();

		if($temp['name'] != '' && ((isset($temp['invisible']) && $temp['invisible'] == 0) || !isset($temp['invisible'])))
			return $temp;
	}
}

?>