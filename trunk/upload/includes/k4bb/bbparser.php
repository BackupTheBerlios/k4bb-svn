<?php
/**
* k4 Bulletin Board, bbparser.php
*
* Copyright (c) 2005, Geoffrey Goodman
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
* @author Geoffrey Goodman
* @version $Id:$
* @package k4bb
*/

//if(!defined('IN_K4')) {
//	return;
//}

class BBEmoticons {
//	var $_smilies = array(
//		':D' => '<img src="http://www.k4bb.org/k4/tmp/upload/emoticons/icon_biggrin.gif" />',
//		':o' => '<img src="http://www.k4bb.org/k4/tmp/upload/emoticons/icon_redface.gif" />',
//		';)' => '<img src="http://www.k4bb.org/k4/tmp/upload/emoticons/icon_wink.gif" />',
//		':p' => '<img src="http://www.k4bb.org/k4/tmp/upload/emoticons/icon_razz.gif" />',
//		':)' => '<img src="http://www.k4bb.org/k4/tmp/upload/emoticons/icon_smile.gif" />',
//		':(' => '<img src="http://www.k4bb.org/k4/tmp/upload/emoticons/icon_frown.gif" />',
//		);
//	
//	var $_emos = array(
//		':confused:' => '<img src="http://www.k4bb.org/k4/tmp/upload/emoticons/icon_confused.gif" />',
//		':cool:' => '<img src="http://www.k4bb.org/k4/tmp/upload/emoticons/icon_cool.gif" />',
//		':eek:' => '<img src="http://www.k4bb.org/k4/tmp/upload/emoticons/icon_eek.gif" />',
//		':mad:' => '<img src="http://www.k4bb.org/k4/tmp/upload/emoticons/icon_mad.gif" />',
//		':rolleyes:' => '<img src="http://www.k4bb.org/k4/tmp/upload/emoticons/icon_rolleyes.gif" />',
//		':twisted:' => '<img src="http://www.k4bb.org/k4/tmp/upload/emoticons/icon_twisted.gif" />',
//		);

	var $_emos = array();
	var $_smilies = array();
	
	function BBEmoticons() {
		$this->getSmiliesEmos();
	}

	function &getInstance() {
		static $instance = NULL;

		if ($instance === NULL)
			$instance = array(new BBEmoticons);

		return $instance[0];
	}

	function getSmilyImg($item) {
		return "<img src=\"tmp/upload/emoticons/". $item['image'] ."\" alt=\"". $item['description'] ."\" />";
	}

	function parse($text) {
		foreach ($this->_smilies as $smily) { //  => $img
			$regex = '~('.preg_quote($smily['typed']).')(\W)~e';

			$text = preg_replace($regex, '$this->_smilies["$1"]."$2"', $text);
		}

		$text = str_replace(array_keys($this->_emos), array_values($this->_emos), $text);

		return $text;
	}

	function revert($text) {
		$text = strtr($text, array_flip($this->_smilies));
		$text = strtr($text, array_flip($this->_emos));
		
		return $text;
	}

	function getSmiliesEmos() {
		global $_DBA;
		
		if(is_a($_DBA, 'FADBConnection')) {
			$all = $_DBA->executeQuery("SELECT * FROM ". K4EMOTICONS);

			while($all->next()) {
				$item = $all->current();

				if($item['typed']{0} == ':' && $item['typed']{(strlen($item['typed'])-1)} == ':') {
					$this->_emos[$item['typed']] = $this->getSmilyImg($item);
				} else {
					$this->_smilies[$item['typed']] = $this->getSmilyImg($item);
				}
			}
			
			$all->free();
		}
	}
}

class FAStack {
	var $_items = array();
	var $_size = 0;

	function _update() {
		$this->_size = sizeof($this->_items);
	}
	
	function getSize() {
		return $this->_size;
	}

	function pop() {
        $ret = FALSE;
        
        if ($this->_size > 0)
            $ret = TRUE;
        
		array_pop($this->_items);
		$this->_update();

		return $ret;
	}

	function push(&$value) {
		$this->_items[] = &$value;
		$this->_update();
	}

	function &top() {
        $ret = NULL;
        
		if ($this->_size > 0)
			$ret = &$this->_items[$this->_size - 1];
        
        return $ret;
	}
}

class BBNode {
	var $_children = array();

	function addChild(&$child) {
		$this->_children[] = &$child;
	}

	function getChildren() {
		return $this->_children;
	}
    
