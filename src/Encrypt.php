<?php
namespace BrownUniversity\DOC ;
/**
 * Extending Kohana_Encrypt to provide extra functionality for dealing with URLs.
 *
 * @author jorrill
 * @deprecated moving methods into Kohana proper
 */
class Encrypt extends \Kohana_Encrypt {
	/**
	 * Encodes and converts the result into something safe for including in URLs.
	 * 
	 * @param string $data
	 * @return string
	 */
	public function encode_url_safe($data) {
		return rtrim(strtr($this->encode($data), '+/', '-_'), '=') ;
	}

	/**
	 * Decode a URL processed with DOC_Encrypt::encode_url_safe() method.
	 * 
	 * @param string $data
	 * @return string
	 */
	public function decode_url_safe($data) {
		return $this->decode( str_pad( strtr( $data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT )) ;
	}
}
