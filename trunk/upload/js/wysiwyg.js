/**
* k4 Bulletin Board, wysiwyg.js
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
* @package k4-2.0-dev
*/

var d = new k4lib();

var richtextmode		= true;
var e_iframe			= new Object();
var e_textarea			= new Object();
var e_iframe_document	= new Object();
var open_tags			= new Array();
var color_format_type	= new String();
var selection			= new String();
var sel					= new Object();

/**
 * Function to create and initialize the WYSIWYG editor (requires AJAX)
 */
function init_wysiwyg(textarea_id, iframe_id, can_wysiwyg) {
	
	try {
		
		if(!d.is_opera && !d.is_ie4 && k4_request && can_wysiwyg) {
			
			try {
				document.writeln('<div id="wysiwygcodex_area" style="text-align:left;">');

				/* Create the editor Controls */
				init_editor_controls(textarea_id, iframe_id);
				
				/* Create the iframe that is the editor */
				document.writeln('<iframe name="' + iframe_id + '" id="' + iframe_id + '" frameborder="no" class="inputbox" style="width: 560px; height: 200px; display: none;" src="js/editor/blank.html"></iframe>');
				
				document.writeln('</div>');

				e_iframe	= d.getElementById(iframe_id);

				if(e_iframe) {
					
					if(richtextmode && document.designMode) {
						e_iframe.style.display		= 'block';
						
						/* Enable rich text editing in this iframe */			
						enable_richtext(iframe_id);

					} else {
						richtextmode				= false;
						e_iframe.style.display		= 'none';
					}
				} else {
					richtextmode					= false;
				}
			} catch(e) {
				alert(e.message);
				richtextmode = false;
				return false;
			}
		} else {
			richtextmode = false;
			return false;
		}
	} catch(e) {
		richtextmode = false;
		return false;
	}
}

