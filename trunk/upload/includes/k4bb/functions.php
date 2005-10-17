<?php
/**
* k4 Bulletin Board, functions.inc.php
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
* @author Geoffrey Goodman
* @version $Id: functions.php 158 2005-07-18 02:55:30Z Peter Goodman $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	return;
}

/**
 * Function to get the current domain
 */
function get_domain() {
//	global $_URL;
//
//	$domain = new FAUrl($_URL->__toString());
//	
//	$domain->args	= array();
//	$domain->anchor = FALSE;
//	$domain->scheme = FALSE;
//	$domain->user	= FALSE;
//	$domain->host	= FALSE;
//	$domain->file	= FALSE;
//
//	$domain			= $domain->__toString();
//
//	$domain			= ($domain != '' && strpos('.', $domain) !== FALSE) ? $domain : '/';
//
//	return $domain;

	return '/';
}

/**
 * Get Profile Field info for iterators such as topics and replies
 */
function get_profile_fields($fields, $temp) {
	foreach($fields as $field) {
				
		if($field['display_topic'] == 1) {

			if(isset($temp['post_user_'. $field['name']]) && $temp['post_user_'. $field['name']] != '') {
				switch($field['inputtype']) {
					default:
					case 'text':
					case 'textarea':
					case 'select': {
						$field['value']		= $temp['post_user_'. $field['name']];
						break;
					}
					case 'multiselect':
					case 'radio':
					case 'check': {
						$result				= unserialize($temp['post_user_'. $field['name']]);
						$field['value']		= implode(", ", (!$result ? array() : $result));
						break;
					}
				}
				$fields[] = $field;
			}
		}
	}
}

/**
 * Standard no permissions error page.. used often (implemented late, so might not be widespread
 */
function no_perms_error(&$request, $section = 'content') {
	
	if(!USE_AJAX) {
		k4_bread_crumbs($request['template'], $request['dba'], 'L_INFORMATION');
		$request['template_file'] = BB_BASE_DIR . "/templates/". $request['user']->get('templateset') ."/information_base.html";
		$request['template']->setFile($section, 'login_form.html');
		$request['template']->setVisibility('no_perms', TRUE);
	} else {
		return ajax_message('L_YOUNEEDPERMS');
	}
}

/**
 * Rudimentry function to see if AJAX should be supported
 * Most of this was from the PHP manual comments
 */
function allow_AJAX() {
	
	$use_ajax = FALSE;
	
	if(isset($_COOKIE['k4_canjs']) && intval($_COOKIE['k4_canjs']) == 0)
		return FALSE;

	$browsers = array ('MSIE','OPERA','MOZILLA','NETSCAPE','FIREFOX','SAFARI',);
	
	foreach ($browsers as $browser) {
		$s			= strpos(strtoupper($_SERVER['HTTP_USER_AGENT']), $browser);
		$f			= $s + strlen($browser);
		$version	= substr($_SERVER['HTTP_USER_AGENT'], $f, 5);
		$version	= preg_replace('/[^0-9,.]/','', $version);

		if (strpos(strtoupper($_SERVER['HTTP_USER_AGENT']), $browser)) {
			switch($browser) {
				case 'MSIE':
				case 'OPERA': {
					$use_ajax = (intval($version) >= 5) ? TRUE : FALSE;
					break;
				}
				case 'MOZILLA': {
					$use_ajax = (floatval($version) >= 1.3) ? TRUE : FALSE;
					break;
				}
				case 'NETSCAPE': {
					$use_ajax = (intval($version) >= 6) ? TRUE : FALSE;
					break;
				}
				case 'FIREFOX':
				case 'SAFARI': {
					$use_ajax = TRUE;
					break;
				}
			}
		}
	}
	
	if(!isset($_REQUEST['use_ajax']) || intval($_REQUEST['use_ajax']) == 0) {
		$use_ajax = FALSE;
	}

	return $use_ajax;
}

/**
 * Rudimentry function to see if WYSIWYG editing should be supported
 * Most of this was from the PHP manual comments
 */
