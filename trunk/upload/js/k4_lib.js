/**
 * k4 Bulletin Board, k4lib JavaScript object
 * Copyright (c) 2005, Peter Goodman
 * Licensed under the LGPL license
 * http://www.gnu.org/copyleft/lesser.html
 * @author Peter Goodman
 * @version $Id$
 * @package k4bb
 */

var FA = {
	
	//
	// Get an object by its id
	//
	"getObj": function(obj_id) {
		var ret_obj = false;
		if (document.getElementById) {
			ret_obj =  document.getElementById(obj_id);
		} else if (document.all) {
			ret_obj =  document.all[obj_id];
		} else if (document.layers) {
			ret_obj =  document.layers[obj_id];
		} else if (document.frames) {
			ret_obj =  document.frames[obj_id];
		}
		return ret_obj;
	},

	//
	// Get elements by their tag names
	//
	"tagsByName": function(obj, tag_name) {
		var elements = new Array();
		if (typeof(obj.getElementsByTagName) != 'undefined') {
			elements = obj.getElementsByTagName(tag_name);
		} else if(obj.all && obj.all.tags) {
			elements = obj.all.tags(tag_name);
		}
		return elements;
	},

	//
	// Prepend child_obj to obj
	//
	"prependChild": function(obj, child_obj) {
		if(typeof(obj.firstChild) != 'undefined' && obj.firstChild) {
			obj.insertBefore(child_obj, obj.firstChild);
		} else {
			obj.appendChild(child_obj);
		}
		return true;
	},
	
	//
	// Get the top position of an element
	//
	"posTop": function(obj) {
		var postop	= 0;
		while(obj && obj != null) {
			postop += obj.offsetTop; // - obj.scrollTop
			obj = obj.offsetParent;
		}
		return postop;
	},
	
	//
	// Get the left position of an object
	//
	"posLeft": function(obj) {
		var posleft = 0;
		if(obj) {
			posleft = obj.offsetLeft;
			while((obj = obj.offsetParent) != null) {
				posleft += obj.offsetLeft;
			}
		}
		return posleft;
	},
	
	//
	// Get the right position of an object
	//
	"posRight": function(obj) {
		return parseInt(this.posLeft(obj) + obj.offsetWidth);
	},
	
	//
	// Get the bottom position of an object
	//
	"posBottom": function(obj) {
		return parseInt(this.posTop(obj) + obj.offsetHeight);	
	},
	
	//
	// Preload images
	//
	"preloadImages": function() {
		if(document.images){ 
			if(!document.preloaded_images) { 
				document.preloaded_images = [];
			}
			var j 			= FA.sizeOf(document.preloaded_images);
			var func_args 	= this.preloadImages.arguments; 
			for(var i = 0; i < FA.sizeOf(func_args); i++) {
				if (func_args[i].indexOf('#') != 0) { 
					document.preloaded_images[j]		= new Image();
					document.preloaded_images[j++].src	= func_args[i];
				}
			}
		}
	},
	
	//
	// Show an element
	//
	"show": function(obj) {
		if(typeof(obj.style) != 'undefined') {
			if(obj.style.display == '' || obj.style.display == 'none') {
				obj.style.display = 'block';
			}
		}
	},
	
	//
	// Hide an element
	//
	"hide": function(obj) {
		if(typeof(obj.style) != 'undefined') {
			obj.style.display = 'none';
		}
	},
	
	//
	// Put a cursor as a link
	//
	"linkCursor": function(obj) {
		try {
			obj.style.cursor = 'pointer';
		} catch(e) {
			obj.style.cursor = 'hand';
		}
	},
	
	//
	// Attach an event to an object
	// this is an adaptation of the AttachEvent function by Gavin Kirstner
	//
	"attachEvent": function(obj, evt, fnc) {
		
		var ret = true;

		if (obj.addEventListener){
			obj.addEventListener(evt, fnc, false);
		} else if (obj.attachEvent) {
			ret = obj.attachEvent("on" + evt, fnc);
		} else{
			
			if (!obj.myEvents) {
				obj.myEvents = {};
			}
			if (!obj.myEvents[evt]) {
				obj.myEvents[evt] = [];
			}
			
			var evts = obj.myEvents[evt];
			
			evts[evts.length] = fnc;
			
			obj['on' + evt] = function() { 
				if (!obj || !obj.myEvents || !obj.myEvents[evt]) {
					return;
				}
				var evts = obj.myEvents[evt];
				for (var i = 0, len = evts.length; i < len;i++) {
					evts[i]();
				}
			}
		}

		return ret;
	},
	
	//
	// Get an event
	//
	"getEvent": function(e) {
		if (typeof(e) == 'undefined') { e = window.event; }
		if (typeof(e.layerX) == 'undefined') { e.layerX = e.offsetX; }
		if (typeof(e.layerY) == 'undefined') { e.layerY = e.offsetY; }
		return e;
	},

	//
	// Get the target of an event
	//
	"eventTarget": function(ev) {
		
		var e		= this.getEvent(ev);
		var targ	= false;

		if (e.target) {
			targ = e.target;
		} else if (e.srcElement) {
			targ = e.srcElement;
		}
		if (targ.nodeType == 3) { // defeat Safari bug
			targ = targ.parentNode;
		}
		return targ;
	},
	
	//
	// Stop an event
	//
	"stopEvent": function(e) {
		this.stopPropagation(e);
		this.preventDefault(e);
	},

	//
	// Stop event propogation and bubbling
	//
	"stopPropagation": function(e) {
		if (e.stopPropagation) {
			e.stopPropagation();
		} else {
			e.cancelBubble = true;
		}
	},

	//
	// Prevent the default behavior of an event if any
	//
	"preventDefault": function(e) {
		if (e.preventDefault) {
			e.preventDefault();
		}
	},
	
	//
	// Enable an element
	//
	"enableElm": function(obj) {
		if(typeof(obj) != 'undefined' && typeof(obj.disabled) != 'undefined') {
			obj.disabled = false;
		}
	},
	
	//
	// Disable an element
	//
	"disableElm": function(obj) {
		if(typeof(obj) != 'undefined') {
			obj.disabled = true;
		}
	},
	
	//
	// Array sizeof function
	//
	"sizeOf": function(thearray) {
		array_length		= 0;
		if(thearray != null && typeof(thearray) != 'undefined') {
			
			if(typeof(thearray.length) != 'undefined') {
				array_length	= thearray.length;
			} else {
				
				var i = 0;
				for (key in thearray) {
					if ((typeof(thearray[i]) == 'undefined') || (thearray[i] == '') || (thearray[i] == null)) {
						return i;
					}
					i++;
				}
			}
		}
		return array_length;
	},
	
	//
	// Array search function
	//
	"search": function(thearray, needle) {
		var pos = 0;
		for(var i = 0; i < this.sizeOf(thearray); i++) {
			if(thearray[i].value == needle) {
				pos = i;
			}
		}
		return pos;
	}
};

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
	for(var i = 0; i < FA.sizeOf(this); i++) {
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
	this[FA.sizeOf(this)] = value;
};
k4lib.prototype.array_push = function(thearray, value) {
	thearray.push(value);
};

