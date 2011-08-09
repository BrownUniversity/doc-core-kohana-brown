<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of table
 *
 * @author jorrill
 */
class DOC_Util_Table extends Table {

	const ACTION_COL = 'actions' ;
	
	/**
	 *
	 * @var boolean Whether to zebra stripe the table. When working with a client-side library for sorting, you'll want to keep this FALSE.
	 */
	public $zebraStriping = FALSE ;


	protected $callbackData = array() ;
	protected $headerClasses = array() ;
	
	
	/**
	 *
	 * @param array $data
	 */
	public function __construct($data, $attributes = NULL) {
		if( !empty( $attributes )) {
			$this->set_attributes( $attributes ) ;
		}
		
		$this->callbackData['actions'] = array() ;
		$this->callbackData['valueLookup'] = array() ;
		$this->callbackData['customColumns'] = array() ;
		$this->callbackData['linkLookup'] = array() ;
		
		$this->set_body_data($data) ;

	}

	/**
	 *
	 * @param int $index
	 * @return Tr
	 */
	protected function _generate_row($index) {
		if( $this->zebraStriping ) {
			if( $index % 2 == 0 ) {
				return new Tr( '', 'zebra' ) ;
			} else {
				return parent::_generate_row($index) ;
			}
		}
	}

	/**
	 * This method will add a column named "Actions" to the table and set up the 
	 * necessary callback pointer. It takes a single array, with each item in the
	 * array specifying what the action should look like. Keys are:
	 * name: the link name that will appear
	 * url_fragment: the portion of the URL between the Kohana::$base_url and the id value from the row
	 * class: an optional class name for the row. This is most useful for javascript hooks.
	 *
	 * @param array $action_specs
	 */
	public function add_actions_column( $action_specs ) {
		$this->add_column( self::ACTION_COL ) ;
		$this->set_callback(__CLASS__.'::actions_callback', 'column', self::ACTION_COL ) ;

		if( !array_key_exists( self::ACTION_COL, $this->headerClasses )) {
			$this->headerClasses[ self::ACTION_COL ] = '{sorter: false}' ;
		}
		
		$this->callbackData['actions'] = $action_specs ;
	}

	/**
	 * Creates callback references for columns specified, and assigns data as 
	 * necessary to the valueLookup and customColumns properties.
	 * 
	 * @param array $format_specs 
	 */
	public function set_formats( $format_specs ) {
		foreach( $format_specs as $spec ) {
			switch ($spec['format']) {
				case 'dollar':
					$this->set_callback( __CLASS__.'::format_dollars_callback', 'column', $spec['key']) ;
					break;

				case 'xls_dollar':
					$this->set_callback( __CLASS__.'::format_xls_dollars_callback', 'column', $spec['key']) ;
					break;
				
				case 'datetime':
					$this->set_callback( __CLASS__.'::format_datetime_callback', 'column', $spec['key']) ;
					break ;

				case 'xls_datetime':
					$this->set_callback( __CLASS__.'::format_xls_datetime_callback', 'column', $spec['key']) ;
					break ;	
					
				case 'date':
					$this->set_callback( __CLASS__.'::format_date_callback', 'column', $spec['key']) ;
					break ;
					
				case 'lookup':
					$this->set_callback( __CLASS__.'::format_lookup_callback', 'column', $spec['key']) ;
					$this->callbackData['valueLookup'][ $spec[ 'key' ]] = $spec[ 'lookup' ] ;
					break ;
				
				case 'list':
					$this->set_callback( __CLASS__.'::list_callback', 'column', $spec['key']) ;
					$this->callbackData['listSpecs'][ $spec[ 'key' ]] = $spec[ 'listSpecs' ] ;
					break ;
					
				case 'truncate':
					$this->set_callback( __CLASS__.'::format_truncate_callback', 'column', $spec[ 'key' ]) ;
					break ;
					
				case 'link':
					$this->set_callback( __CLASS__.'::format_link_callback', 'column', $spec[ 'key' ]) ;
					$this->callbackData['linkLookup'][ $spec[ 'key' ]] = $spec[ 'link' ] ;
					break ;
					
				case 'phone':
					$this->set_callback( __CLASS__.'::format_phone_callback', 'column', $spec[ 'key' ]) ;
					break ;
					
				case 'custom':
					$this->set_callback( __CLASS__.'::custom_output_callback', 'column', $spec['key']) ;
					$this->callbackData['customColumns'][ $spec[ 'key' ]] = $spec[ 'output' ] ;
					break ;
					
				case 'conditional':
					$this->set_callback( __CLASS__.'::conditional_output_callback', 'column', $spec['key']) ;
					$this->callbackData['conditionalSpecs'][ $spec[ 'key' ]] = $spec[ 'conditionalSpecs' ] ;
					break ;
				
				default:
					break;
			}
		}
	}

