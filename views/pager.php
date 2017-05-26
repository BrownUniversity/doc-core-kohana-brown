<?php
	/*
	 * This view should be considered deprecated. Its functionality is moving
	 * into tablesorter/pager, which organizationally makes more sense.
	 */

	$pager = \BrownUniversity\DOC\View::factory('tablesorter/pager') ;
	if( isset( $pager_id )) {
		$pager->pager_id = $pager_id ;
	}
	echo $pager->render() ;
?>