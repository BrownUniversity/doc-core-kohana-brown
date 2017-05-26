<?php
// Deprecated

/*
 * TODO: Implement a way to specify actions for each row dynamically. Perhaps use the config structure?
 */

// hand off to the new object link view

if( isset( $new_object_link )) {

	$newObjectLink = \BrownUniversity\DOC\View::factory('newobjectlink') ;
	$newObjectLink->new_object_link = $new_object_link ;
	echo $newObjectLink->render() ;
}

// build the filter form

if( isset( $filter_fields ) && isset( $filter_model)) {
	$search_filter = \BrownUniversity\DOC\View::factory('searchfilter') ;
	$search_filter->filter_fields = $filter_fields ;
	$search_filter->filter_model = $filter_model ;
	echo $search_filter->render() ;	
}

// crank out the table

if( count( $table_data ) > 0 ) {
	print("<div id='datatable'>");
	$table = new Util_Table($table_data, 'id="sortableTable", class="tablesorter"') ;

	if( isset( $action_specs )) {
		$table->add_actions_column($action_specs) ;
	}

	if( isset( $column_titles )) {
		$table->set_column_titles( $column_titles ) ;
	}

	if( isset( $column_filter )) {
		$table->set_column_filter( $column_filter ) ;
	}

	if( isset( $column_header_classes )) {
		$table->set_header_classes( $column_header_classes ) ;
	}

	if( isset( $format_specs )) {
		$table->set_formats($format_specs) ;
	}
	$table->render(TRUE) ;

	$pager = View::factory('pager') ;
	echo $pager->render() ;

	print("</div>") ;
} else {
	print("<div class='nodata'>No results found.</div>") ;
}

?>