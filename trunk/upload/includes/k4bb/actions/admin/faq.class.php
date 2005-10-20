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

if(!defined('IN_K4')) {
	return;
}

/**
 * Display all of the faq categories
 */
class AdminFAQCategories extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_FAQ');
			$request['template']->setVar('faq_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/faq.html');
			
			$request['template']->setList('categories', new AdminFAQCategoriesIterator($request['dba'], $request['template']->getVar('IMG_DIR')));
			
			$request['template']->setFile('content', 'faq_categories.html');
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

/**
 * Show the form to add a faq category
 */
class AdminAddFAQCategory extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			$request['template']->setFile('content', 'faq_addcategory.html');
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_FAQ');
			$request['template']->setVar('faq_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/faq.html');

			if(isset($_REQUEST['id']) && intval($_REQUEST['id']) > 0) {
				$parent = $request['dba']->getRow("SELECT * FROM ". K4FAQCATEGORIES ." WHERE category_id = ". intval($_REQUEST['id']));

				if(is_array($parent) && !empty($parent)) {
					$request['template']->setVisibility('parent_id', TRUE);
					$request['template']->setVar('parent_id', $parent['category_id']);
					$request['template']->setVar('parent_name', $parent['name']);
				}
			}

			$request['template']->setVar('faq_action', 'admin.php?act=faq_insertcategory');

		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

/**
 * insert a faq category into the database
 */
class AdminInsertFAQCategory extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_FAQ');
			$request['template']->setVar('faq_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/faq.html');

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
			if(isset($_REQUEST['parent_id']) && intval($_REQUEST['parent_id']) > 0) {
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
			
			$request['dba']->executeUpdate("UPDATE ". K4FAQCATEGORIES ." SET num_categories=num_categories+1 WHERE category_id = ". intval($parent['category_id']));

			$request['dba']->commitTransaction();

			$action = new K4InformationAction(new K4LanguageElement('L_ADDEDFAQCATEGORY', $_REQUEST['name']), 'content', FALSE, 'admin.php?act=faq_categories', 3);
			return $action->execute($request);
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

/**
 * Update the row order of a faq category
 */
class AdminFAQCategorySimpleUpdate extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_FAQ');
			$request['template']->setVar('faq_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/faq.html');
			
			if(!isset($_REQUEST['category_id']) || intval($_REQUEST['category_id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_BADFAQCATEGORY'), 'content', FALSE, 'admin.php?act=faq_categories', 3);
				return $action->execute($request);
			}
			$category = $request['dba']->getRow("SELECT * FROM ". K4FAQCATEGORIES ." WHERE category_id = ". intval($_REQUEST['category_id']));
			
			if(!is_array($category) || empty($category)) {
				$action = new K4InformationAction(new K4LanguageElement('L_BADFAQCATEGORY'), 'content', FALSE, 'admin.php?act=faq_categories', 3);
				return $action->execute($request);
			}
			
			$order = isset($_REQUEST['row_order']) && intval($_REQUEST['row_order']) > 0 ? intval($_REQUEST['row_order']) : $category['row_order'];
			
			if($order != $category['row_order']) {
				$request['dba']->executeUpdate("UPDATE ". K4FAQCATEGORIES ." SET row_order = ". intval($order) ." WHERE category_id = ". intval($category['category_id']));
				reset_cache('faq_categories');
			}
			
			$action = new K4InformationAction(new K4LanguageElement('L_UPDATEDFAQCATEGORY', $category['name']), 'content', FALSE, 'admin.php?act=faq_categories', 3);
			return $action->execute($request);
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

/**
 * Show the form to edit a FAQ Category
 */
class AdminEditFAQCategory extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_FAQ');
			$request['template']->setVar('faq_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/faq.html');
			
			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_BADFAQCATEGORY'), 'content', FALSE, 'admin.php?act=faq_categories', 3);
				return $action->execute($request);
			}
			$category = $request['dba']->getRow("SELECT * FROM ". K4FAQCATEGORIES ." WHERE category_id = ". intval($_REQUEST['id']));
			
			if(!is_array($category) || empty($category)) {
				$action = new K4InformationAction(new K4LanguageElement('L_BADFAQCATEGORY'), 'content', FALSE, 'admin.php?act=faq_categories', 3);
				return $action->execute($request);
			}
			
			foreach($category as $key => $val) {
				$request['template']->setVar('faq_'. $key, $val);
			}
			
			$request['template']->setVisibility('edit', TRUE);
			$request['template']->setVisibility('add', FALSE);
			$request['template']->setFile('content', 'faq_addcategory.html');
			$request['template']->setVar('faq_action', 'admin.php?act=faq_cupdate');
			
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

