<?php
/**
* k4 Bulletin Board, css.class.php
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

function get_css_rules($str) {
	/**
	 * Get and format the source code of the CSS file
	 */
	$str	= preg_replace("~(\r\n|\r|\n)~i", "\n", $str);
	$str	= preg_replace("~(\t)~i", "", $str);
	$str	= preg_replace("~/\*(.+?)\*/~is", "", $str);

	/* Set/get our main arrays */
	$css		= array();
	$lines		= explode("\n", $str);

	if(is_array($lines) && !empty($lines)) {
		
		foreach($lines as $number => $source) {
			$source = trim($source);

			if($source != '') {

				preg_match('~([^\{]*)\{([^\}]*)\}~i', $source, $matches);
				
				if(isset($matches[1]) && isset($matches[2])) {
					
					$selector	= trim($matches[1]); // anything before the first {
					$rules		= trim($matches[2]); // everything between the { and }
					
					if($selector && $rules) {
						
						/* Split apart all of the rules */
						$defs	= explode(';', $rules);
						
						/**
						 * @rule		=> The rule, e.g. url() bottom right; (this is and example for background)
						 */
						while(list(, $rule) = each($defs)) {
							
							/* Get the property and definition from the rule */
							$rule_parts = explode(':', $rule);
							
							if(count($rule_parts) >= 2) {
								list($property, $definition) = $rule_parts;
								
								$property	= trim($property);
								$definition = trim($definition);
								
								if($property != '' && $definition != '') {

									/* Add it all to the css array */
									$css[$selector][$property] = $definition;
									
								}
							}
						}
					}
				}
			}
		}
	}
	
	return $css;
}

class AdminManageStyleSets extends FAAction {
	function execute(&$request) {
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			$stylesets			= $request['dba']->executeQuery("SELECT * FROM ". K4STYLES ." ORDER BY name ASC");
			$request['template']->setList('stylesets', $stylesets);
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_STYLESETS');
			$request['template']->setVar('styles_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/styles.html');

			$request['template']->setFile('content', 'css_managess.html');
		} else {
			no_perms_error($request);
		}
	}
}

class AdminAddStyleSet extends FAAction {
	function execute(&$request) {
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			k4_bread_crumbs($request['template'], $request['dba'], 'L_STYLESETS');
			$request['template']->setVar('styles_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/styles.html');
			$request['template']->setVar('css_formaction', 'admin.php?act=css_insertstyleset');
			$request['template']->setVar('edit_styleset', 0);
			
			$stylesets = $request['dba']->executeQuery("SELECT * FROM ". K4STYLES ." ORDER BY name ASC");

			$request['template']->setList('stylesets', $stylesets);
			$request['template']->setFile('content', 'css_addstyleset.html');
		} else {
			no_perms_error($request);
		}
	}
}

class AdminInsertStyleSet extends FAAction {
	function execute(&$request) {
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			k4_bread_crumbs($request['template'], $request['dba'], 'L_STYLESETS');
			$request['template']->setVar('styles_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/styles.html');

			if(!isset($_REQUEST['name']) || $_REQUEST['name'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTSTYLENAME'), 'content', FALSE);
				return $action->execute($request);
			}
			
			if(!isset($_REQUEST['description']) || $_REQUEST['description'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTSTYLEDESCRIPTION'), 'content', FALSE);
				return $action->execute($request);
			}
			
			// collect some info
			$name			= $request['dba']->quote($_REQUEST['name']);
			$description	= $request['dba']->quote(k4_htmlentities($_REQUEST['description'], ENT_QUOTES));
			$use_imageset	= isset($_REQUEST['use_imageset']) && intval($_REQUEST['use_imageset']) == 1 ? 1 : 0;
			$use_templateset	= isset($_REQUEST['use_templateset']) && intval($_REQUEST['use_templateset']) == 1 ? 1 : 0;
			
			// make sure a styleset with the same name doesn't exist.
			$styleset		= $request['dba']->getRow("SELECT * FROM ". K4STYLES ." WHERE name = '{$name}' LIMIT 1");
			
			if(is_array($styleset) && !empty($styleset)) {
				$action = new K4InformationAction(new K4LanguageElement('L_STYLESETEXISTS', $name), 'content', FALSE);
				return $action->execute($request);
			}
			
			// add the stylese
			$request['dba']->executeUpdate("INSERT INTO ". K4STYLES ." (name,description,use_imageset,use_templateset) VALUES ('{$name}', '{$description}',{$use_imageset},{$use_templateset})");
			$styleset_id = $request['dba']->getInsertId(K4STYLES, 'id');

			// clone another styleset
			if(isset($_REQUEST['clone_styleset']) && intval($_REQUEST['clone_styleset']) > 0) {
				$clone			= $request['dba']->getRow("SELECT * FROM ". K4STYLES ." WHERE id = ". intval($_REQUEST['clone_styleset']));
				
				if(is_array($clone) || !empty($clone)) {
					$styles = $request['dba']->executeQuery("SELECT * FROM ". K4CSS ." WHERE style_id = ". intval($clone['id']));				
				
					while($styles->next()) {
						$temp = $styles->current();

						$request['dba']->executeUpdate("INSERT INTO ". K4CSS ." (name, properties, style_id, description) VALUES ('". $request['dba']->quote($temp['name']) ."', '". $request['dba']->quote($temp['properties']) ."', {$styleset_id}, '". $request['dba']->quote($temp['description']) ."')");
					}
				}
			}
			
			// done!
			$action = new K4InformationAction(new K4LanguageElement('L_ADDEDSTYLESET', $name), 'content', FALSE, 'admin.php?act=stylesets', 3);
			return $action->execute($request);
		} else {
			no_perms_error($request);
		}
	}
}

class AdminEditStyleSet extends FAAction {
	function execute(&$request) {
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			k4_bread_crumbs($request['template'], $request['dba'], 'L_STYLESETS');
			$request['template']->setVar('styles_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/styles.html');

			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_BADSTYLESET'), 'content', FALSE);
				return $action->execute($request);
			}

