<?php
/**
* k4 Bulletin Board, common.php
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
* @version $Id$
* @package k42
*/

class K4Url extends FAUrl {
	
	function K4Url() { }

	function getForumUrl($forum_id) {
		return 'viewforum.php?f='. intval($forum_id);
	}
	function getRedirectUrl($id) {
		return 'redirect.php?id='. intval($id);
	}
	function getTopicUrl($topic_id) {
		return 'viewtopic.php?id='. intval($topic_id);
	}
	function getPostUrl($post_id) {
		return 'findpost.php?id='. intval($post_id);
	}
	function getMemberUrl($member_id) {
		return 'member.php?id='. intval($member_id);
	}
	function getUserGroupUrl($group_id) {
		return 'usergroups.php?id='. intval($group_id);
	}
	function getGenUrl($file, $query) {
		return $file .'.php?'. $query;
	}
}

?>