<?php
/**
* k4 Bulletin Board, files.class.php
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
* @version $Id: files.class.php 144 2005-07-05 02:29:07Z Peter Goodman $
* @package k42
*/



if(!defined('IN_K4')) {
	return;
}

class AdminFileBrowser extends FAAction {
	function resize_image($image) {

		$max_width = 48;
		$max_height = 48;
		
		$size = @getimagesize($image);
		
		$cur_width = $size[0];
		$cur_height = $size[1];

		@$x_ratio = $max_width / $cur_width;
		@$y_ratio = $max_height / $cur_height;

		if(($cur_width <= $max_width) && ($cur_height <= $max_height)) {
			$width = $cur_width;
			$height = $cur_height;
		} elseif(($x_ratio * $cur_height) <= $max_height) {
			$height = ceil($x_ratio * $cur_height);
			$width = $max_width;
		} else {
			$width = ceil($y_ratio * $cur_height);
			$height = $max_height;
		}
		return array($width, $height);
	}
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			$request['template']->setVar('current_location', $request['template']->getVar('L_FILEBROWSER'));
			$request['template']->setVar('opener_input', @$_REQUEST['input']);
			$request['template']->setVar('selected', @$_REQUEST['selected']);

			$directory		= BB_BASE_DIR . DIRECTORY_SEPARATOR . @$_REQUEST['dir'];

			if(!isset($_REQUEST['dir']) || $_REQUEST['dir'] == '' || !file_exists($directory) || !is_dir($directory)) {
				$action = new K4InformationAction(new K4LanguageElement('L_DIRECTORYDOESNTEXIST', BB_BASE_DIR . DIRECTORY_SEPARATOR . $dir), 'content', FALSE);
				return $action->execute($request);
			}
			
			$filetypes	= array('html' => array('HTM', 'HTML', 'JS'),
								'php' => array('PHP'),
								'img' => array('GIF', 'PNG', 'TIFF', 'JPG', 'JPEG', 'BMP', 'ICO'));
			
			$filetype	= (!isset($_REQUEST['filetype']) || $_REQUEST['filetype'] == '') && !array_key_exists(@$_REQUEST['filetype'], $filetypes) ? FALSE : $_REQUEST['filetype'];

			$dir		= dir($directory);

			$files		= array();
			
			while(false !== ($file = $dir->read())) {
				
				if($file != '.' && $file != '..' && $file != 'Thumbs.db') {
					
					if(!is_dir($directory . DIRECTORY_SEPARATOR . $file)) {
						
						$temp = array();

						/* Get File extension */
						$exts					= explode(".", $file);
						$temp['fileext']		= count($exts) < 2 ? '' : strtoupper($exts[count($exts)-1]);
						
						$temp['shortname']		= $file;
						$temp['filename']		= $_REQUEST['dir'] . '/' . $file;
						$temp['file']			= $exts[0];

						if(in_array($temp['fileext'], $filetypes['html'])) {
							
							$temp['filetype']	= 'html';
						
						} else if(in_array($temp['fileext'], $filetypes['php'])) {
							
							$temp['filetype']	= 'php';
						
						} else if(in_array($temp['fileext'], $filetypes['img'])) {
							
							$temp['filetype']	= 'img';
							$dimensions			= $this->resize_image($temp['filename']);
							
							$temp['width']		= $dimensions[0];
							$temp['height']		= $dimensions[1];

						} else {
							
							$temp['filetype']	= '';
						}
						
						if(!$filetype) {
							$files[]			= $temp;
						} else if($temp['filetype'] == $filetype) {
							$files[]			= $temp;
						}
					}
				}
			}

			$files		= &new FAArrayIterator($files);
			
			$request['template']->setVar('img', 'img');
			$request['template']->setList('files_list', $files);
			
			$request['template']->setFile('content', 'file_browser.html');
		
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

?>