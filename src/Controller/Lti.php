<?php
namespace BrownUniversity\DOC\Controller ;
/** 
 * @package Kohana 3.x Modules
 * @author Christopher Keith <Christopher_Keith@brown.edu>
 */
use BrownUniversity\DOC\View;
use Kohana\Controller\Template;

defined('SYSPATH') OR die('No direct access allowed.');
 
/**
 * Controller for providing LTI functionality
 */
class Lti extends Template {
    
    /**
     * Main template file
     * 
     * @var string
     */
    public $template = 'template';
    
    /**
     * Logic to execute before this controller
     */
    public function before() {
    	parent::before();
        
        $fragment_file = 'pages/' ; 
		$directory = $this->request->directory() ;
		if( !empty( $directory )) {
			$fragment_file .= $directory . '/' ;
		}
		$fragment_file .= $this->request->controller().'/'.$this->request->action() ;
		
		if( \Kohana::find_file('views', $fragment_file) ) {	
			$this->template->view_fragment = View::factory($fragment_file) ;
		}
    }
    
} // End LTI Controller