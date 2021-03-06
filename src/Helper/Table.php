<?php
namespace BrownUniversity\DOC\Helper ;
use BrownUniversity\DOC\Helper\Json;
use BrownUniversity\DOC\Helper\Text;
use Kohana\Cache\Cache;
use Kohana\HTML;
use Kohana\Kohana;
use Kohana\Profiler;

/**
 * Class to render tabular data with formatting, actions and context-sensitive
 * output.
 *
 * @author jorrill
 */
class Table {

	protected $data ;
	protected $column_specs ;
	protected $table_attrs ;
	protected $context ;
	protected $render_tags ;

	const TYPE_DATA = 1 ;
	const TYPE_CHECKBOX = 2 ;
	const TYPE_ACTION = 3 ;
	const TYPE_SUPPLEMENTAL = 4 ;
	const TYPE_ROW_DATA = 5 ;

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
	const FORMAT_DATETIME_WITH_DAY = 'datetime_with_day' ;
	const FORMAT_DATETIME_PRECISE = 'datetime_precise' ;
	const FORMAT_DATETIME_SHORT = 'datetime_short' ;
	const FORMAT_TIME_12 = 'time-12' ;
	const FORMAT_TIME_24 = 'time-24' ;
	const FORMAT_TIME_12_PRECISE = 'time-12-precise' ;
	const FORMAT_TIME_24_PRECISE = 'time-24-precise' ;
	const FORMAT_XLS_DATETIME = 'xlsdatetime' ;
	const FORMAT_XLS_TEXT = 'xls_text' ;
	const FORMAT_TRUNCATE = 'truncate' ;
	const FORMAT_CUSTOM = 'custom' ;
	const FORMAT_DEFAULT = 'default' ;
	const FORMAT_CALLBACK = 'callback' ;

	const RENDER_AS_TABLE = 'table' ;
	const RENDER_AS_GRID = 'grid' ;

	/**
	 * Table constructor.
	 *
	 * @param mixed $data
	 * @param array $column_specs
	 * @param array $table_attrs
	 * @param int   $context
	 */
	public function __construct( $data, $column_specs, $table_attrs = array(), $context = self::CONTEXT_WEB) {

		$this->data = $data ;
		$this->column_specs = $column_specs ;
		$this->table_attrs = $table_attrs ;
		$this->context = $context ;

		$this->render_tags = array(
			self::RENDER_AS_GRID => array(
				'container' => 'div',
				'row' => 'div',
				'cell' => 'div'
			),
			self::RENDER_AS_TABLE => array(
				'container' => 'table',
				'row' => 'tr',
				'cell' => 'td'
			)
		) ;
	}

