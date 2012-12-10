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
	'students'		=> array(
            'filename' => 'ask_student.dat', 
            'regex' => '#\{\s*"Brown_ID"(.*?)"Cohorts"\s*:\s*\[(.*?)\]\s*,\s*"Attributes"\s*:\s*\[(.*?)\]\s*,\s*"Advisors"\s*:\s*\[(.*?)\]\s*,\s*"Programs"\s*:\s*\[(.*?)\]\s*,\s*"Transfer_Work"\s*:\s*\[(.*?)\]\s*,\s*"Courses"\s*:\s*\[(.*?)\]\s*\}#'
	),
	'writing'		=> array(
            'filename' => 'banner_writing.dat', 
            'regex' => ''
	),
    ),
);

// End Banner Integration Configuration File