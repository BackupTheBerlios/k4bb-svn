<?php
/**
* k4 Bulletin Board, faq.class.php
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

class AdminFAQCategories extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			$request['template']->setList('categories', new AdminFAQCategoriesIterator($request['dba'], $request['template']->getVar('IMG_DIR')));
			
			$request['template']->setFile('content', 'faq_categories.html');
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminAddFAQCategory extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			$request['template']->setFile('content', 'faq_addcategory.html');

			if(isset($_REQUEST['id']) && intval($_REQUEST['id']) > 0) {
				$parent = $request['dba']->getRow("SELECT * FROM ". K4FAQCATEGORIES ." WHERE category_id = ". intval($_REQUEST['id']));

				if(is_array($parent) && !empty($parent)) {
					$request['template']->setVisibility('parent_id', TRUE);
					$request['template']->setVar('parent_id', $parent['category_id']);
					$request['template']->setVar('parent_name', $parent['name']);
				}
			}

		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminInsertFAQCategory extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= SUPERADMIN)) {
			
			/* Error checking on the fields */
			if(!isset($_REQUEST['name']) || $_REQUEST['name'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTCATNAME'), 'content', TRUE);
				return $action->execute($request);
			}
			
			if(!isset($_REQUEST['row_order']) || $_REQUEST['row_order'] == '') {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTCATORDER'), 'content', TRUE);
				return $action->execute($request);
			}

			if(!ctype_digit($_REQUEST['row_order'])) {
				$action = new K4InformationAction(new K4LanguageElement('L_INSERTCATORDERNUM'), 'content', TRUE);
				return $action->execute($request);
			}
			
			// get any parent category stuff
			$parent = array('row_level'=>0,'category_id'=>0);
			if(isset($_REQUEST['id']) && intval($_REQUEST['parent_id']) > 0) {
				$parent_i = $request['dba']->getRow("SELECT * FROM ". K4FAQCATEGORIES ." WHERE category_id = ". intval($_REQUEST['parent_id']));

				if(is_array($parent_i) && !empty($parent_i)) {
					$parent = $parent_i;
				}

				// unset the parent_i variable
				unset($parent_i);
			}
			
			$request['dba']->beginTransaction();

			/* Build the queries */
			$insert_a			= &$request['dba']->prepareStatement("INSERT INTO ". K4FAQCATEGORIES ." (name,row_level,created,row_order,parent_id,can_view) VALUES (?,?,?,?,?,?)");
			
			/* Build the query for the categories table */
			$insert_a->setString(1, $_REQUEST['name']);
			$insert_a->setInt(2, $parent['row_level']+1);
			$insert_a->setInt(3, time());
			$insert_a->setInt(4, $_REQUEST['row_order']);
			$insert_a->setInt(5, $parent['category_id']);
			$insert_a->setInt(6, $_REQUEST['can_view']);
			
			/* Insert the extra category info */
			$insert_a->executeUpdate();

			$request['dba']->commitTransaction();

			if(!@touch(CACHE_FILE, time()-86460)) {
				@unlink(CACHE_FILE);
			}
			
			$action = new K4InformationAction(new K4LanguageElement('L_ADDEDFAQCATEGORY', $_REQUEST['name']), 'content', FALSE, 'admin.php?act=faq_categories', 3);
			return $action->execute($request);
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminFAQCategoriesIterator extends FAProxyIterator {
	var $dba;
	var $result;

	function AdminFAQCategoriesIterator(&$dba, $image_dir) {
		$this->result		= &$dba->executeQuery("SELECT * FROM ". K4FAQCATEGORIES ." ORDER BY row_order ASC");
		$this->dba			= &$dba;
		$this->image_dir	= $image_dir;

		parent::__construct($this->result);
	}

	function &current() {
		$temp = parent::current();
		
		$map['level']	= str_repeat('<img src="Images/'. $this->image_dir .'/Icons/threaded_bit.gif" alt="" border="0" />', $temp['row_level']-1);

		/* Should we free the result? */
		if(!$this->hasNext())
			$this->result->free();

		return $temp;
	}
}

?>