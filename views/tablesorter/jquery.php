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
	if( !isset( $render_as )) {
		$render_as = DOC_Helper_Table::RENDER_AS_TABLE ;
	}
	if( !isset( $default_sort )) {
		$default_sort = '[[0,0]]' ;
	}
	if( !isset( $editable_columns )) {
		$editable_columns = array() ;
	}
	if( !isset( $theme )) {
		if( defined( 'JQUERY_TABLESORTER_THEME' )) {
			$theme = JQUERY_TABLESORTER_THEME ;
		} else {
			$theme = 'default' ;
		}
	}
	if( !isset( $include_render_options )) {
		$include_render_options = FALSE ;
	}
	
	
	$widgets_list = "'zebra','resizable','saveSort','stickyHeaders'" ;
	if( count( $editable_columns ) > 0 ) {
		$widgets_list .= ",'editable'" ;
	}
	
?>

<script language="javascript">

	$(document).ready( function() {
		$('#<?php print( $table_id ) ; ?>')
		<?php if( $render_as == DOC_Helper_Table::RENDER_AS_TABLE ) { ?>
					.tablesorter({
						sortList: <?php print( $default_sort ) ; ?>,
						widgets: [<?php print( $widgets_list ) ; ?>],
						debug: false,
						theme: '<?php print( $theme ) ; ?>',
						widgetOptions: {
							<?php 
								if( count( $editable_columns ) > 0 ) {
									print('editable_columns: ['.implode(',',$editable_columns).']');
								}
							?>
						}
					})

					<?php if( $no_pager == FALSE ) { ?>

						.tablesorterPager({
							container: $('#<?php print( $pager_id ) ; ?>'),
							positionFixed: false,
							size: 50,
							output: "{startRow} to {endRow} ({totalRows})",
							pagerArrows: true
						})

					<?php } ?>

		<?php } else { ?>
					.pager({
						data_selector:'.row-equiv',
						pager_container: $('#<?php print($pager_id); ?>')
					})
		<?php } ?>

			;
	/*
	 * Handle the "Check All" checkbox.
	 */

		$('#<?php print( $table_id ) ; ?> .check_all').on('click', function() {
			var container = $(this).closest('table') ;
			var checkbox_name = $(this).attr('name').replace(/^_/,'') + '[]' ;
			var new_state = $(this).prop('checked') ;
			container.find('input[name="' + checkbox_name + '"]').each(function(){
				if( $(this).prop('disabled') !== true ) {
					$(this).prop('checked', new_state) ; 
				}
			});
		}) ;

	<?php if( $include_render_options ) { ?>
		/*
		 * Need to include handlers here to set the render options and refresh the page.
		 * We'll need to look for the presence of our standard search filter interface and
		 * trigger a submit if we find it, otherwise just refresh the page...
		 */
		 $('.render-selector').on('click',function(){
		 	var filter_form = $('form#filter') ;
		 	var data = {} ;
		 	data.uri = '<?php print(Request::detect_uri()); ?>' ;
		 	if( $(this).hasClass('render-table')) {
		 		data.render_as = '<?php print( DOC_Helper_Table::RENDER_AS_TABLE ) ; ?>' ;
		 	} else {
		 		data.render_as = '<?php print( DOC_Helper_Table::RENDER_AS_GRID ) ; ?>' ;
		 	}
		 	$.ajax({
				type: 'POST',
				url: _APP.approot + 'rest/render/set',
				cache: false,
				data: data,
				dataType: 'json',
				success: function() {
					if( filter_form.length == 1 ) {
						filter_form.submit() ;
					} else {
						location.reload() ;
					}
				}
			}) ;
		 });
	
	
	<?php } ?>

	}) ;


</script>