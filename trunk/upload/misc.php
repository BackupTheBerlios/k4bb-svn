<?php
/**
* k4 Bulletin Board, misc.php
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
* @version $Id: k4_template.php 134 2005-06-25 15:41:13Z Peter Goodman $
* @package k4-2.0-dev
*/

require "includes/filearts/filearts.php";
require "includes/k4bb/k4bb.php";

class K4DefaultAction extends FAAction {
	function execute(&$request) {

		no_perms_error($request);
		return TRUE;
	}
}

class SwitchEditors extends FAAction {
	function execute(&$request) {
		
		global $_SETTINGS, $_URL;

		if(USE_XMLHTTP){
			if(isset($_REQUEST['forum_id']) && intval($_REQUEST['forum_id']) != 0) {
				$forum = $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($_REQUEST['forum_id']));
			
				if(!is_array($forum) || empty($forum)) {
					exit;
				}
			} else {
				$forum = array('forum_id' => 0, 'defaultstyle' => $request['user']->get('templateset'));
			}
			
			foreach($forum as $key => $val)
				$request['template']->setVar('forum_'. $key, $val);

			$request['template']->setVar('allowbbcode', $_SETTINGS['allowbbcode']);

			$message		= isset($_POST['message']) ? stripslashes($_POST['message']) : '';
			$switchto		= ($_POST['switchto'] == 'wysiwyg' && USE_WYSIWYG) ? 'wysiwyg' : 'bbcode';
			
			//$bbcode			= &new BBCodex($request['dba'], $request['user']->getInfoArray(), $message, $forum['forum_id'], TRUE, TRUE, TRUE, TRUE);
			$parser = &new BBParser;
			
			$allowhtml		= $forum['forum_id'] > 0 ? TRUE : FALSE;
			$allowbbcode	= TRUE;
			$allowemoticons = TRUE;

			if($switchto == 'wysiwyg') {

				/**
				 * Try to pass the right things to the bbcode parser
				 */
				$referer	= new FAUrl(referer());

				if(isset($referer->args['act'])) {
					switch($referer->args['act']) {
						case 'usercp': {
							if(isset($referer->args['view'])) {
								if($referer->args['view'] == 'signature') {
									$allowbbcode	= (bool)intval($_SETTINGS['allowbbcodesignatures']);
									$allowemoticons = (bool)intval($_SETTINGS['allowemoticonssignature']);
								} else if($referer->args['view'] == 'pmnewmessage') {
									$allowbbcode	= (bool)intval($_SETTINGS['privallowbbcode']);
									$allowemoticons = (bool)intval($_SETTINGS['privallowemoticons']);
								}
							}
							break;
						}
						default: {
							
							if($referer->file == 'newtopic.php' || $referer->file == 'newreply.php') {
								$allowbbcode	= (bool)($user['perms'] >= get_map($user, 'bbcode', 'can_add', array('forum_id'=>$forum_id)));
								$allowemoticons = (bool)($user['perms'] >= get_map($user, 'emoticons', 'can_add', array('forum_id'=>$forum['forum_id'])));
							}

							break;
						}
					}
				}
				
				if($allowemoticons) {
					$request['template']->setList('emoticons', $request['dba']->executeQuery("SELECT * FROM ". K4EMOTICONS ." WHERE clickable = 1"));
				}
			}
			
			$message = $parser->revert($message);
			$request['template']->setVar('editor_text_reverted', $message);
			//$bbcode = new BBCodex($request['dba'], $request['user']->getInfoArray(), $message, $forum['forum_id'], $allowhtml, $allowbbcode, $allowemoticons, $automaticurls, array('quote', 'php', 'code'));
			$request['template']->setVar('editor_text', $message); // $parser->parse($message)
			
			
			$templateset = $request['user']->isMember() ? $request['user']->get('templateset') : ($forum['forum_id'] > 0 ? $forum['defaultstyle'] : $_SETTINGS['templateset']);
			
			$request['template']->setVar('can_wysiwyg', USE_WYSIWYG ? 1 : 0);
			$request['template']->setVar('has_bbcode_perms', intval($allowbbcode));
			
			$html = $request['template']->run(BB_BASE_DIR .'/templates/'. $templateset .'/editor_'. $switchto .'.html');
			
			xmlhttp_header();
			echo $html;
			xmlhttp_footer();
		}

		return TRUE;
	}
}

