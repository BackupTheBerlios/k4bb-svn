<?php
/**
* k4 Bulletin Board, options.class.php
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

class AdminOptionGroups extends FAAction {
	function execute(&$request) {		

		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			k4_bread_crumbs($request['template'], $request['dba'], 'L_OPTIONS');

			$settings			= &new K4OptionsIterator($request['dba']);
			$request['template']->setList('setting_groups', $settings);
			$request['template']->setVar('options_on', '_on');
			
			$request['template']->setFile('sidebar_menu', 'menus/options.html');
			$request['template']->setFile('content', 'option_groups.html');
			
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminSettings extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			k4_bread_crumbs($request['template'], $request['dba'], 'L_OPTIONS');
			
			$group	= $request['dba']->getRow("SELECT * FROM ". K4SETTINGGROUPS ." WHERE id = ". intval($_REQUEST['id']));
			
			if(!is_array($group) || empty($group)) {
				$action = new K4InformationAction(new K4LanguageElement('L_BADOPTIONGROUP'), 'content', FALSE, 'admin.php?act=options', 3);
				return $action->execute($request);
			}

			$settings			= &new K4OptionsIterator($request['dba'], intval($group['id']));

			$request['template']->setList('setting_groups', $settings);
			$request['template']->setList('all_setting_groups', new K4OptionsIterator($request['dba']));
			$request['template']->setVar('options_on', '_on');
			
			$request['template']->setFile('sidebar_menu', 'menus/options.html');
			$request['template']->setFile('content', 'options.html');
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminUpdateOptions extends FAAction {
	function execute(&$request) {		

		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			
//			// DEMO VERSION
//			if(K4DEMOMODE) {
//				no_perms_error($request, 'content');
//				return TRUE;
//			}
			
			global $_QUERYPARAMS;
			
			if(isset($_REQUEST['settinggroupid']) && intval($_REQUEST['settinggroupid']) > 0) {
				$settings		= $request['dba']->executeQuery("SELECT * FROM ". K4SETTINGS ." WHERE settinggroupid = ". intval($_REQUEST['settinggroupid']));

				while($settings->next()) {
					
					$setting	= $settings->current();

					$new_val	= ctype_digit($_REQUEST[$setting['varname']]) && $_REQUEST[$setting['varname']] != '' ? intval($_REQUEST[$setting['varname']]) : $request['dba']->quote($_REQUEST[$setting['varname']]);
					
					$request['dba']->executeUpdate("UPDATE ". K4SETTINGS ." SET value = '$new_val' WHERE varname = '". $request['dba']->quote($setting['varname']) ."'");
				}
			}
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_OPTIONS');

			reset_cache('settings');
			$request['template']->setVar('options_on', '_on');
			
			$request['template']->setFile('sidebar_menu', 'menus/options.html');
			$action = new K4InformationAction(new K4LanguageElement('L_UPDATEDOPTIONS'), 'content', FALSE, 'admin.php?act=options', 3);
			return $action->execute($request);

		} else {
			no_perms_error($request, 'content');
		}

		return TRUE;
	}
}

class K4OptionsIterator extends FAProxyIterator {
	var $dba;
	var $lang;
	
	function K4OptionsIterator(&$dba, $setting_group = FALSE) {
		$this->__construct($dba, $setting_group);
	}

	function __construct(&$dba, $setting_group = FALSE) {
		global $_LANG;
		
		$result				= $dba->executeQuery("SELECT * FROM ". K4SETTINGGROUPS ." ". (!$setting_group ? '' : "WHERE id = ". intval($setting_group)) ." ORDER BY displayorder ASC");
		$this->dba			= &$dba;
		$this->lang			= $_LANG;

		parent::__construct($result);
	}

	function current() {
		$temp = parent::current();
		
		$result				= $this->dba->executeQuery("SELECT * FROM ". K4SETTINGS ." WHERE settinggroupid = ". intval($temp['id']) ." ORDER BY displayorder ASC");
		
		$temp['num_settings'] = $result->numrows();
		$temp['settings']	= &new K4SettingsIterator($this->dba, $this->lang, $result);
		$temp['title']		= $this->lang[$temp['title']];

		return $temp;
	}
}

class K4SettingsIterator extends FAProxyIterator {
	var $dba;
	var $lang;
	
	function K4SettingsIterator(&$dba, $lang, &$result) {
		$this->__construct($dba, $lang, $result);
	}

	function __construct(&$dba, $lang, $result) {
		$this->dba			= &$dba;
		$this->lang			= $lang;

		parent::__construct(&$result);
	}

	function current() {
		$temp = parent::current();
		
		$temp['title']		= isset($this->lang['L_'. strtoupper($temp['varname'])]) ? $this->lang['L_'. strtoupper($temp['varname'])] : '';
		$temp['description']= isset($this->lang['L_HOWTO'. strtoupper($temp['varname'])]) ? $this->lang['L_HOWTO'. strtoupper($temp['varname'])] : '';
		
		/* Create the input fields */
		if($temp['optioncode'] == 'yesno') {
			
			$temp['input'] = '<select name="'. $temp['varname'] .'" id="'. $temp['varname'] .'"><option value="1">YES</option><option value="0">NO</option></select><script type="text/javascript">d.setIndex(\''. $temp['value'] .'\', \''. $temp['varname'] .'\');</script>';
		} else if($temp['optioncode'] == 'textarea') {
			
			$temp['input'] = '<textarea class="inputbox" name="'. $temp['varname'] .'" rows="4" style="width:95%">'. $temp['value'] .'</textarea>';
		} else {
			
			$temp['input'] = '<input class="inputbox" type="text" name="'. $temp['varname'] .'" value="'. str_replace('"', '&quot;', $temp['value']) .'" />';
		}

		return $temp;
	}
}

?>