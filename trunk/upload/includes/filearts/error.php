<?php
/**
* k4 Bulletin Board, error.php
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
* @version $Id$
* @package k42
*/

if (!defined('FILEARTS'))
	return;

class FAError extends FAObject {
	var $type;
	var $message;
	var $file;
	var $line;
	var $backtrace;

	function __construct($type, $message, $file, $line, $backtrace) {
		$this->type = $type;
		$this->message = $message;
		$this->file = $file;
		$this->line = $line;
		$this->backtrace = $backtrace;
	}
	
	function getArray() {
		return array(	'type' => $this->type,
						'message' => $this->message,
						'file' => $this->file,
						'line' => $this->line );
	}
		
	function getHtml() {
		switch ($this->type) {
			case E_USER_ERROR: return "<br />\n<b>Fatal error</b>: {$this->message} in <b>{$this->file}</b> on line <b>{$this->line}</b><br />\n";
			case E_WARNING:
			case E_USER_WARNING: return "<br />\n<b>Warning</b>: {$this->message} in <b>{$this->file}</b> on line <b>{$this->line}</b><br />\n";
			case E_NOTICE:
			case E_USER_NOTICE: return "<br />\n<b>Notice</b>: {$this->message} in <b>{$this->file}</b> on line <b>{$this->line}</b><br />\n";
			//default: return "<br />\n<b>Unknown</b>: {$this->message} in <b>{$this->file}</b> on line <b>{$this->line}</b><br />\n";
		}
	}
	
	function getBacktraceHtml() {
		$buffer = '';
				
		if (!empty($this->backtrace)) {
			$buffer .= "<b>Call stack:</b>\n<ul>\n";
			
			foreach ($this->backtrace as $call) {
				$function = $call['function'];
				
				if (isset($call['class']))
					$function = $call['class'] . $call['type'] . $function;
				
				$arglist = array();
				
				if (isset($call['args'])) {
					foreach ($call['args'] as $arg) {
						if (is_object($arg))
							$arglist[] = 'object ' . get_class($arg);
						else if (is_array($arg))
							$arglist[] = 'Array(' . count($arg) . ')';
						else
							$arglist[] = "'$arg'";
					}
				}
				
				$args = implode(', ', $arglist);
				
				$buffer .= "<li><b>$function($args)</b>";
				if (isset($call['file'], $call['line']))
					$buffer .= " in <b>{$call['file']}</b> on line <b>{$call['line']}</b>";
				
				$buffer .= "</li>\n";
			}
			
			$buffer .= "</ul>\n";
		}
		
		return $buffer;
	}
}

class FAErrorHandler {
	var $handlers = array();
	
	function FAErrorHandler() {
		set_error_handler(array($this, 'handleError'));
	}
	
	function &getInstance() {
		static $instance = NULL;
		
		if ($instance == NULL) $instance = new FAErrorHandler();
		
		return $instance;
	}
	
	function handleError($type, $message, $file, $line) {
		$instance = &$this->getInstance();

		if ($type & error_reporting()) {
			$backtrace = array_slice(debug_backtrace(), 2);
			$error = &new FAError($type, $message, $file, $line, $backtrace);

			foreach ($instance->handlers as $handler) {
				if (call_user_func($handler, $error))
					break; 
			}
								
			if (empty($instance->handlers)) {
				$html = $error->getHtml() . $error->getBacktraceHtml();
				
				if ($type & (E_ERROR | E_USER_ERROR))
					die ($html);
					
				echo $html;
			}
		}
	}
	
	function pushHandler($handler) {
		if (is_array($handler) && method_exists($handler[0], $handler[1])) {
			array_unshift($this->handlers, $handler);
		} else if (function_exists($handler)) {
			array_unshift($this->handlers, $handler);
		} else {
			trigger_error("Error handler function does not exist.", E_USER_WARNING);
		}
	}
	
	function popHandler() {
		array_shift($this->handlers);
	}
}

function push_error_handler($handler) {
	$instance = &FAErrorHandler::getInstance();
	$instance->pushHandler($handler);
}

function pop_error_handler() {
	$instance = &FAErrorHandler::getInstance();
	$instance->popHandler();
}


// TODO: Figure out why I need to do this 'hack'
//FAErrorHandler::getInstance();


?>
