<?php
/**
* k4 Bulletin Board, email.class.php
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

class AdminEmailUsers extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			
			global $_DATASTORE;
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_EMAILUSERS');
			$request['template']->setVar('misc_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/misc.html');

			if(isset($_DATASTORE['massmail'])) {
				$action = new K4InformationAction(new K4LanguageElement('L_EMAILINPROGRESS'), 'content', FALSE);
				return $action->execute($request);
			}
			
			global $_URL;

			$verify_url					= new FAUrl($_URL->__toString());
			$verify_url->args			= array();
			$verify_url->file			= FALSE;
			$verify_url->anchor			= FALSE;
			$verify_url->scheme			= FALSE;
			$verify_url->path			= FALSE;
			$verify_url->host			= preg_replace('~www\.~i', '', $verify_url->host);

			$request['template']->setFile('content', 'email_users.html');
			$request['template']->setVar('email_from', substr($verify_url->__toString(), 0, -1));

		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminSetSendEmails extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			
			global $_DATASTORE;
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_EMAILUSERS');
			$request['template']->setVar('misc_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/misc.html');

			if(isset($_DATASTORE['massmail'])) {
				$action = new K4InformationAction(new K4LanguageElement('L_EMAILINPROGRESS'), 'content', FALSE);
				return $action->execute($request);
			}
			
			if(!isset($_REQUEST['subject']) || $_REQUEST['subject'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTMAILSUBJECT'), 'content', TRUE);
				return $action->execute($request);
			}

			if(!isset($_REQUEST['message']) || $_REQUEST['message'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTMAILMESSAGE'), 'content', TRUE);
				return $action->execute($request);
			}

			$from		= isset($_REQUEST['from']) && $_REQUEST['from'] != '' ? $_REQUEST['from'] : 'noreply';
			$subject	= $_REQUEST['subject'];
			$message	= preg_replace("~(\r\n|\r|\n)~i", "\n", $_REQUEST['message']);
			
			// set where to start the userids to email in the datastore
			$update = $request['dba']->prepareStatement("INSERT INTO ". K4DATASTORE ." (varname, data) VALUES (?,?)");
			
			$update->setString(1, 'massmail');
			$update->setString(2, serialize(array( 'startid'=>0,'from'=>$from,'subject'=>$subject,'message'=>$message )));
		
			$update->executeUpdate();

			reset_cache('email_queue');
				
			// success
			$action = new K4InformationAction(new K4LanguageElement('L_EMAILSSENTTOUSERS'), 'content', FALSE);
			return $action->execute($request);

		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

?>