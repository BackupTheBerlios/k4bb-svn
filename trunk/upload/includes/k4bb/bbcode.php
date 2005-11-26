<?php
/**
* k4 Bulletin Board, bbcode.php
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
 * K4BBCodeParser, (c) 2005 Peter Goodman
 * @param _compilers		- An array of all of the current compilers
 * @param _default_compiler - A default compiler for non-supported tags
 *
 * HOW THIS PARSER WORKS:
 *
 * This parser is rather unique.. It uses stacks but not for parsing purposes.
 * We start off by looking for the first opening tag of any tag and then ALL of the. 
 * text after it. The order that the tags are parsed in is irrelevant and that's why 
 * this parser is such a beauty. Once it's found the first opening tag, it locates the 
 * numerical position of it relative to the start of the buffer (text). Now that we have 
 * found the first opening tag in the buffer, we have to find its closing tag. This is done 
 * through two methods: an algorithm that I figured out ( when the total number of matched 
 * tags currently in a loop is equal to twice the number of closing tags currently matched 
 * in the loop, that tag is your closing tag. This doesn't fail if the structure is perfect. 
 * I also use a stack to weed out any anomalous tags that come before/after if other checks 
 * don't figure it out. So we have everything to find the last tag. The way it's actually 
 * pin-pointed is every tag matched before it is tokenized so that they will be omitted in an 
 * strpos() to find where in the buffer it is. Yay, we have the position of our starting tag 
 * and our ending tag! Using substr(), we separate our 'tagged' and 'untagged' text (text after 
 * the closing tag). We parse out any anomalous tags using a combination of a multidimensional
 * array and a stack system, and now we can replace! Using substr_replace() and whatever
 * appropriate tag compiler, we replace the tag!
 *
 * Q) This sounds incredibly slow, Peter, why did you bother making it?
 * A) It is almost unbreakable.
 *
 * Q) I have an opened tag, but not a closed one, what happens to it?
 * A) The opened tag will be removed
 *
 * Q) I have tags surrounding no text, what happens to them?
 * A) They get removed
 *
 * Q) I have purposely intertwined closing and ending tags, what happens?
 * A) The first opening tag takes precedence and any anomalies (non-closed tags)
 *	  Get removed
 *
 * Q) Why didn't you do it all with regular expressions like everyone else?
 * A) Reglar expressions don't weed out anomalous tags, and to do that you need
 *	  functions to do a second pass on the text. Also, doing it the regex way means
 *	  that somewhere or another, the order that you call them matters. There is no
 *	  order that things need to be called in, and that's why it's so good.
 *
 * @author Peter Goodman
 */
class K4BBCodeParser {
	
	/**
	 * An array holding all of the tag compilers
	 */
	var $_compilers = array();

	/**
	 * The default tag compiler
	 */
	var $_default_compiler;

	/**
	 * An array of tags to omit while parsing.
	 * These should always be non-closing tags
	 */
	var $_omit_tags = array();
	
	/**
	 * A debug variable to exit search matches or not
	 */
	var $exit_matches = FALSE;

	/**
	 * Constructor, initialize the compilers
	 * @author Peter Goodman
	 */
	function K4BBCodeParser() {

		// set the default tag compiler
		$this->set_default_compiler(new K4Default_Compiler);

		// set tags to omit
		$this->set_omit_tags(array('question', '*', 'poll', 'hr'));

		// add the omit compiler
		$this->set_compiler('omit', new K4Omit_Compiler);
	}
	
	/**
	 * Find the first opening tag and start parsing
	 */
	function parse($buffer, $exit_matches = FALSE) {
		
		$this->exit_matches = $exit_matches;

		$buffer		= trim($buffer, "\r\n\s\t");

		// if there are any open tags
		if(preg_match('~(\[([a-z]+)(=([^\]]+))?\])(.*)~is', $buffer, $matches)) {

			// parse the open tags
			$buffer = preg_replace_callback('~(\[([a-z]+)(=([^\]]+))?\])(.*)~is', array($this, 'parse_tag'), $buffer);
		}
		
		// return the finished, compiled text
		return $this->finish($buffer);
	}

	/**
	 * Revert the text to bbcode
	 */
	function revert($buffer) {

		// loop through the compilers
		foreach($this->_compilers as $name => $compiler) {
				
			// revert the text
			$buffer = $compiler->revert($buffer);
		}
		
		// return the finished, reverted text
		return $buffer;
	}

	/**
	 * Find the closing tag for this opening tag. This will make the assumption that it
	 * is the first opening tag because if there was any other before it, it should have
	 * been replaced
	 */
	function parse_tag($matches) {

		// 0 -> entire string
		// 1 -> full tag
		// 2 -> name
		// 3 -> parameters (with =)
		// 4 -> parameters
		// 5 -> everything after the tag
		
		// the tag name
		$name		= $matches[2];
		
		
		if(!in_array(strtolower($name), $this->_omit_tags)) {
			
			// text that we have matched
			$buffer		= $matches[0];

			// the compiler for the current tag
			$compiler	= $this->get_compiler($name);

			// what will become the finished text with compiled tags
			$new_text	= '';
			
			// If there is a compiler for this tag
			if(is_a($compiler, 'K4BBCodeTag')) {

				// the starting position of the opening tag.. should always be 0
				$start_pos	= intval(strpos($buffer, $matches[1]));
				
				// start position of closing tag to default on
				$end_pos	= $this->find_closing_tag($name, $buffer, $start_pos, strpos($buffer, '[/'. $name .']'));

				// make sure to get the right positions
				$start_pos	= ($start_pos === FALSE ? 0 : $start_pos);
							
				// if the finishing tag doesn't exist, add it
				if($end_pos == strlen($buffer)) {
					$buffer = substr($buffer, 0, $end_pos) .'[/'. $name .']'. substr($buffer, $end_pos);
				}
				
				// this is the text inbetween the two given tags
				$tagged_text	= substr($buffer, ($start_pos + strlen($matches[1])), ($end_pos - ($start_pos + strlen($matches[1]))));
				$untagged_text	= substr($buffer, $end_pos + strlen('[/'. $name .']'));
				
				// If there were no closing tag matches
				if($end_pos < 0) {

					// remove the opening tag
					$new_text = substr($buffer, strlen($matches[1]));
					
					// start the process over
					return $this->parse($new_text);
				}
								
				// remove anomalous tags from the tagged and untagged text
				$this->remove_extra_tags($tagged_text);
				//$this->remove_extra_tags($untagged_text);
				
				// if the buffer isn't empty, compile the tags
				if(trim($tagged_text, "\r\n\s\t") != '') {
					
					// assemble our compiled text
					$new_text .= $compiler->parse_open($matches);
					$new_text .= $compiler->parse_buffer($tagged_text);
					$new_text .= $compiler->parse_close();
				}

				$new_text .= $untagged_text;
				
				// recursively call the parse function to restart the
				// proccess but with a new tag or return our compiled
				// text
				return $this->parse($new_text);
			}
		} else {
			return $matches[1] . $this->parse($matches[5]);
		}
	}

