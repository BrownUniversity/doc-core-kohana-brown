<?php
/**
 * Banner Integration Configuration File
 *
 * @author Christopher Keith <Christopher_Keith@brown.edu>
 * @package DOC Core Module
 */
defined('SYSPATH') or die('No direct access allowed.');

if( file_exists( dirname( __FILE__ ) . '/myConfig.php' )) include_once( dirname( __FILE__ ) . '/myConfig.php') ;

defined('FTPS_SERVER') or define('FTPS_SERVER', 'ftps.example.edu');
defined('FTPS_PATH') or define('FTPS_PATH', '/path/to/files/');

defined('BANNERINTEGRATION_APIS_IAR_SECRET') or define('BANNERINTEGRATION_APIS_IAR_SECRET', '***REMOVED***');
defined('BANNERINTEGRATION_APIS_IAR_URL') or define('BANNERINTEGRATION_APIS_IAR_URL', '***REMOVED***');
//defined('BANNERINTEGRATION_APIS_IAR_URL') or define('BANNERINTEGRATION_APIS_IAR_URL', '***REMOVED***');

defined('BANNERINTEGRATION_APIS_PHOTO_SECRET') or define('BANNERINTEGRATION_APIS_PHOTO_SECRET', '***REMOVED***');
defined('BANNERINTEGRATION_APIS_PHOTO_URL') or define('BANNERINTEGRATION_APIS_PHOTO_URL', '***REMOVED***');
// defined('BANNERINTEGRATION_APIS_PHOTO_URL') or define('BANNERINTEGRATION_APIS_PHOTO_URL', '***REMOVED***');

defined('BANNERINTEGRATION_APIS_CART_SECRET') or define('BANNERINTEGRATION_APIS_CART_SECRET', '***REMOVED***') ;
defined('BANNERINTEGRATION_APIS_CART_URL') or define('BANNERINTEGRATION_APIS_CART_URL', '***REMOVED***');
//defined('BANNERINTEGRATION_APIS_CART_URL') or define('BANNERINTEGRATION_APIS_CART_URL', '***REMOVED***');


return array(
    'server'   => FTPS_SERVER,
    'path'     => FTPS_PATH,
    'username' => '***REMOVED***',
    'password' => '***REMOVED***',
    'imports'  => array(
        'cohorts'		=> array(
                'filename' => 'ask_cohort.dat',
                'regex' => '#\{\s*"cohort_code"\s*:\s*"[^"]*"\s*,\s*"cohort_description"\s*:\s*"[^"]*"\s*\}#'
        ),
        'courses'		=> array(
                'filename' => 'ask_courses.dat',
                'regex' => '#\{\s*"subject_code"\s*:\s*"[^"]*"\s*,\s*"subject_description"\s*:\s*"[^"]*"\s*,\s*"course_number"\s*:\s*"[^"]*"\s*,\s*"start_term"\s*:\s*"[^"]*"\s*,\s*"end_term"\s*:\s*"[^"]*"\s*,\s*"status"\s*:\s*"[^"]*"\s*,\s*"short_title"\s*:\s*"[^"]*"\s*,\s*"long_title"\s*:\s*"[^"]*"\s*\}#'
        ),
        'instructors'	=> array(
                'filename' => 'ask_instructors.dat',
                'regex' => '#\{\s*"Term"\s*:\s*"[^"]*"\s*,\s*"CRN"\s*:\s*"[^"]*"\s*,\s*"LDAP_ID"\s*:\s*"[^"]*"\s*,\s*"Primary_Ind"\s*:\s*"[^"]*"\s*\}#'
        ),
        'undergraduate_active' => array(
                'filename' => 'ask_ug_active.dat',
                'regex' => '#\{\s*"Brown_ID".*?"Courses"\s*:\s*\[.*?\]\s*\}#s', 
        ),
        'undergraduate_inactive' => array(
                'filename' => 'ask_ug_inactive.dat',
                'regex' => '#\{\s*"Brown_ID".*?"Courses"\s*:\s*\[.*?\]\s*\}#s',
        ),
        'undergraduate_graduated' => array(
                'filename' => 'ask_ug_grad.dat',
                'regex' => '#\{\s*"Brown_ID".*?"Courses"\s*:\s*\[.*?\]\s*\}#s',
        ),
    ),
    'exports' => array(
        'writing'		=> array(
            'filename' => 'banner_writing.dat',
            'regex' => ''
        ),
    ),
    'apis' => array(
    	'iar' => array(
    		'secret' => BANNERINTEGRATION_APIS_IAR_SECRET,
    		'url' => BANNERINTEGRATION_APIS_IAR_URL,
    		'contact' => 'Christopher_Keith@brown.edu'
    	),
    	'photo' => array(
    		'secret' => BANNERINTEGRATION_APIS_PHOTO_SECRET,
    		'url' => BANNERINTEGRATION_APIS_PHOTO_URL,
    		'contact' => 'Christopher_Keith@brown.edu'
    	),
		'cart' => array(
			'secret' => BANNERINTEGRATION_APIS_CART_SECRET,
			'url' => BANNERINTEGRATION_APIS_CART_URL,
			'contact' => 'Christopher_Keith@brown.edu'
		)
    )
);

// End Banner Integration Configuration File