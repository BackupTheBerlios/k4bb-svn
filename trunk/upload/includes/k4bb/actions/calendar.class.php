<?php
/**
* k4 Bulletin Board, calendar.class.php
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
* @author Thasmo (thasmo at gmail dot com)
* @version $Id$
* @package k42
*/

if(!defined('IN_K4'))
	return;

class K4Calendar extends FAObject {
	var $data = array();
	var $start_day = 1; # 0 for Sunday, 1 for Monday
	var $years_future = 5;
	var $years_past = 2;
	
	var $timestamp;
	var $current_year;
	var $current_month;
	var $current_day;
	
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
			
		$this->setArray();
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
		return(
			$year !== NULL &&
			$year > 1975 &&
			$year <= ($this->current_year + $this->years_future) &&
			$year >= ($this->current_year - $this->years_past)
		);
	}
	
	function checkMonth($month = NULL) {
		return($month !== NULL && $month > 0 && $month <= 12);
	}
	
	function checkPrevYear($year = NULL) {
		if($year === NULL)
			$year = $this->year;
		
		if($this->month == 1 && $year <= ($this->current_year - $this->years_past))
			return -1;
	}
	
	function checkNextYear($year = NULL) {
		if($year === NULL)
			$year = $this->year;
		
		if($this->month == 12 && $year >= ($this->current_year + $this->years_future))
			return -1;
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
			$month = $this->year >= ($this->current_year + $this->years_future) ? 12 : 1;
		else
			$month += 1;
		
		return $month;
	}
	
	function getPrevMonth($month = NULL) {
		if($month === NULL)
			$month = $this->month;
		
		if($month == 1)
			$month = $this->year <= ($this->current_year - $this->years_past) ? 1 : 12;
		else
			$month -= 1;
		
		return $month;
	}
	
	function getNextYear($year = NULL) {
		if($year === NULL)
			$year = $this->year;
		
		if($this->month == 12)
			$year += $this->year < ($this->current_year + $this->years_future) ? 1 : 0;
		
		return $year;
	}
	
	function getPrevYear($year = NULL) {
		if($year === NULL)
			$year = $this->year;
		
		if($this->month == 1)
			$year -= $this->year > ($this->current_year - $this->years_past) ? 1 : 0;
		
		return $year;
	}
	
	/**
	*
	* void setArray() private method
	*
	* this function populates a 42 element array which represents the cells in a calendar.  
	* It offsets the days in the array so they allign properly in the cells of the calendar.
	*
	**/
	 
	# !!!Known Bugs: When using start day Monday (=1) then month which start with Sunday (=0) display wrong!
	function setArray() {
		$days = date('t', mktime(0, 0, 0, $this->month, 1, $this->year)); # total days of the month to display
		$first = date('w', mktime(0, 0, 0, $this->month, 1, $this->year)); # number of the first day in the month to display
		
		# Arrays to fill up the display-array: $cal. Haven't found any function for this yet. *cough*
		$fill[1] = array(0 => 6, 1 => 0, 2 => 1, 3 => 2, 4 => 3, 5 => 4, 6 => 5);
		$fill[0] = array(0 => 0, 1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6);
		
		$start = $fill[$this->start_day][$first];
		$lenght = $days + $fill[$this->start_day][$first];
		
		# Calculate the cells to fill up
		$rest = 7 - $lenght % 7;
		$lenght += $rest == 7 ? 0 : $rest;
		
		$this->data = array_pad($this->data, $lenght, array('day' => NULL, 'year' => NULL, 'month' => NULL));
		
		// loop through the number of days in the month. This will start at
		// whatever the first day of the month is.
		for($i = $start; $i < $lenght; $i++) {
			
			#$this->data[$i]['week'] = k4; # Tryin' to get the week into the array. =o(
			
			if($i < $start + $days) {
				// insert the current day into one of the arrays in $data
				$this->data[$i]['day'] = $i + 1 - $start;
				$this->data[$i]['month'] = $this->month;
				$this->data[$i]['year'] = $this->year;
			}
		}
	}
	
	function getData() {
		return $this->data;
	}
	
	# TODO: Language support and better routine thou. *cough*
	function getWeekdays() {
		$week = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
		
		$calc = $this->start_day == 0 ? 1 : 0;
		
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