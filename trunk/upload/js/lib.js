/**
* k4 Bulletin Board, lib.js
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

function k4lib() {
	
	/**
	 * Get browser information
	 */
	this.userAgent = navigator.userAgent.toLowerCase();
	this.is_opera  = (this.userAgent.indexOf('opera') != -1);
	this.is_saf    = ((this.userAgent.indexOf('applewebkit') != -1) || (navigator.vendor == 'Apple Computer, Inc.'));
	this.is_webtv  = (this.userAgent.indexOf('webtv') != -1);
	this.is_ie     = ((this.userAgent.indexOf('msie') != -1) && (!this.is_opera) && (!this.is_saf) && (!this.is_webtv));
	this.is_ie4    = ((this.is_ie) && (this.userAgent.indexOf('msie 4.') != -1));
	this.is_ie5    = ((this.is_ie) && (this.userAgent.indexOf('msie 5') != -1));
	this.is_moz    = ((navigator.product == 'Gecko') && (!this.is_saf));
	this.is_kon    = (this.userAgent.indexOf('konqueror') != -1);
	this.is_ns     = ((this.userAgent.indexOf('compatible') == -1) && (this.userAgent.indexOf('mozilla') != -1) && (!this.is_opera) && (!this.is_webtv) && (!this.is_saf));
	this.is_ns6    = ((this.is_ns) && (parseInt(navigator.appVersion) == 6));
	this.is_mac    = (this.userAgent.indexOf('mac') != -1);
}	

/**
 * Get an object by its ID 
 */
k4lib.prototype.getElementById			= function(id) {
	
	if (document.getElementById) {
		return document.getElementById(id);
	} else if (document.all) {
		return document.all[id];
	} else if (document.layers) {
		return document.layers[id];
	} else {
		return false;
	}
}

/**
 * Get an object by its tag name 
 */
k4lib.prototype.getElementsByTagName	= function(parentobj, tagname) {
	
	var elements			= false;

	if (typeof parentobj.getElementsByTagName != 'undefined') {
		elements			= parentobj.getElementsByTagName(tagname);
	} else if (parentobj.all && parentobj.all.tags) {
		elements			= parentobj.all.tags(tagname);
	}

	return elements;
}

/**
 * Array functions
 */

/**
 * Boolean true or false if a value is in an array
 */
k4lib.prototype.in_array				= function(thearray, needle) {
	var bool				= false;
	for(key in thearray) {
		if(bool)
			break;
		if(thearray[key] == needle) {
			bool = true;
		}
	}
	return bool;
}

/**
 * Return the array key of a value
 */
k4lib.prototype.array_key				= function(thearray, needle) {
	var the_key				= false;
	for(key in thearray) {
		if(the_key)
			break;
		if(key == needle) {
			the_key = key;
		}
	}
	return the_key;
}

/**
 * Array unset function for a given value 
 */
k4lib.prototype.unset					= function(thearray, value) {
	
	for(var i = 0; i < this.sizeof(thearray); i++) {
		if(thearray[i] == value) {
			delete thearray[i];
		}
	}

	return true;
}

/**
 * Array Push function 
 */
k4lib.prototype.array_push					= function(thearray, value) {
	thearray[this.sizeof(thearray)] = value;
}

/**
 * count()/sizeof() like function for an array 
 */
k4lib.prototype.sizeof					= function(thearray) {
	
	array_length		= 0;

	if(thearray != null && typeof thearray != 'undefined') {

		for (i = 0; i < thearray.length; i++) {
			if ((typeof thearray[i] == 'undefined') || (thearray[i] == '') || (thearray[i] == null)) {
				return i;
			}
		}
	
		array_length	= thearray.length;
	} else {
		array_length	= 0;
	}

	return array_length;
}
	

/**
 * Form functions
 */

/* Set the index on a select form field */
k4lib.prototype.setIndex				= function(element, array) {
	var temp				= this.getElementById(array);

	if(temp) {
		temp.selectedIndex	= this.getSelectedIndex(element, temp);
	}
}

/* Set the index on a select form field and if the index doesn't exist,
 * set the first option to be it. */
k4lib.prototype.forceSetIndex			= function(element, array) {
	var temp				= this.getElementById(array);

	if(temp) {
		temp.selectedIndex	= this.getSelectedIndex(element, temp);

		if(temp.selectedIndex == 0) {
			temp[0].value	= element;
			temp[0].text	= element;
		}
		temp.disabled		= false;
	}
}

/* Set the indices on a multi-select select field */
k4lib.prototype.setIndices				= function(values_array, select) {
	var temp				= this.getElementById(select);
	
	if(temp) {
		if(this.sizeof(values_array) > 0) {
			for(var i = 0; i < this.sizeof(temp.options); i++) {
				if(this.in_array(values_array, temp.options[i].value)) {
					temp.options[i].selected = true;
				}
			}
		}
	}
}