class RevertHTMLText extends FAAction {
	function execute(&$request) {
		
		if(USE_XMLHTTP){
			if(!isset($_REQUEST['post_id']) || intval($_REQUEST['post_id']) == 0) {
				return xmlhttp_message('L_YOUNEEDPERMS');
			}
				
			// get the post
			$post = $request['dba']->getRow("SELECT * FROM ". K4POSTS ." WHERE post_id = ". intval($_REQUEST['post_id']));
			
			if(!is_array($post) || empty($post)) {
				return xmlhttp_message('L_POSTDOESNTEXIST');
			}
			
			if($post['row_type'] & TOPIC) {

				if($request['user']->get('id') == $post['poster_id'] && $request['user']->get('perms') < get_map($user, 'topics', 'can_edit', array('forum_id'=>$post['forum_id'])) ) {
					return xmlhttp_message('L_YOUNEEDPERMS');
				}

				if($request['user']->get('id') != $post['poster_id'] && $request['user']->get('perms') < get_map($user, 'other_topics', 'can_edit', array('forum_id'=>$post['forum_id'])) ) {
					return xmlhttp_message('L_YOUNEEDPERMS');
				}
			} else if($post['row_type'] & REPLY) {
				
				if($request['user']->get('id') == $post['poster_id'] && $request['user']->get('perms') < get_map($user, 'replies', 'can_edit', array('forum_id'=>$post['forum_id'])) ) {
					return xmlhttp_message('L_YOUNEEDPERMS');
				}

				if($request['user']->get('id') != $post['poster_id'] && $request['user']->get('perms') < get_map($user, 'other_replies', 'can_edit', array('forum_id'=>$post['forum_id'])) ) {
					return xmlhttp_message('L_YOUNEEDPERMS');
				}

			} else {
				return xmlhttp_message('L_POSTDOESNTEXIST');
			}
			
			//$bbcode = &new BBCodex($request['dba'], $request['user']->getInfoArray(), $post['body_text'], $post['forum_id'], TRUE, TRUE, TRUE, TRUE);
			$parser = &new BBParser;
			xmlhttp_header();
			echo $parser->revert($post['body_text']);
			xmlhttp_footer();
		}

		return TRUE;
	}
}

class PostBodyText extends FAAction {
	function execute(&$request) {
		
		if(USE_XMLHTTP){
			
			if(!isset($_REQUEST['post_id']) || intval($_REQUEST['post_id']) == 0) {
				return xmlhttp_message('L_YOUNEEDPERMS');
			}
				
			// get the post
			$post = $request['dba']->getRow("SELECT * FROM ". K4POSTS ." WHERE post_id = ". intval($_REQUEST['post_id']));
			
			if(!is_array($post) || empty($post)) {
				return xmlhttp_message('L_POSTDOESNTEXIST');
			}
			
			if($post['row_type'] & TOPIC) {
				
				if($request['user']->get('id') == $post['poster_id'] && $request['user']->get('perms') < get_map($user, 'topics', 'can_view', array('forum_id'=>$post['forum_id'])) ) {
					return xmlhttp_message('L_YOUNEEDPERMS');
				}

				if($request['user']->get('id') != $post['poster_id'] && $request['user']->get('perms') < get_map($user, 'other_topics', 'can_view', array('forum_id'=>$post['forum_id'])) ) {
					return xmlhttp_message('L_YOUNEEDPERMS');
				}

			} else if($post['row_type'] & REPLY) {
				
				if($request['user']->get('id') == $post['poster_id'] && $request['user']->get('perms') < get_map($user, 'replies', 'can_view', array('forum_id'=>$post['forum_id'])) ) {
					return xmlhttp_message('L_YOUNEEDPERMS');
				}

				if($request['user']->get('id') != $post['poster_id'] && $request['user']->get('perms') < get_map($user, 'other_replies', 'can_view', array('forum_id'=>$post['forum_id'])) ) {
					return xmlhttp_message('L_YOUNEEDPERMS');
				}

			} else {
				return xmlhttp_message('L_YOUNEEDPERMS');
			}
			
			// echo out the original post body text
			xmlhttp_header();
			echo $post['body_text'];
			xmlhttp_footer();
		}

		return TRUE;
	}
}

