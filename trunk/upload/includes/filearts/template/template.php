<?php
/**
* k4 Bulletin Board, template.php
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
* @version $Id: template.php 152 2005-07-14 01:37:57Z Peter Goodman $
* @package k42
*/

if (!defined('FILEARTS'))
	return;

define('TPL_BASE_DIR', dirname(__FILE__));

define('FA_FORCE', 1);
define('FA_NOCACHE', 2);

require TPL_BASE_DIR .'/paginator.php';

class FATemplateTagCompiler extends FAObject {
	var $buffer;
	var $tag;
	var $attribs;
	
	function getClose($tag) {
		$this->setTag($tag, array());
		$this->parseClose();
		
		return $this->getBuffer();		
	}
	
	function getOpen($tag, $attribs) {
		$this->setTag($tag, $attribs);
		$this->parseOpen();
		
		return $this->getBuffer();
	}
	
	function getAttribute($attrib, $default = NULL) {
		return (isset($this->attribs[$attrib])) ? $this->attribs[$attrib] : $default;
	}
	
	function getAttributeArray() {
		$attribs = func_get_args();
		$ret = array();
		
		if (!empty($attribs)) {
			foreach ($this->attribs as $key => $value) {
				if (!in_array($key, $attribs)) {
					$ret[$key] = $value;
				}
			}
		} else {
			$ret = $attribs;
		}
		
		return $ret;
	}

	function getAttributeString() {
		$attribs = func_get_args();
		
		$buffer = '';
		
		foreach ($this->attribs as $key => $value) {
			if (!in_array($key, $attribs))
				$buffer .= " $key=\"$value\"";
		}
		
		return $buffer;
	}

	function isAttribute($attrib) {
		return isset($this->attribs[$attrib]);
	}
	
	function getBuffer() {
		return $this->buffer;
	}
	
	function parseOpen() {
	}
	
	function parseClose() {
	}
	
	function setTag($tag, $attribs) {
		$this->buffer = '';
		$this->tag = $tag;
		$this->attribs = $attribs;
	}
	
	function requireAttributes($arglist) {
		$attribs = func_get_args();
		
		foreach ($attribs as $attrib) {
			if (!isset($this->attribs[$attrib]))
				trigger_error("Missing $attrib for the tag {$this->tag}", E_USER_ERROR);
		}
	}

	function requireOne($arglist) {
		$attribs = func_get_args();

		foreach ($attribs as $attrib) {
			if (isset($this->attribs[$attrib]))
				return $attrib;
		}

		trigger_error("Missing attribute for the tag {$this->tag}", E_USER_ERROR);
	}
	
	function write($buffer) {
		$this->buffer .= $buffer;
	}
	
	function writePHP($buffer) {
		$this->write("<?php $buffer ?>");
	}
}

class FATemplateCompiler extends FAObject {
	var $compilers = array();
	var $default_compiler;
	
	function __construct() {
		$this->default_compiler = &new FATemplateTagCompiler();
		$this->loadCompilers(TPL_BASE_DIR . '/compilers/');
	}
	
	function compile($filename) {
		$buffer = file_get_contents($filename);

		$buffer = preg_replace_callback('~(?:[\t]*)(<(/)?([a-z]+:[a-z]+)((?:[\s][a-zA-Z]+="[^"]*")*)[\s]?(/)?>)~', array($this, 'compileTag'), $buffer);
		$buffer = preg_replace('~{\$([a-zA-Z_-][a-zA-Z0-9_-]*)}~', '<?php echo $scope->getVar("$1"); ?>', $buffer);
		$buffer = preg_replace('~"?{\@([a-zA-Z_-][a-zA-Z0-9_-]*)}"?~', '$scope->getVar("$1")', $buffer);
		
		return $buffer;
	}
	
	function compileTag($matches) {
		$name = $matches[3];
		$attribs = array();
		$compiler = $this->getCompiler($name);
		
		// Parse through the tag's attributes
		if ($count = preg_match_all('~\s([a-zA-Z]+)="([^"]*)"~', $matches[4], $attrib_matches)) {
			for ($i = 0; $i < $count; $i++) {
				$attribs[$attrib_matches[1][$i]] = $attrib_matches[2][$i];
			}
		}
						
		if ($matches[2] == '/') {
			$compiled = $compiler->getClose($name);
		} elseif (@$matches[5] == '/') {
			$compiled = $compiler->getOpen($name, $attribs);
			$compiled .= $compiler->getClose($name);
		} else {
			$compiled = $compiler->getOpen($name, $attribs);
		}
		
		return $compiled;
	}
	
