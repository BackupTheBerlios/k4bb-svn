/**
 * k4 Bulletin Board, k4 Effects objects
 * Copyright (c) 2005, Peter Goodman
 * Licensed under the LGPL license
 * http://www.gnu.org/copyleft/lesser.html
 * @author Peter Goodman
 * @version $Id$
 * @package k4bb
 */

//
// k4SlideResizer filter constructor
//
function k4SlideResizer() { 
	return this;
}

// object definition
k4SlideResizer.prototype = {
	
	lib:			new k4lib(),
	obj:			new Object(),
	
	onSliding:		new Function(),
	onFinished:		new Function(),
	
	x_increment:	0,
	y_increment:	0,
	
	x_dir:			0,
	y_dir:			0,

	width:			0,
	height:			0,
	
	cum_width:		0,
	cum_height:		0,

	slide_steps:	0,
	slide_step:		0,
	slide_timer:	false,
	
	NOSLIDE:		0,
	OPEN:			1,
	CLOSE:			2,
		
	//
	// Start the slide resizer
	//
	Init: function(obj_id, x_dir, y_dir, slide_steps) {
		
		// get the object
		this.obj				= this.lib.getElementById(obj_id);

		if(typeof(this.obj) != 'undefined' && this.obj) {
			
			// make sure to set the object as visible
			this.obj.style.display = 'block';
			
			// get some measurements
			this.x_increment = this.width	= this.lib.width(this.obj);
			this.y_increment = this.height	= this.lib.height(this.obj);
			this.x_dir						= x_dir;
			this.y_dir						= y_dir;
			this.slide_steps				= slide_steps;
			this.slide_step					= 1;
						
			// figure out what to increment the slider by
			if(this.x_dir > this.NOSLIDE) {
				this.x_increment = Math.ceil(this.x_increment / this.slide_steps);
			}
			if(this.y_dir > this.NOSLIDE) {
				this.y_increment =  Math.ceil(this.y_increment / this.slide_steps);
			}
			
			this.doSlide();
		}
	},
	
	//
	// Begin the slider
	//
	doSlide: function() {
		
		if(this.slide_step <= this.slide_steps && this.slide_step > 0) {
			
			// increase the cumulative height for the slider
			if(this.y_dir != this.NOSLIDE) this.cum_height	+= this.y_increment;
			if(this.x_dir != this.NOSLIDE) this.cum_width	+= this.x_increment;
			
			// come up with a cumulative hight to pass to the slider
			cum_height			= this.toZero( (this.y_dir == this.NOSLIDE) ? this.height : ( this.slide_step == 0 ? 0 : this.cum_height ) );
			cum_width			= this.toZero( (this.x_dir == this.NOSLIDE) ? this.width : ( this.slide_step == 0 ? 0 : this.cum_width ) );
			
			if(cum_height <= this.height && cum_width <= this.width) {						
				
				// set the style using the clip method
				if(this.obj.style.position == 'absolute') {

					// determine what values to pass to the clip: rect();
					clip_top				= '0px';
					clip_right				= (this.x_dir == this.NOSLIDE) ? this.width + 'px' : (this.x_dir == this.OPEN ? cum_width + 'px' : (this.width - cum_width) + 'px');
					clip_bottom				= (this.y_dir == this.NOSLIDE) ? this.height + 'px' : (this.y_dir == this.OPEN ? cum_height + 'px' : (this.height - cum_height) + 'px');
					clip_left				= '0px';
					
					this.obj.style.clip		= 'rect(' + clip_top + ', ' + clip_right + ', ' + clip_bottom + ', ' + clip_left + ')';
					
				// set the style using the overflow method
				} else {
					this.obj.style.overflow = 'hidden';
					this.obj.style.width	= (this.x_dir == this.NOSLIDE) ? this.width + 'px' : ((this.x_dir == this.OPEN) ? cum_width + 'px' : this.toZero(this.width - cum_width) + 'px');
					this.obj.style.height	= (this.y_dir == this.NOSLIDE) ? this.height + 'px' : ((this.y_dir == this.OPEN) ? cum_height + 'px' : this.toZero(this.height - cum_height) + 'px');
				}
				
				// execute one of the hooked functions
				this.onSliding();

				// increment the slide step
				this.slide_step++;
				
				// do this all over again in 50 miliseconds
				this.slide_timer = setTimeout( (function(k4_effect){return function(){k4_effect.doSlide();}})(this), 0);
				

			// if we're done slide/resizing
			} else {
				this.finishSlider();
			}
		
		// if we're done slide/resizing
		} else {
			this.finishSlider();
		}		
	},
	
	//
	// Finish off the sliding process
	//
	finishSlider: function() {
		
		// stop the slide/resize timer
		if(this.slide_timer) {
			clearTimeout(this.slide_timer);
		}

		// execute one of the hooked functions
		this.onFinished();
		
		// make sure to fix it
		if(this.obj.style.position != 'absolute') {
			
			if(this.x_dir == this.CLOSE || this.y_dir == this.CLOSE) {
				this.obj.style.display = 'none';
			}
			
			this.obj.style.width	= this.width + 'px';
			this.obj.style.height	= this.height + 'px';
		}
	},

	//
	// If any value is below zero, make it zero
	//
	toZero: function(the_int) {
		ret = parseInt(the_int);
		if(ret < 0) {
			ret = 0;
		}
		return ret;
	}
}