			$styleset			= $request['dba']->getRow("SELECT * FROM ". K4STYLES ." WHERE id = ". intval($_REQUEST['id']));
			
			if(!is_array($styleset) || empty($styleset)) {
				$action = new K4InformationAction(new K4LanguageElement('L_BADSTYLESET'), 'content', TRUE);
				return $action->execute($request);
			}

			foreach($styleset as $key=>$val)
				$request['template']->setVar('styleset_'. $key, $val);

			$request['template']->setVar('css_formaction', 'admin.php?act=css_updatestyleset');
			$request['template']->setVar('edit_styleset', 1);
			$request['template']->setFile('content', 'css_addstyleset.html');
		} else {
			no_perms_error($request);
		}
	}
}

class AdminUpdateStyleSet extends FAAction {
	function execute(&$request) {
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			k4_bread_crumbs($request['template'], $request['dba'], 'L_STYLESETS');
			$request['template']->setVar('styles_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/styles.html');

			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_BADSTYLESET'), 'content', FALSE);
				return $action->execute($request);
			}

			$styleset			= $request['dba']->getRow("SELECT * FROM ". K4STYLES ." WHERE id = ". intval($_REQUEST['id']));
			
			if(!is_array($styleset) || empty($styleset)) {
				$action = new K4InformationAction(new K4LanguageElement('L_BADSTYLESET'), 'content', TRUE);
				return $action->execute($request);
			}

			if(!isset($_REQUEST['name']) || $_REQUEST['name'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTSTYLENAME'), 'content', FALSE);
				return $action->execute($request);
			}
			
			if(!isset($_REQUEST['description']) || $_REQUEST['description'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTSTYLEDESCRIPTION'), 'content', FALSE);
				return $action->execute($request);
			}
			
			$name			= $request['dba']->quote($_REQUEST['name']);
			$use_imageset	= isset($_REQUEST['use_imageset']) && intval($_REQUEST['use_imageset']) == 1 ? 1 : 0;
			$use_templateset	= isset($_REQUEST['use_templateset']) && intval($_REQUEST['use_templateset']) == 1 ? 1 : 0;
			
			$ss				= $request['dba']->getRow("SELECT * FROM ". K4STYLES ." WHERE name = '{$name}' AND id <> ". intval($styleset['id']) ." LIMIT 1");
			if(is_array($ss) && !empty($ss)) {
				$action = new K4InformationAction(new K4LanguageElement('L_STYLESETEXISTS', $name), 'content', FALSE);
				return $action->execute($request);
			}

			$description	= $request['dba']->quote(k4_htmlentities($_REQUEST['description'], ENT_QUOTES));
			$request['dba']->executeUpdate("UPDATE ". K4STYLES ." SET name='{$name}', description='{$description}',use_imageset={$use_imageset},use_templateset={$use_templateset} WHERE id = ". intval($styleset['id']));
			
			if($request['template']->getVar('styleset') == $styleset['name'])
				$request['dba']->executeUpdate("UPDATE ". K4SETTINGS ." SET value = '{$name}' WHERE varname = 'styleset'");
			
			$request['dba']->executeUpdate("UPDATE ". K4USERSETTINGS ." SET styleset = '{$name}' WHERE styleset = '". $styleset['name'] ."'");
			$request['dba']->executeUpdate("UPDATE ". K4FORUMS ." SET defaultstyle = '{$name}' WHERE defaultstyle = '". $styleset['name'] ."'");
			
			if(file_exists(BB_BASE_DIR .'/tmp/stylesets/'. preg_replace("~\s~i", '_', $styleset['name']) .'.css')) {
				unlink(BB_BASE_DIR .'/tmp/stylesets/'. preg_replace("~\s~i", '_', $styleset['name']) .'.css');
			}

			$action = new K4InformationAction(new K4LanguageElement('L_UPDATEDSTYLESET', $styleset['name']), 'content', FALSE, 'admin.php?act=stylesets', 3);
			return $action->execute($request);
		} else {
			no_perms_error($request);
		}
	}
}

class AdminRemoveStyleSet extends FAAction {
	function execute(&$request) {
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			k4_bread_crumbs($request['template'], $request['dba'], 'L_STYLESETS');
			$request['template']->setVar('styles_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/styles.html');

			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_BADSTYLESET'), 'content', FALSE);
				return $action->execute($request);
			}

			$styleset			= $request['dba']->getRow("SELECT * FROM ". K4STYLES ." WHERE id = ". intval($_REQUEST['id']));
			