function init_editor_controls(textarea_id, iframe_id) {
	
	document.writeln('<table cellpadding="0" cellspacing="0" border="0" class=""><tr>');
	
	/**
	 * General buttons
	 */

	create_editor_button('bold', 'b', 'Bold', 'bold', textarea_id, iframe_id);
	create_editor_button('italic', 'i', 'Italic', 'italic', textarea_id, iframe_id);
	create_editor_button('underline', 'u', 'Underline', 'underline', textarea_id, iframe_id);
	create_editor_spacer();
	create_editor_button('left', 'left', 'Align Left', 'justifyleft', textarea_id, iframe_id);
	create_editor_button('center', 'center', 'Align Center', 'justifycenter', textarea_id, iframe_id);
	create_editor_button('right', 'right', 'Align Right', 'justifyright', textarea_id, iframe_id);
	create_editor_button('justify', 'justify', 'Justify Text', 'justifyfull', textarea_id, iframe_id);
	create_editor_spacer();
	create_editor_button('hr', 'hr', 'Horizauntal Rule', 'inserthorizontalrule', textarea_id, iframe_id);
	create_editor_spacer();
	create_editor_button('ol', 'o', 'Ordered List', 'insertorderedlist', textarea_id, iframe_id);
	create_editor_button('ul', 'l', 'Unordered List', 'insertunorderedlist', textarea_id, iframe_id);
	create_editor_button('indent', 'indent', 'Indent', 'indent', textarea_id, iframe_id);
	create_editor_button('outdent', 'outdent', 'Outdent', 'outdent', textarea_id, iframe_id);
	create_editor_spacer();
	create_editor_button('undo', 'undo', 'Undo', 'undo', textarea_id, iframe_id);
	create_editor_button('redo', 'redo', 'Redo', 'redo', textarea_id, iframe_id);
	create_editor_spacer();
	create_editor_custom('image', 'p', 'Image', 'add_image(\'' + iframe_id + '\')', iframe_id);
	
	document.writeln('</tr><tr>');
	
	/**
	 * Things that use bbcode
	 */
	create_editor_custom('code', 'code', 'Code', 'add_wrapping_tag(\'code\', \'code\', \'' + iframe_id + '\')', iframe_id);
	create_editor_custom('php', 'php', 'PHP', 'add_wrapping_tag(\'php\', \'php\', \'' + iframe_id + '\')', iframe_id);
	create_editor_custom('quote', 'quote', 'Quote', 'add_wrapping_tag(\'quote\', \'quote\', \'' + iframe_id + '\')', iframe_id);
	
	document.writeln('<td colspan="17">');
	
	/**
	 * Emoticons and color picker
	 */
	if(allowemoticons)
		document.writeln('<div id="wysiwyg_emoticons_link" style="float: left;"><div class="editor_buton" onmouseover="this.className=\'editor_button_over\'" onmouseout="this.className=\'editor_button\'"><img src="js/editor/emoticon.gif" alt="Emoticons" border="0" /><img src="js/editor/menupop.gif" alt="" border="0" /></div></div>');

	document.writeln('<div id="wysiwyg_colorpicker_link" style="float: left;"><div class="editor_buton" onclick="color_format_type = \'forecolor\'" onmouseover="this.className=\'editor_button_over\'" onmouseout="this.className=\'editor_button\'"><img src="js/editor/textcolor.gif" alt="Font Color" border="0" /><img src="js/editor/menupop.gif" alt="" border="0" /></div></div>');
	/*document.writeln('<div id="bgcolorpicker_link" style="float: left;"><div class="editor_buton" onclick="color_format_type = \'hilitecolor\'" onmouseover="this.className=\'editor_button_over\'" onmouseout="this.className=\'editor_button\'"><img src="js/editor/bgcolor.gif" alt="Background Color" border="0" /><img src="js/editor/menupop.gif" alt="" border="0" /></div></div>');*/
	
	/**
	 * Create the select menus now
	 */
	document.writeln('<select class="smalltext" id="fontname_' + iframe_id + '" onchange="select_value(\'' + iframe_id + '\', this.id)" style="margin-left: 5px;float: left;">');
	document.writeln('	<option value="">[Font]</option>');
	document.writeln('	<option value="Arial, Helvetica, sans-serif">Arial</option>');
	document.writeln('	<option value="Courier New, Courier, mono">Courier New</option>');
	document.writeln('	<option value="Times New Roman, Times, serif">Times New Roman</option>');
	document.writeln('	<option value="Verdana, Arial, Helvetica, sans-serif">Verdana</option>');
	document.writeln('</select>');
	document.writeln('<select class="smalltext" unselectable="on" id="fontsize_' + iframe_id + '" onchange="select_value(\'' + iframe_id + '\', this.id);" style="margin-left: 5px;float: left;">');
	document.writeln('	<option value="">[Size]</option>');
	document.writeln('	<option value="1">1</option>');
	document.writeln('	<option value="2">2</option>');
	document.writeln('	<option value="3">3</option>');
	document.writeln('	<option value="4">4</option>');
	document.writeln('	<option value="5">5</option>');
	document.writeln('	<option value="6">6</option>');
	document.writeln('	<option value="7">7</option>');
	document.writeln('</select>');
	
	/**
	 * Write the switch format button, AJAX required
	 */
	//document.writeln('<div style="float: right;"><img onclick="switch_editor_type(\'wysiwyg\', \'' + textarea_id + '\', \'' + iframe_id + '\')" src="js/editor/switch_format.gif" alt="" border="0" class="alt3" style="padding:0px;border: 1px solid #AAAAAA;" /></div>');

	document.writeln('</td></tr>');
	//document.writeln('<tr><td colspan="20">');
	
	//create_helpline(iframe_id);

	//document.writeln('</td></tr>');
	document.writeln('</table>');
}

