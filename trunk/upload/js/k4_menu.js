/**
 * k4 Bulletin Board, k4menu JavaScript class
 * Copyright (c) 2005, Peter Goodman
 * @author Peter Goodman
 * @version $Id$
 * @package k42
 */
/*
var ALLOW_FOLLOW_URL	= false;
var SET_OVERFLOW_SCROLL	= true;
var rows				= ["alt1", "alt2"];
var menu_actions		= null;

//
// k4Menu class constructor
//
function k4Menu() { 
	return this;
}

//
// k4Menu class definition
//
k4Menu.prototype = {
	
	lib:				new k4lib(),
	open_menu:			false,
	hidden_selects:		new Array(),

	// hooks
	clickRow:			new Function(),
	mouseupRow:			new Function(),
	mouseoverRow:		new Function(),
	mouseoutRow:		new Function(),

	//
	// Initialize a menu
	//
	init: function(link_id, menu_id, use_click, target_id) {

		// get the objects
		var link_obj = this.lib.getElementById(link_id);
		var menu_obj = this.lib.getElementById(menu_id);
		var target_obj = false;
		
		if(link_obj && menu_obj) {
			
			if(typeof(target_id) != 'undefined') {
				target_obj = this.lib.getElementById(target_id);
			}
			this.set_menu_positions(link_obj, menu_obj, target_obj);
			this.attach_events(link_obj, menu_obj, use_click, target_obj);
		}
	},
	
	//
	// Move the menu, taking in mind the browser width
	//
	set_positions: function(link_obj, menu_obj, target_obj) {
		force_right			= parseInt(this.lib.left(link_obj) + this.lib.width(menu_obj)) >= document.body.clientWidth ? true : false;
		temp_obj			= target_obj ? target_obj : link_obj;
		
		menu_obj.style.left	= (force_right ? (this.lib.left(temp_obj) - (this.lib.width(menu_obj) - this.lib.width(temp_obj))) : this.lib.left(temp_obj)) + 'px';
		menu_obj.style.top	= parseInt(this.lib.top(temp_obj) + this.lib.height(temp_obj)) + 'px';
	},

	//
	// Set the menu positions
	//
	set_menu_positions: function(link_obj, menu_obj, target_obj) {
		
		if(link_obj.href && !ALLOW_FOLLOW_URL) { 
			link_obj.href = 'javascript:;'; //link_obj.href = '#' + link_obj.id;
		} 
		
		this.lib.forceCursor(link_obj);
		
		// change the menu
		menu_obj.style.display		= 'none';
		menu_obj.style.position		= 'absolute';
		
		this.set_positions(link_obj, menu_obj, target_obj);
		this.set_highlight_rows(menu_obj, target_obj);
	},
	
	//
	// Check if a menu is open or not
	//
	menu_is_open: function(menu_obj) {
		var ret = false;
		if(menu_obj.style.display == 'block') {
			ret = true;
		}
		return ret;
	},

	//
	// Open a menu
	//
	open_menu: function(link_obj, menu_obj, target_obj) {
		
		// if this menu is not currently open
		if(this.open_menu != menu_obj) {

			// close the open menu
			this.close_menu(this.open_menu);
			
			// start to show our menu
			menu_obj.style.display = 'block';
			
			this.set_positions(link_obj, menu_obj, target_obj);
			this.hide_select_fields(menu_obj);
			this.open_menu			= menu_obj;
			this.set_menu_scroll(menu_obj, target_obj);
		
		} else {

			// close the open menu
			this.close_menu(this.open_menu);
		}
	},

	//
	// Close a menu
	//
	close_menu: function(menu_obj) {
		
		if(typeof(menu_obj) != 'undefined' && menu_obj) {			
			
			if(menu_obj.style) {
				menu_obj.style.display = 'none';
			}
			this.open_menu = false;
			
			this.show_select_fields();
		}
	},

	//
	// Close a menu but this time when a click is registered in the document
	//
	close_menu_onclick: function(event, link_obj, menu_obj, target_obj) {
		if(this.menu_is_open(menu_obj)) {
			
			var pos			= this.get_position(event);
			do_close_menu	= false;
			
			if(pos[1] > this.lib.bottom(menu_obj)) do_close_menu = true;
			if(pos[1] < this.lib.top(link_obj)) do_close_menu = true;
			if(pos[0] < this.lib.left((target_obj ? target_obj : link_obj))) do_close_menu = true; // TODO: include force_right thing
			if(pos[0] > this.lib.right(link_obj)) do_close_menu = true;
			
			if(do_close_menu) {
				this.close_menu(menu_obj);
			}
		}
	},
	
	//
	// Hide any <select> fields that fall under the menu
	//
	hide_select_fields: function(menu_obj) {
		
		var all_selects = this.lib.getElementsByTagName(document, 'select');
		
		if(all_selects) {
			for(var s = 0; s < this.lib.sizeof(all_selects); s++) {
				if(typeof(all_selects[s]) != 'undefined' && this.lib.overlaps(menu_obj, all_selects[s])) {
					
					all_selects[s].style.display = 'none';
					this.lib.array_push(this.hidden_selects, all_selects[s]);
				}
			}
		}
	},
	
	//
	// Show any <select> fields that were hidden by the menu
	//
	show_select_fields: function() {
		if(this.hidden_selects) {
			for(var s = 0; s < this.lib.sizeof(this.hidden_selects); s++) {
				this.hidden_selects[s].style.display = 'block';
			}
			this.hidden_selects = new Array();
		}
	},

	//
	// Attach the opening/closing events to the browser
	//
	attach_events: function(link_obj, menu_obj, use_click, target_obj) {
		
		

		// close a menu
		var close = function(e) { 
			this.close_menu_onclick(e, link_obj, menu_obj, target_obj); 
		}

		return close('hi');
		
		// deal with clicking on a link
		var_open_menu		= this.open_menu(link_obj, menu_obj, target_obj);
		link_obj.onclick	= var_open_menu;
		
		// deal with hovering over a link
		link_obj.onmouseover = function(e) { 
			
			if(_this.open_menu && (menu_obj.id != _this.open_menu.id) ) {
				
				_this.open_menu(link_obj, menu_obj, target_obj);
			}
		}
		if(document.addEventListener ) {
			document.addEventListener('click', close, false);
			if(!use_click) {
				document.addEventListener('mousemove', close, false);
			}
		} else if(document.attachEvent ) {
			document.attachEvent('onclick', close);
			if(!use_click) {
				document.attachEvent('onmousemove', close);
			}
		}
	},
	
	//
	// Set events to highlight the rows of the menu when they are hovered over
	//
	set_highlight_rows: function(menu_obj, target_obj) {
		
		var all_rows	= this.lib.getElementsByTagName(menu_obj, 'td');

		if(all_rows) {
			for(var i = 0; i < this.lib.sizeof(all_rows); i++) {
				if(all_rows[i]) {
					
					all_rows[i].onmouseover = function() { 
						if(this.className == rows[0]) 
							this.className = rows[1]; 
						_this.mouseoverRow(this);
					}
					all_rows[i].onmouseout = function() { 
						if(this.className == rows[1]) 
							this.className = rows[0]; 
						_this.mouseoutRow(this);
					}
					all_rows[i].onclick = function() {
						if(typeof(target_obj) != 'undefined' && target_obj) {
							target_obj.innerHTML = this.innerHTML;
							_this.clickRow(this);
						}
						//_this.close_menu(menu_obj);
					}
					all_rows[i].onmouseup = function() { 
						_this.mouseupRow(this); 
					}
				}
			}
		}
	},
	
	//
	// get the menu position
	//
	get_positions: function(e) {
		if(!e) { e = window.event; }
		if(e) {
			if(typeof( e.pageX ) == 'number') {
				posX = e.pageX; 
				posY = e.pageY;
			} else {
				if(typeof( e.clientX ) == 'number') {
					posX = e.clientX; 
					posY = e.clientY;
					if(document.body && !( window.opera || window.debug || navigator.vendor == 'KDE')) {
						if( typeof( document.body.scrollTop ) == 'number') {
							posX += document.body.scrollLeft; 
							posY += document.body.scrollTop;
						}
					}
					if(document.documentElement && !( window.opera || window.debug || navigator.vendor == 'KDE')) {
						if( typeof( document.documentElement.scrollTop ) == 'number') {
							posX += document.documentElement.scrollLeft; 
							posY += document.documentElement.scrollTop;
						}
					}
				}
			}
		}
		var pos = new Array(posX, posY);
		return pos;
	},
	
	//
	// Set a scroll to the menu so the person can still see it
	//
	set_menu_scroll: function(menu_obj, target_obj) {
		
		var window_size	= this.get_window_size();
		var bottom_pos	= this.lib.bottom(menu_obj);
		var top_pos		= this.lib.top(menu_obj);

		if(bottom_pos > window_size[1] && SET_OVERFLOW_SCROLL) {

			menu_obj.style.height = parseInt(parseInt(bottom_pos - top_pos) - parseInt(bottom_pos - window_size[1]) - 10) + 'px';
			
			if(!this.lib.is_ff) {  }
			if(!this.lib.is_opera) {
				menu_obj.style.overflow = 'auto';
			} else {
				
				// deal with opera's overflow:auto annoyance
				menu_obj.style.width = parseInt(this.lib.width(menu_obj) + 19) + 'px';

				var scroller = this.lib.getElementById('scroller_' + menu_obj.id);
				if(typeof(scroller) == 'undefined' || !scroller) {
					
					var menu_html		= menu_obj.innerHTML;
					menu_obj.innerHTML	= '<div id="scroller_' + menu_obj.id + '" style="overflow: auto;height: 100%;"></div>';
					scroller			= this.lib.getElementById('scroller_' + menu_obj.id);
					
					if(typeof(scroller) != 'undefined') {
						scroller.innerHTML = menu_html;
						this.set_highligh_rows(menu_obj, target_obj);
					}
				}
			}
		} else {
			// TODO: change the size if the scroll bar isn't needed
		}
	}
}

//
// Class factory
//
var k4MenuFactory = {
    createInstance: function() {
        return new k4Menu();
    }
}

//
// Nice wrapper function to bring it all together
//
var k4_menu = null;
var _this	= null;
function menu_init(link_id, menu_id, use_click) {
	if(typeof(use_click) == 'undefined') {
		use_click = true;
	}
	if(k4_menu == null) {
		k4_menu			= k4MenuFactory.createInstance();
	}
	k4_menu.init(link_id, menu_id, use_click);
}

*/

