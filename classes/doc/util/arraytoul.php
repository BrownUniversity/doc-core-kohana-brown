<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * A simple method for spitting out arrays as unordered lists.
 *
 * @author jorrill
 */
class DOC_Util_Arraytoul {
    public static function create($var, $include_outer = TRUE) {
		$_output = '' ;
		if( !is_array( $var )) {
			$var = array($var) ;
		}

		if( $include_outer ) {
			$_output .= '<ul>' ;
		}

		foreach( $var as $v) {
			if( is_array( $v )) {
				$_output .= self::create($v, FALSE) ;
			} else {
				$_output .= "<li>{$v}</li>" ;
			}
			
		}
		if( $include_outer ) {
			$_output .= '</ul>' ;
		}

		return $_output ;
	}
}
?>
