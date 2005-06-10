/**
* k4 Bulletin Board, javascript.js
* Main Javascript Functions
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
* @version $Id: javascript.js,v 1.7 2005/05/24 20:07:17 k4st Exp $
* @package k42
*/

// Check for Browser & Platform for PC & IE specific bits
// More details from: http://www.mozilla.org/docs/web-developer/sniffer/browser_type.html
var clientPC = navigator.userAgent.toLowerCase(); // Get client info
var clientVer = parseInt(navigator.appVersion); // Get browser version

var is_ie = ((clientPC.indexOf("msie") != -1) && (clientPC.indexOf("opera") == -1));
var is_nav = ((clientPC.indexOf('mozilla')!=-1) && (clientPC.indexOf('spoofer')==-1)
                && (clientPC.indexOf('compatible') == -1) && (clientPC.indexOf('opera')==-1)
                && (clientPC.indexOf('webtv')==-1) && (clientPC.indexOf('hotjava')==-1));

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
 * count()/sizeof() like function for an array
 */
function sizeof(thearray) {
	for (i = 0; i < thearray.length; i++) {
		if ((thearray[i] == "undefined") || (thearray[i] == "") || (thearray[i] == null)) {
			return i;
		}
	}
	return thearray.length;
}

/**
 * Array Push function 
 */
function array_push(thearray, value) {
	thearray[sizeof(thearray)] = value;
}

/**
 * Array unset function for a given value 
 */
function array_unset(thearray, value) {
	for(var i = 0; i < sizeof(thearray); i++) {
		if(thearray[i] == value) {
			delete thearray[i];
		}
	}

	return true;
}

/**
 * In array type function
 */
