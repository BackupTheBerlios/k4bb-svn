<?php
/**
* k4 Bulletin Board, filename.php
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
* @version $Id: k4_template.php 134 2005-06-25 15:41:13Z Peter Goodman $
* @package k4-2.0-dev
*/

require "includes/filearts/filearts.php";
require "includes/k4bb/k4bb.php";

/**
 *
 * XHTML 1.0 Strict Calendar generation class
 * Benjamin Kuz <ben@slax0rnet.com>
 * http://ben.nullcreations.net
 *
 * Purpose: To generate a calendar given a month and year, and to render it in XHTML.
 *
 **/
 
class K4Calendar extends FAObject {

	var $month;
	var $year;
	var $output;
	var $cal;
	var $title;
	var $daysInMonth;
	var $events;
	var $errorReporting;
	
	function K4Calendar() {
		$this->__construct();
	}

	function __construct() {
		$this->setDate(date("n", time()), date("Y", time()));
		$this->events			=	array();
		$this->errorReporting	=	TRUE;
	}
	
	/**
	 *
	 * void setErrorReporting(boolean $bool) public method
	 *
	 * boolean $bool - set to boolean true if you want this feature enabled.  false for disabled.
	 *
	 * sets whether or not errors are outputted - this is enabled by default
	 *
	 **/
	 
	function setErrorReporting($bool) {
		if (!is_bool($bool)) {
			$this->error("Calendar::setErrorReporting() failed: argument must be a boolean value");
		}	else {
			$this->errorReporting=$bool;
		}
	}
	
	/**
	 *
	 * void error(string $msg) private method
	 *
	 * reports errors via echo if error reporting is turned on
	 *
	 **/
	 
	function error($msg) {
		if ($this->errorReporting === TRUE) {
			trigger_error("\n$msg\n", E_USER_ERROR);
		}
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
		if(intval($month) < 1) { 
			$this->month		= 12;
			$year--;
		} elseif(intval($month) > 12) {
			$this->month		= 1;
			$year++;
		} else {
			$this->month		= intval($month);
		}

		$this->year			= ($year < 1975) ? 1975 : intval($year);

		if ($title === NULL) {
			$this->title	= strftime("%B %Y", mktime(0, 0, 0, $this->month, 1, $this->year));
		} else {
			$this->title	=$title;
		}	
		$this->daysInMonth	=date("t", mktime(0, 0, 0, $this->month, 1, $this->year));
		
		return TRUE;
	}
	
	/**
	 *
	 * void renderCalendar() public method
	 *
	 * calling this function renders the calendar with the current attribute values and places the output
	 * in Calendar::output
	 *
	 **/
	 
	function renderCalendar() {
		$this->cal			= array();
		$this->cal			= array_pad($this->cal, 42, NULL);
		$this->output		= '';
		$this->monthToCal();
		$this->createOutput();
	}
	 
	/**
	 *
	 * void monthToCal() private method
	 *
	 * this function populates a 42 element array which represents the cells in a calendar.  It offsets the days
	 * in the array so they allign properly in the cells of the calendar.
	 *
	 **/
	 
	function monthToCal() {
		$first_day = date("w", mktime(0, 0, 0, $this->month, 1, $this->year));
		
		for ($x = $first_day; $x < ($this->daysInMonth + $first_day); $x++) {
			$this->cal[$x] = $x - $first_day + 1;
		}
	}
	
	/**
	 *
	 * void createOutput() private method
	 *
	 * This function constructs all of the HTML based upon the attributes provided, and places the results
	 * in Calendar::output
	 *
	 **/
	
	function createOutput() {
		$row			= 0;
		$eventsOutput	= '';
		$currentMonth	= date("n", time());
		$currentDay		= date("j", time());
		$currentYear	= date("Y", time());
		
		if($currentMonth == $this->month && $currentYear == $this->year) {
			$highlightToday = TRUE;
		} else {
			$highLightToday = FALSE;
		}
		$this->output.='<div id="calendar" class="calendar"><table border="0" class="cal" id="cal">'."\n";
		$days=array("Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat");
		$this->output.='<tr class="calTitle" id="calTitle">'."\n".'<td colspan="7">'."\n";
		$this->output.=$this->title;
		$this->output.="</td>\n</tr>\n";
		$this->output.='<tr class="calDaysTitleRow" id="calDaysTitleRow">'."\n";
		foreach ($days as $val) {
			$this->output.="<td>$val</td>\n";
		}
		$this->output.="</tr>\n".'<tr class="calDaysRow" id="calDaysRow0">'."\n";
		foreach($this->cal as $key=>$val) {
			if ($val===NULL) {
				$this->output.='<td class="noDay">&nbsp;</td>'."\n";
			} else {
				if (!isset($keyStart)) {
					$keyStart=$key;
				}
				if ($keyStart > 6) { $keyStart=0; }
				if ($highlightToday && $currentDay==$val) {
					$tdClass="today";
				} else {
					$tdClass=$days[$keyStart];
				}
				if ($this->hasEvents($val)) {
					$eventsOutput.=$this->renderEventsByDay($val);
					$thisDay='<a href="javascript:void(0)" class="eventLink" onclick="document.getElementById(\'eventsForDay'.$val.'\').style.display=\'block\'">'.$val.'</a>';
				} else {
					$thisDay=$val;
				}
				$this->output.="<td class=\"$tdClass\">$thisDay</td>\n";
				$keyStart++;
			}
			if ((($key+1)%7) == 0) {
				$row++;
				$this->output.="</tr>\n";
				if ($row!=6) {
					$this->output.="<tr class=\"calDaysRow\" id=\"calDaysRow$row\">\n";
				}
			}
		}
		$this->output.="</table>\n";
		$this->output.=$eventsOutput;
		$this->output.="</div>\n";
	}

