<script type="text/javascript">
//<![CDATA[
	function doHelpButton(help_id) {
		document.write('<div style="float:right;position:relative;"><a href="javascript:;" onclick="popup_file(\'admin.php?act=help#' + help_id + '\', 500, 450);" title="{$L_HELP}"><img src="Images/{$IMG_DIR}/Icons/help.gif" border="0" alt="{$L_HELP}" /></a></div>');
	}

	function popupImageBrowser(folder, input_id, selected) {
		try {
			popup_file('admin.php?act=file_browser&filetype=img&input=' + input_id + '&dir=' + folder + '&selected=' + selected, 500, 560);
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
//]]>
</script>