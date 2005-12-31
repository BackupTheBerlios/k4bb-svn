/**
* k4 Bulletin Board, rs.js
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
* @package k4-2.0-dev
*/

/**
 * Function to check if the server response is an error
 */
function responseError(response) {
	if(response && typeof(response) != 'undefined') {
		var str = '';
		if(response != '' && response.length > 5) {
			if(response.substring(0, 5) == 'ERROR') {
				str = response.substring(5, response.length);
			}
		}
		return str;
	}
}

function simpleLoadState(obj, anchor) {
	obj.style.display = 'block';
	obj.innerHTML = '<div style="padding: 30px;text-align: center;font-weight: bold;font-size:18px;font-family: Arial, Helvetica, serif;">Loading...</div>';
	document.location = '#' + anchor;
}

/**
 * Function to get the value of a textarea
 */
function textarea_value(textarea) {
	var inner_value = '';
	if(typeof(textarea) == 'object') {
		if(textarea.innerHTML && typeof(textarea.innerHTML) != 'undefined') {
			inner_value = textarea.innerHTML && textarea.innerHTML != '' ? textarea.innerHTML : '';
		}
		inner_value = textarea.value && textarea.value != '' ? textarea.value : inner_value;
	}

	return inner_value;
}

/**
 * Inline Moderation
 */
var edit_mode		= false;
var edit_post_id	= false;
var edit_topic_area = false;

function close_edit_mode() {
	edit_mode = edit_post_id = edit_topic_area = false;
}

function adv_edit(topic_area, post_id, div_id) {
	if(edit_mode && edit_post_id && post_id && (post_id != edit_post_id)) {
		updateTopicTitle(edit_mode, edit_post_id, edit_topic_area);
		close_edit_mode();
	}
	edit_mode		= 'topic' + post_id + '_name';
	edit_post_id	= post_id;
	edit_topic_area = div_id;
	getTopicTitle(post_id);
}

function disable_edit_mode(e) {
	if(edit_mode && edit_post_id && edit_topic_area) {
		if(!e) {
			e = window.event;
		}
		topic_area	= d.getElementById(edit_mode);
		if(e.clientX < d.left(topic_area) ||
			e.clientX > d.right(topic_area) ||
			e.clientY < d.top(topic_area) ||
			e.clientY > d.bottom(topic_area)
			) {
			
			// update the topic name
			updateTopicTitle(edit_mode, edit_post_id, edit_topic_area);
			close_edit_mode();
		}
	}
}
var request_post_id = false;
function getTopicTitle(post_id) {
	k4_request.setRequestType('GET');
	k4_request.Open('mod.php?act=get_topic_title&post_id=' + parseInt(post_id), true);
	k4_request.successState = function() { topicMakeUpdatable(post_id); }
	k4_request.Send("");
}
function topicMakeUpdatable(post_id) {
	var topic_span	= d.getElementById('topicname_' + post_id);
	var topic_area	= d.getElementById('topicname_' + post_id + '_area');
	var response	= k4_request.getResponseText();
	if(topic_span && topic_area) {
		// add an input box into the topic box
		topic_span.innerHTML	= '<input type="text" name="name" id="topic' + post_id + '_name" value="' + response + '" class="alt1" style="padding: 1px;font-size: 11px;" />';

		// attach a function to monitor document clicks
		AttachEvent(document,'click',disable_edit_mode,false);
		if(document.addEventListener ) {
			document.addEventListener('click', disable_edit_mode, false);
		} else if(document.attachEvent ) {
			document.attachEvent('onclick', disable_edit_mode);
		}

		// try to get the input and focus it
		var input = d.getElementById('topic' + post_id + '_name');
		if(input) {
			input.focus();
		}
	}
}
function updateTopicTitle(textbox_id, post_id, div_id) {
	var textbox		= d.getElementById(textbox_id);
	if(textbox) {
		k4_request.setRequestType('POST');
		k4_request.Open('mod.php?act=topic_simpleupdate', true);
		k4_request.successState = function() { topicSimpleUpdate(div_id); }
		k4_request.Send('use_ajax=1&id=' + parseInt(post_id) + '&name=' + encodeURIComponent(textbox.value));
	}
}
function topicSimpleUpdate(topic_area_id) {
	
	var topic_area	= d.getElementById(topic_area_id);
	
	if(topic_area) {
		var response = k4_request.getResponseText();
		if(response != null && response != '') {

			// try to get an error string
			var errorstr = responseError(response);
			
			// if there is an error string
			if(errorstr != '') {
				alert(errorstr);
			} else {
				
				// put in the new topic title
				topic_area.innerHTML	= response;

				// unset some other things
				close_edit_mode();
			}
		}
	}
}

/**
 * Topic Locking
 */
