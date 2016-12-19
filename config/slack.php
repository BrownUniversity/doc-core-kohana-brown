<?php
defined('SYSPATH') or die('No direct access allowed.');

/**
 * Slack API Configuration file
 *
 * @package DOC Core
 * @version 1.0
 * @since 1.0
 */
if( file_exists( APPPATH . 'config/myConfig.php' )) include_once( APPPATH . 'config/myConfig.php' ) ;
if( file_exists( dirname( __FILE__ ) . '/myConfig.php' )) include_once( dirname( __FILE__ ) . '/myConfig.php') ;

return array(
	'url' => 'https://hooks.slack.com/services/T000000000/XXXXXXXXX/xxxxxxxxxx'
) ;