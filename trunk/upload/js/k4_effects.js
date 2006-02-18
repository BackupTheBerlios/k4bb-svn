/**
 * k4 Bulletin Board, k4 Effects objects
 * Copyright (c) 2005, Peter Goodman
 * Licensed under the LGPL license
 * http://www.gnu.org/copyleft/lesser.html
 * @author Peter Goodman
 * @version $Id$
 * @package k4bb
 */

if(typeof(debug) == 'undefined') { function debug(str) { return true; } }

//
// k4SlideResizer filter constructor
//
function k4SlideResizer() { 
	return this;
}

// object definition
k4SlideResizer.prototype = {
	
	lib:			new k4lib(),
	obj:			new Function(),
	
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
		this.obj				= obj_id.obj();

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
};

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
		var table_cells = container_obj.getTagsByName('td');
		if(table_cells && typeof(table_cells) != 'undefined') {
			for(var i = 0; i < table_cells.sizeof(); i++ ) {
				if(typeof(table_cells[i].className) != 'undefined' 
							&& table_cells[i].className == hover_off) {
					
					AttachEvent(table_cells[i],'mouseover',(function(){this.className=hover_over;}),false);
					AttachEvent(table_cells[i],'mouseout',(function(){this.className=hover_off;}),false);
				}
			}
		}
	}
};

//
// Make the color picker menu
//
var k4ColorPicker = {
	lib: new k4lib(),
	Init: function(textarea_id, color_type, link_id) {
		
		var menu_id = textarea_id + '_k4rte_' + color_type;

		// write the colorpicker to the page
		var cp_html = '<div id="' + menu_id + '" style="display:none;border-right:1px solid #CCCCCC;border-bottom:1px solid #CCCCCC;background-color:#FFFFFF;"><table cellpadding="0" cellspacing="2" border="0"><tr><td id="#FFFFFF"><img /></td><td id="#FFCCCC"><img /></td><td id="#FFCC99"><img /></td><td id="#FFFF99"><img /></td><td id="#FFFFCC"><img /></td><td id="#99FF99"><img /></td><td id="#99FFFF"><img /></td><td id="#CCFFFF"><img /></td><td id="#CCCCFF"><img /></td><td id="#FFCCFF"><img /></td></tr><tr><td id="#CCCCCC"><img /></td><td id="#FF6666"><img /></td><td id="#FF9966"><img /></td><td id="#FFFF66"><img /></td><td id="#FFFF33"><img /></td><td id="#66FF99"><img /></td><td id="#33FFFF"><img /></td><td id="#66FFFF"><img /></td><td id="#9999FF"><img /></td><td id="#FF99FF"><img /></td></tr><tr><td id="#C0C0C0"><img /></td><td id="#FF0000"><img /></td><td id="#FF9900"><img /></td><td id="#FFCC66"><img /></td><td id="#FFFF00"><img /></td><td id="#33FF33"><img /></td><td id="#66CCCC"><img /></td><td id="#33CCFF"><img /></td><td id="#6666CC"><img /></td><td id="#CC66CC"><img /></td></tr><tr><td id="#999999"><img /></td><td id="#CC0000"><img /></td><td id="#FF6600"><img /></td><td id="#FFCC33"><img /></td><td id="#FFCC00"><img /></td><td id="#33CC00"><img /></td><td id="#00CCCC"><img /></td><td id="#3366FF"><img /></td><td id="#6633FF"><img /></td><td id="#CC33CC"><img /></td></tr><tr><td id="#666666"><img /></td><td id="#990000"><img /></td><td id="#CC6600"><img /></td><td id="#CC9933"><img /></td><td id="#999900"><img /></td><td id="#009900"><img /></td><td id="#339999"><img /></td><td id="#3333FF"><img /></td><td id="#6600CC"><img /></td><td id="#993399"><img /></td></tr><tr><td id="#333333"><img /></td><td id="#660000"><img /></td><td id="#993300"><img /></td><td id="#996633"><img /></td><td id="#666600"><img /></td><td id="#006600"><img /></td><td id="#336666"><img /></td><td id="#000099"><img /></td><td id="#333399"><img /></td><td id="#663366"><img /></td></tr><tr><td id="#000000"><img /></td><td id="#330000"><img /></td><td id="#663300"><img /></td><td id="#663333"><img /></td><td id="#333300"><img /></td><td id="#003300"><img /></td><td id="#003333"><img /></td><td id="#000066"><img /></td><td id="#330099"><img /></td><td id="#330033"><img /></td></tr></table></div>';
		document.writeln(cp_html);
		
		// get the color picker
		var cp_table = menu_id.obj().firstChild;
		
		// alter the colorpicker table cells
		if(cp_table && typeof(cp_table) != 'undefined') {

			var cp_table_cells = cp_table.getTagsByName('td');
			
			for(var v = 0; v < cp_table_cells.sizeof(); v++) {
				cp_table_cells[v].style.backgroundColor		= cp_table_cells[v].id;
				cp_table_cells[v].style.border				= '1px solid #999999;';
				cp_table_cells[v].firstChild.alt			= '';
				cp_table_cells[v].firstChild.style.width	= '10px;';
				cp_table_cells[v].firstChild.style.height	= '10px;';

			}
		}

		menu_init(link_id, menu_id);
	}
};

//
// Object factory
//
var k4SlideResizerFactory = {
    createInstance: function() {
        return new k4SlideResizer();
    }
};
var k4ManageHoverCellFactory = {
	createInstance: function() {
		return new k4ManageHoverCell();
	}
};