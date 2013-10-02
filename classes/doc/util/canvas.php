<?php
/**
 * @package DOC Core
 * @version 1.0
 * @since 1.0
 * @author Christopher Keith <Christopher_Keith@brown.edu>
 * @requires CURL
 * @requires Canvas API Credentials
 */
defined( 'SYSPATH' ) or die( 'No direct script access.' );

/**
 * Canvas Utility Class
 * 
 * Utility class for making API calls to canvas
 */
class DOC_Util_Canvas {
    
    /**
     * Resource for making CURL requests
     * 
     * @var resource
     */
    private static $ch;
    
    
    
    /**
     * Base URL of the Canvas instance to which requests should be made
     * 
     * @var string
     */
    private static $host_url = NULL;
    
    /**
     * Has this class been initialized yet?
     * 
     * @var boolean
     */
    private static $initialized = FALSE;
    
    /**
     * Token for authentication purpose
     */
    private static $token = NULL;
    
    /**
     * Finalized the file upload process
     */
    public static function confirm_file_upload($uri) {
    	self::init();
    	$options = array();
    	$options[CURLOPT_URL] = $uri;
    	
    	return self::execute_curl($options);
    }
    
    /**
     * Continue the file upload process with returned result from 
     * file upload endpoint 
     * 
     * @param array $data
     * @param string $path
     * @return array
     */
    public static function continue_file_upload($data, $path) {
        self::init(FALSE);
        
        $postfields = $data['upload_params'];
        $postfields['file'] = "@{$path}";
        $options = array();
        $options[CURLOPT_URL] = $data['upload_url'];
        $options[CURLOPT_POST] = TRUE;
        $options[CURLOPT_POSTFIELDS] = $postfields; //implode('&', $data['upload_params']);
        return self::execute_curl($options, FALSE);
    }
    
    /**
     * Determine if a Canvas course for a given LDAP coursespec has been
     * created
     * 
     * @param string $coursespec
     * @return boolean
     * @throws Kohana_Exception
     */
    public static function course_exists($coursespec) {
        $sisid = self::coursespec_to_sisid($coursespec);
        
        $data = self::get_course_info("sis_course_id:{$sisid}");
        
        $output = TRUE;
        if ((isset($data['status'])) && ($data['status'] == 'not_found')) {
            $output = FALSE;
        } elseif (isset($data['id'])) {
            $output = $data['id'];
        } else {
            Throw new Kohana_Exception("Indeterminanent Canvas course existance for: {$coursespec}");
        }
        
        return $output;
    }
    
    /**
     * Convert and LDAP coursespec to the Canvas-formatted Course Code
     * 
     * @param string $coursespec
     * @return string
     */
    public static function coursespec_to_coursecode($coursespec) {
       $parts = explode(':', $coursespec);
       $term_parts = explode('-', $parts[2]);
       
       $_output = $term_parts[1] . ' ' . $term_parts[0] . ' ' . $parts[0]
                . ' ' . $parts[1];
       
       if (count($parts) == 4) {
           $_output .= ' ' . $parts[3];
       }
       
       return $_output;
             
    }
    
    /**
     * Convert an LDAP coursespec to the Canvas-formatted SIS ID
     * 
     * @param string $coursespec
     * @return string
     */
    public static function coursespec_to_sisid($coursespec) {
        $parts = explode(':', $coursespec);
        $term_parts = explode('-', $parts[2]);
        
        $_output = 'brown.' . strtolower($parts[0]) . '.' 
                 . strtolower($parts[1]) . '.' . strtolower($parts[2]);
        if (count($parts) == 4) {
            $_output .= '.' . strtolower($parts[3]);
        }
        
        return $_output;
    }
    
    /**
     * Convert an LDAP coursespec to the Canvas-formatted title
     * 
     * @param string $coursespec
     * @param string $title
     * @return string
     */
    public static function coursespec_to_title($coursespec, $title) {
        $parts = explode(':', $coursespec);
        $term_parts = explode('-', $parts[2]);
        
        $_output = $parts[0] . $parts[1] . ' ' . $term_parts[1]
                 . substr($term_parts[0], -2);
        
        if (count($parts) == 4) {
            $_output .= ' ' . $parts[3];
        }
        
        $_output .= ' ' . $title;
        
        return $_output;
    }
    
    /**
     * Create a new course
     * 
     * @param type $data
     * @return array
     */
    public static function create_course($data) {
        self::init();
        
        $postfields = $data;
        $options = array();
        $options[CURLOPT_URL] = self::$host_url . "/api/v1/accounts/{$data['account_id']}/courses";
        $options[CURLOPT_POST] = TRUE;
        $options[CURLOPT_POSTFIELDS] = $postfields;
        return self::execute_curl($options);
    }
    
