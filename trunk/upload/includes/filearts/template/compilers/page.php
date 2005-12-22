<?php
/**
* k4 Bulletin Board, pag.php
*
* Copyright (c) 2005, Geoffrey Goodman
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