	public function set_header_classes( $classes ) {
		$this->headerClasses = $classes ;
	}
	

	/**
	 * A generic callback function for use with the actions column. The links generated here
	 * assume that the URL will be based on the Kohana::$base_url + the url_fragment from
	 * the $action_specs created with the add_actions_column method, + the id column
	 * for the particular row.
	 *
	 * @param <type> $value
	 * @param <type> $index
	 * @param <type> $key
	 * @param <type> $body_data
	 * @param <type> $user_data
	 * @param <type> $row_data
	 * @param <type> $column_data
	 * @param <type> $table
	 * @return Td
	 */
	static function actions_callback( $value, $index, $key, $body_data, $user_data, $row_data, $column_data, $table ) {
		$id = $body_data[$index]->id ;

		$actions_array = array() ;
		foreach( $table->callbackData['actions'] as $action_spec ) {
			$test_result = TRUE ;
			$add_action = TRUE ;

			$action_str = "<a href='".Kohana::$base_url . $action_spec['url_fragment'] . $id . "'" ;
			if( isset( $action_spec[ 'class' ]) && !empty( $action_spec[ 'class' ])) {
				$action_str .= " class='{$action_spec['class']}'" ;
			}
			$action_str .= ">{$action_spec['name']}</a>" ;
			
			
			if( isset( $action_spec[ 'conditional' ])) {
				$test = "return " . self::parse_string( $action_spec[ 'conditional' ], $body_data[ $index ]) . ';' ;
				$test_result = eval( $test ) ;
				if( $test_result == FALSE ) {
					$action_str = strip_tags($action_str) ;
				}
			}
			
			if( !$test_result && (isset( $action_spec[ 'onfail' ]) && $action_spec[ 'onfail' ] == 'hide' )) {
				$add_action = FALSE ;
			}

			if( $add_action ) {
				$actions_array[] = $action_str ;
			}
		}

		return new Td( implode( '&nbsp;|&nbsp;', $actions_array ), self::ACTION_COL) ;

	}

	static function list_callback($value, $index, $key, $body_data, $user_data, $row_data, $column_data, $table) {
		$content = array() ;
		$root_key = $key ;
		if( isset( $table->callbackData['listSpecs'][$key]['root'] )) {
			$root_key = $table->callbackData['listSpecs'][$key]['root'] ;
		} 	

		if( empty( $root_key )) {
			$root = $body_data[$index] ;
		} else {
			$root = self::static_generate_content($body_data[$index], $root_key) ;
		}	
		
		$relation_name = $table->callbackData['listSpecs'][ $key ][ 'relation_name' ] ;
		$property_name = $table->callbackData['listSpecs'][ $key ][ 'property_name' ] ;
		
		$data = $root->$relation_name->order_by($property_name)->find_all() ;

		if( $data->count() > 0 ) {
			foreach( $data as $item ) {
				$content[] = $item->$property_name ;
			}
		} else {
			if( isset( $table->callbackData['listSpecs'][$key]['if_empty'])) {
				$content[] = '<em>'.$table->callbackData['listSpecs'][$key]['if_empty'].'</em>' ;
			}
			
		}
		
		$separator = '<br />' ;
		if( isset( $table->callbackData['listSpecs'][ $key ][ 'separator' ])) {
			$separator = $table->callbackData['listSpecs'][ $key ][ 'separator' ] ;
		}
		
		return implode( $separator, $content ) ;
	}
	