class AdminUpdateFAQCategory extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_FAQ');
			$request['template']->setVar('faq_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/faq.html');
			
			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_BADFAQCATEGORY'), 'content', FALSE, 'admin.php?act=faq_categories', 3);
				return $action->execute($request);
			}
			$category = $request['dba']->getRow("SELECT * FROM ". K4FAQCATEGORIES ." WHERE category_id = ". intval($_REQUEST['id']));
			
			if(!is_array($category) || empty($category)) {
				$action = new K4InformationAction(new K4LanguageElement('L_BADFAQCATEGORY'), 'content', FALSE, 'admin.php?act=faq_categories', 3);
				return $action->execute($request);
			}
			
			foreach($category as $key => $val) {
				$request['template']->setVar('faq_'. $key, $val);
			}
			
			$request['dba']->beginTransaction();

			/* Build the queries */
			$update			= &$request['dba']->prepareStatement("UPDATE ". K4FAQCATEGORIES ." SET name=?,row_order=?,can_view=? WHERE category_id=?");
			
			/* Build the query for the categories table */
			$update->setString(1, $_REQUEST['name']);
			$update->setInt(2, $_REQUEST['row_order']);
			$update->setInt(3, $_REQUEST['can_view']);
			$update->setInt(4, $category['category_id']);
			
			/* Insert the extra category info */
			$update->executeUpdate();

			$request['dba']->commitTransaction();

			reset_cache('faq_categories');
			
			$action = new K4InformationAction(new K4LanguageElement('L_UPDATEDFAQCATEGORY', $category['name']), 'content', FALSE, 'admin.php?act=faq_categories', 3);
			return $action->execute($request);
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

/**
 * Remove a faq category and everything associated with it
 */
class AdminRemoveFAQCategory extends FAAction {
	function recursive_delete_faq($parent_id) {
		$cats = $request['dba']->executeQuery("SELECT * FROM ". K4FAQCATEGORIES ." WHERE parent_id = $parent_id");
		while($cats->next()) {
			$category = $cats->current();
			$request['dba']->executeUpdate("DELETE FROM ". K4FAQCATEGORIES ." WHERE category_id = ". $category['category_id']);
			$request['dba']->executeUpdate("DELETE FROM ". K4FAQANSWERS ." WHERE category_id = ". $category['category_id']);
			$this->recursive_delete_faq($category['category_id']);
		}
	}
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_FAQ');
			$request['template']->setVar('faq_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/faq.html');
			
			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_BADFAQCATEGORY'), 'content', FALSE, 'admin.php?act=faq_categories', 3);
				return $action->execute($request);
			}
			$category = $request['dba']->getRow("SELECT * FROM ". K4FAQCATEGORIES ." WHERE category_id = ". intval($_REQUEST['id']));
			
			if(!is_array($category) || empty($category)) {
				$action = new K4InformationAction(new K4LanguageElement('L_BADFAQCATEGORY'), 'content', FALSE, 'admin.php?act=faq_categories', 3);
				return $action->execute($request);
			}
			
			$request['dba']->beginTransaction();

			$request['dba']->executeUpdate("DELETE FROM ". K4FAQCATEGORIES ." WHERE category_id = ". $category['category_id']);
			$request['dba']->executeUpdate("DELETE FROM ". K4FAQANSWERS ." WHERE category_id = ". $category['category_id']);
			$this->recursive_delete_faq($category['category_id']);
			
			$request['dba']->executeUpdate("UPDATE ". K4FAQCATEGORIES ." SET num_categories=num_categories-1 WHERE category_id = ". intval($category['parent_id']));

			$request['dba']->commitTransaction();

			reset_cache('faq_categories');

			$action = new K4InformationAction(new K4LanguageElement('L_REMOVEDFAQCATEGORY', $category['name']), 'content', FALSE, 'admin.php?act=faq_categories', 3);
			return $action->execute($request);
			
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

/**
 * Display FAQ categories and top-leve FAQ items
 */
