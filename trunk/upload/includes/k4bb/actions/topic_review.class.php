<?php
/**
* k4 Bulletin Board, topic_review.class.php
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
* @version $Id: topic_review.class.php 144 2005-07-05 02:29:07Z Peter Goodman $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	return;
}

class TopicReviewIterator extends FAArrayIterator {
	
	var $dba;
	var $users = array();
	var $qp;
	var $user;
	var $url;
	
	function TopicReviewIterator(&$dba, $topic, &$replies, $user) {
		$this->__construct($dba, $topic, $replies, $user);
	}

	function __construct(&$dba, $topic, &$replies, $user) {
		
		global $_QUERYPARAMS, $_USERGROUPS, $_URL, $_LANG;
		
		$this->qp						= $_QUERYPARAMS;
		$this->dba						= &$dba;
		$this->groups					= $_USERGROUPS;
		$this->result					= &$replies;
		$this->user						= $user;
		
		// create a url out of this page.
		$url		=& new FAUrl($_URL->__toString());
		$url->args	= array();
		$url->anchor= FALSE;
		$url->file	= 'viewpoll.php';
		$this->url	= $url->__toString();

		$this->poll_text				= $_LANG['L_POLL'];
		
		parent::__construct(array($topic));
	}

	function current() {
		$temp							= parent::current();

		$temp['posticon']				= @$temp['posticon'] != '' ? iif(file_exists(BB_BASE_DIR .'/tmp/upload/posticons/'. @$temp['posticon']), @$temp['posticon'], 'clear.gif') : 'clear.gif';

		if($temp['poster_id'] > 0) {
			$user						= $this->dba->getRow("SELECT ". $this->qp['user'] . $this->qp['userinfo'] ." FROM ". K4USERS ." u LEFT JOIN ". K4USERINFO ." ui ON u.id=ui.user_id WHERE u.id=". intval($temp['poster_id']));
			
			$group						= get_user_max_group($user, $this->groups);
			$user['group_color']		= !isset($group['color']) || $group['color'] == '' ? '000000' : $group['color'];
			$user['group_nicename']		= $group['nicename'];
			$user['group_avatar']		= $group['avatar'];
			
			foreach($user as $key => $val)
				$temp['post_user_'. $key] = $val;
			
			/* This array holds all of the userinfo for users that post to this topic */
			$this->users[$user['id']]	= $user;
			
		}
	
	
		/* Do we have any replies? */
		//$num_replies					= @(($temp['row_right'] - $temp['row_left'] - 1) / 2);
		
		
		$bbcode							= &new BBCodex($this->dba, $this->user, $temp['body_text'], $temp['forum_id'], TRUE, TRUE, TRUE, TRUE);

		if($temp['num_replies'] > 0) {

			$temp['replies']			= &new RepliesReviewIterator($this->result, $this->qp, $this->dba, $this->users, $this->groups, $this->user, $this->url, $this->poll_text, $bbcode);

		}
		
		//$temp['reverted_body_text']		= $bbcode->revert();
		
		if($temp['is_poll'] == 1) {
			do_post_poll_urls($temp['reverted_body_text'], $this->dba, $this->url, $this->poll_text);
		}

		do_post_polls($temp, $this->dba, $this->url, $this->poll_text);
		
		unset($bbcode, $user, $group);

		return $temp;
	}
}

class RepliesReviewIterator extends FAProxyIterator {
	
	var $result;
	var $dba;
	var $img_dir;
	var $forums;
	var $user;
	
	function RepliesReviewIterator(&$result, $queryparams, &$dba, $users, $groups, &$user, $url, $poll_text, &$bbcode) {
		$this->__construct($result, $queryparams, $dba, $users, $groups, $user, $url, $poll_text, $bbcode);
	}

	function __construct(&$result, $queryparams, &$dba, $users, $groups, &$user, $url, $poll_text, &$bbcode) {
		
		$this->users			= $users;
		$this->qp				= $queryparams;
		$this->dba				= &$dba;
		$this->result			= &$result;
		$this->groups			= $groups;
		$this->user				= &$user;
		
		$this->url				= $url;
		$this->poll_text		= $poll_text;
		$this->bbcode			= &$bbcode;

		parent::__construct($this->result);
	}

	function current() {
		$temp					= parent::current();
		
		$temp['posticon']		= isset($temp['posticon']) && $temp['posticon'] != '' ? (file_exists(BB_BASE_DIR .'/tmp/upload/posticons/'. $temp['posticon']) ? $temp['posticon'] : 'clear.gif') : 'clear.gif';

		if($temp['poster_id'] > 0) {
			
			if(!isset($this->users[$temp['poster_id']])) {
			
				$user						= $this->dba->getRow("SELECT ". $this->qp['user'] . $this->qp['userinfo'] ." FROM ". K4USERS ." u LEFT JOIN ". K4USERINFO ." ui ON u.id=ui.user_id WHERE u.id=". intval($temp['poster_id']));
				
				$group						= get_user_max_group($user, $this->groups);
				$user['group_color']		= !isset($group['color']) || $group['color'] == '' ? '000000' : $group['color'];
				$user['group_nicename']		= $group['nicename'];
				$user['group_avatar']		= $group['avatar'];

				$this->users[$user['id']]	= $user;
			} else {
				
				$user						= $this->users[$temp['poster_id']];
			}

			foreach($user as $key => $val)
				$temp['post_user_'. $key] = $val;
		}
		
		$this->bbcode->text				= $temp['body_text'];
		$temp['reverted_body_text']		= $this->bbcode->revert();
		
		if($temp['is_poll'] == 1) {
			$temp['reverted_body_text']	= do_post_poll_urls($temp['reverted_body_text'], $this->dba, $this->url, $this->poll_text);
		}

		do_post_polls($temp, $this->dba, $this->url, $this->poll_text);

		/* Should we free the result? */
		if(!$this->hasNext()) {
			$this->result->free();
		}

		return $temp;
	}
}

?>