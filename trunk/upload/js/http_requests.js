/**
* k4 Bulletin Board, rs.js
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

/**
 * Function to check if the server response is an error
 */
function response_error(response) {
	try {
		if(typeof response != 'undefined') {
			var str = '';
			if(response != '' && response.length > 5) {
				if(response.substring(0, 5) == 'ERROR') {
					str = response.substring(5, response.length);
				}
			}
			return str;
		}
	} catch(e) { alert(e.message); }
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
 * Inline Moderation
 */
function getTopicTitle(topic_id) {
	if(r) {
		r.open("GET", 'mod.php?act=get_topic_title&topic_id=' + parseInt(topic_id), true);
		r.state_handler = function() { /*r.change_ready_state();*/ topicMakeUpdatable(topic_id); };
		r.send(null);
	} else {
		alert('Failed at: r (66)');
	}
}
function topicMakeUpdatable(topic_id) {
	var topic_span	= d.getElementById('topicname_' + topic_id);
	var topic_area	= d.getElementById('topicname_' + topic_id + '_area');
	
	if(r && topic_span && topic_area) {
		try {
			if(r.readyState == 4) {
				if(r.status == 200) {
					if(r.response != null && r.response != '') {
						
						// add an input box into the topic box
						topic_span.innerHTML	= '<input type="text" name="name" id="topic' + topic_id + '_name" value="' + r.response + '" class="alt1" style="padding: 1px;font-size: 11px;" />';

						// attach a function to monitor document clicks
						AttachEvent(document,'click',disable_edit_mode,false);
						if(document.addEventListener ) {
							document.addEventListener('click', disable_edit_mode, false);
						} else if(document.attachEvent ) {
							document.attachEvent('onclick', disable_edit_mode);
						}

						// try to get the input and focus it
						var input = d.getElementById('topic' + topic_id + '_name');
						if(input) {
							input.focus();
						}
					}
				} else {
					r.close();
				}
			}
		} catch(e) { alert(e.message); }
	} else {
		alert('Failed at rs (79)');
	}
}

function updateTopicTitle(textbox_id, topic_id, div_id) {
	var textbox		= d.getElementById(textbox_id);
	
	if(r && textbox) {
		try {
			r.open("POST", 'mod.php?act=topic_simpleupdate', true);
			r.state_handler = function() { /*r.change_ready_state();*/ topicSimpleUpdate(div_id); }
			r.send('use_ajax=1&id=' + parseInt(topic_id) + '&name=' + encodeURIComponent(textbox.value));
		} catch(e) { alert(e.message); }
	} else {
		alert('Failed at rs (106)');
	}
}
function topicSimpleUpdate(topic_area_id) {
	
	var topic_area	= d.getElementById(topic_area_id);
	
	if(r && topic_area) {
		try {
			if(r.readyState == 4) {
				if(r.status == 200) {
					if(r.response != null && r.response != '') {

						// try to get an error string
						var errorstr = response_error(r.response);
						
						// if there is an error string
						if(errorstr != '') {
							alert(errorstr);
						} else {
							
							// put in the new topic title
							topic_area.innerHTML	= r.response;

							// unset some other things
							close_edit_mode();
						}
					}
				} else {
					r.close();
				}
			}
		} catch(e) { alert(e.message); }
	} else {
		alert('Failed at rs (119)');
	}
}

/**
 * Topic Locking
 */
function updateTopicLocked(icon, topic_id) {
	if(r && icon) {
		try {
			
			var lock_regex	= new RegExp("_lock\.gif$", "i");
			var locked		= d.sizeof(icon.src.match(lock_regex)) > 0 ? true : false;
			
			r.open("POST", 'mod.php?act=' + (locked == 1 ? 'un' : '') + 'locktopic', true);
			r.state_handler = function() { /*r.change_ready_state();*/ changeLockedIcon(icon); }
			r.send('id=' + parseInt(topic_id) + '&use_ajax=1');

		} catch(e) { alert(e.message); }
	} else {
		document.location = 'mod.php?act=' + (locked == 1 ? 'un' : '') + 'locktopic&id=' + parseInt(topic_id);
	}
}
// change the topic icon for locked
function changeLockedIcon(icon) {
	if(r && icon) {
		if(r.readyState == 4) {
			if(r.status == 200) {
				if(r.response != '') {
					
					var new_lock	= (locked == 1 ? 0 : 1);
					
					var bad_match			= new RegExp("(announce|sticky)", "i");
					var matches				= icon.src.match(bad_match);
					
					if(d.sizeof(matches) == 0) {
						
						var lock_regex	= new RegExp("_lock\.gif$", "i");
						var locked		= d.sizeof(icon.src.match(lock_regex)) > 0 ? true : false;

						if(locked) {
							icon.src		= icon.src.replace(lock_regex, '.gif');
						} else {
							icon_regex		= new RegExp("\.gif$", "i");
							icon.src		= icon.src.replace(icon_regex, '_lock.gif');
						}
					}
				}
			} else {
				r.close();	
			}
		}
	}
}

/**
 * Quick Reply
 */
var textarea;
function saveQuickReply(form_id, textarea_id, topic_id, page) {
	try {
		var textarea	= d.getElementById(textarea_id);
		var form		= d.getElementById(form_id);
		var query_string = '';
		if(r && textarea && form) {
			if(textarea_value(textarea) != '') query_string += 'message=' + encodeURIComponent(textarea_value(textarea));
			try {
				r.open("POST", 'newreply.php?act=postreply&use_ajax=1&topic_id=' + parseInt(topic_id) + '&submit_type=post&page=' + parseInt(page), true);
				r.state_handler = function() { /*r.change_ready_state();*/ findQuickReply(); };
				r.send(query_string);
			} catch(e) {
				form.submit();
			}
		} else {
			if(form) form.submit();
		}
	} catch(e) { alert(e.message); }
}
function findQuickReply() {
	
	var message_holder	= d.getElementById('quick_reply_sent');
	var container		= d.getElementById('quick_reply_content');
	var message			= d.getElementById('quick_reply_message');
	var preview			= d.getElementById('ajax_post_preview');
	
	if(r && message_holder && container && preview) {
		if(r.readyState < 4 && preview) {
			preview.style.display = 'block';
			document.location = '#preview';
			preview.innerHTML = '';
			preview.innerHTML = '<div style="padding: 30px;text-align: center;font-weight: bold;font-size:18px;font-family: Arial, Helvetica, serif;">Loading...</div>';
		}		
		if(r.readyState == 4) {
			if(r.status == 200) {
				if(r.response != '') {
					
					var errorstr = response_error(r.response);
					message_holder.style.display = 'block';
					
					if(errorstr == '') {
						
						if(container) {
							container.style.display = 'block';
							container.innerHTML += r.response;
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
			} else {
				r.close();
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
	
	try {
		if(r && form) {
			
			var query_string	= new String();
			var sep				= new String();
			
			for(var i = 0; i < d.sizeof(fields); i++) {
				field = d.getElementById(fields[i]);
				if(field) query_string += sep + fields[i] + '=' + encodeURIComponent(field.value);
				sep = '&';
			}

			try {
				r.open("POST", 'member.php?act=register_user&use_ajax=1', true);
				r.state_handler = function() { /*r.change_ready_state();*/ handleRegErrors(button); }
				r.send(query_string);

			} catch(e) { form.submit(); return true; }
		} else {
			if(form) { form.submit(); return true; }
		}
	} catch(e) { alert(e.message); }
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
	if(r && message_holder && message_area) {

		// if the request has completed
		if(r.readyState == 4) {
			if(r.status == 200) {
				if(r.response) {
					// if the response was nothing
					if(r.response == '') {
						
						// submit the form
						form.submit();
						message_area.style.display = 'none';
						return true;
					
					// otherwise
					} else {
						
						// try to get an error string
						var errorstr = response_error(r.response);
						
						// if there is an error string
						if(errorstr != '') {

							// set the error to message holder
							message_holder.innerHTML	= errorstr;
							message_area.style.display	= 'block';
							document.location			= '#top';
							d.enableButton(button);

						// Everything's good, submit the form
						} else { form.submit(); return true; }
					}
				} else {
					// set the error to message holder
					message_holder.innerHTML	= 'Error retrieving response. Please contact this forum\'s administrator.';
					message_area.style.display	= 'block';
					document.location			= '#top';
					d.enableButton(button);
				}
			} else {

				// abort the request
				r.close();
			}
		}
	} else {

		// if there was an error, try to submit the form
		if(form) { form.submit(); return true; }
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
	var fields = new Array('forum_id','topic_id','parent_id','reply_id','poster_name','name','posticon','to','cc');
	
	try {

		// if everything is good sofar
		if(r && form) {
			
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
			
			try {

				// open and send the request
				r.open("POST", form_url + '&use_ajax=1', true);
				r.state_handler = function() { /*r.change_ready_state();*/ getPostPreview(); }
				r.send('submit_type=preview' + query_string);

			} catch(e) { form.submit(); }
		} else {
			if(form) form.submit();
		}
	} catch(e) { alert(e.message); }
}

/**
 * Get the post preview and display it
 */
function getPostPreview() {
	
	// try to get the form and preview holder
	var form			= d.getElementById('savepost_form'); // get the current form
	var preview_holder	= d.getElementById('ajax_post_preview'); // get the preview holder
	
	// if everything looks good
	if(r && preview_holder && form) {
		
		try {
	
			// if the request is loading
			if(r.readyState < 4) {
				
				// display the preview holder
				preview_holder.style.display = 'block';
				
				// insert the post preview into the holder
				preview_holder.innerHTML = '<div style="padding: 30px;text-align: center;font-weight: bold;font-size:18px;font-family: Arial, Helvetica, serif;">Loading...</div>';
				
				// move the page to the preview anchor
				document.location = '#preview';
			}

			// if the request is complete
			if(r.readyState == 4) {

				// if there were no errors
				if(r.status == 200) {
					
					// if there is no response
					if(r.response == null || r.response == '') {
						
						form.submit();
						preview_holder.style.display = 'none';
					
					// if there is a response
					} else {

						// get an error string if any
						var errorstr = response_error(r.response);
							
						// if there is an error
						if(errorstr != '') {
							preview_holder.innerHTML = '<div class="special_panel" style="text-align:center;">' + errorstr + '</div><br />';
						
						// otherwise
						} else {
							preview_holder.style.display = 'block';
							preview_holder.innerHTML = r.response;
						}
					}
				} else {

					// abort the request
					r.close();
				}
			}
		} catch(e) { alert(e.message); }
	} else {
		if(form) form.submit();
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

	try {

		// if everything is good and this isn't Opera
		if(r && e_textarea && !d.is_opera) {
			
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
			
			try {
				
				// open and send a request
				r.open("POST", 'misc.php?act=switch_editor&switchto=' + switch_to, true);
				r.state_handler = function() { /*r.change_ready_state();*/ createEditor(); }
				r.send('use_ajax=1' + query_string + '&switchto=' + switch_to);
			} catch(e) { alert(e.message); }
		}
	} catch(e) { alert(e.message); }
}

/**
 * Create the specified editor: bbcode or wysiwyg
 */
function createEditor() {
	if(r && editorcodex) {

		// if the request is still loading
		if(r.readyState == 1) {
			editorcodex.innerHTML = '';
			editorcodex.innerHTML = '<div style="padding: 30px;text-align: center;font-weight: bold;font-size:18px;font-family: Arial, Helvetica, serif;">Loading...</div>';
		}

		// if the request is complete
		if(r.readyState == 4) {
			
			// if the request status is good
			if(r.status == 200) {
				
				if(r.response != '' && r.response != null) { 

					// get an error string if any
					var errorstr = response_error(r.response);
					
					// if there was an error
					if(errorstr != '') {
						//editorcodex.innerHTML = '<div class="special_panel" style="text-align:center;">' + errorstr + '</div><br />' + editor_container.innerHTML;
					
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
						
						// if we have a response..
						if(editorcodex) {
							editorcodex.innerHTML = '';
							editorcodex.innerHTML = r.response; // set the editor's inner value to the response
						}
						
						// if we are switching to wysiwyg
						if(bbcode_buttons && switchto == 'wysiwyg') {
							enable_richtext('wysiwygcodex'); // enable richtext mode with the iframe
							populate_wysiwyg('messagecodex', 'wysiwygcodex'); // populate the iframe
							init_wysiwyg_menus(); // initialize the wysiwyg menus
						}
					}
				}
			} else {

				// abort the request
				r.close();
			}
		}
	}
}

/**
 * Functions to toggle the quick editing of topics/replies
 */
function quickEditPost(post_type, post_id, div_id) {
	
	// set the quick edit box
	var quickedit_div		= d.getElementById(div_id);
	
	// if we have a connection and the edit box
	if(r && quickedit_div) {
		// set the height of the quickedit box
		var quickedit_height	= d.height(quickedit_div);
		quickedit_height		= quickedit_height > 200 ? (quickedit_height > 400 ? 400 : quickedit_height) : 200;
		
		// send a request via the GET method
		r.open("GET", 'misc.php?act=revert_text&' + post_type + '=' + post_id + '&use_ajax=1', true);
		r.state_handler = function() { /*r.change_ready_state();*/ showQuickEditForm(quickedit_div, quickedit_height, post_type, post_id, false); }
		r.send(null);
	}
}

/* Function to show the quick edit form */
function showQuickEditForm(quickedit_div, quickedit_height, post_type, post_id, hide) {
	
	// if we have a connection and the edit box
	if(r && quickedit_div) {
		try {
			
			// if the request is still loading
			if(r.readyState == 1) {
				quickedit_div.innerHTML = '<div style="padding: 30px;text-align: center;font-weight: bold;font-size:18px;font-family: Arial, Helvetica, serif;">Loading...</div>';
			}

			// if the request is sending stuff back
			if(r.readyState == 4) {

				// if there are no errors
				if(r.status == 200) {
					
					// if the response is nothing
					if(r.response == null || r.response == '') {
						alert('Failed at rs (694)');
					} else {
						
						// see if there is a back-end error sent with this response
						var errorstr = response_error(r.response);
						
						// display an error
						if(errorstr != '') {
							quickedit_div.innerHTML = '<div class="special_panel" style="text-align:center;">' + errorstr + '</div><br />';
						
						// display the quick edit box
						} else {
							
							if(!hide) {
								// insert the textarea
								quickedit_div.innerHTML = '<textarea id="' + post_type + '' + post_id + '_message" rows="10" cols="100" style="width: 100%;height: ' + quickedit_height + 'px;" class="inputbox">' + r.response + '</textarea>';
							} else {
								quickedit_div.innerHTML = r.response;	
							}

							// try to get the button pallette for the quick edit
							var buttons = d.getElementById(post_type + '' + post_id + '_qebuttons');
							if(buttons) {
								buttons.style.display = (hide ? 'none' : 'block');
							}

							document.location = '#' + (post_type == 'topic' ? 't' : 'p') + post_id;
						}
					}
				} else {

					// abort the connection
					r.close();
				}
			}
		} catch(e) { alert(e.message); }
	} else {
		alert('Failed at rs (676)');
	}
}

/**
 * Function that returns the original post text when quick edit
 * has been cancelled
 */
function cancelQuickEdit(post_type, post_id, div_id) {
	
	// set the quick edit box
	var quickedit_div		= d.getElementById(div_id);
	
	try {

		// if we have a connection and the edit box
		if(r && quickedit_div) {
			try {
				
				// send a request via the GET method
				r.open("GET", 'misc.php?act=original_text&' + post_type + '=' + post_id + '&use_ajax=1', true);
				r.state_handler = function() { /*r.change_ready_state();*/ showQuickEditForm(quickedit_div, 0, post_type, post_id, true); }
				r.send(null);

			} catch(e) { alert(e.message); }
		} else {
			alert(e.message);
		}
	} catch(e) { alert(e.message); }
}

/**
 * Function that returns the new post text when quick edit
 * has been saved
 */
function saveQuickEdit(post_type, post_id, div_id) {
	// set the quick edit box
	var quickedit_div		= d.getElementById(div_id);
	
	try {

		// if we have a connection and the edit box
		if(r && quickedit_div) {
			try {
				
				var textarea		= d.getElementById(post_type + '' + post_id + '_message');
				var edited_msg		= textarea_value(textarea);

				// send a request via the GET method
				r.open("POST", 'misc.php?act=save_text&' + post_type + '=' + post_id + '&use_ajax=1', true);
				r.state_handler = function() { /*r.change_ready_state();*/ showQuickEditForm(quickedit_div, 0, post_type, post_id, true); }
				r.send('message=' + encodeURIComponent(edited_msg));

			} catch(e) { alert(e.message); }
		} else {
			alert(e.message);
		}
	} catch(e) { alert(e.message); }
}