	/**
	 * Find the numeric position of the closing tag for $name
	 * in $buffer and return it
	 */
	function find_closing_tag($name, $buffer, $start_pos, $end_pos) {
		
		// 0 -> whole match
		// 1 -> whole tag
		// 2 -> '/' if it's an ending
		// 3 -> name
		// 4 -> params (with =)
		// 5 -> params
		
		// find all of this type of tag in the text
		preg_match_all('~(\[(/)?('. preg_quote($name) .')(=([^\]]+))?\])~i', $buffer, $tag_matches, PREG_SET_ORDER);
				
		// default position of the closing tag
		$pos			= $end_pos;
		
		// $i needs to be 1 for this to work
		$i				= 1;
		$num_total		= count($tag_matches);
		
		$num_closes		= 0;
		$tag_to_match	= '';
		
		// we will also use a stack to 'secure' that
		// we are finding _the_ right tag
		$stack			= array();
		$prev_tag		= array();

		// we also always want to store a previous versio
		// of the buffer to check against the stack
		
		// loop through the matches
		foreach($tag_matches as $match) {				

			// if this is an opening tag, add it to te stack
			if($match[2] == '') {
				$stack[] = $match;
			}

			// if this is a closing tag
			if($match[2] == '/') {
				
				// increment the number of closing tags matched
				$num_closes++;
				
				// the previous tag is an opening tag, pop it from
				// the stack (but only if they share the same tag name)
				if(!empty($prev_tag) && $prev_tag[2] == '' && $match[3] == $prev_tag[3]) {
					array_pop($stack);
				}

				// if this is the case, you have found your closing tag
				if((($i / 2) == $num_closes) || $i == 1) {
					$tag_to_match = $match[1];
					break;
				}
			}

			// replace the tag with a token so that we don't use it again
			$buffer			= substr_replace($buffer, str_repeat('*', strlen($match[1])), strpos($buffer, $match[1]), strlen($match[1]));
			
			// save the current tag as the previous tag for the next iteration
			$prev_tag		= $match;

			$i++;
		}
		
		// if we have reached the end of the loop without a match,
		// set the pos to the end of the string. The alternative is
		// look if the stack isn't empty
		if(!empty($stack) && $tag_to_match == '') {
			
			// tell the parser that we have not matched a closing tag and to
			// remove the opening tag from the buffer
			$pos = -1;
		}
		
		// since we are looking for the closing tag, we may not have
		// matched it because it is nonexistant, therefore we must either
		// remove the starting tag or create the tag
		if($tag_to_match == '' && $i == $num_total) {
			$pos = -1;

		}

		// if we have found our tag to match and if the stack ins't empty
		if(trim($tag_to_match, "\r\n\s\t") != '') {
			$pos = strpos($buffer, $tag_to_match);
		}

		// return the numeric position of the closing tag in $buffer
		return $pos;
	}
	
	/**
	 * Remove extra unwanted or unused tags
	 */
	function remove_extra_tags(&$buffer) {
		
		$workable_buffer = $buffer;

		// find all of the tags
		preg_match_all('~(\[(/)?([a-z]+)(=([^\]]+))?\])~i', $buffer, $tag_matches, PREG_SET_ORDER);
		
		// this will hold a multidimensional array of tag names -> all of the
		// tags with those names found
		$names				= array();

		// This is an array of positions of tags that need to be removed
		$remove_positions	= array();
		
		// loop through all of our matched tags and sort them into an array
		foreach($tag_matches as $match) {
			
			// 0 -> whole match
			// 1 -> whole tag
			// 2 -> '/' if it's an ending
			// 3 -> name
			// 4 -> params (with =)
			// 5 -> params
			
			// add this tag to the names array under its tag name
			if(!in_array(strtolower($match[3]), $this->_omit_tags)) {
				$names[strtolower($match[3])][] = $match;
			}
		}
				
		// did we find any subtags?
		if(!empty($names)) {
			
			// loop through all of the subtags
			foreach($names as $tagname => $tags) {
				
				// the number of matched tags under this category
				$count		= count($tags);
				
				// first-in-last-out stack for opening tags; opening tags will
				// be added to this and popped out when a closing tag is found
				$open		= array();
				
				// the previously opened tag
				$prev_tag	= array();

				// loop through the individual tags that we found
				foreach($tags as $tag) {

					// if this tag is open, add it to the array of open tags
					if($tag[2] == '') {
						$prev_tag	= $tag;
						$open[]		= $tag;
					}
					
					// if this tag is closed and there is an open tag for it, pop
					// the open tag out of the array
					if($tag[2] == '/') {
						
						// if the previous tag is this tags opening tag
						if(!empty($prev_tag) && $prev_tag[2] == '') {
							// pop the previous tag off of the stack
							array_pop($open);
						
						} else {

							// otherwise, remove the closing tag from the text
							$remove_positions[] = array(strpos($workable_buffer, $tag[1]), strlen($tag[1]));
						}
						
						// tokenize the workable buffer to maintain the same string length
						$workable_buffer = substr_replace($workable_buffer, str_repeat('*', strlen($tag[1])), strpos($workable_buffer, $tag[1]), strlen($tag[1]));
					}
				}
				
				// if there are still open tags within the text, remove them
				if(!empty($open)) {
					foreach($open as $tag) {
						$remove_positions[] = array(strpos($workable_buffer, $tag[1]), strlen($tag[1]));
					}
				}
			}
			
			// a variable to record how many characters we have removed
			// to subtract them from future replacements
			$num_chars_less = 0;

			// Now remove all of the extra tags we have found
			foreach($remove_positions as $pos) {
				$buffer = substr_replace($buffer, '', ($pos[0] - $num_chars_less), $pos[1]);
				
				// increment the number of characters to subtract in the
				// next iteration
				$num_chars_less += $pos[1];
			}
		}
	}
	function finish($buffer) {
		$buffer = str_replace(array('&#91;', '&#93;'), array('[', ']'), $buffer);

		return $buffer;
	}

	/**
	 * Get a tag compiler
	 */
	function get_compiler($name) {
		
		// set the compiler the the default one
		$ret	=& $this->_default_compiler;
		
		// if this compiler exists, change it
		if(isset($this->_compilers[$name])) {
			$ret =& $this->_compilers[$name];
		}
		
		// return the appropriate tag compiler
		return $ret;
	}

	/**
	 * Set a tag compiler
	 */
	function set_compiler($name, $class) {
		$this->_compilers[$name] =& $class;
	}

	/**
	 * Set a tag compiler to handle unsupported tags
	 */
	function set_default_compiler($class) {
		$this->_default_compiler =& $class;
	}

