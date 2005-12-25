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
require K4_BASE_DIR. '/actions/calendar.class.php';

class K4DefaultAction extends FAAction {
	function execute(&$request) {
		k4_bread_crumbs($request['template'], $request['dba'], 'L_CALENDAR');
		
		$year = isset($_REQUEST['y']) ? $_REQUEST['y'] : NULL;
		$month = isset($_REQUEST['m']) ? $_REQUEST['m'] : NULL;
		$day = isset($_REQUEST['d']) ? $_REQUEST['d'] : NULL;
		
		$c = &new K4Calendar($year, $month, $day);
		$iteration = new K4CalendarIterator($c->getData());
		
		$request['template']->setVar('month_label', date('F', mktime(0, 0, 0, $c->getMonth(), 1, $c->getYear())));
		$request['template']->setVar('year_label', $c->getYear());
		
		$request['template']->setVar('month_next', $c->getNextMonth());
		$request['template']->setVar('month_prev', $c->getPrevMonth());
		$request['template']->setVar('year_next', $c->getNextYear());
		$request['template']->setVar('year_prev', $c->getPrevYear());
		
		$request['template']->setList('calendar', $iteration);
		
		$request['template']->setFile('content', 'calendar_index.html');
	}
}

$app = &new K4Controller('forum_base.html');
$app->setAction('', new K4DefaultAction);
$app->setDefaultEvent('');

$app->execute();

?>