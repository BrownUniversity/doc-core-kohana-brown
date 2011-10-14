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
}

?>
