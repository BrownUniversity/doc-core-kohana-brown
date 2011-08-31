<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Supports the creation and use of a standard single-line filter interface for
 * tabular data.
 *
 * @author jorrill
 */
class DOC_Util_Filter {
	const FILTER_SUFFIX = '_search_filter' ;
	
	/**
	 * Get the key we are using for the filter data on the current page (URI).
	 * 
	 * @return string
	 */
	public static function get_filter_key() {
		return Request::detect_uri() . self::FILTER_SUFFIX ;
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
	 * Modifies and returns the passed in ORM object, adding in search filters
	 * based on the current parameters. Defaults to data in the session, but
	 * uses POST data if present.
	 * 
	 * @param ORM $orm_base
	 * @return ORM 
	 */
	public static function add_filter($orm_base) {
		$_output = $orm_base ;
		$filter_key = self::get_filter_key() ;
		$filter_specs = NULL ;
		$session = Session::instance( 'database' ) ;
		$search_filters = Kohana::$config->load('searchfilters') ;

		// check for a reset...
		if( isset( $_POST[ 'setFilter' ]) && $_POST[ 'setFilter' ] == 'Clear' ) {
			$session->delete( $filter_key ) ;
			return $_output ;
		}
	
		// start with session data, if it exists
		$filter_specs = $session->get( $filter_key ) ;

		// do we have a new filter request?
		if( isset( $_POST[ 'setFilter' ]) && $_POST[ 'setFilter' ] == 'Search' ) {
			$filter_specs = array(
				'filter_column' => $_POST[ 'filter_column' ],
				'search_val_0' => $_POST[ 'search_val_0' ],
				'search_val_1' => NULL,
				'search_operator' => NULL
			) ;
			if( isset( $_POST[ 'search_val_1' ])) {
				$filter_specs[ 'search_val_1' ] = $_POST[ 'search_val_1' ] ;
			}
			if( isset( $_POST[ 'search_operator' ])) {
				$filter_specs[ 'search_operator' ] = $_POST[ 'search_operator' ] ;
			}

			$session->set( $filter_key, $filter_specs ) ;
			$session->write() ;
		} 	

		if( $filter_specs != NULL ) {
			
			if( isset( $search_filters[ $filter_key ] ) && isset( $search_filters[ $filter_key ][ $filter_specs[ 'filter_column' ]] )) {

				$replacement_0 = $filter_specs[ 'search_val_0' ] ;
				$replacement_1 = $filter_specs[ 'search_val_1' ] ;
				$operator = self::get_operator( $filter_specs[ 'search_operator' ]) ;
				
				// run the query to get the list of IDs, then add to the where clause
				$sql = $search_filters[ $filter_key ][ $filter_specs[ 'filter_column' ]][ 'sql' ] ;	

				$sql = str_replace(	array( '{operator}' ), array( $operator ), $sql ) ;
				$query = DB::query( Database::SELECT, $sql ) ;

				if( isset( $search_filters[ $filter_key ][ $filter_specs[ 'filter_column' ]][ 'data_type' ]) && $search_filters[ $filter_key ][ $filter_specs[ 'filter_column' ]][ 'data_type' ] == 'date') {
					if( empty( $replacement_0 )) {
						$replacement_0 = '2000-01-01 00:00:00' ;
					} else {
						$replacement_0 = Date::formatted_time( $replacement_0 . ' 00:00:00' ) ;
					}
					
					if( empty( $replacement_1 )) {
						$replacement_1 = '2999-12-31 23:59:59' ; // Y3K bug, FTW!
					} else {
						$replacement_1 = Date::formatted_time( $replacement_1 . ' 00:00:00' ) ;
					}
					
					$query->parameters( array(
						':search_val_0' => $replacement_0,
						':search_val_1' => $replacement_1,
					)) ;
				} elseif(isset( $search_filters[ $filter_key ][ $filter_specs[ 'filter_column' ]][ 'data_type' ]) && $search_filters[ $filter_key ][ $filter_specs[ 'filter_column' ]][ 'data_type' ] == 'numeric') {
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
//Util_Debug::dump((string) $query, false ) ;

				

				$result = $query->execute() ;
				$ids = array() ;
				$ids[] = -1 ;
				foreach( $result as $row ) {
					$ids[] = $row['id'] ;
				}

				$_output = $_output->where( $search_filters[ $filter_key ][ $filter_specs[ 'filter_column' ]][ 'id_column' ], 'in', $ids ) ;


				
			} else {
				$column_type = self::get_data_type($_output, $filter_specs[ 'filter_column' ]) ;
				$query_column = self::get_query_column( $_output, $filter_specs[ 'filter_column' ]) ;

				if( self::data_type_is_text( $column_type )) {
					$_output = $_output->where( $query_column, 'LIKE', "%{$filter_specs[ 'search_val_0' ]}%") ;
				} elseif ( self::data_type_is_date( $column_type )) {
					if( !empty( $filter_specs[ 'search_val_0' ])) {
						$_output = $_output->where($query_column, '>=', Date::formatted_time($filter_specs[ 'search_val_0' ] . ' 00:00:00')) ;
					}
					if( !empty( $filter_specs[ 'search_val_1' ])) {
						$_output = $_output->where($query_column, '<=', Date::formatted_time($filter_specs[ 'search_val_1' ] . ' 23:59:59'))  ;
					}
				} elseif ( self::data_type_is_numeric( $column_type )) {
					$_output = $_output->where($query_column, self::get_operator( $filter_specs[ 'search_operator' ]), $filter_specs[ 'search_val_0' ]) ;

				} else {
					$_output = $_output->where($query_column, '=', $filter_specs[ 'search_val_0']) ;
				}			
				
			}
			
			
			
			
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
			return $columns[ $column ][ 'data_type' ] ;
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
			return $orm_object->table_name() . '.' . $column ;
//			return $column ;
		}
		
		// just going to assume for the moment this is describing a child property
		$property_description = explode('->', $column) ;
		$property = $property_description[0] ;
		$column = $property_description[1] ;
		$property_columns = $orm_object->$property->list_columns() ;
		
		if( isset( $property_columns[ $column ])) {
			$child_orm = $orm_object->$property ;
			return $column ;
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
			'ne' => '!='
		) ;
		
		if( array_key_exists( $operator_string, $operators )) {
			$_output = $operators[ $operator_string ] ;
		}
		return $_output ;

	}
}


?>
