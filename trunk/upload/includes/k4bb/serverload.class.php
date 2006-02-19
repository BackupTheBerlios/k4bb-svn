<?php
/**
* k4 Bulletin Board, serverload.class.php
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
* @version $Id$
* @package k42
*/

if(!defined('IN_K4')) {
	return;
}

class K4ServerInfo {
	
	var $load_average;
	var $load_cpupercent;
	
	function K4ServerInfo() {
		$this->getOS();
	}
	
	function getOS() {
		
		$class		= 'Unknown';
		$results	= array('avg' => 0, 'cpupercent' => 0);
		
		switch(PHP_OS) {
			case 'BSD.common': { $class = 'BSD_Common'; break; }
			case 'Darwin': { $class = 'Darwin'; break; }
			case 'FreeBSD': { $class = 'FreeBSD'; break; }
			case 'HP-UX': { $class = 'HPUX'; break; }
			case 'Linux': { $class = 'Linux'; break; }
			case 'NetBSD': { $class = 'BSD_Common'; break; }
			case 'OpenBSD': { $class = 'OpenBSD'; break; }
			case 'SunOS': { $class = 'SunOS'; break; }
			case 'WINNT': { $class = 'WinNT'; break; }
		}

		$class = $class .'_Server';
	
		if(class_exists($class)) {
			$instance	= new $class;
			$results	= $instance->loadAverage(TRUE);
		}
		$this->load_average		= $results['avg'];
		$this->load_cpupercent	= isset($results['cpupercent']) ? $results['cpupercent'] : 0;
	}

	function loadAverage() {
		$avg = &$this->load_average;
		if(is_array($this->load_average)) {
			$sum = array_sum($this->load_average);
			$avg = $sum / count($this->load_average);
		}
		return $avg;
	}
	function cpuPercent() {
		return $this->load_cpupercent;
	}
}

/**
 * This is ALL derived from phpSysInfo - A PHP System Information Script
 * http://phpsysinfo.sourceforge.net/
 * phpSysInfo is licensed under the GNU General Public License
 */

// Find a system program.  Do path checking
function find_program ($program) {
	$path = array('/bin', '/sbin', '/usr/bin', '/usr/sbin', '/usr/local/bin', '/usr/local/sbin');

	if(function_exists("is_executable")) {
		while ($this_path = current($path)) {
			if(is_executable("$this_path/$program")) {
				return "$this_path/$program";
			} 
			next($path);
		} 
	} else {
		return strpos($program, '.exe');
	}

	return;
} 

// Execute a system program. return a trim()'d result.
// does very crude pipe checking.  you need ' | ' for it to work
// ie $program = execute_program('netstat', '-anp | grep LIST');
// NOT $program = execute_program('netstat', '-anp|grep LIST');
function execute_program ($program, $args = '') {
	$buffer = '';
	$program = find_program($program);

	if (!$program) {
		return;
	} 
	// see if we've gotten a |, if we have we need to do patch checking on the cmd
	if ($args) {
		$args_list = split(' ', $args);
		for ($i = 0; $i < count($args_list); $i++) {
			if ($args_list[$i] == '|') {
				$cmd = $args_list[$i + 1];
				$new_cmd = find_program($cmd);
				$args = ereg_replace("\| $cmd", "| $new_cmd", $args);
			} 
		} 
	} 
	// we've finally got a good cmd line.. execute it
	if ($fp = popen("$program $args", 'r')) {
		while (!feof($fp)) {
			$buffer .= fgets($fp, 4096);
		} 
		return trim($buffer);
	} 
} 

// interface
class ServerLoad {
	function loadAverage() {
		return TRUE;
	}
}

// WINNT implementation written by Carl C. Longnecker, longneck@iname.com
class WinNT_Server extends ServerLoad {
	
	var $server;
	
	function WinNT_Server() {
		$this->server = new COM("WinMgmts:\\\\.");
	}

	function loadAverage($bar = false) {
		$objInstance	= $this->server->InstancesOf("Win32_Processor");
		$results		= array();
		foreach ($objInstance as $obj) {
			$results['avg'][] = $obj->LoadPercentage;
		}
		if ($bar) {
			$results['cpupercent'] = array_sum($results['avg']);
		} 
		// while
		return $results;
	}
}

class SunOS_Server extends ServerLoad {
	
