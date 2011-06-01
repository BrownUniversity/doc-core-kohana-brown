<?php
/**
 * Several chunks of data are stored in the database with simple key/value/abbrev
 * in the table. This class provides a standard way to get lookup arrays so that
 * we can refer to the primary key value by the abbreviation.
 *
 * @author jorrill
 */
class DOC_Util_Lookup {

	const BY_KEY = 'byKey' ;
	const BY_VAL = 'byVal' ;

	/**
	 *
	 * @param string $model A model name.
	 * @param string $key A property of the model to be used as the key in the array.
	 * @param string $mode Use one of the class constants here.
	 * @param string $order The field to order the results by. Always ascending.
	 * @param array $wheres An array of arrays, with each matching the arguments that are sent via the where() method.
	 * @return array
	 */
	static function get_lut( $model, $key, $mode = self::BY_VAL, $order = NULL, $wheres = NULL) {
		$_output = array() ;
		$orm = ORM::factory($model) ;
		if( !empty( $order )) {
			$orm->order_by( $order ) ;
		}
		
		if( is_array( $wheres )) {
			foreach( $wheres as $where ) {
				$orm->where( $where[0], $where[1], $where[2] ) ;
			}
		}
		
		$arr = $orm->find_all() ;

		foreach( $arr as $obj ) {
			$_output[ $obj->$key ] = $obj->pk() ;
		}
		if( $mode == self::BY_KEY ) {
			$_output = array_flip( $_output ) ;
		}

		return $_output ;
	}

	/**
	 * Use this when you need to create a string composed of multiple elements in the object.
	 * For example, if you need to combine first and last name into a single name
	 * containing both.
	 * 
	 * @param string $model
	 * @param array $keys
	 * @param string $format For use in sprintf.
	 * @param string $mode Use one of the class constants.
	 * @return type 
	 */
	static function get_formatted_lut( $model, $keys, $format, $mode = self::BY_VAL ) {
		$_output = array() ;
		$orm = ORM::factory($model) ;
		foreach( $keys as $key ) {
			$orm->order_by( $key ) ;
		}

		$arr = $orm->find_all() ;
		foreach( $arr as $obj ) {
			
			$values = array() ;
			foreach( $keys as $key ) {
				$values[] = '"'.$obj->$key.'"' ;
			}
	
			eval("\$thisKey = sprintf(\"$format\", ".implode(',',$values).");") ;
			$_output[ $thisKey ] = $obj->pk() ;
		}
		if( $mode == self::BY_KEY ) {
			$_output = array_flip( $_output ) ;
		}
		
		return $_output ;
	}
	
}
?>