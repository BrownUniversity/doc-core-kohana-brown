<?php
namespace BrownUniversity\DOC ;

use Kohana\View as Kohana_View;
use Kohana\View\ViewException;

class View extends Kohana_View
{

	public static function factory($file = NULL, array $data = NULL ) {
		return new View($file, $data) ;
	}

    /**
     * @param string $file
     * @return $this|\Kohana\View
     * @throws \Kohana\View\ViewException
     */
    public function set_filename($file) {
		try {
			return parent::set_filename($file) ;

		} catch( ViewException $e ) {
			$path = __DIR__ . "/../views/{$file}.php" ;
 			if( file_exists( $path ) ) {
 				$this->_file = $path ;
			} else {
				throw new ViewException('The requested view :file could not be found', array(
					':file' => $file,
				));
 			}
		}
		
		return $this ;
	}
	
	
}