<?php

/**
* k4 Bulletin Board, feed.php
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
* @author Geoffrey Goodman
* @author James Logsdon
* @version $Id: feed.php,v 1.1 2005/05/01 23:24:55 necrotic Exp $
* @package k42
*/

/**
 * This class does the basic XML Formating. Error checking is done, for the most
 * part by this class.
 *
 * @author James Logsdon
 * @package XML
 * @version 1
 */
class XML
{

	/**
	 * Tracks what tags are open
	 */
	var $_openTags = array();

	/**
	 * Current tag tree
	 */
	var $_currentTag = '';

	/**
	 * Current tag name
	 */
	var $_currentTagName = '';

	/**
	 * The actual XML data
	 */
	var $_xml = '';

	/**
	 * Indentation level
	 */
	var $_indent = 0;

	/**
	 * Debug Mode
	 */
	var $debug = false;

	/**
	 * Debug Messages
	 */
	var $_debug;

	/**
	 * Debug Indentation Level
	 */
	var $_debugIndent = 0;

	/**
	 * Start the XML document with a version attribute. If debugging is enabled,
	 * don't send the Content-type header
	 *
	 * @param string $version  The version of the XML document [1.0]
	 * @return void
	 */
	function XML ( $version = "1.0" )
	{
		if ( !$this->debug )
		{
			header("Content-type: text/xml");
		}
		$this->addToXml ( '<?xml version="' . $version . '"?>' );
	}

	/**
	 * Open an XML tag
	 *
	 * $attributes has to following format:
	 *
	 * array ( attributeName => attributeValue );
	 *
	 * @param string $tag  The tag to open
	 * @param array|null $attributes  An array of attributes to add to $tag
	 * @param bool $single  If true, then the tag has no closing tag (IE. <tag />)
	 * @param bool $newLine  Passed to XML::addToXml
	 * @param bool $indent  Passed to XML::addToXml
	 * @see XML::addToXml()
	 * @return void
	 */
	function openTag ( $tag, $attributes = null, $single = false, $newLine = true, $indent = true )
	{
		$this->Debug ( 'Opening tag ' . $tag );
		$this->_debugIndent++;
		$attr = '';
		if ( is_array ( $attributes ) )
		{
			foreach ( $attributes as $key=>$val )
			{
				$attr .= ' ' . $key . '="' . $val . '"';
			}
			$this->Debug ( 'Created Attribute String: ' . $attr );
		}

		// This is a tag that can be closed later
		if ( !$single )
		{
			$this->setCurrentTag ( $tag );
			$this->addToXml ( '<' . $tag . $attr . '>', $newLine, $indent );
			$this->_indent++;
			$this->Debug ( 'Opened non-single tag' );
		}
		// Close it now
		else
		{
			$this->addToXml ( '<' . $tag . $attr . ' />', $newLine, $indent );
			$this->Debug ( 'Opened single tag' );
		}
	}

	/**
	 * Add $val to the current open tag
	 *
	 * @param string $val  The value to add
	 * @param bool $newLine  Passed to XML::addToXml
	 * @param bool $indent  Passed to XML::addToXml
	 * @return void
	 */
	function addValue ( $val, $newLine = false, $indent = false )
	{
		$this->addToXml ( $val, $newLine, $indent );
		$this->Debug ( 'Adding value to ' . $this->_currentTagName );
	}

	/**
	 * Close the current open tag
	 *
	 * @param bool $indent  Passed to XML::addToXml
	 * @see XML::removeCurrentTag()
	 * @return void
	 */
	function closeTag ( $indent = true )
	{
		$this->_indent--;
		$this->addToXml ( '</' . $this->_currentTagName . '>', true, $indent );
		$this->_debugIndent--;
		$this->Debug ( 'Closing current tag ' . $this->_currentTagName );
		$this->_debugIndent++;
		$this->removeCurrentTag ( );
		$this->_debugIndent--;
	}

	/**
	 * Set the current tag to $tag
	 *
	 * @param string $tag  The tag name
	 * @see XML::_currentTag
	 * @return void
	 */
	function setCurrentTag ( $tag )
	{
		$this->_currentTag .= '[\'' . $tag . '\']';
		eval ( "\$this->_openTags{$this->_currentTag} = array();" );
		
		// Uncomment to show the openTags array in Debug Mode
		//$this->Debug ( '<blockquote>' . print_r ( $this->_openTags, true ) . '</blockquote>' );
		$this->Debug ( 'Adding ' . $tag . ' to _currentTagName' );
		$this->setCurrentTagName ( $tag );
	}

	/**
	 * Set XML::_currentTagName
	 *
	 * @param string $tag  The tag name
	 * @see XML::setCurrentTag(), XML::_currentTagName
	 * @return void
	 */
	function setCurrentTagName ( $tag )
	{
		$this->_currentTagName = $tag;
		$this->Debug ( '_currentTagName set to ' . $tag );
	}

	/**
	 * Removes the current tag from usage
	 *
	 * @see XML::closeTag(), XML::_currentTag, XML::_currentTagName
	 * @return void
	 */
	function removeCurrentTag ( )
	{
		$this->Debug ( 'Removing tags from _currentTag' );
		$this->_debugIndent++;

		eval ( "unset ( \$this->_openTags{$this->_currentTag} );" );
		$last = strrpos ( $this->_currentTag, '[' );
		$this->_currentTag = substr ( $this->_currentTag, 0, $last );
		$this->Debug ( 'Removed last tag from string' );

		$tags = explode ( "']['", $this->_currentTag );
		$tag  = $tags[count ( $tags ) - 1];
		$tag  = str_replace ( '\']', '', $tag );
		$tag  = str_replace ( '[\'', '', $tag );
		$this->_currentTagName = $tag;

		$this->_debugIndent--;
	}

