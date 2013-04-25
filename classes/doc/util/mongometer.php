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
     * Instance of DOC_Util_MongoMeter
     * 
     * @var DOC_Util_MongoMeter
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
     * Class constructor
     */
    public function __construct() {
        $config = Kohana::$config->load('mongometer');
        $this->client = new MongoClient(
            "mongodb://{$config->host}:{$config->port}", 
            array(
                'username' => $config->user, 
                'password' => $config->password, 
                'db' => $config->database,
                'connect' => TRUE,
                'connectTimeoutMS' => $config->timeout,
                'socketTimeoutMS' => $config->timeout,
            )
        );
        $this->database = $this->client->selectDB($config->database);
        $this->collection_realtime = $this->database->selectCollection($config->collections['realtime']);
        $this->collection_daily = $this->database->selectCollection($config->collections['daily']);
        $this->collection_hourly = $this->database->selectCollection($config->collections['hourly']);
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
            self::$instance = new DOC_Util_MongoMeter();
        }
        
        return self::$instance;
    }
    
    /**
     * Log performance metric information about a request
     * 
     * @param string $app application abbreviation
     * @param Kohana_Request $request
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