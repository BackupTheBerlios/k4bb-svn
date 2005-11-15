<?php
/**
* k4 Bulletin Board, privmessages.class.php
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

if(!defined('IN_K4')) {
	return;
}

class K4ShowPMFolder extends FAAction {
	function execute(&$request) {
		
		global $_URL;

		$check = new K4PMCheckPerms();
		$check->execute($request);

		if(get_map( 'pm_messages', 'can_add', array()) > $request['user']->get('perms')) {
			no_perms_error($request, 'usercp_content');
			return TRUE;
		}

		k4_bread_crumbs($request['template'], $request['dba'], 'L_USERCONTROLPANEL');
		$request['template']->setFile('content', 'usercp.html');
		
		if(intval($request['template']->getVar('enablepms')) == 0) {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'usercp_content', FALSE);
			return $action->execute($request);
		}

		if(intval($request['template']->getVar('allowcustomfolders')) == 0) {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'usercp_content', FALSE);
			return $action->execute($request);
		}

		if(!isset($_REQUEST['folder']) || intval($_REQUEST['folder']) == 0) {
			$action = new K4InformationAction(new K4LanguageElement('L_BADPMFOLDER'), 'usercp_content', FALSE);
			return $action->execute($request);
		}

		$folder = $request['dba']->getRow("SELECT * FROM ". K4PMFOLDERS ." WHERE id = ". intval($_REQUEST['folder']));
	
		if(!is_array($folder) || empty($folder)) {
			$action = new K4InformationAction(new K4LanguageElement('L_BADPMFOLDER'), 'usercp_content', FALSE);
			return $action->execute($request);
		}

		if(intval($folder['user_id']) != $request['user']->get('id') && $folder['user_id'] != 0 && $folder['is_global'] == 0) {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'usercp_content', FALSE);
			return $action->execute($request);
		}
		
		foreach($folder as $key=>$val)
			$request['template']->setVar('folder_'. $key, $val);
		
		$num_pms			= $request['dba']->getValue("SELECT COUNT(*) FROM ". K4PRIVMESSAGES ." WHERE member_id = ". intval($request['user']->get('id')));
		$max_pms			= intval($request['template']->getVar('pmquota'));
		
		$resultsperpage		= $request['template']->getVar('pmsperpage');
		$num_results		= $request['dba']->getValue("SELECT COUNT(*) FROM ". K4PRIVMESSAGES ." WHERE member_id = ". intval($request['user']->get('id')) ." AND folder_id = ". intval($folder['id']));

		$num_pages			= @ceil($num_results / $resultsperpage);
		$page				= isset($_REQUEST['page']) && ctype_digit($_REQUEST['page']) && intval($_REQUEST['page']) > 0 ? intval($_REQUEST['page']) : 1;
		$start				= ($page - 1) * $resultsperpage;
		
		$request['template']->setVar('page', $page);

		$url				= &new FAUrl($_URL->__toString());
		
		$pager				= &new FAPaginator($url, $num_results, $page, $resultsperpage);

		if($num_results > $resultsperpage) {
			$request['template']->setPager('pms_pager', $pager);

			/* Create a friendly url for our pager jump */
			$page_jumper	= $url;
			$page_jumper->args['limit'] = $resultsperpage;
			$page_jumper->args['page']	= FALSE;
			$page_jumper->anchor		= FALSE;
			$request['template']->setVar('pagejumper_url', preg_replace('~&amp;~i', '&', $page_jumper->__toString()));
		}
		
		/* Outside valid page range, redirect */
		if(!$pager->hasPage($page) && $num_pages > 0) {
			$action = new K4InformationAction(new K4LanguageElement('L_PASTPAGELIMIT'), 'content', FALSE, 'member.php?act=usercp&view=pmfolder&folder='. $folder['id'], 3);
			return $action->execute($request);
		}
		
		$result		= $request['dba']->executeQuery("SELECT * FROM ". K4PRIVMESSAGES ." WHERE member_id = ". intval($request['user']->get('id')) ." AND folder_id = ". intval($folder['id']) ." ORDER BY created DESC LIMIT $start,$resultsperpage");
		$it 		= &new K4PrivMessageIterator($request['dba'], $result, $request['template']->getVar('IMG_DIR'), $request['template']->getVar('pmrepliesperpage') );

		$request['template']->setVar('pm_usedpercent', ceil(($num_pms / $max_pms) * 100));
	
		$request['template']->setVar('L_PMSGSTATS', sprintf($request['template']->getVar('L_PMSGSTATS'), $num_pms, $max_pms));
		$request['template']->setFile('usercp_content', 'pm_listmessages.html');
		$request['template']->setList('pmessages', $it);
		
		return TRUE;
	}
}

class K4CreatePMFolder extends FAAction {
	function execute(&$request) {
		$check = new K4PMCheckPerms();
		$check->execute($request);

		if(intval($request['template']->getVar('allowcustomfolders')) == 0) {
			no_perms_error($request);
			return TRUE;
		}

		if(get_map( 'pm_customfolders', 'can_add', array()) > $request['user']->get('perms')) {
			no_perms_error($request, 'usercp_content');
			return TRUE;
		}

		$num_folders = $request['dba']->getValue("SELECT COUNT(*) FROM ". K4PMFOLDERS ." WHERE user_id = ". intval($request['user']->get('id')) ." AND is_global = 0");
		
		if($num_folders >= intval($request['template']->getVar('numcustomfolders'))) {
			$action = new K4InformationAction(new K4LanguageElement('L_REACHEDMAXPMFOLDERS'), 'usercp_content', FALSE);
			return $action->execute($request);
		}
		
		$result = $request['dba']->executeQuery("SELECT * FROM ". K4PMFOLDERS ." WHERE user_id = ". intval($request['user']->get('id')));

		$request['template']->setList('my_pmfolders', $result);
		$request['template']->setFile('usercp_content', 'pm_folders.html');
		$request['template']->setVar('newfolder_action', 'member.php?act=insert_pmfolder');
	}
}

class K4InsertPMFolder extends FAAction {
	function execute(&$request) {
		
		$check = new K4PMCheckPerms();
		$check->execute($request);

		if(get_map( 'pm_customfolders', 'can_add', array()) > $request['user']->get('perms')) {
			no_perms_error($request, 'usercp_content');
			return TRUE;
		}

		k4_bread_crumbs($request['template'], $request['dba'], 'L_USERCONTROLPANEL');
		$request['template']->setFile('content', 'usercp.html');
		
		if(intval($request['template']->getVar('enablepms')) == 0) {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'usercp_content', FALSE);
			return $action->execute($request);
		}

