<?php
/**
* k4 Bulletin Board, rss.class.php
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
* Lesser General License for more details.
* 
* Licensed under the LGPL license
* http://www.gnu.org/copyleft/lesser.html
*
* @author Peter Goodman
* @version $Id$
* @package k42
*/

if(!defined('IN_K4')) {
	return;
}

class RSS_Element {
	function __toString() {
		return TRUE;
	}
	function Finalize() {
		return TRUE;
	}
}

class RSS_Data extends RSS_Element {
	var $data;

	function RSS_Data($data) {
		$this->data	= $data;
	}

	function __toString() {
		return $this->data;
	}
}

class RSS_Tag extends RSS_Element {
	var $name;
	var $parent;
	var $attribs;
	var $children	= array();

	function RSS_Tag($name, $attribs = FALSE, $parent = FALSE) {
		$this->name		= $name;
		$this->attribs	= $attribs;
		$this->parent	= $parent;
	}

	function __toString() {
		$data	= '';

		foreach ($this->children as $child) {
			$data	.= $child->__toString();
		}

		return $data;
	}

	function AddChild($element) {
		$this->children[]	= $element;
	}
}

class RSS_Channel extends RSS_Tag {
	var $title;
	var $link;
	var $description;
	var $subject;
	var $period;
	var $author_id;
	var $author_name;
	var $page;
	var $num_pages;
	var $post_id;

	function AddChild(RSS_Element $element) {
		if (is_a($element, 'RSS_Tag')) {
			switch ($element->name) {
				case 'title': {
					$this->title		= $element->__toString();
				break; }

				case 'link': {
					$this->link			= $element->__toString();
				break; }

				case 'description': {
					$this->description	= $element->__toString();
				break; }
				
				case 'category':
				case 'dc:subject': {
					$this->subject		= $element->__toString();
				break; }

				case 'syn:updatePeriod': {
					$period	= $element->__toString();

					switch ($period) {
						case 'hourly': $period	= 3600; break;
						default: $period	= 3600;
					}

					$this->period	= $period;

				break; }

				case 'ttl': {
					$this->period		= $element->__toString();
				break; }

				case 'syn:updateFrequency': {
					$frequency			= $element->__toString();

					if ($frequency > 0)
						$this->period	/= $frequency;

				break; }
				case 'postInfo:authorId': {
					$this->author_id	= intval($element->__toString());
				break; }
				case 'postInfo:authorName': {
					$this->author_name	= $element->__toString();
				break; }
				case 'postInfo:postId': {
					$this->post_id		= intval($element->__toString());
				break; }
			}
		}
	}

	function Finalize() {
		for ($parent = $this->parent; $parent = $parent->parent; ) {
			if (is_a($parent, 'RSS_Feed')) {
				$parent->channel	= $this;
				return TRUE;
			}
		}
	}
}

class RSS_Item extends RSS_Tag {
	var $title;
	var $link;
	var $description;
	var $subject;
	var $date;

	function AddChild($element) {
		if (is_a($element, 'RSS_Tag')) {
			switch ($element->name) {
				case 'title': {
					$this->title		= $element->__toString();
				break; }

				case 'link': {
					$this->link			= $element->__toString();
				break; }

				case 'description': {
					$this->description	= $element->__toString();
				break; }

				case 'dc:subject':
				case 'category': {
					$this->subject		= $element->__toString();
				break; }

				case 'date':
				case 'dc:date':
				case 'pubDate': {
					$this->date			= strtotime($element->__toString());
				break; }
				case 'postInfo:pubDate': {
					$this->date			= intval($element->__toString());
				break; }
			}
		}
	}

	function Finalize() {
		for ($parent = $this->parent; $parent = $parent->parent; ) {
			if (is_a($parent, 'RSS_Feed')) {
				$parent->items[]	= $this;
				return TRUE;
			}
		}
	}
}

class RSS_Feed extends RSS_Tag {
	var $feed;
	var $channel;
	var $image;
	var $items;
	var $link;

	function RSS_Feed($feed) {
		$this->feed	= $feed;
	}

	function AddChild($element) {
		if (is_a($element, 'RSS_Tag')) {
			switch ($element->name) {
				case 'channel': {
					$this->channel	= $element;
					break;
				}
				case 'image': {
					$this->image	= $element;
					break;
				}
				case 'item': {
					$this->items[]	= $element;
					break;
				}
				default: {
					parent::AddChild($element);
				}
			}
		}
	}
}

class RSS_Parser {
	
	var $stack;

	function HandleClose($parser, $name) {
		$element	= array_pop($this->stack);
		$parent		= end($this->stack);

		$element->Finalize();
		$parent->AddChild($element);
	}

	function HandleData($parser, $data) {
		if (!trim($data))
			return FALSE;

		$parent		= end($this->stack);
		$element	= new RSS_Data($data, $parent);

		$parent->AddChild($element);
	}

	function HandleOpen($parser, $name, $attribs) {
		$parent	= end($this->stack);

		switch($name) {
			case 'channel': {
				$element	= new RSS_Channel($name, $attribs, $parent);
			break; }

			case 'image': {
				$element	= new RSS_Tag($name, $attribs, $parent);
			break; }

			case 'item': {
				$element	= new RSS_Item($name, $attribs, $parent);
			break; }

			default: {
				$element	= new RSS_Tag($name, $attribs, $parent);
			}
		}

		array_push($this->stack, $element);
	}

	function Parse($feed) {
		$fp			= fopen($feed, 'r');
		$feed		= array();
		if ($fp) {

			$parser	= xml_parser_create('ISO-8859-1');
			
			xml_set_object($parser, $this);
			xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, false);
			xml_set_element_handler($parser, 'HandleOpen', 'HandleClose');
			xml_set_character_data_handler($parser, 'HandleData');

			$this->stack	= array();

			array_push($this->stack, new RSS_Feed($feed));

			while ($data = fread($fp, 4096)) {
				$result	= xml_parse($parser, $data, feof($fp));

//				if ($result == FALSE) {
//					//XML parser error
//					$error		= xml_error_string(xml_get_error_code($parser));
//					//30 characters around the location of the error
//					$context	= substr($data, xml_get_current_byte_index($parser) - 15, 30);
//
//					throw new TPL_ParserException("$error [$context]", $filename, xml_get_current_line_number($parser));
//				}
			}

			xml_parser_free($parser);
			fclose($fp);

			$feed	= array_pop($this->stack);
		}
		return $feed;
	}
}

?>