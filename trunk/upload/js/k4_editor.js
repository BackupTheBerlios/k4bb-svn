/**
 * k4 Bulletin Board, k4RTE JavaScript object and related objects
 * Copyright (c) 2005, Peter Goodman
 * Licensed under the LGPL license
 * http://www.gnu.org/copyleft/lesser.html
 * @author Peter Goodman
 * @version $Id$
 * @package k4bb
 */

var IMG_DIR			= 'js/editor/';
var DEFAULT_SRC		= 'js/editor/blank.html';
var DEFAULT_INST	= 'rte';
var USE_BBCODE		= true;
var USE_RTM			= false;

var color_values	= new Array('black', 'skyblue', 'royalblue', 'blue', 'darkblue', 'orange', 'orangered', 'crimson', 'red', 'firebrick', 'darkred', 'green', 'limegreen', 'seagreen', 'deeppink', 'tomato', 'coral', 'purple', 'indigo', 'burlywood', 'sandybrown', 'sienna', 'chocolate', 'teal', 'silver');
var color_styles	= new Array('color: black;', 'color: skyblue;', 'color: royalblue;', 'color: blue;', 'color: darkblue;', 'color: orange;', 'color: orangered;', 'color: crimson;', 'color: red;', 'color: firebrick;', 'color: darkred;', 'color: green;', 'color: limegreen;', 'color: seagreen;', 'color: deeppink;', 'color: tomato;', 'color: coral;', 'color: purple;', 'color: indigo;', 'color: burlywood;', 'color: sandybrown;', 'color: sienna;', 'color: chocolate;', 'color: teal;', 'color: silver;');
var size_values		= new Array(12, 7, 9, 12, 18, 24);
var size_styles		= new Array('font-size: auto;', 'font-size: 8px;', 'font-size: 9px;', 'font-size: 12px;');

//
// k4RTE Class Constructor
//
function k4RTE(hooks, quicktags) {
	this.hooks		= hooks;
	this.quicktags	= quicktags;
}

