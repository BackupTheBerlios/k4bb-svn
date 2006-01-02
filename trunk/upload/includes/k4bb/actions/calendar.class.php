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
* @author Peter Goodman
* @version $Id$
* @package k42
*/

if(!defined('IN_K4'))
	return;

class K4Calendar extends FAObject {
	var $data = array();
	var $start_day = 0; # 0 for Sunday, 1 for Monday
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
	
	function getYear($month = NULL) {
		return $this->year;
	}
	
	function getMonth() {
		return $this->month;
	}
	
	function getDay() {
		return $this->day;
	}

	function getWeek($m, $d, $y) {
		$time = mktime(0,0,0,$m,$d,$y);
		$day_of_year = date('z', $time);
		$week_number = floor($day_of_year / 7);

		return $week_number;
	}

	function getWeekRange($m, $w, $y) {
		
	}
	
	function getNextMonth($month = NULL) {
		if($month === NULL)
			$month = $this->month;

		if($month == 12)
			$month = $this->year >= ($this->current_year + $this->years_future) ? 12 : 1;
		else
			$month++;
		
		return $month;
	}
	
	function getPrevMonth($month = NULL) {
		if($month === NULL)
			$month = $this->month;
		
		if($month == 1)
			$month = $this->year <= ($this->current_year - $this->years_past) ? 1 : 12;
		else
			$month--;
		
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
	function setArray() {
		
		$first_day_time = mktime(0, 0, 0, $this->month, 1, $this->year);
		
		$days = date('t', $first_day_time); # total days of the month to display
		$start = date('w', $first_day_time); # day of the week of the first day in the month

		# fix the offset of the start day, +1 represents the current day
		$start = $this->start_day == 1 ? (7 - ($start + 1)) : $start;
		
		# total number of days (days in month + offset of start day)
		$length = $start + $days; 
				
		# Calculate the cells to fill up
		$rest = 7 - $length % 7;
		$length += $rest == 7 ? 0 : $rest;
		
		$this->data = array_pad($this->data, $length, array('day' => NULL, 'year' => $this->year, 'month' => $this->month));
		
		// loop through the number of days in the month. This will start at
		// whatever the first day of the month is.
		for($i = $start; $i < $length; $i++) {
			
			#$this->data[$i]['week'] = k4; # Tryin' to get the week into the array. =o(
			
			if($i < $start + $days) {
				// insert the current day into one of the arrays in $data
				$this->data[$i]['day'] = $i + 1 - $start;
			}
		}
	}
	
	function getData() {
		return $this->data;
	}
	
	# TODO: Language support and better routine thou. *cough*
	function getWeekdays() {
		global $_LANG;

		$week = array('L_SUNDAY', 'L_MONDAY', 'L_TUESDAY', 'L_WEDNESDAY', 'L_THURSDAY', 'L_FIRDAY', 'L_SATURDAY', 'L_SUNDAY',);
		
		$calc = $this->start_day == 0 ? 1 : 0;
		
		for($i = 1; $i <= 7; $i++) {
			$array[$i]['weekday'] = $_LANG[$week[$i - $calc]];
		}
		
		return $array;
	}
}

class K4CalendarIterator extends FAArrayIterator {
	var $week, $iteration, $user_bdays;
	 	
	function __construct($data, $start_week, $user_bdays) {
		
		$this->week			= $start_week;
		$this->iteration	= 1;
		$this->user_bdays	= $user_bdays;

		parent::__construct($data);
	}
	
	function current() {
		$temp = parent::current();
		
		// set the week the the data
		$temp['week'] = $this->week;
		
		// set a users iterator
		$temp['user_bdays'] = 0;
		if(isset($this->user_bdays[$temp['day']])) {
			$temp['users'] = &new FAArrayIterator($this->user_bdays[$temp['day']]);
			$temp['user_bdays'] = 1;
		}

		// increment the week number
		if($this->iteration % 7 == 0) {
			$this->week++;
		}
		
		// increment something to keep track of how many days we have iterated over
		$this->iteration++;

		// Return the formatted info
		return $temp;
	}
}

?>