    /**
     * Create a new folder
     * 
     * @param string $parent_folder_id
     * @param string $folder_name
     * @return string id of new folder
     */
    public static function create_course_folder($course_id, $parent_folder_id, $folder_name) {
        self::init();
        
        $postfields = array(
            'name' => $folder_name,
            'parent_folder_id' => $parent_folder_id,
        );
        
        $options = array();
        $options[CURLOPT_URL] = self::$host_url . "/api/v1/courses/{$course_id}/folders";
        $options[CURLOPT_POST] = TRUE;
        $options[CURLOPT_POSTFIELDS] = $postfields;
        return self::execute_curl($options);
    }
    
    /**
     * Delete an existing course from Canvas
     * 
     * @param type $course_id
     * @return array
     */
    public static function delete_course($course_id) {
        self::init();
        $options = array();
        $options[CURLOPT_URL] = self::$host_url . "/api/v1/courses/{$course_id}";
        $options[CURLOPT_CUSTOMREQUEST] = 'DELETE';
        $options[CURLOPT_POSTFIELDS] = array('event' => 'delete');
        return self::execute_curl($options);
    }
    
    /**
     * Use the Delete File API
     *
     * @param int $file_id
     * @return array
     */
    public static function delete_file($file_id) {
    	self::init(FALSE);
    	
    	$options = array();
    	$options[CURLOPT_URL] = self::$host_url . "/api/v1/files/{$file_id}";
    	$options[CURLOPT_CUSTOMREQUEST] = 'DELETE';
    	
    	return self::execute_curl($options);
    }
    
    /**
     * Ensure a syllabus folder exists for a course
     * 
     * @param string $course_id
     * @return string
     */
    public static function ensure_syllabus_folder($course_id) {
        
        $root_folder = self::get_course_folder($course_id);
        
        $folders = self::get_folders_list($root_folder['id']);
        
        $syllabus_folder_id = NULL;
        foreach ($folders as $folder) {
            if ($folder['name'] == 'Syllabus') {
                $syllabus_folder_id = $folder['id'];
            }
        }
            
        if ($syllabus_folder_id == NULL) {
            $result = self::create_course_folder($course_id, $root_folder['id'], 'Syllabus');
            $syllabus_folder_id = $result['id'];
        }
        
        return $syllabus_folder_id;
    }
    
    /**
     * Execute the CURL request
     * 
     * @param array $options additional CURL options
     * @param boolean $include token
     * @return array JSON-decoded response
     */
    private static function execute_curl($options, $include_token = TRUE) {
        self::reset_curl($options, $include_token);
        $response = curl_exec(self::$ch);
        return json_decode($response, TRUE);
    }
    
    /**
     * Use the CANVAS list assignment groups API
     * 
     * @param string $course_id
     * @return array
     */
    public static function get_assignment_groups($course_id) {
        self::init();
        
        $options = array();
        $options[CURLOPT_URL] = self::$host_url . "/api/v1/courses/{$course_id}/assignment_groups";
        
        $result = self::execute_curl($options);
        
        $output = array();
        foreach ($result as $r) {
            $output[$r['position']] = $r;
        }
        ksort($output);
        return $output;
    }
    
    /**
     * Use the CANVAS assignments API
     * 
     * @param int $course_id
     * @return array
     */
    public static function get_assignments($course_id, $assignment_id = NULL) {

        self::init();
       
        $options = array();
       
        if ($assignment_id == NULL) {
            $options[CURLOPT_URL] = self::$host_url . "/api/v1/courses/{$course_id}/assignments";
        } else {
            $options[CURLOPT_URL] = self::$host_url . "/api/v1/courses/{$course_id}/assignments/{$assignment_id}";
        }
        
        return self::execute_curl($options);
    }
    
    /**
     * Use the CANVAS course enrollment API
     * 
     * @param int $course_id
     * @param mixed $role
     * @return array
     */
    public static function get_course_enrollment($course_id, $role = NULL) {
        
        self::init();
        
        $options = array();
        $options[CURLOPT_URL] = self::$host_url . "/api/v1/courses/{$course_id}/enrollments";
        
        $results = self::execute_curl($options);
        
        $output = array();
        foreach ($results as $r) {
            $output[strtolower($r['user']['sortable_name'])] = $r['user'];
        }
        ksort($output);
        return $output;
    }
    
    /**
     * Use the CANVAS folders for a course API
     *
     * @param int $course_id
     * @param mixed $folder_id
     * @return array
     */
    public static function get_course_folder($course_id) {
    	self::init();
    	
    	$options = array();
    	$options[CURLOPT_URL] = self::$host_url . "/api/v1/courses/{$course_id}/folders/root";
    	
    	return self::execute_curl($options);
    }
    
