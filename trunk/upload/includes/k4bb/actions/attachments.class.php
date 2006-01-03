<?php
/**
* k4 Bulletin Board, attachments.class.php
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

/**
 * Deal with file attachments
 *
 * @param object request		General request variable passed to all actions
 * @param array forum			The current forum of the topic
 * @param int post_id			The topic id that the files are being attached to
 *
 * @author Peter Goodman
 */
function attach_files(&$request, $forum, $post) {
	$errors			= array();
	if($request['user']->get('perms') >= get_map( 'attachments', 'can_add', array('forum_id'=>$forum['forum_id']))) {
		
		$size			= 0;
		$max_size		= $request['template']->getVar('maxattachsize');
		$num_files		= 0;
		$in_db			= $request['template']->getVar('storeattachesdb') == 1 ? TRUE : FALSE;
		$num_attaches	= $request['template']->getVar('nummaxattaches');
		
		$total_size		= intval($request['dba']->getValue("SELECT SUM(file_size) FROM ". K4ATTACHMENTS ." WHERE user_id = ". intval($request['user']->get('id')) ." AND user_id > 0"));

		$upload_dir		= BB_BASE_DIR .'/tmp/upload/attachments/';

		// change the upload director if we need to
		if(!$in_db && $request['user']->isMember()) {
			$upload_dir	= BB_BASE_DIR .'/tmp/upload/attachments/'. $request['user']->get('id') .'/';
		}

		// get any already uploaded files for this topic
		$files_on_file	= $request['dba']->executeQuery("SELECT * FROM ". K4ATTACHMENTS ." WHERE post_id = ". $post['post_id']);
		while($files_on_file->next()) {
			$temp		= $files_on_file->current();
			
			// increment how much space this person's used so far
			$size		+= $temp['file_size'];
			
			// reduce the num attaches value by one
			$num_attaches--;
		}

		// get our valid filetypes
		$filetypes		= explode(" ", trim(str_replace(',', ' ', $request['template']->getVar('attachextensions'))));
		
		// have we specified any files to attach?
		if(isset($_FILES) && is_array($_FILES) && count($_FILES) > 0) {
			
			__chmod(BB_BASE_DIR .'/tmp/upload/attachments', 0777);

			// go through the attachments
			for($i = 1; $i <= $num_attaches; $i++) {
				
				if(isset($_FILES['attach'. $i])) {

					// check if this files is valid to upload	
					if(($_FILES['attach'. $i]['size'] <= $max_size) 
						&& ($_FILES['attach'. $i]['size'] + $size) <= $max_size 
						&& (($total_size + $_FILES['attach'. $i]['size']) <= $request['template']->getVar('maxattachquota'))
						) {
						
						// get what file type this file is
						$filetype		= file_extension($_FILES['attach'. $i]['name']);

						if(in_array($filetype, $filetypes) && is_writeable($upload_dir)) {
							
							if(!file_exists($upload_dir)) {
								
								// make out directory
								@mkdir($upload_dir, 0777);
								
								// copy the .htaccess file over
								@copy(BB_BASE_DIR .'/tmp/upload/attachments/.htaccess', $upload_dir . '.htaccess');
							}
							
							__chmod($upload_dir, 0777);
							
							//if($_FILES['attach'. $i]['name'] != 'index.php') {

								// upload the file
								$result				= @move_uploaded_file($_FILES['attach'. $i]['tmp_name'], $upload_dir . $_FILES['attach'. $i]['name']);
			
								// did the upload go smoothly?
								if($result) {
									
									// make sure that the file was actually uploaded
									if(file_exists($upload_dir . $_FILES['attach'. $i]['name']) && is_readable($upload_dir . $_FILES['attach'. $i]['name'])) {
										
										// change the file permissions on the just uploaded file
										__chmod($upload_dir . $_FILES['attach'. $i]['name'], 0777);
										
										// prepare the sql query to insert it into the db
										$insert			= $request['dba']->prepareStatement("INSERT INTO ". K4ATTACHMENTS ." (post_id,user_id,user_name,file_type,mime_type,file_size,file_contents,mdfive,file_name,in_db,created,forum_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
										$insert->setInt(1, $post['post_id']);
										$insert->setInt(2, $request['user']->get('id'));
										$insert->setString(3, $request['user']->get('name'));
										$insert->setString(4, $filetype);
										$insert->setString(5, $_FILES['attach'. $i]['type']);
										$insert->setInt(6, $_FILES['attach'. $i]['size']);
										$insert->setString(7, ($in_db ? file_get_contents($upload_dir . $_FILES['attach'. $i]['name']) : ''));
										$insert->setString(8, md5_file($upload_dir . $_FILES['attach'. $i]['name']));
										$insert->setString(9, $_FILES['attach'. $i]['name']);
										$insert->setInt(10, ($in_db ? 1 : 0));
										$insert->setInt(11, time());
										$insert->setInt(12, $forum['forum_id']);

										// insert the file into the database
										$insert->executeUpdate();
										
										// delete the file
										if($in_db)
											@unlink($upload_dir . $_FILES['attach'. $i]['name']);
										
										// increase how much space we've used so far for the upload
										$size		+= $_FILES['attach'. $i]['size'];
										$total_size += $_FILES['attach'. $i]['size'];
										$num_files++;
									}
								} else {
									$errors[] = basename($_FILES['attach'. $i]['name']);
								}
							//}
						}
					}
				}
			}
			
			// update the topic / reply
			if($num_files > 0) {
				if($post['row_type'] > 0) {
					$request['dba']->executeUpdate("UPDATE ". K4POSTS ." SET total_attachments=total_attachments+". $num_files .", attachments=attachments+". $num_files ." WHERE post_id=". intval(($post['row_type'] & REPLY ? $post['parent_id'] : $post['post_id'])) );
					if($post['row_type'] & REPLY) {
						$request['dba']->executeUpdate("UPDATE ". K4POSTS ." SET attachments=attachments+". $num_files ." WHERE post_id=". intval($post['post_id']) );
					}
				}
			}
			
		}
	}

	return $errors;
}

/**
 * Attach files that are in 'limbo'
 */
function attach_limbo_files(&$request, $forum, $post) {
	
	$files	= $request['dba']->executeQuery("SELECT * FROM ". K4ATTACHMENTS ." WHERE post_id = 0 AND forum_id=". $forum['forum_id'] ." AND user_id=". intval($request['user']->get('id')) );
	
	if($files->hasNext()) {
		
		$num_files = $files->numrows();

		$request['dba']->executeUpdate("UPDATE ". K4ATTACHMENTS ." SET post_id=". intval($post['post_id']) ." WHERE post_id=0 AND forum_id=". $forum['forum_id'] ." AND user_id=". intval($request['user']->get('id')) );

		$request['dba']->executeUpdate("UPDATE ". K4POSTS ." SET total_attachments=total_attachments+". $num_files .", attachments=attachments+". $num_files ." WHERE post_id=". intval(($post['row_type'] & REPLY ? $post['parent_id'] : $post['post_id'])) );
		if($post['row_type'] & REPLY) {
			$request['dba']->executeUpdate("UPDATE ". K4POSTS ." SET attachments=attachments+". $num_files ." WHERE post_id=". intval($post['post_id']) );
		}
	}
	return TRUE;
}

/**
 * Remove attachments
 */
function remove_attachments(&$request, $post, $update = TRUE) {
	$attachments	= $request['dba']->executeQuery("SELECT * FROM ". K4ATTACHMENTS ." WHERE post_id = ". intval($post['post_id']) . ($post['post_id'] > 0 ? " AND user_id=". intval($request['user']->get('id')) : ""));
	
	$upload_dir		= BB_BASE_DIR .'/tmp/upload/attachments/';

	// change the upload director if we need to
	if($request['user']->isMember()) {
		$upload_dir	= BB_BASE_DIR .'/tmp/upload/attachments/'. $request['user']->get('id') .'/';
	}
	
	__chmod($upload_dir, 0777);

	if($attachments->numrows() > 0) {
		while($attachments->next()) {
			$attachment		= $attachments->current();

			if(file_exists($upload_dir . $attachment['file_name'])) {
				__chmod($upload_dir . $attachment['file_name'], 0777);
				@unlink($upload_dir . $attachment['file_name']);
			}
		}
	}
	
	$num_files		= $attachments->numrows();
	
	// fix the attachment counts for topics/replies
	if($update && $post['post_id'] > 0) {
		$request['dba']->executeUpdate("UPDATE ". K4POSTS ." SET total_attachments=total_attachments-". $num_files .", attachments=attachments-". $num_files ." WHERE post_id=". intval(($post['row_type'] & REPLY ? $post['parent_id'] : $post['post_id'])) );
		if($post['row_type'] & REPLY) {
			$request['dba']->executeUpdate("UPDATE ". K4POSTS ." SET attachments=attachments-". $num_files ." WHERE post_id=". intval($post['post_id']) );
		}
	}
	
	// delete them
	$request['dba']->executeUpdate("DELETE FROM ". K4ATTACHMENTS ." WHERE post_id = ". intval($post['post_id']) . ($post['post_id'] > 0 ? " AND user_id=". intval($request['user']->get('id')) : "") );
}

/**
 * Add either the file inputs or remove attachment links
 */
function post_attachment_options(&$request, $forum, $post) {
	if($request['user']->get('perms') >= get_map( 'attachments', 'can_add', array('forum_id'=>$forum['forum_id']))) {
		
		$num_attachments	= $request['template']->getVar('nummaxattaches') - $post['attachments'];
		
		// make the file input fields for the file attachments
		$attach_inputs		= '';
		for($i = 1; $i <= $num_attachments; $i++) {
			$attach_inputs	.= '<br /><input type="file" class="inputbox" name="attach'. $i .'" id="attach'. $i .'" value="" size="55" />';
		}

		// do we have any current attachments?
		if($post['attachments'] > 0) {
			$post_attachments	= $request['dba']->executeQuery("SELECT * FROM ". K4ATTACHMENTS ." WHERE post_id = ". intval($post['post_id']));
			while($post_attachments->next()) {
				$temp			= $post_attachments->current();
				$attach_inputs	.= '<br /><span class="smalltext">'. k4_htmlentities($temp['file_name'], ENT_QUOTES) . '&nbsp;-&nbsp;<a href="viewfile.php?act=remove_attach&amp;id='. $temp['id'] .'&amp;post_id='. $post['post_id'] .'" title="'. $request['template']->getVar('L_REMOVEATTACHMENT') .'">'. $request['template']->getVar('L_REMOVEATTACHMENT') .'</a></span>';
			}
		}
		
		$request['template']->setVar('attach_inputs', $attach_inputs);
	}
}

/**
 * Allow the viewing of attachments
 */
class K4ViewAttachment extends FAAction {
	function execute(&$request) {
		
		global $_QUERYPARAMS;
		
		k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');

		if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
			$action = new K4InformationAction(new K4LanguageElement('L_BADATTACHMENT'), 'content', FALSE);
			return $action->execute($request);
		}

		if(isset($_REQUEST['post_id']) && intval($_REQUEST['post_id']) != 0) {
			$post				= $request['dba']->getRow("SELECT * FROM ". K4POSTS ." WHERE post_id = ". intval($_REQUEST['post_id']));
		} else {
			$action = new K4InformationAction(new K4LanguageElement('L_POSTDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}
		
		$attachment		= $request['dba']->getRow("SELECT * FROM ". K4ATTACHMENTS ." WHERE id = ". intval($_REQUEST['id']));

		if(!is_array($attachment) || empty($attachment)) {
			$action = new K4InformationAction(new K4LanguageElement('L_BADATTACHMENT'), 'content', FALSE);
			return $action->execute($request);
		}
				
		if(!is_array($post) || empty($post)) {
			$action = new K4InformationAction(new K4LanguageElement('L_POSTDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}	

		if($post['row_type'] & TOPIC && ($post['queue'] == 1 || $post['display'] == 0 || $post['is_draft'] == 1) ) {
			$action = new K4InformationAction(new K4LanguageElement('L_TOPICPENDINGMOD'), 'content', FALSE);
			return $action->execute($request);
		}
		
		/* Get the current forum */
		$forum				= $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($post['forum_id']));

		if(!$forum || !is_array($forum) || empty($forum)) {
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}
		
		/* Do we have permission to view attachments in this forum? */
		if($request['user']->get('perms') < get_map( 'attachments', 'can_view', array('forum_id'=>$forum['forum_id']))) {
			no_perms_error($request);
			return TRUE;
		}

		// update the number of times this attachment has been downloaded
		$request['dba']->executeUpdate("UPDATE ". K4ATTACHMENTS ." SET num_downloads=num_downloads+1 WHERE id = ". intval($attachment['id']));

		// send our headers
		header("Content-Type: ". $attachment['mime_type']);
		header("Content-Length: " . $attachment['file_size']);
		header('Content-Disposition: attachment; filename="'. $attachment['file_name'] .'"');
		
		$upload_dir		= BB_BASE_DIR .'/tmp/upload/attachments/';

		// change the upload director if we need to
		if($attachment['in_db'] == 0 && $request['user']->isMember()) {
			$upload_dir	= BB_BASE_DIR .'/tmp/upload/attachments/'. $request['user']->get('id') .'/';
		}
		
		if($attachment['in_db'] == 1) {
			$contents		= $attachment['file_contents'];
		} else {
			if(file_exists($upload_dir . $attachments['file_name'])) {
				$contents	= file_get_contents($upload_dir . $attachments['file_name']);
			} else {
				k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
				$action = new K4InformationAction(new K4LanguageElement('L_BADATTACHMENT'), 'content', FALSE);

				return $action->execute($request);
			}
		}

		echo $contents;
		unset($contents);
		exit;
	}
}

/**
 * Remove an attachment
 */
class K4RemoveAttachment extends FAAction {
	function execute(&$request) {

		global $_QUERYPARAMS;
		
		k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');

		if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
			$action = new K4InformationAction(new K4LanguageElement('L_BADATTACHMENT'), 'content', FALSE);
			return $action->execute($request);
		}
		
		$attachment		= $request['dba']->getRow("SELECT * FROM ". K4ATTACHMENTS ." WHERE id = ". intval($_REQUEST['id']));
		
		if(!is_array($attachment) || empty($attachment)) {
			$action = new K4InformationAction(new K4LanguageElement('L_BADATTACHMENT'), 'content', FALSE);
			return $action->execute($request);
		}

		if(isset($_REQUEST['post_id']) && intval($_REQUEST['post_id']) != 0) {
			$post				= $request['dba']->getRow("SELECT * FROM ". K4POSTS ." WHERE post_id = ". intval($_REQUEST['post_id']));
		} else {
			//$action = new K4InformationAction(new K4LanguageElement('L_POSTDOESNTEXIST'), 'content', FALSE);
			//return $action->execute($request);
			$post = array('post_id'=>0,'forum_id'=>$attachment['forum_id'],'row_type'=>0);
		}

		if(!is_array($post) || empty($post)) {
			$action = new K4InformationAction(new K4LanguageElement('L_POSTDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}

		/* Get the current forum */
		$forum				= $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($post['forum_id']));

		if(!$forum || !is_array($forum) || empty($forum)) {
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}
		
		k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
		
		/* Do we have permission to delete attachments in this forum? */
		if($request['user']->get('perms') < get_map( 'attachments', 'can_del', array('forum_id'=>$forum['forum_id']))) {
			no_perms_error($request);
			return TRUE;
		}

		if(($request['user']->get('id') != 0 && ( $request['user']->get('id') == $attachment['user_id']) || is_moderator($request['user']->getInfoArray(), $forum) ) ) {
			k4_bread_crumbs($request['template'], $request['dba'], 'L_REMOVEATTACHMENT');
			$request['dba']->executeUpdate("DELETE FROM ". K4ATTACHMENTS ." WHERE id = ". intval($attachment['id']));	
			
			if($post['post_id'] > 0) {
				$request['dba']->executeUpdate("UPDATE ". K4POSTS ." SET total_attachments=total_attachments-1, attachments=attachments-1 WHERE post_id=". intval(($post['row_type'] & REPLY ? $post['parent_id'] : $post['post_id'])) );
				if($post['row_type'] & REPLY) {
					$request['dba']->executeUpdate("UPDATE ". K4POSTS ." SET attachments=attachments-1 WHERE post_id=". intval($post['post_id']) );
				}
			}
			
			$referer = basename(referer());
			
			if(strpos($referer, 'misc.php') === FALSE) {
				$action = new K4InformationAction(new K4LanguageElement('L_REMOVEDATTACHMENT', k4_htmlentities($attachment['file_name'], ENT_QUOTES)), 'content', TRUE, referer(), 3);
				return $action->execute($request);
			} else {
				header("Location: misc.php?act=attachments_manager&post_id=". $post['post_id'] ."&forum_id=". $post['forum_id'] ."");
				exit;
			}
		} else {
			no_perms_error($request);
			return TRUE;
		}
	}
}

/**
 * Loop through the attachments of a topic/message
 */
class K4AttachmentsIterator extends FAProxyIterator {
	
	var $post;
	var $images;

	function K4AttachmentsIterator(&$dba, &$user, $post_id) {
		$this->__construct($dba, $user, $post_id);
	}

	function __construct(&$dba, &$user, $post_id) {
		global $_SETTINGS;
		
		$imageset			= $user->isMember() ? $user->get('imageset') : $_SETTINGS['imageset'];
		
		$this->abs_path		= BB_BASE_DIR .'/Images/'. $imageset .'/Icons/Attach/';
		$this->img_dir		= 'Images/'. $imageset .'/Icons/Attach/';

		$this->images		= array('jpe', 'jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff');
		
		$result				= $dba->executeQuery("SELECT * FROM ". K4ATTACHMENTS ." WHERE post_id = ". intval($post_id));

		parent::__construct($result);
	}

	function current() {
		$temp					= parent::current();

		$temp['file_icon']		= !file_exists($this->abs_path . $temp['file_type'] .'.gif') ? $this->img_dir .'unknown.gif' : $this->img_dir . $temp['file_type'] .'.gif';

		$temp['file_name']		= k4_htmlentities($temp['file_name'], ENT_QUOTES);

		$temp['is_image']		= 0;
		if(in_array($temp['file_type'], $this->images)) {
			$temp['is_image']	= 1;
		}

		return $temp;
	}
}

?>