	/**
	 * Set all of the tags to omit
	 */
	function set_omit_tags($tags) {

		// add them to the array of arguments
		$this->_omit_tags	= array_merge($this->_omit_tags, $tags);
	}
	/**
	 * Clear the omit tags
	 */
	function clear_omit_tags() {
		$this->_omit_tags = array();
	}
}

/**
 * Interface for BB code compilers
 */
class K4BBCodeTag {
	
	/**
	 * Handle opening tags
	 */
	function parse_open() { }

	/**
	 * Hanlde closing tags
	 */
	function parse_close() { }

	/**
	 * Handle the text between the opening and closing tags
	 */
	function parse_buffer($buffer) { return $buffer; }

	/**
	 * Revert the parsed tag
	 */
	function revert($buffer) { return $buffer; }
	
	/**
	 * Get any tag parameters
	 */
	function get_params($matches) {
		$params = array();
		
//		// if this was a submatched element, i.e. matched from within
//		// a matched tags text
//		if(isset($matches[2]) && ($matches[2] == '' || $matches[2] == '/')) {
//			
//			$lookwhere = trim($matches[4]);
//
//		// otherwise
//		} else {
			$lookwhere = trim($matches[4]);
//		}
		
//		$pos = strpos($lookwhere, '=');
//
//		if($pos !== FALSE && $pos <= 1) {
//			$params[] = substr($lookwhere, $pos + 1);
//		}
//		print_r($params); exit;
		return array($lookwhere);
	}
}

/**
 * Default tag handler for unsupported tags, this will remove the
 * unsupported tags and maintain the text
 */
class K4Default_Compiler extends K4BBCodeTag {
	var $matches;
	function parse_open($matches) {
		$this->matches = $matches;

		return str_replace(array('[', ']'), array('&#91;', '&#93;'), $matches[1]);
	}
	function parse_close() {
		return '&#91;/'. $this->matches[3] .'&#93;';
	}
}

/**
 * Transform [b] tags
 */
class K4Bold_Compiler extends K4BBCodeTag {
	function parse_open() {
		return '<!-- BOLD --><span style="font-weight: bold;">';
	}
	function parse_close() {
		return '</span><!-- / BOLD -->';
	}
	function revert($buffer) {

		// revert all types of ways to accomplish bold text
		$buffer = preg_replace('~(<!-- BOLD -->)?<span style="font-weight: bold;">~i', '[b]', $buffer);
		$buffer = preg_replace('~</span><!-- / BOLD -->~i', '[/b]', $buffer);
		$buffer = preg_replace('~<strong>~i', '[b]', $buffer);
		$buffer = preg_replace('~</strong>~i', '[/b]', $buffer);
		$buffer = preg_replace('~<b>~i', '[b]', $buffer);
		$buffer = preg_replace('~</b>~i', '[/b]', $buffer);

		return $buffer;
	}
}

/**
 * Transform [i] tags
 */
class K4Italic_Compiler extends K4BBCodeTag {
	function parse_open() {
		return '<!-- ITALIC --><span style="font-style: italic;">';
	}
	function parse_close() {
		return '</span><!-- / ITALIC -->';
	}
	function revert($buffer) {

		// revert all types of ways to accomplish italic text
		$buffer = preg_replace('~(<!-- ITALIC -->)?<span style="font-style: italic;">~i', '[i]', $buffer);
		$buffer = preg_replace('~</span><!-- / ITALIC -->~i', '[/i]', $buffer);
		$buffer = preg_replace('~<em>~i', '[i]', $buffer);
		$buffer = preg_replace('~</em>~i', '[/i]', $buffer);

		return $buffer;
	}
}

/**
 * Transform [u] tags
 */
class K4Underline_Compiler extends K4BBCodeTag {
	function parse_open() {
		return '<!-- UNDERLINE --><span style="text-decoration: underline;">';
	}
	function parse_close() {
		return '</span><!-- / UNDERLINE -->';
	}
	function revert($buffer) {

		// revert all types of ways to accomplish underlined text
		$buffer = preg_replace('~(<!-- UNDERLINE -->)?<span style="text-decoration: underline;">~i', '[u]', $buffer);
		$buffer = preg_replace('~</span><!-- / UNDERLINE -->~i', '[/u]', $buffer);
		$buffer = preg_replace('~<u>~i', '[u]', $buffer);
		$buffer = preg_replace('~</u>~i', '[/u]', $buffer);

		return $buffer;
	}
}

/**
 * Transform [strike] tags
 */
class K4StrikeThrough_Compiler extends K4BBCodeTag {
	function parse_open() {
		return '<!-- STRIKE --><span style="text-decoration: strikethrough;">';
	}
	function parse_close() {
		return '</span><!-- / STRIKE -->';
	}
	function revert($buffer) {

		// revert all types of ways to accomplish underlined text
		$buffer = preg_replace('~(<!-- STRIKE -->)?<span style="text-decoration: strikethrough;">~i', '[strike]', $buffer);
		$buffer = preg_replace('~</span><!-- / STRIKE -->~i', '[/strike]', $buffer);

		return $buffer;
	}
}

/**
 * Transform [left] tags
 */
class K4LeftAlign_Compiler extends K4BBCodeTag {
	function parse_open() {
		return '<!-- LEFT --><div style="text-align: left;">';
	}
	function parse_close() {
		return '</div><!-- / LEFT -->';
	}
	function revert($buffer) {

		// revert all types of ways to accomplish underlined text
		$buffer = preg_replace('~(<!-- LEFT -->)?<div style="text-align: left;">~i', '[left]', $buffer);
		$buffer = preg_replace('~</div><!-- / LEFT -->~i', '[/left]', $buffer);
		$buffer = preg_replace('~<p align=(")?left(")?>(.+?)</p>~is', '[left]\\3[/left]', $buffer);

		return $buffer;
	}
}

/**
 * Transform [right] tags
 */
class K4RightAlign_Compiler extends K4BBCodeTag {
	function parse_open() {
		return '<!-- RIGHT --><div style="text-align: right;">';
	}
	function parse_close() {
		return '</div><!-- / RIGHT -->';
	}
	function revert($buffer) {

		// revert all types of ways to accomplish underlined text
		$buffer = preg_replace('~(<!-- RIGHT -->)?<div style="text-align: right;">~i', '[right]', $buffer);
		$buffer = preg_replace('~</div><!-- / RIGHT -->~i', '[/right]', $buffer);
		$buffer = preg_replace('~<p align=(")?right(")?>(.+?)</p>~is', '[right]\\3[/right]', $buffer);

		return $buffer;
	}
}

/**
 * Transform [center] tags
 */
