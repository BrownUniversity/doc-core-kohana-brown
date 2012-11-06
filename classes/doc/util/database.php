<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of database
 *
 * @author jorrill
 */
class DOC_Util_Database {

	const STD_OBJ = 'object' ;
	const USER_OBJ = 'user_object' ;

	/**
	 *
	 * @param array $posted_ids
	 * @param array $current_collection
	 * @param string $field_name
	 * @param Model $obj_template
	 * @param string $obj_type
	 * @todo Determine whether this method is really necessary. Kohana 2.x could do something like $obj->prop = array(id, id, id), but it's not clear whether 3.1 supports that.
	 * @todo Remove this from the module-- the user handling here may not apply to all apps.
	 * @deprecated since 2012-11-01
	 */
	public static function merge_relation( $posted_ids, $current_collection, $field_name, $obj_template, $obj_type = self::STD_OBJ) {
		$current_ids = array() ;
		$new_ids = array() ;

		foreach( $current_collection as $current_obj) {
			if( is_array( $posted_ids )) {
				if( !in_array( $current_obj->$field_name, $posted_ids)) {
					$current_obj->delete() ;
				} else {
					$current_ids[] = $current_obj->$field_name ;
				}
			} else {
				$current_obj->delete() ;
			}

		}
		if( is_array( $posted_ids )) {
			$new_ids = array_diff( $posted_ids, $current_ids ) ;
		}

		if( count( $new_ids ) > 0 ) {
			foreach( $new_ids as $id ) {
				if( !empty( $id )) {
					// Normally we expect the id to exist in the database already,
					// but users may need to be created on the fly, so we have
					// special handling for those.

					if( $obj_type == self::USER_OBJ ) {
						if( !is_numeric( $id )) {
							$user = Model_User::retrieve_via_uuid( Encrypt::instance()->decode( $id )) ;
							$id = $user->id ;
						}
					}

					$new_obj = clone( $obj_template ) ;
					$new_obj->$field_name = $id ;
					$new_obj->save() ;
				}
			}
		}
	}
}
