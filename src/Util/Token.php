<?php
namespace BrownUniversity\DOC\Util ;

use Kohana\Encrypt;

/**
 * Class to create and parse tokens that can be used to identify resources available
 * to unauthenticated users.
 *
 * @author jorrill
 */
class Token {
    /**
     *
     * @param mixed  $object
     * @param array  $properties
     * @param string $route_as
     * @return string
     * @throws \Kohana\KohanaException
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

    /**
     * @param $token
     * @return mixed
     * @throws \Kohana\KohanaException
     */
    public static function parse( $token ) {
		return json_decode(Encrypt::instance()->decode_url_safe($token)) ;
	}
}