class K4CenterAlign_Compiler extends K4BBCodeTag {
	function parse_open() {
		return '<!-- CENTER --><div style="text-align: center;">';
	}
	function parse_close() {
		return '</div><!-- / CENTER -->';
	}
	function revert($buffer) {

		// revert all types of ways to accomplish underlined text
		$buffer = preg_replace('~<!-- CENTER --><div style="text-align: center;">~i', '[center]', $buffer);
		$buffer = preg_replace('~</div><!-- / CENTER -->~i', '[/center]', $buffer);
		$buffer = preg_replace('~<(p|div) align=(")?center(")?>(.+?)</(p|div)>~is', '[center]\\4[/center]', $buffer);

		return $buffer;
	}
}

/**
 * Transform [indent] tags
 */
class K4IndentText_Compiler extends K4BBCodeTag {
	function parse_open() {
		return '<!-- INDENT --><div style="margin-left: 20px;">';
	}
	function parse_close() {
		return '</div><!-- / INDENT -->';
	}
	function revert($buffer) {

		// revert all types of ways to accomplish underlined text
		$buffer = preg_replace('~<!-- INDENT --><div style="margin-left: 20px;">~i', '[indent]', $buffer);
		$buffer = preg_replace('~</div><!-- / INDENT -->~i', '[/indent]', $buffer);
		$buffer = preg_replace('~<blockquote>~i', '[indent]', $buffer);
		$buffer = preg_replace('~</blockquote>~i', '[/indent]', $buffer);

		return $buffer;
	}
}

/**
 * Transform [justify] tags
 */
class K4JustifyAlign_Compiler extends K4BBCodeTag {
	function parse_open() {
		return '<!-- JUSTIFY --><div style="text-align: justify;">';
	}
	function parse_close() {
		return '</div><!-- / JUSTIFY -->';
	}
	function revert($buffer) {

		// revert all types of ways to accomplish underlined text
		$buffer = preg_replace('~<!-- JUSTIFY --><div style="text-align: justify;">~i', '[justify]', $buffer);
		$buffer = preg_replace('~</div><!-- / JUSTIFY -->~i', '[/justify]', $buffer);
		$buffer = preg_replace('~<p align=(")?justify(")?>(.+?)</p>~is', '[justify]\\3[/justify]', $buffer);

		return $buffer;
	}
}

/**
 * Transform [quote] tags
 */
class K4Quote_Compiler extends K4BBCodeTag {
	var $quote = 1;
	function parse_open($matches) {
		
		$params = $this->get_params($matches);
		
		$who	= (isset($params[0]) && $params[0] != '' ? $params[0] : (isset($params[1]) && $params[1] != '' ? $params[1] : ''));
		
		if($who != '' && !empty($params)) {
			$html = '<!-- QUOTE --><div align="center" id="quote'. $this->quote .'"><br /><div class="quotetitle" align="left">QUOTE ( '. $who .' ): </div><div class="quotecontent" align="left">' . "\n";
		} else {
			$html = '<!-- QUOTE --><div align="center" id="quote'. $this->quote .'"><br /><div class="quotetitle" align="left">QUOTE: </div><div class="quotecontent" align="left">' . "\n";
		}

		$this->quote++;

		return $html;
	}
	function parse_buffer($buffer) {
		$buffer = trim($buffer, '<br />');
		return $buffer;
	}
	function parse_close() {
		return  "\n" . '</div><br /></div><!-- / QUOTE -->';
	}
	function revert($buffer) {

		// revert all types of ways to accomplish underlined text
		$buffer = preg_replace('~<!-- QUOTE --><div align="center" id="quote([0-9]+?)"><br /><div class="quotetitle" align="left">QUOTE: </div><div class="quotecontent" align="left">\n~i', '[quote]', $buffer);
		$buffer = preg_replace('~<!-- QUOTE --><div align="center" id="quote([0-9]+?)"><br /><div class="quotetitle" align="left">QUOTE \( (.+?) \): </div><div class="quotecontent" align="left">\n~i', '[quote=\\1]', $buffer);
		$buffer = preg_replace('~\n</div><br /></div><!-- / QUOTE -->~i', '[/quote]', $buffer);

		return $buffer;
	}
}

/**
 * Omit tag, any bbcodes in between will be omitted
 */
class K4Omit_Compiler extends K4BBCodeTag {
	function parse_open() {
		return '<!-- OMIT -->';
	}
	function parse_buffer($buffer) {
		
		$buffer = str_replace(array('[', ']'), array('&#91;', '&#93;'), $buffer);
		
		return $buffer;
	}
	function parse_close() {
		return  '<!-- / OMIT -->';
	}
	function revert($buffer) {

		// revert all types of ways to accomplish underlined text
		$buffer = preg_replace('~<!-- OMIT -->~i', '[omit]', $buffer);
		$buffer = preg_replace('~<!-- / OMIT -->~i', '[/omit]', $buffer);

		return $buffer;
	}
}

/**
 * Transform [php] tags
 */
class K4PHP_Compiler extends K4BBCodeTag {
	var $php_code = 1;
	function parse_open() {
		
		$html = '<!-- PHP --><div align="center" id="php'. $this->php_code .'"><div class="phptitle">&lt;?PHP: </div><div class="phpcontent" align="left">' . "\n";

		$this->php_code++;

		return $html;
	}
	function parse_buffer($buffer) {
		
		$buffer	= str_replace(array("&#60;?", "?&#62;"), array("<?", "?>"), $buffer);
		$buffer	= str_replace(array("&lt;?", "?&gt;"), array("<?", "?>"), $buffer);
		$buffer = preg_replace('~<br( /)?>~i', "\n", $buffer);
		
		$buffer = trim(html_entity_decode($buffer, ENT_QUOTES));
		
		if (strpos($buffer, '<?') === false) {
			$buffer = "<?php\n". $buffer;
		}
		
		if (substr($buffer, strlen($buffer)-2) != '?>') {
			$buffer .= "\n?>";
		}
		
		/**
		 * Highlight the string
		 */
		$buffer	= @highlight_string(stripslashes($buffer), TRUE);
				
		// replace square brackets to not parse interior bbcode
		$buffer = str_replace(array('[', ']'), array('&#91;', '&#93;'), $buffer);
		
		// put two tags around the highlighted code
		$buffer = '<!-- PHP_HIGHLIGHT -->'. $buffer .'<!-- / PHP_HIGHLIGHT -->';
		
		return $buffer;
	}
	function parse_close() {
		return  "\n" . '</div><br /></div><!-- / PHP -->';
	}
	function revert($buffer) {

		// revert all types of ways to accomplish underlined text
		$buffer = preg_replace('~<!-- PHP --><div align="center" id="php([0-9]+?)"><div class="phptitle">(&lt;|<)\?PHP: </div><div class="phpcontent" align="left">\n~i', '[php]', $buffer);
		$buffer = preg_replace('~\n</div><br /></div><!-- / PHP -->~i', '[/php]', $buffer);
		$buffer = str_replace('&nbsp;&nbsp;&nbsp;&nbsp;', "\t", $buffer);
		$buffer = preg_replace('~<br( /)?>~i', "\n", $buffer);
		
		$buffer = preg_replace_callback('~<!-- PHP_HIGHLIGHT -->(.*?)<!-- / PHP_HIGHLIGHT -->~isU', array(&$this, 'clear_tags'), $buffer);

		return $buffer;
	}
	function clear_tags($matches) {
		$buffer = trim(strip_tags($matches[1]), '\r\n\t');
		
		/* Just making sure */
		$buffer = preg_replace('~<span(.+)>(.+)</span>~isU', '\\2', $buffer);

		$buffer = preg_replace('~<font(.+)>(.+)</font>~isU', '\\2', $buffer);

		$buffer = preg_replace('~<code>(.+)</code>~isU', '\\1', $buffer);

		$buffer	= preg_replace('~(<br />|<br>)~i', "\n", $buffer);

		$buffer = preg_replace('~&nbsp;&nbsp;&nbsp;&nbsp;~i', "\t", $buffer);

		return $buffer;
	}
}

