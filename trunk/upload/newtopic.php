<?php
/**
* k4 Bulletin Board, newtopic.php
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
* @version $Id: newtopic.php 156 2005-07-15 17:51:48Z Peter Goodman $
* @package k42
*/

require "includes/filearts/filearts.php";
require "includes/k4bb/k4bb.php";

class K4DefaultAction extends FAAction {
	function execute(&$request) {
		
		global $_QUERYPARAMS;
		
		/* Check the request ID */
		if(!isset($_REQUEST['id']) || !$_REQUEST['id'] || intval($_REQUEST['id']) == 0) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INVALIDFORUM');
			
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}
			
		$forum				= $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($_REQUEST['id']));
		
		/* Check the forum data given */
		if(!$forum || !is_array($forum) || empty($forum)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INVALIDFORUM');
			
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}
			
		/* Make sure the we are trying to post into a forum */
		if(!($forum['row_type'] & FORUM)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
			
			$action = new K4InformationAction(new K4LanguageElement('L_CANTPOSTTOCATEGORY'), 'content', FALSE);
			return $action->execute($request);
		}
		
		$is_poll		= (isset($_REQUEST['poll']) && intval($_REQUEST['poll']) == 1) ? TRUE : FALSE;
		$perm			= $is_poll ? 'polls' : 'topics';
					
		/* Do we have permission to post to this forum? */
		if($request['user']->get('perms') < get_map( $perm, 'can_add', array('forum_id'=>$forum['forum_id']))) {
			no_perms_error($request);
			return TRUE;

			//$action = new K4InformationAction(new K4LanguageElement('L_PERMCANTPOST'), 'content', FALSE);
			//return $action->execute($request);
		}
		
		/* Prevent post flooding */
		$last_topic		= $request['dba']->getRow("SELECT * FROM ". K4POSTS ." WHERE poster_ip = '". USER_IP ."' ORDER BY created DESC LIMIT 1");
		$last_reply		= $request['dba']->getRow("SELECT * FROM ". K4POSTS ." WHERE poster_ip = '". USER_IP ."' ORDER BY created DESC LIMIT 1");
		
		if(is_array($last_topic) && !empty($last_topic)) {
			if(intval($last_topic['created']) + POST_IMPULSE_LIMIT > time() && $request['user']->get('perms') < MODERATOR) {
				/* set the breadcrumbs bit */
				k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
				
				$action = new K4InformationAction(new K4LanguageElement('L_MUSTWAITSECSTOPOST'), 'content', TRUE);
				return $action->execute($request);
			}
		}

		if(is_array($last_reply) && !empty($last_reply)) {
			if(intval($last_reply['created']) + POST_IMPULSE_LIMIT > time() && $request['user']->get('perms') < MODERATOR) {
				/* set the breadcrumbs bit */
				k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
				
				$action = new K4InformationAction(new K4LanguageElement('L_MUSTWAITSECSTOPOST'), 'content', TRUE);
				return $action->execute($request);
			}
		}

		/**
		 * Start setting useful template information
		 */
		
		if($is_poll)
			$request['template']->setVar('poll', 1);

		/* Get and set the emoticons and post icons to the template */
		$emoticons	= $request['dba']->executeQuery("SELECT * FROM ". K4EMOTICONS ." WHERE clickable = 1");
		$posticons	= $request['dba']->executeQuery("SELECT * FROM ". K4POSTICONS);

		$request['template']->setList('emoticons', $emoticons);
		$request['template']->setList('posticons', $posticons);

		$request['template']->setVar('emoticons_per_row', $request['template']->getVar('smcolumns'));
		$request['template']->setVar('emoticons_per_row_remainder', $request['template']->getVar('smcolumns')-1);
		
		topic_post_options($request['template'], $request['user'], $forum);

		/* Set the forum info to the template */
		foreach($forum as $key => $val)
			$request['template']->setVar('forum_'. $key, $val);
		
		$request['template']->setVar('newtopic_action', 'newtopic.php?act=posttopic');
		
		// set the default number of available attachments to 0
		// if a draft is loaded, we might subtract from that ;)
		$num_attachments		= 0;

		/**
		 * Get topic drafts for this forum
		 */
		$body_text	= '';
		$drafts		= $request['dba']->executeQuery("SELECT * FROM ". K4POSTS ." WHERE forum_id = ". intval($forum['forum_id']) ." AND is_draft = 1 AND poster_id = ". intval($request['user']->get('id')));
		if($drafts->numrows() > 0) {
			$request['template']->setVisibility('load_button', TRUE);
		
			if(isset($_REQUEST['load_drafts']) && $_REQUEST['load_drafts'] == 1) {
				$request['template']->setVisibility('load_button', FALSE);
				$request['template']->setFile('drafts', 'post_drafts.html');
				$request['template']->setList('drafts', $drafts);
			}
			if(isset($_REQUEST['draft']) && intval($_REQUEST['draft']) != 0) {

				/* Get our topic */
				$draft				= $request['dba']->getRow("SELECT * FROM ". K4POSTS ." WHERE post_id=". intval($_REQUEST['draft']) ." AND is_draft=1 AND poster_id=". intval($request['user']->get('id')));
				
				if(!$draft || !is_array($draft) || empty($draft)) {
					k4_bread_crumbs($request['template'], $request['dba'], 'L_INVALIDDRAFT');
					$action = new K4InformationAction(new K4LanguageElement('L_DRAFTDOESNTEXIST'), 'content', FALSE);
					return $action->execute($request);
				}
				
				$request['template']->setVar('newtopic_action', 'newtopic.php?act=postdraft');
				
				//$action = new K4InformationAction(new K4LanguageElement('L_DRAFTLOADED'), 'drafts', FALSE);

				/* Turn the draft text back into bbcode */
				$bbcode				= new BBCodex($request['dba'], $request['user']->getInfoArray(), $draft['body_text'], $forum['forum_id'], TRUE, TRUE, TRUE, TRUE);
				$draft['body_text']	= $bbcode->revert();

				$body_text			= $draft['body_text'];
				
				$request['template']->setVisibility('save_draft', FALSE);
				$request['template']->setVisibility('load_button', FALSE);
				$request['template']->setVisibility('edit_topic', TRUE);
				$request['template']->setVisibility('post_id', TRUE);
				$request['template']->setVisibility('br', TRUE);
				
				$num_attachments	= $draft['attachments'];

				/* Assign the draft information to the template */
				foreach($draft as $key => $val)
					$request['template']->setVar('post_'. $key, $val);
				
				post_attachment_options($request, $forum, $draft);

				//$action->execute($request);
			}
		}
		
		/**
		 * Deal with file attachments
		 */
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

		/* Create our editor */
		create_editor($request, $body_text, 'post', $forum);

		/* set the breadcrumbs bit */
		k4_bread_crumbs($request['template'], $request['dba'], 'L_POSTTOPIC', $forum);
		
		/* Set the post topic form */
		$request['template']->setFile('content', 'newtopic.html');
		$request['template']->setVar('forum_forum_id', $forum['forum_id']);
		$request['template']->setVisibility('post_topic', TRUE);
		$request['template']->setVar('L_TITLETOOSHORT', sprintf($request['template']->getVar('L_TITLETOOSHORT'), $request['template']->getVar('topicminchars'), $request['template']->getVar('topicmaxchars')));

		return TRUE;
	}
}


$app = new K4controller('forum_base.html');

$app->setAction('', new K4DefaultAction);
$app->setDefaultEvent('');

$app->setAction('posttopic', new InsertPost(TOPIC));
$app->setAction('postdraft', new PostDraft);
$app->setAction('deletedraft', new DeleteDraft);
$app->setAction('edittopic', new EditTopic);
$app->setAction('updatetopic', new UpdatePost(TOPIC));

$app->execute();

?>