		if(intval($request['template']->getVar('allowcustomfolders')) == 0) {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'usercp_content', FALSE);
			return $action->execute($request);
		}

		$num_folders = $request['dba']->getValue("SELECT COUNT(*) FROM ". K4PMFOLDERS ." WHERE user_id = ". intval($request['user']->get('id')) ." AND is_global = 0");
		
		if($num_folders >= intval($request['template']->getVar('numcustomfolders'))) {
			$action = new K4InformationAction(new K4LanguageElement('L_REACHEDMAXPMFOLDERS'), 'usercp_content', FALSE);
			return $action->execute($request);
		}
		
		if (!$this->runPostFilter('name', new FARequiredFilter)) {
			$action = new K4InformationAction(new K4LanguageElement('L_NEEDPMFOLDERNAME'), 'usercp_content', TRUE);
			return $action->execute($request);
		}

		if (!$this->runPostFilter('description', new FARequiredFilter)) {
			$action = new K4InformationAction(new K4LanguageElement('L_NEEDPMFOLDERDESC'), 'usercp_content', TRUE);
			return $action->execute($request);
		}

		$insert = $request['dba']->prepareStatement("INSERT INTO ". K4PMFOLDERS ." (user_id,user_name,name,description) VALUES (?,?,?,?)");
		$insert->setInt(1, $request['user']->get('id'));
		$insert->setString(2, $request['user']->get('name'));
		$insert->setString(3, htmlentities(html_entity_decode($_REQUEST['name']), ENT_QUOTES));
		$insert->setString(4, htmlentities(preg_replace("(\r\n|\r|\n)", ' ', $_REQUEST['description'])));
		$insert->executeUpdate();

		$request['template']->setList('pmfolders', $request['dba']->executeQuery("SELECT * FROM ". K4PMFOLDERS ." WHERE is_global = 1 OR user_id = ". intval($request['user']->get('id'))));

		$action = new K4InformationAction(new K4LanguageElement('L_ADDEDPMFOLDER', htmlentities(html_entity_decode($_REQUEST['name']), ENT_QUOTES)), 'usercp_content', TRUE, 'member.php?act=usercp&view=pmfolders', 3);
		return $action->execute($request);
	}
}

class K4EditPMFolder extends FAAction {
	function execute(&$request) {
		$check = new K4PMCheckPerms();
		$check->execute($request);

		if(get_map( 'pm_customfolders', 'can_edit', array()) > $request['user']->get('perms')) {
			no_perms_error($request, 'usercp_content');
			return TRUE;
		}

		k4_bread_crumbs($request['template'], $request['dba'], 'L_USERCONTROLPANEL');
		$request['template']->setFile('content', 'usercp.html');
		
		if(intval($request['template']->getVar('enablepms')) == 0) {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'usercp_content', FALSE);
			return $action->execute($request);
		}

		if(intval($request['template']->getVar('allowcustomfolders')) == 0) {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'usercp_content', FALSE);
			return $action->execute($request);
		}

		if(!isset($_REQUEST['folder']) || intval($_REQUEST['folder']) == 0) {
			$action = new K4InformationAction(new K4LanguageElement('L_BADPMFOLDER'), 'usercp_content', FALSE);
			return $action->execute($request);
		}

		$folder = $request['dba']->getRow("SELECT * FROM ". K4PMFOLDERS ." WHERE id = ". intval($_REQUEST['folder']));
	
		if(!is_array($folder) || empty($folder)) {
			$action = new K4InformationAction(new K4LanguageElement('L_BADPMFOLDER'), 'usercp_content', FALSE);
			return $action->execute($request);
		}

		if(intval($folder['user_id']) != $request['user']->get('id')) {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'usercp_content', FALSE);
			return $action->execute($request);
		}
		
		$request['template']->setList('pmfolders', $request['dba']->executeQuery("SELECT * FROM ". K4PMFOLDERS ." WHERE is_global = 1 OR user_id = ". intval($request['user']->get('id'))));
		$request['template']->setFile('usercp_content', 'pm_newfolder.html');
		$request['template']->setVar('newfolder_action', 'member.php?act=update_pmfolder&amp;id='. intval($folder['id']));
		$request['template']->setVar('folder_name', $folder['name']);
		$request['template']->setVar('folder_description', $folder['description']);
	}
}

class K4UpdatePMFolder extends FAAction {
	function execute(&$request) {
		$check = new K4PMCheckPerms();
		$check->execute($request);

		if(get_map( 'pm_customfolders', 'can_edit', array()) > $request['user']->get('perms')) {
			no_perms_error($request, 'usercp_content');
			return TRUE;
		}

		k4_bread_crumbs($request['template'], $request['dba'], 'L_USERCONTROLPANEL');
		$request['template']->setFile('content', 'usercp.html');
		
		if(intval($request['template']->getVar('enablepms')) == 0) {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'usercp_content', FALSE);
			return $action->execute($request);
		}

		if(intval($request['template']->getVar('allowcustomfolders')) == 0) {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'usercp_content', FALSE);
			return $action->execute($request);
		}

		if(!isset($_REQUEST['folder']) || intval($_REQUEST['folder']) == 0) {
			$action = new K4InformationAction(new K4LanguageElement('L_BADPMFOLDER'), 'usercp_content', FALSE);
			return $action->execute($request);
		}

		$folder = $request['dba']->getRow("SELECT * FROM ". K4PMFOLDERS ." WHERE id = ". intval($_REQUEST['folder']));
	
		if(!is_array($folder) || empty($folder)) {
			$action = new K4InformationAction(new K4LanguageElement('L_BADPMFOLDER'), 'usercp_content', FALSE);
			return $action->execute($request);
		}

		if(intval($folder['user_id']) != $request['user']->get('id')) {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'usercp_content', FALSE);
			return $action->execute($request);
		}
		
		if (!$this->runPostFilter('name', new FARequiredFilter) || !$this->runPostFilter('description', new FARequiredFilter)) {
			$action = new K4InformationAction(new K4LanguageElement('L_NEEDPMFOLDERNAME'), 'usercp_content', TRUE);
			return $action->execute($request);
		}

		$update = $request['dba']->prepareStatement("UPDATE ". K4PMFOLDERS ." SET name=?,description=? WHERE id=?");
		$update->setString(1, htmlentities(html_entity_decode($_REQUEST['name']), ENT_QUOTES));
		$update->setString(2, htmlentities(preg_replace("(\r\n|\r|\n)", ' ', $_REQUEST['description'])));
		$update->setInt(3, $folder['id']);
		$update->executeUpdate();

		$request['template']->setList('pmfolders', $request['dba']->executeQuery("SELECT * FROM ". K4PMFOLDERS ." WHERE is_global = 1 OR user_id = ". intval($request['user']->get('id'))));
		
		$action = new K4InformationAction(new K4LanguageElement('L_UPDATEDPMFOLDER', $folder['name']), 'usercp_content', TRUE, 'member.php?act=usercp&view=pmfolders', 3);
		return $action->execute($request);
	}
}

class K4PreDeleteFolder extends FAAction {
	function execute(&$request) {
		$check = new K4PMCheckPerms();
		$check->execute($request);

		if(get_map( 'pm_customfolders', 'can_del', array()) > $request['user']->get('perms')) {
			no_perms_error($request, 'usercp_content');
			return TRUE;
		}

		k4_bread_crumbs($request['template'], $request['dba'], 'L_USERCONTROLPANEL');
		$request['template']->setFile('content', 'usercp.html');
		
		if(intval($request['template']->getVar('enablepms')) == 0 || intval($request['template']->getVar('allowcustomfolders')) == 0) {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'usercp_content', FALSE);
			return $action->execute($request);
		}
		
		if(!isset($_REQUEST['folder']) || intval($_REQUEST['folder']) == 0) {
			$action = new K4InformationAction(new K4LanguageElement('L_BADPMFOLDER'), 'usercp_content', FALSE);
			return $action->execute($request);
		}

		$folder = $request['dba']->getRow("SELECT * FROM ". K4PMFOLDERS ." WHERE id = ". intval($_REQUEST['folder']));
	
		if(!is_array($folder) || empty($folder)) {
			$action = new K4InformationAction(new K4LanguageElement('L_BADPMFOLDER'), 'usercp_content', FALSE);
			return $action->execute($request);
		}

