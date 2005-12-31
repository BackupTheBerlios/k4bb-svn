<?php
/**
* k4 Bulletin Board, url.inc.php
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
* @author Geoffrey Goodman
* @author Peter Goodman
* @version $Id: url.php 158 2005-07-18 02:55:30Z Peter Goodman $
* @package k42
*/

if(!defined('FILEARTS'))
	return;

/**
 * @author			Geoffrey Goodman
 * @param scheme	The Scheme variable returned from the parse_url function
 * @param host		The host of the url
 * @param path		The path of the url
 * @param port		The port of the url, if any was defined
 * @param fragment	The fragment of the url
 */
class FAUrl extends FAObject {
	var $args = array();
	var $scheme;
	var $user;
	var $pass;
	var $host;
	var $port;
	var $path;
	var $file;
	var $anchor;

	function __construct($url) {
		
		// parse the url
		$query	= parse_url($url);

		if (isset($query['scheme']))
			$this->scheme = $query['scheme'];

		if (isset($query['user']))
			$this->user = $query['user'];

		if (isset($query['pass']))
			$this->pass = $query['pass'];

		if (isset($query['host']))
			$this->host = str_replace('/', '', $query['host']);

		if (isset($query['port']))
			$this->port = $query['port'];

		if (isset($query['path'])) {

			$path		= dirname($query['path']);
			
			if ($path == '/' || $path == '\\')
				$path = '';

			$this->path = str_replace('//', '/', $path);
			$this->file = str_replace('/', '', basename($query['path']));
		}

		if (isset($query['fragment']))
			$this->anchor = $query['fragment'];

		if (isset($query['query'])) {
			$args = explode('&', preg_replace('~&amp;~i', '&', $query['query']));
			
			foreach ($args as $arg) {
				if ($arg && $arg != '') {
					$temp = explode('=', $arg);

					if ($key = array_shift($temp))
						$this->args[$key] = (empty($temp)) ? '' : array_shift($temp);
				}
			}
		}
	}

	function __toString() {

		$url = '';
		
		//if ($this->scheme) $url .= "{$this->scheme}://";
		
		if ($this->user) {
			$url .= $this->user;
			if ($this->pass) $url .= ":{$this->pass}";
			if($this->user && $this->host) $url .= '@';
		}
		if ($this->host) $url .= $this->host ."/";
		if ($this->path) $url .= "{$this->path}/";
		if ($this->file) $url .= "{$this->file}";
		
		for ($i = 0; list($key, $value) = each($this->args); $i++) {
			if($value) {
				$url .= ($i > 0) ? "&amp;$key=$value" : "?$key=$value";
			}
		}
		if ($this->anchor) $url .= "#{$this->anchor}";
		
		$url = str_replace('//', '/', $url);

		if ($this->scheme) $url = "{$this->scheme}://{$url}";

		reset($this->args);

		return $url;
	}
}


?>