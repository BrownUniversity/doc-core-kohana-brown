<?php
namespace BrownUniversity\DOC\Helper; 
/**
 * @package Kohana 3.x Modules
 * @module Impersonation
 * @version 1.0
 * @author Christopher Keith <Christopher_Keith@brown.edu>
 */
use Kohana\HTML;
use Kohana\Kohana;
use Kohana\Log;
use Kohana\Session;
use Kohana\URL;

defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Impersonation Helper class
 */
class Impersonate {

    /**
     * Assume a user's identity
     *
     * @param string $id
     * @throws \Kohana\KohanaException
     */
    public static function assume($id)
    {
        $session = self::session();

        if( $session->get( Kohana::$config->load('impersonate.session_original_user_id')) == NULL ) {
			$method = Kohana::$config->load('impersonate.logged_in_method');
			$model = self::get_user_model_class();
			$attribute = Kohana::$config->load('impersonate.permissions_property');
			$values = Kohana::$config->load('impersonate.permissions_values');
			$user = eval("return {$model}::{$method}();");
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
     *
     * @throws \Kohana\KohanaException
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
     * @return mixed
     * @throws \Kohana\KohanaException
     */
    public static function cancel_link($message = 'Cancel Impersonation')
    {
    	$session = self::session();
    	$active = $session->get(Kohana::$config->load('impersonate.session_key'));
    	if ($active === NULL) {
    		return NULL;
    	}
    	return HTML::anchor('impersonate/clear', $message);

    }

    /**
     * Determine if access to impersonation should be allowed
     *
     * @throws \Kohana\KohanaException
     */
	public static function check_permissions()
	{
		if (self::is_impersonating()) {
			return TRUE;
		}

		$method = Kohana::$config->load('impersonate.logged_in_method');
		$attribute = Kohana::$config->load('impersonate.permissions_property');
		$values = Kohana::$config->load('impersonate.permissions_values');
        $model = self::get_user_model_class() ;

		$user = eval("return {$model}::{$method}();");

		if (array_search($user->$attribute, $values) !== FALSE) {
			return TRUE;
		}
		return FALSE;
	}

    /**
     * Remove impersonation from the session instance and restore the original user.
     *
     * @throws \Kohana\KohanaException
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
     *
     * @throws \Kohana\KohanaException
     */
    public static function clear_history() {
        $session = self::session();
        $session->delete(Kohana::$config->load('impersonate.last_impersonated_key'));
    }

    /**
     * Remove all traces of impersonation, including the id of the last
     * impersonated user.
     *
     * @throws \Kohana\KohanaException
     */
    public static function clear_all() {
        self::clear();
        self::clear_history();
    }

    /**
     * Get the results of a user impersonation search
     *
     * @throws \Kohana\KohanaException
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
     * @throws \Kohana\KohanaException
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
     * @throws \Kohana\KohanaException
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
     *
     * @throws \Kohana\KohanaException
     */
    public static function get_last_impersonated_user() {
        $session = self::session();
        
        $id = $session->get(Kohana::$config->load('impersonate.last_impersonated_key'));
        Kohana::$log->add(Log::INFO, 'Last Impersonated User: ' . $id);
        $logged_in_method = Kohana::$config->load('impersonate.logged_in_method');
        $alternate_method = Kohana::$config->load('impersonate.alternate_method');
        $model = self::get_user_model_class();
        $command = NULL;
        if ($id !== NULL) {
            $command = "return {$model}::{$alternate_method}('{$id}');";
        }
        return eval($command);
    }

    /**
     * Get the current user or impersonated user
     *
     * @throws \Kohana\KohanaException
     */
    public static function get_user()
    {
        $session = self::session();
        $id = $session->get(Kohana::$config->load('impersonate.session_key'));

        $logged_in_method = Kohana::$config->load('impersonate.logged_in_method');
        $alternate_method = Kohana::$config->load('impersonate.alternate_method');
        $model = self::get_user_model_class();
        $command = NULL;
        if ($id !== NULL) {
            $command = "return {$model}::{$alternate_method}('{$id}');";
        } else {
        	$command = "return {$model}::{$logged_in_method}();";
        }
        return eval($command);
    }

    /**
     * Get the real user when impersonating
     *
     * @throws \Kohana\KohanaException
     */
    public static function get_real_user() {
        $session = self::session();
        $id = $session->get(Kohana::$config->load('impersonate.session_original_user_id'));

        $logged_in_method = Kohana::$config->load('impersonate.logged_in_method');
        $alternate_method = Kohana::$config->load('impersonate.alternate_method');
        $model = self::get_user_model_class();
        $command = NULL;
        if ($id !== NULL) {
            $command = "return {$model}::{$alternate_method}('{$id}');";
        } else {
        	$command = "return {$model}::{$logged_in_method}();";
        }
        return eval($command);

    }

    /**
     * Check if an impersonation session is underway
     *
     * return boolean
     *
     * @throws \Kohana\KohanaException
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
     * @return \Kohana\Session Instance
     * @throws \Kohana\KohanaException
     */
    public static function session()
    {
        return Session::instance(Kohana::$config->load('impersonate.session_type'));
    }

    /**
     * Store the entry referrer in the session
     *
     * @param string $link
     * @throws \Kohana\KohanaException
     */
    public static function set_return_link($link = NULL)
    {
        $session = self::session();
        if ($link === NULL) {
            $link = URL::base();
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
     * @throws \Kohana\KohanaException
     */
    public static function set_search_results($results)
    {
        $session = self::session();

        $session->set(Kohana::$config->load('impersonate.results_key'), $results);
    }

    /**
     * @return \Kohana\Kohana_Config_Group|string
     * @throws \Kohana\KohanaException
     */
    private static function get_user_model_class() {
        $model = Kohana::$config->load('impersonate.user_model');
        $model = 'Model\\'.$model;
        if( !class_exists($model)) {
            $model = Kohana::$app_namespace.'\\'.$model;
        }

        return $model ;
    }

} // End Impersonation Helper