	static function format_link_callback($value, $index, $key, $body_data, $user_data, $row_data, $column_data, $table) {
		$content = '' ;
		$url = self::parse_string($table->callbackData['linkLookup'][ $key ][ 'url' ], $body_data[ $index ]) ;
		
		if( !empty( $url )) {
			$link_text = self::parse_string($table->callbackData['linkLookup'][ $key ][ 'text' ], $body_data[ $index ]) ;
			$content = "<a href='$url' target='_blank'>$link_text</a>" ;
		}
		
		return new Td( $content ) ;
	}

	/**
	 * Apply US telephone formatting.
	 * 
	 * @param type $value
	 * @param type $index
	 * @param type $key
	 * @param type $body_data
	 * @param type $user_data
	 * @param type $row_data
	 * @param type $column_data
	 * @param type $table
	 * @return Td 
	 */
	static function format_phone_callback($value, $index, $key, $body_data, $user_data, $row_data, $column_data, $table) {
		// TODO: this duplicates code I've put in a "Util_Massage" class in the SOURCE app. I'm putting the same code
		// here to avoid unnecessary dependencies, but it may be desirable to pull that code into the main DOC library at some point.
		$cell_value = self::static_generate_content($body_data[$index], $key) ;
		if( $cell_value != NULL && $cell_value != '' ) {
			$pattern = '' ;
			$replacement = '' ;
			switch (strlen($cell_value)) {
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
				$cell_value = preg_replace($pattern, $replacement, $cell_value) ;
			}

		}
		return new Td( $cell_value ) ;
	}


	
	
	/**
	 * Apply US dollar formatting.
	 * 
	 * @param type $value
	 * @param type $index
	 * @param type $key
	 * @param type $body_data
	 * @param type $user_data
	 * @param type $row_data
	 * @param type $column_data
	 * @param type $table
	 * @return Td 
	 */
	static function format_dollars_callback($value, $index, $key, $body_data, $user_data, $row_data, $column_data, $table) {
		$cell_value = self::static_generate_content($body_data[$index], $key) ;
		if( $cell_value != NULL && $cell_value != '' ) {
			$cell_value = '$' . number_format($cell_value, 2) ;
		}
		return new Td( $cell_value, 'dollars') ;
	}

	/**
	 * Apply US dollar formatting.
	 * 
	 * @param type $value
	 * @param type $index
	 * @param type $key
	 * @param type $body_data
	 * @param type $user_data
	 * @param type $row_data
	 * @param type $column_data
	 * @param type $table
	 * @return Td 
	 */
	static function format_xls_dollars_callback($value, $index, $key, $body_data, $user_data, $row_data, $column_data, $table) {
		$cell_value = self::static_generate_content($body_data[$index], $key) ;
		if( $cell_value != NULL && $cell_value != '' ) {
			$cell_value = number_format($cell_value, 2) ;
		}
		return new Td( $cell_value, 'dollars' ) ;
	}

	/**
	 * Format a datetime value in a human-friendly format.
	 * 
	 * @param type $value
	 * @param type $index
	 * @param type $key
	 * @param type $body_data
	 * @param type $user_data
	 * @param type $row_data
	 * @param type $column_data
	 * @param type $table
	 * @return Td 
	 */
	static function format_datetime_callback($value, $index, $key, $body_data, $user_data, $row_data, $column_data, $table) {
		$theDate = self::static_generate_content( $body_data[$index], $key) ;
		if( !empty( $theDate )) {
			$theDate = date('M j, Y g:i A', strtotime( $theDate )) ;
		}
		return new Td( $theDate, 'datetime' ) ;		
	}

		/**
	 * Format a datetime value in a human-friendly format.
	 * 
	 * @param type $value
	 * @param type $index
	 * @param type $key
	 * @param type $body_data
	 * @param type $user_data
	 * @param type $row_data
	 * @param type $column_data
	 * @param type $table
	 * @return Td 
	 */
	static function format_xls_datetime_callback($value, $index, $key, $body_data, $user_data, $row_data, $column_data, $table) {
		$theDate = self::static_generate_content( $body_data[$index], $key) ;
		if( !empty( $theDate )) {
			$theDate = date('Y-m-d H:i', strtotime( $theDate )) ;
		}
		return new Td( $theDate, 'datetime' ) ;		
	}

	
	/**
	 * Format a date in a standard US format (mm/dd/yyyy).
	 * 
	 * @param type $value
	 * @param type $index
	 * @param type $key
	 * @param type $body_data
	 * @param type $user_data
	 * @param type $row_data
	 * @param type $column_data
	 * @param type $table
	 * @return Td 
	 */
	static function format_date_callback($value, $index, $key, $body_data, $user_data, $row_data, $column_data, $table) {
		$theDate = self::static_generate_content( $body_data[$index], $key) ;
		if( !empty( $theDate )) {
			$theDate = date('m/j/Y', strtotime( $theDate )) ;
		}
		return new Td( $theDate, 'date' ) ;
	}
	
