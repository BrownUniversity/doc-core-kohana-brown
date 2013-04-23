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
        'EMERGENCY' => array(),
        'ALERT' => array(),
        'CRITICAL' => array(),
        'ERROR' => array(),
        'WARNING' => array(),
        'NOTICE' => array(),
        'INFO' => array(),
        'DEBUG' => array(),
        'STRACE' => array(),
    ),
);
// End Log_Email default configuration file