function updateTopicLocked(icon, post_id) {
	if(icon) {
		var lock_regex	= new RegExp("_lock\.gif$", "i");
		var locked		= d.sizeof(icon.src.match(lock_regex)) > 0 ? true : false;
		
		k4_request.setRequestType('GET');
		k4_request.Open('mod.php?act=' + (locked == 1 ? 'un' : '') + 'locktopic&id=' + parseInt(post_id) + '&use_ajax=1', true);
		k4_request.successState = function() { changeLockedIcon(icon); }
		k4_request.Send("");

	} else {
		document.location.href = 'mod.php?act=' + (locked == 1 ? 'un' : '') + 'locktopic&id=' + parseInt(post_id);
	}
}
// change the topic icon for locked
function changeLockedIcon(icon) {

	var response_text = k4_request.getResponseText();
	
	if(response_text != '') {			
		var new_lock			= (locked == 1 ? 0 : 1);
		
		var bad_match			= new RegExp("(announce|sticky)", "i");
		var matches				= icon.src.match(bad_match);
		
		if(d.sizeof(matches) == 0) {
			
			var lock_regex		= new RegExp("_lock\.gif$", "i");
			var locked			= d.sizeof(icon.src.match(lock_regex)) > 0 ? true : false;

			if(locked) {
				new_src		= icon.src.replace(lock_regex, '.gif');
			} else {
				icon_regex		= new RegExp("\.gif$", "i");
				new_src		= icon.src.replace(icon_regex, '_lock.gif');
			}
			d.preload_images(new_src);
			icon.src = new_src;
		}
	}
}

/**
 * Quick Reply
 */
function saveQuickReply(form_id, textarea_id, post_id, forum_id, page) {
	var textarea	= d.getElementById(textarea_id);
	var form		= d.getElementById(form_id);
	var query_string = '';
	if(textarea && form) {
		if(textarea_value(textarea) != '') {
			query_string += 'message=' + encodeURIComponent(textarea_value(textarea));
			
			k4_request.setRequestType('POST');
			k4_request.Open('newreply.php?act=postreply&use_ajax=1&row_type=8&topic_id=' + parseInt(post_id) + '&forum_id=' + parseInt(forum_id) + '&submit_type=post&page=' + parseInt(page), true);
			k4_request.successState = function() { findQuickReply(textarea); }
			k4_request.loadingState = function() { simpleLoadState(preview, 'preview'); }
			k4_request.Send(query_string);
		}
	} else {
		if(form) form.submit();
	}
}
function findQuickReply(textarea) {
	
	var message_holder	= d.getElementById('quick_reply_sent');
	var container		= d.getElementById('quick_reply_content');
	var message			= d.getElementById('quick_reply_message');
	var preview			= d.getElementById('ajax_post_preview');
	
	if(message_holder && container && preview) {
		var response = k4_request.getResponseText();
		if(response != '') {
			
			var errorstr = responseError(response);
			message_holder.style.display = 'block';
			
			if(errorstr == '') {
				
				if(container) {
					container.style.display = 'block';
					container.innerHTML += response;
				}

				if(textarea) { textarea.value		= ''; }
				if(e_textarea) { e_textarea.value	= ''; }

				if(e_iframe) {
					e_iframe_document = get_document(e_iframe);
					e_iframe_document.body.innerHTML = '';
				}
				if(preview) { preview.style.display = 'none'; }
			} else {
				message.innerHTML = errorstr;
			}
		}
	}
}

/**
 * Registration
 */
function errorCheckRegistration(button) {
	
	var fields	= new Array('username','password','password2','email','email2');
	var form	= d.getElementById('register_form');
	if(form) {
		
		var query_string	= new String();
		var sep				= new String();
		
		for(var i = 0; i < d.sizeof(fields); i++) {
			field = d.getElementById(fields[i]);
			if(field) query_string += sep + fields[i] + '=' + encodeURIComponent(field.value);
			sep = '&';
		}
		
		k4_request.setRequestType('POST');
		k4_request.Open('member.php?act=register_user&use_ajax=1', true);
		k4_request.successState = function() { handleRegErrors(button); }
		k4_request.Send(query_string);

	}
}

/**
 * Handle any possible registration errors returned from the request
 */
function handleRegErrors(button) {

	// get the message holder, message area and form
	var message_holder	= d.getElementById('message_holder');
	var message_area	= d.getElementById('form_error');
	var form			= d.getElementById('register_form');
	
	// if everything is a-okay
	if(form && message_holder && message_area) {
		var response = k4_request.getResponseText();
		// if the response was nothing
		if(response == '') {
			
			// submit the form
			form.submit();
			message_area.style.display = 'none';
		
		// otherwise
		} else {
			
			// try to get an error string
			var errorstr = responseError(response);
			
			// if there is an error string
			if(errorstr != '') {

				// set the error to message holder
				message_holder.innerHTML	= errorstr;
				message_area.style.display	= 'block';
				document.location			= '#top';
				d.enableButton(button);

			// Everything's good, submit the form
			} else { form.submit(); }
		}
	} else {
		// if there was an error, try to submit the form
		if(form) { form.submit(); }
	}
}

