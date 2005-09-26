<?php
/**
* k4 Bulletin Board, misc.php
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
* @version $Id: k4_template.php 134 2005-06-25 15:41:13Z Peter Goodman $
* @package k4-2.0-dev
*/

require "includes/filearts/filearts.php";
require "includes/k4bb/k4bb.php";

class K4DefaultAction extends FAAction {
	function execute(&$request) {

		header("Location: index.php");

	}
}

class SwitchEditors extends FAAction {
	function execute(&$request) {
		
		global $_SETTINGS, $_URL;

		if(USE_AJAX){
			if(isset($_REQUEST['forum_id']) && intval($_REQUEST['forum_id']) != 0) {
				$forum = $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($_REQUEST['forum_id']));
			
				if(!is_array($forum) || empty($forum))
					exit;
			} else {
				$forum = array('forum_id' => 0, 'defaultstyle' => $request['user']->get('templateset'));
			}
			
			foreach($forum as $key => $val)
				$request['template']->setVar('forum_'. $key, $val);

			$request['template']->setVar('allowbbcode', $_SETTINGS['allowbbcode']);

			$message		= isset($_POST['message']) ? stripslashes($_POST['message']) : '';
			$switchto		= ($_POST['switchto'] == 'wysiwyg' && USE_WYSIWYG) ? 'wysiwyg' : 'bbcode';
			
			$bbcode			= &new BBCodex($request['dba'], $request['user']->getInfoArray(), $message, $forum['forum_id'], TRUE, TRUE, TRUE, TRUE);
			
			$allowhtml		= $forum['forum_id'] > 0 ? TRUE : FALSE;
			$allowbbcode	= TRUE;
			$allowemoticons = TRUE;
			$automaticurls	= TRUE;

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
									$automaticurls	= (bool)intval($_SETTINGS['autoparsesignatureurls']);
								} else if($referer->args['view'] == 'pmnewmessage') {
									$allowbbcode	= (bool)intval($_SETTINGS['privallowbbcode']);
									$allowemoticons = (bool)intval($_SETTINGS['privallowemoticons']);
									$automaticurls	= 1;
								}
							}
							break;
						}
						default: {
							
							if($referer->file == 'newtopic.php' || $referer->file == 'newreply.php') {
								$allowbbcode	= (bool)($user['perms'] >= get_map($user, 'bbcode', 'can_add', array('forum_id'=>$forum_id)));
								$allowemoticons = (bool)($user['perms'] >= get_map($user, 'emoticons', 'can_add', array('forum_id'=>$forum['forum_id'])));
								$automaticurls	= TRUE;
							}

							break;
						}
					}
				}
				
				if($allowemoticons)
					$request['template']->setList('emoticons', $request['dba']->executeQuery("SELECT * FROM ". K4EMOTICONS ." WHERE clickable = 1"));
			}
			
			$message = $bbcode->revert();
			$request['template']->setVar('editor_text_reverted', $message);
			$bbcode = new BBCodex($request['dba'], $request['user']->getInfoArray(), $message, $forum['forum_id'], $allowhtml, $allowbbcode, $allowemoticons, $automaticurls, array('quote', 'php', 'code'));
			$request['template']->setVar('editor_text', $bbcode->parse());
			
			
			$templateset = $request['user']->isMember() ? $request['user']->get('templateset') : ($forum['forum_id'] > 0 ? $forum['defaultstyle'] : $_SETTINGS['templateset']);
			
			$request['template']->setVar('can_wysiwyg', USE_WYSIWYG ? 1 : 0);
			$request['template']->setVar('has_bbcode_perms', intval($allowbbcode));
			
			$html = $request['template']->run(BB_BASE_DIR .'/templates/'. $templateset .'/editor_'. $switchto .'.html');
			
			echo $html;
			exit;
		}

		return TRUE;
	}
}

