<?php
/**
 * @package DOC Core
 * @author Christopher Keith <Christopher_Keith@brown.edu>
 */
defined('SYSPATH') or die('No direct script access.');


/**
 * Kohana MongoDB Log writer
 */
class DOC_Log_Mongo extends Log_Writer {

    /**
     * Application in which the error occured
     * 
     * @var string
     */
    protected $application;
    
    /**
     * MongoDB Client
     *
     * @var MongoClient
     */
    protected static $client;
    
    /**
     * MongoDB Collection
     *
     * @var MongoCollection
     */
    protected static $collection;
    
    /**
     * MongoDB Database
     *
     * @var MongoDatabase
     */
    protected static $db;
    
    /**
     * Environment in which the error occured
     * 
     * @var string
     */
    protected $environment;
    
    /**
     * Lookup table for error levels
     * 
     * @var array
     */
    protected static $levels = array(
        Log::EMERGENCY => 'EMERGENCY',
        Log::ALERT     => 'ALERT',
        Log::CRITICAL  => 'CRITICAL',
        Log::ERROR     => 'ERROR',
        Log::WARNING   => 'WARNING',
        Log::NOTICE    => 'NOTICE',
        Log::INFO      => 'INFO',
        Log::DEBUG     => 'DEBUG',
        Log::STRACE    => 'STRACE',
    );
    
    const TIMEOUT = 5000 ;
    
    /**
     * Class constructor override to facilitate configuration mapping
     */
    public function __construct($environment = NULL, $app = NULL) {
        
        $this->application = $app;
        $this->environment = $environment;
        
        $config = Kohana::$config->load('mongodb')->log;
        self::$client = new MongoClient(
            "mongodb://{$config['host']}:{$config['port']}", 
            array(
                'username' => $config['user'], 
                'password' => $config['password'], 
                'db' => $config['database'],
                'connect' => FALSE,
                'connectTimeoutMS' => self::TIMEOUT,
                'socketTimeoutMS' => self::TIMEOUT,
            )
        );
        
        self::$db = self::$client->selectDB($config['database']);
        self::$collection = self::$db->selectCollection($config['default_collection']);
    }
    
    /**
     * Read log entries from the Mongo DB
     *
     * @param array $limit additional criteria
     * @return MongoCursor
     * @throws Kohana_Exception
     */
    public static function read($limit = 50, $filters = array('level' => 'ERROR')) {
    	
        if ( ! is_a(self::$client, 'MongoClient')) {
            throw new Kohana_Exception('Log Mongo not initialized properly - MongoClient.');
        }
        
        if ( ! self::$client->connected) {
            self::$client->connect();
        }
        
        $cursor = self::$collection->find($filters);
        $cursor->sort(array('timestamp' => -1));
    	$cursor->limit($limit);
    	
        return $cursor;
    }
    
    /**
     * Get one specific Mongo Entry
     * 
     * @param type $id
     * @return array
     * @throws Kohana_Exception
     */
    public static function read_one($id) {
        
        if ( ! is_a(self::$client, 'MongoClient')) {
            throw new Kohana_Exception('Log Mongo not initialized properly - MongoClient.');
        }
        
        if ( ! self::$client->connected) {
            self::$client->connect();
        }
        
        try {
            $output = self::$collection->findOne(
                array(
                    '_id' => $id
                )
            );
        } catch (Exception $e) {
            $output = array();
        }
        
        return $output;
    }
    
    /**
     * Send email messages to a pre-configured list of users for a 
     * pre-configured set of error level conditions
     * 
     * @uses DOC_Util_Mail
     * @param array $messages
     * @throws Kohana_Exception
     */
    public function write(array $messages) {
        
        $supp_info = Request::user_agent(array('browser', 'version', 'robot', 'mobile', 'platform'));
			
        foreach ($messages as $message) {
            $entry = array(
            	'timestamp' => new MongoDate(),
            	'environment' => $this->environment,
            	'application' => $this->application,
            	'level' => self::$levels[$message['level']],
            	'message' => $message['body'],
            	'user_agent' => array(
            		'ip_address' => Request::$client_ip,
            		'browser' => $supp_info['browser'],
            		'version' => $supp_info['version'],
            		'robot' => $supp_info['robot'],
            		'mobile' => $supp_info['mobile'],
            		'platform' => $supp_info['platform'],
            	),
            );
            
            try {

                if ( ! is_a(self::$client, 'MongoClient')) {
                    throw new Kohana_Exception('Log Mongo not initialized properly - MongoClient.');
                }
                
                if ( ! self::$client->connected) {
                    self::$client->connect();
                }
            	self::$collection->insert($entry, array('w' => 0));
            } catch (Exception $e) {
            	// Log an error in a log writer?  Nah... we'll just ignore
            }
        }
    }
    
}

// End DOC_Log_Mongo