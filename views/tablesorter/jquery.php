<?php
	if( !isset( $table_id )) {
		$table_id = 'sortableTable' ;
	}
	if( !isset( $pager_id )) {
		$pager_id = 'pager' ;
	}
	if( !isset( $no_pager )) {
		$no_pager = FALSE ;
	}
?>

<script language="javascript">

	$(document).ready( function() {
		$('#<?php print( $table_id ) ; ?>')
			.tablesorter({
				sortList: [[0,0]],
				widgets: ['zebra'],
				debug: false
			})
// 			.on('sortEnd', function(sorter) {
// 				// capture the current sort order for the table
// 				_APP.currentSort = sorter.target.config.sortList ;
// 			})
			<?php if( $no_pager == FALSE ) { ?>

				.tablesorterPager({
					container: $('#<?php print( $pager_id ) ; ?>'),
					positionFixed: false,
					size: 50,
					output: "{startRow} to {endRow} ({totalRows})",
					pagerArrows: true
				})

			<? } ?>
			;
	/*
	 * Handle the "Check All" checkbox.
	 */

		 $('#<?php print( $table_id ) ; ?> .check_all').on('click', function() {
			 var container = $(this).closest('table') ;
			 var checkbox_name = $(this).attr('name').replace(/^_/,'') + '[]' ;
			 container.find('input[name="' + checkbox_name + '"]').prop('checked', $(this).prop('checked')) ;
		 }) ;

	}) ;


</script>