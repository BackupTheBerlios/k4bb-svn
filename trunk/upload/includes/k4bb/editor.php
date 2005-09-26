<?php
/**
* k4 Bulletin Board, editor.php
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
* @version $Id: k4_template.php 134 2005-06-25 15:41:13Z Peter Goodman $
* @package k4-2.0-dev
*/

function create_editor(&$request, $text, $place, $forum = FALSE) {
	global $_LANG;
	
	$can_bbcode		= 0;

	if(isset($place) && $place != '') {
		switch($place) {
			case 'post': {
				$can_bbcode = $request['user']->get('perms') < get_map($request['user'], 'bbcode', 'can_add', array('forum_id'=>$forum['forum_id'])) ? 0 : 1;
				
				break;
			}
			case 'signature': {
				$can_bbcode = intval($request['template']->getVar('allowbbcodesignatures')) == 1 ? 1 : 0;
				
				break;
			}
			case 'pm': {
				$can_bbcode = intval($request['template']->getVar('privallowbbcode')) == 1 ? 1 : 0;
				break;
			}
		}
	}

	$request['template']->setVar('has_bbcode_perms', $can_bbcode);
	
	$request['template']->setFile('start_editor', (USE_WYSIWYG ? 'editor_wysiwyg.html' : 'editor_bbcode.html'));
	$request['template']->setVar('start_wysiwyg', (USE_WYSIWYG ? 1 : 0));
	$request['template']->setVar('can_wysiwyg', (USE_WYSIWYG ? 1 : 0));

	if($text != '') {
		$bbcode				= &new BBCodex($request['dba'], $request['user']->getInfoArray(), $text, $forum['forum_id'], TRUE, TRUE, TRUE, TRUE, array('php', 'quote', 'code'));
		
		$reverted			= $bbcode->revert();
		$request['template']->setVar('editor_text_reverted', $reverted);
		
		$bbcode->text		= $reverted;

		$request['template']->setVar('editor_text', $bbcode->parse());
	}
}

?>