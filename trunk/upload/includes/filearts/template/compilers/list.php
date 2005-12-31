<?php
/**
* k4 Bulletin Board, list.php
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
* @version $Id: list.php 136 2005-06-28 18:12:22Z Peter Goodman $
* @package k42
*/


class List_List_Compiler extends FATemplateTagCompiler {
	function parseOpen() {
		$this->requireAttributes('id');
		
		$list = $this->getAttribute('id');
		
		$this->writePHP("if (\$scope->listBegin(\"$list\")):");
	}
	function parseClose() {
		$this->writePHP("endif;");
	}
}

class List_Sublist_Compiler extends FATemplateTagCompiler {
	function parseOpen() {
		$this->requireAttributes('id', 'column');
		
		$sublist = $this->getAttribute('id');
		$column = $this->getAttribute('column');
		
		$this->writePHP("if (\$scope->listBeginNew(\"$sublist\", \$scope->getVar(\"$column\"))):");
	}
	function parseClose() {
		$this->writePHP("endif;");
	}
}

class List_Item_Compiler extends FATemplateTagCompiler {
	function parseOpen() {
		$this->requireAttributes('list');
		
		$list = $this->getAttribute('list');
		
		$this->writePHP("while(\$scope->listNext(\"$list\")):");
	}
	function parseClose() {
		$this->writePHP("\$scope->pop(); endwhile;");
	}
}

class List_Separator_Compiler extends FATemplateTagCompiler {
	function parseOpen() {
		$this->requireAttributes('list');
		
		$list = $this->getAttribute('list');
		
		$this->writePHP("if (\$scope->listHasNext(\"$list\")):");
	}
	function parseClose() {
		$this->writePHP("endif;");
	}
}

class List_Alternate_Compiler extends FATemplateTagCompiler {
	function parseOpen() {
		$this->requireAttributes('list');
		
		$list = $this->getAttribute('list');
		$count = $this->getAttribute('count', 1);
		$remainder = $this->getAttribute('remainder', 0);
		
		$this->writePHP("if (\$scope->listKey(\"$list\") % $count == $remainder):");
	}
	function parseClose() {
		$this->writePHP("endif;");
	}
}

class List_Switch_Compiler extends FATemplateTagCompiler {
	function parseOpen() {
		$this->requireAttributes('list', 'var');

		$list	= $this->getAttribute('list');
		$var	= $this->getAttribute('var');
		$switch = array_values($this->getAttributeArray('list', 'var'));

		$count	= count($switch);

		$this->writePHP("switch(\$scope->listKey(\"$list\") % $count):");
		
		foreach ($switch as $key => $value) {
			$this->writePHP("case $key: \$scope->push(array('$var' => '$value')); break;");
		}
		
		$this->writePHP("default: \$scope->push(array()); endswitch;");
	}
	function parseClose() {
		$this->writePHP("\$scope->pop();");
	}
}

class List_Default_Compiler extends FATemplateTagCompiler {
	function parseOpen() {
		$this->requireAttributes('list');
		
		$list = $this->getAttribute('list');
		
		$this->writePHP("if (\$scope->listKey(\"$list\") == -1 && !\$scope->listHasNext(\"$list\")):");
	}
	function parseClose() {
		$this->writePHP("endif;");
	}
}

?>