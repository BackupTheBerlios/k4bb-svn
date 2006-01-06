/**
 * k4 Bulletin Board, k4XMLHttpRequest object and related objects
 * Copyright (c) 2005, Peter Goodman
 * Licensed under the LGPL license
 * http://www.gnu.org/copyleft/lesser.html
 * @author Peter Goodman
 * @version $Id$
 * @package k42
 */

var total_requests = 0;

//
// k4XMLHttpRequest object constructor
//
function k4XMLHttpRequest() {
	return this.InitRequest();
}

//
// k4XMLHttpRequest class definition
//
k4XMLHttpRequest.prototype = {

	// some vars
	request_type:	'POST',
	lib:			new k4lib(),

	//
	// Initialize an AJAX request
	//
	InitRequest: function() {

		var http_request = false;

		if(window.XMLHttpRequest) {

			http_request = new XMLHttpRequest();

		} else if(window.ActiveXObject) {
			var control_types = new Array('MSXML2.XMLHTTP','MSXML2.XMLHTTP.5.0','MSXML2.XMLHTTP.4.0','MSXML2.XMLHTTP.3.0','Microsoft.XMLHTTP');
			for (var i = 0; i < 5 && (typeof(http_request) == 'undefined' || !http_request); i++) {
				try {
					http_request = new ActiveXObject(control_types[ i ]);
				} catch(e) {
					http_request = false;
				}
			}
		}

		// increment the total number of requests we are calling
		total_requests++;
		
		// default to using an iframe if needed
		if(!http_request) {
			http_request = k4IframeRequestFactory.createInstance();
		}

		return k4XMLHTTPRequestObjectFactory.createInstance(http_request);
	}
}

//
// k4XMLHTTPRequestObject constructor
//
function k4XMLHTTPRequestObject(request_obj) {
	this.request_obj = request_obj;
}

//
// k4XMLHTTPRequestObject object definition
//
k4XMLHTTPRequestObject.prototype = {

	lib:			new k4lib(),
	request_obj:	null,

	// hooks
	loadingState:	new Function(),
	errorState:		new Function(),
	successState:	new Function(),

	//
	// Open a request
	//
	Open: function(url, asyno_flag) {

		if(!this.request_obj || typeof(this.request_obj) == 'undefined') {
			InitRequest();
		}
		if(this.request_obj && typeof(url) != 'undefined' && url != '' && typeof(this.request_obj) != 'undefined') {
			// reset the state handlers so there's no overlapping
			this.loadingState = this.errorState = this.successState = new Function();

			// open the request
			this.request_obj.open(this.request_type, url, asyno_flag);
			this.request_obj.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
		}
	},

	//
	// Close the request
	//
	Close: function() {
		if(this.request_obj && typeof(this.request_obj) != 'undefined') {
			this.request_obj.abort();
			this.request_obj = false;
		}
	},

	//
	// Send a request
	//
	Send: function(data_to_send) {
		if(this.request_obj && typeof(this.request_obj) != 'undefined') {

			//var k4_state_change_handler = ;
			var k4_http = this;
			this.request_obj.onreadystatechange = function() { k4XMLHttpRequestStatesFactory.createInstance(k4_http); };
			this.request_obj.send(data_to_send);
		}
	},

	//
	// Set the request type (GET or POST)
	//
	setRequestType: function(request_type) {
		if(request_type && typeof(request_type) != null) {
			this.request_type = request_type;
		}
	},

	//
	// Get the response text from a request
	//
	getResponseText: function() {
		var response_text = null;
		if(this.request_obj && typeof(this.request_obj) != 'undefined') {
			if(this.request_obj.responseText && typeof(this.request_obj.responseText) != 'undefined') {
				response_text = this.request_obj.responseText;
			}
		}
		return response_text;
	},

	//
	// Get the respomse XML from a request
	//
	getResponseXML: function() {
		var response_xml = null;
		if(this.request_obj && typeof(this.request_obj) != 'undefined') {
			if(this.request_obj.responseXML && typeof(this.request_obj.responseXML) != 'undefined') {
				response_xml = this.request_obj.responseXML;
			}
		}
		return response_xml;
	},

	//
	// Get the ready-state of a request
	//
	getReadyState: function() {
		var ready_state = 0;
		if(this.request_obj && typeof(this.request_obj) != 'undefined') {
			if(this.request_obj.readyState && typeof(this.request_obj.readyState) != 'undefined') {
				ready_state = parseInt(this.request_obj.readyState);
			}
		}
		return ready_state;
	},

	//
	// Get the status of a request
	//
	getStatus: function() {
		var respose_status = 404;
		if(this.request_obj && typeof(this.request_obj) != 'undefined') {
			if(this.request_obj.status && typeof(this.request_obj.status) != 'undefined') {
				response_status = parseInt(this.request_obj.status);
			}
		}
		return response_status;
	},

	//
	// Get the status text from a request
	//
	getStatusText: function() {
		var response_status_text = '';
		if(this.request_obj && typeof(this.request_obj) != 'undefined') {
			if(this.request_obj.statusText && typeof(this.request_obj.statusText) != 'undefined') {
				response_status_text = this.request_obj.statusText;
			}
		}
		return response_status_text;
	}
}

