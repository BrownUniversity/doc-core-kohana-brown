<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of encrypt
 *
 * @author jorrill
 */
class DOC_Encrypt extends Kohana_Encrypt {
	public function encode_url_safe($data) {
		return rtrim(strtr($this->encode($data), '+/', '-_'), '=') ;
	}

	public function decode_url_safe($data) {
		return $this->decode( str_pad( strtr( $data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT )) ;
	}
}
