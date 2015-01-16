<?php

defined('SYSPATH') OR die('No direct access allowed.');

if( file_exists( APPPATH . 'config/myConfig.php' )) include_once( APPPATH . 'config/myConfig.php' ) ;
if( file_exists( dirname( __FILE__ ) . '/myConfig.php' )) include_once( dirname( __FILE__ ) . '/myConfig.php') ;

return array(
	'storage_class' => 'DOC_Util_Filter_Storage_Session'
) ;