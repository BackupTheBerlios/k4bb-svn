/**
* k4 Bulletin Board, bbcode.js
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
* @version $Id: bbcode.js 156 2005-07-15 17:51:48Z Peter Goodman $
* @package k42
*/


var bbcode_editors			= new Array();
var bbcode_bbcodes			= new Array('b', 'i', 'u', 'quote', 'code', 'php', 'left', 'center', 'right', 'justify');
var bbcode_buttons			= new Array('bold', 'italic', 'underline', 'quote', 'code', 'php', 'left', 'center', 'right', 'justify');
var bbcode_adv				= new Array('color', 'font', 'size');
var bbcode_opentags			= new Array();
var bbcode_button_objects	= new Array();
//var d						= new k4lib();

/**
 * Get the position of an object in an array depending on its opening tag
 */
function get_obj_pos(tag) {
	var tmp = 0;

	for(var i = 0; i < d.sizeof(bbcode_opentags); i++) {
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
	
	var selected_text = false;

	if(editor) {

		/* Focus the textarea */
		editor.focus();
		
		if (window.getSelection) {
			if(editor.selectionEnd && (editor.selectionEnd - editor.selectionStart > 0)) {
				selected_text = true;
			} else {
				//selection	= window.getSelection;
				selected_text	= false;
			}
		} else if(document.getSelection) {
			selected_text	= document.getSelection();
		} else if(document.selection) {
			selected_text	= document.selection.createRange().text;
		} else {
			selected_text	= false;
		}
	}

	return selected_text;
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
			if(matches && matches != null && d.sizeof(matches) > 0 && matches[1] == open) {
				editor.value						= before_tag + matches[2] + after_tag;
			} else {
				editor.value						= before_tag + open + selected_text + close + after_tag;
			}
		} else {
			window.getSelection						= open + window.getSelection + close;
		}
	} else if(document.getSelection) {
		
		matches										= selection.length == 0 ? false : selection.match(/(\[.+\])+(.+)+(\[\/.+\])/);
		
		if(matches && matches != null && d.sizeof(matches) > 0 && matches[1] == open) {
			document.getSelection					= matches[2];					
		} else {
			document.getSelection					= open + document.getSelection + close;
		}
	} else if(document.selection) {
		
		/* Internet Explorer */		
		matches										= selection.length == 0 ? false : selection.match(/(\[.+\])+(.+)+(\[\/.+\])/);
		
		if(matches && matches != null && d.sizeof(matches) > 0 && matches[1] == open) {
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
	
	var obj			= d.getElementById(bbcode_button_objects[i]);

	/* Get this context's editor */
	var editor		= bbcode_button_objects[i].split('_');
	editor			= d.getElementById(editor[d.sizeof(editor)-1]);
	
	/* Get selected text, if any */
	var selection	= get_selection(editor);
	
	/* Get the value of a select option if it is a select */
	var value		= d.sizeof(obj) ? obj[obj.selectedIndex].value : null;
	
	/* If we are using a select box, and the value of the selected index is null */
	if(d.sizeof(obj)) {
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
			d.array_push(bbcode_opentags, new_obj);
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
	for(var i = (d.sizeof(bbcode_opentags) - 1); i > obj_i; i--) {
		
		/* If the open tag is not the current button object, and that we arn't passed this tag */
		//if(bbcode_opentags[i][0] != obj.tag && new_obj != null) {
			
			if(bbcode_opentags[i]) { 
				/* Update the editor value */
				editor.value				+= bbcode_opentags[i][1];
				
				/* Change the look of this button */
				bbcode_opentags[i][2].value	= bbcode_opentags[i][2].name.toUpperCase();

				/* Remove this tag from the open tags array */
				d.unset(bbcode_opentags, bbcode_opentags[i]);
			}
		//} else {
		//	new_obj							= bbcode_opentags[i];
		//}
	}
	
	if(!new_obj) {
		new_obj = new Array(obj.tag, obj.closed_tag, obj)
	}

	/* Add the opening value to the editor */
	editor.value	+= new_obj[1];

	/* Remove this tag from the open tags array */
	d.unset(bbcode_opentags, new_obj);
}

/** 
 * Tag open wrapping 
 */
function tag_open(key, val) {
	if(d.in_array(bbcode_adv, key)) {
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
	for(var i = 0; i < d.sizeof(bbcode_opentags); i++) {
		
		if(bbcode_opentags[i]) {
			
			/* The bbcode_opentags[i].tag can be flimsy, so we create a temporary tag to check against */
			//temp_value = null;
			//if(d.sizeof(bbcode_opentags[i])) {
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
	var editor = d.getElementById(editor_id);

	/* Loop through the open tags array */
	for(var i = (d.sizeof(bbcode_opentags) - 1); i >= 0; i--) {
		
		if(bbcode_opentags[i]) { 
			
			/* Update the editor value */
			editor.value				+= bbcode_opentags[i][1];

			/* Change the look of this button */
			bbcode_opentags[i][2].value	= bbcode_opentags[i][2].name.toUpperCase();
			
			/* Remove this tag from the open tags array */
			d.unset(bbcode_opentags, bbcode_opentags[i]);
		}
	}
}

/** 
 * Function to make the opening tag of the textarea 
 */
function bbcodex_init(name, id, rows, cols, can_wysiwyg) {
	
	try {
		
		if(!d.is_ie4) {
			try {
				/* Do the buttons */
				document.writeln('<div id="bbcodex_buttons" class="" style="text-align:left;width: 490px;"><div>');
				
				document.writeln('<table cellpadding="0" cellspacing="0" border="0"><tr>');

				if(bbcode_buttons && d) {

					/* loop the buttons array, then spit out the data */
					for(var i = 0; i < d.sizeof(bbcode_buttons); i++) {
						document.writeln('<td><img src="js/editor/' + bbcode_buttons[i] + '.gif" class="editor_button" name="' + bbcode_bbcodes[i] + '" id="' + bbcode_buttons[i] + '_' + id + 'codex" accesskey="' + bbcode_buttons[i] + '" onclick="bbcodex_button_click(' + i + ')" onmouseover="bbcodex_helpline(\'' + bbcode_buttons[i] + '\', \'' + bbcode_buttons[i] + '_' + id + 'codex\');this.className=\'editor_button_over\'" onmouseout="this.className=\'editor_button\'" /></td>');
						d.array_push(bbcode_button_objects, bbcode_buttons[i] + '_' + id + 'codex');
					}
				}

				create_spacer();

				document.writeln('<td><img src="js/editor/hyperlink.gif" class="editor_button" name="url" id="URL_' + id + 'codex" onclick="BBCurl(\'' + id + '\')" onmouseover="bbcodex_helpline(\'w\', \'URL_' + id + 'codex\');this.className=\'editor_button_over\'" onmouseout="this.className=\'editor_button\'" accesskey="v" /></td>');
				document.writeln('<td><img src="js/editor/image.gif" class="editor_button" id="IMG_' + id + 'codex" onclick="BBCimg(\'' + id + '\')" onmouseover="bbcodex_helpline(\'p\', \'IMG_' + id + 'codex\');this.className=\'editor_button_over\'" onmouseout="this.className=\'editor_button\'" accesskey="x" /></td>');
				
				create_spacer();

				//<maps:if list="forums" var="polls" forum="forum_forum_id" method="can_add">
				document.writeln('<td><img src="js/editor/poll.gif" class="editor_button" name="poll" id="POLL_' + id + 'codex" onclick="BBCpoll(\'' + id + '\')" onmouseover="bbcodex_helpline(\'poll\', \'POLL_' + id + 'codex\');this.className=\'editor_button_over\'" onmouseout="this.className=\'editor_button\'" accesskey="z" /></td>');
				//</maps:if>
				
				/**
				 * Write the switch format button, AJAX required
				 */
				if(can_wysiwyg && k4_request) {
				//	document.writeln('<td><img onclick="switch_editor_type(\'bbcode\', \'messagecodex\', \'wysiwygcodex\')" src="js/editor/switch_format.gif" alt="" border="0" class="alt3" style="padding:0px;border: 1px solid #AAAAAA;" /></td>');
				}
				//  style="padding: 0px;"

				document.writeln('</tr><tr><td colspan="15">');
				
				//if(allowemoticons)
				//	document.writeln('<div id="bbcode_emoticons_link" style="float: left;"><div class="editor_buton" onmouseover="this.className=\'editor_button_over\'" onmouseout="this.className=\'editor_button\'"><img src="js/editor/emoticon.gif" alt="Emoticons" border="0" /><img src="js/editor/menupop.gif" alt="" border="0" /></div></div>');

				/* Create the color selection box */
				//document.writeln('<div id="bbcode_colorpicker_link" style="float: left;"><div class="editor_buton" onclick="color_format_type = \'forecolor\'" onmouseover="this.className=\'editor_button_over\'" onmouseout="this.className=\'editor_button\'"><img src="js/editor/textcolor.gif" alt="Font Color" border="0" /><img src="js/editor/menupop.gif" alt="" border="0" /></div></div>');
				create_color_select(id);
				
				/* Create the size selection box */
				create_size_select(id);
				
				/* Close all tags button */
				close_tags_button(id);
				
				document.writeln('</td></tr></table></div><div>');

				document.writeln('</div><div>');

				/* Help Line */
				create_helpline(id);

				/* Close the buttons div */
				document.writeln('</div></div>');

				/* Get the textarea */
				var editor				= d.getElementById(id + 'codex');

				/* Register this editor */
				d.array_push(bbcode_editors, editor);
			} catch(e) {
				alert(e.message);
			}
		}
	} catch(e) {
		alert(e.message);
	}
}

/**
 * Create a spacer between buttons
 */
function create_spacer() {
	document.writeln('<td><img src="js/editor/separator.gif" border="0" alt="" /></td>');
}

/**
 * Function to dispay a select field
 */
function draw_select(name, id, values, styles, options) {

	if(d.sizeof(values) > 0) {
		
		/* Open the select tag */
		document.writeln('<select name="' + name + '" id="' + id + '" class="smalltext" onchange="bbcodex_button_click(' + d.sizeof(bbcode_button_objects) + ')" onmouseover="bbcodex_helpline(\'' + name + '\', \'' + id + '\')">');
		
		/* Loop through the options and populate the select */
		for(var i = 0; i < d.sizeof(values); i++) {
			document.writeln('<option value="' + values[i] + '" style="' + (styles[i] ? styles[i] : '') + '">' + options[i] + '</option>');
		}

		/* Close the select tag */
		document.writeln('</select>&nbsp;');

		d.array_push(bbcode_button_objects, id);
	}
}

/**
 * Function to change the Help Line value
 */
function bbcodex_helpline(name, id) {
	var editor			= id.split("_");
	
	if(editor) {

		if(d.sizeof(editor) > 0) {
			try {
				var helpline	= d.getElementById('helpline_' + editor[d.sizeof(editor) - 1]);
				helpline.value	= eval(name + '_help');
			} catch(e) { }
		}
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
	var ToAdd = "[url=" + enterURL + "]" + enterTITLE + "[/url]";
	var editor	= d.getElementById(editor_id + 'codex');
	
	editor.value	+= ToAdd;
	editor.focus();
}

/**
 * Create Image tag + button
 */
function BBCimg(editor_id) {
	var FoundErrors = '';
	
	var enterURL   = prompt(u_enter_img, "http://");
	
	if (!enterURL) {
		FoundErrors += u_error_enter_img;
	}
	if (FoundErrors) {
		alert(s_error + FoundErrors);
		return;
	}
	var toAdd = "[img]" + enterURL + "[/img]";
	
	var editor	= d.getElementById(editor_id + 'codex');
	
	editor.value	+=  toAdd;
	editor.focus();
}

//<maps:if list="forums" var="polls" forum="forum_forum_id" method="can_add">
/**
 * Create [question] and [*] tags
 */
function BBCpoll(editor_id) {
	
	var FoundErrors		= '';
	var theAnswers		= '';
	
	var enterQuestion   = prompt(u_enter_poll, "");
	
	if (!enterQuestion) {
		FoundErrors		+= u_error_enter_poll;
	} else {

		for(var i = 0; i < parseInt(maxpolloptions); i++) {
			answerValue	= prompt(u_enter_answer, "");
			if(answerValue != '' && answerValue != null) {
				theAnswers	= theAnswers + "[*]" + answerValue + "\n";
			} else {
				break;
			}
		}
	}
	
	if(theAnswers == '') {
		FoundErrors		+= u_error_enter_answers;
	}

	if (FoundErrors) {
		alert(s_error + FoundErrors);
		return;
	}
	
	var toAdd	= "\n[question=" + enterQuestion + "]\n";
	toAdd		= toAdd + theAnswers;
	toAdd		= toAdd + "[/question]\n";
	
	var editor	= d.getElementById(editor_id + 'codex');
	
	editor.value	+=  toAdd;
	editor.focus();
}
//</maps:if>

/**
 * Function to draw the helpline for the bbcode editor
 */
function create_helpline(editor_id) {
	document.writeln('<input type="text" name="helpline" value="' + u_styles_tip + '" id="helpline_' + editor_id + 'codex" style="border: 0px; background-color: #FFFFFF; font-size: 11px; width: 450px;" />');
	
	var helpline		= d.getElementById('helpline_' + editor_id + 'codex');
	
	helpline.onfocus	= function() {
		this.blur();
	}
}

/**
 * Function to create the font color selector
 */
function create_color_select(id) {
	try {
		if(color_options && color_values && color_styles) {
			/* Create our select menu */
			draw_select('color', 'color_' + id + 'codex', color_values, color_styles, color_options);
		}
	} catch(e) { }
}

/**
 * Function to create font size selector
 */
function create_size_select(id) {
	try {
		if(size_values && size_styles && size_options) {
			/* Create our select menu */
			draw_select('size', 'size_' + id + 'codex', size_values, size_styles, size_options);
		}
	} catch(e) { }
}

/**
 * Function to close all open bbcode tags
 */
function close_tags_button(id) {
	document.writeln('&nbsp;&nbsp;<a href="javascript:;" title="' + u_close_tags + '" onclick="bbcodex_close_tags(\'' + id + 'codex\')" onmouseover="bbcodex_helpline(\'a\', \'closetags_' + id + 'codex\')">' + u_close_tags + '</a>');
}

/**
 * Function to add a quote tag to the messagecodex from a topic/reply review
 */
function add_review_quote(user_name, text_holder, messagecodex) {
	var codex			= d.getElementById(messagecodex);
	var textarea		= d.getElementById(text_holder);
	
	if(codex && textarea) {
		if(codex.value != '')
			codex.value += "\n\n";

		codex.value		+= "[quote=" + user_name + "]\n" + textarea.value + "\n[/quote]";
		codex.focus();
	}
}

/**
 * Initialize the editor drop-down menus
 */
function init_bbcode_menus() {
	//menu_init('bbcode_colorpicker_link', 'colorpicker_menu');
	//menu_init('bbcode_bgcolorpicker_link', 'colorpicker_menu');

	//if(allowemoticons) {
		//menu_init('bbcode_emoticons_link', 'emoticons_menu');
	//}
}