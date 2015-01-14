<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ords
 *
 * @author Jason Orrill <Jason_Orrill@brown.edu>
 */
class DOC_Util_Banner_ORDS {
	private static $instances = array() ;
	
	private $base_url = NULL ;
	private $client_id = NULL ;
	private $client_secret = NULL ;
	private $auth_code = NULL ;
	private $access_token = NULL ;
	private $refresh_token = NULL ;
	private $token_expires = NULL ;
	private $model_name = NULL ;
	
	private function __construct($base_url, $client_id, $client_secret, $auth_code, $model_name) {
		$this->base_url = $base_url ;
		$this->client_id = $client_id ;
		$this->client_secret = $client_secret ;
		$this->auth_code = $auth_code ;
		$this->model_name = $model_name ;

		$this->get_access_token() ;
	}
	
	public static function instance($base_url, $client_id, $client_secret, $auth_code, $model_name) {
		$instance_key = $base_url . $client_id . $client_secret . $auth_code ;

		
		if( !isset( self::$instances[ $instance_key ])) {
			$instance = new DOC_Util_Banner_ORDS($base_url, $client_id, $client_secret, $auth_code, $model_name) ;
		} else {
			$instance = self::$instances[ $instance_key ] ;
			$instance->get_access_token() ;
		}
		self::$instances[ $instance_key ] = $instance ;
		
		return $instance ;
	}
	
	private function get_user_password() {
		return "{$this->client_id}:{$this->client_secret}" ;
	}
	
	/**
	 * Pull access token data from the database. If there's no record, then request
	 * the initial access token from ORDS. If there's a record but we're past the
	 * expiration date, use the refresh token to get a fresh token.
	 */
	private function get_access_token(){
		
		Kohana::$log->add(Log::DEBUG, "Loading OAuth ORM model: {$this->model_name}") ;
		
		$oauth = ORM::factory( $this->model_name )
				->where('client_id','=',$this->client_id)
				->where('client_secret','=',$this->client_secret)
				->find() ;

		// data exists in database and token not yet expired, just copy the data over
		if( $oauth->loaded() && date('Y-m-d H:i:s') < $oauth->token_expires ) {
			$this->access_token = $oauth->access_token ;
			$this->refresh_token = $oauth->refresh_token ;
			$this->token_expires = $oauth->token_expires ;
		} else {
			
			$curl_handle = curl_init() ;
			curl_setopt( $curl_handle, CURLOPT_URL, $this->base_url . 'oauth2/token') ;
			curl_setopt( $curl_handle, CURLOPT_TIMEOUT, 20 ) ;
			curl_setopt( $curl_handle, CURLOPT_RETURNTRANSFER, TRUE ) ;
			curl_setopt( $curl_handle, CURLINFO_HEADER_OUT, TRUE ) ;
			curl_setopt( $curl_handle, CURLOPT_REFERER, url::base()) ;
			curl_setopt( $curl_handle, CURLOPT_SSL_VERIFYPEER, FALSE ) ;
			curl_setopt( $curl_handle, CURLOPT_POST, TRUE ) ;
			curl_setopt( $curl_handle, CURLOPT_USERPWD, $this->get_user_password()) ;
//			curl_setopt( $curl_handle, CURLOPT_HTTPAUTH, CURLAUTH_ANY ) ;
			
			if( !$oauth->loaded()) {
				$oauth = ORM::factory( $this->model_name ) ;
				$oauth->client_id = $this->client_id ;
				$oauth->client_secret = $this->client_secret ;

//				// get access token from server
				curl_setopt($curl_handle, CURLOPT_POSTFIELDS, "grant_type=authorization_code&code={$this->auth_code}") ;
				
			} else {

				// refresh access token from server
				curl_setopt($curl_handle, CURLOPT_POSTFIELDS, "grant_type=refresh_token&refresh_token={$oauth->refresh_token}") ;				
			}

			$resp = curl_exec( $curl_handle ) ;
			$info = curl_getinfo( $curl_handle, CURLINFO_HEADER_OUT ) ;
			$http_code = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE) ;
		
			if ( $http_code != 200 ) {
				Kohana::$log->add(Log::ERROR,"Error updating OAuth access token: HTTP code={$http_code}, response={$resp}") ;
				throw new ErrorException('There was an error updating the OAuth access token.');
			}
			
			$access = json_decode( $resp ) ;
			
			Kohana::$log->add(Log::DEBUG, 'OAuth token data received: ' . print_r( $access, TRUE )) ;
			
			// store in object
			$this->access_token = $access->access_token ;
			$this->refresh_token = $access->refresh_token ;
			$this->token_expires = date('Y-m-d H:i:s',strtotime("+{$access->expires_in} seconds")) ;
			
			// update model
			$oauth->access_token = $this->access_token ;
			$oauth->refresh_token = $this->refresh_token ;
			$oauth->token_expires = $this->token_expires ;
			
			$oauth->save() ;
		}		
	}
	
	/**
	 * 
	 * @param string $endpoint
	 * @param array $data
	 * @return object
	 * @throws ErrorException
	 * @todo Make _much_ more robust. Currently only handles simple GETs.
	 */
	public function execute_request($endpoint, $data, $method = 'GET') {
		$this->get_access_token() ;
		
		$data = DOC_Util_REST::ordered_query_string($data) ;
		
		$curl_handle = curl_init() ;
		
		switch ( $method ) {
			case 'GET':
				$endpoint .= "?{$data}" ;
				break ;
			case 'POST':
				curl_setopt( $curl_handle, CURLOPT_POST, TRUE ) ;
				curl_setopt( $curl_handle, CURLOPT_POSTFIELDS, $data ) ;
				break ;
			default:
				throw new ErrorException('Unknown HTTP method specified in '.__CLASS__ ) ;
		}
		
		curl_setopt( $curl_handle, CURLOPT_URL, $this->base_url . $endpoint) ;		
		curl_setopt( $curl_handle, CURLOPT_TIMEOUT, 20 ) ;
		curl_setopt( $curl_handle, CURLOPT_RETURNTRANSFER, TRUE ) ;
		curl_setopt( $curl_handle, CURLINFO_HEADER_OUT, TRUE ) ;
		curl_setopt( $curl_handle, CURLOPT_REFERER, url::base()) ;
		curl_setopt( $curl_handle, CURLOPT_SSL_VERIFYPEER, FALSE ) ;
		curl_setopt( $curl_handle, CURLOPT_HTTPHEADER, array( "Authorization: Bearer " . $this->access_token )) ;
		
		$response = curl_exec( $curl_handle ) ;
		
		$info = curl_getinfo( $curl_handle, CURLINFO_HEADER_OUT ) ;
			
		if( curl_getinfo( $curl_handle, CURLINFO_HTTP_CODE ) != 200 ) {
			Kohana::$log->add(Log::ERROR, "Error accessing REST endpoint {$this->base_url}{$endpoint}, data={$data}" ) ; 
			throw new ErrorException('There was a problem executing the specified REST request.' ) ;
		}
		
		return json_decode( $response ) ;		
	}
	
}

