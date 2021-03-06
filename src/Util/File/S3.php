<?php
namespace BrownUniversity\DOC\Util\File ;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client ;
use BrownUniversity\DOC\Util\File ;
use ErrorException;
use Kohana\HTTP\Exception\Code_404;
use Kohana\Kohana;
use Kohana\Log;

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Use this to work with files on Amazon S3 and cached on the local file system.
 *
 * @author jorrill
 */
class S3 extends File {

	protected $aws_config ;
	protected $s3 ;


    /**
     * S3 constructor.
     *
     * @param string $config_file
     * @throws \Kohana\KohanaException
     */
    public function __construct($config_file = self::CONFIG_FILE) {
		parent::__construct($config_file);
		$this->aws_config = Kohana::$config->load('aws') ;
		$this->s3 = S3Client::factory(array( 'key' => $this->aws_config[ 'key' ], 'secret' => $this->aws_config[ 'secret' ])) ;				
	}

    /**
     * Download the file from S3 and cache to read for re-uploading
     *
     * @param string $root_dir
     * @param string $filename
     * @return string
     * @throws \ErrorException
     */
    public function cache_file($root_dir, $filename) {
		$_output = NULL ;
		try {
			$_output = $this->retrieve_file( $root_dir, $filename );
		} catch (ErrorException $ex) {
			throw $ex ;
		}
		
		
        return $_output ;
    }

    /**
     * Delete the file both from S3 and from the local cache, if it exists.
     *
     * @param string $root_dir
     * @param string $filename
     * @return boolean
     * @throws \ErrorException
     */
	public function delete($root_dir, $filename) {
		try {
			$response = $this->s3->deleteObject( array( 
				'Bucket' => $root_dir,
				'Key' => $filename
			)) ;
		} catch( S3Exception $e ) {
			$error = $e->parse() ;
			throw new ErrorException("{$error['message']} (type: {$error['type']}, code: {$error['code']})");
		}

		// also delete from the cache
		if( file_exists( $this->aws_config['cache_path'] . $filename )) {
			unlink( $this->aws_config['cache_path'] . $filename ) ;
		}

		return TRUE ;
	}

    /**
     * Sends the file to the browser. If the file is "web friendly" then sends
     * inline, otherwise will send as an attachment for download.
     *
     * @param string $root_dir
     * @param string $filename
     * @param string $new_filename
     * @throws \Kohana\HTTP\Exception\Code_404
     */
	public function display($root_dir, $filename, $new_filename = NULL) {
		if( $new_filename == NULL ) {
			$new_filename = $filename ;
		}

		try {
			$file_path = $this->retrieve_file( $root_dir, $filename ) ;
			$finfo = finfo_open( FILEINFO_MIME, $this->file_config[ 'default' ][ 'mime_magic_file' ]) ;
			$mime_type = finfo_file( $finfo, $file_path ) ;

			if( $this->is_web_friendly( $mime_type )) {
				$this->send_headers($mime_type, $new_filename, $file_path, self::SEND_AS_DISPLAY) ;

				set_time_limit(0) ;
				@readfile( $file_path ) or die( "file not found" ) ;

			} else {
				$this->download( $root_dir, $filename, $new_filename ) ;
			}			
		} catch( ErrorException $e ) {
			throw new Code_404($e->getMessage());
		}
	}

    /**
     * Send file as an attachment for download.
     *
     * @param string $root_dir
     * @param string $filename
     * @param string $new_filename
     * @throws \Kohana\HTTP\Exception\Code_404
     */
	public function download($root_dir, $filename, $new_filename = NULL) {
		if( $new_filename == NULL ) {
			$new_filename = $filename ;
		}

		try {
			$file_path = $this->retrieve_file( $root_dir, $filename ) ;
			$finfo = finfo_open( FILEINFO_MIME, $this->file_config[ 'default' ][ 'mime_magic_file' ]) ;
			$mime_type = finfo_file( $finfo, $file_path ) ;

			$this->send_headers($mime_type, $new_filename, $file_path, self::SEND_AS_DOWNLOAD) ;

			set_time_limit(0) ;
			@readfile( $file_path ) or die( "file not found" ) ;
			
		} catch( ErrorException $e ) {
			throw new Code_404($e->getMessage()) ;
		}
		

	}