function allow_WYSIWYG() {
	
	$use_wysiwyg = FALSE;
	
	$browsers = array ('MSIE','OPERA','MOZILLA','NETSCAPE','FIREFOX','SAFARI',);
	
	foreach ($browsers as $browser) {
		$s			= strpos(strtoupper($_SERVER['HTTP_USER_AGENT']), $browser);
		$f			= $s + strlen($browser);
		$version	= substr($_SERVER['HTTP_USER_AGENT'], $f, 5);
		$version	= preg_replace('/[^0-9,.]/','', $version);

		if (strpos(strtoupper($_SERVER['HTTP_USER_AGENT']), $browser)) {
			switch($browser) {
				case 'MSIE': {
					$use_wysiwyg = (floatval($version) >= 4) ? TRUE : FALSE;
					break;
				}
				case 'OPERA': {
					$use_wysiwyg = FALSE;
					break;
				}
				case 'MOZILLA': {
					$use_wysiwyg = (floatval($version) >= 1.3) ? TRUE : FALSE;
					break;
				}
				case 'NETSCAPE': {
					$use_wysiwyg = (intval($version) >= 6) ? TRUE : FALSE;
					break;
				}
				case 'FIREFOX':
				case 'SAFARI': {
					$use_wysiwyg = TRUE;
					break;
				}
			}
		}
	}

	return $use_wysiwyg;
}


/**
 * Send plain text to the browser for javascript to interpret
 */
function AJAX_message($lang_element, $prefix = 'ERROR') {
	global $_LANG;
	
	if(is_a($lang_element, 'K4LanguageElement')) {
		echo $prefix . $lang_element->__toString();
	} else if(isset($_LANG[$lang_element])) {
		echo $prefix . $_LANG[$lang_element];
	}

	exit;
}

/**
 * Get a users IP
 */
function get_ip() {

	$ip = '';
	if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown")) {
		$ip		= getenv("HTTP_CLIENT_IP");
	} else if (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown")) {
		$ip		= getenv("HTTP_X_FORWARDED_FOR");
	} else if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown")) {
		$ip		= getenv("REMOTE_ADDR");
	} else if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown")) {
		$ip		= $_SERVER['REMOTE_ADDR'];
	}
	
	return $ip;
}

/**
 * Get cookies that hold information about the last viewed time of forums
 */
function get_forum_cookies() {
	
	$forums			= array();

	if(isset($_COOKIE[K4FORUMINFO])) {
		$cookieinfo		= explode(',', $_COOKIE[K4FORUMINFO]);

		$count			= count($cookieinfo);
		$forums			= array();
		
		if($count % 2 == 0) {
			for($i = 0; $i < $count; $i++) {
				$forums[$cookieinfo[$i]] = $cookieinfo[$i+1];
				$i++;
			}
		}
	}

	return $forums;
}

/**
 * Get cookies that hold topic viewed information
 */
function get_topic_cookies() {
	$topics			= array();

	if(isset($_COOKIE[K4TOPICINFO])) {
		$cookieinfo		= explode(',', $_COOKIE[K4TOPICINFO]);

		$count			= count($cookieinfo);
		$forums			= array();
		
		if($count % 2 == 0) {
			for($i = 0; $i < $count; $i++) {
				$topics[$cookieinfo[$i]] = $cookieinfo[$i+1];
				$i++;
			}
		}
	}

	return $topics;
}

/**
 * Get the current URL 
 */
function current_url($ofk4 = FALSE) {
	$url			= 'http';

	// is it secure?
	if(strpos($_SERVER['SERVER_PROTOCOL'], 'HTTPS') !== FALSE)
		$url		.= 's';
	
	$right_host		= $_SERVER['HTTP_HOST'] != '' ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
	
	$pos			= strpos($right_host, '.');
	$www			= substr($right_host, 0, ($pos === FALSE ? 0 : $pos));
	
	if($www == 'wwww')
		$right_host	= substr($right_host, $pos);

	$url			.= '://'. $right_host . (!$ofk4 ? $_SERVER['SCRIPT_NAME'] : '');
	
	if(!$ofk4) {
		if($_SERVER['QUERY_STRING'] > ' ') {
			$url		.= '?'. $_SERVER['QUERY_STRING'];
		}
	}

	return $url;
}

/**
 * Replace all acronyms in some text with the acronyms
 */
function replace_acronyms(&$text) {
	global $_ACRONYMS;
	
	if(is_array($_ACRONYMS) && !empty($_ACRONYMS)) {
		
		$text			= ' '. $text .' ';

		foreach($_ACRONYMS as $acronym => $meaning) {
			$text		= preg_replace('~(\s|\r|\n)('. preg_quote($acronym) .')(\s|\r|\n)~i', '\\1<acronym title="'. $meaning .'">\\2</acronym>\\3', $text);
		}

		$text			= trim($text);
	}
}

