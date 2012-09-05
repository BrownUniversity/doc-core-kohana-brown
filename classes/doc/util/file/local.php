<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Use this to work with files on the local file system.
 *
 * @author jorrill
 * @todo TEST THIS
 */
class DOC_Util_File_Local extends DOC_Util_File {

	const UPLOAD_SUFFIX = '.upload' ;

	public function delete($root_dir, $filename) {
		$file_path = $root_dir . $filename ;
		if( !file_exists( $file_path )) {
			$file_path .= self::UPLOAD_SUFFIX ;
		}
		unlink( $file_path ) ;
	}

	public function display($root_dir, $filename, $new_filename = NULL) {
		$file_path = $root_dir . $filename ;
		if( !file_exists( $file_path )) {
			$file_path .= self::UPLOAD_SUFFIX ;
		}

		if( file_exists( $file_path )) {
			$finfo = finfo_open( FILEINFO_MIME, $this->file_config[ 'default' ][ 'mime_magic_file' ]) ;
			$mime_type = finfo_file( $finfo, $file_path ) ;

			if( $this->is_web_friendly( $mime_type )) {
				$this->send_headers($mime_type, $filename, @filesize($file_path), self::SEND_AS_DISPLAY) ;

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

	public function download($root_dir, $filename, $new_filename = NULL) {
		$file_path = $root_dir . $filename ;
		if( !file_exists( $file_path )) {
			$file_path .= self::UPLOAD_SUFFIX ;
		}


		if( $new_filename == NULL ) {
			$new_filename = $filename ;
		}

		if( file_exists( $file_path )) {
			$finfo = finfo_open( FILEINFO_MIME, $this->file_config[ 'default' ][ 'mime_magic_file' ]) ;
			$mime_type = finfo_file( $finfo, $file_path ) ;

			$this->send_headers($mime_type, $new_filename, @filesize($file_path), self::SEND_AS_DOWNLOAD) ;

			set_time_limit(0) ;
			@readfile( $file_path ) or die( "file not found" ) ;
		} else {
			header( $_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found', TRUE, 404 ) ;

		}

	}

	public function get_attachment($root_dir, $filename, $new_filename = NULL) {
		$file_path = $root_dir . $filename ;
		if( !file_exists( $file_path )) {
			$file_path .= self::UPLOAD_SUFFIX ;
		}
		if( $new_filename == NULL ) {
			$new_filename = $filename ;
		}

		if( !file_exists( $file_path )) {
			die( "Unable to find file." ) ;
		}

		return Swift_Attachment::newInstance($file_path, $new_filename, $info['content_type']) ;
	}


	public function get_root_dir($root_key = NULL, $dir_key = NULL) {
		return $file_config[ 'default' ][ $root_key ] . $file_config[ 'default' ][ $dir_key ] ;
	}

	public function save($root_dir, $filename, $source_path, $attributes = NULL) {
		$file_path = $root_dir . $filename . self::UPLOAD_SUFFIX ;

		move_uploaded_file($source_path, $file_path) ;
	}

}

?>
