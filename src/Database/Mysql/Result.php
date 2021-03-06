<?php
namespace BrownUniversity\DOC\Database\Mysql ;
/*
 * Expands on the functionality provided by Kohana_Database_MySQL_Result to support
 * JSON and deeper array output.
 */

use \Kohana\Database\Database\MySQLi\Result as MySQLi_Result;

/**
 * Expands on the functionality provided by Kohana_Database_MySQL_Result to support
 * JSON and deeper array output.
 *
 * @author jorrill
 * @todo Move into Kohana
 */
class Result extends MySQLi_Result {

	/**
	 * Returns a JSON-encoded string of the object, diving into each property.
	 * 
	 * @return string json-encoded data for the object
	 */
	public function as_json() {
		$arr = array() ;
		foreach( $this as $obj ) {
			$arr[] = $obj->as_array() ;
		}
		return json_encode( $arr ) ;
	}

	/**
	 * Dives into each property of the object to compile an array of the whole obect.
	 * 
	 * @return array
	 */
	public function as_complete_array() {
		$arr = array() ;
		foreach( $this as $obj ) {
			$arr[] = $obj->as_array() ;
		}
		return $arr ;
	}

}
