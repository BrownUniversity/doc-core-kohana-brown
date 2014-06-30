<?php

class DOC_Util_Debug {
	public static function dump( $var, $die = TRUE ) {
		print( Debug::vars( $var ) ) ;
		if( $die ) {
			die() ;
		}
	}
	
	/**
	 * Convience method for dumping an ORM result. Can be given a single field
	 * or an array of fields to print for each object with a separator between
	 * the fields (default separator = ' '). Optional boolean to exit after printing.
	 * 
	 * @param array $var
	 * @param array $fields
	 * @param boolean $die
	 * @param string $separator
	 */
	public static function dump_orm($var, $fields = NULL, $die = TRUE, $separator = ' ') {
		foreach ($var as $v) {
			if (is_string($fields)) {
				print ( Debug::vars( $v->$fields ) ) ;
			} elseif (is_array($fields) && count($fields)) {
				foreach ($fields as $f) {
					print ( Debug::vars( $v->$f ) ) ;
					print ( $separator ) ;
				}
			} else {
				print( Debug::vars( $v ) ) ;
			}
		}
		if ( $die ) {
			die() ;
		}
	}
}