/**
 * Transform [code] tags
 */
class K4Code_Compiler extends K4BBCodeTag {
	var $code = 1;
	function parse_open() {
		$html = '<!-- CODE --><div align="center" id="code'. $this->code .'"><div class="codetitle">CODE: </div><div class="codecontent" align="left">' . "\n";
		$this->code++;

		return $html;
	}
	function parse_buffer($buffer) {
		$buffer = str_replace(array('[', ']'), array('&#91;', '&#93;'), $buffer);
		$buffer = str_replace("\t", '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $buffer);
		$buffer = str_replace(array("\r", "\n", "\r\n"), array('', '<br />','<br />',), $buffer);
		$buffer = trim($buffer, '<br />');
		return $buffer;
	}
	function parse_close() {
		return  "\n" . '</div><br /></div><!-- / CODE -->';
	}
	function revert($buffer) {

		// revert all types of ways to accomplish underlined text
		$buffer = preg_replace('~<!-- CODE --><div align="center" id="code([0-9]+?)"><div class="codetitle">CODE: </div><div class="codecontent" align="left">\n~i', '[code]', $buffer);
		$buffer = preg_replace('~\n</div><br /></div><!-- / CODE -->~i', '[/code]', $buffer);
		
		$buffer = str_replace(array('&#91;', '&#93;'), array('[', ']'), $buffer);
		$buffer = str_replace('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', "\t", $buffer);

		return $buffer;
	}
}

/**
 * Transform [font] tags
 */
class K4Font_Compiler extends K4BBCodeTag {
	var $font_family = '';
	function parse_open($matches) {
		
		$params = $this->get_params($matches);
		
		$this->font_family = ($params[0] != '' ? $params[0] : (isset($params[1]) ? $params[1] : ''));
		
		if($this->font_family != '') {
			return '<!-- FONT --><span style="font-family: '. $this->font_family .';">';
		}
	}
	function parse_close() {
		if($this->font_family != '') {
			return '</span><!-- / FONT -->';
		}
	}
	function revert($buffer) {

		// revert all types of ways to accomplish underlined text
		$buffer = preg_replace('~<!-- FONT --><span style="font-family: (.+?);">~i', '[font=\\1]', $buffer);
		$buffer = preg_replace('~</span><!-- / FONT -->~i', '[/font]', $buffer);
		
		return $buffer;
	}
}

/**
 * Transform [size] tags
 */
class K4FontSize_Compiler extends K4BBCodeTag {
	var $font_size = 0;
	function parse_open($matches) {
		
		$params = $this->get_params($matches);
		
		$this->font_size = isset($params[0]) && intval($params[0]) != '' ? $params[0] : (isset($params[1]) ? $params[1] : 0);
		$this->font_size = $this->font_size < 7 ? 7 : ($this->font_size > 30 ? 30 : $this->font_size);
		
		return '<!-- SIZE --><span style="font-size: '. $this->font_size .'pt;">';
	}
	function parse_close() {
		return '</span><!-- / SIZE -->';
	}
	function revert($buffer) {

		// revert all types of ways to accomplish underlined text
		$buffer = preg_replace('~<!-- SIZE --><span style="font-size: ([0-9]+?)pt;">~i', '[size=\\1]', $buffer);
		$buffer = preg_replace('~</span><!-- / SIZE -->~i', '[/size]', $buffer);
		
		return $buffer;
	}
}

/**
 * Transform [font] tags
 */
class K4FontColor_Compiler extends K4BBCodeTag {
	var $font_color = '';
	function parse_open($matches) {
		
		$ret = '';
		$params = $this->get_params($matches);
		
		$this->font_color = (isset($params[0]) && $params[0] != '' ? $params[0] : (isset($params[1]) && $params[1] != '' ? $params[1] : ''));
		
		if($this->font_color != '')
			$ret = '<!-- COLOR --><span style="color: '. (ctype_alpha($this->font_color) && preg_match("~([a-f0-9]+)~i", $this->font_color) && strlen($this->font_color) <= 6 ? '#'. strtoupper($this->font_color) : $this->font_color) .';">';
		
		return $ret;
	}
	function parse_close() {
		if($this->font_color != '') {
			return '</span><!-- / COLOR -->';
		}
	}
	function revert($buffer) {

		// revert all types of ways to accomplish underlined text
		$buffer = preg_replace('~<!-- COLOR --><span style="color: #([a-f0-9]+?);">~i', '[color=\\1]', $buffer);
		$buffer = preg_replace('~</span><!-- / COLOR -->~i', '[/color]', $buffer);
		
		return $buffer;
	}
}

/**
 * Transform [anchor] tags
 */
class K4Anchor_Compiler extends K4BBCodeTag {
	var $anchor				= '';

	function parse_open($matches) {

		$params = $this->get_params($matches);
		
		$this->anchor = (isset($params[0]) && $params[0] != '' ? $params[0] : (isset($params[1]) ? $params[1] : ''));
		
		return $this->anchor != '' ? '<!-- ANCHOR --><a name="'. $this->anchor .'" id="'. $this->anchor .'">' : '';
	}
	function parse_close() {
		return $this->anchor != '' ? '</a><!-- / ANCHOR -->' : '';
	}
	function revert($buffer) {

		// revert all types of ways to accomplish underlined text
		$buffer = preg_replace('~<!-- ANCHOR --><a name="(.+?)" id="(.+?)">~i', '[anchor=\\1]', $buffer);
		$buffer = str_replace('</a><!-- / ANCHOR -->', '[/anchor]', $buffer);
				
		return $buffer;
	}
}

/**
 * Transform [url] tags
 */
class K4Url_Compiler extends K4BBCodeTag {

	var $url				= '';
	var $dynamic_url		= FALSE;
	var $auto_url			= TRUE;

