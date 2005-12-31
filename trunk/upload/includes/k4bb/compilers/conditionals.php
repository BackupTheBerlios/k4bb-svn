<?php
/**
* k4 Bulletin Board, conditionals.php
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
* @author Peter Goodman
* @version $Id: conditionals.php 147 2005-07-09 17:12:40Z Peter Goodman $
* @package k42
*/



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
			
			$attribs = array('group', 'category', 'forum', 'user');

			$query = "if(\$_SESSION['user']->get('perms') >= get_map(";
			$query .= isset($this->attribs['var']) ? "'". $this->attribs['var'] ."', " : "'', ";
			$query .= "'". $this->attribs['method'] ."', ";
			$query .= "array(";
			
			if((isset($this->attribs['group']) || isset($this->attribs['category']) || isset($this->attribs['forum'])) && isset($this->attribs['method'])) {

				// These attributes are special because they need $scope->getVar() and the attrib name in them
				foreach($attribs as $attrib) {
					if(isset($this->attribs[$attrib])) {
						$query .= "'". $attrib ."_id'=>intval(\$scope->getVar('". $this->attribs[$attrib] ."')),";
					}
				}
			}
			$query .= "))):";
//			
//			

//			} else if(isset($this->attribs['var'])) {
//				$query .= " && (isset(\$_MAPS['". $this->attribs['var'] ."']) && \$_MAPS['". $this->attribs['var'] ."']['". $this->attribs['method'] ."'] <= \$_SESSION['user']->get('perms'))";
//			} else {
//				$this->write("<h1>Missing VAR</h1>");
//			}
//			
			$this->writePHP($query);
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