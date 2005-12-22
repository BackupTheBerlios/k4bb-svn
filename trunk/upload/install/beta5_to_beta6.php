<?php
/**
* k4 Bulletin Board, beta5_to_beta6.php
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
* @package k4-2.0-dev
*/

if(!file_exists(dirname(__FILE__) .'/includes/filearts/filearts.php'))
	exit("You have put this updater in the wrong directory. Make sure that it is in the k4 Bulletin Board root directory. (where you access the board from online)");

require "includes/filearts/filearts.php";
require "includes/k4bb/k4bb.php";

class K4DefaultAction extends FAAction {
	function execute(&$request) {
		
		$request['dba']->alterTable(K4POSTS, "ADD total_attachments INT UNSIGNED NOT NULL DEFAULT 0");
		$request['dba']->alterTable(K4POSTS, "ADD attachments INT UNSIGNED NOT NULL DEFAULT 0");
		$request['dba']->alterTable(K4ATTACHMENTS, "ADD post_id INT UNSIGNED NOT NULL DEFAULT 0");
		$request['dba']->executeUpdate("UPDATE k4_topics set total_attachments=attachments");
		$request['dba']->alterTable(K4ATTACHMENTS, "ADD forum_id INT UNSIGNED NOT NULL DEFAULT 0");
		$request['dba']->alterTable(K4ATTACHMENTS, "ADD message_id INT UNSIGNED NOT NULL DEFAULT 0");
		
		$action = new K4InformationAction('Successfully updated your k4 Bulletin Board version from BETA 5 to BETA 6. <strong>Please remove this file immediately</strong>. If you wish to change your version number, go into /includes/k4bb/common.php and switch it on line 38.', 'content', FALSE);

		return $action->execute($request);
	}
}

$app = &new K4Controller('forum_base.html');
$app->setAction('', new K4DefaultAction);
$app->setDefaultEvent('');

$app->execute();

?>