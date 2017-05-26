<?php
namespace BrownUniversity\DOC\Controller ;
use BrownUniversity\DOC\Helper\Impersonate as Helper_Impersonate;
use BrownUniversity\DOC\Util\Ldap ;
use BrownUniversity\DOC\View ;
/** 
 * @package Kohana 3.x Modules
 * @module Impersonation
 * @version 0.1
 * @author Christopher Keith <Christopher_Keith@brown.edu>
 */
defined('SYSPATH') OR die('No direct access allowed.');
 
/**
 * Controller for Impersonating Users
 */
class Impersonate extends \Controller_Template {
    
    /**
     * Main template file
     * 
     * @var string
     */
    public $template = 'impersonate/template';
    
    /**
     * Logic to execute before this controller
     */
    public function before()
    {
    	// parent::before();
    	
    	if ($this->auto_render === TRUE)
		{
			// Load the template
			$this->template = View::factory($this->template);
		}
    	
    	if ( ! Helper_Impersonate::check_permissions()) {
    		$this->redirect($this->request->referrer());
    	}
    }
	
    /**
     * Assume a user's identity
     * 
     * @param int $array_key
     */
    public function action_assume()
    {    
    	$id = $this->request->param('id') ;
        if ($id === NULL) {
            $this->redirect('impersonate');
        } else {
            $results = Helper_Impersonate::get_search_results();
            $person = $results[$id];
            Helper_Impersonate::assume(
                $person[\Kohana::$config->load('impersonate.ldap_key')]
            );

            $this->redirect(Helper_Impersonate::get_return_link());
        }
    }
    
    /**
     * Clear an impersonation session
     */
    public function action_clear()
    {
        Helper_Impersonate::clear();
        $this->redirect($this->request->referrer());
    }
    
    /**
     * Fully clear an impersonation session, including the identity of the 
     * last impersonated user.
     */
    public function action_clearall() {
        Helper_Impersonate::clear_all();
        $this->redirect($this->request->referrer());
    }
    
    public function action_history() {
        $key = $this->request->param('id');
        $history = Helper_Impersonate::get_history();
        
        if (array_key_exists($key, $history)) {
            Helper_Impersonate::assume($history[$key]);
        } else {
            Helper_Impersonate::clear_history();
        }
        
        $this->redirect($this->request->referrer());
    }
    
    /**
     * Search for a user to impersonate
     */
    public function action_index()
    {
        if ($this->request->method() == 'POST') {
            if ($this->request->post('btn_submit') == 'Cancel') {
                $this->redirect(
                    Helper_Impersonate::get_return_link()
                );
            } else {
            	if ($this->request->post('search_string') == NULL) {
            		$this->redirect('impersonate');
            	}
            	$affiliation = $this->request->post('affiliation');
            	if ($affiliation == 'any') $affiliation = NULL;
                $ldap = new Ldap();
                $results = $ldap->search_people(
                	$this->request->post('search_string'),
      				\Kohana::$config->load('impersonate.search_limit'),          	
                	$affiliation
                );
                
                if ((isset($results['status']['ok'])) && 
                    ($results['status']['ok'])) 
                {
                    $sort = array();
                    foreach ($results['results'] as $record) {
                    	$key = $record['last_name'] . ', ' 
                             . $record['first_name'] . ', ' 
                             . $record['auth_id'];
                    	$sort[$key] = $record;
                        unset($sort[$key]['memberships']);
                    }
                    ksort($sort);
                    $results = array_values($sort);
                } else {
                    $results = array();
                }
                Helper_Impersonate::set_search_results($results);
                $this->template->content = View::factory('impersonate/results');
                $this->template->content->results = $results;
            }
        } else {
            Helper_Impersonate::set_return_link($this->request->referrer());
            $this->template->content = View::factory('impersonate/search_form');
        }
    }
    
    /**
     * Allow the current user to assume the identify of the person whom they 
     * have last impersonated.
     */
    public function action_last() {
        Helper_Impersonate::assume_last_identity();
        $this->redirect($this->request->referrer());
    }
    
} // End Impersonation Controller