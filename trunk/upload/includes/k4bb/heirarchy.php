<?php
/**
* k4 Bulletin Board, heirarchy.php
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
* @version $Id: heirarchy.php 154 2005-07-15 02:56:28Z Peter Goodman $
* @package k42
*/



if(!defined('IN_K4')) {
	return;
}

function remove_item($id, $id_type) {
	
	global $_DBA, $_QUERYPARAMS, $_DATASTORE;
	
	$heirarchy  = &new Heirarchy();
	
	/* Start the transaction */
	$_DBA->beginTransaction();
	
	switch($id_type) {
		case 'post_id': {
			
			/* Get the row */
			$info		= $_DBA->getRow("SELECT * FROM ". K4POSTS ." WHERE post_id=". intval($id));

			/* The number of replies that this topic has */
			$num_replies		= $info['num_replies'];
			//$num_replies		= @intval(($info['row_right'] - $info['row_left'] - 1) / 2);
		
			/* Get that last topic in this forum that's not this topic */
			$last_topic			= $_DBA->getRow("SELECT * FROM ". K4POSTS ." WHERE post_id <> ". intval($info['post_id']) ." AND is_draft=0 AND queue=0 AND display=1 AND forum_id=". intval($info['forum_id']) ." ORDER BY created DESC LIMIT 1");
			$last_topic			= !$last_topic || !is_array($last_topic) ? array('created'=>0,'name'=>'','poster_name'=>'','id'=>0,'poster_id'=>0,'posticon'=>'') : $last_topic;
			$last_topic['id']	= $last_topic['post_id'];

			/* Get that last post in this forum that's not part of/from this topic */
			$lastpost_created			= $_DBA->getRow("SELECT * FROM ". K4POSTS ." WHERE post_id <> ". intval($info['post_id']) ." AND forum_id=". intval($info['forum_id']) ." ORDER BY created DESC LIMIT 1");
			$lastpost_created['id']	= $lastpost_created['post_id'];
			$lastpost_created			= !$lastpost_created || !is_array($lastpost_created) ? $last_topic : $lastpost_created;
			
			/**
			 * Update the forum and the datastore
			 */
						
			$forum_update		= $_DBA->prepareStatement("UPDATE ". K4FORUMS ." SET topics=topics-1,posts=posts-?,replies=replies-?,topic_created=?,topic_name=?,topic_uname=?,post_id=?,topic_uid=?,topic_posticon=?,post_created=?,post_name=?,post_uname=?,post_id=?,post_uid=?,post_posticon=? WHERE forum_id=?");
			$datastore_update	= $_DBA->prepareStatement("UPDATE ". K4DATASTORE ." SET data=? WHERE varname=?");
				
			/* Set the forum values */
			$forum_update->setInt(1, intval($num_replies)+1);
			$forum_update->setInt(2, intval($num_replies));
			$forum_update->setInt(3, $last_topic['created']);
			$forum_update->setString(4, $last_topic['name']);
			$forum_update->setString(5, $last_topic['poster_name']);
			$forum_update->setInt(6, $last_topic['post_id']);
			$forum_update->setInt(7, $last_topic['poster_id']);
			$forum_update->setString(8, $last_topic['posticon']);
			$forum_update->setInt(9, $lastpost_created['created']);
			$forum_update->setString(10, $lastpost_created['name']);
			$forum_update->setString(11, $lastpost_created['poster_name']);
			$forum_update->setInt(12, $lastpost_created['id']);
			$forum_update->setInt(13, $lastpost_created['poster_id']);
			$forum_update->setString(14, $lastpost_created['posticon']);
			$forum_update->setInt(15, $info['forum_id']);
			
			/* Set the datastore values */
			$datastore					= $_DATASTORE['forumstats'];
			$datastore['num_topics']	= $_DBA->getValue("SELECT COUNT(*) FROM ". K4POSTS ." WHERE is_draft = 0 AND queue = 0 AND display = 1") - 1;
			$datastore['num_replies']	= $_DBA->getValue("SELECT COUNT(*) FROM ". K4POSTS ) - intval($num_replies); // ." WHERE is_draft = 0"
			
			$datastore_update->setString(1, serialize($datastore));
			$datastore_update->setString(2, 'forumstats');
			
			/* Execute the forum and datastore update queries */
			$forum_update->executeUpdate();
			$datastore_update->executeUpdate();

			/**
			 * Change user post counts
			 */
			
			/* Update the user that posted this topic */
			if($info['poster_id'] > 0)
				$_DBA->executeUpdate("UPDATE ". K4USERINFO ." SET num_posts=num_posts-1 WHERE user_id=". intval($info['poster_id']));

			$users						= array();
			
			/* Only if there are more than 0 replies should we update post counts */
			if(intval($num_replies) > 0) {
				
				/* Get all of the replies */
				$replies				= $_DBA->executeQuery("SELECT poster_id FROM ". K4POSTS ." WHERE post_id = ". intval($info['post_id']));
				
				while($replies->next()) {
					$reply				= $replies->current();
					
					if(!isset($users[$reply['poster_id']]) && $reply['poster_id'] > 0)
						$users[$reply['poster_id']] = 1;
					
					$users[$reply['poster_id']] += 1;
				}
				
				/* Memory saving */
				$replies->free();
				unset($replies);

				/* Update all of the users that posted */
				if(count($users) > 0) {
					
					/* Loop through the users and change their post counts */
					foreach($users as $user_id => $num_posts) {
						$_DBA->executeUpdate("UPDATE ". K4USERINFO ." SET num_posts=num_posts-". intval($num_posts) ." WHERE user_id=". intval($user_id));
					}
				}

				/* Memory saving */
				unset($users);
			}
			
			$_DBA->executeUpdate("DELETE FROM ". K4POSTS ." WHERE post_id = ". intval($id));
			$_DBA->executeUpdate("DELETE FROM ". K4POSTS ." WHERE post_id = ". intval($id));
			$_DBA->executeUpdate("DELETE FROM ". K4SUBSCRIPTIONS ." WHERE post_id = ". intval($id));
			$_DBA->executeUpdate("DELETE FROM ". K4MAILQUEUE ." WHERE row_id = ". intval($id) ." AND row_type = ". TOPIC);
			$_DBA->executeUpdate("DELETE FROM ". K4BADPOSTREPORTS ." WHERE post_id = ". intval($id));

			break;
		}
	}
		
	/* Commit the transaction */
	$_DBA->commitTransaction();
	
	return TRUE;
}

