<?php
/** 
 * @package Kohana 3.x Modules
 * @author Christopher Keith <Christopher_Keith@brown.edu>
 */
defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Utility class used for verifying an LTI signature
 */
class DOC_Util_Ltisignature {
    
    /**
     * HTTP request method
     *
     * @var string
     */
    private $_method = NULL;
    
    /**
     * Holds "tuples" of parameter name/value pairs
     *
     * @var array
     */
    private $_params = array();
    
    /**
     * Secret to be used for message signing
     * 
     * @var string
     */
    private $_secret = NULL;
    
    /**
     * Signature passed in via OAuth parameters
     *
     * @var string
     */
    private $_signature;
    
    /**
     * URI used in generation of OAuth signature
     *
     * @param string
     */
    private $_uri;
    
    /**
     * Class constructor (can only be called internally)
     * 
     * @param array $config
     * @return NULL
     */
    protected function __construct($config = NULL) {
    	if (is_array($config)) {
    		if (isset($config['uri'])) {
    			$this->_uri = $config['uri'];
    		}
    		if ((isset($config['post'])) && (is_array($config['post']))) {
    		    foreach ($config['post'] as $key => $value) {
    		    	if ($key != 'oauth_signature') {
    		        	$this->_params[] = array($key, $value);
    		        } else {
    		        	$this->_signature = $value;
    		        }
    		    }
    		}
    		if ((isset($config['get'])) && (is_array($config['get']))) {
    		    foreach ($config['get'] as $key => $value) {
    		    	$this->_params[] = array($key, $value);
    		    }
    		}
    		if (isset($config['method'])) {
    			$this->_method = strtoupper($config['method']);
    		}
    		if (isset($config['secret'])) {
    			$this->_secret = $config['secret'];
    		}
    	}
    }

    /**
     * Calculate the base string uri for the signature process
     * 
     * @return string
     */
    private function base_string_uri() {
        return $this->_uri;
    }
    
    /**
     * Provide an encoding abstraction layer
     *
     * @param string $input
     * @return string
     */
    public static function encode($input) {
    	return rawurlencode($input);
    }
    
    /**
     * Produce a string containing the normalized parameters
     *
     * @return string
     */
    private function normalize_parameters() {
    	$temp = array();
    	$copy = $this->_params;
    	sort($copy);
    	foreach($copy as $parameter) {
    		$temp[] = self::encode($parameter[0]) . '=' . self::encode($parameter[1]);
    	}
    	return implode('&', $temp);
    }
    
    /**
     * Generate the base string of the signature prior to signing
     *
     * @return string
     */
    public function signature_base_string() {
    	return $this->_method . '&'
    	       . self::encode($this->base_string_uri()) . '&'
    	       . self::encode($this->normalize_parameters());
    }
    
    /**
     * Determine if the inputted data constitute a valid OAuth signed launch request
     *
     * @return int
     */
    public function validate() {
    	$message = $this->signature_base_string();
    	$secret = $this->_secret . '&';
    	$sig = base64_encode(hash_hmac('sha1', $message, $secret, TRUE));
    	return strcmp($sig, $this->_signature);
    }
    
    /**
     * Validate a given LTI Oauth signature
     * 
     * @param array $config
     * @return boolean 
     */
    public static function validate_signature($config = NULL) {
        $validator = new DOC_Util_Ltisignature($config);
        return ($validator->validate() === 0);
    }
    
}
// End DOC_Util_Ltisignature