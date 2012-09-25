<?php

class DOC_Util_Debug {
    public static function dump( $var, $die = TRUE ) {
		print( Debug::vars( $var ) ) ;
		if( $die ) {
			die() ;
		}
	}
}