/**
 * Send a request to get a preview of a post
 */
function setSendPostPreview(form_url) {

	// try to get the form object
	var form = d.getElementById('savepost_form');

	// define several possible required fields to get values
	// from to pass to the request
	var fields = new Array('forum_id','post_id','parent_id','post_id','poster_name','name','posticon','to','cc');
	
	// if everything is good sofar
	if(form) {
		
		// loop through the above defined fields
		var query_string = '';
		for(var i = 0; i < d.sizeof(fields); i++) {
			
			// if the field exists and had a value, add it to the query string
			field = d.getElementById(fields[i]);
			if(field && field.value != '') {
				query_string += '&' + fields[i] + '=' + encodeURIComponent(field.value);
			}
		}

		// get the textare field
		field	= d.getElementById('messagecodex');
		
		// if the value of the textare is not null, add it to the query string
		if(field && textarea_value(field) != '') {
			query_string += '&message=' + encodeURIComponent(textarea_value(field));
		}
		
		k4_request.setRequestType('POST');
		k4_request.Open(form_url + '&use_ajax=1', true);
		k4_request.successState = getPostPreview;
		k4_request.Send('submit_type=preview' + query_string);
	}
}

/**
 * Get the post preview and display it
 */
function getPostPreview() {

	// try to get the form and preview holder
	var form			= d.getElementById('savepost_form');
	var preview_holder	= d.getElementById('ajax_post_preview');
	
	// if everything looks good
	if(preview_holder && form) {
		
		var response = k4_request.getResponseText();
				
		// if there is no response
		if(response == null || response == '') {
			form.submit();
			preview_holder.style.display = 'none';
		
		// if there is a response
		} else {

			// get an error string if any
			var errorstr = responseError(response);
				
			// if there is an error
			if(errorstr != '') {
				preview_holder.innerHTML = '<div class="base2" style="text-align:center;">' + errorstr + '</div><br />';
			
			// otherwise
			} else {
				preview_holder.style.display = 'block';
				preview_holder.innerHTML = response;
			}
		}
	}
}

/**
 * Switch between WYSIWYG and BBCode editors
 */
switchto			= new String();
function switch_editor_type(switch_to, curr_type, textarea_id, iframe_id) {
	
	switchto = switch_to;
	
	// get the textare which will hold values
	e_textarea = d.getElementById(textarea_id);

	// if everything is good and this isn't Opera
	if(e_textarea && !d.is_opera) {
		
		// some checks
		if((switch_to == 'bbcode' && curr_type == 'bbcode') || (switch_to == 'wysiwyg' && curr_type == 'wysiwyg')) {
			return false;
		}

		// if we are switching to bbcode
		if(switch_to == 'bbcode' && curr_type == 'wysiwyg') {
			message_value = get_iframe_text(); // get the iframe value

			if(wysiwyg_editor) {
				wysiwyg_editor.style.display = 'none'; // hide the wysiwyg buttons
			}
		}

		// if we are switching to wysiwyg
		if(switch_to == 'wysiwyg' && curr_type == 'bbcode') {
			bbcode_buttons.style.display = 'none'; // hide the bbcode buttons
			message_value = textarea_value(e_textarea); // get the textarea value
		}
		
		var query_string = '';
		
		// add the textarea/iframe value to the query string
		if(message_value != '') {
			query_string += '&message=' + encodeURIComponent(message_value);
		}
		
		// if we have the forum id and it's semi-valid
		if(forum_id && parseInt(forum_id) > 0) {
			query_string += '&forum_id=' + parseInt(forum_id);
		}
		
		k4_request.setRequestType('POST');
		k4_request.Open('misc.php?act=switch_editor&switchto=' + switch_to, true);
		k4_request.successState = createEditor;
		k4_request.loadingState = function() { simpleLoadState(editorcodex, 'top'); }
		k4_request.Send('use_ajax=1' + query_string + '&switchto=' + switch_to);
	}
}

/**
 * Create the specified editor: bbcode or wysiwyg
 */