			if(!is_array($styleset) || empty($styleset)) {
				$action = new K4InformationAction(new K4LanguageElement('L_BADSTYLESET'), 'content', TRUE);
				return $action->execute($request);
			}

			$stylesets			= $request['dba']->executeQuery("SELECT * FROM ". K4STYLES ." WHERE id <> ". intval($styleset['id'])." ORDER BY id ASC");
			
			if($stylesets->numrows() == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_CANTREMOVEDSTYLESET'), 'content', TRUE);
				return $action->execute($request);
			}
			
			$first				= $stylesets->next();

			$revert_to			= $request['template']->getVar('styleset') != $styleset['name'] ? $request['template']->getVar('styleset') : $first['name'];
			
			if($request['template']->getVar('styleset') == $styleset['name'])
				$request['dba']->executeUpdate("UPDATE ". K4SETTINGS ." SET value = '{$revert_to}' WHERE varname = 'styleset'");
			
			$request['dba']->executeUpdate("UPDATE ". K4USERSETTINGS ." SET styleset = '{$revert_to}' WHERE styleset = '". $styleset['name'] ."'");
			$request['dba']->executeUpdate("UPDATE ". K4FORUMS ." SET defaultstyle = '{$revert_to}' WHERE defaultstyle = '". $styleset['name'] ."'");
			$request['dba']->executeUpdate("DELETE FROM ". K4STYLES ." WHERE id = ". intval($styleset['id']));
			$request['dba']->executeUpdate("DELETE FROM ". K4CSS ." WHERE style_id = ". intval($styleset['id']));
			
			if(file_exists(BB_BASE_DIR .'/tmp/stylesets/'. preg_replace("~\s~i", '_', $styleset['name']) .'.css'))
				unlink(BB_BASE_DIR .'/tmp/stylesets/'. preg_replace("~\s~i", '_', $styleset['name']) .'.css');

			$action = new K4InformationAction(new K4LanguageElement('L_REMOVEDSTYLESET', $styleset['name']), 'content', FALSE, 'admin.php?act=stylesets', 3);
			return $action->execute($request);
		} else {
			no_perms_error($request);
		}
	}
}

class AdminManageCSSStyles extends FAAction {
	function execute(&$request) {
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			
			
			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$stylesets			= $request['dba']->executeQuery("SELECT * FROM ". K4STYLES ." ORDER BY name ASC");
				$request['template']->setList('stylesets', $stylesets);
				$request['template']->setFile('content', 'css_manage.html');
			} else {
				$styleset			= $request['dba']->getRow("SELECT * FROM ". K4STYLES ." WHERE id = ". intval($_REQUEST['id']));

				if(!is_array($styleset) || empty($styleset)) {
					$action = new K4InformationAction(new K4LanguageElement('L_BADSTYLESET'), 'content', FALSE);
					return $action->execute($request);
				}
				
				$styles = $request['dba']->executeQuery("SELECT * FROM ". K4CSS ." WHERE style_id = ". intval($styleset['id']) ." ORDER BY name ASC");

				foreach($styleset as $key=>$val)
					$request['template']->setVar('styleset_'. $key, $val);
				
				$request['template']->setVar('edit_all', isset($_REQUEST['edit']) ? 1 : 0);

				$request['template']->setList('styles', new AdminCSSIterator($styles));
				$request['template']->setFile('content', 'css_styles.html');
			}
			k4_bread_crumbs($request['template'], $request['dba'], 'L_MANAGECSSSTYLES');
			$request['template']->setVar('styles_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/styles.html');
		} else {
			no_perms_error($request);
		}
	}
}

class AdminAddCSSClass extends FAAction {
	function execute(&$request) {
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			$request['template']->setFile('content', 'css_addstyle.html');
			$request['template']->setVar('css_formaction', 'admin.php?act=css_insertstyle&amp;id='. $request['styleset']['id']);
			$request['template']->setVar('edit_style', 0);
		} else {
			no_perms_error($request);
		}
	}
}

