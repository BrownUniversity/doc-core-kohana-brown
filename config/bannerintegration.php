<?php
/**
 * Banner Integration Configuration File
 *
 * @author Christopher Keith <Christopher_Keith@brown.edu>
 * @package DOC Core Module
 */
defined('SYSPATH') or die('No direct access allowed.');

return array(
    'server'   => 'ftps.example.edu',
    'path'     => '/directory/to/files/',
    'username' => 'ftp_user',
    'password' => 'password',
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
        'graduate_active' => array(
                'filename' => 'ask_gr_active.dat',
                'regex' => '#\{\s*"Brown_ID".*?"Courses"\s*:\s*\[.*?\]\s*\}#s',
        ),
        'graduate_inactive' => array(
                'filename' => 'ask_gr_inactive.dat',
                'regex' => '#\{\s*"Brown_ID".*?"Courses"\s*:\s*\[.*?\]\s*\}#s',
        ),
        'graduate_graduated' => array(
                'filename' => 'ask_gr_grad.dat',
                'regex' => '#\{\s*"Brown_ID".*?"Courses"\s*:\s*\[.*?\]\s*\}#s',
        ),
        'institutions' => array(
			'filename' => 'ask_institutions.dat',
			'regex' => '#\{\s*"inst_code"\s*:\s*"[^"]*"\s*,\s*"inst_description"\s*:\s*"[^"]*"\s*,\s*"inst_city"\s*:\s*"[^"]*"\s*,\s*"inst_state"\s*:\s*"[^"]*"\s*\}#',
		),
        'previous_institutions' => array(
            'filename' => 'ask_prev_inst.dat',
            'regex' => '#\{\s*"Brown_ID".*?\}#',
        ),
		'buildings' => array(
			'filename' => 'facilities/FM_BUILDING.txt',
			'regex' => '#\{\s*"SITE"\s*:.*?\}#',
		),
		'concentration_advisors' => array(
			'filename' => 'conc_advisors_processed.dat',
			'columns' => array('student_id', 'advisor_id', 'concentration_code', 'action', 'pairing_id', 'msg', 'date'),
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
    		'secret' => 'secret-string',
    		'url' => 'https://banner.example.edu/path/to/service',
    		'contact' => 'contact@example.edu'
    	),
    	'photo' => array(
    		'secret' => 'secret-string',
    		'url' => 'https://banner.example.edu/path/to/service',
    		'contact' => 'contact@example.edu'
    	),
		'cart' => array(
			'secret' => 'secret-string',
			'url' => 'https://banner.example.edu/path/to/service',
			'contact' => 'contact@example.edu'
		),
		'bdms' => array(
			'secret' => 'secret-string',
			'url' => 'https://banner.example.edu/path/to/service',
			'contact' => 'contact@example.edu'
		),
		'greensheets-read' => array(
			'secret' => 'secret-string',
			'url' => 'https://banner.example.edu/path/to/service',
			'contact' => 'contact@example.edu'
		),
		'greensheets-write' => array(
			'secret' => 'secret-string',
			'url' => 'https://banner.example.edu/path/to/service',
			'contact' => 'contact@example.edu'
		),
		'greensheets-permissions' => array(
			'secret' => 'secret-string',
			'url' => 'https://banner.example.edu/path/to/service',
			'contact' => 'contact@example.edu'
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
    ),
	'ords' => array(
		'base-url' => 'https://ords.example.edu/',
		'client-id' => 'client-id',
		'client-secret' => 'secret-string',
		'model' => 'model-name'
	)
);

// End Banner Integration Configuration File
