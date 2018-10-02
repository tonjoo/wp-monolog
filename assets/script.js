jQuery(function($){

	$('.log-container').each(function() {
		if ( $(this).height() > 60 ) {
			$(this).addClass('truncated');
		}
	});

	$('.log-table').on('click', '.truncate-toggle', function() {
		$(this).parents('.log-container').toggleClass('expanded');
	});

});