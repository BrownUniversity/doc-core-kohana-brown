<?php
/**
 * Retrieve information from Brown's global address book via LDAP.
 *
 * Adapted from jcramton's LDAP functions...and then adapted from the Kohana code for CodeIgniter
 * 
 * @author Adam Bradley <atb@brown.edu>
 * @todo: Change search_people function to use parse_result_array
 */
class DOC_Util_Ldap
{
    private $ldap_host_url = "registry.brown.edu";
    private $ldap_query_bind_rdn = null;
    private $ldap_query_bind_password = null; 
    
    /**
     * OLD CREDENTIALS
     *
     * username: ***REMOVED***
     * password: ***REMOVED***
     */
     
     private $cn;
     
     private $last_result_rc ;
    
    /**
     * Attribute list used when retrieving information about people.
     */
    protected $person_attributes = array(
		"first_name"          => "givenname",
		"last_name"           => "sn",
		"full_name"           => "displayname",
		"net_id"              => "brownnetid",
		"auth_id"             => "brownshortid",
		"uu_id"               => "brownuuid",
		"bru_id"              => "brownbruid",
		"banner_id"           => "brownsisid", 
		"primary_affiliation" => "brownprimaryaffiliation",
		"brown_title"         => "browntitle",
		"title"               => "title",
		"department"          => "ou",
		"email_address"       => "mail",
		"phone"               => "telephonenumber",
		"status"              => "brownstatus",
		"office_hours"        => "brownofficehours",
		"url"                 => "labeleduri",
		"memberships"         => "ismemberof",
		"country_code"        => 'brownsecuritycountrycode',
		"local_address"       => 'brownlocaladdress',
	);
    
    /**
     * Attribute list used when retrieving basic course metadata.
     */
    protected $course_attributes = array(
    	'crn'                    => 'brownsectioncrn',
    	'title'                  => 'brownsectiontitle',
    	'description'            => 'brownsectiondescription',
    	'exam_group'             => 'brownsectionexamgroup',
    	'schedule_type'          => 'brownsectionscheduletype',
    	'course_meetings'        => 'brownalternatecoursemeetings',
    	'course_readings'        => 'brownalternatecoursereadings',
    	'enrollment_limit'       => 'browncoursemaxenrollment',
    	'alt_course_assignments' => 'brownalternatecourseassignments',
    	'alt_course_description' => 'brownalternatecoursedescription',
    );
    
