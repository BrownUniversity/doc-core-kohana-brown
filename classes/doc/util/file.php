<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of file
 *
 * @author jorrill
 */
abstract class DOC_Util_File {

	const TYPE_BY_FILE_EXTENSION = 'extension' ;
	const TYPE_BY_MIME = 'mime' ;
	const SEND_AS_DOWNLOAD = 'download' ;
	const SEND_AS_DISPLAY = 'display' ;
	const CONFIG_FILE = 'file' ;

	protected $file_config ;

	public function __construct( $config_file = self::CONFIG_FILE ) {
		$this->file_config = Kohana::$config->load( $config_file ) ;
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
	 */
	abstract public function display( $root_dir, $filename, $new_filename = NULL ) ;

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

		if( count( $this->file_config[ 'default' ][ 'allowed_file_types' ] ) == 0 ) {
			return TRUE ;
		}
		$mime_type = $this->get_mime_type( $filepath ) ;

		return in_array($mime_type, $this->file_config[ 'default' ][ 'allowed_file_types' ]) ;
	}

	/**
	 * Get an array of file types that are allowed to be uploaded.
	 *
	 * @param string $list_by Use one of the class constants.
	 * @return array An array of either extensions or MIME types.
	 */
	public function get_allowable_file_types( $list_by = self::TYPE_BY_FILE_EXTENSION ) {
		$_output = array_keys( $this->file_config[ 'default' ][ 'allowed_file_types' ]) ;

		if( $list_by == self::TYPE_BY_MIME ) {
			$_output = array_unique($this->file_config[ 'default' ][ 'allowed_file_types' ]) ;
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

	protected function send_headers( $content_type, $filename, $content_length, $send_as = self::SEND_AS_DOWNLOAD ) {

		// required for IE, otherwise Content-disposition is ignored
		if(ini_get('zlib.output_compression')) {
			ini_set('zlib.output_compression', 'Off');
		}

		header("Pragma: public");
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // some day in the past
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: private",false);

		if( $send_as == self::SEND_AS_DISPLAY ) {
			header("Content-type: {$content_type}");
			header('Content-Disposition: inline; filename="'.$filename.'"');
		} else {
			header("Content-type: application/x-download");
			header('Content-Disposition: attachment; filename="'.$filename.'"');
		}
		header("Content-Transfer-Encoding: binary");
		header("Content-Length: {$content_length}");
	}


}

?>
