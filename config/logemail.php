<?php
/**
 * Default configuration file for email log writing
 * 
 * @package DOC Core
 */
defined('SYSPATH') or die('No direct script access.');

return array(
    'from' => 'email@example.edu',
    'recipients' => array(
        Log::EMERGENCY => array(

        ),
        Log::ALERT => array(

        ),
        Log::CRITICAL => array(

        ),
        Log::ERROR => array(
            
        ),
        Log::WARNING => array(

        ),
        Log::NOTICE => array(

        ),
        Log::INFO => array(

        ),
        Log::DEBUG => array(

        ),
        Log::STRACE => array(

        ),
    ),
);
// End Log_Email default configuration file