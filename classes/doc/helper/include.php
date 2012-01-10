<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of include
 *
 * @author jorrill
 */
class DOC_Helper_Include {

	const TYPE_CSS = 'css' ;
	const TYPE_JAVASCRIPT = 'javascript' ;
	const TYPE_IMAGE = 'image' ;

	/**
	 *
	 * @param string $path The path fragment you want to check for.
	 * @param string $type Use one of the class constants, or leave NULL to just get the URL with no generated HTML.
	 * @return string
	 */
	static function file_link( $path, $type = NULL ) {
		$_output = '' ;
		$include_url = '' ;
		// check the include directories, find the first instance of the file
		$include_paths = Kohana::$config->load('includepaths') ;

		foreach( $include_paths as $path_arr ) {
			if(file_exists( $path_arr[ 'base_file_path' ] . $path )) {
				$include_url = $path_arr[ 'base_url' ] . $path ;
				break ;
			}
		}


		// if $type is not null, then crank out the full html code, otherwise just output the URL

		switch ($type) {
			case self::TYPE_CSS:
				$_output = "<link rel='stylesheet' href='{$include_url}' type='text/css' />" ;

				break;
			case self::TYPE_JAVASCRIPT:
				$_output = "<script src='{$include_url}' language='javascript' type='text/javascript'></script>" ;
				break ;

			case self::TYPE_IMAGE:
				$_output = "<img src='{$include_url}' />" ;
				break ;
			default:
				$_output = $include_url ;
				break;
		}

		return $_output ;

	}

	static function file_contents( $path ) {
		$_output = '' ;

		$include_paths = Kohana::$config->load( 'includepaths' ) ;
		foreach( $include_paths as $path_arr ) {
			$file_path = $path_arr[ 'base_file_path' ] . $path ;
			if( file_exists( $file_path )) {
				$file_handle = fopen( $file_path, "r" ) ;
				$_output = fread( $file_handle, filesize( $file_path )) ;
				break ;
			}
		}

		return $_output ;
	}


}
?>
