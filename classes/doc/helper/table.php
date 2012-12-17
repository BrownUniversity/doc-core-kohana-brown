<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of tablebuilder
 *
 * @author jorrill
 */
class DOC_Helper_Table {

	protected $data ;
	protected $column_specs ;
	protected $table_attrs ;
	protected $context ;

	const TYPE_DATA = 1 ;
	const TYPE_CHECKBOX = 2 ;
	const TYPE_ACTION = 3 ;
	const TYPE_SUPPLEMENTAL = 4 ;

	const CONTEXT_WEB = 1 ;
	const CONTEXT_SPREADSHEET = 2 ;
	const CONTEXT_PDF = 3 ;
	const CONTEXT_DEBUG = 4 ;

	const FORMAT_DATE = 'date' ;
	const FORMAT_LOOKUP = 'lookup' ;
	const FORMAT_LIST = 'list' ;
	const FORMAT_LINK = 'link' ;
	const FORMAT_PHONE = 'phone' ;
	const FORMAT_DOLLARS = 'dollars' ;
	const FORMAT_XLS_DOLLARS = 'xls_dollars' ;
	const FORMAT_TRIM_DECIMAL = 'trim_decimal' ;
	const FORMAT_DATETIME = 'datetime' ;
	const FORMAT_DATETIME_PRECISE = 'datetime_precise' ;
	const FORMAT_DATETIME_SHORT = 'datetime_short' ;
	const FORMAT_XLS_DATETIME = 'xlsdatetime' ;
	const FORMAT_TRUNCATE = 'truncate' ;
	const FORMAT_CUSTOM = 'custom' ;
	const FORMAT_DEFAULT = 'default' ;
	const FORMAT_CALLBACK = 'callback' ;

	public function __construct( $data, $column_specs, $table_attrs = array(), $context = self::CONTEXT_WEB) {
		$this->data = $data ;
		$this->column_specs = $column_specs ;
		$this->table_attrs = $table_attrs ;
		$this->context = $context ;
	}

