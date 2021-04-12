<?php
namespace BrownUniversity\DOC\Util ;

use Exception;
use Kohana\Log;
use Kohana\Profiler;
use Kohana\Kohana;

/**
 * Retrieve information from Brown's global address book via LDAP.
 *
 * Adapted from jcramton's LDAP functions...and then adapted from the Kohana code for CodeIgniter
 *
 * @author Adam Bradley <atb@brown.edu>
 * @todo: Change search_people function to use parse_result_array
 */
class Ldap
{
    private $ldap_host_url = null;
    private $ldap_query_bind_rdn = null;
    private $ldap_query_bind_password = null;

     private $cn;

     private $last_result_rc ;
	 public static $instance = NULL ;

    /**
     * Attribute list used when retrieving information about people.
     */
    protected $person_attributes = array(
		'first_name'          => 'givenname',
		'last_name'           => 'sn',
		'full_name'           => 'displayname',
		'net_id'              => 'brownnetid',
		'auth_id'             => 'brownshortid',
		'uu_id'               => 'brownuuid',
		'bru_id'              => 'brownbruid',
		'banner_id'           => 'brownsisid',
		'primary_affiliation' => 'brownprimaryaffiliation',
		'brown_title'         => 'browntitle',
		'title'               => 'title',
		'department'          => 'ou',
		'email_address'       => 'mail',
		'phone'               => 'telephonenumber',
		'status'              => 'brownstatus',
		'office_hours'        => 'brownofficehours',
		'url'                 => 'labeleduri',
		'memberships'         => 'ismemberof',
		'country_code'        => 'brownsecuritycountrycode',
		'local_address'       => 'brownlocaladdress',
		'type'                => 'browntype',
		'barcode'             => 'brownbarcode',
		'affiliations'        => 'brownaffiliation',
		'advance_id'          => 'brownadvanceid',
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
     * Attribute list used when retrieving full course metadata
     */
    protected $course_meeting_attributes = array(
        'section_period' => 'brownsectionperiod',
    );
    
    /**
     * Attribute list used when retrieving meeting period information
     * 
     * @var array
     */
    protected $meeting_period_attributes = array(
        'period_start_date' => 'brownsectionperiodstartdate',
        'period_end_date'   => 'brownsectionperiodenddate',
        'frequency'         => 'brownsectionfrequency',
    );
    
    /**
     * Attribute list used when retrieving meeting period frequency info
     * 
     * @var array
     */
    protected $meeting_frequency_attributes = array(
        'hour_code'            => 'brownsectionhourcode',
        'building_code'        => 'brownsectionbuildingcode',
        'building_description' => 'brownsectionbuildingdescription',
        'room'                 => 'brownsectionroom',
        'start_time'           => 'brownsectionstarttime',
        'end_time'             => 'brownsectionendtime',  
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
     * @param array config
	 * @todo make constructor private
	 * @deprecated use instance() instead
     */
    public function __construct($config = array()) {
        $default_config = Kohana::$config->load('ldap') ;

        $this->ldap_host_url = $default_config->host_url;
        $this->ldap_query_bind_rdn = $default_config->query_bind_rdn;
        $this->ldap_query_bind_password = $default_config->query_bind_password;

        if (isset($config['host'])) {
            $this->ldap_host_url = $config['host'];
            Kohana::$log->add(Log::WARNING, 'LDAP config "host" is deprecated. Use "host_url" instead.');
        }

        if (isset($config['host_url'])) {
            $this->ldap_host_url = $config['host_url'];
        }
        
        if (isset($config['dn'])) {
            $this->ldap_query_bind_rdn = $config['dn'];
            Kohana::$log->add(Log::WARNING, 'LDAP config "dn" is deprecated. Use "query_bind_rdn" instead.');
        }

        if (isset($config['query_bind_rdn'])) {
            $this->ldap_query_bind_rdn = $config['query_bind_rdn'];
        }
        
        if (isset($config['password'])) {
            $this->ldap_query_bind_password = $config['password'];
            Kohana::$log->add(Log::WARNING, 'LDAP config "password" is deprecated. Use "query_bind_password" instead.');
        }

        if (isset($config['query_bind_password'])) {
            $this->ldap_query_bind_password = $config['query_bind_password'];
        }
        
        $bm = Profiler::start('LDAP', 'Constructor - connect');
        $this->cn = ldap_connect('ldaps://'.$this->ldap_host_url);
        Profiler::stop($bm);
        $bm1 = Profiler::start('LDAP', 'Constructor - bind');
        ldap_bind($this->cn,
                  $this->ldap_query_bind_rdn,
                  $this->ldap_query_bind_password);
    	Profiler::stop($bm1);
    }

    /**
     * Allow singleton type usage with option configuration
     * 
     * @param array $config
     * @return \BrownUniversity\DOC\Util\Ldap
     */
	public static function instance($config = array()) {
		if( !isset( self::$instance )) {
			self::$instance = new Ldap($config) ;
		}
		return self::$instance ;
	}
	
    /**
     * Class destructor
     */
    public function __destruct()
    {
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
     * @param string $property to use in limiting the search
     * @param boolean $people_only determines which ou's to search against
     * @return Array An associative array of values stripped from the LDAP response.
	 * @todo modify to allow specifying the id to search (brown UUID, net id, etc) (should still default to using the current approach)
     */
    public function get_person_info($id, $id_property = NULL, $people_only = TRUE) {
        // search the people objects
        $bm2 = Profiler::start('LDAP', 'Setup search');
        $base = "ou=People,dc=brown,dc=edu";
        
        if (( $id_property !== NULL) && (array_key_exists($id_property, $this->person_attributes))) {
            $filter = "(" . $this->person_attributes[$id_property] . "={$id})";
        } else {
            $filter = "(|(brownShortID=$id)(brownNetID=$id)(brownBruID=$id)(brownSISID=$id)(brownUUID=$id))";
        }
        
        if ( ! $people_only) {
            $base = array($base, "ou=Bifs,dc=brown,dc=edu");
        }

		Kohana::$log->add(
		        Log::DEBUG,
                "LDAP Search: base=".print_r($base, true).", filter={$filter}"
        ) ;
		
		Profiler::stop($bm2);
        try {
        	$bm1 = Profiler::start('LDAP', 'search');
			$search_result = $this->run_search($base, $filter, array_values($this->person_attributes));
			Profiler::stop($bm1);
		}
        catch (Exception $e)
        {
            $result['status']['ok'] = false;
            $result['status']['message'] = $e->getMessage();
            return $result;
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
        
        $bm3 = Profiler::start('LDAP', 'Parse response');
        $result['info'] = $this->parse_result_array($this->person_attributes, $search_result[0]);
        $result['status']['ok'] = true;
		Profiler::stop($bm3);
        return $result;
    }

    /**
     * Get a list of all courses in LDAP for a given term
     * 
     * @param string $term
     * @param array $subjects
     * @return array
     */
    public function get_term_courses($term, $subjects = array()) {
        
        $base = "ou=Courses,dc=brown,dc=edu";
        $courseRDN = "term:" . $term;
        $filter = "(&(brownCourseRDN={$courseRDN})(objectClass=brownCourseSelector))";
        $attribute = "browncourseselectionlist";
        
        try {
            $search_result = $this->run_search($base, $filter, array($attribute));
        } catch (Exception $e) {
            return array();
        }
        
        if (isset($search_result[0]["{$attribute}"])) {
            unset($search_result[0]["{$attribute}"]['count']);
            
            
            if (count($subjects) > 0) {
                $output = array();
                foreach ($search_result[0]["{$attribute}"] as $spec) {
                    $parts = explode(':', $spec);
                    if (array_search($parts[0], $subjects) !== FALSE) {
                        $output[] = $spec;
                    }
                }
            } else {
                $output = $search_result[0]["{$attribute}"];
            }
            return $output;
        } else {
            return array();
        }
    }

    public function search_service_accounts($search, $limit, $attribute = 'displayname') {
        $result = array();
        $search_trim = trim($search);

        // search the people objects
        $base = "ou=Bifs,dc=brown,dc=edu";
        $split_search = explode(' ', $search_trim);

        /**
         * Attempt a exact match to start of string comparison
         */
        $filters = array();
        $filters[] = "({$attribute}=" . implode('*', $split_search) . "*)";
        $filters = implode($filters);
        $filter = "(&{$filters})";

        try {
            $search_result = $this->run_search($base, $filter, array_values($this->person_attributes), $limit);
        } catch (Exception $e) {
            $result['status']['ok'] = false;
            $result['status']['message'] = $e->getMessage();
            return $result;
        }

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

        if ($result['count'] == $limit) {
            return $result;
        } else {

            /**
             * Run more general search
             */
            $new_limit = $limit - $result['count'];
            $new_result = array();

            $filters = array();
            foreach ($split_search as $s)
            {
                $filters[] = "(|(displayname=*$s*)(brownsisid=$s*)(brownshortid=$s*)(brownnetid=$s*))";
            }
            $filters = implode($filters);
            $filter = "(&$filters)";

            try {
                $search_result = $this->run_search($base, $filter, array_values($this->person_attributes), $new_limit);
            }
            catch (Exception $e)
            {
                return $result;
            }

            $new_result['count'] = array_shift($search_result);

            // get the results
            $new_results = array();
            foreach ( $search_result as $sr )
            {
                $id = $sr['brownshortid'][0];
                $new_results[$id] = $this->parse_person_array($sr);
            }

            $result['count'] += $new_result['count'];
            $result['results'] = array_merge($result['results'], $new_results);
            //$result['status']['ok'] = true;

            return $result;
        }
    }

    /**
     * Search LDAP for people by name.
     *
     * $paffil can be an array of strings or a comma-delimited list.
     *
     * @param string       $s the search term (name to look for)
     * @param int          $limit Maximum number of results to return.  Defaults to 20.
     * @param string|Array $paffil primary_affiliations to limit to.
     * @return array
     */
    public function search_people($s, $limit = 20, $paffil = null, $attribute = 'displayname') {
        $result = array();
        $s_trim = trim($s);

        // search the people objects
        $base = "ou=People,dc=brown,dc=edu";
        $ss = explode(' ', $s_trim);

        /**
         * Attempt a exact match to start of string comparison
         */
        $filters = array();
        $filters[] = "({$attribute}=" . implode('*', $ss) . "*)";

        if ( ! is_null($paffil) ) {
            $affiliations = is_array($paffil) ? $paffil : explode(',', $paffil);
            $afilters = array();
            foreach ($affiliations as $aff) {
                $afilters[] = "(brownprimaryaffiliation=$aff)";
            }
            $filters[] = '(|' . implode('', $afilters) . ')';
        }

        $filters = implode($filters);
        $filter = "(&{$filters})";

        try {
            $search_result = $this->run_search($base, $filter, array_values($this->person_attributes), $limit);
        } catch (Exception $e) {
            $result['status']['ok'] = false;
            $result['status']['message'] = $e->getMessage();
            return $result;
        }

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

        if ($result['count'] == $limit) {
            return $result;
        } else {

            /**
             * Run more general search
             */
            $new_limit = $limit - $result['count'];
            $new_result = array();

            $filters = array();
            $ss = explode(' ', $s);
            foreach ($ss as $s)
            {
                //Probably overkill.  It *should* be ok to just search displayname.
                $filters[] = "(|(displayname=*$s*)(brownsisid=$s*)(brownshortid=$s*)(brownnetid=$s*))";
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
                $search_result = $this->run_search($base, $filter, array_values($this->person_attributes), $new_limit);
            }
            catch (Exception $e)
            {
                return $result;
            }

            $new_result['count'] = array_shift($search_result);

            // get the results
            $new_results = array();
            foreach ( $search_result as $sr )
            {
                $id = $sr['brownshortid'][0];
                $new_results[$id] = $this->parse_person_array($sr);
            }

            $result['count'] += $new_result['count'];
            $result['results'] = array_merge($result['results'], $new_results);
            //$result['status']['ok'] = true;

            return $result;
        }

    }

    /**
     * Provide a more specific person search by allowing us to pass in an 
     * array of attributes
     * 
     * @param array $attributes (ldap attribute name => search value)
     * @param int $limit
     * @throws Exception
     * @return array
     */
    public function search_people_by_attributes($attributes, $limit = 20) {
        $result = array();
        
        if (count($attributes) == 0) {
            throw new Exception('No attributes specified for search');
        }
        
        // search the people objects
        $base = "ou=People,dc=brown,dc=edu";
        
        /**
         * Attempt a exact match to start of string comparison
         */
        $filters = array();
        foreach ($attributes as $key => $value) {
            $filters[] = "({$key}=" . trim($value) . "*)";
        }
        
        
        $filters = implode('', $filters);
        $filter = "(&{$filters})";

        try {
            $search_result = $this->run_search($base, $filter, array_values($this->person_attributes), $limit);
        } catch (Exception $e) {
            $result['status']['ok'] = false;
            $result['status']['message'] = $e->getMessage();
            return $result;
        }

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
    	if( isset( $find_result[0][$attribute] ) && is_array( $find_result[0][$attribute] )) {
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
    	}
    	return $result;
	}

    /**
     * Get course membership for a particular course
     *
     * @author Christopher Keith <Christopher_Keith@brown.edu>
     * @param string course specification
     * @param string person role
     * @return array
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

	    $course_string = "COURSE:{$coursespec}" ;
	    $group = "{$course_string}:{$role}";
	    $base = "ou=People,dc=brown,dc=edu";
	    $filter = "(ismemberof={$group})";
	    $attribute = "ismemberof";

	    $attributes = array(
    		0 => $attribute,
    		1 => 'ismemberof',
		    2 => 'displayname',
		    3 => 'mail',
		    4 => 'brownshortid'
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
			    if( isset( $fr['brownshortid'])) {
				    $result['members'][] = $fr['brownshortid'][0] ;
			    }

				if( $role == '*' ) {
					foreach( $fr['ismemberof'] as $membership_string ) {
						if( strpos($membership_string,$course_string) !== FALSE ) {
							$fields = explode(':', $membership_string) ;
							$membership_group = array_pop($fields) ;
							$result['members'][$membership_group][] = $fr['brownshortid'][0] ;
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
     * @param array $schedule_types include specific schedule types
     * @return array
     */
    public function find_matching_courses($coursespec, array $schedule_types = array()) {

    	// Check for well formed course specification
    	// @todo: implement regular expression matching

    	// Setup find
    	$base = "ou=Courses,dc=brown,dc=edu";
        
        $schedulefilter = NULL;
        if (count($schedule_types) > 0) {
            $schedulefilter = '(|';
            foreach ($schedule_types as $st) {
                $schedulefilter .= "(brownsectionscheduletype={$st})";
            }
            $schedulefilter .= ')';
        }
        
    	$filter = "(&(brownCourseRDN={$coursespec})(objectClass=brownSection){$schedulefilter})";
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

		if( $find_result['count'] > 0 ) {
			return array(
				'status' => array(
					'ok' => true,
					'message' => null,
				),
				'info' => $this->parse_result_array($this->course_attributes, $find_result[0]),
			);
		} else {
			return array(
				'status' => array(
					'ok' => false,
					'message' => 'Empty record'
				)
			) ;
		}
			
    }

    /**
     * Get meeting periods for a given course
     *
     * @param string $coursespec
     * @return array
     * @throws \Exception
     */
    public function get_course_meeting_periods($coursespec) {
        $base = "ou=Courses,dc=brown,dc=edu";
        $filter = "(&(brownCourseRDN={$coursespec})(objectClass=brownSection))";
        
        try {
            $find_result = $this->run_search($base, $filter, array_values($this->course_meeting_attributes));
        } catch (Exception $e) {
            return array(
                'status' => array(
                    'ok' => false,
                    'message' => $e->getMessage(),
                )
            );
        }
        
        if ( $find_result['count'] > 0 ) {
            $data = $this->parse_result_array($this->course_meeting_attributes, $find_result[0]);
            $output = array();
            if ( ! is_array($data['section_period'])) {
                $data['section_period'] = array($data['section_period']);
            }
            foreach ($data['section_period'] as $period) {
                $output[] = $this->get_meeting_period($period);
            }
            return $output;
        } else {
            return array(
                'status' => array(
                    'ok' => false,
                    'message' => 'No meeting periods defined.',
                )
            );
        }
    }
    
    /**
     * Get more detailed information for a meeting period
     * 
     * @param string $periods
     * @return array
     * @throws Exception
     */
    private function get_meeting_period($period) {
        $base = "ou=Courses,dc=brown,dc=edu";
        $filter = "(&({$period})(objectClass=brownSectionPeriod))";
        
        try {
            $find_result = $this->run_search($base, $filter, array_values($this->meeting_period_attributes));
        } catch (Exception $e) {
            throw $e;
        }
        
        if ( $find_result['count'] > 0) {
            $result = $this->parse_result_array($this->meeting_period_attributes, $find_result[0]);
            
            $frequencies = $result['frequency'];
            if ( ! is_array($frequencies)) {
                $frequencies = array($frequencies);
            } 
            
            $result['frequency'] = array();
            foreach ($frequencies as $frequency) {
                $result['frequency'][] = $this->get_meeting_frequency($frequency);
            }
            return $result;
        } else {
            throw new Exception('Invalid LDAP Course meeting period.');
        }
    }
    
    /**
     * Get information about each specific meeting definition
     * 
     * @param string $frequency
     * @return array
     * @throws Exception
     */
    private function get_meeting_frequency($frequency) {
        $base = "ou=Courses,dc=brown,dc=edu";
        $filter = "(&({$frequency})(objectClass=brownSectionFrequency))";
        
        try {
            $find_result = $this->run_search($base, $filter, array_values($this->meeting_frequency_attributes));
        } catch (Exception $e) {
            var_dump($e); die();
        }
        
        if ( $find_result['count'] > 0) {
            $find_result = $this->run_search($base, $filter, array_values($this->meeting_frequency_attributes));
            $result = $this->parse_result_array($this->meeting_frequency_attributes, $find_result[0]);
            return $result;
        } else {
            throw new Exception('Invalid LDAP Course meeting frequency.');
        }
    }
    
    /**
     * Normalize enrollment data by combining non-S** sections so that there is
     * only one listing for the course.
     * 
     * @param type $input result of DOC_Util_LDAP::get_person_enrollment
     * @return array
     */
    public static function normalize_enrollment($input) {
        $_output = array();
        
        if (($input['status']['ok']) && (count($input['courses']) > 0)) {
            /**
             * Process once for all S** enrollments
             */
            foreach ($input['courses'] as $key => $course) {
                $key_parts = explode(':', $key);
                
                if ((isset($key_parts[3])) && (strtoupper(substr($key_parts[3],0,1)) == 'S')) {
                    $_output[$key] = $course;
                    $_output[$key]['additional_sections'] = array();
                } 
            }
            
            /**
             * Process again for all other enrollments
             */
            foreach ($input['courses'] as $key => $course) {
                $key_parts = explode(':', $key);
                $search_key = "{$key_parts[0]}:{$key_parts[1]}:{$key_parts[2]}";
                
                if ((isset($key_parts[3])) && (strtoupper(substr($key_parts[3], 0, 1)) != 'S' )) {
                    $match_found = FALSE;
                    
                    /**
                     * Use by reference for key => value to be able to 
                     * modify the array in the loop
                     */
                    foreach ($_output as $_output_key => &$_output_course) {
                        if (stripos($_output_key, $search_key) !== FALSE) {
                            $match_found = TRUE;
                            $_output_course['additional_sections'][] = $key_parts[3];
                        }
                    }
                    
                    if ( ! $match_found) {
                        $_output[$key] = $course;
                        $_output[$key]['additional_sections'] = array();
                    }
                }
            }
        }
        return $_output;
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
     * @return array
     * @throws \Exception
     */
    private function run_search($base, $filter, $atts = null, $limit = null)
    {
        $conn = is_array($base)
                ? array_fill(0, count($base), $this->cn)
                : $this->cn;

        $search_ref = @ldap_search($conn, $base, $filter, $atts, 0, $limit);

        if (!$search_ref)
            { throw new Exception("LDAP error looking up info for $filter in $base"); }

        if (is_array($search_ref)) {
            $search_result = $this->parse_multiple_results($search_ref);
        } else {
            $this->last_result_rc = $search_ref;
            $search_result = ldap_get_entries($this->cn, $search_ref);
            if (!$search_result)
            { throw new Exception("LDAP error retrieving info for $filter in $base"); }
            ldap_free_result($search_ref);
        }

        return $search_result;
    }

    private function parse_multiple_results($search_ref) {
        $success = false;
        $sub_results = array();
        foreach( $search_ref as $s_ref ) {
            $this->last_result_rc = $s_ref;

            $search_result = ldap_get_entries($this->cn, $s_ref);

            if( $search_result['count']) {
                $sub_results[] = $search_result ;
            }
        }
        if (count($sub_results) == 0) {
            throw new Exception("LDAP error retrieving info for $filter in ".print_r($base, true));
        }
        foreach( $sub_results as $index => $result ) {
            if ($index == 0) {
                $all_results = $result;
                continue;
            }
            $all_results = self::merge_results($all_results, $result);
        }

        return $all_results;
    }

    /**
     * Parse the result of an LDAP query based off an inputted attribute map
     *
     * @author Christopher Keith <Christopher_Keith@brown.edu>
     * @param array $map an associative array of key value pairs
     * @param array $data an associative array returned from LDAP query
     * @return array
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
    			$outp[$attribute] = (is_array($value) || is_object($value)) ? $value : trim($value);
    		} else {
    			$outp[$attribute] = NULL;
    		}
    	}
    	return $outp;
    }

    /**
     * Parse a single result from LDAP about a person.
     *
     * @param array $data an associative array returned from LDAP from a person search.
     * @return array
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

                $outp[$attribute] = (is_array($value) || is_object($value)) ? $value : trim($value);
            } else {
            	$outp[$attribute] = NULL;
            }
       }

       return $outp;
    }

    /**
     * Test if a string matches a pattern
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

    /**
     * @param $results_1
     * @param $results_2
     * @todo generalize this for any number of ldap results
     */
    public static function merge_results($results_1, $results_2) {
        if ($results_1['status']['ok'] || $results_2['status']['ok']) {
            if (!$results_1['status']['ok']) {
                return $results_2;
            } elseif (!$results_2['status']['ok']) {
                return $results_1;
            }

            return array(
                    'count' => $results_1['count'] + $results_2['count'],
                    'results' => array_merge($results_1['results'], $results_2['results']),
                    'status' => $results_1['status']
            );

        } else {
            return $results_1;
        }
    }

}
