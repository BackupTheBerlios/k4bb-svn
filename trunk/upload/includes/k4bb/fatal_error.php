<?php
/**
* k4 Bulletin Board, fatal_error.php
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

/**
 * Return a nicely formatted fatal error message
 */
function k4_fatal_error(&$error) {
	?>
	<div class="error_box">
		<span class="redtext">The following critical error occured:</span>
		<textarea rows="10" cols="100">
			<?php echo $error->message; ?>
			
			Line: <?php echo $error->line; ?>
			File: <?php echo basename($error->file); ?>

			<?php echo $error->getBacktraceHtml(); ?>
		</textarea>
	</div>
	<br /><br />		
	<span style="width:150px;color:#666666;border-top:1px dashed #666666;padding-top:2px;margin:4px;" class="smalltext">
		[ <a href="http://www.k4bb.org" title="k4 Bulletin Board" target="_blank">Powered By: k4 Bulletin Board</a> ]
	</span>
	<?php	
	exit;
}

?>