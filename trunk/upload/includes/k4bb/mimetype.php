<?php
/**
* k4 Bulletin Board, mimetype.php
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
* @version $Id$
* @package k4-2.0-dev
*/

/**
 * mimetypes array
 * @author Peter Goodman
 */
$mimetypes = array (
			
	// images
	'bmp'	=> 'image/bmp',
	'cgm'	=> 'image/cgm',
	'gif'	=> 'image/gif',
	'jpg'	=> 'image/jpeg',
	'jpe'	=> 'image/jpeg',
	'jpeg'	=> 'image/jpeg',
	'png'	=> 'image/png',
	'tif'	=> 'image/tiff',
	'tiff'	=> 'image/tiff',
	'pict'	=> 'image/x-pict',
	'psd'	=> 'image/psd',

	// applications
	'php'	=> 'application/octet-stream',
	'asp'	=> 'application/octet-stream',
	'bin'	=> 'application/octet-stream',
	'exe'	=> 'application/octet-stream',
	'jsp'	=> 'application/octet-stream',
	'pdf'	=> 'application/pdf',
	'doc'	=> 'application/msword',
	'xls'	=> 'application/excel',
	'ai'	=> 'application/postscript',
	'eps'	=> 'application/postscript',
	'ps'	=> 'application/postscript',
	'rtf'	=> 'application/rtf',
	'gtar'	=> 'application/x-gtar',
	'gz'	=> 'application/x-gzip',
	'class'	=> 'application/x-java-vm',
	'ser'	=> 'application/x-java-serialized-object',
	'jar'	=> 'application/x-java-archive',
	'tar'	=> 'application/x-tar',
	'zip'	=> 'application/x-zip',

	// text
	'html'	=> 'text/html',
	'htm'	=> 'text/html',
	'txt'	=> 'text/plain',
	'rtf'	=> 'text/richtext',
	'rtx'	=> 'text/richtext',
	'sgml'	=> 'text/sgml',

	// audio
	'ua'	=> 'audio/basic',
	'wav'	=> 'audio/x-wav',
	'aiff'	=> 'audio/x-aiff',
	'mid'	=> 'audio/x-midi',
	'midi'	=> 'audio/x-midi',
	
	// video
	'mpg'	=> 'video/mpeg',
	'mpeg'	=> 'video/mpeg',
	'mpe'	=> 'video/mpeg',
	'qt'	=> 'video/quicktime',
	'mov'	=> 'video/quicktime',
	'avi'	=> 'video/x-msvideo',
	'movie'	=> 'video/x-sgi-movie',

	// unknown
	FALSE	=> 'application/octet-stream',
);

$GLOBALS['mimetypes']		= $mimetypes;

/**
 * Return the possible MIME type of a function
 * @param string filename		The name of the file to be delt with
 * @return string				Hopefully the MIME type of the file
 * @author Peter Goodman
 */
function get_mimetype($filename) {
	global $mimetypes;
	
	$ext		= file_extension($filename);

	$mimetype	= $mimetypes[FALSE];

	if(isset($mimetypes[$ext]))
		$mimetype	= $mimetypes[$ext];

	return $mimetype;
}

/**
 * Get the file extension from the MIME type
 * @param string mimetype		The files mime-type
 * @author Peter Goodman
 */
function get_fileextension($mimetype) {
	global $mimetypes;

	$mimetypes		= array_flip($mimetypes);

	$ext			= 'txt';

	if(isset($mimetypes[$mimetype]))
		$ext	= $mimetypes[$mimetype];

	return $ext;
}

?>