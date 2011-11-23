<?php
	if( isset( $filter_fields ) && isset( $filter_model )) {
		$session = Session::instance('database') ;
		$table_columns = $filter_model->list_columns() ;
		$relation_menus = array() ;

		$saved_filter_specs = $session->get( Util_Filter::get_filter_key() ) ;
		$form_action = preg_replace('/\/+/', '/', Kohana::$base_url.Request::detect_uri()) ;


		print("<form id='filter' method='POST' action='{$form_action}'>") ;
		
		if( isset( $filter_extras )) {
			if( !is_array( $filter_extras )) {
				$filter_extras = array( $filter_extras ) ;
			}
			print( '<div>' . implode('<br />',$filter_extras) . '</div>' ) ;
		}
		
		print("Filter: ") ;
		print("<select name='filter_column'>") ;
		foreach( $filter_fields as $filter_col => $filter_specs ) {
			$option_value = $filter_col ;
			$selected = '' ;
			$search_val_0 = '' ;
			$search_val_1 = '' ;
			$search_operator = '' ;

			if( isset( $saved_filter_specs[ 'filter_column' ]) && $saved_filter_specs[ 'filter_column' ] == $option_value ) {
				$selected = "selected='selected'" ;
				$search_val_0 = isset( $saved_filter_specs[ 'search_val_0' ]) ? $saved_filter_specs[ 'search_val_0' ] : '' ;
				$search_val_1 = isset( $saved_filter_specs[ 'search_val_1' ]) ? $saved_filter_specs[ 'search_val_1' ] : '' ;
				$search_operator = isset( $saved_filter_specs[ 'search_operator' ]) ? $saved_filter_specs[ 'search_operator' ] : '' ;
			}

			// for related tables
			if( isset( $filter_specs[ 'relation_name' ])) {
				$option_class = $filter_specs[ 'relation_name' ] ;
				$column_menu = Form::select('search_val_0', $filter_specs[ 'relation_options' ], $search_val_0) ;
				$relation_menus[] = "<span class='filter_value {$filter_specs[ 'relation_name' ]}'> is {$column_menu}</span>" ;

			// in case we have a special array for the menu but not a special label (likely for enum fields)
			} elseif( isset( $filter_specs[ 'relation_options' ])) {
				$option_class = $filter_col ;
				$column_menu = Form::select('search_val_0', $filter_specs[ 'relation_options' ], $search_val_0) ;
				$relation_menus[] = "<span class='filter_value {$option_class}'> is {$column_menu}</span>" ;

			} elseif( isset( $filter_specs[ 'custom_query' ]) && $filter_specs[ 'custom_query'] == TRUE ) {
				$option_class = 'filter_text' ;
				if( isset( $filter_specs[ 'data_type' ] )) {
					$option_class = 'filter_' . preg_replace( '/^(.+?) ?/', '$1', $filter_specs[ 'data_type' ] ) ;
				} 
				
			// defaults
			} else {
				if( isset( $table_columns[ $filter_col ])) {
					if( $table_columns[ $filter_col ][ 'data_type' ] == 'enum' ) {
						// the column spec doesn't give us exactly what we need, since it 
						// creates numeric keys. We'll make sure the keys are the same as the values.

						$enum_menu = array() ;
						foreach( $table_columns[ $filter_col ][ 'options' ] as $enum_option ) {
							$enum_menu[ $enum_option ] = $enum_option ;
						}

						$option_class = $filter_col ;
						$column_menu = Form::select('search_val_0', $enum_menu, $search_val_0) ;
						$relation_menus[] = "<span class='filter_value {$filter_col}'> = {$column_menu}</span>" ;
					} else {
						$option_class = preg_replace( '/^(.+?) ?/', '$1', $table_columns[ $filter_col ][ 'data_type' ] ) ;
					}
					
				} else {
					// We haven't been able to find this in the main object's column list.
					// Just going to assume for the moment this is describing a simple child property.
					$property_description = explode('->', $filter_col) ;
					$property = $property_description[0] ;
					$column = $property_description[1] ;
					$property_columns = $filter_model->$property->list_columns() ;
					
					if( $property_columns[ $column ][ 'data_type' ] == 'enum' ) {
						// the column spec doesn't give us exactly what we need, since it 
						// creates numeric keys. We'll make sure the keys are the same as the values.

						$enum_menu = array() ;
						foreach( $property_columns[ $column ][ 'options' ] as $enum_option ) {
							$enum_menu[ $enum_option ] = $enum_option ;
						}

						$option_class = $filter_col ;
						$column_menu = Form::select('search_val_0', $enum_menu, $search_val_0) ;
						$relation_menus[] = "<span class='filter_value {$filter_col}'> = {$column_menu}</span>" ;
					} else {
						$option_class = preg_replace( '/^(.+?) ?/', '$1', $property_columns[ $column ][ 'data_type' ] ) ;
					}
					
				}

			}


			print( "<option value='$filter_col' class='$option_class' $selected>" ) ;
			if( isset( $filter_specs[ 'display' ])) {
				print( $filter_specs[ 'display' ]) ;
			} else {
				print( ucwords(preg_replace('/[\W_]+/', ' ', $filter_col))) ;
			}
			print( "</option>" ) ;
		}
		print("</select>") ;
		
		$text_default = '' ;
		$date_default_0 = '' ;
		$date_default_1 = '' ;
		$numeric_default = '' ;
		if( isset( $saved_filter_specs[ 'filter_column' ])) {
			$this_data_type = Util_Filter::get_data_type($filter_model, $saved_filter_specs[ 'filter_column' ]) ;
			if( $this_data_type == 'unknown' ) {
				if( isset( $filter_fields[ $saved_filter_specs[ 'filter_column' ]][ 'data_type' ] )) {
					$this_data_type = $filter_fields[ $saved_filter_specs[ 'filter_column' ]][ 'data_type' ] ;
				}	
			}
//Util_Debug::dump( $this_data_type, false ) ;			
			if( Util_Filter::data_type_is_text( $this_data_type )) {
				$text_default = isset( $saved_filter_specs[ 'search_val_0' ]) ? $saved_filter_specs[ 'search_val_0' ] : '' ;
			}
			if( Util_Filter::data_type_is_date( $this_data_type )) {
				$date_default_0 = isset( $saved_filter_specs[ 'search_val_0' ]) ? $saved_filter_specs[ 'search_val_0' ] : '' ;
				$date_default_1 = isset( $saved_filter_specs[ 'search_val_1' ]) ? $saved_filter_specs[ 'search_val_1' ] : '' ;
			}
			if( Util_Filter::data_type_is_numeric( $this_data_type )) {
				$numeric_default = isset( $saved_filter_specs[ 'search_val_0' ]) ? $saved_filter_specs[ 'search_val_0' ] : '' ;
			}
			
		}
		
		print("<span class='filter_value filter_text'> like <input type='text' value='{$text_default}' name='search_val_0' /></span>") ;
		print("<span class='filter_value filter_date'> 
					from 
					<input type='text' value='{$date_default_0}' name='search_val_0' class='datepicker' /> 
					to 
					<input type='text' value='{$date_default_1}' name='search_val_1' class='datepicker' />
				</span>") ;
		
		$operators = array(
			'lt' => 'less than',
			'eq' => 'equals',
			'gt' => 'greater than',
			'ne' => 'does not equal'
		) ;
					
		print("<span class='filter_value filter_numeric'>".Form::select('search_operator', $operators, $search_operator)."<input type='text' value='{$numeric_default}' name='search_val_0' /></span>") ;
					
					
		foreach( $relation_menus as $relation_menu ) {
			print( $relation_menu ) ;
		}

		print("<span class='filter_submit'>") ;
		print("<input type='submit' name='setFilter' value='Search' />") ;
		print("<input type='submit' name='setFilter' value='Clear' />") ;
		print("</span>") ;

		print("</form>") ;		
	}
?>