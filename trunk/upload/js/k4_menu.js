/**
* k4 Bulletin Board, k4_menu.js
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

var d						= new k4lib()
var open_menu				= false;
var hidden_selects			= new Array()
var tempX					= 0;
var tempY					= 0;
var use_click				= true; // should the menus be opened by clicking them, or hovering?
var allow_follow_urls		= false; // if the link is an <a> tag, allow it to follow the url?


/* Initialize a menu object */
function menu_init(link_id, menu_id, c_p) {
	
	var menu				= d.getElementById(menu_id);
	var link				= d.getElementById(link_id);
	
	if(typeof c_p != 'undefined') {
		menu.c_p = c_p;
	}

	if(menu && link) {
		
		//menu_positions(menu, link);

		menu.style.display	= 'none';
		menu.style.zIndex	= 99;
		link.unselectable	= true;
		
		/* Add the link cursor */
		d.forceCursor(link);
		
		/* Onclick function for the current link */
		link.onclick = function() {
			
			if(link.href && !allow_follow_urls) {
				link.href = '#' + link.id;
			}

			// enforce the link id
			menu.link_id		= this.id;

			/* Open or close the menu */
			if(menu.style.display == 'none') {
				openmenu(menu, this);
			} else {
				closemenu(menu);
			}
			
			if(allow_follow_urls && !use_click) {
				document.location.href = link.href;
			}

			return false;
		}

		/* Onmouseover for a link object */
		link.onmouseover = function() {
			
			if(typeof open_menu != 'undefined' && open_menu) {
				
				if(open_menu.link_id != this.id) {
					
					/* Close the current open menu */
					closemenu(open_menu);

					/* Open this new menu */
					openmenu(menu, this);
				}
			}

			if(typeof use_click == 'undefined' || !use_click) {
				
				// enforce the link id
				menu.link_id		= this.id;

				/* Open or close the menu */
				if(menu.style.display == 'none') {
					openmenu(menu, this);
				} else {
					if(menu.link_id != open_menu.link_id) {
						closemenu(menu);
					}
				}
			}
		}
		
		if(typeof use_click == 'undefined' || !use_click) {
			link.onmouseout = function(event) {
				hidemenuclick(event);
			}
			menu.onmouseout = function(event) {
				hidemenuclick(event);
			}
		}
		
		highlightmenurows(menu);
	}
}


/* Open a menu */
function openmenu(menu, link) {
	
	if(menu && link) {
		
		/* If the menu is currently closed */
		if(menu.style.display == 'none') {
			
			menu.style.display	= 'block';
			menu_positions(menu, link);

			/* Loop through all of the <select>'s on a page */
			selects				= d.getElementsByTagName(document, 'select');
			
			if(selects) {
				for (var s = 0; s < d.sizeof(selects); s++) {
					
					if(selects[s]) {

						/* If the menu overlaps the select menu, hide the select */
						if (d.overlaps(menu, selects[s])) {
							
							selects[s].style.display = 'none';
							d.array_push(hidden_selects, selects[s]);
						}
					}
				}
			}
			
			open_menu			= menu;
			
		/* Assume that the menu is open */
		} else {
			close_menu(menu);
		}
	}

	return true;
}


/* Close a menu */
function closemenu(menu) {
	
	if(menu && typeof(menu) == 'object') {
		menu.style.display		= 'none';
		open_menu				= false;
		
		/* Show any hidden select menus */
		if(d.sizeof(hidden_selects) > 0) {
			
			for(var i = 0; i < d.sizeof(hidden_selects); i++) {
				
				hidden_selects[i].style.display = 'block';
			}

			hidden_selects		= new Array()
		}
	}
}


/* show/hide a menu when someone clicks the page */

//if(!use_click) {
//	AttachEvent(document,'mousemove',hidemenuclick,false);
//	//document.onmousemove	= hidemenuclick;
//}
//AttachEvent(document,'click',hidemenuclick,false);
////document.onclick		= hidemenuclick;

