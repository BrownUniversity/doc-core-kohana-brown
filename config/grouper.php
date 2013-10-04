<?php
/**
 * MACE Grouper API Configuration
 *
 * @package DOC Core
 * @version 1.0
 * @since 1.0
 * @author Christopher Keith <Christopher_Keith@brown.edu>
 */
if( file_exists( APPPATH . 'config/myConfig.php' )) include_once( APPPATH . 'config/myConfig.php' ) ;

defined('GROUPER_WS_WSDL') OR define('GROUPER_WS_WSDL', 'https://grouper.example.edu/GrouperService.wsdl');
defined('GROUPER_WS_LOGIN') OR define('GROUPER_WS_LOGIN', '***REMOVED***');
defined('GROUPER_WS_PASSWORD') OR define('GROUPER_WS_PASSWORD', '***REMOVED***');
defined('GROUPER_WS_VERSION') OR define('GROUPER_WS_VERSION', '1.6.2');
defined('GROUPER_REST_BASE') OR define('GROUPER_REST_BASE', 'https://grouper.example.edu/grouper-ws/servicesRest/json/v1_6_3/');

return array(
    'GROUPER_WS_WSDL'     => GROUPER_WS_WSDL,
    'GROUPER_WS_LOGIN'    => GROUPER_WS_LOGIN,
    'GROUPER_WS_PASSWORD' => GROUPER_WS_PASSWORD,
    'GROUPER_WS_VERSION'  => GROUPER_WS_VERSION,
    'GROUPER_REST_BASE'   => GROUPER_REST_BASE,
);