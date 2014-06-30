<?php

/**
 * Contains a set of static functions for dealing with Word HTML that may land in
 * rich text fields.
 *
 * @author jorrill
 */
class DOC_Util_WordHTML {

	const ALLOWABLE_TAGS_DEFAULT = "<div><br><strong><em><strike><del><blockquote><ol><ul><li><b><p><i><u>" ;
	const ALLOWABLE_TAGS_LINKS_OK = "<div><br><strong><em><strike><del><blockquote><ol><ul><li><b><p><i><u><a>" ;
	const IGNORE_ATTRS_IN_TAGS = "<a>" ;

	/**
	 *
	 * @var DOMDocument
	 */
	static $domdocument = NULL ;
	
	/**
	 * Turn Word-generated HTML into something without all the cruft. This is basically
	 * an HTML cleaner, and could be used to clean up other problematic code.
	 *
	 * @param string $str
	 * @param string $allow_tags A list of allowable tags, i.e. "<p><b><strong>"
	 * @return string
	 */
	public static function clean( $str, $allow_tags = self::ALLOWABLE_TAGS_DEFAULT, $ignore_attributes_in_tags = self::IGNORE_ATTRS_IN_TAGS ) {
		$_output = $str ;
		
		/* 
		 * Some of the content cleaned here is WYSIWYG generated,
		 * but pre-cleaned by DOMDocument. This converts <br> tags to <br/>
		 * which is not caught by strip_tags unless explicitly included in
		 * the $allow_tags list. However, including the <br/> tag messes up the
		 * preg_replace regular expression below, so we add it only here if the
		 * <br> tag is included in $allow_tags.
		 */
		if (strpos($allow_tags, '<br>') !== FALSE) {
			$_output = strip_tags($str, $allow_tags . '<br/>') ;
		} else {
			$_output = strip_tags($str, $allow_tags) ;
		}
		
		preg_match_all( "/<([^>]+)>/i", $allow_tags, $all_tags, PREG_PATTERN_ORDER ) ;
		preg_match_all( "/<([^>]+)>/i", $ignore_attributes_in_tags, $ignore_tag_attrs, PREG_PATTERN_ORDER ) ;
		foreach( $all_tags[1] as $tag ) {
			if( !(isset( $ignore_tag_attrs[1] ) && is_array( $ignore_tag_attrs[1] ) && in_array($tag, $ignore_tag_attrs[1] ))) {
				$_output = preg_replace( "/<".$tag." [^>]*>/i", "<".$tag.">", $_output ) ;
			}
		}

		return $_output ;
	}

	/**
	 * Convert smart quotes, en dashes, em dashes and ellipsis characters into plain text equivalents.
	 *
	 * @param string $str
	 * @return string
	 */
	public static function convert_problem_chars( $str ) {
		// UTF-8 Characters
		$str = str_replace(
			array("\xe2\x80\x98", "\xe2\x80\x99", "\xe2\x80\x9c", "\xe2\x80\x9d", "\xe2\x80\x93", "\xe2\x80\x94", "\xe2\x80\xa6"),
			array("'", "'", '"', '"', '-', '--', '...'),
			$str
		);
		// Next, replace their Windows-1252 equivalents.
		$str = str_replace(
			array(chr(145), chr(146), chr(147), chr(148), chr(150), chr(151), chr(133)),
			array("'", "'", '"', '"', '-', '--', '...'),
			$str
		);
		return $str ;
	}
	
	/**
	 * We don't have access to HTML Tidy on the server we're using, but running HTML
	 * through DOMDocument cleans things up a bit.
	 * 
	 * @param string $str
	 * @return string
	 */
	public static function domdocument_tidy( $str, $strip_html_body = TRUE ) {
		if( self::$domdocument == NULL ) {
			self::$domdocument = new DOMDocument() ;
		}
		
		@self::$domdocument->loadHTML($str) ;
        $str = self::$domdocument->saveHTML() ;
		
		// a side effect of this approach is that we end up with DOCTYPE, html and body tags
		if( $strip_html_body ) {
			$str = preg_replace('/<\/?(body|html)>/', '', $str) ;
			$str = preg_replace('/<!DOCTYPE.+?>/','',$str) ;
		}
		
		return $str ;
		
	}
}
