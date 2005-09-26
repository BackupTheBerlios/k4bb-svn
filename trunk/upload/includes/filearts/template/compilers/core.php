<?php
/**
* k4 Bulletin Board, core.php
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
* @version $Id: core.php 110 2005-06-13 20:48:58Z Peter Goodman $
* @package k42
*/

class Core_Block_Compiler extends FATemplateTagCompiler {
	function parseOpen() {
		$this->requireAttributes('id');
		
		$id = $this->getAttribute('id');
		$visible = ($this->getAttribute('hidden', 'no') != 'yes') ? 'TRUE' : 'FALSE';
		
		$this->writePHP("if (\$runtime->isVisible('$id', $visible)):");
	}
	
	function parseClose() {
		$this->writePHP("endif;");
	}
}

class Core_Import_Compiler extends FATemplateTagCompiler {
	function parseOpen() {
		$this->requireAttributes('id');
		
		$id = $this->getAttribute('id');
		$file = $this->getAttribute('file');
		
		$this->writePHP("if (\$file = \$runtime->getFile(\"$id\", \"$file\")) \$template->render(\$file, \$scope, \$runtime);");
	}
}


class Core_Date_Compiler extends FATemplateTagCompiler {
	function parseOpen() {
		$format = $this->getAttribute('format', "%x");
		
		$this->writePHP("ob_start(); \$format = \"$format\";");
	}

	function parseClose() {
		$this->writePHP("\$date = ob_get_contents(); ob_end_clean(); echo strftime(\$format, bbtime(intval(\$date)));");
	}
}

class Core_Truncate_Compiler extends FATemplateTagCompiler {
	function parseOpen() {
		$this->requireAttributes('length');
		
		$length = $this->getAttribute('length');
		$append = $this->getAttribute('append', "&hellip;");
		
		$this->writePHP("ob_start(); \$length = \"$length\"; \$append = \"$append\";");
	}

	function parseClose() {
		$this->writePHP("\$string = ob_get_contents(); ob_end_clean(); echo ((strlen(\$string) > \$length) ? substr(\$string, 0, \$length - strlen(\$append)) . \$append : \$string);");
	}
}

?>