	/**
	 * Generate the table HTML
	 *
	 * @return string
	 */
	public function render() {
		$_output = array() ;
		$supplemental_column_headers = array() ;

		if( count( $this->data ) > 0 ) {
			$_output[] = "<table" . HTML::attributes($this->table_attrs) . ">" ;

			/*
			 * Table Header
			 */


			$_output[] = "<thead>" ;
			$_output[] = "<tr>" ;

			foreach( $this->column_specs as $col_spec ) {

				if( !isset( $col_spec[ 'context' ]) || $col_spec[ 'context' ] == $this->context ) {

					// TODO: This should only create columns for data that is NOT TYPE_SUPPLEMENTAL.
					// Anything else should be ignored here except for creating a flag to indicate that
					// supplemental data exists.

					$property = '' ;
					if( isset( $col_spec[ 'property' ] )) {
						$property = $col_spec[ 'property' ] ;
					}

					$heading = ucwords( $property ) ;
					if( isset( $col_spec[ 'heading' ])) {
						$heading = $col_spec[ 'heading' ] ;
					}

					$header_attributes = array() ;
					if( isset( $col_spec[ 'attributes' ] ) && is_array( $col_spec[ 'attributes' ] )) {
						$header_attributes = $col_spec[ 'attributes' ] ;
					}



					if( $col_spec[ 'type' ] != self::TYPE_SUPPLEMENTAL ) {

						if( $col_spec[ 'type' ] == self::TYPE_ACTION ) {
							$header_attributes['class'] = '{sorter: false}' ;
						}

						if( $col_spec[ 'type' ] == self::TYPE_CHECKBOX ) {
							$heading = "<input type='checkbox' name='_id' class='check_all' />" ;
							$header_attributes[ 'class' ] = '{sorter: false} checkbox-column' ;
						}

						$_output[] = "<th" . HTML::attributes( $header_attributes ) . ">{$heading}</th>" ;
					} else {
						if( $this->context == self::CONTEXT_SPREADSHEET ) {
							$supplemental_column_headers[] = "<th" . HTML::attributes( $header_attributes ) . ">{$heading}</th>" ;
						} else {
							$supplemental_column_headers = array("<th".HTML::attributes( array('class' => '{sorter: false}')).">&nbsp;</th>") ;
						}
					}
				}
			}

			// output supplemental headers, if they exist

			if( count( $supplemental_column_headers ) > 0 ) {
				$_output[] = implode("\n", $supplemental_column_headers) ;
			}


			$_output[] = "</tr>" ;
			$_output[] = "</thead>" ;

			/*
			 * Table Body
			 */


			$_output[] = "<tbody>" ;

			foreach( $this->data as $object ) {
				$supplemental_data = array() ;
				$_output[] = "<tr>" ;

				// Supplemental data in the body of the table needs to be handled similarly to our headers.
				// As we encounter supplemental data for a row, it should be collected into an array.
				// At the end of the set of rows, we'll then add either a single column (web) with an icon and class to hook into jquery
				// or we'll create a new set of columns (spreadsheet) with the data we've collected.


				foreach( $this->column_specs as $col_spec ) {
					if( !isset( $col_spec[ 'context' ]) || $col_spec[ 'context' ] == $this->context ) {
						$td_attrs = array() ;


						// Note that both TYPE_SUPPLEMENTAL and TYPE_DATA will need much of the same processing,
						// but only TYPE_DATA should be immediately dumped into a column.

						if( $col_spec[ 'type' ] == self::TYPE_DATA || $col_spec[ 'type' ] == self::TYPE_SUPPLEMENTAL ) {
							$value = $this->generate_content($object, $col_spec[ 'property' ]) ;

							if( isset( $col_spec[ 'format' ]) && is_array( $col_spec[ 'format' ]) && count( $col_spec[ 'format' ]) > 0 ) {
								switch ( $col_spec[ 'format' ][ 'type' ]) {
									case self::FORMAT_DATE:
										$value = $this->format_datetime( $value, 'm/j/Y' ) ;
										$td_attrs[ 'class' ] = 'date' ;
										break;

									case self::FORMAT_LOOKUP:
										$value = $this->format_lookup( $value, $col_spec[ 'format' ][ 'lookup' ]) ;
										break ;

									case self::FORMAT_LIST:

										$root_key = '' ;
										if( isset( $col_spec[ 'format' ][ 'root' ])) {
											$root_key = $col_spec[ 'format' ][ 'root' ] ;
										}
										$order_by = '' ;
										if( isset( $col_spec[ 'format' ][ 'order_by' ])) {
											$order_by = $col_spec[ 'format' ][ 'order_by' ] ;
										}
										$empty_content = '--' ;
										if( isset( $col_spec[ 'format' ][ 'empty_content' ])) {
											$empty_content = $col_spec[ 'format' ][ 'empty_content' ] ;
										}
										$separator = '<br />' ;
										if( isset( $col_spec[ 'format' ][ 'separator' ])) {
											$separator = $col_spec[ 'format' ][ 'separator' ] ;
										}

										$value = $this->format_list($object, $root_key, $col_spec[ 'format' ][ 'relation_name' ], $col_spec[ 'format' ][ 'property_name' ], $order_by, $empty_content, $separator) ;
										break ;

									case self::FORMAT_LINK:
										$value = $this->format_link( $object, $col_spec[ 'format' ][ 'text' ], $col_spec[ 'format' ][ 'url' ] ) ;
										break ;

									case self::FORMAT_PHONE:
										$value = $this->format_phone( $value ) ;
										break ;

									case self::FORMAT_DOLLARS:
										$value = $this->format_dollars($value, TRUE) ;
										$td_attrs[ 'class' ] = 'dollars' ;
										break ;

									case self::FORMAT_TRIM_DECIMAL:
										$value = rtrim($value,'.0') ;
										break ;

									case self::FORMAT_XLS_DOLLARS:
										$value = $this->format_dollars($value, FALSE) ;
										$td_attrs[ 'class' ] = 'dollars' ;
										break ;

									case self::FORMAT_DATETIME:
										$value = $this->format_datetime( $value, 'M j, Y g:i A' ) ;
										$td_attrs[ 'class' ] = 'datetime' ;
										break ;

									case self::FORMAT_DATETIME_SHORT:
										$value = $this->format_datetime( $value, 'm/d/y g:i A' ) ;
										$td_attrs[ 'class' ] = 'datetime' ;
										break ;

									case self::FORMAT_DATETIME_PRECISE:
										$value = $this->format_datetime( $value, 'M j, Y g:i:s A' ) ;
										$td_attrs[ 'class' ] = 'datetime' ;
										break ;

									case self::FORMAT_XLS_DATETIME:
										$value = $this->format_datetime( $value, 'Y-m-d H:i' ) ;
										$td_attrs[ 'class' ] = 'datetime' ;
										break ;

									case self::FORMAT_TRUNCATE:
										$chars = 80 ;
										if( isset( $col_spec[ 'format' ][ 'chars' ])) {
											$chars = $col_spec[ 'format' ][ 'chars' ] ;
										}
										$value = $this->format_truncate($value, $chars) ;
										break ;

									case self::FORMAT_CUSTOM:
										$value = $this->parse_string( $object, $col_spec[ 'format' ][ 'output' ] ) ;
										break ;

									case self::FORMAT_DEFAULT:
										if( is_null( $value ) || $value == '' ) {
											$value = $col_spec[ 'format' ][ 'output' ] ;
										}
										break ;
									case self::FORMAT_CALLBACK:
										$obj = $object ;
										if( isset( $col_spec[ 'format' ][ 'obj' ])) {
											$obj = $col_spec[ 'format' ][ 'obj' ] ;
											if( strpos($obj, '{') !== FALSE ) {
												$obj = $this->parse_string($object, $obj) ;
											}
										}

										$args = array() ;
										if( isset( $col_spec[ 'format' ][ 'args' ])) {
											$args = $col_spec[ 'format' ][ 'args' ] ;
											if( count( $args ) > 0 ) {
												foreach( $args as $key => $arg ) {
													if( strpos( $arg, '{' ) !== FALSE ) {
														$args[$key] = $this->parse_string($object, $arg, FALSE) ;
													}
												}
											}

											if( !is_object($obj) || get_class( $obj ) != get_class( $object )) {
												$args[] = $object ;
											}
										}

										$value = call_user_func_array(array($obj, $col_spec[ 'format' ][ 'method' ]), $args ) ;


									default:
										break;
								}
							}

							if( isset( $col_spec[ 'class' ])) {
								if( is_array( $col_spec[ 'class' ])) {
									$key = $value ;
									if( isset( $col_spec[ 'class' ][ 'key' ])) {
										$key = $this->parse_string($object, $col_spec[ 'class' ][ 'key' ]) ;
									}
									if(array_key_exists( $key , $col_spec[ 'class' ][ 'classes' ])) {
										$td_attrs[ 'class' ] = $col_spec[ 'class' ][ 'classes' ][ $key ] ;
									}
								} else {
									$td_attrs[ 'class' ] = $col_spec[ 'class' ] ;
								}
							}

						} elseif ( $col_spec[ 'type' ] == self::TYPE_ACTION ) {
							$default_id = NULL ;
							if( method_exists( $object, 'pk')) {
								$default_id = $object->pk() ;
							}

							$actions = array() ;
							foreach( $col_spec[ 'actions' ] as $action ) {
								$test_result = TRUE ;
								$add_action = TRUE ;

								$id = $default_id ;
								if( isset( $action[ 'id_key' ]) && !empty( $action[ 'id_key' ])) {
									$id = $this->generate_content($object, $action[ 'id_key' ]) ;
								}

								$action_class = '' ;
								if( isset( $action[ 'class' ]) && !empty( $action[ 'class' ])) {
									$action_class = $action[ 'class' ] ;
								}
								$url_fragment = $this->parse_string($object, $action[ 'url_fragment' ]) ;
								$action_str = "<a href='".Kohana::$base_url."{$url_fragment}{$id}' class='{$action_class}'>{$action[ 'name' ]}</a>" ;

								if( isset( $action[ 'conditional' ])) {
									$test = "return " . $this->parse_string($object, $action[ 'conditional' ]) . ';' ;
									$test_result = eval( $test ) ;

									if( $test_result == FALSE ) {
										$action_str = '<span class="action-disabled">'.strip_tags($action_str).'</span>' ;
									}
								}

								if( $test_result == FALSE && ( isset( $action[ 'onfail' ]) && $action[ 'onfail' ] == 'hide' )) {
									$add_action = FALSE ;
								}

								if( $add_action ) {
									$actions[] = $action_str ;
								}



							}
							$value = implode( '&nbsp;|&nbsp;', $actions ) ;
							$td_attrs[ 'class' ] = 'actions' ;
						} elseif( $col_spec[ 'type' ] == self::TYPE_CHECKBOX ) {
							$default_id = $object->pk() ;
							$id = $default_id ;
							if( isset( $col_spec[ 'id_key' ]) && !empty( $col_spec[ 'id_key' ])) {
								$id = $this->generate_content( $object, $col_spec[ 'id_key']) ;
							}
							$checkbox = "<input type='checkbox' name='id[]' value='{$id}' />" ;
							if( isset( $col_spec[ 'checkbox' ] )) {
								$checkbox = $col_spec[ 'checkbox' ] ;
							}
							$value = $this->parse_string( $object, $checkbox ) ;
						}

						if( $col_spec[ 'type' ] == self::TYPE_SUPPLEMENTAL ) {
							// TODO: Build an array structure containing the header and value pairs.
							//			We'll turn that into a JSON string and assign it to the "data-supplement" property
							//			of our supplemental data column.
							//
							//			This same structure should also store other attributes created in this loop
							//			so that we can pass them to the spreadsheet (or web?) for proper formatting.

							$heading = ucwords( $col_spec[ 'property' ]) ;
							if( isset( $col_spec[ 'heading' ])) {
								$heading = $col_spec[ 'heading' ] ;
							}

							$supplemental_data[] = array(
									'heading' => htmlentities($heading, ENT_QUOTES),
									'value' => htmlentities($value, ENT_QUOTES),
									'td_attrs' => $td_attrs
							) ;

							$value ;
						} else {
							$_output[] = "<td".HTML::attributes( $td_attrs ).">{$value}</td>" ;
						}


					}
				}

				if( count( $supplemental_data ) > 0 ) {
					if( $this->context == self::CONTEXT_WEB ) {
						$supplemental_data_json = json_encode( $supplemental_data ) ;
						$_output[] = "<td class='supplement-column' data-supplement='{$supplemental_data_json}'><span class='supplement-view ui-icon ui-icon-search ui-icon-right ui-icon-clickable'></span></td>" ;

					} else {
						foreach( $supplemental_data as $supplement_col ) {
							$_output[] = "<td".HTML::attributes( $supplement_col['td_attrs'] ).">{$supplement_col['value']}</td>" ;
						}
					}
				}


				$_output[] = "</tr>" ;
			}

			$_output[] = "</tbody>" ;

			$_output[] = "</table>" ;

		} else {
			$_output[] = '<div class="no-data">Nothing to display</div>' ;
		}



		return trim(implode("", $_output)) ;
	}


