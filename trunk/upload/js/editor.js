/**
* k4 Bulletin Board, k4rte JavaScript class
* Copyright (c) 2005, Peter Goodman
* @author Peter Goodman
* @version $Id$
* @package k42
*/

var color_values = new Array('black', 'skyblue', 'royalblue', 'blue', 'darkblue', 'orange', 'orangered', 'crimson', 'red', 'firebrick', 'darkred', 'green', 'limegreen', 'seagreen', 'deeppink', 'tomato', 'coral', 'purple', 'indigo', 'burlywood', 'sandybrown', 'sienna', 'chocolate', 'teal', 'silver');
var color_styles = new Array('color: black;', 'color: skyblue;', 'color: royalblue;', 'color: blue;', 'color: darkblue;', 'color: orange;', 'color: orangered;', 'color: crimson;', 'color: red;', 'color: firebrick;', 'color: darkred;', 'color: green;', 'color: limegreen;', 'color: seagreen;', 'color: deeppink;', 'color: tomato;', 'color: coral;', 'color: purple;', 'color: indigo;', 'color: burlywood;', 'color: sandybrown;', 'color: sienna;', 'color: chocolate;', 'color: teal;', 'color: silver;');
var size_values = new Array(12, 7, 9, 12, 18, 24);
var size_styles = new Array('font-size: auto;', 'font-size: 8px;', 'font-size: 9px;', 'font-size: 12px;');
var img_dir = 'js/editor/';
var default_src = 'js/editor/blank.html';
var default_inst = 'rte';

