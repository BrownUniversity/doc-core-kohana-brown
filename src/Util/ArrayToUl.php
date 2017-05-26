<?php
namespace BrownUniversity\DOC\Util ;

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
    public static function create($var, $include_outer = TRUE, $suppress_duplicates = FALSE) {
		$_output = '' ;
		if( !is_array( $var )) {
			$var = array($var) ;
		}

		if( $include_outer ) {
			$_output .= '<ul>' ;
		}

		if( $suppress_duplicates ) {
			$var = array_unique( $var ) ;
		}

		foreach( $var as $v) {
			if( is_array( $v )) {
				$_output .= self::create($v, FALSE, $suppress_duplicates) ;
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
