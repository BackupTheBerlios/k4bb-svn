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
* @package k4-something
*/



class BBEmoticons {
	var $_smilies = array(
		':D' => '<img src="http://www.k4bb.org/k4/tmp/upload/emoticons/icon_biggrin.gif" />',
		':o' => '<img src="http://www.k4bb.org/k4/tmp/upload/emoticons/icon_redface.gif" />',
		';)' => '<img src="http://www.k4bb.org/k4/tmp/upload/emoticons/icon_wink.gif" />',
		':p' => '<img src="http://www.k4bb.org/k4/tmp/upload/emoticons/icon_razz.gif" />',
		':)' => '<img src="http://www.k4bb.org/k4/tmp/upload/emoticons/icon_smile.gif" />',
		':(' => '<img src="http://www.k4bb.org/k4/tmp/upload/emoticons/icon_frown.gif" />',
		);
	
	var $_emos = array(
		':confused:' => '<img src="http://www.k4bb.org/k4/tmp/upload/emoticons/icon_confused.gif" />',
		':cool:' => '<img src="http://www.k4bb.org/k4/tmp/upload/emoticons/icon_cool.gif" />',
		':eek:' => '<img src="http://www.k4bb.org/k4/tmp/upload/emoticons/icon_eek.gif" />',
		':mad:' => '<img src="http://www.k4bb.org/k4/tmp/upload/emoticons/icon_mad.gif" />',
		':rolleyes:' => '<img src="http://www.k4bb.org/k4/tmp/upload/emoticons/icon_rolleyes.gif" />',
		':twisted:' => '<img src="http://www.k4bb.org/k4/tmp/upload/emoticons/icon_twisted.gif" />',
		);

	function &getInstance() {
		static $instance = NULL;

		if ($instance === NULL)
			$instance = array(new BBEmoticons);

		return $instance[0];
	}

	function getSmilyImg($emo) {
		$img = $this->_smilies[$emo];

		return "<img src=\"$img\" />";
	}

	function parse($text) {
		foreach ($this->_smilies as $smily => $img) {
			$regex = '~('.preg_quote($smily).')(\W)~e';

			$text = preg_replace($regex, '$this->_smilies["$1"]."$2"', $text);
		}

		$text = str_replace(array_keys($this->_emos), array_values($this->_emos), $text);

		return $text;
	}
}

class FAStack {
	var $_items = array();
	var $_size;

	function _update() {
		$this->_size = sizeof($this->_items);
	}

	function getArray() {
		return $this->_items;
	}

	function getSize() {
		return $this->_size;
	}

	function isEmpty() {
		return (!$this->getSize());
	}

	function &pop() {
		$top = &$this->top();

		array_pop($this->_items);
		$this->_update();

		return $top;
	}

	function push(&$value) {
		$this->_items[] = $value;
		$this->_update();
	}

	function &top() {
		if (isset($this->_items[$this->_size - 1]))
			return $this->_items[$this->_size - 1];
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

	function flatten($noparse = FALSE) {
		$buffer = '';

		for ($i = 0; $i < sizeof($this->_children); $i++) {
			$buffer .= $this->_children[$i]->flatten($noparse);
		}

		return $buffer;
	}
}

class BBRootNode extends BBNode {
	function getTag() {
		return 'ROOT_TAG';
	}
}

class BBTextNode extends BBNode {
	var $_text;

	function BBTextNode($text) {
		$this->_text = $text;
	}

	function handleUrl($matches) {
		$url = ($matches[2]) ? $matches[0] : 'http://' . $matches[0];

		return "<a href=\"$url\">{$matches[0]}</a>";
	}

	function flatten($noparse = FALSE) {
		if ($noparse) return $this->_text;

		$buffer = preg_replace_callback('~((https?\:\/\/|ftps?\:\/\/)?(?:(?:[\w\d\-_\+\.]+\:)?(?:[\w\d\-_\+\.]+)?\@)?(?:[\w\d][\d_\-\w\.]+\w){2,}?\.[\dA-Za-z]{2,7})([\:\/]\S*)?~',
			array(&$this, 'handleUrl'), $this->_text);

		$emos = &BBEmoticons::getInstance();
		$buffer = $emos->parse($buffer);

		$paras = preg_split('~(?:\r?\n){2}~', $buffer);

		if (count($paras) > 1) {
			$buffer = '';

			foreach ($paras as $para)
				if ($para = trim($para))
					$buffer .= "\n<p>".nl2br($para)."</p>\n";
		}

		return $buffer;
	}
}

class BBTagNode extends BBNode {
	var $_tag;
	var $_attrib;

	function BBTagNode($tag, $attrib) {
		$this->_tag = $tag;
		$this->_attrib = $attrib;
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
	var $_cache = array();
	var $_default;

	function BBTagRegistry($default) {
		$this->_default = $default;
	}

	function setClass($tag, $class) {
		$this->_cache[$tag] = $class;
	}

	function getClass($tag) {
		$class = $this->_default;
		$tag = strtolower($tag);

		if (isset($this->_cache[$tag]))
			$class = $this->_cache[$tag];

		return $class;
	}
}

class BBParser {
	function &createRegistry() {
		$reg = &new BBTagRegistry('BBDefaultNode');
		$reg->setClass('b', 'BBFormatNode');
		$reg->setClass('i', 'BBFormatNode');
		$reg->setClass('u', 'BBFormatNode');
		$reg->setClass('color', 'BBFormatNode');
		$reg->setClass('size', 'BBFormatNode');

		$reg->setClass('center', 'BBCenterNode');
		$reg->setClass('code', 'BBCodeNode');
		$reg->setClass('link', 'BBLinkNode');
		$reg->setClass('list', 'BBListNode');
		$reg->setClass('php', 'BBPhpNode');
		$reg->setClass('quote', 'BBQuoteNode');
		$reg->setClass('url', 'BBLinkNode');

		return $reg;
	}

