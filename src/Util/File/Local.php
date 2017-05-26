<?php
namespace BrownUniversity\DOC\Util\File ;
use BrownUniversity\DOC\Util\File ;
/**
 * Use this to work with files on the local file system.
 *
 * @author jorrill
 * @todo test this...
 */
class Local extends File {

	/**
	 * Delete the file.
	 * 
	 * @param string $root_dir
	 * @param string $filename
	 */
	public function delete($root_dir, $filename) {
		$file_path = $root_dir . $filename ;
		
		if( file_exists( $file_path )) {
			unlink( $file_path ) ;
		} else {
			\Kohana::$log->add(Log::WARNING, "Cannot delete-- file not found: {$file_path}") ;
		}
	}

	/**
	 * If the file is deemed "web friendly" then sends inline for display in the
	 * browser, otherwise downloads as a file attachment.
	 * 
	 * @param string $root_dir
	 * @param string $filename
	 * @param string $new_filename
	 */
	public function display($root_dir, $filename, $new_filename = NULL) {
		$file_path = $root_dir . $filename ;
		
		if( empty( $new_filename )) {
			$new_filename = $filename ;
		}

		if( file_exists( $file_path )) {
			$finfo = finfo_open( FILEINFO_MIME, $this->file_config[ 'default' ][ 'mime_magic_file' ]) ;
			$mime_type = finfo_file( $finfo, $file_path ) ;

			if( $this->is_web_friendly( $mime_type )) {
				$this->send_headers($mime_type, $new_filename, $file_path, self::SEND_AS_DISPLAY) ;

				set_time_limit(0) ;
				@readfile( $file_path ) or die( "file not found" ) ;
			} else {
				$this->download( $root_dir, $filename, $new_filename ) ;
			}

		} else {
			header( $_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found', TRUE, 404 ) ;
			die( "Unable to find file." ) ;
		}
	}

	/**
	 * Send file as an attachment.
	 * 
	 * @param string $root_dir
	 * @param string $filename
	 * @param string $new_filename
	 */
	public function download($root_dir, $filename, $new_filename = NULL) {
		$file_path = $root_dir . $filename ;
		
		

		if( $new_filename == NULL ) {
			$new_filename = $filename ;
		}

		if( file_exists( $file_path )) {
			$finfo = finfo_open( FILEINFO_MIME, $this->file_config[ 'default' ][ 'mime_magic_file' ]) ;
			$mime_type = finfo_file( $finfo, $file_path ) ;

			$this->send_headers($mime_type, $new_filename, $file_path, self::SEND_AS_DOWNLOAD) ;

			set_time_limit(0) ;
			@readfile( $file_path ) or die( "file not found" ) ;
		} else {
			header( $_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found', TRUE, 404 ) ;

		}

	}

	/**
	 * Get file as a Swift_Attachment for sending via email.
	 * 
	 * @param string $root_dir
	 * @param string $filename
	 * @param string $new_filename
	 * @return Swift_Attachment
	 */
	public function get_attachment($root_dir, $filename, $new_filename = NULL) {
		$file_path = $root_dir . $filename ;
		
		if( $new_filename == NULL ) {
			$new_filename = $filename ;
		}

		if( !file_exists( $file_path )) {
			die( "Unable to find file." ) ;
		}

		return \Swift_Attachment::fromPath($file_path, $this->get_mime_type($file_path))->setFilename( $new_filename ) ;
	}


	/**
	 * Get the root directory.
	 * 
	 * @param string $root_key
	 * @param string $dir_key
	 * @return string
	 */
	public function get_root_dir($root_key = 'root', $dir_key = 'dir') {
		return $this->file_config[ 'default' ][ $root_key ] . $this->file_config[ 'default' ][ $dir_key ] ;
	}

	/**
	 * Save file to the local filesystem.
	 * 
	 * @param string $root_dir
	 * @param string $filename
	 * @param string $source_path
	 * @param array $attributes
	 */
	public function save($root_dir, $filename, $source_path, $attributes = NULL) {
		$file_path = $root_dir . $filename ;

		\Kohana::$log->add(Log::DEBUG, "Attempting file save, source path = {$source_path}, file path = {$file_path}") ;
		
		copy($source_path, $file_path) ;
	}
	
	/**
	 * Save string to local filesystem.
	 * 
	 * @param string $root_dir
	 * @param string $filename
	 * @param string $contents
	 */
	public function save_string_to_file($root_dir, $filename, $contents, $append = FALSE) {
		$file_path = $root_dir . $filename ;
		if (file_put_contents($file_path, $contents, $append ? FILE_APPEND : 0) !== FALSE) {
			return $file_path ;
		}
	}
}
