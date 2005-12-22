<?php
/**
* k4 Bulletin Board, calendar.class.php
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
* @author Thasmo (thasmo at gmail dot com)
* @version $Id$
* @package k42
*/



if(!defined('IN_K4'))
	return;

class K4Calendar extends FAObject {
	
	/**
	 * array $cal		- An array of all of the days in a month
	 * int $daysInMonth	- The number of days in a month
	 * int $month		- The numerical value for the current month
	 * int $year		- the current year
	 * string $title	- The title of the month (month name, year, etc)
	 */

	var $cal = array();
	var $daysInMonth, $month, $year, $title;

	/**
	 * __construct() public method
	 *
	 * Set up the calendar
	 * if you're not passing anything in to the __construct
	 * by reference, that's all you need :D (even in php4)
	 */
	function __construct() {
		
		// create an array that has 42 arrays in it
		// this represents 1 months with padding days on each side.
		$this->cal = array_pad($this->cal, 42, array('day' => NULL,'year' => NULL,'month' => NULL) );
	
		// set up the date and time stuff
		$this->setDate(date("n", time()), date("Y", time()));
	}

	/**
	 *
	 * boolean setDate(int $month, int $year [, string $title]) public method
	 *
	 * int $month    - an integer value between 1 and 12 representing the month for the calendar
	 * int $year     - an integer value greater than or equal to 1975 representing the year for the calendar
	 * string $title - a string value to put in the title cell, cosmetic
	 *
	 * setDate() sets up the month and year for the object.
	 *
	 **/
	function setDate($month, $year, $title = NULL) {
		if ($month < 1 || $month > 12) {
			
			// some error here.
			//$this->error("Calendar::setDate() failed: month must be an integer from 1 to 12");
			return false;
		} else {
			$this->month=$month;
		}

		if ($year < 1975) {
			
			// some error here.
			//$this->error("Calendar::setDate() failed: year must be a valid 4 digit year on or after 1975");
			return false;
		} else {
			$this->year=$year;
		}

		if ($title === NULL) {
			$this->title = date("F Y", mktime(0,0,0,$this->month,1,$this->year));
		} else {
			$this->title = $title;
		}

		$this->daysInMonth = date("t", mktime(0,0,0,$this->month, 1, $this->year));
		return true;
	}
	
	/**
	 *
	 * void daysInMonth() private method
	 *
	 * this function populates a 42 element array which represents the cells in a calendar.  
	 * It offsets the days in the array so they allign properly in the cells of the calendar.
	 *
	 **/

	function daysInMonth() {
		
		// get the first day of the month
		$first_day = date("w", mktime(0,0,0,$this->month, 1, $this->year));
		
		// loop through the number of days in the month. This will start at
		// whatever the first day of the month is.
		for ($i = $first_day; $i < ($this->daysInMonth + $first_day); $i++) {
			
			// insert the current day into one of the arrays in $cal
			$this->cal[$i]['day'] = $i - $first_day + 1;
			$this->cal[$i]['month'] = $this->month;
			$this->cal[$i]['year'] = $this->year;
		}
	}

	function getDays() {
		$this->daysInMonth();
		return $this->cal;
	}
}

class K4CalendarIterator extends FAArrayIterator {
	var $settings;
	var $usergroups;
	 	
	function __construct($data) {
		global $_SETTINGS, $_USERGROUPS;
		
		$this->usergroups = $_USERGROUPS;
		$this->settings = $_SETTINGS;
		
		parent::__construct($data);
	}
	
	function current() {
		$temp = parent::current();
		
		$temp['iteration'] = parent::key();

		/* Return the formatted info */
		return $temp;
	}
}

?>