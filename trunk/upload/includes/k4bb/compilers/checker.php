<?php
/**
* k4 Bulletin Board, checker.php
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
* @version $Id: checker.php,v 1.2 2005/04/13 02:54:29 k4st Exp $
* @package k42
*/

if(!defined('IN_K4'))
	return;

class Is_Admin_Compiler extends FATemplateTagCompiler {
	function parseOpen() {
		$this->writePHP("if (\$_SESSION['user']->get('perms') >= ADMIN):");
	}
	function parseClose() {
		$this->writePHP("endif;");
	}
}

class Is_Unlogged_Compiler extends FATemplateTagCompiler {
	function parseOpen() {
		$this->writePHP("if (!\$_SESSION['user']->isMember()):");
	}
	function parseClose() {
		$this->writePHP("endif;");
	}
}

class Is_Logged_Compiler extends FATemplateTagCompiler {
	function parseOpen() {
		$this->writePHP("if (\$_SESSION['user']->isMember()):");
	}
	function parseClose() {
		$this->writePHP("endif;");
	}
}

?>