<?php
namespace BrownUniversity\DOC;
/**
 * Extension of Kohana_ORM to provide functionality not provided by the core code.
 * This includes updates for performance, property uniqueness and some specialized
 * functions typically required by DOC apps.
 *
 * @author jorrill
 * @todo merge code into Kohana proper
 */
class ORM extends \Kohana_ORM {
	
	/**
	 * Use in slugify to indicate the uniqueness test should use the primary key
	 * for the object, whatever that is.
	 */
	const UNIQUENESS_AGAINST_PK = 'primary key' ;
	const DEFAULT_SLUG_COLUMN = 'slug' ;
	const DEFAULT_SLUG_MAX_LENGTH = 50 ;
	
    /**
     * Columns on which to execute a project on select statements
     * 
     * @var array
     */
    protected $_project_columns = array();
    
    /**
     * OVERLOADED: To allow projections with ORM
     * 
	 * Loads a database result, either as a new record for this model, or as
	 * an iterator for multiple rows.
	 *
     * @author Christopher Keith <Christopher_Keith@brown.edu>
	 * @chainable
	 * @param  bool $multiple Return an iterator or load a single row
	 * @return ORM|\Database_Result
	 */
	protected function _load_result($multiple = FALSE)
	{
		$this->_db_builder->from(array($this->_table_name, $this->_object_name));

		if ($multiple === FALSE)
		{
			// Only fetch 1 record
			$this->_db_builder->limit(1);
		}

		// Select all columns by default or use projection
        if (count($this->_project_columns) == 0) {
            $this->_db_builder->select($this->_object_name.'.*');
        } else {
        	foreach( $this->_project_columns as $project_column ) {
        		$this->_db_builder->select($project_column);
        	}
//            $this->_db_builder->select_array($this->_project_columns);
        }
        
		if ( ! isset($this->_db_applied['order_by']) AND ! empty($this->_sorting))
		{
			foreach ($this->_sorting as $column => $direction)
			{
				if (strpos($column, '.') === FALSE)
				{
					// Sorting column for use in JOINs
					$column = $this->_object_name.'.'.$column;
				}

				$this->_db_builder->order_by($column, $direction);
			}
		}

		if ($multiple === TRUE)
		{
			// Return database iterator casting to this object type
			$result = $this->_db_builder->as_object(get_class($this))->execute($this->_db);

			$this->reset();

			return $result;
		}
		else
		{
			// Load the result as an associative array
			$result = $this->_db_builder->as_assoc()->execute($this->_db);

			$this->reset();

			if ($result->count() === 1)
			{
				// Load object values
				$this->_load_values($result->current());
			}
			else
			{
				// Clear the object, nothing was found
				$this->clear();
			}

			return $this;
		}
	}
    
    /**
     * Overload to include calculated columns
     * 
     * @return array
     */
    public function as_array() {
        $_output = parent::as_array();
        if ((isset($this->_calculated_columns)) && (is_array($this->_calculated_columns))) {
            foreach ($this->_calculated_columns as $cc) {
                $_output[$cc] = (isset($this->$cc)) ? $this->$cc : NULL;
            }
        }
        
        return $_output;
    }
    
