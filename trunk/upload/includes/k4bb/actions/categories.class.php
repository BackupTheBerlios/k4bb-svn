<?php
/**
* k4 Bulletin Board, categories.class.php
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
* @version $Id: categories.class.php 158 2005-07-18 02:55:30Z Peter Goodman $
* @package k42
*/



if(!defined('IN_K4')) {
	return;
}

class MarkCategoryForumsRead extends FAAction {
	function execute(&$request) {
		
		if(isset($_REQUEST['c']) && intval($_REQUEST['c']) != 0) {
			$forum					= $request['dba']->getRow("SELECT * FROM ". K4CATEGORIES ." WHERE category_id = ". intval($_REQUEST['c']));
		} elseif(isset($_REQUEST['f']) && intval($_REQUEST['f']) != 0) {
			$forum					= $request['dba']->getRow("SELECT * FROM ". K4FORUMS ." WHERE forum_id = ". intval($_REQUEST['f']));
		} else {
			/* set the breadcrumbs bit */
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INVALIDFORUM');
			
			$action = new K4InformationAction(new K4LanguageElement('L_FORUMDOESNTEXIST'), 'content', FALSE);
			return $action->execute($request);
		}
				
		if(!$forum || !is_array($forum) || empty($forum)) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INVALIDFORUM');
			$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFORUMASREAD'), 'content', FALSE);

			return $action->execute($request);
		}
			
		if($forum['row_type'] & CATEGORY) {
			
			/* Set the Breadcrumbs bit */
			k4_bread_crumbs($request['template'], $request['dba'], 'L_MARKFORUMSREAD', $forum);
			
			/* Get the forums of this Category */
			$result						= $request['dba']->executeQuery("SELECT * FROM ". K4FORUMS ." WHERE category_id = ". intval($forum['category_id']));
			
			$forums						= isset($_REQUEST['forums']) && $_REQUEST['forums'] != null && $_REQUEST['forums'] != '' ? (!unserialize($_REQUEST['forums']) ? array() : unserialize($_REQUEST['forums'])) : array();
			$cookiestr						= '';
			$cookieinfo						= get_forum_cookies();

			/* Loop through the forums */
			while($result->next()) {
				
				$temp = $result->current();
				
				$cookieinfo[$temp['forum_id']] = time();
				
			}

			$result->free();
			
			foreach($cookieinfo as $key => $val)
				$cookiestr					.= ','. $key .','. $val;

			setcookie(K4FORUMINFO, trim($cookiestr, ','), time() + 2592000, get_domain());

			$action = new K4InformationAction(new K4LanguageElement('L_MARKEDFORUMSREADCAT', $forum['name']), 'content', TRUE, 'viewforum.php?id='. $forum['forum_id'], 3);


			return $action->execute($request);
		} else {
			/* set the breadcrumbs bit */
			k4_bread_crumbs($request['template'], $request['dba'], 'L_INVALIDFORUM');
			$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFORUMASREAD'), 'content', FALSE);

			return $action->execute($request);
		}

		return TRUE;
	}
}

class K4CategoriesIterator extends FAProxyIterator {
	var $dba;
	var $result;
	
	function K4CategoriesIterator(&$dba, $query = NULL) {
		$this->__construct($dba, $query);	
	}

	function __construct(&$dba, $query = NULL) {
		global $_QUERYPARAMS;
		
		$this->dba			= &$dba;
		
		$query				= $query == NULL ? "SELECT * FROM ". K4CATEGORIES ." ORDER BY row_order ASC" : $query;
		
		$this->result		= $this->dba->executeQuery($query);

		parent::__construct($this->result);
	}

	function current() {
		$temp = parent::current();
		
		cache_forum($temp);
		
		$temp['forums']				= &new K4ForumsIterator($this->dba, "SELECT * FROM ". K4FORUMS ." WHERE category_id = ". $temp['category_id'] ." AND row_level = ". ($temp['row_level']+1) ." ORDER BY row_order ASC");

		$temp['safe_description']	= strip_tags($temp['description']);

		/* Should we free the result? */
		if(!$this->hasNext())
			$this->result->free();

		return $temp;
	}
}

?>