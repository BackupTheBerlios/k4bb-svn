<?php
/**
* k4 Bulletin Board, beta4_to_beta5.php
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