	function K4Url_Compiler($dynamic_url, $auto_url) {
		$this->dynamic_url	= (bool)$dynamic_url;
		$this->auto_url		= (bool)$auto_url;
	}
	function parse_open($matches) {

		$params = $this->get_params($matches);
		
		/**
		 * If there is a match, that means that that is a [url=] tag, so set the title
		 * to a member variable
		 */
		$this->url = (isset($params[0]) && $params[0] != '' ? $params[0] : (isset($params[1]) ? $params[1] : ''));
		return '';
	}
	function parse_buffer($buffer) {
		
		// this will end up being the HTML we return
		$html = '';
		
		if($buffer != '') {
			
			$url = new FAUrl(($this->url != '' ? $this->url : $buffer));
			
			/* If we can't use dynamic urls, remove the query string */
			if(!$this->dynamic_url) {
				$url->args = array();
			}
			
			$url = $url->__toString();

			/* Make the html for the url tag */
			$html .= '<!-- URL --><a class="bbcode_url" href="'. $url .'" title="'. ($this->url != '' ? $buffer : '') .'" target="_blank">';
			$html .= ($this->url != $buffer ? $buffer : $url);
			$html .= '</a><!-- / URL -->';
		}
		
		/* Return the link or nothing */
		return $html;
	}
	function parse_close() {
		return '';
	}
	function revert($buffer) {

		// revert all types of ways to accomplish underlined text
		$buffer = preg_replace('~<!-- URL --><a class="bbcode_url" href="(.+?)" title="" target="_blank">(.+?)</a><!-- / URL -->~i', '[url]\\1[/url]', $buffer);
		$buffer = preg_replace('~<!-- URL --><a class="bbcode_url" href="(.+?)" title="(.+?)" target="_blank">(.+?)</a><!-- / URL -->~i', '[url=\\1]\\2[/url]', $buffer);
				
		return $buffer;
	}
}

/**
 * Transform [img] tags
 */
class K4Image_Compiler extends K4BBCodeTag {

	var $dynamic_url	= FALSE;

	function K4Image_Compiler($dynamic_url) {
		$this->dynamic_url = (bool)$dynamic_url;
	}
	function parse_open($matches) {
		
		$params = $this->get_params($matches);
		$this->url = (isset($params[0]) && $params[0] != '' ? $params[0] : (isset($params[1]) ? $params[1] : ''));

		return '';
	}
	function parse_buffer($buffer) {
		$html = '';
		
		if($buffer != '') {
			
			$url = new FAUrl(($this->url != '' ? $this->url : $buffer));
			
			/* If we can't use dynamic urls, remove the query string */
			if(!$this->dynamic_url) {
				$url->args = array();
			}

			/* Make the html for the url tag */
			$html .= '<!-- IMG --><div class="bbcode_img"><img src="'. $url->__toString() .'" alt="" border="0" /></div><!-- / IMG -->';
		} else {
			$html = '';
		}
		
		/* Return the link or nothing */
		return $html;
	}
	function parse_close() {
		return '';
	}
	function revert($buffer) {

		// revert all types of ways to accomplish underlined text
		$buffert	= preg_replace('~<!-- IMG --><div class="bbcode_img"><img src="(.+?)" alt="" border="0" /></div><!-- / IMG -->~i', '[img]\\1[/img]', $buffer);
		$buffer		= preg_replace('~<img src="(.+?)">~i', '[img]\\1[/img]', $buffer);
				
		return $buffer;
	}
}

class K4List_Compiler extends K4BBCodeTag {
	
	function parse_open($matches) {
		
		$params = $this->get_params($matches);
		
		$type = (isset($params[0]) && $params[0] != '' ? $params[0] : (isset($params[1]) ? $params[1] : ''));
		$type = $type != '' ? (ctype_digit($type) ? 1 : ($type == 'a' ? 'a' : 'A')) : '';

		return '<!-- LIST --><ul'. ($type != '' ? ' type="'. $type .'"' : '') .'>';
	}
	function parse_buffer($buffer) {
		
		$html = '';
		
		// remove any type of new lines from the buffer
		$buffer = preg_replace('~<br( /)?>~i', '', $buffer);
		$buffer = preg_replace("~(\r\n|\r|\n)~i", '', $buffer);
		
		$items = explode('[*]', $buffer);
		
		foreach($items as $item) {
			if($item != '') {
				$html .= '<li>'. trim($item) .'</li>';
			}
		}
				
		/* Return the link or nothing */
		return $html;
	}
	function parse_close() {
		return '</ul><!-- / LIST -->';
	}
	function revert($buffer) {

		// revert all types of ways to accomplish underlined text
		$buffer		= preg_replace_callback('~<!-- LIST --><ul( type="(.+?)")?>~i', array($this, 'get_list_tag'), $buffer);
		$buffer		= preg_replace('~</ul><!-- / LIST -->~i', '[/list]', $buffer);
		$buffer		= str_replace(array('<li>', '<li>'), array('[*]', "\n"), $buffer);
				
		return $buffer;
	}
	function get_list_tag($matches) {
		$tag = '[list]';

		if(is_array($matches) && isset($matches[1]) && isset($matches[2])) {
			$tag = ($matches[1] != '' ? ($matches[2] != '' ? '[list='. $matches[2] .']' : '[list]') : '[list]');
		}
		return $tag;
	}
}

class K4Poll_Compiler extends K4BBCodeTag {
	
	var $question;
	var $can_poll			= FALSE;
	var $max_poll_answers	= 0;
	var $max_polls			= 0;
	var $num_polls			= 0;
	var $dba;
	
	function K4Poll_Compiler(&$dba, $can_poll, $max_polls, $max_poll_answers) {
		$this->can_poll			= (bool)$can_poll;
		$this->max_poll_answers = (int)$max_poll_answers;
		$this->max_polls		= (int)$max_polls;
		$this->dba				= $dba;
	}

