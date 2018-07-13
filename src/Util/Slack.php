<?php
namespace BrownUniversity\DOC\Util ;
/**
 * @package DOC core
 * @version 1.0
 * @since 1.0
 */

class Slack {
	/**
     * Send a message to a slack channel
     *
     * @param string $url
     * @param string $message
     */
    public static function send($url, $message)
    {
        $msg = array(
            'text' => $message,
        );

        $opts = array('http' =>
            array(
                'method'  => 'POST',
                'header'  => 'Content-type: application/x-www-form-urlencoded',
                'content' => json_encode($msg)
            )
        );

        $context  = stream_context_create($opts);

        // Intentionally surpressing errors as errors are not important in this context
        @file_get_contents($url, false, $context);
    }
}