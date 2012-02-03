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
	
	/**
	 * Checks whether the given property is supported by the object in any of the
	 * standard relationships. This includes _table_columns, _belongs_to, _has_many
	 * and _has_one.
	 * 
	 * @param string $prop_name
	 * @return boolean 
	 */
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
	
	/**
	 * @todo Deprecate this. Functionality is duplicated and improved on in the "properties_are_unique" method below.
	 */
	public function property_is_unique( $value, $property ) {
		return ! (bool) DB::select( array( DB::expr('COUNT(id)'), 'total'))
				->from($this->_table_name)
				->where($this->_primary_key, '!=', $this->pk())
				->where($property,'=',$value)
				->execute($this->_db_group)
				->get('total') ;
	}
	
	/**
	 * Given an array of property => value pairs, checks that the combination
	 * specified does not already exist in the database.
	 * 
	 * @param array $propval_array
	 * @return boolean 
	 */
	public function properties_are_unique( $propval_array ) {
		$select = DB::select( array( DB::expr('COUNT(id)'), 'total' ))
				->from($this->_table_name)
				->where($this->_primary_key, '!=', $this->pk()) ;
		
		foreach( $propval_array as $prop => $val ) {
			$select->where($prop, '=', $val ) ;
		}
		
		return ! (bool) $select->execute($this->_db_group)->get('total') ;
	}
	
	
	/**
	 * Modifies a string to remove non-ASCII characters and spaces.
	 *
	 * @param string $text
	 * @return string
	 */
	
	
	/**
	 * Modifies a string to remove non-ASCII characters and spaces. May also check
	 * for uniqueness and increment an integer suffix until the uniqueness constraint
	 * is satisfied.
	 * 
	 * @param string $text The original text to slugify.
	 * @param string $slug_column The DB column where slugs are stored.
	 * @param int $max_length The maximum length for the slug.
	 * @param string $uniqueness_property A property that uniquely identifies this object. Used to remove the current object from the query.
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
					->execute($this->_db_group)
					->get('total') ;
			
			$slug_found = $row_count == 0 ;
			$modifier = intval( $modifier ) + 1 ;
			
		}
		
		
		return $slug ;
	}

	/**
	 * Slugify a string.
	 * 
	 * @param string $text
	 * @param string $modifier
	 * @param int $max_length
	 * @return string 
	 */
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