<?php
namespace BrownUniversity\DOC\Util ;

/**
 * @package DOC
 * @author Christopher Keith <Christopher_Keith@brown.edu>
 * @requires PHP MongoDB Driver
 * @deprecated
 */
use Exception;
use Kohana\Kohana;
use Kohana\Log;
use Kohana\Request;
use MongoClient;
use MongoDate;

defined( 'SYSPATH' ) or die( 'No direct script access.');

/**
 * Utility class for Performance Metrics
 * 
 * Data are stored in various collections using MongoDB.  A configuration file
 * will be necessary to intializate an instance of this class.
 */
class MongoMeter {
    
    /**
     * Instance of DOC_Util_MongoMeter
     * 
     * @var MongoMeter
     */
    protected static $instance = NULL;
    
    /**
     * Client class for connecting to MongoDB
     * 
     * @var MongoClient
     */
    protected $client = NULL;
    
    /**
     * Database in which performance data are stored
     * 
     * @var MongoDB
     */
    protected $database = NULL;
    
    /**
     * Collection that stores daily data
     * 
     * @var MongoCollection
     */
    protected $collection_daily = NULL;
    
    /**
     * Collection that stores hourly data
     * 
     * @var MongoCollection
     */
    protected $collection_hourly = NULL;
    
    /**
     * Collection that stores realtime data
     * 
     * @var MongoColleciton
     */
    protected $collection_realtime = NULL;
    
	/**
	 * Collection names for storing data at different time slices.
	 */
    const COLLECTION_REALTIME = 'realtime' ;
	const COLLECTION_DAILY = 'daily' ;
	const COLLECTION_HOURLY = 'hourly' ;
	
	/**
	 * Indicate which configuration block of the mongodb we want to use.
	 */
	const CONFIG_KEY = 'default' ;
	
	/**
	 * MongoDB connection timeout.
	 */
	const TIMEOUT = 1000 ;

    /**
     * Class constructor
     *
     * @throws \Kohana\KohanaException
     * @throws \Exception
     */
    public function __construct() {
        $config = Kohana::$config->load('mongodb');
        $config = $config[ self::CONFIG_KEY ] ;
        $this->client = new MongoClient(
            "mongodb://{$config['host']}:{$config['port']}", 
            array(
                'username' => $config['user'], 
                'password' => $config['password'], 
                'db' => $config['database'],
                'connect' => TRUE,
                'connectTimeoutMS' => self::TIMEOUT,
                'socketTimeoutMS' => self::TIMEOUT,
            )
        );
        $this->database = $this->client->selectDB( $config['database'] ) ;
        $this->collection_realtime = $this->database->selectCollection( self::COLLECTION_REALTIME );
        $this->collection_daily = $this->database->selectCollection( self::COLLECTION_DAILY );
        $this->collection_hourly = $this->database->selectCollection( self::COLLECTION_HOURLY );
    }
    
    /**
     * Compile individual requests into a daily statistical entry
     */
    protected function compile_daily() {}
    
    /**
     * Compile individual requests into an hourly statistics entry
     */
    protected function compile_hourly() {}

    /**
     * Ensure MongoDB client is connected
     *
     * @deprecated unused?
     * @throws \MongoConnectionException
     */
    private function connect() {
        if ( ! $this->client->connected) {
            $this->client->connect();
        }
    }
    
    /**
     * Get daily statistics entries
     */
    protected function get_daily() {}
    
    /**
     * Get hourly statistics entries
     */
    protected function get_hourly() {}
    
    /**
     * Get realtime statistics entries
     * 
     * @param int $interval how many minutes of data to fetch
     * @return array
     */
    protected function get_realtime($interval = 5) {}
    
    /**
     * Get an instance of this class
     */
    public static function instance() {
        if (self::$instance === NULL) {
            self::$instance = new MongoMeter();
        }
        
        return self::$instance;
    }
    
    /**
     * Log performance metric information about a request
     * 
     * @param string $app application abbreviation
     * @param \Kohana_Request $request
     * @param array $supplemental_data
     */
    public function log_request($app, $request, $supplemental_data = array()) {
        
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
            'user_agent' => array(
            	'ip_address' => Request::$client_ip,
            	'browser' => $supp_info['browser'],
            	'version' => $supp_info['version'],
            	'robot' => $supp_info['robot'],
            	'mobile' => $supp_info['mobile'],
            	'platform' => $supp_info['platform'],
            ),
        );
        
        /**
         * Add supplemental data to document
         */
        foreach ($supplemental_data as $key => $value) {
            $data[$key] = $value;
        }
        
        try {
            $this->collection_realtime->insert($data, array('w' => 0));
        } catch (Exception $e) {
            $connected = ($this->client->connected) ? 'is connected' : 'is not connected';
            Kohana::$log->add(Log::ERROR, 'MongoMetrics failed:' . $e->getMessage() . '<hr />Client ' . $connected);
        }
    }
}

// End DOC_Util_MongoMeter