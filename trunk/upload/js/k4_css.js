/**
 * k4 Bulletin Board, k4CSS JavaScript object
 * Copyright (c) 2005, Peter Goodman
 * Licensed under the LGPL license
 * http://www.gnu.org/copyleft/lesser.html
 * @author Peter Goodman
 * @version $Id$
 * @package k42
 */

var k4CSS = {
	
	"inline_elements": ['strong', 'b', 'em', 'i', 'span', 'i', 'label',	'font', 'img', 'select', 'input', 'textarea', 'strike',	'sub', 'sup', 'textarea', 'tt', 'u', 'dfn', 'acronym', 'abbr', 'cite', 'code', 'dfn', 'button'],
	"element_types": false,
	"css_editor": false,
	"css_editor_title": false,
	"css_editor_iframe": false,

	"init": function() {
		
		document.write('<div id="css_inline_editor" style="display:none;width:60%;position:absolute;left:20%;z-index:999;"><div class="header"><div class="title" id="css_inline_editor_title"> </div></div>');
		document.write('<div class="spacer"><div class="alt1"><iframe id="css_inline_editor_iframe" src="" frameborder="no" style="width: 100%;height: 200px;"></iframe></div></div>');
		document.write('<div class="footer_block"><div style="text-align:center;" id="close_css_editor" onclick="FA.getObj(\'css_inline_editor\').style.display=\'none\';">X &nbsp; X &nbsp; X</div></div></div>');
		
		this.element_types = { 'inline':'#00EA06','block':'#0000EA','body':'#D70000' };
		this.css_editor = FA.getObj('css_inline_editor');
		this.css_editor_title = FA.getObj('css_inline_editor_title');
		this.css_editor_iframe = FA.getObj('css_inline_editor_iframe');
		
		this.initNodes(document.body);
	},
	
	//
	// Initialize the hovering of nodes, starting with node_obj
	//
	"initNodes": function(node_obj) {
		if(node_obj.nodeType == 1) {
			this.setNode(node_obj);
			if(node_obj.childNodes) {
				for(var i = 0; i < FA.sizeOf(node_obj.childNodes); i++) {
					if(node_obj.id != 'css_inline_editor') {
						this.initNodes(node_obj.childNodes[i]);
					}
				}
			}
		}
	},
	
	//
	// Add the events to a node, etc
	//
	"setNode": function(node_obj) {
		// node name
		var node_name = node_obj.nodeName.toLowerCase();
		
		// determing the level of the node (block/inline/body)
		if (FA.search(this.inline_elements, node_name)) {
			node_obj._nodeType = 'inline';
		} else if (node_name == 'body') {
			node_obj._nodeType = 'body';
		} else {
			node_obj._nodeType = 'block';
		}
		
		var previous_border = '';
		if(typeof(node_obj.style) != 'undefined') {
			previous_border = typeof(node_obj.style.border) != 'undefined' ? node_obj.style.border : '';
		}

		var self = this;
		FA.attachEvent(node_obj,'mouseover',(function(){self.highlightObj(node_obj);}));
		FA.attachEvent(node_obj,'mouseout',(function(){self.unhighlightObj(node_obj,previous_border);}));
		FA.attachEvent(node_obj,'click',(function(e){self.displayEditor(e);}));
		
		// come up with a name for it
		var node_obj_i = node_obj;
		var obj_title = '';
		while (node_obj_i && node_obj_i != document) {
			if (obj_title) {
				obj_title = node_obj_i.nodeName.toLowerCase() + ' > ' + obj_title;
			} else {
				obj_title = node_obj_i.nodeName.toLowerCase();
			}
			node_obj_i = node_obj_i.parentNode;
		}
		node_obj.title = obj_title;
	},
	
	//
	// Add a border to one whatever we are hovering over
	//
	"highlightObj": function(node_obj) {
		node_obj.style.borderWidth = '1px';
		node_obj.style.borderColor = this.element_types[node_obj._nodeType];
		node_obj.style.borderStyle = (node_obj.className == '' ? 'dotted' : 'solid');
	},
	
	//
	// Remove the border from the hovered object
	//
	"unhighlightObj": function(node_obj, previous_border) {
		node_obj.style.border = previous_border;
	},
	
	//
	// Display the CSS editor
	//
	"displayEditor": function(e) {
		var target = FA.eventTarget(e);
		if(this.css_editor && target && this.css_editor_iframe && this.css_editor_title) {
			if(target.id != 'close_css_editor' && target.className != '') {
				
				var arrayPageSize	= getPageSize();
				var arrayPageScroll = getPageScroll();
				this.css_editor.style.top = (arrayPageScroll[1] + ((arrayPageSize[3] - 35 - this.css_editor.offsetHeight) / 2) + 'px');
				
				FA.show(this.css_editor);

				this.css_editor_title.innerHTML = 'CSS: ' + target.className + '<br />' + target.title + '';
				this.css_editor_iframe.src = 'admin.php?act=css_editstyle&editor=1&class=' + target.className.replace(/\s/, '+');
			}
		}
	}
};