function enable_richtext(iframe_id) {
	try {
		
		//e_textarea = d.getElementById(textarea_id);
		e_iframe = d.getElementById(iframe_id);

		if(e_iframe && document.designMode) {
			
			var text		= "";
			var frameHTML	= "";
			
			/* Create the HTML for our frame */
			frameHTML += "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
			frameHTML += "<html xmlns=\"http://www.w3.org/1999/xhtml\" dir=\"ltr\">\n";
			frameHTML += "<head>\n";
			frameHTML += "<style>\n";
			frameHTML += "body {\n";
			frameHTML += "\tbackground: #FFFFFF;\n";
			frameHTML += "\tmargin: 0px;\n";
			frameHTML += "\tpadding: 0px;\n";
			frameHTML += "\tcursor: text;\n";
			frameHTML += "\tfont-size:11px;\n";
			frameHTML += "\tfont-family: verdana, geneva, lucida, arial, helvetica, sans-serif;\n";
			frameHTML += "}\n";
			frameHTML += "</style>\n";
			frameHTML += "</head>\n";
			frameHTML += "<body id=\"" + e_iframe.id + "\">\n";
			frameHTML += "</body>\n";
			frameHTML += "</html>";
			
			/* Set the iframe document variable */
			e_iframe_document = get_document(e_iframe);
			
			/* Enable design mode with this iframe */
			enable_design_mode(1);

			/* Write HTML to the iframe */
			try {
				e_iframe_document.open();
				e_iframe_document.write(frameHTML);
				e_iframe_document.close();

				richtextmode = true;
			} catch(e) {
				richtextmode = false;
			}
		}
	} catch(e) {
		richtextmode = false;
		alert(e.message);
	}
}

/**
 * Function to get the value of a textarea
 */
function textarea_value(textarea) {
	var inner_value = '';
	if(typeof(textarea) == 'object') {
		try {
			if(textarea.innerHTML && typof(textarea.innerHTML) != 'undefined') {
				inner_value = textarea.innerHTML && textarea.innerHTML != '' ? textarea.innerHTML : '';
			}
		} catch(e) { }
		inner_value = textarea.value && textarea.value != '' ? textarea.value : inner_value;
	}

	return inner_value;
}

/**
 * Add the values to the WYSIWYG editor or hide it
 */
function populate_wysiwyg(textarea_id, iframe_id) {
	e_textarea			= d.getElementById(textarea_id);
	e_iframe			= d.getElementById(iframe_id);
	e_iframe_document	= get_document(e_iframe);

	if(e_textarea && e_iframe && e_iframe_document) {
		if(richtextmode) {
			// gets the value of the textarea and removed html comments
			e_iframe_document.body.innerHTML = remove_html_comments(textarea_value(e_textarea));
			e_textarea.style.display = 'none';
		} else {
			e_iframe.style.display = 'none';			
		}
	}
}

/**
 * Function to write the editor's button
 */
function create_editor_button(image, id, alt, format, textarea_id, iframe_id) {
	
	if(richtextmode) {

		document.writeln('<td><a href="javascript:;" onmouseover="wysiwyg_helpline(\'' + id + '\', \'' + iframe_id + '\')" onclick="simple_format_text(\'' + format + '\', \'' + textarea_id + '\', \'' + iframe_id + '\')" title="' + alt + '"><img src="js/editor/' + image + '.gif" name="button_' + id + '" id="button_' + id + '" alt="' + alt + '" class="editor_button" border="0" /></a></td>');

		var button = d.getElementById('button_' + id);
		if(button) {
			button.onmouseover = function() { this.className = 'editor_button_over'; }
			button.onmouseout = function() { this.className = 'editor_button'; }
		}
	}
}

/**
 * Function to create a button with a custom function attached to it
 */
function create_editor_custom(image, id, alt, function_call, iframe_id) {
	if(richtextmode) {

		document.writeln('<td><a href="javascript:;" onmouseover="wysiwyg_helpline(\'' + id + '\', \'' + iframe_id + '\')" onclick="' + function_call + '" title="' + alt + '"><img src="js/editor/' + image + '.gif" name="button_' + id + '" id="button_' + id + '" alt="' + alt + '" class="editor_button" border="0" /></a></td>');

		var button = d.getElementById('button_' + id);
		if(button) {
			button.onmouseover = function() { this.className = 'editor_button_over'; }
			button.onmouseout = function() { this.className = 'editor_button'; }
		}
	}
}

