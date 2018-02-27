<?php
namespace BrownUniversity\DOC\Util\Banner ;
/**
 * @package DOC Core Module
 */
use Kohana\Kohana;
use Kohana\KohanaException;
use Kohana\Log;

defined('SYSPATH') or die('No direct script access.');

/**
 * Banner Import Utility Class
 */
abstract class Import {

	const DATABASE_NULL = "\N";

	/**
	 *
	 * @var boolean
	 */
	protected static $initialized = FALSE;

	/**
	 *
	 * @var array
	 */
	protected static $ldap_misses = array();

	/**
	 *
	 * @var array
	 */
	protected static $user_ids = array();

	/**
	 * Read a file from the CIS Transfer FTPs File System
	 *
	 * @param string $name name of the file
	 * @param string $pattern regular expression to match individual documents
	 * @param string $local_path root directory for local file storage.
	 * @param bool   $delete_downloaded_file
	 * @return array
	 * @throws KohanaException
	 */
	public static function get_file($name, $pattern, $local_path, $delete_downloaded_file = TRUE) {

		/**
		 * Initialize data for the file transfer
		 */
		$server = Kohana::$config->load('bannerintegration.server');
		$path = Kohana::$config->load('bannerintegration.path');
		$user = Kohana::$config->load('bannerintegration.username');
		$pass = Kohana::$config->load('bannerintegration.password');

		$local = $local_path . $name;
		$remote = $path . $name;

		Kohana::$log->add(Log::DEBUG, "Attempting FTPs connection to [{$server}] to fetch [{$remote}] into [{$local}]") ;
		
		/**
		 * Connect to FTPs server
		 */
		$ftps = ftp_ssl_connect($server);
		if ($ftps === FALSE) {
			$msg = "Failed to connect via FTPs to [{$server}] in Banner data exchange.";
			Kohana::$log->add(Log::ERROR, $msg);
			throw new KohanaException($msg);
		}

		/**
		 * Login to FTPs server
		 * - ignoring errors to prevent PHP warning
		 */
		$login = @ftp_login($ftps, $user, $pass);
		if ($login === FALSE) {
			$msg = "Failed to login to [{$server}] as user [{$user}] in Banner data exchange.";
			Kohana::$log->add(Log::ERROR, $msg);
			throw new KohanaException($msg);
		}

		$pasv = ftp_pasv($ftps, TRUE);

		/**
		 * Fetch specified file from FTPs server
		 */
		$op = ftp_get($ftps, $local, $remote, FTP_ASCII);
		if ($op === FALSE) {
			$msg = "Failed to retrieve [{$remote}] from [{$server}] in Banner data exchange.";
			Kohana::$log->add(Log::ERROR, $msg);
			throw new KohanaException($msg);
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
			throw new KohanaException($msg);
		}

		$data = fread($fp, filesize($local));
		fclose($fp);

		if ($data === FALSE) {
			$msg = "Cannot read [{$local}] file in Banner data exchange.";
			Kohana::$log->add(Log::ERROR, $msg);
			throw new KohanaException($msg);
		}

		if ($delete_downloaded_file === TRUE) {
			unlink($local);
		}

		$_output = array();
		preg_match_all($pattern, $data, $_output);

		if (isset($_output[0])) {
			return $_output[0];
		} else {
			$msg = "No regex match in [{$local}] file.";
			Kohana::$log->add(Log::ERROR, $msg);
			throw new KohanaException($msg);
		}
	}

	/**
	 * Read a file from the CIS Transfer FTPs File System
	 *
	 * @param string  $name name of the file
	 * @param string  $local_path root directory for local file storage.
	 * @param array   $column_names Optional column names. If provided, records will be returned as associative arrays. Otherwise, numerically indexed arrays.
	 * @param boolean $latest If provided, grab the matching file with the newest timestamp. Otherwise, use the provided filename.
	 * @param boolean $delete_downloaded_file Optional parameter to control automatic deletion of local files.
	 * @return array
	 * @throws KohanaException
	 */
	protected static function get_csv($name, $local_path, $column_names = array(), $latest = NULL, $delete_downloaded_file = TRUE) {

		if ($column_names == FALSE) {
			$column_names = array();
		}

		/**
		 * Initialize data for the file transfer
		 */
		$server = Kohana::$config->load('bannerintegration.server');
		$path = Kohana::$config->load('bannerintegration.path');
		$user = Kohana::$config->load('bannerintegration.username');
		$pass = Kohana::$config->load('bannerintegration.password');

		$local = $local_path . $name;
		$remote = $path . $name;

		/**
		 * Connect to FTPs server
		 */
		$ftps = ftp_ssl_connect($server);
		if ($ftps === FALSE) {
			$msg = "Failed to connect via FTPs to [{$server}] in Banner data exchange.";
			Kohana::$log->add(Log::ERROR, $msg);
			throw new KohanaException($msg);
		}

		/**
		 * Login to FTPs server
		 * - ignoring errors to prevent PHP warning
		 */
		$login = @ftp_login($ftps, $user, $pass);
		if ($login === FALSE) {
			$msg = "Failed to login to [{$server}] as user [{$user}] in Banner data exchange.";
			Kohana::$log->add(Log::ERROR, $msg);
			throw new KohanaException($msg);
		}

		$pasv = ftp_pasv($ftps, TRUE);

		/**
		 * Determine which file to grab
		 */
		if ($latest != NULL) {
			$files = ftp_rawlist($ftps, $path);
			$max_timestamp = 0;
			foreach ($files as $f) {
				$matches = array();
				if (preg_match('#^(\d\d-\d\d-\d\d\s+\d\d:\d\d[AP]M)\s+\S+\s+(' . $latest . '.*)$#', $f, $matches)) {
					$timestamp = date_timestamp_get(date_create_from_format('m-d-y  h:iA', $matches[1]));
					if ($timestamp >= $max_timestamp) {
						$max_timestamp = $timestamp;
						$remote = $path . $matches[2];
					}
				}
			}
		}

		/**
		 * Fetch specified file from FTPs server
		 */
		$op = ftp_get($ftps, $local, $remote, FTP_ASCII);
		if ($op === FALSE) {
			$msg = "Failed to retrieve [{$remote}] from [{$server}] in Banner data exchange.";
			Kohana::$log->add(Log::ERROR, $msg);
			throw new KohanaException($msg);
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
			throw new KohanaException($msg);
		}

		$data = array();
		while (($d = fgetcsv($fp)) !== FALSE) {
			$row = array();
			$len = count($d);
			for ($i = 0; $i < $len; $i++) {
				if (isset($column_names[$i])) {
					$row[$column_names[$i]] = $d[$i];
				} else {
					$row[$i] = $d[$i];
				}
			}
			$data[] = $row;
		}
		fclose($fp);

		if (empty($data)) {
			$msg = "Cannot read [{$local}] file in Banner data exchange.";
			Kohana::$log->add(Log::ERROR, $msg);
			throw new KohanaException($msg);
		}

		if ($delete_downloaded_file === TRUE) {
			unlink($local);
		}

		return $data;
	}

	/**
	 * Initialize required lookup tables. These will vary by specific application.
	 * This should also set self::$initialized to TRUE so that the initialization
	 * only happens once.
	 */
	protected static function init() {
		
	}

	/**
	 * Pre-process data read in from the data files
	 * 
	 * @param string $input
	 * @return string
	 */
	protected static function preproc($input) {
		return trim($input);
	}

}

// End DOC_Util_Banner_Import