    /**
     * Use the CANVAS courses API
     * 
     * @param int $course_id Canvas course identified
     * @param boolean $syllabus include HTML syllabus info in response
     * @return array
     */
    public static function get_course_info($course_id, $syllabus = TRUE) {
        self::init();
        
        $options = array();
        $options[CURLOPT_URL] = self::$host_url . "/api/v1/courses/{$course_id}";
        
        /**
         * Include syllabus information
         */
        if ($syllabus) {
            $options[CURLOPT_URL] .= '?include=syllabus_body';
        }
        
        return self::execute_curl($options);
    }
    
    /**
     * Use the CANVAS files list API
     *
     * @param int $folder_id
     * @return array
     */
    public static function get_files_list($folder_id) {
    	self::init();
    	
    	$options = array();
    	$options[CURLOPT_URL] = self::$host_url . "/api/v1/folders/{$folder_id}/files";
    	
    	return self::execute_curl($options);
    }
    
    /**
     * Use the CANVAS folder list API
     *
     * @param int $folder_id
     * @return array
     */
    public static function get_folders_list($folder_id) {
    	self::init();
    	
    	$options = array();
    	$options[CURLOPT_URL] = self::$host_url . "/api/v1/folders/{$folder_id}/folders";
    	
    	return self::execute_curl($options);
    }
    
    /**
     * Use the CANVAS get one submission API
     * 
     * @param int $course_id
     * @param int $assignment_id
     * @param int $user_id
     */
    public static function get_submission($course_id, $assignment_id, $user_id) {
        self::init();
        
        $options = array();
        $options[CURLOPT_URL] = self::$host_url . "/api/v1/courses/{$course_id}/assignments/{$assignment_id}/submissions/{$user_id}";
        
        return self::execute_curl($options);
    }
    
    /**
     * Use the CANVAS submission list API
     * 
     * @param int $course_id
     * @return array
     */
    public static function get_submissions_list($course_id) {
        self::init();
        
        $options = array();
        $options[CURLOPT_URL] = self::$host_url . "/api/v1/courses/{$course_id}/students/submissions";
        
        return self::execute_curl($options);
    }
    
    /**
     * Initialize class for full use
     */
    private static function init($include_token = TRUE) {
            $config = Kohana::$config->load('canvas');
            self::$host_url = $config->host_url;
            self::$token = $config->api_token;
            self::$ch = curl_init();
            self::reset_curl(array(), $include_token);
    }
    
    /**
     * Initial a file upload via CANVAS API
     * 
     * @param array $info
     * @return array
     */
    public static function init_file_upload($info) {
       self::init();
       
       $post_options = array();
       foreach ($info as $key => $value) {
        	$post_options[] = $key . '=' . rawurlencode($value);
        }
					
       $options = array();
       $options[CURLOPT_URL] = self::$host_url . "/api/v1/folders/{$info['parent_folder_id']}/files";
       $options[CURLOPT_POST] = TRUE;
       $options[CURLOPT_POSTFIELDS] = $info; //implode('&', $post_options);
       
       return self::execute_curl($options);
    }
    
    /**
     * Use List Accounts API
     *
     * @return array
     */
    public static function list_accounts() {
    	self::init();
    	
    	$options = array();
    	$options[CURLOPT_URL] = self::$host_url . '/api/v1/accounts';
    	
    	return self::execute_curl($options);
    }
    
    /**
     * Use Course List in Account API
     *
     * @param int $account_id
     * @return array
     */
    public static function list_courses($account_id) {
    	self::init();
    	
    	$options = array();
    	$options[CURLOPT_URL] = self::$host_url . "/api/v1/accounts/{$account_id}/courses";
    	
    	return self::execute_curl($options);
    }
    
    /**
     * Create an array to use for allowing someone to choose a course
     * 
     * @param string $account_id
     * @return array
     */
    public static function list_courses_dropdown($account_id) {
        $courses = self::list_courses($account_id);
        $output = array();
        
        foreach ($courses as $c) {
            if ($c['sis_course_id'] != NULL) {
                $output[$c['id']] = $c['name'];
            }
        }
        
        return $output;
    }
    
    /**
     * Reset CURL resource for a new API Call
     * 
     * @param array $options
     * @param boolean $include_token
     */
    private static function reset_curl($options = array(), $include_token = TRUE) {
        
        if ($include_token === TRUE) {
        	$options[CURLOPT_HTTPHEADER] = array (
                'Authorization: Bearer ' . self::$token,
            );
        } elseif (isset($options[CURLOPT_HTTPHEADER])) {
        	unset($options[CURLOPT_HTTPHEADER]);
        }
        
        $options[CURLOPT_SSL_VERIFYPEER] = FALSE;
        $options[CURLINFO_HEADER_OUT] = TRUE;
        $options[CURLOPT_RETURNTRANSFER] = TRUE;
        
        curl_setopt_array(self::$ch, $options);
    }
}

// End DOC_Util_Canvas