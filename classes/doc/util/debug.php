<?php

class DOC_Util_Debug {
	public static function dump( $var, $die = TRUE ) {
		print( Debug::vars( $var ) ) ;
		if( $die ) {
			die() ;
		}
	}
	
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

