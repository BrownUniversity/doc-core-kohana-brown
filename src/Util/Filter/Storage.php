<?php
namespace BrownUniversity\DOC\Util\Filter ;

/**
 * Abstract class to define how storage subclasses should function. This is used
 * by DOC_Util_Filter and its subclasses to store and retrieve search filter data.
 *
 * @author Jason Orrill <Jason_Orrill@brown.edu>
 */
abstract class Storage {
	
	/**
	 *
	 * @var mixed Used to store an instance of the storage object, which will be an extension of this class. 
	 */
	protected static $storage_object = NULL ;
	
	/**
	 * Private constructor, so that we can use an instance() method below.
	 */
	private function __construct() {}
	
	/**
	 * Get an instance of the storage object.
	 */
	public static function instance() {}
	
	/**
	 * Store a given value at the key location.
	 * 
	 * @param string $key
	 * @param mixed $value
	 */
	public function set($key, $value) {}
	
	/**
	 * Get a value for the given key, using the default value if no value exists.
	 * 
	 * @param string $key
	 * @param mixed $default_value
	 */
	public function get($key, $default_value = NULL) {}
	
	/**
	 * Delete the value at the given key.
	 * 
	 * @param string $key
	 */
	public function delete($key) {}
}
