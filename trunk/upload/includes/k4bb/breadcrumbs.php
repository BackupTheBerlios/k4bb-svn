<?php
/**
* k4 Bulletin Board, breadcrumbs.class.php
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
* @author Geoffrey Goodman
* @version $Id: breadcrumbs.php 156 2005-07-15 17:51:48Z Peter Goodman $
* @package k42
*/



if(!defined('IN_K4')) {
	return;
}

function loop_recursive(&$breadcrumbs, &$dba, $temp) {

	global $_QUERYPARAMS;
	
	if(is_array($temp) && !empty($temp)) {
		
		switch($temp['row_type']) {
			case TOPIC: {
				$temp['location'] = K4Url::getTopicUrl($temp['post_id']);
				break;
			}
			case REPLY: {
				$temp['location'] = K4Url::getPostUrl($temp['post_id']);
				break;
			}
			case FAQANSWER: {
				$temp['location'] = K4Url::getGenUrl('faq', $temp['category_id'] .'#faq'. $temp['answer_id']);
				break;
			}
		}
		
		$breadcrumbs[]			= $temp;
		
		if($temp['row_type'] & REPLY) {
			if($temp['post_id'] != $temp['parent_id']) {
				loop_recursive($breadcrumbs, $dba, $dba->getRow("SELECT * FROM ". K4POSTS ." WHERE row_level = ". intval($temp['row_level'] - 1) ." AND post_id = ". intval($temp['parent_id']) ." LIMIT 1"));
			} else {
				loop_recursive($breadcrumbs, $dba, $dba->getRow("SELECT * FROM ". K4POSTS ." WHERE post_id = ". intval($temp['post_id']) ." LIMIT 1"));
			}
		}
	}
}

function k4_bread_crumbs(&$template, &$dba, $location = NULL, $info = FALSE, $forum = FALSE) {
	global $_LANG, $_QUERYPARAMS;
	
	if($location != NULL && !$info) {
		
		$page = isset($_LANG[$location]) ? $_LANG[$location] : $location;
		$template->setVar('current_location', $page);
	
	} 
	if($info) {
		
		$message_types		= array(CATEGORY, FORUM, TOPIC, REPLY, GALLERY, METAFORUM, IMAGE, ARCHIVEFORUM);
		$top_level_types	= array(CATEGORY, FORUM, GALLERY, METAFORUM, ARCHIVEFORUM);
		
		$breadcrumbs		= array();
		
		// this will look in forums
		if(in_array($info['row_type'], $message_types)) {
			
			$forum				= in_array($info['row_type'], $top_level_types) ? $info : (is_array($forum) ? $forum : array());
			if(!empty($forum)) {
				$breadcrumbs	= array_reverse(follow_forum_ids($breadcrumbs, $forum));
			}
		
		// this will look in non-message things
		} else {
			if($info['row_type'] & FAQCATEGORY) {
				$breadcrumbs	= follow_faqc_ids($breadcrumbs, $info);
				$breadcrumbs[]	= array('name'=>$template->getVar('L_FAQLONG'),'location'=>K4Url::getGenUrl('faq', ''));
				$breadcrumbs	= array_reverse($breadcrumbs);
			}
		}
				
		/**
		 * Do the recursive section of our bread crumbs if needed
		 */
		if(($info['row_type'] & TOPIC || $info['row_type'] & REPLY) && $forum) {
			
			if($info['row_type'] & REPLY) {
				loop_recursive($breadcrumbs, $dba, $dba->getRow("SELECT * FROM ". K4POSTS ." WHERE post_id = ". intval($info['parent_id']) ." LIMIT 1"));
			}

			switch($info['row_type']) {
				case TOPIC: { $info['location'] = 'viewtopic.php?id='. $info['post_id']; break; }
				case REPLY: { $info['location'] = 'findpost.php?id='. $info['post_id']; break; }
			}

			$breadcrumbs[]	= $info;
		}

		/* Check if we have a preset location or not */
		if($location == NULL || $location == '') {
			$current_location = array_pop($breadcrumbs);
			$template->setVar('current_location', $current_location['name']);
		} else {
			$template->setVar('current_location', (isset($_LANG[$location]) ? $_LANG[$location] : $location));
		}

		/* Set the Breadcrumbs list */
		$it = &new FAArrayIterator($breadcrumbs);
		$template->setList('breadcrumbs', $it);
	}
}

function follow_forum_ids($breadcrumbs, $forum) {
	
	switch($forum['row_type']) {
		case CATEGORY:
		case FORUM:
		case GALLERY: {
			$forum['location'] = K4Url::getForumUrl($forum['forum_id']);
			break;
		}
	}
	
	$breadcrumbs[]			= $forum;

	if(isset($forum['parent_id']) && $forum['parent_id'] > 0) {
		global $_ALLFORUMS;
		$breadcrumbs = follow_forum_ids($breadcrumbs, $_ALLFORUMS[$forum['parent_id']]);

		unset($all_forums);
	}
	
	return $breadcrumbs;
}

function follow_faqc_ids($breadcrumbs, $category) {
	
	$category['location']	= K4Url::getGenUrl('faq', 'c='. $category['category_id']);
	
	$breadcrumbs[]			= $category;
	
	if(isset($category['parent_id']) && $category['parent_id'] > 0) {
		global $_FAQCATEGORIES;

		$breadcrumbs		= follow_faqc_ids($breadcrumbs, $_FAQCATEGORIES[$category['parent_id']]);
	}

	return $breadcrumbs;
}

?>