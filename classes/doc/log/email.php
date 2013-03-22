<?php
/**
 * @package DOC Core
 */
defined('SYSPATH') or die('No direct script access.');


/**
 * Kohana Email Log writer
 * 
 * @author Christopher Keith <Christopher_Keith@brown.edu>
 */
class DOC_Log_Email extends Log_Writer {

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
        Log::EMERGENCY => 'EMERGENCY',
        Log::ALERT     => 'ALERT',
        Log::CRITICAL  => 'CRITICAL',
        Log::ERROR     => 'ERROR',
        Log::WARNING   => 'WARNING',
        Log::NOTICE    => 'NOTICE',
        Log::INFO      => 'INFO',
        Log::DEBUG     => 'DEBUG',
        Log::STRACE    => 'STRACE',
    );
    
    /**
     * Class constructor override to facilitate configuration mapping
     */
    public function __construct($environment = NULL, $app = NULL) {
        
        $this->addresses = Kohana::$config->load('logemail.recipients');
        $this->application = $app;
        $this->environment = $environment;
        $this->from = Kohana::$config->load('logemail.from');
    }
    
    /**
     * Send email messages to a pre-configured list of users for a 
     * pre-configured set of error level conditions
     * 
     * @uses DOC_Util_Mail
     * @param array $messages
     */
    public function write(array $messages) {
        
        foreach ($messages as $message) {
            
            $recipients = $this->addresses[$message['level']];
            foreach ($recipients as $recipient) {
                $subject = "[ {$this->environment} | {$this->application} ] -  " . self::$levels[$message['level']];
                $body = text::auto_p($message['body']);
                $result = DOC_Util_Mail::send($subject, $body, $recipients);
            }
        }
    }
    
}

// End DOC_Log_Email