		if(intval($folder['user_id']) != $request['user']->get('id')) {
			no_perms_error($request);
			return TRUE;
		}
		
		$request['template']->setList('pmfolders', $request['dba']->executeQuery("SELECT * FROM ". K4PMFOLDERS ." WHERE is_global = 1 OR user_id = ". intval($request['user']->get('id'))));
		$request['template']->setFile('usercp_content', 'pm_deletepmfolder.html');
		$request['template']->setVar('folder_id', intval($folder['id']));
		$request['template']->setVar('folder_name', $folder['name']);
	}
}

class K4ComposePMessage extends FAAction {
	function execute(&$request) {
		
		if(!$request['user']->isMember()) {
			no_perms_error($request, 'usercp_content');
			return TRUE;
		}

		$check = new K4PMCheckPerms();
		$check->execute($request);

		if(get_map( 'pm_message', 'can_add', array()) > $request['user']->get('perms')) {
			no_perms_error($request, 'usercp_content');
			return TRUE;
		}

		k4_bread_crumbs($request['template'], $request['dba'], 'L_USERCONTROLPANEL');
		$request['template']->setFile('content', 'usercp.html');
		
		if(intval($request['template']->getVar('enablepms')) == 0) {
			no_perms_error($request, 'usercp_content');
			return TRUE;
		}
		
		$num_pms		= $request['dba']->getValue("SELECT COUNT(*) FROM ". K4PRIVMESSAGES ." WHERE poster_id = ". intval($request['user']->get('id')));
		$max_pms		= intval($request['template']->getVar('pmquota'));
		
		if($num_pms >= $max_pms) {
			$action = new K4InformationAction(new K4LanguageElement('L_TOOMANYPMS', $num_pms, $max_pms), 'usercp_content', FALSE);
			return $action->execute($request);
		}
		
		/**
		 * Draft loading
		 */
		$body_text		= '';
		if(isset($_REQUEST['draft']) && intval($_REQUEST['draft']) > 0) {
			$draft		= $request['dba']->getRow("SELECT * FROM ". K4PRIVMESSAGES ." WHERE pm_id = ". intval($_REQUEST['draft']) ." AND is_draft = 1");
		
			if(is_array($draft) && !empty($draft)) {
				
				foreach($draft as $key => $val)
					$request['template']->setVar('pm_'. $key, $val);

				$body_text	= $draft['body_text'];

				$draft_info	= $request['dba']->getRow("SELECT * FROM ". K4PRIVMSGDRAFTS ." WHERE pm_id = ". intval($_REQUEST['draft']));
				
				foreach($draft_info as $key => $val)
					$request['template']->setVar('pm_'. $key, $val);

				$request['template']->setVar('edit_type', 'draft');
				$request['template']->setVisibility('edit_message', TRUE);
				$request['template']->setVisibility('save_draft', FALSE);
				$request['template']->setVisibility('pm_id', TRUE);
			}
		}

		/**
		 * If we are replying to something, get the message
		 */
		if(isset($_REQUEST['reply']) && intval($_REQUEST['reply']) > 0) {
			$message	= $request['dba']->getRow("SELECT * FROM ". K4PRIVMESSAGES ." WHERE pm_id = ". intval($_REQUEST['reply']));
			if(is_array($message) && !empty($message)) {
				$request['template']->setVar('pm_name', $request['template']->getVar('L_RE') .': '. $message['name']);
				$request['template']->setVar('pm_pm_to', $message['poster_name']);
				$request['template']->setVar('pm_pm_id', $message['pm_id']);

				if(isset($_REQUEST['quote'])) {
					$body_text		= '[quote='. $message['poster_name'] .']'. $message['body_text'] .'[/quote]';
				}
				$request['template']->setVar('edit_type', 'reply');
				$request['template']->setVisibility('edit_message', FALSE);
				$request['template']->setVisibility('save_draft', TRUE);
				$request['template']->setVisibility('pm_id', TRUE);
			}
		}
		
		if(isset($_REQUEST['user']) && $_REQUEST['user'] != '') {
			$request['template']->setVar('pm_pm_to', $_REQUEST['user']);
		}
		
		/* Get and set the emoticons and post icons to the template */
		$emoticons	= $request['dba']->executeQuery("SELECT * FROM ". K4EMOTICONS ." WHERE clickable = 1");
		$posticons	= $request['dba']->executeQuery("SELECT * FROM ". K4POSTICONS);
		
		/* Add the emoticons and the post icons to the template */
		$request['template']->setList('emoticons', $emoticons);
		$request['template']->setList('posticons', $posticons);
		
		/* Set some emoticon information */
		$request['template']->setVar('emoticons_per_row', $request['template']->getVar('smcolumns'));
		$request['template']->setVar('emoticons_per_row_remainder', $request['template']->getVar('smcolumns')-1);

		/* Create the bbcode/wysiwyg editor */
		create_editor($request, $body_text, 'pm');
		
		$request['template']->setVar('L_PMSUBJECTTOOSHORT', sprintf($request['template']->getVar('L_TITLETOOSHORT'), $request['template']->getVar('topicminchars'), $request['template']->getVar('topicmaxchars')));
		$request['template']->setFile('usercp_content', 'pm_newmessage.html');
		$request['template']->setVisibility('post_pm', TRUE);
		$request['template']->setVar('newpm_action', 'member.php?act=pm_savemessage');
	}
}