function in_array(thearray, needle) {
	var bool = false;
	for (var i=0; i < sizeof(thearray); i++) {
		if (thearray[i] == needle) {
			bool = true;
		}
	}
	return bool;
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
function select_topic(topic_id, button_id, input_id, savetopic_id) {
	if(topics) {
		var button		= document.getElementById(button_id);
		var savetopic	= document.getElementById(savetopic_id);

		if(button && savetopic) {
			try {
				if(in_array(topics, topic_id)) {
					array_unset(topics, topic_id);
					button_regex		= new RegExp("selected\\.gif$");
					button.src			= button.src.replace(button_regex, 'unselected.gif');
					savetopic.style.display = 'none';
				} else {
					array_push(topics, topic_id);
					button_regex		= new RegExp("unselected\\.gif$");
					button.src			= button.src.replace(button_regex, 'selected.gif');
				}
				collect_topics(input_id);
			} catch(e) {
				alert(e.message);
			}
		}
	}
}
function collect_topics(id) {
	
	var str		= ''
	var input	= document.getElementById(id);
	
	if(topics && input) {
		for(var i = 0; i < sizeof(topics); i++) {
			if(topics[i] && topics[i] != '' && topics[i] != 0) {
				str	+= (i == 0) ? topics[i] : '|' + topics[i];
			}
		}
		
		input.value = str;
	}	
}
function adv_edit(edit_id, topic_url, checkbox_id, submit_button, topics_list) {
	var topic_name	= document.getElementById(edit_id);
	var checkbox	= document.getElementById(checkbox_id);
	var s_button	= document.getElementById(submit_button);
	var topics		= document.getElementById(topics_list);
	
	if(topics && checkbox) {
		topics		= topics.value.split("|");
		id			= edit_id.substring(13)
		
		if(in_array(id, topics)) {
			button_regex		= new RegExp("unselected\\.gif$");
			checkbox.src		= checkbox.src.replace(button_regex, 'selected.gif');
		}
	}

	if(topic_name && checkbox) {
		
		try {
			topic_name.onfocus = function() {
				
				checkbox_regex	= new RegExp("unselected\\.gif$");
				edit			= checkbox.src.match(checkbox_regex);
				
				if(edit) {
					this.blur();
				} else {
					s_button.style.display = 'block';
				}
			}

			topic_name.onclick = function() {

				checkbox_regex	= new RegExp("unselected\\.gif$");
				edit			= checkbox.src.match(checkbox_regex);
				
				if(edit) {
					document.location = topic_url;
				} else {
					s_button.style.display = 'block';
				}
			}
		
			topic_name.onmouseover = function() {
				
				checkbox_regex	= new RegExp("unselected\\.gif$");
				edit			= checkbox.src.match(checkbox_regex);

				if(edit) {
					try { topic_name.style.cursor = 'pointer'; } catch(e) { topic_name.style.cursor = 'hand'; }
				} else {
					topic_name.style.cursor = 'text';
				}
			}

		} catch(e) {
			alert(e.message);
		}
	}
}

/* Function to jump from one forum to another */
function jump_to(select_id) {
	var select			= document.getElementById(select_id);
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
	var ruler					= document.getElementById(ruler_id);
	
	if(ruler) {
		var divs				= document.getElementsByTagName('div');
		for(var i = 0; i < sizeof(divs); i++) {
			if(divs[i] && divs[i].className) {
				if(divs[i].className == 'bbcode_img') {

					bbcodeimages	= divs[i].getElementsByTagName('img');

					if(sizeof(bbcodeimages) > 0) {
						
						divs[i].align	= 'center';

						divs[i].onclick = function() {
							return document.location = bbcodeimages[0].src;
						}

						try {
							divs[i].style.cursor = 'pointer';
						} catch(e) {
							divs[i].style.cursor = 'hand';
						}

						if(divs[i].offsetWidth > ruler.offsetWidth) {

							/* Scale the image accordingly */
							bbcodeimages[0].width.value = (ruler.offsetWidth - 200);
							bbcodeimages[0].height = ((ruler.offsetWidth - 200) / divs[i].offsetWidth) * divs[i].offsetHeight;
						
					
						}
					}
				}
			}
		}
	}
}

var collapsed_items = new Array()

function switch_button(open, button) {
	if(open) {
		if(button.src) {
			button_regex		= new RegExp("_collapsed\\.gif$");
			button.src			= button.src.replace(button_regex, '.gif');
		}
	} else {
		if(button.src) {
			button_regex		= new RegExp("\\.gif$");
			button.src			= button.src.replace(button_regex, '_collapsed.gif');
		}
	}
	button.style.display	= 'block';
}

function collapse_tbody(buttonId, Id) {
	var tbody	= document.getElementById(Id);
	var button	= document.getElementById(buttonId);
	
	try {
		if(tbody) {
			if(tbody.style.display == 'none') {
				switch_button(true, button);
				tbody.style.display = '';
			} else {
				switch_button(false, button);
				tbody.style.display = 'none';
			}
		}
	} catch(e) {
		alert(e.message);
	}
}

/* Show or Hide an html element */
function ShowHide(Id) {
	var obj = document.getElementById(Id);
	
	if(obj) {
		if(obj.style.display == 'none') {
			obj.style.display = 'block';
		} else {
			obj.style.display = 'none';
		}
	}
}

/* Set the index on a select form field */
function setIndex(element, array) {
	var temp = document.getElementById(array);
	temp.selectedIndex = getSelectedIndex(element, temp);
}

/* Set the indices on a multi-select select field */
function setIndices(values_array, select) {
	var temp = document.getElementById(select);
	
	if(sizeof(values_array) > 1) {
		for(var i = 0; i < sizeof(temp.options); i++) {
			if(in_array(values_array, temp.options[i].value)) {
				temp.options[i].selected = true;
			}
		}
	} else {
		setIndex(values_array[0], select);
	}
}

/* Set a radio button */
function setRadio(value, name) {
	var inputs		= document.getElementsByTagName("input");
	
	for (var x = 0; x < sizeof(inputs); x++) {
		if(inputs[x].name == name) {
			if(inputs[x].value == value) {
				inputs[x].checked = true;
			} else {
				inputs[x].checked = false;
			}
		}
	}

	return true;
}

/* Set a checkbox */
function setCheckbox(value, id) {
	var input		= document.getElementById(id);
	if(value || value == 1) {	
		input.checked = true;
	} else {
		input.checked = false;
	}
}

/* Get the positiong of an element in an array */
function getSelectedIndex(element, array) {
	var pos = '';
	for(var i = 0; i < sizeof(array); i++) {
		if(array[i].value == element) {
			pos = i;
		}
	}
	return pos;
}

var ns6			= document.getElementById && !document.all;

function restrictinput(maxlength, e, placeholder){
	if (window.event && event.srcElement.value.length >= maxlength) {
		return false
	} else if (e.target && e.target == eval(placeholder) && e.target.value.length >= maxlength) {
		var pressedkey = /[a-zA-Z0-9\.\,\/]/ //detect alphanumeric keys
		if (pressedkey.test(String.fromCharCode(e.which))) {
			e.stopPropagation();
		}
	}
}

function countlimit(maxlength, e, placeholder){
	var theform						= eval(placeholder)
	var lengthleft					= maxlength-theform.value.length
	var placeholderobj				= document.all? document.all[placeholder] : document.getElementById(placeholder);
	if (window.event || e.target&&e.target == eval(placeholder)) {
		if (lengthleft < 0) {
			theform.value			= theform.value.substring(0,maxlength);
		}
		placeholderobj.innerHTML	= lengthleft;
	}
}


function displaylimit(theform, thelimit){
	var limit_text = '<b><span id="' + theform.toString() + '">' + thelimit + '</span></b>';
	if (document.all||ns6) {
		document.write(limit_text)
	}
	if (document.all){
		eval(theform).onkeypress = function(){ 
			return restrictinput(thelimit,event,theform);
		}
		eval(theform).onkeyup = function() { 
			countlimit(thelimit,event,theform);
		}
	} else if (ns6){
		document.body.addEventListener('keypress', function(event) { restrictinput(thelimit,event,theform) }, true); 
		document.body.addEventListener('keyup', function(event) { countlimit(thelimit,event,theform) }, true); 
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
function set_cookie(name, value, seconds) {
	var expires = new Date ();
	fix_cookie_date(expires);
	expires.setTime (expires.getTime() + (seconds * 1000));
	document.cookie = name + "=" + escape(value) + "; expires=" + expires +  "; path=/";
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
	return null;
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
