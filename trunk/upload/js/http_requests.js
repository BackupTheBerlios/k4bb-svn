/**
 * k4 Bulletin Board, XMLHttpRequest-using/related functions
 * Copyright (c) 2005, Peter Goodman
 * Licensed under the LGPL license
 * http://www.gnu.org/copyleft/lesser.html
 * @author Peter Goodman
 * @version $Id$
 * @package k4bb
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

/**
 * escape a string
 */
function escape_str(str) {
	if(typeof(escape) != 'undefined') {
		str = escape(str);
	} else {
		if(typeof(encodeURIComponent) != 'undefined') {
			str = escape_str(str);
		}
	}

	return str;
}

/**
 * Function to get the value of a textarea
 */
function textarea_value(textarea) {
	var inner_value = '';
	if(typeof(textarea) != 'undefined' && textarea) {
		if(typeof(textarea.innerHTML) != 'undefined') {
			inner_value = textarea.innerHTML && textarea.innerHTML != '' ? textarea.innerHTML : '';
		}
		if(typeof(textarea.value) != 'undefined') {
			inner_value = textarea.value && textarea.value != '' ? textarea.value : inner_value;
		}
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
		topic_area	= FA.getObj(edit_mode);
		if( (e.clientX < FA.posLeft(topic_area) ||
			e.clientX > FA.posRight(topic_area) ||
			e.clientY < FA.posTop(topic_area) ||
			e.clientY > FA.posBottom(topic_area))
			&& FA.eventTarget(e).id != topic_area.id
			) {
			
			// update the topic name
			updateTopicTitle(edit_mode, edit_post_id, edit_topic_area);
		}
	}
}
var request_post_id = false;
function getTopicTitle(post_id) {
	var k4_http = FAHttpRequestsFactory.createInstance();
	k4_http.Request('GET', 'mod.php?act=get_topic_title&post_id=' + parseInt(post_id), false, false, (function(){topicMakeUpdatable(k4_http,post_id);}));
}
function topicMakeUpdatable(k4_http, post_id) {
	
	var topic_span	= FA.getObj('topicname_' + post_id);
	var topic_area	= FA.getObj('topicname_' + post_id + '_area');
	var response	= k4_http.getResponseText();
	
	if(topic_span && topic_area) {
		// add an input box into the topic box
		topic_span.innerHTML	= '<input type="text" name="name" id="topic' + post_id + '_name" value="' + response + '" class="alt1" style="padding: 1px;font-size: 11px;" />';

		// attach a function to monitor document clicks
		FA.attachEvent(document,'click',disable_edit_mode);

		// try to get the input and focus it
		var input = FA.getObj('topic' + post_id + '_name');
		if(input) {
			input.focus();
		}
	}
}
function updateTopicTitle(textbox_id, post_id, div_id) {
	var textbox		= FA.getObj(textbox_id);
	if(textbox) {
		var k4_http = FAHttpRequestsFactory.createInstance();
		k4_http.Request('POST', 'mod.php?act=topic_simpleupdate&use_xmlhttp=1&id=' + parseInt(post_id) + '&name=' + escape_str(textbox.value), false, false, (function(){topicSimpleUpdate(k4_http,div_id);}));
	}
}
function topicSimpleUpdate(k4_http, topic_area_id) {
	
	var topic_area	= FA.getObj(topic_area_id);
	
	if(topic_area) {
		var response = k4_http.getResponseText();
		
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
		var locked		= FA.sizeOf(icon.src.match(lock_regex)) > 0 ? true : false;
		
		var k4_http = FAHttpRequestsFactory.createInstance();
		k4_http.Request('GET', 'mod.php?act=' + (locked == 1 ? 'un' : '') + 'locktopic&id=' + parseInt(post_id) + '&use_xmlhttp=1', false, false, (function(){changeLockedIcon(k4_http, icon);}));

	} else {
		document.location.href = 'mod.php?act=' + (locked == 1 ? 'un' : '') + 'locktopic&id=' + parseInt(post_id);
	}
}
// change the topic icon for locked
function changeLockedIcon(k4_http, icon) {

	var response_text			= k4_http.getResponseText();
	
	if(response_text != '') {			
		var bad_match			= new RegExp("(announce|sticky|poll)", "i");
		var matches				= icon.src.match(bad_match);
		
		if(FA.sizeOf(matches) == 0) {
			
			var lock_regex		= new RegExp("_lock\.gif$", "i");
			var locked			= FA.sizeOf(icon.src.match(lock_regex)) > 0 ? true : false;

			if(locked) {
				new_src			= icon.src.replace(lock_regex, '.gif');
			} else {
				icon_regex		= new RegExp("\.gif$", "i");
				new_src			= icon.src.replace(icon_regex, '_lock.gif');
			}
			icon.src			= new_src;
		}
	}
}

/**
 * Quick Reply
 */
