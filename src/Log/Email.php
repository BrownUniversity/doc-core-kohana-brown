<?php
namespace BrownUniversity\DOC\Log ;
/**
 * @package DOC Core
 */
use BrownUniversity\DOC\Util\Mail;

defined('SYSPATH') or die('No direct script access.');


/**
 * Kohana Email Log writer
 * 
 * @author Christopher Keith <Christopher_Keith@brown.edu>
 */
class Email extends \Log_Writer {

    /**
     * Array of email addresses keyed by log level constants defined in the
     * Kohana_Log classes
     * 
     * @var array
     */
    protected $addresses;
    
    /**
     * Application in which the error occured
     * 
     * @var string
     */
    protected $application;
    
    /**
     * Environment in which the error occured
     * 
     * @var string
     */
    protected $environment;
    
    /**
     * Address from which this notification should appear
     * 
     * @var string
     */
    protected $from;
    
    /**
     * Lookup table for error levels
     * 
     * @var array
     */
    protected static $levels = array(
        \Kohana_Log::EMERGENCY => 'EMERGENCY',
        \Kohana_Log::ALERT     => 'ALERT',
        \Kohana_Log::CRITICAL  => 'CRITICAL',
        \Kohana_Log::ERROR     => 'ERROR',
        \Kohana_Log::WARNING   => 'WARNING',
        \Kohana_Log::NOTICE    => 'NOTICE',
        \Kohana_Log::INFO      => 'INFO',
        \Kohana_Log::DEBUG     => 'DEBUG',
        \Kohana_Log::STRACE    => 'STRACE',
    );
    
    /**
     * Class constructor override to facilitate configuration mapping
     */
    public function __construct($environment = NULL, $app = NULL) {
        
        $this->addresses = \Kohana::$config->load('logemail.recipients');
        $this->application = $app;
        $this->environment = $environment;
        $this->from = \Kohana::$config->load('logemail.from');
    }
    
    /**
     * Send email messages to a pre-configured list of users for a 
     * pre-configured set of error level conditions
     * 
     * @uses DOC_Util_Mail
     * @param array $messages
     */
    public function write(array $messages) {
        
        // Add supplemental information to the error text
		$supp_info = \Request::user_agent(array('browser', 'version', 'robot', 'mobile', 'platform'));
        
        $request = \Request::current();
        if ((is_a($request, 'Request'))) {
            $error_prefix = "Route: " . \Request::current()->uri();
        } else {
            $error_prefix = "Route: could not be determined.";
        }
        
		$error_prefix .= "\nIP Address: " . \Request::$client_ip;
		$error_prefix .= "\nBrowser: " . $supp_info['browser'];
		$error_prefix .= "\nVersion: " . $supp_info['version'];
		$error_prefix .= "\nPlatform: " . $supp_info['platform'];
		$error_prefix .= "\nMobile: " . $supp_info['mobile'];
		$error_prefix .= "\nRobot: " . $supp_info['robot'];
			
        foreach ($messages as $message) {
            $recipients = $this->addresses[ self::$levels[ $message[ 'level' ]]];
			$subject = "[ {$this->environment} | {$this->application} ] -  " . self::$levels[$message['level']];
			$body = \Text::auto_p($error_prefix) . \Text::auto_p($message['body']);
            
            /**
             * Only actually email the error if it is not a 404, And recipients
             * have been defined for this log level
             */
            if ((is_array($recipients)) &&
                (count($recipients) > 0) && 
                (strpos($message['body'], 'HTTP_Exception_404') === FALSE))
            {
                $result = Mail::send($subject, $body, $recipients, NULL, $this->from);
            }
        }
    }
    
}

// End DOC_Log_Email