function hidemenuclick(e) {
	
	var posX = 0, posY = 0;
	if(!e) { 
		e = window.event; 
	}

	if(e) {
		if( typeof( e.pageX ) == 'number' ) {
			posX = e.pageX; 
			posY = e.pageY;
		} else {
			if( typeof( e.clientX ) == 'number' ) {
				posX = e.clientX; 
				posY = e.clientY;
				if( document.body && !( window.opera || window.debug || navigator.vendor == 'KDE' ) ) {
					if( typeof( document.body.scrollTop ) == 'number' ) {
						posX += document.body.scrollLeft; 
						posY += document.body.scrollTop;
					}
				}
				if( document.documentElement && !( window.opera || window.debug || navigator.vendor == 'KDE' ) ) {
					if( typeof( document.documentElement.scrollTop ) == 'number' ) {
						posX += document.documentElement.scrollLeft; 
						posY += document.documentElement.scrollTop;
					}
				}
			}
		}
	}

//	var posx = 0;
//	var posy = 0;
//	if (!e) var e = window.event;
//	if (e.pageX || e.pageY) {
//		posx = e.pageX;
//		posy = e.pageY;
//	} else if (e.clientX || e.clientY) {
//		posx = e.clientX + document.body.scrollLeft;
//		posy = e.clientY + document.body.scrollTop;
//	}
	/* Deal with the menu */
	if(open_menu) {
		
		open_menulink	= d.getElementById(open_menu.link_id);
		
		keep_open		= true;
		
		if(posY > d.bottom(open_menu))	keep_open = false;
		if(posY < d.top(open_menulink)) keep_open = false;
		if(posX < d.left(open_menu))	keep_open = false;
		if(posX > d.right(open_menu))	keep_open = false;

		if(!keep_open && open_menu) {
			open_menu.style.display = 'none';
			open_menu				= false;
		}
		
	}
}


/* Highlight the rows (tr's) of the menu tables */
function highlightmenurows(menu) {
	if(menu) {
		
		var rows		= d.getElementsByTagName(menu, 'td');
		
		if(rows) {

			for(var i = 0; i < d.sizeof(rows); i++) {
				
				if(rows[i].className == 'alt1') {

					/* Apply styling to each of these rows */
					rows[i].onmouseover = function() {
						this.className = 'alt2';
					}

					rows[i].onmouseout = function() {
						this.className = 'alt1';
					}

					rows[i].onclick = function(e) {
						target	= d.get_event_target(e);
						
						// only close the menu if the target element is NOT an input field
						if((target.type != 'text' && target.type != 'radio') || typeof target.type == 'undefined') {
							menu.style.display = 'none';
							open_menu = false;
						}
					}

				}
			}
		}
	}
}


/* Apply menu positioning */
function menu_positions(menu, link) {
	
	if(menu && link) {
		
		//if(parseInt(d.width(menu) - document.body.clientWidth) == 0) {
		//	menu.style.position	= 'absolute';
		//}
		
		menu.style.position	= 'absolute';
		

		/* set some menu position stuff */
		force_right			= parseInt(d.left(link) + d.width(menu)) >= document.body.clientWidth ? true : false;
		
		menu.style.left		= (force_right ? (d.left(link) - (d.width(menu) - d.width(link))) : d.left(link)) + 'px';
		menu.style.top		= parseInt(d.top(link) + d.height(link)) + 'px';

		if(typeof menu.c_p != 'undefined') {
			for(var i = 0; i < d.sizeof(menu.c_p); i++) {
				eval("menu.style." + menu.c_p[i] + "='" + menu.c_p[i+1] + "';");
				i++;
			}
		}
	}
}

/**
 * Deal with the events
 */

if(document.addEventListener ) {
	
	document.addEventListener('click', hidemenuclick, false);
	
	if(!use_click)
		document.addEventListener('mousemove', hidemenuclick, false);

} else if(document.attachEvent ) {
	
	document.attachEvent('onclick', hidemenuclick);
	
	if(!use_click)
		document.attachEvent('onmousemove', hidemenuclick);

}