class Heirarchy {
	
	var $dba;

	function Heirarchy() {
		global $_DBA;

		$this->dba	= &$_DBA;
	}
	function allocateSpace($row_left, $row_right, $destination, $table) {
		$space_needed	= $row_right - $row_left;
		
		$right			= $destination['row_right'] + $space_needed + 1;

		$this->dba->executeUpdate("UPDATE ". $table ." SET row_right = ". $right ." WHERE id = ". intval($destination['id']));
	}
	/**
	 * This will remove a heirarchy recursion item and all of its children
	 */
	function removeItem($info, $table, $connector = FALSE, $mptt = FALSE) {
		$this->dba->executeUpdate("DELETE FROM ". $table ." WHERE id = ". intval($info['id']));
		
		if($connector)
			$this->dba->executeUpdate("DELETE FROM ". $table ." WHERE $connector = ". intval($info['id']));
	
		if($mptt)
			$this->dba->executeUpdate("DELETE FROM ". $table ." WHERE row_left > ". intval($info['row_left']) ." AND row_right < ". intval($info['row_right']));
	}
	/**
	 * This will remove an MPTT node and all of its children
	 */
	function removeNode($info, $table) {
		
		/* If we are using nested sets */

		$descendants = (($info['row_right'] - $info['row_left'] - 1) / 2) + 2;
		$descendants = $descendants % 2 == 0 ? $descendants : $descendants+1; // Make it an even number
		
		/**
		 * Create the Queries
		 */
		$delete		= $this->dba->prepareStatement("DELETE FROM ". $table ." WHERE row_left >= ? AND row_right <= ?");
		$update_a	= $this->dba->prepareStatement("UPDATE ". $table ." SET row_right = row_right-? WHERE row_left < ? AND row_right > ?");
		$update_b	= $this->dba->prepareStatement("UPDATE ". $table ." SET row_left = row_left-?, row_right=row_right-? WHERE row_left > ?");
		
		/**
		 * Populate the queries
		 */
		$delete->setInt(1, $info['row_left']);
		$delete->setInt(2, $info['row_right']);

		$update_a->setInt(1, $descendants);
		$update_a->setInt(2, $info['row_left']);
		$update_a->setInt(3, $info['row_left']);

		$update_b->setInt(1, $descendants);
		$update_b->setInt(2, $descendants);
		$update_b->setInt(3, $info['row_left']);
		
		/**
		 * Execute the queries
		 */
		$delete->executeUpdate();
		$update_a->executeUpdate();
		$update_b->executeUpdate();
	}
}

?>