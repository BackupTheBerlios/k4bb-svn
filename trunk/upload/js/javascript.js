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
	var textarea_obj = d.getElementById(textarea_id);
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
			var editor_obj = d.getElementById(editor_id);
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
	var go_in = d.getElementById(go_in_id);
	
	if(go_in && parseInt(num_messages) > 0) {
		
		document.writeln('<div id="new_pms_box" class="base2" style="z-index: 99;border: 0px;position: absolute;top: ' + d.top(go_in) + 'px;padding: 5px;"><span class="smalltext"><a href="member.php?act=usercp&amp;view=pmfolder&amp;folder=1" title=""><img src="Images/' + img_dir + '/Icons/icon_latest_reply.gif" alt="" border="0" />&nbsp;<strong>' + parseInt(num_messages) + '</strong> ' + text_to_write + '</a></span></div>');
		var new_pms_box = d.getElementById('new_pms_box');
		
		if(new_pms_box) {
			new_pms_box.style.left = parseInt(d.right(go_in) - d.width(new_pms_box) - 1) + 'px';
		}
	}
}

/**
 * Show / Hide a table or other block-level element
 */
function showTable(table_id) {
	var the_tr = d.getElementById(table_id);
	if(the_tr.style.display == 'block') {
		return the_tr.style.display = 'none';
	} else {
		return the_tr.style.display = 'block';
	}
}

/**
 * Change the posticon beside the post title when a new one is set
 */
function swap_posticon(imgid) {
	var out = d.getElementById("display_posticon");
	var img	= d.getElementById(imgid);
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
	var input = d.getElementById(input_id);
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

			var button		= button_id.obj();
			var checkbox	= span_object.getTagsByName('input');
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
	var input	= d.getElementById(id);
	
	if(topics && input) {
		for(var i = 0; i < d.sizeof(topics); i++) {
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
	var select			= d.getElementById(select_id);
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

/* Resize images imported using bbcode */
function resize_bbimgs(ruler_id) {
	var ruler					= d.getElementById(ruler_id);
	
	if(ruler) {
		var divs				= d.getElementsByTagName(document, 'div');
		
		for(var i = 0; i < d.sizeof(divs); i++) {			if(divs[i] && divs[i].className) {
				if(divs[i].className == 'bbcode_img') {

					bbcodeimages	= d.getElementsByTagName(divs[i], 'img');

					if(d.sizeof(bbcodeimages) > 0) {
						
						divs[i].align	= 'center';

						divs[i].onclick = function() {
							return document.location = bbcodeimages[0].src;
						}
						
						d.forceCursor(divs[i]);

						if(d.width(divs[i]) > d.width(ruler)) {

							/* Scale the image accordingly */
							bbcodeimages[0].width.value = (d.width(ruler) - 200);
							bbcodeimages[0].height		= ((d.width(ruler) - 200) / d.width(divs[i])) * d.height(divs[i]);
					
						}
					}
				}
			}
		}
	}
}

var collapsed_items				= new Array()

function switch_button(open, button) {
	if(open) {
		if(button.src) {
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
	var element		= d.getElementById(element_id);
	var button		= d.getElementById(button_id);
	
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
	var obj = d.getElementById(Id);
	
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
// Get the value of a cookie based on its name, this get_cookie function is from Phrogz.net, same guy who made the AttachEvent function, thatnks man!
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


//*** The following is copyright 2003 by Gavin Kistner, gavin@refinery.com
//*** It is covered under the license viewable at http://phrogz.net/JS/_ReuseLicense.txt
//*** Reuse or modification is free provided you abide by the terms of that license.
//*** (Including the first two lines above in your source code satisfies the conditions.)


//***Cross browser attach event function. For 'evt' pass a string value with the leading "on" omitted
//***e.g. AttachEvent(window,'load',MyFunctionNameWithoutParenthesis,false);

function AttachEvent(obj,evt,fnc,useCapture){
	if (!useCapture) useCapture=false;
	if (obj.addEventListener){
		obj.addEventListener(evt,fnc,useCapture);
		return true;
	} else if (obj.attachEvent) return obj.attachEvent("on"+evt,fnc);
	else{
		MyAttachEvent(obj,evt,fnc);
		obj['on'+evt]=function(){ MyFireEvent(obj,evt) };
	}
} 

//The following are for browsers like NS4 or IE5Mac which don't support either
//attachEvent or addEventListener
function MyAttachEvent(obj,evt,fnc){
	if (!obj.myEvents) obj.myEvents={};
	if (!obj.myEvents[evt]) obj.myEvents[evt]=[];
	var evts = obj.myEvents[evt];
	evts[evts.length]=fnc;
}
function MyFireEvent(obj,evt){
	if (!obj || !obj.myEvents || !obj.myEvents[evt]) return;
	var evts = obj.myEvents[evt];
	for (var i=0,len=evts.length;i<len;i++) evts[i]();
}
/* End of the code that Gavin Kistner made */
