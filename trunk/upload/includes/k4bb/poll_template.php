<?php
/**
* k4 Bulletin Board, poll_template.php
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
* @package k42
*/

/**
 * Function to make things such as [poll=1] into a clean looking URL
 */
function do_post_poll_urls(&$text, &$dba, $url, $poll_text) {
	// does this reply have a/some poll(s) ?
	preg_match_all('~\[poll=([0-9]+?)\]~i', $text, $poll_matches, PREG_SET_ORDER);
	
	if(count($poll_matches) > 0) {
		foreach($poll_matches as $poll) {					
			$text	= str_replace('[poll='. $poll[1] .']', $poll_text .': [b][url='. $url .'?id='. $poll[1] .']'. $dba->getValue("SELECT question FROM ". K4POLLQUESTIONS ." WHERE id = ". intval($poll[1])) .'[/url][/b]', $text);
		}
	}
}

/**
 * Function to quickly reuse the poll template in iterators
 * @param array temp			The temporary current iteration
 * @param object dba			Database Object
 *
 * @author Peter Goodman
 */
function do_post_polls(&$temp, &$dba, $url = FALSE, $poll_text = FALSE) {

	if($temp['is_poll'] == 1) {
		// does this topic have a/some poll(s) ?
		preg_match_all('~\[poll=([0-9]+?)\]~i', $temp['body_text'], $poll_matches, PREG_SET_ORDER);

		if(count($poll_matches) > 0) {

			foreach($poll_matches as $poll) {
				
				if(!$url && !$poll_text) {
					poll_template($temp['body_text'], $dba, $poll[1], $poll[0], $temp['post_id'], (isset($temp['post_id']) ? $temp['post_id'] : 0));
				} else {
					//do_post_poll_urls($temp['body_text'], $dba, $url, $poll_text);

					$temp['body_text']	= str_replace($poll[0], '[POLL]', $temp['body_text']);
				}
			}

//			if($url && $poll_text) {
//				$anonymous			= &new AnonymousClass(array('text' => $temp['body_text'], 'auto_urls' => FALSE));
//				$bbcodex			= &new BBCodex($dba, $_SESSION['user']->getInfoArray(), $temp['body_text'], FALSE, FALSE, TRUE, FALSE, FALSE);
//				
//				$urlparser			= &new BBUrl($anonymous);
//				$urlparser->to_html();
//			}

		}
	}
}

/**
 * Function to apply a template to polls inside topics/posts
 * @param string text			The text that the poll is in
 * @param object dba			Database object
 * @param integer poll_id		The poll id
 * @param text replace_text		The text that will be replaced by the poll template
 *
 * @author Peter Goodman
 */
