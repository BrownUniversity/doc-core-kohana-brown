<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of DOC_Util_REST
 *
 * @author jorrill
 */
class DOC_Util_REST {

	const METHOD_POST = 'POST' ;
	const METHOD_GET = 'GET' ;
//	const METHOD_PUT = 'PUT' ;
//	const METHOD_DELETE = 'DELETE' ;

	const DATATYPE_JSON = 'application/json' ;
	const DATATYPE_HTML = 'text/html' ;


	/**
	 * Convert an array into an ordered query string. Using this should ensure that
	 * POST (and other) arrays can be properly compared.
	 *
	 * @param array $arr
	 * @return string
	 * @todo Deal with cases where we might be using the array ("[]") syntax
	 */
	 public static function ordered_query_string($arr) {
		 $_output = '' ;

		 if( is_array( $arr ) && count( $arr ) > 0 ) {
			 ksort($arr) ;
			 $new_array = array() ;
			 foreach( $arr as $key => $value ) {
				 $new_array[] = "{$key}={$value}" ;
			 }
			 $_output = implode('&',$new_array) ;
		 }

		 return $_output ;
	 }

	 /**
	  * Removes the protocol from a URI. Originally required to ensure URIs were
	  * standard across requests when the firewall setup was polluting our protocol
	  * information.
	  *
	  * @param string $uri
	  * @return string
	  */
	 public static function uri_no_protocol($uri) {
		 return preg_replace("#^.+?://(.+)$#", "$1", $uri) ;

	 }


}