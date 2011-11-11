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

	const TYPE_DATA = 1 ;
	const TYPE_CHECKBOX = 2 ;
	const TYPE_ACTION = 3 ;


	public function __construct( $data, $column_specs, $table_attrs = array()) {
		$this->data = $data ;
		$this->column_specs = $column_specs ;
		$this->table_attrs = $table_attrs ;
	}

	/**
	 * Generate the table HTML
	 *
	 * @return string
	 */
	public function render() {
		$_output = array() ;

		if( count( $this->data ) > 0 ) {
			$_output[] = "<table " . HTML::attributes($this->table_attrs) . ">" ;

			/*
			 * Table Header
			 */


			$_output[] = "<thead>" ;
			$_output[] = "<tr>" ;

			foreach( $this->column_specs as $col_spec ) {

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

				if( $col_spec[ 'type' ] == self::TYPE_ACTION ) {
					$header_attributes['class'] = '{sorter: false}' ;
				}

				if( $col_spec[ 'type' ] == self::TYPE_CHECKBOX ) {
					$heading = "<input type='checkbox' name='_id' id='check_all' />" ;
					$header_attributes[ 'class' ] = '{sorter: false}' ;
				}

				$_output[] = "<th " . HTML::attributes( $header_attributes ) . ">{$heading}</th>" ;
			}

			$_output[] = "</tr>" ;
			$_output[] = "</thead>" ;

			/*
			 * Table Body
			 */


			$_output[] = "<tbody>" ;

			foreach( $this->data as $object ) {
				$_output[] = "<tr>" ;
				foreach( $this->column_specs as $col_spec ) {
					$td_attrs = array() ;
					if( $col_spec[ 'type' ] == self::TYPE_DATA ) {
						$value = $this->generate_content($object, $col_spec[ 'property' ]) ;

						if( isset( $col_spec[ 'format' ]) && is_array( $col_spec[ 'format' ]) && count( $col_spec[ 'format' ]) > 0 ) {
							switch ( $col_spec[ 'format' ][ 'type' ]) {
								case 'date':
									$value = $this->format_datetime( $value, 'm/j/Y' ) ;
									$td_attrs[ 'class' ] = 'date' ;
									break;

								case 'lookup':
									$value = $this->format_lookup( $value, $col_spec[ 'format' ][ 'lookup' ]) ;
									break ;

								case 'list':

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
										$order_by = $col_spec[ 'format' ][ 'empty_content' ] ;
									}
									$separator = '<br />' ;
									if( isset( $col_spec[ 'format' ][ 'separator' ])) {
										$order_by = $col_spec[ 'format' ][ 'separator' ] ;
									}

									$value = $this->format_list($object, $root_key, $col_spec[ 'format' ][ 'relation_name' ], $col_spec[ 'format' ][ 'property_name' ], $order_by, $empty_content, $separator) ;
									break ;

								case 'link':
									$value = $this->format_link( $object, $col_spec[ 'format' ][ 'text' ], $col_spec[ 'format' ][ 'url' ] ) ;
									break ;

								case 'phone':
									$value = $this->format_phone( $value ) ;
									break ;

								case 'dollars':
									$value = $this->format_dollars($value, TRUE) ;
									$td_attrs[ 'class' ] = 'dollars' ;
									break ;

								case 'xls_dollars':
									$value = $this->format_dollars($value, FALSE) ;
									$td_attrs[ 'class' ] = 'dollars' ;
									break ;

								case 'datetime':
									$value = $this->format_datetime( $value, 'M j, Y g:i A' ) ;
									$td_attrs[ 'class' ] = 'datetime' ;
									break ;

								case 'xlsdatetime':
									$value = $this->format_datetime( $value, 'Y-m-d H:i' ) ;
									$td_attrs[ 'class' ] = 'datetime' ;
									break ;

								case 'truncate':
									$chars = 80 ;
									if( isset( $col_spec[ 'format' ][ 'chars' ])) {
										$chars = $col_spec[ 'format' ][ 'chars' ] ;
									}
									$value = $this->format_truncate($value, $chars) ;
									break ;

								case 'custom':
									$value = $this->parse_string( $object, $col_spec[ 'format' ][ 'output' ] ) ;
									break ;



								default:
									break;
							}
						}
					} elseif ( $col_spec[ 'type' ] == self::TYPE_ACTION ) {
						$default_id = $object->pk() ;

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
							$action_str = "<a href='".Kohana::$base_url."{$action[ 'url_fragment' ]}{$id}' class='{$action_class}'>{$action[ 'name' ]}</a>" ;

							if( isset( $action[ 'conditional' ])) {
								$test = "return " . $this->parse_string($object, $action[ 'conditional' ]) . ';' ;
								$test_result = eval( $test ) ;

								if( $test_result == FALSE ) {
									$action_str = strip_tags($action_str) ;
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
						$checkbox = "<input type='checkbox' name='id[]' value='{id}' />" ;
						if( isset( $col_spec[ 'checkbox' ] )) {
							$checkbox = $col_spec[ 'checkbox' ] ;
						}
						$value = $this->parse_string( $object, $checkbox ) ;
					}



					$_output[] = "<td ".HTML::attributes( $td_attrs ).">{$value}</td>" ;
				}
				$_output[] = "</tr>" ;
			}

			$_output[] = "</tbody>" ;

			$_output[] = "</table>" ;

		} else {
			$_output[] = '<div class="no-data">Nothing to display</div>' ;
		}



		return implode("\n", $_output) ;
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
		$url = $this->parse_string($link_url, $object) ;
		$text = $this->parse_string($link_text, $object) ;
		if( !empty( $url )) {
			$_output = "<a href='{$url}' target='_blank'>{$text}</a>" ;
		} else {
			$_output = $text ;
		}
		return $_output ;
	}


	protected function parse_string( $object, $parseable_string ) {
		$_output = '' ;
		preg_match_all('/\{(.+?)\}/', $parseable_string, $matches) ;

		if( count( $matches ) > 0 ) {
			$_output = $parseable_string ;
			foreach( $matches[1] as $match ) {
				$_output = str_replace('{'.$match.'}', $this->generate_content($object, $match), $_output) ;
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

	protected function format_datetime( $value, $format ) {
		if( !empty( $value )) {
			$value = date('m/j/Y', strtotime( $value )) ;
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
	 * @param boolean $treat_as_object
	 * @return string
	 */
	protected function generate_content( $data_root, $key, $treat_as_object = FALSE ) {
		$key_array = explode('->', $key) ;

        if( $data_root->supports_property( $key_array[0] )) {
			$_output = @$data_root->{$key_array[0]};
		} elseif (method_exists($data_root, $key_array[0])) {
			$_output = @$data_root->{$key_array[0]}() ;
		} else {
			$_output = 'ERR: unknown data source' ;
		}

		if( count( $key_array ) > 1 ) {
			$key = preg_replace("/^{$key_array[0]}->/", '', $key) ;
			return $this->generate_content( $_output, $key, TRUE ) ;
		}

		return $_output ;

	}


}

?>