/* set all selected items in a <select> field to false */
k4lib.prototype.selectNone				= function(select) {
	var temp				= this.getElementById(select);
	
	if(temp) {
		for(var i = 0; i < this.sizeof(temp.options); i++) {
			if(temp.options[i].selected == true) {
				temp.options[i].selected = false;
			}
		}
	}			
}

/* set all selected items in a <select> field to true */
k4lib.prototype.selectAll				= function(select) {
	var temp				= this.getElementById(select);
	
	if(temp) {
		for(var i = 0; i < this.sizeof(temp.options); i++) {
			temp.options[i].selected = true;
		}
	}			
}

/* Set a text box */
k4lib.prototype.setText		= function(text, textbox) {
	var temp				= this.getElementById(textbox);

	if(temp) {
		temp.value			= text;
		temp.disabled		= false;
	}
}


/* Set a radio button */
k4lib.prototype.setRadio				= function(value, name) {
	var inputs				= this.getElementsByTagName(document, 'input');
	
	if(inputs) {
		for (var x = 0; x < this.sizeof(inputs); x++) {
			
			if(inputs[x]) {
			
				if(inputs[x].name == name) {
					if(inputs[x].value == value) {
						inputs[x].checked = true;
					} else {
						inputs[x].checked = false;
					}
				}
			}
		}
	}

	return true;
}

/* Set a checkbox */
k4lib.prototype.setCheckbox			= function(value, id) {
	var input				= this.getElementById(id);
	var check				= false;

	if(input) {
		check = (value || value > 0) ? true : false;	
	}

	input.checked			= check;
}

/* Get the positiong of an element in an array */
k4lib.prototype.getSelectedIndex		= function(element, array) {
	var pos					= 0;
	
	if(array) {

		for(var i = 0; i < this.sizeof(array); i++) {
			if(array[i].value == element) {
				pos			= i;
			}
		}
	}
	return pos;
}

/* Enable a form button */
k4lib.prototype.enableButton = function(button) {
	if(button) {
		button.disabled = false;
	}
}

/* Disable a form button */
k4lib.prototype.disableButton = function(button) {
	if(button) {
		button.disabled = true;
	}
}

/**
 * Position functions
 */

/* Get the top position of an object */
k4lib.prototype.top				= function(obj) {
	
	var postop	= 0;
	
	while (obj && obj != null){
		postop	+= obj.offsetTop; //  - obj.scrollTop
		obj		= obj.offsetParent;
	}

	return postop;
}

/* get the left position of an object */
k4lib.prototype.left				= function(obj) {
	
	var posleft			= 0;

	if(obj) {
	
		posleft			= obj.offsetLeft;
		while((obj = obj.offsetParent) != null) {
			
			posleft		+= obj.offsetLeft;
		}
	}

	return posleft;
}

/* get the bottom position of an object */
k4lib.prototype.bottom				= function(obj) {
	return (this.top(obj) + this.height(obj));
}

/* get the right position of an object */
k4lib.prototype.right				= function(obj) {
	return (this.left(obj) + this.width(obj));
}
	
/* Get the width of an object */
k4lib.prototype.width				= function(obj) {
	
	var objwidth		= 0;

	if(obj) {
		objwidth			= obj.offsetWidth;
	}

	return objwidth;
}

/* get the height of an object */
k4lib.prototype.height				= function(obj) {
	
	var objheight		= 0;
	
	if(obj) {
		objheight			= obj.offsetHeight;
	}

	return objheight;
}
		
/* Check if 'over' overlaps 'under' */
k4lib.prototype.overlaps			= function(over, under) {

	var does_overlap	= true;
	
	if(this.left(under) > this.right(over)) does_overlap = false;
	if(this.right(under) < this.left(over)) does_overlap = false;
	if(this.top(under) > this.bottom(over)) does_overlap = false;
	if(this.bottom(under) < this.top(over)) does_overlap = false;
	
	return does_overlap;
}

/**
 * Aesthetics
 */

/* Make the object's cursor look like a link */
k4lib.prototype.forceCursor		= function(obj) {
	
	if(obj) {
		try {
			obj.style.cursor = 'pointer';
		} catch(e) {
			obj.style.cursor = 'hand';
		}
	}
}

/* Preload Images */
k4lib.prototype.preload_images	= function() {
	if(document.images){ 
	
		if(!document.preloaded_images) { 
			document.preloaded_images = new Array();
		}
		
		var j 			= this.sizeof(document.preloaded_images);
		var func_args 	= this.preload_images.arguments; 
		
		for(var i = 0; i < this.sizeof(func_args); i++) {

			if (func_args[i].indexOf('#') != 0) { 
				document.preloaded_images[j]		= new Image();
				document.preloaded_images[j++].src	= func_args[i];
			}
		}
	}
}

