<?php
namespace BrownUniversity\DOC\Util ;
/**
 * @package DOC Core
 * @version 1.0
 * @since 1.0
 * @author Christopher Keith <Christopher_Keith@brown.edu>
 */
use Kohana\KohanaException;

defined( 'SYSPATH' ) or die( 'No direct script access' );

/**
 * LTI Utilitiy Class
 */
class Lti {

    /**
     * Parse the "lise_course_offering_sourcedid" value sent as part of an
     * LTI launch request into an array that is more usable for program code.
     *
     * @param type $input
     * @return array
     * @throws \Kohana\KohanaException
     */
    public static function parse_canvas_course_sisid($input) {
       
       $parts = explode('.', $input);
        
       /**
        * Course string should explode into 5 parts
        * 
        * e.g. brown.itg.001.2013-spring.s01
        */
       if (count($parts) <> 5) {
           
           $msg = 'Invalid course specified in LTI launch request.';
           if (count($parts) == 4) {
               $msg = ('This LTI module does not currently support combined sections.');
           }
           
           throw new KohanaException($msg);
       }
       
       $subject = strtoupper($parts[1]);
       $number = strtoupper($parts[2]);
       $term = substr($parts[3], 0, 5) 
             . strtoupper(substr($parts[3], 5, 1)) 
             . substr($parts[3], 6, strlen($parts[3]) - 5);
        
       $section = (count($parts) == 5) ? strtoupper($parts[4]) : 'multi';
       
       
       return array(
           'subject' => $subject,
           'course_number' => $number,
           'term' => $term,
           'section' => $section,
           'coursespec' => "{$subject}:{$number}:{$term}:{$section}",
       );

    }
}

// End Util_Lti