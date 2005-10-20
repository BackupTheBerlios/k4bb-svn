<?php
/**
* k4 Bulletin Board, modusers.class.php
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

class ModBanUser extends FAAction {
	function execute(&$request) {
		if($request['user']->get('perms') < get_map($request['user'], 'banusers', 'can_add', array())) {
			no_perms_error($request);
			return TRUE;
		}
		

		if(isset($_REQUEST['id'])) {
			$user		= $request['dba']->getRow("SELECT * FROM ". K4USERS ." WHERE id = ". intval($_REQUEST['id']));
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_BANUSER');

			if(!is_array($user) || empty($user)) {
				$action = new K4InformationAction(new K4LanguageElement('L_USERDOESNTEXIST'), 'content', TRUE);
				return $action->execute($request);
			}
			
			if($user['perms'] > $request['user']->get('perms')) {
				no_perms_error($request);
				return TRUE;
			}

			if($user['id'] > $request['user']->get('id')) {
				no_perms_error($request);
				return TRUE;
			}

			// unban
			if($user['banned'] == 1) {
				
				$request['dba']->executeUpdate("UPDATE ". K4USERS ." SET banned = 0 WHERE id = ". intval($user['id']));
				$request['dba']->executeUpdate("DELETE FROM ". K4BANNEDUSERS ." WHERE user_id = ". intval($user['id']));
				
				reset_cache('banned_users');

				$action = new K4InformationAction(new K4LanguageElement('L_UNBANNEDUSER', $user['name']), 'content', TRUE, 'index.php', 3);
				return $action->execute($request);

			// show ban form
			} else {

				$request['template']->setFile('content', 'banuser.html');
				$request['template']->setVar('banuser_id', $user['id']);
				$request['template']->setVar('banuser_name', $user['name']);
			}
		} else {
			k4_bread_crumbs($request['template'], $request['dba'], 'L_BANUSER');
			$request['template']->setFile('content', 'finduser.html');
		}
	}
}

class HardBanUser extends FAAction {
	function execute(&$request) {
		
		if($request['user']->get('perms') < get_map($request['user'], 'banusers', 'can_add', array())) {
			no_perms_error($request);
			return TRUE;
		}
		
		if(isset($_REQUEST['id'])) {
			
			if(intval($_REQUEST['id']) > 0) {
				$user		= $request['dba']->getRow("SELECT * FROM ". K4USERS ." WHERE id = ". intval($_REQUEST['id']));
			
				if(!is_array($user) || empty($user)) {
					k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
					$action = new K4InformationAction(new K4LanguageElement('L_USERDOESNTEXIST'), 'content', TRUE);
					return $action->execute($request);
				}
			} else {
				$user		= $request['dba']->getRow("SELECT * FROM ". K4USERS ." WHERE ip = '". $request['dba']->quote($_REQUEST['user_ip']) ."'");
				if(!is_array($user) || empty($user)) {
					$user		= array('id' => 0, 'banned' => 0, 'name' => '','ip' => '', 'perms' => 0);
				}
			}
			
			
			k4_bread_crumbs($request['template'], $request['dba'], (!isset($_REQUEST['user_ip']) ? 'L_BANUSER' : 'L_BANIPRANGE'));
						
			if($user['perms'] > $request['user']->get('perms')) {
				no_perms_error($request);
				return TRUE;
			}

			if($user['id'] > $request['user']->get('id')) {
				no_perms_error($request);
				return TRUE;
			}

			// unban
			if($user['banned'] == 1) {
				
				$request['dba']->executeUpdate("UPDATE ". K4USERS ." SET banned = 0 WHERE id = ". intval($user['id']));
				$request['dba']->executeUpdate("DELETE FROM ". K4BANNEDUSERS ." WHERE user_id = ". intval($user['id']));
				
				reset_cache('banned_users');

				$action = new K4InformationAction(new K4LanguageElement('L_UNBANNEDUSER', $user['name']), 'content', TRUE, 'index.php', 3);
				return $action->execute($request);

			// ban user
			} else {
				
				$reason		= preg_replace("~(\r\n|\r|\n)~i", '<br />', htmlentities(@$_REQUEST['reason'], ENT_QUOTES));

				$ban		= $request['dba']->prepareStatement("INSERT INTO ". K4BANNEDUSERS ." (user_id,user_name,user_ip,reason,expiry) VALUES (?,?,?,?,?)");
				
				$ip			= isset($_REQUEST['user_ip']) && $_REQUEST['user_ip'] != '' ? str_replace('\*', '([0-9]+?)', preg_quote($_REQUEST['user_ip'])) : preg_quote($user['ip']);

				if($ip != '') {
					$ban->setInt(1, $user['id']);
					$ban->setString(2, $user['name']);
					$ban->setString(3, $ip);
					$ban->setString(4, $reason);
					$ban->setInt(5, time() + (intval(@$_REQUEST['expiry']) * 86400));
					
					$ban->executeUpdate();
				}

				if($user['id'] > 0)
					$request['dba']->executeUpdate("UPDATE ". K4USERS ." SET banned = 1 WHERE id = ". intval($user['id']));
				

				reset_cache('banned_users');

				$action = new K4InformationAction(new K4LanguageElement((!isset($_REQUEST['user_ip']) ? 'L_BANNEDUSER' : 'L_BANNEDIPRANGE'), $user['name']), 'content', TRUE, 'index.php', 3);
				return $action->execute($request);
			}
		} else {
			k4_bread_crumbs($request['template'], $request['dba'], 'L_BANUSER');
			$request['template']->setFile('content', 'finduser.html');
		}
	}
}

class BanIPRange extends FAAction {
	function execute(&$request) {
		if($request['user']->get('perms') < get_map($request['user'], 'banusers', 'can_add', array())) {
			no_perms_error($request);
			return TRUE;
		}
		
		k4_bread_crumbs($request['template'], $request['dba'], 'L_BANIPRANGE');

		$result			= $request['dba']->executeQuery("SELECT * FROM ". K4BANNEDUSERS ." WHERE user_id = 0 AND user_ip <> ''");
		
		if($result->numrows() > 0) {
			$it = &new BannedIPsIterator($result);
			$request['template']->setFile('content_extra', 'bannedips.html');
			$request['template']->setList('bannedips', $it);
		}

		if(isset($_REQUEST['ip'])) {
			$request['template']->setVar('iprange', $_REQUEST['ip']);
		}
		
		$request['template']->setFile('content', 'banuser.html');
		$request['template']->setVisibility('iprange', TRUE);
	}
}

class ModViewBanneIPs extends FAAction {
	function execute(&$request) {
		if($request['user']->get('perms') < get_map($request['user'], 'banusers', 'can_add', array())) {
			no_perms_error($request);
			return TRUE;
		}
		
		k4_bread_crumbs($request['template'], $request['dba'], 'L_BANIPRANGE');

		$result			= $request['dba']->executeQuery("SELECT * FROM ". K4BANNEDUSERS ." WHERE user_id >= 0 AND user_ip <> ''");
		
		$it = &new BannedIPsIterator($result);
		$request['template']->setFile('content', 'bannedips.html');
		$request['template']->setList('bannedips', $it);
	}
}


class LiftIPBan extends FAAction {
	function execute(&$request) {
		
		if($request['user']->get('perms') < get_map($request['user'], 'banusers', 'can_add', array())) {
			no_perms_error($request);
			return TRUE;
		}

		if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
			k4_bread_crumbs($request['template'], $request['dba'], 'L_BANUSER');
			$request['template']->setFile('content', 'finduser.html');
		}

		$ban	= $request['dba']->getRow("SELECT * FROM ". K4BANNEDUSERS ." WHERE id = ". intval($_REQUEST['id']));

		if(!is_array($ban) || empty($ban)) {
			k4_bread_crumbs($request['template'], $request['dba'], 'L_BANUSER');
			$request['template']->setFile('content', 'finduser.html');
		}
		
		k4_bread_crumbs($request['template'], $request['dba'], 'L_LIFTBAN');
		
		$request['dba']->executeUpdate("DELETE FROM ". K4BANNEDUSERS ." WHERE id = ". intval($ban['id']));
		
		reset_cache('banned_users');
		
		$ban['user_ip']		= str_replace('\.', '.', $ban['user_ip']);
		$ban['user_ip']		= str_replace('([0-9]+?)', '*', $ban['user_ip']);

		$action = new K4InformationAction(new K4LanguageElement('L_UNBANNEDIP', $ban['user_ip']), 'content', TRUE, 'index.php', 3);
		return $action->execute($request);
	}
}

class ModWarnUser extends FAAction {
	function execute(&$request) {
		
		global $_SETTINGS;

		if($request['user']->get('perms') < get_map($request['user'], 'warnuser', 'can_add', array())) {
			no_perms_error($request);
			return TRUE;
		}
		
		if(isset($_REQUEST['id'])) {
			$user		= $request['dba']->getRow("SELECT * FROM ". K4USERS ." WHERE id = ". intval($_REQUEST['id']));

			if(!is_array($user) || empty($user)) {
				k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
				$action = new K4InformationAction(new K4LanguageElement('L_USERDOESNTEXIST'), 'content', TRUE);
				return $action->execute($request);
			}

			$request['template']->setVar('L_WARNINGTEXTAREA', sprintf($request['template']->getVar('L_WARNINGTEXTAREA'), $user['name'], $_SETTINGS['bbtitle']));
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_WARNUSER');
			$request['template']->setFile('content', 'warnuser.html');
			$request['template']->setVar('warnuser_id', $user['id']);

		} else {
			k4_bread_crumbs($request['template'], $request['dba'], 'L_WARNUSER');
			$request['template']->setFile('content', 'finduser.html');
		}
	}
}

class ModFlagUser extends FAAction {
	function execute(&$request) {
		if($request['user']->get('perms') < get_map($request['user'], 'flaguser', 'can_add', array())) {
			no_perms_error($request);
			return TRUE;
		}
		

		if(isset($_REQUEST['id'])) {
			$user		= $request['dba']->getRow("SELECT * FROM ". K4USERS ." WHERE id = ". intval($_REQUEST['id']));

			if(!is_array($user) || empty($user)) {
				k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
				$action = new K4InformationAction(new K4LanguageElement('L_USERDOESNTEXIST'), 'content', TRUE);
				return $action->execute($request);
			}
			
			reset_cache('flagged_users');

			if($user['flag_level'] == 0) {
				$request['dba']->executeUpdate("UPDATE ". K4USERS ." SET flag_level = 1 WHERE id = ". intval($user['id']));
			
				k4_bread_crumbs($request['template'], $request['dba'], 'L_FLAGUSER');
				$action = new K4InformationAction(new K4LanguageElement('L_FLAGGEDUSER', $user['name']), 'content', TRUE, 'index.php', 3);
				return $action->execute($request);
			} else {
				$request['dba']->executeUpdate("UPDATE ". K4USERS ." SET flag_level = 0 WHERE id = ". intval($user['id']));
			
				k4_bread_crumbs($request['template'], $request['dba'], 'L_UNFLAGUSER');
				$action = new K4InformationAction(new K4LanguageElement('L_UNFLAGGEDUSER', $user['name']), 'content', TRUE, 'index.php', 3);
				return $action->execute($request);
			}

		} else {
			k4_bread_crumbs($request['template'], $request['dba'], 'L_FLAGUSER');
			$request['template']->setFile('content', 'finduser.html');
		}
	}
}

class ModFindUsers extends FAAction {
	function execute(&$request) {
		
		global $_URL;

		if(($request['user']->get('perms') < get_map($request['user'], 'banuser', 'can_add', array()))
			&& ($request['user']->get('perms') < get_map($request['user'], 'warnuser', 'can_add', array()))
			&& ($request['user']->get('perms') < get_map($request['user'], 'flaguser', 'can_add', array()))
			) {
			no_perms_error($request);
			return TRUE;
		}
		
		// include the wildcards in the valid username match
		if(!isset($_REQUEST['username']) || $_REQUEST['username'] == '' || !preg_match('~^[a-zA-Z]([a-zA-Z0-9]*[-_ \*\%]?)*[a-zA-Z0-9]*$~', $_REQUEST['username'])) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_BADUSERS'), 'content', TRUE);
			return $action->execute($request);
		}
		
		$url				= new FAUrl($_URL->__toString());
		
		$username			= str_replace('*', '%', $request['dba']->quote($_REQUEST['username']));
		$num_users			= $request['dba']->getValue("SELECT COUNT(*) FROM ". K4USERS ." WHERE lower(name) LIKE lower('%$username%') ORDER BY name DESC");
		
		$request['template']->setVar('search_num_results', $num_users);

		$perpage			= isset($_REQUEST['limit']) && ctype_digit($_REQUEST['limit']) && intval($_REQUEST['limit']) > 0 ? intval($_REQUEST['limit']) : 25;
		$num_pages			= @ceil($num_users / $perpage);
		$page				= isset($_REQUEST['page']) && ctype_digit($_REQUEST['page']) && intval($_REQUEST['page']) > 0 ? intval($_REQUEST['page']) : 1;
		$url->args['username']	= $username;
		$pager				= &new FAPaginator($url, $num_users, $page, $perpage);
		
		if($num_users > $perpage) {
			$request['template']->setPager('users_pager', $pager);

			/* Create a friendly url for our pager jump */
			$page_jumper	= new FAUrl($_URL->__toString());
			$page_jumper->args['limit']		= $perpage;
			$page_jumper->args['page']		= FALSE;
			$page_jumper->anchor			= FALSE;
			$request['template']->setVar('pagejumper_url', preg_replace('~&amp;~i', '&', $page_jumper->__toString()));
		}
		
		k4_bread_crumbs($request['template'], $request['dba'], 'L_FINDUSERS');

		/* Outside valid page range, redirect */
		if(!$pager->hasPage($page) && $num_pages > 0) {
			$action = new K4InformationAction(new K4LanguageElement('L_PASTPAGELIMIT'), 'content', FALSE, 'mod.php?act=findusers&username='. $username .'&limit='. $perpage .'&page='. $num_pages, 3);
			return $action->execute($request);
		}
		
		$result				= $request['dba']->executeQuery("SELECT * FROM ". K4USERS ." WHERE lower(name) LIKE lower('%$username%') ORDER BY name DESC");		
		
		$it = &new UsersIterator($result);
		$request['template']->setList('users', $it);
		$request['template']->setFile('content', 'foundusers.html');

	}
}


