<?php
/**
* k4 Bulletin Board, pag.php
*
* Copyright (c) 2005, Geoffrey Goodman
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
* @version $Id: page.php 137 2005-06-29 18:48:28Z Peter Goodman $
* @package k42
*/

class Page_Navigator_Compiler extends FATemplateTagCompiler {
	function parseOpen() {
		$this->requireAttributes('id');
		$pager = $this->getAttribute('id');

		$this->writePHP("if (\$pager = \$runtime->getPager(\"$pager\")):");
		$this->writePHP("if(\$scope->getVar(\"num_pages\") == \"\") { \$scope->setVar(\"num_pages\", intval(@ceil(\$pager->count / \$pager->page_size))); }");
	}
	function parseClose() {
		$this->writePHP("endif;");
	}
}

class Page_First_Compiler extends FATemplateTagCompiler {
	function parseOpen() {
		$this->writePHP("if (\$scope->push(\$pager->getFirst())):");
	}
	function parseClose() {
		$this->writePHP("\$scope->pop(); endif;");
	}
}

class Page_Prev_Compiler extends FATemplateTagCompiler {
	function parseOpen() {
		$this->writePHP("if (\$scope->push(\$pager->getPrev())):");
	}
	function parseClose() {
		$this->writePHP("\$scope->pop(); endif;");
	}
}

class Page_Next_Compiler extends FATemplateTagCompiler {
	function parseOpen() {
		$this->writePHP("if (\$scope->push(\$pager->getNext())):");
	}
	function parseClose() {
		$this->writePHP("\$scope->pop(); endif;");
	}
}

class Page_Last_Compiler extends FATemplateTagCompiler {
	function parseOpen() {
		$this->writePHP("if (\$scope->push(\$pager->getLast())):");
	}
	function parseClose() {
		$this->writePHP("\$scope->pop(); endif;");
	}
}

class Page_List_Compiler extends FATemplateTagCompiler {
	function parseOpen() {
		$this->requireAttributes('id', 'before', 'after');

		$list	= $this->getAttribute('id');
		$before = $this->getAttribute('before');
		$after	= $this->getAttribute('after');

		$this->writePHP("if (\$pager->hasPage('2') && \$scope->listBeginNew(\"$list\", \$pager->getIterator(\"$before\", \"$after\"))):");
	}
	function parseClose() {
		$this->writePHP("endif;");
	}
}

class Page_Link_Compiler extends FATemplateTagCompiler {
	function parseOpen() {
		$attribs = $this->getAttributeString();

		$this->writePHP("if (\$pager->page_num != \$scope->getVar(\"pagenum\")): ?><a$attribs href=\"<?php echo \$scope->getVar(\"pagelink\"); ?>\"><?php else: ?><strong$attribs><?php endif;");
	}
	function parseClose() {
		$this->writePHP("if (\$pager->page_num != \$scope->getVar(\"pagenum\")): ?></a><?php else: ?></strong><?php endif;");
	}
}

?>