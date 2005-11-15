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

function k4MenuSystem() {
	
	// public functions
	this.createMenu				= createMenu;
	this.getMenu				= getMenu;
	this.openMenu				= openMenu;
	this.closeMenu				= closeMenu;
	this.menuIsOpen				= menuIsOpen;
	this.setMenuStyles			= setMenuStyles;
	this.setLinkStateHandler	= setLinkStateHandler;
	this.changeLink				= changeLink;
	
	// private variables
	var _menus					= { };
	var _open_menu				= false;
	var _use_click				= true;
	var _allow_follow_urls		= false;
	var _this					= this;
	
	// public variables
	var d						= new k4lib();

	/**
	 * Function to create a new menu
	 */
	function createMenu(link_id, menu_id) {
		
		var link_obj		= d.getElementById(link_id);
		var menu_obj		= d.getElementById(menu_id);

		if(link_obj && menu_obj) {
			_menus[link_id]	= menu_obj;
			setLinkStateHandler(link_id);
			changeLink(link_obj);
		}
	}

	/**
	 * Function to get a menu
	 */
	function getMenu(link_id) {
		var link_obj	= d.getElementById(link_id);
		var menu_obj	= false;
		
		if(typeof(_menus[link_id]) != 'undefined') {
			menu_obj		= _menus[link_id];
		}

		return menu_obj;
	}

	/**
	 * Function to open a menu
	 */
	function openMenu(link_id) {
		var menu_obj				= getMenu(link_id);
		if(menu_obj) {
			if(_open_menu != false) {
				closeMenu(_open_menu);
			}
			setMenuStyles(menu_obj);
			menu_obj.style.display	= 'block';
			
			_open_menu				= menu_obj;
		}
	}

	/**
	 * Function to close a menu
	 */
	function closeMenu(menu_obj) {
		if(menu_obj && menuIsOpen(menu_obj)) {
			menu_obj.style.display	= 'none';
			_open_menu				= false;
		}
	}

	/**
	 * Function to tell if a specific menu is open or not
	 */
	function menuIsOpen(menu_obj) {
		var is_open = false;
		if(menu_obj && menu_obj == _open_menu) {
			is_open = true;
		}
		return is_open;
	}

	/**
	 * Function to set the menu styles: position, z-index, etc
	 */
	function setMenuStyles(menu_obj) {
		
		var link_id		= d.array_key(_menus, menu_obj);
		var link_obj	= d.getElementById(link_id);
		
		if(link_obj && menu_obj) {
			
			menu_obj.style.position	= 'absolute';
			menu_obj.style.display	= 'none';
			menu_obj.style.zIndex	= 99;
			link_obj.unselectable	= true;
			
			if_force_right			= parseInt(d.left(link) + d.width(menu)) >= document.body.clientWidth ? true : false;
			
			menu_obj.style.left		= (if_force_right ? (d.left(link) - (d.width(menu) - d.width(link))) : d.left(link)) + 'px';
			menu_obj.style.top		= parseInt(d.top(link) + d.height(link)) + 'px';
		}
		return true;
	}

	/**
	 * Add an event handler to the onclick event of a link
	 */
	function setLinkStateHandler(link_id) {
		var link_obj	= d.getElementById(link_id);
		if(_use_click) {
			link_obj.onclick = function() { openMenu(link_id); }
		} else {
			link_obj.onmouseover = function() { openMenu(link_id); }
		}
		return true;
	}

	/**
	 * Function to change the link object
	 */
	function changeLink(link_obj) {
		if(link_obj.href && !_allow_follow_urls) {
			link_obj.href = '#' + link_obj.id;
		}
		return true;
	}
}

function menu_init(link_id, menu_id) {
	var menu = new k4MenuSystem();

	menu.createMenu(link_id, menu_id);

	return true;
}