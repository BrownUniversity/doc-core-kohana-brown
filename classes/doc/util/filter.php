<?php

/**
 * Supports the creation and use of a standard single-line filter interface for
 * tabular data.
 *
 * @author jorrill
 */
class DOC_Util_Filter {
	const FILTER_SUFFIX = '_search_filter' ;
	const KEY_FRAGMENT = 'fragment' ;
	const KEY_FULL = 'full' ;
	const WILDCARDS_ON = TRUE ;
	const WILDCARDS_OFF = FALSE ;
	const NOT_SET = '-1' ;

	static $storage_object = NULL ;
	
	/**
	 * Checks the filter config file for the storage class to be used, and returns
	 * an instance of that object.
	 * 
	 * @return mixed
	 */
	public static function storage_instance() {
		if( empty( self::$storage_object )) {
			$storage_object_class = Kohana::$config->load('filter.storage_class') ;
			self::$storage_object = $storage_object_class::instance() ;
		}
		
		return self::$storage_object ;
	}
	
	/**
	 * Get the key we are using for the filter data on the current page (URI).
	 *
	 * @return string
	 */
	public static function get_filter_key( $return_type = self::KEY_FRAGMENT ) {
		if( $return_type == self::KEY_FULL ) {
			$base = Kohana::$base_url . '/' ;
		} elseif( $return_type == self::KEY_FRAGMENT ) {
			$base = '/' ;
		}
		
		$route_arr = Request::process_uri( Request::current()->uri()) ;
		if( !is_array( $route_arr )) {
			return $base ;
		}
		$base_params = array_intersect_key( $route_arr['params'], array('directory' => '', 'controller' => '', 'action' => '' ));

		return $base . $route_arr['route']->uri( $base_params ) . self::FILTER_SUFFIX ;
	}

	/**
	 * Determine whether the passed in data type is text-oriented.
	 *
	 * @param string $data_type
	 * @return boolean
	 */
	public static function data_type_is_text( $data_type ) {
		$valid_types = array(
			'varchar',
			'char',
			'text',
			'longtext',
			'mediumtext',
			'shorttext',
			'unknown'
		) ;

		return in_array( $data_type, $valid_types ) ;
	}

	/**
	 * Determine whether the passed in data type is date-oriented.
	 *
	 * @param string $data_type
	 * @return boolean
	 */
	public static function data_type_is_date( $data_type ) {
		$valid_types = array(
			'date',
			'datetime',
			'timestamp'
		) ;

		return in_array( $data_type, $valid_types ) ;
	}


	/**
	 * Determine whether the passed in data type is numeric.
	 *
	 * @param string $data_type
	 * @return boolean
	 */
	public static function data_type_is_numeric( $data_type ) {
		$valid_types = array(
			'tinyint',
			'smallint',
			'mediumint',
			'int',
			'bigint',
			'float',
			'double',
			'decimal',
			'numeric'
		) ;

		return in_array( $data_type, $valid_types ) ;
	}

	/**
	 * Checks both stored data and the POST array to determine whether
	 * there is a filter in place.
	 *
	 * @return boolean
	 */
	public static function filter_exists() {
		$storage = self::storage_instance() ;
		$stored_filter = $storage->get( self::get_filter_key( self::KEY_FULL )) ;

		$new_filter = Request::current()->post('setFilter') == 'Search' ;
		return !empty( $stored_filter ) || $new_filter ;
	}

	/**
	 * Return the current filter specs from storage. This is primarily for use
	 * with external code that might want to examine the filters...
	 *
	 * @return array
	 */
	public static function get_current_filter_specs() {
		$filter_key = self::get_filter_key( self::KEY_FULL ) ;
		
		$storage = self::storage_instance() ;
		return $storage->get( $filter_key ) ;		
	}

