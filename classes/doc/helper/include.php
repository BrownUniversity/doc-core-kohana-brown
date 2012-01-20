<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Some static functions for locating and including content in your output. Also
 * relies on the includepaths config file, where you indicate paths and URLs to
 * search for content.
 *
 * @author jorrill
 */
class DOC_Helper_Include {

	const TYPE_CSS = 'css' ;
	const TYPE_JAVASCRIPT = 'javascript' ;
	const TYPE_IMAGE = 'image' ;

	const INCLUDE_LINK = 'link' ;
	const INCLUDE_CONTENTS = 'contents' ;

	/**
	 * Generate the proper URL for inclusion in your HTML. If passed a type, this
	 * method will return the appropriate link, script or img tag. If no type
	 * is included, then it will simply return the URL.
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

		if( !empty( $include_url )) {
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
		} else {
			$_output = "<!-- No {$path} in include paths -->" ;
		}

		return $_output ;

	}
	
	
	/**
	 * Given a path, searches the include paths and returns the contents of the 
	 * file it finds.
	 * 
	 * @param string $path
	 * @return string 
	 */
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

	/**
	 * Returns the appropriate tag or contents, using the current request to build
	 * a path. This assumes that your css or javascript directory has a "pages"
	 * directory with a structure that matches the request directory/controller/action.
	 * 
	 * @param string $type Use one of the class constants.
	 * @param string $include_as Use one of the class constants.
	 * @return string 
	 */
	static function companion( $type, $include_as = self::INCLUDE_LINK ) {

		$_output = '' ;
		$dir_and_extensions = array(
			self::TYPE_CSS => 'css',
			self::TYPE_JAVASCRIPT => 'js'
		) ;
		$dir_ext = $dir_and_extensions[ $type ] ;
		
		
		$request = Request::current() ;

		
		
		$file = $dir_ext . '/pages/' ;
		$directory = $request->directory() ;
		if( !empty( $directory )) {
			$file .= $directory . '/' ;
		}
		
		$file .= $request->controller().'/'.$request->action() ;
		$file .= '.' . $dir_ext ;
		

		if( $include_as == self::INCLUDE_LINK ) {
			$_output = self::file_link( $file, $type ) ;
		} else {
			$_output = self::file_contents( $file ) ;
		}
		
		return $_output ;

	}

}
?>