class AdminFAQAnswers extends FAAction {
	function execute(&$request) {
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_FAQ');
			$request['template']->setVar('faq_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/faq.html');
			
			$row_level		= 1;
			$category_id	= 0;
			if(isset($_REQUEST['c']) && intval($_REQUEST['c']) > 0) {
				$category = $request['dba']->getRow("SELECT * FROM ". K4FAQCATEGORIES ." WHERE category_id = ". intval($_REQUEST['c']));

				if(is_array($category) && !empty($category)) {
					$row_level		= intval($category['row_level']);
					$category_id	= intval($category['category_id']);
					$request['template']->setVar('add_extra', '&c='. $category_id);
					$request['template']->setVar('add_catname', ': '. $category['name']);
				}
			}
			
			$it			= &new AdminFAQCategoriesIterator($request['dba'], $request['template']->getVar('IMG_DIR'));
			$answers	= $request['dba']->executeQuery("SELECT * FROM ". K4FAQANSWERS ." WHERE category_id = $category_id ORDER BY row_order ASC");

			$request['template']->setList('categories', $it);
			$request['template']->setList('answers', $answers);

			$request['template']->setFile('content', 'faq_answercategories.html');
		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

/**
 * Form to add a FAQ answer to a question
 */
class AdminFAQAddAnswer extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_FAQ');
			$request['template']->setVar('faq_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/faq.html');

			if(isset($_REQUEST['c']) && intval($_REQUEST['c']) > 0) {
				$category = $request['dba']->getRow("SELECT * FROM ". K4FAQCATEGORIES ." WHERE category_id = ". intval($_REQUEST['c']));

				if(is_array($category) && !empty($category)) {
					$request['template']->setVisibility('category_id', TRUE);
					$request['template']->setVar('category_id', $category['category_id']);
					$request['template']->setVar('category_name', $category['name']);
				}
			}
			
			$request['template']->setFile('content', 'faq_addanswer.html');
			$request['template']->setVar('faq_action', 'admin.php?act=faq_insertanswer');

		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

/**
 * Insert a FAQ answer into the database
 */
class AdminFAQInsertAnswer extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_FAQ');
			$request['template']->setVar('faq_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/faq.html');
			
			$category_id = 0;
			if(isset($_REQUEST['category_id']) && intval($_REQUEST['category_id']) > 0) {
				$category = $request['dba']->getRow("SELECT * FROM ". K4FAQCATEGORIES ." WHERE category_id = ". intval($_REQUEST['category_id']));

				if(is_array($category) && !empty($category)) {
					$category_id = $category['category_id'];
				}
			}
			
			$question = htmlentities(html_entity_decode($_REQUEST['question'], ENT_QUOTES), ENT_QUOTES);
			$bbcode = new BBCodex($request['dba'], $request['user']->getInfoArray(), $_REQUEST['answer'], FALSE, TRUE, TRUE, TRUE, TRUE);
			
			$insert = $request['dba']->prepareStatement("INSERT INTO ". K4FAQANSWERS ." (category_id,question,answer,row_order,created,can_view) VALUES (?,?,?,?,?,?)");
			
			$insert->setInt(1, $category_id);
			$insert->setString(2, $question);
			$insert->setString(3, $bbcode->parse());
			$insert->setInt(4, $_REQUEST['row_order']);
			$insert->setInt(5, time());
			$insert->setInt(6, $_REQUEST['can_view']);

			$insert->executeUpdate();

			$request['dba']->executeUpdate("UPDATE ". K4FAQCATEGORIES ." SET num_answers=num_answers+1 WHERE category_id = ". $category_id);
			
			$action = new K4InformationAction(new K4LanguageElement('L_ADDEDFAQANSWER', $question), 'content', FALSE, 'admin.php?act=faq_answers', 3);
			return $action->execute($request);

		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

/**
 * Display the form to edit a FAQ item
 */
class AdminEditFAQAnswer extends FAAction {
	function execute(&$request) {		
		
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_FAQ');
			$request['template']->setVar('faq_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/faq.html');
			
			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_BADFAQANSER'), 'content', FALSE);
				return $action->execute($request);
			}

			$faq = $request['dba']->getRow("SELECT * FROM ". K4FAQANSWERS ." WHERE answer_id = ". intval($_REQUEST['id']));
			
			if(!is_array($faq) || empty($faq)) {
				$action = new K4InformationAction(new K4LanguageElement('L_BADFAQANSER'), 'content', FALSE);
				return $action->execute($request);
			}
			
			$bbcode = new BBCodex($request['dba'], $request['user']->getInfoArray(), $faq['answer'], FALSE, TRUE, TRUE, TRUE, TRUE);
			$faq['answer'] = $bbcode->revert();

			foreach($faq as $key => $val) {
				$request['template']->setVar('faq_'. $key, $val);
			}
			
			$request['template']->setVisibility('add', FALSE);
			$request['template']->setVisibility('edit', TRUE);
			$request['template']->setFile('content', 'faq_addanswer.html');
			$request['template']->setVar('faq_action', 'admin.php?act=faq_aupdate');

		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

/**
 * Update a FAQ answer
 */
class AdminUpdateFAQAnswer extends FAAction {
	function execute(&$request) {
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_FAQ');
			$request['template']->setVar('faq_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/faq.html');
			
			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_BADFAQANSER'), 'content', FALSE);
				return $action->execute($request);
			}

			$faq = $request['dba']->getRow("SELECT * FROM ". K4FAQANSWERS ." WHERE answer_id = ". intval($_REQUEST['id']));
			
			if(!is_array($faq) || empty($faq)) {
				$action = new K4InformationAction(new K4LanguageElement('L_BADFAQANSER'), 'content', FALSE);
				return $action->execute($request);
			}
			
			$question = htmlentities(html_entity_decode($_REQUEST['question'], ENT_QUOTES), ENT_QUOTES);
			$bbcode = new BBCodex($request['dba'], $request['user']->getInfoArray(), $_REQUEST['answer'], FALSE, TRUE, TRUE, TRUE, TRUE);
			
			$update = $request['dba']->prepareStatement("UPDATE ". K4FAQANSWERS ." SET question=?,answer=?,row_order=?,can_view=? WHERE answer_id=?");
			
			$update->setString(1, $question);
			$update->setString(2, $bbcode->parse());
			$update->setInt(3, $_REQUEST['row_order']);
			$update->setInt(4, $_REQUEST['can_view']);
			$update->setInt(5, $faq['answer_id']);

			$update->executeUpdate();
			
			$action = new K4InformationAction(new K4LanguageElement('L_UPDATEDFAQANSWER', $faq['question']), 'content', FALSE, 'admin.php?act=faq_answers', 3);
			return $action->execute($request);

		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

/**
 * Remove a FAQ answer from the database
 */
class AdminRemoveFAQAnswer extends FAAction {
	function execute(&$request) {
		if($request['user']->isMember() && ($request['user']->get('perms') >= ADMIN)) {
			
			k4_bread_crumbs($request['template'], $request['dba'], 'L_FAQ');
			$request['template']->setVar('faq_on', '_on');
			$request['template']->setFile('sidebar_menu', 'menus/faq.html');
			
			if(!isset($_REQUEST['id']) || intval($_REQUEST['id']) == 0) {
				$action = new K4InformationAction(new K4LanguageElement('L_BADFAQANSER'), 'content', FALSE);
				return $action->execute($request);
			}

			$faq = $request['dba']->getRow("SELECT * FROM ". K4FAQANSWERS ." WHERE answer_id = ". intval($_REQUEST['id']));
			
			if(!is_array($faq) || empty($faq)) {
				$action = new K4InformationAction(new K4LanguageElement('L_BADFAQANSER'), 'content', FALSE);
				return $action->execute($request);
			}
			
			$request['dba']->executeUpdate("DELETE FROM ". K4FAQANSWERS ." WHERE answer_id = ". intval($faq['answer_id']));
			$request['dba']->executeUpdate("UPDATE ". K4FAQCATEGORIES ." SET num_answers=num_answers-1 WHERE category_id = ". intval($faq['category_id']));

			$action = new K4InformationAction(new K4LanguageElement('L_DELETEDFAQANSWER', $faq['question']), 'content', FALSE, 'admin.php?act=faq_answers', 3);
			return $action->execute($request);

		} else {
			no_perms_error($request);
		}

		return TRUE;
	}
}

/**
 * iterate through FAQ categories and set the indent image
 */
class AdminFAQCategoriesIterator extends FAProxyIterator {
	var $dba, $result;

	function AdminFAQCategoriesIterator(&$dba, $image_dir) {
		$this->result		= &$dba->executeQuery("SELECT * FROM ". K4FAQCATEGORIES ." ORDER BY row_order ASC");
		$this->dba			= &$dba;
		$this->image_dir	= $image_dir;

		parent::__construct($this->result);
	}

	function &current() {
		$temp = parent::current();
		
		$temp['level']	= str_repeat('<img src="Images/'. $this->image_dir .'/Icons/threaded_bit.gif" alt="" border="0" />', $temp['row_level']-1);

		/* Should we free the result? */
		if(!$this->hasNext())
			$this->result->free();

		return $temp;
	}
}

?>