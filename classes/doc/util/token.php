<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Class to create and parse tokens that can be used to identify resources available
 * to unauthenticated users.
 *
 * @author jorrill
 */
class DOC_Util_Token {
	/**
	 * 
	 * @param mixed $object
	 * @param array $properties
	 * @param string $route_as
	 * @return string
	 */
	public static function create( $object, $token, $properties, $route_as ) {
		$data = array(
			'token' => $token,
			'route_as' => $route_as,
			'properties' => array()
		) ;
		foreach( $properties as $property ) {
			$data['properties'][$property] = $object->$property ;
		}
		
		return Encrypt::instance()->encode_url_safe(json_encode($data)) ;
	}
	public static function parse( $token ) {
		return json_decode(Encrypt::instance()->decode_url_safe($token)) ;
	}
}