	function parse_open($matches) {
		
		$params = $this->get_params($matches);

		$this->question = (isset($params[0]) && $params[0] != '' ? $params[0] : (isset($params[1]) ? $params[1] : ''));
		
		return '';
	}
	function parse_buffer($buffer) {
		
		$ret = '';

		if($this->question != '' && $this->can_poll) {
			
			// remove any type of new lines from the buffer
			$buffer = preg_replace('~<br( /)?>~i', '', $buffer);
			$buffer = preg_replace("~(\r\n|\r|\n)~i", '', $buffer);
			
			// get the poll answers
			$items = explode('[*]', $buffer);
			
			if(is_array($items) && !empty($items)) {
				
				if($this->num_polls <= $this->max_polls) {

					// increment the number of polls registered
					$this->num_polls++;
					
					// get the question
					$question				= $this->dba->quote(k4_htmlentities(html_entity_decode($this->question, ENT_QUOTES), ENT_QUOTES));
					$insert_question		= $this->dba->executeUpdate("INSERT INTO ". K4POLLQUESTIONS ." (question, created, user_id, user_name) VALUES ('". $question ."', ". time() .", ". intval($_SESSION['user']->get('id')) .", '". $this->dba->quote($_SESSION['user']->get('name')) ."')");
					$question_id			= $this->dba->getInsertId(K4POLLQUESTIONS, 'id');
					
					// loop through the poll questions and add them to the database
					$i = 1;
					foreach($items as $answer) {
						if($answer != '') {
							$this->dba->executeUpdate("INSERT INTO ". K4POLLANSWERS ." (question_id, answer) VALUES (". intval($question_id) .", '". $this->dba->quote(k4_htmlentities(html_entity_decode($answer, ENT_QUOTES), ENT_QUOTES), ENT_QUOTES) ."')");
							
							if($i >= $this->max_poll_answers) {
								break;
							}

							$i++;
						}
					}

					$ret = '[poll='. $question_id .']';
				}
			}
		}
				
		/* Return the link or nothing */
		return $ret;
	}
	function parse_close() {
		return '';
	}
}

/********************************************
 *											*
 *	  Down below, it get's pretty ugly.		*
 *											*
 ********************************************/

/**
 * Deal with Emoticons
 */
class BBEmoticons {
	var $instance;

	function BBEmoticons(&$instance) {

		$this->instance		= $instance;
		$this->emoticons	= $this->instance->dba->executeQuery("SELECT * FROM ". K4EMOTICONS);
	}

	function to_html() {
		
		while($this->emoticons->next()) {
			
			$smilie						= $this->emoticons->current();
			
			$file						= 'tmp/upload/emoticons/'. $smilie['image'];

			if(file_exists(BB_BASE_DIR .'/'. $file)) {
				
				$proportions			= getimagesize($file);

				$this->instance->text	= preg_replace('~(\s|\b|\r|\n)'. preg_quote($smilie['typed']) .'([a-zA-Z0-9 \.,\-\_\r\n\t])~i', '\\1<!-- EMOTICON '. $smilie['typed'] .' --><img src="'. $file .'" alt="'. $smilie['description'] .'" border="0" class="emoticon_image" /><!-- / EMOTICON -->\\2', $this->instance->text);
			}
		}
				
		return $this->instance->text;
	}
	function to_bbcode() {

		while($this->emoticons->next()) {
			
			$smilie						= $this->emoticons->current();
			$this->instance->text		= preg_replace('~<!-- EMOTICON '. preg_quote($smilie['typed']) .' --><img (.+?) /><!-- / EMOTICON -->~is', $smilie['typed'], $this->instance->text);
			$this->instance->text		= preg_replace('~<img src="(.+?)tmp/upload/emoticons/'. preg_quote($smilie['image']).'(.+?)>~is', $smilie['typed'], $this->instance->text);
		}
			
		return $this->instance->text;
	}
}

/**
 * We use this because it is so widely used already
 */
class BBCodex extends FAObject {
	
	var $settings, $dba, $user, $text, $forum_id, $html, $bbcode, $emoticons, $auto_urls;

	/**
	 * Constructor, set some variables
	 */
	function BBCodex(&$dba, $user, $text, $forum_id, $html, $bbcode, $emoticons, $auto_urls, $omit = array()) {
		$this->__construct($dba, $user, $text, $forum_id, $html, $bbcode, $emoticons, $auto_urls, $omit);
	}
	function __construct(&$dba, $user, $text, $forum_id, $html, $bbcode, $emoticons, $auto_urls, $omit = array()) {
		
		global $_SETTINGS;
		
		if(is_a($user, 'FAUser')) {
			$user			= $user->getInfoArray();
		} 
		if(!is_array($user)) {
			trigger_error('Invalid user array passed to BBCodex::__construct.', E_USER_ERROR);
		}
		
		$this->settings		= $_SETTINGS;

		$this->dba			= $dba;

		$this->user			= $user;
		$this->text			= ' '. $text .' ';
		$this->forum_id		= intval($forum_id) > 0 ? intval($forum_id) : FALSE;
		
		$this->html			= (bool)$html;
		$this->bbcode		= (bool)$bbcode;
		$this->emoticons	= (bool)$emoticons;
		$this->auto_urls	= (bool)$auto_urls;

		$this->omit			= $omit;
		
		/* Initialize the parser */
		$this->bbcode_parser = new K4BBCodeParser();
	}

	/**
	 * Initialize all of the buffers with the bbcodex
	 */
	function init() {
		
		// add all of the tag omits
		if(is_array($this->omit))
			$this->bbcode_parser->set_omit_tags($this->omit);

		// add all of the tag compilers, order is irrelevent
		$this->bbcode_parser->set_compiler('b',			new K4Bold_Compiler);
		$this->bbcode_parser->set_compiler('i',			new K4Italic_Compiler);
		$this->bbcode_parser->set_compiler('u',			new K4Underline_Compiler);
		$this->bbcode_parser->set_compiler('strike',	new K4StrikeThrough_Compiler);
		$this->bbcode_parser->set_compiler('left',		new K4LeftAlign_Compiler);
		$this->bbcode_parser->set_compiler('right',		new K4RightAlign_Compiler);
		$this->bbcode_parser->set_compiler('center',	new K4CenterAlign_Compiler);
		$this->bbcode_parser->set_compiler('justify',	new K4JustifyAlign_Compiler);
		$this->bbcode_parser->set_compiler('indent',	new K4IndentText_Compiler);
		$this->bbcode_parser->set_compiler('quote',		new K4Quote_Compiler);
		$this->bbcode_parser->set_compiler('php',		new K4PHP_Compiler);
		$this->bbcode_parser->set_compiler('code',		new K4Code_Compiler);
		$this->bbcode_parser->set_compiler('font',		new K4Font_Compiler);
		$this->bbcode_parser->set_compiler('size',		new K4FontSize_Compiler);
		$this->bbcode_parser->set_compiler('color',		new K4FontColor_Compiler);
		$this->bbcode_parser->set_compiler('list',		new K4List_Compiler);
		$this->bbcode_parser->set_compiler('anchor',	new K4Anchor_Compiler);

		// Set two of the compilers that need input from here
		$this->bbcode_parser->set_compiler('url',		new K4Url_Compiler((bool)intval($this->settings['allowdynurl']), (bool)intval($this->auto_urls))); // true/false to allow dynamic urls
		$this->bbcode_parser->set_compiler('img',		new K4Image_Compiler((bool)intval($this->settings['allowdynimg']))); // true/false to allow dynamic urls
		
		$this->emoticon_parser = new BBEmoticons($this);

	}

	/**
	 * transform bbcode into html
	 */
	function parse() {
		
		$this->init();
		
		//encode html entities
		$this->text = k4_htmlentities($this->text, ENT_QUOTES);

		$this->text = preg_replace("~(\r\n|\r|\n)~", '<br />', $this->text);
		$this->text = str_replace(array('"', "'"), array('&quot;', '&#039;'), $this->text);
		$this->text = str_replace('[hr]', '<hr />', $this->text);
		
		if($this->emoticons) {
			$this->emoticon_parser->to_html();
		}

		// now use the parser
		$this->text = $this->bbcode_parser->parse($this->text);
		
		return trim($this->text);
	}

