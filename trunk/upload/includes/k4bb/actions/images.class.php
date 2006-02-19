<?php
/**
* k4 Bulletin Board, images.class.php
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

class K4Image {
	
	//
	// Upload an image
	//
	function upload($input_name, $destination_name, $allowed_filetypes) {
		$ret		= TRUE;
		
		if($ret && !is_writeable(dirname($destination_name))) {
			$ret	= FALSE;
		}
		
		$filetype	= file_extension($_FILES[$input_name]['name']);

		if($ret && !in_array($filetype, $allowed_filetypes)) {
			$ret	= FALSE;
		}

		if($ret && !@move_uploaded_file($_FILES[$input_name]['tmp_name'], $destination_name)) {
			$ret	= FALSE;
		}

		return $ret;
	}

	//
	// Resize an image
	//
	function resize($file_name, $file_type, $curr_width, $curr_height, $max_width, $max_height, $return_contents = FALSE) {
		
		$mime_type		= get_mimetype($file_name);
		$mime_type		= $file_type != $mime_type ? $file_type : $mime_type;

		// do we have the right functions installed?
		if(!function_exists('imagecreate') || !function_exists('imagecopyresampled')) {
			return FALSE;
		}

		// use a bit of cross-multiplication to get the new image sizes
		if($curr_height >= $curr_width) {
			$new_height = intval($max_height);
			$new_width	= ceil(($curr_width / $curr_height) * $max_width);
		} else {
			$new_width = intval($max_width);
			$new_height	= ceil(($curr_height / $curr_width) * $max_height);
		}
		
		// this will end up being the quality for the jpg images
		$third_param		= FALSE;

		// get our old image
		switch(strtolower($file_type)) {
			case 'gif': {
				$image		= @imagecreatefromgif($file_name);
				break;
			}
			case 'jpg':
			case 'jpeg': {
				$file_type	= 'jpeg';
				$image		= @imagecreatefromjpeg($file_name);
				$third_param= 90; // quality
				break;
			}
			case 'png': {
				$image		= @imagecreatefrompng($file_name);
				break;
			}
			case 'wbmp':
			case 'bmp': {
				$file_type	= 'wbmp';
				$image		= @imagecreatefromwbmp($file_name);
				break;
			}
		}
		
		// do we have the image?
		if(!$image) {
			return FALSE;
		}
		
		// see what color type we can use to create the new image
		// either palette or true color
		$create_fn	= function_exists('imagecreatetruecolor') ? 'imagecreatetruecolor' : 'imagecreate';
		
		// create the new image
		$new_id		= $create_fn($new_width, $new_height);
		$new_image	= imagecopyresampled($new_id, $image, 0, 0, 0, 0, $new_width, $new_height, $curr_width, $curr_height);
		
		// start output buffering
		ob_start();

		// output the image
		$create_image = 'image'. $file_type;
		$create_image($new_id, FALSE, $third_param);
		
		// get the contents of the image
		$contents	= ob_get_contents();
		$file_size	= ob_get_length();

		// end output buffering
		ob_end_clean();
		
		

		// clear up memory
		imagedestroy($image);
		imagedestroy($new_id);
		
		// should we return that data already?
		if($return_contents) {
			return array(
					'x' => $new_width, 
					'y' => $new_height, 
					'mimetype' => $mime_type, 
					'size' => $file_size, 
					'contents' => $contents,
					);
		}
			
		// save the image
		
		__chmod($file_name, 0777);
		if(!is_writeable($file_name)) {
			return FALSE;
		}

		$fp = @fopen($file_name, 'w');
		
		if(!$fp) {
			return FALSE;
		}
		if(fwrite($fp, $contents) === FALSE) {
			return FALSE;
		}
		fclose($fp);
		
		// we're done!
		return TRUE;
	}

}

?>