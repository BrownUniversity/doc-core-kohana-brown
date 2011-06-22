<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of valid
 *
 * @author jorrill
 */
class DOC_Valid extends Kohana_Valid {
	public static function less_than( $number, $max ) {
		return $number <= $max ;
	}
	
	public static function greater_than( $number, $min ) {
		return $number >= $min ;
	}

	/**
	 * Not sure if this works...
	 * 
	 * @param type $a
	 * @param type $b
	 * @param type $operator
	 * @return boolean 
	 */
	public static function comparison( $a, $b, $operator ) {
		$valid_operators = array('==', '>', '<', '>=', '<=', '!=') ;
		if( !in_array( $operator, $valid_operators )) {
			die('invalid operator specified') ; // TODO: how do we properly die in Kohana?
		}
		
		$comparison = "$a $operator $b" ;
		
		return eval( $comparison ) ;
		
	}
	
	/**
	 * Checks that the first datetime comes chronologically before or on the second datetime.
	 * 
	 * @param string $date_1 A date string parsable by strtotime.
	 * @param string $date_2 A date string parsable by strtotime.
	 * @return boolean 
	 */
	public static function before( $date_1, $date_2 ) {
		return strtotime( $date_1 ) <= strtotime( $date_2 ) ;
	}
	
	public static function enum($value, $enum_array) {
		return in_array($value, $enum_array) ;
	}
	
	/**
	 * Because sometimes empty is what you want.
	 * 
	 * @param mixed $value
	 * @return boolean 
	 */
	public static function is_empty( $value ) {
		return empty( $value ) ;
	}
	
	/**
	 * Verifies that the data not only has something in it, but that there is something
	 * displayable and not just HTML formatting with no content.
	 * 
	 * @param string $value
	 * @return boolean 
	 */
	public static function not_empty_html( $value ) {
		if( Valid::not_empty($value) ) {
			$value = strip_tags( $value ) ;
			return !empty( $value ) ;
		}
		return FALSE ;
		
	}
}

?>