/* Get the event target, function from QuirksMode */
k4lib.prototype.get_event_target = function(e) {
	var targ;
	if (!e) var e = window.event;
	if (e.target) targ = e.target;
	else if (e.srcElement) targ = e.srcElement;
	if (targ.nodeType == 3) // defeat Safari bug
		targ = targ.parentNode;

	return targ;
}

/**
 * k4BB XMLHttpRequest Class and related functions
 * @author Peter Goodman
 */
function k4XMLHttpRequest() {
	
	// public functions
	this.InitRequest	= InitRequest;
	this.Open			= Open;
	this.Close			= Close;
	this.Send			= Send;
	this.setRequestType = setRequestType;
	this.getResponseText= getResponseText;
	this.getResponseXML	= getResponseXML;
	this.getReadyState	= getReadyState;
	this.getStatus		= getStatus;
	this.getStatusText	= getStatusText;
	this.stateChanger	= stateChanger;
	
	// state handlers
	this.loadingState	= new Function();
	this.errorState		= new Function();
	this.successState	= new Function();
		
	// general variables
	var _request		= false;
	var _request_type	= 'POST' // default send type
	var _this			= this; // private object instance
		
	/**
	 * Initialize the request object
	 */
	function InitRequest() {
		if(!_request) {
			if(window.XMLHttpRequest) {
				_request = new XMLHttpRequest();
			} else if(window.ActiveXObject) { 
				var control_types = new Array('MSXML2.XMLHTTP.5.0','MSXML2.XMLHTTP.4.0','MSXML2.XMLHTTP.3.0','MSXML2.XMLHTTP','Microsoft.XMLHTTP');
				for (var i = 0; i < 5 && (typeof(_request) == 'undefined' || !_request); i++) {
					try {
						_request = new ActiveXObject(control_types[ i ]);
					} catch(e) { 
						_request = false; 
					}
				}
			}
		}
	}

	/**
	 * Open a request
	 */
	function Open(url, asyno_flag) {
		if(!_request || typeof(_request) == 'undefined') {
			InitRequest();
		}
		if(_request && typeof(url) != 'undefined' && url != '' && typeof(_request) != 'undefined') {
			// reset the state handlers so there's no overlapping
			_this.loadingState = _this.errorState = _this.successState = new Function();
			
			// open the request
			_request.open(_request_type, url, asyno_flag);
			_request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
		}
	}

	/**
	 * Function to close a request
	 */
	function Close() {
		if(_request && typeof(_request) != 'undefined') {
			_request.abort();
			_request = false;
		}
	}

	/**
	 * Function to send data over http asynchronously
	 */
	function Send(data) {
		if(_request && typeof(_request) != 'undefined') {
			_request.onreadystatechange = stateChanger;
			_request.send(data);
		}
	}

	/**
	 * Function to set the request type
	 */
	function setRequestType(request_type) {
		if(request_type && typeof(request_type) != null) {
			_request_type = request_type;
		}
	}

	/**
	 * Function to return the response text from the request
	 */
	function getResponseText() {
		var response_text = null;
		if(_request && typeof(_request) != 'undefined') {
			if(_request.responseText && typeof(_request.responseText) != 'undefined') {
				response_text = _request.responseText;
			}
		}
		return response_text;
	}

	/**
	 * Function to return the response text from the request
	 */
	function getResponseXML() {
		var response_xml = null;
		if(_request && typeof(_request) != 'undefined') {
			if(_request.responseXML && typeof(_request.responseXML) != 'undefined') {
				response_xml = _request.responseXML;
			}
		}
		return response_xml;
	}
	
	/**
	 * Function to get the ready state from the request
	 */
	function getReadyState() {
		var ready_state = 0;
		if(_request && typeof(_request) != 'undefined') {
			if(_request.readyState && typeof(_request.readyState) != 'undefined') {
				ready_state = parseInt(_request.readyState);
			}
		}
		return ready_state;
	}

	/**
	 * Function to get the numerical status of the request
	 */
	function getStatus() {
		var respose_status = 404;
		if(_request && typeof(_request) != 'undefined') {
			if(_request.status && typeof(_request.status) != 'undefined') {
				response_status = parseInt(_request.status);
			}
		}
		return response_status;
	}

	/**
	 * Function to get the status text from a response
	 */
	function getStatusText() {
		var response_status_text = '';
		if(_request && typeof(_request) != 'undefined') {
			if(_request.statusText && typeof(_request.statusText) != 'undefined') {
				response_status_text = _request.statusText;
			}
		}
		return response_status_text;
	}

	/**
	 * Function to handle state changes
	 */
	function stateChanger() {
		if(_request && typeof(_request) != 'undefined') {
			
			var ready_state = getReadyState();
			if(ready_state < 4) {
				_this.loadingState();
			}
			if(ready_state == 4) {
				var status = getStatus();
				if(status == 404) {
					_this.errorState();
				} else {
					_this.successState();
				}
			}
		} else {
			_this.errorState();
		}
	}
}