/**
 * FileArts FAHttpsRequests object and related objects
 * Copyright (c) 2005, Peter Goodman
 * Licensed under the LGPL license
 * http://www.gnu.org/copyleft/lesser.html
 * @author Peter Goodman
 * @version $Id$
 * @package k4bb
 */

var total_requests	= 0;
var prev_url		= '';
var future_url		= '';

if(typeof(debug) == 'undefined') { function debug(str) { return true; } }

//
// FAHttpRequests object constructor
//
function FAHttpRequests() {
	return this.InitRequest();
}

//
// FAHttpRequestsObject constructor
//
function FAHttpRequestsObject(request_obj) {
	this.request_obj = request_obj;
}

//
// FAHttpRequestsStates constructor
//
function FAHttpRequestsStates(FA_http) {
	this.stateChanger(FA_http);
}

//
// FAIframeRequest object constructor
//
function FAIframeRequest() {
	this.createIframe();
	return this;
}

//
// FAHttpRequests class definition
//
FAHttpRequests.prototype = {

	// some vars
	request_type:	'POST',
	lib:			new k4lib(),

	//
	// Initialize an AJAX request
	//
	InitRequest: function() {

		var http_request = false;

		if(typeof(window.XMLHttpRequest) != 'undefined') {

			http_request = new XMLHttpRequest();

		} else if(typeof(window.ActiveXObject) != 'undefined') {
			
			var control_types = ['MSXML2.XMLHTTP','MSXML2.XMLHTTP.5.0','MSXML2.XMLHTTP.4.0','MSXML2.XMLHTTP.3.0','Microsoft.XMLHTTP'];
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
			http_request = FAIframeRequestFactory.createInstance();
		}

		return FAHttpRequestsObjectFactory.createInstance(http_request);
	}
};

