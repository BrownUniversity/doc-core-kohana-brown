<style type="text/css">
	form#filter {
		width: 780px ;
	}
	form#filter .search-group-manipulation {
		float:right;
	}
	form#filter .add-search-group, form#filter .remove-search-group {
		float: right ;
	}
	form#filter .add-search-group span, form#filter .remove-search-group span {
		margin-top: 4px ;
	}
	form#filter .submits {
		clear: right ;
	}
	form#filter .submits-main, form#filter .submits-alternate {
		padding: 4px 0px;
	}
	form#filter .submits-main {
		float: right ;
	}
	form#filter .submits-alternate {
		float: left ;
	}
	form#filter .manipulator-placeholder {
		display: block ;
		float: right ;
		width: 16px ;
		height: 16px ;
	}
	#search-groups {
		overflow: auto ;
	}
	#search-groups .search-group {
		float:left ;
		width: 88% ;
	}
	#search-groups .boolean-connector {
		float: right ;
		width: 10% ;
	}
	form#filter .sr-only {
	/* hide from screen, but allow screen readers to access */
		position:absolute;
		left:-10000px;
		top:auto;
		width:1px;
		height:1px;
		overflow:hidden;
	}

</style>
<?php
	use BrownUniversity\DOC\Helper\Form ;
	use BrownUniversity\DOC\Util\Filter;
	use Kohana\Request;
	use Kohana\Kohana;

	if( !isset( $minute_increment )) {
		$minute_increment = Form::MINUTE_INCREMENT ;
	}


	$operators = array(
		'lt' => 'less than',
		'eq' => 'equals',
		'gt' => 'greater than',
		'ne' => 'does not equal'
	) ;
	
	$relation_operators = array(
		'choice' => array(
			'is' => 'is',
			'not' => 'is not'
		),
		'no-choice' => array(
			'is' => 'is'
		)
	) ;
	

	// For future use. It should be possible to support customized text operators
	// the same way we do for relations. Below are some possible options. Note that
	// some of these require manipulating both the operator and the string being
	// tested in some of the 'fancy' options...
	
