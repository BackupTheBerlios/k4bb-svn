/**
* k4 Bulletin Board, bbcode.js
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
* @version $Id: bbcode.js,v 1.5 2005/05/16 02:16:22 k4st Exp $
* @package k42
*/

u_enter_url				= "{$L_ENTERURL}";
u_enter_pagetitle		= "{$L_ENTERPAGETITLE}";
s_webpage_title			= "{$L_PAGETITLE}";
u_error_enter_url		= " {$L_ERRORENTERURL}";
s_found_errors			= " {$L_ERRORENTERTITLE}";
s_error					= "{$L_ERROR}";
u_error_img				= "{$L_ENTERIMG}";
u_error_enter_img		= " {$L_ERRORENTERIMG}";

b_help					= "{$L_BBCODE_B_HELP}"; // Bold
i_help					= "{$L_BBCODE_I_HELP}"; // Italic
u_help					= "{$L_BBCODE_U_HELP}"; // Underline
quote_help				= "{$L_BBCODE_Q_HELP}"; // Quote
code_help				= "{$L_BBCODE_C_HELP}"; // Code
php_help				= "{$L_BBCODE_PHP_HELP}"; // PHP
l_help					= "{$L_BBCODE_L_HELP}"; // List
o_help					= "{$L_BBCODE_O_HELP}"; // Ordered List
p_help					= "{$L_BBCODE_P_HELP}"; // Image
w_help					= "{$L_BBCODE_W_HELP}"; // URL
a_help					= "{$L_BBCODE_A_HELP}"; // Close all tags
color_help				= "{$L_BBCODE_S_HELP}"; // Color
size_help				= "{$L_BBCODE_F_HELP}"; // Font Size
//n_help				= "{$L_BBCODE_N_HELP}"; // Font

var bbcode_editors			= new Array()
var bbcode_buttons			= new Array('b', 'i', 'u', 'quote', 'code', 'php')
var bbcode_button_styles	= new Array('font-weight: bold;', 'font-style: italic;', 'text-decoration: underline;')
var bbcode_adv				= new Array('color', 'font', 'size')
var bbcode_opentags			= new Array()
var bbcode_button_objects	= new Array()

/**
 * Get the position of an object in an array depending on its opening tag
 */
function get_obj_pos(tag) {
	var tmp = 0;

	for(var i = 0; i < sizeof(bbcode_opentags); i++) {
		if(bbcode_opentags[i]) {
			if(bbcode_opentags[i][0] == tag) {
				tmp = i;
				return i;
			}
		}
	}
	return tmp;
}

/**
 * Function to get a selection 
 */
function get_selection(editor) {
	var selection = '';

	/* Focus the textarea */
	editor.focus();
	
	if (window.getSelection) {
		if(editor.selectionEnd && (editor.selectionEnd - editor.selectionStart > 0)) {
			selection = true;
		} else {
			//selection	= window.getSelection;
			selection	= false;
		}
	} else if(document.getSelection) {
		selection	= document.getSelection();
	} else if(document.selection) {
		selection	= document.selection.createRange().text;
	} else {
		selection	= false;
	}

	return selection;
}

/** 
 * Function to replace a selection 
 */
function replace_selection(editor, open, close, selection) {
	
	/* Focus the textarea */
	editor.focus();

	/* Several methods of checking and replacing the selection */
	if (window.getSelection) {

		/* Mozilla */
		if(editor.selectionEnd && (editor.selectionEnd - editor.selectionStart > 0)) {
			
			/* Mozilla wrap: From http://www.massless.org/mozedit/ */
			var selection_length					= editor.textLength;
			var selection_start						= editor.selectionStart;
			var selection_end						= editor.selectionEnd;
			if (selection_end == 1 || selection_end == 2)
				selection_end						= selection_length;

			var before_tag							= (editor.value).substring(0, selection_start);
			var selected_text						= (editor.value).substring(selection_start, selection_end)
			var after_tag							= (editor.value).substring(selection_end, selection_length);
			
			matches									= selected_text.match(/(\[.+\])+(.+)+(\[\/.+\])/);

			/* Set the new selection data */
			if(matches && matches != null && sizeof(matches) > 0 && matches[1] == open) {
				editor.value						= before_tag + matches[2] + after_tag;
			} else {
				editor.value						= before_tag + open + selected_text + close + after_tag;
			}
		} else {
			window.getSelection()					= open + window.getSelection() + close;
		}
	} else if(document.getSelection) {
		
		matches										= selection.match(/(\[.+\])+(.+)+(\[\/.+\])/);
		
		if(matches && matches != null && sizeof(matches) > 0 && matches[1] == open) {
			document.getSelection()					= matches[2];					
		} else {	
			document.getSelection()					= open + document.getSelection() + close;
		}
	} else if(document.selection) {

		/* Internet Explorer */		
		matches										= selection.match(/(\[.+\])+(.+)+(\[\/.+\])/);
		if(matches && matches != null && sizeof(matches) > 0 && matches[1] == open) {
			document.selection.createRange().text	= matches[2];					
		} else {
			document.selection.createRange().text	= open + document.selection.createRange().text + close;
		}
	}
}

