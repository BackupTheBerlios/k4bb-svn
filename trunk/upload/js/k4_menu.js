/**
 * k4 Bulletin Board, k4menu JavaScript class
 * Copyright (c) 2005, Peter Goodman
 * @author Peter Goodman
 * @version $Id$
 * @package k42
 */

var rows = ["alt1", "alt2"];
var afu = false;

function k4menu() {
	this.init = init;
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
	this.switchTargetValue = new Function(); // hookable function
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
		if(lx.href && !afu) { lx.href = '#'; }
		d.forceCursor(lx);
		mx.style.display = 'none';
		mx.style.position = 'absolute';
		force_right = parseInt(d.left(lx) + d.width(mx)) >= document.body.clientWidth ? true : false;
		xx = tx ? tx : lx;
		mx.style.left = (force_right ? (d.left(xx) - (d.width(mx) - d.width(xx))) : d.left(xx)) + 'px';
		mx.style.top = parseInt(d.top(xx) + d.height(xx)) + 'px';
		_t._hrs(mx, tx);
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
	function _open(mx, tx) {
		if(_om != mx) {
			_t._close(_om);
			mx.style.display = 'block';
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
			if(pos[0] < d.left((tx ? tx : lx))) ko = false;
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
		lx.onclick = function (e) { _t._open(mx, tx); }
		lx.onmouseover = function(e) { 
			if(_om && (mx.id != _om.id)) {
				_t._open(mx, tx);
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
	// highlight rows
	function _hrs(mx, tx) {
		var rs = d.getElementsByTagName(mx, 'td');
		if(rs) {
			for(var i = 0; i < d.sizeof(rs); i++) {
				if(rs[i]) {
					rs[i].onmouseover = function() { if(this.className == rows[0]) this.className = rows[1]; }
					rs[i].onmouseout = function() { if(this.className == rows[1]) this.className = rows[0]; }
					rs[i].onclick = function() {
						if(typeof(tx) != 'undefined' && tx) {
							tx.innerHTML = this.innerHTML;
							_t.switchTargetValue(this.innerHTML);
						}
						//_t._close(mx);
					}
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
			if(!d.is_ff) { mx.style.width = parseInt(d.width(mx) + 17) + 'px'; } // scroll bar width
			if(!d.is_opera) {
				mx.style.overflow = 'auto';
			} else {
				// deal with opera's overflow:auto annoyance
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
		}
	}
}

var k4_menu=new k4menu();
function menu_init(link_id, menu_id, use_click) {
	if(typeof(use_click) == 'undefined') {
		use_click = true;
	}
	k4_menu.init(link_id, menu_id, use_click);
}