function k4rte() {

	// public functions
	this.init = init;
	this.btn = btn;
	this.hs = new Function();
	this.sm = sm;

	// private functions
	this._get = _get;
	this._getd = _getd;
	this._rtm = _rtm;
	this._set = _set;
	this._cbtn = _cbtn;
	this._bs = _bs;
	this._it = _it;
	
	// vars
	this._tags = new Array();
	this._qt = new _qt();
	var d = new k4lib();
	var _t = this;
	var rte_mode = new Array();

	// get and object
	function _get(o) {
		ret = false;
		var obj = d.getElementById(o);
		if(obj && typeof(obj) == 'object') {
			ret = obj;
		}
		return ret;
	}

	// get the 'document' of an iframe
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

	// make an iframe go to rich-text mode
	function _rtm(i) {
		var ix = _t._get(i);
		var ixd = _t._getd(ix);
		var dm = false;
		if(ix && ixd) {
			ixd.open();
			ixd.write("<html><head></head><body></body></html>");
			ixd.close();
			if(ixd.designMode) {
				ixd.designMode = "On";
			} else {
				if(ix.contentDocument.designMode) {
					ix.contentDocument.designMode = "on";
					ix.contentEditable = true;
				}
			}
		}
	}

	// create an iframe and position it
	function _set(i) {
		document.writeln('<iframe name="' + i + '" id="' + i + '" frameborder="no" style="border:1px solid #999999;" src="' + default_src + '"></iframe>');
		_t._rtm(i);
		var ix = _t._get(i);
		var tx = _t._get(i.substring(0, i.length-6));
		ix.style.width = d.width(tx) + 'px';
		ix.style.height = d.height(tx) + 'px';
		tx.style.border = '1px solid #999999';
		rte_mode[i] = false;
		_t.sm(i);
	}

	// execute a button command
	function btn(i, cmd) {
		var ix = _t._get(i);
		var ixd = _t._getd(ix);
		if(cmd in _t.hs) {
			eval("_t.hs." + cmd + "(i);");
		} else {
			if(rte_mode[i] == true) {
				ixd.execCommand(cmd, false, '');
			} else {
				var tx = _t._get(i.substring(0, i.length-6));
				_t._it(tx, cmd);
			}
		}
	}

	// create a button
	function _cbtn(i, alt, img, cmd, tags) {
		if(typeof(tags) != 'undefined') {
			_t._tags[cmd] = tags;
		}
		document.writeln('<a href="javascript:'+ default_inst + '.btn(\'' + i + '\', \'' + cmd + '\');" title="' + alt + '"><img src="' + img_dir + '' + img + '.gif" name="button_' + cmd + '" id="button_' + cmd  + '_' + i + '" alt="' + alt + '" border="0" /></a>');
	}

	// create a panel of buttons
	function _bs(i) {
		_t._cbtn(i, 'Bold', 'bold', 'bold', ["<strong>", "</strong>"], ["[b]", "[/b]"]);
		_t._cbtn(i, 'Italic', 'italic', 'italic', ["<em>", "</em>"], ["[i]", "[/i]"]);
		_t._cbtn(i, 'Underline', 'underline', 'underline', ["<u>", "</u>"], ["[u]", "[/u]"]);
		_t._cbtn(i, 'Left', 'justifyleft', 'justifyleft', ["<span style=\"text-align: left;\">", "</span>"], ["[left]", "[/left]"]);
		_t._cbtn(i, 'Center', 'justifycenter', 'justifycenter', ["<span style=\"text-align: center;\">", "</span>"], ["[center]", "[/center]"]);
		_t._cbtn(i, 'Right', 'justifyright', 'justifyright', ["<span style=\"text-align: right;\">", "</span>"], ["[right]", "[/right]"]);
		_t._cbtn(i, 'Justify', 'justifyfull', 'justifyfull', ["<span style=\"text-align: justify;\">", "</span>"], ["[justify]", "[/justify]"]);
		_t._cbtn(i, 'Ordered List', 'ol', 'insertorderedlist', ["<ol>", "</ol>"], ["[list=1]", "[/list]"]);
		_t._cbtn(i, 'Unordered List', 'ul', 'insertunorderedlist', ["<ol>", "</ul>"], ["[list]", "[/list]"]);
		_t._cbtn(i, 'Indent', 'indent', 'indent', ["<span style=\"margin-left: 20px;\">", "</span>"], ["[indent]", "[/indent]"]);
		_t._cbtn(i, 'Outdent', 'outdent', 'outdent', ["<span style=\"margin-left: 0px;\">", "</span>"], ["[outdent]", "[/outdent]"]);
		_t._cbtn(i, 'Undo', 'undo', 'undo');
		_t._cbtn(i, 'Redo', 'redo', 'redo');
		_t._cbtn(i, 'Color', 'textcolor', 'forecolor');
		document.writeln('<a href="javascript:'+ default_inst + '.sm(\'' + i + '\');" title="Switch Mode"><img src="' + img_dir + 'switch_format.gif" name="button_switch" id="button_switch_' + i + '" alt="Switch Mode" border="0" /></a>');
		document.write('<br />');
	}

	// initialize the editor
	function init(t) {
		tx = _t._get(t);
		if(tx && !d.is_opera) {
			var i = t + '_k4rte';
			_t._bs(i);
			_t._set(i);
		}
	}

	// switch editor modes
	function sm(i) {
		var ix = _t._get(i);
		var ixd = _t._getd(ix);
		var tx = _t._get(i.substring(0, i.length-6));
		if(ix && ixd && tx) {
			if(rte_mode[i] == true) {
				rte_mode[i] = false;
				tx.style.display = 'block';
				ix.style.display = 'none';
				objs = d.getElementsByTagName(ixd, 'body');
				tx.innerHTML = objs[0].innerHTML;
				tx.value = objs[0].innerHTML;
			} else {
				rte_mode[i] = true;
				tx.style.display = 'none';
				ix.style.display = 'block';
				ixd.open();
				ixd.write(tx.value);
				ixd.close();
			}
		}
	}

	/** 
	 * The following code is modified from JS Quicktags
	 * Copyright (c) 2002-2005 Alex King
	 * http://www.alexking.org/
	 *
	 * Licensed under the LGPL license
	 * http://www.gnu.org/copyleft/lesser.html
	 *
	 * Modified on December 11, 2005 by Peter Goodman
	 * Modifications:
	 *  - Changed function names
	 *  - Made it use the k4lib() instead of its old functions
	 *  - Put everything into a sub class of k4rte()
	 *  - Made it remove opening & closing tags when selected and
	 *    appropriate button is clicked.
	 */
	
	// new quick tags object
	function _qt(tags) {
		
		// private functions
		this._pusht = _pusht;
		this._popt = _popt;
		this._tis = _tis;
		this._rep = _rep;

		// vars
		var _tags = new Array();

		// push a tag onto the stack
		function _pusht(cmd) {
			d.array_push(_tags, cmd);
		}

		// pop a tag off the stack
		function _popt(cmd) {
			if(d.in_array(_tags, cmd)) {
				d.unset(_tags, cmd);
			}
		}

		// check if a tag is open
		function _tis(cmd) {
			ret = false;
			if(d.in_array(_tags, cmd))
				ret = true;
			return ret;
		}

		// replace a selection with the appropriate tag or remove the surrounding tag
		function _rep(open, txt, close) {
			st = txt.substring(0, open.length);
			ed = txt.substring(txt.length - close.length);
			if(st == open && ed == close) {
				return txt.substring(open.length, txt.length - close.length);
			} else {
				return open + txt + close;
			}
		}
	}

	// initialize the quick tags and deal with them all
	function _it(txx, cmd) {
		if (document.selection) {
			txx.focus();
			sel = document.selection.createRange();
			if (sel.text.length > 0) {
				sel.text = _t._qt._rep(_t._tags[cmd][0], sel.text, _t._tags[cmd][1]);
			} else {
				if (!_t._qt._tis(cmd) || _t._tags[cmd][1] == '') {
					sel.text = _t._tags[cmd][0];
					_t._qt._pusht(cmd);
				} else {
					sel.text = _t._tags[cmd][1];
					_t._qt._popt(cmd);
				}
			}
			if(txx.focus) txx.focus();
		} else if (txx.selectionStart || txx.selectionStart == '0') {
			var startPos = txx.selectionStart;
			var endPos = txx.selectionEnd;
			var cursorPos = endPos;
			var scrollTop = txx.scrollTop;
			if (startPos != endPos) {
				txx.value = txx.value.substring(0, startPos)
							  + _t._qt._rep(_t._tags[cmd][0], txx.value.substring(startPos, endPos), _t._tags[cmd][1])
							  + txx.value.substring(endPos, txx.value.length);
				cursorPos += _t._tags[cmd][0].length + _t._tags[cmd][1].length;
			} else {
				if (!_t._qt._tis(cmd) || _t._tags[cmd][1] == '') {
					txx.value = txx.value.substring(0, startPos) 
								  + _t._tags[cmd][0]
								  + txx.value.substring(endPos, txx.value.length);
					_t._qt._pusht(cmd);
					cursorPos = startPos + _t._tags[cmd][0].length;
				} else {
					txx.value = txx.value.substring(0, startPos) 
								  + _t._tags[cmd][1]
								  + txx.value.substring(endPos, txx.value.length);
					_t._qt._popt(cmd);
					cursorPos = startPos + _t._tags[cmd][1].length;
				}
			}
			if(txx.focus) txx.focus();
			txx.selectionStart = cursorPos;
			txx.selectionEnd = cursorPos;
			txx.scrollTop = scrollTop;
		} else {
			if (!_t._qt._tis(cmd) || _t._tags[cmd][1] == '') {
				txx.value += _t._tags[cmd][0];
				_t._qt._pusht(cmd);
			} else {
				txx.value += _t._tags[cmd][1];
				_t._qt._popt(cmd);
			}
			if(txx.focus) txx.focus();
		}
	}

	/**
	 * END: modified Steven King code
	 */
	_t.hs.forecolor = function(i) {
		alert('This is a hooked on function.');
	}
}