class RevertHTMLText extends FAAction {
	function execute(&$request) {
		
		if(USE_AJAX){
			if(isset($_REQUEST['topic']) && intval($_REQUEST['topic']) != 0) {
				
				// get the topic
				$post = $request['dba']->getRow("SELECT * FROM ". K4TOPICS ." WHERE topic_id = ". intval($_REQUEST['topic']));

				if(!is_array($post) || empty($post)) {
					return ajax_message('L_TOPICDOESNTEXIST');
				}
				
				/* Check if this user has these permissions */
				if($request['user']->get('id') == $post['poster_id'] && $request['user']->get('perms') < get_map($user, 'topics', 'can_edit', array('forum_id'=>$post['forum_id'])) ) {
					return ajax_message('L_YOUNEEDPERMS');
				}

				if($request['user']->get('id') != $post['poster_id'] && $request['user']->get('perms') < get_map($user, 'other_topics', 'can_edit', array('forum_id'=>$post['forum_id'])) ) {
					return ajax_message('L_YOUNEEDPERMS');
				}

			} else if(isset($_REQUEST['reply']) && intval($_REQUEST['reply']) != 0) {
				
				// get the reply
				$post = $request['dba']->getRow("SELECT * FROM ". K4REPLIES ." WHERE reply_id = ". intval($_REQUEST['reply']));
				
				if(!is_array($post) || empty($post)) {
					return ajax_message('L_REPLYDOESNTEXIST');
				}

				/* Check if this user has these permissions */
				if($request['user']->get('id') == $post['poster_id'] && $request['user']->get('perms') < get_map($user, 'topics', 'can_edit', array('forum_id'=>$post['forum_id'])) ) {
					return ajax_message('L_YOUNEEDPERMS');
				}

				if($request['user']->get('id') != $post['poster_id'] && $request['user']->get('perms') < get_map($user, 'other_topics', 'can_edit', array('forum_id'=>$post['forum_id'])) ) {
					return ajax_message('L_YOUNEEDPERMS');
				}

			} else {
				return ajax_message('L_YOUNEEDPERMS');
			}
			
			$bbcode			= &new BBCodex($request['dba'], $request['user']->getInfoArray(), $post['body_text'], $post['forum_id'], TRUE, TRUE, TRUE, TRUE);
						
			echo $bbcode->revert();
			exit;
		}

		return TRUE;
	}
}

class PostBodyText extends FAAction {
	function execute(&$request) {
		
		if(USE_AJAX){
			if(isset($_REQUEST['topic']) && intval($_REQUEST['topic']) != 0) {
				
				// get the topic
				$post = $request['dba']->getRow("SELECT * FROM ". K4TOPICS ." WHERE topic_id = ". intval($_REQUEST['topic']));

				if(!is_array($post) || empty($post)) {
					return ajax_message('L_TOPICDOESNTEXIST');
				}
				
				/* Check if this user has these permissions */
				if($request['user']->get('id') == $post['poster_id'] && $request['user']->get('perms') < get_map($user, 'topics', 'can_view', array('forum_id'=>$post['forum_id'])) ) {
					return ajax_message('L_YOUNEEDPERMS');
				}

				if($request['user']->get('id') != $post['poster_id'] && $request['user']->get('perms') < get_map($user, 'other_topics', 'can_view', array('forum_id'=>$post['forum_id'])) ) {
					return ajax_message('L_YOUNEEDPERMS');
				}

			} else if(isset($_REQUEST['reply']) && intval($_REQUEST['reply']) != 0) {
				
				// get the reply
				$post = $request['dba']->getRow("SELECT * FROM ". K4REPLIES ." WHERE reply_id = ". intval($_REQUEST['reply']));
				
				if(!is_array($post) || empty($post)) {
					return ajax_message('L_REPLYDOESNTEXIST');
				}

				/* Check if this user has these permissions */
				if($request['user']->get('id') == $post['poster_id'] && $request['user']->get('perms') < get_map($user, 'replies', 'can_view', array('forum_id'=>$post['forum_id'])) ) {
					return ajax_message('L_YOUNEEDPERMS');
				}

				if($request['user']->get('id') != $post['poster_id'] && $request['user']->get('perms') < get_map($user, 'other_replies', 'can_view', array('forum_id'=>$post['forum_id'])) ) {
					return ajax_message('L_YOUNEEDPERMS');
				}

			} else {
				return ajax_message('L_YOUNEEDPERMS');
			}
			
			// echo out the original post body text
			echo $post['body_text'];
			exit;
		}

		return TRUE;
	}
}