class K4SendPMessage extends FAAction {
	function execute(&$request) {
				
		/* set the breadcrumbs bit */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_USERCONTROLPANEL');
		
		global $_SETTINGS;

		$check = new K4PMCheckPerms();
		$check->execute($request);

		if(get_map( 'pm_message', 'can_add', array()) > $request['user']->get('perms')) {
			no_perms_error($request);
			return TRUE;
		}

		$num_pms		= $request['dba']->getValue("SELECT COUNT(*) FROM ". K4PRIVMESSAGES ." WHERE poster_id = ". intval($request['user']->get('id')));
		$max_pms		= intval($request['template']->getVar('pmquota'));
		
		if($num_pms >= $max_pms) {
			$action = new K4InformationAction(new K4LanguageElement('L_TOOMANYPMS', $num_pms, $max_pms), 'usercp_content', FALSE);
			return !USE_AJAX ? $action->execute($request) : ajax_message(new K4LanguageElement('L_TOOMANYPMS', $num_pms, $max_pms));
		}

		k4_bread_crumbs($request['template'], $request['dba'], 'L_USERCONTROLPANEL');
		$request['template']->setFile('content', 'usercp.html');

		/**
		 * Get who the message is going to
		 */
		if (!$this->runPostFilter('to', new FARequiredFilter)) {
			$action = new K4InformationAction(new K4LanguageElement('L_NEEDSENDPMTOSOMEONE'), 'usercp_content', TRUE);
			return !USE_AJAX ? $action->execute($request) : ajax_message('L_NEEDSENDPMTOSOMEONE');
		}

		$users			= (isset($_REQUEST['to']) && $_REQUEST['to'] != '') ? explode(",", $_REQUEST['to']) : array($_REQUEST['to']);
		$users			= (isset($_REQUEST['cc']) && $_REQUEST['cc'] != '') ? array_merge($users, explode(",", $_REQUEST['to'])) : $users;

		$valid_users	= array();
		$draft_users	= array();
		
		foreach($users as $username) {
			
			$username = trim($username);

			if(!in_array($username, $draft_users) && $username != $request['user']->get('name') && $username != '') {

				$user = $request['dba']->getRow("SELECT * FROM ". K4USERS ." WHERE name = '". $request['dba']->quote(htmlentities($username, ENT_QUOTES)) ."'");

				if(is_array($user) && !empty($user)) {
					if(get_map($user, 'pm_message', 'can_view', array()) <= $user['perms']) {
						$valid_users[] = $user;
						$draft_users[] = $user['name'];
					}
				}
			}
		}

		if(!is_array($valid_users) || empty($valid_users)) {
			$action = new K4InformationAction(new K4LanguageElement('L_PMNOVALIDRECIEVERS'), 'usercp_content', TRUE);
			return !USE_AJAX ? $action->execute($request) : ajax_message('L_PMNOVALIDRECIEVERS');
		}

		/**
		 * Check over posting inputs
		 */

		/* General error checking */
		if (!$this->runPostFilter('name', new FARequiredFilter)) {
			$action = new K4InformationAction(new K4LanguageElement('L_INSERTTOPICNAME'), 'usercp_content', TRUE);
			return !USE_AJAX ? $action->execute($request) : ajax_message('L_INSERTPMSUBJECT');
		}

		if (!$this->runPostFilter('name', new FALengthFilter(intval($_SETTINGS['topicmaxchars'])))) {
			$action = new K4InformationAction(new K4LanguageElement('L_PMSUBJECTTOOSHORT', intval($_SETTINGS['topicminchars']), intval($_SETTINGS['topicmaxchars'])), 'usercp_content', TRUE);
			return !USE_AJAX ? $action->execute($request) : ajax_message(new K4LanguageElement('L_PMSUBJECTTOOSHORT', intval($_SETTINGS['topicminchars']), intval($_SETTINGS['topicmaxchars'])));
		}
		if (!$this->runPostFilter('name', new FALengthFilter(intval($_SETTINGS['topicmaxchars']), intval($_SETTINGS['topicminchars'])))) {
			$action = new K4InformationAction(new K4LanguageElement('L_PMSUBJECTTOOSHORT', intval($_SETTINGS['topicminchars']), intval($_SETTINGS['topicmaxchars'])), 'usercp_content', TRUE);
			return !USE_AJAX ? $action->execute($request) : ajax_message(new K4LanguageElement('L_PMSUBJECTTOOSHORT', intval($_SETTINGS['topicminchars']), intval($_SETTINGS['topicmaxchars'])));
		}

		if(!isset($_REQUEST['message']) || $_REQUEST['message'] == '') {
			$action = new K4InformationAction(new K4LanguageElement('L_INSERTPMMESSAGE'), 'usercp_content', TRUE);
			return !USE_AJAX ? $action->execute($request) : ajax_message('L_INSERTPMMESSAGE');
		}
		
		/* Set the message created time */
		$created				= time();
		
		$_REQUEST['message']	= substr($_REQUEST['message'], 0, $_SETTINGS['pmmaxchars']);
		
		/* Initialize the bbcode parser with the topic message */
		$bbcode	= &new BBCodex($request['dba'], $request['user']->getInfoArray(), $_REQUEST['message'], 0, 
			iif((isset($_REQUEST['disable_html']) && $_REQUEST['disable_html']), FALSE, TRUE), 
			iif((isset($_REQUEST['disable_bbcode']) && $_REQUEST['disable_bbcode']), FALSE, TRUE), 
			iif((isset($_REQUEST['disable_emoticons']) && $_REQUEST['disable_emoticons']), FALSE, TRUE), 
			iif((isset($_REQUEST['disable_aurls']) && $_REQUEST['disable_aurls']), FALSE, TRUE));
		
		/* Parse the bbcode */
		$body_text	= $bbcode->parse();
		$parent_id	= 0;
		$message_id	= 0;

		/**
		 * Was this message originally a draft?
		 */
		$draft_loaded	= FALSE;
		if(isset($_REQUEST['draft']) && intval($_REQUEST['draft']) > 0) {
			$draft		= $request['dba']->getRow("SELECT * FROM ". K4PRIVMESSAGES ." WHERE pm_id = ". intval($_REQUEST['draft']) ." AND is_draft = 1");
		
			if(is_array($draft) && !empty($draft)) {
				
				$draft_loaded = TRUE;
			}
		}
		
		if((isset($_REQUEST['reply']) && intval($_REQUEST['reply']) > 0) || ($draft_loaded && $draft['message_id'] > 0)) {
			
			$reply_id	= isset($_REQUEST['reply']) ? $_REQUEST['reply'] : $draft['message_id'];

			$message	= $request['dba']->getRow("SELECT * FROM ". K4PRIVMESSAGES ." WHERE pm_id = ". intval($reply_id));
			if(is_array($message) && !empty($message)) {
				$parent_id		= intval($message['pm_id']);
				$message_id		= intval($message['message_id']) == 0 ? intval($message['pm_id']) : intval($message['message_id']);
			}
		}
				
		if((isset($_REQUEST['submit_type']) && ($_REQUEST['submit_type'] == 'post' || $_REQUEST['submit_type'] == 'draft')) || ( isset($_REQUEST['post']) || isset($_REQUEST['draft']) ) ) {
			
			$is_draft	= 0;
			$folder		= PM_INBOX;
			
			/**
			 * Does this person have permission to post a draft? 
			 */
			if(!$draft_loaded && ($_REQUEST['submit_type'] == 'draft' || isset($_REQUEST['draft']))) {
				if($request['user']->get('perms') < get_map( 'pm_message_save', 'can_add', array())) {
					$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);
					return $action->execute($request);
				}
				
				$is_draft	= 1;
				$folder		= PM_SAVEDITEMS;
				$valid_users = array($request['user']->getInfoArray());
			}
			
			/**
			 * Should we save this message too?
			 */
			$do_save		= isset($_REQUEST['save_message']) && $_REQUEST['save_message'] ? TRUE : FALSE;
			if(!$draft_loaded && $do_save && $is_draft == 0) {
				$valid_users[] = $request['user']->getInfoArray();
			}

			/** 
			 * Can / Do we track this message?
			 */
			$track			= FALSE;
			if(isset($_REQUEST['track_message'])) {
				$track			= FALSE;

				// TODO: Message Tracking
			}

			/**
			 * Build the queries
			 */
			
			$request['dba']->beginTransaction();
			
			$sending_id = md5(uniqid(rand(), true));
			$tracker_id = md5(uniqid(rand(), true));
			
			/**
			 * Loop through the users and send the private message to them
			 */
			$i = 0;
			foreach($valid_users as $user) {
				
				/* Make sure to add a limit to how many messages can be sent if there is one */
				if($i < $request['template']->getVar('maxsendtopms') &$request['template']->getVar('maxsendtopms') > 0) {
					
					/* Prepare the inserting statement */
					$insert_a			= $request['dba']->prepareStatement("INSERT INTO ". K4PRIVMESSAGES ." (name,folder_id,poster_name,poster_id,body_text,posticon,disable_html,disable_bbcode,disable_emoticons,disable_sig,disable_areply,disable_aurls,is_draft,created,member_id,member_name,member_has_read,tracker_id,sending_id,parent_id,message_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
									
					$insert_a->setString(1, htmlentities(html_entity_decode($_REQUEST['name']), ENT_QUOTES));
					$insert_a->setInt(2, (($user['id'] != $request['user']->get('id') || $is_draft == 1) ? $folder : PM_SENTITEMS));
					$insert_a->setString(3, $request['user']->get('name'));
					$insert_a->setInt(4, $request['user']->get('id'));
					$insert_a->setString(5, $body_text);
					$insert_a->setString(6, iif(($request['user']->get('perms') >= get_map( 'pm_posticons', 'can_add', array())), (isset($_REQUEST['posticon']) ? $_REQUEST['posticon'] : 'clear.gif'), 'clear.gif'));
					$insert_a->setInt(7, iif((isset($_REQUEST['disable_html']) && $_REQUEST['disable_html']), 1, 0));
					$insert_a->setInt(8, iif((isset($_REQUEST['disable_bbcode']) && $_REQUEST['disable_bbcode']), 1, 0));
					$insert_a->setInt(9, iif((isset($_REQUEST['disable_emoticons']) && $_REQUEST['disable_emoticons']), 1, 0));
					$insert_a->setInt(10, iif((isset($_REQUEST['enable_sig']) && $_REQUEST['enable_sig']), 0, 1));
					$insert_a->setInt(11, iif((isset($_REQUEST['disable_areply']) && $_REQUEST['disable_areply']), 1, 0));
					$insert_a->setInt(12, iif((isset($_REQUEST['disable_aurls']) && $_REQUEST['disable_aurls']), 1, 0));
					$insert_a->setInt(13, $is_draft);
					$insert_a->setInt(14, $created);
					$insert_a->setInt(15, $user['id']);
					$insert_a->setString(16, $user['name']);
					$insert_a->setInt(17, ($user['id'] == $request['user']->get('id') ? 1 : 0));
					$insert_a->setString(18, $tracker_id);
					$insert_a->setString(19, $sending_id);
					$insert_a->setString(20, $parent_id);
					$insert_a->setString(21, $message_id);
					
					$insert_a->executeUpdate();

					$pm_id			= $request['dba']->getInsertId(K4PRIVMESSAGES, 'pm_id');
					
					// update the number of new pm's for that user
					if($user['id'] != $request['user']->get('id')) {
						$request['dba']->executeUpdate("UPDATE ". K4USERS ." SET new_pms=new_pms+1 WHERE id = ". intval($user['id']));
					}

				} else {
					break;
				}
				$i++;
			}
			
			/**
			 * If this PM was a draft, it was sent back to us, now we need to create
			 * a record of who to send it to for if we choose to send it again
			 */
			if(!$draft_loaded && (isset($_REQUEST['submit_type']) && $_REQUEST['submit_type'] == 'draft') || isset($_REQUEST['draft'])) {
				
				/* Split who this message is to into 'to' and 'carbon copy' */
				$count				= count($draft_users);
				$to					= array_slice($draft_users, 0, ceil($count / 2));
				$cc					= $count > 1 ? array_slice($draft_users, ceil($count / 2), $count) : array();

				$insert				= $request['dba']->prepareStatement("INSERT INTO ". K4PRIVMSGDRAFTS ." (pm_id,pm_to,pm_cc) VALUES (?,?,?)");
				$insert->setInt(1, $pm_id);
				$insert->setString(2, implode(',', $to));
				$insert->setString(3, implode(',', $cc));
				
				/* Add the draft information */
				$insert->executeUpdate();
			}
			
			/**
			 * If we loaded a draft, deal with it
			 */
			if($draft_loaded) {
				$request['dba']->executeUpdate("DELETE FROM ". K4PRIVMESSAGES ." WHERE pm_id = ". intval($draft['pm_id']));
				$request['dba']->executeUpdate("DELETE FROM ". K4PRIVMSGDRAFTS ." WHERE pm_id = ". intval($draft['pm_id']));
			}

			/**
			 * If this was a reply, update its parent and top-message
			 */
			if($message_id > 0) {
				$request['dba']->executeUpdate("UPDATE ". K4PRIVMESSAGES ." SET num_replies=num_replies+1 WHERE pm_id = ". intval($message_id));

				if($message_id != $parent_id) {
					$request['dba']->executeUpdate("UPDATE ". K4PRIVMESSAGES ." SET num_replies=num_replies+1 WHERE pm_id = ". intval($parent_id));
				}
			}
			
			/* Finish everything off by commiting the SQL transaction */
			$request['dba']->commitTransaction();
			
			/**
			 * Now we're done!
			 */
			if($is_draft == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_SENTPRIVATEMSG', htmlentities(html_entity_decode($_REQUEST['name']), ENT_QUOTES)), 'usercp_content', FALSE, 'member.php?act=usercp', 3);
				return $action->execute($request);
			} else {
				$action = new K4InformationAction(new K4LanguageElement('L_SAVEDPRIVATEMSG', htmlentities(html_entity_decode($_REQUEST['name']), ENT_QUOTES)), 'usercp_content', FALSE, 'member.php?act=usercp', 3);
				return $action->execute($request);
			}

		} else {
			
			/**
			 * Message Previewing
			 */

			if(!USE_AJAX) {
				
				$request['template']->setVar('L_PMSUBJECTTOOSHORT', sprintf($request['template']->getVar('L_TITLETOOSHORT'), $request['template']->getVar('topicminchars'), $request['template']->getVar('topicmaxchars')));

				/* Create the bbcode/wysiwyg editor */
				create_editor($request, '', 'pm');

				$request['template']->setFile('usercp_content', 'pm_newmessage.html');
				$request['template']->setVisibility('post_pm', TRUE);
				$request['template']->setVar('newpm_action', 'member.php?act=pm_savemessage');

				/* Get and set the emoticons and post icons to the template */
				$emoticons	= $request['dba']->executeQuery("SELECT * FROM ". K4EMOTICONS ." WHERE clickable = 1");
				$posticons	= $request['dba']->executeQuery("SELECT * FROM ". K4POSTICONS);
				
				/* Add the emoticons and the post icons to the template */
				$request['template']->setList('emoticons', $emoticons);
				$request['template']->setList('posticons', $posticons);
				
				/* Set some emoticon information */
				$request['template']->setVar('emoticons_per_row', $request['template']->getVar('smcolumns'));
				$request['template']->setVar('emoticons_per_row_remainder', $request['template']->getVar('smcolumns')-1);
			}
			
			$msg_preview	= array(
								'pm_id' => 0,
								'name' => htmlentities(html_entity_decode($_REQUEST['name']), ENT_QUOTES),
								'posticon' => (isset($_REQUEST['posticon']) ? $_REQUEST['posticon'] : 'clear.gif'),
								'body_text' => $body_text,
								'poster_name' => $request['user']->get('name'),
								'poster_id' => $request['user']->get('id'),
								'disable_html' => iif((isset($_REQUEST['disable_html']) && $_REQUEST['disable_html']), 1, 0),
								'disable_sig' => iif((isset($_REQUEST['enable_sig']) && $_REQUEST['enable_sig']), 0, 1),
								'disable_bbcode' => iif((isset($_REQUEST['disable_bbcode']) && $_REQUEST['disable_bbcode']), 1, 0),
								'disable_emoticons' => iif((isset($_REQUEST['disable_emoticons']) && $_REQUEST['disable_emoticons']), 1, 0),
								'disable_areply' => iif((isset($_REQUEST['disable_areply']) && $_REQUEST['disable_areply']), 1, 0),
								'disable_aurls' => iif((isset($_REQUEST['disable_aurls']) && $_REQUEST['disable_aurls']), 1, 0)
								);
			
			/* Add the message information to the template */
			$pm_iterator		= &new K4PrivMsgIterator($request['dba'], $request['user'], $msg_preview, FALSE);
			$request['template']->setList('message', $pm_iterator);
			
			/* Assign the message preview values to the template */
			$msg_preview['body_text'] = $_REQUEST['message'];
			
			foreach($msg_preview as $key => $val)
				$request['template']->setVar('pm_'. $key, $val);

			if(!USE_AJAX) {
				/* Set the the button display options */
				$request['template']->setVisibility('save_draft', FALSE);
				$request['template']->setVisibility('load_button', FALSE);
				$request['template']->setVisibility('edit_topic', TRUE);
				$request['template']->setVisibility('topic_id', TRUE);
				$request['template']->setVisibility('post_topic', FALSE);
				$request['template']->setVisibility('edit_post', TRUE);
				$request['template']->setVisibility('post_pm', TRUE);
				
				/* Create the bbcode/wysiwyg editor */
				create_editor($request, $body_text, 'pm');
				
				$request['template']->setVar('L_PMSUBJECTTOOSHORT', sprintf($request['template']->getVar('L_TITLETOOSHORT'), $request['template']->getVar('topicminchars'), $request['template']->getVar('topicmaxchars')));
				$request['template']->setVar('newpm_action', 'member.php?act=pm_savemessage');
				
				if($draft_loaded) {
					$request['template']->setVar('edit_type', 'draft');
					$request['template']->setVisibility('edit_message', TRUE);
					$request['template']->setVisibility('save_draft', FALSE);
				}

				if($parent_id > 0) {
					$request['template']->setVar('edit_type', 'reply');
				}
				
				/* Set the post topic form */
				$request['template']->setFile('preview', 'pm_preview.html');
				$request['template']->setFile('content', 'usercp.html');
				$request['template']->setFile('usercp_content', 'pm_newmessage.html');
			} else {
				echo $request['template']->run(BB_BASE_DIR .'/templates/'. $request['user']->get('templateset') .'/pm_preview.html');
				exit;
			}
		}
	}
}

