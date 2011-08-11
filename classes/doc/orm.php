<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of orm
 *
 * @author jorrill
 */
class DOC_ORM extends Kohana_ORM {
	
	/**
	 * Use in slugify to indicate the uniqueness test should use the primary key
	 * for the object, whatever that is.
	 */
	const UNIQUENESS_AGAINST_PK = 'primary key' ;
	const DEFAULT_SLUG_COLUMN = 'slug' ;
	const DEFAULT_SLUG_MAX_LENGTH = 50 ;
	
	
	public function supports_property( $prop_name ) {
				
		if( array_key_exists( $prop_name, $this->_table_columns)) {
			return TRUE ;
		}
		if( array_key_exists( $prop_name, $this->_belongs_to)) {
			return TRUE ;
		}
		if( array_key_exists( $prop_name, $this->_has_many)) {
			return TRUE ;
		}
		if( array_key_exists( $prop_name, $this->_has_one)) {
			return TRUE ;
		}
		
		return FALSE ;
	}
	
	/**
	 * Not sure what purpose this serves that the basic factory wouldn't give us.
	 * Should it be deprecated?
	 * 
	 * @param string $obj_type
	 * @param string $key_name
	 * @param string $key_value
	 * @return ORM 
	 */
	static function obj_template($obj_type, $key_name, $key_value) {
		$_output = ORM::factory($obj_type)
				->where($key_name,'=',$key_value)
				->find() ;

		if( !$_output->loaded() ) {
			$_output = NULL ;
		}
		
		return $_output ;
	}
	

	/**
	 * Makes up for what appears to be a shortcoming in Kohana where you cannot
	 * clear out a set of related data unless it is a "has many through" relation.
	 * 
	 * @param string $relation
	 */	
	public function remove_all( $relation ) {
		$related_data = $this->$relation->find_all() ;
		foreach( $related_data as $item ) {
			$item->delete() ;
		}
	}
	
	public function as_json() {
		return json_encode( $this->as_array() ) ;
	}
	
	/**
	 * Make a copy of all relation data so that both the current and source
	 * objects have the same set of relations for the given relation name.
	 * 
	 * @param ORM $source_obj
	 * @param string $relation_name 
	 */
	public function clone_relation($source_obj, $relation_name) {
		$data = $source_obj->$relation_name->find_all() ;
		if( count( $data ) > 0 ) {
			$ids = array() ;
			foreach( $data as $obj ) {
				$ids[] = $obj->pk() ;
			}
			$this->add($relation_name, $ids) ;
		}
	}
	
	public function property_is_unique( $value, $property ) {
		return ! (bool) DB::select( array( DB::expr('COUNT(id)'), 'total'))
				->from($this->_table_name)
				->where($this->_primary_key, '!=', $this->pk())
				->where($property,'=',$value)
				->execute()
				->get('total') ;
	}
	
	/**
	 * Modifies a string to remove non-ASCII characters and spaces.
	 *
	 * @param string $text
	 * @return string
	 */
	public function slugify( $text, $slug_column = self::DEFAULT_SLUG_COLUMN, $max_length = self::DEFAULT_SLUG_MAX_LENGTH, $uniqueness_property = self::UNIQUENESS_AGAINST_PK ) {
		$slug_found = FALSE ;
		$modifier = '' ;
		
		$uniq_prop = $this->_primary_key ;
		$uniq_prop_val = $this->pk() ;
		if( $uniqueness_property != self::UNIQUENESS_AGAINST_PK ) {
			$uniq_prop = $uniqueness_property ;
			$uniq_prop_val = $this->$uniq_prop ;
		}
		
		while( !$slug_found ) {
			$slug = self::create_slug($text, $modifier, $max_length) ;
			
			$row_count =  DB::select( array( DB::expr('COUNT(id)'), 'total'))
					->from($this->_table_name)
					->where($uniq_prop, '!=', $uniq_prop_val)
					->where($slug_column, '=', $slug)
					->execute()
					->get('total') ;
			
			$slug_found = $row_count == 0 ;
			$modifier = intval( $modifier ) + 1 ;
			
		}
		
		
		return $slug ;
	}

	
	static function create_slug( $text, $modifier = '', $max_length = self::DEFAULT_SLUG_MAX_LENGTH ) {
		// replace non letter or digits by -
		$text = preg_replace('~[^\\pL\d]+~u', '-', $text);

		$text = trim($text, '-');

		// transliterate
		if (function_exists('iconv')) {
			$text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
		}

		$text = strtolower($text);
		$text = preg_replace('~[^-\w]+~', '', $text);

		if (empty($text)) {
			return 'n-a';
		}

		// if there's a modifier and a max_length, deal with those
		
		if(strlen($text) + strlen($modifier) > $max_length ) {
			$text = substr( $text, 0, $max_length - strlen($modifier)) ;
		} 
		
		$text .= $modifier ;
		
		
		
		return $text;		
	}


}

?>