//
// Manage Mouse-over things
//
function k4ManageHoverCell() { }

//
// k4ManageHoverCell object constructor
//
k4ManageHoverCell.prototype = {
	
	lib: new k4lib(),

	//
	// Highlight the rows
	//
	highlight: function(container_obj, hover_over, hover_off) {
		var table_cells = this.lib.getElementsByTagName(container_obj, 'td');
		
		if(table_cells) {

			for(var i = 0; i < this.lib.sizeof(table_cells); i++ ) {
				
				if(typeof(table_cells[i].className) != 'undefined' 
							&& table_cells[i].className == hover_off) {
					
					AttachEvent(table_cells[i],'mouseover',(function(){this.className=hover_over;}),false);
					AttachEvent(table_cells[i],'mouseout',(function(){this.className=hover_off;}),false);
				}
			}
		}
	}
}

//
// k4PopupAndDrag object constructor
//
function k4ManageDragElements() { }

// object definition
k4ManageDragElements.prototype = {
	
	drag_objs:	new Array(),
	lib:		new k4lib(),
		
	addDragObj: function(handle_id, root_id) {
		var handle_obj	= this.lib.getElementById(handle_id);
		var root_obj	= this.lib.getElementById(root_id);

		if(handle_obj && root_obj) {
			this.lib.array_push(this.drag_objs, new Array(handle_obj, root_obj));
			
		}
	},
	
	Init: function() {
		for(var i = 0; i < this.lib.sizeof(this.drag_objs); i++ ) {
			Drag.init(this.drag_objs[i][0], this.drag_objs[i][1]);
		}
	}
}

//
// Object factory
//
var k4SlideResizerFactory = {
    createInstance: function() {
        return new k4SlideResizer();
    }
}
var k4ManageHoverCellFactory = {
	createInstance: function() {
		return new k4ManageHoverCell();
	}
}
var k4ManageDragElementsFactory = {
    createInstance: function() {
        return new k4ManageDragElements();
    }
}

/**************************************************
 * dom-drag.js
 * 09.25.2001
 * www.youngpup.net
 **************************************************
 * 10.28.2001 - fixed minor bug where events
 * sometimes fired off the handle, not the root.
 **************************************************/