    function getTagNames() {
        return array();
    }
    
    function getRevertPatterns() {
        return array();
    }

	function flatten($noparse = FALSE) {
		$buffer = '';
		
		for ($i = 0; $i < sizeof($this->_children); $i++) {
			$buffer .= $this->_children[$i]->flatten($noparse);
		}
		return $buffer;
	}
    
    function revert() {
		$buffer = '';
		
		for ($i = 0; $i < sizeof($this->_children); $i++) {
			$buffer .= $this->_children[$i]->revert();
		}
		
		return $buffer;
    }
}

class BBRootNode extends BBNode {
	function getTag() {
		return 'ROOT_TAG';
	}
	/*
	function revert() {
		return str_replace('&amp;', '&', parent::revert());
	}
	*/
}

class BBTextNode extends BBNode {
	var $_text;

	function BBTextNode($text) {
		$this->_text = $text;
	}

	function handleUrl($matches) {
		$url = ($matches[2]) ? $matches[0] : 'http://' . $matches[0];

		return "<a class=\"bb_url\" href=\"$url\">{$matches[0]}"; // </a>
	}

	function flatten($noparse = FALSE) {
		if ($noparse) return $this->_text;

		$buffer = preg_replace_callback('~((https?\:\/\/|ftps?\:\/\/)?(?:(?:[\w\d\-_\+\.]+\:)?(?:[\w\d\-_\+\.]+)?\@)?(?:[\w\d][\d_\-\w\.]+\w){2,}?\.[\dA-Za-z]{2,7})([\:\/]\S*)?~',
			array(&$this, 'handleUrl'), $this->_text);

		$paras = preg_split('~(?:\r?\n){2}~', $buffer);

		if (count($paras) > 1) {
			$buffer = '';

			foreach ($paras as $para)
				if ($para = trim($para))
					$buffer .= "\n\n<p>".nl2br($para)."</p>";
				
				$buffer .= "\n\n";
		}

		$emos = &BBEmoticons::getInstance();
		$buffer = $emos->parse($buffer);

		return $buffer;
	}
    
    function revert() {		
        return $this->_text;
    }
}

class BBTagNode extends BBNode {
	var $_tag;
	var $_attrib;

	function BBTagNode($tag, $attrib) {
		$this->_tag = $tag;
		$this->_attrib = $attrib;
	}
    
    function getClass() {
        return 'class="bb_'.$this->getTag().'"';
    }

	function getTag() {
		return $this->_tag;
	}

	function getUnparsed($noparse = FALSE) {
		$attrib = ($this->_attrib) ? '=' . $this->_attrib : '';

		return "[{$this->_tag}$attrib]" . parent::flatten($noparse) . "[/{$this->_tag}]";
	}
}

class BBDefaultNode extends BBTagNode {
	function flatten($noparse = FALSE) {
		return $this->getUnparsed($noparse);
	}
}

class BBTagRegistry {
	var $_parse = array();
    var $_revert = array();
	var $_default;

	function BBTagRegistry($default) {
		$this->_default = $default;
	}

	function getParserClass($tag) {
		$class = $this->_default;
		$tag = strtolower($tag);

		if (isset($this->_parse[$tag]))
			$class = $this->_parse[$tag];

		return $class;
	}
    
    function getReverterClass($tag) {
        $ret = $this->_default;
        
        foreach ($this->_revert as $class => $pattern) {
            if (preg_match($pattern, $tag)) {
                $ret = $class;
            }
        }
        
        return $ret;
    }
    
    function register($class) {
        if (class_exists($class)) {
            $tags = call_user_func(array($class, 'getTagNames'));
            
            foreach ($tags as $tag)
                $this->_parse[$tag] = $class;
            
            $patterns = call_user_func(array($class, 'getRevertPatterns'));
            
            foreach ($patterns as $pattern)
                $this->_revert[$pattern] = $class;
        }
    }
}

class BBParser {
	var $_reg;
	
	function BBParser() {
		$this->_reg = &new BBTagRegistry('BBDefaultNode');
	}

	function register($class) {
		$this->_reg->register($class);
		return TRUE;
	}