class K4ViewPMessage extends FAAction {
	function execute(&$request) {
		
		$check = new K4PMCheckPerms();
		$check->execute($request);
		
		// check over the message id
		if(!isset($_REQUEST['pm']) || intval($_REQUEST['pm']) == 0) {
			$action = new K4InformationAction(new K4LanguageElement('L_PMSGDOESNTEXIST'), 'usercp_content', FALSE);
			return $action->execute($request);
		}
		
		// get the message
		$message	= $request['dba']->getRow("SELECT * FROM ". K4PRIVMESSAGES ." WHERE pm_id = ". intval($_REQUEST['pm']));
		
		// recheck the message
		if(!is_array($message) || empty($message)) {
			$action = new K4InformationAction(new K4LanguageElement('L_PMSGDOESNTEXIST'), 'usercp_content', FALSE);
			return $action->execute($request);
		}
		
		// if this is a draft, return a different action
		if($message['is_draft'] == 1) {
			$_REQUEST['draft']	= $message['pm_id'];
			$_POST['draft']		= $message['pm_id'];

			$action = new K4ComposePMessage();
			return $action->execute($request);
		}
		
//		if($message['message_id'] > 0) {
//			$message			= $request['dba']->getRow("SELECT * FROM ". K4PRIVMESSAGES ." WHERE ( (pm_id = ". intval($message['message_id']) ." AND message_id = 0) OR (message_id = ". intval($message['message_id']) .") ) ORDER BY created ASC LIMIT 1");
//		}

		$request['template']->setList('message', new K4PrivMsgIterator($request['dba'], $request['user'], $message));

		$request['template']->setFile('usercp_content', 'viewpmessage.html');
	}
}

