<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of wordhtml
 *
 * @author jorrill
 */
class DOC_Util_WordHTML {
	
	const ALLOWABLE_TAGS_DEFAULT = "<p><div><br><strong><b><em><i><u><strike><blockquote><ol><ul><li>" ;
	
	/**
	 * Turn Word-generated HTML into something without all the cruft. This is basically
	 * an HTML cleaner, and could be used to clean up other problematic code.
	 * 
	 * @param string $str
	 * @param string $allow_tags A list of allowable tags, i.e. "<p><b><strong>"
	 * @return string 
	 */
	public static function clean( $str, $allow_tags = self::ALLOWABLE_TAGS_DEFAULT ) {
		$_output = $str ;
		$_output = strip_tags($str, $allow_tags) ;
		
		preg_match_all( "/<([^>]+)>/i", $allow_tags, $all_tags, PREG_PATTERN_ORDER ) ;
		foreach( $all_tags[1] as $tag ) {
			$_output = preg_replace( "/<".$tag."[^>]*>/i", "<".$tag.">", $_output ) ;
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
}

?>
