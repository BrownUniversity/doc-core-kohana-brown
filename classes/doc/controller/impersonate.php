<?php
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
class DOC_Controller_Impersonate extends Controller_Template {
    
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
    	parent::before();
    	
    	if ( ! DOC_Helper_Impersonate::check_permissions()) {
    		$this->request->redirect($this->request->referrer());
    	}
    }
    
    /**
     * Assume a user's identity
     * 
     * @param int $array_key
     */
    public function action_assume($array_key = NULL)
    {    
        if ($array_key === NULL) {
            $this->request->redirect('impersonate');
        } else {
            $results = DOC_Helper_Impersonate::get_search_results();
            $person = $results[$array_key];
            DOC_Helper_Impersonate::assume(
                $person[Kohana::config('impersonate.ldap_key')]
            );			
            $this->request->redirect(DOC_Helper_Impersonate::get_return_link());
        }
    }
    
    /**
     * Clear an impersonation session
     */
    public function action_clear()
    {
        DOC_Helper_Impersonate::clear();
        $this->request->redirect($this->request->referrer());
    }
    
    /**
     * Search for a user to impersonate
     */
    public function action_index()
    {
        if ($this->request->method() == 'POST') {
            if ($this->request->post('btn_submit') == 'Cancel') {
                $this->request->redirect(
                    DOC_Helper_Impersonate::get_return_link()
                );
            } else {
            	if ($this->request->post('search_string') == NULL) {
            		$this->request->redirect('impersonate');
            	}
            	$affiliation = $this->request->post('affiliation');
            	if ($affiliation == 'any') $affiliation = NULL;
                $ldap = new Util_Ldap();
                $results = $ldap->search_people(
                	$this->request->post('search_string'),
      				Kohana::config('impersonate.search_limit'),          	
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
                    }
                    ksort($sort);
                    $results = array_values($sort);
                } else {
                    $results = array();
                }
                DOC_Helper_Impersonate::set_search_results($results);
                $this->template->content = View::factory('impersonate/results');
                $this->template->content->results = $results;
            }
        } else {
            DOC_Helper_Impersonate::set_return_link($this->request->referrer());
            $this->template->content = View::factory('impersonate/search_form');
        }
    }
} // End Impersonation Controller