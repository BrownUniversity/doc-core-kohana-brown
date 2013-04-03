<?php
/**
 * @package DOC
 * @author Christopher Keith <Christopher_Keith@brown.edu>
 * @requires PHP MongoDB Driver
 */
defined( 'SYSPATH' ) or die( 'No direct script access.');

/**
 * Utility class for Performance Metrics
 * 
 * Data are stored in various collections using MongoDB.  A configuratino file
 * will be necessary to intializate an instance of this class.
 */
class DOC_Util_MongoMeter {
    
    /**
     * Has this class been initialized
     * 
     * @var boolean
     */
    public static $initialized = FALSE;
    
    /**
     * Client class for connecting to MongoDB
     * 
     * @var MongoClient
     */
    public static $mongo_client = NULL;
    
    /**
     * Database in which performance data are stored
     * 
     * @var MongoDB
     */
    public static $mongo_database = NULL;
    
    /**
     * Name of the collection that stores daily data
     * 
     * @var string
     */
    public static $mongo_collection_daily = NULL;
    
    /**
     * Name of the collection that stores hourly data
     * 
     * @var string
     */
    public static $mongo_collection_hourly = NULL;
    
    /**
     * Name of the collection that stores realtime data
     * 
     * @var string
     */
    public static $mongo_collection_realtime = NULL;
    
    /**
     * Compile individual requests into a daily statistical entry
     */
    public static function compile_daily() {
        
    }
    
    /**
     * Compile individual requests into an hourly statistics entry
     */
    public static function compile_hourly() {
        
    }
    
    /**
     * Get daily statistics entries
     */
    public static function get_daily () {
        
    }
    
    /**
     * Get hourly statistics entries
     */
    public static function get_hourly() {
        
    }
    
    /**
     * Get realtime statistics entries
     * 
     * @param int $interval how many minutes of data to fetch
     * @return array
     */
    public static function get_realtime($interval = 5) {
        
        $date = new MongoDate(time() - $interval * 60);
        
        $filter = array(
            '$match' => array(
                'timestamp' => array('$gt' => $date),
            ),
        );
        
        $application_ops = array(
            $filter,
            array(
                '$group' => array(
                    '_id' => array('application' => '$application'),
                    'count' => array('$sum' => 1),
                ),
            ),
        );
        
        $app_stats = self::$mongo_collection_realtime->aggregate($application_ops);
        
        $user_ops = array(
            $filter,
            array(
                '$group' => array(
                    '_id' => array('name' => '$user.name'),
                    'count' => array('$sum' => 1),
                ),
            ),
        );
        
        $user_stats = self::$mongo_collection_realtime->aggregate($user_ops);
        
        return array(
            'applications' => $app_stats,
            'users' => $user_stats,
        );
        
        
    }
    
    /**
     * Performance neccesary class initialization
     */
    public static function init() {
        if ( ! self::$initialized) {
            $config = Kohana::$config->load('mongometer');
            $host = $config->host;
            $port = $config->port;
            
            self::$mongo_client = new MongoClient("mongodb://{$host}:{$port}");
            self::$mongo_database = self::$mongo_client->selectDB($config->database);
            self::$mongo_collection_realtime = self::$mongo_database->selectCollection($config->collections['realtime']);
            self::$mongo_collection_daily = self::$mongo_database->selectCollection($config->collections['daily']);
            self::$mongo_collection_hourly = self::$mongo_database->selectCollection($config->collections['hourly']);
        }
    }
    
    /**
     * Log performance metric information about a request
     * 
     * @param string $app application abbreviation
     * @param Kohana_Request $request
     * @param Model_Qore_User $user
     */
    public static function log_request($app, $request, $user) {
        
        self::init();
        
        if (($user instanceof Model_Qore_User) && ($user->loaded())) {
            $user_array = array(
                'id' => $user->id,
                'name' => $user->name(FALSE),
                'affiliation' => $user->primary_affiliation,
            );
        } else {
            $user_array = array(
                'id' => '',
                'name' => '',
                'affiliation' => '',
            );
        }
        $data = array(
            'timestamp' => new MongoDate(),
            'application' => $app,
            'request' => array(
                'directory' => $request->directory(),
                'controller' => $request->controller(),
                'action' => $request->action(),
                'method' => $request->method(),
                'type' => $request->is_ajax() ? 'AJAX' : 'HTTP',
            ),
            'user' => $user_array,
        );
        
        self::$mongo_collection_realtime->insert($data);
    }
}

// End DOC_Util_MongoMeter