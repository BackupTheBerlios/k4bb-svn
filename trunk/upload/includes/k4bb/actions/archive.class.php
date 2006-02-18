<?php
/**
* k4 Bulletin Board, common.php
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
* @package k42
*/

if(!defined('IN_K4')) {
	return;
}

class k4Archiver {
	
	var $header, $footer, $xml;
	var $topic, $forum, $url;
	var $errors = array();

	function XMLHeader() {
		
		$xml = "<?xml version=\"1.0\"?>\n";
		$xml .= "<rss version=\"2.0\" xmlns:postInfo=\"http://www.k4bb.org/RSS/index.php\">\n";
		$xml .= "\t<channel>\n";
		$this->header = $xml;
	}

	function XMLFooter() {
		$xml = "\t</channel>\n";
		$xml .= "</rss>";
		$this->footer = $xml;
	}

	function XMLChannelInfo($lang, $page, $num_pages) {
		$xml .= "\t<title>". $this->topic['name'] ."</title>\n";
		$xml .= "\t<description><![CDATA[". $this->topic['body_text'] ."]]></description>\n";
		$xml .= "\t<link>". $this->url ."viewtopic.php?id=". $this->topic['post_id'] ."</link>\n";
		$xml .= "\t<category>". $this->forum['name'] ."</category>\n";
		$xml .= "\t<generator>k4 Bulletin Board</generator>\n";
		$xml .= "\t<postInfo:authorId>". $this->topic['poster_id'] ."</postInfo:authorId>\n";
		$xml .= "\t<postInfo:authorName>". $this->topic['poster_name'] ."</postInfo:authorName>\n";
		$xml .= "\t<postInfo:postId>". $this->topic['post_id'] ."</postInfo:postId>\n";
		$xml .= "\t<postInfo:page>". $page ."</postInfo:page>\n";
		$xml .= "\t<postInfo:numPages>". $num_pages ."</postInfo:numPages>\n";
		$xml .= "\t<postInfo:pubDate>". $this->topic['created'] ."</postInfo:pubDate>\n";
		$xml .= "\t<pubDate>". date("D j M Y G:i:s T", $this->topic['created']) ."</pubDate>\n";
		$xml .= "\t<language>". $lang ."</language>\n";

		$this->header .= $xml;
	}
	
	function XMLItem($post) {
		$xml = "\t<item>\n";
		$xml .= "\t\t<title>". $post['name'] ."</title>\n";
		$xml .= "\t\t<category>". $this->forum['name'] ."</category>\n";
		$xml .= "\t\t<link>". $this->url ."findpost.php?id=". $post['post_id'] ."</link>\n";
		$xml .= "\t\t<guid isPermaLink=\"false\">". $post['post_id'] ."</guid>\n";
		$xml .= "\t\t<postInfo:postId>". $post['post_id'] ."</postInfo:postId>\n";
		$xml .= "\t\t<description><![CDATA[". $post['body_text'] ."]]></description>\n";
		$xml .= "\t\t<postInfo:authorId>". $post['poster_id'] ."</postInfo:authorId>\n";
		$xml .= "\t\t<postInfo:authorName>". $post['poster_name'] ."</postInfo:authorName>\n";
		$xml .= "\t\t<postInfo:pubDate>". $post['created'] ."</postInfo:pubDate>\n";
		$xml .= "\t\t<pubDate>". date("D j M Y G:i:s T", $post['created']) ."</pubDate>\n";
		$xml .= "\t</item>\n";

		$this->xml .= $xml;
	}

	function archiveTopicXML(&$request, &$forum, &$topic) {
		
		$this->forum	= &$forum;
		$this->topic	= &$topic;
		$this->url		= $request['template']->getVar('forum_url');
		
		$this->XMLHeader();
		$this->XMLFooter();

		global $_LANG;
				
		if($topic['num_replies'] > 0) {

			$result = $request['dba']->executeQuery("SELECT * FROM ". K4POSTS ." WHERE ((parent_id=". intval($topic['post_id']) ." AND row_level>1)) ORDER BY created ASC");
			
			$i			= 1;
			$page		= 1;
			$num_pages	= @ceil($result->numrows() / XMLPOSTSPERPAGE);
			
			while($result->next()) {
				
				$this->XMLItem($result->current());
				$i++;

				// should we create this page now?
				if( $i == XMLPOSTSPERPAGE || !$result->hasNext() ) {
					
					$this->XMLChannelInfo($_LANG['locale'], $page, $num_pages);
					$this->archivePage($page);
					
					// change the number of pages and reset the counter
					$page++;
					$i = 1;
				}
			}
		} else {
			$this->XMLChannelInfo($_LANG['locale'], 1, 1);
			$this->archivePage(1);
		}
	}

	function XMLFile() {
		
		$xml = $this->header;
		$xml .= $this->xml;
		$xml .= $this->footer;

		return $xml;
	}

	function archivePage($page) {
		
		$dir_name	= BB_BASE_DIR .'/archive/'. $this->forum['forum_id'];
		$file_name	= $dir_name .'/'. $this->topic['post_id'] .'-'. $page .'.xml';
		
		// make sure our dir for this archive exists.
		if(!file_exists($dir_name) || !is_dir($dir_name)) {
			mkdir($dir_name, 0777);
		}
		
		// create the XML file
		if(!file_exists($file_name) && is_writeable($dir_name)) {
			
			$error = FALSE;

			if (!$handle = fopen($file_name, 'w')) {
				 $this->errors[] = "Cannot open file ($filename)";
				 $error = TRUE;
			}
			
			// Write $somecontent to our opened file.
			if(!$error && fwrite($handle, $this->XMLFile()) === FALSE) {
				$this->errors[] = "Cannot write to file ($filename)";
				$error = TRUE;
			}
			
			if(!$error) {
				fclose($handle);
			}
		}		

		$this->clearXML();
	}

	function clearXML() {
		$this->xml = '';
	}

}

?>