//	$text_operators = array(
//		'choice' => array(
//			'like' => 'like',
//			'not-like' => 'not like'
//		),
//		'no-choice' => array(
//			'like' => 'like'
//		),
//		'fancy' => array(
//			'like' => 'like',
//			'not-like' => 'not like',
//			'starts' => 'starts',
//			'ends' => 'ends',
//			'is' => 'is'
//		)
//	) ;
	

	if( isset( $filter_fields ) && isset( $filter_model )) {
		$table_columns = $filter_model->list_columns() ;
		
		$storage = Filter::storage_instance() ;
		$saved_filter_specs_arr = $storage->get( Filter::get_filter_key( Filter::KEY_FULL ) ) ;

		if( $saved_filter_specs_arr == NULL ) {
			$saved_filter_specs_arr = Filter::get_default_filter_specs() ;
		}
		$form_action = preg_replace('/\/+/', '/', Kohana::$base_url.Request::detect_uri()) ;
//DOC_Util_Debug::dump( $saved_filter_specs_arr, FALSE ) ;
// DOC_Util_Debug::dump( $filter_fields ) ;
		print("<form id='filter' method='POST' action='{$form_action}'>") ;

		if( isset( $filter_extras )) {
			if( !is_array( $filter_extras )) {
				$filter_extras = array( $filter_extras ) ;
			}
			print( '<div>' . implode('<br />',$filter_extras) . '</div>' ) ;
		}

		print("<div id='search-groups'>") ;
		foreach( $saved_filter_specs_arr as $key => $saved_filter_specs ) {
			$label_search_operator = array('aria-label' => "Operator") ;

			print("<div id='search-group-{$key}' class='search-group'>") ;
			$relation_menus = array() ;
			print("<select id='filter-column-{$key}' name='filter_column[]' aria-label='Property to filter'>") ;
			$search_operator = '' ;
			foreach( $filter_fields as $filter_col => $filter_specs ) {
				$option_value = $filter_col ;
				$selected = '' ;
				$search_val_0 = '' ;
				$search_val_1 = '' ;
				$boolean_connector = 'AND' ;
				$boolean_connector = isset( $saved_filter_specs[ 'boolean_connector' ]) ? $saved_filter_specs[ 'boolean_connector' ] : 'AND' ;
				$relation_menu_type = (isset( $filter_specs[ 'relation_menu_type' ]) && array_key_exists( $filter_specs[ 'relation_menu_type'], $relation_operators))
						? $filter_specs[ 'relation_menu_type' ] 
						: 'no-choice' ;

				if( isset( $saved_filter_specs[ 'filter_column' ]) && $saved_filter_specs[ 'filter_column' ] == $option_value ) {
					$selected = "selected='selected'" ;
					$search_val_0 = isset( $saved_filter_specs[ 'search_val_0' ]) ? $saved_filter_specs[ 'search_val_0' ] : '' ;
					$search_val_1 = isset( $saved_filter_specs[ 'search_val_1' ]) ? $saved_filter_specs[ 'search_val_1' ] : '' ;
					$search_operator = isset( $saved_filter_specs[ 'search_operator' ]) ? $saved_filter_specs[ 'search_operator' ] : '' ;
				}

				// for related tables
				if( isset( $filter_specs[ 'relation_name' ])) {
					$option_class = $filter_specs[ 'relation_name' ] ;
					$column_menu = Form::select("search_val_0[]", $filter_specs[ 'relation_options' ], $search_val_0, array('aria-label' => 'Property value')) ;
					
					$relation_menus[] = "<span class='filter_value {$filter_specs[ 'relation_name' ]}'> " .
							Form::select("search_operator[]", $relation_operators[$relation_menu_type], $search_operator, $label_search_operator) .
							" {$column_menu}<input type='hidden' value='' name='search_val_1[]' /></span>" ;

				// in case we have a special array for the menu but not a special label (likely for enum fields)
				} elseif( isset( $filter_specs[ 'relation_options' ])) {
					$option_class = $filter_col ;
					$column_menu = Form::select("search_val_0[]", $filter_specs[ 'relation_options' ], $search_val_0, array('aria-label' => 'Property value')) ;
					$relation_menus[] = "<span class='filter_value {$option_class}'> " .
							Form::select("search_operator[]", $relation_operators[$relation_menu_type], $search_operator, $label_search_operator) .
							" {$column_menu}<input type='hidden' value='' name='search_val_1[]' /></span>" ;

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
							$column_menu = Form::select("search_val_0[]", $enum_menu, $search_val_0, array('aria-label' => 'Property value')) ;
							$relation_menus[] = "<span class='filter_value {$filter_col}'> = {$column_menu}<input type='hidden' name='search_operator[]' value='' /></span>" ;
						} else {
							// Some data types will come in two parts, such as "smallint unsigned", but we only want the first part.
 							$data_type_description = explode( ' ', $table_columns[ $filter_col ][ 'data_type' ] ) ;
							$option_class = array_shift( $data_type_description ) ;
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
							$column_menu = Form::select("search_val_0[]", $enum_menu, $search_val_0, array('aria-label' => 'Property value')) ;
							$relation_menus[] = "<span class='filter_value {$filter_col}'> = {$column_menu}<input type='hidden' name='search_operator[]' value='' /></span>" ;
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
				$this_data_type = Filter::get_data_type($filter_model, $saved_filter_specs[ 'filter_column' ]) ;
				if( $this_data_type == 'unknown' ) {
					if( isset( $filter_fields[ $saved_filter_specs[ 'filter_column' ]][ 'data_type' ] )) {
						$this_data_type = $filter_fields[ $saved_filter_specs[ 'filter_column' ]][ 'data_type' ] ;
					}
				}
//	DOC_Util_Debug::dump( $this_data_type, false ) ;
				if( Filter::data_type_is_text( $this_data_type )) {
					$text_default = isset( $saved_filter_specs[ 'search_val_0' ]) ? $saved_filter_specs[ 'search_val_0' ] : '' ;
				}
				if( Filter::data_type_is_date( $this_data_type )) {
					$date_default_0 = isset( $saved_filter_specs[ 'search_val_0' ]) ? $saved_filter_specs[ 'search_val_0' ] : '' ;
					$date_default_1 = isset( $saved_filter_specs[ 'search_val_1' ]) ? $saved_filter_specs[ 'search_val_1' ] : '' ;
				}
				if( Filter::data_type_is_numeric( $this_data_type )) {
					$numeric_default = isset( $saved_filter_specs[ 'search_val_0' ]) ? $saved_filter_specs[ 'search_val_0' ] : '' ;
				}

			}

			print("<span class='filter_value filter_text'>
						like
						<input type='hidden' name='search_operator[]' value='' />
						<input type='text' value='{$text_default}' name='search_val_0[]' autocorrect='off' autocapitalize='off' spellcheck='false' aria-label='Property value'/>
						<input type='hidden' value='' name='search_val_1[]' />
						</span>") ;
			print("<span class='filter_value filter_date'>
						<input type='hidden' name='search_operator[]' value='' />
						from
						<input type='text' value='{$date_default_0}' name='search_val_0[]' class='datepicker' aria-label='Start date'/>
						to
						<input type='text' value='{$date_default_1}' name='search_val_1[]' class='datepicker' aria-label='End date'/>
					</span>") ;

			$datetime_0 = Form::datetime_input_fields( $date_default_0, 'datetime_0', $minute_increment, 'datepicker-filter', FALSE ) ;
			$datetime_1 = Form::datetime_input_fields( $date_default_1, 'datetime_1', $minute_increment, 'datepicker-filter', FALSE ) ;

			print("<span class='filter_value filter_datetime'>
						<input type='hidden' name='search_operator[]' value='' />
						<input type='hidden' value='{$date_default_0}' name='search_val_0[]' />
						<input type='hidden' value='{$date_default_1}' name='search_val_1[]' />

						from {$datetime_0} to {$datetime_1}

					</span>") ;

// DOC_Util_Debug::dump( $search_operator, false ) ;
			print("<span class='filter_value filter_numeric'>".
					Form::select("search_operator[]", $operators, $search_operator, $label_search_operator).
					"<input type='text' value='{$numeric_default}' name='search_val_0[]' autocorrect='off' autocapitalize='off' spellcheck='false' aria-label='Property value'/>
					<input type='hidden' value='' name='search_val_1[]' />
					</span>"
			) ;


			foreach( $relation_menus as $relation_menu ) {
				print( $relation_menu ) ;
			}
			print("<span class='search-group-manipulation'>") ;
			print("<span class='manipulator-placeholder'></span>") ;

			print("<a class='add-search-group' role='button' aria-label='Add criteria'><span class='ui-icon ui-icon-circle-plus'></span></a>") ;
			print("<a class='remove-search-group' role='button' aria-label='Remove criteria'><span class='ui-icon ui-icon-circle-minus'></span></a>") ;

			print("</span>") ;
			print("</div>") ;
		}
		print('<div class="boolean-connector">') ;
		print(Form::select('boolean_connector', array('AND' => 'AND','OR' => 'OR'), $boolean_connector)) ;
		print('</div>') ;
		print('</div>') ;

		print("<div class='submits'>") ;

		print("<div class='submits-main'>") ;
		print("<input type='submit' name='setFilter' value='Search' id='set-filter-search' class='btn btn-default btn-xs' />") ;
		print("<input type='submit' name='setFilter' value='Clear' id='set-filter-clear' class='btn btn-default btn-xs' />") ;
		print("</div>") ;

		if( isset( $alternate_submits ) && count( $alternate_submits ) > 0 ) {
			print("<div class='submits-alternate'>") ;
			foreach( $alternate_submits as $submit_name => $submit_value ) {
				print( "<input type='submit' id='submit-alt-{$submit_name}' name='{$submit_name}' value='{$submit_value}' class='btn btn-default btn-xs' /> " ) ;
			}
			print("</div>") ;
		}

		print("<div style='clear:right;'></div>") ;
		print("</div>") ;

		print("</form>") ;
		
		if( isset( $quick_filters )) {
			print("<div id='quick-filters'>") ;
			foreach( $quick_filters as $label => $config ) {
				print("<button class='quick-filter' data-filter-config='" . json_encode($config) . "'>{$label}</button>") ;
			}
			print("</div>") ;
		}
	}
?>