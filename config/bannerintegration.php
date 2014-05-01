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

defined('BANNERINTEGRATION_APIS_CONTACT') or define('BANNERINTEGRATION_APIS_CONTACT', 'Christopher_Keith@brown.edu') ;

defined('BANNERINTEGRATION_APIS_IAR_SECRET') or define('BANNERINTEGRATION_APIS_IAR_SECRET', '***REMOVED***');
defined('BANNERINTEGRATION_APIS_IAR_URL') or define('BANNERINTEGRATION_APIS_IAR_URL', '***REMOVED***');
//defined('BANNERINTEGRATION_APIS_IAR_URL') or define('BANNERINTEGRATION_APIS_IAR_URL', '***REMOVED***');

defined('BANNERINTEGRATION_APIS_PHOTO_SECRET') or define('BANNERINTEGRATION_APIS_PHOTO_SECRET', '***REMOVED***');
defined('BANNERINTEGRATION_APIS_PHOTO_URL') or define('BANNERINTEGRATION_APIS_PHOTO_URL', '***REMOVED***');
//defined('BANNERINTEGRATION_APIS_PHOTO_URL') or define('BANNERINTEGRATION_APIS_PHOTO_URL', '***REMOVED***');

defined('BANNERINTEGRATION_APIS_CART_SECRET') or define('BANNERINTEGRATION_APIS_CART_SECRET', '***REMOVED***') ;
defined('BANNERINTEGRATION_APIS_CART_URL') or define('BANNERINTEGRATION_APIS_CART_URL', '***REMOVED***');
//defined('BANNERINTEGRATION_APIS_CART_URL') or define('BANNERINTEGRATION_APIS_CART_URL', '***REMOVED***');

defined('BANNERINTEGRATION_APIS_BDMS_SECRET') or define('BANNERINTEGRATION_APIS_BDMS_SECRET','***REMOVED***') ;
defined('BANNERINTEGRATION_APIS_BDMS_URL') or define('BANNERINTEGRATION_APIS_BDMS_URL','***REMOVED***') ;

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
                'regex' => '#\{\s*"subject_code"\s*:\s*"[^"]*"\s*,\s*"subject_description"\s*:\s*"[^"]*"\s*,\s*"course_number"\s*:\s*"[^"]*"\s*,\s*"start_term"\s*:\s*"[^"]*"\s*,\s*"end_term"\s*:\s*"[^"]*"\s*,\s*"status"\s*:\s*"[^"]*"\s*,\s*"short_title"\s*:\s*"[^"]*"\s*,\s*"long_title"\s*:\s*"[^"]*"\s*,\s*"credits"\s*:\s*"[^"]*"\s*\}#'
        ),
        'instructors'	=> array(
                'filename' => 'ask_instructors.dat',
                'regex' => '#\{\s*"Term"\s*:\s*"[^"]*"\s*,\s*"CRN"\s*:\s*"[^"]*"\s*,\s*"LDAP_ID"\s*:\s*"[^"]*"\s*,\s*"Primary_Ind"\s*:\s*"[^"]*"\s*\}#'
        ),
        'test_score_codes' => array(
                'filename' => 'ask_test_codes.dat',
                'regex' => '#\{\s*"test_code"\s*:\s*"[^"]*"\s*,\s*"test_description"\s*:\s*"[^"]*"\s*\}#',
        ),
        'test_scores' => array(
                'filename' => 'ask_test_scores.dat',
                'regex' => '#\{\s*"Brown_ID".*?"Test_Date"\s*:\s*"[^"]*"\s*\}#',
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
		'institutions' => array(
			'filename' => 'ask_institutions.dat',
			'regex' => '#\{\s*"inst_code"\s*:\s*"[^"]*"\s*,\s*"inst_description"\s*:\s*"[^"]*"\s*\}#',
		)
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
    		'contact' => BANNERINTEGRATION_APIS_CONTACT
    	),
    	'photo' => array(
    		'secret' => BANNERINTEGRATION_APIS_PHOTO_SECRET,
    		'url' => BANNERINTEGRATION_APIS_PHOTO_URL,
    		'contact' => BANNERINTEGRATION_APIS_CONTACT
    	),
		'cart' => array(
			'secret' => BANNERINTEGRATION_APIS_CART_SECRET,
			'url' => BANNERINTEGRATION_APIS_CART_URL,
			'contact' => BANNERINTEGRATION_APIS_CONTACT
		),
		'bdms' => array(
			'secret' => BANNERINTEGRATION_APIS_BDMS_SECRET,
			'url' => BANNERINTEGRATION_APIS_BDMS_URL,
			'contact' => BANNERINTEGRATION_APIS_CONTACT
		)
    ),
    'meta' => array(
    	'ap_institution_codes' => array(
			'T00001',
			'T00002',
			'T00003',
			'T00004',
			'T00005',
			'T00006'
    	),
    	// Length of time into a student's first term a non-privileged user should
    	// have access to BDMS files. Store as a DateInterval compatible string.
    	'bdms_cutoff' => 'P1M' // one month
    )
);

// End Banner Integration Configuration File