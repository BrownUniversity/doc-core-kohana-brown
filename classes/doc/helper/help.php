<?php

/**
 * Locates help content and outputs into a div with id="help". The help content
 * should be in directory structure that mirrors the "pages" directory structure.
 * For example, "pages/foo/bar.php" would look in "help/foo/bar.php". The help
 * div should be hidden by default, with an interface widget elsewhere on the page
 * to toggle it.
 *
 * @author jorrill
 */
class DOC_Helper_Help {
	
	/**
	 * Look for the help content and output into the help div, or display "No help
	 * available" if no file can be found.
	 */
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
