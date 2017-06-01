<?php
namespace BrownUniversity\DOC\Util\Banner ;
/**
 * @package DOC Core
 * @since 1.0
 * @version 1.0
 * @author Christopher Keith <Christopher_Keith@brown.edu>
 */
defined( 'SYSPATH' ) or die( 'No direct script access.' );

/**
 * Banner Export Utility Class
 * 
 * Helper methods for packaging and transmitting data to the TXFR folders use 
 * by CIS for file-based systems integration
 */
class Export {
    
    /**
     * Has the utiliy class already been initialized?
     * 
     * @var boolean
     */
    protected static $initialized = FALSE ;

    /**
     * Initialize required lookup tables. These will vary by specific application.
     * This should also set self::$initialized to TRUE so that the initialization
     * only happens once.
     */
    protected static function init() {}

	/**
	 * Put a file on the transfer folder
	 *
	 * @param string $local_path
	 * @param string $remote_path
	 * @return bool
	 * @throws \Kohana_Exception
	 */
    public static function put_file($local_path, $remote_path) {
    	
	$server = \Kohana::$config->load('bannerintegration.server');
	$user = \Kohana::$config->load('bannerintegration.username');
	$pass = \Kohana::$config->load('bannerintegration.password');

	/**
	 * Connect to FTPs server
	 */
	$ftps = ftp_ssl_connect($server);
	if ($ftps === FALSE) {
		$msg = "Failed to connect via FTPs to [{$server}] in Banner data exchange.";
		\Kohana::$log->add(\Kohana_Log::ERROR, $msg);
		throw new \Kohana_Exception($msg);
	}

	/**
	 * Login to FTPs server
	 * - ignoring errors to prevent PHP warning
	 */
	$login = @ftp_login($ftps, $user, $pass);
	if ($login === FALSE) {
		$msg = "Failed to login to [{$server}] as user [{$user}] in Banner data exchange.";
		\Kohana::$log->add(\Kohana_Log::ERROR, $msg);
		throw new \Kohana_Exception($msg);
	}

	$pasv = ftp_pasv($ftps, TRUE) ;

	/**
	 * Put the specified file to FTPs server
	 */
	$op = ftp_put($ftps, $remote_path, $local_path, FTP_ASCII);
	if ($op === FALSE) {
		$msg = "Failed to put [{$local_path}] to [{$remote_path}] in Banner data exchange.";
		\Kohana::$log->add(\Kohana_Log::ERROR, $msg);
		throw new \Kohana_Exception($msg);
	}

	/**
	 * Clean-up FTP connection
	 */
	ftp_close($ftps);
	unset($ftps);

	return TRUE;
    }
}

// End DOC_Util_Banner_Export