/**
 * Replace all word censors in some text
 */
function replace_censors(&$text, $override = FALSE) {
	global $_CENSORS;
	
	if(!$override && intval(@$_SESSION['user']->get('viewcensors')) == 0 && $_SESSION['user']->isMember())
		return TRUE;

	if(is_array($_CENSORS) && !empty($_CENSORS)) {
		
		$text			= ' '. $text .' ';

		foreach($_CENSORS as $censor) {
			// loose
			if($censor['method'] == 1) {
				
				$text	= preg_replace('~(\b|\s)'. $censor['word'] .'(\b|\s)~i', '\\1'. $censor['replacement'] .'\\2', $text);

			// exact
			} else {
				$text	= preg_replace('~\s'. $censor['word'] .'\s~i', $censor['replacement'], $text);
			}
		}

		$text			= trim($text);
	}
}

/**
 * get the extension on a file
 */
function file_extension($filename) {
	$parts		= explode(".", $filename);
	$ext		= trim(strtolower(@$parts[count($parts)-1]));

	return $ext;
}

/**
 * Replacement for file_get_contents() in older versions of php
 */
if(!function_exists('file_get_contents')) {
	function file_get_contents($filename) {
		$fp			= fopen($filename, "rb");
		$contents	= fread($fp, filesize($filename));

		return $contents;
	}
}

/**
 * stupdily persistent function to chmod a file
 * @param string filename 		The absolute path to the file
 * @param int mode				The file permissions mode
 */
function __chmod($filename, $mode) {
	
	global $_CONFIG;
	
	@chmod($filename, $mode);

	// do we need to chmod the directory?
	if(!is_writeable(dirname($filename)) && !is_dir($filename)) {
		__chmod(dirname($filename), $mode);
	}
	
	// does the file exist?
	if(file_exists($filename)) {

		if($_CONFIG['ftp']['use_ftp']) {
			
			// try to connect
			$conn				= ftp_connect($_SERVER['SERVER_ADDR']);
			
			if(is_resource($conn)) {
				
				// log in to ftp
				if(ftp_login($conn, $_CONFIG['ftp']['username'], $_CONFIG['ftp']['password'])) {
					
					if(phpversion() < 5) {
						
						// this should always fail, but try anyway
						if(!@ftp_site($conn, 'CHMOD 0777 '. $filename)) {

							if(!@ftp_site($conn, 'CHMOD 0777 '. get_ftp_root($conn, dirname($filename)) . basename($filename)))
								@chmod($filename, $mode);
						}
					
					} else {
						
						@ftp_chmod($conn, $mode, $filename);

					}
				} else {
					@chmod($filename, $mode);
				}

				ftp_close($conn);
			
			} else {
				@chmod($filename, $mode);
			}
		} else {
			@chmod($filename, $mode);
		}
	}
}

/**
 * Find the FTP root directory for the file server
 * @param ftp_conn object		FTP Connection
 * @param ftp_root string		Path to a directory
 *
 * @author James Logsdon
 */
function get_ftp_root ( &$ftp_conn, $ftp_root ) {

	if ( $ftp_root === null ) $DOC_ROOT = $_SERVER['DOCUMENT_ROOT'];
    else $DOC_ROOT = $root;

    $DOC_ROOT = str_replace ( '\\', '/', $DOC_ROOT ); // For the windows people

    $ftp_dirs = ftp_nlist ( $ftp_conn, '/' );

    $docs = explode ( '/', $DOC_ROOT );
    $notInIt = array ( );
    foreach ( $docs AS $key=>$dir )
    {
        if ( !in_array ( $dir, $ftp_dirs ) )
        {
            $notInIt[$key] = $dir;
            unset ( $docs[$key] );
        }
        else
        {
            break;
        }
    }

    $newRoot = str_replace ( implode ( '/', $notInIt ), '', $DOC_ROOT ) .'/';

    return $newRoot;

}

/**
 * Function to get the ftp directory for a file
 */
function ftp_safe_dir($directory) {
	$directory		= str_replace('\\', '/', $directory);

	$folders	= explode('/', $directory);
	
	for($i = 0; $i < count($folders); $i++) {
		if($folders[$i] != 'public_html' && $folders[$i] != 'www') {
			unset($folders[$i]);
		} else {
			break;
		}
	}
	
	$folders	= array_values($folders);

	$directory	= '/'. implode('/', $folders);

	return $directory;
}