/** 
 * Function to manage all of the bbcode buttons 
 */
function bbcodex_button_click(i) {
	
	var obj			= FA.getObj(bbcode_button_objects[i]);

	/* Get this context's editor */
	var editor		= bbcode_button_objects[i].split('_');
	editor			= FA.getObj(editor[sizeof(editor)-1]);

	/* Get selected text, if any */
	var selection	= get_selection(editor);
	
	/* Get the value of a select option if it is a select */
	var value		= sizeof(obj) ? obj[obj.selectedIndex].value : null;
	
	/* If we are using a select box, and the value of the selected index is null */
	if(sizeof(obj)) {
		if(obj[obj.selectedIndex] && obj[obj.selectedIndex].value == '') {
			
			/* Return nothing, but focus the editor */
			return editor.focus();
		}
	}
	
	/* Has this person highlighted something? */
	if(selection) {
		
		replace_selection(editor, tag_open(obj.name, value), tag_close(obj.name), selection);
	
	/* If there is no selection */
	} else {
		
		var open_tag	= tag_open(obj.name, value);
		var closed_tag	= tag_close(obj.name);
		
		/* Add the opening tag to the object */
		obj.tag			= open_tag;
		obj.closed_tag	= closed_tag;
		
		/* Check to see if this tag is open or not */
		tag_is_open		= get_open_tag(obj, value);
		
		/* If the tag is already open, close it */
		if(tag_is_open) {
			
			/* Close the current tag, and any others before it that haven't been closed */
			close_tag(obj, editor);

		/* If we are opening this tag */	
		} else {
			
			/* Add the opening value to the editor */
			editor.value	+= open_tag;
			
			/* Change the look of the button itself */
			if(value == null || value == '')
				obj.value	+= '*';

			var new_obj		= new Array(obj.tag, obj.closed_tag, obj)
			
			/* Add this tag to the open tags array */
			array_push(bbcode_opentags, new_obj);
		}
	}

	/* Focus the textarea */
	editor.focus();
}

/** 
 * Close a tag, and any tags that haven't been closed that come before it 
 */
function close_tag(obj, editor) {

	/* Reset the value of the button */
	obj.value		= obj.name.toUpperCase();

	var obj_i		= get_obj_pos(obj.tag);
	
	var new_obj		= bbcode_opentags[obj_i];

	/* Loop through the open tags array */
	for(var i = (sizeof(bbcode_opentags) - 1); i > obj_i; i--) {
		
		/* If the open tag is not the current button object, and that we arn't passed this tag */
		//if(bbcode_opentags[i][0] != obj.tag && new_obj != null) {
			
			if(bbcode_opentags[i]) { 
				/* Update the editor value */
				editor.value				+= bbcode_opentags[i][1];

				/* Change the look of this button */
				bbcode_opentags[i][2].value	= bbcode_opentags[i][2].name.toUpperCase();

				/* Remove this tag from the open tags array */
				array_unset(bbcode_opentags, bbcode_opentags[i]);
			}
		//} else {
		//	new_obj							= bbcode_opentags[i];
		//}
	}
	
	if(new_obj) { }
	else {
		new_obj = new Array(obj.tag, obj.closed_tag, obj)
	}

	/* Add the opening value to the editor */
	editor.value	+= new_obj[1];

	/* Remove this tag from the open tags array */
	array_unset(bbcode_opentags, new_obj);
}

/** 
 * Tag open wrapping 
 */
function tag_open(key, val) {
	if(in_array(bbcode_adv, key)) {
		return '[' + key + '=' + val + ']';
	} else {
		return '[' + key + ']';
	}
}

/**
 * Tag close wrapping 
 */
function tag_close(val) {
	return '[/' + val + ']';
}

/** 
 * get an open tag depending on the object 
 */
function get_open_tag(obj, val) {
	
	/* Make a virtual opening tag using the info given */
	tag = tag_open(obj.name, val);

	/* Loop through all open tags */
	for(var i = 0; i < sizeof(bbcode_opentags); i++) {
		
		if(bbcode_opentags[i]) {
			
			/* The bbcode_opentags[i].tag can be flimsy, so we create a temporary tag to check against */
			//temp_value = null;
			//if(sizeof(bbcode_opentags[i])) {
			//	temp_value = bbcode_opentags[i][bbcode_opentags[i].selectedIndex].value;
			//}

			/* Create the temporary open tag of this element */
			//temp_tag = tag_open(bbcode_opentags[i].name, temp_value);
			//alert(temp_tag);
			//if(bbcode_opentags[i] == obj)
			//	alert('arg');

			/* If the open tag is equal to our virtual tag */
			if(bbcode_opentags[i][0] == tag) {
				return true;
			}
		}
	}

	return false;
}