	/**
	 * Allows for incoming data to be mapped via array to any arbitrary value (arrays stored in valueLookup property).
	 * 
	 * @param type $value
	 * @param type $index
	 * @param type $key
	 * @param type $body_data
	 * @param type $user_data
	 * @param type $row_data
	 * @param type $column_data
	 * @param type $table
	 * @return Td 
	 */
	static function format_lookup_callback($value, $index, $key, $body_data, $user_data, $row_data, $column_data, $table) {
		$_output = '--' ;
		$value = self::static_generate_content($body_data[$index], $key) ;
		if( isset( $table->callbackData['valueLookup'][ $key ][ $value ])) {
			$_output = $table->callbackData['valueLookup'][ $key ][ $value ] ;
		}
		
		return new Td( $_output ) ;
	}

	/**
	 * Strip out any html and truncate to 80 characters
	 * 
	 * @param type $value
	 * @param type $index
	 * @param type $key
	 * @param type $body_data
	 * @param type $user_data
	 * @param type $row_data
	 * @param type $column_data
	 * @param type $table
	 * @return Td 
	 */
	static function format_truncate_callback($value, $index, $key, $body_data, $user_data, $row_data, $column_data, $table) {
		$max_length = 80 ;
		$value = self::static_generate_content($body_data[$index], $key) ;
		if(strlen($value) > $max_length) {
			$value = substr(strip_tags($value), 0, ($max_length - 3)) . '...' ;
		}
		
		return new Td( $value ) ;
	}

	
	
	/**
	 * A callback to implement custom formatting of content. Fields can be combined arbitrarily with this
	 * into any format desired.
	 * 
	 * @param type $value
	 * @param type $index
	 * @param type $key
	 * @param type $body_data
	 * @param type $user_data
	 * @param type $row_data
	 * @param type $column_data
	 * @param type $table
	 * @return Td 
	 */
	static function custom_output_callback($value, $index, $key, $body_data, $user_data, $row_data, $column_data, $table) {
		return new Td( self::parse_string($table->callbackData['customColumns'][ $key ], $body_data[ $index ])) ;
	}
	
	/**
	 * A callback to implement conditional content. Relies on the conditionalSpecs having an evaluatable
	 * test, and a true/false pair for output.
	 * 
	 * @param type $value
	 * @param type $index
	 * @param type $key
	 * @param type $body_data
	 * @param type $user_data
	 * @param type $row_data
	 * @param type $column_data
	 * @param type $table
	 * @return Td 
	 */
	static function conditional_output_callback($value, $index, $key, $body_data, $user_data, $row_data, $column_data, $table) {
		$_output = $table->callbackData[ 'conditionalSpecs' ][ $key ][ 'true' ] ;
		$test = "return " . self::parse_string( $table->callbackData[ 'conditionalSpecs' ][ $key ][ 'test' ], $body_data[ $index ]) . ';' ;
		$test_result = eval( $test ) ;
		if( $test_result == FALSE ) {
			$_output = $table->callbackData[ 'conditionalSpecs' ][ $key ][ 'false' ] ;
		}
		return new Td( $_output ) ;
	}
	
