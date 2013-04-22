<?php

/**
 * Foundation class for file utilities.
 *
 * @author jorrill
 */
abstract class DOC_Util_File {

	const TYPE_BY_FILE_EXTENSION = 'extension' ;
	const TYPE_BY_MIME = 'mime' ;
	const SEND_AS_DOWNLOAD = 'download' ;
	const SEND_AS_DISPLAY = 'display' ;
	const CONFIG_FILE = 'file' ;
	const CACHE_LIFETIME = '1 day' ;

	protected $file_config ;
	protected $use_cache = FALSE ;
	protected $allowed_file_types = array() ;

	public function __construct( $config_file = self::CONFIG_FILE ) {
		$this->file_config = Kohana::$config->load( $config_file ) ;
		$this->allowed_file_types = $this->file_config[ 'default' ][ 'allowed_file_types' ] ;
	}

	/**
	 * Save the file. The $root_dir and $filename arguments describe the destination
	 * location, while the $source_path defines the current location of the file.
	 *
	 * @param string $root_dir The root directory where the file should be saved.
	 * @param string $filename The name of the file to save.
	 * @param string $source_path The current location of the file to be saved.
	 * @param array $attributes Any additional attributes required by the filesystem.
	 */
	abstract public function save( $root_dir, $filename, $source_path, $attributes = NULL) ;

	/**
	 * Send the file to the browser as a download.
	 *
	 * @param string $root_dir The directory location of the file.
	 * @param string $filename The name of the file to be downloaded.
	 * @param string $new_filename The name of the file as presented to the browser.
	 */
	abstract public function download( $root_dir, $filename, $new_filename = NULL ) ;

	/**
	 * Send the file to the browser for inline display on the page.
	 *
	 * @param string $root_dir The directory location of the file.
	 * @param string $filename The name of the file to be displayed.
 	 * @param string $new_filename The name of the file as presented to the browser.
	 */
	abstract public function display( $root_dir, $filename, $new_filename = NULL ) ;

	/**
	 * Create a file attachment for use in an email message.
	 *
	 * @param string $root_dir The directory location of the file.
	 * @param string $filename The name of the file to be displayed.
 	 * @param string $new_filename The name of the file as presented to the browser.
	 */
	abstract public function get_attachment($root_dir, $filename, $new_filename = NULL) ;


	/**
	 * Delete the specified file from the filesystem.
	 *
	 * @param string $root_dir The directory location of the file.
	 * @param string $filename The name of the file to be deleted.
	 */
	abstract public function delete( $root_dir, $filename ) ;

	/**
	 * Get the root directory for file storage. The $root_key and $dir_key
	 * arguments should refer to array keys in the config file used.
	 *
	 * @param string $root_key
	 * @param string $dir_key
	 */
	abstract public function get_root_dir( $root_key = NULL, $dir_key = NULL ) ;


	/**
	 * Sets whether caching should be allowed.
	 *
	 * @param boolean $new_state
	 */
	public function cache_allowed($new_state) {
		$this->use_cache = $new_state ;
	}

	/**
	 * Given the filepath, returns the MIME type of the file.
	 *
	 * @param string $filepath
	 * @return string
	 */
	public function get_mime_type( $filepath ) {
		$finfo = finfo_open(FILEINFO_MIME,$this->file_config[ 'default' ][ 'mime_magic_file' ]) ;
		$mime_type = finfo_file($finfo, $filepath) ;

		return $mime_type ;
	}

	/**
	 * Test whether a given file is of a type that is allowed to be uploaded.
	 *
	 * @param string $filepath The filepath of the file to test.
	 * @return boolean
	 */
	public function file_type_allowed( $filepath ) {

		if( count( $this->allowed_file_types ) == 0 ) {
			return TRUE ;
		}
		$mime_type = $this->get_mime_type( $filepath ) ;

		return in_array($mime_type, $this->allowed_file_types) ;
	}

	/**
	 * Override the file types allowed.
	 *
	 * @param array $allowed_file_types array in the format 'extension' => 'MIME type' (i.e. 'gif' => 'image/gif')
	 */
	public function set_allowed_file_types( $allowed_file_types ) {
		$this->allowed_file_types = $allowed_file_types ;
	}

	/**
	 * Get an array of file types that are allowed to be uploaded.
	 *
	 * @param string $list_by Use one of the class constants.
	 * @return array An array of either extensions or MIME types.
	 */
	public function get_allowable_file_types( $list_by = self::TYPE_BY_FILE_EXTENSION ) {
		$_output = array_keys( $this->allowed_file_types ) ;

		if( $list_by == self::TYPE_BY_MIME ) {
			$_output = array_unique($this->allowed_file_types) ;
		}

		return $_output ;
	}

	/**
	 * Verify that the file is within the filesize limit as set in the config file.
	 *
	 * @param int $filesize
	 * @return boolean
	 */
	public function file_size_ok( $filesize ) {
		return $filesize <= $this->file_config[ 'default' ][ 'max_file_size' ] ;
	}

	/**
	 * Given an PHP file error code, returns a human-friendly error message.
	 *
	 * @param int $error_code One of the standard PHP file upload error codes
	 * @return string
	 */
	public static function get_readable_error( $error_code ) {
		$_output = 'Unknown error.' ;
		$errors = array(
			0 => 'Success',
			1 => 'File size too large (system).',
			2 => 'File size too large (local).',
			3 => 'File partially uploaded.',
			4 => 'No file uploaded.',
			6 => 'No temporary folder to write to.',
			7 => 'Failed to write to disk.',
			8 => 'Upload blocked by PHP extension.'
		) ;

		if(array_key_exists( $error_code, $errors )) {
			$_output = $errors[ $error_code ] ;
		}

		return $_output ;
	}