/**
 * Create a spacer between buttons
 */
function create_editor_spacer() {
	if(richtextmode) {
		document.writeln('<td><img src="js/editor/separator.gif" border="0" alt="" /></td>');
	}
}

/**
 * Function to enable the design mode on an iframe
 */
function enable_design_mode(iteration) {

	try {
		try { 
			e_iframe_document.designMode = "On";
		} catch(e) {
			e_iframe.contentDocument.designMode = "on";
		}
	} catch(e) {

		if(iteration <= 10) {
			setTimeout("enable_design_mode(" + parseInt(iteration+1) + ");", 10);
		} else {
			richtextmode = false;
		}
	}

	if(d.is_ie) {
		e_iframe.contentEditable = true;
	}

	return richtextmode;
}	

/**
 * Get the 'document' of the iframe
 */
function get_document(e_iframe) {
	
	e_iframe_document = false;
	e_iframe_window = false;

	if(e_iframe) {
		try {
			if (document.all) {
				try { e_iframe_window = frames[e_iframe.id]; } catch(e) { }
			} else {
				try { e_iframe_window = e_iframe.contentWindow; } catch(e) { }
			}
			e_iframe_document	= e_iframe_window.document; 
			
			// second pass for document.all browsers
			if(!e_iframe_document && document.all) {
				try { 
					e_iframe_document		= e_iframe.contentWindow.document;
				} catch(e) { richtextmode = false; }
			}
				
		} catch(e) { richtextmode = false; }
	} else { richtextmode = false; }

	try {
		e_iframe_document.focus();
	} catch(e) {
		e_iframe_document.focus = function() {
			try { e_iframe_window.focus(); } catch(e) { return true; }
		}
	}

	return e_iframe_document;
}

/**
 * Format Text
 */
function simple_format_text(command, textarea_id, iframe_id) {
	var data			= '';
	
	e_textarea			= d.getElementById(textarea_id);
	e_iframe			= d.getElementById(iframe_id);
	e_iframe_document	= get_document(e_iframe);

	if(e_iframe && e_iframe_document && richtextmode) { // && e_textarea
		try {		
			
			e_iframe_document.focus();

			sel			= iframe_get_selection(iframe_id, false);
			
			if(command == 'createlink') {
				data = prompt(u_enter_url, 'http://');
				if (data != null && data != '' && data != 'http://') {
					e_iframe_document.execCommand('unlink', false, null);
					e_iframe_document.execCommand('createlink', false, data);
				}
			} else {
				
				e_iframe_document.execCommand(command, false, data);
			}
			
			e_iframe_document.focus();
		} catch (e) {
			alert(e.message);
		}
	}
}

/**
 * Function to add color to text
 */
function set_color(color, iframe_id) {
	var menu				= d.getElementById('colorpicker_menu');
	var e_iframe			= d.getElementById(iframe_id);
	var e_iframe_document	= get_document(e_iframe);
	var e_iframe_body		= false;

	if(e_iframe && menu) {
		
		sel					= iframe_get_selection(iframe_id, false);

		try {
			if(color && color != null) {
				command = (color_format_type != null && color_format_type != '') ? color_format_type : command;
				
				if(document.all) {
					if(command == 'hilitecolor') {
						command = 'backcolor';
					}
				}
				
				e_iframe_document.focus();
				e_iframe_document.execCommand(command, false, color);
				e_iframe_document.focus();

				close_open_menu('colorpicker_menu');
			} else {
				e_iframe_document.execCommand('removeformat', false, null);
			}
		} catch(e) { 
			alert(e.message);
		}
		color_format_type = new String();
		menu.style.display = 'none';
	}
}

/**
 * Get a selection of text
 */
