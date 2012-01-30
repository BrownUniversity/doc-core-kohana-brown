(function( $ ) {
	$.fn.searchfilter = function() {
		
		var theForm = this ;
		
		var getGeneralType = function(dataType) {
			if( dataType == 'varchar' || dataType == 'char' || dataType == 'text' || dataType == 'longtext' || dataType == 'mediumtext' || dataType == 'shorttext') {
				return 'text' ;
			}
			if(  dataType == 'date' || dataType == 'datetime' || dataType == 'timestamp' ) {
				return 'date' ;
			}
			if( dataType == 'tinyint' || dataType == 'smallint' || dataType == 'mediumint' || dataType == 'int' || dataType == 'bigint' || dataType == 'float' || dataType == 'double' || dataType == 'decimal' ) {
				return 'numeric' ;
			}
			return 'other' ;
		} ;
		
		var updateGroup = function(container) {
			container.find('.filter_value').hide() ;
			container.find('.filter_value').children().attr('disabled','disabled') ;

			var selected_class = container.find('select[name^="filter_column"]').find('option:selected').attr('class') ;
			if( getGeneralType( selected_class ) == 'date' ) {
				container.find('.filter_date').show() ;
				container.find('.filter_date').removeAttr('disabled') ;
				container.find('.filter_date').children().removeAttr('disabled') ;
				
			} else if( getGeneralType( selected_class ) == 'text') {
				container.find('.filter_text').show() ;
				container.find('.filter_text').children().removeAttr('disabled') ;
				
			} else if( getGeneralType( selected_class ) == 'numeric') {
				container.find('.filter_numeric').show() ;
				container.find('.filter_numeric').children().removeAttr('disabled') ;
			} else {
				container.find('.'+selected_class).show() ;
				container.find('.'+selected_class).children().removeAttr('disabled') ;
			}
		} ;

		var updateManipulators = function() {
			var searchGroups = theForm.find('div[id|="search-group"]') ;
			if( searchGroups.length > 1 ) {
				// show the "remove" buttons and hide all but the last "add" button
				theForm.find('a.remove-search-group').show() ;
				theForm.find('a.add-search-group').hide() ;
				theForm.find('a.add-search-group:last').show() ;
				theForm.find('span.manipulator-placeholder').show() ;
				theForm.find('span.manipulator-placeholder:last').hide() ;
			} else {
				// disable the "remove" buttons and show the "add" button
				theForm.find('a.remove-search-group').hide() ;
				theForm.find('a.add-search-group').show() ;
				theForm.find('span.manipulator-placeholder').hide() ;
			}
		}

		$('div[id|="search-group"]').each( function() {
			updateGroup($(this)) ;
		})
		updateManipulators() ;

		theForm.find('select[name^="filter_column"]').change( function(){
			var container = $(this).closest('div[id|="search-group"]') ;
			updateGroup(container) ;
		}) ;
		
		theForm.find('a.add-search-group').on('click', function() {
			$('#search-group-0').clone(true).appendTo('#search-groups') ;
			updateManipulators() ;
			
		}) ;
		theForm.find('a.remove-search-group').on('click', function() {
			$(this).closest('div[id|="search-group"]').remove() ;
			updateManipulators() ;
		}) ;
		
		
	} ;
})( jQuery ) ;