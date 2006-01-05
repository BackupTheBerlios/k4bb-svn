/**
 * k4 Bulletin Board, k4lib JavaScript class
 * Copyright (c) 2005, Peter Goodman
 * @author Peter Goodman
 * @version $Id$
 * @package k42
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
	var temp = this.getElementById(select);
	if(temp) {
		if(this.sizeof(values_array) > 0) {
			for(var i=0; i < this.sizeof(temp.options); i++) {
				
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
