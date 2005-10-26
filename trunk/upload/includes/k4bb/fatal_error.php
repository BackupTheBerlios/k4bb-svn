<?php
/**
* k4 Bulletin Board, fatal_error.php
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
* @package k42
*/

/**
 * Return a nicely formatted fatal error message
 */
function k4_fatal_error(&$error) {
	$logo	= file_exists(BB_BASE_DIR .'/Images/k4.gif') ? 'Images/k4.gif' : '';

	?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr">
	<head>
		<title>k4 v2.0 - Critical Error - Powered by k4 BB</title>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-15" />
		<meta http-equiv="Content-Style-Type" content="text/css" />
		<meta name="generator" content="k4 Bulletin Board 2.0" />
		<meta name="robots" content="all" />
		<meta name="revisit-after" content="1 Days" />

		<meta http-equiv="Expires" content="1" />
		<meta name="description" content="k4 v2.0 - Powered by k4 Bulletin Board" />
		<meta name="keywords" content="k4, bb, bulletin, board, bulletin board, forum, k4st, forums, message, board, message board,mysql,php,sqlite,mysqli,ajax" />
		<meta name="author" content="k4 Bulletin Board" />
		<meta name="distribution" content="GLOBAL" />
		<meta name="rating" content="general" />
		<link rel="icon" href="favicon.ico" type="image/x-icon" />
		<style type="text/css">
		body {
			background-color: #FFFFFF;
			padding: 0px;
			margin: 0px;
		}
		a {
			font-family: Geneva, Arial, Helvetica, Sans-Serif;
			font-size: 12px;
			color: #000000;
			text-decoration: none;
		}
		h2 {
			font-family: Geneva, Arial, Helvetica, Sans-Serif;
			color: #045975;
		}
		.error_box {
			text-align: left;
			border: 1px solid #666666;
			background-color: #f7f7f7;
			color: #000000;
			font-family: Geneva, Arial, Helvetica, Sans-Serif;
			font-size: 12px;
			width: 500px;
			padding: 10px;
		}
		.redtext {
			color: #FF0000;
		}
		.greentext {
			color: #009900;
			font-weight: bold;
		}
		.inset_box_small { border: 1px inset;padding: 5px;background-color: #f7f7f7;border-bottom: 1px solid #B2B2B2;border-right: 1px solid #B2B2B2;border-top: 1px solid #000000;border-left: 1px solid #000000; }
		</style>
	</head>
	<body>
	<div align="center">
		<table cellpadding="0" cellspacing="0" border="0">
			<tr>
				<?php if($logo != '')	echo '<td><img src="'. $logo .'" alt="k4 Bulletin Board" border="0" /></td>'; ?>
				<td valign="bottom"><?php echo '<h2>k4 Bulletin Board</h2>'; ?></td>
			</tr>
		</table>
		<div class="error_box">
			<span class="redtext">The following critical error occured:</span>
			<br /><br />
			<span class="greentext"><?php echo $error->message; ?></span>
			<br /><br />
			Line: <strong><?php echo $error->line; ?></strong><br />
			File: <strong><?php echo basename($error->file); ?></strong>
			<br /><br />
			<div class="inset_box_small" style="height: 150px; overflow: auto;">
				<?php echo $error->getBacktraceHtml(); ?>
			</div>
		</div>
		<br /><br />		
		<span style="width:150px;color:#666666;border-top:1px dashed #666666;padding-top:2px;margin:4px;" class="smalltext">
			[ <a href="http://www.k4bb.org" title="k4 Bulletin Board" target="_blank">Powered By: k4 Bulletin Board</a> ]
		</span>
		<br />
	</div>
	</body>
	</html>
	<?php	
	exit;
}

?>