<?php
	if( !isset( $div_id )) {
		$div_id = 'datatable' ;
	}
	if( !isset( $div_class )) {
		$div_class = 'datatable' ;
	}
	if( !isset( $table_id )) {
		$table_id = 'sortableTable' ;
	}
	if( !isset( $pager_id )) {
		$pager_id = 'pager' ;
	}
	if( !isset( $no_pager )) {
		$no_pager = FALSE ;
	}

	$table = new DOC_Helper_Table( $data, $column_specs, $table_attributes ) ;

	print( "<div id='{$div_id}' class='{$div_class}'>" ) ;
	print( $table->render() ) ;
	print( "</div>" ) ;

	if( count( $data ) > 0 ) {
		if( $no_pager == FALSE ) {
			$pager = View::factory('tablesorter/pager') ;
			$pager->pager_id = $pager_id ;
			print( $pager->render() ) ;
		}
		$jquery = View::factory('tablesorter/jquery') ;
		$jquery->table_id = $table_id ;
		$jquery->pager_id = $pager_id ;
		$jquery->no_pager = $no_pager ;
		print( $jquery->render() ) ;
	}

?>