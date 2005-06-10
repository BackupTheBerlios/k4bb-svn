<?php
/**
* k4 Bulletin Board, controller.php
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
* @version $Id$
* @package k42
*/
 
if (!defined('FILEARTS'))
	return;

define('FA_EVENT_VAR', 'act');

define('FA_ACTION', 'FAAction');
define('FA_VIEW', 'FAView');

class FAController extends FAObject {
	var $_unsatisfied = array();
	var $_filters = array();

	var $_actions = array();
	var $_request = array();
	
	var $_defaultEvent;
	var $_invalidAction;
	
	function __construct() {
		// Both actions are invalid until made otherwise
		$this->_invalidAction = &new FAInvalidAction();
	}
	
	function &getAction($event) {
		// Start with an invalid action
		$action = &$this->_invalidAction;
		
		// Attempt to set a valid action
		if (isset($this->_actions[$event]))
			$action = &$this->_actions[$event];
			
		return $action;
	}

	function &getRequest() {
		return $this->_request;
	}
	
	function addFilter(&$filter) {
		assert(is_a($filter, 'FAFilter'));
		
		$id = $filter->getId();
		$dependencies = $filter->getDependencies();
		$satisfied = array();
		$inserted = FALSE;

		foreach ($this->_filters as $pos => $current) {
			$name = $current->getId();

			if (isset($this->_unsatisfied[$name])) {
				// Check whether the new filter satisfies one of the current filter's
				// dependencies
				if (($key = array_search($id, $this->_unsatisfied[$name])) !== FALSE) {
					unset($this->_unsatisfied[$name][$key]);

					// Insert the filter if it has not already be inserted
					if (!$inserted) {
						// Insert the filter at the current position
						array_splice($this->_filters, $pos, 0, array(&$filter));

						$inserted = TRUE;
					}
				}

				if (empty($this->_unsatisfied[$name]))
					unset($this->_unsatisfied[$name]);
			}
			
			// Check whether the current filter satisfies one of the new filter's
			// dependencies
			if (($key = array_search($name, $dependencies)) !== FALSE) {
				// Check for circular dependencies.  ie: the new filter has already
				// been added to satisfy a dependency, but one of its dependencies
				// is after it
				if ($inserted)
					trigger_error("Circular dependency for $id and $name", E_USER_ERROR);
				else
					unset($dependencies[$key]);

				if ($name == $id && !$inserted && empty($dependencies)) {
					// Insert the filter after the current position
					array_splice($this->_filters, $pos + 1, 0, array(&$filter));

					$inserted = TRUE;
				}
			}
		}

		// Add the filter if it has not yet been added
		if (!$inserted)
			$this->_filters[] = &$filter;

		if (!empty($dependencies))
			$this->_unsatisfied[$id] = $dependencies;
	}
	
	function setDefaultEvent($event) {
		$this->_defaultEvent = $event;
	}
	
	function setInvalidAction(&$action) {
		assert(is_a($action, 'FAAction'));
		
		$this->_invalidAction = &$action;
	}
	
	function setAction($event, &$action) {
		assert(is_a($action, 'FAAction'));
		
		$this->_actions[$event] = &$action;
	}

	function execute() {
		// Start with the default event
		$event = $this->_defaultEvent;
		$request = &$this->getRequest();
		
		if (isset($_GET[FA_EVENT_VAR])) {
			$event = $_GET[FA_EVENT_VAR];
		}
		
		$request['event'] = $event;
		
		$action = &$this->getAction($event);

		if (!empty($this->_unsatisfied)) {
			list($filter, $unsatisfied) = each($this->_unsatisfied);

			trigger_error("Unsatisfied dependencies for $filter: " . implode(', ', $unsatisfied), E_USER_ERROR);
		}

		foreach ($this->_filters as $filter) {
			// If the filter returns FALSE run the action immediately
			if ($filter->execute($action, $request))
				break;
		}

		$this->_runAction($action, $request);
	}
	
	function _runAction(&$action, &$request) {
		$return = $action->execute($request);

		if (is_a($return, 'FAAction'))
			$this->_runAction($return, $request);
	}
}

class FARequestFilter extends FAObject {

	function execute(&$request, $var) {
		trigger_error("Request filters should implement the execute method", E_USER_ERROR);
	}

	function getMessage($var) {
		$class = get_class($this);

		return "Failed constraint $class on $var";
	}
}

class FARequiredFilter extends FARequestFilter {

	function execute(&$request, $var) {
		return isset($request[$var]);
	}
}

class FARegexFilter extends FARequestFilter {
	var $_regex;

	function __construct($regex) {
		$this->_regex = $regex;
	}

	function execute(&$request, $var) {
		$value = (isset($request[$var])) ? $request[$var] : '';

		return (bool)preg_match($this->_regex, $value);
	}
}

class FALengthFilter extends FARequestFilter {
	var $_max;
	var $_min;

	function __construct($max, $min = 0) {
		$this->_max = $max;
		$this->_min = $min;
	}

	function execute(&$request, $var) {
		$value = (isset($request[$var])) ? $request[$var] : '';
		$length = strlen($value);
		
		return !($length > $this->_max || $length < $this->_min);
	}
}

class FACompareFilter extends FARequestFilter {
	var $_compare;

	function __construct($compare) {
		$this->_compare = $compare;
	}

	function execute(&$request, $var) {
		$value1 = (isset($request[$var])) ? $request[$var] : '';
		$value2 = (isset($request[$this->_compare])) ? $request[$this->_compare] : '';

		return ($value1 == $value2);
	}
}

class FAEvent extends FAObject {
	var $_failures = array();

	function addPostFilter($var, &$filter) {
		if (!$this->runPostFilter($var, $filter))
			$this->_failures[] = $filter->getMessage($var);
	}

	function addGetFilter($var, &$filter) {
		if (!$this->runGetFilter($var, $filter))
			$this->_failures[] = $filter->getMessage($var);
	}

	function getFailures() {
		return $this->_failures;
	}

	function hasFailures() {
		return !empty($this->_failures);
	}

	function runGetFilter($var, &$filter) {
		return (bool)$filter->execute($_GET, $var);
	}

	function runPostFilter($var, &$filter) {
		return (bool)$filter->execute($_POST, $var);
	}
}

class FAFilter extends FAEvent {
	
	function execute(&$action, &$request) {
		trigger_error("Filters should implement the execute() method", E_USER_NOTICE);
	}

	function getId() {
		return get_class($this);
	}

	function getDependencies() {
		return array();
	}
}

class FAAction extends FAEvent {

	function execute(&$request) {
		trigger_error("Actions should implement the execute() method", E_USER_NOTICE);
	}
}

class FAInvalidAction extends FAAction {
	
	function execute(&$request) {
		echo "TODO: Create a invalid action (404) template";
	}
}

?>