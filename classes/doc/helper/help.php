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
	
	const HELP_ID = 'help' ;
	const TRIGGER_ID = 'helpTrigger' ;
	const TRIGGER_CLASS = 'help-trigger' ;
	const NO_FLOAT_CLASS = 'nofloat' ;
	
	static $contexts = array() ;
	
	public static function trigger($context = NULL, $no_float = FALSE) {
		self::$contexts[] = $context ;
		
		$trigger_id = self::TRIGGER_ID ;
		$trigger_classes = array(self::TRIGGER_CLASS) ;
		
		if( !empty( $context )) {
			$trigger_id .= "-{$context}" ;
		}
		
		if( $no_float ) {
			$trigger_classes[] = self::NO_FLOAT_CLASS ;
		}
		print("<span id='{$trigger_id}' class='".implode(' ', $trigger_classes)."'>?</span>") ;
	}
	
	
	/**
	 * Look for the help content and output into the help div, or display "No help
	 * available" if no file can be found.
	 */
	public static function help() {
		$request = Request::current() ;
		$help_content = "<em>No help available</em>" ;
		
		$help_file_root = 'help/' ;
		$directory = $request->directory() ;
		if( !empty( $directory )) {
			$help_file_root .= $directory . '/' ;
		}
		
		if( !is_array( self::$contexts )) {
			self::$contexts = array(self::$contexts) ;
		}
		
		// make sure we include the page-level context, even if it's not explicitly included.
		if( !in_array(NULL,array_values(self::$contexts))) {
			self::$contexts[] = NULL ;
		}
		
		
		foreach( self::$contexts as $context ) {
			if( empty( $context )) {
				$help_id = self::HELP_ID ;
				$help_file = $help_file_root . $request->controller().'/'.$request->action() ;
			} else {
				$help_id = self::HELP_ID . '-' . $context ;
				$help_file = $help_file_root . $request->controller().'/'.$request->action().'/'.$context ;
			}
		
			if( Kohana::find_file('views', $help_file) ) {	
				$help_content = View::factory($help_file)->render() ;
			}
		
			print("<div id='{$help_id}' class='help'>{$help_content}</div>") ;
		
		}
	}
}