var Drag = {

	obj : null,

	init : function(o, oRoot, minX, maxX, minY, maxY, bSwapHorzRef, bSwapVertRef, fXMapper, fYMapper)
	{
		o.onmousedown	= Drag.start;

		o.hmode			= bSwapHorzRef ? false : true ;
		o.vmode			= bSwapVertRef ? false : true ;

		o.root = oRoot && oRoot != null ? oRoot : o ;

		if (o.hmode  && isNaN(parseInt(o.root.style.left  ))) o.root.style.left   = "0px";
		if (o.vmode  && isNaN(parseInt(o.root.style.top   ))) o.root.style.top    = "0px";
		if (!o.hmode && isNaN(parseInt(o.root.style.right ))) o.root.style.right  = "0px";
		if (!o.vmode && isNaN(parseInt(o.root.style.bottom))) o.root.style.bottom = "0px";

		o.minX	= typeof minX != 'undefined' ? minX : null;
		o.minY	= typeof minY != 'undefined' ? minY : null;
		o.maxX	= typeof maxX != 'undefined' ? maxX : null;
		o.maxY	= typeof maxY != 'undefined' ? maxY : null;

		o.xMapper = fXMapper ? fXMapper : null;
		o.yMapper = fYMapper ? fYMapper : null;

		o.root.onDragStart	= new Function();
		o.root.onDragEnd	= new Function();
		o.root.onDrag		= new Function();
	},

	start : function(e)
	{
		var o = Drag.obj = this;
		e = Drag.fixE(e);
		var y = parseInt(o.vmode ? o.root.style.top  : o.root.style.bottom);
		var x = parseInt(o.hmode ? o.root.style.left : o.root.style.right );
		o.root.onDragStart(x, y);

		o.lastMouseX	= e.clientX;
		o.lastMouseY	= e.clientY;

		if (o.hmode) {
			if (o.minX != null)	o.minMouseX	= e.clientX - x + o.minX;
			if (o.maxX != null)	o.maxMouseX	= o.minMouseX + o.maxX - o.minX;
		} else {
			if (o.minX != null) o.maxMouseX = -o.minX + e.clientX + x;
			if (o.maxX != null) o.minMouseX = -o.maxX + e.clientX + x;
		}

		if (o.vmode) {
			if (o.minY != null)	o.minMouseY	= e.clientY - y + o.minY;
			if (o.maxY != null)	o.maxMouseY	= o.minMouseY + o.maxY - o.minY;
		} else {
			if (o.minY != null) o.maxMouseY = -o.minY + e.clientY + y;
			if (o.maxY != null) o.minMouseY = -o.maxY + e.clientY + y;
		}

		document.onmousemove	= Drag.drag;
		document.onmouseup		= Drag.end;

		return false;
	},

	drag : function(e)
	{
		e = Drag.fixE(e);
		var o = Drag.obj;

		var ey	= e.clientY;
		var ex	= e.clientX;
		var y = parseInt(o.vmode ? o.root.style.top  : o.root.style.bottom);
		var x = parseInt(o.hmode ? o.root.style.left : o.root.style.right );
		var nx, ny;

		if (o.minX != null) ex = o.hmode ? Math.max(ex, o.minMouseX) : Math.min(ex, o.maxMouseX);
		if (o.maxX != null) ex = o.hmode ? Math.min(ex, o.maxMouseX) : Math.max(ex, o.minMouseX);
		if (o.minY != null) ey = o.vmode ? Math.max(ey, o.minMouseY) : Math.min(ey, o.maxMouseY);
		if (o.maxY != null) ey = o.vmode ? Math.min(ey, o.maxMouseY) : Math.max(ey, o.minMouseY);

		nx = x + ((ex - o.lastMouseX) * (o.hmode ? 1 : -1));
		ny = y + ((ey - o.lastMouseY) * (o.vmode ? 1 : -1));

		if (o.xMapper)		nx = o.xMapper(y)
		else if (o.yMapper)	ny = o.yMapper(x)

		Drag.obj.root.style[o.hmode ? "left" : "right"] = nx + "px";
		Drag.obj.root.style[o.vmode ? "top" : "bottom"] = ny + "px";
		Drag.obj.lastMouseX	= ex;
		Drag.obj.lastMouseY	= ey;

		Drag.obj.root.onDrag(nx, ny);
		return false;
	},

	end : function()
	{
		document.onmousemove = null;
		document.onmouseup   = null;
		Drag.obj.root.onDragEnd(	parseInt(Drag.obj.root.style[Drag.obj.hmode ? "left" : "right"]), 
									parseInt(Drag.obj.root.style[Drag.obj.vmode ? "top" : "bottom"]));
		Drag.obj = null;
	},

	fixE : function(e)
	{
		if (typeof e == 'undefined') e = window.event;
		if (typeof e.layerX == 'undefined') e.layerX = e.offsetX;
		if (typeof e.layerY == 'undefined') e.layerY = e.offsetY;
		return e;
	}
};