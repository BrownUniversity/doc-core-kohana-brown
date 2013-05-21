<?php

class DOC_Helper_Date {
	/**
	 * Given a start date, end date and day of the week, return all dates for that 
	 * day within the range as an array.
	 * 
	 * @param string $start_date The first date in the range, using the format Y-m-d
	 * @param string $end_date The last date in the range, using the format Y-m-d
	 * @param string $day The day of the week we want, use the full english name in lowercase
	 * @param string $key_format String format for the array keys.
	 * @param string $value_format String format for the array values.
	 * @return array
	 */
	public static function days_in_range( $start_date, $end_date, $day, $key_format = 'Y-m-d', $value_format = 'm/d/Y' ) {
		$_output = array() ;
		
		$timestamp = strtotime( "first {$day} {$start_date}") ;
		$end_timestamp = strtotime( "last {$day} {$end_date}") ;
		while( $timestamp < $end_timestamp ) {
			$_output[ date( $key_format, $timestamp )] = date($value_format, $timestamp) ;
			$timestamp = strtotime( "next {$day}", $timestamp ) ;
		}
		
		return $_output ;
	}
}