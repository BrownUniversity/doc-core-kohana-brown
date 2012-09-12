<?php
	if( !isset( $table_id )) {
		$table_id = 'sortableTable' ;
	}
?>

<script language="javascript">

	$(document).ready( function() {
		var table_id = '<?php print( $table_id ) ; ?>' ;
		var supplemental_id = 'supplemental-' + table_id ;

		$('#' + table_id).on('click','.supplement-view',function(){
			var row = $(this).closest('tr') ;
			if( row.hasClass('supplemental-highlight')) {
				$('#' + supplemental_id).hide() ;
				row.removeClass('supplemental-highlight') ;
			} else {
				var supplemental_data = $(this).closest('td').data('supplement') ;
				var supplemental_data_value ;
				$(this).closest('table').find('tr').removeClass('supplemental-highlight') ;

				// create the table
				var table = $('<table></table>') ;
				var table_body = $('<tbody></tbody>') ;

				for( var i = 0; i < supplemental_data.length; i++ ) {
					supplemental_data_value = supplemental_data[i].value == null ? '' : supplemental_data[i].value ;
					table_body.append('<tr><th>' + supplemental_data[i].heading + '</th><td>' + supplemental_data_value + '</td></tr>') ;
				}

				table.append(table_body) ;
				$('#'+supplemental_id).html( table ) ;


				row.addClass('supplemental-highlight') ;
				var supplemental_left = $(this).position().left - $('#'+supplemental_id).width() - 4 ;
				var supplemental_top = $(this).position().top + $(this).height() ;

				$('#'+supplemental_id).css({ "left": supplemental_left + "px", "top": supplemental_top + "px" }) ;
				$('#'+supplemental_id).show() ;
			}



		}) ;
	}) ;
</script>

