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
	if( !isset( $no_jquery )) {
		$no_jquery = FALSE ;
	}
	if( !isset( $context )) {
		$context = DOC_Helper_Table::CONTEXT_WEB ;
	}
	if( !isset( $table_attributes )) {
		$table_attributes = NULL ;
	}
	if( !isset( $theme )) {
		if( defined( 'JQUERY_TABLESORTER_THEME' )) {
			$theme = JQUERY_TABLESORTER_THEME ;
		} else {
			$theme = 'default' ;
		}
	}

	// determine the default sort from the column_specs
	$sorts = array('asc' => '0', 'desc' => '1') ;
	$col_index = 0 ;
	$default_sort = array() ;

	foreach( $column_specs as $col ) {
		if( isset( $col['sort'] )) {
			$sort_index = isset( $col['sort']['priority'] ) ? $col['sort']['priority'] : count($default_sort) ;
			$default_sort[ $sort_index ] = "[{$col_index},{$sorts[$col['sort']['dir']]}]" ;
		}
		if( (!isset( $col['context'] ) || $col['context'] == DOC_Helper_Table::CONTEXT_WEB) &&
			isset( $col['type']) && $col['type'] == DOC_Helper_Table::TYPE_DATA )
		{
			$col_index++ ;
		}
	}
	if( count( $default_sort ) > 0 ) {
		ksort($default_sort) ;
		$default_sort = '[' . implode(',',$default_sort) . ']' ;
	} else {
		$default_sort = '[[0,0]]' ;
	}


	$table = new DOC_Helper_Table( $data, $column_specs, $table_attributes, $context ) ;

	print( "<div id='{$div_id}' class='{$div_class}'>" ) ;
	print( $table->render() ) ;
	print( '<div id="supplemental-'.$table_id.'" class="supplemental-table"></div>' ) ;
	print( "</div>" ) ;

	if( count( $data ) > 0 ) {
		if( $no_jquery == FALSE ) {
			if( $no_pager == FALSE ) {
				$pager = View::factory('tablesorter/pager') ;
				$pager->pager_id = $pager_id ;
				print( $pager->render() ) ;
			}
			$jquery = View::factory('tablesorter/jquery') ;
			$jquery->table_id = $table_id ;
			$jquery->pager_id = $pager_id ;
			$jquery->no_pager = $no_pager ;
			$jquery->default_sort = $default_sort ;
			$jquery->theme = $theme ;
			print( $jquery->render() ) ;

		}

		$supplemental = View::factory('tablesorter/supplemental') ;
		$supplemental->table_id = $table_id ;
		print( $supplemental->render() ) ;

	}
