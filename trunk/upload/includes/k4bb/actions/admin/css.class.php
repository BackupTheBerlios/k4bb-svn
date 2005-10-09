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

class AdminManageStyleSets extends FAAction {
	function execute(&$request) {
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			$stylesets			= $request['dba']->executeQuery("SELECT * FROM ". K4STYLES ." ORDER BY name ASC");
			$request['template']->setList('stylesets', $stylesets);
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_STYLESETS');
			$request['template']->setVar('styles_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/styles.html');

			$request['template']->setFile('content', 'css_manage.html');
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
			$description	= $request['dba']->quote(htmlentities($_REQUEST['description'], ENT_QUOTES));
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

			$description	= $request['dba']->quote(htmlentities($_REQUEST['description'], ENT_QUOTES));
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

				$request['template']->setList('styles', $styles);
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
			$description	= $request['dba']->quote(htmlentities($_REQUEST['description'], ENT_QUOTES));
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
			$description	= $request['dba']->quote(htmlentities($_REQUEST['description'], ENT_QUOTES));
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
				
				if($request['event'] != 'css_insertstyle' && $request['event'] != 'css_addstyle') {
					
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
				$action = new K4InformationAction(new K4LanguageElement('L_NEEDPERMS'), 'content', FALSE);
				return TRUE;
			}
		}		
	}
}

?>