<?php
/**
* k4 Bulletin Board, useragents.class.php
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

if(!defined('IN_K4')) {
	return;
}

class AdminSpiders extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			$spiders	= &$request['dba']->executeQuery("SELECT * FROM ". K4SPIDERS ." ORDER BY spidername ASC");
			$request['template']->setList('spiders', $spiders);
			
			$request['template']->setFile('content', 'spiders_manage.html');
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminInsertSpider extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			global $_SETTINGS;

			if(!isset($_REQUEST['useragent']) || !$_REQUEST['useragent'] || $_REQUEST['useragent'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_SUPPLYUSERAGENT'), 'content', TRUE);
				return $action->execute($request);
			}

			if(!isset($_REQUEST['spidername']) || !$_REQUEST['spidername'] || $_REQUEST['spidername'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_SUPPLYSPIDERNAME'), 'content', TRUE);
				return $action->execute($request);
			}
			
			if($request['dba']->getValue("SELECT * FROM ". K4SPIDERS ." WHERE useragent = '". $request['dba']->quote($_REQUEST['useragent']) ."'") > 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_SPIDEREXISTS'), 'content', TRUE);
				return $action->execute($request);
			}

			$request['dba']->executeUpdate("INSERT INTO ". K4SPIDERS ." (useragent,spidername,allowaccess) VALUES ('". $request['dba']->quote($_REQUEST['useragent']) ."','". $request['dba']->quote($_REQUEST['spidername']) ."', ". intval($_REQUEST['allowaccess']) .")");
			
			$action = new K4InformationAction(new K4LanguageElement('L_ADDEDSPIDER', $_REQUEST['useragent']), 'content', FALSE, 'admin.php?act=spiders', 3);
			
			reset_cache(CACHE_FILE);
			
			return $action->execute($request);
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminUpdateSpider extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			global $_SETTINGS;
			
			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDSPIDER'), 'content', FALSE);
				return $action->execute($request);
			}

			$spider		= $request['dba']->getRow("SELECT * FROM ". K4SPIDERS ." WHERE id = ". intval($_REQUEST['id']));
			
			if(!is_array($spider) || empty($spider)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDSPIDER'), 'content', FALSE);
				return $action->execute($request);
			}

			if(!isset($_REQUEST['useragent']) || !$_REQUEST['useragent'] || $_REQUEST['useragent'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_SUPPLYUSERAGENT'), 'content', TRUE);
				return $action->execute($request);
			}

			if(!isset($_REQUEST['spidername']) || !$_REQUEST['spidername'] || $_REQUEST['spidername'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_SUPPLYSPIDERNAME'), 'content', TRUE);
				return $action->execute($request);
			}
			
			if($request['dba']->getValue("SELECT * FROM ". K4SPIDERS ." WHERE useragent = '". $request['dba']->quote($_REQUEST['useragent']) ."' AND spidername = '". $request['dba']->quote($_REQUEST['spidername']) ."' AND allowaccess = ". intval($_REQUEST['allowaccess'])) > 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_SPIDEREXISTS'), 'content', TRUE);
				return $action->execute($request);
			}

			if($request['dba']->getValue("SELECT * FROM ". K4SPIDERS ." WHERE useragent = '". $request['dba']->quote($_REQUEST['useragent']) ."' AND id <> ". intval($spider['id'])) > 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_SPIDEREXISTS'), 'content', TRUE);
				return $action->execute($request);
			}

			$request['dba']->executeUpdate("UPDATE ". K4SPIDERS ." SET useragent = '". $request['dba']->quote($_REQUEST['useragent']) ."', spidername = '". $request['dba']->quote($_REQUEST['spidername']) ."', allowaccess = ". intval($_REQUEST['allowaccess']) ." WHERE id = ". intval($spider['id']));
			
			$action = new K4InformationAction(new K4LanguageElement('L_UPDATEDSPIDER', $spider['spidername']), 'content', FALSE, 'admin.php?act=spiders', 3);

			reset_cache(CACHE_FILE);			

			return $action->execute($request);
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminRemoveSpider extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			global $_SETTINGS;
			
			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDSPIDER'), 'content', FALSE);
				return $action->execute($request);
			}

			$spider		= $request['dba']->getRow("SELECT * FROM ". K4SPIDERS ." WHERE id = ". intval($_REQUEST['id']));
			
			if(!is_array($spider) || empty($spider)) {
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDSPIDER'), 'content', FALSE);
				return $action->execute($request);
			}
			
			$request['dba']->executeUpdate("DELETE FROM ". K4SPIDERS ." WHERE id = ". intval($spider['id']));
			
			$action = new K4InformationAction(new K4LanguageElement('L_REMOVEDSPIDER', $spider['spidername']), 'content', FALSE, 'admin.php?act=spiders', 3);
			
			reset_cache(CACHE_FILE);
			
			return $action->execute($request);
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

?>