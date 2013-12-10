<?php
/**
 * @package Kohana 3.x Modules
 * @module Impersonation
 * @version 1.0
 * @author Christopher Keith <Christopher_Keith@brown.edu>
 */
defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Impersonation Helper class
 */
class DOC_Helper_Impersonate {

    /**
     * Assume a user's identity
     *
     * @param string $id
     */
    public static function assume($id)
    {
        $session = self::session();

        if( $session->get( Kohana::$config->load('impersonate.session_original_user_id')) == NULL ) {
			$method = Kohana::$config->load('impersonate.logged_in_method');
			$model = Kohana::$config->load('impersonate.user_model');
			$attribute = Kohana::$config->load('impersonate.permissions_property');
			$values = Kohana::$config->load('impersonate.permissions_values');
			$user = eval("return Model_{$model}::{$method}();");
        	$session->set(Kohana::$config->load('impersonate.session_original_user_id'),$user->$attribute) ;
        }

        $session->set(Kohana::$config->load('impersonate.session_key'), $id);
        
        /**
         * Add last impersonated id to impersonation history
         */
        $temp = $session->get(Kohana::$config->load('impersonate.last_impersonated_key'), FALSE);
        $impersonate_history = array();
        if ($temp !== FALSE) {
            $impersonate_history = unserialize($temp);
        }
        $impersonate_history[] = $id;
        $unique_history = array_unique($impersonate_history);
        $session->set(Kohana::$config->load('impersonate.last_impersonated_key'), serialize($unique_history)) ;
    }

    /**
     * Allow the current user to assume the identity of the last person that
     * they have impersonated.
     */
    public static function assume_last_identity() {
        $last = self::get_last_impersonated_user();
        if ($last !== NULL) {
            $attribute = Kohana::$config->load('impersonate.permissions_property');
            self::assume($last->$attribute);
        }
    }
    
    /**
     * Generate a link for canceling impersonation if a user is being impersonated.
     *
     * @param string $message
     */
    public static function cancel_link($message = 'Cancel Impersonation')
    {
    	$session = self::session();
    	$active = $session->get(Kohana::$config->load('impersonate.session_key'));
    	if ($active === NULL) {
    		return NULL;
    	} else {
    		return html::anchor('impersonate/clear', $message);
    	}
    }