class changePostBodyText extends FAAction {
	function execute(&$request) {
		
		if(USE_AJAX){
			if(isset($_REQUEST['topic']) && intval($_REQUEST['topic']) != 0) {
				
				// get the topic
				$post = $request['dba']->getRow("SELECT * FROM ". K4TOPICS ." WHERE topic_id = ". intval($_REQUEST['topic']));

				if(!is_array($post) || empty($post)) {
					return ajax_message('L_TOPICDOESNTEXIST');
				}
				
				/* Check if this user has these permissions */
				if($request['user']->get('id') == $post['poster_id'] && $request['user']->get('perms') < get_map($user, 'topics', 'can_edit', array('forum_id'=>$post['forum_id'])) ) {
					return ajax_message('L_YOUNEEDPERMS');
				}

				if($request['user']->get('id') != $post['poster_id'] && $request['user']->get('perms') < get_map($user, 'other_topics', 'can_edit', array('forum_id'=>$post['forum_id'])) ) {
					return ajax_message('L_YOUNEEDPERMS');
				}

			} else if(isset($_REQUEST['reply']) && intval($_REQUEST['reply']) != 0) {
				
				// get the reply
				$post = $request['dba']->getRow("SELECT * FROM ". K4REPLIES ." WHERE reply_id = ". intval($_REQUEST['reply']));
				
				if(!is_array($post) || empty($post)) {
					return ajax_message('L_REPLYDOESNTEXIST');
				}

				/* Check if this user has these permissions */
				if($request['user']->get('id') == $post['poster_id'] && $request['user']->get('perms') < get_map($user, 'replies', 'can_edit', array('forum_id'=>$post['forum_id'])) ) {
					return ajax_message('L_YOUNEEDPERMS');
				}

				if($request['user']->get('id') != $post['poster_id'] && $request['user']->get('perms') < get_map($user, 'other_replies', 'can_edit', array('forum_id'=>$post['forum_id'])) ) {
					return ajax_message('L_YOUNEEDPERMS');
				}

			} else {
				return ajax_message('L_YOUNEEDPERMS');
			}
			
			if(!isset($_REQUEST['message']) || $_REQUEST['message'] == '') {
				return ajax_message('L_INSERTPOSTMESSAGE');
			}
			
			$bbcode	= &new BBCodex($request['dba'], $request['user']->getInfoArray(), $_REQUEST['message'], $post['forum_id'], 
			(bool)$post['disable_html'], 
			(bool)$post['disable_bbcode'], 
			(bool)$post['disable_emoticons'], 
			(bool)$post['disable_aurls']);
						
			$body_text	= $bbcode->parse();
			
			/* If this topic is a redirect/ connects to one, update the original */
			if($post['row_type'] & TOPIC && ($post['moved_new_topic_id'] > 0 || $post['moved_old_topic_id'] > 0)) {
				$update		= $request['dba']->prepareStatement("UPDATE ". ($post['row_type'] & TOPIC ? K4TOPICS : K4REPLIES) ." SET body_text=?,edited_time=?,edited_username=?,edited_userid=? WHERE ". ($post['row_type'] & TOPIC ? 'topic_id' : 'reply_id') ."=?");
				$update->setString(1, $body_text);
				$update->setInt(2, time());
				$update->setString(3, $request['user']->get('name'));
				$update->setInt(4, $request['user']->get('id'));
				$update->setInt(5, ($post['moved_new_topic_id'] > 0 ? $post['moved_new_topic_id'] : $post['moved_old_topic_id']));
				$update->executeUpdate();
			}
			
			/* Update the original */
			$update		= $request['dba']->prepareStatement("UPDATE ". ($post['row_type'] & TOPIC ? K4TOPICS : K4REPLIES) ." SET body_text=?,edited_time=?,edited_username=?,edited_userid=? WHERE ". ($post['row_type'] & TOPIC ? 'topic_id' : 'reply_id') ."=?");
			$update->setString(1, $body_text);
			$update->setInt(2, time());
			$update->setString(3, $request['user']->get('name'));
			$update->setInt(4, $request['user']->get('id'));
			$update->setInt(5, $post[($post['row_type'] & TOPIC ? 'topic_id' : 'reply_id')]);
			$update->executeUpdate();
			
			echo $body_text;
			exit;
		}

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

$app->execute();

exit;

?>