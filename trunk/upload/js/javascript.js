/**
* k4 Bulletin Board, javascript.js
* Main Javascript Functions
* Copyright (c) 2005, Peter Goodman
* Licensed under the LGPL license
* http://www.gnu.org/copyleft/lesser.html
* @author Peter Goodman
* @version $Id: javascript.js 144 2005-07-05 02:29:07Z Peter Goodman $
* @package k4bb
*/

var d			= new k4lib();

/**
 * Do an emoticon
 */
function do_emoticon(textarea_id, emo_image, emo_typed) {
	var textarea_obj = FA.getObj(textarea_id);
	if(textarea_obj && typeof(textarea_obj) != 'undefined') {
		
		if(typeof(textarea_obj.style.display) == 'undefined' 
			|| (typeof(textarea_obj.style.display) != 'undefined' 
			&& (
				textarea_obj.style.display == '' 
				|| textarea_obj.style.display == 'block'
			))) {
			textarea_obj.value += ' ' + emo_typed + ' ';
		} else {
			var editor_id = textarea_id + '_k4rte';
			var editor_obj = FA.getObj(editor_id);
			if(typeof(editor_obj) != 'undefined') {
				
				try {
					var rte = k4RTEFactory.createInstance(false, false);
					var editor_dom = rte.get_object_document(editor_obj);
					editor_dom.execCommand('InsertImage', false, FORUM_URL + '/tmp/upload/emoticons/' + emo_image);
				} catch(e) { }
			}
		}
	}
}

/**
 * Show a div when there are new private messages
 */
function show_newmessage_box(num_messages, text_to_write, img_dir, go_in_id) {
	var go_in = FA.getObj(go_in_id);
	
	if(go_in && parseInt(num_messages) > 0) {
		
		document.writeln('<div id="new_pms_box" class="base2" style="z-index: 99;border: 0px;position: absolute;top: ' + d.top(go_in) + 'px;padding: 5px;"><span class="smalltext"><a href="member.php?act=usercp&amp;view=pmfolder&amp;folder=1" title=""><img src="Images/' + img_dir + '/Icons/icon_latest_reply.gif" alt="" border="0" />&nbsp;<strong>' + parseInt(num_messages) + '</strong> ' + text_to_write + '</a></span></div>');
		var new_pms_box = FA.getObj('new_pms_box');
		
		if(new_pms_box) {
			new_pms_box.style.left = parseInt(FA.posRight(go_in) - new_pms_box.offsetWidth - 1) + 'px';
		}
	}
}

/**
 * Show / Hide a table or other block-level element
 */
function showTable(table_id) {
	var the_tr = FA.getObj(table_id);
	if(the_tr.style.display == 'block') {
		FA.show(the_tr);
	} else {
		FA.hide(the_tr);
	}
	return true;
}

/**
 * Change the posticon beside the post title when a new one is set
 */
function swap_posticon(imgid) {
	var out = FA.getObj("display_posticon");
	var img	= FA.getObj(imgid);
	if (img && out) {
		out.src = img.src;
		out.alt = img.alt;
	} else {
		out.src = "tmp/upload/posticons/clear.gif";
		out.alt = "";
	}
}

/**
 * Change the submit type for topic/reply posting
 */
function change_submit_type(input_id, submit_type) {
	var input = FA.getObj(input_id);
	if(input) input.value = submit_type;
}

/**
 * Alternative to <meta http-equiv="refresh" content="*; url=*">
 */
function redirect_page(seconds, url) {
	setTimeout("document_location('" + url + "')", (seconds * 1000));
}
function document_location(url) {
	return document.location = url;
}

/**
 * iif(), like mIRC script 
 */
function iif(condition, trueval, falseval) {
	if(condition) {
		return trueval;
	} else {
		return falseval;
	}
}

/**
 * Select topic(s) for moderation
 */
var topics	= new Array()
function select_topic(span_object, button_id, post_id) {

	try {
		if(span_object) {

			var button		= FA.getObj(button_id);
			var checkbox	= FA.tagsByName(span_object, 'input');
			button_regex	= new RegExp("\(([0-9]+?)\)", "g");
			
			if(button && checkbox) {
				
				match			= button.value.match(button_regex);
				
				try {
					if(match) {
						
						if(checkbox[0].checked == true) {
							
							var new_value = parseInt(parseInt(match[0])+1) < 0 ? 0 : parseInt(parseInt(match[0])+1);
							
							d.array_push(topics, post_id);
							button.value = button.value.replace(match[0], new_value);

						} else {
							
							var new_value = parseInt(parseInt(match[0])-1) < 0 ? 0 : parseInt(parseInt(match[0])-1);
							
							d.unset(topics, post_id);
							button.value = button.value.replace(match[0], new_value);
						}

						collect_topics('topics');
					}
				} catch(e) { alert(e.message); }
			}
		}
	} catch(e) { alert(e.message); }
}

