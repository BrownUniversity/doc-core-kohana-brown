<?php
namespace BrownUniversity\DOC\Log ;
/**
 * @package BrownConnect
 * @author Christopher Keith <Christopher_Keith@brown.edu>
 */

use BrownUniversity\DOC\Util\Slack as Util_Slack;
use Kohana\Kohana;
use Kohana\Log;
use Kohana\Log\Writer;
use Kohana\Minion\Minion\CLI;
use Kohana\Request;

/**
 * Slack Logger
 */
class Slack extends Writer {

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
     *
     * @param null $environment
     * @param null $app
     */
    public function __construct($environment = NULL, $app = NULL) {

        $this->application = $app;
        $this->environment = $environment;
    }

    /**
     * Send email messages to Slack channel for a
     * pre-configured set of error level conditions
     *
     * @uses Util_Slack::send
     * @param array $messages
     * @throws \Kohana\KohanaException
     */
    public function write(array $messages) {

        // Add supplemental information to the error text
        $supp_info = Request::user_agent(array('browser', 'version', 'robot', 'mobile', 'platform'));

        $request = Request::current();
        if ((is_a($request, 'Request'))) {
            $error_prefix = "Route: " . Request::current()->uri();
        } else {
            $error_prefix = "Route: could not be determined.";
        }

        if (Kohana::$is_cli) {
            $options = CLI::options('task', 'data') ;
            if (isset($options['task'])) {
                $error_prefix .= "\nTask: " . $options['task'];
            }
            if (isset($options['data'])) {
                $error_prefix .= "\nData: " . json_encode($options['data']);
            }
        } else {
            $error_prefix .= "\nIP Address: " . Request::$client_ip;
            $error_prefix .= "\nBrowser: " . $supp_info['browser'];
            $error_prefix .= "\nVersion: " . $supp_info['version'];
            $error_prefix .= "\nPlatform: " . $supp_info['platform'];
            $error_prefix .= "\nMobile: " . $supp_info['mobile'];
            $error_prefix .= "\nRobot: " . $supp_info['robot'];
        }

        $url = Kohana::$config->load('slack.url');

        foreach ($messages as $message) {

            // Skip 404 errors
            if (strpos($message['body'], 'Code_404') !== FALSE) {
                continue;
            }

            $msg = "[ {$this->environment} | {$this->application} ] -  " . self::$levels[$message['level']];
            $msg .= "\n\n" . $error_prefix . "\n\n" . $message['body'];

            try {
                Util_Slack::send($url, $msg);
            } catch (\Exception $e) {
                // Avoid Endless error loop
            }
        }
    }
}

// End Log_Slack