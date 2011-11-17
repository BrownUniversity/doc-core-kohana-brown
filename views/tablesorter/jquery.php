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
			<?php if( $no_pager == FALSE ) { ?>

				.tablesorterPager({
					container: $('#<?php print( $pager_id ) ; ?>'),
					positionFixed: false,
					size: 50
				})

			<? } ?>
			;
	}) ;


</script>