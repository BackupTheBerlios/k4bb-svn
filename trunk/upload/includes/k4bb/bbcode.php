<?php
/**
* k4 Bulletin Board, bbcode.php
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



if(!defined('IN_K4')) {
	return;
}

/** 
 * Do polls
 */
class K4BBPolls extends FAObject {
	
	var $text;
	var $original;
	var $forum;

	var $original_polls		= array();
	var $new_polls			= array();

	var $post_created;

	function __construct($text, $original_text, $forum, $post_id) {
		
		$this->text			= $text;
//		$this->original		= $original_text;
//		$this->post_id		= $post_id;
//		$this->forum		= $forum;
//
//		/* Initialize the parser */
//		$this->bbcode_parser = new K4BBCodeParser();
//		$this->bbcode_parser->clear_omit_tags();
//		$this->bbcode_parser->set_omit_tags(array('*', 'poll', 'hr'));
	}

	/* Parse the text and make a poll out of it */
	function parse(&$request, &$is_poll) {
		
//		// set whether we can poll or not
//		$can_poll	= ($this->forum['forum_id'] > 0 && $request['user']->get('perms') >= get_map( 'bbcode', 'can_add', array('forum_id'=>$this->forum['forum_id'])));
//
//		// set the poll compiler
//		$this->bbcode_parser->set_compiler('question', new K4Poll_Compiler($request['dba'], $can_poll, $request['template']->getVar('maxpollquestions'), $request['template']->getVar('maxpolloptions')));
//
//		$this->text = $this->bbcode_parser->parse($this->text);
//		
//		$this->second_pass($request, $is_poll);

		return $this->text;
	}
	
	/*
	 * Go back through our body text and make sure that there are a limited
	 * number of polls in this post
	 */
	function second_pass(&$request, &$is_poll) {		

		// go through our text and moderate the number of polls there can be per post
		preg_match_all('~\[poll=([0-9]+?)\]~i', $this->text, $poll_matches, PREG_SET_ORDER);

		if(count($poll_matches) > 0) {
			
			$is_poll	= 1;

			$i = 0;
			foreach($poll_matches as $poll) {
				
				if($i > $request['template']->getVar('maxpollquestions')) {
					
					$this->text = str_replace($poll[0], '', $this->text);
					
					// delete this poll
					$this->delete_poll($request, $poll[1]);

				} else {
					
					// add this poll to the array of new polls in this post
					$this->new_polls[]	= $poll[1];

				}
				
				$i++;
			}
		}

		unset($poll_matches);
				
		$differences		= array_diff($this->original_polls, $this->new_polls);
		
		foreach($differences as $diff) {
			if(!in_array($diff, $this->new_polls)) {
				$this->delete_poll($request, $diff);
			}
		}

	}

	/**
	 * Delete a poll
	 */
	function delete_poll(&$request, $poll_id) {

		// check if this poll is being used somewhere else
		$topic_matches		= $request['dba']->executeQuery("SELECT * FROM ". K4POSTS ." WHERE lower(body_text) LIKE lower('%[poll=". $poll_id ."]%') AND post_id <> ". intval($this->post_id));
		$reply_matches		= $request['dba']->executeQuery("SELECT * FROM ". K4POSTS ." WHERE lower(body_text) LIKE lower('%[poll=". $poll_id ."]%') AND post_id <> ". intval($this->post_id));
		
		// we can delete it
		if( ($topic_matches->numRows() == 0) && ($reply_matches->numRows() == 0) ) {
			
			$request['dba']->executeUpdate("DELETE FROM ". K4POLLQUESTIONS ." WHERE id = ". intval($poll_id));
			$request['dba']->executeUpdate("DELETE FROM ". K4POLLANSWERS ." WHERE question_id = ". intval($poll_id));
			$request['dba']->executeUpdate("DELETE FROM ". K4POLLVOTES ." WHERE question_id = ". intval($poll_id));
		}
	}

	/* Make the text back into a poll */
	function revert(&$request) {
		return $this->text;
	}
}

?>