<?php
namespace BrownUniversity\DOC\Util ;
use BrownUniversity\DOC\ORM ;
use Kohana\Cache\Cache;
use Kohana\Cache\CacheException;
use Kohana\Debug;
use Kohana\Kohana;
use Kohana\Log;

/**
 * @package DOC Core Module
 * @version 1.0
 * @since 1.0
 * @author Jason Orrill <Jason_Orrill@brown.edu>
 */
defined( 'SYSPATH' ) or die( 'No direct script access.' );

/**
 * Several chunks of data are stored in the database with simple key/value/abbrev
 * in the table. This class provides a standard way to get lookup arrays so that
 * we can refer to the primary key value by the abbreviation.
 */
class Lookup {

	const BY_KEY = 'byKey' ;
	const BY_VAL = 'byVal' ;
	const PRIMARY_KEY_PROP = 'pkProp' ;
	
	/**
	 * Used to provide extra control over debug logging within the class.
	 * 
	 * @var boolean 
	 */
	static $verbose = FALSE ;

    /**
     * Get a lookup array for the given model and key.
     *
     * @param string  $model A model name.
     * @param string  $key A property of the model to be used as the key in the array.
     * @param string  $mode Use one of the class constants here.
     * @param mixed   $order The field to order the results by. If a string, will be on that column ascending. If an array, will expect the keys to be column names and the values to all be either 'asc' or 'desc'.
     * @param array   $wheres An array of arrays, with each matching the arguments that are sent via the where() method.
     * @param boolean $refresh_cache should we force replace the cached values?
     * @return array
     * @throws \Kohana\KohanaException
     * @throws \Kohana\Cache\CacheException
     */
	static function get_lut( $model, $key, $mode = self::BY_VAL, $order = NULL, $wheres = NULL, $refresh_cache = FALSE) {
		$_output = array() ;
		$cache = FALSE ;
		if( array_key_exists( 'cache', Kohana::modules())) {
			$cache = Cache::instance() ;
		}

		if( self::$verbose ) {
			Kohana::$log->add(Log::DEBUG, Debug::vars($cache)) ;
		}
		
		$cache_key = "{$model}.{$key}.{$mode}." . md5( serialize( $order )) . '.' . md5( serialize( $wheres )) ;

		if(($cache !== FALSE) && ( ! empty( $cache_key )) && ($refresh_cache == FALSE)) {
			$_output = $cache->get($cache_key,$_output) ;
		
			if( self::$verbose ) {
				Kohana::$log->add(Log::DEBUG, "cached value for {$model}.{$key}:") ;
				Kohana::$log->add(Log::DEBUG, Debug::vars( $_output )) ;
			}
		} 

		if( empty( $_output )) {
			$orm = ORM::factory($model) ;
			if( !empty( $order )) {
				if ( ! is_array($order)) {
					$order = array($order => 'asc');
				}
				foreach ($order as $column => $direction) {
					$orm->order_by($column, $direction);
				}
			} else {
				if( $orm->supports_property('id')) {
					$orm->order_by('id') ;
				}
			}

			if( is_array( $wheres )) {
				foreach( $wheres as $where ) {
					$orm->where( $where[0], $where[1], $where[2] ) ;
				}
			}

			$arr = $orm->find_all() ;
			
			if( $arr->count() > 0 ) {
				if ( $mode == self::BY_VAL) {
					foreach( $arr as $obj ) {
						$_output[ $obj->$key ] = $obj->pk() ;
					}
				} else {
					foreach ( $arr as $obj ) {
						$_output[ $obj->pk() ] = $obj->$key;
					}
				}		
			
			}
		}
				
		if( $cache !== FALSE && !empty( $cache_key )) {
			try {
				$cache->set($cache_key,$_output) ;
			} catch( \Exception $e ) {
				Kohana::$log->add(Log::WARNING, "Unable to write to cache: " . $e->getMessage()) ;
			}
		}
		
		return $_output ;
	}

    /**
     * Use this when you need to create a string composed of multiple elements in the object.
     * For example, if you need to combine first and last name into a single name
     * containing both.
     *
     * @param string $model
     * @param array  $keys
     * @param string $format For use in sprintf.
     * @param string $mode Use one of the class constants.
     * @return array
     * @throws \Kohana\KohanaException
     */
	static function get_formatted_lut( $model, $keys, $format, $mode = self::BY_VAL ) {
		$_output = array() ;
		$orm = ORM::factory($model) ;
		foreach( $keys as $key ) {
			$orm->order_by( $key ) ;
		}

		$arr = $orm->find_all() ;
		foreach( $arr as $obj ) {

			$values = array() ;
			foreach( $keys as $key ) {
				$values[] = '"'.$obj->$key.'"' ;
			}

			$thisKey = NULL;
			eval("\$thisKey = sprintf(\"$format\", ".implode(',',$values).");") ;
			$_output[ $thisKey ] = $obj->pk() ;
		}
		if( $mode == self::BY_KEY ) {
			$_output = array_flip( $_output ) ;
		}

		return $_output ;
	}

    /**
     * Lookup a single value for the given property value. Leverages cache, if available.
     *
     * @param string $model
     * @param string $prop_key
     * @param string $prop_val
     * @param string $return_prop The property whose value you want returned. Defaults to the primary key.
     * @return mixed
     * @throws \Kohana\Cache\Cache\CacheException
     * @throws \Kohana\KohanaException
     */
	static function get_single( $model, $prop_key, $prop_val, $return_prop = self::PRIMARY_KEY_PROP ) {
		$_output = NULL ;
		$cache = FALSE ;
		if(array_key_exists('cache', Kohana::modules())) {
			$cache = Cache::instance() ;
		}
		$cache_key = "singlelookup.{$model}.{$prop_key}.{$prop_val}.{$return_prop}" ;
		if( $cache !== FALSE && !empty( $cache_key )) {
			$_output = $cache->get($cache_key,'') ;
			
			if( self::$verbose ) {
				Kohana::$log->add(Log::DEBUG, "cached value for {$model}.{$prop_key}.{$prop_val}:") ;
				Kohana::$log->add(Log::DEBUG, Debug::vars( $_output )) ;
			}
			
		}

		if( empty( $_output )) {
			$obj = ORM::factory($model)->where($prop_key,'=',$prop_val)->find() ;
			if( $obj->loaded() ) {
				if( $return_prop == self::PRIMARY_KEY_PROP ) {
					$_output = $obj->pk() ;
				} else {
					$_output = $obj->$return_prop ;
				}
			}

			if( $cache !== FALSE && !empty( $cache_key )) {
				try {
					$cache->set($cache_key,$_output) ;
				} catch( \Exception $e ) {
					Kohana::$log->add(Log::WARNING, "Unable to write to cache: " . $e->getMessage()) ;
				}
			}
		}

		return $_output ;
	}
}