function iframe_get_selection(iframe_id, return_range) {
	
	var e_iframe			= d.getElementById(iframe_id);
	var e_iframe_document	= get_document(e_iframe);

	var sel					= new Object();
	var selected_text		= '';
	
	/* First try with the selection */
	try {
		if(e_iframe_document.getSelection) {
			sel = e_iframe_document.getSelection();
			try {
				selected_text = sel.getRangeAt(sel.rangeCount - 1).cloneRange();
			} catch(e) {
				selected_text = sel;
			}
		} else if(e_iframe_document.selection) {
			sel = e_iframe_document.selection.createRange();
			try {
				selected_text = sel.text;
			} catch(e) {
				selected_text = sel;
			}
		} else {
			selected_text = false;
		}
	} catch(e) {
		selected_text = false;
	}
	
	if(!sel && !selected_text) {
		/* Take another try at the selection */
		if (document.all) {
			var selection = e_iframe_document.selection;
			if (selection != null) {
				sel = selection.createRange();
				selected_text = sel.text;
			}
		} else {
			e_iframe_window = e_iframe.contentWindow;
			
			//get currently selected range
			var selection = e_iframe_window.getSelection();
			sel = selection.getRangeAt(selection.rangeCount - 1).cloneRange();
		}
	}
	
	if(!selected_text && !sel) {
		e_iframe_document.focus();
		selected_text = new String();
		sel = new Object();
	}
	
	ret = return_range ? sel : selected_text;

	return ret;
}

/**
 * get the selected range of text
 */
function select_value(iframe_id, select_id) {
	
	var e_iframe			= d.getElementById(iframe_id);
	var e_iframe_document	= get_document(e_iframe);
	var e_iframe_window		= new Object();
	var the_select			= d.getElementById(select_id);
	var selected_text		= iframe_get_selection(iframe_id, true);
	
	var idx					= the_select.selectedIndex;
	
	// First one is always a label
	if (idx != 0) {
		var selected	= the_select.options[idx].value;
		var cmd			= select_id.replace('_' + iframe_id, '');
		
		e_iframe_document.focus();
		e_iframe_document.execCommand(cmd, false, selected);
		e_iframe_document.focus();
		
		the_select.selectedIndex = 0;
	}
}

/**
 * Add an image to the iframe
 */
function add_image(iframe_id) {
	image_url = prompt(u_enter_img, 'http://');		
	insert_image(image_url)
}

/**
 * Function to insert an image
 */
function insert_image(image_url) {
	if(e_iframe) {
	
		var e_iframe_document	= get_document(e_iframe);
		
		sel						= iframe_get_selection(e_iframe.id, true);
		if (image_url != null && image_url != '' && image_url != 'http://') {
			e_iframe_document.focus();
			e_iframe_document.execCommand('InsertImage', false, image_url);
			e_iframe_document.focus();
		}
	}
}

/**
 * Place a selection in opening and closing bbcode tags
 */
function add_wrapping_tag(button_id, tag, iframe_id) {
	var e_iframe			= d.getElementById(iframe_id);
	var e_iframe_document	= get_document(e_iframe);
	var new_content			= '';
	var e_iframe_body		= false;
	
	sel						= iframe_get_selection(iframe_id, false);
	selection				= sel.text;
	
	if(selection && selection != null && selection != '') {
		matches					= selection.length == 0 ? null : selection.match(/(\[.+\])+(.+)+(\[\/.+\])/);
		
		if(matches != null && d.sizeof(matches) > 0 && matches[1] == '[' + tag + ']' && matches[3] == '[/' + tag + ']') {
			new_content	= matches[2];					
		} else {
			new_content = '[' + tag + ']' + selection + '[/' + tag + ']';
		}

		if(document.all) {
			e_iframe_document.selection.createRange().text = new_content;
		} else {
			var e_iframe_window = e_iframe.contentWindow;
			e_iframe_window.getSelection() = new_content;
		}
	} else {
		if(document.all) {
			e_iframe_body = e_iframe_document[iframe_id];
		} else if(document.getElementById) {
			e_iframe_body = e_iframe_document.getElementById(iframe_id);
		}
		
		// second pass try for document.all browsers
		if(!e_iframe_body && document.all) {
			e_iframe_body = e_iframe_document.getElementById(iframe_id);
		}

		try {
			if(tag_is_open(tag)) {
				d.unset(open_tags, tag);
				e_iframe_body.innerHTML += '[/' + tag + ']';
			} else {
				d.array_push(open_tags, tag);
				e_iframe_body.innerHTML += '[' + tag + ']';
			}
		} catch(e) { alert(e.message); }
	}
}