/**
 * Set which folders to move this message to/from
 */
class K4SelectPMMoveFolder extends FAAction {
	function execute(&$request) {
		
		$check = new K4PMCheckPerms();
		$check->execute($request);

		// check the folder
		if(!isset($_REQUEST['original_folder']) || intval($_REQUEST['original_folder']) == 0) {
			$action = new K4InformationAction(new K4LanguageElement('L_PMBADORIGINALFOLDER'), 'usercp_content', FALSE);
			return $action->execute($request);
		}
		$original		= $request['dba']->getRow("SELECT * FROM ". K4PMFOLDERS ." WHERE id = ". intval($_REQUEST['original_folder']));
		if(!is_array($original) || empty($original)) {
			$action = new K4InformationAction(new K4LanguageElement('L_PMBADORIGINALFOLDER'), 'usercp_content', FALSE);
			return $action->execute($request);
		}
		
		// get the folders that pm's can be moved to
		$folders = $request['dba']->executeQuery("SELECT * FROM ". K4PMFOLDERS ." WHERE ((user_id = ". intval($request['user']->get('id')) ." AND is_global = 0) OR is_global = 1) AND (id <> ". PM_SENTITEMS ." AND id <> ". PM_SAVEDITEMS .") ORDER BY id ASC");
		
		// set the original folder to the template
		foreach($original as $key => $val) {
			$request['template']->setVar('folder_'. $key, $val);
		}
		
		$pm_ids = $prefix ='';

		// loop through the messages and move them
		foreach($_REQUEST['pmessage'] as $pm_id) {
			
			$pm_ids .= $prefix .' pm_id = '. intval($pm_id);
			$prefix = ' OR';
		}

		$result		= $request['dba']->executeQuery("SELECT * FROM ". K4PRIVMESSAGES ." WHERE member_id = ". intval($request['user']->get('id')) ." AND ($pm_ids)");
		$it 		= &new K4PrivMessageIterator($request['dba'], $result, $request['template']->getVar('IMG_DIR'), $request['template']->getVar('pmrepliesperpage') );
		
		// set the folders list and template
		$request['template']->setList('moveto_folders', $folders);
		$request['template']->setList('pmessages', $it);
		$request['template']->setFile('usercp_content', 'pm_movemessages.html');

		return TRUE;
	}
}

/**
 * Move a/multiple Private Messages
 */
class K4MovePMessages extends FAAction {
	function execute(&$request) {
		
		k4_bread_crumbs($request['template'], $request['dba'], 'L_USERCONTROLPANEL');
		$request['template']->setFile('content', 'usercp.html');

		$check = new K4PMCheckPerms();
		$check->execute($request);
		
		// check the ids
		if(!isset($_REQUEST['pmessage']) || !is_array($_REQUEST['pmessage']) || empty($_REQUEST['pmessage'])) {
			$action = new K4InformationAction(new K4LanguageElement('L_PMNEEDSELECTONE'), 'usercp_content', FALSE);
			return $action->execute($request);
		}
		
		// check the folders
		if(!isset($_REQUEST['original_folder']) || intval($_REQUEST['original_folder']) == 0) {
			$action = new K4InformationAction(new K4LanguageElement('L_PMBADORIGINALFOLDER'), 'usercp_content', FALSE);
			return $action->execute($request);
		}
		if(!isset($_REQUEST['destination_folder']) || intval($_REQUEST['destination_folder']) == 0) {
			$action = new K4InformationAction(new K4LanguageElement('L_PMBADDESTFOLDER'), 'usercp_content', FALSE);
			return $action->execute($request);
		}
		
		// get the folders
		$original		= $request['dba']->getRow("SELECT * FROM ". K4PMFOLDERS ." WHERE id = ". intval($_REQUEST['original_folder']));
		$destination	= $request['dba']->getRow("SELECT * FROM ". K4PMFOLDERS ." WHERE id = ". intval($_REQUEST['destination_folder']));
		
		// recheck the folders
		if(!is_array($original) || empty($original)) {
			$action = new K4InformationAction(new K4LanguageElement('L_PMBADORIGINALFOLDER'), 'usercp_content', FALSE);
			return $action->execute($request);
		}
		if(!is_array($destination) || empty($destination)) {
			$action = new K4InformationAction(new K4LanguageElement('L_PMBADDESTFOLDER'), 'usercp_content', FALSE);
			return $action->execute($request);
		}

		// loop through the messages and move them
		foreach($_REQUEST['pmessage'] as $pm_id) {
			
			$temp = $request['dba']->getRow("SELECT * FROM ". K4PRIVMESSAGES ." WHERE pm_id = ". intval($pm_id));

			if($temp['member_id'] == $request['user']->get('id')) {
				$request['dba']->executeUpdate("UPDATE ". K4PRIVMESSAGES ." SET folder_id = ". intval($destination['id']) ." WHERE (pm_id = ". intval($pm_id) ." OR message_id = ". intval($pm_id) .") AND member_id = ". intval($request['user']->get('id')));
			}
		}
		
		// success!
		$action = new K4InformationAction(new K4LanguageElement('L_MOVEDPMESSAGES', $original['name'], $destination['name']), 'usercp_content', FALSE, 'member.php?act=usercp&view=pmfolder&folder='. $destination['id'], 3);
		return $action->execute($request);

		return TRUE;
	}
}

