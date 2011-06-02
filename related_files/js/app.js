/*
 * _APP will be a global object we can use throughout this application to store
 * properties that we may need. This avoids creating global variables, which can
 * be dangerous. See "Javascript: The Good Parts" section on Global Abatement for
 * additional detail.
 */

var _APP = {} ;

_APP.removeRelatedItem = function removeRelatedItem() {
		var parentDiv = $(this).parent().parent() ;
		parentDiv.children('.related-item-label').addClass('deleted') ;
		parentDiv.children('input[type="hidden"]').val('') ;

		// make sure these are no longer editable...
		parentDiv.children('.related-item-label').each( function() {
			if( $(this).hasClass('edit')) {
				$(this).removeClass('edit') ;
				$(this).editable('disable') ;
			}
			if( $(this).hasClass('editarea')) {
				$(this).removeClass('editarea') ;
				$(this).editable('disable') ;
			}
		}) ;
	} ;

// TODO: merge addRelatedItem and addBudgetItem functionality into a single
// function, perhaps calling addRelatedItem from addBudgetItem.
_APP.addRelatedItem = function addRelatedItem(container, label, hidden_field, hidden_value) {
	var relatedItem = $('<div class="related-item"></div>') ;
	relatedItem.append('<span class="related-item-label">' + label + '</span>') ;
	relatedItem.append('<span class="related-item-link">(<a class="removal-link">remove</a>)</span>') ;
	relatedItem.append('<input type="hidden" name="'+hidden_field+'[]" value="' + hidden_value + '" />') ;

	relatedItem.appendTo(container) ;

	$('.removal-link').bind('click', _APP.removeRelatedItem) ;
}

$(document).ready(function() {


	$( ".datepicker" ).datepicker({
		changeMonth: true,
		changeYear: true
	});

	// a generic confirm handler
	$('.confirmDialog').click(function() {
		return confirm('This action cannot be undone. Are you sure you want to do this?') ;
	});


	/*
	 * Client-side table sorting support. We'll assume that the first column
	 * is the default sort and that we always want zebra striping.
	 */

	/*
    * Extra parser: checkbox
    * Credit: Christian Bach
    * Modified By Bill Beckelman
    */
    $.tablesorter.addParser({
        id: 'input',
        is: function(s) {
            return s.toLowerCase().match(/<input[^>]*checkbox[^>]*/i); ;
        },
        format: function(s) {
            var integer = 0;
            if (s.toLowerCase().match(/<input[^>]*checked*/i)) {
                integer = 1;
            }
            return integer;
        },
        type: "numeric"
    });    

	$('#sortableTable')
		.tablesorter({
			sortList: [[0,0]],
			widgets: ['zebra'],
			debug: false
		}) 
		.tablesorterPager({
			container: $('#pager'),
			positionFixed: false,
			size: 50
		});

	$('form#filter').searchfilter() ;

	/*
	 * Handle the "Check All" checkbox...this assumes only one on a given page.
	 * 
	 * Note the use of $(selector).prop() instead of $(selector).attr() below.
	 * This is for jQuery 1.6 and above. Versions of jQuery earlier than that will
	 * need to use $(selector).attr() instead.
	 */

	 $('#check_all').bind('click', function(){
		 var checkbox_name = $(this).attr('name').replace(/^_/,'') + '[]' ;
		 $('input[name="' + checkbox_name + '"]').prop('checked', $(this).prop('checked')) ;
	 }) ;


	/*
	 * A handler for removing items from a related items list. This is based on
	 * the following structure:
	 *
	 * <div id="__some id__">
	 *     <div class='related-item'>
	 *	       <span class='related-item-label'>__some label__</span>
	 *         <span class='related-item-link'>(<a class='removal-link'>remove</a>)</span>
	 *         <input type='hidden' name='__some_name__[]' value='__some id__' />
	 *     </div>
	 * </div>
	 *
	 * The code here will add the strikeout class to the label and zero out the hidden field's value.
	 *
	 */
	
	$('.removal-link').click(_APP.removeRelatedItem) ;


});