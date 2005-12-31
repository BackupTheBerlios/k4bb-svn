<?php
/**
* k4 Bulletin Board, lazyload.php
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
* @version $Id: lazyload.php 154 2005-07-15 02:56:28Z Peter Goodman $
* @package k42
*/



if(!defined('IN_K4')) {
	return;
}

/**
 * This sets the mail queue items for emails that need to be sent out
 * because people have posted a topic in a forum
 */
function set_send_topic_mail($forum_id, $poster_name) {

	global $_DBA, $_QUERYPARAMS, $_SETTINGS, $lang;

	if(ctype_digit($forum_id) && intval($forum_id) != 0) {
		
		$forum				= $_DBA->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($forum_id));
		
		if(is_array($forum) && !empty($forum)) {

			/**
			 * Get the subscribers of this forum
			 */
			$users			= $_DBA->executeQuery("SELECT * FROM ". K4SUBSCRIPTIONS ." WHERE post_id = 0 AND forum_id = ". intval($forum['forum_id']));
			
			$subscribers	= array();

			while($users->next()) {
				
				$u				= $users->current();
				$subscribers[]	= array('name' => $u['user_name'], 'id' => $u['id'], 'email' => $u['email'], 'poster_name' => $poster_name);

			}
			
			/* Memory Saving */
			$users->free();
			unset($users);

			/**
			 * Insert the data into the mail queue
			 */
			$subject			= $lang['L_TOPICPOSTIN'] .": ". $forum['name'];
			$message			= sprintf($lang['L_FORUMSUBSCRIBEEMAIL'], "%s", "%s", $forum['name'], $forum['forum_id'], $_SETTINGS['bbtitle'], $forum['forum_id']);
			$userinfo			= serialize($subscribers);
			
			$insert				= $_DBA->prepareStatement("INSERT INTO ". K4MAILQUEUE ." (subject,message,row_id,row_type,userinfo) VALUES (?,?,?,?,?)");
			$insert->setString(1, $subject);
			$insert->setString(2, $message);
			$insert->setInt(3, $forum['forum_id']);
			$insert->setInt(4, FORUM);
			$insert->setString(5, $userinfo);

			$insert->executeUpdate();
			
			/* Memory saving */
			unset($_SETTINGS, $lang, $_QUERYPARAMS, $_DBA);

			reset_cache('email_queue');
		}
	}
}

/**
 * This sets the mail queue items for emails that need to be sent out
 * because people have replied to a topic
 */
function set_send_reply_mail($post_id, $poster_name) {

	global $_DBA, $_QUERYPARAMS, $_SETTINGS, $_LANG;

	if(ctype_digit($post_id) && intval($post_id) != 0) {
		
		$topic				= $_DBA->getRow("SELECT * FROM ". K4POSTS ." WHERE post_id = ". intval($post_id));
		
		if(is_array($topic) && !empty($topic)) {
			

			/**
			 * Get the subscribers of this topic
			 */
			$users			= $_DBA->executeQuery("SELECT * FROM ". K4SUBSCRIPTIONS ." WHERE post_id = ". intval($topic['post_id']) ." AND requires_revisit = 0");
			
			$subscribers	= array();

			while($users->next()) {
				
				$u				= $users->current();
				$subscribers[]	= array('name' => $u['user_name'], 'id' => $u['user_id'], 'email' => $u['email'], 'poster_name' => $poster_name, 'post_id' => $topic['post_id']);
			}
			
			/* Memory Saving */
			$users->free();
			unset($users);

			/**
			 * Insert the data into the mail queue
			 */
			$subject			= $_LANG['L_REPLYTO'] .": ". $topic['name'];
			$message			= sprintf($_LANG['L_TOPICSUBSCRIBEEMAIL'], "%s", "%s", $topic['name'], $topic['post_id'], $_SETTINGS['bbtitle'], $topic['post_id']);
			$userinfo			= serialize($subscribers);
			
			$insert				= $_DBA->prepareStatement("INSERT INTO ". K4MAILQUEUE ." (subject,message,row_id,row_type,userinfo) VALUES (?,?,?,?,?)");
			$insert->setString(1, $subject);
			$insert->setString(2, $message);
			$insert->setInt(3, $topic['post_id']);
			$insert->setInt(4, TOPIC);
			$insert->setString(5, $userinfo);

			$insert->executeUpdate();
			
			/* Memory saving */
			unset($_SETTINGS, $_LANG, $_QUERYPARAMS, $_DBA);

			reset_cache('email_queue');
		}
	}
}

/**
 * Execute our mail queue by sending out an appropriate amount of emails at once
 */
function execute_mail_queue(&$dba, $mailqueue) {
	global $_SETTINGS, $_URL;
	
	if(is_array($mailqueue) && !empty($mailqueue)) {
		array_values($mailqueue);

		if(isset($mailqueue[0])) {
			
			$queue			= $mailqueue[0];
			
			$users			= force_unserialize($mailqueue[0]['userinfo']);

			if(is_array($users) && !empty($users)) {
				
				/* Reset the starting point of this array */
				$users		= array_values($users);
				$count		= count($users);
				$user_query	= '';

				/* Loop through the users */
				for($i = 0; $i < EMAIL_INTERVAL; $i++) {
					
					if(isset($users[$i]) && is_array($users[$i]) && intval($users[$i]['id']) != 0) {
						
						$temp_i			= $i;

						if($users[$i]['name'] != $users[$i]['poster_name']) {
							
							$message	= sprintf($mailqueue[0]['message'], $users[$i]['name'], $users[$i]['poster_name']);
							
							$page				= &new FAUrl(forum_url());
							$page->args			= array();
							$page->file			= FALSE;
							$page->path			= FALSE;
							$page->anchor		= FALSE;
							$page->scheme		= FALSE;

							/* Email our user */
							mail($users[$i]['email'], $mailqueue[0]['subject'], $message, "From: \"". $_SETTINGS['bbtitle'] ." Forums\" <noreply@". $page->__toString() .">");
							
							$user_query	.= ($i == 0) ? 'user_id = '. intval($users[$i]['id']) : ' OR user_id = '. intval($users[$i]['id']);	
							
							unset($users[$i]);
						}
					}

				}
				
				/* Update the subscriptions 'requires revisit' field */
				$dba->executeUpdate("UPDATE ". K4SUBSCRIPTIONS ." SET requires_revisit = 1 WHERE post_id = ". $queue['row_id'] ." ". ($user_query != '' ? "AND (". $user_query .")" : ''));
				
				/* If we have finished with this queue item */
				if($count <= EMAIL_INTERVAL) {
					$dba->executeUpdate("DELETE FROM ". K4MAILQUEUE ." WHERE id = ". intval($mailqueue[0]['id']));
				} else {
					
					$users		= array_values($users);
					$update		= $dba->prepareStatement("UPDATE ". K4MAILQUEUE ." SET userinfo=? WHERE id=?");
					$update->setString(1, serialize($users));
					$update->setInt(2, $mailqueue[0]['id']);
					$update->executeUpdate();
				}

			} else {
				$dba->executeUpdate("DELETE FROM ". K4MAILQUEUE ." WHERE id = ". intval($mailqueue[0]['id']));
			}
			reset_cache('email_queue');
		}
	}
}

?>