	/**
	 * Return file as a Swift_Attachment for sending with an email message.
	 * 
	 * @param string $root_dir
	 * @param string $filename
	 * @param string $new_filename
	 * @return Swift_Attachment
	 * @throws ErrorException
	 */
	public function get_attachment($root_dir, $filename, $new_filename = NULL) {
		if( $new_filename == NULL ) {
			$new_filename = $filename ;
		}

		try {
			$local_file = $this->retrieve_file( $root_dir, $filename ) ;
		} catch( ErrorException $e ) {
			throw $e ;
		}
		
		return \Swift_Attachment::fromPath( $local_file, $this->get_mime_type( $local_file))->setFilename( $new_filename ) ;
	}

	/**
	 * For S3, the root directory will always be the bucket as specified in the 
	 * config file.
	 * 
	 * @param string $root_key
	 * @param string $dir_key
	 * @return string
	 */
	public function get_root_dir($root_key = NULL, $dir_key = NULL) {
		return $this->aws_config['bucket'] ;
	}

    /**
     * Save file to S3.
     *
     * @param string $root_dir
     * @param string $filename
     * @param string $source_path
     * @param array  $attributes
     * @return boolean
     * @throws \ErrorException
     */
	public function save($root_dir, $filename, $source_path, $attributes = NULL) {
	
		try {
			if( is_array( $attributes )) {
				$attributes['fileUpload'] = $source_path ;
			} else {
				$attributes = array('fileUpload' => $source_path) ;
			}
			$attributes[ 'contentType' ] = $this->get_mime_type( $source_path ) ;

			$object_args = $attributes ;
			$object_args['Bucket'] = $root_dir ;
			$object_args['Key'] = $filename ;
			$object_args['SourceFile'] = $source_path ;


			$response = $this->s3->createObject( $object_args ) ;

		} catch( S3Exception $e ) {
			$error = $e->parse() ;
			throw new ErrorException("{$error['message']} (type: {$error['type']}, code: {$error['code']})");
		}	

		return TRUE ;
	}

	/**
	 * Pulls a file from S3 into the local cache and returns the local file path.
	 * 
	 * @param string $root_dir
	 * @param string $filename
	 * @return string
	 * @throws ErrorException
	 */
	private function retrieve_file($root_dir, $filename) {
		// check the cache for the file and use it if it's within the cache lifetime
		$cached_file = $this->aws_config['cache_path'] . $filename ;
		if( file_exists( $cached_file )) {
			$stat = stat($cached_file) ;
			if( $stat['mtime'] >= strtotime('-'.self::CACHE_LIFETIME)) {
				return $cached_file ;
			} else {
				unlink( $cached_file ) ;
			}
		}

		if( $this->s3->doesObjectExist($root_dir, $filename )) {
			
			// No valid cache exists, retrieve from AWS. We'll still use the same $cached_file location.
			$object_args = array(
				'Bucket' => $root_dir,
				'Key' => $filename,
				'SaveAs' => $cached_file
			) ;
			$response = $this->s3->getObject( $object_args ) ;
				
		} else {
			if( file_exists( $cached_file )) {
				unlink( $cached_file ) ;
			}
			throw new ErrorException("Cannot find {$filename} in bucket {$root_dir}");
		}
		
		
		return $cached_file ;
	}

	/**
	 * Verify that the given file exists on S3.
	 * 
	 * @param string $root_dir
	 * @param string $filename
	 * @return boolean
	 */
	public function file_exists( $root_dir, $filename ) {
		try {
			$_output = $this->s3->doesObjectExist($root_dir, $filename) ;
		} catch (\Exception $ex) {
			$_output = FALSE ;
			Kohana::$log->add(Log::WARNING,'Error communicating with AWS: ' . $ex->getMessage()) ;
		}
		return $_output ;
	}
	
	/**
	 * Get the AmazonS3 property from this object.
	 * 
	 * @return \Aws\S3\S3Client
     */
	public function get_s3_object() {
		return $this->s3 ;
	}

}
