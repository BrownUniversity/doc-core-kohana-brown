<?php

if( isset( $new_object_link ) && is_array( $new_object_link )) {
	$action_str = "<a href='".\Kohana\Kohana::$base_url . $new_object_link['url_fragment'] ."'" ;
	if( isset( $new_object_link[ 'class' ]) && !empty( $new_object_link[ 'class' ])) {
		$action_str .= " class='{$new_object_link['class']}'" ;
	}
	$action_str .= ">{$new_object_link['name']}</a>" ;

	print("<div id='newObjectLink'>{$action_str}</div>") ;
}