class ModSendWarning extends FAAction {
	function execute(&$request) {
		
		global $_SETTINGS;

		if($request['user']->get('perms') < get_map($request['user'], 'warnuser', 'can_add', array())) {
			no_perms_error($request);
			return TRUE;
		}
		
		if(isset($_REQUEST['id'])) {
			$user		= $request['dba']->getRow("SELECT * FROM ". K4USERS ." WHERE id = ". intval($_REQUEST['id']));
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_WARNUSER');

			if(!isset($_REQUEST['warning']) || $_REQUEST['warning'] == '') {
				
				$action = new K4InformationAction(new K4LanguageElement('L_PASTPAGELIMIT'), 'content', FALSE, 'mod.php?act=findusers&username='. $username .'&limit='. $perpage .'&page='. $num_pages, 3);
				return $action->execute($request);
			}

			if(!is_array($user) || empty($user)) {
				k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTWARNING'), 'content', TRUE);
				return $action->execute($request);
			}

			$request['dba']->executeUpdate("UPDATE ". K4USERS ." SET warn_level=warn_level+1 WHERE id = ". intval($user['id']));
			
			email_user($user['email'], $request['template']->getVar('L_WARNING'), $_REQUEST['warning']);
			
			$action = new K4InformationAction(new K4LanguageElement('L_SENTWARNING', $user['name']), 'content', TRUE, 'index.php', 3);
			return $action->execute($request);

		} else {
			k4_bread_crumbs($request['template'], $request['dba'], 'L_WARNUSER');
			$request['template']->setFile('content', 'finduser.html');
		}
	}
}

class BannedIPsIterator extends FAProxyIterator {
	
	var $result;
	
	function BannedIPsIterator(&$result) {
		$this->__construct($result);
	}

	function __construct(&$result) {
		$this->result			= &$result;
		
		parent::__construct($this->result);
	}

	function &current() {
		$temp					= parent::current();
		
		$temp['user_ip']		= str_replace('\.', '.', $temp['user_ip']);
		$temp['user_ip']		= str_replace('([0-9]+?)', '*', $temp['user_ip']);
		
		if(!$this->hasNext())
			$this->result->free();

		return $temp;
	}
}

?>