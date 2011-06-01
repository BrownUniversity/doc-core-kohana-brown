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
		
		var updateForm = function() {
			theForm.find('.filter_value').hide() ;
			theForm.find('.filter_value').children().attr('disabled','disabled') ;

			var selected_class = $('form select[name="filter_column"]').find('option:selected').attr('class') ;
			if( getGeneralType( selected_class ) == 'date' ) {
				theForm.find('.filter_date').show() ;
				theForm.find('.filter_date').removeAttr('disabled') ;
				theForm.find('.filter_date').children().removeAttr('disabled') ;
				
			} else if( getGeneralType( selected_class ) == 'text') {
				theForm.find('.filter_text').show() ;
				theForm.find('.filter_text').children().removeAttr('disabled') ;
				
			} else if( getGeneralType( selected_class ) == 'numeric') {
				theForm.find('.filter_numeric').show() ;
				theForm.find('.filter_numeric').children().removeAttr('disabled') ;
			} else {
				theForm.find('.'+selected_class).show() ;
				theForm.find('.'+selected_class).children().removeAttr('disabled') ;
				
			}
			
		} ;

		updateForm() ;

		theForm.find('select[name="filter_column"]').change( function(){
			updateForm() ;
		}) ;
		
		
	} ;
})( jQuery ) ;