	function &createRegistry() {
        $this->register('BBFormatNode');
		$this->register('BBCenterNode');
		$this->register('BBLeftNode');
		$this->register('BBRightNode');
		$this->register('BBJustifyNode');
		$this->register('BBHorizauntalRuleNode');
		$this->register('BBCodeNode');
        $this->register('BBCodeBodyNode');
        $this->register('BBCodeTitleNode');
		$this->register('BBLinkNode');
		$this->register('BBListNode');
		$this->register('BBListItemNode');
		$this->register('BBPhpNode');
        $this->register('BBPhpBodyNode');
        $this->register('BBPhpTitleNode');
		$this->register('BBQuoteNode');
		$this->register('BBQuoteBodyNode');
		$this->register('BBQuoteTitleNode');
		$this->register('BBLinkNode');

		return $this->_reg;
	}
    
    function revert($buffer) {
 		$stack = &new FAStack;
		$root = &new BBRootNode;
		$registry = &$this->createRegistry();
		$emos = &BBEmoticons::getInstance();
        
        $buffer = preg_replace('~<br( /)?>\n?~', "\n", $buffer);
		$buffer = $emos->revert($buffer);

		$stack->push($root);

		$matches = preg_split('~< ( (?>[^<>]+) | (?R) )* >~x', $buffer, -1, PREG_SPLIT_DELIM_CAPTURE);
        
		foreach ($matches as $i => $match) {
			$parent = &$stack->top();

			if ((int)$i & 1) {
				if (preg_match('~^(/?)([a-z]+)(?: class="bb_([a-z]+)"(.*?)(/?))?$~i', $match, $tag)) {
                    if ($tag[5] == '/') {
    					$class = $registry->getParserClass($tag[3]);
                        
						$node = &new $class($tag[3], $tag[4]);
						$parent->addChild($node);
                    } elseif ($tag[1] == '/') {
						if ($stack->getSize() > 1)
                        	$stack->pop();
					} else {
    					$class = $registry->getParserClass($tag[3]);
                        
						$node = &new $class($tag[3], $tag[4]);
						$stack->push($node);
						$parent->addChild($node);
					}
				} else {
					$class = $registry->getParserClass('');
					
					$node = &new $class('', '');
					$stack->push($node);
					$parent->addChild($node);
				}
			} else {
				$node = &new BBTextNode($match);
				$parent->addChild($node);
			}

		}

		return $root->revert();
   }

	function parse($buffer) {
		$stack = &new FAStack;
		$root = &new BBRootNode;
		$registry = &$this->createRegistry();

		$stack->push($root);

		$buffer = htmlentities($buffer, ENT_QUOTES);
		$buffer = preg_replace('~(\r?\n)~', '<br />', $buffer);
		$matches = preg_split('~\[ ( (?>[^\[\]]+) | (?R) )* \]~x', $buffer, -1, PREG_SPLIT_DELIM_CAPTURE);

		foreach ($matches as $i => $match) {
			$parent = &$stack->top();

			if ((int)$i & 1) {
				if (preg_match('~^(/?)([a-z]+)(?:=([^\]]*))?$~i', $match, $tag)) {
					$class = $registry->getParserClass($tag[2]);

					if ($tag[1] == '/') {
						if ($tag[2] == $parent->getTag()) {
							$stack->pop();
						} else {
							$node = &new BBTextNode("[$match]");
							$parent->addChild($node);
						}
					} else {
						$node = &new $class($tag[2], $tag[3]);
						$stack->push($node);
						$parent->addChild($node);
					}
				} else {
					$node = &new BBTextNode("[$match]");
					$parent->addChild($node);
				}
			} else {
				$node = &new BBTextNode($match);
				$parent->addChild($node);
			}

		}

		while ($stack->getSize() > 1) {
			//$node = &$stack->pop();
			//echo "Incomplete ".$node->getTag().", automatically closed<br />\n";
		}

		return $root->flatten();
	}