	/**
	 * Modifies and returns the passed in ORM object, adding in search filters
	 * based on the current parameters. Defaults to data in storage, but
	 * uses POST data if present.
	 *
	 * @param ORM $orm_base
	 * @return ORM
	 */
	public static function add_filter($orm_base, $substitutions = NULL) {

		$_output = $orm_base ;
		$filter_key = self::get_filter_key( self::KEY_FULL ) ;
		$search_filter_key = self::get_filter_key( self::KEY_FRAGMENT ) ;
		$filter_specs_arr = NULL ;
		$request = Request::current() ;
		
		$storage = self::storage_instance() ;
		
		$search_filters = Kohana::$config->load('searchfilters') ;
		$orm_connectors = array(
			'OR' => 'or_where',
			'AND' => 'and_where'
		) ;

        // DOC_Util_Debug::dump( array( $filter_key, $search_filters )) ;
		// check for a reset...
		if (($request->post('setFilter') == 'Clear' ) || 
            ($request->post('setFilter') == 'Reset' ))
        {
			
			$storage->delete( $filter_key ) ;
			return $_output ;
		}

		
		
		// start with stored data, if it exists
		$filter_specs_arr = $storage->get( $filter_key ) ;
		
		// do we have a new filter request?
		if( $request->post('setFilter') == 'Search' ) {
			$filter_column_arr = $request->post( 'filter_column' ) ;
			$search_val_0_arr = $request->post( 'search_val_0' ) ;
			$search_val_1_arr = $request->post( 'search_val_1' ) ;
			$search_operator_arr = $request->post( 'search_operator' ) ;
			$boolean_connector = $request->post( 'boolean_connector' ) ;

			$filter_specs_arr = array() ;
			foreach( $filter_column_arr as $key => $filter_column ) {
				$filter_specs_arr[] = array(
					'filter_column' => $filter_column_arr[ $key ],
					'search_val_0' => $search_val_0_arr[ $key ],
					'search_val_1' => isset($search_val_1_arr[ $key ]) ? $search_val_1_arr[ $key ] : '',
					'search_operator' => isset($search_operator_arr[ $key ]) ? $search_operator_arr[ $key ] : '',
					'boolean_connector' => $boolean_connector
				) ;
			}
            
			$storage->set( $filter_key, $filter_specs_arr ) ;			
		}

        if( $filter_specs_arr != NULL ) {
			$_output = $_output->and_where_open() ;
			foreach( $filter_specs_arr as $filter_specs ) {
				$bool_connector = 'AND' ;
				if( isset( $filter_specs[ 'boolean_connector' ])) {
					$bool_connector = $filter_specs[ 'boolean_connector' ] ;
				}

                if( isset( $search_filters[ $search_filter_key ] ) && isset( $search_filters[ $search_filter_key ][ $filter_specs[ 'filter_column' ]] )) {
					if(($filter_specs[ 'search_val_0' ] != '') || ( $filter_specs[ 'search_val_1' ] != '')) {
                    
						$replacement_0 = $filter_specs[ 'search_val_0' ] ;
						$replacement_1 = $filter_specs[ 'search_val_1' ] ;
						$operator = self::get_operator( $filter_specs[ 'search_operator' ]) ;

						// run the query to get the list of IDs, then add to the where clause
						$sql = $search_filters[ $search_filter_key ][ $filter_specs[ 'filter_column' ]][ 'sql' ] ;

						$sql = str_replace(	array( '{operator}' ), array( $operator ), $sql ) ;
                        
                        // do any other replacements based on the substitutions array
						if( is_array( $substitutions ) && count( $substitutions ) > 0 ) {
							foreach( $substitutions as $key => $value ) {
								$sql = str_replace( array( '{'.$key.'}' ), array( $value ), $sql ) ;
							}
						}

                        
                        $query = DB::query( Database::SELECT, $sql ) ;
                        
						if( isset( $search_filters[ $search_filter_key ][ $filter_specs[ 'filter_column' ]][ 'data_type' ]) && $search_filters[ $search_filter_key ][ $filter_specs[ 'filter_column' ]][ 'data_type' ] == 'date') {
							if( empty( $replacement_0 )) {
								$replacement_0 = '2000-01-01 00:00:00' ;
							} else {
								if( preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $replacement_0) == 0 ) {
									$replacement_0 = Date::formatted_time( $replacement_0 . ' 00:00:00' ) ;
								}
							}

							if( empty( $replacement_1 )) {
								$replacement_1 = '2999-12-31 23:59:59' ; // Y3K bug, FTW!
							} else {
								if( preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $replacement_1) == 0 ) {
									$replacement_1 = Date::formatted_time( $replacement_1 . ' 23:59:59' ) ;
								}
							}

							$query->parameters( array(
								':search_val_0' => $replacement_0,
								':search_val_1' => $replacement_1,
							)) ;
						} elseif(isset( $search_filters[ $search_filter_key ][ $filter_specs[ 'filter_column' ]][ 'data_type' ]) && $search_filters[ $search_filter_key ][ $filter_specs[ 'filter_column' ]][ 'data_type' ] == 'numeric') {
							$query->parameters( array(
								':search_val_0' => $replacement_0,
								':search_val_1' => $replacement_1,
							)) ;
						} else {
							$query->parameters( array(
								':search_val_0' => "%$replacement_0%",
								':search_val_1' => "%$replacement_1%",
							)) ;
						}

						$db = Database::instance() ;
						if( isset( $search_filters[ $search_filter_key ][ $filter_specs[ 'filter_column' ]][ 'db_instance' ]) && !empty( $search_filters[ $search_filter_key ][ $filter_specs[ 'filter_column' ]][ 'db_instance' ])) {
							$db = Database::instance($search_filters[ $search_filter_key ][ $filter_specs[ 'filter_column' ]][ 'db_instance' ]) ;
						}
	//					DOC_Util_Debug::dump( $query->compile($db), false) ;
						$result = $query->execute( $db ) ;

						$ids = array() ;
						$ids[] = -1 ;
						foreach( $result as $row ) {
							$ids[] = $row['id'] ;
						}

						$in = 'in' ;
						if( $replacement_0 === self::NOT_SET ) {
							$in = 'not in' ;
						}
						$_output = $_output->$orm_connectors[ $bool_connector ]( $search_filters[ $search_filter_key ][ $filter_specs[ 'filter_column' ]][ 'id_column' ], $in, $ids ) ;

					} else {
						$_output = $_output->$orm_connectors[ $bool_connector ](DB::expr('1'),'=',DB::expr('1')) ;
					}
						

				} else {
					$column_type = self::get_data_type($_output, $filter_specs[ 'filter_column' ]) ;
					$query_column = self::get_query_column( $_output, $filter_specs[ 'filter_column' ]) ;

					if( self::data_type_is_text( $column_type )) {
						$_output = $_output->$orm_connectors[ $bool_connector ]( $query_column, 'LIKE', "%{$filter_specs[ 'search_val_0' ]}%") ;
					} elseif ( self::data_type_is_date( $column_type )) {
                        $open = $orm_connectors[ $bool_connector ] . "_open";
                        $close = $orm_connectors[ $bool_connector ] . "_close";

                        $_output = $_output->$open();
						$_output = $_output->and_where_open() ;
						if( empty( $filter_specs[ 'search_val_0' ])) {
							$filter_specs[ 'search_val_0' ] = '2000-01-01' ;
						}
						if( empty( $filter_specs[ 'search_val_1' ])) {
							$filter_specs[ 'search_val_1' ] = '2999-12-31' ;
						}

						$date_string_0 = $filter_specs[ 'search_val_0' ];
						$date_string_1 = $filter_specs[ 'search_val_1' ];

						if( preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $date_string_0) == 0 ) {
							$date_string_0 = Date::formatted_time($filter_specs[ 'search_val_0' ] . ' 00:00:00') ;
						}
						if( preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $date_string_1) == 0 ) {
							$date_string_1 = Date::formatted_time($filter_specs[ 'search_val_1' ] . ' 23:59:59') ;
						}

						$_output = $_output->and_where( $query_column, '>=', $date_string_0 ) ;
						$_output = $_output->and_where( $query_column, '<=', $date_string_1 ) ;


						$_output = $_output->and_where_close() ;
                        $_output = $_output->$close();
					} elseif ( self::data_type_is_numeric( $column_type )) {
						$_output = $_output->$orm_connectors[ $bool_connector ]($query_column, self::get_operator( $filter_specs[ 'search_operator' ]), $filter_specs[ 'search_val_0' ]) ;

					} else {
						$_output = $_output->$orm_connectors[ $bool_connector ]($query_column, self::get_operator( $filter_specs[ 'search_operator' ]), $filter_specs[ 'search_val_0']) ;
					}

				}
			}
			$_output = $_output->and_where_close() ;
		}

        return $_output ;
	}


	/**
	 * Looks for the column specified by the arguments and returns the data type.
	 * This will work for either columns in the basic object ($foo, 'bar') or
	 * one-level deep properties ($foo, 'bar->foobar').
	 *
	 * @param ORM $orm_object
	 * @param string $column
	 * @return string
	 */
	public static function get_data_type( $orm_object, $column ) {
		$columns = $orm_object->list_columns() ;
		if( isset( $columns[ $column ])) {
			// some data types are two words (such as "smallint unsigned"), but we only want the first part
			$data_type_description = explode( ' ', $columns[ $column ][ 'data_type' ] ) ;
			return array_shift( $data_type_description ) ;
		}

		// just going to assume for the moment this is describing a child property
		$property_description = explode('->', $column) ;
		if( count( $property_description ) > 1 ) {
			$property = $property_description[0] ;
			$column = $property_description[1] ;
			$property_columns = $orm_object->$property->list_columns() ;

			if( isset( $property_columns[ $column ])) {
				return $property_columns[ $column ][ 'data_type' ] ;
			}
		}

		// no? try checking the custom search filters
		$search_filters = Kohana::$config->load('searchfilters') ;
		$this_filter = $search_filters[ self::get_filter_key() ] ;
//
		if( isset( $this_filter[ $column ]) && isset( $this_filter[ $column ][ 'data_type' ] )) {
			return $this_filter[ $column ][ 'data_type' ] ;
		}


		return 'unknown' ;

	}

	/**
	 * Looks for the column (property) specified by the arguments and returns the
	 * database column. This will work for either columns in the basic object
	 * ($foo, 'bar') or one-level deep properties ($foo, 'bar->foobar').
	 *
	 * @param ORM $orm_object
	 * @param string $column
	 * @return string
	 */
	public static function get_query_column( $orm_object, $column ) {
		$columns = $orm_object->list_columns() ;

		if( isset( $columns[ $column ])) {
			return $orm_object->object_name() . '.' . $column ;
//			return $column ;
		}

		// just going to assume for the moment this is describing a child property
		$property_description = explode('->', $column) ;
		$property = $property_description[0] ;
		$column = $property_description[1] ;
		$property_columns = $orm_object->$property->list_columns() ;

		if( isset( $property_columns[ $column ])) {
			$child_orm = $orm_object->$property ;
			//return $column ;
			return "{$property}.{$column}" ;
		}

		return 'unknown' ;
	}

	/**
	 * Convert a string to one of our pre-defined operators.
	 *
	 * @param string $operator_string
	 * @return string
	 */
	public static function get_operator( $operator_string ) {
		$_output = '=' ;
		$operators = array(
			'lt' => '<',
			'eq' => '=',
			'gt' => '>',
			'ne' => '!=',
			'is' => '=',
			'not' => '!='
		) ;

		if( array_key_exists( $operator_string, $operators )) {
			$_output = $operators[ $operator_string ] ;
		}
		return $_output ;

	}

	/**
	 * Return a default set of filters...basically an array with NULL values for
	 * each element in the array.
	 *
	 * @return array
	 */
	public static function get_default_filter_specs() {
		return array(
			array(
				'filter_column' => NULL,
				'search_val_0' => NULL,
				'search_val_1' => NULL,
				'search_operator' => NULL
			)
		) ;
	}

	/**
	 * Return an array safe for use in a SQL 'IN' clause by adding a default value
	 * to the array if it would otherwise be empty.
	 *
	 * @return array
	 */
	public static function safe_array_for_in_clause( $arr, $substitute = -1 ) {
		$_output = $arr ;

		if( !is_array( $arr ) || count( $arr ) == 0 ) {
			$_output = array( $substitute ) ;
		}

		foreach( $_output as $key => $val ) {
			if( $val === NULL ) {
				$_output[ $key ] = $substitute ;
			}
		}

		return $_output ;
	}

	/**
	 * Breaks up a single string into an array of search terms based on white
	 * space in the original string. Optionally adds SQL wildcards to either end of
	 * each string.
	 *
	 * @param string $str
	 * @param boolean $add_wildcards Use the class constants for clarity
	 * @return array
	 */
	public static function get_search_terms( $str, $add_wildcards = self::WILDCARDS_ON ) {
		$terms = array() ;

		// First look for any quoted strings. Add those to the array
		// and remove them from the original string.
		preg_match_all('/"(.*?)"/', $str, $matches) ;

		if( count( $matches[0] ) > 0 ) {
			$terms = $matches[1] ;
			$str = str_replace( $matches[0], '', $str ) ;
			$str = preg_replace( '/\s+/', ' ', $str ) ;
		}

		if( !empty( $str )) {
			$terms = array_merge( $terms, explode( ' ', $str )) ;
		}


		if( $add_wildcards == self::WILDCARDS_ON ) {
			array_walk( $terms, "Util_Filter::add_sql_wildcards" ) ;
		}

		return $terms ;

	}

	/**
	 * Used by DOC_Util_Filter::get_search_terms in an array_walk to surround a
	 * given string with SQL wildcard characters.
	 *
	 * @param string $item
	 * @param type $key
	 */
	public static function add_sql_wildcards( &$item, $key ) {
		$item = "%{$item}%" ;
	}
}