class changePostBodyText extends FAAction {
	function execute(&$request) {
		
		if(USE_XMLHTTP){

			if(!isset($_REQUEST['post_id']) || intval($_REQUEST['post_id']) == 0) {
					return xmlhttp_message('L_YOUNEEDPERMS');
			}
				
			// get the post
			$post = $request['dba']->getRow("SELECT * FROM ". K4POSTS ." WHERE post_id = ". intval($_REQUEST['post_id']));
			
			if(!is_array($post) || empty($post)) {
				return xmlhttp_message('L_POSTDOESNTEXIST');
			}
			
			if($post['row_type'] & TOPIC) {
					
				if($request['user']->get('id') == $post['poster_id'] && $request['user']->get('perms') < get_map($user, 'topics', 'can_edit', array('forum_id'=>$post['forum_id'])) ) {
					return xmlhttp_message('L_YOUNEEDPERMS');
				}

				if($request['user']->get('id') != $post['poster_id'] && $request['user']->get('perms') < get_map($user, 'other_topics', 'can_edit', array('forum_id'=>$post['forum_id'])) ) {
					return xmlhttp_message('L_YOUNEEDPERMS');
				}
			} else if($post['row_type'] & REPLY) {
				
				if($request['user']->get('id') == $post['poster_id'] && $request['user']->get('perms') < get_map($user, 'replies', 'can_edit', array('forum_id'=>$post['forum_id'])) ) {
					return xmlhttp_message('L_YOUNEEDPERMS');
				}

				if($request['user']->get('id') != $post['poster_id'] && $request['user']->get('perms') < get_map($user, 'other_replies', 'can_edit', array('forum_id'=>$post['forum_id'])) ) {
					return xmlhttp_message('L_YOUNEEDPERMS');
				}
			} else {
				return xmlhttp_message('L_YOUNEEDPERMS');
			}
			
			if(!isset($_REQUEST['message']) || $_REQUEST['message'] == '') {
				return xmlhttp_message('L_INSERTPOSTMESSAGE');
			}
			
			//$bbcode	= &new BBCodex($request['dba'], $request['user']->getInfoArray(), $_REQUEST['message'], $post['forum_id'], 
			//(bool)$post['disable_html'], 
			//(bool)$post['disable_bbcode'], 
			//(bool)$post['disable_emoticons'],
			
			$parser = &new BBParser;
			$body_text = $parser->parse($_REQUEST['message']);
			$poller		= &new K4BBPolls($body_text, '', array('forum_id'=>$post['forum_id']), $post['post_id']);

			$is_poll	= 0;
			// put it here to avoid previewing
			$poll_text		= $poller->parse($request, $is_poll);
							
			if($body_text != $poll_text) {
				$body_text	= $poll_text;
				$is_poll	= 1;
			}
			
			/* If this topic is a redirect/ connects to one, update the original */
			if($post['row_type'] & TOPIC && ($post['moved_new_post_id'] > 0 || $post['moved_old_post_id'] > 0)) {
				$update		= $request['dba']->prepareStatement("UPDATE ". K4POSTS ." SET body_text=?,edited_time=?,edited_username=?,edited_userid=?,is_poll=? WHERE post_id=?");
				$update->setString(1, $body_text);
				$update->setInt(2, time());
				$update->setString(3, $request['user']->get('name'));
				$update->setInt(4, $request['user']->get('id'));
				$update->setInt(5,$is_poll);
				$update->setInt(6, ($post['moved_new_post_id'] > 0 ? $post['moved_new_post_id'] : $post['moved_old_post_id']));
				$update->executeUpdate();
			}
			
			/* Update the original */
			$update		= $request['dba']->prepareStatement("UPDATE ". K4POSTS ." SET body_text=?,edited_time=?,edited_username=?,edited_userid=?,is_poll=? WHERE post_id=?");
			$update->setString(1, $body_text);
			$update->setInt(2, time());
			$update->setString(3, $request['user']->get('name'));
			$update->setInt(4, $request['user']->get('id'));
			$update->setInt(5,$is_poll);
			$update->setInt(6, $post['post_id']);
			$update->executeUpdate();
			
			xmlhttp_header();
			echo $body_text;
			xmlhttp_footer();
		}
		return TRUE;
	}
}

class postAttachForm extends FAAction {
	function execute(&$request) {
		
		if(isset($_REQUEST['forum_id']) && intval($_REQUEST['forum_id']) != 0) {
			$forum = $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($_REQUEST['forum_id']));
			
			if(!is_array($forum) || empty($forum)) {
				exit;
			}
		} else {
			exit;
		}
		
		$request['template']->setVar('forum_id', $forum['forum_id']);

		if($request['user']->get('perms') < get_map($request['user'], 'attachments', 'can_add', array('forum_id'=>$forum['forum_id']) ) ) {
			exit;
		}

		$num_attachments = 0;
		
