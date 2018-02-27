<?php
namespace BrownUniversity\DOC\Util\Filter\Storage ;
use BrownUniversity\DOC\Util\Filter\Storage ;
use Kohana\Session as Kohana_Session;

/**
 * Storage object for using the session as filter storage. This is the default
 * approach unless overridden in the application.
 *
 * @author Jason Orrill <Jason_Orrill@brown.edu>
 */
class Session extends Storage {
	private $session ;
	
	/**
	 * Standard constructor, stores an instance of the Session object in a private property.
	 */
	private function __construct() {
		$this->session = Kohana_Session::instance( 'database' ) ;
	}
	
	/**
	 * 
	 * @return \BrownUniversity\DOC\Util\Filter\Storage\Session
	 */
	public static function instance() {
		$my_class = __CLASS__ ;
		if( self::$storage_object == NULL ) {
			self::$storage_object = new $my_class() ;
		}
		
		return self::$storage_object ;
	}

	/**
	 * Set the value for the given key.
	 * 
	 * @param string $key
	 * @param mixed $value
	 */
	public function set($key, $value) {
		$this->session->set( $key, $value ) ;
		$this->session->write() ;
	}
	
	/**
	 * Get the value at the given key, or the default value if nothing is set.
	 * 
	 * @param string $key
	 * @param mixed $default_value
	 * @return mixed
	 */
	public function get($key, $default_value = NULL) {
		return $this->session->get( $key, $default_value ) ;
	}
		
	/**
	 * Delete value for the given key.
	 * 
	 * @param string $key
	 */
	public function delete($key) {
		$this->session->delete( $key ) ;
	}
	
}