	/**
	 * Generate the table HTML
	 *
	 * @return string
	 */
	public function render($render_as = self::RENDER_AS_TABLE) {
		
		if( Kohana::$profiling === TRUE ) {
			$bm = Profiler::start(__CLASS__,__METHOD__) ;
		}
		
		$_output = '' ;
		$supplemental_column_headers = '' ;

		if( count( $this->data ) > 0 ) {
			$_output .= "<{$this->render_tags[$render_as]['container']}" . HTML::attributes($this->table_attrs) . ">" ;

			/*
			 * Table Header, not necessary for grid render
			 */

			if( $render_as == self::RENDER_AS_TABLE ) {
				$_output .= "<thead>" ;
				$_output .= "<tr>" ;

				foreach( $this->column_specs as $key => $col_spec ) {

					// set the conditional property for the column, executing the test if it exists
					if( isset( $col_spec[ 'conditional'])) {
						$test = "return " . $col_spec[ 'conditional' ] . ';' ;
						$col_spec['conditional'] = eval( $test ) ;
					} else {
						$col_spec['conditional'] = TRUE ;
					}
					$this->column_specs[ $key ][ 'conditional' ] = $col_spec[ 'conditional' ] ;
					
					
					if( (!isset( $col_spec[ 'context' ]) || $col_spec[ 'context' ] == $this->context) && (!isset( $col_spec[ 'render_type' ]) || $col_spec[ 'render_type' ] == $render_as) && $col_spec['conditional'] == TRUE ) {

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



						if( $col_spec[ 'type' ] != self::TYPE_SUPPLEMENTAL && $col_spec[ 'type' ] != self::TYPE_ROW_DATA ) {

							if( $col_spec[ 'type' ] == self::TYPE_ACTION ) {
								$header_attributes['class'] = '{sorter: false}' ;
							}

							if( $col_spec[ 'type' ] == self::TYPE_CHECKBOX ) {
								$heading = "<input type='checkbox' name='_id' class='check_all' />" ;
								$header_attributes[ 'class' ] = '{sorter: false} checkbox-column' ;
							}

							$_output .= "<th" . HTML::attributes( $header_attributes ) . ">{$heading}</th>" ;
						} else {
							if( $col_spec[ 'type' ] == self::TYPE_SUPPLEMENTAL ) {
								if( $this->context == self::CONTEXT_SPREADSHEET ) {
									$supplemental_column_headers .= "<th" . HTML::attributes( $header_attributes ) . ">{$heading}</th>" ;
								} else {
									$supplemental_column_headers = "<th".HTML::attributes( array('class' => '{sorter: false}')).">&nbsp;</th>" ;
								}
							}
						}
					}
				}

				// output supplemental headers, if they exist
				$_output .= $supplemental_column_headers ;

				$_output .= "</tr>" ;
				$_output .= "</thead>" ;
			}



			/*
			 * Table Body
			 */

			if( $render_as == self::RENDER_AS_TABLE ) {
				$_output .= "<tbody>" ;
			}


			foreach( $this->data as $object ) {
				set_time_limit(30) ;
				$supplemental_data = array() ;
				$row_data = array() ;

				// Supplemental data in the body of the table needs to be handled similarly to our headers.
				// As we encounter supplemental data for a row, it should be collected into an array.
				// At the end of the set of rows, we'll then add either a single column (web) with an icon and class to hook into jquery
				// or we'll create a new set of columns (spreadsheet) with the data we've collected.

				$row_cells = '' ;
				foreach( $this->column_specs as $col_spec ) {
					if( (!isset( $col_spec[ 'context' ]) || $col_spec[ 'context' ] == $this->context)  && (!isset( $col_spec[ 'render_type' ]) || $col_spec[ 'render_type' ] == $render_as) && ( !isset( $col_spec[ 'conditional' ]) || $col_spec[ 'conditional' ] == TRUE)) {
						$td_attrs = array() ;


						// Note that TYPE_SUPPLEMENTAL, TYPE_ROW_DATA and TYPE_DATA will need much of the same processing,
						// but only TYPE_DATA should be immediately dumped into a column.

						if( $col_spec[ 'type' ] == self::TYPE_DATA || $col_spec[ 'type' ] == self::TYPE_SUPPLEMENTAL || $col_spec[ 'type' ] == self::TYPE_ROW_DATA ) {
							$value = $this->generate_content($object, $col_spec[ 'property' ]) ;
							$td_attrs[ 'class' ][] = 'col-' . preg_replace('/[^A-Za-z0-9]+/', '-', $col_spec[ 'property' ]) ;

							if( isset( $col_spec[ 'format' ]) && is_array( $col_spec[ 'format' ]) && count( $col_spec[ 'format' ]) > 0 ) {
								switch ( $col_spec[ 'format' ][ 'type' ]) {
									case self::FORMAT_DATE:
										$value = $this->format_datetime( $value, 'm/d/Y' ) ;
										$td_attrs[ 'class' ][] = 'date' ;
										break;

									case self::FORMAT_TIME_12:
										$value = $this->format_datetime( $value, 'h:i A' ) ;
										$td_attrs[ 'class' ][] = 'time' ;
										break ;
									case self::FORMAT_TIME_24:
										$value = $this->format_datetime( $value, 'H:i' ) ;
										$td_attrs[ 'class' ][] = 'time' ;
										break ;
									case self::FORMAT_TIME_12_PRECISE:
										$value = $this->format_datetime( $value, 'h:i:s A' ) ;
										$td_attrs[ 'class' ][] = 'time' ;
										break ;
									case self::FORMAT_TIME_24_PRECISE:
										$value = $this->format_datetime( $value, 'H:i:s' ) ;
										$td_attrs[ 'class' ][] = 'time' ;
										break ;

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
										$cache_lifetime = 0 ;
										if( isset( $col_spec[ 'format' ][ 'cache_lifetime' ])) {
											$cache_lifetime = $col_spec[ 'format' ][ 'cache_lifetime' ] ;
										}

										$value = $this->format_list(
												$object,
												$root_key,
												$col_spec[ 'format' ][ 'relation_name' ],
												$col_spec[ 'format' ][ 'property_name' ],
												$order_by,
												$empty_content,
												$separator,
												$cache_lifetime) ;
										break ;

									case self::FORMAT_LINK:
										$value = $this->format_link( $object, $col_spec[ 'format' ][ 'text' ], $col_spec[ 'format' ][ 'url' ] ) ;
										break ;

									case self::FORMAT_PHONE:
										$value = $this->format_phone( $value ) ;
										break ;

									case self::FORMAT_DOLLARS:
										$value = $this->format_dollars($value, TRUE) ;
										$td_attrs[ 'class' ][] = 'dollars' ;
										break ;
										
									case self::FORMAT_XLS_TEXT:
										$td_attrs[ 'class' ][] = 'xls-text' ;
										break ;

									case self::FORMAT_TRIM_DECIMAL:
										$value = rtrim($value,'.0') ;
										break ;

									case self::FORMAT_XLS_DOLLARS:
										$value = $this->format_dollars($value, FALSE) ;
										$td_attrs[ 'class' ][] = 'dollars' ;
										break ;

									case self::FORMAT_DATETIME:
										$value = $this->format_datetime( $value, 'M j, Y g:i A' ) ;
										$td_attrs[ 'class' ][] = 'datetime' ;
										break ;

									case self::FORMAT_DATETIME_WITH_DAY:
										$value = $this->format_datetime( $value, 'D M j, Y g:i A' ) ;
										$td_attrs[ 'class' ][] = 'datetime' ;
										break ;

									case self::FORMAT_DATETIME_SHORT:
										$value = $this->format_datetime( $value, 'm/d/y g:i A' ) ;
										$td_attrs[ 'class' ][] = 'datetime' ;
										break ;

									case self::FORMAT_DATETIME_PRECISE:
										$value = $this->format_datetime( $value, 'M j, Y g:i:s A' ) ;
										$td_attrs[ 'class' ][] = 'datetime' ;
										break ;

									case self::FORMAT_XLS_DATETIME:
										$value = $this->format_datetime( $value, 'Y-m-d H:i' ) ;
										$td_attrs[ 'class' ][] = 'datetime' ;
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
													if( is_string( $arg ) && strpos( $arg, '{' ) !== FALSE ) {
														$args[$key] = $this->parse_string($object, $arg, FALSE) ;
													}
												}
											}

											if( !is_object($obj) || get_class( $obj ) != get_class( $object )) {
												$args[] = $object ;
											}
										}

										$value = call_user_func_array(array($obj, $col_spec[ 'format' ][ 'method' ]), $args ) ;
										break ;


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
										$td_attrs[ 'class' ][] = $col_spec[ 'class' ][ 'classes' ][ $key ] ;
									}
								} else {
									$td_attrs[ 'class' ][] = $col_spec[ 'class' ] ;
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

								$base = Kohana::$base_url ;
								if( isset( $action[ 'base' ]) && !empty( $action[ 'base' ])) {
									$base = $action[ 'base' ] ;
								}

								$action_str = "<a href='{$base}{$url_fragment}{$id}' class='{$action_class}'>".str_replace(' ','&nbsp;',$action[ 'name' ])."</a>" ;

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
							$td_attrs[ 'class' ][] = 'actions' ;
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
							/*
							 * Build an array structure containing the header and value pairs.
							 * We'll turn that into a JSON string and assign it to the
							 * "data-supplement" property of our supplemental data column.
							 * This same structure should also store other attributes created
							 * in this loop so that we can pass them to the spreadsheet (or web?)
							 * for proper formatting.
							 */

							$heading = ucwords( $col_spec[ 'property' ]) ;
							if( isset( $col_spec[ 'heading' ])) {
								$heading = $col_spec[ 'heading' ] ;
							}

							$supplemental_data[] = array(
									'heading' => $heading, //htmlentities($heading, ENT_QUOTES),
									'value' => $value, //htmlentities($value, ENT_QUOTES),
									'td_attrs' => $td_attrs
							) ;

							$value ;
						} elseif ( $col_spec['type'] == self::TYPE_ROW_DATA ) {
							$prop = $col_spec[ 'property' ] ;
							if( isset( $col_spec[ 'heading' ])) {
								$prop = $col_spec[ 'heading' ] ;
							}

							$row_data[ $prop ] = $value ;

						} else {
							$row_cells .= "<{$this->render_tags[$render_as]['cell']}".HTML::attributes( $this->compiled_attributes($td_attrs) ).">{$value}</{$this->render_tags[$render_as]['cell']}>" ;
						}
					}
				}

				if( count( $supplemental_data ) > 0 ) {
					if( !in_array($this->context, array( self::CONTEXT_PDF, self::CONTEXT_SPREADSHEET )) ) {
						$supplemental_data_json = json_encode(
						        $supplemental_data,
                                JSON_HEX_QUOT | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG
                        ) ;
						$row_cells .= "<{$this->render_tags[$render_as]['cell']} class='supplement-column' data-supplement='{$supplemental_data_json}'><span class='supplement-view ui-icon ui-icon-search ui-icon-right ui-icon-clickable'></span></{$this->render_tags[$render_as]['cell']}>" ;

					} else {
						foreach( $supplemental_data as $supplement_col ) {
							$row_cells .= "<{$this->render_tags[$render_as]['cell']}".HTML::attributes( $this->compiled_attributes( $supplement_col['td_attrs'] )).">{$supplement_col['value']}</{$this->render_tags[$render_as]['cell']}>" ;
						}
					}
				}

				if( count( $row_data ) > 0 && !in_array($this->context, array( self::CONTEXT_PDF, self::CONTEXT_SPREADSHEET ))) {

					$row_data_json = htmlentities( Json::get_json($row_data), ENT_QUOTES ) ;
					$_output .= "<{$this->render_tags[$render_as]['row']} class='row-equiv' data-row-data='{$row_data_json}'>" ;
				} else {
					$_output .= "<{$this->render_tags[$render_as]['row']} class='row-equiv'>" ;
				}

				$_output .= $row_cells ;
				$_output .= "</{$this->render_tags[$render_as]['row']}>" ;

			}

