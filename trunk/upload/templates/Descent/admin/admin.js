<script type="text/javascript">

	// <!--

	/**
	* k4 Bulletin Board, admin.js
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
	* @version $Id: admin.js,v 1.1 2005/04/05 03:12:18 k4st Exp $
	* @package k42
	*/

	function doHelpButton(help_id) {
		document.write('<div style="float: right;"><a href="javascript:;" onclick="popup_file(\'admin.php?act=help#' + help_id + '\', 500, 450);" title="{$L_HELP}"><img src="Images/{$IMG_DIR}/Icons/help.gif" border="0" alt="{$L_HELP}" /></a></div>');
	}

	function popupImageBrowser(folder, input_id, selected) {
		try {
			popup_file('admin.php?act=file_browser&filetype=img&input=' + input_id + '&dir=' + folder + '&selected=' + selected, 500, 450);
		} catch(e) {
			alert(e.message);
		}
	}
	function set_file(filename, input) {
		var inputobj				= document.getElementById(input);
		
		if(inputobj) {
			
			inputobj.value			= filename;
		}
	}

	function select_file(input, opener_input) {
		var inputobj				= document.getElementById(input);
		if(inputobj) {
			var openerobj			= window.opener.document.getElementById(opener_input);
			if(openerobj) {
				openerobj.value		= inputobj.value;
				window.close();
			}
		}
	}
	// -->
</script>