//
// k4XMLHttpRequestStates constructor
//
function k4XMLHttpRequestStates(k4_http) {
	this.stateChanger(k4_http);
}

// class definition
k4XMLHttpRequestStates.prototype = {

	//
	// Handle request state changes
	//
	stateChanger: function(k4_http) {
		if(k4_http.request_obj && typeof(k4_http.request_obj) != 'undefined') {

			var ready_state = k4_http.getReadyState();
			
			if(ready_state < 4) {
				k4_http.loadingState();
			}
			if(ready_state == 4) {

				var request_status = k4_http.getStatus();

				if(request_status == 404) {
					k4_http.errorState();
				} else {
					k4_http.successState();
				}
			}
		} else {
			k4_http.errorState();
		}
	}
}

//
// k4IframeRequest class constructor
//
function k4IframeRequest() {
	this.createIframe();
	//this.iframe_obj		= this.lib.getElementById(this.iframe_id);

	return this;
}

// object definition, mimicks the XMLHttpRequest object definition
k4IframeRequest.prototype = {

	// general vars
	lib:				new k4lib(),
	iframe_id:			'request_no_' + parseInt(total_requests),
	iframe_obj:			false,
	iframe_src:			'',

	// request-specific var
	onreadystatechange: new Function(),
	readyState:			1,
	responseText:		'',
	responseXML:		'',
	status:				200,
	statusText:			'',
	frame_loaded:		false,
	iframe_timer:		false,

	//
	// Create the iframe
	//
	createIframe: function() {
		
		// get or try to create a container to put the iframes in
		var iframe_div = this.lib.getElementById('iframe_request_div');
		if(typeof(iframe_div) == 'undefined' || !iframe_div) {
			document.writeln('<div id="iframe_request_div" style="display:none;"> </div>');
			iframe_div = this.lib.getElementById('iframe_request_div');
		}
		
		// create the iframe
		if(typeof(iframe_div) != 'undefined' && iframe_div) {
		
			this.iframe_obj = document.createElement('iframe');
			iframe_div.appendChild(this.iframe_obj);
			
			// set some parameters
			this.iframe_obj.id				= this.iframe_id;
			this.iframe_obj.name			= this.iframe_id;
			this.iframe_obj.style.display	= 'none';
			this.iframe_obj.src				= '';
		}
	},

	//
	// Abort a request, remove the iframe
	//
	abort: function() {
		if(typeof(this.iframe_obj) != 'undefined' && this.iframe_obj) {
			this.iframe_obj.parentNode.removeChild(this.iframe_obj);
		}
	},

	//
	// Return a string of all headers sent to the browser
	//
	getAllResponseHeaders: function() {
		return '';
	},

	//
	// Get a specific response header
	//
	getResponseHeader: function() {
		return '';
	},

	//
	// Set a request header
	//
	setRequestHeader: function() {
		return true;
	},

	//
	// Open a request: tell the iframe to go to a url
	//
	open: function(request_method, url) {
		this.iframe_src = url;
	},

	//
	// Send the request
	//
	send: function(send_data) {
		
		if(!this.iframe_obj) {
			this.iframe_obj		= this.lib.getElementById(this.iframe_id);
		}
		
		if(typeof(this.iframe_obj) != 'undefined' && this.iframe_obj) {

			// create the full url
			var append_str = '?';
			if(this.iframe_src.indexOf('.php?') != -1) { // watch out! this requires for the script to be PHP
				append_str = '&';
			}
			
			// pass the url to the iframe
			this.iframe_src		+= (send_data.length != '' ? append_str + send_data : '');
			this.iframe_obj.src = this.iframe_src;
						
			// set a function to the loading of the iframe using
			// Gavin Kistner's AttachEvent function
			AttachEvent(this.iframe_obj,'load',function() { k4_iframe_http.frame_loaded = true; },false);

			// toggle a state change after 1 second
			this.iframe_timer = setTimeout( (function(k4_iframe_http){ return function(){ k4_iframe_http.stateChange(); } })(this), 1000);
		}
	},

	//
	// Deal with a state-changes
	//
	stateChange: function() {
		
		if(this.frame_loaded) {
			
			if(this.iframe_timer) {
				clearTimerout(this.iframe_timer);
			}

			var iframe_document = this.get_iframe_document();
			
			// get the stuf in the frame
			frame_html					= '';
			if(typeof(iframe_document.body) != 'undefined' && iframe_document.body) {
				frame_html				= iframe_document.body.innerHTML;
			} else {
				if(typeof(iframe_document.firstChild) != 'undefined') {
					// should we strip out any tags?
					frame_html			= iframe_document.firstChild.innerHTML;
					
					// NOTE: you could try iframe_document.firstChild.lastChild.nodeValue and probably
					// get the same results

					if(frame_html.indexOf('<head></head><body>') != -1) {
						frame_html		= frame_html.substring(19);
						frame_html		= frame_html.substring(0, (frame_html.length - 7));
					}
				}				
			}
			
			

			// change the state stuff
			this.readyState		= 4;
			this.status			= 200;
			this.responseText	= frame_html;
			this.onreadystatechange();
			this.abort();

		} else {
			if(this.readyState < 4) {
				
				this.readyState	= 1;
				this.onreadystatechange();
				
				// toggle a state change after 1 second
				this.iframe_timer = setTimeout( (function(k4_iframe_http){ return function(){ k4_iframe_http.stateChange(); } })(this), 1000);
			}
		}
	},
	
	//
	// get the 'document' DOM of an object (an iframe)
	//
	get_iframe_document: function() {
		var dom_object		= false;
		var frame_object	= false;

		if(this.iframe_obj) {
			if (document.all) {
				try { frame_object = frames[this.iframe_id]; } catch(ex) { debug('Could not fetch Frame object (document.all)', ex); }
			} else {
				try { frame_object = this.iframe_obj.contentWindow; } catch(e) { debug('Could not fetch Frame object (!document.all)', e); }
			}
			if(frame_object) {

				dom_object	= frame_object.document;

				if(!dom_object && document.all && this.iframe_obj.contentWindow) {
					dom_object = this.iframe_obj.contentWindow.document;
				}
			}
		}
		return dom_object;
	}
}

//
// Class factories
//
var k4XMLHttpRequestFactory = {
    createInstance: function() {
        return new k4XMLHttpRequest();
    }
}
var k4XMLHTTPRequestObjectFactory = {
    createInstance: function(request_obj) {
        return new k4XMLHTTPRequestObject(request_obj);
    }
}
var k4XMLHttpRequestStatesFactory = {
    createInstance: function(k4_http) {
        return new k4XMLHttpRequestStates(k4_http);
    }
}
var k4IframeRequestFactory = {
    createInstance: function() {
        return new k4IframeRequest();
    }
}