	protected function format_list( $object, $root_key, $relation_name, $property_name, $order_by = NULL, $empty_content = '--', $separator = '<br />') {
		$content = array() ;

		if( empty( $root_key )) {
			$root = $object ;
		} else {
			$root = $this->generate_content( $object, $root_key ) ;
		}

		if( empty( $order_by )) {
			$order_by = $property_name ;
		}

		$data = $root->$relation_name->order_by($order_by)->find_all() ;

		if( $data->count() > 0 ) {
			foreach( $data as $item ) {
				$content[] = $item->$property_name ;
			}
		} else {
			$content[] = "<em>{$empty_content}</em>" ;
		}

		return implode( $separator, $content ) ;
	}

	protected function format_link( $object, $link_text, $link_url ) {
		$_output = '' ;
		$url = $this->parse_string($object, $link_url) ;
		$text = $this->parse_string($object, $link_text) ;

		if( !empty( $url )) {
			$_output = "<a href='{$url}' target='_blank'>{$text}</a>" ;
		} else {
			$_output = $text ;
		}
		return $_output ;
	}


	protected function parse_string( $object, $parseable_string, $return_as_string = TRUE ) {
		$_output = $parseable_string ;
		preg_match_all('/\{(.+?)\}/', $parseable_string, $matches) ;

		if( count( $matches ) > 0 ) {
			if( count( $matches[1] ) > 1 || $return_as_string ) {
				foreach( $matches[1] as $match ) {
					$_output = str_replace('{'.$match.'}', $this->generate_content($object, $match), $_output) ;
				}
			} else {
				$match = $matches[1][0] ;
				$_output = $this->generate_content($object, $match) ;
			}
		}



		return $_output ;	}