    /**
     * Determine if access to impersonation should be allowed
     */
	public static function check_permissions()
	{
		if (self::is_impersonating()) {
			return TRUE;
		}

		$method = Kohana::$config->load('impersonate.logged_in_method');
		$model = Kohana::$config->load('impersonate.user_model');
		$attribute = Kohana::$config->load('impersonate.permissions_property');
		$values = Kohana::$config->load('impersonate.permissions_values');
		$user = eval("return Model_{$model}::{$method}();");

		if (array_search($user->$attribute, $values) !== FALSE) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

    /**
     * Remove impersonation from the session instance and restore the original user.
     */
    public static function clear()
    {
        $session = self::session();
        $session->delete(Kohana::$config->load('impersonate.session_key'));
        $session->delete(Kohana::$config->load('impersonate.return_link_key'));
        $session->set( Kohana::$config->load('impersonate.session_key'),
        		$session->get( Kohana::$config->load('impersonate.session_original_user_id'))
        ) ;
    }
    
    /**
     * Remove impersonation history from the session
     */
    public static function clear_history() {
        $session = self::session();
        $session->delete(Kohana::$config->load('impersonate.last_impersonated_key'));
    }
    
    /**
     * Remove all traces of impersonation, including the id of the last 
     * impersonated user.
     */
    public static function clear_all() {
        self::clear();
        self::clear_history;
    }

    /**
     * Get the results of a user impersonation search
     */
    public static function get_search_results()
    {
        $session = self::session();
        return $session->get(Kohana::$config->load('impersonate.results_key'));
    }

    /**
     * Get the return link
     *
     * @return string
     */
    public static function get_return_link()
    {
        $key = Kohana::$config->load('impersonate.return_link_key');
        $session = self::session();
        $link = $session->get($key);
        $session->delete($key);
        return $link;
    }

    /**
     * Get the history of impersonated users
     * 
     * @return array
     */
    public static function get_history() {
        $session = self::session();
        
        $temp = $session->get(Kohana::$config->load('impersonate.last_impersonated_key'), FALSE);
        
        $impersonate_history = array();
        if ($temp !== FALSE) {
            $impersonate_history = unserialize($temp);
        }
        
        return $impersonate_history;
    }
    
    /**
     * Get the last user impersonated by the current user in this session
     */
    public static function get_last_impersonated_user() {
        $session = self::session();
        
        $id = $session->get(Kohana::$config->load('impersonate.last_impersonated_key'));
        Kohana::$log->add(Log::INFO, 'Last Impersonated User: ' . $id);
        $logged_in_method = Kohana::$config->load('impersonate.logged_in_method');
        $alternate_method = Kohana::$config->load('impersonate.alternate_method');
        $model = Kohana::$config->load('impersonate.user_model');
        $command = NULL;
        if ($id !== NULL) {
            $command = "return Model_{$model}::{$alternate_method}('{$id}');";
        }
        return eval($command);
    }
    
    /**
     * Get the current user or impersonated user
     */
    public static function get_user()
    {
        $session = self::session();
        $id = $session->get(Kohana::$config->load('impersonate.session_key'));

        $logged_in_method = Kohana::$config->load('impersonate.logged_in_method');
        $alternate_method = Kohana::$config->load('impersonate.alternate_method');
        $model = Kohana::$config->load('impersonate.user_model');
        $command = NULL;
        if ($id !== NULL) {
            $command = "return Model_{$model}::{$alternate_method}('{$id}');";
        } else {
        	$command = "return Model_{$model}::{$logged_in_method}();";
        }
        return eval($command);
    }

    /**
     * Get the real user when impersonating
     */
    public static function get_real_user() {
        $session = self::session();
        $id = $session->get(Kohana::$config->load('impersonate.session_original_user_id'));

        $logged_in_method = Kohana::$config->load('impersonate.logged_in_method');
        $alternate_method = Kohana::$config->load('impersonate.alternate_method');
        $model = Kohana::$config->load('impersonate.user_model');
        $command = NULL;
        if ($id !== NULL) {
            $command = "return Model_{$model}::{$alternate_method}('{$id}');";
        } else {
        	$command = "return Model_{$model}::{$logged_in_method}();";
        }
        return eval($command);

    }

    /**
     * Check if an impersonation session is underway
     *
     * return boolean
     */
    public static function is_impersonating()
    {
    	$_output = FALSE ;
    	$session = self::session();
    	$current_id = $session->get(Kohana::$config->load('impersonate.session_key'));
    	$original_id = $session->get(Kohana::$config->load('impersonate.session_original_user_id'));

    	if( $current_id != NULL && $original_id != NULL && $current_id != $original_id ) {
    		$_output = TRUE ;
		}

    	return $_output ;
    }

    /**
     * Return an instance of a Session
     *
     * @return Session Instance
     */
    public static function session()
    {
        return Session::instance(Kohana::$config->load('impersonate.session_type'));
    }

    /**
     * Store the entry referrer in the session
     *
     * @param string $link
     */
    public static function set_return_link($link = NULL)
    {
        $session = self::session();
        if ($link === NULL) {
            $link = url::base();
		}
        $already_set = $session->get(Kohana::$config->load('impersonate.return_link_key'), FALSE);
        if ( ! $already_set) {
            $session->set(Kohana::$config->load('impersonate.return_link_key'), $link);
        }
    }

    /**
     * Store the impersonation search results array
     *
     * @param array $results
     */
    public static function set_search_results($results)
    {
        $session = self::session();

        $session->set(Kohana::$config->load('impersonate.results_key'), $results);
    }

} // End Impersonation Helper