/**
 * Function to close all tags in an editor
 */
function bbcodex_close_tags(editor_id) {
	var editor = FA.getObj(editor_id);

	/* Loop through the open tags array */
	for(var i = (sizeof(bbcode_opentags) - 1); i >= 0; i--) {
		
		if(bbcode_opentags[i]) { 
			/* Update the editor value */
			editor.value				+= bbcode_opentags[i][1];

			/* Change the look of this button */
			bbcode_opentags[i].value	= bbcode_opentags[i][2].name.toUpperCase();

			/* Remove this tag from the open tags array */
			array_unset(bbcode_opentags, bbcode_opentags[i]);
		}
	}
}

/** 
 * Function to make the opening tag of the textarea 
 */
function bbcodex_init(name, id, rows, cols, button_style) {
	
	try {
		/* Do the buttons */
		document.write('<div id="bbcode_buttons_' + id + '" align="left">');
		
		if(bbcode_buttons) {

			/* loop the buttons array, then spit out the data */
			for(var i = 0; i < sizeof(bbcode_buttons); i++) {
				document.write('<input type="button" name="' + bbcode_buttons[i] + '" id="' + bbcode_buttons[i] + '_' + id + 'codex" value="' + bbcode_buttons[i].toUpperCase() + '" class="' + button_style + '" style="' + (bbcode_button_styles[i] ? bbcode_button_styles[i] : '') + '" accesskey="' + bbcode_buttons[i] + '" onclick="bbcodex_button_click(' + i + ')" onmouseover="bbcodex_helpline(\'' + bbcode_buttons[i] + '\', \'' + bbcode_buttons[i] + '_' + id + 'codex\')" />&nbsp;');
				array_push(bbcode_button_objects, bbcode_buttons[i] + '_' + id + 'codex');
			}
		}

		document.write('<input type="button" name="URL" id="URL_' + id + 'codex" value="URL" class="' + button_style + '" onclick="BBCurl(\'' + id + '\')" onmouseover="bbcodex_helpline(\'w\', \'URL_' + id + 'codex\')" />&nbsp;');
		document.write('<input type="button" name="IMG" id="IMG_' + id + 'codex" value="IMG" class="' + button_style + '" onclick="BBCimg(\'' + id + '\')" onmouseover="bbcodex_helpline(\'p\', \'IMG_' + id + 'codex\')" />');
		
		document.write('<br />');

		/* Create the color selection box */
		create_color_select(id);
		
		/* Create the size selection box */
		create_size_select(id);
		
		/* Close all tags button */
		close_tags_button(id);
		
		document.write('<br />');

		/* Help Line */
		create_helpline(id);

		/* Close the buttons div */
		document.write('</div>');

		/* Get the textarea */
		var editor				= document.getElementById(id + 'codex');

		/* Register this editor */
		array_push(bbcode_editors, editor);
	
	} catch(e) {
		alert(e.message);
	}
}

/**
 * Function to dispay a select field
 */
function draw_select(name, id, values, styles, options) {

	if(sizeof(values) > 0) {
		
		/* Open the select tag */
		document.write('<select name="' + name + '" id="' + id + '" onchange="bbcodex_button_click(' + sizeof(bbcode_button_objects) + ')" onmouseover="bbcodex_helpline(\'' + name + '\', \'' + id + '\')">');
		
		/* Loop through the options and populate the select */
		for(var i = 0; i < sizeof(values); i++) {
			document.write('<option value="' + values[i] + '" style="' + (styles[i] ? styles[i] : '') + '">' + options[i] + '</option>');
		}

		/* Close the select tag */
		document.write('</select>&nbsp;');

		array_push(bbcode_button_objects, id);
	}
}

/**
 * Function to change the Help Line value
 */
function bbcodex_helpline(name, id) {
	var editor			= id.split("_");
	
	if(sizeof(editor) > 0) {
		var helpline	= document.getElementById('helpline_' + editor[sizeof(editor) - 1]);
		helpline.value	= eval(name + '_help');
	}
}

/**
 * URL tag + button
 */
function BBCurl(editor_id) {
	var FoundErrors = '';
	var enterURL   = prompt(u_enter_url, "http://");
	var enterTITLE = prompt(u_enter_pagetitle, s_webpage_title);
	
	if (!enterURL)    {
		FoundErrors += u_error_enter_url;
	}
	if (!enterTITLE)  {
		FoundErrors += s_found_errors;
	}
	if (FoundErrors)  {
		alert(s_error+FoundErrors);
		return;
	}
	var ToAdd = "[URL="+enterURL+"]"+enterTITLE+"[/URL]";
	var editor	= document.getElementById(editor_id + 'codex');
	
	editor.value	+=ToAdd;
	editor.focus();
}