	static function parse_string( $source_string, $row_data ) {
		$_output = '' ;
		preg_match_all('/\{(.+?)\}/', $source_string, $matches) ;
	
		if( count( $matches ) > 0 ) {
			$_output = $source_string ;
			foreach( $matches[1] as $match ) {
				$_output = str_replace('{'.$match.'}', self::static_generate_content($row_data, $match), $_output) ;
			}
		}
		
		return $_output ;
	}
	
	
	/**
	 * Override the parent method so that we can build in support for data deeper
	 * in the object properties. This checks for the presence of a property marker
	 * ('->') in the $key, and if found it calls _generate_content to build the data.
	 *
	 * @param int $index
	 * @param string $key
	 * @return html
	 */
	protected function  _generate_body_cell($index, $key) {
		if( strpos( $key, '->' )) {
			$content = $this->_generate_content( $this->body_data[$index], $key );
		} else {
			// variables
			//$content = @$this->body_data[$index][$key];
			
			if(is_object($this->body_data[$index])) {
				if( $this->body_data[$index]->supports_property( $key )) {
					$content = @$this->body_data[$index]->$key ;
				} elseif (method_exists($this->body_data[ $index ], $key)) {
					$content = @$this->body_data[$index]->$key() ;
				} else {
					$content = 'ERR: unknown data source' ;
				}
				
			} else {
				$content = @$this->body_data[$index][$key] ;
			}
			
			
			
		}

		if( is_array( $content )) {
			$content = implode('<br />', $content) ;
		}

		// if there's a callback, call it
		if(array_key_exists($key, $this->get_column_cell_callback))
		{
			$content = call_user_func($this->get_column_cell_callback[$key], $content, $index, $key, $this->body_data, $this->user_data, $this->row_data, $this->column_data, $this);
		}
		elseif($this->get_body_cell_callback != NULL)
		{
			$content = call_user_func($this->get_body_cell_callback, $content, $index, $key, $this->body_data, $this->user_data, $this->row_data, $this->column_data, $this);
		}

		// render the cell
		if($content instanceof HTML_Element)
		{
			return $content->html();
		}
		elseif($content !== NULL)
		{
			return '<td>' . $content . '</td>';
		}
		else
		{
			return '<td>&nbsp;</td>';
		}



	}

	/**
	 * Works in conjunction with _generate_body_cell to pull data out of the row.
	 * This assumes that the $key will be a chained series of object properties,
	 * with the first call referencing an array.
	 * 
	 * @param object $data_root
	 * @param string $key
	 * @param boolean $treat_as_object
	 * @return string 
	 */
	protected function _generate_content( $data_root, $key, $treat_as_object = FALSE ) {
		return self::static_generate_content( $data_root, $key, $treat_as_object ) ;
	}

	
	static function static_generate_content( $data_root, $key, $treat_as_object = FALSE ) {
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
			return self::static_generate_content( $_output, $key, TRUE ) ;
		}

		return $_output ;

	}
	
	
	
	/**
	 * Internal method to render table heading
	 * 
	 * @return html
	 */
	protected function _generate_heading()
		{
			
			// start html
				$html	 = '';
				$html	.= '<thead>' . $this->newline;
				$html	.= '	<tr>' . $this->trNewline;
				
			// add in empty cell if there's a row_title
				if($this->has_row_title)
				{
					$html .= '		<th>&nbsp;</th>' . $this->tdNewline;
				}
				
			// build the heading cells
			
				// if there are titles, just render the titles, and filter if needs be
					if(is_array($this->column_titles))
					{
						// render the filtered titles
							if($this->auto_filter_titles)
							{
								foreach($this->column_filter as $key)
								{
									if(in_array($key, $this->column_filter))
									{
										
										if(array_key_exists($key, $this->headerClasses )) {
											$html .= '		<th class="'.$this->headerClasses[ $key ].'">' ;
										} else {
											$html .= '		<th>' ;
										}
																				
										$html .= (in_array($key, array_keys($this->column_titles)) ? $this->column_titles[$key] : $this->missing_title_warning) ;
										$html .= '</th>' . $this->tdNewline;
									}
								}
							}
						// render all titles
							else
							{
								foreach($this->column_titles as $value)
								{
									$html .= '		<th>' . $value . '</th>' . $this->tdNewline;
								}
							}
					}
					
				// if there's data and callbacks, do the callback thing instead
					else
					{
						foreach($this->column_filter as $key)
						{
							$html .= '		' . $this->_generate_heading_cell($key) . $this->tdNewline;
						}
					}
				
					
			// close html
				$html .= '	</tr>' . $this->trNewline;
				$html .= '</thead>' . $this->newline;
				$this->head_html = $html;
				
			// return
				return $html;
	
		}



}
?>