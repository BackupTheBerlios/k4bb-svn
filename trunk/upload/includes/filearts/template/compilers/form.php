<?php
/**
* k4 Bulletin Board, form.php
*
* Copyright (c) 2005, Peter Goodman
*
* This library is free software; you can redistribute it and/orextension=php_gd2.dll
* modify it under the terms of the GNU Lesser General Public
* License as published by the Free Software Foundation; either
* version 2.1 of the License, or (at your option) any later version.
* 
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
* Lesser General Public License for more details.
* 
* Licensed under the LGPL license
* http://www.gnu.org/copyleft/lesser.html
*
* @author Geoffrey Goodman
* @version $Id: form.php 110 2005-06-13 20:48:58Z Peter Goodman $
* @package k42
*/

class Form_Form_Compiler extends FATemplateTagCompiler {
	function parseOpen() {
		static $forms = 0;
		
		$attribs = $this->getAttributeString();
//		$js = (!$forms++) ? file_get_contents(BB_BASE_DIR.'/js/form_check.js') : '';
//
//		$this->write("<script type=\"text/javascript\">$js</script>");
		$this->write("<form$attribs onsubmit=\"return checkForm(this);\" onreset=\"resetErrors();\"><script type=\"text/javascript\">resetErrors();</script>");
	}

	function parseClose() {
		$this->write("</form>");
	}
}

class Form_Error_Compiler extends FATemplateTagCompiler {
	function writeVerificationJs() {
			$id = $this->getAttribute('id');
			$class = $this->getAttribute('setclass');
			$for = $this->getAttribute('for');

			if ($this->isAttribute('regex')) {
				$regex = str_replace(str_repeat(chr(92), 2), str_repeat(chr(92), 3), addslashes($this->getAttribute('regex')));
				$this->write("<script type=\"text/javascript\">addVerification('$for', '$regex', '$id', '$class');</script>");
			} else if ($this->isAttribute('match')) {
				$match = $this->getAttribute('match');
				$this->write("<script type=\"text/javascript\">addCompare('$for', '$match', '$id', '$class');</script>");
			}
	}

	function parseOpen() {
		$this->requireAttributes('id', 'for');

		$id = $this->getAttribute('id');
		
		$this->writeVerificationJs();
		$this->write("<div id=\"$id\" style=\"display: none;\">");
	}
	function parseClose() {
		$this->write("</div>");
	}
}

class Form_Message_Compiler extends FATemplateTagCompiler {
	function writeMessageJs() {
		$id = $this->getAttribute('id');
		$for = $this->getAttribute('for');

		$this->write("<script type=\"text/javascript\">addMessage('$for', '$id');</script>");
	}
	function parseOpen() {
		$this->requireAttributes('id', 'for');

		$id = $this->getAttribute('id');

		$this->writeMessageJs();
		$this->write("<div id=\"$id\" style=\"display: block;\">");
	}
	function parseClose() {
		$this->write("</div>");
	}
}

?>
