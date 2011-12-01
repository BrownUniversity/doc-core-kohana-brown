<?php

require_once( Kohana::find_file('classes', 'sdk-1.4.7/sdk.class')) ;

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

	public function delete($root_dir, $filename) {
		$response = $this->s3->delete_object( $root_dir, $filename ) ;
		
		return $response->isOK() ;
	}

	public function display($root_dir, $filename) {
		
		$headers = $this->s3->get_object_headers( $root_dir, $filename ) ;
		$info = $headers->header['_info'] ;
		
		$file = $this->s3->get_object( 
				$root_dir, 
				$filename, 
				array( 'returnCurlHandle' => TRUE )
		) ;
		
		$this->send_headers($info[ 'content_type' ], $filename, $info[ 'download_content_length'], self::SEND_AS_DISPLAY) ;
		
		curl_setopt( $file, CURLOPT_HEADER, FALSE ) ;
		curl_setopt( $file, CURLOPT_RETURNTRANSFER, FALSE ) ;
		curl_exec( $file ) ;
		
	}

	public function download($root_dir, $filename, $new_filename = NULL) {
		if( $new_filename == NULL ) {
			$new_filename = $filename ;
		}
		
		$headers = $this->s3->get_object_headers( $root_dir, $filename ) ;
		$info = $headers->header['_info'] ;
		
		$file = $this->s3->get_object( 
				$root_dir, 
				$filename, 
				array( 'returnCurlHandle' => TRUE )
		) ;
		
		$this->send_headers($info[ 'content_type' ], $new_filename, $info[ 'download_content_length'], self::SEND_AS_DOWNLOAD) ;
		
		curl_setopt( $file, CURLOPT_HEADER, FALSE ) ;
		curl_setopt( $file, CURLOPT_RETURNTRANSFER, FALSE ) ;
		curl_exec( $file ) ;
		
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
		
		$response = $this->s3->create_object( $root_dir, $filename, $attributes ) ;
		
		return $response->isOK() ;
	}
	
}

?>