/**
 * Delete a/multiple Private Messages
 */
class K4DeletePMessages extends FAAction {
	function execute(&$request) {
		
		$check = new K4PMCheckPerms();
		$check->execute($request);
		
		// check the ids
		if(!isset($_REQUEST['pmessage']) || !is_array($_REQUEST['pmessage']) || empty($_REQUEST['pmessage'])) {
			$action = new K4InformationAction(new K4LanguageElement('L_PMNEEDSELECTONE'), 'usercp_content', FALSE);
			return $action->execute($request);
		}
		
		$less_newpms = 0;
		
		// loop through the messages and move them
		foreach($_REQUEST['pmessage'] as $pm_id) {
			
			$temp = $request['dba']->getRow("SELECT * FROM ". K4PRIVMESSAGES ." WHERE pm_id = ". intval($pm_id));

			if($temp['member_id'] == $request['user']->get('id')) {
				
				// TODO: this can be optimized easily
				$less_newpms += intval($request['dba']->getValue("SELECT COUNT(*) FROM ". K4PRIVMESSAGES ." WHERE (pm_id = ". intval($pm_id) ." OR message_id = ". intval($pm_id) .") AND member_has_read = 0 AND member_id = ". intval($request['user']->get('id'))));
				
				$request['dba']->executeUpdate("DELETE FROM ". K4PRIVMESSAGES ." WHERE (pm_id = ". intval($pm_id) ." OR message_id = ". intval($pm_id) .") AND member_id = ". intval($request['user']->get('id')));
			}
		}
		
		if($less_newpms > 0) {
			$request['dba']->executeUpdate("UPDATE ". K4USERS ." SET new_pms=new_pms-$less_newpms WHERE id = ". intval($request['user']->get('id')));
		}

		// success! removed selected pms :D
		$action = new K4InformationAction(new K4LanguageElement('L_DELETEDPMESSAGES'), 'usercp_content', FALSE, referer(), 3);
		return $action->execute($request);

		return TRUE;
	}
}

class K4PMCheckPerms extends FAAction {
	function execute(&$request) {
		// check the perms
		if(!$request['user']->isMember()) {
			no_perms_error($request, 'usercp_content');
			return TRUE;
		}
		if($request['user']->get('perms') < get_map( 'private_messaging', 'can_view', array())) {
			no_perms_error($request, 'usercp_content');
			return TRUE;
		}
	}
}

/**
 * Iterate through a private message an its replies
 */
class K4PrivMsgIterator extends FAArrayiterator {
	var $dba;
	var $users = array();
	var $qp;
	var $sr;
	var $user;
	
	function K4PrivMsgIterator(&$dba, &$user, $message, $show_replies = TRUE) {
		$this->__construct($dba, $user, $message, $show_replies);
	}

	function __construct(&$dba, &$user, $message, $show_replies = TRUE) {
		
		global $_QUERYPARAMS, $_USERGROUPS, $_PROFILEFIELDS;
		
		$this->qp						= $_QUERYPARAMS;
		$this->sr						= (bool)$show_replies;
		$this->dba						= &$dba;
		$this->user						= &$user;
		$this->groups					= $_USERGROUPS;
		$this->fields					= $_PROFILEFIELDS;
				
		parent::__construct(array(0 => $message));
	}

	function current() {
		$temp							= parent::current();
				
		$temp['posticon']				= @$temp['posticon'] != '' ? (file_exists(BB_BASE_DIR .'/tmp/upload/posticons/'. @$temp['posticon']) ? @$temp['posticon'] : 'clear.gif') : 'clear.gif';

		if($temp['poster_id'] > 0) {
			
			if(!isset($this->users[$temp['poster_id']])) {
				$user						= $this->dba->getRow("SELECT ". $this->qp['user'] . $this->qp['userinfo'] ." FROM ". K4USERS ." u LEFT JOIN ". K4USERINFO ." ui ON u.id=ui.user_id WHERE u.id=". intval($temp['poster_id']));
				$group						= get_user_max_group($user, $this->groups);
				$user['group_color']		= (!isset($group['color']) || $group['color'] == '') ? '000000' : $group['color'];
				$user['group_nicename']		= $group['nicename'];
				$user['group_avatar']		= $group['avatar'];
				$user['online']				= (time() - ini_get('session.gc_maxlifetime')) > $user['seen'] ? 'offline' : 'online';
				$this->users[$user['id']]	= $user;
			} else {
				$user						= $this->users[$temp['poster_id']];
			}

			if($user['flag_level'] > 0 && $_SESSION['user']->get('perms') >= MODERATOR)
				$temp['post_user_background'] = 'background-color: #FFFF00;';

			foreach($user as $key => $val)
				$temp['post_user_'. $key] = $val;
			
			$temp['profilefields']			= &new FAArrayIterator(get_profile_fields($this->fields, $temp));

			/* This array holds all of the userinfo for users that post to this topic */
			$this->users[$user['id']]			= $user;
			
		} else {
			$temp['post_user_id']			= 0;
			$temp['post_user_name']			= $temp['poster_name'];
		}
		
		if($temp['member_has_read'] == 0) {
			$this->dba->executeUpdate("UPDATE ". K4USERS ." SET new_pms=new_pms-1 WHERE id = ". intval($_SESSION['user']->get('id')));
			$this->dba->executeUpdate("UPDATE ". K4PRIVMESSAGES ." SET member_has_read = 1 WHERE pm_id = ". intval($temp['pm_id']));
		}

		/* Deal with acronyms */
		replace_acronyms($temp['body_text']);
		
		/* word censors */
		replace_censors($temp['body_text']);
		replace_censors($temp['name']);

		/* do we have any attachments? */
//		if(isset($temp['attachments']) && $temp['attachments'] > 0) {
//			$temp['attachment_files']		= &new K4AttachmentsIterator($this->dba, $this->user, $temp['topic_id'], 0);
//		}

		if($this->sr && $temp['num_replies'] > 0) {
			
//			$ids		= array($temp['pm_id']);
//			$db_ids		= $this->dba->executeQuery("SELECT pm_id FROM ". K4PRIVMESSAGES ." WHERE message_id = ". intval($temp['pm_id']) ." AND member_id = ". $this->user->get('id'));
//			while($db_ids->next()) {
//				$t		= $db_ids->current();
//				$ids[]	= $t['pm_id'];
//			}
//			$db_ids->free();

			$poster_extra					= $temp['poster_id'] == $this->user->get('id') ? ' AND folder_id <> '. PM_SENTITEMS : '';
			$member_extra					= $temp['member_id'] == $this->user->get('id') ? '' : ' AND folder_id = '. PM_SENTITEMS;
			
//			$this->result					= $this->dba->executeQuery("SELECT * FROM ". K4PRIVMESSAGES ." WHERE message_id = ". intval($temp['pm_id']) ." ORDER BY created ASC"); // ." AND ((poster_id = ". intval($temp['poster_id']) ." $poster_extra) OR (member_id = ". intval($temp['member_id']) ." $member_extra))
//			$temp['replies']				= &new K4PrivMsgRepliesIterator($this->user, $this->dba, $this->result, $this->qp, $this->users, $this->groups, $this->fields);
		}
		
		return $temp;
	}
}