	/**
	 *
	 * boolean addEvent(string $event, int $day) public method
	 *
	 * string $event - a string containing the description of the date event
	 * int $day      - an integer value representing the day the event should be added for
	 *
	 * adds an event to the calendar for a specified day
	 *
	 * returns: boolean true on success, boolean flase on failure
	 *
	 **/
	
	function addEvent($event, $day) {
		if ($day<1 || $day>$this->daysInMonth) {
			$this->error("Calendar::addEvent() falied: the specified day is not valid for this month.  only ".$this->daysInMonth." days in this month");
			return false;
		} else {
			$this->events[$day][]=$event;
			return true;
		}
	}
	
	/**
	 *
	 * boolean removeEvent(int $eventId, int $day) public method
	 *
	 * int $eventId - the eventId is the array key for the event contained within the array for the day
	 * int $day     - the day the event was scheduled for
	 *
	 * removes an event from the calendar
	 *
	 * returns: boolean true on success, boolean flase on failure
	 *
	 **/
	 
	function removeEvent($eventId, $day) {
		if (!isset($this->events[$day][$eventId])) {
			$this->error("Calendar::removeEvent() failed: no event exists at id $eventId on day $day");
			return false;
		} else {
			unset($this->events[$day][$eventId]);
			return true;
		}
	}
	
	
	/**
	 *
	 * mixed getEventsByDay(int $day) public method
	 *
	 * int $day - the day to retrieve the events list for
	 *
	 * gets an array of event descriptions for a specified day
	 *
	 * returns: an array of events for the specified day keyed on their eventIds on success, boolean false on failure
	 *
	 **/
	 
	function getEventsByDay($day) {
		if ($this->hasEvents($day)) {
			$events=$this->events[$day];
			return $events;
		} else {
			return false;
		}
	}
	
	
	/**
	 *
	 * boolean hasEvents(int $day) public method
	 *
	 * int $day - the day to check for events 
	 *
	 * checks to see if events exist for the specified day
	 *
	 * returns: boolean true if events exist, boolean false if they do not
	 *
	 **/
	
	function hasEvents($day) {
		if (!is_numeric($day)) {
			$this->error("Calendar::hasEvents() failed: argument must be an integer between 1 and 31");
			return false;
		}
		if (isset($this->events[$day])) {
			if (count($this->events[$day])>0) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	
	/**
	 *
	 * void resetEvents() public method
	 *
	 * clears all value out of the events array an prepares it for fresh events
	 *
	 **/
	 
	function resetEvents() {
		$this->events=array();
	}

	/**
	 *
	 * mixed renderEventsByDay(int $day) private method
	 *
	 * int $day - an integer value representing the day to render an events block for
	 *
	 * renders the html for a set of events based on a day
	 *
	 * returns: a string containing the events block on success, boolean false on failure
	 *
	 **/
	 
	function renderEventsByDay($day) {
		if ($this->hasEvents($day)) {
			$eventsOutput='<div class="events" id="eventsForDay'.$day.'" style="display: none">'."\n";
			$eventsOutput.='<a href="javascript:void(0)" onclick="document.getElementById(\'eventsForDay'.$day.'\').style.display=\'none\'">[close]</a><span> '.$this->title."</span>\n";
			foreach($this->events[$day] as $key=>$val) {
				$rowClass=(($key+1) % 2) ? "eventRow" : "altEventRow";
				$eventsOutput.='<div class="'.$rowClass.'"><span>'.$val.'</span></div>'."\n";
			}
			$eventsOutput.="</div>\n";
			return $eventsOutput;
		} else {
			return false;
		}
	}
	
	/**
	 *
	 * array getNextMonth() public method
	 *
	 * returns an array containing two key/value pairs, one containing the next month, and the year adjusted if nessisary
	 * format is $array['year'] and $array['month'] respectivly
	 *
	 **/
	 
	function getNextMonth() {
		$return=array();
		if ($this->month+1>12) {
			$return['month']=1;
			$return['year']=$this->year+1;
		} else {
			$return['month']=$this->month+1;
			$return['year']=$this->year;
		}
		return $return;
	}

	/**
	 *
	 * array getPrevMonth() public method
	 *
	 * returns an array containing two key/value pairs, one containing the previous month, and the year adjusted if nessisary
	 * format is $array['year'] and $array['month'] respectivly
	 *
	 **/
	 	
	function getPrevMonth() {
		$return=array();
		if ($this->month-1<1) {
			$return['month']=12;
			$return['year']=$this->year-1;
		} else {
			$return['month']=$this->month-1;
			$return['year']=$this->year;
		}
		return $return;
	}
}

class K4DefaultAction extends FAAction {
	function execute(&$request) {
				
		k4_bread_crumbs($request['template'], $request['dba'], 'L_CALENDAR');
		
		$calendar	= &new K4Calendar();
	}
}

$app = &new K4Controller('forum_base.html');
$app->setAction('', new K4DefaultAction);

$app->execute();

?>