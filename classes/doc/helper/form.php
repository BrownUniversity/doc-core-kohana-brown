<?php
/**
 * A collection of static methods to make working with form data a little easier
 *
 * @author jorrill
 */
class DOC_Helper_Form {

	const DATE_FORMAT = 'm/d/Y' ;
	const HOUR_FORMAT = 'g' ;
	const MINUTE_FORMAT = 'i' ;
	const MINUTE_INCREMENT = 15 ;
	const DATEPICKER_CLASS = 'datepicker' ;
	const MERIDIAN_FORMAT = 'A' ;
	const DATETIME_FORMAT = 'Y-m-d H:i:s' ;
	const DATETIME_FORMAT_USA = 'm/d/Y h:i A' ;
	const DATE_FORMAT_USA = 'm/d/Y' ;
	const DATE_FORMAT_DB = 'Y-m-d' ;
	const KEEP_NULL = 'keep-null' ;
	const NULL_TO_DEFAULT = 'null-to-default' ;
	
	const SUFFIX_DATE = '_date' ;
	const SUFFIX_HOUR = '_hour' ;
	const SUFFIX_MINUTE = '_minute' ;
	const SUFFIX_MERIDIAN = '_meridian' ;

	const MODE_READ_ONLY = 'read-only' ;
	const MODE_EDITABLE = 'editable' ;

	/**
	 * Given a standard datetime string (such as that returned from MySQL), create
	 * a set of form fields to edit the data. 
	 * 
	 * @param string $datetime
	 * @param string $name_prefix
	 * @return string 
	 */
	public static function datetime_input_fields( $datetime, $name_prefix ) {
		$hours = array() ;
		for( $i = 1; $i <= 12; $i++ ) {
			$hours[ $i ] = sprintf( '%02d', $i ) ;
		}

		$minutes = array() ;
		for( $i = 0; $i < 60; $i += self::MINUTE_INCREMENT) {
			$minute_formatted = sprintf( '%02d', $i ) ;
			$minutes[ $minute_formatted ] = $minute_formatted ;
		}

		$meridian = array(
			'AM' => 'AM',
			'PM' => 'PM'
		) ;

		$_output = '' ;
		$_output .= 'Date ' ;
		$_output .= '<input type="text" name="'.$name_prefix.self::SUFFIX_DATE.'" value="'.Date::formatted_time( $datetime, self::DATE_FORMAT ).'" class="'.self::DATEPICKER_CLASS.'" size="12" maxlength="12" /> ' ;
		$_output .= 'Time ' ;

		$_output .= Form::select($name_prefix.self::SUFFIX_HOUR, $hours, Date::formatted_time( $datetime, self::HOUR_FORMAT)) ;
		$_output .= ':' ;
		$_output .= Form::select($name_prefix.self::SUFFIX_MINUTE, $minutes, Date::formatted_time( $datetime, self::MINUTE_FORMAT)) ;
		$_output .= Form::select($name_prefix.self::SUFFIX_MERIDIAN, $meridian, Date::formatted_time( $datetime, self::MERIDIAN_FORMAT)) ;

		return $_output ;
	}

	/**
	 * This is a companion to datetime_input_fields. Use to collect the data from
	 * the separate input fields into a single string.
	 *
	 * @param string $name_prefix
	 * @return string A formatted datetime string.
	 * @see Helper_Form::datetime_input_fields
	 */
	public static function input_fields_to_datetime( $name_prefix ) {
		$_output = '' ;

		$request = Request::current() ;

		$date = $request->post( $name_prefix . self::SUFFIX_DATE ) ;
		$hour = $request->post( $name_prefix . self::SUFFIX_HOUR ) ;
		$minute = $request->post( $name_prefix . self::SUFFIX_MINUTE ) ;
		$meridian = $request->post( $name_prefix . self::SUFFIX_MERIDIAN ) ;

		$datetime_str = "{$date} {$hour}:{$minute}:00 {$meridian}" ;

		$_output = date( self::DATETIME_FORMAT, strtotime( $datetime_str )) ;

		return $_output ;
	}

	/**
	 * Format an incoming date, with the option to either allow NULL values to
	 * stay NULL or to allow the Date::formatted_time() method to do its thing.
	 * 
	 * @param string $date_str
	 * @param string $date_format
	 * @param string $null_behavior Use one of the class constants.
	 * @return type 
	 */
	public static function format_date($date_str, $date_format, $null_behavior = self::KEEP_NULL) {
		$_output = NULL ;
		if( !empty( $date_str ) || $null_behavior == self::NULL_TO_DEFAULT ) {
			$_output = Date::formatted_time($date_str, $date_format) ;
		}
		
		return $_output ;
	}
	
	/**
	 * This creates the HTML structure used to define a set of objects related
	 * to whatever the main form is set up to edit. It can optionally be read-only.
	 * 
	 * @param array $data An array of objects or arrays containing the related items.
	 * @param string $label_key
	 * @param string $value_key
	 * @param string $field_name
	 * @param string $mode Use one of the MODE_* class constants
	 * @return string 
	 */
	public static function related_items( $data, $label_key, $value_key, $field_name, $mode = self::MODE_EDITABLE ) {
		$_output = '' ;
		foreach( $data as $item ) {
			if( is_array( $item )) {
				$label = $item[ $label_key ] ;
				$value = $item[ $value_key ] ;
			} else {
				$label = $item->$label_key ;
				$value = $item->$value_key ;
			}

			$_output .= "<div class='related-item'>" ;
			$_output .= "<span class='related-item-label'>{$label}</span>" ;
			if( $mode == self::MODE_EDITABLE ) {
				$_output .= "<span class='related-item-link'>(<a class='removal-link'>remove</a>)</span>" ;
				$_output .= "<input type='hidden' name='{$field_name}[]' value='{$value}' />" ;
			}
			$_output .= "</div>" ;
		}

		return $_output ;
	}

	/**
	 * Create a set of checkboxes using the "[]" suffix to automatically create
	 * a PHP array. (Why is this not already in Kohana?)
	 * 
	 * @param string $checkbox_name
	 * @param array $checkbox_array
	 * @param array $selected
	 * @return array 
	 */
	public static function checkbox_group( $checkbox_name, $checkbox_array, $selected, $mode = self::MODE_EDITABLE ) {
		$_output = array() ;
		$checkbox_group_name = $checkbox_name . '[]' ;
		foreach( $checkbox_array as $key => $value ) {
			$unique_id = "{$checkbox_name}_{$key}" ;
			if( $mode == self::MODE_EDITABLE ) {
				$cb = Form::checkbox($checkbox_group_name, $key, in_array($key, $selected), array('id' => $unique_id)) ;
			} else {
				if( in_array( $key, $selected )) {
					$cb = "<span id='{$unique_id}' class='checkmark-checked'>&nbsp;</span>" ;
				} else {
					$cb = "<span id='{$unique_id}' class='checkmark-unchecked'>&nbsp;</span>" ;
				}
				
			}
			
			$cb .= "<label for='{$unique_id}'>{$value}</label>" ;
			$_output[$key] = $cb ;
		}
		
		return $_output ;
	}
	
	public static function input( $name, $value, $attributes = NULL, $mode = self::MODE_EDITABLE ) {
		if( $mode == self::MODE_EDITABLE ) {
			return Form::input($name, $value, $attributes) ;
		}
		return $value ;
	}

}
?>