function saveQuickReply(form_id, textarea_id, post_id, forum_id, page) {
	var textarea	= FA.getObj(textarea_id);
	var form		= FA.getObj(form_id);
	var query_string = '&';
	if(textarea && form) {
		if(textarea_value(textarea) != '') {
			query_string += 'message=' + escape_str(textarea_value(textarea));
			
			var k4_http = FAHttpRequestsFactory.createInstance();
			k4_http.Request('POST', 'newreply.php?act=postreply&use_xmlhttp=1&row_type=8&topic_id=' + parseInt(post_id) + '&forum_id=' + parseInt(forum_id) + '&submit_type=post&page=' + parseInt(page) + query_string, (function(){FAHTTP.loadingState('preview_loader');}), false, (function(){findQuickReply(k4_http,textarea);}));
		}
	} else {
		if(form) { form.submit(); }
	}
}
function findQuickReply(k4_http, textarea) {
	
	FAHTTP.cancelLoader('preview_loader');

	var message_holder	= FA.getObj('quick_reply_sent');
	var container		= FA.getObj('quick_reply_content');
	var message			= FA.getObj('quick_reply_message');
	var preview			= FA.getObj('ajax_post_preview');
	
	if(message_holder && container && preview) {
		var response = k4_http.getResponseText();
		if(response != '') {
			
			var errorstr = responseError(response);
			message_holder.style.display = 'block';
			
			if(errorstr == '') {
				
				if(container) {
					container.style.display = 'block';
					container.innerHTML += response;
				}

				if(textarea) { textarea.value		= ''; }
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
	
	var fields	= ['username','password','password2','email','email2'];
	var form	= FA.getObj('register_form');
	if(form) {
		
		var query_string	= '';
		
		for(var i = 0; i < FA.sizeOf(fields); i++) {
			field = FA.getObj(fields[i]);
			if(field) {
				query_string += '&' + fields[i] + '=' + escape_str(field.value);
			}
		}

		var k4_http = FAHttpRequestsFactory.createInstance();
		k4_http.Request('POST', 'member.php?act=register_user&use_xmlhttp=1' + query_string, false, false, (function(){handleRegErrors(k4_http,button);}));
	}
}

/**
 * Handle any possible registration errors returned from the request
 */
function handleRegErrors(k4_http, button) {

	// get the message holder, message area and form
	var message_holder	= FA.getObj('message_holder');
	var message_area	= FA.getObj('form_error');
	var form			= FA.getObj('register_form');
	
	// if everything is a-okay
	if(form && message_holder && message_area) {
		var response = k4_http.getResponseText();
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
				FA.enableElm(button);

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
function setSendPostPreview(form_url, editor_id) {

	// try to get the form object
	var form = FA.getObj('savepost_form');

	// define several possible required fields to get values
	// from to pass to the request
	var fields = ['forum_id','post_id','parent_id','post_id','poster_name','name','posticon','to','cc'];
	
	// if everything is good sofar
	if(form) {
		
		// loop through the above defined fields
		var query_string = '&submit_type=preview';
		for(var i = 0; i < FA.sizeOf(fields); i++) {
			
			// if the field exists and had a value, add it to the query string
			field = FA.getObj(fields[i]);
			if(field && field.value != '') {
				query_string += '&' + fields[i] + '=' + escape_str(field.value);
			}
		}

		// get the textare field
		field	= FA.getObj(editor_id);
		
		// if the value of the textare is not null, add it to the query string
		if(field && textarea_value(field) != '') {
			query_string += '&message=' + escape_str(textarea_value(field));
		}
		
		var preview_holder	= FA.getObj('ajax_post_preview');
		var k4_http = FAHttpRequestsFactory.createInstance();
		k4_http.Request('POST', form_url + '&use_xmlhttp=1' + query_string, (function(){FAHTTP.loadingState('preview_loader');}), false, (function(){getPostPreview(k4_http);}));
	}
}

/**
 * Get the post preview and display it
 */
function getPostPreview(k4_http) {

	// try to get the form and preview holder
	var form			= FA.getObj('savepost_form');
	var preview_holder	= FA.getObj('ajax_post_preview');
	
	FAHTTP.cancelLoader('preview_loader');

	// if everything looks good
	if(preview_holder && form) {
		
		var response = k4_http.getResponseText();
				
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
switchto			= '';
function switch_editor_type(switch_to, curr_type, textarea_id, iframe_id) {
	
	switchto = switch_to;
	
	// get the textare which will hold values
	e_textarea = FA.getObj(textarea_id);

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
			query_string += '&message=' + escape_str(message_value);
		}
		
		// if we have the forum id and it's semi-valid
		if(forum_id && parseInt(forum_id) > 0) {
			query_string += '&forum_id=' + parseInt(forum_id);
		}
		
		var k4_http = FAHttpRequestsFactory.createInstance();
		k4_http.Request('POST', 'misc.php?act=switch_editor&switchto=' + switch_to + '&use_xmlhttp=1' + query_string, (function(){FAHTTP.loadingState('top_loader');}), false, (function(){createEditor(k4_http);}));
	}
}

/**
 * Create the specified editor: bbcode or wysiwyg
 */
function createEditor(k4_http) {
	if(editorcodex) {
		
		var response = k4_http.getResponseText();		
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
	var quickedit_div		= FA.getObj(div_id);
	
	// if we have a connection and the edit box
	if(quickedit_div) {
		
		// set the height of the quickedit box
		var quickedit_height	= quickedit_div.offsetHeight;
		quickedit_height		= quickedit_height > 200 ? (quickedit_height > 400 ? 400 : quickedit_height) : 200;
		
		// is there a quick edit already open?
		if(typeof(quick_edit) != 'undefined' && quick_edit) {
			if(quick_edit['id'] != post_id) {
				saveQuickEdit(quick_edit['id'], quick_edit['div']);
				quick_edit = false;
			}
			return true;
		}
		
		quick_edit = { 'id' : post_id, 'div' : div_id };
		
		var k4_http = FAHttpRequestsFactory.createInstance();
		k4_http.Request('GET', 'misc.php?act=revert_text&post_id=' + post_id + '&use_xmlhttp=1', (function(){FAHTTP.loadingState('p'+post_id+'_loader');}), false, (function(){showQuickEditForm(k4_http,quickedit_div,quickedit_height,post_id,false);}));
	}
}


/**
 * Function to show the quick edit form 
 */
function showQuickEditForm(k4_http, quickedit_div, quickedit_height, post_id, hide) {
	
	FAHTTP.cancelLoader('p'+post_id+'_loader');

	// if we have a connection and the edit box
	if(quickedit_div) {
		
		var response = k4_http.getResponseText();
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
				var buttons = FA.getObj(post_id + '_qebuttons');
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
	var quickedit_div		= FA.getObj(div_id);
	
	// if we have a connection and the edit box
	if(quickedit_div) {
		
		var k4_http = FAHttpRequestsFactory.createInstance();
		k4_http.Request('GET', 'misc.php?act=original_text&post_id=' + post_id + '&use_xmlhttp=1', (function(){FAHTTP.loadingState('p'+post_id+'_loader');}), false, (function(){showQuickEditForm(k4_http,quickedit_div,0,post_id,true);}));
	}
}

/**
 * Function that returns the new post text when quick edit
 * has been saved
 */
function saveQuickEdit(post_id, div_id) {
	// set the quick edit box
	var quickedit_div		= FA.getObj(div_id);
	
	// if we have a connection and the edit box
	if(quickedit_div) {
		var textarea		= FA.getObj(post_id + '_message');
		var edited_msg		= textarea_value(textarea);
		quick_edit			= false;
		
		var k4_http = FAHttpRequestsFactory.createInstance();
		k4_http.Request('POST', 'misc.php?act=save_text&post_id=' + post_id + '&use_xmlhttp=1&message=' + escape_str(edited_msg), (function(){FAHTTP.loadingState('p'+post_id+'_loader');}), false, (function(){showQuickEditForm(k4_http,quickedit_div,0,post_id,true);}));
	}
}

/**
 * Get and show the results from a quick search
 */
function showSearchResults(search_button) {	
	var form_obj			= search_button.parentNode;
	if(form_obj) {
		var inputs			= FA.tagsByName(form_obj, 'input');
		var query_str		= '';
		if(inputs) {
			for(var i = 0; i < FA.sizeOf(inputs); i++) {
				if(typeof(inputs[i].name) != 'undefined' && inputs[i].name != '') {
					query_str	+= '&' + inputs[i].name + '=' + escape_str(inputs[i].value);
				}
			}
		}
		var k4_http = FAHttpRequestsFactory.createInstance();
		k4_http.Request('POST', 'search.php?act=find' + query_str + '&use_xmlhttp=1', false, false, (function(){showSimpleSearchResults(k4_http,'search.php?act=find'+query_str);}));
	}
}
function showSimpleSearchResults(k4_http, search_url) {
	
	var forum_head_obj = FA.getObj('forum_head');
	
	if(forum_head_obj && k4_http) {
		
		var response = k4_http.getResponseText();
		
		if(response && response != '') {
			
			var simple_search_results_box	= FA.getObj('simple_search_results_box');
			var simple_search_results		= FA.getObj('simple_search_results');
			var search_results_link			= FA.getObj('search_results_link');
			
			if(simple_search_results_box && simple_search_results && search_results_link) {
				//forum_head_obj.appendChild(simple_search_results);
				
				if(simple_search_results_box.style.display == 'block') {
					simple_search_results_box.style.height = '';
				}

				var errorstr = responseError(response);
				search_results_link.href = search_url;
				if(errorstr == '') {
					simple_search_results.innerHTML = response;
				} else {
					simple_search_results.innerHTML = '<div class="base3" style="text-align:center;">' + errorstr + '</div>';
				}
				k4SlideResizerFactory.createInstance().Init('simple_search_results_box', 0, 1, 10);
			}
		}
	}
}
function closeSimpleSearchBox() {
	var simple_search_results_box	= FA.getObj('simple_search_results_box');
	var simple_search_results		= FA.getObj('simple_search_results');
	if(simple_search_results_box && simple_search_results) {
		var effect = k4SlideResizerFactory.createInstance();
		effect.Init('simple_search_results_box', 0, 2, 10);
		effect.onFinished = function() { 
			simple_search_results.innerHTML = ''; 
			simple_search_results_box.style.height = '';
		};
	}
}