			if( $render_as == self::RENDER_AS_TABLE ) {
				$_output .= "</tbody>" ;
			}


			$_output .= "</".$this->render_tags[$render_as]['container'].">" ;

		} else {
			$_output .= '<div class="no-data">Nothing to display</div>' ;
		}

		if( isset( $bm )) \Profiler::stop ($bm) ;

		return trim( $_output) ;
	}

    /**
     * Given an object and relation information, generate a list where a "has many"
     * relationship exists.
     *
     * @param object $object The object we're starting with.
     * @param string $root_key The property of the object we're working with, or empty to use the object itself.
     * @param string $relation_name The name of the "has many" relation.
     * @param string $property_name The property name in the "has many" relation to display.
     * @param string $order_by
     * @param string $empty_content Default string if there are no relations.
     * @param string $separator The separator to use in output.
     * @param int    $cache_lifetime Cache lifetime, set to zero for no cache
     * @return string
     * @throws \Kohana\KohanaException
     * @throws \Kohana\Cache\Cache\CacheException
     */
	protected function format_list( $object, $root_key, $relation_name, $property_name, $order_by = NULL, $empty_content = '--', $separator = '<br />', $cache_lifetime = 0) {
		$_output = '' ;
		$cache = FALSE ;

		if( $cache_lifetime > 0 && array_key_exists('cache', Kohana::modules())) {
			$cache = Cache::instance() ;
		}
		if( $cache !== FALSE ) {
			$cache_key = md5(implode('::',array(
				serialize($object),
				empty( $root_key ) ? 'root' : $root_key,
				$relation_name,
				$property_name,
				empty( $order_by ) ? 'nosort' : $order_by,
				$empty_content,
				$separator
			))) ;

			$_output = $cache->get($cache_key,NULL) ;

		}

		if( empty( $_output )) {
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
					$content[] = $this->generate_content( $item, $property_name ) ;
				}
			} else {
				$content[] = "<em>{$empty_content}</em>" ;
			}

			$_output = implode( $separator, $content ) ;

			if( $cache !== FALSE && !empty( $_output)) {
				$cache->set($cache_key, $_output) ;
			}
		}


		return $_output ;
	}

	/**
	 * Create a link, with the result going in a blank browser window.
	 *
	 * @param mixed $object
	 * @param string $link_text
	 * @param string $link_url
	 * @return string
	 */
	protected function format_link( $object, $link_text, $link_url ) {
		$url = $this->parse_string($object, $link_url) ;
		$text = $this->parse_string($object, $link_text) ;

		if( !empty( $url )) {
			$_output = "<a href='{$url}' target='_blank'>{$text}</a>" ;
		} else {
			$_output = $text ;
		}
		return $_output ;
	}


	/**
	 * Parse the given string and return either a string or an object. The input string
	 * can be either a simple property or object reference ("foo" or "foo->bar"),
	 * or a more elaborate string with object references in curly braces ("foo is equal to {foo}".
	 *
	 * @param mixed $object
	 * @param string $parseable_string
	 * @param boolean $return_as_string Set to FALSE to return an object suitable for further processing instead of a string.
	 * @return mixed
	 */
	protected function parse_string( $object, $parseable_string, $return_as_string = TRUE ) {
		if( Kohana::$profiling === TRUE ) {
			$bm = \Profiler::start(__CLASS__,__METHOD__) ;
		}
		
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

		if( isset( $bm )) \Profiler::stop($bm);

		return $_output ;

	}

	/**
	 * Output an array value for the given key, or "--" if the incoming value
	 * does not exist as a key in the array.
	 *
	 * @param string $value
	 * @param array $lookup
	 * @return string
	 */
	protected function format_lookup( $value, $lookup ) {
		$_output = '--' ;
		if( isset( $lookup[ $value ])) {
			$_output = $lookup[ $value ] ;
		}
		return $_output ;
	}

	/**
	 * Format the value as a datetime.
	 *
	 * @param string $value
	 * @param string $format
	 * @return string
	 */
	protected function format_datetime( $value, $format = 'm/j/Y') {
		if( !empty( $value )) {
			$value = date($format, strtotime( $value )) ;
		}
		return $value ;
	}

	/**
	 * Format as US dollars, optionally including the dollar sign (defaults to
	 * TRUE except for Excel output).
	 *
	 * @param float $value
	 * @param boolean $include_dollar_sign
	 * @return string
	 */
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

	/**
	 * Output as a standard US phone number or an internal 5-digit number. This
	 * will generate any one of the following based on the incoming string length:
	 * #-####, ###-####, (###) ###-####, # (###) ###-####
	 *
	 * @param string $value
	 * @return string
	 */
	protected function format_phone( $value ) {
		return Text::phone($value) ;
	}

	/**
	 * Truncate the string to the specified number of characters. Note that this
	 * is NOT smart enough to handle HTML or other formatting, so be sure to pass
	 * this method only plain strings or you run the risk of breaking whatever
	 * format exists in the original.
	 *
	 * @param string $value
	 * @param int $chars Number of characters to include in the final string
	 * @return string
	 */
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

		if( Kohana::$profiling === TRUE ) {
			$bm = Profiler::start(__CLASS__,__METHOD__) ;
		}

		if( $key == 'ROOT' ) {
			$_output = $data_root ;	
		} else {
			$_output = eval("return \$data_root->{$key};");
		}

		if( isset( $bm )) Profiler::stop($bm);

		return $_output ;

	}

	/**
	 * @param mixed $attrs
	 * @return mixed
	 */
	protected function compiled_attributes( $attrs ) {
		$_output = $attrs ;
		if( is_array( $attrs ) && count( $attrs ) > 0 ) {
			foreach( $attrs as $key => $value ) {
				if( is_array( $value )) {
					$_output[ $key ] = implode(' ', $value) ;
				}
			}
		}

		return $_output ;
	}



}
