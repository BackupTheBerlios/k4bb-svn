<?php
/**
* k4 Bulletin Board, beta4_to_beta5.php
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

require "includes/filearts/filearts.php";
require "includes/k4bb/k4bb.php";

class K4DefaultAction extends FAAction {
	function execute(&$request) {
		
		if(is_a($request['dba'], 'SQLiteConnection')) {
			$banned_users_tbl	= "CREATE TABLE k4_bannedusers (id INTEGER UNSIGNED,user_id INTEGER UNSIGNED NOT NULL DEFAULT 0,user_name VARCHAR(30) NOT NULL DEFAULT '',user_ip VARCHAR(114) NOT NULL DEFAULT '',reason TEXT,expiry INTEGER UNSIGNED NOT NULL DEFAULT 0,PRIMARY KEY(id))";
		} else {
			$banned_users_tbl	= "DROP TABLE IF EXISTS k4_bannedusers;CREATE TABLE k4_bannedusers (id INT UNSIGNED NOT NULL AUTO_INCREMENT,user_id INT UNSIGNED NOT NULL DEFAULT 0,user_name VARCHAR(30) NOT NULL DEFAULT '',user_ip VARCHAR(114) NOT NULL DEFAULT '',reason TEXT,expiry INT UNSIGNED NOT NULL DEFAULT 0,PRIMARY KEY(id))";
		}

		$request['dba']->alterTable(K4POSTS, "ADD moved_old_post_id INT UNSIGNED NOT NULL DEFAULT 0");
		$request['dba']->alterTable(K4POSTS, "ADD moved_new_post_id INT UNSIGNED NOT NULL DEFAULT 0");
		$request['dba']->alterTable(K4POSTS, "ADD moved_old_post_id INT UNSIGNED NOT NULL DEFAULT 0");
		$request['dba']->alterTable(K4USERS, "ADD banned INT UNSIGNED NOT NULL DEFAULT 0");
		$request['dba']->alterTable(K4USERINFO, "DROP banned");
		$request['dba']->alterTable(K4USERINFO, "DROP ban_time");
		$request['dba']->executeUpdate($banned_users_tbl);
		
		$action = new K4InformationAction('Successfully updated your k4 Bulletin Board version from BETA 4 to BETA 5. <strong>Please remove this file immediately</strong>. If you wish to change your version number, go into /includes/k4bb/common.php and switch it on line 38.', 'content', FALSE);

		return $action->execute($request);
	}
}

$app = &new K4Controller('forum_base.html');
$app->setAction('', new K4DefaultAction);
$app->setDefaultEvent('');

$app->execute();

?>