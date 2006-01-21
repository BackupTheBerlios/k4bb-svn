/**
 * k4 Bulletin Board, k4Menu JavaScript object and related objects
 * Copyright (c) 2005, Peter Goodman
 * Licensed under the LGPL license
 * http://www.gnu.org/copyleft/lesser.html
 * @author Peter Goodman
 * @version $Id$
 * @package k4bb
 */

var ALL_MENUS		= new Array();
var open_menu		= false;
var row_highlights	= new Array('alt1', 'alt2');

//
// k4Menu constructor
//
function k4Menu() { }

// k4Menu object definition
k4Menu.prototype = {
	
	lib:		new k4lib(),
	
	//
	// Initialize a menu object
	//
	Init: function(link_id, menu_id) {
		
		// get some vars
		var link_obj	= this.lib.getElementById(link_id);
		var menu_obj	= this.lib.getElementById(menu_id);
		
		if(menu_obj && link_obj) {	
			var actions_obj = k4MenuActionsFactory.createInstance();
			var filters_obj = k4MenuFiltersFactory.createInstance();
					
			// set some stuff
			menu_obj.style.display	= 'none';
			menu_obj.style.position = 'absolute';
			menu_obj.link_id		= link_id;
			this.lib.forceCursor(link_obj);
			
			// put this menu into an array of all of our menus
			this.lib.array_push(ALL_MENUS, menu_obj);
			
			// apply filters to the menu
			filters_obj.stopLinkRedirect(link_obj);
			filters_obj.highlightMenuRows(menu_obj);
			filters_obj.slideResizeMenu(actions_obj, menu_obj);

			// get the <body> tag
			var body_element = this.lib.getElementsByTagName(document, 'body');
			
			// attach the events
			AttachEvent(link_obj,'click',(function() {actions_obj.openMenu(link_obj, menu_obj); }),false);
			AttachEvent(link_obj,'mouseover',(function() { actions_obj.openMenuIfOneIsOpen(link_obj, menu_obj); }),false);
			AttachEvent(body_element[0],'click',(function(e) { var open_menu_find = actions_obj.getOpenMenu();if(actions_obj.shouldCloseMenu(e, open_menu_find)) { actions_obj.closeMenu(open_menu_find); } }),false);
			AttachEvent(document,'click',(function(e) { var open_menu_find = actions_obj.getOpenMenu();if(actions_obj.shouldCloseMenu(e, open_menu_find)) { actions_obj.closeMenu(open_menu_find); } }),false);
		}
	}
}

//
// k4MenuActions constructor
//
function k4MenuActions(menu_obj) { }

// k4MenuActions definitions
k4MenuActions.prototype = {
	
	lib:		new k4lib(),
	onOpening:	new Function(),
	onClosing:	new Function(),
	
	//
	// Open a menu
	//
	openMenu: function(link_obj, menu_obj) {
		if(!this.menuIsOpen(menu_obj)) {

			var open_menu_find = this.getOpenMenu();
			
			if(open_menu_find && open_menu_find.id != menu_obj.id) {
				this.closeMenu(open_menu_find);
			}
			
			// open the menu
			open_menu				= menu_obj;
			menu_obj.style.display	= 'block';
			
			k4MenuPositionsFactory.createInstance().setMenuXY(link_obj, menu_obj);
			
			this.onOpening(menu_obj);

		} else {
			this.closeMenu(menu_obj);
		}
	},

	//
	// Open a menu only if another menu is opened
	// this is for traversing between one clicked menu
	// and one hovered menu
	//
	openMenuIfOneIsOpen: function(link_obj, menu_obj) {
		var open_menu_find = this.getOpenMenu();
		if(typeof(open_menu_find) != 'undefined' && open_menu_find) {

			if(open_menu_find.id != menu_obj.id) {
				this.closeMenu(open_menu_find);
				this.openMenu(link_obj, menu_obj);
			}
		}
	},

	//
	// Close a menu
	//
	closeMenu: function(menu_obj) {
		
		if(this.menuIsOpen(menu_obj)) {
			menu_obj.style.display	= 'none';
			open_menu				= false;
			
			this.onClosing(menu_obj);
		}
	},

	//
	// Is this menu open?
	//
	menuIsOpen: function(menu_obj) {
		is_open = false;

		if(menu_obj) {
			if(menu_obj.style.display == 'block') {
				is_open  = true;
			}
		}
		return is_open;
	},
	
	//
	// Get the current open menu
	//
	getOpenMenu: function() {
		if(typeof(open_menu) == 'undefined' || !open_menu) {
			var open_menu_find = false;
			for(var i = 0; i < this.lib.sizeof(ALL_MENUS); i++ ) {
				if(this.menuIsOpen(ALL_MENUS[i])) {
					open_menu_find = ALL_MENUS[i];
					break;
				}
			}
		} else {
			open_menu_find = open_menu;
		}
		return open_menu_find;
	},
	
	//
	// Should this menu be closed?
	//
	shouldCloseMenu: function(e, menu_obj) {
		var link_obj	= this.lib.getElementById(menu_obj.link_id);
		var misc_obj	= k4MenuMiscFactory.createInstance();
		var positions	= misc_obj.menuPositions(e); // x0 y1
		var should_close= false;
		var event_target= this.lib.get_event_target(e);
		
		if(link_obj && menu_obj && event_target) {			

			if(positions[0] < this.lib.left(menu_obj)) should_close = true;
			if(positions[0] > this.lib.right(menu_obj)) should_close = true;
			if(positions[1] < this.lib.top(menu_obj)) should_close = true;
			if(positions[1] > this.lib.bottom(menu_obj)) should_close = true;
			if(event_target.id == link_obj.id || event_target.parentNode.id == link_obj.id) { should_close = false; }
			if(event_target.id == menu_obj.id) { should_close = false; }
		}
		
		return should_close;
	}
}

