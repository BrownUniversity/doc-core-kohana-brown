<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of help
 *
 * @author jorrill
 */
class DOC_Helper_Help {
	
	public static function help() {
		$request = Request::current() ;
		$help_content = "<em>No help available</em>" ;
		
		$help_file = 'help/' ;
		$directory = $request->directory() ;
		if( !empty( $directory )) {
			$help_file .= $directory . '/' ;
		}
		
		$help_file .= $request->controller().'/'.$request->action() ;
		if( Kohana::find_file('views', $help_file) ) {	
			$help_content = View::factory($help_file)->render() ;
		}
		
		print("<div id='help'>{$help_content}</div>") ;
	}
}
