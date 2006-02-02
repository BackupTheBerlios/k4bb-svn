<?php
/**
* k4 Bulletin Board, faq.php
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
* @package k4-2.0-dev
*/

require "includes/filearts/filearts.php";
require "includes/k4bb/k4bb.php";

class K4DefaultAction extends FAAction {
	function execute(&$request) {
		
		$row_level		= 1;
		$category_id	= 0;
		$category		= FALSE;
		$faq			= FALSE;
		
		if(isset($_REQUEST['c']) && intval($_REQUEST['c']) > 0) {
			$category = $request['dba']->getRow("SELECT * FROM ". K4FAQCATEGORIES ." WHERE category_id = ". intval($_REQUEST['c']));

			if(is_array($category) && !empty($category)) {

				$row_level		= intval($category['row_level'])+1;
				$category_id	= intval($category['category_id']);
				$request['template']->setVar('add_extra', '&c='. $category_id);
				$request['template']->setVar('add_catname', ': '. $category['name']);
			}
		}
		
		k4_bread_crumbs($request['template'], $request['dba'], (!$category ? 'L_FAQLONG' : NULL), $category);
		$request['template']->setFile('content', 'faq.html');
		
		$result = $request['dba']->executeQuery("SELECT * FROM ". K4FAQCATEGORIES ." WHERE row_level=$row_level AND parent_id=$category_id AND can_view <= ". intval($request['user']->get('perms')) ." ORDER BY row_order ASC");
		$it		= &new K4FAQIterator($result, $request['dba']);
		$top_level = $request['dba']->executeQuery("SELECT * FROM ". K4FAQANSWERS ." WHERE category_id = $category_id AND can_view <= ". intval($request['user']->get('perms')) ." ORDER BY row_order ASC");
		
		$request['template']->setVar('has_top_level', ($top_level->hasNext() ? 1 : 0));
		$request['template']->setList('faq_categories', $it);
		$request['template']->setList('faq_answers', $top_level);

	}
}

class K4FAQIterator extends FAProxyIterator {
	var $result, $dba;
	
	function K4FAQIterator(&$result, &$dba) {
		$this->result		= &$result;
		$this->dba			= &$dba;

		parent::__construct($this->result);
	}

	function current() {
		$temp = parent::current();
		
		// if there are more than one answers
		if($temp['num_answers'] > 0) {
			$temp['sub_answers'] = $this->dba->executeQuery("SELECT * FROM ". K4FAQANSWERS ." WHERE category_id = ". intval($temp['category_id']) ." AND can_view <= ". intval($_SESSION['user']->get('perms')) ." ORDER BY row_order ASC");
		}

		// if there are more than one sub-categories
		if($temp['num_categories'] > 0) {
			$temp['sub_categories'] = $this->dba->executeQuery("SELECT * FROM ". K4FAQCATEGORIES ." WHERE parent_id = ". intval($temp['category_id']) ." AND can_view <= ". intval($_SESSION['user']->get('perms')));
		}
		
		// custom url's
		$temp['U_FAQANSWER'] = K4Url::getGenUrl('faq', 'c='. $temp['category_id'] .'#faq'. $temp['answer_id']);
		$temp['U_FAQCATEGORY'] = K4Url::getGenUrl('faq', 'c='. $temp['category_id']);

		/* Should we free the result? */
		if(!$this->hasNext())
			$this->result->free();

		return $temp;
	}
}

$app = &new K4Controller('forum_base.html');
$app->setAction('', new K4DefaultAction);
$app->setDefaultEvent('');

$app->execute();

?>