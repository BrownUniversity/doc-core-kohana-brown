<?php
/**
 * @package DOC Core Module
 */
defined('SYSPATH') or die('No direct script access.') ;

/**
 * Banner Import Utility Class
 */
abstract class DOC_Util_Banner_Import {

	const DATABASE_NULL = "\N" ;

	/**
	 *
	 * @var boolean
	 */
	protected static $initialized = FALSE ;

	/**
	 *
	 * @var array
	 */
	protected static $ldap_misses = array() ;

	/**
	 *
	 * @var array
	 */
	protected static $user_ids = array() ;

	/**
	 *
	 * @throws Kohana_Exception
	 * @param string $name name of the file
	 * @param string $pattern regular expression to match individual documents
	 * @return array JSON-encoded documents
	 */
	public static function get_file($name, $pattern) {
        /**
         * Initialize data for the file transfer
         */
        $server = Kohana::$config->load('bannerintegration.server');
        $path = Kohana::$config->load('bannerintegration.path');
        $user = Kohana::$config->load('bannerintegration.username');
        $pass = Kohana::$config->load('bannerintegration.password');

        $local = Kohana::$config->load('bannerintegration.local_path')
               . DIRECTORY_SEPARATOR . $name;

        $remote = $path . DIRECTORY_SEPARATOR . $name;

        /**
         * Connect to FTPs server
         */
        $ftps = ftp_ssl_connect($server);
        if ($ftps === FALSE) {
            $msg = "Failed to connect via FTPs to [{$server}] in Banner data exchange.";
            Kohana::$log->add(Log::ERROR, $msg);
            throw new Kohana_Exception($msg);
        }

        /**
         * Login to FTPs server
         * - ignoring errors to prevent PHP warning
         */
        $login = @ftp_login($ftps, $user, $pass);
        if ($login === FALSE) {
            $msg = "Failed to login to [{$server}] as user [{$user}] in Banner data exchange.";
            Kohana::$log->add(Log::ERROR, $msg);
            throw new Kohana_Exception($msg);
        }

        /**
         * Fetch specified file from FTPs server
         */
        $op = ftp_get($ftps, $local, $remote, FTP_ASCII);
        if ($op === FALSE) {
            $msg = "Failed to retrieve [{$remote}] from [{$server}] in Banner data exchange.";
            Kohana::$log->add(Log::ERROR, $msg);
            throw new Kohana_Exception($msg);
        }

        /**
         * Clean-up FTP connection
         */
        ftp_close($ftps);
        unset($ftps);

        /**
         * Read data from local file
         */
        $fp = fopen($local, 'r');
        if ($fp === FALSE) {
            $msg = "Cannot open [{$local}] file in Banner data exchange.";
            Kohana::$log->add(Log::ERROR, $msg);
            throw new Kohana_Exception($msg);
        }

        $data = fread($fp, filesize($local));
        if ($data === FALSE) {
            $msg = "Cannot read [{$local}] file in Banner data exchange.";
            Kohana::$log->add(Log::ERROR, $msg);
            throw new Kohana_Exception($msg);
        }
        $data = preg_replace("#\n#", '', $data);
        fclose($fp);
        unlink($local);

        $_output = array();
        preg_match_all($pattern, $data, $_output);

        if (isset ($_output[0])) {
            return $_output[0];
        } else {
            return array();
        }
	}

	/**
	 * Initialize required lookup tables. These will vary by specific application.
	 * This should also set self::$initialized to TRUE so that the initialization
	 * only happens once.
	 */
	protected static function init() {}
}

// End DOC_Util_Banner_Import