<?php
/**
 * MACE Grouper API Configuration
 *
 * @package DOC Core
 * @version 1.0
 * @since 1.0
 * @author Christopher Keith <Christopher_Keith@brown.edu>
 */

defined('SYSPATH') or die('No direct access allowed.');

return array(
    'GROUPER_WS_WSDL'     => 'https://groups.example.edu/GrouperService.wsdl',
    'GROUPER_WS_LOGIN'    => 'login-id',
    'GROUPER_WS_PASSWORD' => 'password',
    'GROUPER_WS_VERSION'  => '1.6.2',
    'GROUPER_REST_BASE'   => 'https://groups.example.edu/grouper-ws/servicesRest/json/v1_6_3',
);