/**
 * k4 Bulletin Board, k4 Effects objects
 * Copyright (c) 2005, Peter Goodman
 * Licensed under the LGPL license
 * http://www.gnu.org/copyleft/lesser.html
 * @author Peter Goodman
 * @version $Id$
 * @package k42
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

					this.obj.style.clip		= 'rect(' + clip_top + ', ' + clip_right + ', ' + clip_bottom + ', ' + clip_left + ');';
				
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
// Object factory
//
var k4SlideResizerFactory = {
    createInstance: function() {
        return new k4SlideResizer();
    }
}