function collect_topics(id) {
	
	var str		= ''
	var input	= FA.getObj(id);
	
	if(topics && input) {
		for(var i = 0; i < FA.sizeOf(topics); i++) {
			if(topics[i] && topics[i] != '' && topics[i] != 0) {
				str	+= (i == 0) ? topics[i] : '|' + topics[i];
			}
		}
		
		input.value = str;
	}	
}

/**
 * Function to jump from one forum to another 
 */
function jump_to(select_id) {
	var select			= FA.getObj(select_id);
	if(select) {
		if(select.selectedIndex) {
			if(select[select.selectedIndex].value != '-1') {
				document.location = select[select.selectedIndex].value;
			} else {
				return;
			}
		}
	}
}

var collapsed_items				= [];

function switch_button(open, button) {
	if(open) {
		if(typeof(button.src) != 'undefined' && button.src) {
			button_regex		= new RegExp("_collapsed\.gif$");
			button.src			= button.src.replace(button_regex, '.gif');
		}
	} else {
		if(button.src) {
			button_regex		= new RegExp("\.gif$");
			button.src			= button.src.replace(button_regex, '_collapsed.gif');
		}
	}
	button.style.display		= 'block';
}

function collapse_tbody(button_id, element_id) {
	var element		= FA.getObj(element_id);
	var button		= FA.getObj(button_id);
	
	if(element) {
		if(element.style.display == 'none') {
			element.style.display = '';
			switch_button(true, button);
		} else {
			switch_button(false, button);
			element.style.display = 'none';
		}
	}
}

/* Show or Hide an html element */
function ShowHide(Id) {
	var obj = FA.getObj(Id);
	
	if(obj) {
		if(obj.style.display == 'none') {
			obj.style.display = 'block';
		} else {
			obj.style.display = 'none';
		}
	}
}

/* Popup the a file, in this case, the files.php (set elsewhere) */
function popup_upload(file) {
	day = new Date();
	id = day.getTime();
	eval("page" + id + " = window.open('" + file + "', '" + id + "', 'toolbar=0,scrollbars=0,location=0,statusbar=0,menubar=0,resizable=0,width=300,height=100,left = 462,top = 334');");
}

function popup_file(file, width, height) {
	day = new Date();
	id = day.getTime();
	eval("page" + id + " = window.open('" + file + "', '" + id + "', 'toolbar=0,scrollbars=0,location=0,statusbar=0,menubar=0,resizable=0,width=" + width + ",height=" + height + ",left = 462,top = 334');");
}

function fix_cookie_date(date) {
  var base = new Date(0);
  var skew = base.getTime(); // dawn of (Unix) time - should be 0
  if (skew > 0)  // Except on the Mac - ahead of its time
    date.setTime (date.getTime() - skew);
}

/* Set a cookie */
function set_cookie(name, value, seconds, k4_domain) {
	var expires = new Date();
	fix_cookie_date(expires);
	expires.setTime (expires.getTime() + (seconds * 1000));
	document.cookie = name + "=" + escape(value) + "; expires=" + expires +  "; path=" + k4_domain + ";";

}

/* Fetch a cookie */
// Get the value of a cookie based on its name, this get_cookie function is from Phrogz.net, 
// same guy who made the AttachEvent function, thanks man!
function fetch_cookie(cookieName){
	var cookies=document.cookie+"";
	if (!cookies) return null;
	cookies=cookies.split(/; */);
	for (var i=0,len=cookies.length;i<len;i++){
		var keyVal = cookies[i].split("=");
		if (unescape(keyVal[0])==cookieName) return unescape(keyVal[1]);
	}
	return false;
}
/* Delete a cookie */
function delete_cookie(name)
{
	var expireNow = new Date();
	document.cookie = name + "=" + "; expires=Thu, 01-Jan-70 00:00:01 GMT" +  "; path=/";
}