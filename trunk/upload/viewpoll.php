<?php
/**
* k4 Bulletin Board, viewpoll.php
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
* @version $Id$
* @package k42
*/

require "includes/filearts/filearts.php";
require "includes/k4bb/k4bb.php";

class K4DefaultAction extends FAAction {
	function execute(&$request) {
				
		if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_POLLDOESNTEXIST'), 'content', TRUE);

			return $action->execute($request);
		}

		$question	= $request['dba']->getRow("SELECT * FROM ". K4POLLQUESTIONS ." WHERE id = ". intval($_REQUEST['id']));

		if(!is_array($question) || empty($question)) {
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_POLLDOESNTEXIST'), 'content', TRUE);

			return $action->execute($request);
		}
		
		foreach($question as $key => $val)
			$request['template']->setVar('poll_'. $key, $val);

		$has_voted	= $request['dba']->executeQuery("SELECT * FROM ". K4POLLVOTES ." WHERE question_id = ". intval($question['id']) ." AND user_id = ". intval($request['user']->get('id')));
		
		$request['template']->setVar('can_vote', (($has_voted->numRows() > 0) ? 0 : 1));
		
		// get the poll answers
		$result		= $request['dba']->executeQuery("SELECT * FROM ". K4POLLANSWERS ." WHERE question_id = ". intval($question['id']) ." ORDER BY id ASC");
		
		if(($has_voted->numRows() > 0) || isset($_REQUEST['sr']) || !$request['user']->isMember()) {
			
			$it		= &new K4PollAnswersIterator($result, $request['dba'], $question['num_votes']);

			$request['template']->setList('poll_answers', $it);
			$request['template']->setFile('content', 'poll_results.html');
		} else {
			$request['template']->setList('poll_answers', $result);
			$request['template']->setFile('content', 'poll_vote.html');
		}

		k4_bread_crumbs($request['template'], $request['dba'], $question['question']);

		return TRUE;
	}
}

class K4VoteOnPoll extends FAAction {
	function execute(&$request) {
		
		if(!$request['user']->isMember()) {
			no_perms_error($request);
			return TRUE;
		}

		if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_POLLDOESNTEXIST'), 'content', TRUE);

			return $action->execute($request);
		}

		if(!isset($_POST['vote']) || intval($_POST['vote']) <= 0) {
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_CHOOSEPOLLOPTION'), 'content', TRUE);

			return $action->execute($request);
		}

		$question	= $request['dba']->getRow("SELECT * FROM ". K4POLLQUESTIONS ." WHERE id = ". intval($_REQUEST['id']));

		if(!is_array($question) || empty($question)) {
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_POLLDOESNTEXIST'), 'content', TRUE);

			return $action->execute($request);
		}
		
		$answer		= $request['dba']->getRow("SELECT * FROM ". K4POLLANSWERS ." WHERE id = ". intval($_POST['vote']));

		if(!is_array($answer) || empty($answer)) {
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_POLLOPTIONDOESNTEXIST'), 'content', TRUE);

			return $action->execute($request);
		}

		$has_voted	= $request['dba']->executeQuery("SELECT * FROM ". K4POLLVOTES ." WHERE question_id = ". intval($question['id']) ." AND user_id = ". intval($request['user']->get('id')));
		
		if($has_voted->numRows() > 0) {
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
			$action = new K4InformationAction(new K4LanguageElement('L_USERHASVOTED'), 'content', TRUE);

			return $action->execute($request);
		}
		
		$insert		= $request['dba']->prepareStatement("INSERT INTO ". K4POLLVOTES ." (question_id, answer_id, user_id, user_name, voted_time) VALUES (?,?,?,?,?)");
		$insert->setInt(1, $question['id']);
		$insert->setInt(2, $answer['id']);
		$insert->setInt(3, $request['user']->get('id'));
		$insert->setString(4, $request['user']->get('name'));
		$insert->setInt(5, time());

		$insert->executeUpdate();

		$request['dba']->executeUpdate("UPDATE ". K4POLLQUESTIONS ." SET num_votes=num_votes+1 WHERE id = ". intval($question['id']));

		k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
		$action = new K4InformationAction(new K4LanguageElement('L_VOTEDONPOLL', $answer['answer'], $question['question']), 'content', TRUE, referer() .'#poll'. $question['id'], 3);

		return $action->execute($request);

		return TRUE;
	}
}

class K4PollAnswersIterator extends FAProxyIterator {
	
	var $num_votes, $dba;
	
	function K4PollAnswersIterator(&$result, &$dba, $num_votes) {
		$this->__construct($result, $dba, $num_votes);
	}
	
	function __construct(&$result, &$dba, $num_votes) {
		
		$this->num_votes		= $num_votes;
		$this->dba				= &$dba;

		parent::__construct($result);
	}

	function current() {
		
		global $_QUERYPARAMS;
		
		$temp					= parent::current();

		//$result					= $this->dba->getValue("SELECT * FROM ". K4POLLVOTES ." WHERE answer_id = ". intval($temp['id']));
		$result					= $this->dba->executeQuery("SELECT ". $_QUERYPARAMS['user'] .", u.id as user_id FROM ". K4POLLVOTES ." pv LEFT JOIN ". K4USERS ." u ON u.id=pv.user_id WHERE pv.answer_id = ". intval($temp['id']));

		$temp['users']			= &new UsersIterator($result);
		$temp['num_votes']		= intval($result->numRows());
		$temp['percent']		= @ceil(($temp['num_votes'] / $this->num_votes) * 100);
		
		return $temp;
	}
}

$app = new K4controller('forum_base.html');

$app->setAction('', new K4DefaultAction);
$app->setDefaultEvent('');

$app->setAction('vote', new K4VoteOnPoll);

$app->execute();


?>