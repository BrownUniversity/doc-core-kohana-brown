<?php
/**
 * @package DOC Core
 * @version 1.0
 * @since 1.0
 * @author Christopher Keith <Christopher_Keith@brown.edu>
 */

/**
 * Extending the base Kohana Request class as a way to globally add the F5 IP
 * addresses to the Request::$trusted_proxies configuration.  This is required
 * to take advantage of assigning the IP address from the $_SERVER['HTTP_X_FORWARDED_FOR']
 * to the Request::$client_ip property.
 */
class Request extends Kohana_Request {
    
    public static $trusted_proxies = array(
        '127.0.0.1', 
        'localhost', 
        'localhost.localdomain',
        '10.55.28.252', // Production F5
        '10.55.28.249', // Development F5
    );
}