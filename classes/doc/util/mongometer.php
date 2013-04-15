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
    private static $initialized = FALSE;
    
    /**
     * Client class for connecting to MongoDB
     * 
     * @var MongoClient
     */
    protected static $mongo_client = NULL;
    
    /**
     * Database in which performance data are stored
     * 
     * @var MongoDB
     */
    protected static $mongo_database = NULL;
    
    /**
     * Name of the collection that stores daily data
     * 
     * @var string
     */
    protected static $mongo_collection_daily = NULL;
    
    /**
     * Name of the collection that stores hourly data
     * 
     * @var string
     */
    protected static $mongo_collection_hourly = NULL;
    
    /**
     * Name of the collection that stores realtime data
     * 
     * @var string
     */
    protected static $mongo_collection_realtime = NULL;
    
    /**
     * Compile individual requests into a daily statistical entry
     */
    protected static function compile_daily() {}
    
    /**
     * Compile individual requests into an hourly statistics entry
     */
    protected static function compile_hourly() {}
    
    /**
     * Get daily statistics entries
     */
    protected static function get_daily() {}
    
    /**
     * Get hourly statistics entries
     */
    protected static function get_hourly() {}
    
    /**
     * Get realtime statistics entries
     * 
     * @param int $interval how many minutes of data to fetch
     * @return array
     */
    protected static function get_realtime($interval = 5) {}
    
    /**
     * Ensure MongoDB client is connected
     */
    private static function connect() {
        if ( ! self::$mongo_client->connected) {
            self::$mongo_client->connect();
        }
    }
    
    /**
     * Performance neccesary class initialization
     */
    protected static function init() {
        if ( ! self::$initialized) {
            $config = Kohana::$config->load('mongometer');
            self::$mongo_client = new MongoClient(
                "mongodb://{$config->host}:{$config->port}", 
                array(
                    'username' => $config->user, 
                    'password' => $config->password, 
                    'db' => $config->database,
                    'connect' => FALSE,
                    'connectTimeoutMS' => 300,
                    'socketTimeoutMS' => 300,
                )
            );
            self::$mongo_database = self::$mongo_client->selectDB($config->database);
            self::$mongo_collection_realtime = self::$mongo_database->selectCollection($config->collections['realtime']);
            self::$mongo_collection_daily = self::$mongo_database->selectCollection($config->collections['daily']);
            self::$mongo_collection_hourly = self::$mongo_database->selectCollection($config->collections['hourly']);
        }
        
        self::connect();
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
        $supp_info = Request::user_agent(array('browser', 'version', 'robot', 'mobile', 'platform'));
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
            'user_agent' => array(
            	'ip_address' => Request::$client_ip,
            	'browser' => $supp_info['browser'],
            	'version' => $supp_info['version'],
            	'robot' => $supp_info['robot'],
            	'mobile' => $supp_info['mobile'],
            	'platform' => $supp_info['platform'],
            ),
        );
        
        self::$mongo_collection_realtime->insert($data, array('w' => 0));
    }
}

// End DOC_Util_MongoMeter