//
// FAHttpRequestsObject object definition
//
FAHttpRequestsObject.prototype = {

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
			//FAHttpRequests.InitRequest();
		}
		if(this.request_obj && typeof(url) != 'undefined' && url != '' && typeof(this.request_obj) != 'undefined') {
			// reset the state handlers so there's no overlapping
			this.loadingState	= new Function();
			this.errorState		= new Function();
			this.successState	= new Function();

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

			//var FA_state_change_handler = ;
			var FA_http = this;
			this.request_obj.onreadystatechange = function() { FAHttpRequestsStatesFactory.createInstance(FA_http); };
			this.request_obj.send(data_to_send);
		}
	},

	//
	// Set the request type (GET or POST)
	//
	setRequestType: function(request_type) {
		if(request_type && typeof(request_type) != 'undefined') {
			
			// make sure the request method is good
			request_type = (request_type == 'GET' || request_type == 'get') ? 'GET' : 'POST';
			
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
};

// object definition
FAHttpRequestsStates.prototype = {

	//
	// Handle request state changes
	//
	stateChanger: function(FA_http) {
		if(FA_http.request_obj && typeof(FA_http.request_obj) != 'undefined') {

			var ready_state = FA_http.getReadyState();
			
			if(ready_state < 4) {
				FA_http.loadingState();
			}
			if(ready_state == 4) {

				var request_status = FA_http.getStatus();

				if(request_status == 404) {
					FA_http.errorState();
				} else {
					FA_http.successState();
				}
			}
		} else {
			FA_http.errorState();
		}
	}
};

// object definition, mimicks the XMLHttpRequest object definition
FAIframeRequest.prototype = {

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
		
		// try to get the container to put the iframes in
		var iframe_div = FA.getObj('iframe_request_div');

		// if it doesn't exist, create it
		if(typeof(iframe_div) == 'undefined' || !iframe_div) {			
			
			iframe_div					= document.createElement('div');
			iframe_div.id				= 'iframe_request_div';
			iframe_div.style.display	= 'none';
			
			// get the <body> tag
			var body_elements			= FA.tagsByName(document, 'body');
			
			// a bit of a hack to make our div without using document.write()
			if(typeof(body_elements[0]) != 'undefined') {
				FA.prependChild(body_elements[0], iframe_div);
			}
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
			this.iframe_obj		= FA.getObj(this.iframe_id);
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
			
			var self = this;						

			// set a function to the loading of the iframe using
			FA.attachEvent(this.iframe_obj,'load',function(){self.frame_loaded=true;});

			// toggle a state change after 1 second
			this.iframe_timer = setTimeout( (function(self){return function(){self.stateChange();}})(this), 1000);
		}
	},

	//
	// Deal with a state-changes
	//
	stateChange: function() {
		
		if(this.frame_loaded) {
			
			if(this.iframe_timer) {
				clearTimeout(this.iframe_timer);
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
				this.iframe_timer = setTimeout( (function(FA_iframe_http){ return function(){ FA_iframe_http.stateChange(); } })(this), 1000);
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
			if(document.all) {
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
};

//
// Class factories
//
var FAHttpRequestsFactory = {
    createInstance: function() {
        return new FAHttpRequests();
    }
};
var FAHttpRequestsObjectFactory = {
    createInstance: function(request_obj) {
        return new FAHttpRequestsObject(request_obj);
    }
};
var FAHttpRequestsStatesFactory = {
    createInstance: function(FA_http) {
        return new FAHttpRequestsStates(FA_http);
    }
};
var FAIframeRequestFactory = {
    createInstance: function() {
        return new FAIframeRequest();
    }
};

//
// Send a request
//
FAHttpRequestsObject.prototype.Request = function(r_type, r_url, r_loading, r_error, r_success) {
	this.setRequestType(r_type);
	this.Open(r_url, true);
	this.Send("");
	this.loadingState	= r_loading ? r_loading : new Function();
	this.errorState		= r_error ? r_error : new Function();
	this.successState	= r_success ? r_success : new Function();
};
FAIframeRequest.prototype.Request = FAHttpRequestsObject['Request'];

//
// Basic mini-lib of things to do
//
var FAHTTP = {

	//
	// Load an entire page, start to finish
	//
	loadPage: function(r_url, r_method, container_id) {
				
		// is this a good url?
		if(typeof(r_url) != 'undefined' && r_url) {
			
			// some other checking
			if(r_url != '' && r_url.indexOf('javascript:') == -1) {
				
				// deal with some objects
				var FA_http			= FAHttpRequestsFactory.createInstance();
				var container_obj	= FA.getObj(container_id);

				// get what's the current url?
				var current_url		= this.escapeStr(document.location);
				
				// add the current url onto the request url
				r_url				= r_url + (r_url.indexOf('?') != -1 ? '&' : '?') + 'currurl=' + current_url;
				
				// get this instance
				var self = this;

				// get things in motion
				FA_http.Request(r_method, r_url, (function(){self.loadingState(container_obj,container_id+'_anchor');}), false, (function(){self.loadHTML(FA_http,container_id);}));
			}
		}
	},
	
	//
	// Load the response HTML of a request
	//
	loadHTML: function(FA_http, container_id) {
		if(typeof(FA_http) != 'undefined' && FA_http) {
			
			this.cancelLoader(container_id + '_anchor');
			
			var container_obj = FA.getObj(container_id);

			if(typeof(container_obj) != 'undefined') {
				
				var response = FA_http.getResponseText();
				
				// if there is no response
				if(response != null && response != '') {
					
					// show the container
					container_obj.style.display = 'block';
					
					if(typeof(container_obj.innerHTML) != 'undefined') {
						
						// inser the new html
						container_obj.innerHTML = response;

						// now, change the title
						var title_element = FA.getObj('page_title_element');
						if(title_element && typeof(document.title) != 'undefined') {
							document.title = title_element.innerHTML;
						}

						// go over all of the <script> tags.. this could be dangerous, but whatever
						var script_objs = FA.tagsByName(container_obj, 'script');
						for(var s = 0; s < FA.sizeOf(script_objs); s++) {
							//try { eval(script_objs[s].innerHTML); } catch(e) { }
						}
						
						// parse the links over again
						this.parseLinks();
					}
				}
			}
		}
	},
	
	//
	// Show the nice loading thing
	//
	loadingState: function(container_obj, anchor_id) {
		if(typeof(container_obj) != 'undefined') {
			
			// show the object
			container_obj.style.display		= 'block';
			
			// try to get the anchor, otherwise create it
			if( ! FA.getObj(anchor_id) ) {
				var anchor_obj				= document.createElement('a');
				anchor_obj.id				= anchor_id;
				anchor_obj.name				= anchor_id;
				
				FA.prependChild(container_obj, anchor_obj);
			}

			// change the url
			this.changeUrl('#' + anchor_id);
			
			var loader_id						= anchor_id + '_loader';
			var loader							= FA.getObj(loader_id);

			// let's make sure none of these exist first!
			if( ! loader ) {
				
				// create the loader
				loader							= document.createElement('div');
				
				var loader_img					= new Image(); //document.createElement('img')
				
				// do some stuff to the loader
				loader.id						= anchor_id + '_loader';
				loader.style.top				= parseInt(FA.posTop(container_obj) + 30) + 'px';
				loader.style.textAlign			= 'center';
				loader.style.zIndex				= 100;
				loader.style.position			= 'absolute';
				loader_img.src					= 'Images/loading.gif';
				loader_img.style.border			= '0px';
				
				// bring it all together
				loader.appendChild(loader_img);
				
				// get the <body> tag
				var body_elements				= FA.tagsByName(document, 'body');
				
				// a bit of a hack to make our div without using document.write()
				if(typeof(body_elements[0]) != 'undefined') {
					body_elements[0].appendChild(loader);
				}
			} else {
				loader.style.display			= 'block';
			}

			// center the loader on the page
			var arrayPageSize	= getPageSize();
			var arrayPageScroll = getPageScroll();
			loader.style.top	= (arrayPageScroll[1] + ((arrayPageSize[3] - 35 - loader.offsetHeight) / 2) + 'px');
			loader.style.left	= (((arrayPageSize[0] - 20 - loader.offsetWidth) / 2) + 'px');
		}
	},
	
	//
	// Destroy the loader message
	//
	cancelLoader: function(anchor_id) {
		var loader_obj = FA.getObj(anchor_id + '_loader');
		if(typeof(loader_obj) != 'undefined' && loader_obj) {
			loader_obj.style.display = 'none';
		}
	},
	
	//
	// Function to change the current page url
	//
	changeUrl: function(url) {
		// hrmm..
	},
	
	//
	// Parse all <a> links and make them able to use this ajax object
	//
	parseLinks: function() {
		// get the <a> tags
		var link_tags		= FA.tagsByName(document, 'a');
		var link_tag_hrefs	= { };

		// loop through all of the <a> tags
		for(var s = 0; s < FA.sizeOf(link_tags); s++) {
			if(typeof(link_tags[s].href) != 'undefined') {
				
				// should we change this one?
				if(link_tags[s].href.indexOf('javascript:') == -1 // don't allow for javascript urls
					&& link_tags[s].href != '' // don't allow for links without urls
					&& link_tags[s].href.indexOf('#') == -1 // don't allow for urls with anchors
					&& link_tags[s].href.charAt(0) != '#' // don't allow for urls with anchors
					&& link_tags[s].href.indexOf('?') == -1 // don't allow for all dynamic urls 
					&& typeof(link_tags[s].onclick) == 'undefined' // don't allow for urls with onclick events
					&& (link_tags[s].target == '' || link_tags[s].target == '_self') // don't allow for urls that popup pages 
					) {
					
					// store the url of this link
					if(typeof(link_tags[s].id) == 'undefined' || !link_tags[s].id || link_tags[s].id == '') {
						link_tags[s].id		= 'FAlink_' + s;
					}
					link_tag_hrefs[link_tags[s].id] = link_tags[s].href;
					
					// current instance
					var self = this;

					// attach an onlick event
					FA.attachEvent(link_tags[s],'click',(function(e){self.loadPage(link_tag_hrefs[self.linkTarget(e)],'GET','t');}));
					
					// change the link
					link_tags[s].href	= 'javascript:;';
				}
			}
		}
	},
	
	//
	// Get the proper link target
	//
	linkTarget: function(the_event) {
		var link_target = FA.eventTarget(the_event);
		if (link_target.nodeType == 3) { // defeat Safari bug
			link_target = link_target.parentNode;
		}
		if(link_target.nodeName.toLowerCase() != 'a') {
			if(link_target.parentNode.nodeName.toLowerCase() == 'a') {
				link_target = link_target.parentNode;
			}
		}
		return link_target.id;
	},
	
	//
	// Escape a string for passing through a url
	//
	escapeStr: function(str) {
		if(typeof(escape) != 'undefined') {
			str = escape(str);
		} else {
			if(typeof(encodeURIComponent) != 'undefined') {
				str = escape_str(str);
			}
		}
		return str;
	}
};