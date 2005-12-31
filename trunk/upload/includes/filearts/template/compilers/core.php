<?php
/**
* k4 Bulletin Board, core.php
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