<?php
/**
* k4 Bulletin Board, javascript.php
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

class Moderator_Js_Compiler extends FATemplateTagCompiler {
	function parseOpen() {
		
		if(isset($this->attribs['var']) && isset($this->attribs['factory'])) {
			$this->writePHP("if (\$_SESSION['user']->get('perms') >= MODERATOR && \$scope->getVar(\"modpanel\") == 1):");
			$this->write('<script type="text/javascript">var '. $this->attribs['var'] .'='. $this->attribs['factory'] .'.createInstance();</script>');
			$this->writePHP("endif;");
		}
	}
}


?>