// k4RTE class definition
k4RTE.prototype = {

	hooks:		new Object(), // add-on functions
	quicktags:	new Object(), // the JSQuickTags library by Alex King
	tags:		new Array(), // tags array
	lib:		new k4lib(), // the k4Lib library
	rte_mode:	new Array(), // an array of editors

	//
	// initialize the editor
	//
	init: function(textarea_id) {
		
		textarea_obj = this.get_object(textarea_id);

		if(textarea_obj) {
			var iframe_id = textarea_id + '_k4rte';

			this.create_buttons(iframe_id);
			this.create_iframe(iframe_id);
		}
	},

	//
	// get an object
	//
	get_object: function(object_id) {
		ret = false;
		var obj = this.lib.getElementById(object_id);
		if(obj && typeof(obj) == 'object') {
			ret = obj;
		}
		return ret;
	},

	//
	// get the 'document' DOM of an object (an iframe)
	//
	get_object_document: function(iframe_obj) {
		var dom_object		= false;
		var frame_object	= false;

		if(iframe_obj) {
			if (document.all) {
				try { frame_object = frames[iframe_obj.id]; } catch(ex) { debug('Could not fetch Frame object (document.all)', ex); }
			} else {
				try { frame_object = iframe_obj.contentWindow; } catch(e) { debug('Could not fetch Frame object (!document.all)', e); }
			}
			if(frame_object) {

				dom_object	= frame_object.document;

				if(!dom_object && document.all && iframe_obj.contentWindow) {
					dom_object = iframe_obj.contentWindow.document;
				}
			}
		}
		return dom_object;
	},

	//
	// make an iframe go into 'rich text' mode
	//
	richtext_mode: function(iframe_id) {
		var iframe_obj		= this.get_object(iframe_id);
		var iframe_do	    = this.get_object_document(iframe_obj);
		var ret			      = false;
		if(iframe_obj && iframe_do) {

			// open and write html to the iframe
			iframe_do.open();
			iframe_do.write("<html><head><style type=\"text/css\">body { font-family:Tahoma, Arial, Helvetica, Sans-serif;background-color:#FFFFFF; }</style></head><body></body></html>");
			iframe_do.close();

			// try to toggle designmode
			if(typeof(document.designMode) != 'undefined') {
				try {
					iframe_do.designMode	= "On";
					ret						        = true;
				} catch(e) { debug('Could not toggle design mode (document.designMode)', e); }
			}
			if(typeof(iframe_obj.contentDocument.designMode) != 'undefined') {
				try {
					iframe_obj.contentDocument.designMode	= "on";
					ret									= true;
				} catch(ex) { debug('Could not toggle design mode (contentDocument.designMode)', ex); }
			}
			if(typeof(iframe_obj.contentEditable) != 'undefined') {
				iframe_obj.contentEditable	= true;
				ret						= true;
			}
		}
		return ret;
	},

	//
	// Create and position the iframe
	//
	create_iframe: function(iframe_id) {

		var textarea_id = iframe_id.substring(0, iframe_id.length-6);

		if(USE_RTM) {

			// make the iframe
			document.write('<iframe name="' + iframe_id + '" id="' + iframe_id + '" frameborder="no" style="background-color:#FFFFFF;" src="' + DEFAULT_SRC + '"></iframe>');

			if(this.richtext_mode(iframe_id)) {

				// get the iframe and textarea
				var iframe_obj			= this.get_object(iframe_id);
				var textarea_obj		= this.get_object(textarea_id);

				// position the iframe
				iframe_obj.style.width	= this.lib.width(textarea_obj) + 'px';
				iframe_obj.style.height = this.lib.height(textarea_obj) + 'px';

				// set the iframe modes
				this.rte_mode[iframe_id] = false;
				this.switch_mode(iframe_id);
			}
		}
	},

	//
	// Execute a general command (e.g.when a button is clicked)
	//
	exec_command: function(iframe_id, command) {

		// find a way to execute a command
		if(command in this.hooks) {
			eval("this.hooks." + command + "(textarea_id, iframe_id);");
		} else {

			if(this.rte_mode[iframe_id] == true && USE_RTM) {

				// get objects
				var iframe_obj  = this.get_object(iframe_id);
				var iframe_do	  = this.get_object_document(iframe_obj);

				iframe_do.execCommand(command, false, '');
			} else {

				var textarea_id   = iframe_id.substring(0, iframe_id.length-6);
				var textarea_obj	= this.get_object(textarea_id);

				this.quicktags.initialize_tags(textarea_obj, command);
			}
		}
	},

	//
	// Create a button
	//
	create_button: function(iframe_id, alt, img, command, tags_html, tags_bbcode) {

		if(typeof(tags_html) != 'undefined' && typeof(tags_bbcode) != 'undefined') {
			this.quicktags.tags[command] = (USE_BBCODE ? tags_bbcode : tags_html);
		}

		document.writeln('<a href="javascript:' + DEFAULT_INST + '.exec_command(\'' + iframe_id + '\', \'' + command + '\');" title="' + alt + '"><img src="' + IMG_DIR + '' + img + '.gif" name="button_' + command + '" id="button_' + command  + '_' + iframe_id + '" alt="' + alt + '" border="0" /></a>');
	},

	//
	// Create the button set for the editor
	//
	create_buttons: function(iframe_id) {

		document.write('<div class="alt1">');

		this.create_button(iframe_id, 'Bold', 'bold', 'bold', ["<strong>", "</strong>"], ["[b]", "[/b]"]);
		this.create_button(iframe_id, 'Italic', 'italic', 'italic', ["<em>", "</em>"], ["[i]", "[/i]"]);
		this.create_button(iframe_id, 'Underline', 'underline', 'underline', ["<u>", "</u>"], ["[u]", "[/u]"]);
		this.create_button(iframe_id, 'Left', 'justifyleft', 'justifyleft', ["<span style=\"text-align: left;\">", "</span>"], ["[left]", "[/left]"]);
		this.create_button(iframe_id, 'Center', 'justifycenter', 'justifycenter', ["<span style=\"text-align: center;\">", "</span>"], ["[center]", "[/center]"]);
		this.create_button(iframe_id, 'Right', 'justifyright', 'justifyright', ["<span style=\"text-align: right;\">", "</span>"], ["[right]", "[/right]"]);
		this.create_button(iframe_id, 'Justify', 'justifyfull', 'justifyfull', ["<span style=\"text-align: justify;\">", "</span>"], ["[justify]", "[/justify]"]);
		this.create_button(iframe_id, 'Ordered List', 'ol', 'insertorderedlist', ["<ol>", "</ol>"], ["[list=1]", "[/list]"]);
		this.create_button(iframe_id, 'Unordered List', 'ul', 'insertunorderedlist', ["<ol>", "</ul>"], ["[list]", "[/list]"]);
		this.create_button(iframe_id, 'Indent', 'indent', 'indent', ["<span style=\"margin-left: 20px;\">", "</span>"], ["[indent]", "[/indent]"]);
		this.create_button(iframe_id, 'Outdent', 'outdent', 'outdent', ["<span style=\"margin-left: 0px;\">", "</span>"], ["[outdent]", "[/outdent]"]);
		//this.create_button(iframe_id, 'Undo', 'undo', 'undo');
		//this.create_button(iframe_id, 'Redo', 'redo', 'redo');
		this.create_button(iframe_id, 'Color', 'textcolor', 'forecolor');

		if(USE_RTM) {
			document.write('<a href="javascript:'+ DEFAULT_INST + '.switch_mode(\'' + iframe_id + '\');" title="Switch Mode"><img src="' + IMG_DIR + 'switch_format.gif" name="button_switch" id="button_switch_' + iframe_id + '" alt="Switch Mode" border="0" /></a>');
		}

		document.write('</div>');
	},

	//
	// Switch the editor mode
	//
	switch_mode: function(iframe_id) {
		var iframe_obj    = this.get_object(iframe_id);
		var iframe_do	    = this.get_object_document(iframe_obj);
		var textarea_obj	= this.get_object(iframe_id.substring(0, iframe_id.length-6));

		if(iframe_obj && iframe_do && textarea_obj) {
			if(this.rte_mode[iframe_id] == true) {

				this.rte_mode[iframe_id]  = false;
				textarea_obj.style.display= 'block';
				iframe_obj.style.display	= 'none';
				objs					            = this.lib.getElementsByTagName(iframe_do, 'body');
				textarea_obj.innerHTML		= objs[0].innerHTML;
				textarea_obj.value			  = objs[0].innerHTML;

			} else {

				this.rte_mode[iframe_id]		= true;
				textarea_obj.style.display	= 'none';
				iframe_obj.style.display	  = 'block';

				iframe_do.open();
				iframe_do.write(textarea_obj.value);
				iframe_do.close();

			}
		}
	}
}

