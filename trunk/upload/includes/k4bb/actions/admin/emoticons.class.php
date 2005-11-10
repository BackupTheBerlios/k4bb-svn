<?php
/**
* k4 Bulletin Board, emoticons.class.php
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
* @version $Id: emoticons.class.php 144 2005-07-05 02:29:07Z Peter Goodman $
* @package k42
*/



if(!defined('IN_K4')) {
	return;
}

class AdminEmoticons extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			$icons			= $request['dba']->executeQuery("SELECT * FROM ". K4EMOTICONS);

			$request['template']->setList('emoticons', $icons);
			
			$request['template']->setFile('content', 'emoticons_manage.html');

			k4_bread_crumbs($request['template'], $request['dba'], 'L_EMOTICONS');
			$request['template']->setVar('posts_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/posts.html');
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminAddEmoticon extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
						
			$request['template']->setFile('content', 'emoticons_add.html');

			k4_bread_crumbs($request['template'], $request['dba'], 'L_EMOTICONS');
			$request['template']->setVar('posts_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/posts.html');
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminInsertEmoticon extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			/**		
			 * Error checking on all fields :P
			 */
			if(!isset($_REQUEST['description']) || $_REQUEST['description'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTICONDESC'), 'content', TRUE);
				return $action->execute($request);
			}

			if(!isset($_REQUEST['typed']) || $_REQUEST['typed'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTICONTYPED'), 'content', TRUE);
				return $action->execute($request);
			}
			
			if(!isset($_REQUEST['image_browse']) && !isset($_FILES['image_upload'])) {
				$action = new K4InformationAction(new K4LanguageElement('L_NEEDCHOOSEICONIMG'), 'content', TRUE);
				return $action->execute($request);
			}
			
			if(isset($_FILES['image_upload']) && is_array($_FILES['image_upload']))
				$filename	= $_FILES['image_upload']['tmp_name'];
			
			if(isset($_REQUEST['image_browse']) && $_REQUEST['image_browse'] != '') {
				$filename	= $_REQUEST['image_browse'];
			} else {
				$action = new K4InformationAction(new K4LanguageElement('L_NEEDCHOOSEICONIMG'), 'content', TRUE);
				return $action->execute($request);
			}
			

			$file_ext		= explode(".", $filename);
			$exts			= array('gif', 'jpg', 'jpeg', 'bmp', 'png', 'tiff');
			
			if(count($file_ext) >= 2) {
				$file_ext		= $file_ext[count($file_ext) - 1];

				if(!in_array(strtolower($file_ext), $exts)) {
					$action = new K4InformationAction(new K4LanguageElement('L_INVALIDICONEXT'), 'content', TRUE);
					return $action->execute($request);
				}
			} else {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDICONEXT'), 'content', TRUE);
				return $action->execute($request);
			}
			
			/**
			 * Add the icon finally
			 */
			$query		= $request['dba']->prepareStatement("INSERT INTO ". K4EMOTICONS ." (description, typed, image, clickable) VALUES (?,?,?,?)");
			$query->setString(1, $_REQUEST['description']);
			$query->setString(2, $_REQUEST['typed']);
			$query->setString(3, $filename);
			$query->setInt(4, @$_REQUEST['clickable']);

			$query->executeUpdate();

			if(isset($_FILES['image_upload']) && is_array($_FILES['image_upload'])) {
				$dir		= BB_BASE_DIR . '/tmp/upload/emoticons';
				
				__chmod($dir, 0777);
				@move_uploaded_file($_FILES['image_upload']['tmp_name'], $dir .'/'. $filename);
			}

			k4_bread_crumbs($request['template'], $request['dba'], 'L_EMOTICONS');
			$request['template']->setVar('posts_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/posts.html');

			$action = new K4InformationAction(new K4LanguageElement('L_ADDEDEMOTICON'), 'content', TRUE, 'admin.php?act=emoticons', 3);
			return $action->execute($request);

		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminRemoveEmoticon extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_EMOTCIONDOESNTEXIST'), 'content', FALSE);
				return $action->execute($request);
			}

			$icon			= $request['dba']->getRow("SELECT * FROM ". K4EMOTICONS ." WHERE id = ". intval($_REQUEST['id']));
			
			if(!is_array($icon) || empty($icon)) {
				$action = new K4InformationAction(new K4LanguageElement('L_EMOTICONDOESNTEXIST'), 'content', FALSE);
				return $action->execute($request);
			}
			
			/* Remove the icon from the db */
			$request['dba']->executeUpdate("DELETE FROM ". K4EMOTICONS ." WHERE id = ". intval($icon['id']));
			
			/* Remove the actual icon */
			$dir		= BB_BASE_DIR . '/tmp/upload/emoticons';

			__chmod($dir);
			@unlink($dir .'/'. $icon['image']);

			k4_bread_crumbs($request['template'], $request['dba'], 'L_EMOTICONS');
			$request['template']->setVar('posts_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/posts.html');
			
			$action = new K4InformationAction(new K4LanguageElement('L_REMOVEDPOSTICON'), 'content', TRUE, 'admin.php?act=posticons', 3);			
			return $action->execute($request);

		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminEditEmoticon extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_POSTICONSDOESNTEXIST'), 'content', FALSE);
				return $action->execute($request);
			}

			$icon			= $request['dba']->getRow("SELECT * FROM ". K4EMOTICONS ." WHERE id = ". intval($_REQUEST['id']));
			
			if(!is_array($icon) || empty($icon)) {
				$action = new K4InformationAction(new K4LanguageElement('L_EMOTCIONDOESNTEXIST'), 'content', FALSE);
				return $action->execute($request);
			}

			foreach($icon as $key => $val) {
				$request['template']->setVar('icon_'. $key, $val);
			}
			
			$request['template']->setFile('content', 'emoticons_edit.html');

			k4_bread_crumbs($request['template'], $request['dba'], 'L_EMOTICONS');
			$request['template']->setVar('posts_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/posts.html');
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminUpdateEmoticon extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			/**		
			 * Error checking on all fields :P
			 */

			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_EMOTCIONDOESNTEXIST'), 'content', FALSE);
				return $action->execute($request);
			}

			$icon			= $request['dba']->getRow("SELECT * FROM ". K4EMOTICONS ." WHERE id = ". intval($_REQUEST['id']));
			
			if(!is_array($icon) || empty($icon)) {
				$action = new K4InformationAction(new K4LanguageElement('L_EMOTCIONDOESNTEXIST'), 'content', FALSE);
				return $action->execute($request);
			}

			if(!isset($_REQUEST['description']) || $_REQUEST['description'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTICONDESC'), 'content', TRUE);
				return $action->execute($request);
			}

			if(!isset($_REQUEST['typed']) || $_REQUEST['typed'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTICONTYPED'), 'content', TRUE);
				return $action->execute($request);
			}
			
			if(!isset($_REQUEST['image_browse']) && !isset($_FILES['image_upload'])) {
				$action = new K4InformationAction(new K4LanguageElement('L_NEEDCHOOSEICONIMG'), 'content', TRUE);
				return $action->execute($request);
			}
			
			if(isset($_FILES['image_upload']) && is_array($_FILES['image_upload']))
				$filename	= $_FILES['image_upload']['tmp_name'];
			
			if(isset($_REQUEST['image_browse']) && $_REQUEST['image_browse'] != '') {
				$filename	= $_REQUEST['image_browse'];
			} else {
				$action = new K4InformationAction(new K4LanguageElement('L_NEEDCHOOSEICONIMG'), 'content', TRUE);
				return $action->execute($request);
			}
			

			$file_ext		= explode(".", $filename);
			$exts			= array('gif', 'jpg', 'jpeg', 'bmp', 'png', 'tiff');
			
			if(count($file_ext) >= 2) {
				$file_ext		= $file_ext[count($file_ext) - 1];

				if(!in_array(strtolower($file_ext), $exts)) {
					$action = new K4InformationAction(new K4LanguageElement('L_INVALIDICONEXT'), 'content', TRUE);
					return $action->execute($request);
				}
			} else {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDICONEXT'), 'content', TRUE);
				return $action->execute($request);
			}
			
			/**
			 * Add the icon finally
			 */
			$query		= $request['dba']->prepareStatement("UPDATE ". K4EMOTICONS ." SET description=?,typed=?,image=?,clickable=? WHERE id=?");
			$query->setString(1, $_REQUEST['description']);
			$query->setString(2, $_REQUEST['typed']);
			$query->setString(3, $filename);
			$query->setInt(4, @$_REQUEST['clickable']);
			$query->setInt(5, $icon['id']);

			$query->executeUpdate();

			if(isset($_FILES['image_upload']) && is_array($_FILES['image_upload'])) {
				$dir		= BB_BASE_DIR . '/tmp/upload/emoticons';
				
				@chmod($dir, 0777);
				@move_uploaded_file($_FILES['image_upload']['tmp_name'], $dir .'/'. $filename);
			}

			k4_bread_crumbs($request['template'], $request['dba'], 'L_EMOTICONS');
			$request['template']->setVar('posts_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/posts.html');

			$action = new K4InformationAction(new K4LanguageElement('L_UPDATEDEMOTICON'), 'content', TRUE, 'admin.php?act=emoticons', 3);
			return $action->execute($request);

		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminUpdateEmoticonClick extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_POSTICONSDOESNTEXIST'), 'content', FALSE);
				return $action->execute($request);	
			}

			$icon			= $request['dba']->getRow("SELECT * FROM ". K4EMOTICONS ." WHERE id = ". intval($_REQUEST['id']));
			
			if(!is_array($icon) || empty($icon)) {
				$action = new K4InformationAction(new K4LanguageElement('L_EMOTCIONDOESNTEXIST'), 'content', FALSE);
				return $action->execute($request);
			}

			$clickable		= $icon['clickable'] == 1 ? 0 : 1;

			$request['dba']->executeUpdate("UPDATE ". K4EMOTICONS ." SET clickable = ". intval($clickable) ." WHERE id = ". intval($icon['id']));
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_EMOTICONS');
			$request['template']->setVar('posts_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/posts.html');

			$action = new K4InformationAction(new K4LanguageElement('L_UPDATEDEMOCLICK'), 'content', TRUE, 'admin.php?act=emoticons', 3);
			return $action->execute($request);

		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

?>