class K4PrivMsgRepliesIterator extends FAProxyIterator {
	var $user, $dba, $result, $qp, $users, $groups, $fields;

	function K4PrivMsgRepliesIterator(&$user, &$dba, &$result, $queryparams, $users, $groups, $fields) {
		$this->__construct($user, $dba, $result, $queryparams, $users, $groups, $fields);
	}

	function __construct(&$user, &$dba, &$result, $queryparams, $users, $groups, $fields) {
		
		$this->users			= $users;
		$this->qp				= $queryparams;
		$this->result			= &$result;
		$this->groups			= $groups;
		$this->fields			= $fields;
		$this->user				= &$user;
		$this->dba				= &$dba;
		
		parent::__construct($this->result);
	}

	function current() {
		$temp					= parent::current();
		
		//if( ($temp['folder_id'] == PM_SENTITEMS && $temp['member_id'] != $this->user->get('id')) || ($temp['member_id'] == $this->user->get('id')) ) {
			$temp['posticon']		= isset($temp['posticon']) && @$temp['posticon'] != '' ? iif(file_exists(BB_BASE_DIR .'/tmp/upload/posticons/'. @$temp['posticon']), @$temp['posticon'], 'clear.gif') : 'clear.gif';
			
			if($temp['poster_id'] > 0) {
				
				if(!isset($this->users[$temp['poster_id']])) {
				
					$user						= $this->dba->getRow("SELECT ". $this->qp['user'] . $this->qp['userinfo'] ." FROM ". K4USERS ." u LEFT JOIN ". K4USERINFO ." ui ON u.id=ui.user_id WHERE u.id=". intval($temp['poster_id']));
					
					if(is_array($user) && !empty($user)) {
						$group						= get_user_max_group($user, $this->groups);
						$user['group_color']		= !isset($group['color']) || $group['color'] == '' ? '000000' : $group['color'];
						$user['group_nicename']		= $group['nicename'];
						$user['group_avatar']		= $group['avatar'];
						$user['online']				= (time() - ini_get('session.gc_maxlifetime')) > $user['seen'] ? 'offline' : 'online';

						$this->users[$user['id']]	= $user;
					}
				} else {
					$user						= $this->users[$temp['poster_id']];
				}
				
				if(is_array($user) && !empty($user)) {

					if($user['flag_level'] > 0 && $_SESSION['user']->get('perms') >= MODERATOR)
						$temp['post_user_background'] = 'background-color: #FFFF00;';
					
					foreach($user as $key => $val)
						$temp['post_user_'. $key] = $val;

					$temp['profilefields']	= &new FAArrayIterator(get_profile_fields($this->fields, $temp));
				}

				if(!isset($temp['post_user_online']))
					$temp['post_user_online'] = 'offline';

			} else {
				$temp['post_user_id']	= 0;
				$temp['post_user_name']	= $temp['poster_name'];
			}

	//		/* do we have any attachments? */
	//		if(isset($temp['attachments']) && $temp['attachments'] > 0) {
	//			$temp['attachment_files']		= &new K4AttachmentsIterator($this->dba, $this->user, $temp['topic_id'], $temp['reply_id']);
	//		}
			
			if($temp['member_has_read'] == 0) {
				$this->dba->executeUpdate("UPDATE ". K4USERS ." SET new_pms=new_pms-1 WHERE id = ". intval($_SESSION['user']->get('id')));
				$this->dba->executeUpdate("UPDATE ". K4PRIVMESSAGES ." SET member_has_read = 1 WHERE pm_id = ". intval($temp['pm_id']));
			}

			/* Deal with acronyms */
			replace_acronyms($temp['body_text']);
			
			/* word censors!! */
			replace_censors($temp['body_text']);
			replace_censors($temp['name']);
			
			/* Should we free the result? */
			if(!$this->hasNext())
				$this->result->free();
			
			return $temp;
		//}
	}
}

/**
 * Iterate through private messages in a folder
 */
class K4PrivMessageIterator extends FAProxyIterator {
	
	var $result, $img_dir, $dba, $repliesperpage;
	
	function K4PrivMessageIterator(&$dba, &$result, $img_dir, $repliesperpage) {
		$this->__construct($dba, $result, $img_dir, $repliesperpage);
	}

	function __construct(&$dba, &$result, $img_dir, $repliesperpage) {
		
		global $_FLAGGEDUSERS;

		$this->result			= &$result;
		$this->img_dir			= $img_dir;
		$this->dba				= &$dba;
		$this->flagged_users	= $_FLAGGEDUSERS;
		$this->repliesperpage	= $repliesperpage;
		
		parent::__construct($this->result);
	}

	function current() {
		$temp					= parent::current();
		
		/* Set the topic icons */
		$temp['posticon']		= $temp['posticon'] != '' ? iif(file_exists(BB_BASE_DIR .'/tmp/upload/posticons/'. $temp['posticon']), $temp['posticon'], 'clear.gif') : 'clear.gif';
		
		$new					= $temp['member_has_read'] == 0 ? TRUE : FALSE;
		
		$temp['use_pager']		= 0;
		if($this->repliesperpage < $temp['num_replies']) {
			
			/* Create a pager */
			$temp['use_pager']	= 1;
			$temp['num_pages']	= @ceil($temp['num_replies'] / $this->repliesperpage);
			$temp['pager']		= paginate($temp['num_replies'], '&laquo;', '&lt;', '', '&gt;', '&raquo;', $this->repliesperpage, $temp['pm_id']);
		}

		if($temp['poster_id'] > 0) {
			if(in_array($temp['poster_id'], $this->flagged_users) && $_SESSION['user']->get('perms') >= MODERATOR) {
				$temp['post_user_background'] = 'background-color: #FFFF00;';
			}
		}

		if($temp['is_draft'] == 1) {
			$temp['url'] = 'member.php?act=usercp&amp;view=pmnewmessage&amp;draft='. $temp['pm_id'];
		} else {
			$temp['url'] = 'member.php?act=usercp&amp;view=pmsg&amp;pm='. $temp['pm_id'];
		}	
				
		if($new) {
			$temp['is_new']			= 1;
		}
		
		/* Censor subjects if necessary */
		replace_censors($temp['name']);

		/* Should we free the result? */
		if(!$this->hasNext())
			$this->result->free();

		return $temp;
	}
}

?>