/**
 * Function to get the files out of a directory
 */
function get_files($directory, $dirs_only = FALSE, $numerical = FALSE, $exceptions = array()) {
	$dir		= dir($directory);
	
	$files		= array();
	
	while(FALSE !== ($file = $dir->read())) {
		if($file != '.' && $file != '..' && $file != '.svn' && $files != 'CVS' && $file != 'index.html' && !in_array($file, $exceptions)) {
			
			if( ($dirs_only && is_dir($directory .'/'. $file)) || (!$dirs_only) ) {
				$files[]	= !$numerical ? array('name' => $file) : $file;
			}
		}
	}
	$dir->close();

	return $files;
}

/**
 * Function to get a parent directory
 */
if(!function_exists('one_dir_up')) {
	function one_dir_up($dir) {

		$dir		= str_replace('\\', '/', $dir);

		$folders	= explode('/', $dir);

		unset($folders[count($folders)-1]);
		
		$folders	= array_values($folders);

		$dir		= implode('/', $folders);
		
		return $dir;
	}
}

/**
 * This function checks if we should rewrite a file
 */
function rewrite_file($filename, $time_interval) {
	$return = FALSE;
	
	if(file_exists($filename) && is_readable($filename) && is_writable($filename)) {

		//if(time() > (filemtime($filename) + $time_interval)) {
		if((time() - (filemtime($filename) + $time_interval)) > 0) {
			$return = TRUE;
		}
	}
	
	return $return;
}

/**
 * Get's the referring filename
 */
function referer() {
	
	$file			= isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';

	$url			= new FAUrl($file);
	$url->scheme	= FALSE;
	$url->user		= FALSE;
	$url->host		= FALSE;
	$url->path		= FALSE;

	$url			= $url->__toString();
	
	return $url;
}

/**
 * This get's the forum url
 */
function forum_url($url = FALSE) {
	
	global $_URL;
	
	$url			= !$url ? $_URL : $url;
	
	if(!is_a($url, 'FAUrl'))
		$url			= &new FAUrl($url->__toString());
	
	$url->file		= FALSE;
	$url->anchor	= FALSE;
	$url->args		= array();

	$url			= $url->__toString();
	
	return $url;
}

/**
 * Format a custom profile field
 */
function format_profilefield($data) {
	global $lang;

	switch($data['inputtype']) {
		case 'text': {
			
			$input		= '<input type="text" class="inputbox" name="'. $data['name'] .'" id="'. $data['name'] .'" value="'. $data['default_value'] .'" size="'. $data['display_size'] .'" maxlength="'. $data['user_maxlength'] .'" />';
			
			if($data['is_required'] == 1)
				$input .= '<script type="text/javascript">addVerification(\''. $data['name'] .'\', \'.+\', \''. $data['name'] .'_error\', \'inputfailed\');</script><div id="'. $data['name'] .'_error" style="display: none;">'. sprintf($lang['L_FILLINTHISFIELD'], $data['title']) .'</div>';

			break;
		}
		case 'textarea': {
			
			$input		= '<textarea name="'. $data['name'] .'" id="'. $data['name'] .'" cols="'. $data['display_size'] .'" rows="'. $data['display_rows'] .'" class="inputbox">'. $data['default_value'] .'</textarea>';

			if($data['is_required'] == 1)
				$input .= '<script type="text/javascript">addVerification(\''. $data['name'] .'\', \'(\n|\r\n|\r|.)+\', \''. $data['name'] .'_error\', \'inputfailed\');</script><div id="'. $data['name'] .'_error" style="display: none;">'. sprintf($lang['L_FILLINTHISFIELD'], $data['title']) .'</div>';

			break;
		}
		case 'select': {
			
			$input		= '<select name="'. $data['name'] .'" id="'. $data['name'] .'">';
			
			$options	= $data['inputoptions'] != '' ? iif(!unserialize($data['inputoptions']), array(), unserialize($data['inputoptions'])) : array();

			if(is_array($options) && !empty($empty)) {
				foreach($options as $option)
					$input .= '<option value="'. $option .'">'. $option .'</option>';
			}

			$input		.= '</select>';

			break;
		}
		case 'multiselect': {
			
			$input		= '<select name="'. $data['name'] .'[]" id="'. $data['name'] .'" multiple="multiple" '. iif(intval($data['display_rows']) > 0, 'size="'. intval($data['display_rows']) .'"', '') .'>';
			
			$options	= $data['inputoptions'] != '' ? iif(!unserialize($data['inputoptions']), array(), unserialize($data['inputoptions'])) : array();

			if(is_array($options) && !empty($empty)) {
				foreach($options as $option)
					$input .= '<option value="'. $option .'">'. $option .'</option>';
			}

			$input		.= '</select>';

			break;
		}
		case 'radio': {
			
			$options	= $data['inputoptions'] != '' ? iif(!unserialize($data['inputoptions']), array(), unserialize($data['inputoptions'])) : array();
			
			$input		= '';
			
			if(is_array($options) && !empty($empty)) {
				
				$i = 0;
				foreach($options as $option) {
					$input .= '<label for="'. $data['name'] . $i .'"><input type="radio" name="'. $data['name'] .'" id="'. $data['name'] . $i .'" value="'. $option .'" />&nbsp;&nbsp;'. $option .'</label>';
					$i++;
				}
			}

			break;
		}
		case 'check': {
			
			$options	= $data['inputoptions'] != '' ? iif(!unserialize($data['inputoptions']), array(), unserialize($data['inputoptions'])) : array();
			
			$input		= '';
			
			if(is_array($options) && !empty($empty)) {
				
				$i = 0;
				foreach($options as $option) {
					$input .= '<label for="'. $data['name'] . $i .'"><input type="checkbox" name="'. $data['name'] .'[]" id="'. $data['name'] . $i .'" value="'. $option .'" />&nbsp;&nbsp;'. $option .'</label>';
					$i++;
				}
			}

			break;
		}
	}

	if(isset($input))
		return $input;
}

