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

}

?>
