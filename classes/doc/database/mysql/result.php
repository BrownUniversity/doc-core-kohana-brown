<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of result
 *
 * @author jorrill
 */
class DOC_Database_MySQL_Result extends Kohana_Database_MySQL_Result {

	public function as_json() {
		$arr = array() ;
		foreach( $this as $obj ) {
			$arr[] = $obj->as_array() ;
		}
		return json_encode( $arr ) ;
	}

	public function as_complete_array() {
		$arr = array() ;
		foreach( $this as $obj ) {
			$arr[] = $obj->as_array() ;
		}
		return $arr ;
	}

}
