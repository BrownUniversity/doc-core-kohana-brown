<?php
namespace BrownUniversity\DOC\Helper ;

use ErrorException;
use Kohana\Date;
use Kohana\Form as Kohana_Form;
use Kohana\Kohana;
use Kohana\Log;
use Kohana\Request;

/**
 * A collection of static methods to make working with form data a little easier
 *
 * @author jorrill
 */
class Form {

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

	const UNCHECKED_BLANK = 'unchecked-blank' ;
	const UNCHECKED_EXPLICIT = 'unchecked-explicit' ;
	
	/**
	 * Place the label tag after the input element.
	 */
	const LABEL_AFTER = 'label-after' ;
	/**
	 * Place the label tag before the input element.
	 */
	const LABEL_BEFORE = 'label-before' ;
	/**
	 * Wrap label tags around input element.
	 */
	const LABEL_SURROUND = 'label-surround' ;
	/**
	 * Wrap label tags around input element and add appropriate bootstrap inline class.
	 */
	const LABEL_INLINE = 'label-inline' ;

	/**
	 * Given a standard datetime string (such as that returned from MySQL), create
	 * a set of form fields to edit the data.
	 *
	 * @param string $datetime
	 * @param string $name_prefix
	 * @param int    $minute_increment
	 * @param string $date_class
	 * @param bool   $include_labels
	 * @return string
	 */
	public static function datetime_input_fields( $datetime, $name_prefix, $minute_increment = self::MINUTE_INCREMENT, $date_class = self::DATEPICKER_CLASS, $include_labels = TRUE ) {
		$hours = array() ;
		for( $i = 1; $i <= 12; $i++ ) {
			$hours[ $i ] = sprintf( '%02d', $i ) ;
		}

		$minutes = array() ;
		for( $i = 0; $i < 60; $i += $minute_increment) {
			$minute_formatted = sprintf( '%02d', $i ) ;
			$minutes[ $minute_formatted ] = $minute_formatted ;
		}

		$_output = '' ;
		if( $include_labels ) {
			$_output .= 'Date ' ;
		}
		$_output .= '<input type="text" name="'.$name_prefix.self::SUFFIX_DATE.'" value="'. Date::formatted_time( $datetime, self::DATE_FORMAT ).'" class="'.$date_class.'" size="12" maxlength="12" autocomplete="off" /> ' ;
		if( $include_labels ) {
			$_output .= 'Time ' ;
		}

		$_output .= self::time_input_fields(
				Date::formatted_time( $datetime, self::HOUR_FORMAT),
				Date::formatted_time( $datetime, self::MINUTE_FORMAT),
				Date::formatted_time( $datetime, self::MERIDIAN_FORMAT),
				$name_prefix,
				$minute_increment
		) ;


		return $_output ;
	}


