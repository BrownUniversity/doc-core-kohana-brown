<?php
namespace BrownUniversity\DOC\Helper ;
/**
 *
 *
 * @author jorrill
 */
class Json {

	/**
	 * Convert the data to a JSON-encoded string.
	 *
	 * @param $data
	 * @return string
	 */
	public static function get_json( $data ) {
		// $as_json_supported = array('ORM', 'Database_Result') ;

		if(is_object( $data ) && (in_array( 'ORM', class_parents( $data )) || in_array('Database\\Result', class_parents( $data )))) {
			$_output = $data->as_json() ;
		} elseif( is_array( $data )) {
			foreach( $data as $key => $value ) {
				$data[ $key ] = self::parse_data( $value ) ;
			}
			$_output = json_encode( $data ) ;
		} else {
			// this should generate some form of error object
			$_output = 'unknown data type' ;
		}

		if( isset( $options[ 'callback' ]) && !empty( $options[ 'callback' ])) {
			$_output = "{$options[ 'callback' ]}({$_output})" ;
		}

		return $_output ;

	}

	/**
	 * Parse data into an array.
	 *
	 * @param $data
	 * @return array
	 */
	protected static function parse_data( $data ) {
		$_output = $data ;
		if( is_object( $data )) {
			if( in_array( 'ORM', class_parents( $data ))) {
				$_output = $data->as_array() ;
			} elseif( in_array( 'Database_Result', class_parents( $data ))) {
				$_output = $data->as_complete_array() ;
			}
		} elseif ( is_array( $data )) {
			$_output = array() ;
			foreach( $data as $key => $value ) {
				$_output[ $key ] = self::parse_data( $value ) ;
			}
		}
		return $_output ;
	}

}
