<?php
/**
* k4 Bulletin Board, beta5_to_beta6.php
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