	protected function format_lookup( $value, $lookup ) {
		$_output = '--' ;
		if( isset( $lookup[ $value ])) {
			$_output = $lookup[ $value ] ;
		}
		return $_output ; ;
	}

	protected function format_datetime( $value, $format = 'm/j/Y') {
		if( !empty( $value )) {
			$value = date($format, strtotime( $value )) ;
		}
		return $value ;
	}

	protected function format_dollars( $value, $include_dollar_sign = TRUE ) {
		$dollar_sign = '' ;
		if( $include_dollar_sign ) {
			$dollar_sign = '$' ;
		}
		if( $value != NULL && $value != '' ) {
			$value = $dollar_sign . number_format( $value, 2 ) ;
		}
		return $value ;
	}
	protected function format_phone( $value ) {
		// TODO: this duplicates code I've put in a "Util_Massage" class in the SOURCE app. I'm putting the same code
		// here to avoid unnecessary dependencies, but it may be desirable to pull that code into the main DOC library at some point.

		if( $value != NULL && $value != '' ) {
			$pattern = '' ;
			$replacement = '' ;
			switch (strlen($value)) {
				case 5:
					$pattern = '/(\d{1})(\d{4})/' ;
					$replacement = '$1-$2' ;
					break;

				case 7:
					$pattern = '/(\d{3})(\d{4})/' ;
					$replacement = '$1-$2' ;
					break;

				case 10:
					$pattern = '/(\d{3})(\d{3})(\d{4})/' ;
					$replacement = '($1) $2-$3' ;
					break;

				case 11:
					$pattern = '/(\d{1})(\d{3})(\d{3})(\d{4})/' ;
					$replacement = '$1 ($2) $3-$4' ;
					break;

				default:
					break;
			}

			if( !empty( $pattern )) {
				$value = preg_replace($pattern, $replacement, $value) ;
			}

		}
		return $value ;
	}

	protected function format_truncate( $value, $chars = 80 ) {
		if( strlen( $value ) > $chars ) {
			$value = substr(strip_tags($value), 0, ($chars - 3)) . '...' ;
		}
		return $value ;
	}

	/**
	 * Given an arbitrary object and string representing a chain of object properties,
	 * return the appropriate data.
	 *
	 * @param object $data_root
	 * @param string $key
	 * @return string
	 */
	protected function generate_content( $data_root, $key ) {
		$key_array = explode('->', $key) ;

        if( property_exists($data_root, $key) || $data_root->supports_property( $key_array[0] )) {
			$_output = @$data_root->{$key_array[0]};
		} elseif (method_exists($data_root, $key_array[0])) {
			$_output = @$data_root->{$key_array[0]}() ;
		} else {
			$_output = 'ERR: unknown data source' ;
		}

		if( count( $key_array ) > 1 ) {
			$key = preg_replace("/^{$key_array[0]}->/", '', $key) ;
			return $this->generate_content( $_output, $key ) ;
		}

		return $_output ;

	}




}