// global vars
var rows = ["alt1", "alt2"];
var afu = false;

function k4menu() {

	// public functions
	this.init = init;

	// private functions
	this._open = _open;
	this._close = _close;
	this._closec = _closec;
	this._set = _set;
	this._io = _io;
	this._cs = _cs;
	this._ss = _ss;
	this._ae = _ae;
	this._hrs = _hrs;
	this._pos = _pos;
	this._ws = _ws;
	this._sets = _sets;
	this._psnts = _psnts;
	
	// hooks
	this.clickRow = new Function();
	this.mouseupRow = new Function();
	this.mouseoverRow = new Function();
	this.mouseoutRow = new Function();
	
	// vars
	var d = new k4lib(); // k4 library
	var _om = false; // open menu
	var _hs = new Array(); // hidden selects
	var _t = this; // this
	
	// initialize a menu
	function init(li, mi, uc, t) {
		var lx = d.getElementById(li);
		var mx = d.getElementById(mi);
		var tx = false;
		if(lx && mx) {
			if(typeof(t) != 'undefined') {
				tx = d.getElementById(t);
			}
			_t._set(lx, mx, tx);
			_t._ae(lx, mx, uc, tx);
		}
	}
	// set menu positions
	function _set(lx, mx, tx) {
		if(lx.href && !afu) { lx.href = 'javascript:;'; } //lx.href = '#' + lx.id;
		d.forceCursor(lx);
		mx.style.display = 'none';
		mx.style.position = 'absolute';
		_t._psnts(lx, mx, tx);
		_t._hrs(mx, tx);
	}
	// set menu positions
	function _psnts(lx, mx, tx) {
		force_right = parseInt(d.left(lx) + d.width(mx)) >= document.body.clientWidth ? true : false;
		xx = tx ? tx : lx;
		mx.style.left = (force_right ? (d.left(xx) - (d.width(mx) - d.width(xx))) : d.left(xx)) + 'px';
		mx.style.top = parseInt(d.top(xx) + d.height(xx)) + 'px';
	}
	// is open function
	function _io(mx) {
		var ret = false;
		if(mx.style.display == 'block') {
			ret = true;
		}
		return ret;
	}
	// open the menu
	function _open(lx, mx, tx) {
		if(_om != mx) {
			_t._close(_om);
			mx.style.display = 'block';
			_t._psnts(lx, mx, tx);
			_t._cs(mx);
			_om = mx;
			_t._sets(mx, tx);
		} else {
			_t._close(_om);
		}
	}
	// close the menu
	function _close(mx) {
		if(typeof(mx) != 'undefined' && mx) {			
			mx.style.display = 'none';
			_om = false;
			_t._ss();
		}
	}
	// close on document click
	function _closec(e, lx, mx, tx) {
		if(_t._io(mx)) {
			var pos = _t._pos(e);
			ko = true;
			if(pos[1] > d.bottom(mx)) ko = false;
			if(pos[1] < d.top(lx)) ko = false;
			if(pos[0] < d.left((tx ? tx : lx))) ko = false; // TODO: include force_right thing
			if(pos[0] > d.right(lx)) ko = false;
			if(!ko) {
				_t._close(mx);
			}
		}
	}
	// hide <select>s
	function _cs(mx) {
		var ss = d.getElementsByTagName(document, 'select');
		if(ss) {
			for(var s = 0; s < d.sizeof(ss); s++) {
				if(typeof(ss[s]) != 'undefined' && d.overlaps(mx, ss[s])) {
					ss[s].style.display = 'none';
					d.array_push(_hs, ss[s]);
				}
			}
		}
	}
	// show <select>s
	function _ss() {
		if(_hs) {
			for(var s = 0; s < d.sizeof(_hs); s++) {
				_hs[s].style.display = 'block';
			}
			_hs = new Array();
		}
	}
	// attach event
	function _ae(lx, mx, uc, tx) {
		var close = function(e) { _t._closec(e, lx, mx, tx); }
		lx.onclick = function (e) { _t._open(lx, mx, tx); }
		lx.onmouseover = function(e) { 
			if(_om && (mx.id != _om.id) ) {
				_t._open(lx, mx, tx);
			}
		}
		if(document.addEventListener ) {
			document.addEventListener('click', close, false);
			if(!uc) {
				document.addEventListener('mousemove', close, false);
			}
		} else if(document.attachEvent ) {
			document.attachEvent('onclick', close);
			if(!uc) {
				document.attachEvent('onmousemove', close);
			}
		}
	}
	// highlight rows, and do other stuff
	function _hrs(mx, tx) {
		var rs = d.getElementsByTagName(mx, 'td');
		if(rs) {
			for(var i = 0; i < d.sizeof(rs); i++) {
				if(rs[i]) {
					rs[i].onmouseover = function() { 
						if(this.className == rows[0]) 
							this.className = rows[1]; 
						_t.mouseoverRow(this);
					}
					rs[i].onmouseout = function() { 
						if(this.className == rows[1]) 
							this.className = rows[0]; 
						_t.mouseoutRow(this);
					}
					rs[i].onclick = function() {
						if(typeof(tx) != 'undefined' && tx) {
							tx.innerHTML = this.innerHTML;
							_t.clickRow(this);
						}
						//_t._close(mx);
					}
					rs[i].onmouseup = function() { _t.mouseupRow(this); }
				}
			}
		}
	}
	// get positions
	function _pos(e) {
		if(!e) { e = window.event; }
		if(e) {
			if(typeof( e.pageX ) == 'number') {
				posX = e.pageX; 
				posY = e.pageY;
			} else {
				if(typeof( e.clientX ) == 'number') {
					posX = e.clientX; 
					posY = e.clientY;
					if(document.body && !( window.opera || window.debug || navigator.vendor == 'KDE')) {
						if( typeof( document.body.scrollTop ) == 'number') {
							posX += document.body.scrollLeft; 
							posY += document.body.scrollTop;
						}
					}
					if(document.documentElement && !( window.opera || window.debug || navigator.vendor == 'KDE')) {
						if( typeof( document.documentElement.scrollTop ) == 'number') {
							posX += document.documentElement.scrollLeft; 
							posY += document.documentElement.scrollTop;
						}
					}
				}
			}
		}
		var pos = new Array(posX, posY);
		return pos;
	}
	// get the window size
	function _ws() {
		var x = y = 0;
		if( typeof( window.innerWidth ) == 'number' ) {
			x = window.innerWidth;
			y = window.innerHeight;
		} else if( document.documentElement && ( document.documentElement.clientWidth || document.documentElement.clientHeight ) ) {
			x = document.documentElement.clientWidth;
			y = document.documentElement.clientHeight;
		} else if( document.body && ( document.body.clientWidth || document.body.clientHeight ) ) {
			x = document.body.clientWidth;
			y = document.body.clientHeight;
		}
		var ws = new Array(x, y);
		return ws;
	}
	// set a scroll to the menu
	function _sets(mx, tx) {
		var ws = _t._ws();
		var b = d.bottom(mx);
		var t = d.top(mx);
		var es = d.getElementsByTagName(mx, 'table');
		if(b > ws[1]) {
			mx.style.height = parseInt(parseInt(b - t) - parseInt(b - ws[1]) - 10) + 'px';
			if(!d.is_ff) {  } // scroll bar width
			if(!d.is_opera) {
				mx.style.overflow = 'auto';
			} else {
				// deal with opera's overflow:auto annoyance
				mx.style.width = parseInt(d.width(mx) + 19) + 'px';
				var scroller = d.getElementById('scroller_' + mx.id);
				if(typeof(scroller) == 'undefined' || !scroller) {
					var html = mx.innerHTML;
					mx.innerHTML = '<div id="scroller_' + mx.id + '" style="overflow: auto;height: 100%;"></div>';
					scroller = d.getElementById('scroller_' + mx.id);
					if(typeof(scroller) != 'undefined') {
						scroller.innerHTML = html;
						_t._hrs(mx, tx);
					}
				}
			}
		} else {
			// TODO: change the size if the scroll bar isn't needed
		}
	}
}

var k4_menu=new k4menu();
function menu_init(link_id, menu_id, use_click) {
	if(typeof(use_click) == 'undefined') {
		use_click=true;
	}
	k4_menu.init(link_id, menu_id, use_click);
}
// */
