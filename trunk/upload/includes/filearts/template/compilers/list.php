<?php
/**
* k4 Bulletin Board, list.php
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