	function getCompiler($tag) {
		$compiler = &$this->default_compiler;

		if (isset($this->compilers[$tag])) {
			$compiler = &$this->compilers[$tag];
		} else {
			$class = implode('_', explode(':', $tag)) . '_compiler';
			
			if (class_exists($class)) {
				$this->compilers[$tag] = &new $class();
				$compiler = $this->compilers[$tag];
			}
		}

		return $compiler;
	}
	
	function loadCompilers($directory) {
		$dir = dir($directory);
		
		while (($file = $dir->read()) !== FALSE) {
			if ($file != '.' && $file != '..' && !is_dir("$directory$file"))
				require_once "$directory$file";
		}

		$dir->close();
	}
}

class FATemplateRuntime extends FAObject {
	var $_filename;
	var $_files;
	var $_blocks;
	var $_pagers;

	function __construct($filename, $blocks, $files, $pagers) {
		$this->_filename = $filename;
		$this->_blocks = $blocks;
		$this->_files = $files;
		$this->_pagers = $pagers;
	}

	function isVisible($id, $default = TRUE) {
		if (isset($this->_blocks[$id]))
			$default = $this->_blocks[$id];

		return $default;
	}

	function getFile($id, $file) {
		if (isset($this->_files[$id]))
			$file = dirname($this->_filename) . '/' . $this->_files[$id];
		else if ($file)
			$file = dirname($this->_filename) . '/' . $file;

		return $file;
	}

	function getPager($id) {
		$ret = FALSE;
		if (isset($this->_pagers[$id])) {
			$ret = &$this->_pagers[$id];
		}
		return $ret;
	}
}

class FATemplateScope extends FAObject {
	var $_scope;
	var $_lists;
	var $_keys;
	
	function __construct($vars, $lists) {
		$this->_scope[] = $vars;
		$this->_lists = $lists;
		$this->_keys = array();
	}
	
	function getVar($name) {
		$ret = FALSE;
		
		for ($i = count($this->_scope) - 1; $i >= 0; $i--) {
			if (isset($this->_scope[$i][$name])) {
				$ret = &$this->_scope[$i][$name];
				break;
			}
		}
		
		return $ret;
	}

	function setVar($name, $value) {
		$this->_scope[count($this->_scope)][$name] = $value;
	}

	function push($vars) {
		if (is_array($vars)) {
			array_push($this->_scope, $vars);
			return TRUE;
		}
	}
	
	function pop() {
		array_pop($this->_scope);
	}
	
	function listBegin($name) {
		$ret = FALSE;

		if (isset($this->_lists[$name])) {
			$this->_lists[$name]->reset();
			$this->_keys[$name] = -1;
			$ret = TRUE;
		}
		return $ret;
	}

	function listBeginNew($name, &$list) {
		//var_dump($list);
		if ($list != NULL && is_a($list, 'FAIterator')) {
			$this->_lists[$name] = &$list;
		}

		return $this->listBegin($name);
	}
	
	function listHasNext($name) {
		$ret = FALSE;
		
		if ($list = &$this->_lists[$name]) {
			$ret = $list->hasNext();
		}
		
		return $ret;
	}
	
	function listKey($name) {
		$ret = -1;
		
		if (isset($this->_keys[$name])) {
			$ret = $this->_keys[$name];
		}
		
		return $ret;
	}
	
	function listNext($name) {
		$ret = FALSE;
		
		if ($list = &$this->_lists[$name]) {
			$scope = $list->next();

			if (is_array($scope)) {
				$this->_keys[$name]++;
				$this->push($scope);
				$ret = TRUE;
			}
		}
		
		return $ret;
	}
}

class FATemplate extends FAObject {
	var $_force = FALSE;
	var $_cache = TRUE;

	var $_lists = array();
	var $_blocks = array();
	var $_files = array();
	var $_vars = array();
	var $_pagers = array();

	var $_compiler;
	
	function __construct($flags = 0) {
		if ($flags & FA_FORCE) $this->_force = TRUE;
		if ($flags & FA_NOCACHE) $this->_cache = FALSE;

		$this->_compiler = &new FATemplateCompiler();
	}