/**
 * A quick way to do a conditional statement 
 */
function iif($argument, $true_val, $false_val) {
	if($argument) {
		return $true_val;
	} else {
		return $false_val;
	}
}

/** 
 * Format a timestamp according to the user's timezone settings 
 */
function bbtime($timestamp = FALSE) {
	
	if(!$timestamp)
		$timestamp = time();

	if(isset($_SESSION['user']) && $_SESSION['user']->isMember())
		return $timestamp + ($_SESSION['user']->get('timezone') * 3600);
	else
		return $timestamp;
}


/**
 * Function to make pagination 
 */
function paginate($count, $first, $prev, $separator, $next, $last, $limit, $id) {
	
	global $_URL, $_LANG;
	
	$page				= isset($_REQUEST['page']) && ctype_digit($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
	$limit				= isset($_REQUEST['limit']) && ctype_digit($_REQUEST['limit']) ? intval($_REQUEST['limit']) : $limit;
	
	$url				= new FAUrl($_URL->__toString());

	$url->anchor		= FALSE;
	$url->host			= FALSE;
	$url->user			= FALSE;
	$url->scheme		= FALSE;
	$url->path			= FALSE;

	$url->file			= 'viewtopic.php';
	$url->args['id']	= intval($id);

	$before				= 3;
	$after				= 3;
	
	$num_pages			= ceil($count / $limit);

	$page_start			= ($page - $before) < 1 ? 1 : $page - $before;
	$page_end			= ($page + $after) > $num_pages ? $num_pages : $page + $after;
	
	$url->args['page'] = $page;
	$url->args['limit']= $limit;

	if($count > $limit) {
		
		$str = '<div style="float: right;"><table celpadding="0" cellspacing="'. K4_TABLE_CELLSPACING .'" border="0" class="pagination"><tr>';
		
		if($page > 1 ) {
			
			$str .= '<td class="alt2" style="padding:2px;"><a href="'. $url->__toString() .'" class="minitext">'. $first .'</a></td>';
			$url->args['page'] = ($page - 1) <= 0 ? 1 : ($page - 1);
			$str .= '<td class="alt2" style="padding:2px;"><a href="'. $url->__toString() .'" class="minitext">'. $prev .'</a></td>';
		}

		//$str .= '(';
		for($i = $page_start; $i <= $page_end; $i++) {
			$url->args['page']		= $i;
			
			$str					.= '<td class="alt1" style="padding:2px;"><a href="'. $url->__toString() .'" class="minitext">'. $i .'</a></td>';
			if($i != $page_end)
				$str				.= $separator;

		}
		//$str .= ')';
		
		if($page != $num_pages) {
			
			if($page != $num_pages) {
				$url->args['page']	= ($page + 1) < $num_pages ? ($page + 1) : $num_pages;
				$str .= '<td class="alt2" style="padding:2px;"><a href="'. $url->__toString() .'" class="minitext">'. $next .'</a></td>';
			}

			$url->args['page']		= $num_pages;
			$str					.= '<td class="alt2" style="padding:2px;"><a href="'. $url->__toString() .'" class="minitext">'. $last .'</a></td>';
		}
		
		$str .= '</tr></table></div>';

		return $str;
	}
}

/**
 * I got the following two functions, to check an email address from php.net comments,
 * But more importanly, from 'expert@dotgeek.org' Thanks a lot :) 
 */

function ereg_words($car, $data){
   $err = false;
   $cnt = strlen($data);
   $len = strlen($car);
   for($i=0;$i<$cnt;$i++){
       $errm = false;
       $chrm = strtolower($data{$i});
       for($k=0;$k<$len;$k++) if($car{$k}==$chrm) $errm = true;
       if(!$errm) $err = true;
   }
   return $err;
}


/**
 * A function to _really_ validate an email 
 */
function check_mail($mail){
	
	$mail	= strtolower($mail);

	// $car -> list acceptable characters
	$car = "0123456789.abcdefghijklmnopqrstuvwxyz_@-";
	// $ext -> list extension domain characters
	$ext = "abcdefghijklmnopqrstuvwxyz";

	/**
	* if you not use return(), is necesary to put elseif()
	*/

	if(ereg_words($car, $mail)) 
		return FALSE; // contain invalid caracter(s)
	
	$expMail = explode("@", $mail);
	
	if(count($expMail)==1) 
		return FALSE; // invalid format
	
	if(count($expMail)>2) {
		return FALSE; // contain multi @ caracters
	} else {
		if(empty($expMail[0])) 
			return FALSE; // begin at @ is empty
		if(strlen($expMail[1])< 3) 
			return FALSE; // after @ invalid format
		
		$expSep = explode(".", $expMail[1]);
		
		if(count($expSep)==1) {
			return FALSE; // invalid format domain host
		} else {
			if(empty($expSep[count($expSep)-2])) 
				return FALSE; // domain name is empty
			if(strlen($expSep[count($expSep)-1])<2 || strlen($expSep[count($expSep)-1])>4) 
				return FALSE; // invalid extension domain
			if(ereg_words($ext, $expSep[count($expSep)-1])) 
				return FALSE; // extension domain contain invalid caracter(s)
		}
	}

	return TRUE;

}

/**
 * Append a '/' onto the end of a string 
 */
function append_slash($in) {
	if (strpos("\\/", substr($in, -1)) === false) {
		$in	.= '/';
	}

	return $in;
}

/**
 * Check if an iterator has array access (PHP5) 
 */
function array_access($in) {
	if (is_array($in) || is_a($in, 'ArrayAccess') || is_a($in, 'ArrayObject')) return true;
}

/**
 * Check if a class is defined, either already, or in the lazy_load files 
 */
function class_defined($class) {
	if (class_exists($class)) return TRUE;
	if (isset($GLOBALS['lazy_load'][strtolower($class)])) return TRUE;

	return FALSE;
}

/**
 * Define a class to be loaded by the lazy_load 
 */
function define_class($class, $path) {
	assert('is_readable($path)');

	$GLOBALS['lazy_load'][strtolower($class)]	= $path;
}


/**
 * Get the relative time to now (ENG)
 */
function relative_time($timestamp, $format = 'g:iA') {
	$time	= mktime(0, 0, 0);
	$delta	= time() - $timestamp;

	if ($timestamp < $time - 86400) {
		return date("F j, Y, g:i a", $timestamp);
	}

	if ($delta > 86400 && $timestamp < $time) {
		return "Yesterday at " . date("g:i a", $timestamp);
	}

	$string	= '';

	if ($delta > 7200)
		$string	.= floor($delta / 3600) . " hours, ";

	else if ($delta > 3660)
		$string	.= "1 hour, ";

	else if ($delta >= 3600)
		$string	.= "1 hour ";

	$delta	%= 3600;

	if ($delta > 60)
		$string	.= floor($delta / 60) . " minutes ";
	else
		$string .= $delta . " seconds ";

	return "$string ago";
}


?>