function poll_template(&$text, &$dba, $poll_id, $replace_text, $post_id, $post_id = FALSE) {
	
	global $_URL, $_LANG;
	
	// attempt to get our poll question
	$question		= $dba->getRow("SELECT * FROM ". K4POLLQUESTIONS ." WHERE id = ". intval($poll_id));
	
	// is this person logges it?
	$show_results	= $_SESSION['user']->isMember() ? FALSE : TRUE;

	// do we show the results or not?
	$show_results	= isset($_REQUEST['sr'. intval($poll_id)]) && intval($_REQUEST['sr'. intval($poll_id)]) == 1 ? TRUE : $show_results;
	
	// if the question is valid
	if(is_array($question) && !empty($question)) {
		
		$has_voted	= $dba->executeQuery("SELECT * FROM ". K4POLLVOTES ." WHERE question_id = ". intval($question['id']) ." AND user_id = ". intval($_SESSION['user']->get('id')));
		$can_vote	= TRUE;

		if($has_voted->numRows() > 0) {
			$show_results	= TRUE;
			$can_vote		= FALSE;
		}

		/**
		 * POLL TEMPLATE HEADER
		 */

		$tpl	= '<a name="poll'. intval($question['id']) .'" id="poll'. intval($question['id']) .'"></a><div align="center"><div align="center" style="width: 75%;">';
		//$tpl	.=	'	<div style="width: 75%;" class="inset_box_small">';

		if(!$show_results) {
			$tpl.= '<form action="'. K4Url::getGenUrl('viewpoll', 'act=vote&amp;id='. intval($question['id'])) .'" method="post" enctype="multipart/form-data">';
			$tpl.= '<input type="hidden" name="post_id" value="'. intval($post_id) .'" />';
			$tpl.= '<input type="hidden" name="post_id" value="'. intval($post_id) .'" />';
		}
		$tpl	.= '	<div class="subheader"><a href="'. K4Url::getGenUrl('viewpoll', 'id='. $question['id'] .'" title="'. $question['question']) .'">'. $question['question'] .'</a> : '. $_LANG['L_POLLOPTIONS'] .'</div>';
		$tpl	.= '	<div class="spacer">';
		$tpl	.= '		<table width="100%" cellpadding="0" cellspacing="'. K4_TABLE_CELLSPACING .'" border="0" class="table">';

		/**
		 * / POLL TEMPLATE HEADER
		 */
		

		// get the answers
		$answers				= $dba->executeQuery("SELECT * FROM ". K4POLLANSWERS ." WHERE question_id = ". intval($question['id']) ." ORDER BY id ASC");
		$i						= 0;

		// loop through the answers
		while($answers->next()) {
			
			$answer				= $answers->current();
		
			$tpl .= '			<tr class="'. iif(($i % 2) == 0, 'alt1', 'alt2') .'">';
			
			if($show_results) {
				
				$num_votes		= $dba->getValue("SELECT COUNT(*) FROM ". K4POLLVOTES ." WHERE question_id = ". $question['id'] ." AND answer_id = ". $answer['id']);
				$percent		= @ceil(($num_votes / $question['num_votes']) * 100);
				
				$tpl		.=	'	<td align="left"><div class="smalltext">'. k4_htmlentities($answer['answer'], ENT_QUOTES) .'</div></td>';
				$tpl		.=	'	<td width="100" align="left"><div class="smalltext"><div style="float: left;border: 1px solid #333333;width: 100px;height: 18px;background-color: #FFFFFF;"><div style="float: left; height: 18px; width: '. $percent .'%;background-color: #666666;"></div></div><br />('. $percent .'%, '. $num_votes .' '. $_LANG['L_VOTES'] .')</div></td>';

			} else {
				$tpl		.=	'	<td align="left"><div class="smalltext"><label for="vote'. $answer['id'] .'">'. k4_htmlentities($answer['answer'], ENT_QUOTES) .'</label></div></td>';
				$tpl		.=	'	<td align="center"><div class="smalltext"><input type="radio" id="vote'. $answer['id'] .'" name="vote" value="'. $answer['id'] .'" /></div></td>';
			}

			$tpl .= '			</tr>';
			
			$i++;
		}
		
		
		/**
		 * POLL TEMPLATE FOOTER
		 */
		
		if(!$show_results) {
			$tpl.= '			<tr class="base3">';
			$tpl.= '				<td colspan="2" style="text-align:center;"><input type="submit" class="button" value="'. $_LANG['L_VOTE'] .'" /></td>';
			$tpl.= '			</tr>';
		}

		if(!$show_results) {
			$url			= &new FAUrl($_URL->__toString());
			$url->args['sr'. $question['id']] = 1;
			$url->anchor	= FALSE;

			$tpl.= '			<tr class="alt3">';
			$tpl.= '				<td colspan="2" style="text-align:center;"><a class="smalltext" href="'. $url->__toString() .'#poll'. intval($question['id']) .'" title="'. $_LANG['L_VIEWRESULTS'] .'">'. $_LANG['L_VIEWRESULTS'] .'</a></td>';
			$tpl.= '			</tr>';
		} else {
			$url		= &new FAUrl($_URL->__toString());
			unset($url->args['sr'. $question['id']]);
			
			if($can_vote) {
				$tpl.= '		<tr class="alt3">';
				$tpl.= '			<td colspan="2" style="text-align:center;"><a class="smalltext" href="'. $url->__toString() .'#poll'. intval($question['id']) .'" title="'. $_LANG['L_VIEWOPTIONS'] .'">'. $_LANG['L_VIEWOPTIONS'] .'</a></td>';
				$tpl.= '		</tr>';
			}
		}

		$tpl	.= '		</table>';
		$tpl	.= '	</div>';
		
		if(!$show_results)
			$tpl.= '</form>';
		$tpl	.= '</div></div>';

		/**
		 * / POLL TEMPLATE FOOTER
		 */
				
		// replace the poll tag with this poll template
		$text	= str_replace($replace_text, $tpl, $text);
		
	} else {
		$text	= str_replace($replace_text, '', $text); 
	}
}

?>