function createEditor() {
	if(editorcodex) {
		
		var response = k4_request.getResponseText();		
		if(response != '' && response != null) { 

			// get an error string if any
			var errorstr = responseError(response);
			
			// if there was an error
			if(errorstr != '') {
				//editorcodex.innerHTML = '<div class="base2" style="text-align:center;">' + errorstr + '</div><br />' + editor_container.innerHTML;
			
			// otherwise
			} else {
				
				// if we are switching to bbcode mode, show the bbcode buttons
				if(switchto == 'bbcode' && bbcode_buttons) {
					bbcode_buttons.style.display = 'block';
					//init_bbcode_menus();
				}

				// if we are switching to wysiwyg
				if(bbcode_buttons && switchto == 'wysiwyg') {

					// hide the bbcode buttons
					bbcode_buttons.style.display = 'none';
					
					// show the wysiywg editor
					if(wysiwyg_editor) {
						wysiwyg_editor.style.display = 'block';
					}
				}
				
				editorcodex.innerHTML = '';
				editorcodex.innerHTML = response; // set the editor's inner value to the response
				
				// if we are switching to wysiwyg
				if(bbcode_buttons && switchto == 'wysiwyg') {
					enable_richtext('wysiwygcodex'); // enable richtext mode with the iframe
					populate_wysiwyg('messagecodex', 'wysiwygcodex'); // populate the iframe
					init_wysiwyg_menus(); // initialize the wysiwyg menus
				}
			}
		}
	}
}

/**
 * Functions to toggle the quick editing of topics/replies
 */
var quick_edit = false;
function quickEditPost(post_id, div_id) {
	
	// set the quick edit box
	var quickedit_div		= d.getElementById(div_id);
	
	// if we have a connection and the edit box
	if(quickedit_div) {
		
		// set the height of the quickedit box
		var quickedit_height	= d.height(quickedit_div);
		quickedit_height		= quickedit_height > 200 ? (quickedit_height > 400 ? 400 : quickedit_height) : 200;
		
		// is there a quick edit already open?
		if(typeof(quick_edit) != 'undefined' && quick_edit) {
			if(quick_edit['id'] != post_id) {
				saveQuickEdit(quick_edit['id'], quick_edit['div']);
				quick_edit = false;
			}
			return true;
		}
		
		quick_edit = { 'id' : post_id, 'div' : div_id }

		k4_request.setRequestType('GET');
		k4_request.Open('misc.php?act=revert_text&post_id=' + post_id + '&use_ajax=1', true);
		k4_request.successState = function() { showQuickEditForm(quickedit_div, quickedit_height, post_id, false); }
		k4_request.loadingState = function() { simpleLoadState(quickedit_div, post_id); }
		k4_request.Send("");
	}
}


/**
 * Function to show the quick edit form 
 */
function showQuickEditForm(quickedit_div, quickedit_height, post_id, hide) {
	
	// if we have a connection and the edit box
	if(quickedit_div) {
		var response = k4_request.getResponseText();
		// if the response is nothing
		if(response != null && response != '') {
			
			// see if there is a back-end error sent with this response
			var errorstr = responseError(response);
			
			// display an error
			if(errorstr != '') {
				quickedit_div.innerHTML = '<div class="base2" style="text-align:center;">' + errorstr + '</div><br />';
			
			// display the quick edit box
			} else {
				
				if(!hide) {
					// insert the textarea
					quickedit_div.innerHTML = '<textarea id="' + post_id + '_message" rows="10" cols="100" style="width: 100%;height: ' + quickedit_height + 'px;" class="inputbox">' + response + '</textarea>';
				} else {
					quickedit_div.innerHTML = response;	
				}

				// try to get the button pallette for the quick edit
				var buttons = d.getElementById(post_id + '_qebuttons');
				if(buttons) {
					buttons.style.display = (hide ? 'none' : 'block');
				}
				document.location = '#' + post_id;
			}
		}
	}
}

/**
 * Function that returns the original post text when quick edit
 * has been cancelled
 */
function cancelQuickEdit(post_id, div_id) {
	
	// set the quick edit box
	var quickedit_div		= d.getElementById(div_id);
	
	// if we have a connection and the edit box
	if(quickedit_div) {
		k4_request.setRequestType('GET');
		k4_request.Open('misc.php?act=original_text&post_id=' + post_id + '&use_ajax=1', true);
		k4_request.successState = function() { showQuickEditForm(quickedit_div, 0, post_id, true); }
		k4_request.loadingState = function() { simpleLoadState(quickedit_div, post_id); }
		k4_request.Send("");
	}
}

/**
 * Function that returns the new post text when quick edit
 * has been saved
 */
function saveQuickEdit(post_id, div_id) {
	// set the quick edit box
	var quickedit_div		= d.getElementById(div_id);
	
	// if we have a connection and the edit box
	if(quickedit_div) {
		var textarea		= d.getElementById(post_id + '_message');
		var edited_msg		= textarea_value(textarea);
		quick_edit			= false;

		k4_request.setRequestType('POST');
		k4_request.Open('misc.php?act=save_text&post_id=' + post_id + '&use_ajax=1', true);
		k4_request.successState = function() { showQuickEditForm(quickedit_div, 0, post_id, true); }
		k4_request.loadingState = function() { simpleLoadState(quickedit_div, post_id); }
		k4_request.Send('message=' + encodeURIComponent(edited_msg));
	}
}