	/**
	 * Given separated fields to define a time, return a set of select menus to edit the data.
	 * Note that this expects the hour to be 12 or less, so if you are working with a 24-hour
	 * time string you'll need to convert to PM when your hours are greater than 12.
	 *
	 * @param int $hour
	 * @param int $minute
	 * @param string $meridian
	 * @param string $name_prefix
	 * @param int $minute_increment The number of minutes between options in the minutes menu.
	 * @return string
	 */
	public static function time_input_fields( $hour, $minute, $meridian, $name_prefix, $minute_increment = self::MINUTE_INCREMENT ) {
		$hours = array() ;
		for( $i = 1; $i <= 12; $i++ ) {
			$hours[ $i ] = sprintf( '%02d', $i ) ;
		}

		$minutes = array() ;
		for( $i = 0; $i < 60; $i += $minute_increment) {
			$minute_formatted = sprintf( '%02d', $i ) ;
			$minutes[ $minute_formatted ] = $minute_formatted ;
		}

		$meridians = array(
			'AM' => 'AM',
			'PM' => 'PM'
		) ;

		$_output = '' ;

		$_output .= Form::select( $name_prefix.self::SUFFIX_HOUR, $hours, $hour, array('class' => str_replace('_', '-', 'select'.self::SUFFIX_HOUR ))) ;
		$_output .= ':' ;
		$_output .= Form::select( $name_prefix.self::SUFFIX_MINUTE, $minutes, floor( $minute/$minute_increment) * $minute_increment, array('class' => str_replace('_', '-', 'select'.self::SUFFIX_MINUTE ))) ;
		$_output .= Form::select( $name_prefix.self::SUFFIX_MERIDIAN, $meridians, $meridian, array('class' => str_replace('_', '-', 'select'.self::SUFFIX_MERIDIAN ))) ;

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
	 * Similar to input_fields_to_datetime, this compiles time-related
	 * fields into a simple time string using a 24-hour clock.
	 *
	 * @param string $name_prefix
	 * @return string
	 */
	public static function input_fields_to_time( $name_prefix ) {
		$request = Request::current() ;

		$meridian = $request->post( $name_prefix . self::SUFFIX_MERIDIAN ) ;
		$hour = $request->post( $name_prefix . self::SUFFIX_HOUR ) ;
		$minute = $request->post( $name_prefix . self::SUFFIX_MINUTE ) ;

		if( strtoupper($meridian) == 'PM' && (int) $hour < 12 ) {
			$hour = ((int) $hour) + 12 ;
		}

		$_output = "{$hour}:{$minute}:00" ;

		return $_output ;
	}

	/**
	 * Format an incoming date, with the option to either allow NULL values to
	 * stay NULL or to allow the Date::formatted_time() method to do its thing.
	 *
	 * @param string $date_str
	 * @param string $date_format
	 * @param string $null_behavior Use one of the class constants.
	 * @return string
	 */
	public static function format_date($date_str, $date_format, $null_behavior = self::KEEP_NULL) {
		$_output = NULL ;
		if( !empty( $date_str ) || $null_behavior == self::NULL_TO_DEFAULT ) {
			try {
				$_output = Date::formatted_time($date_str, $date_format) ;
			} catch( ErrorException $e ) {
				$_output = $date_str ;
				Kohana::$log->add(Log::WARNING, 'Invalid date string: ' . $_output ) ;
			}
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
	 * @param boolean $is_removable_key key in the data array to indicate whether a given item should be removable or not.
	 * @return string
	 */
	public static function related_items( $data, $label_key, $value_key, $field_name, $mode = self::MODE_EDITABLE, $is_removable_key = NULL ) {
		$_output = '' ;
		foreach( $data as $item ) {
			$removable = TRUE ;
			if( is_array( $item )) {
				$label = $item[ $label_key ] ;
				$value = $item[ $value_key ] ;
				if( !empty( $is_removable_key )) {
					$removable = $item[ $is_removable_key ] ;
				}
			} else {
				$label = $item->$label_key ;
				$value = $item->$value_key ;
				if( !empty( $is_removable_key )) {
					$removable = $item->$is_removable_key ;
				}
			}

			$_output .= '<div class="related-item">' ;
			$_output .= '<span class="related-item-label">'.$label.'</span>' ;
			if( $mode == self::MODE_EDITABLE ) {
				if( $removable ) {
					$_output .= '<span class="related-item-link">(<a class="removal-link">Remove</a>)</span>' ;
				} else {
					$_output .= '<span class="related-item-link">(<a class="disabled">Remove</a>)</span>' ;
				}
				$_output .= '<input type="hidden" name="'.$field_name.'[]" value="'.$value.'" />' ;
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
	 * @param array  $checkbox_array
	 * @param array  $selected
	 * @param string $mode
	 * @param string $unchecked
	 * @param string $label_placement
	 * @return array
	 */
	public static function checkbox_group( $checkbox_name, $checkbox_array, $selected, $mode = self::MODE_EDITABLE, $unchecked = self::UNCHECKED_BLANK, $label_placement = self::LABEL_AFTER ) {
		$_output = array() ;
		$unchecked_class = 'checkmark-unchecked' ;
		if( $unchecked == self::UNCHECKED_EXPLICIT ) {
			$unchecked_class = 'checkmark-unchecked-explicit' ;
		}
		$checkbox_group_name = $checkbox_name . '[]' ;
		if( !is_array( $selected )) {
			$selected = array( $selected ) ;
		}
		foreach( $checkbox_array as $key => $value ) {
			$cb = '' ;
			$unique_id = "{$checkbox_name}_{$key}" ;
			$label_tag = "<label for='{$unique_id}'>{$value}</label>" ;
			if( $label_placement == self::LABEL_SURROUND ) {
				$cb .= "<label for='{$unique_id}'>" ;
			} elseif ( $label_placement == self::LABEL_INLINE) {
				$_output .= "<label for='{$unique_id}' class='checkbox-inline'>" ;
			} elseif( $label_placement == self::LABEL_BEFORE ) {
				$cb .= $label_tag ;
			}
			
			if( $mode == self::MODE_EDITABLE ) {
				$cb .= Kohana_Form::checkbox($checkbox_group_name, $key, in_array($key, $selected), array('id' => $unique_id)) ;
			} else {
				if( in_array( $key, $selected )) {
					$cb .= "<span id='{$unique_id}' class='checkmark-checked'>&nbsp;</span>" ;
				} else {
					$cb .= "<span id='{$unique_id}' class='{$unchecked_class}'>&nbsp;</span>" ;
				}

			}
			if( $label_placement == self::LABEL_SURROUND ) {
				$cb .= $value.'</label>' ;
			} elseif( $label_placement == self::LABEL_INLINE ) {
				$_output .= " {$value}</label>" ;
			} elseif( $label_placement == self::LABEL_AFTER) {
				$cb .= $label_tag ;
			}
			$_output[$key] = $cb ;
		}

		return $_output ;
	}

	/**
	 * Returns a single checkbox with label.
	 * 
	 * @param string $name
	 * @param string $value
	 * @param string $label
	 * @param boolean $selected
	 * @param string $mode
	 * @param string $unchecked
	 * @param string $label_placement
	 * @return string
	 */
	public static function checkbox_single( $name, $value, $label, $selected, $mode = self::MODE_EDITABLE, $unchecked = self::UNCHECKED_BLANK, $label_placement = self::LABEL_AFTER ) {
		$_output = '' ;
		$unchecked_class = 'checkmark-unchecked' ;
		if( $unchecked == self::UNCHECKED_EXPLICIT ) {
			$unchecked_class = 'checkmark-unchecked-explicit' ;
		}
		$css_id = preg_replace('/[^A-Za-z0-9]/', '-', $name) ;
		$label_tag = "<label for='{$css_id}'>{$label}</label>" ;
		
		
		if( $label_placement == self::LABEL_SURROUND ) {
			$_output .= "<label for='{$css_id}'>" ;
		} elseif ( $label_placement == self::LABEL_INLINE) {
			$_output .= "<label for='{$css_id}' class='checkbox-inline'>" ;
		} elseif ( $label_placement == self::LABEL_BEFORE) {
			$_output .= $label_tag ;
		}
		if( $mode == self::MODE_EDITABLE ) {
			$_output .= Kohana_Form::checkbox($name, $value, $selected, array('id' => $css_id )) ;
		} else {
			$css_class = $selected ? 'checkmark-checked' : $unchecked_class ;
			$_output .= "<span id='{$name}' class='{$css_class}'>&nbsp;</span>" ;
		}
		if( $label_placement == self::LABEL_SURROUND ) {
			$_output .= $label.'</label>' ;
		} elseif( $label_placement == self::LABEL_INLINE ) {
			$_output .= " {$label}</label>" ;
		} elseif( $label_placement == self::LABEL_AFTER) {
			$_output .= $label_tag ;
		}
		
		return $_output ;
	}
	
	
	/**
	 * Create a set of radio buttons from an array.
	 *
	 * @param string $radio_name
	 * @param array $radio_array
	 * @param string $selected
	 * @param string $mode
	 * @return array
	 */
	public static function radio_group( $radio_name, $radio_array, $selected, $mode = self::MODE_EDITABLE, $label_placement = self::LABEL_AFTER ) {
		$_output = array() ;

		foreach( $radio_array as $key => $value ) {
			$unique_id = "{$radio_name}_{$key}" ;
			if( $mode == self::MODE_EDITABLE ) {
				$radio = Kohana_Form::radio($radio_name, $key, $selected == $key, array('id' => $unique_id )) ;
			} else {
				if( $selected == $key ) {
					$radio = "<span id='{$unique_id}' class='checkmark-checked'>&nbsp;</span>" ;
				} else {
					$radio = "<span id='{$unique_id}' class='checkmark-unchecked'>&nbsp;</span>" ;
				}
			}
			$label_tag = "<label for='{$unique_id}'>{$value}</label>" ;
			if( $label_placement === self::LABEL_SURROUND ) {
				$radio = "<label for='{$unique_id}'>{$radio} {$value}</label>" ;
			} elseif( $label_placement === self::LABEL_INLINE ) {
				$radio = "<label for='{$unique_id}' class='radio-inline'>{$radio} {$value}</label>" ;
			} elseif( $label_placement === self::LABEL_BEFORE ) {
				$radio = $label_tag . $radio ;
			} else {
				$radio .= $label_tag ;
			}
			$_output[ $key ] = $radio ;
		}

		return $_output ;
	}


	/**
	 * Depending on the value of $mode, either generates an input text field via
	 * Form::input or simply returns the current value as passed.
	 *
	 * @param string $name
	 * @param string $value
	 * @param array $attributes
	 * @param string $mode Use one of the class constants.
	 * @return string
	 */
	public static function input( $name, $value = NULL, $attributes = NULL, $mode = self::MODE_EDITABLE ) {
		if( $mode == self::MODE_EDITABLE ) {
			return Kohana_Form::input($name, $value, $attributes) ;
		}
		return $value ;
	}

	/**
	 * Depending on the value of $mode, either generates a select menu via
	 * Form::select or simply returns the display for the current selection.
	 *
	 * @param string $name
	 * @param null   $options
	 * @param null   $selected
	 * @param array  $attributes
	 * @param string $mode Use one of the class constants.
	 * @return string
	 * @internal param string $value
	 * @todo Make this smart enough to handle an array for $selected.
	 */
	public static function select( $name, $options = NULL, $selected = NULL, $attributes = NULL, $mode = self::MODE_EDITABLE ) {
		if( $mode == self::MODE_EDITABLE ) {
			return Kohana_Form::select($name, $options, $selected, $attributes) ;
		}
		$_output = NULL ;
		if( isset( $options[ $selected ])) {
			$_output = $options[ $selected ] ;
		}
		return $_output ;
	}

	/**
	 * Depending on the value of $mode, either generates a textarea via
	 * Form::textarea or simply returns the current value as passed.
	 *
	 * @param string $name
	 * @param string $body
	 * @param array  $attributes
	 * @param bool   $double_encode
	 * @param string $mode Use one of the class constants.
	 * @return string
	 * @internal param string $value
	 */
	public static function textarea($name, $body = '', $attributes = NULL, $double_encode = TRUE, $mode = self::MODE_EDITABLE) {
		if( $mode == self::MODE_EDITABLE ) {
			return Kohana_Form::textarea($name, $body, $attributes, $double_encode) ;
		}
		return $body ;
	}

	/**
	 * Convenience method to get the value of a checkbox field and default to some
	 * value if it comes back NULL, which is what happens when checkboxes are not
	 * selected...
	 * 
	 * @param string $checkbox_name Name of the checkbox field in the POST array
	 * @param mixed $default_value The value to return if the checkbox is not present
	 * @return type
	 */
	public static function checkbox_value( $checkbox_name, $default_value = 0 ) {
		$_output = $default_value ;
		if( Request::$current->post( $checkbox_name ) != NULL ) {
			$_output = Request::$current->post( $checkbox_name ) ;

			if( is_array( $_output )) {
				$_output = array_pop( $_output ) ;
			}

		}
		return $_output ;
	}

}