	// compare polls
	function comparePolls($post_id, $new_text, $old_text, &$dba) {
		
		$to_delete = array();
		$new_polls = array();

		preg_match_all('~\[poll=([0-9]+?)\]~i', $new_text, $new_poll_matches, PREG_SET_ORDER);
		preg_match_all('~\[poll=([0-9]+?)\]~i', $old_text, $old_poll_matches, PREG_SET_ORDER);
		
		// go over the new text
		if(isset($new_poll_matches[0])) {
			$i = 0;
			foreach($new_poll_matches as $poll) {
				if($i > Globals::getGlobal('maxpollquestions')) {
					$new_text = str_replace($poll[0], '', $new_text);
					$to_delete[] = $poll[1];
				} else {
					$new_polls[]	= $poll[1];
				}
				$i++;
			}
		}
		
		// go over the old text
		if(isset($old_poll_matches[0])) {
			foreach($old_poll_matches as $poll) {
				if(!in_array($poll[1], $new_polls)) {
					$to_delete[] = $poll[1];
				}
			}
		}
		// delete all the polls that need to be removed
		foreach($to_delete as $poll_id) {
			
			// check if this poll is being used somewhere else
			$topic_matches		= $dba->executeQuery("SELECT * FROM ". K4POSTS ." WHERE lower(body_text) LIKE lower('%[poll=". $poll_id ."]%') AND post_id <> ". intval($post_id));
			$reply_matches		= $dba->executeQuery("SELECT * FROM ". K4POSTS ." WHERE lower(body_text) LIKE lower('%[poll=". $poll_id ."]%') AND post_id <> ". intval($post_id));
			
			// we can delete it
			if( !$topic_matches->hasNext() && !$reply_matches->hasNext() ) {
				
				$dba->executeUpdate("DELETE FROM ". K4POLLQUESTIONS ." WHERE id = ". intval($poll_id));
				$dba->executeUpdate("DELETE FROM ". K4POLLANSWERS ." WHERE question_id = ". intval($poll_id));
				$dba->executeUpdate("DELETE FROM ". K4POLLVOTES ." WHERE question_id = ". intval($poll_id));
			}
		}
		
		return $new_text;
	}
}

/**
 *
 * TAG COMPILERS
 *
 */

class BBCenterNode extends BBTagNode {
	function flatten($noparse = FALSE) {
		if ($noparse) return $this->getUnparsed($noparse);

		$body = parent::flatten($noparse);
        $class = $this->getClass();

		return "<div $class style=\"text-align: center;\">$body</div>";
	}
    
    function getTagNames() {
        return array('center');
    }
    
    function revert() {
        $body = parent::revert();
        
        return "[center]{$body}[/center]";
    }
}
class BBLeftNode extends BBTagNode {
	function flatten($noparse = FALSE) {
		if ($noparse) return $this->getUnparsed($noparse);

		$body = parent::flatten($noparse);
        $class = $this->getClass();

		return "<div $class style=\"text-align: left;\">$body</div>";
	}
    
    function getTagNames() {
        return array('left');
    }
    
    function revert() {
        $body = parent::revert();
        
        return "[left]{$body}[/left]";
    }
}
class BBRightNode extends BBTagNode {
	function flatten($noparse = FALSE) {
		if ($noparse) return $this->getUnparsed($noparse);

		$body = parent::flatten($noparse);
        $class = $this->getClass();

		return "<div $class style=\"text-align: right;\">$body</div>";
	}
    
    function getTagNames() {
        return array('right');
    }
    
    function revert() {
        $body = parent::revert();
        
        return "[right]{$body}[/right]";
    }
}
class BBJustifyNode extends BBTagNode {
	function flatten($noparse = FALSE) {
		if ($noparse) return $this->getUnparsed($noparse);

		$body = parent::flatten($noparse);
        $class = $this->getClass();

		return "<div $class style=\"text-align: justify;\">$body</div>";
	}
    
    function getTagNames() {
        return array('justify');
    }
    
    function revert() {
        $body = parent::revert();
        
        return "[justify]{$body}[/justify]";
    }
}
class BBHorizauntalRuleNode extends BBTagNode {
	function flatten($noparse = FALSE) {
		if ($noparse) return $this->getUnparsed($noparse);
		return "<hr />";
	}
    
    function getTagNames() {
        return array('hr');
    }
    
    function revert() {
        return "[hr /]";
    }
}
class BBCodeNode extends BBTagNode {
	function flatten($noparse = FALSE) {
		if ($noparse) return $this->getUnparsed($noparse);

		$body = parent::flatten(TRUE);

		$title = '<div class="bb_codetitle">CODE:</div>';
		$body = '<pre class="bb_codebody">' . $body . '</pre>';
        $class = $this->getClass();


		return "<div $class>$title$body</div>";
	}
    
    function getTagNames() {
        return array('code');
    }
    
    function revert() {
        $body = parent::revert();
        
        return "[code]{$body}[/code]";
    }
}

class BBCodeBodyNode extends BBTagNode {
    function getTagNames() {
        return array('codebody');
    }
    
    function revert() {
        return parent::revert();
    }
}

class BBCodeTitleNode extends BBTagNode {
    function getTagNames() {
        return array('codetitle');
    }
    
    function revert() {
        return '';
    }
}

