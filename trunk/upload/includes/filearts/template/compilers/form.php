<?php
/**
* k4 Bulletin Board, form.php
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
