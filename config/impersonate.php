<?php
/**
 * Configuration File for User Impersonation Functionality
 *  
 * @package Kohana 3.x Modules
 * @module Impersonation
 * @version 0.1
 * @author Christopher Keith <Christopher_Keith@brown.edu>
 */
defined('SYSPATH') OR die('No direct access allowed.');
 
$config = array();

/**
 * Type of session in which to store the impersonation information
 *
 * @var string
 */
$config['session_type'] = 'database';

/**
 * Session key at which to store the impersonated user's id
 *
 * @var string
 */
$config['session_key'] = 'impersonate.id';

/**
 * Session key at which to store the id of the user who was last impersonated
 * by the current user.
 * 
 * @var string
 */
$config['last_impersonated_key'] = 'impersonate.last_impersonated_id';

/**
 * Session key at which to store the results of a user search
 *
 * @var string
 */
$config['results_key'] = 'impersonate.search_results';

/**
 * Session key at which to store the return link after completing impersonation
 *
 * @var string
 */
$config['return_link_key'] = 'impersonate.return_link';

/**
 * Application model that describes a user
 *
 * @var string
 */
$config['user_model'] = 'User';

/**
 * Method of the user model that returns the logged in user
 *
 * @var string
 */
$config['logged_in_method'] = 'get_logged_in_user';

/**
 * Method of the user model that returns an impersonated user
 *
 * @var string
 */
$config['alternate_method'] = 'retrieve_via_uuid';

/**
 * Attribute of the user model to use when retrieving user instance
 *
 * @var string
 */
$config['user_key'] = 'uu_id';

/**
 * Ldap attribute used to load an impersonated user
 *
 * @var string
 */
$config['ldap_key'] = 'uu_id';

/**
 * Add new user to the system if an impersonated user does not already exist
 *
 * @var boolean
 */
$config['add_new_users'] = TRUE;

/**
 * Property of user model to check for access to impersonation functionality
 *
 * @var string
 */
$config['permissions_property'] = 'brown_uuid';

/**
 * People who should have access to the impersonation functionality
 *
 * @var array
 */
$config['permissions_values'] = array();

/**
 * Maximum number of search results to return
 *
 * @var int
 */
$config['search_limit'] = 100;

return $config;

// End User Impersonation Configuration File