	function getCompiledFilename($filename) {
		return dirname($filename) . '/compiled/' . basename($filename);
	}

	function getTemplateCompiler() {
		$ret = &$this->_compiler;
		return $ret;
	}

	function isCompiled($filename) {
		$compiled_file = $this->getCompiledFilename($filename);
		$compiled = FALSE;

		if (is_readable($compiled_file) && filemtime($compiled_file) > filemtime($filename))
			$compiled = TRUE;

		return $compiled;
	}
		
	function render($filename, $scope = NULL, $runtime = NULL) {
		if (!is_readable($filename)) trigger_error("Template does not exist or is not readable: $filename", E_USER_ERROR);
		
		if ($scope == NULL)
			$scope = &new FATemplateScope($this->_vars, $this->_lists);
		if ($runtime == NULL)
			$runtime = &new FATemplateRuntime($filename, $this->_blocks, $this->_files, $this->_pagers);
		
		$template = $this;

		if (!$this->_force && $this->isCompiled($filename)) {
			include $this->getCompiledFilename($filename);
		} else {
			$compiler = $this->getTemplateCompiler();
			$buffer = $compiler->compile($filename);

			$compiled_file = $this->getCompiledFilename($filename);
			if ($this->_cache) {
				$this->writeBuffer($compiled_file, $buffer);
			}

			//$buffer		= preg_replace('~&amp;~i', '&', $buffer);
			//$buffer		= preg_replace('~&~', '&amp;', $buffer);

			//echo "<pre>$buffer</pre>";
			
			eval("?> $buffer");
			// <?php

		}
	}

	function run($filename) {
		ob_start();
		
		// Prevent template compiling
		$old_cache		= $this->_cache;
		$this->_cache	= FALSE;

		// render the template
		$this->render($filename);

		$buffer = ob_get_contents();
		ob_end_clean();
		
		// reset the cache setting
		$this->_cache	= $old_cache;

		return $buffer;
	}

	function writeBuffer($filename, $buffer) {
		$ret = TRUE;
		if (!is_dir(dirname($filename))) {
			$mask = umask(0);

			if (!mkdir(dirname($filename), 0777)) {
				// TODO: send error to admin here
				//trigger_error("Unable to create the compiled template cache: " . dirname($filename), E_USER_WARNING);
				$ret = FALSE;
			}
			
			__chmod(dirname($filename), 0777);

			umask($mask);
		}

		if (!is_writable(dirname($filename))) {
			
			__chmod(dirname($filename), 0777);
			
			// means that the chmod function is not working.
			if (!is_writable(dirname($filename))) {
				// TODO: send error to admin here
				//trigger_error("Unable to write to the compiled template cache: " . dirname($filename), E_USER_WARNING);
				$ret = FALSE;
			}
		}
		
		__chmod($filename, 0777);

		$fp = @fopen($filename, "w");

		if (!$fp) {
			//trigger_error("Unable to write to the compiled template: $filename", E_USER_ERROR);
			// TODO: send error to admin
			$ret = FALSE;
		} else {
			fwrite($fp, $buffer);
			fclose($fp);

			__chmod($filename, 0777);
		}
		//return $ret;
	}

	function getVar($name) {
		$value = '';

		if (isset($this->_vars[$name]))
			$value = $this->_vars[$name];

		return $value;
	}

	function setList($name, &$list) {
		$this->_lists[$name] = &$list;
	}
	
	function setFile($name, $file) {
		$this->_files[$name] = $file;
	}

	function getFile($name) {
		$ret = FALSE;
		
		if(isset($this->_files[$name]))
			$ret = $this->_files[$name];

		return $ret;
	}

	function setPager($name, &$pager) {
		$this->_pagers[$name] = &$pager;
	}
	
	function setVar($name, $value) {
		$this->_vars[$name] = $value;
	}
	
	function setVarArray($vars, $prefix = FALSE) {
		assert(is_array($vars));

		if ($prefix != FALSE) {
			$fixed = array();

			foreach ($vars as $key => $value)
				$fixed[$prefix.$key] = $value;

			$vars = $fixed;
		}

		$this->_vars = $vars + $this->_vars;
	}
	
	function setVisibility($name, $visibility) {
		$this->_blocks[$name] = (bool)$visibility;
	}
}

?>