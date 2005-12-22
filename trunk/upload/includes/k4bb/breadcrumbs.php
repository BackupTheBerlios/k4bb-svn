<?php
/**
* k4 Bulletin Board, breadcrumbs.class.php
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
				$temp['location'] = 'viewtopic.php?id='. $temp['post_id'];
				break;
			}
			case REPLY: {

				$temp['location'] = 'findpost.php?id='. $temp['post_id'];
				break;
			}
			case FAQANSWER: {
				$temp['location'] = 'faq.php?c='. $temp['category_id'] .'#faq'. $temp['answer_id'];
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
		
		$message_types		= array(CATEGORY, FORUM, TOPIC, REPLY);
		
		$breadcrumbs		= array();
		
		// this will look in forums
		if(in_array($info['row_type'], $message_types)) {
			
			$forum				= ($info['row_type'] & FORUM || $info['row_type'] & CATEGORY) ? $info : (is_array($forum) ? $forum : array());
			if(!empty($forum)) {
				$breadcrumbs	= array_reverse(follow_forum_ids($breadcrumbs, $forum));
			}
		
		// this will look in non-message things
		} else {
			if($info['row_type'] & FAQCATEGORY) {
				$breadcrumbs	= follow_faqc_ids($breadcrumbs, $info);
				$breadcrumbs[]	= array('name'=>$template->getVar('L_FAQLONG'),'location'=>'faq.php');
				$breadcrumbs	= array_reverse($breadcrumbs);
			}
		}
				
		/**
		 * Do the recursive section of our bread crumbs if needed
		 */
		if(($info['row_type'] & TOPIC || $info['row_type'] & REPLY) && $forum) {
			
			if($info['row_type'] & REPLY) {
				if($info['post_id'] != $info['parent_id']) {
					loop_recursive($breadcrumbs, $dba, $dba->getRow("SELECT * FROM ". K4POSTS ." WHERE row_level = ". intval($info['row_level'] - 1) ." AND post_id = ". intval($info['parent_id']) ." LIMIT 1"));
				} else {
					loop_recursive($breadcrumbs, $dba, $dba->getRow("SELECT * FROM ". K4POSTS ." WHERE post_id = ". intval($info['post_id']) ." LIMIT 1"));
				}
			}

			switch($info['row_type']) {
				case 4: { $info['location'] = 'viewtopic.php?id='. $info['post_id']; break; }
				case 8: { $info['location'] = 'findpost.php?id='. $info['post_id']; break; }
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
		case FORUM: {
			$forum['location'] = 'viewforum.php?f='. $forum['forum_id'];
			break;
		}
		case GALLERY: {
			$forum['location'] = 'viewgallery.php?f='. $forum['forum_id'];
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
	
	$category['location']	= 'faq.php?c='. $category['category_id'];
	
	$breadcrumbs[]			= $category;
	
	if(isset($category['parent_id']) && $category['parent_id'] > 0) {
		global $_FAQCATEGORIES;

		$breadcrumbs		= follow_faqc_ids($breadcrumbs, $_FAQCATEGORIES[$category['parent_id']]);
	}

	return $breadcrumbs;
}

?>