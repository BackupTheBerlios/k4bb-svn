/**
 * k4 Bulletin Board, k4css JavaScript object
 * Copyright (c) 2005, Peter Goodman
 * Licensed under the LGPL license
 * http://www.gnu.org/copyleft/lesser.html
 * @author Peter Goodman
 * @version $Id$
 * @package k42
 */

function k4css() {
	this.init = init;
	this._init_nodes = _init_nodes;
	this._set_node = _set_node;
	this._highlight = _highlight;
	this._unhighlight = _unhighlight;
	this._editor = _editor;
	this._getd = _getd;
	
	var d = new k4lib();
	var ils = new Array('strong', 'b', 'em', 'i', 'span', 'i', 'label', 
						'font', 'img', 'select', 'input', 'textarea', 'strike',
						'sub', 'sup', 'textarea', 'tt', 'u', 'dfn', 'acronym',
						'abbr', 'cite', 'code', 'dfn', 'button');
	
	var level_cs = new Array();
	level_cs['inline'] = '#00EA06';
	level_cs['block'] = '#0000EA';
	level_cs['body'] = '#D70000';
	
	var _t = this;

	// initialize
	function init() {
		_t._init_nodes(document.body);
		document.write('<div id="css_inline_editor" style="display:none;"><div class="header"><div class="title" id="css_inline_editor_title"> </div></div>');
		document.write('<div class="spacer"><div class="alt1"><iframe id="css_inline_editor_iframe" src="" frameborder="no" style="width: 100%;height: 200px;"></iframe></div></div>');
		document.write('<div class="footer_block"><div style="text-align:center;" id="close_css_editor" onclick="d.getElementById(\'css_inline_editor\').style.display=\'none\';">X &nbsp; X &nbsp; X</div></div></div>');
	}
	function _init_nodes(nx) {
		if(nx.nodeType == 1) {
			_t._set_node(nx);
			if(nx.childNodes) {
				for(var i = 0; i < d.sizeof(nx.childNodes); i++) {
					_t._init_nodes(nx.childNodes[i]);
				}
			}
		}
	}
	// set properties, etc for a specific node
	function _set_node(nx) {
		// node name
		var nn = nx.nodeName.toLowerCase();
		
		// determing the level of the node (block/inline/body)
		if (d.in_array(ils, nn)) {
			nx._nodeType = 'inline';
		} else if (nn == 'body') {
			nx._nodeType = 'body';
		} else {
			nx._nodeType = 'block';
		}
		
		var bt = '';
		if(typeof(this.style) != 'undefined') {
			bt = typeof(this.style.border) != 'undefined' ? this.style.border : '';
		}

		nx.onmouseover = function(e) { _t._highlight(e, this); }
		nx.onmouseout = function(e) { _t._unhighlight(e, this, bt); }
		nx.onclick = function(e) { _t._editor(e, this); }
		
		// come up with a name for it
		var nxx = nx;
		var ttl = '';
		while (nxx && nxx != document) {
			if (ttl) {
				ttl = nxx.nodeName.toLowerCase() + ' > ' + ttl;
			} else {
				ttl = nxx.nodeName.toLowerCase();
			}
			nxx = nxx.parentNode;
		}
		nx.title = ttl;
	}
	// un/highlight
	function _highlight(e, nx) {
		nx.style.borderWidth = '1px';
		nx.style.borderColor = level_cs[nx._nodeType];
		nx.style.borderStyle = (nx.className == '' ? 'dotted' : 'solid');
	}
	function _unhighlight(e, nx, bt) {
		nx.style.border = bt;
	}
	// editor
	function _editor(e, nx) {
		
		var ex = d.getElementById('css_inline_editor');
		var ext = d.getElementById('css_inline_editor_title');
		var ifr = _t._getd(d.getElementById('css_inline_editor_iframe'));
		var t = d.get_event_target(e);
		if(ex && ext && t && ifr) {
			if(t.id != 'close_css_editor' && t.className != '') {
				ex.style.position = 'absolute';
				ex.style.top = '20%';
				ex.style.left = '20%';
				ex.style.width = '60%';
				ex.style.display = 'block';
				ext.innerHTML = 'CSS: ' + t.className + '<br />' + t.title + '';
				ifr.location.href = 'admin.php?act=css_editstyle&editor=1&class=' + t.className.replace(/\s/, '+');
			}
		}
	}
	function _getd(ix) {
		ixd = ixw = false;
		if(ix) {
			if (document.all) {
				try { ixw = frames[ix.id]; } catch(e) { }
			} else {
				try { ixw = ix.contentWindow; } catch(e) { }
			}
			if(ixw) {
				ixd	= ixw.document; 
				if(!ixd && document.all && ix.contentWindow) {
					ixd = ix.contentWindow.document;
				}
			}
		}
		return ixd;
	}
}