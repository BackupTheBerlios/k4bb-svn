<?php
/**
* k4 Bulletin Board, posticons.php
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
* @version $Id: posticons.class.php 110 2005-06-13 20:48:58Z Peter Goodman $
* @package k42
*/

if(!defined('IN_K4'))
	return;

class AdminPostIcons extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			$icons			= $request['dba']->executeQuery("SELECT * FROM ". K4POSTICONS);

			$request['template']->setList('posticons', $icons);
			
			$request['template']->setFile('content', 'posticons_manage.html');

			k4_bread_crumbs($request['template'], $request['dba'], 'L_POSTICONS');
			$request['template']->setVar('posts_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/posts.html');
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminAddPostIcon extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
						
			$request['template']->setFile('content', 'posticons_add.html');

			k4_bread_crumbs($request['template'], $request['dba'], 'L_POSTICONS');
			$request['template']->setVar('posticon_action', 'admin.php?act=posticons_insert');
			$request['template']->setVar('posts_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/posts.html');
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminInsertPostIcon extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			/**		
			 * Error checking on all _three_ fields :P
			 */
			if(!isset($_REQUEST['description']) || $_REQUEST['description'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTICONDESC'), 'content', TRUE);
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
				return TRUE;
			}
			

			$file_ext		= explode(".", $filename);
			$exts			= array('gif', 'jpg', 'jpeg', 'bmp', 'png', 'tiff');
			
			if(count($file_ext) >= 2) {
				$file_ext		= $file_ext[count($file_ext) - 1];

				if(!in_array(strtolower($file_ext), $exts)) {
					$action = new K4InformationAction(new K4LanguageElement('L_INVALIDICONEXT'), 'content', TRUE);

					return $action->execute($request);
					return TRUE;
				}
			} else {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDICONEXT'), 'content', TRUE);
				return $action->execute($request);
			}
			
			/**
			 * Add the icon finally
			 */
			$query		= $request['dba']->prepareStatement("INSERT INTO ". K4POSTICONS ." (description, image) VALUES (?,?)");
			$query->setString(1, $_REQUEST['description']);
			$query->setString(2, $filename);

			$query->executeUpdate();

			if(isset($_FILES['image_upload']) && is_array($_FILES['image_upload'])) {
				$dir		= BB_BASE_DIR . '/tmp/upload/posticons';
				
				@chmod($dir, 0777);
				@move_uploaded_file($_FILES['image_upload']['tmp_name'], $dir .'/'. $filename);
			}

			k4_bread_crumbs($request['template'], $request['dba'], 'L_POSTICONS');
			$request['template']->setVar('posts_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/posts.html');

			$action = new K4InformationAction(new K4LanguageElement('L_ADDEDPOSTICON'), 'content', TRUE, 'admin.php?act=posticons', 3);
			return $action->execute($request);

		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminRemovePostIcon extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_POSTICONDOESNTEXIST'), 'content', FALSE);

				return $action->execute($request);
				return TRUE;
			}

			$icon			= $request['dba']->getRow("SELECT * FROM ". K4POSTICONS ." WHERE id = ". intval($_REQUEST['id']));
			
			if(!is_array($icon) || empty($icon)) {
				$action = new K4InformationAction(new K4LanguageElement('L_POSTICONDOESNTEXIST'), 'content', FALSE);
				return $action->execute($request);
			}
			
			/* Remove the icon from the db */
			$request['dba']->executeUpdate("DELETE FROM ". K4POSTICONS ." WHERE id = ". intval($icon['id']));

			/* Change all of the topics to have no icon */
			$request['dba']->executeUpdate("UPDATE ". K4POSTS ." SET posticon = '' WHERE posticon = '". $request['dba']->quote($icon['image']) ."'");
			
			/* Remove the actual icon */
			$dir		= BB_BASE_DIR . '/tmp/upload/posticons';

			@chmod($dir);
			@unlink($dir .'/'. $icon['image']);
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_POSTICONS');
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

class AdminEditPostIcon extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_POSTICONDOESNTEXIST'), 'content', FALSE);
				return $action->execute($request);
			}

			$icon			= $request['dba']->getRow("SELECT * FROM ". K4POSTICONS ." WHERE id = ". intval($_REQUEST['id']));
			
			if(!is_array($icon) || empty($icon)) {
				$action = new K4InformationAction(new K4LanguageElement('L_POSTICONDOESNTEXIST'), 'content', FALSE);
				return $action->execute($request);
			}

			foreach($icon as $key => $val) {
				$request['template']->setVar('icon_'. $key, $val);
			}
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_POSTICONS');
			$request['template']->setVar('posticon_action', 'admin.php?act=posticons_update');
			$request['template']->setVar('posts_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/posts.html');
			$request['template']->setVar('is_edit', 1);
			$request['template']->setFile('content', 'posticons_add.html');
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminUpdatePostIcon extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			/**		
			 * Error checking on all _three_ fields :P
			 */

			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_POSTICONDOESNTEXIST'), 'content', FALSE);
				return $action->execute($request);	
			}

			$icon			= $request['dba']->getRow("SELECT * FROM ". K4POSTICONS ." WHERE id = ". intval($_REQUEST['id']));
			
			if(!is_array($icon) || empty($icon)) {
				$action = new K4InformationAction(new K4LanguageElement('L_POSTICONDOESNTEXIST'), 'content', FALSE);
				return $action->execute($request);
			}
			if(!isset($_REQUEST['description']) || $_REQUEST['description'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTICONDESC'), 'content', TRUE);
				return $action->execute($request);
			}
			if(!isset($_REQUEST['image_browse']) && !isset($_FILES['image_upload'])) {
				$action = new K4InformationAction(new K4LanguageElement('L_NEEDCHOOSEICONIMG'), 'content', TRUE);
				return $action->execute($request);
			}
			if(isset($_FILES['image_upload']) && is_array($_FILES['image_upload'])) {
				$filename	= $_FILES['image_upload']['tmp_name'];
			}
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
			 * Update the icon finally
			 */
			$query		= $request['dba']->prepareStatement("UPDATE ". K4POSTICONS ." SET description=?,image=? WHERE id=?");
			$query->setString(1, $_REQUEST['description']);
			$query->setString(2, $filename);
			$query->setInt(3, $icon['id']);

			$query->executeUpdate();

			if(isset($_FILES['image_upload']) && is_array($_FILES['image_upload'])) {
				$dir		= BB_BASE_DIR . '/tmp/upload/posticons';
				
				@chmod($dir, 0777);
				@move_uploaded_file($_FILES['image_upload']['tmp_name'], $dir .'/'. $filename);
			}
			
			/* Change all of the topics to have no icon */
			$request['dba']->executeUpdate("UPDATE ". K4POSTS ." SET posticon = '". $request['dba']->quote($filename) ."' WHERE posticon = '". $request['dba']->quote($icon['image']) ."'");
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_POSTICONS');
			$request['template']->setVar('posts_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/posts.html');

			$action = new K4InformationAction(new K4LanguageElement('L_UPDATEDPOSTICON'), 'content', TRUE, 'admin.php?act=posticons', 3);
			return $action->execute($request);

		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

?>