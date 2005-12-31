<?php
/**
* k4 Bulletin Board, filename.php
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
* @version $Id: k4_template.php 134 2005-06-25 15:41:13Z Peter Goodman $
* @package k4-2.0-dev
*/

require "includes/filearts/filearts.php";
require "includes/k4bb/k4bb.php";
require K4_BASE_DIR. '/actions/calendar.class.php';

class K4DefaultAction extends FAAction {
	function execute(&$request) {
		k4_bread_crumbs($request['template'], $request['dba'], 'L_CALENDAR');
		
		$year = isset($_REQUEST['y']) ? $_REQUEST['y'] : NULL;
		$month = isset($_REQUEST['m']) ? $_REQUEST['m'] : NULL;
		$day = isset($_REQUEST['d']) ? $_REQUEST['d'] : NULL;
		
		$c = &new K4Calendar($year, $month, $day);
		$iteration_c = new K4CalendarIterator($c->getData());
		$iteration_d = new FAArrayIterator($c->getWeekdays());
		
		$request['template']->setList('calendar', $iteration_c);
		$request['template']->setList('weekdays', $iteration_d);
		
		$request['template']->setVar('month_label', date('F', mktime(0, 0, 0, $c->getMonth(), 1, $c->getYear())));
		$request['template']->setVar('year_label', $c->getYear());
		$request['template']->setVar('month_next', $c->getNextMonth());
		$request['template']->setVar('month_prev', $c->getPrevMonth());
		$request['template']->setVar('year_next', $c->getNextYear());
		$request['template']->setVar('year_prev', $c->getPrevYear());
		
		$request['template']->setVar('nav_prev', $c->checkPrevYear());
		$request['template']->setVar('nav_next', $c->checkNextYear());
		
		$request['template']->setFile('content', 'calendar_index.html');
	}
}

$app = &new K4Controller('forum_base.html');
$app->setAction('', new K4DefaultAction);
$app->setDefaultEvent('');

$app->execute();

?>