class BBFormatNode extends BBTagNode {
	function getSize() {
		$size = $this->_attrib;

		if (intval($size) == $size)
			return $size;

		if (in_array($size, array('small', 'medium', 'large')))
			return $size;
	}

	function getColor() {
		$color = $this->_attrib;

		if (ctype_alpha($color))
			return $color;

		if (preg_match('~^#[0-9a-f]{3,6}$~i', $color))
			return $color;
	}

	function getFont() {
		$font = $this->_attrib;

		if (ctype_alpha($font))
			return $font;
	}

	function flatten($noparse = FALSE) {
		if ($noparse) return $this->getUnparsed($noparse);

		$body = parent::flatten($noparse);
		$styles = array(
			'b' => 'font-weight: bold;',
			'i' => 'font-style: italic;',
			'u' => 'text-decoration: underline;',
			'color' => 'color: '.$this->getColor().';',
			'size' => 'font-size: '.$this->getSize().';',
			'font' => 'font-family: '.$this->getFont().';',
			'indent' => 'margin-left: 40px;',
			'outdent' => 'margin-left: 0px;',
		);
		
        $tag = $this->getTag();
		$style = $styles[$tag];
        $class = $this->getClass();

		return "<span $class style=\"$style\">$body</span>";
	}
    
    function getTagNames() {
        return array('b', 'color', 'i', 'size', 'u', 'font', 'indent', 'outdent');
    }
    
    function revert() {
        switch ($this->getTag()) {
            case 'b': {
                return "[b]".parent::revert()."[/b]";
            }
            case 'i': {
                return "[i]".parent::revert()."[/i]";
            }
            case 'u': {
                return "[u]".parent::revert()."[/u]";
            }
            case 'size': {
                $size = preg_replace('~^.*font-size: (\S+);.*$~', '$1', $this->_attrib);
                return "[size=$size]".parent::revert()."[/size]";
            }
            case 'color': {
				$color = preg_replace('~^.*color: (\S+);.*$~', '$1', $this->_attrib);
				return "[color=$color]".parent::revert()."[/color]";
            }
			case 'font': {
				$font = preg_replace('~^.*font-family: (\S+);.*$~', '$1', $this->_attrib);
				return "[font=$font]".parent::revert()."[/font]";
            }
			 case 'indent': {
                return "[indent]".parent::revert()."[/indent]";
            }
			 case 'outdent': {
                return "[outdent]".parent::revert()."[/outdent]";
            }
       }
    }
}

class BBLinkNode extends BBTagNode {
	function flatten($noparse = FALSE) {
		if ($noparse) return $this->getUnparsed($noparse);

		$body = parent::flatten(TRUE);
		$url = ($this->_attrib) ? $this->_attrib : $this->_body;
        $class = $this->getClass();

		return "<a $class href=\"$url\">$body</a>";
	}
    
    function getTagNames() {
        return array('link', 'url');
    }
    
    function revert() {
        $body = parent::revert();
		$tag = $this->getTag();
		
		$url = preg_replace('~^.*href="([^"]*)".*$~', '$1', $this->_attrib);
        
        return "[$tag=$url]{$body}[/$tag]";
    }
}

class BBListNode extends BBTagNode {
	function flatten($noparse = FALSE) {
		if ($noparse) return $this->getUnparsed($noparse);

		$body = parent::flatten($noparse);
        $class = $this->getClass();

		$items = explode('[*]', $body);
		$param = $this->_attrib;

		if (ctype_digit($param)) {
			$list = 'ol';
			$attribs = " type=\"1\" start=\"$param\"";
		} elseif ($param == 'a' || $param == 'A' || $param == 'i' || $param == 'I') {
			$list = 'ol';
			$attribs = " type=\"$param\"";
		} else {
			$list = 'ul';
			$attribs = '';
		}

		$buffer = '';
		foreach ($items as $item) {
			if (trim($item))
				$buffer .= "<li class=\"bb_li\">$item</li>";
		}

		return "<$list $class$attribs>\n$buffer</$list>";
	}
    
    function getTagNames() {
        return array('list');
    }
    
    function revert() {
        $body = parent::revert();
		$type = preg_replace('~^.*type(=)"([^"]*)".*$~', '$1$2', $this->_attrib);
        
        return "[list$type]{$body}[/list]";
    }
}

