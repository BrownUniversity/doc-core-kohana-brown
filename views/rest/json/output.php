<?php
	$_output = '' ;

	// $as_json_supported = array('ORM', 'Database_Result') ;

	if(is_object( $data ) && (in_array( 'ORM', class_parents( $data )) || in_array('Database\\Result', class_parents( $data )))) {
		$_output = $data->as_json() ;
	} elseif( is_array( $data )) {
		foreach( $data as $key => $value ) {
			$data[ $key ] = parse_data( $value ) ;
		}
		$_output = json_encode( $data ) ;
	} else {
		// this should generate some form of error object
		$_output = 'unknown data type' ;
	}

	if( isset( $options[ 'callback' ]) && !empty( $options[ 'callback' ])) {
		$_output = "{$options[ 'callback' ]}({$_output})" ;
	}

	print( $_output ) ;
	exit() ;

function parse_data( $data ) {
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
			$_output[ $key ] = parse_data( $value ) ;
		}
	}
	return $_output ;
}