class AdminInsertCSSClass extends FAAction {
	function execute(&$request) {
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
						
			if(!isset($_REQUEST['name']) || $_REQUEST['name'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTSTYLENAME'), 'content', FALSE);
				return $action->execute($request);
			}

			if(!isset($_REQUEST['properties']) || $_REQUEST['properties'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTSTYLEPROPERTIES'), 'content', FALSE);
				return $action->execute($request);
			}

			if(!isset($_REQUEST['description']) || $_REQUEST['description'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTSTYLEDESCRIPTION'), 'content', FALSE);
				return $action->execute($request);
			}

			$name			= $request['dba']->quote($_REQUEST['name']);
			$properties		= $request['dba']->quote(preg_replace("~(\r\n|\r|\n)~i", "", $_REQUEST['properties']));
			$description	= $request['dba']->quote(k4_htmlentities($_REQUEST['description'], ENT_QUOTES));
			$request['dba']->executeUpdate("INSERT INTO ". K4CSS ." (name, properties, style_id, description) VALUES ('{$name}', '{$properties}', ". intval($request['styleset']['id']) .", '{$description}')");
			
			if(file_exists(BB_BASE_DIR .'/tmp/stylesets/'. preg_replace("~\s~i", '_', $request['styleset']['name']) .'.css'))
				unlink(BB_BASE_DIR .'/tmp/stylesets/'. preg_replace("~\s~i", '_', $request['styleset']['name']) .'.css');

			$action = new K4InformationAction(new K4LanguageElement('L_ADDEDCSSSTYLE', $name), 'content', FALSE, 'admin.php?act=css&id='. $request['styleset']['id'], 3);
			return $action->execute($request);
		} else {
			no_perms_error($request);
		}
	}
}

define('CSS_NUM', 0);
define('CSS_ALPHA', 2); // includes '-', ','
define('CSS_ALPHANUM', 4);
define('CSS_ALL', 8);

class AdminCSSEditor extends FAAction {
	/**
	 * Now this looks really ugly. What it's doing is separating
	 * the various parts of these three css things and adding them
	 * to the css array
	 */
	function sort_box_parts(&$css, &$positions, $property, $parts) {
		foreach($parts as $p) {
			preg_match("~([0-9]+)(px|pt|in|cm|mm|pc|em|ex|\%)~i", $p, $matches);
			
			if(isset($matches[1]) && isset($matches[2])) {
				if(isset($positions[0])) {
					$css[substr($property, 0, 1) . substr($positions[0], 0, 1) .'-measurement'] = $matches[2];
					$css[$property .'-'. $positions[0]] = $matches[1];
				}
			}
			$first			= array_shift($positions);
			$positions[]	= $first;
		}
	}
	
	/**
	 * this is specific for border sorting
	 */
	function sort_border_parts(&$css, &$positions, $property, $parts) {
		foreach($parts as $p) {
			if(isset($positions[0])) {
				$css['border-'. $positions[0] .'-'. $property] = $p;
			}
			$first			= array_shift($positions);
			$positions[]	= $first;
		}
	}

	/**
	 * Create a shortened version of a porperty name
	 */
	function alt_property($property) {
		$ret = '';
		if(strpos($property, '-') !== FALSE) {
			list($parta, $partb) = explode("-", $property);
			$ret = substr($parta, 0, 1) . substr($partb, 0, 1);
		} else {
			$ret = substr($property, 0, 1);
		}

		return $ret;
	}

	function execute(&$request) {
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			$request['template']->setFile('content', 'css_editor.html');
			
			foreach($request['style'] as $key=>$val) {
				$request['template']->setVar('style_'. $key, $val);
			}

			$str	= preg_replace("~(\r\n|\r|\n)~i", "\n", $request['style']['properties']);
			$str	= preg_replace("~(\t)~i", "", $str);
			$str	= preg_replace("~/\*(.+?)\*/\n~is", "", $str);

			$props	= explode(';', $str);
			
			// Remake the css into a nice formatted thing
			$css	= "";
			foreach($props as $rule) {
				if($rule != '')
					$css .= "{$rule};\n";
			}
			
			$request['template']->setVar('style_properties', $css);
			
			$mode		= !isset($_REQUEST['mode']) ? '' : ($_REQUEST['mode'] == 'normal' ? 'normal' : 'advanced');
			
			/**
			 * Define all of the inputs and selects in the CSS editor
			 */
			$text_boxes = array(
								'background-color'		=> CSS_ALPHANUM,
								'background-image'		=> CSS_ALL,
								'word-spacing'			=> CSS_NUM,
								'letter-spacing'		=> CSS_NUM,
								'text-indent'			=> CSS_NUM,
								'border-top'		=> CSS_NUM, //5
								'border-top-color'		=> CSS_ALPHANUM,
								'border-right'	=> CSS_NUM,
								'border-right-color'	=> CSS_ALPHANUM,
								'border-bottom'	=> CSS_NUM,
								'border-bottom-color'	=> CSS_ALPHANUM,
								'border-left'		=> CSS_NUM,
								'border-left-color'		=> CSS_ALPHANUM, // 12
								'width'					=> CSS_NUM,
								'height'				=> CSS_NUM,
								'padding-top'			=> CSS_NUM, // 15
								'padding-right'			=> CSS_NUM,
								'padding-bottom'		=> CSS_NUM,
								'padding-left'			=> CSS_NUM, // 18
								'margin-top'			=> CSS_NUM,
								'margin-right'			=> CSS_NUM,
								'margin-bottom'			=> CSS_NUM,
								'margin-left'			=> CSS_NUM,
								'z-index'				=> CSS_NUM,
								'top'					=> CSS_NUM,
								'right'					=> CSS_NUM,
								'bottom'				=> CSS_NUM,
								'left'					=> CSS_NUM,
								'clip-top'				=> CSS_NUM,
								'clip-right'			=> CSS_NUM,
								'clip-bottom'			=> CSS_NUM,
								'clip-left'				=> CSS_NUM,
								'color'					=> CSS_ALPHANUM,
							);

			$select_menus = array(
								'background-repeat'		=> CSS_ALL,
								'background-attachment' => CSS_ALPHA,
								'background-position-h' => CSS_ALPHA,
								'background-position-v' => CSS_ALPHA,
								'ws-measurement'		=> CSS_NUM, // word spacing
								'ls-measurement'		=> CSS_NUM, // letter spacing
								'vertical-align'		=> CSS_ALPHA,
								'text-align'			=> CSS_ALPHA,
								'ti-measurement'		=> CSS_ALL, // text indent measurement
								'white-space'			=> CSS_ALPHA,
								'display'				=> CSS_ALPHA,
								'bt-measurement'		=> CSS_ALL, // border-top 11
								'border-top-style'		=> CSS_ALPHA,
								'br-measurement'		=> CSS_ALL, // border-right
								'border-right-style'	=> CSS_ALPHA,
								'bb-measurement'		=> CSS_ALL, // border-bottom
								'border-bottom-style'	=> CSS_ALPHA,
								'bl-measurement'		=> CSS_ALL, // border-left
								'border-left-style'		=> CSS_ALPHA, // 18
								'width-measurement'		=> CSS_ALL,
								'float'					=> CSS_ALPHA,
								'height-measurement'	=> CSS_ALL,
								'clear'					=> CSS_ALPHA,
								'pt-measurement'		=> CSS_ALL, // top padding 23
								'pr-measurement'		=> CSS_ALL,
								'pb-measurement'		=> CSS_ALL,
								'pl-measurement'		=> CSS_ALL, // 26
								'mt-measurement'		=> CSS_ALL, // top margin 27
								'mr-measurement'		=> CSS_ALL,
								'mb-measurement'		=> CSS_ALL,
								'ml-measurement'		=> CSS_ALL, // 30
								'page-break-before'		=> CSS_ALPHA,
								'page-break-after'		=> CSS_ALPHA,
								'cursor'				=> CSS_ALPHA,
								'list-style-type'		=> CSS_ALPHA,
								'list-style-position'	=> CSS_ALPHA,
								'position'				=> CSS_ALPHA,
								'visibility'			=> CSS_ALPHA,
								'overflow'				=> CSS_ALPHA,
								't-measurment'			=> CSS_ALL, // top measurement
								'r-measurment'			=> CSS_ALL,
								'b-measurment'			=> CSS_ALL,
								'l-measurment'			=> CSS_ALL,
								'ct-measurment'			=> CSS_ALL, // top clip measurement
								'cr-measurment'			=> CSS_ALL,
								'cb-measurment'			=> CSS_ALL,
								'cl-measurment'			=> CSS_ALL,
								'font-family'			=> CSS_ALPHA,
								'text-transform'		=> CSS_ALPHA,
								'font-size'				=> CSS_NUM,
								'size-measurement'		=> CSS_ALL,
								'font-weight'			=> CSS_ALPHA,
								'font-style'			=> CSS_ALPHA,
								'font-variant'			=> CSS_ALPHA,
								'text-decoration'		=> CSS_ALPHA,
							);
			
			$border_styles = array(
								'none', 
								'hidden', 
								'dotted', 
								'dashed', 
								'solid', 
								'double', 
								'groove', 
								'ridge', 
								'inset', 
								'outset',
							);
			$positions = array(
							'top', 
							'right', 
							'bottom', 
							'left',
						);
			$vert_positions = array(
							'top', 
							'center', 
							'bottom', 
						);
			$horiz_positions = array(
							'left',
							'center',
							'right',
						);
			$repeat_types = array(
							'repeat',
							'no-repeat',
							'repeat-x',
							'repeat-y',
						);

			switch($mode) {
				case 'normal': {
					
					$text_boxes_keys	= array_keys($text_boxes);
					$select_menus_keys	= array_keys($select_menus);
					// FIX ME
					$border_related		= array_slice($text_boxes_keys, 5, 12) + array_slice($select_menus_keys, 11, 18);
					$padding_related	= array_slice($text_boxes_keys, 15, 18) + array_slice($select_menus_keys, 23, 26);
					$margin_related		= array_slice($text_boxes_keys, 19, 22) + array_slice($select_menus_keys, 27, 30);
					
					$css				= "";
					$postvar			= $_REQUEST;
					
					/**
					 * Add css to the postvar array if they don't exist
					 */
					foreach($text_boxes_keys as $tbk)
						$postvar[$tbk] = !isset($postvar[$tbk]) ? '' : $postvar[$tbk];
					
					foreach($select_menus_keys as $smk)
						$postvar[$smk] = !isset($postvar[$smk]) ? '' : $postvar[$smk];
					
					/**
					 * Loop through the array of css
					 */
					foreach($postvar as $property => $definition) {
						
						/**
						 * is this part of our css defined stuff?
						 */
						if(in_array($property, $text_boxes_keys) || in_array($property, $select_menus_keys)) { // $postvar[$property] != '' && 
							
							/**
							 * Look for Border, Padding and Margin
							 */
							if(in_array($property, $border_related) || in_array($property, $padding_related) || in_array($property, $margin_related)) {
								
								$start	= in_array($property, $padding_related) ? 'padding' : (in_array($property, $margin_related) ? 'margin' : 'border');
								$s		= $start == 'padding' ? 'p' : ($start == 'margin' ? 'm' : 'b');
								$array_to_use = $start == 'padding' ? $padding_related : ($start == 'margin' ? $margin_related : $border_related);
								
								if(isset($postvar[$start .'-top']) 
									&& isset($postvar[$start .'-right']) 
									&& isset($postvar[$start .'-bottom']) 
									&& isset($postvar[$start .'-left'])) {
									
									// are they all the same?
									if($postvar[$start .'-top'] == $postvar[$start .'-right']
										&& $postvar[$start .'-right'] == $postvar[$start .'-bottom']
										&& $postvar[$start .'-bottom'] == $postvar[$start .'-left']) {
										
										if($postvar[$start .'-top'] != '' && $postvar[$start .'-right'] != '' && $postvar[$start .'-bottom'] != '' && $postvar[$start .'-left'] != '') {
											
											// for borders
											if($start == 'border') {
												$css .= "border: ". $postvar['border-top'] . $postvar['bt-measurement'] ." ". $postvar['border-top-style'] ." ". $postvar['border-top-color'] .";\n";
											
											// for padding and margins
											} else {
												$css .= $start ." ". $postvar[$start .'-top'] . $postvar[$s .'t-measurement'] .";\n";
											}
										}
										
									// the borders/paddings/margins are all different
									} else {
										
										if($postvar[$start .'-top'] != '')
											$css .= $start ."-top: ". intval($postvar[$start .'-top']) . $postvar[$s .'t-measurement'] ." ". ($s == 'b' ? $postvar['border-top-style'] ." ". $postvar['border-top-color'] : '') .";\n";
										if($postvar[$start .'-right'] != '')
											$css .= $start ."-right: ". intval($postvar[$start .'-right']) . $postvar[$s .'r-measurement'] ." ". ($s == 'b' ? $postvar['border-right-style'] ." ". $postvar['border-right-color'] : '') .";\n";
										if($postvar[$start .'-bottom'] != '')
											$css .= $start ."-bottom: ". intval($postvar[$start .'-bottom']) . $postvar[$s .'b-measurement'] ." ". ($s == 'b' ? $postvar['border-bottom-style'] ." ". $postvar['border-bottom-color'] : '') .";\n";
										if($postvar[$start .'-left'] != '')
											$css .= $start ."-left: ". intval($postvar[$start .'-left']) . $postvar[$s .'l-measurement'] ." ". ($s == 'b' ? $postvar['border-left-style'] ." ". $postvar['border-left-color'] : '') .";\n";
									}
									
									// this removes all of the border/padding/margin-related things from 
									// request now that we're done parsing them.
									foreach($array_to_use as $br) {
										unset($postvar[$br]);
									}
								}

							/**
							 * The who-cares properties.. they are fine either way :P
							 */
							} else {
								
								if($definition != '')
									$css .= "{$property}: {$definition};\n";
							}
						}
					}
					echo $css; exit;
					unset($array_to_use, $border_related, $padding_related, $margin_related, $text_boxes, $select_menus);
					
					$request['template']->setVisibility('mode_text', TRUE);
					break;
				}
				case 'advanced': {
					
					$css			= array(); // an array of all of our expanded css
					$css_unknown	= array(); // unknown css elements
					$css_def		= '';
					
					// separate the properties
					$props = explode(";", $_REQUEST['properties']);
					
					// loop through the rules
					while(list(, $rule) = each($props)) {
						
						/* Get the property and definition from the rule */
						$rule_parts = explode(':', $rule);
						
						if(count($rule_parts) >= 2) {
							list($property, $definition) = $rule_parts;
							
							$property	= strtolower(trim($property));
							$definition = trim($definition);
							
							if($property != '' && $definition != '') {
								
								$border_pos = strpos($property, 'border') === TRUE;
								$padding_pos = strpos($property, 'border') === TRUE;
								$margin_pos = strpos($property, 'border') === TRUE;

								/**
								 * Manage padding, margins and borders
								 */
								if($border_pos || $padding_pos || $margin_pos) {
								
									
									$parts = explode(" ", $definition);
									
									if($border_pos) { // $property == 'border'
										
										if(count($parts) == 3) {
											list($width, $style, $color) = $parts;
											
											// if this is the case, this means that we need to parse this part
											// differently. This is just a mini check to see if we have the
											// right thing.. 
											if(in_array(trim($style), $border_styles)) {

												// add all of the border stylr properties
												$parts = explode(" ", trim(str_repeat($style . " ", 4)));
												$this->sort_border_parts($css, $positions, 'style', $parts);

												// add all of the border color properies
												$parts = explode(" ", trim(str_repeat($color . " ", 4)));
												$this->sort_border_parts($css, $positions, 'color', $parts);
												
												// finally, reset the parts array so that it can be formatted below
												$parts = array($width);
											}
										}
									}

									if(count($parts) == 1) {
										$parts = explode(" ", trim(str_repeat($parts[0] . " ", 4)));
									}

									$this->sort_box_parts($css, $positions, $property, $parts);

								}

								/**
								 * Manage clip
								 */
								else if($property == 'clip') {
									preg_match("~(rect|shape|auto)\((.*?)\)~i", $definition, $ms);
									if(isset($ms[1])) {
										if($ms[1] == 'rect' || $ms[1] == 'shape') {
											
											$parts = explode(",", $definition);
											$this->sort_box_parts($css, $positions, $property, $parts);
										} else {
											$css_unknown['clip'] = 'auto';
										}
									}
								}

								/**
								 * Manage the background element
								 */
								else if($property == 'background') {
									
									$parts = explode(" ", $definition);
									
									foreach($parts as $p) {
										
										$p = trim($p);
										
										/**
										 * Figure out what's what. I don't use an elseif because then
										 * the some of the positions could override themselves, etc.
										 */
										if(!isset($css['background-attachment']) && preg_match("~(fixed|scroll)~is", $p)) {
											$css['background-attachment'] = $p;
										} 
										if(!isset($css['background-color']) && preg_match("~\#([a-fA-F0-9]+)~is", $p)) {
											$css['background-color'] = $p;
										} 
										if(!isset($css['background-image']) && preg_match("~url\((.+?)\)~is", $p, $matches)) {
											$css['background-image'] = trim($matches[1], "'");
										} 
										if(!isset($css['background-position-h']) && in_array(strtolower($p), $horiz_positions)) {
											$css['background-position-h'] = $p;
										} 
										if(!isset($css['background-position-v']) && in_array(strtolower($p), $vert_positions)) {
											$css['background-position-v'] = $p;
										} 
										if(!isset($css['background-repeat']) && in_array(strtolower($p), $repeat_types)) {
											$css['background-repeat'] = $p;
										}
									}

									break;
								}

								/**
								 * The 'who cares' properties that work anyway
								 */
								else {

									// look for a number and a measurement
									preg_match("~((\-)?[0-9]+)(px|pt|in|cm|mm|pc|em|ex|\%)~i", $definition, $matches);
									
									$count = count($matches);

									// this means that we have found a number and a measurement
									if($count >= 3) {
										$css[$property] = $matches[1];
										$css[$this->alt_property($property) .'-measurement'] = $count == 4 ? $matches[3] : $matches[2];
									} else {
										$css[$property] = $definition;
									}
									break;
								}
									
							}
						}
						
					}
					
					/**
					 * Compile some javascript to deal with putting info into the editor
					 */
					$text_boxes		= array_keys($text_boxes);
					$select_menus	= array_keys($select_menus);
					
					$javascript		= '<script type="text/javascript">';
					
					foreach($css as $def => $prop) {
						
						if(in_array($def, $text_boxes)) {
							$javascript		.= "\nd.setText('". $prop ."', '". $def ."');";
						
						} else if(in_array($def, $select_menus)) {
							$javascript		.= "\nd.forceSetIndex('". $prop ."', '". $def ."');";
						} else {
							$css_unknown[$def] = $prop;
						}
					}
					$javascript		.= "\n</script>";
					
					/**
					 * Remake the css that is not known by the editor
					 */
					$unknown_css = '';
					foreach($css_unknown as $def => $prop) {
						$unknown_css .= "{$def}: {$prop};\n";
					}
					
					$request['template']->setVar('unknown_css', $unknown_css);
					$request['template']->setVar('adv_javascript', $javascript);
					$request['template']->setVisibility('mode_advanced', TRUE);
					break;
				}
				default: {
					$request['template']->setVisibility('mode_text', TRUE);
					break;
				}
			}


		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminEditCSSClass extends FAAction {
	function execute(&$request) {
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			
			$request['template']->setFile('content', 'css_addstyle.html');
			$request['template']->setVar('edit_style', 1);
			$request['template']->setVar('css_formaction', 'admin.php?act=css_updatestyle');
			
			foreach($request['style'] as $key=>$val) {
				$request['template']->setVar('style_'. $key, $val);
			}
		} else {
			no_perms_error($request);
		}
	}
}

class AdminUpdateCSSClass extends FAAction {
	function execute(&$request) {
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			
			if(!isset($_REQUEST['name']) || $_REQUEST['name'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTSTYLENAME'), 'content', FALSE);
				return TRUE;
			}			

			if(!isset($_REQUEST['properties']) || $_REQUEST['properties'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTSTYLEPROPERTIES'), 'content', FALSE);
				return $action->execute($request);
			}

			if(!isset($_REQUEST['description']) || $_REQUEST['description'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTSTYLEDESCRIPTION'), 'content', FALSE);
				return $action->execute($request);
			}

			$name			= $request['dba']->quote($_REQUEST['name']);
			$properties		= $request['dba']->quote(preg_replace("~(\r\n|\r|\n)~i", "", $_REQUEST['properties']));
			$description	= $request['dba']->quote(k4_htmlentities($_REQUEST['description'], ENT_QUOTES));
			$request['dba']->executeUpdate("UPDATE ". K4CSS ." SET name='{$name}',properties='{$properties}',description='{$description}' WHERE id=". intval($request['style']['id']));
			
			if(file_exists(BB_BASE_DIR .'/tmp/stylesets/'. preg_replace("~\s~i", '_', $request['styleset']['name']) .'.css'))
				unlink(BB_BASE_DIR .'/tmp/stylesets/'. preg_replace("~\s~i", '_', $request['styleset']['name']) .'.css');
			
			$action = new K4InformationAction(new K4LanguageElement('L_UPDATEDCSSSTYLE', $name), 'content', FALSE, 'admin.php?act=css&id='. $request['styleset']['id'], 3);
			return $action->execute($request);
		} else {
			no_perms_error($request);
		}
	}
}

class AdminUpdateAllCSSClasses extends FAAction {
	function execute(&$request) {
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			
			$styles = $request['dba']->executeQuery("SELECT * FROM ". K4CSS ." WHERE style_id = ". intval($request['styleset']['id']));
			
			while($styles->next()) {
				$css = $styles->current();
				if(isset($_REQUEST['properties'. $css['id']]) && $_REQUEST['properties'. $css['id']] != '') {
					$properties		= $request['dba']->quote(preg_replace("~(\r\n|\r|\n)~i", "", $_REQUEST['properties'. $css['id']]));
					$request['dba']->executeUpdate("UPDATE ". K4CSS ." SET properties='{$properties}' WHERE id=". intval($css['id']));
				}
			}
			
			if(file_exists(BB_BASE_DIR .'/tmp/stylesets/'. preg_replace("~\s~i", '_', $request['styleset']['name']) .'.css'))
				unlink(BB_BASE_DIR .'/tmp/stylesets/'. preg_replace("~\s~i", '_', $request['styleset']['name']) .'.css');

			$action = new K4InformationAction(new K4LanguageElement('L_UPDATEDCSSSTYLES', $request['styleset']['name']), 'content', FALSE, 'admin.php?act=css&id='. $request['styleset']['id'], 3);
			return $action->execute($request);
		}
		return TRUE;
	}
}

class AdminRevertCSSClass extends FAAction {
	function execute(&$request) {
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			
			if(file_exists(BB_BASE_DIR .'/tmp/stylesets/'. preg_replace("~\s~i", '_', $request['styleset']['name']) .'.css'))
				unlink(BB_BASE_DIR .'/tmp/stylesets/'. preg_replace("~\s~i", '_', $request['styleset']['name']) .'.css');

			if($request['style']['prev_properties'] != '')
				$request['dba']->executeUpdate("UPDATE ". K4CSS ." SET properties=prev_properties, prev_properties='' WHERE id = ". intval($request['style']['id']));
			
			$action = new K4InformationAction(new K4LanguageElement('L_REVERTEDCSSSTYLE', $request['style']['name']), 'content', FALSE, 'admin.php?act=css&id='. $request['styleset']['id']);
			return $action->execute($request);
		} else {
			no_perms_error($request);
		}
	}
}

class AdminRemoveCSSClass extends FAAction {
	function execute(&$request) {
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
						
			//if($request['style']['prev_properties'] != '')
				$request['dba']->executeUpdate("DELETE FROM ". K4CSS ." WHERE id = ". intval($request['style']['id']));
			
			if(file_exists(BB_BASE_DIR .'/tmp/stylesets/'. preg_replace("~\s~i", '_', $request['styleset']['name']) .'.css'))
				unlink(BB_BASE_DIR .'/tmp/stylesets/'. preg_replace("~\s~i", '_', $request['styleset']['name']) .'.css');

			$action = new K4InformationAction(new K4LanguageElement('L_REMOVEDCSSSTYLE', $request['style']['name']), 'content', FALSE, 'admin.php?act=css&id='. $request['styleset']['id'], 3);
			return $action->execute($request);
		} else {
			no_perms_error($request);
		}
	}
}

class AdminCSSRequestFilter extends FAFilter {
	function execute(&$action, &$request) {
		
		$events = array('css_removestyle', 
						'css_revertstyle',
						'css_editstyle',
						'css_updatestyle',
						'css_addstyle',
						'css_insertstyle',
						'css_updateallclasses',
						'css_editor',
						);
		
		if(in_array($request['event'], $events)) {

			if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			
				k4_bread_crumbs($request['template'], $request['dba'], 'L_MANAGECSSSTYLES');
				$request['template']->setVar('styles_on', '_on');
				$request['template']->setFile('sidebar_menu', 'menus/styles.html');

				if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
					$action = new K4InformationAction(new K4LanguageElement('L_BADSTYLESET'), 'content', FALSE);
					return TRUE;
				}

				$styleset			= $request['dba']->getRow("SELECT * FROM ". K4STYLES ." WHERE id = ". intval($_REQUEST['id']));
				
				if(!is_array($styleset) || empty($styleset)) {
					$action = new K4InformationAction(new K4LanguageElement('L_BADSTYLESET'), 'content', FALSE);
					return TRUE;
				}
				
				if($request['event'] != 'css_insertstyle' &$request['event'] != 'css_addstyle' &$request['event'] != 'css_updateallclasses') {
					
					if(!isset($_REQUEST['style_id']) || intval($_REQUEST['style_id']) == 0) {
						$action = new K4InformationAction(new K4LanguageElement('L_CSSCLASSDOESNTEXIST'), 'content', FALSE);
						return TRUE;
					}

					$style			= $request['dba']->getRow("SELECT * FROM ". K4CSS ." WHERE id = ". intval($_REQUEST['style_id']) ." AND style_id = ". intval($styleset['id']));
					
					if(!is_array($style) || empty($style)) {
						$action = new K4InformationAction(new K4LanguageElement('L_CSSCLASSDOESNTEXIST'), 'content', FALSE);
						return TRUE;
					}
				}

				$request['styleset']	= isset($styleset) ? $styleset : array();
				$request['style']		= isset($style) ? $style : array();

			} else {
				no_perms_error($request);
				return TRUE;
			}
		}		
	}
}

class AdminCSSIterator extends FAProxyIterator {
	var $result;

	function AdminCSSIterator(&$result) {
		$this->result		= &$result;

		parent::__construct($this->result);
	}

	function current() {
		$temp = parent::current();
		
		$temp['properties'] = trim(str_replace(';', ";\n", $temp['properties']), "\s\n\r");
		
		/* Should we free the result? */
		if(!$this->hasNext())
			$this->result->free();

		return $temp;
	}
}

?>