<?php
// TODO: Modify this to be cleaner (fewer in_array() calls for one) and more flexible-- some recursion is probably called for here...

	$_output = '' ;

	$as_json_supported = array('ORM', 'Database_Result') ;

	if(is_object( $data ) && (in_array( 'ORM', class_parents( $data )) || in_array('Database_Result', class_parents( $data )))) {
		$_output = $data->as_json() ;
	} elseif( is_array( $data )) {
		foreach( $data as $key => $value ) {		
			if( in_array( 'ORM', class_parents( $value ))) {
				$data[ $key ] = $value->as_array() ;
			} elseif( in_array( 'Database_Result', class_parents( $value ))) {
				$data[ $key ] = $value->as_complete_array() ;
			}
		}
		$_output = json_encode( $data ) ;
	} else {
		// this should generate some form of error object
		$_output = 'unknown data type' ;
	}
	
	print( $_output ) ;
?>