	/**
	 * Replaces any non-alphanumeric characters, underscores or periods with a dash.
	 *
	 * @param string $original_filename
	 * @return string
	 */
	public static function safe_filename( $original_filename ) {
		return preg_replace('/[^A-Za-z0-9_\.]/','-', $original_filename) ;
	}

	/**
	 * Given the file specifications provided, send the file either inline or as
	 * an attachment to be downloaded.
	 *
	 * @param string $content_type
	 * @param string $filename
	 * @param string $filepath
	 * @param string $send_as Either SEND_AS_DISPLAY or SEND_AS_DOWNLOAD
	 * @param array $headers Additional headers to be sent.
	 */
	protected function send_headers( $content_type, $filename, $filepath, $send_as = self::SEND_AS_DOWNLOAD, $headers = array()) {
		$stat = stat( $filepath ) ;
		/*
		 * The common wisdom is that the zlib bit here is "required for IE, otherwise
		 * Content-disposition is ignored". However, I tested with this commented
		 * out in IE 8 and it worked fine...
		 */

		if(ini_get('zlib.output_compression')) {
			ini_set('zlib.output_compression', 'Off');
		}

		header("Pragma: public");
		if( $send_as == self::SEND_AS_DISPLAY && $this->use_cache == TRUE ) {
			header("Expires: ".gmdate('D, d M Y H:i:s',strtotime('+'.self::CACHE_LIFETIME))." GMT") ;
			header("Last-Modified: " . gmdate("D, d M Y H:i:s", $stat['mtime']) . " GMT") ;
		} else {
			header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // some day in the past
			header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		}

		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: private",false);
		header("Content-type: {$content_type}");

		if( $send_as == self::SEND_AS_DISPLAY ) {
			header('Content-Disposition: inline; filename="'.$filename.'"');
		} else {
			header('Content-Disposition: attachment; filename="'.$filename.'"');
		}
		header("Content-Transfer-Encoding: binary");
		header("Content-Length: {$stat['size']}");

		if( is_array( $headers ) && count( $headers ) > 0 ) {
			foreach( $headers as $header ) {
				header( $header ) ;
			}
		}
	}

	/**
	 * Given a file on the server, generate a file specs array the equivalent to
	 * what we would have received in a file upload from the _FILES array. This
	 * is required to enable us to generate files on the fly and have them
	 * be treated as if they were a normal file upload.
	 *
	 * @param string $file Full path and filename to the file for which we want specs generated
	 * @param string $original_filename An original filename to insert into the array.
	 * @return array
	 */
	public static function get_file_specs( $filename, $original_filename ) {
		$_output = array() ;
		$file_util = new DOC_Util_File_Local() ;
		if( file_exists( $filename )) {
			$_output[ 'name' ] = $original_filename ;
			$_output[ 'type' ] = $file_util->get_mime_type( $filename );
			$_output[ 'size' ] = filesize( $filename ) ;
			$_output[ 'tmp_name' ] = $filename ;
			$_output[ 'error' ] = UPLOAD_ERR_OK ;
		} else {
			// throw an exception...
		}

		return $_output ;
	}

	/**
	 * By default the incoming $_FILES array structure for an array of files is a little non-standard
	 * in that we get a single item where the properties have multiple rows. This restructures
	 * the array to better parallel what we would get from normal POST data. Stolen from PHP
	 * user-contributed notes at http://www.php.net/manual/en/reserved.variables.files.php#106608.
	 *
	 * @param array $files Assumed to be the _FILES array from the form submission.
	 * @param bool $top Indicates whether this is the first time through or not.
	 */
	public static function restructure_files_array( $files, $top = TRUE ) {
		$_out_files = array();
		foreach($files as $name=>$file){
			if( $top ) {
				$sub_name = $file['name'];
			} else {
				$sub_name = $name;
			}
			if(is_array($sub_name)){
				foreach(array_keys($sub_name) as $key){
					$_out_files[$name][$key] = array(
						'name'     => $file['name'][$key],
						'type'     => $file['type'][$key],
						'tmp_name' => $file['tmp_name'][$key],
						'error'    => $file['error'][$key],
						'size'     => $file['size'][$key],
					);
					$_out_files[$name] = self::restructure_files_array($_out_files[$name], FALSE);
				}
			}else{
				$_out_files[$name] = $file;
			}
		}
		return $_out_files;
	}

	/**
	 * Check the given MIME type string to see if we have deemed it "web friendly."
	 * A more appropriate term might be "browser friendly", since that's the context
	 * this will most likely be used in.
	 *
	 * @param string $mime_type
	 * @return boolean
	 */
	public function is_web_friendly($mime_type) {
		$friendly_mime_types = array(
			'application/pdf',
			'image/jpeg',
			'video/jpeg',
			'image/gif',
			'image/png',
			'application/x-shockwave-flash',
			'audio/mpeg',
			'video/mpeg',
			'audio/mp4',
			'video/mp4',
			'video/quicktime',
			'image/tiff'
		) ;

		return in_array($mime_type, $friendly_mime_types) ;
	}
	
	/**
	 * Check the given MIME type string to see if it's a valid image file.
	 *
	 * @param string $mime_type
	 * @return boolean
	 */
	public function is_image( $mime_type ) {
		$valid_mime_types = array(
			'image/jpeg',
			'video/jpeg',
			'image/gif',
			'image/png',
			'image/tiff'
		) ;

		return in_array($mime_type, $valid_mime_types) ;
	}
}