	/**
	 * transform html into bbcode
	 */
	function revert() {
		
		$this->init();

		// revert emoticons
		$this->emoticon_parser->to_bbcode();

		// revert the code with the bbcode parser
		$this->text = $this->bbcode_parser->revert($this->text);
	
		if(TRUE) {
		
			// clean up some firefox generated html from the wysiwyg
			preg_match_all('~(<!\-\-([A-Z]+?)\-\->)?<(span|div) style="(.+?)">(.+?)</(span|div)>(<!\-\- / ([A-Z]+?)\-\->)?~is', $this->text, $matches, PREG_SET_ORDER);

			if(is_array($matches) && !empty($matches)) { // the false disables this
				foreach($matches as $match) {
					
					$starting		= array();
					$closing		= array();
					$bbcode_start	= '';
					$bbcode_end		= '';

					//$args = explode(";", $match[4]);

					if(strpos($match[4], 'font-weight: bold') !== FALSE) {
						$starting[] = '[b]';
						$closing[] = '[/b]';
					} 
					if(strpos($match[4], 'font-style: italic') !== FALSE) {
						$starting[] = '[i]';
						$closing[] = '[/i]';
					} 
					if(strpos($match[4], 'text-decoration: underline') !== FALSE) {
						$starting[] = '[u]';
						$closing[] = '[/u]';
					} 
					if(strpos($match[4], 'text-align: center') !== FALSE) {
						$starting[] = '[center]';
						$closing[] = '[/center]';
					} 
					if(strpos($match[4], 'text-align: left') !== FALSE) {
						$starting[] = '[left]';
						$closing[] = '[/left]';
					} 
					if(strpos($match[4], 'text-align: right') !== FALSE) {
						$starting[] = '[right]';
						$closing[] = '[/right]';
					} 
					if(strpos($match[4], 'text-align: justify') !== FALSE) {
						$starting[] = '[justify]';
						$closing[] = '[/justify]';
					}
					// TODO: this limits someone to one level of indentation
					if(strpos($match[4], 'margin-left') !== FALSE) {
						$starting[] = '[indent]';
						$closing[] = '[/indent]';
					}
					
					if(!empty($starting) && !empty($closing)) {
						$bbcode_start = implode('', $starting);
						$bbcode_end = implode('', array_reverse($closing));
					}

					$this->text = str_replace($match[0], $bbcode_start . $match[5] . $bbcode_end, $this->text);
				}
			}
		}
		

		// now deal with some html stuff
		$this->text = preg_replace('~<br( /)?>~i', "\n", $this->text);
		$this->text = str_replace(array('&quot;', '&#039;'), array('"', "'"), $this->text);
		$this->text = preg_replace('~<hr( /)?>~i', '[hr]', $this->text);
		
		// decode html entities
		$this->text = html_entity_decode($this->text, ENT_QUOTES);

		return trim($this->text);
	}
}

/** 
 * Do polls
 */
class K4BBPolls extends FAObject {
	
	var $text;
	var $original;
	var $forum;

	var $original_polls		= array();
	var $new_polls			= array();

	var $post_created;

	function __construct($text, $original_text, $forum, $post_id) {
		
		$this->text			= $text;
		$this->original		= $original_text;
		$this->post_id		= $post_id;
		$this->forum		= $forum;

		/* Initialize the parser */
		$this->bbcode_parser = new K4BBCodeParser();
		$this->bbcode_parser->clear_omit_tags();
		$this->bbcode_parser->set_omit_tags(array('*', 'poll', 'hr'));
	}

	/* Parse the text and make a poll out of it */
	function parse(&$request, &$is_poll) {
		
		// set whether we can poll or not
		$can_poll	= ($this->forum['forum_id'] > 0 &$request['user']->get('perms') >= get_map( 'bbcode', 'can_add', array('forum_id'=>$this->forum['forum_id'])));

		// set the poll compiler
		$this->bbcode_parser->set_compiler('question', new K4Poll_Compiler($request['dba'], $can_poll, $request['template']->getVar('maxpollquestions'), $request['template']->getVar('maxpolloptions')));

		$this->text = $this->bbcode_parser->parse($this->text);
		
		$this->second_pass($request, $is_poll);

		return $this->text;
	}
	
	/*
	 * Go back through our body text and make sure that there are a limited
	 * number of polls in this post
	 */
	function second_pass(&$request, &$is_poll) {		

		// go through our text and moderate the number of polls there can be per post
		preg_match_all('~\[poll=([0-9]+?)\]~i', $this->text, $poll_matches, PREG_SET_ORDER);

		if(count($poll_matches) > 0) {
			
			$is_poll	= 1;

			$i = 0;
			foreach($poll_matches as $poll) {
				
				if($i > $request['template']->getVar('maxpollquestions')) {
					
					$this->text = str_replace($poll[0], '', $this->text);
					
					// delete this poll
					$this->delete_poll($request, $poll[1]);

				} else {
					
					// add this poll to the array of new polls in this post
					$this->new_polls[]	= $poll[1];

				}
				
				$i++;
			}
		}

		unset($poll_matches);
				
		$differences		= array_diff($this->original_polls, $this->new_polls);
		
		foreach($differences as $diff) {
			if(!in_array($diff, $this->new_polls)) {
				$this->delete_poll($request, $diff);
			}
		}

	}

	/**
	 * Delete a poll
	 */
	function delete_poll(&$request, $poll_id) {

		// check if this poll is being used somewhere else
		$topic_matches		= $request['dba']->executeQuery("SELECT * FROM ". K4TOPICS ." WHERE lower(body_text) LIKE lower('%[poll=". $poll_id ."]%') AND topic_id <> ". intval($this->post_id));
		$reply_matches		= $request['dba']->executeQuery("SELECT * FROM ". K4REPLIES ." WHERE lower(body_text) LIKE lower('%[poll=". $poll_id ."]%') AND reply_id <> ". intval($this->post_id));
		
		// we can delete it
		if( ($topic_matches->numRows() == 0) && ($reply_matches->numRows() == 0) ) {
			
			$request['dba']->executeUpdate("DELETE FROM ". K4POLLQUESTIONS ." WHERE id = ". intval($poll_id));
			$request['dba']->executeUpdate("DELETE FROM ". K4POLLANSWERS ." WHERE question_id = ". intval($poll_id));
			$request['dba']->executeUpdate("DELETE FROM ". K4POLLVOTES ." WHERE question_id = ". intval($poll_id));
		}
	}

	/* Make the text back into a poll */
	function revert(&$request) {
		return $this->text;
	}
}

?>