    /**
     * Encode the object as JSON
     * 
     * @return string
     */
    public function as_json() {
		return json_encode( $this->as_array(), JSON_HEX_APOS | JSON_HEX_QUOT ) ;
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
    
	/**
	 * Not sure what purpose this serves that the basic factory wouldn't give us.
	 * Should it be deprecated?
	 * 
	 * @param string $obj_type
	 * @param string $key_name
	 * @param string $key_value
	 * @return \ORM
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
     * Allow projection on a single or set of columns
     * 
     * @author Christopher Keith <Christopher_Keith@brown.edu>
     * @param mixed $column_name
     * @return ORM 
     */
	public function project($column_name = NULL) {
        if ($column_name !== NULL) {
            if ( ! is_array($column_name)) {
                $column_name = func_get_args();
            }
            
            foreach ($column_name as $cn) {
                if (strpos($cn, '.') === FALSE) {
                    $this->_project_columns[] = "{$this->_object_name}.{$cn}";
                } else {
                    $this->_project_columns[] = array($cn,str_replace('.',':',$cn));
                }
            }
        }
        return $this;
    }
    
    /**
	 * Given an array of property => value pairs, checks that the combination
	 * specified does not already exist in the database.
	 * 
	 * @param array $propval_array
	 * @return boolean 
	 */
	public function properties_are_unique( $propval_array ) {
		$select = \DB::select( array( \DB::expr('COUNT(id)'), 'total' ))
				->from($this->_table_name)
				->where($this->_primary_key, '!=', $this->pk()) ;
		
		foreach( $propval_array as $prop => $val ) {
			$select->where($prop, '=', $val ) ;
		}
		
		return ! (bool) $select->execute($this->_db_group)->get('total') ;
	}
    
    /**
	 * @deprecated Use DOC_ORM::properties_are_unique instead.
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
			
			$row_count =  \DB::select( array( \DB::expr('COUNT(id)'), 'total'))
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
		if( array_key_exists( $prop_name, $this->_object )) {
			return TRUE ;
		}
		
		return FALSE ;
	}
    
    /**
     * OVERLOADED: for handling projects
     * 
	 * Binds another one-to-one object to this model.  One-to-one objects
	 * can be nested using 'object1:object2' syntax
	 *
     * @author Christopher Keith <Christopher_Keith@brown.edu>
	 * @param  string $target_path Target model to bind to
     * @param boolean $include_columns whether or not to include columns in projection
	 * @return \BrownUniversity\DOC\ORM
     */
	public function with($target_path, $include_columns = TRUE)
	{
		if (isset($this->_with_applied[$target_path]))
		{
			// Don't join anything already joined
			return $this;
		}

		// Split object parts
		$aliases = explode(':', $target_path);
		$target = $this;
		foreach ($aliases as $alias)
		{
			// Go down the line of objects to find the given target
			$parent = $target;
			$target = $parent->_related($alias);

			if ( ! $target)
			{
				// Can't find related object
				return $this;
			}
		}

		// Target alias is at the end
		$target_alias = $alias;

		// Pop-off top alias to get the parent path (user:photo:tag becomes user:photo - the parent table prefix)
		array_pop($aliases);
		$parent_path = implode(':', $aliases);

		if (empty($parent_path))
		{
			// Use this table name itself for the parent path
			$parent_path = $this->_object_name;
		}
		else
		{
			if ( ! isset($this->_with_applied[$parent_path]))
			{
				// If the parent path hasn't been joined yet, do it first (otherwise LEFT JOINs fail)
				$this->with($parent_path);
			}
		}

		// Add to with_applied to prevent duplicate joins
		$this->_with_applied[$target_path] = TRUE;

		// Use the keys of the empty object to determine the columns
		foreach (array_keys($target->_object) as $column)
		{
			$name = $target_path.'.'.$column;
			$alias = $target_path.':'.$column;

			// Add the prefix so that load_result can determine the relationship
			if ($include_columns) {
                $this->select(array($name, $alias));
            }
		}

		if (isset($parent->_belongs_to[$target_alias]))
		{
			// Parent belongs_to target, use target's primary key and parent's foreign key
			$join_col1 = $target_path.'.'.$target->_primary_key;
			$join_col2 = $parent_path.'.'.$parent->_belongs_to[$target_alias]['foreign_key'];
		}
		else
		{
			// Parent has_one target, use parent's primary key as target's foreign key
			$join_col1 = $parent_path.'.'.$parent->_primary_key;
			$join_col2 = $target_path.'.'.$parent->_has_one[$target_alias]['foreign_key'];
		}

		// Join the related object into the result
		$this->join(array($target->_table_name, $target_path), 'LEFT')->on($join_col1, '=', $join_col2);

		return $this;
	}
}
// END DOC_ORM