/**
 * Create Image tag + button
 */
function BBCimg(editor_id) {
	var FoundErrors = '';
	
	var enterURL   = prompt(u_error_img,"http://");
	
	if (!enterURL) {
		FoundErrors += u_error_enter_img;
	}
	if (FoundErrors) {
		alert(s_error+FoundErrors);
		return;
	}
	var ToAdd = "[IMG]"+enterURL+"[/IMG]";
	
	var editor	= document.getElementById(editor_id + 'codex');
	
	editor.value	+=ToAdd;
	editor.focus();
}

/**
 * Function to draw the helpline for the bbcode editor
 */
function create_helpline(editor_id) {
	document.write('<input type="text" name="helpline" value="{$L_STYLES_TIP}" id="helpline_' + editor_id + 'codex" readonly="readonly" style="border: 0px; background-color: #FFFFFF; font-size: 11px; width: 500px;" />');
}

/**
 * Function to create the font color selector
 */
function create_color_select(id) {
	var options = new Array('{$L_FONT_COLOR}', '{$L_SKYBLUE}', '{$L_ROYALBLUE}', '{$L_BLUE}', '{$L_DARKBLUE}', '{$L_ORANGE}', '{$L_ORANGERED}', '{$L_CRIMSON}', '{$L_RED}', '{$L_FIREBRICK}', '{$L_DARKRED}', '{$L_GREEN}', '{$L_LIMEGREEN}', '{$L_SEAGREEN}', '{$L_DEEPPINK}', '{$L_TOMATO}', '{$L_CORAL}', '{$L_PURPLE}', '{$L_INDIGO}', '{$L_BURLYWOOD}', '{$L_SANDYBROWN}', '{$L_SIENNA}', '{$L_CHOCOLATE}', '{$L_TEAL}', '{$L_SILVER}')
	var values	= new Array('black', 'skyblue', 'royalblue', 'blue', 'darkblue', 'orange', 'orangered', 'crimson', 'red', 'firebrick', 'darkred', 'green', 'limegreen', 'seagreen', 'deeppink', 'tomato', 'coral', 'purple', 'indigo', 'burlywood', 'sandybrown', 'sienna', 'chocolate', 'teal', 'silver')
	var styles	= new Array('color: black;', 'color: skyblue;', 'color: royalblue;', 'color: blue;', 'color: darkblue;', 'color: orange;', 'color: orangered;', 'color: crimson;', 'color: red;', 'color: firebrick;', 'color: darkred;', 'color: green;', 'color: limegreen;', 'color: seagreen;', 'color: deeppink;', 'color: tomato;', 'color: coral;', 'color: purple;', 'color: indigo;', 'color: burlywood;', 'color: sandybrown;', 'color: sienna;', 'color: chocolate;', 'color: teal;', 'color: silver;')
	
	/* Create our select menu */
	draw_select('color', 'color_' + id + 'codex', values, styles, options);
}

/**
 * Function to create font size selector
 */
function create_size_select(id) {
	
	var options = new Array('{$L_FONT_SIZE}', '{$L_FONT_TINY}', '{$L_FONT_SMALL}', '{$L_FONT_NORMAL}', '{$L_FONT_LARGE}', '{$L_FONT_HUGE}')
	var values	= new Array(12, 7, 9, 12, 18, 24)
	var styles	= new Array('font-size: auto;', 'font-size: 8px;', 'font-size: 9px;', 'font-size: 12px;')
	
	/* Create our select menu */
	draw_select('size', 'size_' + id + 'codex', values, styles, options);
}

/**
 * Function to close all open bbcode tags
 */
function close_tags_button(id) {
	document.write('&nbsp;&nbsp;<a href="javascript:;" title="{$L_BBCODE_CLOSE_TAGS}" onclick="bbcodex_close_tags(\'' + id + 'codex\')" onmouseover="bbcodex_helpline(\'a\', \'closetags_' + id + 'codex\')">{$L_BBCODE_CLOSE_TAGS}</a>');
}

/**
 * Function to add a quote tag to the messagecodex from a topic/reply review
 */
function add_review_quote(user_name, text_holder, messagecodex) {
	var codex			= document.getElementById(messagecodex);
	var textarea		= document.getElementById(text_holder);
	
	if(codex && textarea) {
		if(codex.value != '')
			codex.value += "\n\n";
		codex.value		+= "[quote=" + user_name + "]\n" + textarea.value + "\n[/quote]";
		codex.focus();
	}
}