    /**
     * Roles by which a person can be associated with a course
     */
    protected $roles = array(
    	'*',
    	'All',
    	'Administrator',
    	'Instructor',
    	'Manager',
    	'TeachingAssistant',
    	'Contributor',
    	'ContentDevelopr',
    	'Mentor',
    	'Learner',
    	'Auditor',
    	'Student',
    	'Vagabond',
    );
    
    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->cn = ldap_connect('ldaps://'.$this->ldap_host_url);
        ldap_bind($this->cn,
                  $this->ldap_query_bind_rdn,
                  $this->ldap_query_bind_password);
    }
    
    /**
     * Class destructor
     */
    public function __destruct()
    {
//         if ($this->last_result_rc) { 
//         	ldap_free_result($this->last_result_rc); 
//         }
        ldap_unbind($this->cn);
    }
    
    /**
     * Comparison function for resulting person array
     *
     * @param array person data
     * @param array person data
     * @return int comparison result
     */
    public static function person_compare($p1, $p2)
    {
    	$last = strcasecmp($p1['last_name'], $p2['last_name']);
    	$first = strcasecmp($p2['first_name'], $p2['first_name']);
    	log_message('error', "{$p1['last_name']} {$p2['last_name']} {$last}");
    	if ($last == 0) {
    		return $first;
    	} else {
    		return $last;
    	}
    }
    
    /**
     * Get a set of information about a user based on ID.
     *
     * ID can be auth_id and brown_id (or, apparently, SISID or BRUID).
     *
     * @param string|int $id The id to search for.
     * @return Array An associative array of values stripped from the LDAP response.
     */
    public function get_person_info($id) {
        // search the people objects
        $base = "ou=People,dc=brown,dc=edu";
        $filter = "(|(brownShortID=$id)(brownNetID=$id)(brownBruID=$id)(brownSISID=$id)(brownUUID=$id))";
        
        try {
			$search_result = $this->run_search($base, $filter, array_values($this->person_attributes));
		}
        catch (Exception $e)
        {
            $result['status']['ok'] = false;
            $result['status']['message'] = $e->getMessage();
        }
        
        // check exactly one match
        $count = $search_result["count"];
        if ($count == 0) {
            $result['status']['ok'] = false;
            $result['status']['message'] = "$id not found in directory";
            return $result;
        }
        
        if ($count > 1) {
            $result['status']['ok'] = false;
            $result['status']['message'] = "Multiple matches for $id in directory";
            return $result;
        }
    
        // get the results
        //$result['info'] = $this->parse_person_array($search_result[0]);
        $result['info'] = $this->parse_result_array($this->person_attributes, $search_result[0]);
        $result['status']['ok'] = true;
        
        return $result;
    }
    
    /**
     * Search LDAP for people by name.
     *
     * $paffil can be an array of strings or a comma-delimited list.
     *
     * @param string $s the search term (name to look for)
     * @param int $limit Maximum number of results to return.  Defaults to 20.
     * @param string|Array $paffil primary_affiliations to limit to.
     */
    public function search_people($s, $limit = 20, $paffil = null) {
        $result = array();
        
        // search the people objects
        $base = "ou=People,dc=brown,dc=edu";
        $ss = explode(' ', $s);
        
        $filters = array();
        
        foreach ($ss as $s)
        {
            //Probably overkill.  It *should* be ok to just search displayname.
            $filters[] = "(|(displayname=*$s*)(brownsisid=$s*))";
        }
        
        if ( !is_null($paffil) )
        {
            $affiliations = is_array($paffil) ? $paffil : explode(',', $paffil);
            $afilters = array();
            
            foreach ( $affiliations as $aff )
            {
                $afilters[] = "(brownprimaryaffiliation=$aff)";
            }
            
            $filters[] = '(|'.implode('', $afilters).')';
        }
        
        $filters = implode($filters);
        $filter = "(&$filters)";
        
        try {
			$search_result = $this->run_search($base, $filter, array_values($this->person_attributes), $limit);
		}
        catch (Exception $e)
        {
            $result['status']['ok'] = false;
            $result['status']['message'] = $e->getMessage();
        }
        
        //print_r($search_result);

        $result['count'] = array_shift($search_result);
        
        // get the results
        $results = array();
        foreach ( $search_result as $sr )
        {
            $id = $sr['brownshortid'][0];
            $results[$id] = $this->parse_person_array($sr);
        }
        
        $result['results'] = $results;
        $result['status']['ok'] = true;
        
        return $result;
    }
    
    /**
     * Lookup department codes from LDAP
     *
     * @author Christopher Keith <Christopher_Keith@brown.edu>
     * @return array
     */
    public function get_department_codes() {
    	// Setup search
    	$base = "ou=Courses,dc=brown,dc=edu";
    	$courseRDN = "departments";
    	$filter = "(&(brownCourseRDN={$courseRDN})(objectClass=brownCourseSelector))";
    	$attribute = "browncourseselectionlist";
    	
    	// Execute Search
    	try {
    		$search_result = $this->run_search($base, $filter, array($attribute));
	    } 
	    catch (Exception $e) {
	    	return array(
	    		'status' => array(
	    			'ok'      => false,
	    			'message' => $e->getMessage(),
	    		)
	    	);
    	}

		$count = array_shift($search_result[0][$attribute]);
		return array(
			'status'  => array(
				'ok'      => true,
				'message' => null,
			),
			'results' => $search_result[0][$attribute],
		);
    }
    
    /** 
     * Find courses associated with a user, specification, and role
     *
     * @author Christopher Keith <Christopher_Keith@brown.edu>
     * @param string person identifier
     * @param string course specification
     * @param string role
     * @return array
     */
    public function find_person_courses($id, $coursespec, $role)
    {
    	//@todo: implement regular expression matching
    	
    	// Validate role parameter
    	if (array_search($role, $this->roles) === FALSE) {
    		return array(
    			'status' => array(
    				'ok'      => FALSE,
    				'message' => 'Invalid Role',
    			),
    		);
    	}
    	
    	// Setup find
    	$base = "ou=People,dc=brown,dc=edu";
    	$filter = "(|(brownShortID=$id)(brownNetID=$id))";
    	$attribute = "ismemberof";
    	
    	// Execute find
    	try {
    		$find_result = $this->run_search($base, $filter, array($attribute));
    	}
    	catch (Exception $e) {
    		return array(
    			'status' => array(
    				'ok'      => FALSE,
    				'message' => $e->getMessage(),
    			),
    		);
    	}
    	
    	// Check for no matches
    	if ($find_result['count'] == 0) {
    		return array(
    			'status' => array(
    				'ok'      => FALSE,
    				'message' => "{$id} not found in directory.",
    			),
    		);
    	}
    	
    	// Check for too many matches
    	if ($find_result['count'] > 1) {
    		return array(
    			'status' => array(
    				'ok'      => FALSE,
    				'message' => "Multiple matches for {$id} in directory.",
    			),
    		);
    	}
    	
    	$role = strtolower($role);
    	$result['status']['ok'] = TRUE;
    	$result['courses'] = array();
    	$count = array_shift($find_result[0][$attribute]);
    	foreach ($find_result[0][$attribute] as $c) {
    		$fields = explode(':', $c);
    		if (strtolower($fields[0]) === "course") {
    			$course = "{$fields[1]}:{$fields[2]}:{$fields[3]}:{$fields[4]}";
    			if ($this->wildcard_match($coursespec, $course)) {
    				if ($role == '*') {
    					$result['courses'][$fields[5]][] = $course;
    				} else {
    					if (strtolower($fields[5]) == $role) {
    						$result['courses'][] = $course;
    					}
    				}
    			}
    		}
    	}
    	return $result;
	}
    
    /**
     * Get course membership for a particular course
     *
     * @author Christopher Keith <Christopher_Keith@brown.edu>
     * @param string course specification
     * @param string person role
     * @return Array
     */
    public function get_course_membership($coursespec, $role)
    {
    	// validate role
    	if (array_search($role, $this->roles) === FALSE) {
    		return array(
    			'status' => array(
    				'ok'      => FALSE,
    				'message' => "Role {$role} is not valid",
    			),
    		);
    	}
    	
    	$group = "COURSE:{$coursespec}:{$role}";
    	$base = "ou=Groups,dc=brown,dc=edu";
    	$filter = "(brownGroupRDN={$group})";
    	$attribute = "hasmember";
    	
    	$attributes = array(
    		0 => $attribute,
    		1 => 'browngrouprdn',
    	);
    	
    	// Execute Find
    	try {
    		$find_result = $this->run_search($base, $filter, $attributes);
    	}
    	catch (Exception $e) {
    		return array(
    			'status' => array(
    				'ok'      => FALSE,
    				'message' => $e->getMessage(),
    			),
    		);
    	}
    	
    	// check for at least one match
    	$count = array_shift($find_result);
    	$result['members'] = array();
    	if ($count > 0) {
    		foreach ($find_result as $fr) {
    			$rdn = $fr['browngrouprdn'][0];
    			$fields = explode(':', $rdn);
    			$member_count = array_shift($fr[$attribute]);
    			foreach ($fr[$attribute] as $data) {
    				if ($data !== '') {
    					$short_id = $this->convert_uuid($data);
    					if ($role == '*') {
    						$result['members'][$fields[5]][] = $short_id;
    					} else {
    						$result['members'][] = $short_id;
    					}
    				}
    			}
    		}
    	}
    	
    	$result['status']['ok'] = TRUE;
    	if ($role == '*') {
    		$result['status']['allroles'] = TRUE;
    		$roles = $result['members'];
    		foreach ($roles as $rolename => $rolemembers) {
    			sort($rolemembers);
    			$result['members'][$rolename] = $rolemembers;
    		}
    	} else {
    		$result['status']['allroles'] = FALSE;
    		sort($result['members']);
    	}

		return $result;
		
    }
    
    /** 
     * Find courses associated with a learner in a given term
     *
     * @author Christopher Keith <Christopher_Keith@brown.edu>
     * @param string person identifier
     * @param string term identifier
     * @return array
     */
    public function get_person_enrollment($id, $term, $coursespec = NULL)
    {
    	$result['status']['ok'] = TRUE;
    	$result['courses'] = array();
    	
    	if ($coursespec == NULL) {
    		$coursespec = "*:*:{$term}:*";
    	}
    	
    	// Get the person's courses
    	$courses = $this->find_person_courses($id, $coursespec, 'Learner');
    	if ( ! $courses['status']['ok']) {
    		return $courses;
    	}
    	
    	// Get the metadata foreach course
    	foreach ($courses['courses'] as $coursespec) {
    		
    		$metadata = $this->get_course_metadata_basic($coursespec);
    		if ($metadata['status']['ok']) {
    			$result['courses'][$coursespec] = $metadata['info'];
    			$course_info = explode(':', $coursespec);
    			$result['courses'][$coursespec]['display'] = "{$course_info[0]} {$course_info[1]} ({$course_info[3]})";
    		}
    		$result['courses'][$coursespec]['instructors'] = array();
    		$instructors = $this->get_course_membership($coursespec, 'Instructor');
    		if ($instructors['status']['ok']) {
    			foreach ($instructors['members'] as $i) {
    				$instructor = $this->get_person_info($i);
    				if ($instructor['status']['ok']) {
    					$result['courses'][$coursespec]['instructors'][$i] = $instructor['info'];
    				}
    			}
    		}
    	}
    	
    	return $result;
    }
    
    /**
     * Find courses in LDAP matching input string
     *
     * @author Christopher Keith <Christopher_Keith@brown.edu>
     * @param string course specification (DEPT:NUMBER:TERM:SECTION)
     * @return array
     */
    public function find_matching_courses($coursespec) {
    
    	// Check for well formed course specification
    	// @todo: implement regular expression matching
    	
    	// Setup find
    	$base = "ou=Courses,dc=brown,dc=edu";
    	$filter = "(&(brownCourseRDN={$coursespec})(objectClass=brownSection))";
    	$attribute = "browncourserdn";
    	
    	// Execute find
    	try {
    		$find_result = $this->run_search($base, $filter, array($attribute));
    	}
    	catch (Exception $e) {
    		// Return error on exception
			return array(
				'status' => array(
					'ok'      => false,
					'message' => $e->getMessage(),
				)
    		);
    	}
    	
    	// Extract resulting courses
    	$count = array_shift($find_result);
    	$courses = array();
    	foreach ($find_result as $fr) {
    		$courses[] = $fr[$attribute][0];
    	}
    	return array(
    		'status'  => array(
    			'ok'      => true,
    			'message' => null,
    		),
    		'courses' => $courses,
    	);
    }
    
    /**
     * Get basic metadata for a course
     *
     * @author Christopher Keith <Christopher_Keith@brown.edu>
     * @param string course specification
     * @return array
     */
    public function get_course_metadata_basic($coursespec) {
    
    	// Check fo well formed course specification
    	// @todo: implement regular expression matching
    	
    	// Setup find
    	$base = "ou=Courses,dc=brown,dc=edu";
    	$filter = "(&(brownCourseRDN={$coursespec})(objectClass=brownSection))";
    	
    	// Execute find
    	try {
    		$find_result = $this->run_search($base, $filter, array_values($this->course_attributes));
    	}
    	catch (Exception $e) {
    		// Return error on exception
    		return array(
    			'status' => array(
    				'ok'      => false,
    				'message' => $e->getMessage(),
    			)
    		);
    	}
    	
    	return array(
    		'status' => array(
    			'ok' => true,
    			'message' => null,
    		),
    		'info' => $this->parse_result_array($this->course_attributes, $find_result[0]),
    	);
    }
    
	public function count_last() {
		if ( !$this->last_result_rc ) { 
			return false; 
		}
        return ldap_count_entries($this->cn, $this->last_result_rc);
    }

    /**
     * For testing/development only.
     */
    public function raw_person_info($id) {
        // search the people objects
        $base = "ou=People,dc=brown,dc=edu";
        $filter = "(|(brownShortID=$id)(brownNetID=$id)(brownBruID=$id)(brownSISID=$id))";
        $attributes = $this->person_attributes;
        
        $search_ref = ldap_search($this->cn, $base, $filter);
        
        if (!$search_ref) {
            $result['status']['ok'] = false;
            $result['status']['message'] = "LDAP error looking up info for $filter in $base";
            return $result;
        }
    
        $search_result = ldap_get_entries($this->cn, $search_ref);
        if (!$search_result) {
            $result['status']['ok'] = false;
            $result['status']['message'] = "LDAP error retrieving info for $filter in $base";
            ldap_free_result($search_ref);
            return $result;
        }
    
        // check exactly one match
        $count = $search_result["count"];
        if ($count == 0) {
            $result['status']['ok'] = false;
            $result['status']['message'] = "$id not found in directory";
            ldap_free_result($search_ref);
            return $result;
        }
        
        if ($count > 1) {
            $result['status']['ok'] = false;
            $result['status']['message'] = "Multiple matches for $id in directory";
            ldap_free_result($search_ref);
            return $result;
        }
        
        ldap_free_result($search_ref);
        
        return $search_result;
    }
    
    /**
     * Utility method to run a search on LDAP and return the raw result as an array.
     *
     * @param string $base
     * @param string $filter
     * @param string $atts Attributes to pull from LDAP.  Defaults to null (all).
     * $param int $limit Maximum number of entries.
     */
    private function run_search($base, $filter, $atts = null, $limit = null)
    {
        $search_ref = @ldap_search($this->cn, $base, $filter,
                                  $atts, 0, $limit);
        
        $this->last_result_rc = $search_ref;
        
        if (!$search_ref)
            { throw new Exception("LDAP error looking up info for $filter in $base"); }
            
        $search_result = ldap_get_entries($this->cn, $search_ref);
        if (!$search_result)
            { throw new Exception("LDAP error retrieving info for $filter in $base"); }
        
        ldap_free_result($search_ref);
        return $search_result;
    }
    
    /** 
     * Parse the result of an LDAP query based off an inputted attribute map
     *
     * @author Christopher Keith <Christopher_Keith@brown.edu>
     * @param Array $map an associative array of key value pairs
     * @param Array $data an associative array returned from LDAP query
     * @return Array
     */
    private function parse_result_array(Array $map, Array $data) {
    	$outp = array();
    	foreach ($map as $attribute => $ldapname) {
    		if (isset($data[$ldapname]['count'])) {
    			if ($data[$ldapname]['count'] > 1) {
    				unset($data[$ldapname]['count']);
    				$value = $data[$ldapname];
    			} else {
    				$value = $data[$ldapname][0];
    			}
    			$outp[$attribute] = $value;
    		} else {
    			$outp[$attribute] = NULL;
    		}
    	}
    	return $outp;
    }
    
    /**
     * Parse a single result from LDAP about a person.
     *
     * @param Array $data an associative array returned from LDAP from a person search.
     */
    private function parse_person_array(Array $data)
    {
        $outp = array();
        
        foreach ($this->person_attributes as $attribute => $ldapname) {
            if ( isset($data[$ldapname]['count']) ) {
                //print_r($data[$ldapname]);
                if ( $data[$ldapname]['count'] > 1 )
                {
                    unset($data[$ldapname]['count']);
                    $value = $data[$ldapname];
                } else {
                    $value = $data[$ldapname][0];
                }
                
                $outp[$attribute] = $value;
            } else {
            	$outp[$attribute] = NULL;
            }
       }
       
       return $outp;
    }
    
    /**
     * Test is a string matches a pattern
     * 
     * @param string pattern for matching
     * @param string string for testing
     * @return boolean
     */
    private function wildcard_match($pattern, $string)
    {
    	$npattern = '';
    	$l = strlen($pattern);
    	for ($n = 0; $n < $l; $n++) {
    		$c = $pattern[$n];
    		switch ($c) {
    			case '\\' :
    				$npattern .= '\\' . @$pattern[++$n];
    				break;
    			case '$' :
    			case '^' :
    			case '.' :
    			case '+' :
    			case '?' :
    			case '[' :
    			case ']' :
    			case '(' :
    			case ')' :
    			case '{' :
    			case '}' :
    			case '=' :
    			case '!' :
    			case '<' :
    			case '>' :
    			case '|' :
    				$npattern .= '\\' . $c;
    				break;
    			case '*' :
    				$npattern .= '.' . $c;
    				break;
    			default :
    				$npattern .= $c;
    				break;
    		}
    	}
    	return preg_match('/' . $npattern . '/i', $string);
    }
    
    /**
     * Lookup a short id for a given uuid
     *
     * @param string uuid_dn
     * @return string short id
     */
    private function convert_uuid($uuid_dn)
    {
    	// get the UUID part
    	$fields = explode(',', $uuid_dn);
    	$uuid = $fields[0];
    	
    	// setup search
    	$base = "ou=People,dc=brown,dc=edu";
    	$filter = "($uuid)";
    	$attribute = 'brownshortid';
    	
    	try {
    		$search_result = $this->run_search($base, $filter, array($attribute));
    	}
    	catch (Exception $e) {
    		return '';
    	}
    	
    	$count = array_shift($search_result);
    	if ($count == 0) {
    		return '';
    	}
    	if ($count > 1) {
    		return '';
    	}
    	
    	return $search_result[0][$attribute][0];
    }
}