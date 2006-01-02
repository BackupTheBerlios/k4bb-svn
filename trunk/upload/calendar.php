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
* @author Thasmo (thasmo at gmail dot com)
* @author Peter Goodman
* @version $Id: k4_template.php 134 2005-06-25 15:41:13Z Peter Goodman $
* @package k4-2.0-dev
*/

require "includes/filearts/filearts.php";
require "includes/k4bb/k4bb.php";
require K4_BASE_DIR. '/actions/calendar.class.php';

class K4DefaultAction extends FAAction {
	function mini_month($m, $y, &$request) {
		$c			= &new K4Calendar($y, $m, 1);
		$month_it	= &new K4CalendarIterator($c->getData(), $c->getWeek($c->month, 1, $c->year), array());
		$request['template']->setList('calendar', $month_it);
		$request['template']->setVar('month_label', date('F', mktime(0, 0, 0, $c->getMonth(), 1, $c->getYear())));
		$html		= $request['template']->run(BB_BASE_DIR .'/templates/'. $request['user']->get('templateset') .'/calendar_mini.html');
		
		return $html;
	}
	function execute(&$request) {
		
		global $_QUERYPARAMS, $_USERGROUPS;

		k4_bread_crumbs($request['template'], $request['dba'], 'L_CALENDAR');
		
		# get the year month and day from request vars
		$year		= isset($_REQUEST['y']) && intval($_REQUEST['y']) > 0 ? $_REQUEST['y'] : date('Y', time());
		$month		= isset($_REQUEST['m']) && intval($_REQUEST['m']) > 0 ? $_REQUEST['m'] : date('j', time());
		$day		= isset($_REQUEST['d']) && intval($_REQUEST['d']) > 0 ? $_REQUEST['d'] : 1;
		
		# new k4Calendar instance
		$c			= &new K4Calendar($year, $month, $day);

		# The next and previous months, do this all first.
		$year		= $month == 1 ? $year-1 : $year;
		$html		= $this->mini_month($c->getPrevMonth(), $year, $request);
		$request['template']->setVar('prev_month_cal', $html);
		$year		= $month == 12 ? $year+1 : $year;
		$html		= $this->mini_month($c->getNextMonth(), $c->getPrevYear(), $request);
		$request['template']->setVar('next_month_cal', $html);

		# Get user birthdays
		$birthdays	= $request['dba']->executeQuery("SELECT {$_QUERYPARAMS['user']}{$_QUERYPARAMS['userinfo']} FROM ". K4USERS ." u LEFT JOIN ". K4USERINFO ." ui ON u.id=ui.user_id WHERE ui.birthday LIKE '". str_pad($month, 2, '0', STR_PAD_LEFT) ."/%'");
		$bdays		= array();
		
		if($birthdays->hasNext()) {
			while($birthdays->next()) {
				$user					= $birthdays->current();
				$parts					= explode("/", $user['birthday']);
				
				$group					= get_user_max_group($user, $_USERGROUPS);
				$user['group_color']	= !isset($group['color']) || $group['color'] == '' ? '000000' : $group['color'];
				
				$user['age']			= $year - intval($parts[2]);
				$bdays[$parts[1]][]		= $user;
			}
		}
		
		# Add the iterator to the template
		$c->month		= $month;
		$c->year		= $year;
		$iteration_c = new K4CalendarIterator($c->getData(), $c->getWeek($month, $day, $year), $bdays);
		$iteration_d = new FAArrayIterator($c->getWeekdays());
		
		$request['template']->setList('calendar', $iteration_c);
		$request['template']->setList('weekdays', $iteration_d);
		
		# The rest
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