	// Extract kernel values via kstat() interface
	function kstat ($key) {
		$m = execute_program('kstat', "-p d $key");
		list($key, $value) = split("\t", trim($m), 2);
		return $value;
	}

	function loadAverage($bar = false) {
		$load1	= $this->kstat('unix:0:system_misc:avenrun_1min');
		$load5	= $this->kstat('unix:0:system_misc:avenrun_5min');
		$load15 = $this->kstat('unix:0:system_misc:avenrun_15min');
		$results['avg'] = array($load1, $load5, $load15);
		return $results;
	} 
}

class OpenBSD_Server extends ServerLoad {
	function loadAverage() {
		return array('avg' => 0);
	}
}

class NetBSD_Server extends ServerLoad {
	function loadAverage() {
		return array('avg' => 0);
	}
}

class Linux_Server extends ServerLoad {
	function loadAverage($bar = false) {
		$results = array();
		if ($fd = @fopen('/proc/loadavg', 'r')) {
			$results['avg'] = preg_split("/\s/", fgets($fd, 4096),4);
			unset($results['avg'][3]);	// don't need the extra values, only first three
			fclose($fd);
		} else {
			$results['avg'] = array('N.A.', 'N.A.', 'N.A.');
		} 
		if ($bar) {
			if ($fd = @fopen('/proc/stat', 'r')) {
				fscanf($fd, "%*s %Ld %Ld %Ld %Ld", $ab, $ac, $ad, $ae);
				// Find out the CPU load
				// user + sys = load 
				// total = total
				$load = $ab + $ac + $ad;	// cpu.user + cpu.sys
				$total = $ab + $ac + $ad + $ae;	// cpu.total
				fclose($fd);

				// we need a second value, wait 1 second befor getting (< 1 second no good value will occour)
				sleep(1);
				$fd = fopen('/proc/stat', 'r');
				fscanf($fd, "%*s %Ld %Ld %Ld %Ld", $ab, $ac, $ad, $ae);
				$load2 = $ab + $ac + $ad;
				$total2 = $ab + $ac + $ad + $ae;
				$results['cpupercent'] = (100*($load2 - $load)) / ($total2 - $total);
				fclose($fd);
			}
		}
		return $results;
	}
}

class HPUX_Server extends ServerLoad {
	function loadAverage() {
		$results	= array();
		$ar_buf		= array();
		$buf		= execute_program('uptime');
		if (preg_match("/average: (.*), (.*), (.*)$/", $buf, $ar_buf)) {
			$results['avg'] = array($ar_buf[1], $ar_buf[2], $ar_buf[3]);
		} else {
			$results['avg'] = array('N.A.', 'N.A.', 'N.A.');
		} 
		return $results;
	}
}

class FreeBSD_Server extends ServerLoad {
	function loadAverage() {
		return array('avg' => 0);
	}
}

class Darwin_Server extends ServerLoad {
	function loadAverage() {
		return array('avg' => 0);
	}
}

class BSD_Common_Server extends ServerLoad {
	function grab_key ($key) {
		return execute_program('sysctl', "-n $key");
	}
	function loadAverage($bar = false) {
		$s = $this->grab_key('vm.loadavg');
		$s = ereg_replace('{ ', '', $s);
		$s = ereg_replace(' }', '', $s);
		$results['avg'] = explode(' ', $s);

		if ($bar) {
			if ($fd = $this->grab_key('kern.cp_time')) {
				sscanf($fd, "%*s %Ld %Ld %Ld %Ld", $ab, $ac, $ad, $ae);
				// Find out the CPU load
				// user + sys = load
				// total = total
				$load = $ab + $ac + $ad;        // cpu.user + cpu.sys
				$total = $ab + $ac + $ad + $ae; // cpu.total

				// we need a second value, wait 1 second befor getting (< 1 second no good value will occour)
				sleep(1);
				$fd = $this->grab_key('kern.cp_time');
				sscanf($fd, "%*s %Ld %Ld %Ld %Ld", $ab, $ac, $ad, $ae);
				$load2 = $ab + $ac + $ad;
				$total2 = $ab + $ac + $ad + $ae;
				$results['cpupercent'] = (100*($load2 - $load)) / ($total2 - $total);
			}
		}
		return $results;
	}
}

class Unknown_Server extends ServerLoad {
	function loadAverage() {
		return array('avg' => 0);
	}
}

?>