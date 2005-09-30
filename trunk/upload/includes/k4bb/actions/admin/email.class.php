<?php
/**
* k4 Bulletin Board, email.class.php
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

			reset_cache(CACHE_DS_FILE);
				
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