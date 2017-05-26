<?php
namespace BrownUniversity\DOC\Util ;
use BrownUniversity\DOC\Util\Ldap ;

/**
 * @package DOC Core
 * @version 1.0
 * @since 1.0
 * @author Christopher Keith <Christopher_Keith@brown.edu>
 */
defined('SYSPATH') or die('No direct script access.');

/**
 * Grouper Utility class
 */
class Grouper {
	
    /**
     * Add a member to a group
     * 
     * @param string $bru_id
     * @param string $group
     * @return json
     */
    public static function add_member($bru_id, $group) {
        $config = \Kohana::$config->load('grouper');
        
        $ch = curl_init();
        $url = $config['GROUPER_REST_BASE'] . 'groups/' . rawurlencode($group) . '/members/' . $bru_id;
        $auth = $config['GROUPER_WS_LOGIN'] . ':' . $config['GROUPER_WS_PASSWORD'];
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $auth);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        $response = curl_exec($ch);
        
        return $response;
    }
    
    /**
     * Convert an array of BRU IDs to arrays of names
     * 
     * @param array $input
     * @return array 
     */
    public static function convert($input) {
        $_output = array();
        $ldap = new Ldap();
        foreach ($input as $i) {
            $person = $ldap->get_person_info($i);
            if ( ! empty($person['info'])) {
                $key = $person['info']['last_name'] . ',' . $person['info']['first_name'] . $person['info']['bru_id'];
                $_output[$key] = array(
                    'name' => $person['info']['first_name'] . ' ' . $person['info']['last_name'],
                    'bru_id' => $person['info']['bru_id'],
                    'email' => $person['info']['email_address'],
                );
            }
        }
        ksort($_output);
        return $_output;
    }
    
    /**
     * Delete a member from group
     * 
     * @param string $bru_id
     * @param string $group
     * @return json
     */
    public static function delete_member($bru_id, $group) {
        $config = \Kohana::$config->load('grouper');
        
        $ch = curl_init();
        $url = $config['GROUPER_REST_BASE'] . 'groups/' . rawurlencode($group) . '/members/' . $bru_id;
        $auth = $config['GROUPER_WS_LOGIN'] . ':' . $config['GROUPER_WS_PASSWORD'];
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $auth);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        $response = curl_exec($ch);
        
        return $response;
    }
    
    /**
     * Query the member of a particular group
     * 
     * @param string $group
     * @return array
     */
    public static function get_membership($group) {
        $config = \Kohana::$config->load('grouper');
        
        $ch = curl_init();
        $url = $config['GROUPER_REST_BASE'] . 'groups/' . rawurlencode($group) . '/members';
        $auth = $config['GROUPER_WS_LOGIN'] . ':' . $config['GROUPER_WS_PASSWORD'];
        
        /**
         * Add the following to the body to restrict to direct members
         * &memberFilter=Immediate
         */
        $body = 'wsLiteObjectType=WsRestGetMembersLiteRequest&actAsSubjectId=GrouperSystem&sourceIds=brown:LDAP';
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $auth);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        $response = json_decode(curl_exec($ch));
        $error = curl_error($ch);
        $errorno = curl_errno($ch);
        var_dump($error);
        var_dump($errorno); die();
        $_output = array();
        
        if (isset($response->WsGetMembersLiteResult)) {
            if (isset($response->WsGetMembersLiteResult->wsSubjects)) {
                foreach ($response->WsGetMembersLiteResult->wsSubjects as $p) {
                    $_output[] = $p->id;
                }
            }
        }
        
        return self::convert($_output);
    }
    
    /**
     * Determine if a user is a member of a group
     * 
     * @param string $bru_id
     * @param string $group
     * @return boolean
     */
    public static function is_member($bru_id, $group) {
        $config = \Kohana::$config->load('grouper');
        
        $ch = curl_init();
        $url = $config['GROUPER_REST_BASE'] . 'groups/' . rawurlencode($group) . '/members/' . $bru_id;
        $auth = $config['GROUPER_WS_LOGIN'] . ':' . $config['GROUPER_WS_PASSWORD'];
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $auth);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        
        $response = json_decode(curl_exec($ch));
        
        $_output = FALSE;
        
        if (isset($response->WsHasMemberLiteResult)) {
            if (isset($response->WsHasMemberLiteResult->resultMetadata)) {
                if (isset($response->WsHasMemberLiteResult->resultMetadata->resultCode)) {
                    $_output = ($response->WsHasMemberLiteResult->resultMetadata->resultCode == 'IS_MEMBER');
                }
            }
        }
        
        return $_output;
    }
    
}

// End DOC_Util_Grouper