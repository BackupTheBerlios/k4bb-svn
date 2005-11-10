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
				$can_bbcode = $request['user']->get('perms') < get_map( 'bbcode', 'can_add', array('forum_id'=>$forum['forum_id'])) ? 0 : 1;
				
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
	$request['template']->setVar('input_id', 'wysiwygcodex');
	$request['template']->setFile('start_editor', (USE_WYSIWYG ? 'editor_wysiwyg.html' : 'editor_bbcode.html'));
	$request['template']->setVar('use_wysiwyg', (USE_WYSIWYG ? 1 : 0));
	$request['template']->setVar('use_bbcode', (USE_WYSIWYG ? 0 : 1));
	
	$request['template']->setVar('javascript', encode_javascript((USE_WYSIWYG ? '/js/wysiwyg.js' : '/js/bbcode.js')));

	if($text != '') {
		$bbcode				= &new BBCodex($request['dba'], $request['user']->getInfoArray(), $text, $forum['forum_id'], TRUE, TRUE, TRUE, TRUE, array('php', 'quote', 'code'));
		
		$reverted			= $bbcode->revert();
		$request['template']->setVar('editor_text_reverted', $reverted);
		
		$bbcode->text		= $reverted;

		$request['template']->setVar('editor_text', $bbcode->parse());
	}
	$request['template']->setVar('editor_enabled', 1);
}

function encode_javascript($filename) {
	$file	= BB_BASE_DIR . $filename;
	$ret	= '';

	if(file_exists($file)) {
		$ret = file_get_contents($file);
		
//		$ret = preg_replace("~//(.*)+$~", '', $ret);
//		$ret = preg_replace("~(\s)?(=|\|\||\+)(\s)?~i", '$2', $ret);
//		$ret = preg_replace("~/\*(.+?)\*/~is", '', $ret);
//		$ret = preg_replace("~\{(\r\n|\r|\n)~i", '{', $ret);
//		$ret = preg_replace("~(\/\/(.+?)(\n|\r\n|\r)$)~i", '', $ret);
//		$ret = preg_replace("~(\t|\n)~i", '', $ret);
//		$ret = preg_replace("~(\n\n|\r\n\r\n|\r\r)~i", "\n", $ret);
//		$ret = preg_replace("~\} (else|catch\(e\)) \{~i", '}$1{', $ret);
		//$ret = preg_replace("~(\r\n|\n|\r|\s)?\}(\r\n|\n|\r|\s)?\}(\r\n|\n|\r|\s)?~i", '}}', $ret);
	}

	return $ret;
}

?>