//
// Get the positiong of an element in an array
//
Array.prototype.search = function(needle) {
	return FA.search(this, needle);
};
Object.prototype.search = function(needle) {
	return FA.search(this, needle);
}
	
//
// Set the index on a select form field
//
k4lib.prototype.setIndex = function(needle, obj_id) {
	var temp				= FA.getObj(obj_id);
	if(temp) {
		temp.selectedIndex	= temp.search(needle);
	}
};

//
// Set the index on a select form field and if the index doesn't exist,
// set the first option to be it.
//
k4lib.prototype.forceSetIndex			= function(needle, obj_id) {
	var temp				= FA.getObj(obj_id);

	if(temp) {
		temp.selectedIndex	= temp.search(needle);

		if(temp.selectedIndex == 0) {
			temp[0].value	= needle;
			temp[0].text	= needle;
		}
		FA.enableElm(temp);
	}
};

//
// Set the indices on a multi-select select field
//
k4lib.prototype.setIndices = function(values_array, obj_id) {
	var temp = FA.getObj(obj_id);
	if(temp) {
		if(FA.sizeOf(values_array) > 0) {
			for(var i = 0; i < FA.sizeOf(temp.options); i++) {
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
	var temp = FA.getObj(obj_id);
	if(temp) {
		for(var i = 0; i < FA.sizeOf(temp.options); i++) {
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
	var temp = FA.getObj(obj_id);
	if(temp) {
		for(var i = 0; i < FA.sizeOf(temp.options); i++) {
			temp.options[i].selected = true;
		}
	}			
};

//
// Set a text box
//
k4lib.prototype.setText = function(text, obj_id) {
	var temp = FA.getObj(obj_id);
	if(temp) {
		temp.value = text;
		FA.enableElm(temp);
	}
};

//
// Set a radio button
//
k4lib.prototype.setRadio = function(value, name) {
	var inputs = document.getElementsByTagName('input');
	if(inputs) {
		for (var x = 0; x < FA.sizeOf(inputs); x++) {
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
	var input = FA.getObj(obj_id);
	var check = false;
	if(input) {
		check = (value || value > 0) ? true : false;	
	}
	input.checked = check;
};

//
// Get the selected index of a <select> menu
//
k4lib.prototype.getSelectedIndex = function(needle, thearray) {
	thearray.search(needle);
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