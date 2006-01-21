<?php
/**
* k4 Bulletin Board, editor.php
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
	$request['template']->setVar('editor_input_id', 'editor_area');
	$request['template']->setVar('use_wysiwyg', (USE_WYSIWYG ? 1 : 0));
	$request['template']->setVar('use_bbcode', (USE_WYSIWYG ? 0 : 1));
	
	$editor_text = (USE_WYSIWYG ? '<br />' : '');
	if($text != '') {
		$bbcode			= &new BBCodex($request['dba'], $request['user']->getInfoArray(), $text, $forum['forum_id'], TRUE, TRUE, TRUE, TRUE, array('php', 'quote', 'code'));
		
		$editor_text	= $bbcode->revert();
	}

	$request['template']->setVar('editor_text_reverted', $editor_text);
	$request['template']->setVar('editor_enabled', 1);
}

?>