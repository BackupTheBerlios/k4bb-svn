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
* @version $Id: categories.class.php,v 1.5 2005/05/16 02:11:54 k4st Exp $
* @package k42
*/

error_reporting(E_ALL);

if(!defined('IN_K4')) {
	exit;
}

class MarkCategoryForumsRead extends FAAction {
	function execute(&$request) {
		
		if(!isset($_REQUEST['id']) || !$_REQUEST['id'] || intval($_REQUEST['id']) == 0) {
			/* set the breadcrumbs bit */
			k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INVALIDFORUM');
			$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFORUMASREAD'), 'content', FALSE);

			return $action->execute($request);
		} else {
			
			$forum			= $request['dba']->getRow("SELECT * FROM ". K4INFO ." WHERE id = ". intval($_REQUEST['id']));
			
			if(!$forum || !is_array($forum) || empty($forum)) {
				/* set the breadcrumbs bit */
				k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INVALIDFORUM');
				$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFORUMASREAD'), 'content', FALSE);

				return $action->execute($request);
			} else {
				
				if($forum['row_type'] & CATEGORY) {
					
					/* Set the Breadcrumbs bit */
					k4_bread_crumbs(&$request['template'], $request['dba'], 'L_MARKFORUMSREAD', $forum);
					
					/* Get the forums of this Category */
					$result						= $request['dba']->executeQuery("SELECT * FROM ". K4INFO ." WHERE row_left > ". $forum['row_left'] ." AND row_right < ". $forum['row_right'] ." AND row_type = ". FORUM);
					
					$forums						= isset($_REQUEST['forums']) && $_REQUEST['forums'] != null && $_REQUEST['forums'] != '' ? iif(!unserialize($_REQUEST['forums']), array(), nserialize($_REQUEST['forums'])) : array();

					/* Loop through the forums */
					while($result->next()) {
						
						$temp = $result->current();
						
						$forums[$temp['id']]	= array();
						
					}
					
					$forums						= serialize($forums);

					/* Cache some info to set a cookie on the next refresh */
					bb_setcookie_cache('forums', $forums, time() + ini_get('session.gc_maxlifetime'));

					$action = new K4InformationAction(new K4LanguageElement('L_MARKEDFORUMSREADCAT', $forum['name']), 'content', TRUE, 'viewforum.php?id='. $forum['id'], 3);


					return $action->execute($request);
				} else {
					/* set the breadcrumbs bit */
					k4_bread_crumbs(&$request['template'], $request['dba'], 'L_INVALIDFORUM');
					$action = new K4InformationAction(new K4LanguageElement('L_INVALIDFORUMASREAD'), 'content', FALSE);

					return $action->execute($request);
				}
			}
		}

		return TRUE;
	}
}

class K4CategoriesIterator extends FAProxyIterator {
	var $dba;
	var $result;

	function __construct(&$dba, $query = NULL) {
		global $_QUERYPARAMS;
		
		$this->query_params	= $_QUERYPARAMS;
		$this->dba			= &$dba;
		
		$query_params		= $this->query_params['info'] . $this->query_params['category'];

		$query				= $query == NULL ? "SELECT $query_params FROM ". K4INFO ." i LEFT JOIN ". K4CATEGORIES ." c ON c.category_id = i.id WHERE i.row_type = ". CATEGORY ." ORDER BY i.row_order ASC" : $query;
		
		$this->result		= &$this->dba->executeQuery($query);

		parent::__construct($this->result);
	}

	function &current() {
		$temp = parent::current();
		
		cache_forum($temp);
		
		if(($temp['row_right'] - $temp['row_left'] - 1) > 0) {
			
			$query_params	= $this->query_params['info'] . $this->query_params['forum'];

			$temp['forums'] = &new K4ForumsIterator($this->dba, "SELECT $query_params FROM ". K4INFO ." i LEFT JOIN ". K4FORUMS ." f ON f.forum_id = i.id WHERE i.row_left > ". $temp['row_left'] ." AND i.row_right < ". $temp['row_right'] ." AND i.row_type = ". FORUM ." AND i.parent_id = f.category_id ORDER BY i.row_order ASC");
		}

		/* Should we free the result? */
		if(!$this->hasNext())
			$this->result->free();

		return $temp;
	}
}

?>