//
// k4MenuPositions constructor
//
function k4MenuPositions() { }

// k4MenuPositions object definition
k4MenuPositions.prototype = {
	
	lib: new k4lib(),
	
	//
	// Move the menu in to position under the link
	//
	setMenuXY: function(link_obj, menu_obj) {
		
		if(link_obj && menu_obj) {
			
			var link_obj_left		= this.lib.left(link_obj);

			menu_obj.style.position	= 'absolute';
			menu_obj.style.top		= this.lib.bottom(link_obj) + 'px';
			menu_obj.style.left		= link_obj_left + 'px';
			
			if( this.lib.right(menu_obj) > document.body.clientWidth) {
				menu_obj.style.left	= (( link_obj_left - this.lib.width(menu_obj) ) + this.lib.width(link_obj)) + 'px';
			}
		}
	}
}

//
// k4MenuFilters constructor
//
function k4MenuFilters() { }

// k4MenuFilters object definition
k4MenuFilters.prototype = {
	
	lib: new k4lib(),
	
	//
	// Highlight the table cells (so hope that each menu has only 1 column)
	//
	highlightMenuRows: function(menu_obj) {
		k4ManageHoverCellFactory.createInstance().highlight(menu_obj, row_highlights[1], row_highlights[0]);
	},
	
	//
	// Open the menu up in a fancy way
	//
	slideResizeMenu: function(actions_obj) {
		actions_obj.onOpening = function(menu_obj) {
			//k4SlideResizerFactory.createInstance().Init(menu_obj.id, 1, 1, 10);
		}
	},
	
	//
	// Stop the redirection of link urls
	//
	stopLinkRedirect: function(link_obj) {
		if(typeof(link_obj) != 'undefined' && link_obj) {
			if(typeof(link_obj.href) != 'undefined') { 
				link_obj.href = 'javascript:;'; //link_obj.href = '#' + link_obj.id;
			}
		}
	}
}

//
// k4MenuMisc constructor
//
function k4MenuMisc() { }

// k4MenuMisc object definition
k4MenuMisc.prototype = {
	
	lib: new k4lib(),

	//
	// get mouse x and y positions
	//
	menuPositions: function(e) {
		if(!e) { 
			e = window.event; 
		}
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
		return new Array(posX, posY);
	}
}

//
// Class factories
//
var k4MenuFactory = {
    createInstance: function() {
        return new k4Menu();
    }
}
var k4MenuActionsFactory = {
    createInstance: function() {
        return new k4MenuActions();
    }
}
var k4MenuPositionsFactory = {
    createInstance: function() {
        return new k4MenuPositions();
    }
}
var k4MenuFiltersFactory = {
    createInstance: function() {
        return new k4MenuFilters();
    }
}
var k4MenuMiscFactory = {
    createInstance: function() {
        return new k4MenuMisc();
    }
}

//
// Function to bring it all together nicely
//
function menu_init(link_id, menu_id) {
	k4MenuFactory.createInstance().Init(link_id, menu_id);
}