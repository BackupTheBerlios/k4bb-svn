<?php
/**
* k4 Bulletin Board, mimetype.php
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