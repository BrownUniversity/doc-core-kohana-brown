<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * A collection of functions to make sure data is structurally sound. This should
 * check things like whether arrays have data, and if we're working with multiple
 * arrays that they have matching keys.
 *
 * @author jorrill
 */
class DOC_Util_Prevalidation {
	
	
	public static function array_keys_match() {
		$arrays = func_get_args() ;
		
		for( $i = 0 ; $i < count( $arrays ) - 1; $i++) {
			if( count( array_diff_key( $arrays[$i], $arrays[$i+1])) > 0 ) {
				return FALSE ;
			}
			if( count( array_diff_key( $arrays[$i+1], $arrays[$i])) > 0 ) {
				return FALSE ;
			}
		}
		
		return TRUE ;
	}
	
	public static function arrays_non_empty() {
		$arrays = func_get_args() ;
		
		foreach( $arrays as $arr ) {
			if( !is_array( $arr ) || count( $arr ) == 0 ) {
				return FALSE ;
			}
		}
		
		return TRUE ;
	}
	
}

?>