/**
 * Function to check if a tag is open
 */
function tag_is_open(tag) {
	is_open = false;
	
	if(open_tags) {
		for(var i = 0; i < d.sizeof(open_tags); i++) {
			if(open_tags[i] == tag) {
				is_open = true;
				break;
			}
		}
	}

	return is_open;
}

/**
 * Function to change the help line value
 */
function wysiwyg_helpline(name, iframe_id) {
	var helpline	= d.getElementById('helpline_' + iframe_id + 'codex');

	if(helpline) {
		try { helpline.value	= eval(name + '_help'); } catch(e) { helpline.value = u_styles_tip }
	}
}

/**
 * Function to draw the helpline for the bbcode/wysiwyg editor
 */
function create_helpline(editor_id) {
	document.writeln('<input type="text" name="helpline" value="' + u_styles_tip + '" id="helpline_' + editor_id + 'codex" style="border: 0px; background-color: #F7F7F7; font-size: 11px; width: 500px;" />');
	
	var helpline		= d.getElementById('helpline_' + editor_id + 'codex');
	
	helpline.onfocus	= function() {
		this.blur();
	}
}

/**
 * Function to set the text of the editor to the textarea
 */
function set_message_text() {
	try {
		if(e_iframe && e_iframe_document && e_textarea) {
			var e_iframe_body		= false;
			
			if(document.all) {
				e_iframe_body = e_iframe_document[e_iframe.id];
			} else if(document.getElementById) {
				e_iframe_body = e_iframe_document.getElementById(e_iframe.id);
			}

			// second pass try for document.all browsers
			if(!e_iframe_body && document.all) {
				e_iframe_body = e_iframe_document.getElementById(e_iframe.id);
			}

			e_textarea.value		= e_iframe_body.innerHTML;
			e_textarea.innerHTML	= e_iframe_body.innerHTML;
		}
	} catch(e) {
		alert(e.message);
	}
}

/**
 * Function to get the text of the editor
 */
function get_iframe_text() {
	var text = '';

	try {
		if(e_iframe && e_iframe_document && e_textarea) {
			var e_iframe_body		= false;
			
			if(document.all) {
				e_iframe_body = e_iframe_document[e_iframe.id];
			} else if(document.getElementById) {
				e_iframe_body = e_iframe_document.getElementById(e_iframe.id);
			}

			// second pass try for document.all browsers
			if(!e_iframe_body && document.all) {
				e_iframe_body = e_iframe_document.getElementById(e_iframe.id);
			}
			
			text = e_iframe_body.innerHTML;
		}
	} catch(e) {
		alert(e.message);
	}

	return text;
}

/**
 * Initialize the editor drop-down menus
 */
function init_wysiwyg_menus() {
	menu_init('wysiwyg_colorpicker_link', 'colorpicker_menu');
	menu_init('wysiwyg_bgcolorpicker_link', 'colorpicker_menu');

	if(allowemoticons) {
		menu_init('wysiwyg_emoticons_link', 'emoticons_menu');
	}
}

/**
 * Close an open drop-down menu
 */
function close_open_menu(menu_id) {
	if(open_menu) {
		open_menu = false;
		var menu = d.getElementById(menu_id);

		if(menu) {
			menu.style.display = 'none';
		}
	}
}

/**
 * Remove HTML comments from converted bbcode->html
 */
function remove_html_comments(buffer) {
	comment_regex	= new RegExp("(<!--(.+?)-->)");
	buffer			= buffer.replace(comment_regex, '');

	return buffer;
}