	/**
	 * Add a string to the document
	 *
	 * @param string $str  The string to add
	 * @param bool $newLine  If true, append \n to the string
	 * @param bool $indent  If true, indent the string
	 */
	function addToXml ( $str, $newLine = true, $indent = true )
	{
		if ( $indent )
		{
			$this->_xml .= $this->getIndent();
		}
		$this->_xml .= $str;
		if ( $newLine )
		{
			$this->_xml .= "\n";
		}
	}

	/**
	 * Get the string of tabs to indent with
	 *
	 * @see XML::_indent
	 * @return string  The string of tabs
	 */
	function getIndent ( )
	{
		if ( $this->_indent == 0 )
		{
			return '';
		}
		$toReturn = "";
		for ( $i = 0; $i < $this->_indent; $i++ )
		{
			$toReturn .= "\t";
		}
		return $toReturn;
	}

	/**
	 * Add a message for debugging
	 *
	 * @param string $msg  The message
	 */
	function Debug ( $msg )
	{
		$indent = "";
		if ( $this->_debugIndent > 0 )
		{
			for ( $i = 0; $i < $this->_debugIndent; $i++ )
			{
				$indent .= "\t";
			}
		}

		$this->_debug .= $indent . $msg . "\n";
	}

	/**
	 * If debug mode is enabled, output the debug information.
	 *
	 * Also checks for open tags. If all is clean, return the document.
	 *
	 * @see XML::debug,XML::_currentTag
	 * @return string  The XML Document
	 */
	function Go ( )
	{
		if ( $this->debug === true )
		{
			echo '<pre>' . $this->_debug . '</pre>';
			unset ( $this->_debug );
			echo '<pre>' . print_r ( $this, true ) . '</pre>';
		}

		if ( $this->_currentTag != '' )
		{
			$tags = str_replace ( '[\'', '', $this->_currentTag );
			$tags = str_replace ( '\']', ', ', $tags );
			$tags = substr ( $tags, 0, strlen ( $tags ) - 2 );
			die ( 'You have un-closed tags! Tag Tree: ' . $tags );
		}

		return $this->_xml;
	}
}

/**
 * This class handles RSS output
 *
 * @author James Logsdon
 * @package XML
 * @version 1
 */
class RSS extends XML
{

	/**
	 * When you call the class, an array is expected with information on the
	 * Channel
	 *
	 * The array must have three elements: title, link, and description. These
	 * are required for a valid RSS file.
	 *
	 * The only task this function (the constructor) performs is initializing
	 * the document.
	 *
	 * @param array $info  Consists of three elements: title, link, and description
	 * @return void
	 */
	function RSS ( $info = array () )
	{
		if ( !isset ( $info['title'] ) OR
			!isset ( $info['link'] ) OR
			!isset ( $info['description'] ) )
		{
			die ( 'You must provide a Title, Link, and Description' );
		}

		// Start the feed
		parent::XML();
		$this->openTag ( 'rss', array ( 'version' => '2.0' ) );
		$this->openTag ( 'channel' );

		// Add the tags
		foreach ( $info as $tag=>$val )
		{
			$this->openTag ( $tag, null, false, false );
			$this->addValue ( $val );
			$this->closeTag ( false );
		}
	}

	/**
	 * Add an item to the feed
	 *
	 * You must provide either a title or a description.
	 *
	 * If an array value is an array, key 1 is made into an attribute string
	 * while key 0 is the value itself. The attributes must be in an array
	 * formatted like this:
	 *
	 * array ( attributeName => attributeValue );
	 *
	 * If giving a GUID and you wish for it to be a permaLink, you can pass
	 * a value of true for the second key instead of an array. Example:
	 *
	 * array ( 'guid' => array ( 'UniqueID', true ) );
	 *
	 * @param array $info  The item information
	 * @return void
	 */
	function addItem ( $info = array() )
	{
		if ( !isset ( $info['title'] ) OR
			!isset ( $info['description'] ) )
		{
			die ( 'You must provide either a description or a title' );
		}

		$this->openTag ( 'item' );
		foreach ( $info as $tag=>$val )
		{
			if ( is_array ( $val ) AND $tag == 'guid' AND $val[1] === true )
			{
				$this->openTag ( $tag, array('isPermaLink'=>'true'), false, false );
				$this->addValue ( $val[0] );
			}
			else if ( is_array ( $val ) )
			{
				$this->openTag ( $tag, $val[1], false, false );
				$this->addValue ( $val[0] );
			}
			else
			{
				$this->openTag ( $tag, null, false, false );
				$this->addValue ( $val );
			}
			$this->closeTag ( false );
		}
		$this->closeTag ( );
	}

	/**
	 * Close the channel and rss tags and return the feed
	 *
	 * @return string  The feed contents
	 */
	function Go()
	{
		// If the document is not malformed, this will close the channel and rss tags
		$this->closeTag ( );
		$this->closeTag ( );
		return parent::Go();
	}

}

?>