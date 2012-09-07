<?php
/**
 * @package DOC Core Module
 */
defined('SYSPATH') or die('No direct script access.');

/**
 * DOC_Util_Mysql
 * 
 * Used to allow ad-hoc querying of MySQL database that the Kohana 3 
 * framework doesn't seem to give us.  By default, Auto Commit will be off.
 * 
 * @author Christopher Keith <Christopher_Keith@brown.edu>
 */
class DOC_Util_Mysql {
    
    private static $instances = array();
    
    private $mysqli;
    
    private $statements = array();
    
    /**
     * Class constructor
     * 
     * @param string $config database configuration name
     * @throws Kohana_Exception
     * @todo figure out non-standard port usage
     */
    private function __construct($config = 'default') {
        
        $config = Kohana::$config->load("database.{$config}.connection");
        
        $this->mysqli = mysqli_init();
        
        /**
         * Allow the use of LOAD DATA LOCAL INFILE command
         */
        $this->mysqli->options(MYSQLI_OPT_LOCAL_INFILE, TRUE);
        
        $this->mysqli->real_connect(
                $config['hostname'],
                $config['username'],
                $config['password'],
                $config['database']
        );
        
        if ($this->mysqli->connect_errno) {
            $msg = "Failed to connect to MySQL: " . $this->mysqli->connect_error;
            throw new Kohana_Exception($msg);
        }
        
        $this->mysqli->autocommit(FALSE);
    }
    
    /**
     * Class destructor
     */
    public function __destruct() {
        $this->mysqli->close();
    }
    
    /**
     * Singleton pattern?
     * 
     * @param string $config
     * @return DOC_Util_Mysql
     */
    public static function instance($config = 'default') {
        if ( ! isset(self::$instances[$config])) {
            self::$instances[$config] = new DOC_Util_Mysql($config);
        }

        return self::$instances[$config];
    }
    
    /**
     * Commit a transaction
     * 
     * @return boolean
     */
    public function commit() {
        return $this->mysqli->query('COMMIT;');
    }
    
    /**
     * Use file transfer to achieve bulk data inserts/updates
     * 
     * @param string $path path to local file
     * @param string $table name of table to update
     * @param array $columns ordered list of columns to update
     * @return ?
     */
    public function load($path, $table, $columns) {
        
        $columns = implode(', ', $columns);
        $sql = "
            LOAD DATA LOCAL INFILE '{$path}'
            REPLACE INTO TABLE {$table}
            FIELDS TERMINATED BY '\t' ENCLOSED BY ''
            LINES TERMINATED BY '\n'
            ({$columns}) 
        ";
        
            return $this->mysqli->query($sql);
    }
    
    /**
     * Execute an arbitrary sequel query
     * @param type $sql
     * @return mixed depends on type of query
     */
    public function query($sql) {
        return $this->mysqli->query($sql);
    }
    
    /**
     * Prepare a statement for multiple executions
     * 
     * @throws Kohana_Exception
     * @param string $sql
     * @return MySQLi Statment
     */
    public function prepare($sql) {
        $key = hash('sha256', $sql);
        
        if ( ! array_key_exists($key, $this->statements)) {
            $this->statements[$key] = $this->mysqli->prepare($sql);
        }
        
        if ($this->statements[$key] === FALSE) {
            throw new Kohana_Exception('MySQL: preparing statement failed.');
        }
        
        return $this->statements[$key];
    }
    
    /**
     * Remove a set of records from a table (used in provisioning)
     * 
     * @param string $table
     * @param string $key
     * @param string $values
     */
    public function purge($table, $key, $values) {
        $values = implode(', ', $values);
        
        $sql = "
            DELETE FROM {$table}
            WHERE {$key} IN ({$values})
        ";
        
        return $this->mysqli->query($sql);
    }
    
    /**
     * Rollback a transaction
     * 
     * @return boolean
     */
    public function rollback() {
        return $this->mysqli->query('ROLLBACK;');
    }
    
    /**
     * Begin a transaction
     * 
     * @return boolean
     */
    public function start() {
        return $this->mysqli->query('START TRANSACTION;');
    }
}

// End DOC_Util_Mysql