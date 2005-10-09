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
	
	for (var i = 0; i < this.sizeof(thearray); i++) {
		
		if (thearray[i] == needle) {
			bool			= true;
		}
	}

	return bool;
}

/**
 * Return the array key of a value
 */
k4lib.prototype.array_key				= function(thearray, needle) {
	
	var key				= 0;
	
	for (var i = 0; i < this.sizeof(thearray); i++) {
		
		if (thearray[i] == needle) {
			key			= i;
		}
	}

	return key;
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

/**
 * k4 AJAX/XML-RPC object handler
 */
function k4http() {
	this.d			= new k4lib();
	this.request	= null;
	this.state		= 0;
	this.status		= 200;
	this.response	= '';
	this.state_handler = null;
}

/* Initiate a http request object */
k4http.prototype.init	= function() {
	if(typeof this.request == 'undefined' || this.request == null) {
		
		if(window.XMLHttpRequest) {
			
			// create an XML HTTP Request object
			this.request = new XMLHttpRequest();

		} else if(window.ActiveXObject) { 
			
			// define control types for the ActiveX object
			var control_types = new Array(
											'MSXML2.XMLHTTP.5.0',
											'MSXML2.XMLHTTP.4.0',
											'MSXML2.XMLHTTP.3.0',
											'MSXML2.XMLHTTP',
											'Microsoft.XMLHTTP'
											);
			
			// loop through and try to instanciate an ActiveX object
			for (var i = 0; i < d.sizeof(control_types) && (typeof this.request == 'undefined' || this.request == null); i++) {
				try {
					this.request = new ActiveXObject(control_types[i]);
				} catch (e) { 
					this.request = null; 
				}
			}
		}
	}
}

/* Close a request object */
k4http.prototype.close	= function() {
	if(typeof this.request != 'undefined' && this.request != null) {
		this.request.abort();
		this.request		= null;
		this.r_state		= 1;
		this.r_status		= 200;
		this.r_response		= '';
		this.state_handler	= null;
	}
}

/* Function to open a request */
k4http.prototype.open	= function(method, url, flag) {
	if(typeof this.request != 'undefined' && this.request != null) {
		
		method	= (method == 'POST' ? 'POST' : 'GET');
		flag	= typeof flag != 'undefined' ? flag : false;
		
		// some redundant checking
		if(method != null && url != null) {
			
			// open a request
			this.request.open(method, url, flag);
			
			// set the request header format
			this.set_header();
		}
	}
}

/* Function to set the request header */
k4http.prototype.set_header	= function() {
	if(typeof this.request != 'undefined' && this.request != null) {
		this.request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
	}
}

/* Function to set the current ready-state status */
k4http.prototype.change_ready_state = function() {
	if(typeof this.request != 'undefined' && this.request != null) {
//		this.state		= (typeof this.request.readyState != 'undefined' && this.request.readyState ? this.request.readyState : 0);
//		this.status		= (typeof this.request.status != 'undefined' && this.request.status ? this.request.status : 0);
//		this.response	= (typeof this.request.responseText != 'undefined' && this.request.responseText ? this.request.responseText : '');
	
		if(this.request.readyState && typeof this.request.readyState != 'undefined') this.r_state = this.request.readyState;
		if(this.request.status && typeof this.request.status != 'undefined') this.r_status = this.request.status;
		if(this.request.responseText && typeof this.request.responseText != 'undefined') this.r_response = this.request.responseText;
	}
}

/* Create a ready-state-change function */
k4http.prototype.set_handler = function() {
	
	// add a function to the ready state change handler
	if(this.request != null) {
		
		this.request.onreadystatechange = this.state_handler;

//		this.request.onreadystatechange = function() { 
//			
//			if(r.state_handler != null) {
//
//				// get the state and status, need to use 'r' here because we are within a sub-function
//				//r.change_ready_state();
//				
//				// evaluate the state change handling function
//				eval(r.state_handler);
//			}
//		}
	}
}

/* Function to send data */
k4http.prototype.send	= function(data_string) {
	
	if(this.request != null) {
		// set the handling functions
		this.set_handler();
		
		if(typeof data_string == 'string') {
			this.request.send(data_string);
		} else {
			if(!this.d.is_ie) {
				this.request.send(null);
			} else {
				this.request.send();
			}
		}
	}
}

/* Function to evaluate a function */
k4http.prototype.evaluate_function = function(function_name, arguments) {
	var str = '';
	var args = arguments;
	
	for(var i = 0; i < this.d.sizeof(args); i++) {
		
		switch(typeof args[i]) {
			case 'string': args[i] = "'" + args[i] + "'"; break;
			case 'object': alert('Error: cannot complete function evaluation.'); break;
			case 'boolean':
			case 'integer': arguments[i] = parseInt(args[i]); break;
			default: alert('Unknown argument type for function evaluation.');
		}
		
		str += args[i] + ",";
	}
	
	//str		= str.length > 1 ? str.substring(1) : str;
	alert(function_name + "(" + str + ");");
	//return eval(function_name + "(" + str + ");");
}