//
// k4RTEHooks Class constructor
//

function k4RTEHooks() { return true; }

// the class
k4RTEHooks.prototype = {
	forecolor: function(textarea_id, iframe_id) {
		alert('hazaa');
	}
}

/**
 * The following code is modified from JS Quicktags
 * Copyright (c) 2002-2005 Alex King
 * http://www.alexking.org/
 *
 * Licensed under the LGPL license
 * http://www.gnu.org/copyleft/lesser.html
 *
 * Modified on December 11, 2005 by Peter Goodman
 * Modifications:
 *  - Changed function names
 *  - Made it use the k4lib() instead of its old functions
 *  - Put everything into a js class
 *  - Made it remove opening & closing tags when selected and
 *    appropriate button is clicked.
 */

//
// Object Constructor
//
function k4QuickTags() { return true; }

// Class
k4QuickTags.prototype = {

	tags:	new Array(),
	lib:	new k4lib(),

	//
	// push a tag onto the stack
	//
	push_tag: function(cmd) {
		this.lib.array_push(this.tags, cmd);
	},

	//
	// pop a tag off the stack
	//
	pop_tag: function(cmd) {
		if(this.lib.in_array(this.tags, cmd)) {
			this.lib.unset(this.tags, cmd);
		}
	},

	//
	// check if a tag is open
	//
	tag_is_open: function(cmd) {
		ret = false;
		if(this.lib.in_array(this.tags, cmd)) {
			ret = true;
		}
		return ret;
	},

	//
	// replace a selection with the appropriate tag or remove the surrounding tag
	//
	replace_selection: function(open_tag, txt, close_tag) {
		st = txt.substring(0, open_tag.length);
		ed = txt.substring(txt.length - close_tag.length);
		if(st == open_tag && ed == close_tag) {
			return txt.substring(open_tag.length, txt.length - close_tag.length);
		} else {
			return open_tag + txt + close_tag;
		}
	},

	//
	// Deal with the tags
	//
	initialize_tags: function(textarea_obj, cmd) {
		if (document.selection) {

			if(textarea_obj.focus) textarea_obj.focus();

			sel	= document.selection.createRange();
			if (sel.text.length > 0) {
				sel.text = this.replace_selection(this.tags[cmd][0], sel.text, this.tags[cmd][1]);
			} else {
				if (!this.tag_is_open(cmd) || this.tags[cmd][1] == '') {
					sel.text = this.tags[cmd][0];
					this.push_tag(cmd);
				} else {
					sel.text = this.tags[cmd][1];
					this.pop_tag(cmd);
				}
			}
			if(textarea_obj.focus) textarea_obj.focus();
		} else if (textarea_obj.selectionStart || textarea_obj.selectionStart == '0') {

			var startPos	= textarea_obj.selectionStart;
			var endPos		= textarea_obj.selectionEnd;
			var cursorPos	= endPos;
			var scrollTop	= textarea_obj.scrollTop;

			if (startPos != endPos) {
				textarea_obj.value = textarea_obj.value.substring(0, startPos)
							  + this.replace_selection(this.tags[cmd][0], textarea_obj.value.substring(startPos, endPos), this.tags[cmd][1])
							  + textarea_obj.value.substring(endPos, textarea_obj.value.length);
				cursorPos += this.tags[cmd][0].length + this.tags[cmd][1].length;
			} else {
				if (!this.tag_is_open(cmd) || this.tags[cmd][1] == '') {
					textarea_obj.value = textarea_obj.value.substring(0, startPos)
								  + this.tags[cmd][0]
								  + textarea_obj.value.substring(endPos, textarea_obj.value.length);
					this.push_tag(cmd);
					cursorPos = startPos + this.tags[cmd][0].length;
				} else {
					textarea_obj.value = textarea_obj.value.substring(0, startPos)
								  + this.tags[cmd][1]
								  + textarea_obj.value.substring(endPos, textarea_obj.value.length);
					this.pop_tag(cmd);
					cursorPos = startPos + this.tags[cmd][1].length;
				}
			}

			if(textarea_obj.focus) textarea_obj.focus();

			textarea_obj.selectionStart = cursorPos;
			textarea_obj.selectionEnd	= cursorPos;
			textarea_obj.scrollTop		= scrollTop;

		} else {

			if (!this.tag_is_open(cmd) || this.tags[cmd][1] == '') {
				textarea_obj.value += this.tags[cmd][0];
				this.push_tag(cmd);
			} else {
				textarea_obj.value += this.tags[cmd][1];
				this.pop_tag(cmd);
			}

			if(textarea_obj.focus) textarea_obj.focus();
		}
	}
}

//
// Class factories
//
var k4RTEFactory = {
    createInstance: function(hooks, quicktags) {
        return new k4RTE(hooks, quicktags);
    }
}
var k4QuickTagsFactory = {
    createInstance: function() {
        return new k4QuickTags();
    }
}
var k4RTEHooksFactory = {
	createInstance: function() {
		return new k4RTEHooks();
	}
}

//
// Debug function
//
function debug(nice_error, exception) {
	return true;
}
