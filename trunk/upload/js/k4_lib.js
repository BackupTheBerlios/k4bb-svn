/**
 * k4 Bulletin Board, k4lib JavaScript object
 * Copyright (c) 2005, Peter Goodman
 * Licensed under the LGPL license
 * http://www.gnu.org/copyleft/lesser.html
 * @author Peter Goodman
 * @version $Id$
 * @package k4bb
 */

function k4lib() {
	
	//
	// Get browser information
	//
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

//
// Get an object by its ID 
//
String.prototype.obj = function() {
	var ret_obj = false;
	if (document.getElementById) {
		ret_obj = document.getElementById(this);
	} else if (document.all) {
		ret_obj = document.all[this];
	} else if (document.layers) {
		ret_obj = document.layers[this];
	} else if (document.frames) {
		ret_obj = document.frames[this];
	}
	return ret_obj;
};
k4lib.prototype.getElementById = function(obj_id) {
	var obj = false;
	if(typeof(obj_id) == 'string') {
		obj = obj_id.obj();	
	}
	return obj;
};

//
// Get an object by its tag name
//
Object.prototype.getTagsByName = function(tagname) {
	var elements = false;
	if (typeof(this.getElementsByTagName) != 'undefined') {
		elements = this.getElementsByTagName(tagname);
	} else if(this.all && this.all.tags) {
		elements = this.all.tags(tagname);
	}
	return elements;
};
document.getTagsByName = document.getElementsByTagName;
k4lib.prototype.getElementsByTagName = function(parentobj, tagname) {
	parentobj.getTagsByName(tagname);	
};

//
// Append text onto any object
//
Object.prototype.appendText = function(text) {
	var text_obj = document.createTextNode(text);
    this.appendChild(text_obj); 
};
k4lib.prototype.appendText = function(obj, text) {
     obj.appendText(text);
};

//
// Prepend an object (obj) to its parent object
//
Object.prototype.prependChild = function(obj) {
	var ret = false;
	if(typeof(this.firstChild) != 'undefined') {
		this.insertBefore(obj, this.firstChild);
		ret = true;
	}
	return ret;
};

//
// Boolean true or false if a value is in an array
//
Array.prototype.find = Object.prototype.find = function(needle) {
	var bool				= false;
	for(key in this) {
		if(bool) {
			break;
		}
		if(this[key] == needle) {
			bool = true;
		}
	}
	return bool;
};
k4lib.prototype.in_array = function(thearray, needle) {
	return thearray.find(needle);
};

//
// Return the array key of a value
//
Array.prototype.key = function(needle) {
	var the_key				= false;
	for(key in this) {
		if(the_key) {
			break;
		}
		if(key == needle) {
			the_key = key;
		}
	}
	return the_key;
};
k4lib.prototype.array_key = function(thearray, needle) {
	thearray.key(needle);
};

//
// Array unset function for a given value 
//
Array.prototype.kill = function(value) {
	for(var i = 0; i < this.sizeof(); i++) {
		if(this[i] == value) {
			delete this[i];
		}
	}
	return true;
};
k4lib.prototype.unset = function(thearray, value) {
	thearray.kill(value);	
};

//
// Array Push function 
//
Array.prototype.push = function(value) {
	this[this.sizeof()] = value;
};
k4lib.prototype.array_push = function(thearray, value) {
	thearray.push(value);
};

//
// count()/sizeof() like function for an array 
//
function sizeof(thearray) {
	array_length		= 0;
	if(thearray != null && typeof(thearray) != 'undefined') {
		for (i = 0; i < thearray.length; i++) {
			if ((typeof(thearray[i]) == 'undefined') || (thearray[i] == '') || (thearray[i] == null)) {
				return i;
			}
		}
		array_length	= thearray.length;
	} else {
		array_length	= 0;
	}
	return array_length;
}
k4lib.prototype.sizeof = function(thearray) {
	return sizeof(thearray);
};
Array.prototype.sizeof = function() {
	return sizeof(this);
};
Object.prototype.sizeof = function() {
	return sizeof(this);
};
	
//
// Set the index on a select form field
//
k4lib.prototype.setIndex = function(needle, obj_id) {
	var temp				= obj_id.obj();
	if(temp) {
		temp.selectedIndex	= temp.search(needle);
	}
};

//
// Set the index on a select form field and if the index doesn't exist,
// set the first option to be it.
//
k4lib.prototype.forceSetIndex			= function(needle, obj_id) {
	var temp				= obj_id.obj();

	if(temp) {
		temp.selectedIndex	= temp.search(needle);

		if(temp.selectedIndex == 0) {
			temp[0].value	= needle;
			temp[0].text	= needle;
		}
		temp.disabled		= false;
	}
};

//
// Set the indices on a multi-select select field
//
k4lib.prototype.setIndices = function(values_array, obj_id) {
	var temp = obj_id.obj();
	if(temp) {
		if(values_array.sizeof() > 0) {
			for(var i = 0; i < temp.options.sizeof(); i++) {
				if(values_array.find(temp.options[i].value)) {
					temp.options[i].selected = true;
				}
			}
		}
	}
};

//
// set all selected items in a <select> field to false
//
k4lib.prototype.selectNone = function(obj_id) {
	var temp = obj_id.obj();
	if(temp) {
		for(var i = 0; i < temp.options.sizeof(); i++) {
			if(temp.options[i].selected == true) {
				temp.options[i].selected = false;
			}
		}
	}			
};

//
// set all selected items in a <select> field to true
//
k4lib.prototype.selectAll = function(obj_id) {
	var temp = obj_id.obj();
	if(temp) {
		for(var i = 0; i < temp.options.sizeof(); i++) {
			temp.options[i].selected = true;
		}
	}			
};

//
// Set a text box
//
k4lib.prototype.setText = function(text, obj_id) {
	var temp = obj_id.obj();
	if(temp) {
		temp.value = text;
		temp.disabled = false;
	}
};

//
// Set a radio button
//
k4lib.prototype.setRadio = function(value, name) {
	var inputs = document.getElementsByTagName('input');
	if(inputs) {
		for (var x = 0; x < inputs.sizeof(); x++) {
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
};

//
// Set a checkbox
//
k4lib.prototype.setCheckbox = function(value, obj_id) {
	var input = obj_id.obj();
	var check = false;
	if(input) {
		check = (value || value > 0) ? true : false;	
	}
	input.checked = check;
};

//
// Get the positiong of an element in an array
//
Array.prototype.search = Object.prototype.search = function(needle) {
	var pos					= 0;
	for(var i = 0; i < this.sizeof(); i++) {
		if(this[i].value == needle) {
			pos			= i;
		}
	}
	return pos;
};
k4lib.prototype.getSelectedIndex = function(needle, thearray) {
	thearray.search(needle);
};

//
// Enable a form button
//
Object.prototype.enable = function() {
	if(typeof(this.disabled) != 'undefined') {
		this.disabled = false;
	}
};
k4lib.prototype.enableButton = function(button) {
	if(button) {
		button.enable();
	}
};

//
// Disable a form button
//
Object.prototype.disable = function() {
	if(typeof(this.disabled) != 'undefined') {
		this.disabled = true;
	}
};
k4lib.prototype.disableButton = function(button) {
	if(button) {
		button.disable();
	}
};

/**
 * Position functions
 */

/* Get the top position of an object */
Object.prototype.top = function() {
	var postop	= 0;
	var obj = this;
	while(obj && obj != null) {
		postop += obj.offsetTop; // - obj.scrollTop
		obj = obj.offsetParent;
	}
	return postop;
};
k4lib.prototype.top	= function(obj) {
	return obj.top();
};

/* get the left position of an object */
Object.prototype.left = function() {
	var posleft = 0;
	var obj = this;
	if(obj) {
		posleft = obj.offsetLeft;
		while((obj = obj.offsetParent) != null) {
			posleft += obj.offsetLeft;
		}
	}
	return posleft;
};
k4lib.prototype.left = function(obj) {
	return obj.left();
};

/* Get the width of an object */
Object.prototype.width = function() {
	return this.offsetWidth;
};
k4lib.prototype.width = function(obj) {
	var objwidth = 0;
	if(obj) {
		objwidth = obj.width();
	}
	return objwidth;
};

/* get the height of an object */
Object.prototype.height = function() {
	return this.offsetHeight;
};
k4lib.prototype.height = function(obj) {
	var objheight = 0;
	if(obj) {
		objheight = obj.height();
	}
	return objheight;
};

/* get the bottom position of an object */
Object.prototype.bottom = function() {
	return parseInt(this.top() + this.offsetHeight);
};
k4lib.prototype.bottom = function(obj) {
	return obj.bottom();
};

/* get the right position of an object */
Object.prototype.right = function() {
	return parseInt(this.left() + this.offsetWidth);
};
k4lib.prototype.right = function(obj) {
	return obj.right();
};
		
/* Check if 'over' overlaps 'under' */
Object.prototype.overlaps = function(under) {
	var does_overlap	= true;
	if(under.left() > this.right()) { does_overlap = false; }
	if(under.right() < this.left()) { does_overlap = false; }
	if(under.top() > this.bottom()) { does_overlap = false; }
	if(under.bottom() < this.top()) { does_overlap = false; }
	return does_overlap;
};
k4lib.prototype.overlaps = function(over, under) {
	return over.overlaps(under);
};

/**
 * Aesthetics
 */

/* Make the object's cursor look like a link */
Object.prototype.linkCursor = function() {
	try {
		this.style.cursor = 'pointer';
	} catch(e) {
		this.style.cursor = 'hand';
	}
};
k4lib.prototype.forceCursor	= function(obj) {
	if(obj) {
		obj.linkCursor();
	}
};

/* Preload Images */
k4lib.prototype.preload_images	= function() {
	if(document.images){ 
		if(!document.preloaded_images) { 
			document.preloaded_images = [];
		}
		var j 			= document.preloaded_images.sizeof();
		var func_args 	= this.preload_images.arguments; 
		for(var i = 0; i < func_args.sizeof(); i++) {
			if (func_args[i].indexOf('#') != 0) { 
				document.preloaded_images[j]		= new Image();
				document.preloaded_images[j++].src	= func_args[i];
			}
		}
	}
};

/* Get the event target, function from QuirksMode */
function get_event_target(the_event) {
	var targ;
	var e = the_event;
	if (!e) { 
		e = window.event; 
	}
	if (e.target) { 
		targ = e.target; 
	} else if (e.srcElement) {
		targ = e.srcElement;
	}
	if (targ.nodeType == 3) { // defeat Safari bug
		targ = targ.parentNode;
	}
	return targ;
}
k4lib.prototype.get_event_target = get_event_target;

/* Create an overflow layer, for such things as Opera */
k4lib.prototype.overflow_layer = function(obj, overflow_type) {
	ret = false;
	// apparently opera doesn't actually need this.. ugh.
	if(typeof(obj.style.overflow) == 'undefined' && this.is_opera && obj.id.indexOf('_overflowLayer') == -1) {
		// this is a hack of using document.write to make the node, (which I don't really like)
		// then instead of having to position it, it will be removed
		// and placed where 'obj' is. 'obj' will be duplicated and placed
		// inside the newly created and moved node.
		document.write('<div id="' + obj.id + '_overflowLayer" style="overflow:' + overflow_type + ';"></div>');
		var overflow_layer = d.getElementById(obj.id + '_overflowLayer');
		if(typeof(overflow_layer) != 'undefined' && overflow_layer) {
			var temp_obj	= obj.cloneNode(false);
			if(obj.replaceNode(overflow_layer)) {
				
				this.obj	= overflow_layer;
				obj.appendChild(temp_obj);
				ret			= true;
			}
			this.using_overflow = true;
		}
	} else {
		ret = true;
	}
	return ret;
};

Object.prototype.show = function() {
	if(typeof(this.style) != 'undefined') {
		if(this.style.display == '' || this.style.display == 'none') {
			this.style.display = 'block';
		}
	}
};
Object.prototype.hide = function() {
	if(typeof(this.style) != 'undefined') {
		this.style.display = 'none';
	}
};

//
// getPageScroll()
// Returns array with x,y page scroll values.
// Core code from - quirksmode.org
//
function getPageScroll(){

	var yScroll;

	if (self.pageYOffset) {
		yScroll = self.pageYOffset;
	} else if (document.documentElement && document.documentElement.scrollTop){	 // Explorer 6 Strict
		yScroll = document.documentElement.scrollTop;
	} else if (document.body) {// all other Explorers
		yScroll = document.body.scrollTop;
	}
	
	return ['',yScroll];
}

//
// getPageSize()
// Returns array with page width, height and window width, height
// Core code from - quirksmode.org
// Edit for Firefox by pHaez
//
function getPageSize(){
	
	var xScroll, yScroll;
	
	if (window.innerHeight && window.scrollMaxY) {	
		xScroll = document.body.scrollWidth;
		yScroll = window.innerHeight + window.scrollMaxY;
	} else if (document.body.scrollHeight > document.body.offsetHeight){ // all but Explorer Mac
		xScroll = document.body.scrollWidth;
		yScroll = document.body.scrollHeight;
	} else { // Explorer Mac...would also work in Explorer 6 Strict, Mozilla and Safari
		xScroll = document.body.offsetWidth;
		yScroll = document.body.offsetHeight;
	}
	
	var windowWidth, windowHeight;
	if (self.innerHeight) {	// all except Explorer
		windowWidth = self.innerWidth;
		windowHeight = self.innerHeight;
	} else if (document.documentElement && document.documentElement.clientHeight) { // Explorer 6 Strict Mode
		windowWidth = document.documentElement.clientWidth;
		windowHeight = document.documentElement.clientHeight;
	} else if (document.body) { // other Explorers
		windowWidth = document.body.clientWidth;
		windowHeight = document.body.clientHeight;
	}	
	
	// for small pages with total height less then height of the viewport
	if(yScroll < windowHeight){
		pageHeight = windowHeight;
	} else { 
		pageHeight = yScroll;
	}

	// for small pages with total width less then width of the viewport
	if(xScroll < windowWidth){	
		pageWidth = windowWidth;
	} else {
		pageWidth = xScroll;
	}
	
	return [pageWidth,pageHeight,windowWidth,windowHeight];
}