		// check for a post id and add attachments accordingly
		if(isset($_REQUEST['post_id']) && intval($_REQUEST['post_id']) > 0) {
			$post = $request['dba']->getRow("SELECT * FROM ". K4POSTS ." WHERE post_id=". intval($_REQUEST['post_id']) ." AND poster_id=". intval($request['user']->get('id')));
			if(!$post || !is_array($post) || empty($post)) {
				exit;
			}

			$request['template']->setVar('post_id', $post['post_id']);

			$num_attachments	= $post['attachments'];
			post_attachment_options($request, $forum, $post);
		}
		
		// if there are no attachments set by the above post check
		if($request['template']->getVar('attach_inputs') == '') {
			
			// this will deal with any attachments in 'limbo'
			$limbo_attachments = $request['dba']->getValue("SELECT COUNT(*) FROM ". K4ATTACHMENTS ." WHERE post_id = ". intval($post['post_id']) ." AND user_id=". intval($request['user']->get('id')));
			post_attachment_options($request, $forum, array('post_id'=>0,'attachments'=>$limbo_attachments) );
			
			if($request['template']->getVar('attach_inputs') == '') {
				if($request['user']->get('perms') >= get_map( 'attachments', 'can_add', array('forum_id'=>$forum['forum_id']))) {
					$num_attachments	= $request['template']->getVar('nummaxattaches') - $num_attachments;
					
					$attach_inputs		= '';
					for($i = 1; $i <= $num_attachments; $i++) {
						$attach_inputs	.= '<br /><input type="file" class="inputbox" name="attach'. $i .'" id="attach'. $i .'" value="" size="55" />';
					}
					
					$request['template']->setVar('attach_inputs', $attach_inputs);
				}
			}
		}
		
		if(isset($_REQUEST['error']) && $_REQUEST['error'] != '') {			
			$errorstr = '<strong>'. $request['template']->getVar('L_ERRORS') .'</strong><br />';
			$temp = explode('|', $_REQUEST['error']);
			$errorstr .= implode('<br />', $temp);
			
			$request['template']->setVar('errors', $errorstr);

			unset($temp);
		}
		
		// set some stuff
		$templateset = $request['user']->isMember() ? $request['user']->get('templateset') : $forum['defaultstyle'];
		$request['template_file'] = BB_BASE_DIR .'/templates/'. $templateset .'/misc_base.html';
		$request['template']->setFile('content', 'post_attach_form.html');
		$request['template']->setVisibility('copyright', FALSE);

		return TRUE;
	}
}

class AttachFilesToPost extends FAAction {
	function execute(&$request) {
		
		if(isset($_REQUEST['forum_id']) && intval($_REQUEST['forum_id']) != 0) {
			$forum = $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($_REQUEST['forum_id']));
			
			if(!is_array($forum) || empty($forum))
				exit;
		} else {
			exit;
		}
		
		if($request['user']->get('perms') < get_map($request['user'], 'attachments', 'can_add', array('forum_id'=>$forum['forum_id']) ) ) {
			exit;
		}

		$num_attachments = 0;
		$post_id = 0;
		$row_type = 0;
		$parent_id = 0;

		// check for a post id and add attachments accordingly
		if(isset($_REQUEST['post_id']) && intval($_REQUEST['post_id']) > 0) {
			$post = $request['dba']->getRow("SELECT * FROM ". K4POSTS ." WHERE post_id=". intval($_REQUEST['post_id']) ." AND poster_id=". intval($request['user']->get('id')));
			if(!$post || !is_array($post) || empty($post)) {
				exit;
			}

			$post_id = $post['post_id'];
			$row_type = $post['row_type'];
			$parent_id = $post['parent_id'];
		} else {
			$post = array('post_id'=>$post_id,'parent_id'=>$parent_id,'row_type'=>$row_type);
		}
				
		$result = attach_files($request, $forum, $post);
		
		$error_str = '';
		if(is_array($result) && !empty($result)) {
			$error_str = implode('|', $result);
		}
		
		header("Location: misc.php?act=attachments_manager&post_id=". $post['post_id'] ."&forum_id=". $forum['forum_id'] ."&error=". $error_str);
		
		return TRUE;
	}
}

$app = &new K4Controller('forum_base.html');
$app->setAction('', new K4DefaultAction);
$app->setDefaultEvent('');

$app->setAction('switch_editor', new SwitchEditors);
$app->setAction('revert_text', new RevertHTMLText);
$app->setAction('original_text', new PostBodyText);
$app->setAction('save_text', new changePostBodyText);

$app->setAction('attachments_manager', new postAttachForm);
$app->setAction('attach_files', new AttachFilesToPost);

$app->execute();

exit;

?>