class BBPollNode extends BBTagNode {
	function flatten($noparse = FALSE) {
		if ($noparse) return $this->getUnparsed($noparse);
		
		$body			= parent::flatten($noparse);
        $question		= trim($this->_attrib);
		
		$items			= explode('[*]', $body);
		$param			= $this->_attrib;
		
		$maxpolloptions = intval(Globals::getGlobal('maxpolloptions'));
		$forum_id		= intval(Globals::getGlobal('forum_id'));
		
		if(!Globals::getGlobal('num_polls')) {
			Globals::setGlobal('num_polls', 0);
		}

		$can_poll = ($forum_id > 0 && $_SESSION['user']->get('perms') >= get_map( 'bbcode', 'can_add', array('forum_id'=>$forum_id)));

		$ret = '';
		
		if(count($items) > 0 && $maxpolloptions > 0 && $can_poll && $question != '' && Globals::getGlobal('num_polls') <= Globals::getGlobal('maxpollquestions')) {
			
			global $_DBA;

			$question		= $_DBA->quote(k4_htmlentities($question, ENT_QUOTES));
			$insert_question= $_DBA->executeUpdate("INSERT INTO ". K4POLLQUESTIONS ." (question, created, user_id, user_name) VALUES ('{$question}', ". time() .", ". intval($_SESSION['user']->get('id')) .", '". $_DBA->quote($_SESSION['user']->get('name')) ."')");
			$question_id	= $_DBA->getInsertId(K4POLLQUESTIONS, 'id');

			$buffer = '';
			$i = 0;
			foreach ($items as $item) {
				if($i >= $maxpolloptions) {
					break;
				}
				$item = trim(strip_tags(preg_replace("~(\r\n|\r|\n|\t|<br>|<br\/>|<br \/>)~i", "", $item)));
				if ($item != '') {
					$_DBA->executeUpdate("INSERT INTO ". K4POLLANSWERS ." (question_id,answer) VALUES (". intval($question_id) .", '". $_DBA->quote(k4_htmlentities($item, ENT_QUOTES)) ."')");
					$i++;
				}
			}
			
			Globals::setGlobal('is_poll', TRUE);
			Globals::setGlobal('num_polls', Globals::getGlobal('num_polls')+1);

			$ret = "[poll=$question_id]";
		}
		return $ret;
	}
    
    function getTagNames() {
        return array('question');
    }
    
    function revert() {
        return "";
    }
}

class BBListItemNode extends BBTagNode {
	function getTagNames() {
		return array('li');
	}
	
    function revert() {
        $body = parent::revert();
        
        return "[*]{$body}";
    }
}

class BBPhpNode extends BBTagNode {
	function flatten($noparse = FALSE) {
		if ($noparse) return $this->getUnparsed($noparse);

		$body = parent::flatten(TRUE);
		$body = highlight_string(html_entity_decode($body), TRUE);

		$title = '<div class="bb_phptitle">&lt;PHP:</div>';
		$body = '<div class="bb_phpbody">' . $body . '</div>';
        $class = $this->getClass();

		return "<div $class>$title$body</div>";
	}
    
    function getTagNames() {
        return array('php');
    }
	
	function revert() {
		$body = parent::revert();
		
		return "[php]{$body}[/php]";
	}
}

class BBPhpBodyNode extends BBTagNode {
    function getTagNames() {
        return array('phpbody');
    }
    
    function revert() {
        return trim(html_entity_decode(strip_tags(parent::revert())));
    }
}

class BBPhpTitleNode extends BBTagNode {
    function getTagNames() {
        return array('phptitle');
    }
    
    function revert() {
        return '';
    }
}

class BBQuoteNode extends BBTagNode {
	function flatten($noparse = FALSE) {
		if ($noparse) return $this->getUnparsed($noparse);

		$body = parent::flatten();

		$title = '<div class="bb_quotetitle">' . (($this->_attrib) ? "QUOTE ({$this->_attrib}):" : 'QUOTE:') . '</div>';
		$body = '<div class="bb_quotebody">' . $body . '</div>';
        $class = $this->getClass();


		return "<div $class>$title$body</div>";
	}
    
    function getTagNames() {
        return array('quote');
    }
}

class BBQuoteBodyNode extends BBTagNode {
    function getTagNames() {
        return array('quotebody');
    }
    
    function revert() {
		return html_entity_decode(parent::revert()) . "[/quote]";
    }
}

class BBQuoteTitleNode extends BBTagNode {
    function getTagNames() {
        return array('quotetitle');
    }
    
    function revert() {
		$title = '';
		if (preg_match('~\((.+)\)~', parent::revert(), $matches)) {
			$title = "={$matches[1]}";
		}
        return "[quote$title]";
    }
}

?>