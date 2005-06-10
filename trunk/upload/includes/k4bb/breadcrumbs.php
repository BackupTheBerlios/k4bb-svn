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
* @version $Id: breadcrumbs.class.php,v 1.5 2005/05/16 02:11:54 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	return;
}

function loop_recursive(&$info, &$dba, $temp) {
	
	global $_QUERYPARAMS;
	
	if(is_array($temp) && !empty($temp)) {
		
		switch($temp['row_type']) {
			/* Thread */
			case 4: {
				$temp['location'] = 'viewtopic.php?id='. $temp['id'];
				break;
			}
			/* Reply */
			case 8: {
				$temp['location'] = 'findpost.php?id='. $temp['id'];
				break;
			}
		}

		$info[]					= $temp;

		if($temp['row_level'] >= 3) {
			$this->loop_recursive($info, $dba, $dba->getRow("SELECT * FROM ". K4INFO ." WHERE (row_type = ". TOPIC ." OR row_type = ". REPLY .") AND row_level = ". intval($temp['row_level'] - 1) ." AND id = ". intval($temp['parent_id']) ." LIMIT 1"));
		}
	}
}

function k4_bread_crumbs(&$template, &$dba, $location = NULL, $info = FALSE, $forum = FALSE) {
	global $_LANG, $_QUERYPARAMS;

	if($location != NULL && !$info) {
		
		$page = isset($_LANG[$location]) ? $_LANG[$location] : $location;
		$template->setVar('current_location', $page);
	
	} elseif($info) {
		
		$breadcrumbs	= array();
		
		$forum			= ($info['row_type'] == FORUM || $info['row_type'] == CATEGORY) ? $info : ($forum ? $forum : array());
		
		if(is_array($forum) && !empty($forum)) {
			$query = &$dba->prepareStatement("SELECT * FROM ". K4INFO ." WHERE row_left <= ? AND row_right >= ? ORDER BY row_left ASC");
			$query->setInt(1, intval($forum['row_left']));
			$query->setInt(2, intval($forum['row_right']));
			
			$result			= &$query->executeQuery();
			
			while($result->next()) {
				$current	= $result->current();

				switch($current['row_type']) {
					/* Categories and forums */
					case 1:
					case 2: {
						$current['location'] = 'viewforum.php?id='. $current['id'];
						break;
					}
					/* Gallery Category */
					case 16: {
						$current['location'] = 'viewgallery.php?id='. $current['id'];
						break;
					}
					/* Gallery Image */
					case 32: {
						$current['location'] = 'viewimage.php?id='. $current['id'];
						break;
					}
				}

				$breadcrumbs[] = $current;
			}
		} else {
			trigger_error("Failed to supply forum information to bread crumbs.", E_USER_ERROR);
		}
		
		/* Free up some memory */
		$result->free();
		
		/**
		 * Do the recursive section of our bread crumbs if needed
		 */
		if(($info['row_type'] == TOPIC || $info['row_type'] == REPLY) && $forum) {
			
			if($info['row_level'] >= 3) {
				loop_recursive($breadcrumbs, $dba, $dba->getRow("SELECT * FROM ". K4INFO ." WHERE (row_type = ". TOPIC ." OR row_type = ". REPLY .") AND row_level = ". intval($info['row_level'] - 1) ." AND id = ". intval($info['parent_id']) ." LIMIT 1"));
			}

			switch($info['row_type']) {
				case 4: { $info['location'] = 'viewtopic.php?id='. $info['id']; break; }
				case 8: { $info['location'] = 'findpost.php?id='. $info['id']; break; }
			}

			$breadcrumbs[]	= $info;
		}

		/* Check if we have a preset location or not */
		if($location == NULL) {
			$current_location = array_pop($breadcrumbs);
			$template->setVar('current_location', $current_location['name']);
		} else {
			$template->setVar('current_location', (isset($_LANG[$location]) ? $_LANG[$location] : $location));
		}

		/* Set the Breadcrumbs list */
		$template->setList('breadcrumbs', new FAArrayIterator($breadcrumbs));
	}
}

?>