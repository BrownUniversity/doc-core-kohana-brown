<?php
namespace BrownUniversity\DOC ;

class View extends \Kohana_View {

	public static function factory($file = NULL, array $data = NULL ) {
		return new View($file, $data) ;
	}
	
	public function set_filename($file) {
		try {
			return parent::set_filename($file) ;

		} catch( \View_Exception $e ) {
			$path = __DIR__ . "/../views/{$file}.php" ;
 			if( file_exists( $path ) ) {
 				$this->_file = $path ;
			} else {
				throw new \View_Exception('The requested view :file could not be found', array(
					':file' => $file,
				));
 			}
		}
		
		return $this ;
	}
	
	
}