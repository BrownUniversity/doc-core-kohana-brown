<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of s3
 *
 * @author jorrill
 */
class DOC_Util_File_S3 extends DOC_Util_File {

	protected $aws_config ;
	protected $s3 ;


	public function __construct($config_file = self::CONFIG_FILE) {
		parent::__construct($config_file);
		$this->aws_config = Kohana::$config->load('aws') ;
		$this->s3 = new AmazonS3( $this->aws_config[ 'key' ], $this->aws_config[ 'secret' ]) ;
	}

    /**
     * Download the file from S3 and cache to read for re-uploading
     *
     * @param string $root_dir
     * @param string $filename
     * @return string
     */
    public function cache_file($root_dir, $filename) {
        return $this->retrieve_file( $root_dir, $filename );
    }

	public function delete($root_dir, $filename) {
		$response = $this->s3->delete_object( $root_dir, $filename ) ;
		// also delete from the cache
		if( file_exists( $this->aws_config['cache_path'] . $filename )) {
			unlink( $this->aws_config['cache_path'] . $filename ) ;
		}

		return $response->isOK() ;
	}

	public function display($root_dir, $filename, $new_filename = NULL) {
		if( $new_filename == NULL ) {
			$new_filename = $filename ;
		}

		$file_path = $this->retrieve_file( $root_dir, $filename ) ;
		$finfo = finfo_open( FILEINFO_MIME, $this->file_config[ 'default' ][ 'mime_magic_file' ]) ;
		$mime_type = finfo_file( $finfo, $file_path ) ;

		$this->send_headers($mime_type, $new_filename, @filesize($file_path), self::SEND_AS_DISPLAY) ;

		set_time_limit(0) ;
		@readfile( $file_path ) or die( "file not found" ) ;

	}

	public function download($root_dir, $filename, $new_filename = NULL) {
		if( $new_filename == NULL ) {
			$new_filename = $filename ;
		}

		$file_path = $this->retrieve_file( $root_dir, $filename ) ;
		$finfo = finfo_open( FILEINFO_MIME, $this->file_config[ 'default' ][ 'mime_magic_file' ]) ;
		$mime_type = finfo_file( $finfo, $file_path ) ;

		$this->send_headers($mime_type, $new_filename, @filesize($file_path), self::SEND_AS_DOWNLOAD) ;

		set_time_limit(0) ;
		@readfile( $file_path ) or die( "file not found" ) ;

	}

	public function get_attachment($root_dir, $filename, $new_filename = NULL) {
		if( $new_filename == NULL ) {
			$new_filename = $filename ;
		}

		$local_file = $this->retrieve_file( $root_dir, $filename ) ;
		return Swift_Attachment::fromPath( $local_file, $this->get_mime_type( $local_file))->setFilename( $new_filename ) ;
	}

	public function get_root_dir($root_key = NULL, $dir_key = NULL) {
		return $this->aws_config['bucket'] ;
	}

	public function save($root_dir, $filename, $source_path, $attributes = NULL) {
		if( is_array( $attributes )) {
			$attributes['fileUpload'] = $source_path ;
		} else {
			$attributes = array('fileUpload' => $source_path) ;
		}
		$attributes[ 'contentType' ] = $this->get_mime_type( $source_path ) ;

		$response = $this->s3->create_object( $root_dir, $filename, $attributes ) ;

		return $response->isOK() ;
	}

	private function retrieve_file($root_dir, $filename) {
		// check the cache for the file and use it if it's 24 hours old or less
		$cached_file = $this->aws_config['cache_path'] . $filename ;
		if( file_exists( $cached_file )) {
			$stat = stat($cached_file) ;
			if( $stat['mtime'] >= strtotime('-1 day')) {
				return $cached_file ;
			} else {
				unlink( $cached_file ) ;
			}
		}
		// no valid cache exists, retrieve from AWS. We'll still use the same $cached_file location
		$response = $this->s3->get_object(
				$root_dir,
				$filename,
				array('fileDownload' => $cached_file)
		) ;

		return $cached_file ;
	}

}
