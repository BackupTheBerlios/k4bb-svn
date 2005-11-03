<?php
/**
* k4 Bulletin Board, filearts.php
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
* @author Geoffrey Goodman
* @version $Id: filearts.php 110 2005-06-13 20:48:58Z Peter Goodman $
* @package k42
*/

define('FILEARTS', TRUE);
error_reporting(E_ALL ^ E_NOTICE);

define('FA_BASE_DIR', dirname(__FILE__));
define('FA_NL', "\r\n");

include FA_BASE_DIR . '/error.php';
include FA_BASE_DIR . '/iterator.php';
include FA_BASE_DIR . '/url.php';
include FA_BASE_DIR . '/controller.php';
include FA_BASE_DIR . '/session.php';
include FA_BASE_DIR . '/user.php';
include FA_BASE_DIR . '/timer.php';

include FA_BASE_DIR . '/database/database.php';
include FA_BASE_DIR . '/template/template.php';



class FAObject {
	function FAObject() {
		$args = func_get_args();
		call_user_func_array(array(&$this, '__construct'), $args);
	}

	function __construct() {
	}
}

?>