	function parse($buffer) {
		$stack = &new FAStack;
		$root = &new BBRootNode;
		$registry = &$this->createRegistry();

		$stack->push($root);

		$buffer = htmlentities($buffer);
		$matches = preg_split('~\[ ( (?>[^\[\]]+) | (?R) )* \]~x', $buffer, -1, PREG_SPLIT_DELIM_CAPTURE);

		foreach ($matches as $i => $match) {
			$parent = &$stack->top();

			if ((int)$i & 1) {
				if (preg_match('~^(/?)([a-z]+)(?:=([^\]]*))?$~i', $match, $tag)) {
					$class = $registry->getClass($tag[2]);

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
			$node = &$stack->pop();
			echo "Incomplete ".$node->getTag().", automatically closed<br />\n";
		}

		echo $root->flatten();
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

		return "<div style=\"text-align: center;\">$body</div>";
	}
}

class BBCodeNode extends BBTagNode {
	function flatten($noparse = FALSE) {
		if ($noparse) return $this->getUnparsed($noparse);

		$body = parent::flatten(TRUE);

		$title = '<div class="bb_codetitle">CODE:</div>';
		$body = '<pre class="bb_codecontent">' . $body . '</pre>';

		return "<div class=\"bb_code\">$title$body</div>";
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

		if (preg_match('~^[0-9a-f]{3,6}$~i', $color))
			return $color;
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
		);
		
		$style = $styles[$this->getTag()];

		return "<span style=\"$style\">$body</span><!--".$this->getTag()."-->";
	}
}

class BBLinkNode extends BBTagNode {
	function flatten($noparse = FALSE) {
		if ($noparse) return $this->getUnparsed($noparse);

		$body = parent::flatten(TRUE);
		$url = ($this->_attrib) ? $this->_attrib : $this->_body;

		return "<a href=\"$url\">$body</a>";
	}
}

class BBListNode extends BBTagNode {
	function flatten($noparse = FALSE) {
		if ($noparse) return $this->getUnparsed($noparse);

		$body = parent::flatten($noparse);

		$items = explode('[*]', $body);

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
			if ($item = trim($item))
				$buffer .= "<li>$item</li>\n";
		}

		return "<$list$attribs>\n$buffer</$list>";
	}
}

class BBPhpNode extends BBTagNode {
	function flatten($noparse = FALSE) {
		if ($noparse) return $this->getUnparsed($noparse);

		$body = parent::flatten(TRUE);
		$body = highlight_string(html_entity_decode($body), TRUE);

		$title = '<div class="phptitle">&lt;PHP:</div>';
		$body = '<div class="phpbody">' . $body . '</div>';

		return "<div>$title$body</div>";
	}
}

class BBQuoteNode extends BBTagNode {
	function flatten($noparse = FALSE) {
		if ($noparse) return $this->getUnparsed($noparse);

		$body = parent::flatten();

		$title = '<div class="bb_quotetitle">' . (($this->_attrib) ? "QUOTE ({$this->_attrib}):" : 'QUOTE:') . '</div>';
		$body = '<div class="bb_quotebody">' . $body . '</div>';

		return "<div class=\"bb_quote\">$title$body</div>";
	}
}


$parser = &new BBParser;

$source = <<<EOF
www.bestwebever.com
http://bestwebever.com
http://k4.bestwebever.com.
[b]bold text[/b]
[i]italic text[/i]
[u]underlined text[/u]
[quote]quoted text[/quote]
[code]code text[/code]
[URL=http://www.bestwebever.com]BestWebEver.com[/URL]
[url]http://k4.bestwebever.com[/url]
[color=blue]colored text[/color]
[size=18]Sized text[/size]

Emoticons:
 :D :confused: :cool: :eek: :( :mad: :o :rolleyes: :) :p ;) :twisted:

[list=a]
[*]hiii
[*]hooooo
[*]
[list=A]
[*]hoohaaa
[/list]
[/list]


[php]<?php
$str = 'hello world'; echo $str; exit;
if(1 >= 0) { echo 'hii'; }
?>[/php]

[code]INSERT INTO BLAH '' VALUES blah;[/code]

[quote=peter wrote this on 29/04/2005]
hello...
[quote]just testing[/quote]
[quote]another test[/quote]
cool...
[/quote]

[quote]
[quote=k4st]
	top level
	[quote]
		second level
		[quote]third level[/quote]
		[quote]third level[/quote]
	[/quote]
	[quote]
		second level
		[quote]third level[/quote]
	[/quote]
	[quote]
		second level
		[quote]third level[/quote]
		[quote]third level[/quote]
	[/quote]
[/quote]
[b][/code]
[b][i]lalla[/i][/b]
[code]
[quote= ]
	top level
	[quote]
		second level
		[quote]third level[/quote]
		[quote]third level[/quote]
	[/quote]
	[quote]
		second level
		[quote]third level[/quote]
	[/quote]
	[quote]
		second level
		[quote]third level[/quote]
		[quote]third level[/quote]
	[/quote]
[/quote]
[b][/quote]
[i]lalla[/i][/b]

[b]www.k4bb.org
[quote]
	[b]this is bold[/b]
	[i]italicized[/i]
	[quote]
		[u]underlined[/u]
		[center]
		[quote]
			[quote]
				blah blah
			[quote]
			blah blah more and more layers
		[/quote]
	[/quote]
	[quote]
		blah2
	[/quote]
[/quote]
[php]<?php echo 'hello world!'; ?>[/php]
[code]
[/code]
[b]Example Bold[/b]
[/quote]
EOF;

$parser->parse($source);

?>