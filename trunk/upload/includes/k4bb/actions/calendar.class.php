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
	var $start_day = 1;
	var $cal = array();
	var $timestamp, $current_year, $current_month, $current_day, $daysInMonth;
	
	/**
	 * __construct() public method
	 *
	 * Set up the calendar
	 * if you're not passing anything in to the __construct
	 * by reference, that's all you need :D (even in php4)
	 */
	function __construct($year = NULL, $month = NULL, $day = NULL) {
		$this->timestamp = time();
		$this->current_year = date('Y', $this->timestamp);
		$this->current_month = date('n', $this->timestamp);
		$this->current_day = date('j', $this->timestamp);
		
		// create an array that has 42 arrays in it
		// this represents 1 months with padding days on each side.
		$this->cal = array_pad($this->cal, 42, array('day' => NULL, 'year' => NULL, 'month' => NULL));
		
		// set up the date and time stuff
		$this->setCalendar($year, $month, $day);
	}

	/**
	 *
	 * boolean setDate(int $month [, int $year, string $title]) public method
	 *
	 * int $month    - an integer value between 1 and 12 representing the month for the calendar
	 * int $year     - an integer value greater than or equal to 1975 representing the year for the calendar
	 * string $title - a string value to put in the title cell, cosmetic
	 *
	 * setDate() sets up the month and year for the object.
	 *
	 **/
	function setCalendar($year = NULL, $month = NULL, $day = NULL) {
		# Set Year
		if(!$this->checkYear($year))
			$this->setYear();
		else
			$this->setYear($year);
		
		# Set Month
		if(!$this->checkMonth($month))
			$this->setMonth();
		else
			$this->setMonth($month);
		
		# Set Day
		if(!$this->checkDay($day))
			$this->setDay();
		else
			$this->setDay($day);
		
		$this->daysInMonth = date('t', mktime(0, 0, 0, $this->month, 1, $this->year));
	}
	
	/**
	*
	* int getDay() public method
	*
	* This method returns the number of a day for a given timestamp.
	*
	**/
	function checkDay($day = NULL, $month = NULL) {
		if($day === NULL)
			$day = $this->current_day;
		
		if($month === NULL)
			$month = $this->current_month;
		
		$max = date('t', mktime(0, 0, 0, $month, 1, 2005));
		
		return($day > 0 && $day <= $max);
	}
	
	function checkYear($year = NULL) {
		return($year !== NULL && $year > 1975);
	}
	
	function checkMonth($month = NULL) {
		return($month !== NULL && $month > 0 && $month <= 12);
	}
	
	function setYear($year = NULL) {
		if($year === NULL)
			$this->year = $this->current_year;
		else
			$this->year = $year;
	}
	
	function setMonth($month = NULL) {
		if($month === NULL)
			$this->month = $this->current_month;
		else
			$this->month = $month;
	}
	
	function setDay($day = NULL) {
		if($day === NULL)
			$this->day = $this->current_day;
		else
			$this->day = $day;
	}
	
	function getYear() {
		return $this->year;
	}
	
	function getMonth() {
		return $this->month;
	}
	
	function getDay() {
		return $this->day;
	}
	
	function getNextMonth($month = NULL) {
		if($month === NULL)
			$month = $this->month;
		
		if($month == 12)
			$month = 1;
		else
			$month += 1;
		
		return $month;
	}
	
	function getPrevMonth($month = NULL) {
		if($month === NULL)
			$month = $this->month;
		
		if($month == 1)
			$month = 12;
		else
			$month -= 1;
		
		return $month;
	}
	
	function getNextYear($year = NULL) {
		if($year === NULL)
			$year = $this->year;
		
		if($this->month == 12)
			$year += 1;
		
		return $year;
	}
	
	function getPrevYear($year = NULL) {
		if($year === NULL)
			$year = $this->year;
		
		if($this->month == 1)
			$year -= 1;
		
		return $year;
	}
	
	/**
	 *
	 * void daysInMonth() private method
	 *
	 * this function populates a 42 element array which represents the cells in a calendar.  
	 * It offsets the days in the array so they allign properly in the cells of the calendar.
	 *
	 **/
	function setArray() {
		
		// get the first day of the month
		$first_day = date('w', mktime(0, 0, 0, $this->month, 1, $this->year));
		
		// loop through the number of days in the month. This will start at
		// whatever the first day of the month is.
		for ($i = $first_day; $i < ($this->daysInMonth + $first_day); $i++) {
			
			// insert the current day into one of the arrays in $cal
			$this->cal[$i]['day'] = $i - $first_day + 1;
			$this->cal[$i]['month'] = $this->month;
			$this->cal[$i]['year'] = $this->year;
		}
	}
	
	function getData() {
		$this->setArray();
		return $this->cal;
	}
	
	function getWeekdays($start = 0) {
		$week = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
		
		if($start < 0 || $start > 1)
			$start = 0;
		
		$calc = $start == 0 ? 1 : 0;
		
		for($i = 1; $i <= 7; $i++) {
			$array[$i]['weekday'] = $week[$i - $calc];
		}
		
		return $array;
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