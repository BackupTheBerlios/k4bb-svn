<?php
/**
* k4 Bulletin Board, conditionals.php
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
* @version $Id: conditionals.php 147 2005-07-09 17:12:40Z Peter Goodman $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	return;
}

class If_If_Compiler extends FATemplateTagCompiler {
	function parseOpen() {
		$this->requireAttributes('var');
		$operator = $this->requireOne('eq', 'noteq', 'mod', 'greater', 'geq', 'less', 'lesseq');

		$operators = array(
						'eq'		=> '==',
						'noteq'		=> '!=',
						'mod'		=> '%',
						'greater'	=> '>',
						'geq'		=> '>=',
						'less'		=> '<',
						'lesseq'	=> '<=');
		
		$value = $this->getAttribute($operator);
		$var = $this->getAttribute('var');

		$this->writePHP("if (\$scope->getVar(\"$var\") {$operators[$operator]} ". (ctype_digit($value) && $value != '' ? intval($value) : ($value == '' ? "\"\"" : "(!\$scope->getVar(\"$value\") ? \"$value\" : \$scope->getVar(\"$value\"))")) ."):");
	}
	function parseClose() {
		$this->writePHP("endif;");
	}
}

class Else_If_Compiler extends FATemplateTagCompiler {
	function parseOpen() {
		$this->requireAttributes('var');
		$operator = $this->requireOne('eq', 'noteq', 'mod', 'greater', 'geq', 'less', 'lesseq');

		$operators = array(
						'eq'		=> '==',
						'noteq'		=> '!=',
						'mod'		=> '%',
						'greater'	=> '>',
						'geq'		=> '>=',
						'less'		=> '<',
						'lesseq'	=> '<=');
		
		$value = $this->getAttribute($operator);
		$var = $this->getAttribute('var');

		$this->writePHP("elseif (\$scope->getVar(\"$var\") {$operators[$operator]} ". (ctype_digit($value) && $value != '' ? intval($value) : ($value == '' ? "\"\"" : "(!\$scope->getVar(\"$value\") ? \"$value\" : \$scope->getVar(\"$value\"))")) ."):");
	}
}

class If_Else_Compiler extends FATemplateTagCompiler {
	function parseOpen() {
		$this->writePHP("else:");
	}
}


class Maps_If_Compiler extends FATemplateTagCompiler {
	function parseOpen() {
		
		if((isset($this->attribs['var']) || isset($this->attribs['group']) || isset($this->attribs['category']) || isset($this->attribs['forum'])) && isset($this->attribs['method'])) {
			
			$query = "TRUE";

			$attribs = array('groups' => 'group', 'categories' => 'category', 'forums' => 'forum', 'users' => 'user');
			
			
			if((isset($this->attribs['group']) || isset($this->attribs['category']) || isset($this->attribs['forum'])) && isset($this->attribs['method'])) {

				// These attributes are special because they need $scope->getVar() and the attrib name in them
				foreach($attribs as $key => $val) {
					if(isset($this->attribs[$val])) {
						$var		= "";
						if(isset($this->attribs['var']))
							$var	= "['". $this->attribs['var'] ."']";
						
						// Make the *query* to check the permissions
						$query		.= " && (isset(\$_MAPS['". $key ."'][\$scope->getVar(\"". $this->attribs[$val] ."\")]". $var ."['". $this->attribs['method'] ."'])";
						$query		.= " && \$_MAPS['". $key ."'][\$scope->getVar(\"". $this->attribs[$val] ."\")]". $var ."['". $this->attribs['method'] ."'] <= \$_SESSION['user']->get('perms'))";
					}
				}
			} else if(isset($this->attribs['var'])) {
				$query .= " && (isset(\$_MAPS['". $this->attribs['var'] ."']) && \$_MAPS['". $this->attribs['var'] ."']['". $this->attribs['method'] ."'] <= \$_SESSION['user']->get('perms'))";
			} else {
				$this->write("<h1>Missing VAR</h1>");
			}
			
			$this->writePHP("if(!isset(\$_MAPS)) { global \$_MAPS; } if($query):");
		} else {
			$this->write("<h1>Missing (VAR, CATEGORY, FORUM, GROUP) or METHOD for conditional MAPS statement.</h1>");
		}
	}
	function parseClose() {
		$this->writePHP("endif;");
	}
}

class Maps_Else_Compiler extends FATemplateTagCompiler {
	function parseOpen() {
		$this->writePHP("else:");
	}
}

?>