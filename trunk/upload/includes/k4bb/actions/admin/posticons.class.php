<?php
/**
* k4 Bulletin Board, posticons.php
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
* @version $Id: posticons.class.php,v 1.3 2005/04/19 21:51:45 k4st Exp $
* @package k42
*/

if(!defined('IN_K4'))
	return;

class AdminPostIcons extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			$icons			= &$request['dba']->executeQuery("SELECT * FROM ". K4POSTICONS);

			$request['template']->setList('posticons', $icons);
			
			$request['template']->setFile('content', 'admin.html');
			$request['template']->setFile('admin_panel', 'posticons_manage.html');
		} else {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

			return $action->execute($request);
		}

		return TRUE;
	}
}

class AdminAddPostIcon extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
						
			$request['template']->setFile('content', 'admin.html');
			$request['template']->setFile('admin_panel', 'posticons_add.html');
		} else {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

			return $action->execute($request);
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
				return TRUE;
			}
			
			/**
			 * Add the icon finally
			 */
			$query		= &$request['dba']->prepareStatement("INSERT INTO ". K4POSTICONS ." (description, image) VALUES (?,?)");
			$query->setString(1, $_REQUEST['description']);
			$query->setString(2, $filename);

			$query->executeUpdate();

			if(isset($_FILES['image_upload']) && is_array($_FILES['image_upload'])) {
				$dir		= BB_BASE_DIR . '/tmp/upload/posticons';
				
				@chmod($dir, 0777);
				@move_uploaded_file($_FILES['image_upload']['tmp_name'], $dir .'/'. $filename);
			}

			$action = new K4InformationAction(new K4LanguageElement('L_ADDEDPOSTICON'), 'content', TRUE, 'admin.php?act=posticons', 3);


			return $action->execute($request);

		} else {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

			return $action->execute($request);
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
				return TRUE;
			}
			
			/* Remove the icon from the db */
			$request['dba']->executeUpdate("DELETE FROM ". K4POSTICONS ." WHERE id = ". intval($icon['id']));

			/* Change all of the topics to have no icon */
			$request['dba']->executeUpdate("UPDATE ". K4TOPICS ." SET posticon = '' WHERE posticon = '". $request['dba']->quote($icon['image']) ."'");
			
			/* Remove the actual icon */
			$dir		= BB_BASE_DIR . '/tmp/upload/posticons';

			@chmod($dir);
			@unlink($dir .'/'. $icon['image']);
			
			$action = new K4InformationAction(new K4LanguageElement('L_REMOVEDPOSTICON'), 'content', TRUE, 'admin.php?act=posticons', 3);

			
			return $action->execute($request);
		} else {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

			return $action->execute($request);
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
				return TRUE;
			}

			$icon			= $request['dba']->getRow("SELECT * FROM ". K4POSTICONS ." WHERE id = ". intval($_REQUEST['id']));
			
			if(!is_array($icon) || empty($icon)) {
				$action = new K4InformationAction(new K4LanguageElement('L_POSTICONDOESNTEXIST'), 'content', FALSE);

				return $action->execute($request);
				return TRUE;
			}

			foreach($icon as $key => $val) {
				$request['template']->setVar('icon_'. $key, $val);
			}
			
			$request['template']->setFile('content', 'admin.html');
			$request['template']->setFile('admin_panel', 'posticons_edit.html');
		} else {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

			return $action->execute($request);
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
				return TRUE;
			}

			$icon			= $request['dba']->getRow("SELECT * FROM ". K4POSTICONS ." WHERE id = ". intval($_REQUEST['id']));
			
			if(!is_array($icon) || empty($icon)) {
				$action = new K4InformationAction(new K4LanguageElement('L_POSTICONDOESNTEXIST'), 'content', FALSE);

				return $action->execute($request);
				return TRUE;
			}
			if(!isset($_REQUEST['description']) || $_REQUEST['description'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTICONDESC'), 'content', TRUE);

				return $action->execute($request);
				return TRUE;
			}
			if(!isset($_REQUEST['image_browse']) && !isset($_FILES['image_upload'])) {
				$action = new K4InformationAction(new K4LanguageElement('L_NEEDCHOOSEICONIMG'), 'content', TRUE);

				return $action->execute($request);
				return TRUE;
			}
			if(isset($_FILES['image_upload']) && is_array($_FILES['image_upload'])) {
				$filename	= $_FILES['image_upload']['tmp_name'];
			}
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
				return TRUE;
			}
			
			/**
			 * Update the icon finally
			 */
			$query		= &$request['dba']->prepareStatement("UPDATE ". K4POSTICONS ." SET description=?,image=? WHERE id=?");
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
			$request['dba']->executeUpdate("UPDATE ". K4TOPICS ." SET posticon = '". $request['dba']->quote($filename) ."' WHERE posticon = '". $request['dba']->quote($icon['image']) ."'");

			$action = new K4InformationAction(new K4LanguageElement('L_UPDATEDPOSTICON'), 'content', TRUE, 'admin.php?act=posticons', 3);


			return $action->execute($request);

		} else {
			$action = new K4InformationAction(new K4LanguageElement('L_YOUNEEDPERMS'), 'content', FALSE);

			return $action->execute($request);
		}

		return TRUE;
	}
}

?>